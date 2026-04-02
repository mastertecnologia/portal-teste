<?php
namespace App\Service;

use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;

/**
 * Integração Autentique v2 (GraphQL + multipart upload).
 * Doc: https://docs.autentique.com.br/api/2
 */
class AutentiqueService {

	protected const DEFAULT_GRAPHQL_URL = 'https://api.autentique.com.br/v2/graphql';

	/** @return bool */
	public function isEnabled() {
		return (bool)Configure::read('Contract.autentique.enabled');
	}

	/**
	 * Cria documento na Autentique e devolve id + assinaturas (links por signatário).
	 *
	 * @param \Cake\Datasource\EntityInterface $contract
	 * @param \Cake\Datasource\EntityInterface[] $signatories
	 * @return array{id:?string,signatures:array,errors:array,message:?string}
	 */
	public function criarDocumento(EntityInterface $contract, string $pdfPath, array $signatories) {
		$result = ['id' => null, 'signatures' => [], 'errors' => [], 'message' => null];
		if (!$this->isEnabled()) {
			$result['message'] = 'Autentique desligado.';

			return $result;
		}
		$key = $this->getApiKey();
		if ($key === '') {
			$result['errors'][] = ['message' => 'CONTRACT_AUTENTIQUE_API_KEY não configurada.'];

			return $result;
		}
		if (!is_file($pdfPath) || !is_readable($pdfPath)) {
			$result['errors'][] = ['message' => 'PDF inexistente ou ilegível: ' . $pdfPath];

			return $result;
		}

		$signersPayload = $this->buildSignersPayload($signatories);
		if ($signersPayload === []) {
			$result['errors'][] = ['message' => 'Nenhum signatário válido para envio.'];

			return $result;
		}

		$name = trim((string)$contract->get('name'));
		if ($name === '') {
			$name = 'Contrato ' . (string)($contract->get('code') ?: $contract->get('id'));
		}

		$sandbox = (bool)Configure::read('Contract.autentique.sandbox');
		$mutation = 'mutation CreateDocumentMutation($document: DocumentInput!, $signers: [SignerInput!]!, $file: Upload!, $sandbox: Boolean!) {
  createDocument(sandbox: $sandbox, document: $document, signers: $signers, file: $file) {
    id
    name
    signatures {
      public_id
      name
      email
      link { short_link }
    }
  }
}';

		$variables = [
			'document' => ['name' => $name],
			'signers' => $signersPayload,
			'file' => null,
			'sandbox' => $sandbox,
		];

		$decoded = $this->graphqlMultipart($mutation, $variables, $pdfPath);
		if ($decoded === null) {
			$result['errors'][] = ['message' => 'Resposta vazia da API Autentique.'];

			return $result;
		}
		if (!empty($decoded['errors'])) {
			$result['errors'] = $decoded['errors'];

			return $result;
		}

		$created = $decoded['data']['createDocument'] ?? null;
		if (!is_array($created) || empty($created['id'])) {
			$result['errors'][] = ['message' => 'createDocument sem id na resposta.'];

			return $result;
		}

		$result['id'] = (string)$created['id'];
		$result['signatures'] = is_array($created['signatures'] ?? null) ? $created['signatures'] : [];

