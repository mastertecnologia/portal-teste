<?php
namespace App\Test\TestCase\Utility;

use App\Utility\RbacChecker;
use Cake\Core\Configure;
use PHPUnit\Framework\TestCase;

class RbacCheckerTest extends TestCase {

	protected function tearDown(): void {
		Configure::delete('Rbac');
		parent::tearDown();
	}

	public function testMatchActionWildcard() {
		$this->assertTrue(RbacChecker::matchAction('Foo', 'bar', ['controller' => 'Foo', 'action' => '*']));
		$this->assertTrue(RbacChecker::matchAction('Foo', 'bar', ['controller' => 'Foo', 'action' => '']));
	}

	public function testMatchActionNormalizesControllerAndActionCase() {
		$this->assertTrue(RbacChecker::matchAction('USERS', 'Login', [
			'controller' => 'users',
			'action' => 'login',
		]));
	}

	public function testMatchActionExact() {
		$this->assertTrue(RbacChecker::matchAction('ContractManagement', 'index', [
			'controller' => 'ContractManagement',
			'action' => 'index',
		]));
		$this->assertFalse(RbacChecker::matchAction('ContractManagement', 'delete', [
			'controller' => 'ContractManagement',
			'action' => 'index',
		]));
	}

	public function testMatchActionControllerMismatchReturnsFalse() {
		$this->assertFalse(RbacChecker::matchAction('Users', 'index', [
			'controller' => 'Clientes',
			'action' => '*',
		]));
	}

	public function testMatchActionCommaOnlyActionListDoesNotMatchConcreteAction() {
		$row = ['controller' => 'Foo', 'action' => ' ,  , '];
		$this->assertFalse(RbacChecker::matchAction('Foo', 'index', $row));
	}

	public function testMatchActionMissingControllerKeyUsesEmptyString() {
		$this->assertFalse(RbacChecker::matchAction('Users', 'index', ['action' => 'index']));
	}

	public function testMatchActionMissingActionKeyMatchesAnyAction() {
		$this->assertTrue(RbacChecker::matchAction('Foo', 'delete', ['controller' => 'Foo']));
		$this->assertTrue(RbacChecker::matchAction('Foo', 'index', ['controller' => 'Foo']));
	}

	public function testMatchActionCommaSeparated() {
		$row = [
			'controller' => 'ContractManagement',
			'action' => 'index, view ,exportar',
		];
		$this->assertTrue(RbacChecker::matchAction('ContractManagement', 'view', $row));
		$this->assertTrue(RbacChecker::matchAction('ContractManagement', 'exportar', $row));
		$this->assertFalse(RbacChecker::matchAction('ContractManagement', 'delete', $row));
	}

	public function testPortalContratosAlias() {
		$row = ['controller' => 'PortalAdvancedContracts', 'action' => 'index'];
		$this->assertTrue(RbacChecker::matchAction('PortalContratos', 'index', $row));
		$this->assertFalse(RbacChecker::matchAction('PortalContratos', 'admin', $row));
	}

	public function testVisitasMatchesLegacyAgendaControllerRow() {
		$row = ['controller' => 'Agenda', 'action' => '*'];
		$this->assertTrue(RbacChecker::matchAction('Visitas', 'index', $row));
		$this->assertTrue(RbacChecker::matchAction('Visitas', 'calendario', $row));
		$this->assertFalse(RbacChecker::matchAction('Visitas', 'index', ['controller' => 'Agenda', 'action' => 'only']));
	}

	public function testMatchActionTicketsPortalApiIndexCliente() {
		$row = ['controller' => 'Tickets', 'action' => 'indexcliente,meustickets,view,apiindexcliente'];
		$this->assertTrue(RbacChecker::matchAction('Tickets', 'apiIndexCliente', $row));
	}

	public function testMatchActionUsersDashboard() {
		$row = ['controller' => 'Users', 'action' => 'dashboard'];
		$this->assertTrue(RbacChecker::matchAction('Users', 'dashboard', $row));
	}

