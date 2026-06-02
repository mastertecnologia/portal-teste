<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\ClientesCrmListaTrait;
use App\Controller\Traits\ClientesVisao360SupportTrait;
use App\Controller\Traits\ClientesVisao360Trait;
use App\Controller\Traits\ErpPrototypeRbacTrait;
use App\Utility\ClientesPapelCadastro;
use App\Utility\PortalUi;
use App\Controller\Traits\PrototypeApiSecurityTrait;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

$__pgmUserConstants = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';
if (is_file($__pgmUserConstants)) {
	require_once $__pgmUserConstants;
}
$__pgmUtilities = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'Utilities.php';
if (is_file($__pgmUtilities)) {
	require_once $__pgmUtilities;
}
if (!defined('C_ClientesTipoJuridica')) {
	define('C_ClientesTipoJuridica', 2);
}
if (!defined('C_ClientesTipoFisica')) {
	define('C_ClientesTipoFisica', 1);
}

/**
 * Clientes — protótipo (mockup pg-clientes, pg-cliente-novo, pg-cliente-360).
 *
 * Lado-a-lado com ClientesController (legado). Rotas em /clientes-prototype/*.
 * Somente leitura nesta fase.
 */
class ClientesPrototypeController extends AppController {

	use ClientesCrmListaTrait;
	use ClientesVisao360SupportTrait;
	use ClientesVisao360Trait;
	use ErpPrototypeRbacTrait;
	use PrototypeApiSecurityTrait;

	public function initialize() {
		parent::initialize();
		$this->loadModel('Clientes');
		$this->loadModel('Users');
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

	/**
	 * pg-clientes — lista CRM (KPIs, top 5, segmentos, tabela rica — paridade legado / mock).
	 */
	public function lista() {
		$busca = trim((string)$this->request->getQuery('q', ''));
		$filtroTipo = (string)$this->request->getQuery('tipo', '');
		$filtroStatus = (string)$this->request->getQuery('status', '');

		$todos = [];
		try {
			$qAll = $this->Clientes->find('all')
				->contain(['Cidades.Estados'])
				->order(['Clientes.id' => 'DESC']);
			$this->Abac->applyToQuery($qAll, 'Clientes');
			$todos = $qAll->toArray();
		} catch (\Throwable $e) {
			$this->log('ClientesPrototype::lista: ' . $e->getMessage(), 'warning');
		}

		$papelCols = ClientesPapelCadastro::columnsAvailable($this->Clientes);
		if ($papelCols) {
			$todos = array_values(array_filter($todos, function ($c) use ($papelCols) {
				return ClientesPapelCadastro::isCliente($c, $papelCols);
			}));
		}

		$clientesAtivos = array_values(array_filter($todos, function ($c) {
			return (int)$c->inativo === 0;
		}));
		$crm = $this->_clientesIndexCrmMetrics($todos, count($clientesAtivos));
		$cliRows = $this->_clientesIndexRows($todos, $crm);
		$cliVendedores = $this->_clientesIndexVendedoresLista();

		$this->set([
			'title' => __('Clientes'),
			'erpNavActive' => 'clientes',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Cadastros')],
				['label' => __('Clientes'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'cliCrm' => $crm,
			'cliRows' => $cliRows,
			'cliVendedores' => $cliVendedores,
			'cliFiltros' => ['q' => $busca, 'tipo' => $filtroTipo, 'status' => $filtroStatus],
			'cliPapelColumns' => $papelCols,
		]);
	}

	/**
	 * POST /clientes-prototype/api/atualizar-contato — edita telefone/e-mail inline.
	 */
	public function apiAtualizarContato() {
		$this->request->allowMethod(['post']);
		if ($guard = $this->guardApiEquipe()) {
			return $guard;
		}
		$this->autoRender = false;
		$this->response = $this->response->withType('application/json');
		$empresa = (int)$this->Auth->user('idempresa');
		$cliId = (int)$this->request->getData('cliente_id');
		$campo = (string)$this->request->getData('campo');
		$valor = trim((string)$this->request->getData('valor'));
		if (!in_array($campo, ['email', 'fone'], true) || $cliId <= 0) {
			return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Campo inválido.')]));
		}
		if ($campo === 'email' && $valor !== '' && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
			return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('E-mail inválido.')]));
		}
		try {
			$row = $this->Clientes->find()->where(['Clientes.id' => $cliId, 'Clientes.idempresa' => $empresa])->first();
			if ($row === null) {
				return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Fora do escopo.')]));
			}
			$row->set($campo, $valor !== '' ? $valor : null);
			if (!$this->Clientes->save($row)) {
				return $this->response->withStringBody(json_encode(['ok' => false, 'error' => __('Falha ao salvar.')]));
			}

			return $this->response->withStringBody(json_encode(['ok' => true, 'campo' => $campo, 'valor' => $valor]));
		} catch (\Throwable $e) {
			return $this->response->withStringBody(json_encode(['ok' => false, 'error' => $e->getMessage()]));
		}
	}

