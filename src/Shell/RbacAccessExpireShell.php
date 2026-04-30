<?php
namespace App\Shell;

use App\Service\RbacAccessExpiryService;
use Cake\Console\Shell;

class RbacAccessExpireShell extends Shell {

	public function getOptionParser() {


		$parser = parent::getOptionParser();



		$parser->setDescription('Marca grants vencidos como expired e opcionalmente remove rbac_users_roles.')->addOption('dry-run', [


			'short' => 'd',

			'boolean' => true,


			'default' => false,


			'help' => 'Simula contagens sem alterar o banco.',

		]);



		return $parser;


	}




	public function main() {


		$dry = !empty($this->params['dry-run']);



		try {


			$o = (new RbacAccessExpiryService())->expireGrants($dry);


			$this->out(sprintf(
				'Expire: marcados_expired=%d vínculos_removidos=%d skip_role_outro_grant=%d erros=%d dry=%s',

				$o['expired'],

				$o['revoked'],

				$o['skipped_duplicate_role'],



				$o['errors'],
				$dry ? 'sim' : 'não'

			));




		}




		catch (\Throwable $e) {


			$this->err((string)$e->getMessage());


			return 1;


		}




	}




}