	public function testMatchActionUsersAccessDenied() {
		$row = ['controller' => 'Users', 'action' => 'accessDenied'];
		$this->assertTrue(RbacChecker::matchAction('Users', 'accessDenied', $row));
	}

	public function testMatchActionPesquisaSidebarSearch() {
		$row = ['controller' => 'Pesquisa', 'action' => 'pesquisa,link'];
		$this->assertTrue(RbacChecker::matchAction('Pesquisa', 'pesquisa', $row));
		$this->assertTrue(RbacChecker::matchAction('Pesquisa', 'link', $row));
	}

	public function testMatchActionNormasempresaReadAndAcessoremoto() {
		$row = ['controller' => 'Normasempresa', 'action' => 'index,contato,download,downloadFile,download_file'];
		$this->assertTrue(RbacChecker::matchAction('Normasempresa', 'download', $row));
		$this->assertTrue(RbacChecker::matchAction('Normasempresa', 'download_file', $row));
		$this->assertTrue(RbacChecker::matchAction('Normasempresa', 'acessoremoto', [
			'controller' => 'Normasempresa',
			'action' => 'acessoremoto,acesso_remoto',
		]));
		$this->assertTrue(RbacChecker::matchAction('Normasempresa', 'acesso_remoto', [
			'controller' => 'Normasempresa',
			'action' => 'acessoremoto,acesso_remoto',
		]));
	}

	public function testMatchActionUsersChangePasswordAdmin() {
		$row = ['controller' => 'Users', 'action' => 'changePasswordAdmin'];
		$this->assertTrue(RbacChecker::matchAction('Users', 'changePasswordAdmin', $row));
	}

	public function testMatchActionUsersProfileAndPasswordUnderscoreAndCamelCase() {
		$rowProfile = ['controller' => 'Users', 'action' => 'change_profile,changeProfile'];
		$this->assertTrue(RbacChecker::matchAction('Users', 'changeProfile', $rowProfile));
		$rowPass = ['controller' => 'Users', 'action' => 'change_password,changePassword'];
		$this->assertTrue(RbacChecker::matchAction('Users', 'changePassword', $rowPass));
	}

	public function testMatchActionUsersRequisicoesAndDesbloquear() {
		$this->assertTrue(RbacChecker::matchAction('Users', 'requisicoesAcesso', [
			'controller' => 'Users',
			'action' => 'requisicoesAcesso',
		]));
		$this->assertTrue(RbacChecker::matchAction('Users', 'desbloquear', [
			'controller' => 'Users',
			'action' => 'desbloquear',
		]));
	}

	public function testMatchActionEmpresasUpdateChangePasswordVariants() {
		$row = ['controller' => 'Empresas', 'action' => 'edit,change_password,changePassword,deleteLogotipo'];
		$this->assertTrue(RbacChecker::matchAction('Empresas', 'changePassword', $row));
	}

	public function testMatchActionPrefaturamentoConferenciaIncludesIndex() {
		$row = ['controller' => 'Prefaturamento', 'action' => 'index,conferencia'];
		$this->assertTrue(RbacChecker::matchAction('Prefaturamento', 'index', $row));
		$this->assertTrue(RbacChecker::matchAction('Prefaturamento', 'conferencia', $row));
	}

	public function testMatchActionFaturamentoAlterarStatusVariants() {
		$row = ['controller' => 'Faturamento', 'action' => 'edit,alterar_status,alterarStatus,gerarDeOS'];
		$this->assertTrue(RbacChecker::matchAction('Faturamento', 'alterarStatus', $row));
	}

