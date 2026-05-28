<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\ErpPrototypeRbacTrait;
use Cake\Event\Event;
use Cake\I18n\Time;

/**
 * Dashboard ERP unificado (pg-home do mock pgm_erp_completo.html).
 */
class ErpHomePrototypeController extends AppController {

	use ErpPrototypeRbacTrait;

	public function initialize() {
		parent::initialize();
		$this->loadModel('Orcamentos');
		$this->loadModel('Ordensservico');
		$this->loadModel('Tickets');
		$this->loadModel('Clientes');
		$this->loadModel('Faturas');
	}

	public function beforeFilter(Event $event) {
		$redirect = $this->request->getRequestTarget();
		$staffLogin = [
			'controller' => 'Users',
			'action' => 'acessoEmpresa',
			'prefix' => false,
			'?' => ['redirect' => $redirect],
		];
		$this->Auth->setConfig('loginAction', $staffLogin);
		$this->Auth->setConfig('unauthorizedRedirect', $staffLogin);
		parent::beforeFilter($event);
	}

	public function index() {
		$empresa = (int)$this->Auth->user('idempresa');
		$now = Time::now();
		$monthStart = $now->copy()->startOfMonth();

		$kpi = [
			'orcamentos_mes' => 0,
			'orcamentos_valor' => 0.0,
			'os_abertas' => 0,
			'tickets_abertos' => 0,
			'clientes_ativos' => 0,
			'cr_receber' => 0.0,
		];
		$recentOrc = [];
		$recentOs = [];

		try {
			foreach ($this->Orcamentos->find()
				->where(['Orcamentos.idempresa' => $empresa, 'Orcamentos.created >=' => $monthStart])
				->contain(['Clientes'])
				->order(['Orcamentos.created' => 'DESC'])
				->limit(5)
				->all() as $o) {
				$kpi['orcamentos_mes']++;
				$kpi['orcamentos_valor'] += (float)($o->get('valortotal') ?? $o->get('valor') ?? 0);
				$cl = $o->cliente ?? null;
				$recentOrc[] = [
					'id' => (int)$o->get('id'),
					'label' => sprintf('ORC-%04d', (int)$o->get('id')),
					'cliente' => $cl ? (string)($cl->get('razaosocial') ?? $cl->get('nome') ?? '') : '—',
					'valor' => (float)($o->get('valortotal') ?? $o->get('valor') ?? 0),
				];
			}
		} catch (\Throwable $e) {
		}

		try {
			$kpi['os_abertas'] = $this->Ordensservico->find()
				->where(['Ordensservico.idempresa' => $empresa])
				->count();
			foreach ($this->Ordensservico->find()
				->contain(['Clientes'])
				->where(['Ordensservico.idempresa' => $empresa])
				->order(['Ordensservico.created' => 'DESC'])
				->limit(5)
				->all() as $os) {
				$cl = $os->cliente ?? null;
				$recentOs[] = [
					'id' => (int)$os->get('id'),
					'label' => (string)($os->get('nroordem') ?? ('OS-' . (int)$os->get('id'))),
					'cliente' => $cl ? (string)($cl->get('razaosocial') ?? $cl->get('nome') ?? '') : '—',
				];
			}
		} catch (\Throwable $e) {
		}

		try {
			$q = $this->Tickets->find()->where(['Tickets.idempresa' => $empresa]);
			$this->Abac->applyToQuery($q, 'Tickets', 'Tickets');
			$kpi['tickets_abertos'] = $q->count();
		} catch (\Throwable $e) {
		}

		try {
			$kpi['clientes_ativos'] = $this->Clientes->find()
				->where(['Clientes.idempresa' => $empresa, 'Clientes.inativo' => 0])
				->count();
		} catch (\Throwable $e) {
		}

		try {
			foreach ($this->Faturas->find()->where(['Faturas.idempresa' => $empresa])->all() as $f) {
				$st = strtolower((string)($f->get('status') ?? ''));
				if (strpos($st, 'pag') === false && !$f->get('dtretorno') instanceof \DateTimeInterface) {
					$kpi['cr_receber'] += (float)($f->get('valor') ?? 0);
				}
			}
		} catch (\Throwable $e) {
		}

		$user = (array)$this->Auth->user();
		$nomeCompleto = trim((string)($user['nome'] ?? $user['username'] ?? ''));
		$primeiroNome = $nomeCompleto !== '' ? explode(' ', $nomeCompleto)[0] : __('usuário');

		$activity = [];
		foreach ($recentOrc as $r) {
			$activity[] = [
				'icon' => '📧',
				'bg' => '#FAEEDA',
				'title' => sprintf('%s · %s', $r['label'], $r['cliente']),
				'sub' => sprintf(__('Orçamento · %s'), 'R$ ' . number_format((float)$r['valor'], 2, ',', '.')),
			];
		}
		foreach ($recentOs as $r) {
			$activity[] = [
				'icon' => '🔧',
				'bg' => 'var(--blue-light)',
				'title' => sprintf('%s · %s', $r['label'], $r['cliente']),
				'sub' => __('Ordem de serviço recente'),
			];
		}
		$activity = array_slice($activity, 0, 5);

		$this->set([
			'title' => __('Dashboard'),
			'erpNavActive' => 'erp-home',
			'erpBreadcrumb' => [
				['label' => __('Dashboard'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'homeKpi' => $kpi,
			'homeRecentOrc' => $recentOrc,
			'homeRecentOs' => $recentOs,
			'homeActivity' => $activity,
			'homeUserFirstName' => $primeiroNome,
			'homeDataLabel' => $now->i18nFormat('EEEE, dd/MM/yyyy'),
		]);

		return $this->render('index');
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function loadEmpresasParaTopbar(): array {
		try {
			$tbl = $this->loadModel('Empresas');
		} catch (\Throwable $e) {
			return [];
		}
		$active = (int)$this->Auth->user('idempresa');
		$out = [];
		try {
			foreach ($tbl->find()->order(['id' => 'ASC'])->limit(20)->all() as $e) {
				$nome = (string)($e->get('razaosocial') ?? $e->get('nome') ?? '');
				if ($nome === '') {
					continue;
				}
				$out[] = [
					'id' => (int)$e->get('id'),
					'nome' => $nome,
					'cnpj' => (string)($e->get('cnpj') ?? ''),
					'current' => (int)$e->get('id') === $active,
				];
			}
		} catch (\Throwable $e) {
			return [];
		}

		return $out;
	}
}
