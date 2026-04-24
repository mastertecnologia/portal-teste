<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

/**
 * Backfill opcional: comentários e horas legados → ticket_events (Timeline B).
 *
 * Uso:
 *   bin/cake ticket_events_backfill comments --dry-run
 *   bin/cake ticket_events_backfill comments --empresa=1
 *   bin/cake ticket_events_backfill worklogs --dry-run
 */
class TicketEventsBackfillShell extends Shell {

	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->setDescription('Backfill ticket_events a partir de ticketcomentarios e ticketshoras (idempotente por metadata).');
		$parser->addOption('dry-run', [
			'boolean' => true,
			'help' => 'Apenas contar; não grava (onde aplicável).',
		]);
		$parser->addOption('empresa', [
			'short' => 'e',
			'help' => 'Limitar a idempresa (comentários).',
		]);

		return $parser;
	}

	protected function _tableExists($name) {
		try {
			$c = ConnectionManager::get('default');

			return in_array($name, $c->getSchemaCollection()->listTables(), true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	/** @return bool */
	protected function _dry() {
		return !empty($this->params['dry-run']);
	}

	public function comments() {
		if (!$this->_tableExists('ticket_events')) {
			$this->err('Tabela ticket_events não existe. Corra as migrations primeiro.');

			return;
		}
		$dry = $this->_dry();
		$empRaw = isset($this->params['empresa']) ? $this->params['empresa'] : null;
		$empId = ($empRaw !== null && $empRaw !== '' && ctype_digit((string)$empRaw)) ? (int)$empRaw : null;

		$tc = TableRegistry::get('Ticketcomentarios');
		$te = TableRegistry::get('TicketEvents');

		$importedIds = [];
		$allMeta = $te->find()->select(['metadata'])->where(['type' => 'comment'])->all();
		foreach ($allMeta as $row) {
			$m = $row->metadata;
			if (is_array($m) && !empty($m['ticket_comentario_id'])) {
				$importedIds[(int)$m['ticket_comentario_id']] = true;
			}
		}

		$q = $tc->find()
			->select(['id', 'idticket', 'idempresa', 'idautor', 'comentario', 'created'])
			->order(['Ticketcomentarios.id' => 'ASC']);
		if ($empId !== null) {
			$q->where(['idempresa' => $empId]);
		}

		$inserted = 0;
		$skipped = 0;
		foreach ($q as $c) {
			$cid = (int)$c->id;
			if (isset($importedIds[$cid])) {
				$skipped++;
				continue;
			}
			$desc = (string)($c->comentario ?? '');
			$created = Time::now();
			if (!empty($c->created)) {
				try {
					$created = new Time($c->created);
				} catch (\Throwable $e) {
				}
			}
			if ($dry) {
				$this->out("DRY: comentario id={$cid} ticket={$c->idticket}");
				$inserted++;
				continue;
			}
			$te->save($te->newEntity([
				'idempresa' => (int)$c->idempresa,
				'ticket_id' => (int)$c->idticket,
				'user_id' => (int)($c->idautor ?? 0) > 0 ? (int)$c->idautor : null,
				'type' => 'comment',
				'description' => $desc,
				'metadata' => ['ticket_comentario_id' => $cid, 'backfill' => true],
				'created' => $created,
			], ['validate' => false]), ['checkRules' => false, 'validate' => false, 'skipBillingClassify' => true]);
			$importedIds[$cid] = true;
			$inserted++;
		}
		$this->out($dry
			? "DRY-RUN: {$inserted} comentário(s) seriam inseridos; {$skipped} já existem."
			: "Inseridos: {$inserted} | Já mapeados (ignorados): {$skipped}"
		);
	}

	public function worklogs() {
		if (!$this->_tableExists('ticket_events')) {
			$this->err('Tabela ticket_events não existe.');

			return;
		}
		$dry = $this->_dry();
		$th = TableRegistry::get('Ticketshoras');
		$te = TableRegistry::get('TicketEvents');

		$importedIds = [];
		$allMeta = $te->find()->select(['metadata'])->where(['type' => 'worklog'])->all();
		foreach ($allMeta as $row) {
			$m = $row->metadata;
			if (is_array($m) && !empty($m['ticketshoras_id'])) {
				$importedIds[(int)$m['ticketshoras_id']] = true;
			}
		}

		$q = $th->find()
			->contain(['Tickets'])
			->order(['Ticketshoras.id' => 'ASC']);

		$inserted = 0;
		$skipped = 0;
		foreach ($q as $h) {
			$hid = (int)($h->id ?? 0);
			if ($hid <= 0) {
				continue;
			}
			if (isset($importedIds[$hid])) {
				$skipped++;
				continue;
			}
			$tid = (int)($h->idticket ?? 0);
			if ($tid <= 0) {
				continue;
			}
			$tk = $h->tickets;
			$eid = $tk ? (int)($tk->idempresa ?? 0) : 0;
			$at = $h->horafin ?? $h->data ?? null;
			$created = Time::now();
			if ($at instanceof \DateTimeInterface) {
				try {
					$created = new Time($at);
				} catch (\Throwable $e) {
				}
			} elseif (is_string($at) && $at !== '') {
				try {
					$created = new Time($at);
				} catch (\Throwable $e) {
				}
			}
			$min = 0;
			if ($h->horaini !== null && $h->horafin !== null) {
				try {
					$min = (int)$th->getMinutos($h->horaini, $h->horafin);
				} catch (\Throwable $e) {
				}
			}
			if ($dry) {
				$this->out("DRY: ticketshoras id={$hid} ticket={$tid} min={$min}");
				$inserted++;
				continue;
			}
			$te->save($te->newEntity([
				'idempresa' => $eid,
				'ticket_id' => $tid,
				'user_id' => (int)($h->iduser ?? 0) > 0 ? (int)$h->iduser : null,
				'type' => 'worklog',
				'description' => 'Horas técnicas (backfill legado)',
				'seconds_spent' => (int)($min * 60),
				'metadata' => ['ticketshoras_id' => $hid, 'backfill' => true],
				'created' => $created,
			], ['validate' => false]), ['checkRules' => false, 'validate' => false, 'skipBillingClassify' => true]);
			$importedIds[$hid] = true;
			$inserted++;
		}
		$this->out($dry
			? "DRY-RUN: {$inserted} worklog(s) seriam inseridos; {$skipped} já existem."
			: "Inseridos: {$inserted} | Já mapeados: {$skipped}"
		);
	}

	public function main() {
		$this->out('Uso:');
		$this->out('  bin/cake ticket_events_backfill comments [--dry-run] [--empresa=ID]');
		$this->out('  bin/cake ticket_events_backfill worklogs [--dry-run]');
	}
}
