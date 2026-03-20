<?php
namespace App\Utility;

use Cake\Log\Log;
use Cake\Mailer\Email;
use Cake\ORM\TableRegistry;

/**
 * Envio para config.emailtickets — mesmo contrato de UsersController::email() (tela Cadastre-se).
 */
class SupportInboxMail {

	public static function sendHtml($htmlBody, $subject, $idempresa) {
		$config = TableRegistry::get('Config')->get(1);
		$emailDest = !empty($config->emailtickets) ? trim((string)$config->emailtickets) : '';
		if ($emailDest === '') {
			Log::warning('[SupportInboxMail] emailtickets vazio.');
			return false;
		}

		$empresa = TableRegistry::get('Empresas')->get($idempresa);
		$nomeempresa = !empty($empresa->nomefantasia) ? $empresa->nomefantasia : $empresa->razaosocial;

		$email = new Email();
		$email->transport(((int)$idempresa === (int)C_EmpresaMaster) ? 'master' : 'pgm');
		$from = 'helpdesk@pgm.inf.br';

		$email->from([$from => $nomeempresa])
			->to($emailDest)
			->emailFormat('html')
			->subject($subject);

		try {
			return (bool)$email->send($htmlBody);
		} catch (\Throwable $e) {
			Log::error('[SupportInboxMail] Falha ao enviar: ' . $e->getMessage());
			return false;
		}
	}
}