	public function testMatchActionUsersVerificaAndPermissaoPortal() {
		$this->assertTrue(RbacChecker::matchAction('Users', 'verificasenha', [
			'controller' => 'Users', 'action' => 'verificasenha',
		]));
		$this->assertTrue(RbacChecker::matchAction('Users', 'verificadadoscliente', [
			'controller' => 'Users', 'action' => 'verificadadoscliente',
		]));
		$this->assertTrue(RbacChecker::matchAction('Users', 'permissaoacesso', [
			'controller' => 'Users', 'action' => 'permissaoacesso',
		]));
	}

	public function testMatchActionClientesExtendedRoutes() {
		$view = ['controller' => 'Clientes', 'action' => 'index,view,search,eventos,contrato,clientebyid,cliente_by_id,solicitantes,solicitante,cliemail,solemail'];
		$this->assertTrue(RbacChecker::matchAction('Clientes', 'eventos', $view));
		$this->assertTrue(RbacChecker::matchAction('Clientes', 'cliente_by_id', $view));
		$this->assertTrue(RbacChecker::matchAction('Clientes', 'sincronizacliente', [
			'controller' => 'Clientes',
			'action' => 'edit,consultacnpj,consulta_cnpj,consultaIe,consulta_ie,cidadesestado,cidades_estado,sincronizacliente,sincroniza_cliente',
		]));
		$this->assertTrue(RbacChecker::matchAction('Clientes', 'sincroniza_cliente', [
			'controller' => 'Clientes',
			'action' => 'edit,consultacnpj,consulta_cnpj,consultaIe,consulta_ie,cidadesestado,cidades_estado,sincronizacliente,sincroniza_cliente',
		]));
		$this->assertTrue(RbacChecker::matchAction('Clientes', 'cadastrar', [
			'controller' => 'Clientes',
			'action' => 'add,cadastrar,consultacnpj,consulta_cnpj,consultaIe,consulta_ie,cidadesestado,cidades_estado',
		]));
		$this->assertTrue(RbacChecker::matchAction('Clientes', 'consulta_cnpj', [
			'controller' => 'Clientes',
			'action' => 'add,cadastrar,consultacnpj,consulta_cnpj,consultaIe,consulta_ie,cidadesestado,cidades_estado',
		]));
		$this->assertTrue(RbacChecker::matchAction('Clientes', 'consultaIe', [
			'controller' => 'Clientes',
			'action' => 'edit,consultacnpj,consulta_cnpj,consultaIe,consulta_ie,cidadesestado,cidades_estado,sincronizacliente,sincroniza_cliente',
		]));
	}

	public function testMatchActionOrcamentosCatalogoSugestoes() {
		$row = ['controller' => 'Orcamentos', 'action' => 'solicitar,catalogosugestoes'];
		$this->assertTrue(RbacChecker::matchAction('Orcamentos', 'catalogoSugestoes', $row));
	}

	public function testMatchActionOrdensservicoImprimirordens() {
		$row = ['controller' => 'Ordensservico', 'action' => 'view,imprimir,imprimirordens,imprimir_ordens'];
		$this->assertTrue(RbacChecker::matchAction('Ordensservico', 'imprimirordens', $row));
		$this->assertTrue(RbacChecker::matchAction('Ordensservico', 'imprimir_ordens', $row));
	}

	public function testMatchActionOrcamentosUnderscoreAndCamelCaseRoutes() {
		$update = ['controller' => 'Orcamentos', 'action' => 'edit,alterarsituacao,alterar_situacao,envioassinatura,envio_assinatura,criarMov,criar_mov'];
		$this->assertTrue(RbacChecker::matchAction('Orcamentos', 'alterarSituacao', $update));
		$this->assertTrue(RbacChecker::matchAction('Orcamentos', 'alterar_situacao', $update));
		$this->assertTrue(RbacChecker::matchAction('Orcamentos', 'envioAssinatura', $update));
		$this->assertTrue(RbacChecker::matchAction('Orcamentos', 'envio_assinatura', $update));
		$this->assertTrue(RbacChecker::matchAction('Orcamentos', 'criarMov', $update));
		$view = ['controller' => 'Orcamentos', 'action' => 'imprimirPdf,imprimir_pdf,seguroProposta,seguro_proposta'];
		$this->assertTrue(RbacChecker::matchAction('Orcamentos', 'imprimirPdf', $view));
		$this->assertTrue(RbacChecker::matchAction('Orcamentos', 'imprimir_pdf', $view));
	}

