<?php
namespace App\Shell;

use App\Service\Agenda\AgendaReminderService;
use Cake\Console\Shell;

/**
 * Lembretes automáticos da agenda (visitas com lembrete configurado).
 *
 * Uso:
 *   bin/cake agenda_lembretes
 *
 * Crontab (exemplo, a cada 10 minutos):
 *   cd /var/www/portal && php bin/cake agenda_lembretes
 */
class AgendaLembretesShell extends Shell {

	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->setDescription('Envia notificações in-app quando chega o horário dos lembretes da agenda.');

		return $parser;
	}

	public function main() {
		$this->out('[AgendaLembretes] Verificando lembretes...');
		$n = (new AgendaReminderService())->dispatchDueReminders();
		$this->out(sprintf('[AgendaLembretes] Enviados: %d.', $n));
	}
}
