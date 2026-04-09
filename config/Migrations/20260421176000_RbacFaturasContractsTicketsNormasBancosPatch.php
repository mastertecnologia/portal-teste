<?php
use Migrations\AbstractMigration;

/**
 * Alinhar rbac_permissions: Faturas, ContractManagement, Tickets assign/timer/delete, Normas, Bancosenhas, AdvancedInvoices, AdvancedContracts.
 */
class RbacFaturasContractsTicketsNormasBancosPatch extends AbstractMigration {

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
		$this->_appendActions('faturas.view', 'view_item');
		$this->_appendActions('faturas.create', 'carrinho_edit,add_item,edit_item,limpa_carrinho');
		$this->_appendActions('faturas.update', 'devolver_item,delete_item,aprovar_hash,rejeitar_hash');
		$this->_appendActions('normasempresa.read', 'downloadfile,download_file');
		$this->_appendActions('normasempresa.acessoremoto', 'acesso_remoto');
		$this->_appendActions('bancosenhas.update', 'verifica_senha');
		$this->_appendActions('bancosenhas.reveal', 'vault_reveal');
		$this->_appendActions('erp.advanced.contracts.view', 'export_pdf');
		$this->_appendActions('erp.advanced.invoices.update', 'mark_paid');
		$this->_appendActions('erp.contracts.management.view', 'ver_renovacoes,download_pdf,download_signed');
		$this->_appendActions('erp.contracts.management.edit', 'add_servicos,delete_servico,add_signatarios,delete_signatario,gerar_pdf');
		$this->_appendActions('erp.contracts.management.signature', 'enviar_assinatura,reenviar_link');
		$this->_appendActions('erp.contracts.management.lifecycle', 'aprovar_renovacao,recusar_renovacao,solicitar_renovacao');
		$this->_appendActions('erp.contracts.management.webhook', 'webhook_autentique');
		$this->_appendActions('tickets.delete', 'delete_anexo');
		$this->_appendActions('tickets.assign', 'start_ticket,api_transferir_ticket,api_start_ticket');
		$this->_appendActions('tickets.timer', 'timer_iniciar,timer_pausar,timer_retomar,timer_finalizar,api_timer');
	}

	public function down() {
		// Patch aditivo; rollback manual se necessário.
	}
}
