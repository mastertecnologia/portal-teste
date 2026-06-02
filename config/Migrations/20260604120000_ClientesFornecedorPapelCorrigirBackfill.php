<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Corrige backfill que marcou todo PJ como eh_fornecedor.
 * Regra: listagem de fornecedores usa somente eh_fornecedor = TRUE explícito.
 */
class ClientesFornecedorPapelCorrigirBackfill extends AbstractMigration {

	public function up(): void {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('clientes')) {
			return;
		}
		$t = $this->table('clientes');
		if (!$t->hasColumn('eh_fornecedor')) {
			return;
		}

		$this->execute('UPDATE clientes SET eh_fornecedor = FALSE WHERE eh_fornecedor IS DISTINCT FROM FALSE');

		$this->execute(
			"UPDATE clientes SET eh_fornecedor = TRUE
			WHERE (eh_cliente = FALSE AND tipo = 2)
			   OR (fornecedor_categoria IS NOT NULL AND BTRIM(fornecedor_categoria) <> '')
			   OR fornecedor_lead_time_dias IS NOT NULL
			   OR fornecedor_status_homologacao IN ('homologado', 'analise')"
		);

		if ($this->hasTable('lic_catalogo_produtos')) {
			$this->execute(
				'UPDATE clientes c SET eh_fornecedor = TRUE
				WHERE EXISTS (
					SELECT 1 FROM lic_catalogo_produtos p
					WHERE p.idfornecedor_cliente = c.id
				)'
			);
		}

		if ($this->hasTable('financeiro_lancamentos')) {
			$this->execute(
				"UPDATE clientes c SET eh_fornecedor = TRUE
				WHERE EXISTS (
					SELECT 1 FROM financeiro_lancamentos fl
					WHERE fl.idcliente = c.id AND fl.tipo = 'despesa'
				)"
			);
		}
	}

	public function down(): void {
		// Irreversível com segurança — reaplicar migration anterior manualmente se necessário.
	}
}
