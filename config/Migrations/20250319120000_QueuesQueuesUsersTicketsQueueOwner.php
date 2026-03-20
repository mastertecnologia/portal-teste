<?php
/**
 * Filas por empresa: coluna idempresa equivale a company_id (FK lógico para empresas.id).
 * queues_users: N:N users ↔ queues. tickets.queue_id e tickets.owner_id (espelho de idtecnico_responsavel).
 *
 * Rodar: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class QueuesQueuesUsersTicketsQueueOwner extends AbstractMigration {

	public function up() {
		$hasEmpresas = $this->hasTable('empresas');
		$hasUsers = $this->hasTable('users');
		$hasTickets = $this->hasTable('tickets');

		if (!$this->hasTable('queues')) {
			$t = $this->table('queues');
			$t->addColumn('name', 'string', ['limit' => 255, 'null' => false])
				->addColumn('idempresa', 'integer', ['null' => false, 'default' => 0])
				->addColumn('codigo', 'string', ['limit' => 32, 'null' => true, 'default' => null])
				->addColumn('sort_order', 'integer', ['null' => false, 'default' => 0])
				->addTimestamps();
			if ($hasEmpresas) {
				$t->addForeignKey('idempresa', 'empresas', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE']);
			}
			$t->addIndex(['idempresa'], ['name' => 'ix_queues_idempresa'])
				->create();
		} else {
			$qt = $this->table('queues');
			if (!$qt->hasColumn('created')) {
				$qt->addColumn('created', 'datetime', ['null' => true, 'default' => null])->update();
			}
			if (!$qt->hasColumn('modified')) {
				$qt->addColumn('modified', 'datetime', ['null' => true, 'default' => null])->update();
			}
		}

		if (!$this->hasTable('queues_users')) {
			$qu = $this->table('queues_users');
			$qu->addColumn('queue_id', 'integer', ['null' => false])
				->addColumn('user_id', 'integer', ['null' => false])
				->addTimestamps();
			if ($this->hasTable('queues')) {
				$qu->addForeignKey('queue_id', 'queues', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);
			}
			if ($hasUsers) {
				$qu->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE']);
			}
			$qu->addIndex(['user_id'], ['name' => 'ix_queues_users_user'])
				->addIndex(['queue_id', 'user_id'], ['name' => 'ux_queues_users_queue_user', 'unique' => true])
				->create();
		} else {
			$qut = $this->table('queues_users');
			if (!$qut->hasColumn('created')) {
				$qut->addColumn('created', 'datetime', ['null' => true, 'default' => null])->update();
			}
			if (!$qut->hasColumn('modified')) {
				$qut->addColumn('modified', 'datetime', ['null' => true, 'default' => null])->update();
			}
		}

		if ($hasTickets) {
			$tt = $this->table('tickets');
			if (!$tt->hasColumn('queue_id')) {
				$this->table('tickets')->addColumn('queue_id', 'integer', ['null' => true, 'default' => null])->update();
				if ($this->hasTable('queues')) {
					try {
						$this->table('tickets')->addForeignKey('queue_id', 'queues', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])->update();
					} catch (\Throwable $e) {
					}
				}
			}
			if (!$this->table('tickets')->hasColumn('owner_id')) {
				$this->table('tickets')->addColumn('owner_id', 'integer', ['null' => true, 'default' => null])->update();
				if ($hasUsers) {
					try {
						$this->table('tickets')->addForeignKey('owner_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])->update();
					} catch (\Throwable $e) {
					}
				}
			}
			$ticketsTable = $this->table('tickets');
			if ($ticketsTable->hasColumn('owner_id') && $ticketsTable->hasColumn('idtecnico_responsavel')) {
				$this->execute('UPDATE tickets SET owner_id = idtecnico_responsavel WHERE owner_id IS NULL AND idtecnico_responsavel IS NOT NULL AND idtecnico_responsavel > 0');
			}
		}
	}

	public function down() {
		if ($this->hasTable('tickets')) {
			$tt = $this->table('tickets');
			if ($tt->hasColumn('owner_id')) {
				try {
					$this->table('tickets')->removeColumn('owner_id')->update();
				} catch (\Throwable $e) {
				}
			}
			if ($tt->hasColumn('queue_id')) {
				try {
					$this->table('tickets')->removeColumn('queue_id')->update();
				} catch (\Throwable $e) {
				}
			}
		}
		// Não remove tabelas queues / queues_users no down (dados de produção); rollback manual se necessário.
	}
}
