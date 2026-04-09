<?php
/**
 * Funções globais legado PGM (datas BR d/m/Y, rótulos OS/tickets).
 * Requer TicketConstants.php carregado antes das chamadas que usam C_*.
 */

use DateTime;
use DateTimeImmutable;
use DateTimeZone;

/**
 * @param string $dmY
 * @return DateTimeImmutable|null
 */
function _pgm_parse_br($dmY) {
	$s = trim((string)$dmY);
	if ($s === '') {
		return null;
	}
	$d = DateTimeImmutable::createFromFormat('d/m/Y', $s, new DateTimeZone('America/Sao_Paulo'));
	return $d !== false ? $d : null;
}

function dataAtual() {
	$tz = new DateTimeZone('America/Sao_Paulo');
	return (new DateTimeImmutable('now', $tz))->format('d/m/Y');
}

function primeiroDiaMes($dmY) {
	$d = _pgm_parse_br($dmY);
	if ($d === null) {
		return dataAtual();
	}
	$tz = $d->getTimezone();
	$y = (int)$d->format('Y');
	$m = (int)$d->format('m');
	return (new DateTimeImmutable(sprintf('%04d-%02d-01', $y, $m), $tz))->format('d/m/Y');
}

function ultimoDiaMes($dmY) {
	$d = _pgm_parse_br($dmY);
	if ($d === null) {
		return dataAtual();
	}
	$tz = $d->getTimezone();
	$y = (int)$d->format('Y');
	$m = (int)$d->format('m');
	$first = new DateTimeImmutable(sprintf('%04d-%02d-01', $y, $m), $tz);
	$last = $first->modify('last day of this month');

	return $last->format('d/m/Y');
}

function increaseMonths($dmY) {
	$d = _pgm_parse_br($dmY);
	if ($d === null) {
		return $dmY;
	}
	return $d->modify('+1 month')->format('d/m/Y');
}

function decreaseMonths($dmY, $n) {
	$d = _pgm_parse_br($dmY);
	if ($d === null) {
		return $dmY;
	}
	$k = max(0, (int)$n);

	return $d->modify('-' . $k . ' month')->format('d/m/Y');
}

function descricaoMes($dmY) {
	$d = _pgm_parse_br($dmY);
	if ($d === null) {
		return '';
	}
	static $meses = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
	$m = (int)$d->format('n');

	return $meses[$m - 1] . '/' . $d->format('Y');
}

function primeiroDiaSemana() {
	$tz = new DateTimeZone('America/Sao_Paulo');
	$d = new DateTime('now', $tz);
	$d->modify('monday this week');

	return $d->format('d/m/Y');
}

function ultimoDiaSemana() {
	$tz = new DateTimeZone('America/Sao_Paulo');
	$d = new DateTime('now', $tz);
	$d->modify('sunday this week');

	return $d->format('d/m/Y');
}

/**
 * Texto simples da situação da OS (flash, relatórios).
 *
 * @param int|string|null $situacao
 * @return string
 */
function DescricaoSituacaoOrdem($situacao) {
	if (!defined('C_OrdensSituacao') || !is_array(C_OrdensSituacao)) {
		return (string)$situacao;
	}
	$map = C_OrdensSituacao;
	$k = (string)$situacao;
	if (isset($map[$k])) {
		return (string)$map[$k];
	}
	$ki = (int)$situacao;
	if (isset($map[$ki])) {
		return (string)$map[$ki];
	}

	return (string)$situacao;
}

/**
 * Rótulo HTML da situação da OS (listagens).
 *
 * @param int|string|null $situacao
 * @return string
 */
function SituacaoOrdem($situacao) {
	$t = DescricaoSituacaoOrdem($situacao);

	return '<span class="os-situacao">' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '</span>';
}

/**
 * @param int|string|null $valor chave em C_OrdensContrato
 * @return string
 */
function OrdensContrato($valor) {
	if (!defined('C_OrdensContrato') || !is_array(C_OrdensContrato)) {
		return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
	}
	$map = C_OrdensContrato;
	$t = $map[(string)$valor] ?? $map[(int)$valor] ?? (string)$valor;

	return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8');
}

/**
 * @param int|string|null $valor chave em C_OrdensAtendimento
 * @return string
 */
function OrdensAtendimento($valor) {
	if (!defined('C_OrdensAtendimento') || !is_array(C_OrdensAtendimento)) {
		return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
	}
	$map = C_OrdensAtendimento;
	$t = $map[(string)$valor] ?? $map[(int)$valor] ?? (string)$valor;

	return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8');
}

/**
 * Rótulo HTML do assunto do ticket.
 *
 * @param int|string|null $assunto
 * @return string
 */
function AssuntoTicket($assunto) {
	if (defined('C_TicketCategoria') && is_array(C_TicketCategoria)) {
		$map = C_TicketCategoria;
		$t = $map[(string)$assunto] ?? $map[(int)$assunto] ?? null;
		if ($t !== null) {
			return '<span class="ticket-assunto">' . htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8') . '</span>';
		}
	}

	return '<span class="ticket-assunto">' . htmlspecialchars((string)$assunto, ENT_QUOTES, 'UTF-8') . '</span>';
}

/**
 * Rótulo HTML da situação do ticket.
 *
 * @param int|string|null $situacao
 * @return string
 */
function SituacaoTicket($situacao) {
	$labels = [];
	if (defined('C_TicketSituacaoPendente')) {
		$labels[(string)(int)C_TicketSituacaoPendente] = 'Aguardando técnico';
	}
	if (defined('C_TicketSituacaoEmandamento')) {
		$labels[(string)(int)C_TicketSituacaoEmandamento] = 'Em execução';
	}
	if (defined('C_TicketSituacaoResolvido')) {
		$labels[(string)(int)C_TicketSituacaoResolvido] = 'Resolvido';
	}
	if (defined('C_TicketSituacaoFechado')) {
		$labels[(string)(int)C_TicketSituacaoFechado] = 'Fechado';
	}
	if (defined('C_TicketSituacaoRespondido')) {
		$labels[(string)(int)C_TicketSituacaoRespondido] = 'Respondido';
	}
	if (defined('C_TicketSituacaoCancelado')) {
		$labels[(string)(int)C_TicketSituacaoCancelado] = 'Cancelado';
	}
	$sk = (string)(int)$situacao;
	$text = $labels[$sk] ?? $labels[(string)$situacao] ?? (string)$situacao;

	return '<span class="ticket-situacao">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</span>';
}