<?php
namespace App\Shell;

use App\Service\AutentiqueService;
use App\Service\ContractLifecycleService;
use App\Service\ContractNotificationService;
use App\Service\ContractRenewalService;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * Alertas de contratos (vencimento, auto-renovação, Autentique).
 *
 * Uso:
 *   bin/cake contract_alerts
 *   bin/cake contract_alerts sincronizarAutentique
 *
 * Crontab (exemplo):
 *   0 8 * * * cd /var/www/portal && php bin/cake contract_alerts
 */
class ContractAlertsShell extends Shell {

	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->setDescription('Alertas e rotinas do módulo de contratos (PGM).');

		return $parser;
	}

	public function main() {
		$this->out('[ContractAlerts] Iniciando...');
		$this->verificarVencimentos();
		$this->verificarRenovacoesAuto();
		$this->sincronizarAutentique();
		$this->out('[ContractAlerts] Concluído.');
	}

	/**
	 * Sincroniza status de documentos no Autentique (quando habilitado e API implementada).
	 *
	 * @return void
	 */
	public function sincronizarAutentique() {
		$autentique = new AutentiqueService();
		if (!$autentique->isEnabled()) {
			$this->out('  (Autentique desligado — ignorar sincronização.)');

			return;
		}

		$contracts = TableRegistry::get('Contracts');
		$notif = new ContractNotificationService();
		$storage = (string)Configure::read('Contract.pdf.storage_path');
		if ($storage === '') {
			$storage = TMP . 'contracts' . DS;
		}
		$signedDir = $storage . 'signed';
		if (!is_dir($signedDir)) {
			mkdir($signedDir, 0775, true);
		}

		$pendentes = $contracts->find()
			->contain(['Clientes'])
			->where([
				'Contracts.status' => ['aguardando_assinatura', 'awaiting_signature'],
				'Contracts.autentique_doc_id IS NOT' => null,
			])
			->all();

		foreach ($pendentes as $c) {
			try {
				$docId = (string)$c->get('autentique_doc_id');
				$status = $autentique->statusDocumento($docId);
				$st = $status['status'] ?? 'unknown';
				if ($st === 'signed') {
					$signedPath = $signedDir . DS . 'signed_' . (int)$c->get('id') . '.pdf';
					$autentique->downloadSignedPdf($docId, $signedPath);
					$contracts->patchEntity($c, [
						'status' => 'active',
						'autentique_status' => 'signed',
						'signed_pdf_path' => is_file($signedPath) ? $signedPath : $c->get('signed_pdf_path'),
						'assinado_em' => date('Y-m-d H:i:s'),
					]);
					$contracts->save($c);
					try {
						$notif->notificarAssinado($c);
					} catch (\Throwable $e) {
						$this->err('  E-mail assinado: ' . $e->getMessage());
					}
					$this->out('  Assinado: #' . (int)$c->get('id') . ' (' . $c->get('code') . ')');
				} elseif (in_array($st, ['rejected', 'canceled', 'expired'], true)) {
					$newStatus = $st === 'expired' ? 'assinatura_expirada' : 'recusado';
					$contracts->patchEntity($c, [
						'status' => $newStatus,
						'autentique_status' => $st,
					]);
					$contracts->save($c);
					$this->out('  ' . $st . ': #' . (int)$c->get('id'));
				}
			} catch (\Exception $e) {
				$this->err('  Erro #' . (int)$c->get('id') . ': ' . $e->getMessage());
			}
		}
	}

	/**
	 * @return void
	 */
	protected function verificarVencimentos() {
		$contracts = TableRegistry::get('Contracts');
		$notif = new ContractNotificationService();
		$hoje = date('Y-m-d');
		$d30 = date('Y-m-d', strtotime('+30 days'));
		$activeStatuses = ContractLifecycleService::statusesOpenForOperationalAlerts();

		if (Configure::read('Contract.alerts.auto_close_expired')) {
			foreach ($contracts->find()->where([
				'Contracts.end_date <' => $hoje,
				'Contracts.status' => $activeStatuses,
			])->all() as $c) {
				$contracts->patchEntity($c, ['status' => 'encerrado']);
				$contracts->save($c);
				$this->out('  Encerrado (vencido): #' . (int)$c->get('id'));
			}
		}

		$q = $contracts->find()
			->contain(['Clientes'])
			->where([
				'Contracts.end_date >=' => $hoje,
				'Contracts.end_date <=' => $d30,
				'Contracts.status' => $activeStatuses,
			]);

		foreach ($q->all() as $c) {
			$vf = $c->get('end_date');
			$endTs = $vf instanceof \DateTimeInterface ? $vf->getTimestamp() : strtotime((string)$vf);
			$startDay = strtotime($hoje . ' 00:00:00');
			$dias = (int)ceil(($endTs - $startDay) / 86400);
			if (Configure::read('Contract.alerts.auto_mark_ending_status')
				&& in_array($c->get('status'), ContractLifecycleService::statusesEligibleForBilling(), true)) {
				$contracts->patchEntity($c, ['status' => 'a_vencer']);
				$contracts->save($c);
			}
			if (in_array($dias, [30, 15, 7, 1], true)) {
				try {
					$notif->avisarVencimento($c, $dias);
					$this->out('  Aviso ' . $dias . 'd: #' . (int)$c->get('id'));
				} catch (\Throwable $e) {
					$this->err('  Aviso falhou #' . (int)$c->get('id') . ': ' . $e->getMessage());
				}
			}
		}
	}

	/**
	 * @return void
	 */
	protected function verificarRenovacoesAuto() {
		$contracts = TableRegistry::get('Contracts');
		$renewal = new ContractRenewalService();
		$hoje = date('Y-m-d');
		$d30 = date('Y-m-d', strtotime('+30 days'));

		foreach ($contracts->find()->where([
			'Contracts.auto_renew' => true,
			'Contracts.end_date >=' => $hoje,
			'Contracts.end_date <=' => $d30,
			'Contracts.status' => ContractLifecycleService::statusesOpenForOperationalAlerts(),
		])->all() as $c) {
			$r = $renewal->solicitarRenovacao($c, null);
			if ($r) {
				$this->out('  Auto-renovação solicitada: #' . (int)$c->get('id'));
			}
		}
	}
}
