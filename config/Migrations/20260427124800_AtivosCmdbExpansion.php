<?php
/**
 * Ativos — expansão CMDB/ITSM.
 *
 * - Estende a tabela `assets` com campos de CMDB (tipo, marca, modelo, serial, hostname,
 *   ip/mac, localização, responsável, garantia/financeiro, status operacional…).
 * - Cria tabela pivot `ticket_assets` (N CIs afetados por chamado).
 *
 * Idempotente; suporta PostgreSQL e MySQL/genérico.
 * Rodar: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class AtivosCmdbExpansion extends AbstractMigration {

	public function up() {
		$isPg = $this->getAdapter()->getAdapterType() === 'pgsql';
		if ($isPg) {
			$this->_upPgsql();
		} else {
			$this->_upGeneric();
		}
	}

	protected function _upPgsql(): void {
		if ($this->hasTable('assets')) {
			$cols = [
				'tipo VARCHAR(48) NULL',
				'categoria VARCHAR(64) NULL',
				'marca VARCHAR(96) NULL',
				'modelo VARCHAR(96) NULL',
				'numero_serie VARCHAR(128) NULL',
				'patrimonio VARCHAR(64) NULL',
				'hostname VARCHAR(128) NULL',
				'ip VARCHAR(45) NULL',
				'mac VARCHAR(17) NULL',
				'sistema_operacional VARCHAR(96) NULL',
				'usuario VARCHAR(128) NULL',
				'senha TEXT NULL',
				'porta_interna INTEGER NULL',
				'porta_externa INTEGER NULL',
				'localizacao VARCHAR(160) NULL',
				'responsavel_user_id INTEGER NULL',
				"propriedade VARCHAR(16) NULL DEFAULT 'proprio'",
				"status_operacional VARCHAR(20) NULL DEFAULT 'em_uso'",
				'dt_aquisicao DATE NULL',
				'dt_instalacao DATE NULL',
				'dt_garantia_fim DATE NULL',
				'fornecedor VARCHAR(160) NULL',
				'custo_aquisicao NUMERIC(15,2) NULL',
				'observacoes TEXT NULL',
			];
			foreach ($cols as $col) {
				$this->execute("ALTER TABLE assets ADD COLUMN IF NOT EXISTS {$col}");
			}
			$this->execute('CREATE INDEX IF NOT EXISTS ix_assets_tipo ON assets (tipo)');
			$this->execute('CREATE INDEX IF NOT EXISTS ix_assets_status_op ON assets (status_operacional)');
			$this->execute('CREATE INDEX IF NOT EXISTS ix_assets_idempresa ON assets (idempresa)');
			$this->execute('CREATE INDEX IF NOT EXISTS ix_assets_numero_serie ON assets (numero_serie)');
		}

		if (!$this->hasTable('ticket_assets')) {
			$t = $this->table('ticket_assets');
			$t->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('ticket_id', 'integer', ['null' => false])
				->addColumn('asset_id', 'integer', ['null' => false])
				->addColumn('papel', 'string', ['limit' => 16, 'null' => false, 'default' => 'afetado'])
				->addColumn('user_id', 'integer', ['null' => true, 'default' => null])
				->addColumn('observacao', 'string', ['limit' => 255, 'null' => true, 'default' => null])
				->addColumn('created', 'timestamp', ['null' => true, 'default' => null]);
			$t->addIndex(['ticket_id'], ['name' => 'ix_ticket_assets_ticket']);
			$t->addIndex(['asset_id'], ['name' => 'ix_ticket_assets_asset']);
			$t->addIndex(['ticket_id', 'asset_id'], ['unique' => true, 'name' => 'uq_ticket_assets_ticket_asset']);
			$t->create();
		}
		if ($this->hasTable('tickets') && $this->hasTable('ticket_assets')) {
			try {
				$this->table('ticket_assets')
					->addForeignKey('ticket_id', 'tickets', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
					->update();
			} catch (\Throwable $e) {
			}
		}
		if ($this->hasTable('assets') && $this->hasTable('ticket_assets')) {
			try {
				$this->table('ticket_assets')
					->addForeignKey('asset_id', 'assets', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
					->update();
			} catch (\Throwable $e) {
			}
		}
	}

	protected function _upGeneric(): void {
		if ($this->hasTable('assets')) {
			$tbl = $this->table('assets');
			$add = [
				['tipo', 'string', ['limit' => 48, 'null' => true, 'default' => null]],
				['categoria', 'string', ['limit' => 64, 'null' => true, 'default' => null]],
				['marca', 'string', ['limit' => 96, 'null' => true, 'default' => null]],
				['modelo', 'string', ['limit' => 96, 'null' => true, 'default' => null]],
				['numero_serie', 'string', ['limit' => 128, 'null' => true, 'default' => null]],
				['patrimonio', 'string', ['limit' => 64, 'null' => true, 'default' => null]],
				['hostname', 'string', ['limit' => 128, 'null' => true, 'default' => null]],
				['ip', 'string', ['limit' => 45, 'null' => true, 'default' => null]],
				['mac', 'string', ['limit' => 17, 'null' => true, 'default' => null]],
				['sistema_operacional', 'string', ['limit' => 96, 'null' => true, 'default' => null]],
				['usuario', 'string', ['limit' => 128, 'null' => true, 'default' => null]],
				['senha', 'text', ['null' => true, 'default' => null]],
				['porta_interna', 'integer', ['null' => true, 'default' => null]],
				['porta_externa', 'integer', ['null' => true, 'default' => null]],
				['localizacao', 'string', ['limit' => 160, 'null' => true, 'default' => null]],
				['responsavel_user_id', 'integer', ['null' => true, 'default' => null]],
				['propriedade', 'string', ['limit' => 16, 'null' => true, 'default' => 'proprio']],
				['status_operacional', 'string', ['limit' => 20, 'null' => true, 'default' => 'em_uso']],
				['dt_aquisicao', 'date', ['null' => true, 'default' => null]],
				['dt_instalacao', 'date', ['null' => true, 'default' => null]],
				['dt_garantia_fim', 'date', ['null' => true, 'default' => null]],
				['fornecedor', 'string', ['limit' => 160, 'null' => true, 'default' => null]],
				['custo_aquisicao', 'decimal', ['precision' => 15, 'scale' => 2, 'null' => true, 'default' => null]],
				['observacoes', 'text', ['null' => true, 'default' => null]],
			];
			foreach ($add as [$name, $type, $opts]) {
				if (!$tbl->hasColumn($name)) {
					$tbl->addColumn($name, $type, $opts);
				}
			}
			$tbl->update();
			try {
				if (!$tbl->hasIndexByName('ix_assets_tipo')) {
					$tbl->addIndex(['tipo'], ['name' => 'ix_assets_tipo'])->update();
				}
			} catch (\Throwable $e) {
			}
			try {
				if (!$tbl->hasIndexByName('ix_assets_status_op')) {
					$tbl->addIndex(['status_operacional'], ['name' => 'ix_assets_status_op'])->update();
				}
			} catch (\Throwable $e) {
			}
		}

		if (!$this->hasTable('ticket_assets')) {
			$this->table('ticket_assets')
				->addColumn('idempresa', 'integer', ['null' => false])
				->addColumn('ticket_id', 'integer', ['null' => false])
				->addColumn('asset_id', 'integer', ['null' => false])
				->addColumn('papel', 'string', ['limit' => 16, 'null' => false, 'default' => 'afetado'])
				->addColumn('user_id', 'integer', ['null' => true, 'default' => null])
				->addColumn('observacao', 'string', ['limit' => 255, 'null' => true, 'default' => null])
				->addColumn('created', 'timestamp', ['null' => true, 'default' => null])
				->addIndex(['ticket_id'], ['name' => 'ix_ticket_assets_ticket'])
				->addIndex(['asset_id'], ['name' => 'ix_ticket_assets_asset'])
				->addIndex(['ticket_id', 'asset_id'], ['unique' => true, 'name' => 'uq_ticket_assets_ticket_asset'])
				->create();
		}
	}

	public function down() {
		if ($this->hasTable('ticket_assets')) {
			$this->table('ticket_assets')->drop()->save();
		}
		// Não remove colunas adicionadas em `assets` para preservar dados.
	}
}
