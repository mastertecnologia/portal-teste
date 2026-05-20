<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation,
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use App\Utility\ErpApiRoutes;
use App\Utility\ErpIntegrationRequest;
use App\Utility\RbacChecker;
use App\Service\Common\ModelService;
use App\View\Sidebar\PgmPortalSidebarNotifUrls;
use App\View\Sidebar\PgmSidebarClientPayloadBuilder;
use App\View\Sidebar\PgmSidebarStaffContext;
use App\View\Sidebar\PgmSidebarStaffPayloadBuilder;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Event\Event;

class AppController extends Controller
{
    /** @var string|null escopo ABAC da permissão RBAC que autorizou a ação (empresa|cliente|own) */
    public $rbacAbacScope = null;

    /** @var string|null código da permissão RBAC que autorizou (ex.: clientes.manage) */
    public $rbacAbacPermissionCode = null;

    public function initialize()
    {
        parent::initialize();
        // Use ModelService instead of repetitive loadModel calls
        // This reduces code duplication and improves performance through caching
        ModelService::loadModelsIntoController($this, ["Atividades", "Users", "Empresas", "Empresasusers"]);

        $this->loadComponent("RequestHandler", [
            "enableBeforeRedirect" => false,
        ]);
        $this->loadComponent("Flash");
        $this->loadComponent("Rbac");
        $this->loadComponent("Abac");
        $this->loadComponent("Security", [
            "unlockedActions" => [
                "login",
                "logout",
                "loginempresa",
                "acessoEmpresa",
                "loginduasetapas",
                "verificaloginduasetapas",
                "verificacodigo",
                "add",
                "edit",
                // Troca de empresa via dropdown (AJAX) não envia _Token.
                "alteraempresa",
                "carrinho",
                "carrinhoadd",
                "carrinhoedititem",
                "carrinhodelitem",
                "valortotal",
                "acaoindex",
                "addservico",
                "limpacarrinho",
                "additem",
                "edititem",
                "deleteitem",
                "excluiitemcarrinho",
                "getitemcarrinho",
                "edititemcarrinho",
                "editaitemcarrinho",
                "carrinhoedit",
                // Solicitar orçamento: inputs HTML + itens dinâmicos (itens[n][*]) fora do FormHelper
                "solicitar",
                "catalogoSugestoes",
                "timerIniciar",
                "timerPausar",
                "timerRetomar",
                "timerFinalizar",
                "produto",
                "qtdestoque",
                "estoquesLote", // Orçamentos: produto, estoque e catálogo (lote)
                // APIs de integração ERP (sem sessão web; token em header) — ver ErpApiRoutes
                ...ErpApiRoutes::securityUnlockedActionNames(),
                // Clientes: consulta CNPJ (Receita) e IE (SEFAZ/SINTEGRA) via AJAX
                "consultacnpj",
                "consultaIe",
                // API cadastro consolidado (CadastroController)
                "empresa",
                "consultar",
                // Tickets UI React (JSON; autenticação via sessão Auth)
                "apiIndex",
                "apiDashboardOperacional",
                "apiIndexCliente",
                "apiView",
                "apiComments",
                "apiSaveTicket",
                "apiTimer",
                "apiTicketSlaPause",
                "apiTicketSlaResume",
                "apiTimeline",
                "apiValidateGeolocation",
                "apiTicketSignature",
                "apiAddTicketProduct",
                "apiAddEvidencePhoto",
                "apiPdfTicketOs",
                "apiPdfLaudo",
                "apiTicketMessages",
                "apiRealtimeToken",
                "apiServicedeskData",
                "apiTicketAssetsAttach",
                "apiTicketAssetsDetach",
                "apiValidate",
                "apiSetUserAuditPassword",
                "apiAlterarSituacao",
                "apiAnexoUpload",
                "apiAnexoDelete",
                "apiTecnicosLista",
                "apiTicketSubjectOptions",
                "apiTransferirTicket",
                "apiPatchAssignment",
                "apiPatchTicketStatus",
                "apiPatchTicketPriority",
                "apiPatchTicketSubject",
                "apiStartTicket",
                "startTicket",
                "workflowSlaAdmin",
                "workflowSlaPolicies",
                "workflowSla",
                "workflowStates",
                "workflowTransitions",
                "workflowTransition",
                "workflowSlaLogs",
                "workflowSlaDuplicate",
                "workflowSlaEmpresasOptions",
                "contractSlaApi",
                "apiForTicket",
                "apiForUser",
                "getAvailableQueues",
                "apiEnsureDefaults",
                "apiSupportLevels",
                "apiSave",
                "adminIndex",
                "adminEdit",
                "adminTechnicians",
                "adminDelete",
                "adminEnsureDefaults",
                "apiAdd",
                "adminSyncRegistry",
                "adminMatrix",
                "adminMatrixSave",
                "adminGrantSuperAll",
                "adminUsers",
                "adminUserRoles",
                "adminUserEffective",
                "adminPermissionPolicies",
                "adminPermissionPolicyEdit",
                "adminPermissionPolicyDelete",
                // Cofre de senhas: revelar segredo via POST JSON (senha admin nunca na URL)
                "vaultReveal",
                "verificasenha",
                "verificadadoscliente",
                // selectTheme: desbloqueado do _Token do Security (POST AJAX só com theme);
                // CSRF validado manualmente em UsersController::selectTheme (hash_equals _csrfToken).
                "selectTheme",
                // Faturamento: modal alterar status (POST sem _Token no corpo)
                "alterarStatus",
                // Fiscal: consultas SEFAZ (form POST sem _Token completo)
                "consultarCadastro",
                "consultarChave",
                // Fiscal dashboard: fetch JSON (GET sem _Token)
                "statusSefaz",
                "distribuicaoDfe",
                // Financeiro: ações AJAX (registrar recebimento/pagamento, cancelar)
                "registrarRecebimento",
                "registrarPagamento",
                "cancelarDespesa",
                "conciliarExtrato",
                // Webhook Autentique (corpo JSON; sem _Token)
                "webhookAutentique",
                // Laudos: API JSON (fetch/AJAX sem _Token)
                "clientesBuscar",
                "delete",
                "changeStatus",
                "duplicar",
                "historico",
                "uploadImagem",
                "deleteImagem",
                "uploadAnexo",
                "downloadAnexo",
                "pecas",
                "addPeca",
                "servicos",
                "templates",
                "enviarEmail",
                "publica",
                "validar",
            ],
        ]);
        $this->loadComponent("Auth", [
            "loginAction" => [
                "controller" => "Users",
                "action" => "login",
                // Sem isto, pedidos sob prefixo (ex.: api/laudos) geram URL de login com o mesmo prefixo e o Router falha.
                "prefix" => false,
            ],
            "authenticate" => [
                "Form" => [
                    "userModel" => "Users",
                    // Credencial no POST continua com chave "username"; o Form autentica contra a coluna users.email.
                    // idempresa é escolhido após o login (getEmpresaPreferencial).
                    "fields" => [
                        "username" => "email",
                        "password" => "password",
                    ],
                ],
            ],
            "loginRedirect" => [
                "controller" => "Users",
                "action" => "dashboard",
                "prefix" => false,
            ],
            "logoutRedirect" => [
                "controller" => "Users",
                "action" => "login",
                "prefix" => false,
            ],
            "authError" => false,
        ]);

        $this->Auth->setConfig("authorize", ["Controller"]);
    }

