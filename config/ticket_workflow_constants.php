<?php
/**
 * Movimentações extras em ticketsmovs.sitnova (valores fora do enum de situação do ticket).
 * Evite colisão com TicketConstants do pacote PGM (timer/anexos costumam usar 10–20).
 */
if (!defined('C_TicketMovTransferencia')) {
	define('C_TicketMovTransferencia', 951);
}
if (!defined('C_TicketMovMudancaFila')) {
	define('C_TicketMovMudancaFila', 952);
}
