<?php
use Migrations\AbstractMigration;

/**
 * Alinhar rbac_permissions: Financeiro (underscore) e Servicedesk (download anexo, controller próprio).
 */
class RbacFinanceiroServicedeskActionsPatch extends AbstractMigration {

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
		$this->_appendActions('financeiro.view', 'contas_receber,exportar_fatura,exportar_fatura_pdf,baixar_anexo_fatura');
		$this->_appendActions('financeiro.faturas.receive', 'registrar_recebimento');
		$this->_appendActions('financeiro.faturas.anexos', 'adicionar_anexo_fatura,remover_anexo_fatura');
		$this->_appendActions('servicedesk.view', 'downloadanexo,downloadfile,download_anexo,download_file');
	}

	public function down() {
		// Patch aditivo; rollback manual se necessário.
	}
}
