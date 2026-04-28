<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class AssetsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('assets');
		$this->setDisplayField('descricao');
		$this->setPrimaryKey('id');
		$this->addBehavior('Timestamp');
		$this->setEntityClass('App\\Model\\Entity\\Asset');

		$this->belongsTo('Clientes', ['foreignKey' => 'idcliente']);
		$this->belongsTo('Empresas', ['foreignKey' => 'idempresa']);
		$this->belongsTo('Responsavel', [
			'className' => 'Users',
			'foreignKey' => 'responsavel_user_id',
		]);
		$this->hasMany('TechnicalReports', ['foreignKey' => 'asset_id']);
		$this->hasMany('TicketAssets', ['foreignKey' => 'asset_id']);
	}

	public function validationDefault(Validator $validator) {
		$validator->integer('id')->allowEmptyString('id', null, 'create');

		$validator
			->integer('idempresa')
			->requirePresence('idempresa', 'create')
			->notEmptyString('idempresa', 'Empresa obrigatória.');

		$validator
			->integer('idcliente')
			->requirePresence('idcliente', 'create')
			->notEmptyString('idcliente', 'Cliente obrigatório.');

		$validator
			->scalar('descricao')
			->maxLength('descricao', 255)
			->requirePresence('descricao', 'create')
			->notEmptyString('descricao', 'Descrição é obrigatória.');

		$validator->scalar('tipo')->maxLength('tipo', 48)->allowEmptyString('tipo');
		$validator->scalar('categoria')->maxLength('categoria', 64)->allowEmptyString('categoria');
		$validator->scalar('marca')->maxLength('marca', 96)->allowEmptyString('marca');
		$validator->scalar('modelo')->maxLength('modelo', 96)->allowEmptyString('modelo');
		$validator->scalar('numero_serie')->maxLength('numero_serie', 128)->allowEmptyString('numero_serie');
		$validator->scalar('patrimonio')->maxLength('patrimonio', 64)->allowEmptyString('patrimonio');
		$validator->scalar('identificador')->maxLength('identificador', 128)->allowEmptyString('identificador');
		$validator->scalar('codigo_qr')->maxLength('codigo_qr', 128)->allowEmptyString('codigo_qr');
		$validator->scalar('hostname')->maxLength('hostname', 128)->allowEmptyString('hostname');
		$validator->scalar('ip')->maxLength('ip', 45)->allowEmptyString('ip');
		$validator->scalar('mac')->maxLength('mac', 17)->allowEmptyString('mac');
		$validator->scalar('sistema_operacional')->maxLength('sistema_operacional', 96)->allowEmptyString('sistema_operacional');
		$validator->scalar('usuario')->maxLength('usuario', 128)->allowEmptyString('usuario');
		$validator->scalar('senha')->allowEmptyString('senha');
		$validator->integer('porta_interna')->greaterThanOrEqual('porta_interna', 1)->lessThanOrEqual('porta_interna', 65535)->allowEmptyString('porta_interna');
		$validator->integer('porta_externa')->greaterThanOrEqual('porta_externa', 1)->lessThanOrEqual('porta_externa', 65535)->allowEmptyString('porta_externa');
		$validator->scalar('so_edicao')->maxLength('so_edicao', 48)->allowEmptyString('so_edicao');
		$validator->scalar('windows_chave')->maxLength('windows_chave', 64)->allowEmptyString('windows_chave');
		$validator->scalar('office_versao')->maxLength('office_versao', 48)->allowEmptyString('office_versao');
		$validator->scalar('office_chave')->maxLength('office_chave', 64)->allowEmptyString('office_chave');
		$validator->scalar('localizacao')->maxLength('localizacao', 160)->allowEmptyString('localizacao');
		$validator->integer('responsavel_user_id')->allowEmptyString('responsavel_user_id');

		$validator->add('propriedade', 'inList', [
			'rule' => ['inList', ['proprio', 'locado', 'comodato']],
			'message' => 'Propriedade inválida.',
		]);
		$validator->allowEmptyString('propriedade');

		$validator->add('status_operacional', 'inList', [
			'rule' => ['inList', ['em_uso', 'estoque', 'manutencao', 'reservado', 'descartado', 'perdido']],
			'message' => 'Status operacional inválido.',
		]);
		$validator->allowEmptyString('status_operacional');

		$validator->boolean('ativo')->allowEmptyString('ativo');
		$validator->date('dt_aquisicao')->allowEmptyDate('dt_aquisicao');
		$validator->date('dt_instalacao')->allowEmptyDate('dt_instalacao');
		$validator->date('dt_garantia_fim')->allowEmptyDate('dt_garantia_fim');
		$validator->scalar('fornecedor')->maxLength('fornecedor', 160)->allowEmptyString('fornecedor');
		$validator->decimal('custo_aquisicao')->allowEmptyString('custo_aquisicao');
		$validator->scalar('nfe_referencia')->maxLength('nfe_referencia', 64)->allowEmptyString('nfe_referencia');
		$validator->scalar('observacoes')->allowEmptyString('observacoes');

		return $validator;
	}

	public function buildRules(RulesChecker $rules) {
		$rules->add(function ($entity) {
			$idempresa = (int)($entity->idempresa ?? 0);
			$idcliente = (int)($entity->idcliente ?? 0);
			if ($idempresa <= 0 || $idcliente <= 0) {
				return false;
			}
			$cli = $this->Clientes->find()
				->select(['id'])
				->where(['Clientes.id' => $idcliente, 'Clientes.idempresa' => $idempresa])
				->first();

			return $cli !== null;
		}, 'cliente_empresa_match', [
			'errorField' => 'idcliente',
			'message' => 'Cliente informado não pertence à empresa do ativo.',
		]);

		$rules->add(function ($entity) {
			$ns = trim((string)($entity->numero_serie ?? ''));
			if ($ns === '') {
				return true;
			}
			$q = $this->find()
				->where(['idempresa' => (int)$entity->idempresa, 'numero_serie' => $ns]);
			if (!$entity->isNew() && !empty($entity->id)) {
				$q->where(['id !=' => (int)$entity->id]);
			}

			return $q->count() === 0;
		}, 'numero_serie_unique', [
			'errorField' => 'numero_serie',
			'message' => 'Número de série já cadastrado para esta empresa.',
		]);

		$rules->add(function ($entity) {
			$qr = trim((string)($entity->codigo_qr ?? ''));
			if ($qr === '') {
				return true;
			}
			$q = $this->find()
				->where(['idempresa' => (int)$entity->idempresa, 'codigo_qr' => $qr]);
			if (!$entity->isNew() && !empty($entity->id)) {
				$q->where(['id !=' => (int)$entity->id]);
			}

			return $q->count() === 0;
		}, 'codigo_qr_unique', [
			'errorField' => 'codigo_qr',
			'message' => 'Código QR já cadastrado para esta empresa.',
		]);

		return $rules;
	}
}
