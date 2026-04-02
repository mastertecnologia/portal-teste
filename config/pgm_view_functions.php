<?php
/**
 * Função usada em templates (.ctp) de locação/faturas (ex.: Faturas/edit.ctp).
 * Evita "Call to undefined function LocacaoStatus" quando vendor/PGMPackages
 * não expõe esse helper. (OrdensPagamento permanece em Utilities.php para
 * evitar conflito de redeclare com pacotes legados.)
 */
if (!function_exists('LocacaoStatus')) {
	function LocacaoStatus($status) {
		$pend = defined('C_LocacaoStatusPendente') ? (int) C_LocacaoStatusPendente : 1;
		$apr = defined('C_LocacaoStatusAprovado') ? (int) C_LocacaoStatusAprovado : 2;
		$rej = defined('C_LocacaoStatusRejeitado') ? (int) C_LocacaoStatusRejeitado : 3;
		$fin = defined('C_LocacaoStatusFinalizado') ? (int) C_LocacaoStatusFinalizado : 4;
		$map = [
			$pend => 'Pendente',
			$apr => 'Aprovado',
			$rej => 'Rejeitado',
			$fin => 'Finalizado',
		];
		$s = (int) $status;
		$out = $map[$s] ?? (string) $status;

		return htmlspecialchars($out, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}
}
