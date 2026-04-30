<?php
namespace App\Shell;

use App\Service\AccessDiagnosticService;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Database\Driver\Postgres;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;

/** GO LIVE pré-vôo IAM */

class RbacGoLiveCheckShell extends Shell {


	/** @var array<int,string> */


	protected $_sev = [];


	protected function severity(string $s): void {


		if ($s !== 'OK') {


			$this->_sev[] = $s;



		}

	}




	public function main() {


		$this->_sev = [];


		$this->stepTables();


		$this->stepIndexes();


		$this->stepRegistryPrefixes();





		$this->stepConfigBlocks();





		$this->stepRoutesMarkers();





		$this->stepDiagnosticSim();


		$r = implode('', $this->_sev);





		if ($r === '') {


			$this->out('RELATÓRIO: OK');


			return 0;



		}





		if (strpos($r, 'ERROR') !== false) {


			$this->err('RELATÓRIO: ERROR');


			return 3;



		}


		$this->err('RELATÓRIO: WARNING');


		return 2;



	}




	protected function stepTables(): void {




		$want = ['rbac_access_requests', 'rbac_access_grants', 'rbac_change_audit_logs'];


		try {


			$list = TableRegistry::get('RbacPermissions')->getConnection()->getSchemaCollection()->listTables();





		}


		catch (\Throwable $e) {


			$this->err('[ERROR] schema: ' . $e->getMessage());





			$this->severity('ERROR');


			return;





		}




		foreach ($want as $t) {


			if (!in_array($t, $list, true)) {


				$this->err('[ERROR] tabela falta ' . $t);


				$this->severity('ERROR');




			} else {


				$this->out('[OK] tabela ' . $t);





			}




		}




	}




	protected function idxColsSubset(array $def): array {


		$c = isset($def['columns']) ? (array)$def['columns'] : [];


		return array_map('strtolower', array_map('strval', array_values($c)));


	}




	protected function idxMatch(array $def, array $want): bool {


		$a = $this->idxColsSubset($def);


		sort($a);


		$b = $want;



		sort($b);





		return $a === $b;





	}




	/** Datasource default é PostgreSQL (GO LIVE em prod). */


	protected function isPgsqlDatasource(): bool {


		try {


			return ConnectionManager::get('default')->getDriver() instanceof Postgres;


		}


		catch (\Throwable $e) {


			return false;


		}


	}




	protected function stepIndexes(): void {




		try {


			$r = TableRegistry::get('RbacAccessRequests')->getSchema();


			$h = false;


			foreach ($r->indexes() as $indexName) {


				$def = $r->getIndex($indexName);


				if (!is_array($def)) {


					continue;


				}


				if ($this->idxMatch($def, ['user_id', 'controller', 'action', 'status'])) {


					$h = true;





					break;



				}




			}




			if ($h) {


				$this->out('[OK] índice composto rbac_access_requests');


			}




			else {


				$this->err('[WARN] índice composto rbac_access_requests não encontrado');


				$this->severity('WARNING');


			}




		}


		catch (\Throwable $e1) {


			$this->err('[WARN] indexes requests — ' . $e1->getMessage());





			$this->severity('WARNING');


		}






		try {


			$uniq = false;


			$ursSchema = TableRegistry::get('RbacUsersRoles')->getSchema();


			foreach ($ursSchema->indexes() as $indexName) {


				$def = $ursSchema->getIndex($indexName);


				if (!is_array($def)) {


					continue;


				}


				$u = false;





				if (!empty($def['unique'])) {


					$u = true;



				}








				if (!$u && isset($def['type']) && strtolower((string)$def['type']) === 'unique') {


					$u = true;



				}










				if ($u && $this->idxMatch($def, ['user_id', 'role_id'])) {


					$uniq = true;



					break;


				}




			}




			if (!$uniq) {


				$pk = $ursSchema->primaryKey();


				if (is_array($pk) && $this->idxMatch(['columns' => $pk], ['user_id', 'role_id'])) {


					$uniq = true;


				}


			}




			if (!$uniq && $this->isPgsqlDatasource()) {


				try {


					$q = ConnectionManager::get('default')->execute(
						"SELECT 1 AS ok FROM pg_indexes WHERE tablename = 'rbac_users_roles' AND indexname = 'rbac_users_roles_user_role_uq' LIMIT 1"
					);


					$row = $q ? $q->fetch('assoc') : false;


					if (is_array($row) && isset($row['ok'])) {


						$uniq = true;


					}


				}


				catch (\Throwable $ePg) {


				}


			}




			if ($uniq) {


				$this->out('[OK] unique rbac_users_roles (user_id, role_id)');


			}




			else {


				$this->err('[WARN] unique rbac_users_roles — confirmar migração rbac_users_roles_user_role_uq');


				$this->severity('WARNING');


			}




		}


		catch (\Throwable $e2) {


			$this->err('[WARN] indexes users_roles — ' . $e2->getMessage());


			$this->severity('WARNING');


		}




	}




