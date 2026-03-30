<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

require_once ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';

/**
 * Módulo Financeiro
 * Dashboard, Contas a Receber, Contas a Pagar e Fluxo de Caixa.
 */
class FinanceiroController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('FinanceiroLancamentos');
		$this->loadModel('Clientes');
		$this->loadModel('Faturamento');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->set('title', 'Financeiro');
	}

	public function isAuthorized($user) {
		if ((int)($user['role'] ?? 1) === C_RoleCliente) {
			return false;
		}
		return parent::isAuthorized($user);
	}

	/* ── Dashboard financeiro ──────────────────────────────────────────── */
	public function index() {
		$idempresa = $this->Auth->user('idempresa');

		$lancamentos = $this->FinanceiroLancamentos->find('all')
			->where(['FinanceiroLancamentos.idempresa' => $idempresa])
			->contain(['Clientes' => ['fields' => ['id', 'razaosocial', 'tipo', 'nome']]])
			->order(['FinanceiroLancamentos.data_vencimento' => 'ASC'])
			->toArray();

		// KPIs
		$kpi = [
			'total_receitas'  => 0,
			'total_despesas'  => 0,
			'a_receber'       => 0,
			'a_pagar'         => 0,
			'vencidos'        => 0,
			'recebido_mes'    => 0,
		];
		$hoje = date('Y-m-d');
		$mesAtual = date('Y-m');

		foreach ($lancamentos as $l) {
			if ($l->tipo === 'receita') {
				$kpi['total_receitas'] += $l->valor;
				if ($l->status === 'aberto') {
					$kpi['a_receber'] += $l->valor;
					if ($l->data_vencimento && $l->data_vencimento->format('Y-m-d') < $hoje) {
						$kpi['vencidos'] += $l->valor;
					}
				}
				if ($l->status === 'recebido' && $l->data_recebimento
					&& $l->data_recebimento->format('Y-m') === $mesAtual) {
					$kpi['recebido_mes'] += $l->valor;
				}
			} else {
				$kpi['total_despesas'] += $l->valor;
				if ($l->status === 'aberto') $kpi['a_pagar'] += $l->valor;
			}
		}

		// Últimos 6 meses — receitas x despesas por mês
		$grafico = [];
		for ($i = 5; $i >= 0; $i--) {
			$mes = date('Y-m', strtotime("-$i months"));
			$grafico[$mes] = ['receita' => 0, 'despesa' => 0];
		}
		foreach ($lancamentos as $l) {
			$mes = $l->data_lancamento ? $l->data_lancamento->format('Y-m') : null;
			if ($mes && isset($grafico[$mes])) {
				$grafico[$mes][$l->tipo === 'receita' ? 'receita' : 'despesa'] += $l->valor;
			}
		}

		// Próximos vencimentos (30 dias)
		$vencimentos = array_filter($lancamentos, function($l) use ($hoje) {
			if ($l->status !== 'aberto' || !$l->data_vencimento) return false;
			$dv = $l->data_vencimento->format('Y-m-d');
			return $dv >= $hoje && $dv <= date('Y-m-d', strtotime('+30 days'));
		});

		$this->set(compact('kpi', 'grafico', 'vencimentos', 'lancamentos'));
		$this->set('hideLayoutPageTitle', true);
	}

	/* ── Contas a receber ──────────────────────────────────────────────── */
	public function contasReceber() {
		$idempresa = $this->Auth->user('idempresa');
		$cliente   = $this->request->getQuery('cliente') ?? '';
		$status    = $this->request->getQuery('status') ?? 'aberto';

		$q = $this->FinanceiroLancamentos->find('all')
			->where([
				'FinanceiroLancamentos.idempresa' => $idempresa,
				'FinanceiroLancamentos.tipo'      => 'receita',
			])
			->contain([
				'Clientes'    => ['fields' => ['id', 'razaosocial', 'tipo', 'nome']],
				'Faturamento' => ['fields' => ['id', 'numero']],
			])
			->order(['FinanceiroLancamentos.data_vencimento' => 'ASC']);

		if ($status !== '') $q->where(['FinanceiroLancamentos.status' => $status]);
		if ($cliente !== '' && $cliente !== '0') $q->where(['FinanceiroLancamentos.idcliente' => $cliente]);

		$lancamentos = $q->toArray();

		$clientes = $this->Clientes->find('list', [
			'keyField'   => 'id',
			'valueField' => 'razaosocial',
		])->where(['idempresa' => $idempresa, 'inativo' => 0])->order(['razaosocial'])->toArray();

		$this->set(compact('lancamentos', 'clientes', 'status', 'cliente'));
		$this->set('title', 'Contas a Receber');
	}

	/* ── Registrar recebimento ─────────────────────────────────────────── */
	public function registrarRecebimento($id = null) {
		$this->request->allowMethod(['post']);
		$idempresa = $this->Auth->user('idempresa');

		$lancamento = $this->FinanceiroLancamentos->find('all')
			->where(['id' => $id, 'idempresa' => $idempresa])->first();

		if (empty($lancamento)) {
			return $this->jsonResponse(['ok' => false, 'msg' => 'Não encontrado.'], 404);
		}

		$lancamento->status          = 'recebido';
		$lancamento->data_recebimento = $this->request->getData('data_recebimento') ?? date('Y-m-d');
		$this->FinanceiroLancamentos->save($lancamento);

		// Atualiza status do faturamento vinculado
		if (!empty($lancamento->idfaturamento)) {
			$fat = $this->Faturamento->find('all')
				->where(['id' => $lancamento->idfaturamento])->first();
			if (!empty($fat)) {
				$fat->status = 'pago';
				$this->Faturamento->save($fat);
			}
		}

		return $this->jsonResponse(['ok' => true]);
	}
}
