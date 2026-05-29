<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\I18n\FrozenDate;
use Cake\I18n\Time;

/**
 * Helpers compartilhados entre builders do módulo Financeiro (protótipo premium).
 */
class FinanceiroPrototypeSupport {

	/**
	 * @param object $l
	 */
	public static function isReceitaPaga($l): bool {
		$status = strtolower((string)$l->get('status'));
		if (in_array($status, ['recebido', 'pago', 'baixado'], true)) {
			return true;
		}
		$baixa = $l->get('data_baixa');
		$rec = $l->get('data_recebimento');

		return $baixa instanceof \DateTimeInterface || $rec instanceof \DateTimeInterface;
	}

	/**
	 * @param object $l
	 */
	public static function isDespesaPaga($l): bool {
		$status = strtolower((string)$l->get('status'));
		if (in_array($status, ['pago', 'recebido', 'baixado'], true)) {
			return true;
		}

		return $l->get('data_baixa') instanceof \DateTimeInterface;
	}

	/**
	 * @return array{state:string,badge:string,label:string,dias:?int,row_bg:string}
	 */
	public static function classifyReceita($l, Time $now): array {
		$pago = self::isReceitaPaga($l);
		$venc = $l->get('data_vencimento');
		if ($pago) {
			return [
				'state' => 'pago',
				'badge' => 'paga',
				'label' => '✓ Pago',
				'dias' => null,
				'row_bg' => '',
			];
		}
		if (!$venc instanceof \DateTimeInterface) {
			return [
				'state' => 'pendente',
				'badge' => 'pendente',
				'label' => 'A receber',
				'dias' => null,
				'row_bg' => '',
			];
		}
		$vencDate = FrozenDate::parse($venc->format('Y-m-d'));
		$today = FrozenDate::parse($now->format('Y-m-d'));
		$diff = (int)$today->diff($vencDate)->format('%r%a');
		if ($diff < 0) {
			$dias = abs($diff);

			return [
				'state' => 'atraso',
				'badge' => 'vencida',
				'label' => '⚠ ' . $dias . 'd atraso',
				'dias' => $dias,
				'row_bg' => '#FCF0F1',
			];
		}
		if ($diff <= 7) {
			return [
				'state' => 'vencendo',
				'badge' => 'vencendo',
				'label' => '⏰ ' . $diff . 'd',
				'dias' => $diff,
				'row_bg' => '#FFFBF0',
			];
		}

		return [
			'state' => 'pendente',
			'badge' => 'pendente',
			'label' => 'A receber',
			'dias' => $diff,
			'row_bg' => '',
		];
	}

	/**
	 * @return array{state:string,badge:string,label:string,dias:?int,row_bg:string,valor_color:string,action:string,action_class:string}
	 */
	public static function classifyDespesa($l, Time $now): array {
		$pago = self::isDespesaPaga($l);
		$venc = $l->get('data_vencimento');
		$status = strtolower((string)$l->get('status'));
		if ($pago) {
			return [
				'state' => 'pago',
				'badge' => 'paga',
				'label' => '✓ Pago',
				'dias' => null,
				'row_bg' => '',
				'valor_color' => '',
				'action' => 'recibo',
				'action_class' => 'btn btn-ghost btn-xs',
			];
		}
		if (!$venc instanceof \DateTimeInterface) {
			return [
				'state' => 'aberto',
				'badge' => 'pendente',
				'label' => '⏳ Aguarda aprov.',
				'dias' => null,
				'row_bg' => '',
				'valor_color' => '',
				'action' => 'aprovar',
				'action_class' => 'btn btn-primary btn-xs',
			];
		}
		$vencDate = FrozenDate::parse($venc->format('Y-m-d'));
		$today = FrozenDate::parse($now->format('Y-m-d'));
		$diff = (int)$today->diff($vencDate)->format('%r%a');
		if ($diff < 0) {
			$dias = abs($diff);

			return [
				'state' => 'vencido',
				'badge' => 'vencida',
				'label' => '⚠ Vencido ' . $dias . 'd',
				'dias' => $dias,
				'row_bg' => '#FEF2F2',
				'valor_color' => '#7A1822',
				'action' => 'pagar',
				'action_class' => 'btn btn-red btn-xs',
			];
		}
		if ($diff <= 7) {
			return [
				'state' => 'proximo',
				'badge' => 'vencendo',
				'label' => '⏰ Próximo',
				'dias' => $diff,
				'row_bg' => '#FFFBF0',
				'valor_color' => '#8A4D02',
				'action' => 'agendar',
				'action_class' => 'btn btn-amber btn-xs',
			];
		}
		if (strpos($status, 'aprov') !== false) {
			return [
				'state' => 'aprovado',
				'badge' => 'aprov',
				'label' => '✓ Aprovado',
				'dias' => $diff,
				'row_bg' => '',
				'valor_color' => '',
				'action' => 'menu',
				'action_class' => 'btn btn-ghost btn-xs',
			];
		}

		return [
			'state' => 'aberto',
			'badge' => 'pendente',
			'label' => '⏳ Aguarda aprov.',
			'dias' => $diff,
			'row_bg' => '',
			'valor_color' => '',
			'action' => 'aprovar',
			'action_class' => 'btn btn-primary btn-xs',
		];
	}

