<?php
/**
 * Módulo avançado (Fase 1): histórico de atendimento consolidado, timeline, anexos,
 * contratos comerciais (novo modelo), faturas vinculadas a contrato, auditoria genérica.
 *
 * Não duplica:
 * - ticket_histories (eventos tipados já existentes)
 * - tickets.sla_* / prazos (TicketsSlaEnterpriseColumns)
 * - faturamento / faturas legadas
 * - portal_internal_notifications / RBAC
 *
 * Colunas de cliente/empresa alinhadas ao portal: idcliente, idempresa (FK clientes / empresas).
 *
 * PostgreSQL apenas (mesmo padrão de FaturamentoFinanceiro). Outros SGBDs: ignorado.
 *
 * Rodar: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class PortalAdvancedAttendanceContractsInvoicesAudit extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		if (!$this->hasTable('clientes')) {
			return;
		}

		if (!$this->hasTable('contracts')) {
			$this->execute(<<<'SQL'
CREATE TABLE contracts (
	id SERIAL PRIMARY KEY,
	idempresa INTEGER NULL,
	idcliente INTEGER NOT NULL,
	unit_id INTEGER NULL,
	code VARCHAR(50) NOT NULL,
	name VARCHAR(255) NOT NULL,
	type VARCHAR(100) NOT NULL,
	status VARCHAR(50) NOT NULL,
	start_date DATE NOT NULL,
	end_date DATE NOT NULL,
	renewal_date DATE NULL,
	billing_cycle VARCHAR(50) NOT NULL DEFAULT 'monthly',
	monthly_value NUMERIC(12,2) NOT NULL DEFAULT 0,
	sla_hours INTEGER NOT NULL DEFAULT 0,
	included_hours NUMERIC(8,2) NOT NULL DEFAULT 0,
	overage_hour_value NUMERIC(12,2) NOT NULL DEFAULT 0,
	readjustment_index VARCHAR(50) NULL,
	readjustment_date DATE NULL,
	auto_renew BOOLEAN NOT NULL DEFAULT false,
	notes TEXT NULL,
	created TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
	modified TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT uq_portal_contracts_code UNIQUE (code)
);
CREATE INDEX idx_portal_contracts_client ON contracts (idcliente);
CREATE INDEX idx_portal_contracts_empresa ON contracts (idempresa);
CREATE INDEX idx_portal_contracts_status ON contracts (status);
CREATE INDEX idx_portal_contracts_dates ON contracts (start_date, end_date);
SQL
			);
			try {
				$this->execute('ALTER TABLE contracts ADD CONSTRAINT fk_portal_contracts_cliente FOREIGN KEY (idcliente) REFERENCES clientes (id) ON UPDATE CASCADE ON DELETE RESTRICT');
			} catch (\Throwable $e) {
			}
			if ($this->hasTable('empresas')) {
				try {
					$this->execute('ALTER TABLE contracts ADD CONSTRAINT fk_portal_contracts_empresa FOREIGN KEY (idempresa) REFERENCES empresas (id) ON UPDATE CASCADE ON DELETE SET NULL');
				} catch (\Throwable $e) {
				}
			}
		}

		if (!$this->hasTable('contract_services')) {
			$this->execute(<<<'SQL'
CREATE TABLE contract_services (
	id SERIAL PRIMARY KEY,
	contract_id INTEGER NOT NULL,
	service_name VARCHAR(255) NOT NULL,
	service_description TEXT NULL,
	is_included BOOLEAN NOT NULL DEFAULT true,
	max_hours NUMERIC(8,2) NOT NULL DEFAULT 0,
	created TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
	modified TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_contract_services_contract ON contract_services (contract_id);
SQL
			);
			try {
				$this->execute('ALTER TABLE contract_services ADD CONSTRAINT fk_contract_services_contract FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE ON UPDATE CASCADE');
			} catch (\Throwable $e) {
			}
		}

		if (!$this->hasTable('contract_documents')) {
			$this->execute(<<<'SQL'
CREATE TABLE contract_documents (
	id SERIAL PRIMARY KEY,
	contract_id INTEGER NOT NULL,
	title VARCHAR(255) NOT NULL,
	file_path VARCHAR(500) NOT NULL,
	is_public BOOLEAN NOT NULL DEFAULT true,
	created TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_contract_documents_contract ON contract_documents (contract_id);
SQL
			);
			try {
				$this->execute('ALTER TABLE contract_documents ADD CONSTRAINT fk_contract_documents_contract FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE ON UPDATE CASCADE');
			} catch (\Throwable $e) {
			}
		}

		if (!$this->hasTable('contract_consumptions')) {
			$this->execute(<<<'SQL'
CREATE TABLE contract_consumptions (
	id SERIAL PRIMARY KEY,
	contract_id INTEGER NOT NULL,
	ticket_id INTEGER NULL,
	service_order_id INTEGER NULL,
	reference_month VARCHAR(7) NOT NULL,
	consumed_hours NUMERIC(8,2) NOT NULL DEFAULT 0,
	consumed_amount NUMERIC(12,2) NOT NULL DEFAULT 0,
	notes TEXT NULL,
	created TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
	modified TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_contract_consumptions_contract ON contract_consumptions (contract_id);
CREATE INDEX idx_contract_consumptions_month ON contract_consumptions (reference_month);
SQL
			);
			try {
				$this->execute('ALTER TABLE contract_consumptions ADD CONSTRAINT fk_contract_consumptions_contract FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE ON UPDATE CASCADE');
			} catch (\Throwable $e) {
			}
			if ($this->hasTable('tickets')) {
				try {
					$this->execute('ALTER TABLE contract_consumptions ADD CONSTRAINT fk_contract_consumptions_ticket FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON UPDATE CASCADE ON DELETE SET NULL');
				} catch (\Throwable $e) {
				}
			}
		}

		if ($this->hasTable('tickets') && !$this->hasTable('attendance_histories')) {
			$this->execute(<<<'SQL'
CREATE TABLE attendance_histories (
	id SERIAL PRIMARY KEY,
	ticket_id INTEGER NOT NULL,
	idcliente INTEGER NOT NULL,
	contract_id INTEGER NULL,
	idempresa INTEGER NULL,
	unit_id INTEGER NULL,
	technician_id INTEGER NULL,
	category VARCHAR(100) NULL,
	subject VARCHAR(255) NOT NULL,
	status VARCHAR(50) NOT NULL,
	priority VARCHAR(50) NULL,
	sla_status VARCHAR(50) NULL,
	first_response_at TIMESTAMP WITHOUT TIME ZONE NULL,
	resolved_at TIMESTAMP WITHOUT TIME ZONE NULL,
	closed_at TIMESTAMP WITHOUT TIME ZONE NULL,
	total_minutes INTEGER NULL,
	resolution_minutes INTEGER NULL,
	is_reopened BOOLEAN NOT NULL DEFAULT false,
	customer_rating INTEGER NULL,
	created TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
	modified TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_attendance_ticket ON attendance_histories (ticket_id);
CREATE INDEX idx_attendance_client ON attendance_histories (idcliente);
CREATE INDEX idx_attendance_contract ON attendance_histories (contract_id);
CREATE INDEX idx_attendance_status ON attendance_histories (status);
SQL
			);
			try {
				$this->execute('ALTER TABLE attendance_histories ADD CONSTRAINT fk_attendance_hist_ticket FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE ON UPDATE CASCADE');
			} catch (\Throwable $e) {
			}
			try {
				$this->execute('ALTER TABLE attendance_histories ADD CONSTRAINT fk_attendance_hist_cliente FOREIGN KEY (idcliente) REFERENCES clientes (id) ON UPDATE CASCADE ON DELETE RESTRICT');
			} catch (\Throwable $e) {
			}
			try {
				$this->execute('ALTER TABLE attendance_histories ADD CONSTRAINT fk_attendance_hist_contract FOREIGN KEY (contract_id) REFERENCES contracts (id) ON UPDATE CASCADE ON DELETE SET NULL');
			} catch (\Throwable $e) {
			}
			if ($this->hasTable('empresas')) {
				try {
					$this->execute('ALTER TABLE attendance_histories ADD CONSTRAINT fk_attendance_hist_empresa FOREIGN KEY (idempresa) REFERENCES empresas (id) ON UPDATE CASCADE ON DELETE SET NULL');
				} catch (\Throwable $e) {
				}
			}
			if ($this->hasTable('users')) {
				try {
					$this->execute('ALTER TABLE attendance_histories ADD CONSTRAINT fk_attendance_hist_tech FOREIGN KEY (technician_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL');
				} catch (\Throwable $e) {
				}
			}
		}

		if ($this->hasTable('tickets') && !$this->hasTable('attendance_timeline')) {
			$this->execute(<<<'SQL'
CREATE TABLE attendance_timeline (
	id SERIAL PRIMARY KEY,
	ticket_id INTEGER NOT NULL,
	history_id INTEGER NULL,
	actor_user_id INTEGER NULL,
	actor_type VARCHAR(50) NULL,
	event_type VARCHAR(100) NOT NULL,
	event_label VARCHAR(255) NOT NULL,
	public_note TEXT NULL,
	internal_note TEXT NULL,
	metadata JSONB NULL,
	created TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_attendance_timeline_ticket ON attendance_timeline (ticket_id);
CREATE INDEX idx_attendance_timeline_history ON attendance_timeline (history_id);
CREATE INDEX idx_attendance_timeline_event ON attendance_timeline (event_type);
SQL
			);
			try {
				$this->execute('ALTER TABLE attendance_timeline ADD CONSTRAINT fk_attendance_tl_ticket FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE ON UPDATE CASCADE');
			} catch (\Throwable $e) {
			}
			if ($this->hasTable('attendance_histories')) {
				try {
					$this->execute('ALTER TABLE attendance_timeline ADD CONSTRAINT fk_attendance_tl_history FOREIGN KEY (history_id) REFERENCES attendance_histories (id) ON UPDATE CASCADE ON DELETE SET NULL');
				} catch (\Throwable $e) {
				}
			}
			if ($this->hasTable('users')) {
				try {
					$this->execute('ALTER TABLE attendance_timeline ADD CONSTRAINT fk_attendance_tl_actor FOREIGN KEY (actor_user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL');
				} catch (\Throwable $e) {
				}
			}
		}

		if ($this->hasTable('tickets') && !$this->hasTable('attendance_attachments')) {
			$this->execute(<<<'SQL'
CREATE TABLE attendance_attachments (
	id SERIAL PRIMARY KEY,
	ticket_id INTEGER NOT NULL,
	uploaded_by INTEGER NOT NULL,
	file_name VARCHAR(255) NOT NULL,
	file_path VARCHAR(500) NOT NULL,
	mime_type VARCHAR(100) NULL,
	is_public BOOLEAN NOT NULL DEFAULT true,
	created TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_attendance_attach_ticket ON attendance_attachments (ticket_id);
SQL
			);
			try {
				$this->execute('ALTER TABLE attendance_attachments ADD CONSTRAINT fk_attendance_att_ticket FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE ON UPDATE CASCADE');
			} catch (\Throwable $e) {
			}
			if ($this->hasTable('users')) {
				try {
					$this->execute('ALTER TABLE attendance_attachments ADD CONSTRAINT fk_attendance_att_user FOREIGN KEY (uploaded_by) REFERENCES users (id) ON UPDATE CASCADE ON DELETE RESTRICT');
				} catch (\Throwable $e) {
				}
			}
		}

		if ($this->hasTable('contracts') && !$this->hasTable('invoices')) {
			$this->execute(<<<'SQL'
CREATE TABLE invoices (
	id SERIAL PRIMARY KEY,
	contract_id INTEGER NOT NULL,
	idcliente INTEGER NOT NULL,
	idempresa INTEGER NULL,
	code VARCHAR(50) NOT NULL,
	reference_month VARCHAR(7) NOT NULL,
	issue_date DATE NOT NULL,
	due_date DATE NOT NULL,
	subtotal NUMERIC(12,2) NOT NULL DEFAULT 0,
	discounts NUMERIC(12,2) NOT NULL DEFAULT 0,
	additions NUMERIC(12,2) NOT NULL DEFAULT 0,
	taxes NUMERIC(12,2) NOT NULL DEFAULT 0,
	total NUMERIC(12,2) NOT NULL DEFAULT 0,
	status VARCHAR(50) NOT NULL,
	billing_type VARCHAR(50) NOT NULL DEFAULT 'contract',
	external_invoice_number VARCHAR(100) NULL,
	pdf_path VARCHAR(500) NULL,
	fiscal_document_path VARCHAR(500) NULL,
	created TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
	modified TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT uq_portal_invoices_code UNIQUE (code)
);
CREATE INDEX idx_portal_invoices_contract ON invoices (contract_id);
CREATE INDEX idx_portal_invoices_client ON invoices (idcliente);
CREATE INDEX idx_portal_invoices_refmonth ON invoices (reference_month);
CREATE INDEX idx_portal_invoices_status ON invoices (status);
SQL
			);
			try {
				$this->execute('ALTER TABLE invoices ADD CONSTRAINT fk_portal_invoices_contract FOREIGN KEY (contract_id) REFERENCES contracts (id) ON UPDATE CASCADE ON DELETE RESTRICT');
			} catch (\Throwable $e) {
			}
			try {
				$this->execute('ALTER TABLE invoices ADD CONSTRAINT fk_portal_invoices_cliente FOREIGN KEY (idcliente) REFERENCES clientes (id) ON UPDATE CASCADE ON DELETE RESTRICT');
			} catch (\Throwable $e) {
			}
			if ($this->hasTable('empresas')) {
				try {
					$this->execute('ALTER TABLE invoices ADD CONSTRAINT fk_portal_invoices_empresa FOREIGN KEY (idempresa) REFERENCES empresas (id) ON UPDATE CASCADE ON DELETE SET NULL');
				} catch (\Throwable $e) {
				}
			}
		}

		if ($this->hasTable('invoices') && !$this->hasTable('invoice_items')) {
			$this->execute(<<<'SQL'
CREATE TABLE invoice_items (
	id SERIAL PRIMARY KEY,
	invoice_id INTEGER NOT NULL,
	item_type VARCHAR(50) NOT NULL,
	description VARCHAR(255) NOT NULL,
	quantity NUMERIC(10,2) NOT NULL DEFAULT 1,
	unit_value NUMERIC(12,2) NOT NULL DEFAULT 0,
	total_value NUMERIC(12,2) NOT NULL DEFAULT 0,
	source_reference VARCHAR(100) NULL,
	created TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_invoice_items_invoice ON invoice_items (invoice_id);
SQL
			);
			try {
				$this->execute('ALTER TABLE invoice_items ADD CONSTRAINT fk_invoice_items_invoice FOREIGN KEY (invoice_id) REFERENCES invoices (id) ON DELETE CASCADE ON UPDATE CASCADE');
			} catch (\Throwable $e) {
			}
		}

		if ($this->hasTable('invoices') && !$this->hasTable('invoice_payments')) {
			$this->execute(<<<'SQL'
CREATE TABLE invoice_payments (
	id SERIAL PRIMARY KEY,
	invoice_id INTEGER NOT NULL,
	payment_date DATE NOT NULL,
	amount NUMERIC(12,2) NOT NULL,
	payment_method VARCHAR(50) NULL,
	transaction_reference VARCHAR(100) NULL,
	created TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_invoice_payments_invoice ON invoice_payments (invoice_id);
SQL
			);
			try {
				$this->execute('ALTER TABLE invoice_payments ADD CONSTRAINT fk_invoice_payments_invoice FOREIGN KEY (invoice_id) REFERENCES invoices (id) ON DELETE CASCADE ON UPDATE CASCADE');
			} catch (\Throwable $e) {
			}
		}

		if (!$this->hasTable('audit_logs')) {
			$this->execute(<<<'SQL'
CREATE TABLE audit_logs (
	id SERIAL PRIMARY KEY,
	user_id INTEGER NULL,
	entity_type VARCHAR(100) NOT NULL,
	entity_id INTEGER NOT NULL,
	action VARCHAR(100) NOT NULL,
	old_data JSONB NULL,
	new_data JSONB NULL,
	ip_address VARCHAR(45) NULL,
	user_agent VARCHAR(255) NULL,
	created TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_audit_logs_entity ON audit_logs (entity_type, entity_id);
CREATE INDEX idx_audit_logs_user ON audit_logs (user_id);
SQL
			);
			if ($this->hasTable('users')) {
				try {
					$this->execute('ALTER TABLE audit_logs ADD CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL');
				} catch (\Throwable $e) {
				}
			}
		}
	}

	public function down() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}
		foreach ([
			'invoice_payments',
			'invoice_items',
			'invoices',
			'attendance_timeline',
			'attendance_attachments',
			'attendance_histories',
			'contract_consumptions',
			'contract_documents',
			'contract_services',
			'contracts',
			'audit_logs',
		] as $table) {
			if ($this->hasTable($table)) {
				$this->execute('DROP TABLE IF EXISTS ' . $table . ' CASCADE');
			}
		}
	}
}