	public function testMatchActionProdutosPricingAndStockUnderscores() {
		$pricing = ['controller' => 'Produtos', 'action' => 'precificacao,salvarPrecos,salvar_precos'];
		$this->assertTrue(RbacChecker::matchAction('Produtos', 'salvarPrecos', $pricing));
		$this->assertTrue(RbacChecker::matchAction('Produtos', 'salvar_precos', $pricing));
		$stock = ['controller' => 'Produtos', 'action' => 'qtdestoque,qtde_estoque,estoquesLote,estoques_lote,estoquePdf,estoque_pdf'];
		$this->assertTrue(RbacChecker::matchAction('Produtos', 'estoquesLote', $stock));
		$this->assertTrue(RbacChecker::matchAction('Produtos', 'estoques_lote', $stock));
		$this->assertTrue(RbacChecker::matchAction('Produtos', 'estoquePdf', $stock));
	}

	public function testMatchActionFaturamentoGerarDeOsUnderscore() {
		$row = ['controller' => 'Faturamento', 'action' => 'edit,gerarDeOS,gerar_de_os'];
		$this->assertTrue(RbacChecker::matchAction('Faturamento', 'gerarDeOS', $row));
		$this->assertTrue(RbacChecker::matchAction('Faturamento', 'gerar_de_os', $row));
	}

	public function testMatchActionTicketsDownloadAndCancelamento() {
		$view = ['controller' => 'Tickets', 'action' => 'cancelamento,cancelamentoview,downloadAnexo,downloadFile,download_anexo,download_file'];
		$this->assertTrue(RbacChecker::matchAction('Tickets', 'downloadAnexo', $view));
		$this->assertTrue(RbacChecker::matchAction('Tickets', 'download_anexo', $view));
		$this->assertTrue(RbacChecker::matchAction('Tickets', 'cancelamento', $view));
		$upd = ['controller' => 'Tickets', 'action' => 'edit,alterarsituacao,alterar_situacao'];
		$this->assertTrue(RbacChecker::matchAction('Tickets', 'alterar_situacao', $upd));
	}

	public function testMatchActionFinanceiroUnderscoreRoutes() {
		$view = ['controller' => 'Financeiro', 'action' => 'contasReceber,contas_receber,exportarFaturaPdf,exportar_fatura_pdf,baixarAnexoFatura,baixar_anexo_fatura'];
		$this->assertTrue(RbacChecker::matchAction('Financeiro', 'contasReceber', $view));
		$this->assertTrue(RbacChecker::matchAction('Financeiro', 'contas_receber', $view));
		$this->assertTrue(RbacChecker::matchAction('Financeiro', 'exportarFaturaPdf', $view));
		$this->assertTrue(RbacChecker::matchAction('Financeiro', 'exportar_fatura_pdf', $view));
		$recv = ['controller' => 'Financeiro', 'action' => 'registrarRecebimento,registrar_recebimento'];
		$this->assertTrue(RbacChecker::matchAction('Financeiro', 'registrarRecebimento', $recv));
		$this->assertTrue(RbacChecker::matchAction('Financeiro', 'registrar_recebimento', $recv));
		$anex = ['controller' => 'Financeiro', 'action' => 'adicionarAnexoFatura,adicionar_anexo_fatura,removerAnexoFatura,remover_anexo_fatura'];
		$this->assertTrue(RbacChecker::matchAction('Financeiro', 'adicionar_anexo_fatura', $anex));
	}

