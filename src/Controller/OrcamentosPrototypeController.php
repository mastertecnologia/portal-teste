<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\ErpPrototypeRbacTrait;
use App\Controller\Traits\PrototypeApiSecurityTrait;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

/**
 * Orçamentos — protótipo (telas do mockup pgm_erp_completo.html, prefixo `pg-`).
 *
 * Lado-a-lado com OrcamentosController (legado). Rotas em /orcamentos-prototype/*.
 * Dados reais via ORM; lista/detalhe com filtros, edição inline de itens e
 * transição de status no protótipo (convive com OrcamentosController legado).
 */
class OrcamentosPrototypeController extends AppController {

	use PrototypeApiSecurityTrait;

	public function initialize() {
		parent::initialize();
		$this->loadModel('Orcamentos');
		$this->loadModel('Clientes');
		$this->loadModel('Users');
		$this->loadModel('Produtos');
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
		$this->viewBuilder()->setLayout('erp_prototype');
	}

	/**
	 * pg-lista — listagem de orçamentos com KPIs + tabela.
	 */
	public function lista() {
		$empresa = (int)$this->Auth->user('idempresa');
		$query = $this->request->getQueryParams();
		$filtroStatus = $query['status'] ?? '';
		$filtroCliente = trim((string)($query['cliente'] ?? ''));
		$filtroDe = trim((string)($query['de'] ?? ''));
		$filtroAte = trim((string)($query['ate'] ?? ''));

		$where = ['Orcamentos.idempresa' => $empresa];
		if ($filtroStatus !== '' && is_numeric($filtroStatus)) {
			$where['Orcamentos.status'] = (int)$filtroStatus;
		}
		if ($filtroCliente !== '') {
			$where['Orcamentos.idcliente'] = (int)$filtroCliente;
		}
		if ($filtroDe !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtroDe)) {
			$where['Orcamentos.created >='] = $filtroDe . ' 00:00:00';
		}
		if ($filtroAte !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtroAte)) {
			$where['Orcamentos.created <='] = $filtroAte . ' 23:59:59';
		}

		$base = $this->Orcamentos->find()
			->contain(['Clientes', 'Users'])
			->where($where)
			->order(['Orcamentos.created' => 'DESC'])
			->limit(100);

		$rows = [];
		try {
			$rows = $base->all()->toArray();
		} catch (\Throwable $e) {
			$rows = [];
		}

		$stPend = defined('C_OrcamentoStatusPendente') ? (int)C_OrcamentoStatusPendente : 0;
		$stEnv = defined('C_OrcamentoStatusEnviado') ? (int)C_OrcamentoStatusEnviado : 1;
		$stApr = defined('C_OrcamentoStatusAprovado') ? (int)C_OrcamentoStatusAprovado : 2;
		$stRec = defined('C_OrcamentoStatusRecusado') ? (int)C_OrcamentoStatusRecusado : 3;

		$counts = ['pendente' => 0, 'enviado' => 0, 'aprovado' => 0, 'recusado' => 0, 'total' => 0];
		$totalValor = 0.0;
		$items = [];
		foreach ($rows as $o) {
			$st = (int)$o->get('status');
			$counts['total']++;
			if ($st === $stPend) {
				$counts['pendente']++;
			} elseif ($st === $stEnv) {
				$counts['enviado']++;
			} elseif ($st === $stApr) {
				$counts['aprovado']++;
			} elseif ($st === $stRec) {
				$counts['recusado']++;
			}
			$valor = (float)($o->get('valortotal') ?? $o->get('valor') ?? 0);
			$totalValor += $valor;
			$cl = $o->cliente ?? null;
			$autor = $o->user ?? null;
			$stKey = 'pend';
			if ($st === $stEnv) {
				$stKey = 'env';
			} elseif ($st === $stApr) {
				$stKey = 'aprov';
			} elseif ($st === $stRec) {
				$stKey = 'recus';
			}
			$empNome = $cl ? (string)($cl->get('razaosocial') ?? $cl->get('nome') ?? '') : '—';
			$items[] = [
				'id' => (int)$o->get('id'),
				'cliente' => $empNome,
				'empresa' => $empNome,
				'autor' => $autor ? trim((string)($autor->get('name') ?? $autor->get('username'))) : '—',
				'valor' => $valor,
				'status' => $st,
				'st_key' => $stKey,
				'versao' => 'v1',
				'margem_pct' => null,
				'modified' => $o->get('modified') ?? $o->get('created'),
				'observacao' => (string)($o->get('observacao') ?? ''),
			];
		}

		$this->set([
			'title' => __('Orçamentos'),
			'erpNavActive' => 'orc-lista',
			'erpBreadcrumb' => [
				['label' => __('Orçamentos')],
				['label' => __('Lista'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'orcCounts' => $counts,
			'orcTotalValor' => $totalValor,
			'orcItems' => $items,
			'orcStatusLabels' => [
				$stPend => __('Pendente'),
				$stEnv => __('Enviado'),
				$stApr => __('Aprovado'),
				$stRec => __('Recusado'),
			],
			'orcClientesOptions' => $this->buildClientesOptions(),
			'orcFiltros' => [
				'status' => $filtroStatus,
				'cliente' => $filtroCliente,
				'de' => $filtroDe,
				'ate' => $filtroAte,
			],
		]);
	}

	/**
	 * Detalhe de um orçamento (mockup pg-revisao com dados reais).
	 *
	 * @param string|int $id
	 */
	public function detalhe($id) {
		$id = (int)$id;
		if ($id <= 0) {
			throw new NotFoundException(__('Orçamento inválido.'));
		}
		$empresa = (int)$this->Auth->user('idempresa');
		$orc = null;
		$itens = [];
		try {
			$orc = $this->Orcamentos->find()
				->contain(['Clientes', 'Users'])
				->where(['Orcamentos.id' => $id, 'Orcamentos.idempresa' => $empresa])
				->first();
		} catch (\Throwable $e) {
		}
		if ($orc === null) {
			throw new NotFoundException(__('Orçamento não encontrado ou fora do seu escopo.'));
		}
		try {
			$tblItens = $this->loadModel('Orcamentositens');
			$itens = $tblItens->find()
				->where(['Orcamentositens.idorcamento' => $id])
				->order(['Orcamentositens.id' => 'ASC'])
				->all()
				->toArray();
		} catch (\Throwable $e) {
		}

		$totalSub = 0.0;
		$totalDesc = 0.0;
		$linhas = [];
		foreach ($itens as $it) {
			$qtd = (float)($it->get('quantidade') ?? 1);
			$vu = (float)($it->get('valorunitario') ?? 0);
			$desc = (float)($it->get('valordesconto') ?? 0);
			$subtotal = $qtd * $vu - $desc;
			$totalSub += $qtd * $vu;
			$totalDesc += $desc;
			$linhas[] = [
				'id' => (int)$it->get('id'),
				'codigo' => (string)($it->get('codproduto') ?? ''),
				'descricao' => (string)($it->get('descricao') ?? ''),
				'unidade' => (string)($it->get('unidade') ?? ''),
				'qtd' => $qtd,
				'vlr' => $vu,
				'desconto' => $desc,
				'subtotal' => $subtotal,
			];
		}

		$cliente = $orc->cliente ?? null;
		$autor = $orc->user ?? null;
		$historico = (new \App\Service\PrototypeStatusHistoryService())->fetch('orcamento', $id, 30);
		$this->set([
			'title' => __('Orçamento #{0}', $id),
			'orcHistorico' => $historico,
			'erpNavActive' => 'orc-lista',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Comercial')],
				['label' => __('Orçamentos'), 'url' => ['controller' => 'OrcamentosPrototype', 'action' => 'lista']],
				['label' => '#' . $id, 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'orc' => [
				'id' => (int)$orc->get('id'),
				'cliente' => $cliente ? (string)($cliente->get('razaosocial') ?? $cliente->get('nome') ?? '') : '—',
				'cliente_cnpj' => $cliente ? (string)($cliente->get('cnpj') ?? $cliente->get('cpf') ?? '') : '',
				'autor' => $autor ? trim((string)($autor->get('name') ?? $autor->get('username'))) : '—',
				'status' => (int)$orc->get('status'),
				'created' => $orc->get('created'),
				'modified' => $orc->get('modified'),
				'observacao' => (string)($orc->get('observacao') ?? ''),
				'valortotal' => (float)($orc->get('valortotal') ?? $orc->get('valor') ?? 0),
			],
			'orcLinhas' => $linhas,
			'orcTotalSub' => $totalSub,
			'orcTotalDesc' => $totalDesc,
		]);

		return $this->render('detalhe');
	}

	/**
	 * Telas wizard (pg-novo|revisao|print|esign|sucesso) e faturamento/cobranca.
	 *
	 * @param string $page
	 */
	public function view($page = 'lista') {
		if ($page === 'lista') {
			return $this->lista();
		}
		$orcId = (int)$this->request->getQuery('id', 0);
		if ($orcId > 0) {
			if ($page === 'revisao') {
				return $this->redirect(['action' => 'detalhe', $orcId]);
			}
			if ($page === 'print') {
				return $this->redirect(['controller' => 'Orcamentos', 'action' => 'imprimirPdf', $orcId]);
			}
			if ($page === 'esign' || $page === 'sucesso') {
				return $this->redirect(['controller' => 'Orcamentos', 'action' => 'view', $orcId]);
			}
		}
		$wizard = ['novo' => 1, 'revisao' => 2, 'print' => 3, 'esign' => 4, 'sucesso' => 5];
		$allowed = array_merge(array_keys($wizard), ['faturamento', 'cobranca']);
		if (!in_array($page, $allowed, true)) {
			throw new NotFoundException(__('Tela do protótipo não encontrada.'));
		}

		$steps = [
			['label' => __('Novo'), 'state' => 'pending'],
			['label' => __('Revisão'), 'state' => 'pending'],
			['label' => __('Impressão'), 'state' => 'pending'],
			['label' => __('Assinatura'), 'state' => 'pending'],
			['label' => __('Sucesso'), 'state' => 'pending'],
		];
		if (isset($wizard[$page])) {
			$current = (int)$wizard[$page] - 1;
			for ($i = 0; $i < $current; $i++) {
				$steps[$i]['state'] = 'done';
			}
			$steps[$current]['state'] = 'active';
		}

		$this->set([
			'title' => __('Orçamentos · {0}', ucfirst($page)),
			'erpNavActive' => 'orc-' . $page,
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Comercial')],
				['label' => __('Orçamentos'), 'url' => ['controller' => 'OrcamentosPrototype', 'action' => 'lista']],
				['label' => ucfirst($page), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'page' => $page,
			'wizardSteps' => $steps,
			'wizardCurrent' => $page,
		]);

		$dedicated = ['novo', 'revisao', 'print', 'esign', 'sucesso'];
		if (in_array($page, $dedicated, true)) {
			if ($page === 'novo' || $page === 'revisao') {
				$this->set('orcCatalogo', $this->buildCatalogoProdutos());
				$this->set('orcClientesOptions', $this->buildClientesOptions());
			}

			return $this->render('wizard_' . $page);
		}

		return $this->render('placeholder');
	}

	/**
	 * Catálogo (até 40 produtos ativos) para o wizard de orçamento.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function buildCatalogoProdutos(): array {
		$empresa = (int)$this->Auth->user('idempresa');
		$busca = trim((string)$this->request->getQuery('q', ''));
		$out = [];
		try {
			$q = $this->Produtos->find()
				->where(['Produtos.idempresa' => $empresa, 'Produtos.ativo' => 1])
				->order(['Produtos.descricao' => 'ASC'])
				->limit(40);
			if ($busca !== '') {
				$q->where(['OR' => [
					'Produtos.descricao ILIKE' => '%' . $busca . '%',
					'Produtos.codigo ILIKE' => '%' . $busca . '%',
				]]);
			}
			foreach ($q->all() as $p) {
				$out[] = [
					'id' => (int)$p->get('id'),
					'codigo' => (string)$p->get('codigo'),
					'descricao' => (string)$p->get('descricao'),
					'tipo' => (string)$p->get('tipo'),
					'unidade' => (string)$p->get('unidade'),
					'preco' => (float)$p->get('vlunitario'),
					'estoque' => (float)$p->get('estoque_atual'),
				];
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @return array<int,string> id => nome
	 */
	protected function buildClientesOptions(): array {
		$empresa = (int)$this->Auth->user('idempresa');
		$out = [];
		try {
			$rows = $this->Clientes->find()
				->where(['Clientes.idempresa' => $empresa, 'Clientes.inativo' => 0])
				->order(['Clientes.nome' => 'ASC'])
				->limit(200)
				->all();
			foreach ($rows as $c) {
				$nome = (int)$c->get('tipo') === 2
					? (string)($c->get('razaosocial') ?? $c->get('nome'))
					: (string)$c->get('nome');
				$out[(int)$c->get('id')] = $nome;
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * Endpoint AJAX/JSON: busca produtos ativos por texto (código ou descrição).
	 * GET /orcamentos-prototype/api/produtos?q=ryzen&tipo=prod
	 */
	public function apiProdutos() {
		$this->request->allowMethod(['get']);
		if ($guard = $this->guardApiEquipeGet()) {
			return $guard;
		}
		$this->autoRender = false;
		$this->response = $this->response->withType('application/json');
		$empresa = (int)$this->Auth->user('idempresa');
		$q = trim((string)$this->request->getQuery('q', ''));
		$tipo = trim((string)$this->request->getQuery('tipo', ''));
		$produtos = $this->loadModel('Produtos');
		$conds = ['Produtos.idempresa' => $empresa, 'Produtos.ativo' => 1];
		if ($tipo !== '' && in_array($tipo, ['prod', 'serv', 'lic', 'loc'], true)) {
			$conds['Produtos.tipo'] = $tipo;
		}
		$query = $produtos->find()->where($conds)->order(['Produtos.descricao' => 'ASC'])->limit(20);
		if ($q !== '') {
			$query->where(['OR' => [
				'Produtos.descricao ILIKE' => '%' . $q . '%',
				'Produtos.codigo ILIKE' => '%' . $q . '%',
			]]);
		}
		$out = [];
		foreach ($query->all() as $p) {
			$out[] = [
				'id' => (int)$p->get('id'),
				'codigo' => (string)$p->get('codigo'),
				'descricao' => (string)$p->get('descricao'),
				'tipo' => (string)$p->get('tipo'),
				'unidade' => (string)$p->get('unidade'),
				'preco' => round((float)$p->get('vlunitario'), 2),
				'estoque' => round((float)$p->get('estoque_atual'), 2),
			];
		}

		return $this->response->withStringBody(json_encode(['ok' => true, 'q' => $q, 'count' => count($out), 'items' => $out], JSON_UNESCAPED_UNICODE));
	}

	/**
	 * Endpoint AJAX/JSON: busca clientes ativos por texto (nome, fantasia, CNPJ).
	 * GET /orcamentos-prototype/api/clientes?q=mobles
	 */
	public function apiClientes() {
		$this->request->allowMethod(['get']);
		if ($guard = $this->guardApiEquipeGet()) {
			return $guard;
		}
		$this->autoRender = false;
		$this->response = $this->response->withType('application/json');
		$empresa = (int)$this->Auth->user('idempresa');
		$q = trim((string)$this->request->getQuery('q', ''));
		$query = $this->Clientes->find()
			->where(['Clientes.idempresa' => $empresa, 'Clientes.inativo' => 0])
			->order(['Clientes.nome' => 'ASC'])
			->limit(20);
		if ($q !== '') {
			$query->where(['OR' => [
				'Clientes.nome ILIKE' => '%' . $q . '%',
				'Clientes.razaosocial ILIKE' => '%' . $q . '%',
				'Clientes.nomefantasia ILIKE' => '%' . $q . '%',
				'Clientes.cnpj ILIKE' => '%' . $q . '%',
			]]);
		}
		$out = [];
		foreach ($query->all() as $c) {
			$nome = (int)$c->get('tipo') === 2
				? (string)($c->get('razaosocial') ?? $c->get('nome'))
				: (string)$c->get('nome');
			$out[] = [
				'id' => (int)$c->get('id'),
				'nome' => $nome,
				'fantasia' => (string)($c->get('nomefantasia') ?? ''),
				'cnpj' => (string)($c->get('cnpj') ?? $c->get('cpf') ?? ''),
			];
		}

		return $this->response->withStringBody(json_encode(['ok' => true, 'q' => $q, 'count' => count($out), 'items' => $out], JSON_UNESCAPED_UNICODE));
	}

	/**
	 * GET /orcamentos-prototype/export.csv — exporta lista filtrada como CSV.
	 * Mesmos filtros de lista() (status, cliente, datas).
	 */
	public function exportCsv() {
		$empresa = (int)$this->Auth->user('idempresa');
		$query = $this->request->getQueryParams();
		$where = ['Orcamentos.idempresa' => $empresa];
		if (isset($query['status']) && is_numeric($query['status']) && $query['status'] !== '') {
			$where['Orcamentos.status'] = (int)$query['status'];
		}
		if (!empty($query['cliente'])) {
			$where['Orcamentos.idcliente'] = (int)$query['cliente'];
		}
		if (!empty($query['de']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$query['de'])) {
			$where['Orcamentos.created >='] = $query['de'] . ' 00:00:00';
		}
		if (!empty($query['ate']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$query['ate'])) {
			$where['Orcamentos.created <='] = $query['ate'] . ' 23:59:59';
		}
		$rows = $this->Orcamentos->find()->contain(['Clientes', 'Users'])->where($where)->order(['Orcamentos.created' => 'DESC'])->limit(5000)->all();

		$labels = [0 => 'Pendente', 1 => 'Enviado', 2 => 'Aprovado', 3 => 'Recusado', 4 => 'Arquivado'];
		$this->autoRender = false;
		$fname = 'orcamentos-' . date('Ymd-His') . '.csv';
		$this->response = $this->response
			->withType('text/csv')
			->withHeader('Content-Disposition', 'attachment; filename="' . $fname . '"');

		$out = fopen('php://temp', 'w+');
		fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel
		fputcsv($out, ['ID', 'Cliente', 'Autor', 'Valor', 'Status', 'Criado', 'Modificado'], ';');
		foreach ($rows as $o) {
			$cli = $o->cliente ?? null;
			$autor = $o->user ?? null;
			fputcsv($out, [
				'ORC-' . str_pad((string)$o->get('id'), 4, '0', STR_PAD_LEFT),
				$cli ? (string)($cli->get('razaosocial') ?? $cli->get('nome') ?? '') : '',
				$autor ? trim((string)($autor->get('name') ?? $autor->get('username'))) : '',
				number_format((float)($o->get('valortotal') ?? $o->get('valor') ?? 0), 2, ',', '.'),
				(string)($labels[(int)$o->get('status')] ?? $o->get('status')),
				$o->get('created') instanceof \DateTimeInterface ? $o->get('created')->format('d/m/Y H:i') : '',
				$o->get('modified') instanceof \DateTimeInterface ? $o->get('modified')->format('d/m/Y H:i') : '',
			], ';');
		}
		rewind($out);

		return $this->response->withStringBody(stream_get_contents($out));
	}

	/**
	 * POST /orcamentos-prototype/mudar-status — avança/recua status do orçamento.
	 * Transições permitidas:
	 *   Pendente(0) → Enviado(1) | Recusado(3) | Arquivado(4)
	 *   Enviado(1)  → Aprovado(2) | Recusado(3) | Pendente(0)
	 *   Aprovado(2) → Arquivado(4) | Pendente(0)  (reabertura)
	 *   Recusado(3) → Pendente(0)
	 */
	public function mudarStatus() {
		$this->request->allowMethod(['post']);
		$empresa = (int)$this->Auth->user('idempresa');
		$id = (int)$this->request->getData('orcamento_id');
		$novo = (int)$this->request->getData('novo_status');
		if ($id <= 0) {
			$this->Flash->error(__('Orçamento inválido.'));

			return $this->redirect(['controller' => 'OrcamentosPrototype', 'action' => 'lista']);
		}
		$valid = [
			0 => [1, 3, 4],
			1 => [0, 2, 3],
			2 => [0, 4],
			3 => [0],
			4 => [0],
		];
		try {
			$orc = $this->Orcamentos->find()->where(['id' => $id, 'idempresa' => $empresa])->first();
			if ($orc === null) {
				$this->Flash->error(__('Orçamento fora do escopo.'));

				return $this->redirect(['controller' => 'OrcamentosPrototype', 'action' => 'lista']);
			}
			$atual = (int)$orc->get('status');
			if (!isset($valid[$atual]) || !in_array($novo, $valid[$atual], true)) {
				$this->Flash->error(__('Transição de status não permitida.'));

				return $this->redirect(['controller' => 'OrcamentosPrototype', 'action' => 'detalhe', $id]);
			}
			$labels = [0 => __('Pendente'), 1 => __('Enviado'), 2 => __('Aprovado'), 3 => __('Recusado'), 4 => __('Arquivado')];
			$orc->set('status', $novo);
			if ($this->Orcamentos->save($orc)) {
				$lbl = (string)($labels[$novo] ?? $novo);
				$nota = trim((string)$this->request->getData('nota'));
				$this->Flash->success(__('Status alterado para "{0}".', $lbl));
				(new \App\Service\PrototypeStatusHistoryService())->record(
					'orcamento', $id, (string)($labels[$atual] ?? $atual), $lbl,
					(array)$this->Auth->user(), $nota, $empresa
				);
				// Push hook ao autor
				try {
					$autorId = (int)$orc->get('idautor');
					if ($autorId > 0) {
						(new \App\Service\WebPushSenderService())->sendToUser($autorId, [
							'title' => '📋 ' . __('Orçamento {0} · {1}', sprintf('ORC-%04d', $id), $lbl),
							'body' => __('Status do orçamento alterado por {0}.', trim((string)$this->Auth->user('name')) ?: 'equipe'),
							'url' => $this->Url->build(['controller' => 'OrcamentosPrototype', 'action' => 'detalhe', $id]),
							'tag' => 'orc-' . $id,
						]);
					}
				} catch (\Throwable $e) {
				}
			}
		} catch (\Throwable $e) {
			$this->Flash->error(__('Erro: {0}', $e->getMessage()));
		}

		return $this->redirect(['controller' => 'OrcamentosPrototype', 'action' => 'detalhe', $id]);
	}

	/**
	 * POST /orcamentos-prototype/api/atualizar-item — edita qtd/valor unitário de item existente.
	 */
	public function apiAtualizarItem() {
		$this->request->allowMethod(['post']);
		if ($guard = $this->guardApiEquipe()) {
			return $guard;
		}
		$this->autoRender = false;
		$this->response = $this->response->withType('application/json');
		$empresa = (int)$this->Auth->user('idempresa');
		$itemId = (int)$this->request->getData('item_id');
		$qtd = (float)str_replace(',', '.', (string)$this->request->getData('quantidade'));
		$vu = (float)str_replace(',', '.', (string)$this->request->getData('valor_unitario'));
		$descRaw = $this->request->getData('desconto');
		$desc = $descRaw !== null && $descRaw !== '' ? (float)str_replace(',', '.', (string)$descRaw) : null;
		if ($itemId <= 0 || $qtd <= 0 || $vu < 0 || ($desc !== null && $desc < 0)) {
			return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Dados inválidos.')]));
		}
		try {
			$itens = $this->loadModel('Orcamentositens');
			$row = $itens->find()->where(['Orcamentositens.id' => $itemId, 'Orcamentositens.idempresa' => $empresa])->first();
			if ($row === null) {
				return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Item fora do escopo.')]));
			}
			$row->set('quantidade', $qtd);
			$row->set('valorunitario', $vu);
			if ($desc !== null) {
				$row->set('valordesconto', $desc);
			}
			$descFinal = (float)($row->get('valordesconto') ?? 0);
			if (!$itens->save($row)) {
				return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Falha ao salvar.')]));
			}
			$subtotal = $qtd * $vu - $descFinal;

			return $this->response->withStringBody(json_encode([
				'ok' => true,
				'item' => ['id' => $itemId, 'qtd' => $qtd, 'vlr' => $vu, 'desconto' => $descFinal, 'subtotal' => $subtotal],
			], JSON_UNESCAPED_UNICODE));
		} catch (\Throwable $e) {
			return $this->response->withStringBody(json_encode(['ok' => false, 'error' => $e->getMessage()]));
		}
	}

	/**
	 * POST /orcamentos-prototype/api/excluir-item — remove item do orçamento.
	 */
	public function apiExcluirItem() {
		$this->request->allowMethod(['post']);
		if ($guard = $this->guardApiEquipe()) {
			return $guard;
		}
		$this->autoRender = false;
		$this->response = $this->response->withType('application/json');
		$empresa = (int)$this->Auth->user('idempresa');
		$itemId = (int)$this->request->getData('item_id');
		if ($itemId <= 0) {
			return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('ID inválido.')]));
		}
		try {
			$itens = $this->loadModel('Orcamentositens');
			$row = $itens->find()->where(['Orcamentositens.id' => $itemId, 'Orcamentositens.idempresa' => $empresa])->first();
			if ($row === null) {
				return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Item fora do escopo.')]));
			}
			if (!$itens->delete($row)) {
				return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Falha ao excluir.')]));
			}

			return $this->response->withStringBody(json_encode(['ok' => true]));
		} catch (\Throwable $e) {
			return $this->response->withStringBody(json_encode(['ok' => false, 'error' => $e->getMessage()]));
		}
	}

	/**
	 * POST /orcamentos-prototype/api/adicionar-item — adiciona item ao orçamento.
	 */
	public function apiAdicionarItem() {
		$this->request->allowMethod(['post']);
		if ($guard = $this->guardApiEquipe()) {
			return $guard;
		}
		$this->autoRender = false;
		$this->response = $this->response->withType('application/json');
		$empresa = (int)$this->Auth->user('idempresa');
		$orcId = (int)$this->request->getData('orcamento_id');
		$descricao = trim((string)$this->request->getData('descricao'));
		$qtd = (float)str_replace(',', '.', (string)$this->request->getData('quantidade'));
		$vu = (float)str_replace(',', '.', (string)$this->request->getData('valor_unitario'));
		$codigo = trim((string)$this->request->getData('codigo'));
		$unidade = trim((string)$this->request->getData('unidade'));
		if ($orcId <= 0 || $descricao === '' || $qtd <= 0) {
			return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Descrição e quantidade obrigatórias.')]));
		}
		try {
			$orc = $this->Orcamentos->find()->where(['id' => $orcId, 'idempresa' => $empresa])->first();
			if ($orc === null) {
				return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Orçamento fora do escopo.')]));
			}
			$itens = $this->loadModel('Orcamentositens');
			$entity = $itens->newEntity([
				'idempresa' => $empresa,
				'idorcamento' => $orcId,
				'codproduto' => $codigo,
				'descricao' => $descricao,
				'unidade' => $unidade !== '' ? $unidade : 'un',
				'quantidade' => $qtd,
				'valorunitario' => $vu,
				'valordesconto' => 0,
				'tipo' => 'serv',
			], ['validate' => false]);
			$saved = $itens->save($entity);
			if (!$saved) {
				return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Falha ao gravar item.')]));
			}

			return $this->response->withStringBody(json_encode([
				'ok' => true,
				'item' => [
					'id' => (int)$entity->get('id'),
					'codigo' => $codigo,
					'descricao' => $descricao,
					'unidade' => $unidade,
					'qtd' => $qtd,
					'vlr' => $vu,
					'subtotal' => $qtd * $vu,
				],
			], JSON_UNESCAPED_UNICODE));
		} catch (\Throwable $e) {
			return $this->response->withStringBody(json_encode(['ok' => false, 'error' => $e->getMessage()]));
		}
	}

	/**
	 * Salva orçamento rascunho a partir do wizard premium (pg-novo).
	 * Cria registro em orcamentosnovosdes status=Pendente e redireciona ao detalhe.
	 */
	public function salvarRascunho() {
		$this->request->allowMethod(['post']);
		$empresa = (int)$this->Auth->user('idempresa');
		$autor = (int)$this->Auth->user('id');
		$idcliente = (int)$this->request->getData('idcliente');
		$solicitacao = trim((string)$this->request->getData('solicitacao'));
		$validade = (int)$this->request->getData('validade_dias');
		if ($validade <= 0 || $validade > 365) {
			$validade = 30;
		}
		if ($idcliente <= 0) {
			$this->Flash->error(__('Selecione um cliente para iniciar o orçamento.'));

			return $this->redirect(['controller' => 'OrcamentosPrototype', 'action' => 'view', 'novo']);
		}
		try {
			$cli = $this->Clientes->find()
				->where(['Clientes.id' => $idcliente, 'Clientes.idempresa' => $empresa])
				->first();
			if ($cli === null) {
				$this->Flash->error(__('Cliente fora do seu escopo.'));

				return $this->redirect(['controller' => 'OrcamentosPrototype', 'action' => 'view', 'novo']);
			}
			$entity = $this->Orcamentos->newEntity([
				'idempresa' => $empresa,
				'idcliente' => $idcliente,
				'idautor' => $autor,
				'status' => defined('C_OrcamentoStatusPendente') ? (int)C_OrcamentoStatusPendente : 0,
				'solicitacao' => $solicitacao !== '' ? $solicitacao : __('Orçamento iniciado via wizard premium'),
				'created' => date('Y-m-d H:i:s'),
				'validoate' => date('Y-m-d', strtotime('+' . $validade . ' days')),
			], ['validate' => false]);
			$saved = $this->Orcamentos->save($entity);
			if ($saved === false) {
				$this->Flash->error(__('Falha ao gravar o orçamento.'));

				return $this->redirect(['controller' => 'OrcamentosPrototype', 'action' => 'view', 'novo']);
			}
			$this->Flash->success(__('Orçamento {0} criado em rascunho.', sprintf('ORC-%04d', (int)$entity->get('id'))));

			return $this->redirect(['controller' => 'OrcamentosPrototype', 'action' => 'detalhe', (int)$entity->get('id')]);
		} catch (\Throwable $e) {
			$this->Flash->error(__('Erro ao gravar: {0}', $e->getMessage()));

			return $this->redirect(['controller' => 'OrcamentosPrototype', 'action' => 'view', 'novo']);
		}
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
		$userId = (int)$this->Auth->user('id');
		$active = (int)$this->Auth->user('idempresa');
		$out = [];
		try {
			$cols = $tbl->getSchema()->columns();
			$q = $tbl->find()->order(['id' => 'ASC'])->limit(20);
			foreach ($q->all() as $e) {
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
