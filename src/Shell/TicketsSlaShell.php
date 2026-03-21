<?php
namespace App\Shell;

use App\Service\Ticket\SlaRecalculationService;
use Cake\Console\Shell;
use Cake\ORM\TableRegistry;

/**
 * Recálculo de SLA (campos enterprise em tickets).
 *
 * Uso: bin/cake tickets_sla recalculate
 *       bin/cake tickets_sla recalculate --empresa=1
 */
class TicketsSlaShell extends Shell {

	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->setDescription('Comandos de SLA para tickets (service desk enterprise).');
		$parser->addOption('empresa', [
			'short' => 'e',
			'help' => 'Limitar à idempresa informada.',
		]);

		return $parser;
	}

	public function recalculate() {
		$path = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'TicketConstants.php';
		if (is_file($path)) {
			require_once $path;
		}

		$empRaw = isset($this->params['empresa']) ? $this->params['empresa'] : null;
		$empId = ($empRaw !== null && $empRaw !== '' && ctype_digit((string)$empRaw)) ? (int)$empRaw : null;

		$tickets = TableRegistry::get('Tickets');
		$svc = new SlaRecalculationService($tickets);
		$r = $svc->recalculateAll($empId);

		$this->out(sprintf(
			'Atualizados: %d | Ignorados: %d | Erros: %d',
			$r['updated'],
			$r['skipped'],
			$r['errors']
		));
	}

	public function main() {
		$this->out('Use: bin/cake tickets_sla recalculate [--empresa=ID]');
	}
}
