<?php
namespace App\Shell;

use App\Service\RbacAccessExpiryService;
use Cake\Console\Shell;

class RbacAccessExpiryNotifyShell extends Shell {

	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->setDescription('Envia alertas antes do vencimento de rbac_access_grants (cron).')->addOption('dry-run', [
			'short' => 'd',

			'boolean' => true,

			'default' => false,

			'help' => 'Lista quantos tiers seriam notificados, sem gravar nem enviar.',
		]);

		return $parser;
	}




	public function main() {

		$dry = !empty($this->params['dry-run']);


		try {







			$o = (new RbacAccessExpiryService())->notifyExpiringSoon($dry);





			$this->out(sprintf('Expiry notify: enviados=%d ignorados_erro=%d dry_run=%s', $o['sent'], $o['errors'], $dry ? 'sim' : 'não'));

			$this->out(sprintf('Já cobertos pelo JSON / dry count skip=%d', $o['skipped']));






		} catch (\Throwable $e) {



			$this->err((string)$e->getMessage());



			return 1;



		}





	}




}
