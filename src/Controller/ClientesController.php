<?php
namespace App\Controller;

use App\Utility\ErpIntegrationRequest;
use App\Controller\AppController;
use App\Service\Common\CryptoService;
use App\Service\ClienteDomain\ClienteDomainBridge;
use App\Service\ClienteDomain\InfrastructureGuard;
use App\Service\ClienteIntegration\ClienteErpSyncService;
use App\Model\Table\ClientesTable;
use App\Utility\ClienteDomainEventType;
use App\Utility\RbacChecker;
use Cake\Event\Event;
use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use CakeSoap\Network\CakeSoap;

$__pgmUserConstants = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';
if (is_file($__pgmUserConstants)) {
	require_once $__pgmUserConstants;
}
$__pgmUtilities = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'Utilities.php';
if (is_file($__pgmUtilities)) {
	require_once $__pgmUtilities;
}
$__cakeSoap = ROOT . DS . 'vendor' . DS . 'queencitycodefactory' . DS . 'cakesoap' . DS . 'src' . DS . 'Network' . DS . 'CakeSoap.php';
if (is_file($__cakeSoap)) {
	require_once $__cakeSoap;
}
if (!defined('C_RoleCliente')) {
	define('C_RoleCliente', 1);
}
if (!defined('C_RoleFuncionario')) {
	define('C_RoleFuncionario', 0);
}
if (!defined('C_ClientesTipoJuridica')) {
	define('C_ClientesTipoJuridica', 2);
}
if (!defined('C_ClientesTipoFisica')) {
	define('C_ClientesTipoFisica', 1);
}

