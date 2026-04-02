<?php
namespace App\Service;

use Cake\Core\Configure;
use Cake\Mailer\Email;
use Cake\ORM\TableRegistry;

/**
 * E-mails de eventos de contratos. Templates em src/Template/Email/html/contract_*.ctp
 */
class ContractNotificationService {

	/**
	 * @param string $to
	 * @param string $subject
	 * @param string $template Nome do ficheiro sem .ctp
	 * @param array $vars
	 * @return void
	 */
	protected function sendHtml($to, $subject, $template, array $vars) {
		$from = (string)Configure::read('Contract.notifications.from_email');
		$name = (string)Configure::read('Contract.notifications.from_name');
		if ($from === '' || $to === '') {
			throw new \InvalidArgumentException('E-mail remetente ou destinatário vazio (configure Contract.notifications).');
		}
		$email = new Email('default');
		$email->setFrom([$from => $name ?: $from])
			->setTo($to)
			->setSubject($subject)
			->setEmailFormat('html')
			->setTemplate($template)
			->setLayout('default')
			->setViewVars($vars);
		$email->send();
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $contract
	 * @param int $dias
	 * @return void
	 */
	public function avisarVencimento($contract, $dias) {
		$dias = (int)$dias;
		$tipo = 'vencimento_' . $dias . 'd';
		$table = TableRegistry::get('ContractNotifications');
		$exists = $table->find()
			->where([
				'contract_id' => $contract->get('id'),
				'tipo' => $tipo,
				'enviado' => true,
			])
			->count();
		if ($exists > 0) {
			return;
		}

		$team = (string)Configure::read('Contract.notifications.team_email');
		$cliMail = $this->clienteEmail($contract);
		$code = (string)($contract->get('code') ?? '');
		if ($team === '' && $cliMail === '') {
			return;
		}

		$ok = false;
		$lastErr = '';
		if ($team !== '') {
			try {
				$this->sendHtml(
					$team,
					'Contrato ' . $code . ' vence em ' . $dias . ' dia(s)',
					'contract_vencimento',
					['contract' => $contract, 'dias' => $dias]
				);
				$ok = true;
			} catch (\Throwable $e) {
				$lastErr = $e->getMessage();
			}
		}
		if ($cliMail !== '') {
			try {
				$this->sendHtml(
					$cliMail,
					'Seu contrato vence em ' . $dias . ' dia(s)',
					'contract_vencimento_cliente',
					['contract' => $contract, 'dias' => $dias]
				);
				$ok = true;
			} catch (\Throwable $e) {
				$lastErr = $e->getMessage();
			}
		}
		if ($ok) {
			$this->_markSent($table, (int)$contract->get('id'), $tipo, 'ambos', null);
		} elseif ($lastErr !== '') {
			$this->_markFailed($table, (int)$contract->get('id'), $tipo, $lastErr);
		}
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $contract
	 * @return void
	 */
	public function notificarAssinado($contract) {
		$cliMail = $this->clienteEmail($contract);
		if ($cliMail === '') {
			return;
		}
		$code = (string)($contract->get('code') ?? '');
		$this->sendHtml(
			$cliMail,
			'Contrato ' . $code . ' assinado',
			'contract_assinado_cliente',
			['contract' => $contract]
		);
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $contract
	 * @return void
	 */
	public function notificarNovoContrato($contract) {
		$cliMail = $this->clienteEmail($contract);
		if ($cliMail === '') {
			return;
		}
		$code = (string)($contract->get('code') ?? '');
		$this->sendHtml(
			$cliMail,
			'Novo contrato disponível — ' . $code,
			'contract_novo_cliente',
			['contract' => $contract]
		);
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $contract
	 * @param \Cake\Datasource\EntityInterface $signatory
	 * @return void
	 */
	public function lembrarAssinatura($contract, $signatory) {
		$to = (string)($signatory->get('email') ?? '');
		$link = (string)($signatory->get('link_assinatura') ?? '');
		if ($to === '' || $link === '') {
			return;
		}
		$code = (string)($contract->get('code') ?? '');
		$this->sendHtml(
			$to,
			'Lembrete: assinatura necessária — ' . $code,
			'contract_lembrar_assinatura',
			['contract' => $contract, 'signatory' => $signatory]
		);
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $contract
	 * @return void
	 */
	public function notificarRenovacaoAprovada($contract) {
		$cliMail = $this->clienteEmail($contract);
		if ($cliMail === '') {
			return;
		}
		$code = (string)($contract->get('code') ?? '');
		$this->sendHtml(
			$cliMail,
			'Renovação aprovada — ' . $code,
			'contract_renovacao_aprovada',
			['contract' => $contract]
		);
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $contract
	 * @param string $motivo
	 * @return void
	 */
	public function notificarCanceladoCliente($contract, $motivo = '') {
		$cliMail = $this->clienteEmail($contract);
		if ($cliMail === '') {
			return;
		}
		$code = (string)($contract->get('code') ?? '');
		$this->sendHtml(
			$cliMail,
			'Contrato ' . $code . ' cancelado',
			'contract_cancelado_cliente',
			['contract' => $contract, 'motivo' => $motivo]
		);
	}

	/**
	 * @param \Cake\ORM\Table $table
	 * @param int $contractId
	 * @param string $tipo
	 * @param string $dest
	 * @param string|null $erro
	 * @return void
	 */
	protected function _markSent($table, $contractId, $tipo, $dest, $erro) {
		$n = $table->newEntity([
			'contract_id' => $contractId,
			'tipo' => $tipo,
			'destinatario' => $dest,
			'canal' => 'email',
			'enviado' => true,
			'enviado_em' => date('Y-m-d H:i:s'),
			'erro' => $erro,
		]);
		$table->save($n);
	}

	/**
	 * @param \Cake\ORM\Table $table
	 * @param int $contractId
	 * @param string $tipo
	 * @param string $msg
	 * @return void
	 */
	protected function _markFailed($table, $contractId, $tipo, $msg) {
		$n = $table->newEntity([
			'contract_id' => $contractId,
			'tipo' => $tipo,
			'destinatario' => 'equipe',
			'canal' => 'email',
			'enviado' => false,
			'erro' => mb_substr($msg, 0, 2000),
		]);
		$table->save($n);
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $contract
	 * @return string
	 */
	protected function clienteEmail($contract) {
		$cli = $contract->cliente ?? $contract->get('cliente');
		if ($cli instanceof \Cake\Datasource\EntityInterface) {
			return (string)($cli->get('email') ?? '');
		}

		return '';
	}
}
