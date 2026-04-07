<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class RbacAuditAuthorizationsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('rbac_audit_authorizations');
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');
		$this->setEntityClass('App\Model\Entity\RbacAuditAuthorization');
	}
}