	public static function tituloCodigo(int $id, $refDate = null): string {
		$y = date('Y');
		if ($refDate instanceof \DateTimeInterface) {
			$y = $refDate->format('Y');
		}

		return sprintf('TIT-%s-%04d', $y, $id);
	}

	public static function despesaCodigo(int $id, $refDate = null): string {
		$y = date('Y');
		if ($refDate instanceof \DateTimeInterface) {
			$y = $refDate->format('Y');
		}

		return sprintf('CP-%s-%04d', $y, $id);
	}

	/**
	 * @param object|null $cliente
	 * @return array{nome:string,cnpj:string}
	 */
	public static function clienteInfo($cliente): array {
		if ($cliente === null) {
			return ['nome' => '—', 'cnpj' => ''];
		}
		$nome = (string)($cliente->get('razaosocial') ?? $cliente->get('nome') ?? '');
		$cnpj = (string)($cliente->get('cnpj') ?? $cliente->get('cpf') ?? '');

		return ['nome' => $nome !== '' ? $nome : '—', 'cnpj' => $cnpj];
	}

	/**
	 * @param object|null $banco
	 */
	public static function bancoLabel($banco): string {
		if ($banco === null) {
			return '—';
		}
		$codigo = (string)($banco->get('codigo_banco') ?? $banco->get('numero_banco') ?? '');
		$nome = (string)$banco->get('nome');
		$brand = FinanceiroBancosPrototypeUi::branding($codigo, $nome);
		[$ag] = FinanceiroBancosPrototypeUi::formatAgenciaConta($banco);
		$agShort = preg_replace('/\D/', '', $ag);
		if (strlen($agShort) > 4) {
			$agShort = substr($agShort, 0, 4);
		}

		return $brand['sigla'] . ' · ' . ($agShort !== '' ? $agShort : '—');
	}

	/**
	 * @param object $l
	 * @param object|null $fat
	 * @return array{label:string,badge:string,url:?array}
	 */
	public static function origemReceita($l, $fat): array {
		if ($fat !== null) {
			$idorc = (int)($fat->get('idorcamento') ?? 0);
			if ($idorc > 0) {
				return [
					'label' => 'Orç #' . $idorc,
					'badge' => 'aprov',
					'url' => ['controller' => 'Orcamentos', 'action' => 'view', $idorc],
				];
			}
			$idordem = (int)($fat->get('idordem') ?? 0);
			if ($idordem > 0) {
				return [
					'label' => 'OS #' . $idordem,
					'badge' => 'corretiva',
					'url' => ['controller' => 'Ordensservico', 'action' => 'view', $idordem],
				];
			}
			$num = trim((string)($fat->get('numero') ?? ''));
			if ($num !== '') {
				return [
					'label' => $num,
					'badge' => 'aprov',
					'url' => ['controller' => 'Faturamento', 'action' => 'view', (int)$fat->get('id')],
				];
			}
		}
		$desc = trim((string)$l->get('descricao'));

		return [
			'label' => $desc !== '' ? $desc : '—',
			'badge' => 'arq',
			'url' => null,
		];
	}

	/**
	 * @param array<int,object> $rows
	 * @return array<int,string>
	 */
	public static function parcelaMap(array $rows): array {
		$groups = [];
		foreach ($rows as $row) {
			$fid = (int)($row->get('idfaturamento') ?? 0);
			if ($fid > 0) {
				$groups[$fid][] = $row;
			}
		}
		$map = [];
		foreach ($groups as $items) {
			usort($items, static function ($a, $b) {
				$da = $a->get('data_vencimento');
				$db = $b->get('data_vencimento');
				$sa = $da instanceof \DateTimeInterface ? $da->format('Y-m-d') : '9999-99-99';
				$sb = $db instanceof \DateTimeInterface ? $db->format('Y-m-d') : '9999-99-99';

				return strcmp($sa, $sb);
			});
			$total = count($items);
			foreach ($items as $i => $item) {
				$map[(int)$item->get('id')] = ($i + 1) . '/' . $total;
			}
		}

		return $map;
	}
}