	public function testMatchActionServicedeskDownloadAnexo() {
		$row = ['controller' => 'Servicedesk', 'action' => 'index,operacional,view,downloadAnexo,download_anexo'];
		$this->assertTrue(RbacChecker::matchAction('Servicedesk', 'downloadAnexo', $row));
		$this->assertTrue(RbacChecker::matchAction('Servicedesk', 'download_anexo', $row));
		$this->assertFalse(RbacChecker::matchAction('Tickets', 'downloadAnexo', $row));
	}

	public function testMatchActionPermissoesQueuesConfigUnderscoreRoutes() {
		$perm = ['controller' => 'Permissoes', 'action' => 'adminSyncRegistry,admin_sync_registry'];
		$this->assertTrue(RbacChecker::matchAction('Permissoes', 'admin_sync_registry', $perm));
		$q = ['controller' => 'Queues', 'action' => 'adminIndex,admin_index,apiForTicket,api_for_ticket'];
		$this->assertTrue(RbacChecker::matchAction('Queues', 'admin_index', $q));
		$this->assertTrue(RbacChecker::matchAction('Queues', 'api_for_ticket', $q));
		$cfg = ['controller' => 'Config', 'action' => 'createFinanceiroIfNotExist,create_financeiro_if_not_exist'];
		$this->assertTrue(RbacChecker::matchAction('Config', 'create_financeiro_if_not_exist', $cfg));
	}

	public function testMatchActionPortalRelatoriosAndVisitasClienteUnderscore() {
		$pr = ['controller' => 'PortalRelatorios', 'action' => 'exportar,exportarExcel,exportar_excel'];
		$this->assertTrue(RbacChecker::matchAction('PortalRelatorios', 'exportar_excel', $pr));
		$v = ['controller' => 'Visitas', 'action' => 'indexcliente,index_cliente'];
		$this->assertTrue(RbacChecker::matchAction('Visitas', 'index_cliente', $v));
	}

	public function testMatchActionFaturasAndContractManagementUnderscores() {
		$fat = ['controller' => 'Faturas', 'action' => 'viewitem,view_item,aprovarhash,aprovar_hash,limpa_carrinho'];
		$this->assertTrue(RbacChecker::matchAction('Faturas', 'view_item', $fat));
		$this->assertTrue(RbacChecker::matchAction('Faturas', 'aprovar_hash', $fat));
		$cm = ['controller' => 'ContractManagement', 'action' => 'downloadPdf,download_pdf,gerarPdf,gerar_pdf'];
		$this->assertTrue(RbacChecker::matchAction('ContractManagement', 'download_pdf', $cm));
		$this->assertTrue(RbacChecker::matchAction('ContractManagement', 'gerar_pdf', $cm));
	}

	public function testMatchActionTicketsAssignTimerUnderscore() {
		$asg = ['controller' => 'Tickets', 'action' => 'apiStartTicket,api_start_ticket'];
		$this->assertTrue(RbacChecker::matchAction('Tickets', 'api_start_ticket', $asg));
		$tm = ['controller' => 'Tickets', 'action' => 'timerIniciar,timer_iniciar,apiTimer,api_timer'];
		$this->assertTrue(RbacChecker::matchAction('Tickets', 'timer_iniciar', $tm));
		$this->assertTrue(RbacChecker::matchAction('Tickets', 'api_timer', $tm));
	}

	public function testMatchActionPortalNotificationsAndTicketsPortalUnderscore() {
		$n = ['controller' => 'PortalNotifications', 'action' => 'unreadCount,unread_count,markRead,mark_read'];
		$this->assertTrue(RbacChecker::matchAction('PortalNotifications', 'unread_count', $n));
		$this->assertTrue(RbacChecker::matchAction('PortalNotifications', 'mark_read', $n));
		$tp = ['controller' => 'Tickets', 'action' => 'indexcliente,index_cliente,apiindexcliente,api_index_cliente'];
		$this->assertTrue(RbacChecker::matchAction('Tickets', 'index_cliente', $tp));
		$this->assertTrue(RbacChecker::matchAction('Tickets', 'api_index_cliente', $tp));
	}

