<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

if (!defined('C_RoleCliente')) { define('C_RoleCliente', 1); }

/**
 * Relatórios financeiros: Aging, Inadimplência, por Centro de Custo.
 */
class FinanceiroRelatoriosController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('FinanceiroLancamentos');
		$this->loadModel('Clientes');
		$this->loadModel('FinanceiroPlanoContas');
		$this->loadModel('FinanceiroCentrosCusto');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->set('title', 'Relatórios Financeiros');
	}

	public function isAuthorized($user) {
		if ((int)($user['role'] ?? 1) === C_RoleCliente) return false;
		return parent::isAuthorized($user);
	}

	/**
	 * Painel de relatórios — links para cada relatório.
	 */
	public function index() {
		$this->set('hideLayoutPageTitle', true);
	}

	/**
	 * Aging — títulos por faixa de atraso.
	 */
	public function aging() {
		$idempresa = (int)$this->Auth->user('idempresa');
		$tipo = $this->request->getQuery('tipo') ?? 'receita';

		$lancamentos = $this->FinanceiroLancamentos->find()
			->where([
				'FinanceiroLancamentos.idempresa' => $idempresa,
				'FinanceiroLancamentos.tipo' => $tipo,
				'FinanceiroLancamentos.status' => 'aberto',
				'FinanceiroLancamentos.data_vencimento IS NOT' => null,
			])
			->contain([
				'Clientes' => ['fields' => ['id', 'razaosocial', 'tipo', 'nome']],
			])
			->order(['data_vencimento' => 'ASC'])
			->toArray();

		$hoje = new \DateTime();
		$faixas = [
			'a_vencer'  => ['label' => 'A vencer',     'items' => [], 'total' => 0],
			'1_30'      => ['label' => '1-30 dias',     'items' => [], 'total' => 0],
			'31_60'     => ['label' => '31-60 dias',    'items' => [], 'total' => 0],
			'61_90'     => ['label' => '61-90 dias',    'items' => [], 'total' => 0],
			'90_mais'   => ['label' => '> 90 dias',     'items' => [], 'total' => 0],
		];

		foreach ($lancamentos as $l) {
			$venc = $l->data_vencimento;
			$diff = (int)$hoje->diff($venc)->format('%r%a');

			if ($diff >= 0) {
				$faixa = 'a_vencer';
			} elseif ($diff >= -30) {
				$faixa = '1_30';
			} elseif ($diff >= -60) {
				$faixa = '31_60';
			} elseif ($diff >= -90) {
				$faixa = '61_90';
			} else {
				$faixa = '90_mais';
			}

			$faixas[$faixa]['items'][] = $l;
			$faixas[$faixa]['total'] += (float)$l->valor;
		}

		$this->set(compact('faixas', 'tipo'));
		$this->set('title', 'Aging — ' . ($tipo === 'receita' ? 'Contas a Receber' : 'Contas a Pagar'));
	}

	/**
	 * Inadimplência — clientes com mais atraso e maior valor em aberto.
	 */
	public function inadimplencia() {
		$idempresa = (int)$this->Auth->user('idempresa');

		$lancamentos = $this->FinanceiroLancamentos->find()
			->where([
				'FinanceiroLancamentos.idempresa' => $idempresa,
				'FinanceiroLancamentos.tipo' => 'receita',
				'FinanceiroLancamentos.status' => 'aberto',
				'FinanceiroLancamentos.data_vencimento <' => date('Y-m-d'),
			])
			->contain([
				'Clientes' => ['fields' => ['id', 'razaosocial', 'tipo', 'nome']],
			])
			->toArray();

		$porCliente = [];
		$hoje = new \DateTime();
		foreach ($lancamentos as $l) {
			$cid = $l->idcliente ?: 0;
			if (!isset($porCliente[$cid])) {
				$nome = '(Sem cliente)';
				if (!empty($l->cliente)) {
					$nome = ($l->cliente->tipo == 1) ? ($l->cliente->nome ?? '—') : ($l->cliente->razaosocial ?? '—');
				}
				$porCliente[$cid] = ['nome' => $nome, 'total' => 0, 'qtd' => 0, 'max_dias' => 0];
			}
			$porCliente[$cid]['total'] += (float)$l->valor;
			$porCliente[$cid]['qtd']++;
			$dias = (int)$hoje->diff($l->data_vencimento)->format('%a');
			if ($dias > $porCliente[$cid]['max_dias']) {
				$porCliente[$cid]['max_dias'] = $dias;
			}
		}

		// Sort by total desc
		uasort($porCliente, function ($a, $b) { return $b['total'] <=> $a['total']; });

		$this->set(compact('porCliente'));
		$this->set('title', 'Inadimplência');
	}

	/**
	 * Relatório por Centro de Custo.
	 */
	public function porCentroCusto() {
		$idempresa = (int)$this->Auth->user('idempresa');
		$periodo = $this->request->getQuery('periodo') ?? date('Y-m');

		if (preg_match('/^(\d{4})-(\d{2})$/', $periodo)) {
			$dataInicio = $periodo . '-01';
			$dataFim = date('Y-m-t', strtotime($dataInicio));
		} else {
			$dataInicio = $periodo . '-01-01';
			$dataFim = $periodo . '-12-31';
		}

		$lancamentos = $this->FinanceiroLancamentos->find()
			->where([
				'FinanceiroLancamentos.idempresa' => $idempresa,
				'FinanceiroLancamentos.status IN' => ['recebido', 'pago'],
				'FinanceiroLancamentos.data_recebimento >=' => $dataInicio,
				'FinanceiroLancamentos.data_recebimento <=' => $dataFim,
			])
			->contain([
				'FinanceiroCentrosCusto' => ['fields' => ['id', 'codigo', 'descricao']],
			])
			->toArray();

		$porCentro = [];
		foreach ($lancamentos as $l) {
			$ccLabel = '(Sem centro de custo)';
			$ccKey = '___sem';
			if (!empty($l->financeiro_centros_custo)) {
				$ccLabel = $l->financeiro_centros_custo->codigo . ' — ' . $l->financeiro_centros_custo->descricao;
				$ccKey = $l->financeiro_centros_custo->codigo;
			}
			if (!isset($porCentro[$ccKey])) {
				$porCentro[$ccKey] = ['label' => $ccLabel, 'receita' => 0, 'despesa' => 0];
			}
			if ($l->tipo === 'receita') {
				$porCentro[$ccKey]['receita'] += (float)$l->valor;
			} else {
				$porCentro[$ccKey]['despesa'] += (float)$l->valor;
			}
		}

		ksort($porCentro);

		$this->set(compact('porCentro', 'periodo'));
		$this->set('title', 'Relatório por Centro de Custo');
	}

	/* ── Exportações ──────────────────────────────────────────────────── */

	/**
	 * Exportar aging em Excel.
	 */
	public function exportarAgingExcel() {
		$this->autoRender = false;
		$idempresa = (int)$this->Auth->user('idempresa');
		$tipo = $this->request->getQuery('tipo') ?? 'receita';

		$lancamentos = $this->FinanceiroLancamentos->find()
			->where([
				'idempresa' => $idempresa, 'tipo' => $tipo,
				'status' => 'aberto', 'data_vencimento IS NOT' => null,
			])
			->contain(['Clientes' => ['fields' => ['id', 'razaosocial', 'tipo', 'nome']]])
			->order(['data_vencimento' => 'ASC'])
			->toArray();

		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setTitle('Aging');
		$sheet->fromArray(['Cliente', 'Descrição', 'Valor', 'Vencimento', 'Dias atraso'], null, 'A1');

		$hoje = new \DateTime();
		$row = 2;
		foreach ($lancamentos as $l) {
			$nome = '—';
			if (!empty($l->cliente)) {
				$nome = ($l->cliente->tipo == 1) ? ($l->cliente->nome ?? '—') : ($l->cliente->razaosocial ?? '—');
			}
			$dias = (int)$hoje->diff($l->data_vencimento)->format('%r%a');
			$sheet->fromArray([
				$nome,
				$l->descricao,
				(float)$l->valor,
				$l->data_vencimento ? $l->data_vencimento->format('d/m/Y') : '',
				$dias < 0 ? abs($dias) : 0,
			], null, "A$row");
			$row++;
		}

		$sheet->getStyle('C2:C' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');

		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
		$tmp = tempnam(TMP, 'aging_') . '.xlsx';
		$writer->save($tmp);

		$fn = 'aging-' . $tipo . '-' . date('Ymd-His') . '.xlsx';
		return $this->response->withFile($tmp, ['download' => true, 'name' => $fn]);
	}

	/**
	 * Exportar inadimplência em Excel.
	 */
	public function exportarInadimplenciaExcel() {
		$this->autoRender = false;
		$idempresa = (int)$this->Auth->user('idempresa');

		$lancamentos = $this->FinanceiroLancamentos->find()
			->where([
				'idempresa' => $idempresa, 'tipo' => 'receita',
				'status' => 'aberto', 'data_vencimento <' => date('Y-m-d'),
			])
			->contain(['Clientes' => ['fields' => ['id', 'razaosocial', 'tipo', 'nome']]])
			->toArray();

		$porCliente = [];
		$hoje = new \DateTime();
		foreach ($lancamentos as $l) {
			$cid = $l->idcliente ?: 0;
			if (!isset($porCliente[$cid])) {
				$nome = '(Sem cliente)';
				if (!empty($l->cliente)) {
					$nome = ($l->cliente->tipo == 1) ? ($l->cliente->nome ?? '—') : ($l->cliente->razaosocial ?? '—');
				}
				$porCliente[$cid] = ['nome' => $nome, 'total' => 0, 'qtd' => 0, 'max_dias' => 0];
			}
			$porCliente[$cid]['total'] += (float)$l->valor;
			$porCliente[$cid]['qtd']++;
			$dias = (int)$hoje->diff($l->data_vencimento)->format('%a');
			if ($dias > $porCliente[$cid]['max_dias']) $porCliente[$cid]['max_dias'] = $dias;
		}
		uasort($porCliente, function ($a, $b) { return $b['total'] <=> $a['total']; });

		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setTitle('Inadimplência');
		$sheet->fromArray(['Cliente', 'Títulos vencidos', 'Total em atraso', 'Maior atraso (dias)'], null, 'A1');

		$row = 2;
		foreach ($porCliente as $c) {
			$sheet->fromArray([$c['nome'], $c['qtd'], $c['total'], $c['max_dias']], null, "A$row");
			$row++;
		}
		$sheet->getStyle('C2:C' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');

		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
		$tmp = tempnam(TMP, 'inad_') . '.xlsx';
		$writer->save($tmp);

		$fn = 'inadimplencia-' . date('Ymd-His') . '.xlsx';
		return $this->response->withFile($tmp, ['download' => true, 'name' => $fn]);
	}

	/**
	 * Exportar relatório por centro de custo em Excel.
	 */
	public function exportarCentroCustoExcel() {
		$this->autoRender = false;
		$idempresa = (int)$this->Auth->user('idempresa');
		$periodo = $this->request->getQuery('periodo') ?? date('Y-m');

		if (preg_match('/^(\d{4})-(\d{2})$/', $periodo)) {
			$dataInicio = $periodo . '-01';
			$dataFim = date('Y-m-t', strtotime($dataInicio));
		} else {
			$dataInicio = $periodo . '-01-01';
			$dataFim = $periodo . '-12-31';
		}

		$lancamentos = $this->FinanceiroLancamentos->find()
			->where([
				'idempresa' => $idempresa,
				'status IN' => ['recebido', 'pago'],
				'data_recebimento >=' => $dataInicio,
				'data_recebimento <=' => $dataFim,
			])
			->contain(['FinanceiroCentrosCusto' => ['fields' => ['id', 'codigo', 'descricao']]])
			->toArray();

		$porCentro = [];
		foreach ($lancamentos as $l) {
			$ccLabel = '(Sem centro de custo)';
			$ccKey = '___sem';
			if (!empty($l->financeiro_centros_custo)) {
				$ccLabel = $l->financeiro_centros_custo->codigo . ' — ' . $l->financeiro_centros_custo->descricao;
				$ccKey = $l->financeiro_centros_custo->codigo;
			}
			if (!isset($porCentro[$ccKey])) $porCentro[$ccKey] = ['label' => $ccLabel, 'receita' => 0, 'despesa' => 0];
			if ($l->tipo === 'receita') $porCentro[$ccKey]['receita'] += (float)$l->valor;
			else $porCentro[$ccKey]['despesa'] += (float)$l->valor;
		}
		ksort($porCentro);

		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setTitle('Centro de Custo');
		$sheet->fromArray(['Centro de Custo', 'Receitas', 'Despesas', 'Saldo'], null, 'A1');

		$row = 2;
		foreach ($porCentro as $c) {
			$sheet->fromArray([$c['label'], $c['receita'], $c['despesa'], $c['receita'] - $c['despesa']], null, "A$row");
			$row++;
		}
		$sheet->getStyle('B2:D' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');

		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
		$tmp = tempnam(TMP, 'cc_') . '.xlsx';
		$writer->save($tmp);

		$fn = 'centro-custo-' . $periodo . '-' . date('Ymd-His') . '.xlsx';
		return $this->response->withFile($tmp, ['download' => true, 'name' => $fn]);
	}
}