		return $result;
	}

	/**
	 * @param string $documentId
	 * @return array{status:string, signed_url?:string}
	 */
	public function statusDocumento($documentId) {
		if (!$this->isEnabled() || $documentId === '') {
			return ['status' => 'unknown'];
		}
		$q = 'query DocStatus($id: ID!) {
  document(id: $id) {
    id
    signed_count
    signatures_count
    rejected_count
    files { signed }
  }
}';
		$decoded = $this->graphqlJson($q, ['id' => $documentId]);
		if ($decoded === null || !empty($decoded['errors'])) {
			return ['status' => 'unknown'];
		}
		$doc = $decoded['data']['document'] ?? null;
		if (!is_array($doc)) {
			return ['status' => 'unknown'];
		}

		$signedUrl = isset($doc['files']['signed']) ? (string)$doc['files']['signed'] : '';
		$sc = (int)($doc['signed_count'] ?? 0);
		$tc = (int)($doc['signatures_count'] ?? 0);

		if ($tc > 0 && $sc >= $tc) {
			$out = ['status' => 'signed'];
			if ($signedUrl !== '') {
				$out['signed_url'] = $signedUrl;
			}

			return $out;
		}

		return ['status' => 'pending'];
	}

	/**
	 * @param string $documentId
	 * @param string $targetPath Caminho absoluto do PDF assinado.
	 * @return bool
	 */
	public function downloadSignedPdf($documentId, $targetPath) {
		if (!$this->isEnabled() || $documentId === '') {
			return false;
		}
		$st = $this->statusDocumento($documentId);
		$url = $st['signed_url'] ?? '';
		if ($url === '') {
			$q = 'query DocFiles($id: ID!) { document(id: $id) { files { signed } } }';
			$decoded = $this->graphqlJson($q, ['id' => $documentId]);
			if (is_array($decoded) && empty($decoded['errors'])) {
				$url = (string)($decoded['data']['document']['files']['signed'] ?? '');
			}
		}
		if ($url === '') {
			return false;
		}

		return $this->downloadUrlToFile($url, $targetPath);
	}

	/**
	 * Valida assinatura HMAC (header x-autentique-signature, SHA-256 hex).
	 * Sem secret configurado: aceita apenas para desenvolvimento.
	 *
	 * @param string $payload Corpo bruto
	 * @param string $signature Valor do header
	 * @return bool
	 */
	public function validarWebhook($payload, $signature) {
		$secret = (string)Configure::read('Contract.autentique.webhook_secret');
		if ($secret === '') {
			return true;
		}
		if ($signature === '') {
			return false;
		}
		$expected = hash_hmac('sha256', (string)$payload, $secret);

		return hash_equals($expected, $signature);
	}

	/**
	 * Processa payload oficial do webhook (objeto envelope com "event": { type, data }).
	 *
	 * @param array $webhookPayload
	 * @return void
	 */
	public function applyWebhookEvent(array $webhookPayload) {
		$parsed = $this->parseWebhookEnvelope($webhookPayload);
		if ($parsed === null) {
			return;
		}

		$type = $parsed['type'];
		$docId = $parsed['document_id'];
		$docPayload = $parsed['document_payload'];

		if ($docId === '') {
			return;
		}

		$contracts = TableRegistry::get('Contracts');
		$contract = $contracts->find()->where(['autentique_doc_id' => $docId])->first();
		if (!$contract) {
			return;
		}

		$cid = (int)$contract->id;
		$this->saveWebhookLog($cid, $type, $webhookPayload);

		if ($type === 'signature.rejected') {
			$contracts->patchEntity($contract, [
				'status' => 'recusado',
				'autentique_status' => 'rejected',
			]);
			$contracts->save($contract);

			return;
		}

		if ($this->eventIndicatesFullySigned($type, $docPayload)) {
			$this->finalizeContractSigned($contracts, $contract, $docId, is_array($docPayload) ? $docPayload : []);
		}
	}

	/**
	 * @param array $webhookPayload
	 * @return array{type:string,document_id:string,document_payload:?array}|null
	 */
	public function parseWebhookEnvelope(array $webhookPayload) {
		$ev = $webhookPayload['event'] ?? null;
		if (!is_array($ev)) {
			return null;
		}
		$type = (string)($ev['type'] ?? '');
		if ($type === '') {
			return null;
		}
		$data = $ev['data'] ?? null;
		if (!is_array($data)) {
			return ['type' => $type, 'document_id' => '', 'document_payload' => null];
		}

		$docId = '';
		$docPayload = null;
		if (($data['object'] ?? '') === 'document') {
			$docId = (string)($data['id'] ?? '');
			$docPayload = $data;
		} elseif (($data['object'] ?? '') === 'signature') {
			$docId = (string)($data['document'] ?? '');
		}

		return ['type' => $type, 'document_id' => $docId, 'document_payload' => $docPayload];
	}

	/**
	 * @param \Cake\ORM\Table $contracts
	 * @param \Cake\Datasource\EntityInterface $contract
	 * @param string $docId
	 * @param array $docPayload Dados do documento no webhook (files.signed, contagens, …)
	 * @return void
	 */
	protected function finalizeContractSigned($contracts, EntityInterface $contract, $docId, array $docPayload) {
		$storage = (string)Configure::read('Contract.pdf.storage_path');
		if ($storage === '') {
			$storage = TMP . 'contracts' . DS;
		}
		$signedDir = $storage . 'signed';
		if (!is_dir($signedDir)) {
			mkdir($signedDir, 0775, true);
		}
		$signedPath = $signedDir . DS . 'signed_' . (int)$contract->get('id') . '.pdf';

		$signedUrl = '';
		if (!empty($docPayload['files']['signed'])) {
			$signedUrl = (string)$docPayload['files']['signed'];
		}

		$ok = $this->downloadSignedPdf($docId, $signedPath);
		if (!$ok && $signedUrl !== '') {
			$ok = $this->downloadUrlToFile($signedUrl, $signedPath);
		}

		$contracts->patchEntity($contract, [
			'status' => 'active',
			'autentique_status' => 'signed',
			'signed_pdf_path' => ($ok && is_file($signedPath)) ? $signedPath : $contract->get('signed_pdf_path'),
			'signed_file_url' => $signedUrl !== '' ? $signedUrl : $contract->get('signed_file_url'),
			'assinado_em' => date('Y-m-d H:i:s'),
			'fully_signed_at' => date('Y-m-d H:i:s'),
		]);
		$contracts->save($contract);

		try {
			$c2 = $contracts->get((int)$contract->id, ['contain' => ['Clientes']]);
			(new ContractNotificationService())->notificarAssinado($c2);
		} catch (\Throwable $e) {
		}
	}

	/**
	 * @param string $type
	 * @param array|null $docPayload
	 * @return bool
	 */
	protected function eventIndicatesFullySigned($type, $docPayload) {
		if ($type === 'document.finished') {
			return true;
		}
		if ($type !== 'document.updated' || !is_array($docPayload)) {
			return false;
		}
		$sc = (int)($docPayload['signed_count'] ?? 0);
		$tc = (int)($docPayload['signatures_count'] ?? 0);

		return $tc > 0 && $sc >= $tc;
	}

	/**
	 * @param int $contractId
	 * @param string $eventType
	 * @param array $payload
	 * @return void
	 */
	protected function saveWebhookLog($contractId, $eventType, array $payload) {
		try {
			$logs = TableRegistry::get('ContractAutentiqueLogs');
			$logs->save($logs->newEntity([
				'contract_id' => $contractId,
				'evento' => 'webhook:' . $eventType,
				'payload' => $payload,
			]));
		} catch (\Throwable $e) {
		}
	}

	/**
	 * @param \Cake\Datasource\EntityInterface[] $signatories
	 * @return array<int,array<string,mixed>>
	 */
	protected function buildSignersPayload(array $signatories) {
		$list = array_values($signatories);
		usort($list, function ($a, $b) {
			return ((int)$a->get('ordem') ?: 0) <=> ((int)$b->get('ordem') ?: 0);
		});

		$out = [];
		foreach ($list as $s) {
			$email = trim((string)$s->get('email'));
			$nome = trim((string)$s->get('nome'));
			$action = $this->mapSignerAction($s);
			if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$out[] = ['email' => $email, 'action' => $action];
			} elseif ($nome !== '') {
				$out[] = ['name' => $nome, 'action' => $action];
			}
		}

		return $out;
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $signatory
	 * @return string
	 */
	protected function mapSignerAction(EntityInterface $signatory) {
		$raw = strtoupper(str_replace([' ', '-'], '_', trim((string)$signatory->get('action_type'))));
		$allowed = ['SIGN', 'APPROVE', 'RECOGNIZE', 'SIGN_AS_A_WITNESS', 'ENDORSE', 'PARTY', 'INTERVENING'];
		if (in_array($raw, $allowed, true)) {
			return $raw;
		}

		return 'SIGN';
	}

	/**
	 * @return string
	 */
	protected function getEndpoint() {
		$url = trim((string)Configure::read('Contract.autentique.api_base_url'));

		return $url !== '' ? $url : self::DEFAULT_GRAPHQL_URL;
	}

	/**
	 * @return string
	 */
	protected function getApiKey() {
		return trim((string)Configure::read('Contract.autentique.api_key'));
	}

	/**
	 * @param string $query
	 * @param array $variables
	 * @return array|null
	 */
	protected function graphqlJson($query, array $variables) {
		$key = $this->getApiKey();
		if ($key === '') {
			return null;
		}
		$body = json_encode(['query' => $query, 'variables' => $variables], JSON_UNESCAPED_UNICODE);
		$ch = curl_init($this->getEndpoint());
		curl_setopt_array($ch, [
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json',
				'Authorization: Bearer ' . $key,
			],
			CURLOPT_POSTFIELDS => $body,
			CURLOPT_TIMEOUT => 120,
		]);
		$raw = curl_exec($ch);
		curl_close($ch);
		if ($raw === false || $raw === '') {
			return null;
		}
		$decoded = json_decode($raw, true);

		return is_array($decoded) ? $decoded : null;
	}

	/**
	 * @param string $query
	 * @param array $variables
	 * @param string $pdfPath
	 * @return array|null
	 */
	protected function graphqlMultipart($query, array $variables, $pdfPath) {
		$key = $this->getApiKey();
		if ($key === '') {
			return null;
		}

		if (!class_exists(\CURLFile::class)) {
			return null;
		}

		$operations = json_encode(['query' => $query, 'variables' => $variables], JSON_UNESCAPED_UNICODE);
		$map = json_encode(['0' => ['variables.file']], JSON_UNESCAPED_UNICODE);

		$ch = curl_init($this->getEndpoint());
		$post = [
			'operations' => $operations,
			'map' => $map,
			'0' => new \CURLFile($pdfPath, 'application/pdf', 'contrato.pdf'),
		];
		curl_setopt_array($ch, [
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer ' . $key,
			],
			CURLOPT_POSTFIELDS => $post,
			CURLOPT_TIMEOUT => 300,
		]);
		$raw = curl_exec($ch);
		curl_close($ch);
		if ($raw === false || $raw === '') {
			return null;
		}
		$decoded = json_decode($raw, true);

		return is_array($decoded) ? $decoded : null;
	}

	/**
	 * @param string $url
	 * @param string $targetPath
	 * @return bool
	 */
	protected function downloadUrlToFile($url, $targetPath) {
		$key = $this->getApiKey();
		$hdr = '';
		if ($key !== '') {
			$hdr = 'Authorization: Bearer ' . $key . "\r\n";
		}
		$ctx = stream_context_create([
			'http' => [
				'timeout' => 120,
				'header' => $hdr,
			],
			'ssl' => [
				'verify_peer' => true,
				'verify_peer_name' => true,
			],
		]);
		$data = @file_get_contents($url, false, $ctx);
		if ($data === false || $data === '') {
			return false;
		}

		return file_put_contents($targetPath, $data) !== false;
	}
}