	protected function stepRegistryPrefixes(): void {

		$file = defined('CONFIG') ? CONFIG . 'permissions_registry.php' : dirname(__DIR__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'permissions_registry.php';
		try {
			$codes = [];

			if (is_readable($file)) {
				$r = include $file;
				foreach ((array)$r as $row) {

					if (!empty($row['code'])) {


						$codes[strtolower((string)$row['code'])] = true;





					}


				}




			}





			if ($codes === []) {




				$this->err('[WARN] permissões não carregadas de ' . $file);





				$this->severity('WARNING');


				return;





			}




			foreach (['rbac.requests.create', 'rbac.requests.grant', 'rbac.grants.revoke', 'rbac.dashboard.view'] as $need) {


				if (!isset($codes[$need])) {


					$this->err('[WARN] registry sem código ' . $need);





					$this->severity('WARNING');




				}


				else {


					$this->out('[OK] registry ' . $need);





				}


			}




		} catch (\Throwable $e3) {


			$this->err('[WARN] registry — ' . $e3->getMessage());


			$this->severity('WARNING');


		}




	}






	protected function stepConfigBlocks(): void {






		$rb = Configure::read('Rbac');


		if (!is_array($rb)) {


			$this->err('[ERROR] Configure Rbac');


			$this->severity('ERROR');


			return;





		}




		foreach (['diagnostics', 'notifications', 'access_expiration'] as $b) {


			if (!isset($rb[$b]) || !is_array($rb[$b])) {


				$this->err('[WARN] Rbac.' . $b);


				$this->severity('WARNING');


			}




			else {


				$this->out('[OK] Rbac.' . $b);



			}



		}




	}




	protected function stepRoutesMarkers(): void {




		$rfile = CONFIG . 'routes.php';


		if (!is_readable($rfile)) {


			$rfile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'routes.php';


			if (!is_readable($rfile)) {


				$this->err('[WARN] routes.php');


				$this->severity('WARNING');


				return;





			}




		}




		$txt = strtolower((string)file_get_contents($rfile));


		foreach ([
			'users/access-denied',

			'dashboard-acessos',




			'meus-pedidos-acesso',


			'matriz-visual',


			'/permissoes/pedidos-acesso',




		] as $needle) {


			if ($needle === '') {


				continue;


			}








			if (strpos($txt, strtolower($needle)) !== false) {


				$this->out('[OK] rota esperada encontrada substring ' . $needle);





			}




			else {


				$this->err('[WARN] rota esperada substring ' . $needle);


				$this->severity('WARNING');


			}




		}




	}




	protected function stepDiagnosticSim(): void {




		try {


			$row = TableRegistry::get('Users')


				->find()

				->select(['id', 'username', 'admin', 'role'])

				->where(['role' => 0])

				->order(['id' => 'ASC'])


				->first();


			if (!$row) {


				$this->err('[WARN] simulação: sem utilizador equipe');


				$this->severity('WARNING');


				return;





			}




			$u = $row->toArray();


			if (!isset($u['idempresa'])) {


				$u['idempresa'] = 0;


			}


			if ((int)($u['role'] ?? 1) === 0) {


				$eu = TableRegistry::get('Empresasusers')


					->find()


					->select(['idempresa'])


					->where(['iduser' => (int)$u['id']])


					->order(['idempresa' => 'ASC'])


					->first();


				if ($eu && isset($eu->idempresa)) {


					$u['idempresa'] = (int)$eu->idempresa;


				}


			}


			$svc = new AccessDiagnosticService();


			$r = $svc->diagnose($u, 'permissoes', 'index');


			if (is_array($r) && $r !== []) {


				$this->out('[OK] simulação AccessDiagnostic permissoes/index');


				return;





			}




			$this->err('[WARN] simulação retorno vazio');


			$this->severity('WARNING');




		}


		catch (\Throwable $eX) {


			$this->err('[WARN] simulação ' . substr($eX->getMessage(), 0, 180));


			$this->severity('WARNING');


		}




	}




}
