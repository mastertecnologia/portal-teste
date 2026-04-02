<?php
namespace App\Service;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

/**
 * Regras de envio para assinatura (Autentique) e consistência de signatários/PDF.
 * A API real da Autentique permanece em AutentiqueService (Fase 7).
 */
class ContractSigningService {

	/**
	 * Valida se há signatários com e-mail antes de enviar à plataforma.
	 *
	 * @param \Cake\Datasource\EntityInterface[]|iterable $signatories
	 * @return string[] Mensagens de erro (vazio = OK)
	 */
	public function validateSignatoriesForSend($signatories) {
		$errors = [];
		$n = 0;
		foreach ($signatories as $s) {
			$n++;
			$em = trim((string)$s->get('email'));
			$nm = trim((string)$s->get('nome'));
			if ($nm === '') {
				$errors[] = 'Signatário #' . $n . ': nome obrigatório.';
			}
			if ($em === '' || !filter_var($em, FILTER_VALIDATE_EMAIL)) {
				$errors[] = 'Signatário #' . $n . ': e-mail inválido.';
			}
		}
		if ($n === 0) {
			$errors[] = 'Cadastre pelo menos um signatário.';
		}

		return $errors;
	}

	/**
	 * Atualiza contrato após envio (ou simulação) ao provedor de assinatura.
	 *
	 * @param \Cake\ORM\Table $contracts
	 * @param \Cake\Datasource\EntityInterface $contract
	 * @param string|null $docId ID retornado pela API
	 * @return \Cake\Datasource\EntityInterface|false
	 */
	public function markAwaitingSignature(Table $contracts, EntityInterface $contract, $docId = null) {
		$patch = [
			'status' => 'aguardando_assinatura',
			'sent_for_signature_at' => date('Y-m-d H:i:s'),
		];
		if ($docId !== null && $docId !== '') {
			$patch['autentique_doc_id'] = $docId;
		}
		$contracts->patchEntity($contract, $patch);

		return $contracts->save($contract);
	}

	/**
	 * Regista convite na tabela de logs (auditoria).
	 *
	 * @param int $contractId
	 * @param string $evento
	 * @param array|null $payload
	 * @param int|null $userId
	 * @return void
	 */
	public function logEvent($contractId, $evento, array $payload = null, $userId = null) {
		$table = TableRegistry::get('ContractAutentiqueLogs');
		$row = $table->newEntity([
			'contract_id' => (int)$contractId,
			'evento' => $evento,
			'payload' => $payload,
			'user_id' => $userId,
		]);
		$table->save($row);
	}
}