	/**
	 * GET /clientes-prototype/export.csv — exporta clientes.
	 */
	public function exportCsv() {
		$empresa = (int)$this->Auth->user('idempresa');
		$rows = [];
		try {
			$rows = $this->Clientes->find()
				->where(['Clientes.idempresa' => $empresa])
				->order(['Clientes.id' => 'DESC'])
				->limit(10000)
				->all();
		} catch (\Throwable $e) {
		}
		$this->autoRender = false;
		$fname = 'clientes-' . date('Ymd-His') . '.csv';
		$this->response = $this->response
			->withType('text/csv')
			->withHeader('Content-Disposition', 'attachment; filename="' . $fname . '"');
		$out = fopen('php://temp', 'w+');
		fwrite($out, "\xEF\xBB\xBF");
		fputcsv($out, ['ID', 'Tipo', 'Nome / Razão social', 'Fantasia', 'CNPJ/CPF', 'E-mail', 'Telefone', 'Cidade', 'Estado', 'Status', 'Desde'], ';');
		foreach ($rows as $c) {
			$tipo = (int)$c->get('tipo') === 2 ? 'PJ' : 'PF';
			$nome = $tipo === 'PJ'
				? (string)($c->get('razaosocial') ?? $c->get('nome'))
				: (string)$c->get('nome');
			fputcsv($out, [
				(int)$c->get('id'),
				$tipo,
				$nome,
				(string)($c->get('nomefantasia') ?? ''),
				(string)($c->get('cnpj') ?? $c->get('cpf') ?? ''),
				(string)($c->get('email') ?? ''),
				(string)($c->get('fone') ?? $c->get('fone2') ?? ''),
				(string)($c->get('idcidade') ?? ''),
				(string)($c->get('estado') ?? ''),
				(int)$c->get('inativo') === 1 ? 'Inativo' : 'Ativo',
				$c->get('membrodesde') instanceof \DateTimeInterface ? $c->get('membrodesde')->format('d/m/Y') : '',
			], ';');
		}
		rewind($out);

		return $this->response->withStringBody(stream_get_contents($out));
	}