    public function beforeRender(Event $event)
    {
        $c = (string) $this->request->getParam("controller");
        if (
            preg_match(
                "/^(Advanced|PortalAdvanced|ContractManagement|PortalContratos)/",
                $c,
            )
        ) {
            $this->set("pgmAdvancedModuleStylesheet", true);
        }

        if (
            (bool) Configure::read("PgmSidebar.react_enabled")
            && $this->components()->has("Auth")
            && $this->Auth->user("id")
        ) {
            $role = (int) $this->Auth->user("role");
            if ($role === 0) {
                $ctx = PgmSidebarStaffContext::computeFromArray(
                    $this->viewVars,
                    $this->request,
                );
                $props = PgmSidebarStaffPayloadBuilder::build(
                    $ctx,
                    $this->viewVars,
                    $this->request,
                );
                $api = PgmPortalSidebarNotifUrls::build(
                    $this->request,
                    $this->response,
                    isset($ctx["sg"]) && is_array($ctx["sg"]) ? $ctx["sg"] : [],
                    0,
                );
                if ($api !== null) {
                    $props["notificationBellApi"] = $api;
                }
                $this->set("pgmSidebarReactProps", $props);
            } else {
                $this->set(
                    "pgmSidebarReactProps",
                    PgmSidebarClientPayloadBuilder::build(
                        $this->viewVars,
                        $this->request,
                    ),
                );
            }
        }
    }

    public function afterFilter(Event $event)
    {
        $controllerLower = strtolower(
            (string) $this->request->getParam("controller"),
        );
        $actionLower = strtolower((string) $this->request->getParam("action"));
        if (ErpApiRoutes::matches($controllerLower, $actionLower)) {
            $this->response = ErpIntegrationRequest::applyCorsHeaders(
                $this->response,
                $this->request,
            );
        }
    }