	public function testMatchActionOrdensservicoCarrinhoUnderscore() {
		$row = ['controller' => 'Ordensservico', 'action' => 'carrinhoadd,carrinho_add,relatorioVer,relatorio_ver'];
		$this->assertTrue(RbacChecker::matchAction('Ordensservico', 'carrinho_add', $row));
		$this->assertTrue(RbacChecker::matchAction('Ordensservico', 'relatorio_ver', $row));
	}

	public function testMatchActionPortalNotificationsJson() {
		$rowRead = ['controller' => 'PortalNotifications', 'action' => 'unreadCount,listJson'];
		$this->assertTrue(RbacChecker::matchAction('PortalNotifications', 'unreadCount', $rowRead));
		$this->assertTrue(RbacChecker::matchAction('PortalNotifications', 'listJson', $rowRead));
		$rowWrite = ['controller' => 'PortalNotifications', 'action' => 'markRead,markAllRead,preferences,savePreferences'];
		$this->assertTrue(RbacChecker::matchAction('PortalNotifications', 'markRead', $rowWrite));
		$this->assertTrue(RbacChecker::matchAction('PortalNotifications', 'savePreferences', $rowWrite));
	}

	public function testUserHasPermissionCodeRejectsInvalidUserId() {
		$this->assertFalse(RbacChecker::userHasPermissionCode(0, 'config.manage'));
		$this->assertFalse(RbacChecker::userHasPermissionCode(-1, 'config.manage'));
	}

	public function testUserHasPermissionCodeRejectsEmptyCode() {
		$this->assertFalse(RbacChecker::userHasPermissionCode(1, ''));
		$this->assertFalse(RbacChecker::userHasPermissionCode(1, '   '));
	}

	public function testPermOrcamentosSolicitarConstant() {
		$this->assertSame('orcamentos.solicitar', RbacChecker::PERM_ORCAMENTOS_SOLICITAR);
	}

	public function testClientePodeSolicitarOrcamentoRejectsEmptyPermissaoAcesso() {
		$this->assertFalse(RbacChecker::clientePodeSolicitarOrcamento(1, ''));
		$this->assertFalse(RbacChecker::clientePodeSolicitarOrcamento(1, '   '));
		$this->assertFalse(RbacChecker::clientePodeSolicitarOrcamento(1, null));
		$this->assertFalse(RbacChecker::clientePodeSolicitarOrcamento(1, 0));
	}

	public function testClientePodeSolicitarOrcamentoRejectsNonPositiveUserId() {
		$this->assertFalse(RbacChecker::clientePodeSolicitarOrcamento(0, 1));
		$this->assertFalse(RbacChecker::clientePodeSolicitarOrcamento(-5, 1));
	}

	public function testResourceFieldAccessReturnsNullForInvalidUserOrKey() {
		$this->assertNull(RbacChecker::resourceFieldAccess(0, 'Foo.field.x'));
		$this->assertNull(RbacChecker::resourceFieldAccess(1, ''));
		$this->assertNull(RbacChecker::resourceFieldAccess(1, '   '));
		$this->assertNull(RbacChecker::resourceFieldAccess(1, "\t"));
	}

	public function testShouldShowConfigAdminHubRequiresEquipeAdmin() {
		Configure::write('Rbac', ['menu_filter_config' => false]);
		$this->assertFalse(RbacChecker::shouldShowConfigAdminHub(null, 0, 1));
		$this->assertFalse(RbacChecker::shouldShowConfigAdminHub(1, 1, 1));
	}

	public function testShouldShowConfigAdminHubWhenMenuFilterOff() {
		Configure::write('Rbac', ['menu_filter_config' => false]);
		$this->assertTrue(RbacChecker::shouldShowConfigAdminHub(1, 0, 42));
	}

