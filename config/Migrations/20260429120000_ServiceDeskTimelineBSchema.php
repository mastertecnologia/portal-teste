<?php
/**
 * Timeline B: ticket_events, holidays, assets, technical_reports, ticket_checklists, ticket_products.
 * Geo de referência em clientes. estoque_atual em produtos (baixa local; opcional ao ERP).
 */
use Migrations\AbstractMigration;

class ServiceDeskTimelineBSchema extends AbstractMigration {

	public function up() {
		$isPg = $this->getAdapter()->getAdapterType() === 'pgsql';
		if ($isPg) {
			$this->_upPgsql();
		} else {
			$this->_upGeneric();
		}
	}

	protected function _upPgsql(): void {
		if ($this->hasTable('clientes')) {
			$this->execute('ALTER TABLE clientes ADD COLUMN IF NOT EXISTS latitude DOUBLE PRECISION NULL');
			$this->execute('ALTER TABLE clientes ADD COLUMN IF NOT EXISTS longitude DOUBLE PRECISION NULL');
			$this->execute('ALTER TABLE clientes ADD COLUMN IF NOT EXISTS geo_validacao_raio_m INTEGER NULL');
		}
		if ($this->hasTable('produtos')) {
			$this->execute('ALTER TABLE produtos ADD COLUMN IF NOT EXISTS estoque_atual NUMERIC(14,4) NULL');
		}

		if (!$this->hasTable('holidays')) {
			$t = $this->table('holidays');
			$t->addColumn('idempresa', 'integer', ['null' => true, 'default' => null])
				->addColumn('holiday_date', 'date', ['null' => false])
				->addColumn('title', 'string', ['limit' => 255, 'null' => false, 'default' => ''])
				->addColumn('created', 'timestamp', ['null' => true, 'default' => null])
				->addIndex(['holiday_date'], ['name' => 'ix_holidays_date'])
				->addIndex(['idempresa'], ['name' => 'ix_holidays_idempresa'])
				->create();
		}

		if (!$this->hasTable('ticket_events')) {
			$t = $this->table('ticket_events');
			$t->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('ticket_id', 'integer', ['null' => false])
				->addColumn('user_id', 'integer', ['null' => true, 'default' => null])
				->addColumn('type', 'string', ['limit' => 32, 'null' => false])
				->addColumn('description', 'text', ['null' => true, 'default' => null])
				->addColumn('seconds_spent', 'integer', ['null' => false, 'default' => 0])
				->addColumn('billing_type', 'string', ['limit' => 24, 'null' => true, 'default' => null])
				->addColumn('hourly_rate', 'decimal', ['precision' => 12, 'scale' => 4, 'null' => true, 'default' => null])
				->addColumn('rating', 'integer', ['null' => true, 'default' => null])
				->addColumn('attachment', 'text', ['null' => true, 'default' => null])
				->addColumn('is_billed', 'boolean', ['null' => false, 'default' => false])
				->addColumn('created', 'timestamp', ['null' => true, 'default' => null]);
			$t->addIndex(['ticket_id', 'type'], ['name' => 'ix_ticket_events_ticket_type']);
			$t->addIndex(['ticket_id', 'created'], ['name' => 'ix_ticket_events_ticket_created']);
			$t->addIndex(['idempresa'], ['name' => 'ix_ticket_events_idempresa']);
			$t->create();
			$this->execute('ALTER TABLE ticket_events ADD COLUMN IF NOT EXISTS metadata JSONB NULL');
		} else {
			// Coluna metadata se tabela existir parcial
			$this->execute('ALTER TABLE ticket_events ADD COLUMN IF NOT EXISTS metadata JSONB NULL');
		}

		if ($this->hasTable('tickets') && $this->hasTable('ticket_events')) {
			try {
				$te = $this->table('ticket_events');
				$te->addForeignKey('ticket_id', 'tickets', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])->update();
			} catch (\Throwable $e) {
			}
		}
		if ($this->hasTable('users') && $this->hasTable('ticket_events')) {
			try {
				$te = $this->table('ticket_events');
				$te->addForeignKey('user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])->update();
			} catch (\Throwable $e) {
			}
		}

		if (!$this->hasTable('assets')) {
			$t = $this->table('assets');
			$t->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('idcliente', 'integer', ['null' => false])
				->addColumn('descricao', 'string', ['limit' => 255, 'null' => true, 'default' => null])
				->addColumn('codigo_qr', 'string', ['limit' => 128, 'null' => true, 'default' => null])
				->addColumn('identificador', 'string', ['limit' => 128, 'null' => true, 'default' => null])
				->addColumn('ativo', 'boolean', ['null' => false, 'default' => true])
				->addColumn('created', 'timestamp', ['null' => true, 'default' => null])
				->addColumn('modified', 'timestamp', ['null' => true, 'default' => null]);
			$t->addIndex(['idcliente'], ['name' => 'ix_assets_idcliente']);
			$t->addIndex(['codigo_qr'], ['name' => 'ix_assets_codigo_qr']);
			$t->create();
		}
		if ($this->hasTable('clientes') && $this->hasTable('assets')) {
			try {
				$this->table('assets')->addForeignKey('idcliente', 'clientes', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])->update();
			} catch (\Throwable $e) {
			}
		}

