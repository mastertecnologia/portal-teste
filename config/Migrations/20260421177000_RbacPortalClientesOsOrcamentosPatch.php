<?php
use Migrations\AbstractMigration;

/**
 * Alinhar rbac_permissions: notificações portal, tickets portal, users/clientes portal, clientes ERP, empresas sessão/migração, contratos portal PDF, comentários API, orçamentos aprovar, OS update/reports.
 */
class RbacPortalClientesOsOrcamentosPatch extends AbstractMigration {

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
		$this->_appendActions('portal.notifications.read', 'unread_count,list_json');
		$this->_appendActions('portal.notifications.write', 'mark_read,mark_all_read,save_preferences');
		$this->_appendActions('tickets.portal.view', 'index_cliente,meus_tickets,api_index_cliente');
		$this->_appendActions('users.clientes_index', 'index_clientes');
		$this->_appendActions('users.cliente_add', 'add_cliente');
		$this->_appendActions('users.cliente_edit', 'edit_cliente');
		$this->_appendActions('clientes.usuarios.view', 'index_clientes');
		$this->_appendActions('clientes.usuarios.create', 'add_cliente');
		$this->_appendActions('clientes.usuarios.update', 'edit_cliente');
		$this->_appendActions('clientes.view', 'cliente_by_id');
		$this->_appendActions('clientes.create', 'consulta_cnpj,cidades_estado');
		$this->_appendActions('clientes.update', 'consulta_cnpj,cidades_estado,sincroniza_cliente');
		$this->_appendActions('clientes.token.regenerate', 'update_token');
		$this->_appendActions('clientes.portal_edit', 'consulta_cnpj,cidades_estado');
		$this->_appendActions('empresas.session.switch', 'altera_empresa');
		$this->_appendActions('empresas.migrate', 'migrar_cliente');
		$this->_appendActions('portal.contracts.client.pdf', 'export_pdf');
		$this->_appendActions('tickets.comments.api_add', 'api_add');
		$this->_appendActions('orcamentos.approve', 'aprovar_hash');
		$this->_appendActions('ordensservico.update', 'carrinho_add,carrinho_edit_item,carrinho_del_item,valor_total,em_exec');
		$this->_appendActions('ordensservico.reports', 'relatorio_ver,relatorio_pdf,relatorio_enviar_email,ticket_ordem,acao_index');
	}

	public function down() {
		// Patch aditivo; rollback manual se necessário.
	}
}
