<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ContractTemplatesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('contract_templates');
		$this->setDisplayField('nome');
		$this->setEntityClass('App\Model\Entity\ContractTemplate');
		$this->addBehavior('Timestamp');

		$this->belongsTo('Empresas', ['foreignKey' => 'idempresa', 'joinType' => 'INNER']);
		$this->hasMany('Contracts', ['foreignKey' => 'template_id', 'dependent' => false]);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->scalar('nome')
			->maxLength('nome', 150)
			->requirePresence('nome', 'create')
			->notEmpty('nome');

		$validator
			->integer('idempresa')
			->requirePresence('idempresa', 'create')
			->notEmpty('idempresa');

		$validator->scalar('tipo_contrato')->maxLength('tipo_contrato', 40)->allowEmpty('tipo_contrato');
		$validator
			->scalar('conteudo_html')
			->requirePresence('conteudo_html', 'create')
			->notEmpty('conteudo_html', __('Informe o corpo HTML do modelo.'), 'create');
		$validator->scalar('descricao')->allowEmpty('descricao');
		$validator->boolean('ativo')->allowEmpty('ativo');
		$validator->integer('versao')->allowEmpty('versao');

		return $validator;
	}
}
