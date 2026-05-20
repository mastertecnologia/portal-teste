<?php
declare(strict_types=1);

namespace App\Shell;

use App\Controller\TicketCsatController;
use Cake\Console\Shell;
use Cake\I18n\Time;
use Cake\Mailer\Email;
use Cake\ORM\TableRegistry;

/**
 * Despacha pesquisa CSAT por e-mail para tickets fechados nas últimas N horas
 * que ainda não foram pesquisados.
 *
 * Uso:
 *   bin/cake csat_dispatch list           # lista candidatos (não envia)
 *   bin/cake csat_dispatch list --hours=48
 *   bin/cake csat_dispatch send            # envia (real)
 *   bin/cake csat_dispatch send --dry      # simula (mostra mensagens)
 */
class CsatDispatchShell extends Shell {

	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->addArgument('action', [
			'help' => 'list|send',
			'required' => true,
		]);
		$parser->addOption('hours', ['help' => 'Janela em horas (default 24)', 'default' => '24']);
		$parser->addOption('dry', ['help' => 'Não envia, apenas mostra', 'boolean' => true]);

		return $parser;
	}

	public function main() {
		$action = (string)($this->args[0] ?? 'list');
		$hours = max(1, (int)$this->params['hours']);
		$candidatos = $this->buscarCandidatos($hours);
		$this->out(sprintf('Candidatos a CSAT (últimos %d h fechados sem pesquisa): %d', $hours, count($candidatos)));
		if ($candidatos === []) {
			return;
		}
		foreach ($candidatos as $c) {
			$this->out(sprintf(
				'  ticket #%d empresa=%d cliente=%d fechado=%s token=%s',
				$c['ticket_id'],
				$c['idempresa'],
				$c['idcliente'],
				$c['fechado_em'] instanceof \DateTimeInterface ? $c['fechado_em']->format('Y-m-d H:i') : '?',
				$c['token']
			));
		}

		if ($action !== 'send') {
			$this->out(__('Use `send` para enviar (com --dry para simular).'));

			return;
		}

		$dry = !empty($this->params['dry']);
		$enviados = 0;
		$erros = 0;
		foreach ($candidatos as $c) {
			if ($dry) {
				$this->out(sprintf('  [DRY] enviaria CSAT do ticket #%d', $c['ticket_id']));
				$enviados++;
				continue;
			}
			try {
				$this->enviarEmail($c);
				$enviados++;
			} catch (\Throwable $e) {
				$this->err('Falha #' . $c['ticket_id'] . ': ' . $e->getMessage());
				$erros++;
			}
		}
		$this->out(sprintf('Enviados: %d · Erros: %d', $enviados, $erros));
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function buscarCandidatos(int $hours): array {
		$tickets = TableRegistry::getTableLocator()->get('Tickets');
		$csat = TableRegistry::getTableLocator()->get('TicketCsatResponses');
		$desde = Time::now()->subHours($hours);
		$where = ['Tickets.data_fechamento >=' => $desde];
		if (defined('C_TicketSituacaoFechado')) {
			$where['Tickets.situacao'] = (int)C_TicketSituacaoFechado;
		}
		$jaPesquisados = $csat->find()
			->select(['ticket_id'])
			->extract('ticket_id')
			->toList();
		if ($jaPesquisados !== []) {
			$where['Tickets.id NOT IN'] = $jaPesquisados;
		}
		$out = [];
		foreach ($tickets->find()->contain(['Clientes'])->where($where)->limit(200)->all() as $t) {
			$cli = $t->cliente ?? null;
			$email = '';
			if ($cli) {
				$email = (string)($cli->get('emailresponsavel') ?? $cli->get('email') ?? '');
			}
			$token = TicketCsatController::tokenForTicket((int)$t->get('id'), (int)$t->get('idempresa'));
			$out[] = [
				'ticket_id' => (int)$t->get('id'),
				'idempresa' => (int)$t->get('idempresa'),
				'idcliente' => (int)$t->get('idcliente'),
				'cliente_email' => $email,
				'cliente_nome' => $cli ? (string)($cli->get('razaosocial') ?? $cli->get('nome') ?? '') : '',
				'assunto' => (string)$t->get('solicitacao'),
				'fechado_em' => $t->get('data_fechamento'),
				'token' => $token,
			];
		}

		return $out;
	}

	protected function enviarEmail(array $c): void {
		if (empty($c['cliente_email'])) {
			throw new \RuntimeException('Cliente sem e-mail.');
		}
		$base = (string)\Cake\Core\Configure::read('App.fullBaseUrl');
		if ($base === '') {
			$base = 'https://portal.pgm.inf.br/portal';
		}
		$link = rtrim($base, '/') . '/csat/' . $c['token'];
		$email = new Email('default');
		$email->setTo($c['cliente_email'])
			->setSubject('Pesquisa de satisfação · Ticket #' . $c['ticket_id'])
			->setEmailFormat('text')
			->send(sprintf(
				"Olá,\n\nGostaríamos da sua opinião sobre o atendimento do ticket #%d (%s).\n\nResponda em 30 segundos: %s\n\nObrigado!\nEquipe PGM",
				(int)$c['ticket_id'],
				$c['assunto'],
				$link
			));
	}
}
