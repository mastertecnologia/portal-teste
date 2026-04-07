<?php
use Migrations\AbstractMigration;

/**
 * Aliases legado → canónico: empresas.manage, produtos.manage, orcamentos.manage, orcamentos.portal_cliente.
 * empresas.session.switch não é expandido de empresas.manage (troca de empresa é necessidade ampla da equipe).
 */
class RbacLegacyAliasSeedEmpresasProdutosOrcamentos extends AbstractMigration {

	/** @var array<int, array{0:string,1:string}> */
	protected $_pairs = [
		['empresas.manage', 'empresas.view'],
		['empresas.manage', 'empresas.create'],
		['empresas.manage', 'empresas.update'],
		['empresas.manage', 'empresas.tokens.sync'],
		['empresas.manage', 'empresas.migrate'],
		['produtos.manage', 'produtos.view'],
		['produtos.manage', 'produtos.create'],
		['produtos.manage', 'produtos.update'],
		['produtos.manage', 'produtos.delete'],
		['produtos.manage', 'produtos.pricing'],
		['produtos.manage', 'produtos.stock'],
		['orcamentos.manage', 'orcamentos.view'],
		['orcamentos.manage', 'orcamentos.create'],
		['orcamentos.manage', 'orcamentos.update'],
		['orcamentos.manage', 'orcamentos.approve'],
		['orcamentos.portal_cliente', 'orcamentos.portal.view'],
		['orcamentos.portal_cliente', 'orcamentos.portal.update'],
		['orcamentos.portal_cliente', 'orcamentos.solicitar'],
	];

	public function up() {
		if (!$this->hasTable('rbac_permission_legacy_aliases')) {
			return;
		}
		foreach ($this->_pairs as $pair) {
			$legacy = str_replace("'", "''", $pair[0]);
			$canonical = str_replace("'", "''", $pair[1]);
			$this->execute(sprintf(
				"INSERT INTO rbac_permission_legacy_aliases (legacy_code, canonical_code, notes, created, modified) " .
				"SELECT '%s', '%s', 'Fase 2c — empresas/produtos/orçamentos', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP " .
				"WHERE NOT EXISTS (SELECT 1 FROM rbac_permission_legacy_aliases e WHERE e.legacy_code = '%s' AND e.canonical_code = '%s')",
				$legacy,
				$canonical,
				$legacy,
				$canonical
			));
		}
	}

	public function down() {
		if (!$this->hasTable('rbac_permission_legacy_aliases')) {
			return;
		}
		foreach ($this->_pairs as $pair) {
			$legacy = str_replace("'", "''", $pair[0]);
			$canonical = str_replace("'", "''", $pair[1]);
			$this->execute(sprintf(
				"DELETE FROM rbac_permission_legacy_aliases WHERE legacy_code = '%s' AND canonical_code = '%s'",
				$legacy,
				$canonical
			));
		}
	}
}
