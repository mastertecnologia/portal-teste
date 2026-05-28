<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\ErpPrototypeRbacTrait;
use Cake\Event\Event;
use App\Service\Pcp\PcpPrototypeDataService;
use Cake\Http\Exception\NotFoundException;

/**
 * PCP / Indústria — protótipo (mockup pg-pcp-dashboard, pg-engenharia, pg-bom,
 * pg-roteiro, pg-configurador, pg-centro-trabalho, pg-mrp, pg-pcp-cronograma,
 * pg-op-lista, pg-op-detalhe, pg-apontamento, pg-qualidade-ind, pg-expedicao).
 *
 * Módulo INTEIRO NOVO no portal — não existem tabelas dedicadas hoje. Esta classe
 * telas premium com dados reais via ORM (tabelas pcp_*) e a estimativa de
 * implementação. Roadmap completo: BOM/roteiros/MRP/OPs/apontamento.
 */
class PcpPrototypeController extends AppController {

	use ErpPrototypeRbacTrait;

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

	public function lista() {
		$this->set([
			'title' => __('Indústria · PCP'),
			'erpNavActive' => 'pcp',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Indústria'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'pcpTiles' => $this->buildPages(),
		]);

		return $this->render('lista');
	}

	/**
	 * Dashboard PCP — KPIs reais das tabelas pcp_* (sem dados inventados).
	 */
	public function dashboard() {
		$empresa = (int)$this->Auth->user('idempresa');
		$svc = new PcpPrototypeDataService($empresa);
		$kpi = $svc->dashboardKpis();
		$migrationHint = !$svc->tablesAvailable();

		$this->set([
			'title' => __('PCP · Dashboard'),
			'erpNavActive' => 'pcp-dashboard',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Indústria'), 'url' => ['controller' => 'PcpPrototype', 'action' => 'lista']],
				['label' => __('Dashboard'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'pcpKpi' => $kpi,
			'pcpMigrationHint' => $migrationHint,
			'pcpOrdensRecentes' => array_slice($svc->listOrdens(8), 0, 8),
		]);

		return $this->render('dashboard');
	}

	/**
	 * GET /pcp-prototype/op-detalhe/:id
	 *
	 * @param string|int|null $id
	 */
	public function opDetalhe($id = null) {
		$opId = (int)$id;
		if ($opId <= 0) {
			$opId = (int)$this->request->getQuery('id', 0);
		}
		if ($opId <= 0) {
			throw new NotFoundException(__('Ordem de produção inválida.'));
		}
		$empresa = (int)$this->Auth->user('idempresa');
		$svc = new PcpPrototypeDataService($empresa);
		$ordem = $svc->getOrdem($opId);
		if ($ordem === null) {
			throw new NotFoundException(__('OP não encontrada.'));
		}
		$this->set([
			'title' => __('OP {0}', $ordem['numero']),
			'erpNavActive' => 'op-lista',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Indústria'), 'url' => ['controller' => 'PcpPrototype', 'action' => 'lista']],
				['label' => __('Ordens de Produção'), 'url' => ['controller' => 'PcpPrototype', 'action' => 'view', 'op-lista']],
				['label' => $ordem['numero'], 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'pcpOrdem' => $ordem,
		]);

		return $this->render('op_detalhe');
	}

	/**
	 * @param string $page
	 */
	public function view($page = 'dashboard') {
		if ($page === 'dashboard') {
			return $this->dashboard();
		}
		if ($page === 'op-lista') {
			return $this->opLista();
		}
		if ($page === 'op-detalhe') {
			$id = (int)$this->request->getQuery('id', 0);

			return $this->opDetalhe($id > 0 ? $id : null);
		}
		$pages = $this->buildPages();
		if (!isset($pages[$page])) {
			throw new NotFoundException(__('Tela PCP não encontrada.'));
		}
		$meta = $pages[$page];
		$empresa = (int)$this->Auth->user('idempresa');
		$svc = new PcpPrototypeDataService($empresa);
		$migrationHint = !$svc->tablesAvailable();
		$template = 'pagina';
		$extra = [];
		switch ($page) {
			case 'engenharia':
				$template = 'engenharia';
				$extra['pcpRows'] = $svc->listFichas();
				break;
			case 'bom':
				$template = 'bom';
				$extra['pcpRows'] = $svc->listBom();
				break;
			case 'centro-trabalho':
				$template = 'centro_trabalho';
				$extra['pcpRows'] = $svc->listCentros();
				break;
			case 'apontamento':
				$template = 'apontamento';
				$extra['pcpRows'] = $svc->listApontamentosRecentes();
				break;
			case 'roteiro':
				$template = 'roteiro';
				$extra['pcpRows'] = $svc->listRoteiros();
				break;
			case 'configurador':
				$template = 'configurador';
				$extra['pcpRows'] = $svc->listProdutosConfigurador();
				break;
			case 'mrp':
				$template = 'mrp';
				$extra['pcpRows'] = $svc->listMrpNecessidades();
				break;
			case 'pcp-cronograma':
				$template = 'cronograma';
				$extra['pcpRows'] = $svc->listCronograma();
				break;
			case 'qualidade-ind':
				$template = 'qualidade';
				$extra['pcpRows'] = $svc->listQualidadeRefugo();
				break;
			case 'expedicao':
				$template = 'expedicao';
				$extra['pcpRows'] = $svc->listExpedicaoPendente();
				break;
			case 'requisicoes':
				$template = 'requisicoes';
				$extra['pcpRows'] = $svc->listRequisicoesCompra('requisicao');
				break;
			case 'cotacoes':
				$template = 'cotacoes';
				$extra['pcpRows'] = $svc->listRequisicoesCompra('cotacao');
				break;
			case 'pedido-compra':
				$template = 'pedido_compra';
				$extra['pcpRows'] = $svc->listRequisicoesCompra('pedido');
				break;
			case 'recebimento':
				$template = 'recebimento';
				$extra['pcpRows'] = $svc->listRequisicoesCompra('recebimento');
				break;
			case 'custos-producao':
				$template = 'custos';
				$extra['pcpRows'] = $svc->listCustosProducao();
				break;
		}
		$this->set(array_merge([
			'title' => 'PCP · ' . $meta['title'],
			'erpNavActive' => 'pcp',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Indústria'), 'url' => ['controller' => 'PcpPrototype', 'action' => 'lista']],
				['label' => $meta['title'], 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'pageMeta' => $meta + ['key' => $page],
			'pcpMigrationHint' => $migrationHint,
		], $extra));

		return $this->render($template);
	}

	public function opLista() {
		$empresa = (int)$this->Auth->user('idempresa');
		$svc = new PcpPrototypeDataService($empresa);
		$this->set([
			'title' => __('Ordens de Produção'),
			'erpNavActive' => 'op-lista',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Indústria'), 'url' => ['controller' => 'PcpPrototype', 'action' => 'lista']],
				['label' => __('Ordens de Produção'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'pcpOrdens' => $svc->listOrdens(),
			'pcpKpi' => $svc->dashboardKpis(),
			'pcpMigrationHint' => !$svc->tablesAvailable(),
		]);

		return $this->render('op_lista');
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	protected function buildPages(): array {
		return [
			'dashboard' => [
				'icon' => '🏭',
				'title' => __('Dashboard PCP'),
				'subtitle' => __('Cards de OEE, capacidade, gargalos e ordens em atraso'),
				'roteiro' => [
					__('KPI: OEE, takt time, throughput'),
					__('Gantt resumido de produção'),
					__('Top 5 itens em atraso · top 5 estações ociosas'),
				],
			],
			'engenharia' => [
				'icon' => '🛠',
				'title' => __('Engenharia · Fichas técnicas'),
				'subtitle' => __('Cadastro de produtos manufaturados com revisões'),
				'roteiro' => [
					__('Tabela engenharia_fichas (produto, revisão, status)'),
					__('Anexos PDF/CAD/STEP por revisão'),
					__('Aprovação em workflow'),
				],
			],
			'bom' => [
				'icon' => '🌳',
				'title' => __('Estrutura BOM'),
				'subtitle' => __('Lista de materiais multi-nível (Bill of Materials)'),
				'roteiro' => [
					__('Tabela engenharia_bom (parent_id, child_id, qty, scrap)'),
					__('Visualização em árvore expandível'),
					__('Versionamento (BOM as-designed × as-built)'),
				],
			],
			'roteiro' => [
				'icon' => '🗺',
				'title' => __('Roteiros de Produção'),
				'subtitle' => __('Sequência de operações por centro de trabalho'),
				'roteiro' => [
					__('Tabela engenharia_roteiro (operação, centro_trabalho, tempo setup/run)'),
					__('Vínculo BOM × roteiro'),
					__('Custeio por operação'),
				],
			],
			'configurador' => [
				'icon' => '⚙',
				'title' => __('Configurador de produto'),
				'subtitle' => __('Wizard para produtos sob medida'),
				'roteiro' => [
					__('Define opcionais por linha de produto'),
					__('Gera BOM automaticamente'),
					__('Calcula preço a partir das escolhas'),
				],
			],
			'centro-trabalho' => [
				'icon' => '🏗',
				'title' => __('Centros de Trabalho'),
				'subtitle' => __('Estações, máquinas e capacidade'),
				'roteiro' => [
					__('Cadastro de máquinas (capacidade/dia, custo/h)'),
					__('Calendário de disponibilidade'),
					__('Operadores habilitados'),
				],
			],
			'mrp' => [
				'icon' => '📦',
				'title' => __('MRP · Necessidades de material'),
				'subtitle' => __('Planejamento de compras a partir de OPs abertas'),
				'roteiro' => [
					__('Explode BOMs das OPs em aberto'),
					__('Confronta com saldo de estoque'),
					__('Sugere pedidos de compra'),
				],
			],
			'pcp-cronograma' => [
				'icon' => '📊',
				'title' => __('Cronograma · Gantt'),
				'subtitle' => __('Sequenciamento e capacidade de centros de trabalho'),
				'roteiro' => [
					__('Drag-drop de operações'),
					__('Detecção de overload'),
					__('Replanejamento automático'),
				],
			],
			'op-lista' => [
				'icon' => '📋',
				'title' => __('Ordens de Produção'),
				'subtitle' => __('Lista de OPs em aberto, em execução e fechadas'),
				'roteiro' => [
					__('Tabela ordens_producao (numero, item, qtd, status)'),
					__('Geração automática a partir de pedido de venda'),
					__('Apontamentos por operação'),
				],
			],
			'op-detalhe' => [
				'icon' => '📝',
				'title' => __('Detalhe da Ordem de Produção'),
				'subtitle' => __('Header + operações + materiais + apontamentos'),
				'roteiro' => [
					__('Header (item, qty, datas, status)'),
					__('Operações cronograma'),
					__('Materiais consumidos × planejados'),
				],
			],
			'apontamento' => [
				'icon' => '⏱',
				'title' => __('Apontamento de produção'),
				'subtitle' => __('Tempo real do chão de fábrica'),
				'roteiro' => [
					__('App mobile para operadores'),
					__('Início/parada/setup/refugo por operação'),
					__('Cálculo OEE automático'),
				],
			],
			'qualidade-ind' => [
				'icon' => '✅',
				'title' => __('Controle de Qualidade'),
				'subtitle' => __('Inspeção, refugo e RNCs'),
				'roteiro' => [
					__('Pontos de inspeção por operação'),
					__('Registro de não conformidade (RNC)'),
					__('Tratamento e ações corretivas'),
				],
			],
			'expedicao' => [
				'icon' => '🚚',
				'title' => __('Expedição & Logística'),
				'subtitle' => __('Picking, conferência e despacho de pedidos'),
				'roteiro' => [
					__('Lista de pedidos prontos para expedir'),
					__('Etiquetas, romaneios, conferência'),
					__('Integração transportadora'),
				],
			],
			'requisicoes' => [
				'icon' => '📝',
				'title' => __('Requisições de Compra'),
				'subtitle' => __('Demandas de material e compras urgentes'),
				'roteiro' => [],
			],
			'cotacoes' => [
				'icon' => '💬',
				'title' => __('Cotações'),
				'subtitle' => __('Comparativo de fornecedores'),
				'roteiro' => [],
			],
			'pedido-compra' => [
				'icon' => '🛒',
				'title' => __('Pedido de Compra'),
				'subtitle' => __('Ordens emitidas a fornecedores'),
				'roteiro' => [],
			],
			'recebimento' => [
				'icon' => '📥',
				'title' => __('Recebimento'),
				'subtitle' => __('Entrada de materiais no estoque'),
				'roteiro' => [],
			],
			'custos-producao' => [
				'icon' => '📊',
				'title' => __('Custos de Produção'),
				'subtitle' => __('Análise de custo real × padrão'),
				'roteiro' => [],
			],
		];
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
