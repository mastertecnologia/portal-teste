<?php
namespace App\Shell;

use App\Service\ClienteDomain\ClienteDomainCronService;
use Cake\Console\Shell;

/**
 * Rotinas do domínio cliente (cron-friendly).
 *
 * Uso: bin/cake cliente_domain alertas_contratos
 *       bin/cake cliente_domain alertas_contratos --dias=30 --dedupe=7
 */
class ClienteDomainShell extends Shell {

	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->setDescription('Alertas de contratos e domínio cliente (notificações internas / e-mail opcional).');
		$parser->addOption('dias', [
			'help' => 'Janela em dias para "prestes a vencer" (padrão 30).',
			'default' => '30',
		]);
		$parser->addOption('dedupe', [
			'help' => 'Não repetir o mesmo alerta para o mesmo contrato neste intervalo em dias (padrão 7).',
			'default' => '7',
		]);

		return $parser;
	}

	public function alertasContratos() {
		$dias = isset($this->params['dias']) ? (int)$this->params['dias'] : 30;
		$dedupe = isset($this->params['dedupe']) ? (int)$this->params['dedupe'] : 7;
		$dias = $dias > 0 ? $dias : 30;
		$dedupe = $dedupe > 0 ? $dedupe : 7;

		$r = ClienteDomainCronService::runContractExpiryAlerts($dias, $dedupe);
		$this->out(sprintf(
			'Contratos: vencendo=%d | vencidos=%d | ignorados (fora da janela ou dedupe/cancelado)=%d',
			$r['vencendo'],
			$r['vencido'],
			$r['skipped']
		));
	}

	public function main() {
		$this->out('Uso: bin/cake cliente_domain alertas_contratos [--dias=30] [--dedupe=7]');
	}
}