		if (!$this->hasTable('technical_reports')) {
			$t = $this->table('technical_reports');
			$t->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('ticket_id', 'integer', ['null' => false])
				->addColumn('asset_id', 'integer', ['null' => true, 'default' => null])
				->addColumn('causa_provavel', 'text', ['null' => true, 'default' => null])
				->addColumn('conclusao_tecnica', 'text', ['null' => true, 'default' => null])
				->addColumn('condition_status', 'string', ['limit' => 64, 'null' => true, 'default' => null])
				->addColumn('created', 'timestamp', ['null' => true, 'default' => null])
				->addColumn('modified', 'timestamp', ['null' => true, 'default' => null]);
			$t->addIndex(['ticket_id'], ['name' => 'ix_technical_reports_ticket']);
			$t->create();
		}
		if ($this->hasTable('tickets') && $this->hasTable('technical_reports')) {
			try {
				$this->table('technical_reports')->addForeignKey('ticket_id', 'tickets', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])->update();
			} catch (\Throwable $e) {
			}
		}
		if ($this->hasTable('assets') && $this->hasTable('technical_reports')) {
			try {
				$this->table('technical_reports')->addForeignKey('asset_id', 'assets', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])->update();
			} catch (\Throwable $e) {
			}
		}

		if (!$this->hasTable('ticket_checklists')) {
			$t = $this->table('ticket_checklists');
			$t->addColumn('technical_report_id', 'integer', ['null' => false])
				->addColumn('item_nome', 'string', ['limit' => 255, 'null' => false])
				->addColumn('status', 'string', ['limit' => 16, 'null' => false, 'default' => 'NA'])
				->addColumn('observacao', 'text', ['null' => true, 'default' => null])
				->addColumn('sort_order', 'integer', ['null' => false, 'default' => 0])
				->addColumn('created', 'timestamp', ['null' => true, 'default' => null]);
			$t->addIndex(['technical_report_id'], ['name' => 'ix_ticket_checklists_report']);
			$t->create();
		}
		if ($this->hasTable('technical_reports') && $this->hasTable('ticket_checklists')) {
			try {
				$this->table('ticket_checklists')->addForeignKey('technical_report_id', 'technical_reports', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])->update();
			} catch (\Throwable $e) {
			}
		}

		if (!$this->hasTable('ticket_products')) {
			$t = $this->table('ticket_products');
			$t->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('ticket_id', 'integer', ['null' => false])
				->addColumn('produto_id', 'integer', ['null' => false])
				->addColumn('quantidade', 'decimal', ['precision' => 14, 'scale' => 4, 'null' => false, 'default' => 1])
				->addColumn('custo_unitario', 'decimal', ['precision' => 14, 'scale' => 4, 'null' => true, 'default' => null])
				->addColumn('preco_unitario', 'decimal', ['precision' => 14, 'scale' => 4, 'null' => true, 'default' => null])
				->addColumn('user_id', 'integer', ['null' => true, 'default' => null])
				->addColumn('created', 'timestamp', ['null' => true, 'default' => null]);
			$t->addIndex(['ticket_id'], ['name' => 'ix_ticket_products_ticket']);
			$t->create();
		}
		if ($this->hasTable('tickets') && $this->hasTable('ticket_products')) {
			try {
				$this->table('ticket_products')->addForeignKey('ticket_id', 'tickets', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])->update();
			} catch (\Throwable $e) {
			}
		}
		if ($this->hasTable('produtos') && $this->hasTable('ticket_products')) {
			try {
				$this->table('ticket_products')->addForeignKey('produto_id', 'produtos', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])->update();
			} catch (\Throwable $e) {
			}
		}
	}

	/**
	 * MySQL / fallback genérico (sem jsonb; metadata como text).
	 */
	protected function _upGeneric(): void {
		if ($this->hasTable('clientes') && !$this->table('clientes')->hasColumn('latitude')) {
			$this->table('clientes')->addColumn('latitude', 'float', ['null' => true, 'default' => null])->update();
		}
		if ($this->hasTable('clientes') && !$this->table('clientes')->hasColumn('longitude')) {
			$this->table('clientes')->addColumn('longitude', 'float', ['null' => true, 'default' => null])->update();
		}
		if ($this->hasTable('clientes') && !$this->table('clientes')->hasColumn('geo_validacao_raio_m')) {
			$this->table('clientes')->addColumn('geo_validacao_raio_m', 'integer', ['null' => true, 'default' => null])->update();
		}
		if ($this->hasTable('produtos') && !$this->table('produtos')->hasColumn('estoque_atual')) {
			$this->table('produtos')->addColumn('estoque_atual', 'decimal', ['precision' => 14, 'scale' => 4, 'null' => true, 'default' => null])->update();
		}
		if (!$this->hasTable('holidays')) {
			$this->table('holidays')
				->addColumn('idempresa', 'integer', ['null' => true, 'default' => null])
				->addColumn('holiday_date', 'date', ['null' => false])
				->addColumn('title', 'string', ['limit' => 255, 'null' => false, 'default' => ''])
				->addColumn('created', 'timestamp', ['null' => true, 'default' => null])
				->addIndex(['holiday_date'], ['name' => 'ix_holidays_date'])
				->addIndex(['idempresa'], ['name' => 'ix_holidays_idempresa'])
				->create();
		}
		if (!$this->hasTable('ticket_events')) {
			$t = $this->table('ticket_events');
			$t->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('ticket_id', 'integer', ['null' => false])
				->addColumn('user_id', 'integer', ['null' => true, 'default' => null])
				->addColumn('type', 'string', ['limit' => 32, 'null' => false])
				->addColumn('description', 'text', ['null' => true, 'default' => null])
				->addColumn('seconds_spent', 'integer', ['null' => false, 'default' => 0])
				->addColumn('billing_type', 'string', ['limit' => 24, 'null' => true, 'default' => null])
				->addColumn('hourly_rate', 'decimal', ['precision' => 12, 'scale' => 4, 'null' => true, 'default' => null])
				->addColumn('rating', 'integer', ['null' => true, 'default' => null])
				->addColumn('attachment', 'text', ['null' => true, 'default' => null])
				->addColumn('is_billed', 'boolean', ['null' => false, 'default' => false])
				->addColumn('metadata', 'text', ['null' => true, 'default' => null])
				->addColumn('created', 'timestamp', ['null' => true, 'default' => null]);
			$t->addIndex(['ticket_id', 'type'], ['name' => 'ix_ticket_events_ticket_type']);
			$t->addIndex(['ticket_id', 'created'], ['name' => 'ix_ticket_events_ticket_created']);
			$t->addIndex(['idempresa'], ['name' => 'ix_ticket_events_idempresa']);
			$t->create();
		}
		// demais tabelas como em PG sem jsonb
		if (!$this->hasTable('assets')) {
			$this->table('assets')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('idcliente', 'integer', ['null' => false])
				->addColumn('descricao', 'string', ['limit' => 255, 'null' => true, 'default' => null])
				->addColumn('codigo_qr', 'string', ['limit' => 128, 'null' => true, 'default' => null])
				->addColumn('identificador', 'string', ['limit' => 128, 'null' => true, 'default' => null])
				->addColumn('ativo', 'boolean', ['null' => false, 'default' => true])
				->addColumn('created', 'timestamp', ['null' => true, 'default' => null])
				->addColumn('modified', 'timestamp', ['null' => true, 'default' => null])
				->addIndex(['idcliente'], ['name' => 'ix_assets_idcliente'])
				->addIndex(['codigo_qr'], ['name' => 'ix_assets_codigo_qr'])
				->create();
		}
		if (!$this->hasTable('technical_reports')) {
			$this->table('technical_reports')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('ticket_id', 'integer', ['null' => false])
				->addColumn('asset_id', 'integer', ['null' => true, 'default' => null])
				->addColumn('causa_provavel', 'text', ['null' => true, 'default' => null])
				->addColumn('conclusao_tecnica', 'text', ['null' => true, 'default' => null])
				->addColumn('condition_status', 'string', ['limit' => 64, 'null' => true, 'default' => null])
				->addColumn('created', 'timestamp', ['null' => true, 'default' => null])
				->addColumn('modified', 'timestamp', ['null' => true, 'default' => null])
				->addIndex(['ticket_id'], ['name' => 'ix_technical_reports_ticket'])
				->create();
		}
		if (!$this->hasTable('ticket_checklists')) {
			$this->table('ticket_checklists')
				->addColumn('technical_report_id', 'integer', ['null' => false])
				->addColumn('item_nome', 'string', ['limit' => 255, 'null' => false])
				->addColumn('status', 'string', ['limit' => 16, 'null' => false, 'default' => 'NA'])
				->addColumn('observacao', 'text', ['null' => true, 'default' => null])
				->addColumn('sort_order', 'integer', ['null' => false, 'default' => 0])
				->addColumn('created', 'timestamp', ['null' => true, 'default' => null])
				->addIndex(['technical_report_id'], ['name' => 'ix_ticket_checklists_report'])
				->create();
		}
		if (!$this->hasTable('ticket_products')) {
			$this->table('ticket_products')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('ticket_id', 'integer', ['null' => false])
				->addColumn('produto_id', 'integer', ['null' => false])
				->addColumn('quantidade', 'decimal', ['precision' => 14, 'scale' => 4, 'null' => false, 'default' => 1])
				->addColumn('custo_unitario', 'decimal', ['precision' => 14, 'scale' => 4, 'null' => true, 'default' => null])
				->addColumn('preco_unitario', 'decimal', ['precision' => 14, 'scale' => 4, 'null' => true, 'default' => null])
				->addColumn('user_id', 'integer', ['null' => true, 'default' => null])
				->addColumn('created', 'timestamp', ['null' => true, 'default' => null])
				->addIndex(['ticket_id'], ['name' => 'ix_ticket_products_ticket'])
				->create();
		}
	}

	public function down() {
		// Não remove colunas de clientes/produtos legados
		$drop = [
			'ticket_products', 'ticket_checklists', 'technical_reports', 'assets', 'ticket_events', 'holidays',
		];
		foreach ($drop as $tbl) {
			if ($this->hasTable($tbl)) {
				$this->table($tbl)->drop()->save();
			}
		}
	}
}
