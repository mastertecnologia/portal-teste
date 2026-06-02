<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Service\AccessDiagnosticService;
use App\Service\RbacAccessRequestService;
use App\Service\Common\ModelService;
use App\Service\ClienteDomain\ClienteDomainBridge;
use App\Service\Ticket\DashboardService;
use App\Utility\ClienteDomainEventType;
use App\Utility\Fiscal\FiscalSqlConditions;
use App\Utility\PortalUi;
use App\Utility\RbacClientePortal;
use App\Utility\SupportInboxMail;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Event\Event;
use Cake\Http\Response;
use Cake\Log\Log;
use Cake\Routing\Router;

// PGMPackages: config/pgmpackages_require.php (incluído a partir de bootstrap.php).

// Fallbacks se UserConstants não definir (Linux/case do vendor). Alinhado a docs e FaturasController::C_RoleCliente.
// Não há troca de banco por empresa: um único Datasource; idempresa na sessão escopos dados.
if (!defined('C_RoleCliente'))             define('C_RoleCliente', 1);
if (!defined('C_RoleFuncionario'))          define('C_RoleFuncionario', 0);
if (!defined('C_EmpresaPGM'))               define('C_EmpresaPGM', 2);
if (!defined('C_EmpresaMaster'))            define('C_EmpresaMaster', 1);

//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/Utilities.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/TicketConstants.php';

// GoogleAuthenticator: carregamento lazy em _loadGoogleAuthenticatorLibs() — evita 500 em login/acessoEmpresa se o path falhar.

use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\Mailer\Email;
use Cake\Mailer\TransportFactory;
use Cake\Utility\Security;

class UsersController extends AppController {
	public function initialize() {
		parent::initialize();
		// Use ModelService instead of repetitive loadModel calls
		// This reduces code duplication from 19 loadModel calls to 1 line
		ModelService::loadModelsIntoController($this, [
			'Clientes', 'Clicontratos', 'Users', 'Atividades', 'Tickets',
			'Ticketsmovs', 'Visitas', 'Empresas', 'Queues', 'QueuesUsers',
			'SupportLevels', 'Empresasusers', 'Orcamentos', 'Orcamentosservicos',
			'Produtos', 'Ordensservico', 'Config'
		]);
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->set('title', 'Usuários');
		$this->Auth->allow(['login', 'acessoEmpresa', 'desativaverificacaosemlogin', 'enviaEmailAutenticacaoSemLogin', 'loginempresa', 'logout', 'privacyPolicy', 'cadastrocliente', 'verificacnpjcliente', 'verificacpfcliente', 'verificalogincadastro', 'resetPassword', 'resetPasswordNew', 'verificacpf', 'verificacodigo', 'verificaloginduasetapas']);

		if ($this->request->getParam('action') === 'resetPasswordNew' && in_array('Security', $this->components()->loaded(), true)) {
			$existing = $this->Security->getConfig('unlockedFields');
			$this->Security->setConfig('unlockedFields', array_values(array_unique(array_merge(
				is_array($existing) ? $existing : [],
				['password', 'confirmPassword']
			))));
		}
		if ($this->request->getParam('action') === 'changeProfile' && in_array('Security', $this->components()->loaded(), true)) {
			$existing = $this->Security->getConfig('unlockedFields');
			$this->Security->setConfig('unlockedFields', array_values(array_unique(array_merge(
				is_array($existing) ? $existing : [],
				['foto_perfil', 'remover_foto']
			))));
		}

		if (in_array('Security', $this->components()->loaded(), true)) {
			$existingUnlocked = $this->Security->getConfig('unlockedActions');
			if (!is_array($existingUnlocked)) {
				$existingUnlocked = [];
			}
			$this->Security->setConfig('unlockedActions', array_values(array_unique(array_merge(
				$existingUnlocked,
				['verificacnpjcliente', 'verificacpfcliente', 'cadastrocliente']
			))));
		}

		if (in_array('Csrf', $this->components()->loaded())) {
            if (in_array($this->request->getParam('action'), ['verificacnpjcliente', 'verificacpfcliente', 'cadastrocliente'])) {
                $this->getEventManager()->off($this->Csrf);
            }
        }
	}

	/**
	 * Inclui Sonata/Google libs só nas ações 2FA — login/acessoEmpresa não dependem disto (evita fatal se path errado).
	 */
	protected function _loadGoogleAuthenticatorLibs(): void {
		static $done = false;
		if ($done) {
			return;
		}
		$bases = [
			WWW_ROOT . 'plugins' . DS . 'GoogleAuthenticator-2.x' . DS . 'src' . DS,
			ROOT . DS . 'public' . DS . 'plugins' . DS . 'GoogleAuthenticator-2.x' . DS . 'src' . DS,
			ROOT . DS . 'webroot' . DS . 'plugins' . DS . 'GoogleAuthenticator-2.x' . DS . 'src' . DS,
		];
		$dir = null;
		foreach ($bases as $base) {
			if (is_file($base . 'GoogleAuthenticator.php')) {
				$dir = $base;
				break;
			}
		}
		if ($dir === null) {
			throw new \RuntimeException(
				'GoogleAuthenticator não encontrado. Verifique WEBROOT_DIR=public ou a pasta plugins/GoogleAuthenticator-2.x.'
			);
		}
		require_once $dir . 'FixedBitNotation.php';
		require_once $dir . 'GoogleQrUrl.php';
		require_once $dir . 'GoogleAuthenticatorInterface.php';
		require_once $dir . 'GoogleAuthenticator.php';
		$done = true;
	}

	public function index() {
		$this->set('title', 'Usuários da equipe (PGM/Master)');
		if ($this->Auth->user('role') == 1) return $this->redirect(['action' => 'dashboard']);
		$fromQueues = ($this->request->getQuery('from') === 'queues');
		$admins = $this->Users
			->find('all', ['fields' => ['id', 'username', 'name', 'email', 'secret', 'created', 'inativo', 'idcliente']])
			->where(['role' => 0, 'idcliente IS' => null])
			->order(['username' => 'ASC'])
			->toArray();
		$this->set(compact('admins', 'fromQueues'));
		$this->set('hideLayoutPageTitle', true);
	}

	public function indexClientes() {
		$this->set('title', 'Usuários clientes');
		if ($this->Auth->user('role') == 1) return $this->redirect(['action' => 'dashboard']);
		$qClients = $this->Users
			->find('all')
			->where(['role' => 1, 'idcliente IS NOT' => null])
			->contain(['Clientes' => ['fields' => ['razaosocial', 'nome', 'tipo', 'email', 'cnpj', 'cpf', 'fone', 'fone2', 'inativo']]])
			->order(['username' => 'ASC']);
		// ABAC desabilitado: tabela users não possui coluna idempresa
		$this->set('clients', $qClients->toArray());
	}

	/**
	 * Página de fallback quando RBAC (enforce) nega acesso; rota em whitelist (config/rbac.php).
	 * Se existir captura em sessão (gravada pelo RbacComponent), mostra diagnóstico e sugestões (somente leitura).
	 */
	public function accessDenied() {
		$this->set('title', 'Acesso não autorizado');
		$this->set('hideLayoutPageTitle', true);

		$rbacDenialReport = null;
		$supportCode = null;
		$canViewDetailedDiagnostic = false;
		$allowAccessRequests = false;
		$session = $this->request->getSession();
		$cap = $session->read(AccessDiagnosticService::SESSION_ACCESS_DENIED_CAPTURE);
		$session->delete(AccessDiagnosticService::SESSION_ACCESS_DENIED_CAPTURE);
		$user = (array)$this->Auth->user();
		$rbCfg = (array)Configure::read('Rbac');
		$diagCfg = isset($rbCfg['diagnostics']) && is_array($rbCfg['diagnostics']) ? $rbCfg['diagnostics'] : [];
		$diagEnabled = !array_key_exists('enabled', $diagCfg) || (bool)$diagCfg['enabled'];
		$showDetailsOnDenied = !empty($diagCfg['show_details_on_access_denied']);
		$ttlMin = isset($diagCfg['support_code_ttl_minutes']) ? (int)$diagCfg['support_code_ttl_minutes'] : 60;
		if ($ttlMin <= 0) {
			$ttlMin = 60;
		}
		$allowAccessRequests = !empty($diagCfg['allow_user_access_requests']) && !empty($user['id']);
		$canViewDetailedDiagnostic = $diagEnabled
			&& $showDetailsOnDenied
			&& AccessDiagnosticService::canViewRbacDiagnostic($user);

		try {
			if (is_array($cap) && !empty($user['id']) && (int)$cap['user_id'] === (int)$user['id']) {
				$ts = isset($cap['ts']) ? (int)$cap['ts'] : 0;
				if ($ts > 0 && (time() - $ts) <= ($ttlMin * 60)) {
					$supportCode = !empty($cap['support_code']) ? (string)$cap['support_code'] : null;
					if ($allowAccessRequests && $supportCode !== null) {
						$session->write(RbacAccessRequestService::SESSION_ACCESS_REQUEST_CAPTURE, [
							'user_id' => (int)$user['id'],
							'controller' => (string)($cap['controller'] ?? ''),
							'action' => (string)($cap['action'] ?? ''),
							'reason' => (string)($cap['reason'] ?? ''),
							'prefix' => (string)($cap['prefix'] ?? ''),
							'plugin' => (string)($cap['plugin'] ?? ''),
							'support_code' => $supportCode,
							'ts' => $ts,
						]);
					}
					if ($canViewDetailedDiagnostic) {
						$svc = new AccessDiagnosticService();
						$rbacDenialReport = $svc->diagnoseFromDenialCapture($cap, $user);
					}
				}
			}
		} catch (\Throwable $e) {
			Log::warning(sprintf(
				'RBAC accessDenied diagnostic failed user_id=%d msg=%s',
				(int)($user['id'] ?? 0),
				$e->getMessage()
			));
			$rbacDenialReport = null;
		}
		$this->set(compact('rbacDenialReport', 'supportCode', 'canViewDetailedDiagnostic', 'allowAccessRequests'));
	}

	public function dashboard($erro = null) {
		if ((int)$this->Auth->user('role') === (int)C_RoleFuncionario
			&& (string)$this->request->getQuery('legacy') !== '1') {
			$protoHome = PortalUi::redirectToPrototypeIfEnabled('home', 'ErpHomePrototype', 'index');
			if ($protoHome !== null) {
				return $this->redirect($protoHome);
			}
		}
		$this->set('title', 'Dashboard');
		// Título e breadcrumb próprios em dashboard.ctp (evita duplicar "Dashboard" com .page-titles).
		$this->set('hideLayoutPageTitle', true);
		$idusuario = $this->Auth->user('id');
		$idcliente = $this->Auth->user('idcliente');
		$role = $this->Auth->user('role');
		$hoje = date('d/m/Y');
		$mes = date('01/m/Y');
		$empresa = $this->Auth->user('idempresa');

		$iniSemana = $this->_dataBrParaDb(\primeiroDiaSemana());
		$finSemana = $this->_dataBrParaDb(\ultimoDiaSemana());

		if ($role == 0) {
			$empresaInt = (int)$empresa;
			$this->set('dashPgmSemEmpresaSessao', $empresaInt <= 0);
			if ($empresaInt <= 0) {
				$this->set('ticketsPendentesTable', []);
				$this->set('ticketsSendoResolvidosTable', []);
				$this->set('ticketsFinalizadosCount', 0);
				$this->set('ticketsFinalizadosTable', []);
				$this->set('usuariosBloqueadosTable', []);
				$this->set('dashPgmKpi', $this->_dashPgmKpiEmpty());
			} else {
			$ordensTable = $this->Ordensservico->findByIdempresa($empresa)
				->contain(['Clientes'])
				->where([
					'Ordensservico.iduser' => $idusuario,
					'dataabertura >=' => $iniSemana,
					'dataabertura <=' => $finSemana,
				])
				->order(['Ordensservico.id DESC'])
				->limit(5)
			->toArray();
			$qTp = $this->Tickets->find('all')
				->contain(['Clientes'])
				->where([ 'situacao IN' => [C_TicketSituacaoPendente]])
				->order(['Tickets.id DESC']);
			$this->Abac->applyToQuery($qTp, 'Tickets', 'Tickets');
			$ticketsPendentesTable = $qTp->toArray();
			$qTs = $this->Tickets->find('all')
				->contain(['Clientes'])
				->where([ 'situacao IN' => [C_TicketSituacaoEmandamento], ])
				->order(['Tickets.id DESC']);
			$this->Abac->applyToQuery($qTs, 'Tickets', 'Tickets');
			$ticketsSendoResolvidosTable = $qTs->toArray();
			$qTfc = $this->Tickets->find()
				->where(['situacao IN' => [C_TicketSituacaoResolvido, C_TicketSituacaoFechado]]);
			$this->Abac->applyToQuery($qTfc, 'Tickets', 'Tickets');
			$ticketsFinalizadosCount = $qTfc->count();
			$qTft = $this->Tickets->find('all')
				->contain(['Clientes'])
				->where(['situacao IN' => [C_TicketSituacaoResolvido, C_TicketSituacaoFechado]])
				->order(['Tickets.id DESC'])
				->limit(50);
			$this->Abac->applyToQuery($qTft, 'Tickets', 'Tickets');
			$ticketsFinalizadosTable = $qTft->toArray();
			$usuariosBloqueadosTable = $this->_usuariosBloqueadosEmpresa($empresaInt);

			$this->set('ticketsPendentesTable', $ticketsPendentesTable);
			$this->set('ticketsSendoResolvidosTable', $ticketsSendoResolvidosTable);
			$this->set('ticketsFinalizadosCount', $ticketsFinalizadosCount);
			$this->set('ticketsFinalizadosTable', $ticketsFinalizadosTable);
			$this->set('usuariosBloqueadosTable', $usuariosBloqueadosTable);
			$this->set('dashPgmKpi', $this->_dashPgmKpiData($empresaInt));
			}
		} else {
			if(!$this->Auth->user('permissaoacesso')) return $this->redirect(['controller' => 'Tickets', 'action' => 'indexcliente']);

			$qOrdDash = $this->Ordensservico->find('all');
			$this->Abac->applyToQuery($qOrdDash, 'Ordensservico', 'Ordensservico');
			$ordensCliente = sizeof($qOrdDash->toArray());
			$qOrcDash = $this->Orcamentos->find('all');
			$this->Abac->applyToQuery($qOrcDash, 'Orcamentos', 'Orcamentos');
			$orcamentosCliente = sizeof($qOrcDash->toArray());
			$qTcDash = $this->Tickets->find('all')->where(['idautor' => $idusuario]);
			$this->Abac->applyToQuery($qTcDash, 'Tickets', 'Tickets');
			$ticketsCliente = sizeof($qTcDash->toArray());
			$qVisDash = $this->Visitas->find('all')->where(['situacao' => C_UserSituacaoFinalizada]);
			$this->Abac->applyToQuery($qVisDash, 'Visitas', 'Visitas');
			$visitasCliente = sizeof($qVisDash->toArray());
			$ticketsPendentes = $this->Tickets->find('all')->where([
				'situacao IN' => [C_TicketSituacaoPendente, C_TicketSituacaoEmandamento]
			]);
			$this->Abac->applyToQuery($ticketsPendentes, 'Tickets', 'Tickets');
			if(!$this->Auth->user('permissaoacesso')) $ticketsPendentes = $ticketsPendentes->where(['idautor' => $idusuario]);

			$qContrDash = $this->Clicontratos->find('all')->order(['id DESC']);
			$this->Abac->applyToQuery($qContrDash, 'Clicontratos', 'Clicontratos');
			$contratos = $qContrDash->toArray();

			$qOrcRec = $this->Orcamentos->find('all')->contain(['Clientes'])->order(['Orcamentos.id DESC'])->limit(5);
			$this->Abac->applyToQuery($qOrcRec, 'Orcamentos', 'Orcamentos');
			$orcamentosRecentes = $qOrcRec->toArray();
			$qVisRec = $this->Visitas->find('all')->contain(['Listamembros', 'Clientes'])->limit(5)->order(['Visitas.data']);
			$this->Abac->applyToQuery($qVisRec, 'Visitas', 'Visitas');
			$visitasRecentes = $qVisRec->toArray();

			$autores =  $this->Users->find('list', ['keyField' => 'id', 'valueField' => 'name'])->where(['role' => 0])->toArray();
			$this->set('autores', $autores);

			$this->set('ordensCliente',  $ordensCliente);
			$this->set('orcamentosCliente', $orcamentosCliente);
			$this->set('ticketsCliente', $ticketsCliente);
			$this->set('visitasCliente', $visitasCliente);
			$this->set('ticketsPendentes', $ticketsPendentes->toArray());

			$this->set('contratos', $contratos);
			$this->set('orcamentosRecentes', $orcamentosRecentes);
			$this->set('visitasRecentes', $visitasRecentes);
		}

		// Desempenho pessoal (ABAC em Ordensservico quando habilitado)
		if ((int)$role === 0 && (int)$empresa <= 0) {
			$this->set('historico', []);
		} else {
			$this->set('historico', $this->Ordensservico->historicoOrdens($this->Auth->user('id'), $empresa, $this));
		}
		// Label para o gráfico
		$this->set('labelHist', ['Ordens de Serviço']);
	}

