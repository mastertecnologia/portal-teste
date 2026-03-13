<?php
namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Mailer\Email;
use Cake\ORM\TableRegistry;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'Utilities.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'TicketConstants.php');

class FechamentoMensalCommand extends Command {
    public function execute(Arguments $args, ConsoleIo $io): int {
        $io->out('Iniciando fechamento mensal de horas técnicas de tickets...');

        $clientesTable = TableRegistry::getTableLocator()->get('Clientes');
        $ticketshorasTable = TableRegistry::getTableLocator()->get('Ticketshoras');
        $ticketsTable = TableRegistry::getTableLocator()->get('Tickets');
        $empresasTable = TableRegistry::getTableLocator()->get('Empresas');
        $configTable = TableRegistry::getTableLocator()->get('Config');

        // Período: mês anterior completo (formato d/m/Y para manter padrão do sistema)
        $referencia = strtotime('first day of last month');
        $inicioMes = '01/' . date('m/Y', $referencia);
        $fimMes = date('t', $referencia) . '/' . date('m/Y', $referencia);
        $mesAnoLegivel = date('m/Y', $referencia);

        // Percorre todos os clientes ativos
        $clientes = $clientesTable->find('all')
            ->where(['inativo' => 0])
            ->toArray();

        foreach ($clientes as $cliente) {
            // Destinatários específicos (campo já utilizado para responsáveis)
            if (empty($cliente->emailresponsavel)) {
                continue;
            }

            // Total de minutos do mês para o cliente (usando TicketshorasTable)
            $minutosMes = $ticketshorasTable->minutosCliente($cliente->id, $inicioMes, $fimMes);
            if ($minutosMes <= 0) {
                continue; // Sem horas para este cliente no mês
            }

            $horasMes = number_format($minutosMes / 60, 2, ',', '.');

            // Busca todos os tickets do cliente e calcula apenas os que têm horas no mês
            $ticketsCliente = $ticketsTable->find('all')
                ->where(['Tickets.idcliente' => $cliente->id])
                ->contain(['Users'])
                ->order(['Tickets.id' => 'ASC'])
                ->toArray();

            $linhasTickets = '';
            foreach ($ticketsCliente as $ticket) {
                $minutosTicketMes = $ticketshorasTable->minutosTicket($ticket->id, $inicioMes, $fimMes);
                if ($minutosTicketMes <= 0) {
                    continue;
                }

                $horasTicketMes = number_format($minutosTicketMes / 60, 2, ',', '.');
                $assunto = AssuntoTicket($ticket->assunto);
                $autor = !empty($ticket->user) && !empty($ticket->user->name) ? $ticket->user->name : '-';

                $linhasTickets .=
                    '<tr>'
                    . '<td style="padding:4px 8px;border:1px solid #ccc;">' . (int)$ticket->id . '</td>'
                    . '<td style="padding:4px 8px;border:1px solid #ccc;">' . $assunto . '</td>'
                    . '<td style="padding:4px 8px;border:1px solid #ccc;">' . htmlspecialchars((string)$ticket->solicitacao) . '</td>'
                    . '<td style="padding:4px 8px;border:1px solid #ccc;">' . $autor . '</td>'
                    . '<td style="padding:4px 8px;border:1px solid #ccc;text-align:right;">' . $horasTicketMes . ' h</td>'
                    . '</tr>';
            }

            if (empty($linhasTickets)) {
                continue; // Não há tickets com horas no mês, apesar de minutosMes > 0 (caso extremo)
            }

            // Empresa dominante deste cliente para obter nome/URL
            $empresa = $empresasTable->get($cliente->idempresa);
            $config = $configTable->get(1);

            $nomeEmpresa = isset($empresa->nomefantasia) ? $empresa->nomefantasia : $empresa->razaosocial;
            $urlPortal = $config->urlfora;

            $message =
                "<h3> Relatório mensal de horas técnicas - $mesAnoLegivel </h3>"
                . "<p><b>Cliente:</b> " . ($cliente->tipo == C_ClientesTipoFisica ? $cliente->nome : $cliente->razaosocial) . "</p>"
                . "<p><b>Total de horas técnicas no mês:</b> {$horasMes} h</p>"
                . "<br>"
                . "<table cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse:collapse;border:1px solid #ccc;font-family:Arial, sans-serif;font-size:12px;\">"
                . "<thead>"
                . "<tr>"
                . "<th style=\"padding:4px 8px;border:1px solid #ccc;background:#f5f5f5;\">Ticket</th>"
                . "<th style=\"padding:4px 8px;border:1px solid #ccc;background:#f5f5f5;\">Assunto</th>"
                . "<th style=\"padding:4px 8px;border:1px solid #ccc;background:#f5f5f5;\">Descrição</th>"
                . "<th style=\"padding:4px 8px;border:1px solid #ccc;background:#f5f5f5;\">Aberto por</th>"
                . "<th style=\"padding:4px 8px;border:1px solid #ccc;background:#f5f5f5;\">Horas no mês</th>"
                . "</tr>"
                . "</thead>"
                . "<tbody>"
                . $linhasTickets
                . "</tbody>"
                . "</table>"
                . "<br/><strong>Você pode acompanhar os tickets detalhadamente acessando o portal em: "
                . "<a href=\"" . $urlPortal . "\">" . $urlPortal . "</a></strong>"
                . "<br/><br />Atenciosamente,<br />" . $nomeEmpresa . '.';

            $subject = "Relatório mensal de horas técnicas - {$mesAnoLegivel} - {$nomeEmpresa}";

            $destinatarios = array_filter(array_map('trim', explode(';', (string)$cliente->emailresponsavel)));

            foreach ($destinatarios as $emailDest) {
                if (empty($emailDest)) {
                    continue;
                }

                $email = new Email();
                $email->transport('pgm');
                $from = 'helpdesk@pgm.inf.br';

                $email->from([$from => $nomeEmpresa])
                    ->to($emailDest)
                    ->emailFormat('html')
                    ->subject($subject);

                if ($email->send($message)) {
                    $io->success("Relatório mensal enviado para {$emailDest} (Cliente {$cliente->id})");
                } else {
                    $io->err("Falha ao enviar relatório mensal para {$emailDest} (Cliente {$cliente->id})");
                }
            }
        }

        $io->success('Fechamento mensal de horas técnicas concluído.');
        return static::CODE_SUCCESS;
    }
}