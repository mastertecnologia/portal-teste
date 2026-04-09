<?php
use Migrations\AbstractMigration;

/**
 * Alinhar rbac_permissions ao catálogo: OS imprimirordens + variantes underscore em Orçamentos.
 */
class RbacOsImprimirordensOrcamentosActionVariants extends AbstractMigration {

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
		$this->_appendActions('ordensservico.view', 'imprimirordens,imprimir_ordens');
		$this->_appendActions('orcamentos.view', 'seguro_proposta,imprimir_pdf,catalogosugestoes');
		$this->_appendActions('orcamentos.create', 'carrinho_edit,limpa_carrinho,edit_item_carrinho,editaitem_carrinho,limpa_session,nova_ordem');
		$this->_appendActions('orcamentos.update', 'alterar_situacao,envio_assinatura,criar_mov');
		$this->_appendActions('orcamentos.portal.view', 'seguro_proposta,catalogosugestoes');
		$this->_appendActions('orcamentos.solicitar', 'catalogosugestoes');
	}

	public function down() {
		// Patch aditivo; rollback manual se necessário.
	}
}