class ClientesController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->loadModel('Cidades');
		$this->loadModel('Estados');
		$this->loadModel('Servicos');
		$this->loadModel('Clientes');
		$this->loadModel('Cliacessos');
		$this->loadModel('Clicontratos');
		$this->loadModel('Visitas');
		$this->loadModel('Empresas');
		$this->loadModel('Users');
		$this->loadModel('Config');
	}

	/**
	 * Cliente do ID informado, restrito à empresa do utilizador (mitiga IDOR).
	 *
	 * @param int|string|null $id
	 * @return \App\Model\Entity\Cliente|null
	 */
	protected function _findClienteForCurrentUser($id) {
		if ($id === null || $id === '') {
			return null;
		}
		$q = $this->Clientes->find()->where(['id' => (int) $id]);
		$this->Abac->applyToQuery($q, 'Clientes');

		return $q->first();
	}

	/**
	 * Data de validade do item de contrato em Y-m-d, ou null se cancelado ou sem validade útil.
	 * Usado no resumo do rodapé e na situação da linha na aba Contratos (mesma regra).
	 *
	 * @param object $c Registro Clicontratos
	 */
	protected function _clicontratoValidadeYmd($c): ?string {
		if (!empty($c->dtcancelamento)) {
			return null;
		}
		$raw = $c->dtvalidade ?? null;
		if ($raw instanceof \DateTimeInterface) {
			return $raw->format('Y-m-d');
		}
		if (is_string($raw) && $raw !== '') {
			$t = strtotime($raw);

			return $t ? date('Y-m-d', $t) : null;
		}

		return null;
	}

	/**
	 * Rótulo, classe de linha e badge Bootstrap para a listagem de contratos na ficha do cliente.
	 *
	 * @param object $c Registro Clicontratos
	 */
	protected function _clicontratoRowUi($c, string $todayStr, string $lim30): array {
		if (!empty($c->dtcancelamento)) {
			return [
				'label' => 'Cancelado',
				'row_class' => 'cli-ctr-row--cancelado',
				'badge_class' => 'badge-secondary',
			];
		}
		$dv = $this->_clicontratoValidadeYmd($c);
		if ($dv === null) {
			return [
				'label' => 'Sem validade',
				'row_class' => 'cli-ctr-row--semvalidade',
				'badge_class' => 'badge-secondary',
			];
		}
		if ($dv < $todayStr) {
			return [
				'label' => 'Vencido',
				'row_class' => 'cli-ctr-row--vencido',
				'badge_class' => 'badge-danger',
			];
		}
		if ($dv <= $lim30) {
			return [
				'label' => 'Vence em 30 dias',
				'row_class' => 'cli-ctr-row--vencendo',
				'badge_class' => 'badge-warning text-dark',
			];
		}

		return [
			'label' => 'Ativo',
			'row_class' => 'cli-ctr-row--ok',
			'badge_class' => 'badge-success',
		];
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->set('title', 'Clientes');
		$this->Auth->allow(['addApi', 'listApi', 'addAPI', 'listAPI']);
	}

	public function index() {
		// Permissão para o cliente
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		$this->set('title', 'Clientes');
		$this->set('hideLayoutPageTitle', true);
		$empresaLbl = trim((string)($this->viewVars['nomeempresa'] ?? ''));
		if ($empresaLbl === '') {
			$empresaLbl = __('PGM Soluções');
		}
		$this->set('topbarParentLabel', __('Cadastros'));
		$this->set('topbarCurrentLabel', __('Clientes'));
		$this->set('pgmTopbarClientesLista', true);
		$this->set('pgmTopbarEmpresas', $this->_clientesTopbarEmpresas());

		$qAll = $this->Clientes->find('all')->contain(['Cidades.Estados'])->order(['Clientes.id' => 'DESC']);
		$this->Abac->applyToQuery($qAll, 'Clientes');
		$todos = $qAll->toArray();

		$clientesAtivos = array_values(array_filter($todos, function ($c) {
			return (int)$c->inativo === 0;
		}));
		$clientesInativos = array_values(array_filter($todos, function ($c) {
			return (int)$c->inativo === 1;
		}));

		$this->set('clientesAtivos', $clientesAtivos);
		$this->set('clientesInativos', $clientesInativos);
		$this->set('clientesLista', $todos);

		$this->set('clientesAtivosPJ', array_values(array_filter($clientesAtivos, function ($c) {
			return (int)$c->tipo === (int)C_ClientesTipoJuridica;
		})));
		$this->set('clientesAtivosPF', array_values(array_filter($clientesAtivos, function ($c) {
			return (int)$c->tipo === (int)C_ClientesTipoFisica;
		})));
		$this->set('clientesInativosPJ', array_values(array_filter($clientesInativos, function ($c) {
			return (int)$c->tipo === (int)C_ClientesTipoJuridica;
		})));
		$this->set('clientesInativosPF', array_values(array_filter($clientesInativos, function ($c) {
			return (int)$c->tipo === (int)C_ClientesTipoFisica;
		})));

		$crm = $this->_clientesIndexCrmMetrics($todos, count($clientesAtivos));
		$this->set('cliCrm', $crm);
		$this->set('cliRows', $this->_clientesIndexRows($todos, $crm));
		$this->set('cliVendedores', $this->_clientesIndexVendedoresLista());
	}

	/**
	 * Empresas do utilizador para o seletor da topbar (lista CRM).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function _clientesTopbarEmpresas() {
		$lista = [];
		$idAtiva = (int)$this->Auth->user('idempresa');
		$opt = (array)($this->viewVars['empresasOptSidebar'] ?? []);
		if ($opt === [] && $this->Auth->user('id') > 0) {
			try {
				$rows = $this->Empresasusers->find('all')
					->where(['Empresasusers.iduser' => (int)$this->Auth->user('id')])
					->contain(['Empresas'])
					->order(['Empresas.nomefantasia' => 'ASC'])
					->all();
				foreach ($rows as $reg) {
					if (!empty($reg->empresa)) {
						$opt[(int)$reg->idempresa] = (string)$reg->empresa->nomefantasia;
					}
				}
			} catch (\Throwable $e) {
			}
		}
		foreach ($opt as $eid => $nome) {
			$eid = (int)$eid;
			$cnpj = '';
			try {
				$emp = $this->Empresas->get($eid, ['fields' => ['cnpj', 'razaosocial', 'nomefantasia']]);
				$cnpj = formatCnpjCpf((string)($emp->cnpj ?? ''));
				if (trim((string)$nome) === '') {
					$nome = (string)($emp->nomefantasia ?? $emp->razaosocial ?? '');
				}
			} catch (\Throwable $e) {
			}
			$parts = preg_split('/\s+/u', trim((string)$nome), -1, PREG_SPLIT_NO_EMPTY) ?: [];
			$ini = '';
			if (!empty($parts[0])) {
				$ini .= mb_strtoupper(mb_substr($parts[0], 0, 1, 'UTF-8'));
			}
			if (!empty($parts[1])) {
				$ini .= mb_strtoupper(mb_substr($parts[1], 0, 1, 'UTF-8'));
			}
			$lista[] = [
				'id' => $eid,
				'nome' => (string)$nome,
				'cnpj' => $cnpj,
				'initials' => $ini !== '' ? $ini : 'PG',
				'current' => $eid === $idAtiva,
			];
		}

		return $lista;
	}

	/**
	 * Vendedores (autores de orçamentos) para filtro — dados reais quando existir orçamento.
	 *
	 * @return array<int,string>
	 */
	protected function _clientesIndexVendedoresLista() {
		$out = [];
		$idempresa = (int)$this->Auth->user('idempresa');
		try {
			$this->loadModel('Orcamentos');
			$rows = $this->Orcamentos->find()
				->select(['Users.id', 'Users.name'])
				->distinct(['Users.id'])
				->contain(['Users'])
				->where([
					'Orcamentos.idempresa' => $idempresa,
					'Orcamentos.idautor IS NOT' => null,
				])
				->order(['Users.name' => 'ASC'])
				->limit(200)
				->all();
			foreach ($rows as $r) {
				if (!empty($r->user) && !empty($r->user->id)) {
					$out[(int)$r->user->id] = trim((string)($r->user->name ?? ''));
				}
			}
		} catch (\Throwable $e) {
		}
		if ($out === []) {
			try {
				$this->loadModel('Users');
				$q = $this->Users->find('list', ['keyField' => 'id', 'valueField' => 'name'])
					->where(['Users.role' => 0, 'Users.inativo' => 0])
					->order(['Users.name' => 'ASC'])
					->limit(200);
				$out = $q->toArray();
			} catch (\Throwable $e) {
			}
		}

		return $out;
	}

	/**
	 * Último autor de orçamento por cliente (vendedor para filtro da lista).
	 *
	 * @return array<int,int>
	 */
	protected function _clientesVendedorPorCliente() {
		$map = [];
		try {
			$this->loadModel('Orcamentos');
			$idempresa = (int)$this->Auth->user('idempresa');
			$rows = $this->Orcamentos->find()
				->select(['Orcamentos.idcliente', 'Orcamentos.idautor'])
				->where([
					'Orcamentos.idempresa' => $idempresa,
					'Orcamentos.idcliente IS NOT' => null,
					'Orcamentos.idautor IS NOT' => null,
				])
				->order(['Orcamentos.id' => 'DESC'])
				->limit(8000)
				->all();
			foreach ($rows as $r) {
				$cid = (int)$r->idcliente;
				$aid = (int)$r->idautor;
				if ($cid > 0 && $aid > 0 && !isset($map[$cid])) {
					$map[$cid] = $aid;
				}
			}
		} catch (\Throwable $e) {
		}

		return $map;
	}

	/**
	 * Classifica segmento de mercado por nome/fantasia (heurística — sem coluna segmento no cadastro).
	 *
	 * @param \App\Model\Entity\Cliente $cliente
	 * @return array{slug:string,label:string,short:string,tone:string}
	 */
	protected function _clientesClassificarSegmento($cliente) {
		$blob = mb_strtolower(trim(implode(' ', array_filter([
			(string)($cliente->razaosocial ?? ''),
			(string)($cliente->nomefantasia ?? ''),
			(string)($cliente->nome ?? ''),
		]))), 'UTF-8');
		$rules = [
			['slug' => 'moveis', 'label' => __('Móveis & Decoração'), 'short' => __('Móveis'), 'tone' => 'teal', 'words' => ['móvel', 'moveis', 'moble', 'decora', 'ambiente', 'marcen', 'móveis']],
			['slug' => 'saude', 'label' => __('Saúde & Estética'), 'short' => __('Saúde'), 'tone' => 'blue', 'words' => ['saúde', 'saude', 'clín', 'clin', 'estét', 'estet', 'hospital', 'biosseg', 'medic', 'laborat']],
			['slug' => 'imob', 'label' => __('Imobiliário'), 'short' => __('Imobiliário'), 'tone' => 'rose', 'words' => ['imob', 'imóvel', 'imovel', 'corretor', 'incorp']],
			['slug' => 'industria', 'label' => __('Indústria'), 'short' => __('Indústria'), 'tone' => 'orange', 'words' => ['indúst', 'indust', 'fabri', 'metalúrg', 'metalurg', 'plást', 'plastic']],
		];
		foreach ($rules as $rule) {
			foreach ($rule['words'] as $w) {
				if ($w !== '' && mb_strpos($blob, $w, 0, 'UTF-8') !== false) {
					return [
						'slug' => $rule['slug'],
						'label' => $rule['label'],
						'short' => $rule['short'],
						'tone' => $rule['tone'],
					];
				}
			}
		}

		return ['slug' => 'outros', 'label' => __('Outros'), 'short' => __('Outros'), 'tone' => 'purple'];
	}

	/**
	 * @param \App\Model\Entity\Cliente[] $todos
	 * @return array<int,array{slug:string,label:string,count:int,pct:int,tone:string}>
	 */
	protected function _clientesSegmentosDistribuicao(array $todos) {
		$defs = [
			'moveis' => ['label' => __('Móveis & Decoração'), 'tone' => 'teal'],
			'saude' => ['label' => __('Saúde & Estética'), 'tone' => 'blue'],
			'imob' => ['label' => __('Imobiliário'), 'tone' => 'rose'],
			'industria' => ['label' => __('Indústria'), 'tone' => 'orange'],
			'outros' => ['label' => __('Outros'), 'tone' => 'purple'],
		];
		$counts = array_fill_keys(array_keys($defs), 0);
		foreach ($todos as $c) {
			if ((int)$c->inativo === 1) {
				continue;
			}
			$seg = $this->_clientesClassificarSegmento($c);
			$slug = $seg['slug'];
			if (!isset($counts[$slug])) {
				$counts['outros']++;
			} else {
				$counts[$slug]++;
			}
		}
		$total = array_sum($counts);
		$out = [];
		foreach ($defs as $slug => $meta) {
			$cnt = (int)($counts[$slug] ?? 0);
			$out[] = [
				'slug' => $slug,
				'label' => $meta['label'],
				'count' => $cnt,
				'pct' => $total > 0 ? (int)round(100 * $cnt / $total) : 0,
				'tone' => $meta['tone'],
			];
		}

		return $out;
	}

	/**
	 * Linhas da tabela CRM com métricas por cliente.
	 *
	 * @param \App\Model\Entity\Cliente[] $todos
	 * @param array<string,mixed> $crm
	 * @return array<int,array<string,mixed>>
	 */
	protected function _clientesIndexRows(array $todos, array $crm) {
		$receitaPorCliente = (array)($crm['receita_por_cliente'] ?? []);
		$aReceberPorCliente = (array)($crm['a_receber_por_cliente'] ?? []);
		$atrasoDias = (array)($crm['atraso_dias_por_cliente'] ?? []);
		$ultimaOs = (array)($crm['ultima_os_por_cliente'] ?? []);
		$vipIds = (array)($crm['vip_ids'] ?? []);
		$top10Ids = (array)($crm['top10_ids'] ?? []);
		$vendedorPorCliente = (array)($crm['vendedor_por_cliente'] ?? []);
		$cutoffContato = (new \DateTimeImmutable('today'))->modify('-30 days');
		$mesAtual = (int)date('n');
		$anoAtual = (int)date('Y');
		$avTones = ['teal', 'blue', 'rose', 'orange', 'purple', 'navy', 'wine'];
		$rows = [];
		foreach ($todos as $reg) {
			$cid = (int)$reg->id;
			$isPj = (int)$reg->tipo === (int)C_ClientesTipoJuridica;
			$nome = $this->_clientesIndexNomeExibicao($reg);
			$doc = $isPj ? (string)($reg->cnpj ?? '') : (string)($reg->cpf ?? '');
			$seg = $this->_clientesClassificarSegmento($reg);
			$contato = trim((string)($reg->nomeresponsavel ?? ''));
			if ($contato === '' && !$isPj) {
				$contato = trim((string)($reg->nome ?? ''));
			}
			$email = trim((string)($reg->email ?? ''));
			$sub = $contato;
			if ($email !== '') {
				$sub = $sub !== '' ? $sub . ' (' . $email . ')' : $email;
			}
			$cidadeDisplay = '';
			if (!empty($reg->cidade) && !empty($reg->cidade->nome)) {
				$cidadeDisplay = (string)$reg->cidade->nome;
				$uf = '';
				if (!empty($reg->cidade->estado) && !empty($reg->cidade->estado->sigla)) {
					$uf = strtoupper(trim((string)$reg->cidade->estado->sigla));
				}
				if ($uf !== '') {
					$cidadeDisplay .= '/' . $uf;
				}
			}
			$codigo = trim((string)($reg->public_code ?? ''));
			if ($codigo === '') {
				$codigo = '—';
			}
			$rec12 = isset($receitaPorCliente[$cid]) ? (float)$receitaPorCliente[$cid] : 0.0;
			$aRec = isset($aReceberPorCliente[$cid]) ? (float)$aReceberPorCliente[$cid] : 0.0;
			$diasAtraso = (int)($atrasoDias[$cid] ?? 0);
			$ultima = (string)($ultimaOs[$cid] ?? '');
			if ($ultima === '' && !empty($reg->membrodesde) && $reg->membrodesde instanceof \DateTimeInterface) {
				$ultima = $reg->membrodesde->format('d/m/Y');
			}
			if ($ultima === '') {
				$ultima = '—';
			}
			$statusUi = 'ativo';
			$statusLabel = __('Ativo');
			$statusClass = 'on';
			if ((int)$reg->inativo === 1) {
				$statusUi = 'inativo';
				$statusLabel = __('Bloqueado');
				$statusClass = 'blocked';
			} elseif ($diasAtraso > 0) {
				$statusUi = 'atraso';
				$statusLabel = __('Atraso {0}d', $diasAtraso);
				$statusClass = 'warn';
			} elseif (isset($vipIds[$cid])) {
				$statusUi = 'vip';
				$statusLabel = __('VIP · Ativo');
				$statusClass = 'vip';
			}
			$isNovo = false;
			$isAniv = false;
			if (!empty($reg->membrodesde) && $reg->membrodesde instanceof \DateTimeInterface) {
				$isNovo = ((int)$reg->membrodesde->format('n') === $mesAtual && (int)$reg->membrodesde->format('Y') === $anoAtual);
				$isAniv = ((int)$reg->membrodesde->format('n') === $mesAtual);
			}
			$semContato = 0;
			if ((int)$reg->inativo === 0) {
				$ultimaDt = null;
				if ($ultima !== '' && $ultima !== '—') {
					$ultimaDt = \DateTime::createFromFormat('d/m/Y', $ultima) ?: null;
				}
				if ($ultimaDt === null || $ultimaDt < $cutoffContato) {
					$semContato = 1;
				}
			}
			$rows[] = [
				'entity' => $reg,
				'id' => $cid,
				'codigo' => $codigo,
				'nome' => $nome,
				'subline' => $sub,
				'doc' => $doc,
				'segmento' => $seg,
				'cidade' => $cidadeDisplay,
				'av_tone' => $avTones[$cid % count($avTones)],
				'receita12' => $rec12,
				'a_receber' => $aRec,
				'status_ui' => $statusUi,
				'status_label' => $statusLabel,
				'status_class' => $statusClass,
				'ultima' => $ultima,
				'is_pj' => $isPj,
				'status_key' => (int)$reg->inativo === 1 ? 'inativos' : 'ativos',
				'tipo_key' => $isPj ? 'pj' : 'pf',
				'atraso' => $diasAtraso > 0 ? 1 : 0,
				'vip' => isset($vipIds[$cid]) ? 1 : 0,
				'segmento_slug' => $seg['slug'],
				'novo_mes' => $isNovo ? 1 : 0,
				'aniversariante' => $isAniv ? 1 : 0,
				'top_receita' => isset($top10Ids[$cid]) ? 1 : 0,
				'sem_contato' => $semContato,
				'vendedor_id' => (int)($vendedorPorCliente[$cid] ?? 0),
			];
		}

		return $rows;
	}

	/**
	 * KPIs e painéis da lista CRM (receita / inadimplência via financeiro_lancamentos quando existir).
	 *
	 * @param \App\Model\Entity\Cliente[] $todos
	 * @param int $cntAtivos
	 * @return array<string,mixed>
	 */
	protected function _clientesIndexCrmMetrics(array $todos, int $cntAtivos) {
		$cntTotal = count($todos);
		$cntPj = 0;
		$cntPf = 0;
		foreach ($todos as $c) {
			if ((int)$c->tipo === (int)C_ClientesTipoJuridica) {
				$cntPj++;
			} elseif ((int)$c->tipo === (int)C_ClientesTipoFisica) {
				$cntPf++;
			}
		}
		$cntInativos = $cntTotal - $cntAtivos;

		$receita12 = 0.0;
		$receitaPrev = 0.0;
		$inadValor = 0.0;
		$inadClientes = 0;
		$top5 = [];
		$receitaPorCliente = [];
		$aReceberPorCliente = [];
		$atrasoDiasPorCliente = [];
		$ultimaOsPorCliente = [];
		$hasFin = false;
		$novosMes = 0;
		$aniversariantes = 0;
		$mesAtual = (int)date('n');
		$anoAtual = (int)date('Y');
		foreach ($todos as $c) {
			if (!empty($c->membrodesde) && $c->membrodesde instanceof \DateTimeInterface) {
				$m = (int)$c->membrodesde->format('n');
				$y = (int)$c->membrodesde->format('Y');
				if ($m === $mesAtual && $y === $anoAtual) {
					$novosMes++;
				}
				if ($m === $mesAtual) {
					$aniversariantes++;
				}
			}
		}

		try {
			$finTable = TableRegistry::getTableLocator()->get('FinanceiroLancamentos');
			$hasFin = true;
			$idempresa = (int)$this->Auth->user('idempresa');
			$hoje = FrozenDate::today();
			$ini12 = $hoje->subMonths(12);
			$iniPrev = $ini12->subMonths(12);
			$fimPrev = $ini12->subDay(1);

			$qRec = $finTable->find();
			$qRec->select(['s' => $qRec->func()->sum('FinanceiroLancamentos.valor')])
				->where([
					'FinanceiroLancamentos.idempresa' => $idempresa,
					'FinanceiroLancamentos.tipo' => 'receita',
					'FinanceiroLancamentos.data_lancamento >=' => $ini12->format('Y-m-d'),
					'FinanceiroLancamentos.data_lancamento <=' => $hoje->format('Y-m-d'),
				]);
			$rowRec = $qRec->first();
			$receita12 = $rowRec && $rowRec->s !== null ? (float)$rowRec->s : 0.0;

			$qPrev = $finTable->find();
			$qPrev->select(['s' => $qPrev->func()->sum('FinanceiroLancamentos.valor')])
				->where([
					'FinanceiroLancamentos.idempresa' => $idempresa,
					'FinanceiroLancamentos.tipo' => 'receita',
					'FinanceiroLancamentos.data_lancamento >=' => $iniPrev->format('Y-m-d'),
					'FinanceiroLancamentos.data_lancamento <=' => $fimPrev->format('Y-m-d'),
				]);
			$rowPrev = $qPrev->first();
			$receitaPrev = $rowPrev && $rowPrev->s !== null ? (float)$rowPrev->s : 0.0;

			$qInad = $finTable->find();
			$qInad->select([
				'FinanceiroLancamentos.idcliente',
				'FinanceiroLancamentos.data_vencimento',
				'valor' => 'FinanceiroLancamentos.valor',
			])
				->where([
					'FinanceiroLancamentos.idempresa' => $idempresa,
					'FinanceiroLancamentos.tipo' => 'receita',
					'FinanceiroLancamentos.status' => 'aberto',
					'FinanceiroLancamentos.data_vencimento IS NOT' => null,
					'FinanceiroLancamentos.data_vencimento <' => $hoje->format('Y-m-d'),
				]);
			$inadIds = [];
			$hojeStr = $hoje->format('Y-m-d');
			foreach ($qInad->all() as $inadRow) {
				$cidInad = (int)$inadRow->idcliente;
				$inadIds[$cidInad] = true;
				$inadValor += (float)($inadRow->valor ?? 0);
				if (!empty($inadRow->data_vencimento)) {
					$dv = $inadRow->data_vencimento instanceof \DateTimeInterface
						? $inadRow->data_vencimento->format('Y-m-d')
						: (string)$inadRow->data_vencimento;
					try {
						$d1 = new \DateTimeImmutable($dv);
						$d2 = new \DateTimeImmutable($hojeStr);
						$diff = (int)$d1->diff($d2)->days;
						if (!isset($atrasoDiasPorCliente[$cidInad]) || $diff > $atrasoDiasPorCliente[$cidInad]) {
							$atrasoDiasPorCliente[$cidInad] = $diff;
						}
					} catch (\Throwable $e) {
					}
				}
			}
			$inadClientes = count($inadIds);

			$qTop = $finTable->find();
			$qTop->select([
				'FinanceiroLancamentos.idcliente',
				'total' => $qTop->func()->sum('FinanceiroLancamentos.valor'),
			])
				->where([
					'FinanceiroLancamentos.idempresa' => $idempresa,
					'FinanceiroLancamentos.tipo' => 'receita',
					'FinanceiroLancamentos.data_lancamento >=' => $ini12->format('Y-m-d'),
				])
				->group(['FinanceiroLancamentos.idcliente'])
				->order(['total' => 'DESC'])
				->limit(5);
			$topRows = $qTop->all()->toArray();

			$nomePorId = [];
			foreach ($todos as $c) {
				$nomePorId[(int)$c->id] = $this->_clientesIndexNomeExibicao($c);
			}
			foreach ($topRows as $tr) {
				$cid = (int)$tr->idcliente;
				$val = (float)($tr->total ?? 0);
				$top5[] = [
					'id' => $cid,
					'nome' => $nomePorId[$cid] ?? ('#' . $cid),
					'valor' => $val,
					'pct' => $receita12 > 0 ? (int)round(100 * $val / $receita12) : 0,
				];
			}

			$qPorCli = $finTable->find();
			$qPorCli->select([
				'FinanceiroLancamentos.idcliente',
				'total' => $qPorCli->func()->sum('FinanceiroLancamentos.valor'),
			])
				->where([
					'FinanceiroLancamentos.idempresa' => $idempresa,
					'FinanceiroLancamentos.tipo' => 'receita',
					'FinanceiroLancamentos.data_lancamento >=' => $ini12->format('Y-m-d'),
				])
				->group(['FinanceiroLancamentos.idcliente']);
			foreach ($qPorCli->all() as $pc) {
				$receitaPorCliente[(int)$pc->idcliente] = (float)($pc->total ?? 0);
			}

			$qAberto = $finTable->find();
			$qAberto->select([
				'FinanceiroLancamentos.idcliente',
				'total' => $qAberto->func()->sum('FinanceiroLancamentos.valor'),
			])
				->where([
					'FinanceiroLancamentos.idempresa' => $idempresa,
					'FinanceiroLancamentos.tipo' => 'receita',
					'FinanceiroLancamentos.status' => 'aberto',
				])
				->group(['FinanceiroLancamentos.idcliente']);
			foreach ($qAberto->all() as $ab) {
				$aReceberPorCliente[(int)$ab->idcliente] = (float)($ab->total ?? 0);
			}
		} catch (\Throwable $e) {
			$this->log('Clientes::index CRM financeiro: ' . $e->getMessage(), 'warning');
		}

		try {
			$this->loadModel('Ordensservico');
			$idempresa = (int)$this->Auth->user('idempresa');
			$osRows = $this->Ordensservico->find()
				->select(['Ordensservico.idcliente', 'Ordensservico.dataabertura'])
				->where(['Ordensservico.idempresa' => $idempresa])
				->order(['Ordensservico.dataabertura' => 'DESC'])
				->limit(5000)
				->all();
			foreach ($osRows as $os) {
				$cidOs = (int)$os->idcliente;
				if ($cidOs <= 0 || isset($ultimaOsPorCliente[$cidOs])) {
					continue;
				}
				$raw = $os->dataabertura;
				if ($raw instanceof \DateTimeInterface) {
					$ultimaOsPorCliente[$cidOs] = $raw->format('d/m/Y');
				} elseif (is_string($raw) && trim($raw) !== '') {
					$ultimaOsPorCliente[$cidOs] = trim($raw);
				}
			}
		} catch (\Throwable $e) {
		}

		$vipIds = [];
		$top10Ids = [];
		if ($receitaPorCliente !== []) {
			arsort($receitaPorCliente);
			$iTop = 0;
			foreach (array_keys($receitaPorCliente) as $tid) {
				if ($iTop >= 10) {
					break;
				}
				$top10Ids[(int)$tid] = true;
				$iTop++;
			}
			$topN = array_slice($receitaPorCliente, 0, max(1, (int)ceil(count($receitaPorCliente) * 0.1)), true);
			foreach (array_keys($topN) as $vid) {
				$vipIds[(int)$vid] = true;
			}
			foreach ($top5 as $t) {
				$vipIds[(int)$t['id']] = true;
			}
		}

		$receitaPct = null;
		if ($hasFin && $receitaPrev > 0.0001) {
			$receitaPct = (int)round(100 * ($receita12 - $receitaPrev) / $receitaPrev);
		}
		$ticketMedio = ($cntAtivos > 0 && $receita12 > 0) ? $receita12 / $cntAtivos : 0.0;

		$segPctPj = $cntTotal > 0 ? (int)round(100 * $cntPj / $cntTotal) : 0;
		$segPctPf = $cntTotal > 0 ? (int)round(100 * $cntPf / $cntTotal) : 0;
		$segmentos = $this->_clientesSegmentosDistribuicao($todos);
		$vendedorPorCliente = $this->_clientesVendedorPorCliente();

		$alertaConc = null;
		if ($top5 !== [] && $receita12 > 0 && $top5[0]['pct'] >= 30) {
			$alertaConc = [
				'nome' => $top5[0]['nome'],
				'pct' => $top5[0]['pct'],
			];
		}

		return [
			'has_fin' => $hasFin,
			'ativos' => $cntAtivos,
			'novos_mes' => $novosMes,
			'receita12_fmt' => $this->_clientesFmtBrlCompact($receita12),
			'receita12_pct' => $receitaPct,
			'ticket_fmt' => $this->_clientesFmtBrl($ticketMedio),
			'inadimplentes' => $inadClientes,
			'inadimplentes_valor_fmt' => $this->_clientesFmtBrl($inadValor),
			'bloqueados' => $cntInativos,
			'aniversariantes' => $aniversariantes,
			'top5' => $top5,
			'alerta_concentracao' => $alertaConc,
			'segmentos' => $segmentos,
			'pj_bar' => ['count' => $cntPj, 'pct' => $segPctPj],
			'pf_bar' => ['count' => $cntPf, 'pct' => $segPctPf],
			'receita_por_cliente' => $receitaPorCliente,
			'a_receber_por_cliente' => $aReceberPorCliente,
			'atraso_dias_por_cliente' => $atrasoDiasPorCliente,
			'ultima_os_por_cliente' => $ultimaOsPorCliente,
			'vip_ids' => $vipIds,
			'top10_ids' => $top10Ids,
			'vendedor_por_cliente' => $vendedorPorCliente,
		];
	}

	protected function _clientesIndexNomeExibicao($cliente) {
		if ((int)$cliente->tipo === (int)C_ClientesTipoJuridica) {
			$n = trim((string)($cliente->razaosocial ?? ''));
			if ($n === '') {
				$n = trim((string)($cliente->nomefantasia ?? ''));
			}

			return $n !== '' ? $n : __('(sem nome)');
		}

		$n = trim((string)($cliente->nome ?? ''));

		return $n !== '' ? $n : __('(sem nome)');
	}

	protected function _clientesFmtBrl($amount) {
		return 'R$ ' . number_format((float)$amount, 2, ',', '.');
	}

	protected function _clientesFmtBrlCompact($amount) {
		$v = (float)$amount;
		if ($v >= 1000000) {
			return 'R$ ' . number_format($v / 1000000, 2, ',', '.') . 'M';
		}
		if ($v >= 1000) {
			return 'R$ ' . number_format($v / 1000, 1, ',', '.') . 'k';
		}

		return $this->_clientesFmtBrl($v);
	}

	public function cadastrar() {
		return $this->redirect(['action' => 'add']);
	}

	public function view($id) {
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		$cliente = $this->_findClienteForCurrentUser($id);
		if (empty($cliente)) {
			$this->Flash->error(__('Cliente não encontrado.'));
			return $this->redirect(['action' => 'index']);
		}
		$cidade = null;
		if (!empty($cliente->idcidade)) {
			$cidade = $this->Cidades->get($cliente->idcidade);
		}
		$this->set(compact('cliente', 'cidade'));
	}

	/**
	 * Pesquisa server-side (tela search.ctp): mesma regra de formatos da lista — e-mail, CNPJ/CPF (apenas dígitos e máscara) ou nome (palavras em nome/razão/fantasia/e-mail).
	 *
	 * @param string $keywords
	 * @return \App\Model\Entity\Cliente[]
	 */
	protected function _findClientesPorKeywords($keywords) {
		$kw = trim((string)$keywords);
		if ($kw === '') {
			return [];
		}
		$qCode = $this->Clientes->find('all')->where(['Clientes.public_code' => $kw]);
		$this->Abac->applyToQuery($qCode, 'Clientes');
		$byPublicCode = $qCode->toArray();
		if (!empty($byPublicCode)) {
			return $byPublicCode;
		}
		if (mb_strpos($kw, '@') !== false) {
			$email = mb_strtolower($kw, 'UTF-8');
			$q = $this->Clientes->find('all')->where([
				'LOWER(Clientes.email) LIKE' => '%' . $email . '%',
			]);
			$this->Abac->applyToQuery($q, 'Clientes');

			return $q->toArray();
		}

		$digits = preg_replace('/\D/', '', $kw);
		if (preg_match('/^[\d\s.\-\/\(\)]+$/u', $kw) && strlen($digits) >= 3) {
			$q = $this->Clientes->find('all')->where([
				'OR' => [
					['Clientes.cnpj LIKE' => '%' . $digits . '%'],
					['Clientes.cpf LIKE' => '%' . $digits . '%'],
				],
			]);
			$this->Abac->applyToQuery($q, 'Clientes');

			return $q->toArray();
		}

		$words = preg_split('/\s+/', mb_strtolower($kw, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);
		if (empty($words)) {
			return [];
		}

		$q = $this->Clientes->find('all');
		$this->Abac->applyToQuery($q, 'Clientes');
		foreach ($words as $w) {
			$q->andWhere([
				'OR' => [
					['LOWER(Clientes.razaosocial) LIKE' => '%' . $w . '%'],
					['LOWER(Clientes.nomefantasia) LIKE' => '%' . $w . '%'],
					['LOWER(Clientes.nome) LIKE' => '%' . $w . '%'],
					['LOWER(Clientes.email) LIKE' => '%' . $w . '%'],
					['LOWER(Clientes.public_code) LIKE' => '%' . $w . '%'],
				],
			]);
		}

		return $q->toArray();
	}

	public function search() {
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		$this->set('title', 'Pesquisa de Clientes');
		$clientes = [];
		if ($this->request->is('get')) {
			$keywords = $this->request->getQuery('keywords');

			if ($keywords) {
				$clientes = $this->_findClientesPorKeywords($keywords);

				foreach ($clientes as $key => $reg) {
					$reg->controller = 'Clientes';
					if ($reg->inativo == 0) {
						$clientes[$key]->search = '<span class="label label-info">Ativo</span>';
					} elseif ($reg->inativo == 1) {
						$clientes[$key]->search = '<span class="label label-danger">Inativo</span>';
					}
				}
			}
		}

		$this->set('clientes', $clientes);
	}
		
	public function add() {
		// Permissão para o cliente
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		
		$this->set('title', 'Adicionar Cliente');
		$this->set('hideLayoutPageTitle', true);
		$this->set('topbarParentLabel', __('Cadastros'));
		$this->set('topbarCurrentLabel', __('Cadastrar clientes'));
		$cliente = $this->Clientes->newEntity();

		if ($this->request->is('post')) {
			$data = $this->request->getData();
			if ($data['tipo'] == C_ClientesTipoFisica) {
				$qDup = $this->Clientes->findByCpf($data['cpf'])->where(['tipo' => C_ClientesTipoFisica]);
				$this->Abac->applyToQuery($qDup, 'Clientes');
				$clientequejaexiste = $qDup->first();
			} else {
				$qDup = $this->Clientes->findByCnpj($data['cnpj']);
				$this->Abac->applyToQuery($qDup, 'Clientes');
				$clientequejaexiste = $qDup->first();
			}
			if(empty($clientequejaexiste)){
				if (!isset($data['inativo'])) $data['inativo'] = '0';
				unset($data['public_code']);

				// Geração do token
				$cpfoucnpj = isset($data['cnpj']) ? $data['cnpj'] : $data['cpf'];
				$string = $this->Auth->user('id') . $cpfoucnpj .  date('d/m/y') .  date('H:i');
				$data['token'] = $this->Clientes->generateToken($string);
			
				$cliente = $this->Clientes->patchEntity($cliente, $data);
				$cliente->membrodesde = date('Y-m-d');
				$cliente->idempresa = $this->Auth->user('idempresa');
				if(!empty($data['cnpj'])) $cliente->cnpj = \removeCaracteres($data['cnpj']);
				if(!empty($data['cpf'])) $cliente->cpf = \removeCaracteres($data['cpf']);
				if(!empty($data['inscricaoestadual'])) $cliente->inscricaoestadual = \removeCaracteres($data['inscricaoestadual']);
				if(!empty($data['inscricaomunicipal'])) $cliente->inscricaomunicipal = \removeCaracteres($data['inscricaomunicipal']);
				if(!empty($data['cep'])) $cliente->cep = \removeCaracteres($data['cep']);
	
				if ($this->Clientes->save($cliente)) {
					$this->sincronizacliente($cliente->id);
					$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $cliente->id);
					$nomeCli = $cliente->tipo == C_ClientesTipoFisica ? ($cliente->nome ?? '') : ($cliente->razaosocial ?? '');
					ClienteDomainBridge::emit(ClienteDomainEventType::CLIENTE_CRIADO, [
						'idcliente' => (int)$cliente->id,
						'idempresa' => (int)$this->Auth->user('idempresa'),
						'actor_user_id' => (int)$this->Auth->user('id'),
						'title' => __('Cliente cadastrado'),
						'message' => __('Novo cliente: {0}', $nomeCli),
						'action_url' => Router::url(['controller' => 'Clientes', 'action' => 'edit', $cliente->id]),
						'entity_type' => 'Cliente',
						'entity_id' => $cliente->id,
					]);
					$this->Flash->success(__('O cliente foi salvo.'));
					return $this->redirect(['action' => 'index']);
				}
				$this->Flash->error(__('Não foi possível adicionar o cliente.'));
			} else $this->Flash->error(__('Já existe um cliente cadastrado com este CPF/CNPJ.'));
		}

		$cidades = $this->Cidades->find('list', ['keyField' => 'id', 'valueField' => 'nome'])->order(['nome'])->toArray();
		$this->set('cidades', $cidades);
		$this->set('cliente', $cliente);
	}

	public function edit($id = null) {
		// Permissão para o cliente
		if ($this->Auth->user('role') == 1){
			if(!$this->Auth->user('permissaoacesso')) return $this->redirect(['controller' => 'Tickets', 'action' => 'indexcliente']);	
			if($this->Auth->user('idcliente') != $id) return $this->redirect(['controller' => 'Clientes', 'action' => 'edit', $this->Auth->user('idcliente')]);
		}

		$cliente = $this->_findClienteForCurrentUser($id);
		if (empty($cliente)) {
			$this->Flash->error(__('Cliente não encontrado ou sem permissão.'));
			return $this->redirect(['action' => 'index']);
		}
		$titlenome = $cliente->tipo == C_ClientesTipoFisica ? $cliente->nome : $cliente->razaosocial;
		$this->set('title', 'Cliente: ' . $titlenome);
		$this->set('hideLayoutPageTitle', true);
		$this->set('topbarParentLabel', __('Cadastros'));
		$this->set('topbarCurrentLabel', __('Editar cliente'));

		// Todos os usuários relacionados a este cliente:
		// - vinculados diretamente por idcliente
		// - ou associados a um cliente com mesmo CNPJ/CPF (casos de cadastros antigos/dominantes)
		$usuariosQuery = $this->Users
			->find('all')
			->contain(['Clientes' => ['fields' => ['id', 'razaosocial', 'nome', 'cnpj', 'cpf']]])
			->order(['Users.username' => 'ASC']);

		$conditions = ['Users.idcliente' => $id];

		if (!empty($cliente->cnpj)) {
			$conditions = [
				'OR' => [
					['Users.idcliente' => $id],
					['Clientes.cnpj IS NOT' => null, 'Clientes.cnpj' => $cliente->cnpj],
				],
			];
		} elseif (!empty($cliente->cpf)) {
			$conditions = [
				'OR' => [
					['Users.idcliente' => $id],
					['Clientes.cpf IS NOT' => null, 'Clientes.cpf' => $cliente->cpf],
				],
			];
		}

		$usuarios = $usuariosQuery->where($conditions)->toArray();
		$usuariosOptions = [];
		foreach ($usuarios as $u) {
			$label = (string)($u->username ?? '');
			if (!empty($u->email)) {
				$label .= ($label !== '' ? ' · ' : '') . $u->email;
			}
			if (!empty($u->name)) {
				$label .= ($label !== '' ? ' — ' : '') . $u->name;
			}
			$usuariosOptions[$u->id] = $label !== '' ? $label : '#' . $u->id;
		}
		$cliente->users = $this->Users->find('all')->where(['idcliente' => $id, 'permissaoacesso' => 1])->toArray();
		$usuariosValue = $this->Users->find('list', ['keyField' => 'id', 'valueField' => 'id'])->where(['idcliente' => $id, 'permissaoacesso' => 1])->toArray();
		$cliente->senha = CryptoService::decrypt($cliente->senha, $cliente->idempresa ?? 0);

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			unset($data['public_code']);
			if ((int)$this->Auth->user('role') === C_RoleFuncionario) {
				$inativoGate = RbacChecker::resourceFieldAccess((int)$this->Auth->user('id'), 'Clientes.field.inativo');
				if ($inativoGate !== null && (empty($inativoGate['visible']) || empty($inativoGate['editable']))) {
					unset($data['inativo']);
				}
			}

			$cliente = $this->Clientes->patchEntity($cliente, $data);
			if(!empty($data['cpf'])) $cliente->cpf = \removeCaracteres($data['cpf']);
			if(!empty($data['senha'])) $cliente->senha = CryptoService::encrypt($data['senha'], $cliente->idempresa ?? 0);

			if ($this->Clientes->save($cliente)) {
				$this->sincronizacliente($id);
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $cliente->id);
				$nomeCli = $cliente->tipo == C_ClientesTipoFisica ? ($cliente->nome ?? '') : ($cliente->razaosocial ?? '');
				ClienteDomainBridge::emit(ClienteDomainEventType::CLIENTE_ATUALIZADO, [
					'idcliente' => (int)$cliente->id,
					'idempresa' => (int)$this->Auth->user('idempresa'),
					'actor_user_id' => (int)$this->Auth->user('id'),
					'title' => __('Cliente atualizado'),
					'message' => __('Cadastro alterado: {0}', $nomeCli),
					'action_url' => Router::url(['controller' => 'Clientes', 'action' => 'edit', $cliente->id]),
					'entity_type' => 'Cliente',
					'entity_id' => $cliente->id,
				]);

				$this->Flash->success(__('O cliente foi salvo.'));
				return $this->redirect(['action' => 'edit', $cliente->id]);
			}

			$this->Flash->error(__('Não foi possível salvar o cliente.'));
		}

		$hoje = date('d/m/Y');
		$mes = date('01/m/Y');

		$cidades = $this->Cidades->find('list', ['keyField' => 'id', 'valueField' => 'nome'])->order(['nome'])->toArray();
		$acessos = $this->Cliacessos->find('all')->order(['nome'])->where(['idcliente' => $id])->toArray();
		$contratos = $this->Clicontratos->find('all')->where(['idcliente' => $id])->toArray();

		$cliFooter = [
			'status_label' => $cliente->inativo ? 'Inativo' : 'Ativo',
			'status_class' => $cliente->inativo ? 'danger' : 'success',
			'contratos_total' => count($contratos),
			'contratos_vencidos' => 0,
			'contratos_vencendo30' => 0,
			'token_note' => 'Renove o token de integração periodicamente; não há data de validade cadastrada no sistema.',
		];
		$todayStr = (new \DateTimeImmutable('today'))->format('Y-m-d');
		$lim30 = (new \DateTimeImmutable('today'))->add(new \DateInterval('P30D'))->format('Y-m-d');
		foreach ($contratos as $c) {
			$dv = $this->_clicontratoValidadeYmd($c);
			if ($dv === null) {
				continue;
			}
			if ($dv < $todayStr) {
				$cliFooter['contratos_vencidos']++;
			} elseif ($dv <= $lim30) {
				$cliFooter['contratos_vencendo30']++;
			}
		}

		$contratosRowUi = [];
		foreach ($contratos as $c) {
			$cid = (int)$c->id;
			if ($cid <= 0) {
				continue;
			}
			$contratosRowUi[$cid] = $this->_clicontratoRowUi($c, $todayStr, $lim30);
		}

		$this->set('acessos', $acessos);
		$this->set('contratos', $contratos);
		$this->set('contratosRowUi', $contratosRowUi);
		$this->set('cliFooter', $cliFooter);
		// Ativos de TI (CMDB) — listagem compacta na aba "Ativos" da ficha do cliente.
		$ativosCliente = [];
		try {
			$assetsTbl = $this->loadModel('Assets');
			$ativosCliente = $assetsTbl->find()
				->where(['Assets.idcliente' => (int)$cliente->id])
				->order(['Assets.id' => 'DESC'])
				->limit(200)
				->toArray();
		} catch (\Throwable $e) {
			$ativosCliente = [];
		}
		$this->set('ativosCliente', $ativosCliente);
		// UF do contribuinte (para consulta IE na edição): a partir da cidade do cliente
		$ufContribuinte = null;
		if (!empty($cliente->idcidade)) {
			$cidade = $this->Cidades->find()->where(['id' => $cliente->idcidade])->first();
			if ($cidade && $cidade->idestado) {
				$estado = $this->Estados->find()->where(['id' => $cidade->idestado])->first();
				if ($estado) {
					$ufContribuinte = $estado->sigla;
				}
			}
		}
		$this->set('ufContribuinte', $ufContribuinte);
		$this->set('cidades', $cidades);
		$this->set('cliente', $cliente);	
		$this->set('usuarios', $usuarios);
		$this->set('usuariosOptions', $usuariosOptions);
		$this->set('usuariosValue', $usuariosValue);
	}

	/**
	 * Visão 360º do cliente (indicadores + histórico com dados reais do ERP).
	 *
	 * @param int|string|null $id
	 */
	public function visao360($id = null) {
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		$q = $this->Clientes->find()
			->contain(['Cidades.Estados'])
			->where(['Clientes.id' => (int)$id]);
		$this->Abac->applyToQuery($q, 'Clientes');
		$cliente = $q->first();
		if (empty($cliente)) {
			$this->Flash->error(__('Cliente não encontrado ou sem permissão.'));
			return $this->redirect(['action' => 'index']);
		}
		$nome = $this->_clientesIndexNomeExibicao($cliente);
		$this->set('title', __('Visão 360° — {0}', $nome));
		$this->set('hideLayoutPageTitle', true);
		$this->set('topbarParentLabel', __('Clientes'));
		$this->set('topbarCurrentLabel', __('Visão 360°'));
		$tab = trim((string)$this->request->getQuery('tab', 'geral'));
		$allowedTabs = ['geral', 'orcamentos', 'os', 'financeiro', 'contratos', 'historico', 'arquivos'];
		if (!in_array($tab, $allowedTabs, true)) {
			$tab = 'geral';
		}
		$this->set('cli360Tab', $tab);
		$this->set('cliente', $cliente);
		$this->set('cli360', $this->_clientesVisao360Payload($cliente));
	}

	/**
	 * Histórico legado — redireciona para a aba Histórico da Visão 360º.
	 *
	 * @param int|string|null $id
	 */
	public function eventos($id = null) {
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		if ($id === null || $id === '') {
			return $this->redirect(['action' => 'index']);
		}

		return $this->redirect(['action' => 'visao360', (int)$id, '?' => ['tab' => 'historico']]);
	}

	/**
	 * Dados da Visão 360º (financeiro, contagens, timeline, saúde).
	 *
	 * @param \App\Model\Entity\Cliente $cliente
	 * @return array<string,mixed>
	 */
	protected function _clientesVisao360Payload($cliente) {
		$cid = (int)$cliente->id;
		$idempresa = (int)$this->Auth->user('idempresa');
		$isPj = (int)$cliente->tipo === (int)C_ClientesTipoJuridica;
		$codigo = trim((string)($cliente->public_code ?? ''));
		if ($codigo === '') {
			$codigo = '—';
		}
		$seg = $this->_clientesClassificarSegmento($cliente);
		$cidadeDisplay = '';
		if (!empty($cliente->cidade) && !empty($cliente->cidade->nome)) {
			$cidadeDisplay = (string)$cliente->cidade->nome;
			if (!empty($cliente->cidade->estado) && !empty($cliente->cidade->estado->sigla)) {
				$cidadeDisplay .= '/' . strtoupper(trim((string)$cliente->cidade->estado->sigla));
			}
		}
		$endereco = trim(implode(', ', array_filter([
			trim((string)($cliente->endereco ?? '')),
			trim((string)($cliente->nroendereco ?? '')),
			trim((string)($cliente->bairro ?? '')),
			$cidadeDisplay,
			trim((string)($cliente->cep ?? '')),
		], static function ($p) {
			return $p !== '';
		})));
		$membroLabel = '';
		$anosCliente = '';
		if (!empty($cliente->membrodesde) && $cliente->membrodesde instanceof \DateTimeInterface) {
			$membroLabel = $cliente->membrodesde->i18nFormat('MMMM yyyy');
			$diff = $cliente->membrodesde->diff(new \DateTimeImmutable('today'));
			$anos = (int)$diff->y;
			$anosCliente = $anos > 0 ? __('{0} anos', $anos) : __('menos de 1 ano');
		}

		$payload = [
			'codigo' => $codigo,
			'nome' => $this->_clientesIndexNomeExibicao($cliente),
			'fantasia' => trim((string)($cliente->nomefantasia ?? '')),
			'doc' => $isPj ? (string)($cliente->cnpj ?? '') : (string)($cliente->cpf ?? ''),
			'ie' => trim((string)($cliente->inscricaoestadual ?? '')),
			'segmento' => $seg,
			'endereco' => $endereco,
			'cidade' => $cidadeDisplay,
			'fone' => trim((string)($cliente->fone ?? '')),
			'fone2' => trim((string)($cliente->fone2 ?? '')),
			'email' => trim((string)($cliente->email ?? '')),
			'membro_label' => $membroLabel,
			'anos_cliente' => $anosCliente,
			'inativo' => (int)$cliente->inativo === 1,
			'is_vip' => false,
			'kpis' => [
				'receita12' => 0.0,
				'receita12_fmt' => $this->_clientesFmtBrl(0),
				'receita12_pct' => null,
				'receita_total' => 0.0,
				'receita_total_fmt' => $this->_clientesFmtBrlCompact(0),
				'a_receber' => 0.0,
				'a_receber_fmt' => $this->_clientesFmtBrl(0),
				'parcelas_abertas' => 0,
				'a_receber_hint' => '',
				'ticket_medio' => 0.0,
				'ticket_medio_fmt' => $this->_clientesFmtBrl(0),
				'has_fin' => false,
			],
			'receita_mensal' => [],
			'saude' => [],
			'counts' => [
				'orcamentos' => 0,
				'os' => 0,
				'contratos' => 0,
				'tickets_abertos' => 0,
				'arquivos' => 0,
			],
			'timeline' => [],
			'orcamentos' => [],
			'os_list' => [],
			'financeiro' => [],
			'contratos' => [],
			'domain_events' => [],
			'domain_events_ready' => InfrastructureGuard::isReady(),
		];

		$hoje = FrozenDate::today();
		$ini12 = $hoje->subMonths(12);
		$iniPrev = $ini12->subMonths(12);
		$fimPrev = $ini12->subDay(1);

		try {
			$finTable = TableRegistry::getTableLocator()->get('FinanceiroLancamentos');
			$payload['kpis']['has_fin'] = true;
			$baseWhere = [
				'FinanceiroLancamentos.idempresa' => $idempresa,
				'FinanceiroLancamentos.idcliente' => $cid,
				'FinanceiroLancamentos.tipo' => 'receita',
			];

			$q12 = $finTable->find();
			$q12->select(['s' => $q12->func()->sum('FinanceiroLancamentos.valor')])
				->where($baseWhere + [
					'FinanceiroLancamentos.data_lancamento >=' => $ini12->format('Y-m-d'),
					'FinanceiroLancamentos.data_lancamento <=' => $hoje->format('Y-m-d'),
				]);
			$row12 = $q12->first();
			$receita12 = $row12 && $row12->s !== null ? (float)$row12->s : 0.0;

			$qPrev = $finTable->find();
			$qPrev->select(['s' => $qPrev->func()->sum('FinanceiroLancamentos.valor')])
				->where($baseWhere + [
					'FinanceiroLancamentos.data_lancamento >=' => $iniPrev->format('Y-m-d'),
					'FinanceiroLancamentos.data_lancamento <=' => $fimPrev->format('Y-m-d'),
				]);
			$rowPrev = $qPrev->first();
			$receitaPrev = $rowPrev && $rowPrev->s !== null ? (float)$rowPrev->s : 0.0;

			$qTot = $finTable->find();
			$qTot->select(['s' => $qTot->func()->sum('FinanceiroLancamentos.valor')])
				->where($baseWhere);
			$rowTot = $qTot->first();
			$receitaTotal = $rowTot && $rowTot->s !== null ? (float)$rowTot->s : 0.0;

			$qAb = $finTable->find();
			$qAb->select(['s' => $qAb->func()->sum('FinanceiroLancamentos.valor')])
				->where($baseWhere + ['FinanceiroLancamentos.status' => 'aberto']);
			$rowAb = $qAb->first();
			$aReceber = $rowAb && $rowAb->s !== null ? (float)$rowAb->s : 0.0;

			$parcelasAbertas = (int)$finTable->find()
				->where($baseWhere + ['FinanceiroLancamentos.status' => 'aberto'])
				->count();

			$atraso = false;
			$qInad = $finTable->find()
				->where($baseWhere + [
					'FinanceiroLancamentos.status' => 'aberto',
					'FinanceiroLancamentos.data_vencimento IS NOT' => null,
					'FinanceiroLancamentos.data_vencimento <' => $hoje->format('Y-m-d'),
				])
				->limit(1);
			if ($qInad->count() > 0) {
				$atraso = true;
			}

			$receitaPct = null;
			if ($receitaPrev > 0.0001) {
				$receitaPct = (int)round(100 * ($receita12 - $receitaPrev) / $receitaPrev);
			}

			$mesesPt = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
			$receitaMensal = [];
			for ($i = 11; $i >= 0; $i--) {
				$ref = $hoje->subMonths($i);
				$ym = $ref->format('Y-m');
				$qMes = $finTable->find();
				$qMes->select(['s' => $qMes->func()->sum('FinanceiroLancamentos.valor')])
					->where($baseWhere + [
						'FinanceiroLancamentos.data_lancamento >=' => $ym . '-01',
						'FinanceiroLancamentos.data_lancamento <=' => $ref->format('Y-m-t'),
					]);
				$rMes = $qMes->first();
				$valMes = $rMes && $rMes->s !== null ? (float)$rMes->s : 0.0;
				$receitaMensal[] = [
					'label' => $mesesPt[(int)$ref->format('n') - 1],
					'valor' => $valMes,
					'pct' => 0,
				];
			}
			$maxMes = 0.0;
			foreach ($receitaMensal as $rm) {
				if ($rm['valor'] > $maxMes) {
					$maxMes = $rm['valor'];
				}
			}
			if ($maxMes > 0) {
				foreach ($receitaMensal as $idx => $rm) {
					$receitaMensal[$idx]['pct'] = (int)round(100 * $rm['valor'] / $maxMes);
				}
			}
			$mediaMensal = $receita12 > 0 ? $receita12 / 12 : 0.0;
			$pico = ['valor' => 0.0, 'label' => '—'];
			foreach ($receitaMensal as $rm) {
				if ($rm['valor'] >= $pico['valor']) {
					$pico = ['valor' => $rm['valor'], 'label' => $rm['label']];
				}
			}
			$tendencia = __('Estável');
			if (count($receitaMensal) >= 6) {
				$ult3 = array_slice($receitaMensal, -3);
				$ant3 = array_slice($receitaMensal, -6, 3);
				$sUlt = array_sum(array_column($ult3, 'valor'));
				$sAnt = array_sum(array_column($ant3, 'valor'));
				if ($sAnt > 0.0001) {
					$delta = ($sUlt - $sAnt) / $sAnt;
					if ($delta > 0.08) {
						$tendencia = __('↑ Crescente');
					} elseif ($delta < -0.08) {
						$tendencia = __('↓ Em queda');
					}
				}
			}

			$ticketMedio = 0.0;

			$hintReceber = $parcelasAbertas > 0
				? __(
					'{0} parcela(s) · {1}',
					$parcelasAbertas,
					$atraso ? __('em atraso') : __('em dia')
				)
				: __('sem títulos em aberto');

			$payload['kpis'] = [
				'receita12' => $receita12,
				'receita12_fmt' => $this->_clientesFmtBrlCompact($receita12),
				'receita12_pct' => $receitaPct,
				'receita_total' => $receitaTotal,
				'receita_total_fmt' => $this->_clientesFmtBrlCompact($receitaTotal),
				'a_receber' => $aReceber,
				'a_receber_fmt' => $this->_clientesFmtBrl($aReceber),
				'parcelas_abertas' => $parcelasAbertas,
				'a_receber_hint' => $hintReceber,
				'em_atraso' => $atraso,
				'ticket_medio' => $ticketMedio,
				'ticket_medio_fmt' => $this->_clientesFmtBrl($ticketMedio),
				'has_fin' => true,
				'desde_hint' => $membroLabel !== '' ? __('desde {0}', $membroLabel) : '',
			];
			$payload['receita_mensal'] = $receitaMensal;
			$payload['receita_chart'] = [
				'media_fmt' => $this->_clientesFmtBrl($mediaMensal),
				'pico_fmt' => $this->_clientesFmtBrl($pico['valor']),
				'pico_label' => $pico['label'],
				'tendencia' => $tendencia,
			];

			$finRows = $finTable->find()
				->where($baseWhere)
				->order(['FinanceiroLancamentos.data_vencimento' => 'DESC', 'FinanceiroLancamentos.id' => 'DESC'])
				->limit(30)
				->all();
			foreach ($finRows as $fr) {
				$venc = $fr->get('data_vencimento');
				$vencStr = $venc instanceof \DateTimeInterface ? $venc->format('d/m/Y') : '—';
				$payload['financeiro'][] = [
					'id' => (int)$fr->get('id'),
					'descricao' => trim((string)($fr->get('descricao') ?? $fr->get('historico') ?? __('Lançamento'))),
					'valor_fmt' => $this->_clientesFmtBrl((float)($fr->get('valor') ?? 0)),
					'status' => (string)($fr->get('status') ?? ''),
					'vencimento' => $vencStr,
				];
				$stFin = strtolower((string)($fr->get('status') ?? ''));
				$isPago = $stFin !== '' && $stFin !== 'aberto';
				$payload['timeline'][] = [
					'kind' => 'financeiro',
					'icon' => 'fa-coins',
					'tone' => $isPago ? 'teal' : ($atraso ? 'orange' : 'blue'),
					'label' => ($isPago ? __('Pagamento recebido') : __('Título em aberto')) . ' · ' . $this->_clientesFmtBrl((float)($fr->get('valor') ?? 0)),
					'sub' => $vencStr . ' · ' . (string)($fr->get('descricao') ?? ''),
					'data' => $venc instanceof \DateTimeInterface ? $venc : ($fr->get('data_lancamento') instanceof \DateTimeInterface ? $fr->get('data_lancamento') : null),
					'url' => null,
				];
			}
		} catch (\Throwable $e) {
			$this->log('Clientes::visao360 financeiro: ' . $e->getMessage(), 'warning');
		}

		try {
			$this->loadModel('Orcamentos');
			$payload['counts']['orcamentos'] = (int)$this->Orcamentos->find()
				->where(['Orcamentos.idempresa' => $idempresa, 'Orcamentos.idcliente' => $cid])
				->count();
			foreach ($this->Orcamentos->find()
				->where(['Orcamentos.idempresa' => $idempresa, 'Orcamentos.idcliente' => $cid])
				->order(['Orcamentos.id' => 'DESC'])
				->limit(12)
				->all() as $orc) {
				$oid = (int)$orc->get('id');
				$payload['orcamentos'][] = [
					'id' => $oid,
					'label' => __('Orçamento #{0}', $oid),
					'status' => (string)($orc->get('status') ?? ''),
					'data' => $orc->get('created'),
					'url' => ['controller' => 'Orcamentos', 'action' => 'edit', $oid],
				];
				$payload['timeline'][] = [
					'kind' => 'orcamento',
					'icon' => 'fa-file-invoice',
					'tone' => 'purple',
					'label' => __('Orçamento #{0}', $oid),
					'sub' => (string)($orc->get('status') ?? ''),
					'data' => $orc->get('created'),
					'url' => ['controller' => 'Orcamentos', 'action' => 'edit', $oid],
				];
			}
		} catch (\Throwable $e) {
		}

		try {
			$this->loadModel('Ordensservico');
			$wOs = ['Ordensservico.idempresa' => $idempresa, 'Ordensservico.idcliente' => $cid];
			$payload['counts']['os'] = (int)$this->Ordensservico->find()->where($wOs)->count();
			foreach ($this->Ordensservico->find()->where($wOs)->order(['Ordensservico.id' => 'DESC'])->limit(12)->all() as $os) {
				$oid = (int)$os->get('id');
				$rel = \Cake\Utility\Text::truncate((string)($os->get('relato') ?? $os->get('descricao') ?? ''), 80, ['ellipsis' => '…']);
				$payload['os_list'][] = [
					'id' => $oid,
					'label' => sprintf('OS-%05d', $oid),
					'sub' => $rel,
					'situacao' => (string)($os->get('situacao') ?? ''),
					'data' => $os->get('dataabertura') ?? $os->get('created'),
					'url' => ['controller' => 'Ordensservico', 'action' => 'view', $oid],
				];
				$payload['timeline'][] = [
					'kind' => 'os',
					'icon' => 'fa-wrench',
					'tone' => 'blue',
					'label' => sprintf('OS-%05d', $oid) . ($rel !== '' ? ' · ' . $rel : ''),
					'sub' => (string)($os->get('situacao') ?? ''),
					'data' => $os->get('dataabertura') ?? $os->get('created'),
					'url' => ['controller' => 'Ordensservico', 'action' => 'view', $oid],
				];
			}
		} catch (\Throwable $e) {
		}

		try {
			$payload['counts']['contratos'] = (int)$this->Clicontratos->find()
				->where(['Clicontratos.idcliente' => $cid])
				->count();
			foreach ($this->Clicontratos->find()
				->where(['Clicontratos.idcliente' => $cid])
				->order(['Clicontratos.id' => 'DESC'])
				->limit(12)
				->all() as $ct) {
				$payload['contratos'][] = [
					'id' => (int)$ct->get('id'),
					'label' => trim((string)($ct->get('descricao') ?? $ct->get('servico') ?? __('Contrato #{0}', (int)$ct->get('id')))),
					'validade' => $this->_clicontratoValidadeYmd($ct),
					'url' => ['controller' => 'Clientes', 'action' => 'edit', $cid, '#' => 'contratos'],
				];
			}
			if ($payload['kpis']['ticket_medio'] <= 0 && $payload['counts']['contratos'] > 0 && $payload['kpis']['receita_total'] > 0) {
				$payload['kpis']['ticket_medio'] = $payload['kpis']['receita_total'] / $payload['counts']['contratos'];
				$payload['kpis']['ticket_medio_fmt'] = $this->_clientesFmtBrl($payload['kpis']['ticket_medio']);
			}
		} catch (\Throwable $e) {
		}

		try {
			$tickets = $this->loadModel('Tickets');
			$closed = [];
			if (defined('C_TicketSituacaoFechado')) {
				$closed[] = (int)C_TicketSituacaoFechado;
			}
			if (defined('C_TicketSituacaoResolvido')) {
				$closed[] = (int)C_TicketSituacaoResolvido;
			}
			$wT = ['Tickets.idempresa' => $idempresa, 'Tickets.idcliente' => $cid];
			$wAb = $wT;
			if ($closed !== []) {
				$wAb['Tickets.situacao NOT IN'] = $closed;
			}
			$payload['counts']['tickets_abertos'] = (int)$tickets->find()->where($wAb)->count();
			foreach ($tickets->find()->where($wT)->order(['Tickets.created' => 'DESC'])->limit(8)->all() as $t) {
				$tid = (int)$t->get('id');
				$payload['timeline'][] = [
					'kind' => 'ticket',
					'icon' => 'fa-headset',
					'tone' => 'indigo',
					'label' => '#' . $tid . ' · ' . \Cake\Utility\Text::truncate((string)$t->get('solicitacao'), 60, ['ellipsis' => '…']),
					'sub' => (string)$t->get('situacao'),
					'data' => $t->get('created'),
					'url' => ['controller' => 'Tickets', 'action' => 'view', $tid],
				];
			}
		} catch (\Throwable $e) {
		}

		try {
			$faturas = $this->loadModel('Faturas');
			$wF = ['Faturas.idempresa' => $idempresa, 'Faturas.idcliente' => $cid];
			foreach ($faturas->find()->where($wF)->order(['Faturas.vencimento' => 'DESC'])->limit(8)->all() as $f) {
				$fid = (int)$f->get('id');
				$v = (float)($f->get('valor') ?? 0);
				$venc = $f->get('vencimento');
				$payload['timeline'][] = [
					'kind' => 'fatura',
					'icon' => 'fa-file-invoice-dollar',
					'tone' => 'teal',
					'label' => __('Fatura {0}', (string)($f->get('nro') ?? '#' . $fid)) . ' · ' . $this->_clientesFmtBrl($v),
					'sub' => $venc instanceof \DateTimeInterface ? $venc->format('d/m/Y') : '',
					'data' => $venc,
					'url' => ['controller' => 'Faturas', 'action' => 'view', $fid],
				];
			}
		} catch (\Throwable $e) {
		}

		if ($payload['domain_events_ready']) {
			try {
				$payload['domain_events'] = TableRegistry::get('ClientDomainEvents')
					->find()
					->where(['idcliente' => $cid])
					->order(['created' => 'DESC'])
					->limit(200)
					->toArray();
				foreach ($payload['domain_events'] as $ev) {
					$payload['timeline'][] = [
						'kind' => 'evento',
						'icon' => 'fa-history',
						'tone' => 'gray',
						'label' => (string)($ev->event_type ?? __('Evento')),
						'sub' => \Cake\Utility\Text::truncate((string)($ev->description ?? ''), 120, ['ellipsis' => '…']),
						'data' => $ev->created,
						'url' => null,
					];
				}
			} catch (\Throwable $e) {
				$payload['domain_events'] = [];
			}
		}

		usort($payload['timeline'], static function ($a, $b) {
			$ta = $a['data'] instanceof \DateTimeInterface ? $a['data']->getTimestamp() : 0;
			$tb = $b['data'] instanceof \DateTimeInterface ? $b['data']->getTimestamp() : 0;

			return $tb <=> $ta;
		});
		$payload['timeline'] = array_slice($payload['timeline'], 0, 40);
		$payload['timeline_preview'] = array_slice($payload['timeline'], 0, 8);

		$interacoes30 = 0;
		$cut = (new \DateTimeImmutable('today'))->modify('-30 days')->getTimestamp();
		foreach ($payload['timeline'] as $tl) {
			if ($tl['data'] instanceof \DateTimeInterface && $tl['data']->getTimestamp() >= $cut) {
				$interacoes30++;
			}
		}
		$engLabel = $interacoes30 >= 5 ? __('Alto') : ($interacoes30 >= 2 ? __('Médio') : __('Baixo'));
		$engPct = min(100, $interacoes30 * 15);
		$payload['saude'] = [
			[
				'label' => __('Engajamento (30 dias)'),
				'valor' => $engLabel,
				'pct' => $engPct,
				'hint' => __('{0} interações', $interacoes30),
			],
		];
		if ($payload['kpis']['has_fin'] && $payload['kpis']['parcelas_abertas'] > 0) {
			$pontPct = !empty($payload['kpis']['em_atraso']) ? 40 : 95;
			$payload['saude'][] = [
				'label' => __('Situação financeira'),
				'valor' => $payload['kpis']['a_receber_hint'],
				'pct' => $pontPct,
				'hint' => $payload['kpis']['a_receber_fmt'],
			];
		}
		if (!empty($payload['receita_chart']['tendencia'])) {
			$payload['saude'][] = [
				'label' => __('Tendência de receita'),
				'valor' => (string)$payload['receita_chart']['tendencia'],
				'pct' => strpos((string)$payload['receita_chart']['tendencia'], 'Crescente') !== false ? 85 : 50,
				'hint' => (string)$payload['receita_chart']['media_fmt'] . ' ' . __('média/mês'),
			];
		}

		$crmOne = $this->_clientesIndexCrmMetrics([$cliente], (int)$cliente->inativo === 0 ? 1 : 0);
		$payload['is_vip'] = !empty($crmOne['vip_ids'][$cid]);
		$payload['contatos'] = $this->_clientesVisao360Contatos($cliente);
		$payload['counts']['arquivos'] = $this->_clientesContarArquivosCliente($cid, $idempresa);

		return $payload;
	}

	/**
	 * Contatos exibidos na Visão 360° (cadastro + usuários do portal; sem dados fictícios).
	 *
	 * @param \App\Model\Entity\Cliente $cliente
	 * @return array<int,array<string,mixed>>
	 */
	protected function _clientesVisao360Contatos($cliente) {
		$cid = (int)$cliente->id;
		$isPj = (int)$cliente->tipo === (int)C_ClientesTipoJuridica;
		$avTones = ['teal', 'blue', 'rose', 'orange', 'purple', 'navy'];
		$contatos = [];
		$seen = [];
		$push = function ($nome, $cargo, $email, $fone) use (&$contatos, &$seen, $avTones) {
			$nome = trim((string)$nome);
			$email = trim((string)$email);
			$fone = trim((string)$fone);
			if ($nome === '' && $email === '' && $fone === '') {
				return;
			}
			if ($nome === '') {
				$nome = $email !== '' ? $email : ($fone !== '' ? $fone : __('Contato'));
			}
			$key = mb_strtolower($nome . '|' . $email, 'UTF-8');
			if (isset($seen[$key])) {
				return;
			}
			$seen[$key] = true;
			$parts = preg_split('/\s+/', $nome, -1, PREG_SPLIT_NO_EMPTY);
			$ini = strtoupper(substr($parts[0] ?? 'C', 0, 1)) . strtoupper(substr($parts[1] ?? '', 0, 1));
			$contatos[] = [
				'nome' => $nome,
				'cargo' => trim((string)$cargo),
				'email' => $email,
				'fone' => $fone,
				'iniciais' => $ini !== '' ? $ini : 'C',
				'av_tone' => $avTones[count($contatos) % count($avTones)],
			];
		};

		if ($isPj) {
			$resp = trim((string)($cliente->nomeresponsavel ?? ''));
			if ($resp !== '') {
				$push($resp, __('Representante legal'), '', (string)($cliente->fone2 ?? $cliente->fone ?? ''));
			}
		} else {
			$push((string)($cliente->nome ?? ''), __('Titular'), (string)($cliente->email ?? ''), (string)($cliente->fone ?? ''));
		}

		$emailsContato = trim((string)($cliente->emailresponsavel ?? ''));
		if ($emailsContato !== '') {
			foreach (preg_split('/[;,]+/', $emailsContato) as $em) {
				$em = trim($em);
				if ($em !== '') {
					$push($em, __('Contato operacional'), $em, '');
				}
			}
		}
		$emailFat = trim((string)($cliente->email ?? ''));
		if ($emailFat !== '') {
			foreach (preg_split('/[;,]+/', $emailFat) as $em) {
				$em = trim($em);
				if ($em !== '') {
					$push($em, __('Faturamento'), $em, '');
				}
			}
		}
		if ((string)($cliente->fone ?? '') !== '' && count($contatos) > 0) {
			if ($contatos[0]['fone'] === '') {
				$contatos[0]['fone'] = (string)$cliente->fone;
			}
		}

		try {
			$users = $this->Users->find()
				->select(['id', 'name', 'email', 'username'])
				->where(['Users.idcliente' => $cid])
				->order(['Users.name' => 'ASC'])
				->limit(20)
				->all();
			foreach ($users as $u) {
				$push(
					(string)($u->name ?? $u->username ?? ''),
					__('Usuário portal'),
					(string)($u->email ?? ''),
					''
				);
			}
		} catch (\Throwable $e) {
		}

		return $contatos;
	}

	/**
	 * Total de anexos vinculados ao cliente (tickets + financeiro).
	 */
	protected function _clientesContarArquivosCliente(int $idcliente, int $idempresa): int {
		$total = 0;
		try {
			$this->loadModel('Ticketsanexos');
			$total += (int)$this->Ticketsanexos->find()
				->innerJoinWith('Tickets', function ($q) use ($idcliente, $idempresa) {
					return $q->where([
						'Tickets.idcliente' => $idcliente,
						'Tickets.idempresa' => $idempresa,
					]);
				})
				->count();
		} catch (\Throwable $e) {
		}
		try {
			$anexos = TableRegistry::getTableLocator()->get('FinanceiroLancamentoAnexos');
			$total += (int)$anexos->find()
				->innerJoinWith('FinanceiroLancamentos', function ($q) use ($idcliente, $idempresa) {
					return $q->where([
						'FinanceiroLancamentos.idcliente' => $idcliente,
						'FinanceiroLancamentos.idempresa' => $idempresa,
					]);
				})
				->count();
		} catch (\Throwable $e) {
		}

		return $total;
	}

	public function cidadesestado($idcidade){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');

		$cidade = $this->Cidades->find('all')->where(['id' => $idcidade])->first();
		if (empty($cidade) || empty($cidade->idestado)) {
			return;
		}
		$estado = $this->Estados->find()->where(['id' => $cidade->idestado])->first();
		if (!empty($estado)) {
			echo h($estado->sigla);
		}
	}

	public function delete($id = null) {
		// Permissão para o cliente
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$cliente = $this->_findClienteForCurrentUser($id);
		if (empty($cliente)) {
			$this->Flash->error('Você não possui permissão para excluir este cliente ou registro não encontrado.');
			return $this->redirect(['action' => 'index']);
		}

		if ($this->Clientes->delete($cliente)) {
			$this->Flash->success('O cliente foi deletado com sucesso!');
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
			return $this->redirect(['action' => 'index']);
		}
	}

	public function reativar($id = null) {
		// Permissão para o cliente
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$cliente = $this->_findClienteForCurrentUser($id);
		if (empty($cliente)) {
			$this->Flash->error('Cliente não encontrado ou sem permissão.');
			return $this->redirect(['action' => 'index']);
		}
		$cliente->inativo = 0;

		if ($this->Clientes->save($cliente)) {
			$this->sincronizacliente($id);
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
			$nomeCli = $cliente->tipo == C_ClientesTipoFisica ? ($cliente->nome ?? '') : ($cliente->razaosocial ?? '');
			ClienteDomainBridge::emit(ClienteDomainEventType::CLIENTE_ATIVADO, [
				'idcliente' => (int)$id,
				'idempresa' => (int)$this->Auth->user('idempresa'),
				'actor_user_id' => (int)$this->Auth->user('id'),
				'title' => __('Cliente reativado'),
				'message' => __('Cliente ativo novamente: {0}', $nomeCli),
				'action_url' => Router::url(['controller' => 'Clientes', 'action' => 'edit', $id]),
				'entity_type' => 'Cliente',
				'entity_id' => $id,
			]);
			$this->Flash->success('O cliente foi reativado com sucesso!');
		} else {
			$this->Flash->error('Não foi possível reativar o cliente.');
		}

		return $this->redirect(['action' => 'index', '#' => 'inativos']);
	}

	public function inativar($id = null) {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$cliente = $this->_findClienteForCurrentUser($id);
		if (empty($cliente)) {
			$this->Flash->error('Cliente não encontrado ou sem permissão.');
			return $this->redirect(['action' => 'index']);
		}
		if ((int)$cliente->inativo === 1) {
			$this->Flash->warning('Este cliente já está inativo.');
			return $this->redirect(['action' => 'index', '#' => 'inativos']);
		}
		$cliente->inativo = 1;

		if ($this->Clientes->save($cliente)) {
			$this->sincronizacliente($id);
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
			$nomeCli = $cliente->tipo == C_ClientesTipoFisica ? ($cliente->nome ?? '') : ($cliente->razaosocial ?? '');
			ClienteDomainBridge::emit(ClienteDomainEventType::CLIENTE_INATIVADO, [
				'idcliente' => (int)$id,
				'idempresa' => (int)$this->Auth->user('idempresa'),
				'actor_user_id' => (int)$this->Auth->user('id'),
				'title' => __('Cliente inativado'),
				'message' => __('Cliente inativado: {0}', $nomeCli),
				'action_url' => Router::url(['controller' => 'Clientes', 'action' => 'edit', $id]),
				'entity_type' => 'Cliente',
				'entity_id' => $id,
			]);
			$this->Flash->success('O cliente foi inativado com sucesso!');
		} else {
			$this->Flash->error('Não foi possível inativar o cliente.');
		}

		return $this->redirect(['action' => 'index', '#' => 'inativos']);
	}

	public function solicitantes($idcliente) {
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');

		if (!$this->Auth->user()) {
			return $this->jsonResponse([], 401);
		}
		if ((int)$this->Auth->user('role') !== 0) {
			return $this->jsonResponse([], 403);
		}
		$cid = (int)$idcliente;
		if ($cid <= 0) {
			return $this->jsonResponse([], 400);
		}
		$qCli = $this->Clientes->find()->where(['id' => $cid]);
		$this->Abac->applyToQuery($qCli, 'Clientes');
		$cli = $qCli->first();
		if (empty($cli)) {
			return $this->jsonResponse([], 404);
		}

		$solicitantes = $this->Users->find('list', ['keyField' => 'id', 'valueField' => 'name'])
			->order(['name'])
			->where(['idcliente' => $cid, 'inativo' => 0])
			->toArray();

		return $this->jsonResponse($solicitantes, 200);
	}

	public function solicitante($idsolicitante) {
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');

		if ($this->request->is('ajax')) {
			$user = $this->Users->find('list', ['keyField' => 'id', 'valueField' => 'name'])->where(['id' => $idsolicitante, 'inativo' => '0'])->toArray();
			return $this->jsonResponse($user, 200);
		}
	}

	public function cliemail($idcliente) {
		$this->autoRender = false;

		if (!$this->Auth->user()) {
			return $this->jsonResponse(['email' => ''], 401);
		}
		if ((int)$this->Auth->user('role') !== 0) {
			return $this->jsonResponse(['email' => ''], 403);
		}
		$cid = (int)$idcliente;
		if ($cid <= 0) {
			return $this->jsonResponse(['email' => ''], 400);
		}
		$qMail = $this->Clientes->find('all')->where([
			'id' => $cid,
			'inativo' => '0',
		]);
		$this->Abac->applyToQuery($qMail, 'Clientes');
		$cliente = $qMail->first();
		if (empty($cliente)) {
			return $this->jsonResponse(['email' => ''], 404);
		}

		return $this->jsonResponse([
			'email' => (string)($cliente->get('email') ?? ''),
		], 200);
	}

	public function solemail($idsolicitante) {
		$this->autoRender = false;

		if ($this->request->is('ajax')) {
			$contato = $this->Users->find('all')->where(['id' => $idsolicitante, 'inativo' => 0])->first();
			return $this->jsonResponse($contato, 200);
		}
	}

	public function clientebyid($idsclientes){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');
		
		$idsclientes = explode(',', $idsclientes);
		$clientes = [];
		$idempresa = $this->Auth->user('idempresa');

		if ($this->request->is('ajax') && $idempresa !== null && $idempresa !== '') {
			foreach ($idsclientes as $id) {
				$id = trim((string) $id);
				if ($id === '') {
					continue;
				}
				$qRow = $this->Clientes->find()
					->select(['id', 'razaosocial'])
					->where(['id' => (int) $id]);
				$this->Abac->applyToQuery($qRow, 'Clientes');
				$rows = $qRow->toArray();
				foreach ($rows as $row) {
					$clientes[] = $row;
				}
			}
			return $this->jsonResponse($clientes, 200);
		}
		return $this->jsonResponse([], 400);
	}

	public function consultacnpj($cnpj = null) {
		$this->autoRender = false;

		if (!$this->request->is('ajax')) {
			return $this->jsonResponse(['status' => 'ERROR', 'message' => 'Requisição inválida'], 400);
		}

		$cnpj = preg_replace('/\D+/', '', (string)($cnpj ?? ''));
		if (strlen($cnpj) !== 14) {
			return $this->jsonResponse(['status' => 'ERROR', 'message' => 'CNPJ inválido'], 400);
		}

		$url = 'https://www.receitaws.com.br/v1/cnpj/' . $cnpj;

		$context = stream_context_create([
			'http' => [
				'method' => 'GET',
				'timeout' => 15,
				'header' => [
					'Accept: application/json',
				],
			],
		]);

		$result = @file_get_contents($url, false, $context);
		if ($result === false) {
			return $this->jsonResponse(['status' => 'ERROR', 'message' => 'Falha ao acessar serviço de CNPJ'], 502);
		}

		$data = json_decode($result, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return $this->jsonResponse(['status' => 'ERROR', 'message' => 'Retorno inválido do serviço de CNPJ'], 502);
		}

		// Tenta localizar a cidade (idcidade) a partir do município + UF (comparação sem acentos)
		if (!empty($data['municipio'])) {
			$municipioNorm = $this->normalizaTextoParaBusca($data['municipio']);
			$uf = !empty($data['uf']) ? strtoupper(trim($data['uf'])) : null;

			$query = $this->Cidades->find('all');
			if ($uf) {
				$estado = $this->Estados->find()->where(['sigla' => $uf])->first();
				if ($estado) {
					$query->where(['idestado' => $estado->id]);
				}
			}
			$cidadesList = $query->toArray();

			foreach ($cidadesList as $c) {
				if ($this->normalizaTextoParaBusca($c->nome) === $municipioNorm) {
					$data['idcidade'] = $c->id;
					break;
				}
			}
		}

		return $this->jsonResponse($data, 200);
	}

	/**
	 * Consulta Inscrição Estadual (IE) na SEFAZ/SINTEGRA via API SintegraPI.
	 * Requer chave em SINTEGRA_API_KEY (env) ou Configure Sintegra.apiKey.
	 * Parâmetros: cnpj (obrigatório), uf (opcional; ex: RS, SP).
	 */
	public function consultaIe($cnpj = null, $uf = null) {
		$this->autoRender = false;

		if (!$this->request->is('ajax')) {
			return $this->jsonResponse(['success' => false, 'message' => 'Requisição inválida'], 400);
		}

		$cnpj = preg_replace('/\D+/', '', (string)($cnpj ?? ''));
		if (strlen($cnpj) !== 14) {
			return $this->jsonResponse(['success' => false, 'message' => 'CNPJ inválido'], 400);
		}

		$apiKey = env('SINTEGRA_API_KEY', \Cake\Core\Configure::read('Sintegra.apiKey'));
		if (empty($apiKey)) {
			return $this->jsonResponse([
				'success' => false,
				'message' => 'Consulta de IE não configurada. Defina SINTEGRA_API_KEY no ambiente ou Sintegra.apiKey na configuração.',
				'ie' => null,
			], 200);
		}

		$uf = $uf ? strtoupper(trim($uf)) : null;
		$url = 'https://api.sintegrapi.com.br/consultas/v2/sintegra/' . $cnpj;
		if ($uf) {
			$url .= '?uf=' . rawurlencode($uf);
		}

		$context = stream_context_create([
			'http' => [
				'method' => 'GET',
				'timeout' => 15,
				'header' => [
					'Accept: application/json',
					'x-api-key: ' . $apiKey,
					'cache: 25',
				],
			],
		]);

		$result = @file_get_contents($url, false, $context);
		if ($result === false) {
			return $this->jsonResponse(['success' => false, 'message' => 'Falha ao acessar serviço de IE (SEFAZ/SINTEGRA).', 'ie' => null], 502);
		}

		$data = json_decode($result, true);
		if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
			return $this->jsonResponse(['success' => false, 'message' => 'Retorno inválido do serviço de IE.', 'ie' => null], 502);
		}

		if (!empty($data['error']) || empty($data['success'])) {
			return $this->jsonResponse([
				'success' => false,
				'message' => isset($data['message']) ? $data['message'] : 'IE não encontrada ou indisponível.',
				'ie' => null,
			], 200);
		}

		$ie = null;
		$situacao = null;
		$inscricoes = $data['inscricoes_estaduais'] ?? [];
		if ($uf) {
			foreach ($inscricoes as $item) {
				if (isset($item['uf']) && strtoupper($item['uf']) === $uf && !empty($item['inscricao_estadual'])) {
					$ie = preg_replace('/\D+/', '', $item['inscricao_estadual']);
					$situacao = $item['situacao_pj'] ?? ($item['ativa'] ? 'Ativa' : 'Inativa');
					break;
				}
			}
		}
		if ($ie === null && !empty($inscricoes)) {
			$first = $inscricoes[0];
			$ie = preg_replace('/\D+/', '', $first['inscricao_estadual'] ?? '');
			$situacao = $first['situacao_pj'] ?? null;
		}

		return $this->jsonResponse([
			'success' => true,
			'ie' => $ie,
			'situacao' => $situacao,
			'uf' => $uf,
		], 200);
	}

	/**
	 * Normaliza texto para busca (maiúsculas, sem acentos) para comparar nomes de cidade.
	 */
	private function normalizaTextoParaBusca($texto) {
		$t = mb_strtoupper(trim((string)$texto), 'UTF-8');
		$map = ['Á'=>'A','À'=>'A','Ã'=>'A','Â'=>'A','Ä'=>'A','Ç'=>'C','É'=>'E','È'=>'E','Ê'=>'E','Ë'=>'E','Í'=>'I','Ì'=>'I','Î'=>'I','Ï'=>'I','Ó'=>'O','Ò'=>'O','Õ'=>'O','Ô'=>'O','Ö'=>'O','Ú'=>'U','Ù'=>'U','Û'=>'U','Ü'=>'U'];
		return strtr($t, $map);
	}

	public function updateToken($idcliente) {
		$this->autoRender = false;

		$cliente = $this->_findClienteForCurrentUser($idcliente);
		if (empty($cliente)) {
			$this->Flash->error(__('Cliente não encontrado.'));
			return $this->redirect(['action' => 'index']);
		}
		$uidTok = (int)$this->Auth->user('id');
		$apiTokGate = RbacChecker::resourceFieldAccess($uidTok, 'Clientes.field.api_token');
		if ($apiTokGate !== null && (empty($apiTokGate['visible']) || empty($apiTokGate['editable']))) {
			$this->Flash->error(__('Sem permissão para renovar o token.'));
			return $this->redirect(['action' => 'edit', $idcliente]);
		}
		// Gera o token
		$cpfoucnpj = isset($cliente->cnpj) ? $cliente->cnpj : $cliente->cpf;
		$string = $this->Auth->user('idempresa') . $cpfoucnpj .  date('d/m/y') .  date('H:i');
		// Atualiza o token
		$cliente->token = $this->Clientes->generateToken($string);
		if($this->Clientes->save($cliente)){
			ClienteDomainBridge::emit(ClienteDomainEventType::TOKEN_GERADO, [
				'idcliente' => (int)$idcliente,
				'idempresa' => (int)$this->Auth->user('idempresa'),
				'actor_user_id' => (int)$this->Auth->user('id'),
				'title' => __('Token do cliente renovado'),
				'message' => __('Foi gerado um novo token de integração para este cliente.'),
				'action_url' => Router::url(['controller' => 'Clientes', 'action' => 'edit', $idcliente]),
				'entity_type' => 'Cliente',
				'entity_id' => $idcliente,
			]);
			$this->Flash->success(__('O token foi atualizado com sucesso.'));
			return $this->redirect(['action' => 'edit', $idcliente]);
		}
	}

	public function contrato($id){
		$this->autoRender = false;

		$cliente = $this->_findClienteForCurrentUser($id);
		if (empty($cliente)) {
			echo 'Você não possui permissão para visualizar este contrato.';
			return;
		}

		// Clientes só podem ver o próprio contrato (quando vinculados)
		if ($this->Auth->user('role') == C_RoleCliente && $this->Auth->user('idcliente') != $id) {
			echo 'Você não possui permissão para visualizar este contrato.';
			return;
		}

		$this->response = $this->response->withType('text/html; charset=UTF-8');
		echo h((string) $cliente->contrato);
	}

	public function addAPI() {
		$this->autoRender = false;

		$apiRet = function ($msg, $status = 200) {
			return $this->jsonResponse(['mensagem' => $msg, 'retorno' => $msg], $status);
		};

		if (!$this->request->is('post')) {
			return $apiRet('Método não permitido. Use POST com JSON em /clientes/addAPI.', 405);
		}

		try {
			list($empresa, $token, $erpCredErr) = ErpIntegrationRequest::readEmpresaAndToken(
				$this->request,
			);
			if ($erpCredErr !== null) {
				return $apiRet($erpCredErr, 400);
			}
			$json = $this->request->getData();
			if (empty($json) || !is_array($json)) {
				$raw = $this->request->input('json_decode');
				$json = is_string($raw) ? json_decode($raw) : $raw;
			} else {
				$json = (object)$json;
			}

			if (empty($token)) {
				return $apiRet('O token não foi informado', 400);
			}
			if (empty($empresa)) {
				return $apiRet('O ID da empresa não foi informado', 400);
			}
			if ($json === null || !is_object($json)) {
				return $apiRet('O JSON não foi informado ou é inválido.', 400);
			}
			if (!isset($json->cnpj) || trim((string) $json->cnpj) === '') {
				return $apiRet('JSON inválido: o campo cnpj é obrigatório.', 400);
			}
			if (empty($this->Empresas->findById($empresa)->first())) {
				return $apiRet("Não foi encontrada uma empresa com o ID ($empresa) informado", 400);
			}

			if ($token != $this->Empresas->get($empresa)->token) {
				return $apiRet('Autenticação Inválida', 401);
			}

			$retorno['CNPJ'] = \removeCaracteres($json->cnpj);
			$retorno['Empresa'] = $empresa;
			$tipo = strlen($retorno['CNPJ']) > 11 ? 'j' : 'f';

			if ($tipo == 'j') {
				$cliente = $this->Clientes->findByCnpj($retorno['CNPJ'])->where(['idempresa' => $retorno['Empresa']])->first();
			} else {
				$cliente = $this->Clientes->findByCpf($retorno['CNPJ'])->where(['idempresa' => $retorno['Empresa'], 'tipo' => C_ClientesTipoFisica])->first();
			}

			if ($cliente == null) {
				$cliente = $this->Clientes->newEntity();
				$string = $empresa . $retorno['CNPJ'] . date('d/m/y') . date('H:i');
				$cliente->token = $this->Clientes->generateToken($string);
			}

			$nomeIn = strtoupper(trim((string)($json->nome ?? '')));
			if ($tipo == 'j') {
				$cliente->razaosocial = $nomeIn;
				$cliente->nome = ' ';
				$cliente->cnpj = $retorno['CNPJ'];
				$cliente->tipo = C_ClientesTipoJuridica;
			} else {
				$cliente->nome = $nomeIn !== '' ? $nomeIn : ' ';
				$cliente->cpf = $retorno['CNPJ'];
				$cliente->tipo = C_ClientesTipoFisica;
			}
			$cliente->inscricaoestadual = $json->inscest ?? null;
			$cliente->membrodesde = date('Y-m-d');
			$cliente->idempresa = $empresa;
			$cliente->endereco = $json->endereco ?? null;
			$cliente->nroendereco = $json->nroendereco ?? null;
			$cliente->complemento = $json->complemento ?? null;
			$cliente->bairro = $json->bairro ?? null;
			$cliente->cep = isset($json->cep) ? \removeCaracteres((string)$json->cep) : null;
			if (isset($json->telefone)) {
				$cliente->fone = \removeCaracteres((string)$json->telefone);
			}
			if (isset($json->celular)) {
				$cliente->fone2 = \removeCaracteres((string)$json->celular);
			}
			$cliente->email = $json->email ?? null;
			$cliente->contrato = $json->contrato ?? null;
			$cliente->nomefantasia = $json->fantasia ?? null;
			$cliente->inativo = 0;

			$codibge = isset($json->codibge) ? trim((string)$json->codibge) : '';
			if ($codibge === '') {
				return $apiRet('JSON inválido: informe codibge (código IBGE do município).', 400);
			}
			$cidade = $this->Cidades->findByCodibge($codibge)->first();
			if ($cidade === null) {
				return $apiRet("Município não encontrado no portal para codibge={$codibge}. Cadastre a cidade ou corrija o IBGE.", 400);
			}
			$cliente->idcidade = $cidade->id;
			$cliente->empresadominante = (int)$empresa;

			$extPublic = null;
			if (isset($json->public_code)) {
				$extPublic = ClientesTable::normalizeIntegrationPublicCode($json->public_code);
			} elseif (isset($json->codigo_publico)) {
				$extPublic = ClientesTable::normalizeIntegrationPublicCode($json->codigo_publico);
			}
			if ($extPublic === false) {
				return $apiRet('JSON inválido: public_code/codigo_publico com formato inválido (use até 32 caracteres: letras, números, ponto, hífen e sublinhado).', 400);
			}
			if ($extPublic !== null) {
				$cliente->accessible('public_code', true);
				$cliente->set('public_code', $extPublic);
			}

			try {
				$saved = $this->Clientes->save($cliente);
			} catch (\Throwable $e) {
				$pdo = $e instanceof \PDOException ? $e : $e->getPrevious();
				$msg = $pdo instanceof \PDOException ? $pdo->getMessage() : $e->getMessage();
				if ($pdo instanceof \PDOException
					&& (strpos($msg, '23505') !== false
						|| stripos($msg, 'unique') !== false
						|| stripos($msg, 'uq_clientes_idempresa_public_code') !== false)) {
					return $this->jsonResponse([
						'mensagem' => 'Código de cliente já cadastrado para esta empresa.',
						'retorno' => 'Código de cliente já cadastrado para esta empresa.',
					], 409);
				}
				throw $e;
			}
			if (!$saved) {
				$errors = $cliente->getErrors();
				if (!empty($errors['public_code'])) {
					return $this->jsonResponse([
						'mensagem' => 'Código de cliente já cadastrado para esta empresa.',
						'retorno' => 'Código de cliente já cadastrado para esta empresa.',
					], 409);
				}
				$err = json_encode($errors, JSON_UNESCAPED_UNICODE);

				return $apiRet('Erro ao salvar cliente no portal: ' . $err, 400);
			}

			$deuerro = 'não';
			$contratos = $this->Clicontratos->find('all')->where(['idempresa' => $empresa, 'idcliente' => $cliente->id])->toArray();
			foreach ($contratos as $reg) {
				$this->Clicontratos->delete($reg);
			}

			$servicosList = [];
			if (isset($json->Servicos)) {
				if (is_array($json->Servicos)) {
					$servicosList = $json->Servicos;
				} elseif (is_object($json->Servicos)) {
					$servicosList = [$json->Servicos];
				}
			}
			foreach ($servicosList as $servico) {
				$servico = is_array($servico) ? (object)$servico : $servico;
				$contrato = $this->Clicontratos->newEntity();
				$contrato->iderp = $servico->idERP ?? null;
				$contrato->codproduto = $servico->codproduto ?? null;
				$contrato->descricao = $servico->descricao ?? null;
				$contrato->infadicional = $servico->infadicional ?? null;
				$contrato->vlunit = $servico->vlunit ?? null;
				$contrato->qtde = $servico->qtde ?? null;
				$contrato->vltotal = $servico->vltotal ?? null;
				if (!empty($servico->dtcontratacao)) {
					$contrato->dtcontratacao = $servico->dtcontratacao;
				}
				if (!empty($servico->dtvalidade)) {
					$contrato->dtvalidade = $servico->dtvalidade;
				}
				if (!empty($servico->dtcancelamento)) {
					$contrato->dtcancelamento = $servico->dtcancelamento;
				}
				$contrato->idcliente = $cliente->id;
				$contrato->idempresa = $empresa;
				if ($this->Clicontratos->save($contrato)) {
					$contratos[] = $contrato;
				} else {
					$deuerro = 'sim';
				}
			}

			if ($deuerro == 'não') {
				return $apiRet('Cliente cadastrado/atualizado com sucesso', 201);
			}

			return $apiRet('Houve um erro ao salvar os contratos do cliente.', 400);
		} catch (\Throwable $e) {
			$this->log('Clientes::addAPI: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine(), 'error');

			return $this->jsonResponse([
				'mensagem' => 'Erro interno ao processar addAPI: ' . $e->getMessage(),
				'retorno' => 'Erro interno ao processar addAPI: ' . $e->getMessage(),
			], 500);
		}
	}
	
	public function listAPI() {
        $this->autoRender = false;
        if ($this->request->is('get')) {
			list($empresa, $token, $erpCredErr) = ErpIntegrationRequest::readEmpresaAndToken(
				$this->request,
			);
            $cnpj = $this->request->getHeaderLine('cnpj') ?: $this->request->getQuery('cnpj');

			$apiRetList = function ($msg, $status = 200) {
				return $this->jsonResponse(['mensagem' => $msg, 'retorno' => $msg], $status);
			};
			if ($erpCredErr !== null) {
				return $apiRetList($erpCredErr, 400);
			}
			if(empty($token) || empty($empresa)) 
			return $apiRetList('Parâmetros da requisição inválidos', 400);

			if(empty($this->Empresas->findById($empresa)->first())) return $apiRetList('Parâmetros da requisição inválidos', 400);
			if($token == $this->Empresas->get($empresa)->token){
				$retorno['CNPJ'] = \removeCaracteres($cnpj);
				$retorno['Empresa'] = $empresa;

				if(!empty($cnpj)){
					$tipo = strlen($retorno['CNPJ']) > 11 ? 'j' : 'f' ;
					if($tipo == 'j') $cliente = $this->Clientes->findByCnpj($retorno['CNPJ'])->where(['idempresa' => $retorno['Empresa']])->toArray();
					else $cliente = $this->Clientes->findByCpf($retorno['CNPJ'])->where(['idempresa' => $retorno['Empresa'], 'tipo' => C_ClientesTipoFisica])->toArray();
					if (empty($cliente)) {
						return $apiRetList('Não foi encontrado um cliente com o CNPJ/CPF '. $cnpj, 404);
					}
				} else {
					$cliente = $this->Clientes->find('all')->where(['idempresa' => $retorno['Empresa']])->toArray(); 
				}
				foreach ($cliente as $reg) {
					$publicCode = $reg->get('public_code');
					$reg = $this->Clientes->clicontratosArr($reg);
					$reg = $this->Clientes->clientesArr($reg);
					if ($publicCode !== null && $publicCode !== '') {
						$reg->accessible('public_code', true);
						$reg->set('public_code', $publicCode);
					}
				}
				return $this->jsonResponse($cliente, 200);
			} else {
				return $apiRetList('Autenticação Inválida', 401);
			}
		}
	}

	public function sincronizacliente($idcliente) {
		$clienteEnt = $this->_findClienteForCurrentUser($idcliente);
		if (empty($clienteEnt)) {
			$this->Flash->error(__('Cliente não encontrado para sincronização.'));

			return;
		}
		$err = ClienteErpSyncService::sincronizarCliente(
			$clienteEnt,
			(int)$idcliente,
			(int)$this->Auth->user('idempresa'),
			(int)$this->Auth->user('id')
		);
		if ($err !== null) {
			$this->Flash->error($err);
		}
	}
}