	/**
	 * Telas adicionais (novo, 360, import, export).
	 *
	 * @param string $page
	 */
	public function view($page = 'lista') {
		if ($page === 'lista') {
			return $this->lista();
		}
		if ($page === 'novo') {
			return $this->redirect(['controller' => 'Clientes', 'action' => 'add']);
		}
		if ($page === 'export') {
			return $this->redirect(['action' => 'exportCsv']);
		}
		$allowed = ['360', 'export', 'import'];
		if (!in_array($page, $allowed, true)) {
			throw new NotFoundException(__('Tela do protótipo não encontrada.'));
		}

		$this->set([
			'title' => __('Clientes · {0}', ucfirst($page)),
			'erpNavActive' => 'clientes',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Cadastros')],
				['label' => __('Clientes'), 'url' => ['controller' => 'ClientesPrototype', 'action' => 'lista']],
				['label' => ucfirst($page), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'page' => $page,
		]);

		if ($page === '360') {
			$cliId = (int)$this->request->getQuery('id', 0);
			if ($cliId > 0) {
				return $this->redirect(['action' => 'visao360', $cliId]);
			}
			$this->Flash->warning(__('Informe o cliente para abrir a Visão 360°.'));

			return $this->redirect(['action' => 'lista']);
		}
		if ($page === 'import') {
			return $this->render('import');
		}

		return $this->render('placeholder');
	}

	/**
	 * pg-cliente-360 — paridade com Clientes/visao360 no shell erp_prototype.
	 *
	 * @param int|string|null $id
	 */
	public function visao360($id = null) {
		if ((int)($this->Auth->user('role') ?? -1) === 1) {
			$this->Flash->error(__('Você não possui permissões para acessar esta página.'));

			return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
		}
		$cid = (int)$id;
		if ($cid <= 0) {
			$this->Flash->warning(__('Cliente inválido.'));

			return $this->redirect(['action' => 'lista']);
		}
		$q = $this->Clientes->find()
			->contain(['Cidades.Estados'])
			->where(['Clientes.id' => $cid]);
		$this->Abac->applyToQuery($q, 'Clientes');
		$cliente = $q->first();
		if (empty($cliente)) {
			$this->Flash->error(__('Cliente não encontrado ou sem permissão.'));

			return $this->redirect(['action' => 'lista']);
		}
		$nome = $this->_clientesIndexNomeExibicao($cliente);
		$tab = trim((string)$this->request->getQuery('tab', 'geral'));
		$allowedTabs = ['geral', 'orcamentos', 'os', 'financeiro', 'contratos', 'historico', 'arquivos'];
		if (!in_array($tab, $allowedTabs, true)) {
			$tab = 'geral';
		}
		$this->set([
			'title' => __('Visão 360° — {0}', $nome),
			'erpNavActive' => 'clientes',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Cadastros')],
				['label' => __('Clientes'), 'url' => ['controller' => 'ClientesPrototype', 'action' => 'lista']],
				['label' => __('Visão 360°'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'cli360Proto' => true,
			'cli360Tab' => $tab,
			'cliente' => $cliente,
			'cliContatosReady' => $this->_clientesContatosReady(),
			'cli360' => $this->_clientesVisao360Payload($cliente),
		]);
		$this->viewBuilder()->setTemplate('visao360');
		$this->viewBuilder()->setTemplatePath('Clientes');
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	/**
	 * Clientes com ao menos uma fatura vencida e não quitada (escopo empresa).
	 */
	protected function countClientesInadimplentes(int $empresa): int {
		if ($empresa <= 0) {
			return 0;
		}
		try {
			$faturas = $this->loadModel('Faturas');
		} catch (\Throwable $e) {
			return 0;
		}
		$now = \Cake\I18n\FrozenTime::now();
		$clientes = [];
		try {
			$rows = $faturas->find()
				->select(['idcliente', 'status', 'vencimento', 'dtretorno'])
				->where([
					'Faturas.idempresa' => $empresa,
					'Faturas.idcliente IS NOT' => null,
					'Faturas.vencimento <' => $now,
				])
				->limit(5000)
				->all();
			foreach ($rows as $f) {
				$cid = (int)$f->get('idcliente');
				if ($cid <= 0) {
					continue;
				}
				$status = strtolower((string)($f->get('status') ?? ''));
				$pago = strpos($status, 'pag') !== false || $f->get('dtretorno') instanceof \DateTimeInterface;
				if (!$pago) {
					$clientes[$cid] = true;
				}
			}
		} catch (\Throwable $e) {
			return 0;
		}

		return count($clientes);
	}

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