	public function testShouldShowConfigAdminHubStrictWithoutUserId() {
		Configure::write('Rbac', ['menu_filter_config' => true]);
		$this->assertFalse(RbacChecker::shouldShowConfigAdminHub(1, 0, 0));
		$this->assertFalse(RbacChecker::shouldShowConfigAdminHub(1, 0, null));
	}

	public function testShouldShowPermissoesRbacShortcutOnlyNonAdminEquipe() {
		$this->assertFalse(RbacChecker::shouldShowPermissoesRbacShortcut(1, 0, 10));
		$this->assertFalse(RbacChecker::shouldShowPermissoesRbacShortcut(0, 1, 10));
		$this->assertFalse(RbacChecker::shouldShowPermissoesRbacShortcut(0, 0, 0));
	}

	public function testBuildSidebarMenuGatesIncludesDashboardWhenConfigured() {
		Configure::write('Rbac', [
			'menu_filter_sidebar' => false,
			'menu_sidebar_gates' => [
				'dashboard' => 'dashboard.view',
				'clientes' => 'clientes.view',
			],
		]);
		$gates = RbacChecker::buildSidebarMenuGates(1, 0, 999);
		$this->assertArrayHasKey('dashboard', $gates);
		$this->assertTrue($gates['dashboard']);
	}

	public function testBuildSidebarMenuGatesIncludesSidebarFunctionsSearchWhenFilterOff() {
		Configure::write('Rbac', [
			'menu_filter_sidebar' => false,
			'menu_sidebar_gates' => [
				'sidebar_functions_search' => 'pesquisa.sidebar_search',
			],
		]);
		$gates = RbacChecker::buildSidebarMenuGates(1, 0, 999);
		$this->assertArrayHasKey('sidebar_functions_search', $gates);
		$this->assertTrue($gates['sidebar_functions_search']);
	}

	public function testBuildSidebarMenuGatesIncludesPhase6fKeysWhenFilterOff() {
		$sidebarGates = [
			'sidebar_notifications_bell' => ['portal.notifications', 'portal.notifications.read', 'portal.notifications.write'],
			'footer_acesso_remoto' => 'normasempresa.acessoremoto',
			'footer_perfil_senha' => ['users.profile', 'users.password'],
			'footer_twofactor_menu' => 'users.twofactor',
		];
		Configure::write('Rbac', [
			'menu_filter_sidebar' => false,
			'menu_sidebar_gates' => $sidebarGates,
		]);
		$gates = RbacChecker::buildSidebarMenuGates(1, 0, 999);
		$this->assertSame(array_keys($sidebarGates), array_keys($gates));
		foreach (array_keys($sidebarGates) as $key) {
			$this->assertTrue($gates[$key], $key);
		}
	}

	public function testBuildSidebarMenuGatesIncludesPrefaturamentoSubgateKeysWhenFilterOff() {
		$sidebarGates = [
			'prefaturamento_fila' => ['prefaturamento.queue', 'prefaturamento.manage'],
			'prefaturamento_conferencia' => ['prefaturamento.conferencia', 'prefaturamento.manage'],
		];
		Configure::write('Rbac', [
			'menu_filter_sidebar' => false,
			'menu_sidebar_gates' => $sidebarGates,
		]);
		$gates = RbacChecker::buildSidebarMenuGates(1, 0, 999);
		$this->assertSame(array_keys($sidebarGates), array_keys($gates));
		foreach (array_keys($sidebarGates) as $key) {
			$this->assertTrue($gates[$key], $key);
		}
	}