    public function beforeFilter(Event $event)
    {
        ini_set("memory_limit", "518M");
        set_time_limit(0);

        $controller = $this->request->getParam("controller");
        $action = $this->request->getParam("action");
        $controllerLower = strtolower($controller);
        $actionLower = strtolower($action);

        if (ErpApiRoutes::matches($controllerLower, $actionLower)) {
            $this->response = ErpIntegrationRequest::applyCorsHeaders(
                $this->response,
                $this->request,
            );
            if ($this->request->is("options")) {
                return $this->response->withStatus(204);
            }
        }

        $path = (string)$this->request->getPath();
        if (
            strpos($path, '-prototype') !== false
            || strpos($path, '/prototype-history') === 0
            || $controller === 'PrototypeHistory'
        ) {
            $erpLocale = (string)$this->request->getSession()->read('Erp.locale');
            if (in_array($erpLocale, ['pt_BR', 'en_US', 'es'], true)) {
                \Cake\I18n\I18n::setLocale($erpLocale);
            }
        }

        $role = $this->Auth->user("role");
        $iduser = $this->Auth->user("id");
        $admin = $this->Auth->user("admin");
        $setor = $this->Auth->user("setor");
        $idcliente = $this->Auth->user("idcliente");
        $empresa = $this->Auth->user("idempresa");

        $showConfigAdminHub = RbacChecker::shouldShowConfigAdminHub(
            $admin,
            $role,
            $iduser,
        );
        $showPermissoesRbacShortcut = RbacChecker::shouldShowPermissoesRbacShortcut(
            $admin,
            $role,
            $iduser,
        );

        $menuStates = [
            "dashboard" => "",
            "usuarios" => "",
            "clientesActive" => "",
            "empresasActive" => "",
            "empresasusersActive" => "",
            "configuracoes" => "",
            "relActive" => "",
            "produtosActive" => "",
            "visitasActive" => "",
            "orcamentosActive" => "",
            "faturamentoActive" => "",
            "financeiroActive" => "",
            "areasActive" => "",
            "problemasActive" => "",
            "ordensActive" => "",
            "ticketsActive" => "",
            "senhasActive" => "",
            "laudosActive" => "",
            "faturasActive" => "",
            "prefaturamentoActive" => "",
            "fiscalModuleActive" => "",
            "config" => "",
            "queuesAtendimentoActive" => "",
            "advancedModuleActive" => "",
        ];

        if ($action === "dashboard") {
            $menuStates["dashboard"] = "active";
        }

        if (
            in_array(
                $controllerLower,
                [
                    "config",
                    "empresasusers",
                    "empresas",
                    "users",
                    "clientes",
                    "areas",
                    "problemas",
                    "visitas",
                    "feriados",
                    "queues",
                    "permissoes",
                ],
                true,
            )
        ) {
            $menuStates["config"] = "active";
        }

        $controllerToMenuMap = [
            "users" => "usuarios",
            "clientes" => "clientesActive",
            "empresas" => "empresasActive",
            "empresasusers" => "empresasusersActive",
            "produtos" => "produtosActive",
            "config" => "configuracoes",
            "relatorios" => "relActive",
            "visitas" => "visitasActive",
            "orcamentos" => "orcamentosActive",
            "financeiro" => "financeiroActive",
            "financeirobancos" => "financeiroActive",
            "faturamento" => "faturamentoActive",
            "areas" => "areasActive",
            "problemas" => "problemasActive",
            "ordensservico" => "ordensActive",
            "bancosenhas" => "senhasActive",
            "faturas" => "faturasActive",
            "prefaturamento" => "prefaturamentoActive",
            "fiscal" => "fiscalModuleActive",
            "fiscalnotas" => "fiscalModuleActive",
            "fiscalnotasentrada" => "fiscalModuleActive",
            "fiscalcertificados" => "fiscalModuleActive",
            "fiscalconfig" => "fiscalModuleActive",
            "fiscalrelatorios" => "fiscalModuleActive",
            "tickets" => "ticketsActive",
            "servicedesk" => "ticketsActive",
            "portaladvancedattendance" => "ticketsActive",
            "queues" => "queuesAtendimentoActive",
            "advancedcontracts" => "advancedModuleActive",
            "contractmanagement" => "advancedModuleActive",
            "contracttemplates" => "advancedModuleActive",
            "advancedinvoices" => "advancedModuleActive",
            "advancedreports" => "relActive",
            "laudos" => "laudosActive",
        ];

        if (isset($controllerToMenuMap[$controllerLower])) {
            $menuStates[$controllerToMenuMap[$controllerLower]] = "active";
        }

        $this->set("role", $role);
        $this->set("admin", $admin);
        $this->set("setor", $setor);
        $this->set("empresa", $empresa);
        if (!empty($empresa)) {
            try {
                $this->set(
                    "nomeempresa",
                    $this->Empresas->get($empresa)->razaosocial,
                );
            } catch (RecordNotFoundException $e) {
                $this->set("nomeempresa", "Grid Sistemas");
            }
        } else {
            $this->set("nomeempresa", "Grid Sistemas");
        }
        $this->set("idempresa", $this->Auth->user("idempresa"));
        $this->set("name", $this->Auth->user("name"));
        $this->set("permissaoacesso", $this->Auth->user("permissaoacesso"));
        $this->set("showConfigAdminHub", $showConfigAdminHub);
        $this->set("showPermissoesRbacShortcut", $showPermissoesRbacShortcut);
        $this->set(
            "sidebarMenuGates",
            RbacChecker::buildSidebarMenuGates($admin, $role, $iduser),
        );
        $canClienteSolicitarOrcamento = false;
        if ((int) $role === 1) {
            $canClienteSolicitarOrcamento = RbacChecker::clientePodeSolicitarOrcamento(
                (int) $iduser,
                !empty($this->Auth->user("permissaoacesso")),
            );
        }
        $this->set(
            "canClienteSolicitarOrcamento",
            $canClienteSolicitarOrcamento,
        );
        $this->set($menuStates);
        $this->set("idcliente", $idcliente);
        $this->set("iduser", $iduser);

        $srcBase = $this->request->getAttribute("src");
        $url =
            (string) ($srcBase !== null ? $srcBase : "") .
            "template/notificacoes/notificacoes/";
        $this->set("url", $url);

        $empresasOptSidebar = [];
        // Sem iduser, não buscar (evita WHERE iduser IS NULL e erro ao acessar empresa nula no PHP 8+).
        if (!empty($iduser)) {
            foreach (
                $this->Empresasusers
                    ->find("all")
                    ->where(["iduser" => $iduser])
                    ->contain(["Empresas" => ["fields" => ["nomefantasia"]]])
                    ->order(["Empresas.nomefantasia" => "ASC"])
                    ->toArray()
                as $reg
            ) {
                if (!empty($reg->empresa)) {
                    $empresasOptSidebar[$reg->idempresa] =
                        $reg->empresa->nomefantasia;
                }
            }
        }
        $this->set("empresasOptSidebar", $empresasOptSidebar);

        // Obtém os dados atualizados do usuário logado
        if ($this->Auth->user("id") > 0) {
            try {
                $user = $this->Users->get($this->Auth->user("id"));
                // Tema exclusivamente escuro (skin-pgm-light descontinuado na UI).
                $this->set("skin", "skin-green");
                if (($user->skin ?? "") === "skin-pgm-light") {
                    $this->Users->updateAll(
                        ["skin" => "skin-green"],
                        ["id" => $user->id, "skin" => "skin-pgm-light"],
                    );
                }
                $u = $this->Auth->user();
                if (is_array($u) && ($u["skin"] ?? "") === "skin-pgm-light") {
                    $u["skin"] = "skin-green";
                    $this->Auth->setUser($u);
                }
                $this->set("sidebar", $user->sidebar);
                $this->set("pagelength", $user->pagelength);
            } catch (RecordNotFoundException $e) {
                $this->set("skin", "skin-green");
                $this->set("sidebar", 1);
                $this->set("pagelength", 25);
            }
        }

        if ($this->components()->has("Rbac") && $this->Auth->user("id") > 0) {
            $rbacResp = $this->Rbac->checkRequest($controller, $action);
            if ($rbacResp !== null) {
                return $rbacResp;
            }
        }
    }

    public function isAuthorized($user)
    {
        // APIs de integração ERP: permitir mesmo sem usuário logado (auth por token no header)
        $controller = strtolower((string) $this->request->getParam("controller"));
        $action = strtolower((string) $this->request->getParam("action"));
        if (ErpApiRoutes::matches($controller, $action)) {
            return true;
        }

        if (
            $controller === "pgmassets" &&
            in_array($action, ["css", "legacycss"], true)
        ) {
            return true;
        }

        // Se não há usuário logado, nega acesso por padrão
        if (empty($user)) {
            return false;
        }

        $prefix = $this->request->getParam("prefix");

        // Regra simples: qualquer rota com prefixo "admin" exige usuário admin
        if ($prefix === "admin") {
            return !empty($user["admin"]);
        }

        // Fora do prefixo admin, por padrão permite
        return true;
    }

    public function jsonResponse($responseData = [], $responseStatusCode = 200)
    {
        return $this->response
            ->withType("application/json")
            ->withStatus($responseStatusCode)
            ->withStringBody(
                json_encode(
                    $responseData,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                ),
            );
    }
}
