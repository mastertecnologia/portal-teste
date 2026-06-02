<?php
namespace App\Model\Table;

use Cake\Database\Driver\Postgres;
use Cake\Database\Schema\TableSchema;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Mailer\Email;

$__pgmUtilities = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'Utilities.php';
if (is_file($__pgmUtilities)) {
	require_once $__pgmUtilities;
}

//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/Utilities.php';

class UsersTable extends Table {
	public function initialize(array $config) {
		$this->belongsTo('SupportLevels', ['foreignKey' => 'support_level_id', 'joinType' => 'LEFT']);
		$this->hasMany('Ordensservico', [ 'dependent'  => true, 'cascadeCallbacks' => true ])->setForeignKey('iduser');
		$this->hasMany('Listamembros')->setForeignKey('iduser');
		$this->belongsToMany('Visitas', [
			'foreignKey' => 'iduser',
			'targetForeignKey' => 'idvisita',
			'joinTable' => 'listamembros',
			'dependent'  => true,
			'cascadeCallbacks' => true
		]);
		$this->belongsTo('Clientes')->setForeignKey('idcliente');
		$this->hasMany('Orcamentosnovosdes')->setForeignKey('idautor');
		$this->hasMany('Empresasusers', [ 'dependent'  => true, 'cascadeCallbacks' => true ])->setForeignKey('iduser');
		$this->hasMany('Atividades', [ 'dependent'  => true, 'cascadeCallbacks' => true ])->setForeignKey('iduser');
		$this->hasMany('Ticketcomentarios', [ 'dependent'  => true, 'cascadeCallbacks' => true ])->setForeignKey('idautor');
		$this->hasMany('Ticketsmovs', [ 'dependent'  => true, 'cascadeCallbacks' => true ])->setForeignKey('idusuario');
		$this->hasMany('Tickets', [ 'dependent'  => true, 'cascadeCallbacks' => true ])->setForeignKey('idautor');
		$this->hasMany('Empresasusers', [ 'dependent'  => true, 'cascadeCallbacks' => true ])->setForeignKey('iduser');
	}

	/**
	 * Só colunas realmente boolean no PG (information_schema) — evita forçar boolean em smallint
	 * (quebra WHERE com 't') e corrige hidratação quando o driver devolve bool com schema integer.
	 */
	protected function _initializeSchema(TableSchema $schema) {
		if (!$this->getConnection()->getDriver() instanceof Postgres) {
			return $schema;
		}
		foreach (['inativo', 'bloqueado'] as $col) {
			if ($schema->getColumn($col) !== null && $this->_postgresColumnDataType($col) === 'boolean') {
				$schema->setColumnType($col, 'boolean');
			}
		}

		return $schema;
	}

	/**
	 * Condição de usuário ativo no login: NULL, 0 ou false (coluna boolean ou smallint/int no PostgreSQL).
	 *
	 * @return array<string, mixed>
	 */
	public function loginActiveInativoCondition(): array {
		// OR explícito: evita CAST em massa (falha no PG se houver valor não numérico em inativo)
		// e alinha ao restante do portal (Users.inativo => 0).
		return [
			'OR' => [
				['Users.inativo IS' => null],
				['Users.inativo' => false],
				['Users.inativo' => 0],
				['Users.inativo' => '0'],
			],
		];
	}

	/**
	 * @return string|null data_type em information_schema (ex.: boolean, smallint)
	 */
	protected function _postgresColumnDataType(string $column): ?string {
		static $cache = [];
		$table = $this->getTable();
		$key = $table . '.' . $column;
		if (array_key_exists($key, $cache)) {
			return $cache[$key];
		}
		$row = $this->getConnection()->execute(
			'SELECT data_type FROM information_schema.columns
			 WHERE table_schema = current_schema() AND table_name = :table AND column_name = :col
			 LIMIT 1',
			['table' => $table, 'col' => $column]
		)->fetch('assoc');
		$cache[$key] = $row ? strtolower((string)$row['data_type']) : null;

		return $cache[$key];
	}

	public function validationDefault(Validator $validator) {
		return $validator
			->notEmpty('username', 'Usuário obrigatório')
			->notEmpty('password', 'Senha é necessária')
			->notEmpty('role', 'Função é necessária')
			// ->add('username', [
			// 		'unique' => [
			// 			'rule' => 'validateUnique',
			// 			'provider' => 'table',
			// 			'message' => 'Este usuário já existe!'
			// 		]
			// ])
			// ->add('email', [
			// 		'unique' => [
			// 			'rule' => 'validateUnique',
			// 			'provider' => 'table',
			// 			'message' => 'Já existe um usuário cadastrado com este e-mail!'
			// 		]
			// ])
			->add('role', 'inList', [
				'rule' => ['inList', ['0', '1']],
				'message' => 'Por favor informe um tipo válido!'
			]
		);
	}
	
	public function selectAllByRole($role) {
		return $this->find('all', ['fields' => ['id', 'username', 'name', 'secret', 'created', 'inativo']])->where(['role' => $role])->toArray();
	}

	public function selectAllUsers() {
		return $this->find('list', ['keyField' => 'id', 'valueField' => 'name'])->order(['id'])->toArray();
	}

	public function validationPassword (Validator $validator) {
		$validator
		->add('old_password','custom',[
				'rule'=>  function($value, $context){
					$user = $this->get($context['data']['id']);
					if ($user) if ((new DefaultPasswordHasher)->check($value, $user->password)) return true;
					return false;
				},
				'message'=>'Senha antiga incorreta!',
		])
		->notEmpty('old_password');

		$validator
		->add('password1', [
				'length' => [
					'rule' => ['minLength', 4],
					'message' => 'A senha tem que ter no mínimo 4 caracteres!',
				]
		])
		->notEmpty('password1');

		$validator
		->add('password2',[
				'match'=>[
					'rule'=> ['compareWith','password1'],
					'message'=>'Senhas não conferem!',
				]
		])
		->notEmpty('password2');

		return $validator;
	}

	public function validationPasswordTecnico (Validator $validator) {
		$validator
		->add('password1', [
				'length' => [
					'rule' => ['minLength', 4],
					'message' => 'A senha tem que ter no mínimo 4 caracteres!',
				]
		])
		->notEmpty('password1');

		$validator
		->add('password2',[
				'match'=>[
					'rule'=> ['compareWith','password1'],
					'message'=>'Senhas não conferem!',
				]
		])
		->notEmpty('password2');

		return $validator;
	}
}