	public function testBuildSidebarMenuGatesIncludesAdvancedModuleSubgateKeysWhenFilterOff() {
		$sidebarGates = [
			'advanced_module_gestao' => ['erp.contracts.management', 'erp.advanced.contracts'],
			'advanced_module_modelos' => ['erp.contracts.templates', 'erp.advanced.contracts'],
			'advanced_module_faturas' => ['erp.advanced.invoices', 'erp.advanced.invoices.view'],
		];
		Configure::write('Rbac', [
			'menu_filter_sidebar' => false,
			'menu_sidebar_gates' => $sidebarGates,
		]);
		$gates = RbacChecker::buildSidebarMenuGates(1, 0, 999);
		$this->assertSame(array_keys($sidebarGates), array_keys($gates));
		foreach (array_keys($sidebarGates) as $key) {
			$this->assertTrue($gates[$key], $key);
		}
	}

	public function testBuildSidebarMenuGatesIncludesAllPhase6cGateKeysWhenFilterOff() {
		$sidebarGates = [
			'relatorios_painel' => 'relatorios.painel.view',
			'relatorios_indicadores_adv' => 'relatorios.indicadores.view',
			'ordensservico_list' => ['ordensservico.list', 'ordensservico.full'],
			'ordensservico_nova' => ['ordensservico.create', 'ordensservico.full'],
			'tickets_servicedesk' => ['servicedesk.view', 'servicedesk.tickets'],
			'tickets_historico' => ['tickets.view', 'tickets.api'],
		];
		Configure::write('Rbac', [
			'menu_filter_sidebar' => false,
			'menu_sidebar_gates' => $sidebarGates,
		]);
		$gates = RbacChecker::buildSidebarMenuGates(1, 0, 999);
		$this->assertSame(array_keys($sidebarGates), array_keys($gates));
		foreach (array_keys($sidebarGates) as $key) {
			$this->assertTrue($gates[$key], $key);
		}
	}

	public function testShouldShowSidebarGateDashboardBypassWhenMenuFilterOff() {
		Configure::write('Rbac', [
			'menu_filter_sidebar' => false,
			'menu_sidebar_gates' => ['dashboard' => 'dashboard.view'],
		]);
		$this->assertTrue(RbacChecker::shouldShowSidebarGate(1, 0, 1, 'dashboard.view'));
	}

	public function testShouldShowSidebarGateNonEquipeAlwaysTrueEvenWithStrictMenu() {
		Configure::write('Rbac', ['menu_filter_sidebar' => true]);
		$this->assertTrue(RbacChecker::shouldShowSidebarGate(0, 1, 0, 'dashboard.view'));
	}

	public function testShouldShowSidebarGateEquipeAdminEmptyCodeListBypassesStrictMenu() {
		Configure::write('Rbac', ['menu_filter_sidebar' => true]);
		$this->assertTrue(RbacChecker::shouldShowSidebarGate(1, 0, 0, ['', '  ']));
	}

	public function testShouldShowSidebarGateEquipeNonAdminStrictMenuInvalidUserIdFalse() {
		Configure::write('Rbac', ['menu_filter_sidebar' => true]);
		$this->assertFalse(RbacChecker::shouldShowSidebarGate(0, 0, 0, 'dashboard.view'));
	}

	public function testBuildSidebarMenuGatesOmitsBlankKeys() {
		Configure::write('Rbac', [
			'menu_filter_sidebar' => false,
			'menu_sidebar_gates' => [
				'' => 'dashboard.view',
				'ok' => 'dashboard.view',
			],
		]);
		$gates = RbacChecker::buildSidebarMenuGates(1, 0, 1);
		$this->assertSame(['ok'], array_keys($gates));
	}

	public function testBuildSidebarMenuGatesReturnsEmptyWhenGatesNotArray() {
		Configure::write('Rbac', [
			'menu_filter_sidebar' => false,
			'menu_sidebar_gates' => 'dashboard.view',
		]);
		$this->assertSame([], RbacChecker::buildSidebarMenuGates(1, 0, 1));
	}

	public function testBuildSidebarMenuGatesReturnsEmptyWhenRbacConfigMissing() {
		Configure::delete('Rbac');
		$this->assertSame([], RbacChecker::buildSidebarMenuGates(1, 0, 1));
	}
}