	public function requisicoesAcesso() {
		$this->set('title', 'Requisições de acesso');
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['action' => 'dashboard']);
		}

		$usuariosBloqueadosTable = $this->_usuariosBloqueadosEmpresa((int)$this->Auth->user('idempresa'));

		$this->set('usuariosBloqueadosTable', $usuariosBloqueadosTable);
	}

	/**
	 * Usuários bloqueados vinculados à empresa (filtro em memória — evita matching/distinct que podem falhar em alguns DB/drivers).
	 */
	protected function _usuariosBloqueadosEmpresa(int $idempresa): array {
		$all = $this->Users
			->findByBloqueado(1)
			->contain(['Clientes' => ['fields' => ['nome', 'razaosocial', 'cnpj', 'tipo', 'cpf']]])
			->contain(['Empresasusers', 'Empresasusers.Empresas'])
			->order(['Users.created' => 'DESC'])
			->toArray();
		$out = [];
		foreach ($all as $u) {
			$links = $u->empresasusers ?? [];
			foreach ($links as $eu) {
				if ((int)$eu->idempresa === $idempresa) {
					$out[] = $u;
					break;
				}
			}
		}
		return $out;
	}

	/**
	 * KPIs vazios quando não há empresa na sessão (evita consultas com id 0).
	 *
	 * @return array<string,mixed>
	 */
	protected function _dashPgmKpiEmpty(): array {
		$trendLabels = [];
		$trendOpened = [];
		$trendClosed = [];
		for ($i = 29; $i >= 0; $i--) {
			$ts = strtotime('-' . $i . ' days');
			$trendLabels[] = date('d/m', $ts);
			$trendOpened[] = 0;
			$trendClosed[] = 0;
		}

		return [
			'sla_usa_enterprise' => false,
			'sla_no_prazo' => 0,
			'sla_em_risco' => 0,
			'sla_vencido' => 0,
			'sla_pct' => 0,
			'abertos_hoje' => 0,
			'fechados_hoje' => 0,
			'saldo_dia' => 0,
			'ranking' => [],
			'ranking_period_label' => 'mês',
			'ranking_month_closed_count' => 0,
			'ranking_month_hint' => date('m/Y'),
			'trend_labels' => $trendLabels,
			'trend_opened' => $trendOpened,
			'trend_closed' => $trendClosed,
		];
	}

	/**
	 * KPIs reais do dashboard PGM (funcionários): SLA, saldo do dia, ranking de técnicos, série 30 dias.
	 */
	protected function _dashPgmKpiData(int $idempresa): array {
		$cols = $this->Tickets->getSchema()->columns();
		$closedSit = $this->_closedTicketSituacoes();
		$openSit = [];
		if (defined('C_TicketSituacaoPendente')) {
			$openSit[] = (int)C_TicketSituacaoPendente;
		}
		if (defined('C_TicketSituacaoEmandamento')) {
			$openSit[] = (int)C_TicketSituacaoEmandamento;
		}

		$d0 = date('Y-m-d') . ' 00:00:00';
		$d1 = date('Y-m-d') . ' 23:59:59';

		$qAb = $this->Tickets->find()
			->where(['Tickets.created >=' => $d0, 'Tickets.created <=' => $d1]);
		$this->Abac->applyToQuery($qAb, 'Tickets', 'Tickets');
		$abertosHoje = $qAb->count();

		$svc = new DashboardService($this->Tickets);
		$snapshot = $svc->operationalSnapshot($idempresa);
		$fechadosHoje = (int)($snapshot['resolvidos_hoje'] ?? 0);
		$saldoDia = $fechadosHoje - $abertosHoje;

		$slaNoPrazo = 0;
		$slaEmRisco = 0;
		$slaVencido = 0;
		$slaUsaEnterprise = in_array('sla_status', $cols, true) && $openSit !== [];

		if ($slaUsaEnterprise) {
			$q = $this->Tickets->find();
			$f = $q->func()->count('*');
			$q->select(['sla_status', 'total' => $f])
				->where(['Tickets.situacao IN' => $openSit])
				->group('sla_status');
			$this->Abac->applyToQuery($q, 'Tickets', 'Tickets');
			$rows = $q->hydrate(false)->toArray();
			foreach ($rows as $r) {
				$st = isset($r['sla_status']) ? (string)$r['sla_status'] : '';
				$n = (int)$r['total'];
				if ($st === 'violado') {
					$slaVencido += $n;
				} elseif ($st === 'em_risco') {
					$slaEmRisco += $n;
				} else {
					$slaNoPrazo += $n;
				}
			}
		} elseif ($openSit !== []) {
			$qOpen = $this->Tickets->find()
				->select(['created'])
				->where(['Tickets.situacao IN' => $openSit]);
			$this->Abac->applyToQuery($qOpen, 'Tickets', 'Tickets');
			$openList = $qOpen->toArray();
			foreach ($openList as $t) {
				$dias = function_exists('pgm_ticket_open_age_days')
					? pgm_ticket_open_age_days($t->created)
					: max(0, (int) floor((time() - strtotime((string) $t->created)) / 86400));
				if ($dias <= 3) {
					$slaNoPrazo++;
				} elseif ($dias <= 10) {
					$slaEmRisco++;
				} else {
					$slaVencido++;
				}
			}
		}

		$totalSla = max(1, $slaNoPrazo + $slaEmRisco + $slaVencido);
		$slaPct = (int)round(($slaNoPrazo / $totalSla) * 100);

		$tecMap = $this->_tecnicosEmpresaMap($idempresa);
		$ranking = [];
		$rankingPeriodLabel = 'mês';
		$rankingMonthClosedCount = 0;
		$hasRespCol = in_array('idtecnico_responsavel', $cols, true);
		$hasOwnerCol = in_array('owner_id', $cols, true);
		if (($hasRespCol || $hasOwnerCol) && $closedSit !== []) {
			$monthStart = date('Y-m-01 00:00:00');
			$monthEnd = date('Y-m-t 23:59:59');
			$rankingMonthClosedCount = $this->_dashPgmClosedTicketsCountInRange($idempresa, $closedSit, $cols, $monthStart, $monthEnd);
			$byCount = $this->_dashPgmTechnicianRankingCounts($idempresa, $closedSit, $cols, $monthStart, $monthEnd);
			if ($byCount !== []) {
				if ($tecMap !== []) {
					$byCount = array_intersect_key($byCount, $tecMap);
				}
				arsort($byCount);
			}
			$idToNome = [];
			if ($byCount !== []) {
				$nameRows = $this->Users->find()
					->select(['id', 'name', 'username'])
					->where(['id IN' => array_keys($byCount)])
					->enableHydration(false)
					->toArray();
				foreach ($nameRows as $nr) {
					$uid = (int)($nr['id'] ?? $nr['Users__id'] ?? 0);
					if ($uid <= 0) {
						continue;
					}
					$nm = '';
					$un = '';
					foreach ($nr as $k => $v) {
						if (!is_string($k) || $v === null) {
							continue;
						}
						if ($k === 'name' || $k === 'Users__name' || substr($k, -6) === '__name') {
							$nm = trim((string)$v);
						}
						if ($k === 'username' || $k === 'Users__username' || substr($k, -10) === '__username') {
							$un = trim((string)$v);
						}
					}
					$idToNome[$uid] = $nm !== '' ? $nm : ($un !== '' ? $un : '#' . $uid);
				}
			}
			$place = 1;
			foreach ($byCount as $uid => $cnt) {
				if ($place > 10) {
					break;
				}
				$ranking[] = [
					'place' => $place,
					'nome' => $idToNome[$uid] ?? $tecMap[$uid] ?? ('#' . $uid),
					'tickets' => $cnt,
				];
				$place++;
			}
		}

		$trendLabels = [];
		$trendOpened = [];
		$trendClosed = [];
		for ($i = 29; $i >= 0; $i--) {
			$ts = strtotime('-' . $i . ' days');
			$day = date('Y-m-d', $ts);
			$ds = $day . ' 00:00:00';
			$de = $day . ' 23:59:59';
			$trendLabels[] = date('d/m', $ts);
			$qTrO = $this->Tickets->find()
				->where(['Tickets.created >=' => $ds, 'Tickets.created <=' => $de]);
			$this->Abac->applyToQuery($qTrO, 'Tickets', 'Tickets');
			$trendOpened[] = $qTrO->count();
			if (in_array('data_resolucao', $cols, true)) {
				$qTrC = $this->Tickets->find()
					->where([
						'Tickets.situacao IN' => $closedSit,
						'OR' => [
							[
								'AND' => [
									'Tickets.data_resolucao IS NOT' => null,
									'Tickets.data_resolucao >=' => $ds,
									'Tickets.data_resolucao <=' => $de,
								],
							],
							[
								'AND' => [
									'Tickets.data_resolucao IS' => null,
									'Tickets.data_fechamento IS NOT' => null,
									'Tickets.data_fechamento >=' => $ds,
									'Tickets.data_fechamento <=' => $de,
								],
							],
						],
					]);
				$this->Abac->applyToQuery($qTrC, 'Tickets', 'Tickets');
				$trendClosed[] = $qTrC->count();
			} elseif ($closedSit !== []) {
				$qTrC2 = $this->Tickets->find()
					->where([
						'Tickets.situacao IN' => $closedSit,
						'Tickets.data_fechamento >=' => $ds,
						'Tickets.data_fechamento <=' => $de,
					]);
				$this->Abac->applyToQuery($qTrC2, 'Tickets', 'Tickets');
				$trendClosed[] = $qTrC2->count();
			} else {
				$trendClosed[] = 0;
			}
		}

		return [
			'sla_usa_enterprise' => $slaUsaEnterprise,
			'sla_no_prazo' => $slaNoPrazo,
			'sla_em_risco' => $slaEmRisco,
			'sla_vencido' => $slaVencido,
			'sla_pct' => $slaPct,
			'abertos_hoje' => $abertosHoje,
			'fechados_hoje' => $fechadosHoje,
			'saldo_dia' => $saldoDia,
			'ranking' => $ranking,
			'ranking_period_label' => $rankingPeriodLabel,
			'ranking_month_closed_count' => $rankingMonthClosedCount,
			'ranking_month_hint' => date('m/Y'),
			'trend_labels' => $trendLabels,
			'trend_opened' => $trendOpened,
			'trend_closed' => $trendClosed,
		];
	}

	/**
	 * @return int[]
	 */
	protected function _closedTicketSituacoes(): array {
		if (!defined('C_TicketSituacaoResolvido') || !defined('C_TicketSituacaoFechado')) {
			return [];
		}
		$out = [(int)C_TicketSituacaoResolvido, (int)C_TicketSituacaoFechado];
		if (defined('C_TicketSituacaoCancelado')) {
			$out[] = (int)C_TicketSituacaoCancelado;
		}

		return $out;
	}

	/**
	 * @param array<string,mixed> $row
	 */
	protected function _rankingRowTecnicoId(array $row): ?int {
		foreach (['tecnico_efetivo', 'Tickets__tecnico_efetivo'] as $key) {
			if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
				$tid = (int)$row[$key];
				if ($tid > 0) {
					return $tid;
				}
			}
		}
		foreach ($row as $k => $val) {
			if (!is_string($k) || $val === null || $val === '') {
				continue;
			}
			if (substr($k, -strlen('tecnico_efetivo')) === 'tecnico_efetivo') {
				$tid = (int)$val;
				if ($tid > 0) {
					return $tid;
				}
			}
		}
		$v = $row['idtecnico_responsavel'] ?? $row['Tickets__idtecnico_responsavel'] ?? null;
		if ($v === null || $v === '') {
			foreach ($row as $k => $val) {
				if (is_string($k) && substr($k, -strlen('idtecnico_responsavel')) === 'idtecnico_responsavel' && $val !== null && $val !== '') {
					return (int)$val;
				}
			}

			return null;
		}

		return (int)$v;
	}

	/**
	 * @param array<string,mixed> $row
	 */
	protected function _rankingRowCount(array $row): ?int {
		foreach (['cnt', 'count', 'Tickets__cnt'] as $key) {
			if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
				return (int)$row[$key];
			}
		}
		foreach ($row as $k => $val) {
			if (!is_string($k)) {
				continue;
			}
			if ($val === null || $val === '') {
				continue;
			}
			if ($k === 'cnt' || substr($k, -4) === '__cnt' || substr($k, -6) === '__count') {
				return (int)$val;
			}
		}

		return null;
	}

	/**
	 * Converte data em formato BR (d/m/Y ou d-m-Y) para Y-m-d (PostgreSQL / campos date).
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	protected function _dataBrParaDb($value) {
		if ($value === null || $value === '') {
			return $value;
		}
		if ($value instanceof \DateTimeInterface) {
			return $value->format('Y-m-d');
		}
		$s = trim((string)$value);
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
			return $s;
		}
		if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/', $s, $m)) {
			$dd = (int)$m[1];
			$mm = (int)$m[2];
			$yy = (int)$m[3];
			if (checkdate($mm, $dd, $yy)) {
				return sprintf('%04d-%02d-%02d', $yy, $mm, $dd);
			}
		}

		return $value;
	}

	/**
	 * Mesma janela de datas do ranking (data_resolucao ou data_fechamento).
	 *
	 * @param \Cake\ORM\Query $q
	 * @param string[] $cols
	 */
	protected function _dashPgmApplyRankingDateWindow($q, array $cols, $rangeStart, $rangeEnd) {
		if ($rangeStart === null || $rangeEnd === null) {
			return;
		}
		if (in_array('data_resolucao', $cols, true)) {
			$q->where([
				'OR' => [
					[
						'AND' => [
							'Tickets.data_resolucao IS NOT' => null,
							'Tickets.data_resolucao >=' => $rangeStart,
							'Tickets.data_resolucao <=' => $rangeEnd,
						],
					],
					[
						'AND' => [
							'Tickets.data_resolucao IS' => null,
							'Tickets.data_fechamento IS NOT' => null,
							'Tickets.data_fechamento >=' => $rangeStart,
							'Tickets.data_fechamento <=' => $rangeEnd,
						],
					],
				],
			]);
		} else {
			$q->where([
				'Tickets.data_fechamento >=' => $rangeStart,
				'Tickets.data_fechamento <=' => $rangeEnd,
			]);
		}
	}

	/**
	 * Quantidade de tickets fechados (situações encerradas) no intervalo, sem filtro de técnico.
	 *
	 * @param int $idempresa
	 * @param int[] $closedSit
	 * @param string[] $cols
	 * @return int
	 */
	protected function _dashPgmClosedTicketsCountInRange($idempresa, array $closedSit, array $cols, $rangeStart, $rangeEnd) {
		$q = $this->Tickets->find();
		$q->where([
			'Tickets.situacao IN' => $closedSit,
		]);
		$this->Abac->applyToQuery($q, 'Tickets', 'Tickets');
		$this->_dashPgmApplyRankingDateWindow($q, $cols, $rangeStart, $rangeEnd);

		return (int)$q->count();
	}

	/**
	 * Expressão SQL do “técnico efetivo” para o ranking: campos do ticket + fallback pelo último ticketsmovs
	 * que registrou transição para situação encerrada (quem operou o fechamento).
	 *
	 * @param string[] $cols
	 * @param int[] $closedSit Situações consideradas fechamento (resolvido/fechado/cancelado…)
	 * @param int $idempresa Empresa do ticket (movimentação só conta se o usuário for funcionário vinculado a essa empresa)
	 */
	protected function _dashPgmTecnicoEfetivoSql(array $cols, array $closedSit, int $idempresa): ?string {
		if ($closedSit === []) {
			return null;
		}
		$hasResp = in_array('idtecnico_responsavel', $cols, true);
		$hasOwner = in_array('owner_id', $cols, true);
		if ($hasResp && $hasOwner) {
			$base = 'COALESCE(NULLIF(Tickets.idtecnico_responsavel, 0), NULLIF(Tickets.owner_id, 0))';
		} elseif ($hasResp) {
			$base = 'NULLIF(Tickets.idtecnico_responsavel, 0)';
		} elseif ($hasOwner) {
			$base = 'NULLIF(Tickets.owner_id, 0)';
		} else {
			return null;
		}
		$tm = $this->Ticketsmovs->getTable();
		$usersTbl = $this->Users->getTable();
		$euTbl = $this->Empresasusers->getTable();
		$closedIn = implode(',', array_map('intval', $closedSit));
		// Só usuários role=0 (funcionário), ativos, com vínculo na mesma empresa do ticket (critério do ranking PGM).
		$sub = '(SELECT tm_sub.idusuario FROM ' . $tm . ' AS tm_sub '
			. 'INNER JOIN ' . $usersTbl . ' AS u_r ON u_r.id = tm_sub.idusuario AND u_r.role = 0 AND (COALESCE(u_r.inativo, 0) = 0) '
			. 'INNER JOIN ' . $euTbl . ' AS eu_r ON eu_r.iduser = u_r.id AND eu_r.idempresa = Tickets.idempresa '
			. 'WHERE tm_sub.idticket = Tickets.id AND tm_sub.sitnova IN (' . $closedIn . ') '
			. 'ORDER BY tm_sub.id DESC LIMIT 1)';

		return 'COALESCE(' . $base . ', NULLIF(' . $sub . ', 0))';
	}

	/**
	 * Tickets fechados por técnico no intervalo.
	 * Data: com data_resolucao, usa resolução; se for NULL, usa data_fechamento.
	 * Técnico: COALESCE(idtecnico_responsavel, owner_id, último idusuario em ticketsmovs com sitnova encerrada).
	 *
	 * @param int $idempresa
	 * @param int[] $closedSit
	 * @param string[] $cols Colunas do schema tickets
	 * @param string|null $rangeStart Início do intervalo; null com $rangeEnd null = sem filtro de data
	 * @param string|null $rangeEnd
	 * @return int[]
	 */
	protected function _dashPgmTechnicianRankingCounts($idempresa, array $closedSit, array $cols, $rangeStart, $rangeEnd) {
		$tecSql = $this->_dashPgmTecnicoEfetivoSql($cols, $closedSit, $idempresa);
		if ($tecSql === null) {
			return [];
		}

		$q = $this->Tickets->find();
		$f = $q->func()->count('*');
		$tecExpr = $q->newExpr($tecSql);
		$q->select(['tecnico_efetivo' => $tecExpr, 'cnt' => $f])
			->where([
				'Tickets.situacao IN' => $closedSit,
			])
			->where($q->newExpr('(' . $tecSql . ') IS NOT NULL'));
		$this->Abac->applyToQuery($q, 'Tickets', 'Tickets');
		$this->_dashPgmApplyRankingDateWindow($q, $cols, $rangeStart, $rangeEnd);

		$rows = $q->group($tecExpr)
			->hydrate(false)
			->toArray();
		$byCount = [];
		foreach ($rows as $r) {
			$tid = $this->_rankingRowTecnicoId($r);
			$cnt = $this->_rankingRowCount($r);
			if ($tid !== null && $tid > 0 && $cnt !== null) {
				$byCount[$tid] = (int)$cnt;
			}
		}
		arsort($byCount);

		return $byCount;
	}

	/**
	 * Técnicos (role 0) vinculados à empresa via empresasusers.
	 *
	 * @return array<int,string> iduser => nome exibição
	 */
	protected function _tecnicosEmpresaMap(int $idempresa): array {
		$rels = $this->Empresasusers->find()
			->contain(['Users'])
			->where(['Empresasusers.idempresa' => $idempresa])
			->toArray();
		$map = [];
		foreach ($rels as $r) {
			$u = $r->user ?? null;
			if ($u === null || (int)$u->role !== 0) {
				continue;
			}
			if (isset($u->inativo) && (int)$u->inativo === 1) {
				continue;
			}
			$map[(int)$u->id] = !empty($u->name) ? (string)$u->name : (string)$u->username;
		}

		return $map;
	}

	public function add() {
		// Permissão para o cliente
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		
		$this->set('title', 'Adicionar Usuário');

		$user = $this->Users->newEntity();
		$fromQueues = ($this->request->getQuery('from') === 'queues');

		if ($this->request->is('post')) {
			$data = $this->request->getData();
			if (!empty($data['from']) && $data['from'] === 'queues') {
				$fromQueues = true;
			}
			unset($data['from']);

			$empresaVinculoIds = $this->_normalizeEmpresaVinculoPost($data);
			unset($data['empresa_vinculo_ids']);

			$this->usuarioExistente($data['username']);

			if (strcmp($data['password'], $data['confirm_password']) != 0) {
				$this->Flash->warning(__('Senhas não conferem!'));
			} else {
				// $data['admin'] = $data['role'] == C_RoleFuncionario ? 1 : 0;
				$user = $this->Users->patchEntity($user, $data);
				$user->role = 0;

				if ($this->Users->save($user)) {
					$allowedEmp = $this->_empresaIdsAllowedForEquipeVinculo();
					if ($allowedEmp === []) {
						$allowedEmp = [(int)C_EmpresaPGM];
					}
					$pickedEmp = $this->_resolveEmpresaVinculoForNewUser($empresaVinculoIds, $allowedEmp);
					if (!$this->_syncEmpresasVinculoEquipe((int)$user->id, $pickedEmp, $allowedEmp)) {
						$this->Flash->warning(__('Usuário criado, mas faltou vínculo com empresa (empresasusers). Edite o usuário e marque ao menos uma empresa.'));
					}
					$this->_syncQueuesUsuario((int)$user->id, (int)$this->Auth->user('idempresa'));
					$this->Flash->success(__('O usuário foi salvo.'));
					$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $user->id);
					if ($fromQueues) {
						return $this->redirect(['controller' => 'Queues', 'action' => 'adminTechnicians']);
					}
					return $this->redirect(['action' => 'index']);
				}

				$this->Flash->error(__('Não é possível adicionar o usuário.'));
			}
		}

		$queuesList = [];
		if ($this->_queuesTablesExist()) {
			$queuesListQ = $this->Queues->find('list', ['keyField' => 'id', 'valueField' => 'name'])
				->order(['sort_order' => 'ASC', 'id' => 'ASC']);
			$this->Abac->applyToQuery($queuesListQ, 'Queues', 'Queues');
			$queuesList = $queuesListQ->toArray();
		}
		$supportLevelsList = $this->_supportLevelsListForTechnicians();
		$empresasVinculoOptions = $this->_empresasOptionsForEquipeVinculo();
		$defaultEmpresaVinculoId = (int)$this->Auth->user('idempresa');
		if ($defaultEmpresaVinculoId <= 0) {
			$pref = $this->getEmpresaPreferencial((int)$this->Auth->user('id'));
			$defaultEmpresaVinculoId = $pref !== null ? (int)$pref : (int)C_EmpresaPGM;
		}
		$this->set(compact('queuesList', 'supportLevelsList', 'fromQueues', 'empresasVinculoOptions', 'defaultEmpresaVinculoId'));
		$this->set('user', $user);
		$this->set('hideLayoutPageTitle', true);
	}

	public function addcliente() {
		$idempresa = 0;

		$this->set('title', 'Adicionar Usuário de Cliente');
		if ($this->Auth->user('role') == 1) return $this->redirect(['action' => 'dashboard']);

		$user = $this->Users->newEntity();

		$clientesQ = $this->Clientes->find('all', ['keyField' => 'id', 'valueField' => 'razaosocial'])->where(['inativo' => 0])->order(['razaosocial']);
		$this->Abac->applyToQuery($clientesQ, 'Clientes');
		$clientes = $clientesQ->toArray();
		$clientesOpt = [];
		foreach($clientes as $reg){
			if($reg->tipo == C_ClientesTipoJuridica) $clientesOpt[$reg->id] = $reg->razaosocial;
			else $clientesOpt[$reg->id] = $reg->nome;
			
			$idempresa = $reg->empresadominante;
		}
		asort($clientesOpt);

		if ($this->request->is('post')) {
			$data = $this->request->getData();
			$cliente = $this->Clientes->findById($data['idcliente']);
			$cliente = $cliente->toList();

			// Mantém o cadastro do usuário na empresa "atual" (multi-empresa via dropdown).
			if (!empty($this->Auth->user('idempresa'))) $idempresa = (int)$this->Auth->user('idempresa');
			elseif (isset($cliente[0])) $idempresa = (int)$cliente[0]['empresadominante'];
			
			$this->usuarioExistente($data['email']);

			if (strcmp($data['password'], $data['confirm_password']) != 0) $this->Flash->warning(__('Senhas não conferem!'));
			else {
				$user = $this->Users->patchEntity($user, $data);
				$user->role = 1;

				if ($this->Users->save($user)) {
					// Coleta todas as empresas onde este cliente está cadastrado (mesmo CNPJ/CPF).
					$empresaIds = [$idempresa];
					if (isset($cliente[0])) {
						$cli = $cliente[0];
						$docField = ((int)$cli['tipo'] === C_ClientesTipoJuridica) ? 'cnpj' : 'cpf';
						$docVal = !empty($cli[$docField]) ? \removeCaracteres($cli[$docField]) : '';
						if ($docVal !== '') {
							$outrosClientes = $this->Clientes->find()
								->select(['idempresa'])
								->where([$docField => $docVal, 'inativo' => 0])
								->group(['idempresa'])
								->all();
							foreach ($outrosClientes as $oc) {
								if (!empty($oc->idempresa)) {
									$empresaIds[] = (int)$oc->idempresa;
								}
							}
						}
					}
					$empresaIds = array_unique(array_filter($empresaIds));

					$vinculosCriados = 0;
					foreach ($empresaIds as $eid) {
						$existe = $this->Empresasusers->find()->where(['iduser' => $user->id, 'idempresa' => $eid])->count();
						if (!$existe) {
							$eu = $this->Empresasusers->newEntity(['idempresa' => $eid, 'iduser' => $user->id]);
							if ($this->Empresasusers->save($eu)) {
								$vinculosCriados++;
							}
						}
					}
					RbacClientePortal::syncUserIfEligible((int)$user->id);

					$msg = __('O usuário foi salvo.');
					if ($vinculosCriados > 1) {
						$msg .= ' ' . __('Vinculado automaticamente a {0} empresas.', $vinculosCriados);
					}
					$this->Flash->success($msg);
					$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $user->id);
					return $this->redirect(['action' => 'index']);
				}
				$this->Flash->error(__('Não é possível adicionar o usuário.'));
			}
		}

		$this->set('clientes', $clientesOpt);
		$this->set('user', $user);
	}

	public function edit($id = null) {
		// Verifica as permissões
		if (!$this->Auth->user('admin')) {
			$this->Flash->error('Você não possui permissões para editar sua conta. Contate um administrador do sistema.');
			return $this->redirect(['action' => 'dashboard']);
		}
		if ($this->Auth->user('role') == 1) return $this->redirect(['action' => 'dashboard']);
		
		if ($id == null) $id = $this->Auth->user('id');
		$situacoes = "";
		
		$user = $this->Users->get($id);
		$fromQueues = ($this->request->getQuery('from') === 'queues');
		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			if (!empty($data['from']) && $data['from'] === 'queues') {
				$fromQueues = true;
			}
			unset($data['from']);

			$empresaVinculoPost = null;
			if ((int)$user->role === 0 && !empty($data['process_empresa_vinculo'])) {
				$empresaVinculoPost = $this->_normalizeEmpresaVinculoPost($data);
			}
			unset($data['empresa_vinculo_ids'], $data['process_empresa_vinculo']);

			$auditGenerate = (int)$user->role === 0
				&& (
					!empty($data['audit_password_generate_submit'])
					|| !empty($data['audit_password_generate'])
				);
			// #region agent log
			$this->_debugAuditLog('H1', 'UsersController::edit audit trigger computed', [
				'authUserId' => (int)$this->Auth->user('id'),
				'authUserEmpresa' => (int)$this->Auth->user('idempresa'),
				'authUserAdminRaw' => (string)$this->Auth->user('admin'),
				'targetUserId' => (int)$user->id,
				'targetUserEmpresa' => (int)$user->idempresa,
				'targetUserRole' => (int)$user->role,
				'auditGenerate' => (bool)$auditGenerate,
				'hasAuditGenerateSubmit' => !empty($data['audit_password_generate_submit']),
				'hasAuditGenerateHidden' => !empty($data['audit_password_generate']),
			]);
			// #endregion
			unset(
				$data['audit_password_generate'],
				$data['audit_password_generate_submit'],
				$data['audit_password_new'],
				$data['audit_password_confirm'],
				$data['audit_password_hash']
			);

			if ($auditGenerate && !$this->_userAdminMaySetAuditPasswordForUser($user)) {
				$adminRaw = $this->Auth->user('admin');
				$adminRawType = gettype($adminRaw);
				$adminRawValue = is_scalar($adminRaw) ? (string)$adminRaw : json_encode($adminRaw);
				$isSystemAdmin = $this->_isSystemAdminUser();
				// #region agent log
				$this->_debugAuditLog('H4', 'UsersController::edit blocked by permission check', [
					'authUserId' => (int)$this->Auth->user('id'),
					'targetUserId' => (int)$user->id,
					'authUserEmpresa' => (int)$this->Auth->user('idempresa'),
					'targetUserEmpresa' => (int)$user->idempresa,
					'adminRawType' => $adminRawType,
					'adminRawValue' => $adminRawValue,
					'isSystemAdmin' => $isSystemAdmin,
				]);
				// #endregion
				$this->Flash->error(
					'Não é permitido gerar chave de auditoria para um utilizador de outra empresa.'
					. ' [DBG audit: adminType=' . $adminRawType
					. ', adminRaw=' . $adminRawValue
					. ', isSystemAdmin=' . ($isSystemAdmin ? '1' : '0')
					. ', authEmpresa=' . (int)$this->Auth->user('idempresa')
					. ', targetEmpresa=' . (int)$user->idempresa
					. ']'
				);
				$redirEdit = ['action' => 'edit', $id];
				if ($fromQueues) {
					$redirEdit['?'] = ['from' => 'queues'];
				}

				return $this->redirect($redirEdit);
			}

			// Se o usuário permanecer ativo (inativo vazio ou 0), mantém a validação rígida de e-mail duplicado.
			// Se ele estiver sendo inativado, permitimos salvar mesmo com e-mail duplicado, para justamente
			// conseguir desativar o usuário conflitante.
			$isBecomingInactive = isset($data['inativo']) && (int)$data['inativo'] === 1;

			if (!$isBecomingInactive) {
				$this->usuarioExistente($data['email'], $id);

				$userExistente = $this->Users
					->find()
					->where([
						'inativo' => 0,
						'id !=' => $id,
						'OR' => [
							'username' => $data['email'],
							'email' => $data['email'],
						],
					])
					->first();

				if (!empty($userExistente)) {
					$this->Flash->error('Já existe um usuário com este e-mail no sistema, verifique e inative o usuário correspondente.');
					return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
				}
			}

			if (isset($data['role'])) unset($data['role']);
			$this->Users->patchEntity($user, $data);
			$savedMain = (bool)$this->Users->save($user);
			$oneTimeAuditSecret = null;
			$auditEmailSent = null;
			if ($savedMain && $auditGenerate) {
				$oneTimeAuditSecret = $this->_generateAuditPasswordPlain();
				$this->_applyAuditPasswordHashToUser($user, $oneTimeAuditSecret);
				if (!(bool)$this->Users->save($user)) {
					$this->Flash->warning('Utilizador guardado, mas falhou a gravação da chave de auditoria. Marque de novo «Gerar nova chave» e salve.');
					$oneTimeAuditSecret = null;
				} else {
					$auditEmailSent = $this->_sendAuditPasswordEmailToUser($user, $oneTimeAuditSecret);
				}
			}

			if ($savedMain) {
				if ((int)$user->role === 0) {
					if ($empresaVinculoPost !== null) {
						$allowedEv = $this->_empresaIdsAllowedForEquipeVinculo();
						if ($allowedEv === []) {
							$allowedEv = [(int)C_EmpresaPGM];
						}
						if ($empresaVinculoPost === []) {
							$this->Flash->warning('Marque ao menos uma empresa de acesso (obrigatório para login da equipe e Service Desk).');
						} else {
							$this->_replaceEmpresasVinculoEquipe((int)$id, $empresaVinculoPost, $allowedEv);
						}
					}
					$this->_syncQueuesUsuario((int)$id, (int)$this->Auth->user('idempresa'));
				}
				$this->Flash->success('As informações do usuário foram alteradas com sucesso!');
				if ($oneTimeAuditSecret !== null) {
					$this->Flash->warning(
						'Chave de auditoria gerada (mostrada uma única vez). Guarde e entregue ao técnico por canal seguro. '
						. 'Se perder, gere nova neste ecrã. Valor: ' . $oneTimeAuditSecret
					);
					if ($auditEmailSent === true) {
						$this->Flash->success('A chave de auditoria também foi enviada por e-mail ao usuário.');
					} elseif ($auditEmailSent === false) {
						$this->Flash->warning('A chave foi gerada, mas não foi possível enviar o e-mail ao usuário.');
					}
				}
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $id);
				if ($auditGenerate) {
					$redirStay = ['action' => 'edit', $id];
					if ($fromQueues) {
						$redirStay['?'] = ['from' => 'queues'];
					}

					return $this->redirect($redirStay);
				}
				if ($fromQueues) {
					return $this->redirect(['controller' => 'Queues', 'action' => 'adminTechnicians']);
				}
				return $this->redirect(['action' => 'index']);
			}

			$this->Flash->error('Ocorreu um erro ao editar as informações do usuário! Tente novamente mais tarde.');
		}

		$queuesList = [];
		$selectedQueues = [];
		$queuesUserSupportLevels = [];
		$showQueueLevelOverrides = false;
		if ($this->_queuesTablesExist() && (int)$user->role === 0) {
			$queuesListQ = $this->Queues->find('list', ['keyField' => 'id', 'valueField' => 'name'])
				->order(['sort_order' => 'ASC', 'id' => 'ASC']);
			$this->Abac->applyToQuery($queuesListQ, 'Queues', 'Queues');
			$queuesList = $queuesListQ->toArray();
			$selectedQueues = $this->QueuesUsers->find()
				->select(['queue_id'])
				->where(['user_id' => $user->id])
				->extract('queue_id')
				->toList();
			try {
				if (in_array('support_level_id', $this->QueuesUsers->getSchema()->columns(), true)) {
					foreach ($this->QueuesUsers->find()->where(['user_id' => $user->id])->all() as $link) {
						if (!empty($link->support_level_id)) {
							$queuesUserSupportLevels[(int)$link->queue_id] = (int)$link->support_level_id;
						}
					}
				}
			} catch (\Throwable $e) {
			}
		}

		$supportLevelsList = ((int)$user->role === 0) ? $this->_supportLevelsListForTechnicians() : [];
		$showQueueLevelOverrides = !empty($supportLevelsList) && $this->_queuesUsersSupportLevelColumn();
		$empresasVinculoOptions = [];
		$selectedEmpresaVinculoIds = [];
		if ((int)$user->role === 0) {
			$empresasVinculoOptions = $this->_empresasOptionsForEquipeVinculo();
			foreach ($this->Empresasusers->find()->where(['iduser' => $user->id])->all() as $evr) {
				$selectedEmpresaVinculoIds[] = (int)$evr->idempresa;
			}
		}
		$this->set('user', $user);
		$this->set(compact(
			'queuesList',
			'selectedQueues',
			'supportLevelsList',
			'queuesUserSupportLevels',
			'showQueueLevelOverrides',
			'fromQueues',
			'empresasVinculoOptions',
			'selectedEmpresaVinculoIds'
		));
		$this->set('hideLayoutPageTitle', true);
		$this->set('title', 'Editar Usuário');
	}

	public function editcliente($id = null) {
		$this->set('title', 'Usuários');
		if ($this->Auth->user('role') == 1) return $this->redirect(['action' => 'dashboard']);
		if ($id == null) $id = $this->Auth->user('id');

		$user = $this->Users->get($id);
		$role = $user->role;

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			$this->usuarioExistente($data['email'], $id);

			if (isset($data['role'])) unset($data['role']);
			if (isset($data['desativasecret']) && !empty($data['desativasecret'])) $user->secret = null;

			$this->Users->patchEntity($user, $data);

			if ($this->Users->save($user)) {
				if ((int)$user->role === 1) {
					RbacClientePortal::syncUserIfEligible((int)$user->id);
				}
				$this->Flash->success('As informações do usuário foram alteradas com sucesso!');
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $id);

				return $this->redirect(['action' => 'index']);
			}
			$this->Flash->error('Ocorreu um erro ao editar as informações do usuário! Tente novamente mais tarde.');
		}
		$clientesQ = $this->Clientes->find('all', ['keyField' => 'id', 'valueField' => 'razaosocial'])->where(['inativo' => 0])->order(['razaosocial']);
		$this->Abac->applyToQuery($clientesQ, 'Clientes');
		$clientes = $clientesQ->toArray();
		$clientesOpt = [];
		foreach($clientes as $reg) $clientesOpt[$reg->id] = $reg->tipo == C_ClientesTipoJuridica ? $reg->razaosocial :  $reg->nome;
		asort($clientesOpt);

		$this->set('clientes', $clientesOpt);
		$this->set('user', $user);
	}

	public function desbloquear($id = null) {
		// Verifica as permissões
		if ($this->Auth->user('admin') !== 1) {
			$this->Flash->error('Você não possui permissões para desbloquear este cliente.');
			return $this->redirect(['action' => 'dashboard']);
		}
		
		$user = $this->Users->findById($id)->first();
		$user->bloqueado = 0;
		
		if ($this->Users->save($user)) {
			$this->Flash->success('O usuário foi liberado com sucesso!');
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $id);
		} else $this->Flash->error('Ocorreu um erro ao liberar o usuário, tente novamente.');
		return $this->redirect(['action' => 'dashboard']);
	}

	/**
	 * GET ?redirect=… após login (ex.: retorno ao Service Desk). Só caminhos internos / servicedesk.
	 */
	protected function _rememberServicedeskRedirectFromQuery(): void {
		if (!$this->request->is('get') && !$this->request->is('post')) {
			return;
		}
		$raw = $this->request->getQuery('redirect');
		if ($raw === null || $raw === '') {
			return;
		}
		$target = $this->_sanitizePostLoginRedirect((string)$raw);
		if ($target !== null) {
			$this->request->getSession()->write('Auth.redirect', $target);
		}
	}

	/** Volta ao login do Service Desk após falha de autenticação (formulário embutido). */
	protected function _redirectServicedeskLoginIfEmbedded() {
		if ($this->request->getData('service_desk') !== '1' && $this->request->getData('service_desk') !== 1) {
			return null;
		}

		return $this->redirect(['controller' => 'Servicedesk', 'action' => 'index']);
	}

	/**
	 * @return array|string|null URL/array Cake ou null se inválido
	 */
	protected function _sanitizePostLoginRedirect(string $s) {
		$s = trim($s);
		if ($s === '' || strpos($s, "\n") !== false || strpos($s, "\r") !== false) {
			return null;
		}
		if (preg_match('#^(https?:)?//#i', $s) || strpos($s, '..') !== false) {
			return null;
		}
		$w = $this->request->getAttribute('webroot');
		$wTrim = rtrim((string)$w, '/');
		if ($s === 'servicedesk' || preg_match('#(^|/)servicedesk/?$#', $s)) {
			return ['controller' => 'Servicedesk', 'action' => 'index'];
		}
		if (preg_match('#(^|/)servicedesk-prototype#', $s)) {
			return $s;
		}
		if ($wTrim !== '' && $wTrim !== '/' && ($s === $wTrim . '/servicedesk' || $s === $w . 'servicedesk')) {
			return ['controller' => 'Servicedesk', 'action' => 'index'];
		}
		if ($wTrim !== '' && $wTrim !== '/' && strpos($s, $wTrim . '/servicedesk-prototype') === 0) {
			return $s;
		}
		if ($s[0] === '/' && preg_match('#^[a-zA-Z0-9/_?&=\-.]+$#', $s)) {
			if ($wTrim !== '' && $wTrim !== '/' && strpos($s, $wTrim) !== 0) {
				$s = $wTrim . $s;
			}

			return $s;
		}

		return null;
	}

	/**
	 * Redirect após Auth sem 500 por `Auth.redirect` inválido na sessão ou URL inexistente.
	 *
	 * @return \Cake\Http\Response
	 */
	protected function _safeAuthRedirectResponse(): Response {
		try {
			$target = $this->Auth->redirectUrl();
		} catch (\Throwable $e) {
			Log::warning('Auth::redirectUrl failed: ' . $e->getMessage());
			$this->request->getSession()->delete('Auth.redirect');
			$target = null;
		}
		if ($target === null || $target === '' || $target === '/') {
			$target = ['controller' => 'Users', 'action' => 'dashboard', 'prefix' => false];
		}

		return $this->redirect($target);
	}

	/**
	 * Lê usuário e senha do POST. O FormHelper pode emitir `username`/`password` ou `Users[username]`/`Users[password]`.
	 *
	 * @return array username (string), password (string|null)
	 */
	protected function _extractLoginCredentials(): array {
		$req = $this->request;
		$body = $req->getParsedBody();
		if (!is_array($body)) {
			$body = [];
		}

		$username = $req->getData('username');
		if ($username === null || $username === '') {
			$username = $req->getData('Users.username');
		}
		if ($username === null || $username === '') {
			$username = $body['username'] ?? ($body['Users']['username'] ?? null);
		}

		$password = $req->getData('password');
		if ($password === null || $password === '') {
			$password = $req->getData('Users.password');
		}
		if ($password === null || $password === '') {
			$password = $body['password'] ?? ($body['Users']['password'] ?? null);
		}
		// Fallback: em alguns ambientes getData() vem vazio com application/x-www-form-urlencoded.
		if (($password === null || $password === '') && $req->is('post')) {
			$raw = (string)$req->input();
			if ($raw !== '') {
				$parsedRaw = [];
				parse_str($raw, $parsedRaw);
				if ($username === null || $username === '') {
					$username = $parsedRaw['username'] ?? ($parsedRaw['Users']['username'] ?? null);
				}
				if ($password === null || $password === '') {
					$password = $parsedRaw['password'] ?? ($parsedRaw['Users']['password'] ?? null);
				}
			}
		}

		// Não aplicar trim na senha (espaços/$ no fim podem ser válidos; trim quebrava em alguns POSTs).
		$passwordOut = null;
		if ($password !== null && $password !== '') {
			$passwordOut = (string)$password;
		}

		return [
			'username' => strtolower(trim((string)$username)),
			'password' => $passwordOut,
		];
	}

	/**
	 * users.inativo: 0 ou NULL = ativo no portal; 1 = inativo.
	 * Aceita também boolean/string vinda do PostgreSQL/PDO.
	 */
	protected function _isUserInactiveForLogin($inativo): bool {
		if ($inativo === null || $inativo === '') {
			return false;
		}
		if (is_bool($inativo)) {
			return $inativo;
		}
		if (is_int($inativo)) {
			return $inativo !== 0;
		}
		$s = strtolower(trim((string)$inativo));

		return !in_array($s, ['0', 'f', 'false', 'no', 'off', ''], true);
	}

	/**
	 * users.bloqueado: só bloqueia quando explicitamente verdadeiro (1, t, true…).
	 * Evita tratar 'f' (boolean PG em string) como bloqueado — !$v falharia em PHP.
	 */
	protected function _isUserBlockedForLogin($bloqueado): bool {
		if ($bloqueado === null || $bloqueado === '') {
			return false;
		}
		if (is_bool($bloqueado)) {
			return $bloqueado;
		}
		if (is_int($bloqueado)) {
			return $bloqueado !== 0;
		}
		$s = strtolower(trim((string)$bloqueado));

		return in_array($s, ['1', 't', 'true', 'yes', 'on', 'sim'], true);
	}

	/**
	 * Usuários ativos que batem com o login digitado (e-mail ou username).
	 * Ordem fixa por id: importante quando há e-mails duplicados na base.
	 *
	 * @param string $login
	 * @return \App\Model\Entity\User[]
	 */
	protected function _findActiveUsersForLogin($login): array {
		$login = trim((string)$login);
		if ($login === '') {
			return [];
		}

		try {
			$conn = $this->Users->getConnection();
			$loginMatch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $login);

			return $this->Users->find()
				->where($this->Users->loginActiveInativoCondition())
				->where([
					'OR' => array_merge(
						FiscalSqlConditions::caseInsensitiveLike($conn, 'Users.email', $loginMatch),
						FiscalSqlConditions::caseInsensitiveLike($conn, 'Users.username', $loginMatch)
					),
				])
				->order(['Users.id' => 'ASC'])
				->toArray();
		} catch (\Throwable $e) {
			Log::error('_findActiveUsersForLogin: ' . $e->getMessage(), ['exception' => $e]);

			return [];
		}
	}

	/**
	 * Localiza um usuário ativo pelo login (primeiro id em caso de duplicidade).
	 * Para autenticação use {@see _identifyUserByCredentials()}.
	 *
	 * @param string $login
	 * @return \Cake\Datasource\EntityInterface|null
	 */
	protected function _findActiveUserForLogin($login) {
		$users = $this->_findActiveUsersForLogin($login);

		return !empty($users) ? $users[0] : null;
	}

	/**
	 * Autentica login+senha percorrendo todos os candidatos ativos.
	 * Evita falha quando há dois users com o mesmo e-mail: Auth->identify() podia validar outra linha que a de ->first().
	 *
	 * Compara senha em texto com hash armazenado (bcrypt via DefaultPasswordHasher + legado sha1/md5).
	 */
	protected function _passwordMatchesForLogin(string $plain, string $storedHash): bool {
		$storedHash = (string)$storedHash;
		if ($storedHash === '') {
			return false;
		}
		if ((new DefaultPasswordHasher())->check($plain, $storedHash)) {
			return true;
		}
		if (strlen($storedHash) === 40 && ctype_xdigit($storedHash)) {
			return hash_equals(strtolower($storedHash), sha1($plain));
		}
		if (strlen($storedHash) === 32 && ctype_xdigit($storedHash)) {
			return hash_equals(strtolower($storedHash), md5($plain));
		}

		return false;
	}

	/**
	 * Diagnóstico sem logar senha (suporte produção).
	 */
	protected function _diagnoseLoginRejectReason(string $login, $password): string {
		$candidates = $this->_findActiveUsersForLogin($login);
		if ($candidates === []) {
			return 'no_user';
		}
		$plain = (string)$password;
		foreach ($candidates as $entity) {
			if ($this->_passwordMatchesForLogin($plain, (string)$entity->get('password'))) {
				return 'hash_ok_user_id_' . $entity->get('id');
			}
		}

		return 'hash_mismatch';
	}

	protected function _identifyUserByCredentials(string $login, $password): ?array {
		if ($password === null || $password === '') {
			return null;
		}
		$plain = (string)$password;
		$candidates = $this->_findActiveUsersForLogin($login);
		foreach ($candidates as $entity) {
			if ($this->_passwordMatchesForLogin($plain, (string)$entity->get('password'))) {
				return $entity->toArray();
			}
		}
		// Fallback: Auth\Form (mapeia username → users.email), alinhado ao login legado.
		foreach ($candidates as $entity) {
			$authLogin = trim((string)$entity->get('email'));
			if ($authLogin === '') {
				$authLogin = trim((string)$entity->get('username'));
			}
			if ($authLogin === '') {
				continue;
			}
			$identified = $this->Auth->identify([
				'username' => $authLogin,
				'password' => $plain,
			]);
			if ($identified) {
				return $identified;
			}
		}

		return null;
	}

	/** Texto exibido no e-mail de reset (bloco de validade do link). */
	protected const PASSWORD_RESET_EXPIRATION_EMAIL_TEXT = '10 minutos ou até a primeira utilização bem-sucedida do link';

	/**
	 * Link de reset com hashreset_expires definido expirou (10 minutos).
	 * Tokens sem data (NULL) mantêm compatibilidade com links antigos.
	 */
	protected function _isPasswordResetLinkExpired($user): bool {
		$exp = $user->get('hashreset_expires');
		if ($exp === null || $exp === '') {
			return false;
		}
		if ($exp instanceof \DateTimeInterface) {
			return $exp->getTimestamp() < FrozenTime::now()->getTimestamp();
		}

		return false;
	}

	/**
	 * Remove hash de reset expirado para o link deixar de ser reutilizado.
	 */
	protected function _invalidateExpiredPasswordResetToken($user): void {
		$user->set('hashreset', null);
		$user->set('hashreset_expires', null);
		$this->Users->save($user);
	}

	/**
	 * Rota de login pós-reset (convidado): equipe → acessoEmpresa; cliente → login.
	 * Usa users.role; se ambíguo, ?from=empresa na URL (link do e-mail ou tela de equipe).
	 *
	 * @param \Cake\Datasource\EntityInterface|null $user
	 * @return array<string, string> URL array para Router::url / redirect
	 */
	protected function _guestLoginRouteAfterPasswordReset($user = null): array {
		if ($user !== null) {
			$role = $user->get('role');
			if ($role !== null && $role !== '') {
				if ((int)$role === (int)C_RoleFuncionario) {
					return ['controller' => 'Users', 'action' => 'acessoEmpresa'];
				}
				if ((int)$role === (int)C_RoleCliente) {
					return ['controller' => 'Users', 'action' => 'login'];
				}
			}
		}
		if ($this->request->getQuery('from') === 'empresa') {
			return ['controller' => 'Users', 'action' => 'acessoEmpresa'];
		}

		return ['controller' => 'Users', 'action' => 'login'];
	}

	/**
	 * Após solicitar reset de senha (sem sessão): volta ao login certo — equipe (acesso-empresa) ou cliente (login).
	 *
	 * @param \Cake\Datasource\EntityInterface|null $user
	 */
	protected function _redirectGuestAfterPasswordReset($user = null) {
		return $this->redirect($this->_guestLoginRouteAfterPasswordReset($user));
	}

	/**
	 * Portal do cliente — URL típica: /portal/users/login
	 * Espera users.role = C_RoleCliente (1). Equipe (role 0) deve usar acessoEmpresa().
	 */
	public function login() {
		$this->viewBuilder()->setLayout("login");
		$this->_rememberServicedeskRedirectFromQuery();

		if ($this->Auth->user()) {
			return $this->_safeAuthRedirectResponse();
		}
	
		if ($this->request->is('post')) {
			$creds = $this->_extractLoginCredentials();
			$user = $this->_identifyUserByCredentials($creds['username'], $creds['password']);
			
			if ($user) {
				// Só clientes (role = C_RoleCliente) podem logar aqui. Qualquer outro role é rejeitado.
				if (!isset($user['role']) || (int)$user['role'] !== (int)C_RoleCliente) {
					$this->Flash->error(__('Este acesso é para clientes. Use o link "Acesso PGM / Master" para entrar com usuário da equipe.'));
					$r = $this->_redirectServicedeskLoginIfEmbedded();
					if ($r !== null) {
						return $r;
					}
					return $this->redirect(['action' => 'acessoEmpresa']);
				}
				if (!$this->_isUserInactiveForLogin($user['inativo'] ?? null) && !$this->_isUserBlockedForLogin($user['bloqueado'] ?? null)) {
					try {
						$user['idempresa'] = $this->getEmpresaPreferencial($user['id']);
					} catch (\Throwable $e) {
						$user['idempresa'] = null;
					}
					$this->Auth->setUser($user);
					return $this->_safeAuthRedirectResponse();
				} elseif ($this->_isUserInactiveForLogin($user['inativo'] ?? null)) {
					$this->Flash->error(__('Seu usuário está inativo!'));
					return $this->_safeAuthRedirectResponse();
				} elseif ($this->_isUserBlockedForLogin($user['bloqueado'] ?? null)) {
					$this->Flash->error(__('Seu usuário está bloqueado! Aguarde a liberação de um administrador.'));
					return $this->_safeAuthRedirectResponse();
				}
			}
	
			$this->Flash->error(__('Usuário e/ou senha incorretos. Tente novamente.'));
			$r = $this->_redirectServicedeskLoginIfEmbedded();
			if ($r !== null) {
				return $r;
			}
		}
	}

	/**
	 * Equipe PGM/Master — URL típica: /portal/users/acesso-empresa
	 * Espera users.role = C_RoleFuncionario (0). Clientes (role 1) devem usar login().
	 */
	public function acessoEmpresa() {
		try {
			$this->viewBuilder()->setLayout("login");
			$this->viewBuilder()->setTemplate("login_empresa");
			$this->_rememberServicedeskRedirectFromQuery();

			if ($this->Auth->user()) {
				return $this->_safeAuthRedirectResponse();
			}

			if ($this->request->is('post')) {
				$creds = $this->_extractLoginCredentials();
				if ($creds['password'] === null || $creds['password'] === '') {
					$this->Flash->error(__('Informe a senha.'));
				} else {
				$user = $this->_identifyUserByCredentials($creds['username'], $creds['password']);

				if ($user) {
					// Só equipe PGM/Master (role = C_RoleFuncionario) pode logar aqui. Qualquer outro role é rejeitado.
					if (!isset($user['role']) || (int)$user['role'] !== (int)C_RoleFuncionario) {
						$this->Flash->error(__('Este acesso é para a equipe PGM / Master. Use o acesso para clientes.'));
						$r = $this->_redirectServicedeskLoginIfEmbedded();
						if ($r !== null) {
							return $r;
						}

						return $this->redirect(['action' => 'login']);
					}
					if (!$this->_isUserInactiveForLogin($user['inativo'] ?? null) && !$this->_isUserBlockedForLogin($user['bloqueado'] ?? null)) {
						try {
							$user['idempresa'] = $this->getEmpresaPreferencial($user['id']);
						} catch (\Throwable $e) {
							$user['idempresa'] = null;
						}
						$this->Auth->setUser($user);

						return $this->_safeAuthRedirectResponse();
					}
					if ($this->_isUserInactiveForLogin($user['inativo'] ?? null)) {
						$this->Flash->error(__('Seu usuário está inativo!'));

						return $this->redirect(['action' => 'acessoEmpresa']);
					}
					if ($this->_isUserBlockedForLogin($user['bloqueado'] ?? null)) {
						$this->Flash->error(__('Seu usuário está bloqueado! Aguarde a liberação de um administrador.'));

						return $this->redirect(['action' => 'acessoEmpresa']);
					}
				}

				if (empty($user)) {
					$reason = $this->_diagnoseLoginRejectReason($creds['username'], $creds['password']);
					$pwd = $creds['password'];
					$pwdMeta = '';
					if ($pwd !== null && $pwd !== '') {
						$pwdMeta = sprintf(
							' has_dollar=%d ord_first=%d ord_last=%d pwd_fp=%s',
							strpos($pwd, '$') !== false ? 1 : 0,
							ord($pwd[0]),
							ord($pwd[strlen($pwd) - 1]),
							substr(hash('sha256', $pwd), 0, 12)
						);
					}
					$candidateIds = [];
					foreach ($this->_findActiveUsersForLogin($creds['username']) as $cand) {
						$candidateIds[] = (string)$cand->get('id');
					}
					$postKeys = '';
					$parsedBody = $this->request->getParsedBody();
					if (is_array($parsedBody)) {
						$postKeys = implode(',', array_keys($parsedBody));
					}
					$dbName = (string)($this->Users->getConnection()->config()['database'] ?? '');
					Log::warning(sprintf(
						'acessoEmpresa: credenciais rejeitadas login=%s password_len=%d reason=%s candidate_ids=%s db=%s post_keys=%s%s',
						$creds['username'],
						$pwd !== null ? strlen($pwd) : 0,
						$reason,
						$candidateIds === [] ? '-' : implode(',', $candidateIds),
						$dbName !== '' ? $dbName : '-',
						$postKeys !== '' ? $postKeys : '-',
						$pwdMeta
					));
					$this->Flash->error(__('Usuário e/ou senha incorretos. Tente novamente.'));
				}
				$r = $this->_redirectServicedeskLoginIfEmbedded();
				if ($r !== null) {
					return $r;
				}
				}
			}
		} catch (\Throwable $e) {
			Log::error('acessoEmpresa: ' . $e->getMessage(), ['exception' => $e]);
			$this->Flash->error(__('Não foi possível concluir o login. Tente novamente ou contate o suporte.'));
		}
	}

	private function getEmpresaPreferencial($userId) {
		$relacoes = $this->Empresasusers->find('all')
			->where(['iduser' => $userId])
			->contain(['Empresas' => ['fields' => ['Empresas.id', 'Empresas.nomefantasia']]])
			->order(['Empresas.id' => 'ASC'])
			->toArray();
		
		// Equipe: prioriza vínculo com empresa PGM (C_EmpresaPGM)
		$user = $this->Users->get($userId);
		if ((int)$user->role === (int)C_RoleFuncionario) {
			foreach ($relacoes as $rel) {
				if (!empty($rel->empresa) && (int)$rel->empresa->id === (int)C_EmpresaPGM) {
					return (int)$rel->empresa->id;
				}
			}
		}
		
		// Primeira empresa do vínculo (ordem por id da empresa)
		if (!empty($relacoes[0]->idempresa)) {
			return (int)$relacoes[0]->idempresa;
		}
		return null;
	}

	public function loginempresa($string) {
		// Agora essa função é o ajax que retorna a lista, ela pega pela $string que é o login que vai ser enviado pelo ajax
		$this->viewBuilder()->setLayout("login");
		$this->autoRender = false;
		$user = $this->Users->find('all')->where(['username' => $string])->first();

		// Usa o contain pra fazer o join da empresasusers com a empresas, aí tem tudo no mesmo array
		$relacoes = $this->Empresasusers->find('all')->where(['iduser' => $user['id']])->contain(['Empresas' => ['fields' => ['Empresas.id', 'Empresas.nomefantasia']]])->toArray();

		// Aqui arruma a lista certo
		foreach($relacoes as $rel){
			$lista[$rel->empresa->id] = $rel->empresa->nomefantasia;
		}
		
		if(isset($lista) and sizeof($lista) > 0){
			// Isso escreve o json com a lista pra formar depois no html com o jquery o select com as options
			//echo json_encode($lista, JSON_PRETTY_PRINT);
			return $this->jsonResponse($lista, 200);
		}
	}

	public function logout() {
		$this->Ordensservico->limpacarrinho();
		$this->Orcamentos->limpacarrinho();
		$redirectEmpresa = $this->Auth->user()
			&& (int)$this->Auth->user('role') === (int)C_RoleFuncionario;
		$this->Auth->logout();
		if ($redirectEmpresa) {
			return $this->redirect(['action' => 'acessoEmpresa']);
		}

		return $this->redirect(['action' => 'login']);
	}

	public function isAuthorized($user) {
		// Reutiliza as regras padrão do AppController (inclui verificação de prefixo admin)
		return parent::isAuthorized($user);
	}

	public function delete($id = null) {
		if ($this->Auth->user('role') == 1) {
			return $this->redirect(['action' => 'dashboard']);
		}

		$user = $this->Users->get($id);
		$osSchema = $this->Ordensservico->getSchema();
		$bloqueios = [];

		if ($osSchema->hasColumn('iduser')) {
			$nOsUsuario = $this->Ordensservico->find()->where(['iduser' => $user->id])->count();
			if ($nOsUsuario > 0) {
				$amostraOs = $this->Ordensservico->find()
					->select(['id'])
					->where(['iduser' => $user->id])
					->order(['id' => 'DESC'])
					->limit(8)
					->all()
					->extract('id')
					->toList();
				$amostraTxt = $amostraOs !== [] ? ' Exemplos de número de OS: ' . implode(', ', $amostraOs) . '.' : '';
				$bloqueios[] = 'Motivo: existem ' . $nOsUsuario . ' ordem(ns) de serviço em que este usuário está vinculado como técnico/responsável (campo da OS). '
					. 'Transfira essas ordens para outro usuário ou ajuste o cadastro antes de excluir.' . $amostraTxt;
			}
		}

		$ticketIds = $this->Tickets->find('list', [
			'keyField' => 'id',
			'valueField' => 'id',
		])->where(['idautor' => $user->id])->toArray();
		$ticketIds = array_values($ticketIds);

		if ($ticketIds !== [] && $osSchema->hasColumn('idticket')) {
			$nOsTicket = $this->Ordensservico->find()->where(['idticket IN' => $ticketIds])->count();
			if ($nOsTicket > 0) {
				$ticketsAmostra = $this->Ordensservico->find('list', [
					'keyField' => 'idticket',
					'valueField' => 'idticket',
				])->where(['idticket IN' => $ticketIds])->toArray();
				$ticketsAmostra = array_slice(array_unique(array_map('intval', array_values($ticketsAmostra))), 0, 8);
				$ticketsTxt = $ticketsAmostra !== [] ? ' Tickets envolvidos (amostra): ' . implode(', ', $ticketsAmostra) . '.' : '';
				$bloqueios[] = 'Motivo: existem ' . $nOsTicket . ' ordem(ns) de serviço vinculadas a ticket(s) dos quais este usuário é o autor (campo ticket→OS). '
					. 'Remova ou altere o vínculo entre essas OS e os tickets antes de excluir o usuário.' . $ticketsTxt;
			}
		}

		if ($bloqueios !== []) {
			$this->Flash->error('Não foi possível excluir o usuário. ' . implode(' ', $bloqueios));

			return $this->redirect(['action' => 'index']);
		}

		$conn = $this->Users->getConnection();

		try {
			$conn->transactional(function () use ($user) {
				if (!$this->Users->delete($user)) {
					throw new \RuntimeException('Não foi possível excluir o usuário.');
				}
			});

			$this->Flash->success('O usuário foi deletado com sucesso!');
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
			if ($this->Auth->user('id') == $id) {
				return $this->redirect(['action' => 'logout']);
			}

			return $this->redirect(['action' => 'index']);
		} catch (\PDOException $e) {
			$msg = $e->getMessage();
			if (strpos($msg, '23503') !== false || stripos($msg, 'foreign key') !== false) {
				$this->Flash->error(
					'Não foi possível excluir o usuário. Motivo: ainda há registros vinculados no banco de dados '
					. '(regra de integridade — por exemplo tickets, comentários ou outras tabelas). '
					. 'Verifique vínculos desse usuário antes de tentar novamente.'
				);
			} else {
				$this->Flash->error('Erro ao excluir usuário. Tente novamente ou contate o suporte.');
			}

			return $this->redirect(['action' => 'index']);
		} catch (\Exception $e) {
			$this->Flash->error($e->getMessage());

			return $this->redirect(['action' => 'index']);
		}
	}

	public function changeProfile($id = null) {
		if ($this->Auth->user('role') == 1 or $id == null) {
			$id = $this->Auth->user('id');
		}
		$user = $this->Users->get($id);
		$avatarCache = [];
		$fotoPerfilUrl = \App\Service\Ticket\TicketServiceDeskApiService::userAvatarPublicPath((int)$id, $avatarCache);
		$this->set('fotoPerfilUrl', $fotoPerfilUrl);

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();

			if (!empty($data['remover_foto'])) {
				$this->_deleteUserAvatarFiles((int)$id);
			}
			$file = isset($data['foto_perfil']) && is_array($data['foto_perfil']) ? $data['foto_perfil'] : null;
			if ($file !== null && !empty($file['tmp_name']) && (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
				$errFoto = $this->_saveUserAvatarFromUpload((int)$id, $file);
				if ($errFoto !== null) {
					$this->Flash->error($errFoto);
				}
			} elseif ($file !== null && isset($file['error']) && (int)$file['error'] !== UPLOAD_ERR_NO_FILE && (int)$file['error'] !== UPLOAD_ERR_OK) {
				$this->Flash->error('Erro ao enviar a foto. Tente novamente.');
			}

			$email = isset($data['email']) ? (string)$data['email'] : '';
			$this->usuarioExistente($email, $id);

			if (isset($data['role'])) {
				unset($data['role']);
			}
			unset($data['foto_perfil'], $data['remover_foto']);

			$this->Users->patchEntity($user, $data);

			if ($this->Users->save($user)) {
				$this->Flash->success('O perfil foi alterado com sucesso!!');
				$this->Atividades->registrar(
					$this->Auth->user('id'),
					$this->request->getParam('controller'),
					$this->request->getParam('action'),
					$id
				);

				return $this->redirect(['action' => 'dashboard']);
			}

			$this->Flash->error('Ocorreu um erro ao salvar os dados!');
		}

		$this->set('user', $user);
		$this->set('title', 'Alterar Perfil');
	}

	public function changePassword($id = null) {
		$this->set('title', 'Alterar Senha');

		if ($this->Auth->user('role') == 1 or $id == null) $id = $this->Auth->user('id');

		$user = $this->Users->get($id);

		if (!empty($this->request->data)) {
			$user = $this->Users->patchEntity($user, [
				'old_password'  => $this->request->data['old_password'],
				'password'      => $this->request->data['password1'],
				'password1'     => $this->request->data['password1'],
				'password2'     => $this->request->data['password2']
			],
			['validate' => 'password']);

			if ($this->Users->save($user)) {
				$this->Flash->success('A senha foi alterada com sucesso!');

				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $id);

				if ($this->Auth->user('id') == $id) return $this->redirect(['action' => 'logout']);
				else return $this->redirect(['action' => 'dashboard']);
			} else $this->Flash->error('Ocorreu um erro ao salvar a senha!');
		}

		$this->set('user', $user);
	}

	public function changePasswordAdmin($id = null) {
		$this->set('title', 'Alterar Senha');
		
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Seu usuário não possui permissão para acessar este recurso!');
			return $this->redirect(['controller' => 'tickets', 'action' => 'indexcliente']);
		} else if ($id == $this->Auth->user('id')) {
			$this->Flash->error('Você não pode resetar sua própria senha por esta página! Por favor, acesse a troca de senha individual do usuário.');
			return $this->redirect(['action' => 'index']);
		}

		$user = $this->Users->get($id);

		if ($user->role == 0) {
			$this->Flash->error('Você não pode resetar a senha de administradores do sistema por esta página.');
			return $this->redirect(['action' => 'index']);
		}

		if (!empty($this->request->data)) {
			$user = $this->Users->patchEntity($user, [
				'password'      => $this->request->data['password1'],
				'password1'     => $this->request->data['password1'],
				'password2'     => $this->request->data['password2']
			],
			['validate' => 'passwordTecnico']);

			if ($this->Users->save($user)) {
				$this->Flash->success("A senha do usuário $user->username foi alterada com sucesso!");

				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $id);

				return $this->redirect(['action' => 'index']);
			} else $this->Flash->error('Ocorreu um erro ao salvar a senha!');
		}

		$this->set('user', $user);
	}

	public function atendimentosSemana() {
		$inicio = \primeiroDiaSemana();
		$fim = \ultimoDiaSemana();

		$this->set('dataini', $inicio);
		$this->set('datafim', $fim);

		$atendimentos = [];
		$dataYmd = $this->_dataBrParaDb($inicio);
		$fimYmd = $this->_dataBrParaDb($fim);
		if (!is_string($dataYmd) || !is_string($fimYmd)
			|| !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataYmd) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fimYmd)) {
			return $atendimentos;
		}
		$t = strtotime($dataYmd . ' 00:00:00');
		$tEnd = strtotime($fimYmd . ' 23:59:59');
		if ($t === false || $tEnd === false) {
			return $atendimentos;
		}
		while ($t <= $tEnd) {
			$atendimentos[] = $this->Atendimentos->nroAtendimentosDia(date('Y-m-d', $t));
			$t = strtotime('+1 day', $t);
		}

		return $atendimentos;
	}

	public function privacyPolicy() {
		$this->viewBuilder()->setLayout("privacy_policy");
		$this->set('title', 'Políticas de Privacidade');
	}

	public function selectTemplate(){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');

		if ($this->request->is('ajax')) {
	 		$user = $this->Users->get($this->Auth->user('id'));
		 	$user->skin = $this->request->data['skin'];
		 	$this->Users->save($user);
		}
	}

	public function selectTheme() {
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');
		$this->request->allowMethod(['post']);

		if (!$this->Auth->user('id')) {
			return $this->jsonResponse(['ok' => false, 'error' => 'auth'], 403);
		}

		// POST sem _Token de formulário (Security unlocked); CSRF obrigatório como em verificadadoscliente.
		$posted = (string)$this->request->getData('_csrfToken');
		$trusted = (string)($this->request->getAttribute('csrfToken') ?: $this->request->getParam('_csrfToken'));
		if ($posted === '' || $trusted === '' || !hash_equals($trusted, $posted)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'csrf'], 403);
		}

		$theme = $this->request->getData('theme');
		if ($theme !== 'light' && $theme !== 'dark') {
			return $this->jsonResponse(['ok' => false, 'error' => 'theme'], 400);
		}

		// Portal em tema escuro único; mantém compatibilidade com POST antigo (light/dark).
		$skin = 'skin-green';
		$user = $this->Users->get($this->Auth->user('id'));
		$user->skin = $skin;
		$this->Users->save($user);
		$u = $this->Auth->user();
		if (is_array($u)) {
			$u['skin'] = $skin;
			$this->Auth->setUser($u);
		}

		return $this->jsonResponse(['ok' => true]);
	}

	/**
	 * Gera chave de auditoria em claro (uma vez); só deve ser mostrada ao admin e depois só existe o hash na base.
	 */
	protected function _generateAuditPasswordPlain(): string {
		return bin2hex(Security::randomBytes(16));
	}

	/**
	 * Administrador pode atribuir senha de auditoria ao alvo (mesma empresa).
	 *
	 * @param \Cake\Datasource\EntityInterface|array $target
	 */
	protected function _isSystemAdminUser(): bool {
		$v = $this->Auth->user('admin');
		$r = false;
		if (is_bool($v)) {
			$r = $v;
			// #region agent log
			$this->_debugAuditLog('H2', 'UsersController::_isSystemAdminUser bool branch', [
				'adminRawType' => gettype($v),
				'adminRawValue' => $v ? 'true' : 'false',
				'isSystemAdmin' => $r,
			]);
			// #endregion
			return $r;
		}
		if (is_int($v)) {
			$r = ($v === 1);
			// #region agent log
			$this->_debugAuditLog('H2', 'UsersController::_isSystemAdminUser int branch', [
				'adminRawType' => gettype($v),
				'adminRawValue' => (string)$v,
				'isSystemAdmin' => $r,
			]);
			// #endregion
			return $r;
		}
		$s = strtolower(trim((string)$v));
		$r = in_array($s, ['1', 't', 'true', 'yes', 'on', 'sim'], true);
		// #region agent log
		$this->_debugAuditLog('H2', 'UsersController::_isSystemAdminUser string branch', [
			'adminRawType' => gettype($v),
			'adminRawValue' => $s,
			'isSystemAdmin' => $r,
		]);
		// #endregion

		return $r;
	}

	/**
	 * Administrador pode atribuir senha de auditoria ao alvo (mesma empresa).
	 *
	 * @param \Cake\Datasource\EntityInterface|array $target
	 */
	protected function _userAdminMaySetAuditPasswordForUser($target): bool {
		$adminEmpresaId = (int)$this->Auth->user('idempresa');
		$isSystemAdmin = $this->_isSystemAdminUser();
		if ($isSystemAdmin) {
			// #region agent log
			$this->_debugAuditLog('H3', 'UsersController::_userAdminMaySetAuditPasswordForUser allowed by system admin', [
				'adminEmpresaId' => $adminEmpresaId,
				'targetEmpresaId' => (int)(is_object($target) ? $target->get('idempresa') : ($target['idempresa'] ?? 0)),
				'isSystemAdmin' => true,
				'allowed' => true,
			]);
			// #endregion
			return true;
		}
		$tid = (int)(is_object($target) ? $target->get('idempresa') : ($target['idempresa'] ?? 0));
		if ($tid < 1) {
			$targetUserId = (int)(is_object($target) ? $target->get('id') : ($target['id'] ?? 0));
			if ($targetUserId > 0) {
				$linkEmpresa = $this->Empresasusers->find()
					->select(['idempresa'])
					->where(['iduser' => $targetUserId])
					->order(['idempresa' => 'ASC'])
					->first();
				if ($linkEmpresa) {
					$tid = (int)$linkEmpresa->idempresa;
				}
			}
		}
		if ($tid < 1) {
			// #region agent log
			$this->_debugAuditLog('H3', 'UsersController::_userAdminMaySetAuditPasswordForUser invalid target empresa', [
				'adminEmpresaId' => $adminEmpresaId,
				'targetEmpresaId' => $tid,
				'allowed' => false,
			]);
			// #endregion
			return false;
		}

		// Multiempresas: admins da PGM/Master podem gerir senha de auditoria entre essas empresas.
		$isAdminGlobalPgmMaster = in_array($adminEmpresaId, [(int)C_EmpresaPGM, (int)C_EmpresaMaster], true);
		$isTargetPgmMaster = in_array($tid, [(int)C_EmpresaPGM, (int)C_EmpresaMaster], true);
		if ($isAdminGlobalPgmMaster && $isTargetPgmMaster) {
			// #region agent log
			$this->_debugAuditLog('H3', 'UsersController::_userAdminMaySetAuditPasswordForUser allowed by pgm/master cross-company', [
				'adminEmpresaId' => $adminEmpresaId,
				'targetEmpresaId' => $tid,
				'isAdminGlobalPgmMaster' => $isAdminGlobalPgmMaster,
				'isTargetPgmMaster' => $isTargetPgmMaster,
				'allowed' => true,
			]);
			// #endregion
			return true;
		}
		$allowedSameEmpresa = ($tid === $adminEmpresaId);
		// #region agent log
		$this->_debugAuditLog('H3', 'UsersController::_userAdminMaySetAuditPasswordForUser fallback same-company decision', [
			'adminEmpresaId' => $adminEmpresaId,
			'targetEmpresaId' => $tid,
			'isSystemAdmin' => $isSystemAdmin,
			'isAdminGlobalPgmMaster' => $isAdminGlobalPgmMaster,
			'isTargetPgmMaster' => $isTargetPgmMaster,
			'allowed' => $allowedSameEmpresa,
		]);
		// #endregion

		return $allowedSameEmpresa;
	}

	protected function _debugAuditLog(string $hypothesisId, string $message, array $data = []): void {
		try {
			$runId = (string)$this->request->getQuery('dbg_runid');
			if ($runId === '') {
				$runId = 'run-' . date('YmdHis');
			}
			$payload = [
				'sessionId' => '9b064b',
				'runId' => $runId,
				'hypothesisId' => $hypothesisId,
				'location' => 'src/Controller/UsersController.php',
				'message' => $message,
				'data' => $data,
				'timestamp' => (int)round(microtime(true) * 1000),
			];
			$line = json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL;
			file_put_contents('c:\\Portal-git\\portal-teste\\debug-9b064b.log', $line, FILE_APPEND);
			Log::info('[AUDIT_DEBUG_9b064b] ' . $line);
		} catch (\Throwable $e) {
			try {
				Log::error('[AUDIT_DEBUG_9b064b_ERROR] ' . $e->getMessage());
			} catch (\Throwable $e2) {
			}
		}
	}

	/**
	 * Define hash da senha de auditoria na entidade (gravar com save()).
	 *
	 * @param \Cake\Datasource\EntityInterface $user
	 */
	protected function _applyAuditPasswordHashToUser($user, string $plain): void {
		$hasher = new DefaultPasswordHasher();
		$user->set('audit_password_hash', $hasher->hash($plain));
	}

	/**
	 * Envia ao usuário a chave de auditoria recém-gerada.
	 *
	 * @param \Cake\Datasource\EntityInterface $user
	 * @return bool true quando enviado; false quando não enviado/falhou.
	 */
	protected function _sendAuditPasswordEmailToUser($user, string $plain): bool {
		$to = trim((string)$user->get('email'));
		if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
			return false;
		}
		$idempresaEmail = (int)$user->get('idempresa');
		if ($idempresaEmail < 1) {
			$idempresaEmail = (int)$this->Auth->user('idempresa');
		}
		if ($idempresaEmail < 1) {
			$idempresaEmail = (int)C_EmpresaPGM;
		}
		$nome = trim((string)$user->get('name'));
		if ($nome === '') {
			$nome = trim((string)$user->get('username'));
		}
		$from = 'helpdesk@pgm.inf.br';
		$nomeSafe = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
		$plainSafe = htmlspecialchars($plain, ENT_QUOTES, 'UTF-8');
		$saudacao = $nome !== '' ? 'Olá, ' . $nomeSafe . '.' : 'Olá.';
		$message = '<p>' . $saudacao . '</p>'
			. '<p>Sua senha de auditoria do Service Desk foi gerada/atualizada.</p>'
			. '<p><strong>Senha de auditoria:</strong> <code>' . $plainSafe . '</code></p>'
			. '<p>Guarde esta chave em local seguro. Ela é usada apenas para ajuste de auditoria de tempo e é diferente da senha de login.</p>';

		return $this->_sendEmailWithTransportFallback(
			$to,
			[$from => 'PGM'],
			'Senha de auditoria do Service Desk',
			$message,
			$idempresaEmail
		);
	}

	/**
	 * Admin: gera nova chave de auditoria (hash gravado; chave em claro devolvida uma vez no JSON).
	 * Corpo: { "userId": 1, "generate": true } (ou regenerate: true)
	 */
	public function apiSetUserAuditPassword() {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		if (!$this->_isSystemAdminUser()) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden', 'message' => 'Apenas administrador.'], 403);
		}
		$body = $this->request->input('json_decode', true);
		if (!is_array($body)) {
			$body = $this->request->getData();
		}
		if (isset($body['auditPassword']) || isset($body['audit_password'])) {
			return $this->jsonResponse([
				'ok' => false,
				'error' => 'invalid',
				'message' => 'A chave é apenas gerada pelo sistema. Envie {"userId": N, "generate": true}.',
			], 400);
		}
		$targetId = (int)($body['userId'] ?? $body['user_id'] ?? 0);
		$doGen = !empty($body['generate']) || !empty($body['regenerate']);
		if ($targetId < 1 || !$doGen) {
			return $this->jsonResponse([
				'ok' => false,
				'error' => 'invalid',
				'message' => 'Informe userId e generate: true (a chave não pode ser escolhida manualmente).',
			], 400);
		}
		try {
			$target = $this->Users->get($targetId);
		} catch (RecordNotFoundException $e) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_userAdminMaySetAuditPasswordForUser($target)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden', 'message' => 'Usuário de outra empresa.'], 403);
		}
		$plain = $this->_generateAuditPasswordPlain();
		$this->_applyAuditPasswordHashToUser($target, $plain);
		if ($this->Users->save($target)) {
			return $this->jsonResponse([
				'ok' => true,
				'generated_password' => $plain,
				'message' => 'Guarde esta chave; não será possível recuperá-la. Gere nova se for perdida.',
			]);
		}

		return $this->jsonResponse(['ok' => false, 'error' => 'save_failed'], 500);
	}

	public function selectSidebar(){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');

		if ($this->request->is('ajax')) {
			$user = $this->Users->get($this->Auth->user('id'));
			$user->sidebar = $this->request->data['sidebar'];
			$this->Users->save($user);
		}
	}
	
	public function pagelength(){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');

		if ($this->request->is('ajax')) {
			$user = $this->Users->get($this->Auth->user('id'));
			$user->pagelength = $this->request->data['len'];
			$this->Users->save($user);
		}
	}

	public function cadastrocliente() {
		$this->viewBuilder()->setLayout("cadastrocliente");
		
		$user = $this->Users->newEntity();
		$empresasCadastro = $this->Empresas
			->find('list', ['keyField' => 'id', 'valueField' => 'nomefantasia'])
			->order(['nomefantasia' => 'ASC'])
			->toArray();
		$this->set('empresasCadastro', $empresasCadastro);
		
		if ($this->request->is('post')) {
			$data = $this->request->getData();
			// No auto-cadastro o usuário normalmente não está logado.
			// Então exigimos que ele selecione a empresa (PGM/Master) na tela.
			$empresaIdAtual = !empty($this->Auth->user('idempresa')) ? (int)$this->Auth->user('idempresa') : null;
			$empresaIdCadastro = !empty($data['idempresa_cadastro']) ? (int)$data['idempresa_cadastro'] : null;
			$empresaId = $empresaIdAtual ?: $empresaIdCadastro;
			if (empty($empresaId) || $empresaId <= 0) {
				$this->Flash->error(__('Selecione a empresa para concluir o cadastro.'));
				return $this->redirect(['controller' => 'users', 'action' => 'login']);
			}
			
			// Normalização do e-mail
			$data['email1'] = strtolower(trim($data['email1'])); 

			if (!filter_var($data['email1'], FILTER_VALIDATE_EMAIL)) {
				$this->Flash->error(__('O e-mail fornecido não é válido!'));
				return $this->redirect(['controller' => 'users', 'action' => 'login']);
			}

			$cliente = null;

			$cnpj = \removeCaracteres(trim((string)($data['cnpj'] ?? '')));
			$cpf = \removeCaracteres(trim((string)($data['cpfcliente'] ?? '')));

			if (!empty($cnpj)) {
				$cliente = $this->Clientes
					->findByCnpj($cnpj)
					->where(['idempresa' => $empresaId])
					->first();
			}
			if(empty($cliente)) {
				if (!empty($cpf)) {
					$cliente = $this->Clientes
						->findByCpf($cpf)
						->where(['idempresa' => $empresaId])
						->first();
				}
			}

			if(empty($cliente)) {
				// Auto-cadastro: cria o cliente automaticamente na empresa selecionada.
				$cliente = $this->Clientes->newEntity();
				$tipoCliente = (int)($data['tipo'] ?? 0);

				$cliente->idempresa = $empresaId;
				$cliente->empresadominante = $empresaId;
				$cliente->tipo = $tipoCliente;
				$cliente->inativo = 0;
				$cliente->fone = (string)($data['fone'] ?? '');

				if ($tipoCliente === C_ClientesTipoJuridica) {
					$cliente->cnpj = $cnpj;
					$cliente->razaosocial = trim((string)($data['razaosocial'] ?? ''));
					$cliente->nomefantasia = trim((string)($data['razaosocial'] ?? ''));
					if (empty($cliente->cnpj) || empty($cliente->razaosocial)) {
						$this->Flash->error(__('Informe CNPJ e Nome da Empresa para concluir o cadastro.'));
						return $this->redirect(['controller' => 'users', 'action' => 'login']);
					}
				} else {
					$cliente->cpf = $cpf;
					$cliente->nome = trim((string)($data['nomecliente'] ?? ''));
					if (empty($cliente->cpf) || empty($cliente->nome)) {
						$this->Flash->error(__('Informe CPF e Nome do cliente para concluir o cadastro.'));
						return $this->redirect(['controller' => 'users', 'action' => 'login']);
					}
				}

				if (!$this->Clientes->save($cliente)) {
					$this->Flash->error(__('Não foi possível criar o cadastro do cliente automaticamente. Verifique os dados e tente novamente.'));
					return $this->redirect(['controller' => 'users', 'action' => 'login']);
				}
			}

			$bExists = $this->usuarioExistente(trim($data['email1']));
			if (isset($bExists)) {
				$this->Flash->error(__('Já existe um usuário com o e-mail informado!'));
				$this->redirect(['controller' => 'users', 'action' => 'login']);
			}
			
			if (strcmp($data['password'], $data['confirm_password']) != 0) $this->Flash->error(__('Senhas não conferem!'));
			else {
				$user = $this->Users->patchEntity($user, $data);
				
				$user->name = trim($data['name']);
				$user->username = strtolower(trim($data['email1']));
				$user->email = strtolower(trim($data['email1']));
				$user->role = 1;
				$user->bloqueado = 1;
				$user->created = date('Y-m-d');
				// Sempre usa o id do cliente encontrado/criado (não confia apenas no hidden do form).
				$user->idcliente = (int)$cliente->id;
				$user->tipo = intval($data['tipo']);
				$user->ip = $this->request->clientIp();

				if ($this->Users->save($user)) {
					$empresauser = $this->Empresasusers->newEntity();
					// Se existe contexto multi-empresa (usuário logado), usa a empresa selecionada.
					$empresauser->idempresa = $empresaId;
					$empresauser->iduser = $user->id;
					$this->Empresasusers->save($empresauser);
					$bEmailSent = $this->email($user->id, $empresauser->idempresa);
					if (!$bEmailSent) {
						$this->Flash->warning(__('Usuário cadastrado, porém não foi possível enviar a notificação por e-mail (falha no SMTP).'));
					}
					$this->Flash->success(__('Usuário cadastrado com sucesso! Aguarde a liberação de um administrador para efetuar o login.'));
				} else $this->Flash->error(__('Não foi possível adicionar o usuário.'));

				return $this->redirect(['controller' => 'users', 'action' => 'login']);
			}
		}
		
		$this->set('title', 'Cadastro de Cliente');
		$this->set('user', $user);
	}

	public function verificacnpjcliente(){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout("ajax");

		if ($this->request->is('post')) {
			$data = $this->request->getData();
			$cnpj = $data['cnpj'];
			$empresaIdAtual = !empty($this->Auth->user('idempresa')) ? (int)$this->Auth->user('idempresa') : null;
			$empresaIdCadastro = !empty($data['idempresa']) ? (int)$data['idempresa'] : null;
			$empresaId = $empresaIdAtual ?: $empresaIdCadastro;
		
			$clientes = $this->Clientes->findByCnpj(\removeCaracteres($cnpj))->toArray();
			$cliente = null;

			// Prioriza a empresa selecionada pelo usuário (quando houver contexto).
			if ($empresaId) {
				foreach($clientes as $reg) {
					if ((int)$reg->idempresa === $empresaId) { $cliente = $reg; break; }
				}
			}

			// Fallback: comportamento antigo (empresa dominante).
			if (empty($cliente)) {
				foreach($clientes as $reg) {
					if($reg->empresadominante == $reg->idempresa) { $cliente = $reg; break; }
				}
			}

			if(empty($cliente)) return $this->jsonResponse(['Mensagem' => 'naopode'], 404);
			if($cliente->inativo) return $this->jsonResponse(['Mensagem' => 'inativo'], 400);
			
			return $this->jsonResponse(['IdCliente' => $cliente->id, 'RazaoSocial' => $cliente->razaosocial], 200);

		}
	}	

	public function verificacpfcliente(){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout("ajax");

		if ($this->request->is(['post'])) {
			$data = $this->request->getData();
			$cpf = $data['cpf'];
			$empresaIdAtual = !empty($this->Auth->user('idempresa')) ? (int)$this->Auth->user('idempresa') : null;
			$empresaIdCadastro = !empty($data['idempresa']) ? (int)$data['idempresa'] : null;
			$empresaId = $empresaIdAtual ?: $empresaIdCadastro;
			$clientes = $this->Clientes->findByCpf(\removeCaracteres($cpf))->toArray();
			$cliente = null;

			if ($empresaId) {
				foreach($clientes as $reg) {
					if ((int)$reg->idempresa === $empresaId) { $cliente = $reg; break; }
				}
			}

			// Fallback: comportamento antigo (empresa dominante).
			if (empty($cliente)) {
				foreach($clientes as $reg) {
					if($reg->empresadominante == $reg->idempresa) { $cliente = $reg; break; }
				}
			}
			
			if(empty($cliente)) return $this->jsonResponse(['Mensagem' => 'naopode'], 404);
			if($cliente->inativo) return $this->jsonResponse(['Mensagem' => 'inativo'], 400);
			
			return $this->jsonResponse(['IdCliente' => $cliente->id, 'NomeCliente' => $cliente->nome], 200);
		}
	}	

	public function verificalogincadastro($login){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout("ajax");

		$user = $this->Users->findByUsername($login)->where(['inativo' => 0])->first();
		if(empty($user)) $user = $this->Users->findByEmail($login)->where(['inativo' => 0])->first();

		if(empty($user))	$tudocerto = 'podecadastrar';
		else				$tudocerto = 'naopode';

		echo $tudocerto;
	}

	public function verificaloginedit($login){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout("ajax");

		$user = $this->Users->findByUsername($login)->where(['inativo' => 0])->first();
		if(empty($user)) $user = $this->Users->findByEmail($login)->where(['inativo' => 0])->first();

		if(empty($user))	$tudocerto = 'podecadastrar';
		else				$tudocerto = $user->name;

		echo $tudocerto;
	}

	public function verificacpf($cpf){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout("ajax");

		$cpf = \removeCaracteres($cpf);
		$user = $this->Users->findByCpf($cpf)->first();

		if(empty($user)) $tudocerto = 'Não foi encontrado um usuário com este CPF!';
		else $tudocerto = $user->id;

		echo $tudocerto;
	}

	/**
	 * Confirma dados do responsável antes de renovar token (portal cliente).
	 * Apenas POST + usuário autenticado + CSRF (_csrfToken) + mitigação IDOR.
	 */
	public function verificadadoscliente() {
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');
		$this->request->allowMethod(['post']);

		if (!$this->Auth->user()) {
			echo 'nao';

			return;
		}
		$posted = (string)$this->request->getData('_csrfToken');
		$trusted = (string)($this->request->getAttribute('csrfToken') ?: $this->request->getParam('_csrfToken'));
		if ($posted === '' || $trusted === '' || !hash_equals($trusted, $posted)) {
			echo 'nao';

			return;
		}

		$data = (array)$this->request->getData();
		$idcliente = (int)($data['idcliente'] ?? 0);
		$nomeresponsavel = (string)($data['nomeresponsavel'] ?? '');
		$cpf = (string)($data['cpf'] ?? '');
		$rg = (string)($data['rg'] ?? '');

		if ($idcliente <= 0) {
			echo 'nao';

			return;
		}

		$role = (int)$this->Auth->user('role');
		if ($role === 1 && (int)$this->Auth->user('idcliente') !== $idcliente) {
			echo 'nao';

			return;
		}

		try {
			$cliente = $this->Clientes->get($idcliente);
		} catch (\Throwable $e) {
			echo 'nao';

			return;
		}

		if ($role === 0 && (int)($cliente->idempresa ?? 0) !== (int)$this->Auth->user('idempresa')) {
			echo 'nao';

			return;
		}

		$tudocerto = 'tudocerto';
		if (mb_strtoupper($nomeresponsavel, 'UTF-8') !== mb_strtoupper((string)$cliente->nomeresponsavel, 'UTF-8')) {
			$tudocerto = 'nao';
		}
		if (\removeCaracteres($cpf) !== \removeCaracteres($cliente->cpf ?? '')) {
			$tudocerto = 'nao';
		}
		if (\removeCaracteres($rg) !== \removeCaracteres($cliente->rg ?? '')) {
			$tudocerto = 'nao';
		}

		echo $tudocerto;
	}

	public function permissaoacesso() {
		error_reporting(0);
		if ($this->request->is('post')) {
			$data = $this->request->getData();
			foreach ($this->Users->findByIdcliente($data['idcliente']) as $user) {
				$user->permissaoacesso = 0;
				$this->Users->save($user);
			}
			foreach($data['users']['_ids'] as $id){
				$user = $this->Users->get($id);
				$user->permissaoacesso = 1;
				if ($this->Users->save($user)) {
					RbacClientePortal::syncUserIfEligible((int)$user->id);
				}
			}
			ClienteDomainBridge::emit(ClienteDomainEventType::USUARIO_VINCULADO_CLIENTE, [
				'idcliente' => (int)$data['idcliente'],
				'idempresa' => (int)$this->Auth->user('idempresa'),
				'actor_user_id' => (int)$this->Auth->user('id'),
				'title' => __('Permissões de acesso ao cliente atualizadas'),
				'message' => __('Usuários com acesso a senhas/contratos/token foram atualizados para este cliente.'),
				'action_url' => Router::url(['controller' => 'Clientes', 'action' => 'edit', $data['idcliente']]),
				'entity_type' => 'Cliente',
				'entity_id' => (int)$data['idcliente'],
			]);
			$this->Flash->success(__('Os usuários foram salvos.'));
			return $this->redirect(['controller' => 'Clientes', 'action' => 'edit', $data['idcliente']]);
		}
	}

	public function recuperasenha($id, $destinatario) {
		$this->autoRender = false;
		$this->viewBuilder()->setLayout("ajax");

		$user = $this->Users->get($id);

		$vogais = ['a','e','i','o','u'];
		$consoantes = ['b','c','d','f','g','h','nh','lh','ch','j','k','l','m','n','p','qu','r','rr','s','ss','t','v','w','x','y','z','shim','ba','la','ie'];
		
		$novasenha = '';
		$tamanho_palavra = rand(2,7);
		$contar_silabas = 0;
		while($contar_silabas < $tamanho_palavra){
			$vogal = $vogais[rand(0,count($vogais)-1)];
			$consoante = $consoantes[rand(0,count($consoantes)-1)];
			$silaba = $consoante.$vogal;
			$novasenha .=$silaba;
			$contar_silabas++;
			unset($vogal,$consoante,$silaba);
		}

		$user->password = $novasenha;

		$message = "Sua nova senha é $novasenha";

		if ($this->Users->save($user)) {
			$email = new Email();
			$idempresaEmail = !empty($user->idempresa) ? (int)$user->idempresa : (int)$this->Auth->user('idempresa');
			if (empty($idempresaEmail)) $idempresaEmail = (int)C_EmpresaPGM;
			$email->transport($idempresaEmail === (int)C_EmpresaMaster ? 'master' : 'pgm');
			$from = 'helpdesk@pgm.inf.br';
	
			$email->from([$from => 'PGM']);
			$email->to($destinatario)
				->emailFormat('html')
				->subject('Recuperação de senha - PGM');
	
			if($email->send($message)) echo 'Verifique seu e-mail!';

		} else echo 'Erro ao recuperar a senha!';
	
		
	}

	public function email($iduser, $idempresa) {
		$user = $this->Users->get($iduser);
		$cliente = $this->Clientes->findById($user->idcliente)->first();

		$nomeCliente = ($cliente && $cliente->tipo == C_ClientesTipoFisica) ? $cliente->nome : ($cliente ? $cliente->razaosocial : '');
		$message =
		"<h3> Novo usuário cadastrado! </h3>
		<h3> O usuário $user->name do cliente $nomeCliente foi cadastrado e está aguardando liberação. </h3>";
		$subject = "Novo usuário cadastrado";

		return SupportInboxMail::sendHtml($message, $subject, (int)$idempresa);
	}

	public function verificasenha($senhaadm) {
        $this->autoRender = false;
		$empresa = $this->Empresas->get(C_EmpresaPGM);
        if($empresa->senhaadministrativa == \criptografasenha($senhaadm)) return $this->jsonResponse(['Mensagem' => 'Senha correta'], 200);
		else return $this->jsonResponse(['Mensagem' => 'Senha administrativa incorreta'], 400);
	}

	public function loginduasetapas() {
		$this->_loadGoogleAuthenticatorLibs();
		$user = $this->Users->get($this->Auth->user('id'));
		if(empty($user->secret)) $this->set('bAutenticacao', false);
		else {
			$urlQRCode = \Sonata\GoogleAuthenticator\GoogleQrUrl::generate($user->username, $user->secret, 'PGM');
			$this->set('urlQRCode', $urlQRCode);
			$this->set('bAutenticacao', true);
		}
		if ($this->request->is('post')) {
			if($this->request->getData('duasetapas') == 'ativa') {
				$g = new \Google\Authenticator\GoogleAuthenticator();
				$secret = $g->generateSecret();
				$user->secret = $secret;
				$this->Users->save($user);
				$urlQRCode = \Sonata\GoogleAuthenticator\GoogleQrUrl::generate($user->username, $secret, 'PGM');
				$this->set('urlQRCode', $urlQRCode);
				$this->Flash->success('A verificação em duas etapas foi ativada com sucesso! Leia o código com o Google Authenticator App');
			} else if($this->request->getData('duasetapas') == 'tira') {
				$user->secret = null;
				if($this->Users->save($user)){
					$this->Flash->success('A verificação em duas etapas foi desativada com sucesso!');
					return $this->redirect(['action' => 'dashboard']);
				}
			}				
		}
	}

	public function verificacodigo($username, $code) {
		$this->autoRender = false;
		$this->response = $this->response->withType('text/plain; charset=UTF-8');
		try {
			$this->_loadGoogleAuthenticatorLibs();
			$username = rawurldecode((string)$username);
			$code = str_replace(' ', '', rawurldecode((string)$code));
			$user = $this->_findActiveUserForLogin($username);
			if (empty($user)) {
				echo 'erro';

				return;
			}
			$g = new \Google\Authenticator\GoogleAuthenticator();
			$secret = $user->secret;

			if ($g->checkCode($secret, $code)) {
				echo 'sucesso';
			} else {
				echo 'erro';
			}
		} catch (\Throwable $e) {
			Log::error('verificacodigo: ' . $e->getMessage(), ['exception' => $e]);
			echo 'erro';
		}
	}

	public function verificaloginduasetapas($username = null) {
		$this->autoRender = false;
		$this->response = $this->response->withType('text/plain; charset=UTF-8');
		try {
			if ($username === null || $username === '') {
				$username = $this->request->getQuery('username');
			}
			$username = rawurldecode(trim((string)$username));
			if ($username === '') {
				echo 'naotemcodigo';

				return;
			}
			$user = $this->_findActiveUserForLogin($username);
			if (empty($user) || empty($user->secret)) {
				echo 'naotemcodigo';
			} else {
				echo 'temcodigo';
			}
		} catch (\Throwable $e) {
			Log::error('verificaloginduasetapas: ' . $e->getMessage(), ['exception' => $e]);
			echo 'naotemcodigo';
		}
	}

	public function desativaverificacao() {
		$this->_loadGoogleAuthenticatorLibs();
		$user = $this->Users->get($this->Auth->user('id'));
	
		if ($this->request->is(['post', 'put'])) {
			$user = $this->Users->patchEntity($user, [
				'old_password'  => $this->request->data['senha'],
				'password'      => $this->request->data['senha'],
			],
			['validate' => 'password']);

			if(!empty($user->getErrors()['old_password'])) {
				$this->Flash->error('A senha informada está incorreta!');
				return $this->redirect(['action' => 'desativaverificacao']);
			}

			$g = new \Google\Authenticator\GoogleAuthenticator();
			$secret = $user->secret;

			if (!$g->checkCode($secret,  $this->request->data['codigo'])) {
				$this->Flash->error('O código informado está incorreto!');
				return $this->redirect(['action' => 'desativaverificacao']);
			} 
			
			$user->secret = null;
			if($this->Users->save($user)){
				$this->Flash->success('A verificação em duas etapas foi desativada com sucesso! Remova a conta no Google Authenticator App');
				return $this->redirect(['action' => 'dashboard']);
			}
		}
		$this->set('title', 'Desativar verificação');
		$this->set('user', $user);
	}

	public function usuarioExistente($email, $iduser = null) {
		$userExistente = $this->Users->findByUsername($email)->where(['inativo' => 0])->first();
		if(empty($userExistente)) $userExistente = $this->Users->findByEmail($email)->where(['inativo' => 0])->first();
		if(!empty($userExistente) && $userExistente->id != $iduser) {
			//$this->Flash->error('Já existe um usuário com este e-mail no sistema, verifique e inative o usuário "' . $userExistente->username . '"');
			$this->Flash->error('Já existe um usuário com este e-mail no sistema, verifique e inative o usuário correspondente.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
	}

	/**
	 * SMTP: tenta PGM ou Master conforme empresa; se falhar, tenta transporte `default` (MAIL_DEFAULT_*).
	 * Controlar com MAIL_RESET_FALLBACK_DEFAULT (default true). Erros em logs/error.log.
	 */
	protected function _sendEmailWithTransportFallback(
		string $to,
		array $from,
		string $subject,
		string $htmlBody,
		int $idempresa
	): bool {
		$primary = ((int)$idempresa === (int)C_EmpresaMaster) ? 'master' : 'pgm';
		$candidates = [$primary];
		if (filter_var(env('MAIL_RESET_FALLBACK_DEFAULT', true), FILTER_VALIDATE_BOOLEAN)) {
			if (!in_array('default', $candidates, true)) {
				$candidates[] = 'default';
			}
		}
		$verbose = filter_var(env('MAIL_EMAIL_VERBOSE_LOG', false), FILTER_VALIDATE_BOOLEAN);
		$last = null;
		foreach ($candidates as $transport) {
			$t0 = microtime(true);
			try {
				$cfg = TransportFactory::getConfig($transport);
				if (!is_array($cfg) || ($cfg['className'] ?? '') !== 'Smtp') {
					Log::warning(sprintf('[email] Transporte "%s": configuração em falta ou não-SMTP.', $transport));
					continue;
				}
				$pwd = isset($cfg['password']) ? trim((string)$cfg['password']) : '';
				$usr = isset($cfg['username']) ? trim((string)$cfg['username']) : '';
				if ($transport === 'default' && $usr === '' && $pwd === '') {
					Log::info('[email] Ignorado transporte "default": MAIL_DEFAULT_USERNAME/PASSWORD vazios (evita espera de timeout).');

					continue;
				}
				if ($usr !== '' && $pwd === '') {
					$hint = ['master' => 'MAIL_MASTER_PASSWORD', 'pgm' => 'MAIL_PGM_PASSWORD', 'default' => 'MAIL_DEFAULT_PASSWORD'][$transport] ?? 'MAIL_*_PASSWORD';
					Log::warning(sprintf('[email] Transporte "%s": utilizador SMTP definido mas senha vazia — defina %s no .env.', $transport, $hint));
				}
				$host = (string)($cfg['host'] ?? '');
				$port = (int)($cfg['port'] ?? 0);
				$timeout = (int)($cfg['timeout'] ?? 0);
				$tls = !empty($cfg['tls']);
				Log::info(sprintf(
					'[email] Tentativa transporte=%s host=%s port=%d timeout=%ds tls=%s auth_user=%s',
					$transport,
					$host !== '' ? $host : '(vazio)',
					$port,
					$timeout,
					$tls ? 'yes' : 'no',
					$usr !== '' ? 'yes' : 'no'
				));

				$email = new Email();
				$email->transport($transport);
				$email->from($from)
					->to($to)
					->emailFormat('html')
					->subject($subject);
				$email->send($htmlBody);
				$ms = (int)round((microtime(true) - $t0) * 1000);
				if ($transport !== $primary) {
					Log::info(sprintf('[email] Enviado via fallback "%s" (falhou "%s") em %d ms.', $transport, $primary, $ms));
				} else {
					Log::info(sprintf('[email] Enviado via "%s" em %d ms.', $transport, $ms));
				}

				return true;
			} catch (\Throwable $e) {
				$last = $e;
				$ms = (int)round((microtime(true) - $t0) * 1000);
				$chain = $e->getMessage();
				$prev = $e->getPrevious();
				for ($i = 0; $prev && $i < 4; $i++, $prev = $prev->getPrevious()) {
					$chain .= ' | causa: ' . $prev->getMessage();
				}
				Log::warning(sprintf('[email] Transporte "%s" falhou após %d ms: %s', $transport, $ms, $chain));
				if (stripos($chain, 'password') !== false || stripos($chain, 'authentication') !== false || stripos($chain, '535') !== false) {
					$ep = ['pgm' => 'PGM', 'master' => 'MASTER', 'default' => 'DEFAULT'][$transport] ?? strtoupper($transport);
					Log::warning(sprintf(
						'[email] SMTP rejeitou credenciais (transporte "%s"). Confirme no .env MAIL_%s_USERNAME e MAIL_%s_PASSWORD (senha da caixa no Skymail/webmail; se for Gmail use "app password").',
						$transport,
						$ep,
						$ep
					));
				}
				if ($verbose) {
					Log::error('[email] MAIL_EMAIL_VERBOSE_LOG trace transporte ' . $transport . ":\n" . $e->getTraceAsString());
				}
			}
		}
		if ($last !== null) {
			Log::error('[email] Todos os transportes falharam. Último: ' . $last->getMessage()
				. ' | .env: MAIL_SMTP_TIMEOUT (s), MAIL_PGM_*, MAIL_RESET_FALLBACK_DEFAULT=0 para só testar pgm, MAIL_EMAIL_VERBOSE_LOG=1 para trace em error.log');
		}

		return false;
	}

	/**
	 * E-mail multipart (HTML + texto) usando templates Email/html/reset_password e Email/text/reset_password.
	 * Mesma política de transporte/fallback que _sendEmailWithTransportFallback.
	 */
	protected function _sendResetPasswordEmailWithTransportFallback(
		string $to,
		array $from,
		string $subject,
		int $idempresa,
		array $viewVars
	): bool {
		$primary = ((int)$idempresa === (int)C_EmpresaMaster) ? 'master' : 'pgm';
		$candidates = [$primary];
		if (filter_var(env('MAIL_RESET_FALLBACK_DEFAULT', true), FILTER_VALIDATE_BOOLEAN)) {
			if (!in_array('default', $candidates, true)) {
				$candidates[] = 'default';
			}
		}
		$verbose = filter_var(env('MAIL_EMAIL_VERBOSE_LOG', false), FILTER_VALIDATE_BOOLEAN);
		$last = null;
		foreach ($candidates as $transport) {
			$t0 = microtime(true);
			try {
				$cfg = TransportFactory::getConfig($transport);
				if (!is_array($cfg) || ($cfg['className'] ?? '') !== 'Smtp') {
					Log::warning(sprintf('[email] Transporte "%s": configuração em falta ou não-SMTP.', $transport));
					continue;
				}
				$pwd = isset($cfg['password']) ? trim((string)$cfg['password']) : '';
				$usr = isset($cfg['username']) ? trim((string)$cfg['username']) : '';
				if ($transport === 'default' && $usr === '' && $pwd === '') {
					Log::info('[email] Ignorado transporte "default": MAIL_DEFAULT_USERNAME/PASSWORD vazios (evita espera de timeout).');

					continue;
				}
				if ($usr !== '' && $pwd === '') {
					$hint = ['master' => 'MAIL_MASTER_PASSWORD', 'pgm' => 'MAIL_PGM_PASSWORD', 'default' => 'MAIL_DEFAULT_PASSWORD'][$transport] ?? 'MAIL_*_PASSWORD';
					Log::warning(sprintf('[email] Transporte "%s": utilizador SMTP definido mas senha vazia — defina %s no .env.', $transport, $hint));
				}
				$host = (string)($cfg['host'] ?? '');
				$port = (int)($cfg['port'] ?? 0);
				$timeout = (int)($cfg['timeout'] ?? 0);
				$tls = !empty($cfg['tls']);
				Log::info(sprintf(
					'[email] reset_password: tentativa transporte=%s host=%s port=%d timeout=%ds tls=%s auth_user=%s',
					$transport,
					$host !== '' ? $host : '(vazio)',
					$port,
					$timeout,
					$tls ? 'yes' : 'no',
					$usr !== '' ? 'yes' : 'no'
				));

				$email = new Email();
				$email->transport($transport);
				// ViewBuilder: Email não tem ->layout(); template() com 1 arg não desliga o layout padrão.
				$email->viewBuilder()
					->setTemplate('reset_password')
					->setLayout(false);
				$email->from($from)
					->to($to)
					->subject($subject)
					->emailFormat('both')
					->viewVars($viewVars);
				$email->send();
				$ms = (int)round((microtime(true) - $t0) * 1000);
				if ($transport !== $primary) {
					Log::info(sprintf('[email] Enviado via fallback "%s" (falhou "%s") em %d ms.', $transport, $primary, $ms));
				} else {
					Log::info(sprintf('[email] Enviado via "%s" em %d ms.', $transport, $ms));
				}

				return true;
			} catch (\Throwable $e) {
				$last = $e;
				$ms = (int)round((microtime(true) - $t0) * 1000);
				$chain = $e->getMessage();
				$prev = $e->getPrevious();
				for ($i = 0; $prev && $i < 4; $i++, $prev = $prev->getPrevious()) {
					$chain .= ' | causa: ' . $prev->getMessage();
				}
				Log::warning(sprintf('[email] Transporte "%s" falhou após %d ms: %s', $transport, $ms, $chain));
				if (stripos($chain, 'password') !== false || stripos($chain, 'authentication') !== false || stripos($chain, '535') !== false) {
					$ep = ['pgm' => 'PGM', 'master' => 'MASTER', 'default' => 'DEFAULT'][$transport] ?? strtoupper($transport);
					Log::warning(sprintf(
						'[email] SMTP rejeitou credenciais (transporte "%s"). Confirme no .env MAIL_%s_USERNAME e MAIL_%s_PASSWORD (senha da caixa no Skymail/webmail; se for Gmail use "app password").',
						$transport,
						$ep,
						$ep
					));
				}
				if ($verbose) {
					Log::error('[email] MAIL_EMAIL_VERBOSE_LOG trace transporte ' . $transport . ":\n" . $e->getTraceAsString());
				}
			}
		}
		if ($last !== null) {
			Log::error('[email] Todos os transportes falharam (reset_password). Último: ' . $last->getMessage());
		}

		return false;
	}

	public function resetPassword($iduser) {
		$token = rawurldecode(trim((string)$iduser));
		if ($token === '') {
			$this->Flash->error(__('Informe um e-mail válido.'));
			if ($this->Auth->user()) {
				return $this->redirect(['action' => 'dashboard']);
			}
			return $this->_redirectGuestAfterPasswordReset(null);
		}

		if (is_numeric($token)) {
			$user = $this->Users->find()->where(['id' => (int)$token])->first();
		} else {
			$lower = strtolower($token);
			$user = $this->Users->find()
				->where(['LOWER(Users.email)' => $lower])
				->first();
		}

		if (empty($user)) {
			$this->Flash->error(__('Não foi encontrado um usuário com o email informado!'));
			if ($this->Auth->user()) {
				return $this->redirect(['action' => 'dashboard']);
			}
			return $this->_redirectGuestAfterPasswordReset(null);
		}

		if (empty($this->Auth->user('idempresa'))) {
			$idempresa = (int)C_EmpresaPGM;
		} else {
			$idempresa = (int)$this->Auth->user('idempresa');
		}

		$urlfora = '';
		try {
			$cfg = $this->Config->get(1);
			if (!empty($cfg->urlfora)) {
				$urlfora = (string)$cfg->urlfora;
			}
		} catch (RecordNotFoundException $e) {
		}
		$urlfora = rtrim($urlfora, '/');
		if ($urlfora === '') {
			$urlfora = rtrim(Router::url('/', true), '/');
		}

		$nomeempresa = 'PGM';
		try {
			$empresa = $this->Empresas->get($idempresa);
			if (!empty($empresa->nomefantasia)) {
				$nomeempresa = (string)$empresa->nomefantasia;
			} elseif (!empty($empresa->razaosocial)) {
				$nomeempresa = (string)$empresa->razaosocial;
			}
		} catch (RecordNotFoundException $e) {
		}

		$user->hashreset = \criptografaSenha($user->id . $user->name . date('d/m/Y|H:i:s'));
		$user->hashreset = \removeCaracteres($user->hashreset);
		$user->hashreset_expires = FrozenTime::now()->addMinutes(10);
		if (!$this->Users->save($user)) {
			$this->Flash->error(__('Não foi possível gerar o link de redefinição. Tente novamente.'));
			if ($this->Auth->user()) {
				return $this->redirect(['action' => 'dashboard']);
			}
			return $this->_redirectGuestAfterPasswordReset($user);
		}

		$link = $urlfora . '/Users/resetPasswordNew?hash=' . rawurlencode((string)$user->hashreset);
		if ((int)$user->get('role') === (int)C_RoleFuncionario) {
			$link .= '&from=empresa';
		}

		$name = trim((string)($user->name ?? ''));
		if ($name === '') {
			$name = trim((string)($user->username ?? ''));
		}
		if ($name === '') {
			$name = __('usuário');
		}

		$fromAddr = 'helpdesk@pgm.inf.br';
		$subject = 'Portal PGM — Redefinição de senha';
		if ($this->_sendResetPasswordEmailWithTransportFallback($user->email, [$fromAddr => $nomeempresa], $subject, $idempresa, [
			'name' => $name,
			'resetUrl' => $link,
			'expirationText' => self::PASSWORD_RESET_EXPIRATION_EMAIL_TEXT,
			'currentYear' => (int)date('Y'),
		])) {
			$this->Flash->success(__('Email para redefiniçao de senha enviado!'));
		} else {
			$this->Flash->error(__('Não foi possível enviar o e-mail. Tente mais tarde ou contate o suporte.'));
		}

		if ($this->Auth->user()) {
			return $this->redirect(['action' => 'dashboard']);
		}
		return $this->_redirectGuestAfterPasswordReset($user);
	}

	public function resetPasswordNew() {
		$this->viewBuilder()->setLayout('reset_password_canvas');
		extract($this->request->getQuery());

		$user = $this->Users->findByHashreset($hash)->first();

		if (!empty($user) && $this->_isPasswordResetLinkExpired($user)) {
			$this->_invalidateExpiredPasswordResetToken($user);
			$this->Flash->error(__('O link de redefinição expirou ou já foi utilizado. Solicite um novo e-mail de redefinição de senha.'));

			return $this->redirect($this->_guestLoginRouteAfterPasswordReset(null));
		}

		if (empty($user)) {
			$this->Flash->success('Não foi encontrado um usuário válido!');

			return $this->redirect($this->_guestLoginRouteAfterPasswordReset(null));
		}

		if ($this->request->is('post')) {
			$data = $this->request->getData();
			$pw = $data['password'] ?? $data['password1'] ?? '';
			$pw2 = $data['confirmPassword'] ?? $data['password2'] ?? '';

			$user = $this->Users->patchEntity($user, [
				'password'      => $pw,
				'password1'     => $pw,
				'password2'     => $pw2
			],
			['validate' => 'password']);
			// Invalida o hash de redefinição após o uso para evitar reutilização do link
			$user->hashreset = null;
			$user->hashreset_expires = null;

			if ($this->Users->save($user)) {
				$this->Flash->success('A senha foi alterada com sucesso!');

				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $user->id);

				if ($this->Auth->user('id') == $user->id) {
					$target = ['action' => 'logout'];
				} elseif ($this->Auth->user()) {
					$target = ['action' => 'dashboard'];
				} else {
					$target = $this->_guestLoginRouteAfterPasswordReset($user);
				}

				if ($this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
					return $this->response->withType('application/json')
						->withStringBody(json_encode([
							'success' => true,
							'redirect' => Router::url($target, true),
						]));
				}

				return $this->redirect($target);
			}

			$this->Flash->error('Ocorreu um erro ao salvar a senha!');

			if ($this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
				$err = $user->getErrors();
				$msg = 'Ocorreu um erro ao salvar a senha.';
				foreach (['password1', 'password2'] as $ek) {
					if (empty($err[$ek]) || !is_array($err[$ek])) {
						continue;
					}
					foreach ($err[$ek] as $v) {
						if (is_string($v)) {
							$msg = $v;
							break 2;
						}
						if (is_array($v)) {
							foreach ($v as $vv) {
								if (is_string($vv)) {
									$msg = $vv;
									break 3;
								}
							}
						}
					}
				}

				return $this->response->withType('application/json')
					->withStatus(422)
					->withStringBody(json_encode([
						'success' => false,
						'message' => $msg,
						'errors' => $err,
					]));
			}
		}

		$this->set('user', $user);
		$this->set('title', 'Redefinir senha');
		$this->set('voltarLoginUrl', Router::url($this->_guestLoginRouteAfterPasswordReset($user)));
	}

	public function enviaEmailAutenticacaoSemLogin($destinatario) {

		if(($destinatario)) $user = $this->Users->findByEmail($destinatario)->first();
		else $user = NULL;

		if(empty($user)) {
			$this->Flash->error('Não foi encontrado um usuário com o email informado!');
			return $this->redirect(['action' => 'logout']);
		}
			$idempresaEmail = !empty($user->idempresa) ? (int)$user->idempresa : (int)$this->Auth->user('idempresa');
			if (empty($idempresaEmail)) {
				$idempresaEmail = (int)C_EmpresaPGM;
			}
			$from = 'helpdesk@pgm.inf.br';

			$urlfora = $this->Config->get(1)->urlfora;

			$link = $urlfora . "Users/desativaverificacaosemlogin?hash=$user->hashreset";

			$message =
			"<h3>Desativação de autenticação em duas etapas</h3>
			<h3>Desative sua autenticação 2FA <a href='$link'>clicando aqui</a>.</h3>";

			if ($this->_sendEmailWithTransportFallback($destinatario, [$from => 'PGM'], 'Desativação de autenticação 2FA - PGM', $message, $idempresaEmail)) {
				$this->Flash->success('Email para desativação de 2FA enviado!');
				return $this->redirect(['action' => 'dashboard']);
			}
			$this->Flash->error(__('Não foi possível enviar o e-mail. Tente mais tarde ou contate o suporte.'));
			return $this->redirect(['action' => 'login']);
	}

	//possibilita qualquer usuario desabilitar sua 2FA sem fazer login
	public function desativaverificacaosemlogin() {
		$this->viewBuilder()->setLayout("login");
	
		if ($this->request->is(['post', 'put'])) {

			if ($this->Auth->user()) {
				return $this->_safeAuthRedirectResponse();
			}

			$creds = $this->_extractLoginCredentials();
			$logou = $this->_identifyUserByCredentials($creds['username'], $creds['password']);
			if (!$logou) {
				$this->Flash->error(__('Usuário e/ou senha incorretos. Tente novamente.'));
				return;
			}
			$user = $this->Users->get($logou['id']);
			$user->secret = null;

			if($this->Users->save($user)){
				$this->Flash->success('A verificação em duas etapas foi desativada com sucesso! Remova a conta no Google Authenticator App');
				return $this->redirect(['action' => 'dashboard']);
			}
		}
		$this->set('title', 'Desativar verificação');
	}

	//possibilita o adm desabilitar a 2FA de qualquer usuário
	public function desativaverificacaoqualqueruser($id) {
		if (!$this->Auth->user('admin')) {
			$this->Flash->error('Você não possui permissão para desativar a autenticação de dois fatores. Contate um administrador do sistema.');
			return $this->redirect(['action' => 'dashboard']);
		}

		if ($id == null) $id = $this->Auth->user('id');

		$user = $this->Users->get($id);
		$user['secret'] = null;

		if($this->Users->save($user)){
			$this->Flash->success('A verificação em duas etapas foi desativada com sucesso! Remova a conta no Google Authenticator App');
			return $this->redirect(['action' => 'index']);
		}
	}

	/**
	 * Empresas que o utilizador autenticado pode atribuir a um colega da equipe (empresasusers).
	 *
	 * @return array<int,string>
	 */
	protected function _empresasOptionsForEquipeVinculo(): array {
		try {
			if (!empty($this->Auth->user('admin'))) {
				return $this->Empresas->find('list', [
					'keyField' => 'id',
					'valueField' => 'nomefantasia',
				])->order(['nomefantasia' => 'ASC', 'id' => 'ASC'])->toArray();
			}
			$uid = (int)$this->Auth->user('id');
			if ($uid <= 0) {
				return [];
			}
			$opts = [];
			foreach (
				$this->Empresasusers->find()
					->where(['iduser' => $uid])
					->contain(['Empresas' => ['fields' => ['id', 'nomefantasia']]])
					->order(['Empresas.nomefantasia' => 'ASC'])
					->all() as $r
			) {
				if (!empty($r->empresa)) {
					$opts[(int)$r->empresa->id] = (string)$r->empresa->nomefantasia;
				}
			}

			return $opts;
		} catch (\Throwable $e) {
			return [];
		}
	}

	/**
	 * @return int[]
	 */
	protected function _empresaIdsAllowedForEquipeVinculo(): array {
		return array_map('intval', array_keys($this->_empresasOptionsForEquipeVinculo()));
	}

	/**
	 * @param array<string,mixed> $data
	 * @return int[]
	 */
	protected function _normalizeEmpresaVinculoPost(array $data): array {
		$raw = $data['empresa_vinculo_ids'] ?? [];
		if (!is_array($raw)) {
			$raw = $raw !== null && $raw !== '' ? [(int)$raw] : [];
		}

		return array_values(array_unique(array_filter(array_map('intval', $raw), static function ($v) {
			return $v > 0;
		})));
	}

	/**
	 * @param int[] $picked
	 * @param int[] $allowed
	 * @return int[]
	 */
	protected function _resolveEmpresaVinculoForNewUser(array $picked, array $allowed): array {
		$allowed = array_values(array_unique(array_filter(array_map('intval', $allowed))));
		if ($allowed === []) {
			$allowed = [(int)C_EmpresaPGM];
		}
		$picked = array_values(array_intersect($picked, $allowed));
		if ($picked !== []) {
			return $picked;
		}
		$def = (int)$this->Auth->user('idempresa');
		if ($def > 0 && in_array($def, $allowed, true)) {
			return [$def];
		}
		$pref = $this->getEmpresaPreferencial((int)$this->Auth->user('id'));
		if ($pref !== null && in_array((int)$pref, $allowed, true)) {
			return [(int)$pref];
		}

		return [(int)$allowed[0]];
	}

	/**
	 * @param int[] $empresaIds
	 * @param int[] $allowed
	 */
	protected function _syncEmpresasVinculoEquipe(int $userId, array $empresaIds, array $allowed): bool {
		$allowed = array_values(array_unique(array_filter(array_map('intval', $allowed))));
		if ($allowed === []) {
			return false;
		}
		$empresaIds = array_values(array_intersect(array_unique(array_map('intval', $empresaIds)), $allowed));
		if ($empresaIds === []) {
			return false;
		}
		$ok = false;
		foreach ($empresaIds as $eid) {
			$n = $this->Empresasusers->find()->where(['iduser' => $userId, 'idempresa' => $eid])->count();
			if ($n === 0) {
				$eu = $this->Empresasusers->newEntity(['idempresa' => $eid, 'iduser' => $userId]);
				if ($this->Empresasusers->save($eu)) {
					$ok = true;
				}
			} else {
				$ok = true;
			}
		}

		return $ok;
	}

	/**
	 * @param int[] $empresaIds
	 * @param int[] $allowed
	 */
	protected function _replaceEmpresasVinculoEquipe(int $userId, array $empresaIds, array $allowed): void {
		$allowed = array_values(array_unique(array_filter(array_map('intval', $allowed))));
		if ($allowed === []) {
			return;
		}
		$empresaIds = array_values(array_intersect(array_unique(array_map('intval', $empresaIds)), $allowed));
		if ($empresaIds === []) {
			return;
		}
		foreach ($this->Empresasusers->find()->where(['iduser' => $userId])->all() as $row) {
			if (!in_array((int)$row->idempresa, $empresaIds, true)) {
				$this->Empresasusers->delete($row);
			}
		}
		foreach ($empresaIds as $eid) {
			$n = $this->Empresasusers->find()->where(['iduser' => $userId, 'idempresa' => $eid])->count();
			if ($n === 0) {
				$eu = $this->Empresasusers->newEntity(['idempresa' => $eid, 'iduser' => $userId]);
				$this->Empresasusers->save($eu);
			}
		}
	}

	protected function _queuesTablesExist(): bool {
		try {
			$tables = $this->Queues->getConnection()->getSchemaCollection()->listTables();

			return in_array('queues', $tables, true) && in_array('queues_users', $tables, true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	protected function _syncQueuesUsuario(int $userId, int $idempresa): void {
		if (!$this->_queuesTablesExist()) {
			return;
		}
		$raw = $this->request->getData('queue_ids');
		if (!is_array($raw)) {
			$raw = [];
		}
		$ids = array_values(array_unique(array_filter(array_map('intval', $raw))));
		$allowedIds = $this->Queues->find()
			->select(['id'])
			->where(['idempresa' => $idempresa])
			->extract('id')
			->toList();
		if (!empty($allowedIds)) {
			$this->QueuesUsers->deleteAll(['user_id' => $userId, 'queue_id IN' => $allowedIds]);
		}
		$perQueueSl = $this->request->getData('queue_support_level');
		if (!is_array($perQueueSl)) {
			$perQueueSl = [];
		}
		$quHasSl = false;
		try {
			$quHasSl = in_array('support_level_id', $this->QueuesUsers->getSchema()->columns(), true);
		} catch (\Throwable $e) {
		}
		$userSl = 0;
		if ($quHasSl && $this->_usersSupportLevelColumn()) {
			try {
				$ur = $this->Users->get($userId);
				$userSl = (int)($ur->support_level_id ?? 0);
			} catch (\Throwable $e) {
			}
		}
		foreach ($ids as $qid) {
			if (!in_array($qid, $allowedIds, true)) {
				continue;
			}
			$row = $this->QueuesUsers->newEntity(['queue_id' => $qid, 'user_id' => $userId]);
			if ($quHasSl) {
				$pid = 0;
				if (isset($perQueueSl[$qid])) {
					$pid = (int)$perQueueSl[$qid];
				} elseif (isset($perQueueSl[(string)$qid])) {
					$pid = (int)$perQueueSl[(string)$qid];
				}
				if ($pid > 0 && $this->_supportLevelIdAllowedForTechnician($pid)) {
					$row->support_level_id = $pid;
				} elseif ($userSl > 0) {
					$row->support_level_id = $userSl;
				}
			}
			$this->QueuesUsers->save($row);
		}
	}

	protected function _usersSupportLevelColumn(): bool {
		try {
			return in_array('support_level_id', $this->Users->getSchema()->columns(), true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	protected function _supportLevelsTableExists(): bool {
		try {
			$tables = $this->Users->getConnection()->getSchemaCollection()->listTables();

			return in_array('support_levels', $tables, true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	/** N1–N3 para select do técnico (sort_order 1–3). */
	protected function _supportLevelsListForTechnicians(): array {
		if (!$this->_usersSupportLevelColumn() || !$this->_supportLevelsTableExists()) {
			return [];
		}

		return $this->SupportLevels->find('list', ['keyField' => 'id', 'valueField' => 'name'])
			->where(['sort_order IN' => [1, 2, 3]])
			->order(['sort_order' => 'ASC', 'id' => 'ASC'])
			->toArray();
	}

	protected function _supportLevelIdAllowedForTechnician(int $id): bool {
		if (!$this->_supportLevelsTableExists()) {
			return false;
		}
		$r = $this->SupportLevels->find()->select(['id'])->where(['id' => $id, 'sort_order IN' => [1, 2, 3]])->first();

		return !empty($r);
	}

	protected function _queuesUsersSupportLevelColumn(): bool {
		try {
			return in_array('support_level_id', $this->QueuesUsers->getSchema()->columns(), true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * Remove ficheiros de avatar em arquivos/usuarios/{id}.* (mesmos sufixos que a timeline do Service Desk).
	 */
	protected function _deleteUserAvatarFiles(int $userId): void {
		if ($userId < 1) {
			return;
		}
		$dir = WWW_ROOT . 'arquivos' . DS . 'usuarios';
		foreach (['jpg', 'jpeg', 'png', 'gif', 'webp'] as $ext) {
			$p = $dir . DS . $userId . '.' . $ext;
			if (is_file($p)) {
				@unlink($p);
			}
		}
	}

	/**
	 * @param array $file Entrada estilo $_FILES
	 * @return string|null Mensagem de erro ou null se OK
	 */
	protected function _saveUserAvatarFromUpload(int $userId, array $file): ?string {
		if ($userId < 1) {
			return 'Utilizador inválido.';
		}
		$maxBytes = 2097152;
		if (!empty($file['size']) && (int)$file['size'] > $maxBytes) {
			return 'A imagem deve ter no máximo 2 MB.';
		}
		$tmp = (string)($file['tmp_name'] ?? '');
		if ($tmp === '' || !is_uploaded_file($tmp)) {
			return 'Upload inválido.';
		}
		$info = @getimagesize($tmp);
		if ($info === false) {
			return 'Envie uma imagem válida (JPG, PNG, GIF ou WebP).';
		}
		$type = (int)($info[2] ?? 0);
		$map = [
			IMAGETYPE_JPEG => 'jpg',
			IMAGETYPE_PNG => 'png',
			IMAGETYPE_GIF => 'gif',
		];
		if (defined('IMAGETYPE_WEBP')) {
			$map[(int) IMAGETYPE_WEBP] = 'webp';
		}
		if (!isset($map[$type])) {
			return 'Formato de imagem não suportado. Use JPG, PNG, GIF ou WebP.';
		}
		$ext = $map[$type];
		$dir = WWW_ROOT . 'arquivos' . DS . 'usuarios';
		if (!is_dir($dir)) {
			if (!@mkdir($dir, 0755, true)) {
				return 'Não foi possível criar a pasta de fotos de perfil.';
			}
		}
		$this->_deleteUserAvatarFiles($userId);
		$dest = $dir . DS . $userId . '.' . $ext;
		if (!@move_uploaded_file($tmp, $dest)) {
			return 'Não foi possível salvar a imagem.';
		}

		return null;
	}
}
