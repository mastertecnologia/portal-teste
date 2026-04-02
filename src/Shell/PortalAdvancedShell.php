<?php
namespace App\Shell;

use App\Service\PortalAdvanced\InvoiceGenerationService;
use Cake\Console\Shell;

/**
 * Rotinas do módulo avançado (contratos / faturas / auditoria).
 *
 * Uso: bin/cake portal_advanced gerar_faturas_mes --mes=2026-04
 *       bin/cake portal_advanced gerar_faturas_mes --mes=2026-04 --empresa=1 --no-notify
 */
class PortalAdvancedShell extends Shell {

	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->setDescription('Comandos do módulo avançado (PortalAdvanced services).');
		$parser->addOption('mes', [
			'help' => 'Para gerar_faturas_mes: mês YYYY-MM (obrigatório).',
		]);
		$parser->addOption('empresa', [
			'help' => 'Limitar contratos à idempresa.',
		]);
		$parser->addOption('no-notify', [
			'boolean' => true,
			'help' => 'Não enviar notificações internas aos staff.',
		]);

		return $parser;
	}

	public function gerarFaturasMes() {
		$mes = isset($this->params['mes']) ? trim((string)$this->params['mes']) : '';
		if ($mes === '') {
			$this->err('Informe --mes=YYYY-MM');

			return;
		}
		$emp = isset($this->params['empresa']) && $this->params['empresa'] !== ''
			? (int)$this->params['empresa'] : null;
		$notify = empty($this->params['no-notify']);

		$r = InvoiceGenerationService::generateMonthly($mes, $emp > 0 ? $emp : null, $notify);

		$this->out(sprintf(
			'Criadas: %d | Ignoradas (já existia): %d | Erros: %d',
			$r['created'],
			$r['skipped'],
			$r['errors']
		));
		foreach ($r['messages'] as $m) {
			$this->out('  - ' . $m);
		}
	}

	public function main() {
		$this->out('Use: bin/cake portal_advanced gerar_faturas_mes --mes=YYYY-MM [--empresa=ID] [--no-notify]');
	}
}
