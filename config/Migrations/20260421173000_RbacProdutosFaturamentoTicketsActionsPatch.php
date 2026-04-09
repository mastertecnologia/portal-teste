<?php
use Migrations\AbstractMigration;

/**
 * Alinhar rbac_permissions: produtos (underscore), faturamento gerar_de_os, tickets (download/cancelamento/alterar_situacao).
 */
class RbacProdutosFaturamentoTicketsActionsPatch extends AbstractMigration {

	protected function _appendActions($code, $csv) {
		$conn = $this->getAdapter()->getConnection();
		$stmt = $conn->prepare('SELECT id, action FROM rbac_permissions WHERE code = ? LIMIT 1');
		$stmt->execute([$code]);
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (!$row) {
			return;
		}
		$action = (string)$row['action'];
		foreach (array_filter(array_map('trim', explode(',', $csv))) as $p) {
			$p = strtolower($p);
			if ($p === '') {
				continue;
			}
			if (stripos($action, $p) !== false) {
				continue;
			}
			$action .= ($action === '' ? '' : ',') . $p;
		}
		if ($action === $row['action']) {
			return;
		}
		$u = $conn->prepare('UPDATE rbac_permissions SET action = ? WHERE id = ?');
		$u->execute([$action, $row['id']]);
	}

	public function up() {
		if (!$this->hasTable('rbac_permissions')) {
			return;
		}
		$this->_appendActions('produtos.pricing', 'salvar_precos');
		$this->_appendActions('produtos.stock', 'qtde_estoque,estoques_lote,serial_number_produto,estoque_pdf');
		$this->_appendActions('faturamento.update', 'gerar_de_os');
		$this->_appendActions('tickets.view', 'cancelamento,downloadanexo,downloadfile,download_anexo,download_file');
		$this->_appendActions('tickets.update', 'alterar_situacao');
	}

	public function down() {
		// Patch aditivo; rollback manual se necessário.
	}
}
