<?php
/**
 * Routes configuration
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Core\Plugin;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

/**
 * The default class to use for all routes
 *
 * The following route classes are supplied with CakePHP and are appropriate
 * to set as the default:
 *
 * - Route
 * - InflectedRoute
 * - DashedRoute
 *
 * If no call is made to `Router::defaultRouteClass()`, the class used is
 * `Route` (`Cake\Routing\Route\Route`)
 *
 * Note that `Route` does not do any inflections on URLs which will result in
 * inconsistently cased URLs when used with `:plugin`, `:controller` and
 * `:action` markers.
 *
 */
Router::defaultRouteClass(DashedRoute::class);

Router::scope("/", function ($routes) {
    $routes->connect("/", ["controller" => "Users", "action" => "dashboard"]);
    $routes->connect("/agenda", [
        "controller" => "Visitas",
        "action" => "index",
    ]);
    $routes->connect("/agenda/index", [
        "controller" => "Visitas",
        "action" => "index",
    ]);
    $routes->connect("/agenda/indexcliente", [
        "controller" => "Visitas",
        "action" => "indexcliente",
    ]);
    $routes->connect("/agenda/calendario", [
        "controller" => "Visitas",
        "action" => "calendario",
    ]);
    $routes->connect("/agenda/feriados", [
        "controller" => "Visitas",
        "action" => "feriados",
    ]);
    $routes->connect("/agenda/add", [
        "controller" => "Visitas",
        "action" => "add",
    ]);
    $routes->connect("/agenda/edit/*", [
        "controller" => "Visitas",
        "action" => "edit",
    ]);
    $routes->connect("/agenda/delete/*", [
        "controller" => "Visitas",
        "action" => "delete",
    ]);
    $routes->connect("/agenda/view/*", [
        "controller" => "Visitas",
        "action" => "view",
    ]);
    $routes->connect("/prefaturamento", [
        "controller" => "Prefaturamento",
        "action" => "index",
    ]);
    $routes->connect("/prefaturamento/index", [
        "controller" => "Prefaturamento",
        "action" => "index",
    ]);
    $routes
        ->connect("/prefaturamento/conferencia", [
            "controller" => "Prefaturamento",
            "action" => "conferencia",
        ])
        ->setMethods(["POST"]);
    $routes->connect("/locacao", [
        "controller" => "Faturas",
        "action" => "index",
    ]);
    $routes->connect("/locacao/index", [
        "controller" => "Faturas",
        "action" => "index",
    ]);
    $routes->connect("/locacao/view/*", [
        "controller" => "Faturas",
        "action" => "view",
    ]);
    $routes->connect("/locacao/add", [
        "controller" => "Faturas",
        "action" => "add",
    ]);
    $routes->connect("/locacao/edit/*", [
        "controller" => "Faturas",
        "action" => "edit",
    ]);
    $routes->connect("/locacao/imprimir/*", [
        "controller" => "Faturas",
        "action" => "imprimir",
    ]);
    $routes->connect("/locacao/aprovar/*", [
        "controller" => "Faturas",
        "action" => "aprovar",
    ]);
    $routes->connect("/locacao/rejeitar/*", [
        "controller" => "Faturas",
        "action" => "rejeitar",
    ]);
    $routes->connect("/locacao/receber/*", [
        "controller" => "Faturas",
        "action" => "receber",
    ]);
    $routes->connect("/locacao/devolveritem/*", [
        "controller" => "Faturas",
        "action" => "devolveritem",
    ]);
    $routes->connect("/locacao/recibo/*", [
        "controller" => "Faturas",
        "action" => "recibo",
    ]);
    // API integração ERP: listar ordens (ex.: liberadas para faturamento) e atualizar situação
    $routes
        ->connect("/ordensservico/list-api", [
            "controller" => "Ordensservico",
            "action" => "listAPI",
        ])
        ->setMethods(["GET", "POST"]);
    $routes
        ->connect("/ordensservico/listAPI", [
            "controller" => "Ordensservico",
            "action" => "listAPI",
        ])
        ->setMethods(["GET", "POST"]);
    $routes
        ->connect("/ordensservico/refresh-api", [
            "controller" => "Ordensservico",
            "action" => "refreshAPI",
        ])
        ->setMethods(["PUT"]);
    $routes
        ->connect("/ordensservico/refreshAPI", [
            "controller" => "Ordensservico",
            "action" => "refreshAPI",
        ])
        ->setMethods(["PUT"]);
    // API integração ERP: cadastro de produtos (Integrador GridERP + Web → Portal)
    $routes
        ->connect("/produtos/add-api", [
            "controller" => "Produtos",
            "action" => "addAPI",
        ])
        ->setMethods(["POST"]);
    $routes
        ->connect("/produtos/addAPI", [
            "controller" => "Produtos",
            "action" => "addAPI",
        ])
        ->setMethods(["POST"]);
    $routes
        ->connect("/produtos/list-api", [
            "controller" => "Produtos",
            "action" => "listAPI",
        ])
        ->setMethods(["GET"]);
    $routes
        ->connect("/produtos/listAPI", [
            "controller" => "Produtos",
            "action" => "listAPI",
        ])
        ->setMethods(["GET"]);
    $routes
        ->connect("/produtos/precificacao", [
            "controller" => "Produtos",
            "action" => "precificacao",
        ])
        ->setMethods(["GET"]);
    $routes
        ->connect("/produtos/salvar-precos", [
            "controller" => "Produtos",
            "action" => "salvarPrecos",
        ])
        ->setMethods(["POST"]);
    $routes
        ->connect("/produtos/salvarPrecos", [
            "controller" => "Produtos",
            "action" => "salvarPrecos",
        ])
        ->setMethods(["POST"]);
    // API integração ERP: clientes e contratos
    // Contratos (itens) — detalhe no ERP
    // Path canónico: /view/123. Sem segmento, ClicontratosController aceita ?id= (links legados / reverse routing).

    $routes->connect(
        "/clicontratos/view/*",
        ["controller" => "Clicontratos", "action" => "view"],
        ["pass" => ["id"]],
    );
    $routes->connect(
        "/clicontratos/renovar/*",
        ["controller" => "Clicontratos", "action" => "renovar"],
        ["pass" => ["id"]],
    );
    $routes
        ->connect("/clientes/add-api", [
            "controller" => "Clientes",
            "action" => "addAPI",
        ])
        ->setMethods(["POST"]);
    $routes
        ->connect("/clientes/addAPI", [
            "controller" => "Clientes",
            "action" => "addAPI",
        ])
        ->setMethods(["POST"]);
    $routes
        ->connect("/clientes/list-api", [
            "controller" => "Clientes",
            "action" => "listAPI",
        ])
        ->setMethods(["GET"]);
    $routes
        ->connect("/clientes/listAPI", [
            "controller" => "Clientes",
            "action" => "listAPI",
        ])
        ->setMethods(["GET"]);
    // UI ERP: mesmas URLs que DashedRoute geraria; explícito para não depender só do fallback e documentar o contrato.
    $routes->connect("/clientes/reativar/*", [
        "controller" => "Clientes",
        "action" => "reativar",
    ]);
    $routes->connect("/clientes/inativar/*", [
        "controller" => "Clientes",
        "action" => "inativar",
    ]);
    // API cadastro consolidado: dados empresa por CNPJ (Receita + IE + IM)
    $routes
        ->connect("/api/cadastro/empresa/consultar", [
            "controller" => "Cadastro",
            "action" => "consultar",
        ])
        ->setMethods(["POST"]);
    $routes
        ->connect("/api/cadastro/empresa/:cnpj", [
            "controller" => "Cadastro",
            "action" => "empresa",
            "cnpj",
        ])
        ->setPass(["cnpj"])
        ->setMethods(["GET"]);
    $routes
        ->connect("/api/audit/validate", [
            "controller" => "Audit",
            "action" => "apiValidate",
        ])
        ->setMethods(["POST"]);
    $routes
        ->connect("/users/api-set-user-audit-password", [
            "controller" => "Users",
            "action" => "apiSetUserAuditPassword",
        ])
        ->setMethods(["POST"]);
    // Tickets — rotas legadas redirecionam para /servicedesk (consolidação da UI React).
    $routes->redirect("/tickets", "/servicedesk", ["status" => 301]);
    $routes->redirect("/tickets/", "/servicedesk", ["status" => 301]);
    $routes->redirect("/tickets/index", "/servicedesk", ["status" => 301]);
    $routes->redirect("/tickets/indexcliente", "/servicedesk", ["status" => 301]);
    $routes->redirect("/tickets/operacional", "/servicedesk/operacional", ["status" => 301]);
    $routes
        ->connect(
            "/tickets/:id/gerar-os",
            ["controller" => "Ordensservico", "action" => "addFromTicket"],
            ["pass" => ["id"], "id" => "\d+", "_name" => "ticketsGerarOs"],
        )
        ->setMethods(["GET"]);
    $routes->connect("/tickets/historico", [
        "controller" => "Tickets",
        "action" => "historico",
    ]);
    $routes
        ->connect("/tickets/api-index", [
            "controller" => "Tickets",
            "action" => "apiIndex",
        ])
        ->setMethods(["GET"]);
    $routes
        ->connect("/tickets/api-dashboard-operacional", [
            "controller" => "Tickets",
            "action" => "apiDashboardOperacional",
        ])
        ->setMethods(["GET"]);
    $routes
        ->connect("/tickets/api-index-cliente", [
            "controller" => "Tickets",
            "action" => "apiIndexCliente",
        ])
        ->setMethods(["GET"]);
    $routes
        ->connect(
            "/tickets/api-view/*",
            ["controller" => "Tickets", "action" => "apiView"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["GET"]);
    $routes
        ->connect(
            "/tickets/api-comments/*",
            ["controller" => "Tickets", "action" => "apiComments"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["GET"]);
    $routes
        ->connect(
            "/tickets/api-save/*",
            ["controller" => "Tickets", "action" => "apiSaveTicket"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["POST", "PUT"]);
    $routes
        ->connect(
            "/tickets/api-sla-pause/*",
            ["controller" => "Tickets", "action" => "apiTicketSlaPause"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect(
            "/tickets/api-sla-resume/*",
            ["controller" => "Tickets", "action" => "apiTicketSlaResume"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect(
            "/tickets/api-servicedesk-await-cliente/*",
            ["controller" => "Tickets", "action" => "apiServicedeskAwaitCliente"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect(
            "/tickets/api-servicedesk-escalate-level/*",
            ["controller" => "Tickets", "action" => "apiServicedeskEscalateLevel"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect(
            "/tickets/:id/assignment",
            ["controller" => "Tickets", "action" => "apiPatchAssignment"],
            ["pass" => ["id"], "id" => "\d+"],
        )
        ->setMethods(["PATCH", "POST"]);
    $routes
        ->connect(
            "/tickets/:id/status",
            ["controller" => "Tickets", "action" => "apiPatchTicketStatus"],
            ["pass" => ["id"], "id" => "\d+"],
        )
        ->setMethods(["PATCH", "POST"]);
    $routes
        ->connect(
            "/tickets/:id/priority",
            ["controller" => "Tickets", "action" => "apiPatchTicketPriority"],
            ["pass" => ["id"], "id" => "\d+"],
        )
        ->setMethods(["PATCH", "POST"]);
    $routes
        ->connect(
            "/tickets/api-subject-options",
            ["controller" => "Tickets", "action" => "apiTicketSubjectOptions"],
        )
        ->setMethods(["GET"]);
    $routes
        ->connect(
            "/tickets/:id/subject",
            ["controller" => "Tickets", "action" => "apiPatchTicketSubject"],
            ["pass" => ["id"], "id" => "\d+"],
        )
        ->setMethods(["PATCH", "POST"]);
    $routes
        ->connect(
            "/tickets/api-anexo-upload/*",
            ["controller" => "Tickets", "action" => "apiAnexoUpload"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect(
            "/tickets/api-anexo-delete/*",
            ["controller" => "Tickets", "action" => "apiAnexoDelete"],
            ["pass" => ["idanexo"]],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect(
            "/tickets/api-timeline/*",
            ["controller" => "Tickets", "action" => "apiTimeline"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["GET"]);
    $routes
        ->connect(
            "/tickets/api-validate-geolocation/*",
            ["controller" => "Tickets", "action" => "apiValidateGeolocation"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect(
            "/tickets/api-ticket-signature/*",
            ["controller" => "Tickets", "action" => "apiTicketSignature"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect(
            "/tickets/api-add-ticket-product/*",
            ["controller" => "Tickets", "action" => "apiAddTicketProduct"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect(
            "/tickets/api-ticket-product-search/*",
            ["controller" => "Tickets", "action" => "apiTicketProductSearch"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["GET"]);
    $routes
        ->connect(
            "/tickets/api-add-evidence-photo/*",
            ["controller" => "Tickets", "action" => "apiAddEvidencePhoto"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect(
            "/tickets/api-pdf-ticket-os/*",
            ["controller" => "Tickets", "action" => "apiPdfTicketOs"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["GET"]);
    $routes
        ->connect(
            "/tickets/api-pdf-laudo/*",
            ["controller" => "Tickets", "action" => "apiPdfLaudo"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["GET"]);
    $routes
        ->connect(
            "/tickets/api-ticket-messages/*",
            ["controller" => "Tickets", "action" => "apiTicketMessages"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["GET", "POST"]);
    $routes
        ->connect(
            "/tickets/api-realtime-token/*",
            ["controller" => "Tickets", "action" => "apiRealtimeToken"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["GET"]);
    $routes
        ->connect(
            "/tickets/api-servicedesk-data/*",
            ["controller" => "Tickets", "action" => "apiServicedeskData"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["GET"]);
    $routes
        ->connect(
            "/tickets/api-time-entries/*",
            ["controller" => "Tickets", "action" => "apiTimeEntries"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["GET", "POST", "PUT", "DELETE"]);
    $routes
        ->connect(
            "/tickets/start-ticket/*",
            ["controller" => "Tickets", "action" => "startTicket"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["POST", "PUT"]);
    $routes
        ->connect(
            "/tickets/startTicket/*",
            ["controller" => "Tickets", "action" => "startTicket"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["POST", "PUT"]);
    $routes
        ->connect(
            "/queues/get-available-queues/*",
            ["controller" => "Queues", "action" => "getAvailableQueues"],
            ["pass" => ["ticketId"]],
        )
        ->setMethods(["GET"]);
    $routes
        ->connect(
            "/queues/getAvailableQueues/*",
            ["controller" => "Queues", "action" => "getAvailableQueues"],
            ["pass" => ["ticketId"]],
        )
        ->setMethods(["GET"]);
    $routes
        ->connect(
            "/ticket-comentarios/api-add/*",
            ["controller" => "Ticketcomentarios", "action" => "apiAdd"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["POST"]);
    // Central de Atendimento (layout dedicado; mesma sessão e APIs de tickets)
    $routes->connect("/servicedesk", [
        "controller" => "Servicedesk",
        "action" => "index",
    ]);
    $routes->connect("/servicedesk/", [
        "controller" => "Servicedesk",
        "action" => "index",
    ]);
    $routes->connect("/servicedesk/operacional", [
        "controller" => "Servicedesk",
        "action" => "operacional",
    ]);
    $routes->connect("/servicedesk-prototype", [
        "controller" => "ServicedeskPrototype",
        "action" => "index",
    ]);
    $routes->connect("/servicedesk-prototype/", [
        "controller" => "ServicedeskPrototype",
        "action" => "index",
    ]);
    $routes->connect("/servicedesk-prototype/fila", [
        "controller" => "ServicedeskPrototype",
        "action" => "fila",
    ]);
    $routes->connect("/servicedesk-prototype/ticket/:id", [
        "controller" => "ServicedeskPrototype",
        "action" => "ticket",
    ], ["pass" => ["id"], "id" => "[0-9]+"]);
    $routes->connect("/servicedesk-prototype/ci/:id", [
        "controller" => "ServicedeskPrototype",
        "action" => "ci",
    ], ["pass" => ["id"], "id" => "[0-9]+"]);
    $routes->connect("/servicedesk-prototype/api/badges", [
        "controller" => "ServicedeskPrototype",
        "action" => "apiBadges",
    ]);
    $routes->connect("/servicedesk-prototype/aprovacao/:source_type/:source_id/:decisao", [
        "controller" => "ServicedeskPrototype",
        "action" => "aprovacao",
    ], [
        "pass" => ["source_type", "source_id", "decisao"],
        "source_type" => "[a-z\-]+",
        "source_id" => "[0-9]+",
        "decisao" => "aprovar|reprovar",
    ]);

    // ===== Web Push =====
    $routes->connect("/web-push", [
        "controller" => "WebPush",
        "action" => "index",
    ]);
    $routes->connect("/web-push/vapid", [
        "controller" => "WebPush",
        "action" => "vapid",
    ]);
    $routes->connect("/web-push/subscribe", [
        "controller" => "WebPush",
        "action" => "subscribe",
    ]);
    $routes->connect("/web-push/unsubscribe", [
        "controller" => "WebPush",
        "action" => "unsubscribe",
    ]);

    // ===== CSAT público (cliente acessa via token) =====
    $routes->connect("/csat/:token/ok", [
        "controller" => "TicketCsat",
        "action" => "sucesso",
    ], ["pass" => ["token"], "token" => "csat\-[0-9]+\-[a-f0-9]{16}"]);
    $routes->connect("/csat/:token", [
        "controller" => "TicketCsat",
        "action" => "responder",
    ], ["pass" => ["token"], "token" => "csat\-[0-9]+\-[a-f0-9]{16}"]);
    $routes->connect("/servicedesk-prototype/:page", [
        "controller" => "ServicedeskPrototype",
        "action" => "view",
    ], ["pass" => ["page"], "page" => "[a-z0-9-]+"]);

    // ===== Protótipo Orçamentos (mockup pg-lista, pg-novo, pg-revisao, etc.) =====
    $routes->connect("/orcamentos-prototype", [
        "controller" => "OrcamentosPrototype",
        "action" => "lista",
    ]);
    $routes->connect("/orcamentos-prototype/", [
        "controller" => "OrcamentosPrototype",
        "action" => "lista",
    ]);
    $routes->connect("/orcamentos-prototype/detalhe/:id", [
        "controller" => "OrcamentosPrototype",
        "action" => "detalhe",
    ], ["pass" => ["id"], "id" => "[0-9]+"]);
    $routes->connect("/orcamentos-prototype/salvar-rascunho", [
        "controller" => "OrcamentosPrototype",
        "action" => "salvarRascunho",
    ]);
    $routes->connect("/orcamentos-prototype/api/produtos", [
        "controller" => "OrcamentosPrototype",
        "action" => "apiProdutos",
    ]);
    $routes->connect("/orcamentos-prototype/api/clientes", [
        "controller" => "OrcamentosPrototype",
        "action" => "apiClientes",
    ]);
    $routes->connect("/orcamentos-prototype/api/adicionar-item", [
        "controller" => "OrcamentosPrototype",
        "action" => "apiAdicionarItem",
    ]);
    $routes->connect("/ordens-prototype/api/adicionar-item", [
        "controller" => "OrdensservicoPrototype",
        "action" => "apiAdicionarItem",
    ]);
    $routes->connect("/orcamentos-prototype/:page", [
        "controller" => "OrcamentosPrototype",
        "action" => "view",
    ], ["pass" => ["page"], "page" => "[a-z0-9-]+"]);

    // ===== Protótipo Ordens de Serviço (mockup pg-os-*) =====
    $routes->connect("/ordens-prototype", [
        "controller" => "OrdensservicoPrototype",
        "action" => "lista",
    ]);
    $routes->connect("/ordens-prototype/", [
        "controller" => "OrdensservicoPrototype",
        "action" => "lista",
    ]);
    $routes->connect("/ordens-prototype/detalhe/:id", [
        "controller" => "OrdensservicoPrototype",
        "action" => "detalhe",
    ], ["pass" => ["id"], "id" => "[0-9]+"]);
    $routes->connect("/ordens-prototype/salvar-rascunho", [
        "controller" => "OrdensservicoPrototype",
        "action" => "salvarRascunho",
    ]);
    $routes->connect("/ordens-prototype/:page", [
        "controller" => "OrdensservicoPrototype",
        "action" => "view",
    ], ["pass" => ["page"], "page" => "[a-z0-9-]+"]);

    // ===== Protótipo Clientes (mockup pg-clientes, pg-cliente-novo, pg-cliente-360) =====
    $routes->connect("/clientes-prototype", [
        "controller" => "ClientesPrototype",
        "action" => "lista",
    ]);
    $routes->connect("/clientes-prototype/", [
        "controller" => "ClientesPrototype",
        "action" => "lista",
    ]);
    $routes->connect("/clientes-prototype/:page", [
        "controller" => "ClientesPrototype",
        "action" => "view",
    ], ["pass" => ["page"], "page" => "[a-z0-9-]+"]);

    // ===== Protótipo Produtos + Estoque (mockup pg-produtos, pg-estoque, pg-precos, etc.) =====
    $routes->connect("/produtos-prototype", [
        "controller" => "ProdutosPrototype",
        "action" => "lista",
    ]);
    $routes->connect("/produtos-prototype/", [
        "controller" => "ProdutosPrototype",
        "action" => "lista",
    ]);
    $routes->connect("/produtos-prototype/estoque", [
        "controller" => "ProdutosPrototype",
        "action" => "estoque",
    ]);
    $routes->connect("/produtos-prototype/preco-save", [
        "controller" => "ProdutosPrototype",
        "action" => "precoSave",
    ]);
    $routes->connect("/produtos-prototype/:page", [
        "controller" => "ProdutosPrototype",
        "action" => "view",
    ], ["pass" => ["page"], "page" => "[a-z0-9-]+"]);

    // ===== Protótipo PCP / Indústria (mockup pg-pcp-dashboard e demais 12 telas) =====
    $routes->connect("/pcp-prototype", [
        "controller" => "PcpPrototype",
        "action" => "lista",
    ]);
    $routes->connect("/pcp-prototype/", [
        "controller" => "PcpPrototype",
        "action" => "lista",
    ]);
    $routes->connect("/pcp-prototype/:page", [
        "controller" => "PcpPrototype",
        "action" => "view",
    ], ["pass" => ["page"], "page" => "[a-z0-9-]+"]);

    // ===== Protótipo Fornecedores (mockup pg-fornecedores, pg-fornecedor-novo, pg-fornecedor-360) =====
    $routes->connect("/fornecedores-prototype", [
        "controller" => "FornecedoresPrototype",
        "action" => "lista",
    ]);
    $routes->connect("/fornecedores-prototype/", [
        "controller" => "FornecedoresPrototype",
        "action" => "lista",
    ]);
    $routes->connect("/fornecedores-prototype/:page", [
        "controller" => "FornecedoresPrototype",
        "action" => "view",
    ], ["pass" => ["page"], "page" => "[a-z0-9-]+"]);

    // ===== Protótipo Financeiro (mockup pg-financeiro, pg-titulos, pg-contas-pagar, etc.) =====
    $routes->connect("/financeiro-prototype", [
        "controller" => "FinanceiroPrototype",
        "action" => "lista",
    ]);
    $routes->connect("/financeiro-prototype/", [
        "controller" => "FinanceiroPrototype",
        "action" => "lista",
    ]);
    $routes->connect("/financeiro-prototype/titulos", [
        "controller" => "FinanceiroPrototype",
        "action" => "titulos",
    ]);
    $routes->connect("/financeiro-prototype/contas-pagar", [
        "controller" => "FinanceiroPrototype",
        "action" => "contasPagar",
    ]);
    $routes->connect("/financeiro-prototype/:page", [
        "controller" => "FinanceiroPrototype",
        "action" => "view",
    ], ["pass" => ["page"], "page" => "[a-z0-9-]+"]);

    // ===== Protótipo Bancos (mockup pg-bancos, pg-extrato, pg-conciliacao, etc.) =====
    $routes->connect("/bancos-prototype", [
        "controller" => "BancosPrototype",
        "action" => "lista",
    ]);
    $routes->connect("/bancos-prototype/", [
        "controller" => "BancosPrototype",
        "action" => "lista",
    ]);
    $routes->connect("/bancos-prototype/conciliar", [
        "controller" => "BancosPrototype",
        "action" => "conciliar",
    ]);
    $routes->connect("/bancos-prototype/:page", [
        "controller" => "BancosPrototype",
        "action" => "view",
    ], ["pass" => ["page"], "page" => "[a-z0-9-]+"]);

    // ===== Protótipo Empresas (mockup pg-empresas, pg-empresa-nova) =====
    $routes->connect("/empresas-prototype", [
        "controller" => "EmpresasPrototype",
        "action" => "lista",
    ]);
    $routes->connect("/empresas-prototype/", [
        "controller" => "EmpresasPrototype",
        "action" => "lista",
    ]);
    $routes->connect("/empresas-prototype/:page", [
        "controller" => "EmpresasPrototype",
        "action" => "view",
    ], ["pass" => ["page"], "page" => "[a-z0-9-]+"]);

    // ===== Protótipo Sistema · RBAC · Auditoria (mockup pg-config, pg-usuarios, pg-acesso-*, pg-auditoria) =====
    $routes->connect("/sistema-prototype/usuarios", [
        "controller" => "SistemaPrototype",
        "action" => "usuarios",
    ]);
    $routes->connect("/sistema-prototype/acesso-central", [
        "controller" => "SistemaPrototype",
        "action" => "acessoCentral",
    ]);
    $routes->connect("/sistema-prototype/acesso-papeis", [
        "controller" => "SistemaPrototype",
        "action" => "acessoPapeis",
    ]);
    $routes->connect("/sistema-prototype/auditoria", [
        "controller" => "SistemaPrototype",
        "action" => "auditoria",
    ]);
    $routes->connect("/sistema-prototype/config", [
        "controller" => "SistemaPrototype",
        "action" => "config",
    ]);
    $routes->connect("/sistema-prototype/view-as", [
        "controller" => "SistemaPrototype",
        "action" => "viewAs",
    ]);
    $routes->connect("/sistema-prototype/:page", [
        "controller" => "SistemaPrototype",
        "action" => "view",
    ], ["pass" => ["page"], "page" => "[a-z0-9-]+"]);

    $routes->connect("/servicedesk/sla-relatorio", [
        "controller" => "Servicedesk",
        "action" => "slaRelatorio",
    ]);
    $routes->connect("/servicedesk/workflow-sla-admin", [
        "controller" => "Servicedesk",
        "action" => "workflowSlaAdmin",
    ]);
    // Prefixos mais longos antes de /workflow-sla e /workflow-sla/:id (evita ambiguidade com proxies / cache).
    $routes
        ->connect("/servicedesk/workflow-sla-logs", [
            "controller" => "Servicedesk",
            "action" => "workflowSlaLogs",
        ])
        ->setMethods(["GET"]);
    $routes
        ->connect("/servicedesk/workflow-sla-empresas", [
            "controller" => "Servicedesk",
            "action" => "workflowSlaEmpresasOptions",
        ])
        ->setMethods(["GET"]);
    // Lista + POST criar — action workflowSlaPolicies (URL dashed; fallback sem rota explícita não pode usar workflowSla).
    $routes
        ->connect("/servicedesk/workflow-sla-policies", [
            "controller" => "Servicedesk",
            "action" => "workflowSlaPolicies",
        ])
        ->setMethods(["GET", "POST"]);
    $routes
        ->connect("/servicedesk/workflow-sla", [
            "controller" => "Servicedesk",
            "action" => "workflowSla",
        ])
        ->setMethods(["GET", "POST"]);
    $routes
        ->connect("/servicedesk/workflow-sla/", [
            "controller" => "Servicedesk",
            "action" => "workflowSla",
        ])
        ->setMethods(["GET", "POST"]);
    $routes
        ->connect("/servicedesk/workflow-sla/:id/duplicate", [
            "controller" => "Servicedesk",
            "action" => "workflowSlaDuplicate",
            "id",
        ], ["pass" => ["id"], "id" => "\d+"])
        ->setMethods(["POST"]);
    $routes
        ->connect("/servicedesk/workflow-sla/:id", [
            "controller" => "Servicedesk",
            "action" => "workflowSla",
            "id",
        ], ["pass" => ["id"], "id" => "\d+"])
        ->setMethods(["GET", "PATCH", "DELETE"]);
    $routes
        ->connect("/servicedesk/workflow-states", [
            "controller" => "Servicedesk",
            "action" => "workflowStates",
        ])
        ->setMethods(["GET"]);
    $routes
        ->connect("/servicedesk/workflow-transitions", [
            "controller" => "Servicedesk",
            "action" => "workflowTransitions",
        ])
        ->setMethods(["GET", "POST"]);
    $routes
        ->connect("/servicedesk/workflow-transitions/:id", [
            "controller" => "Servicedesk",
            "action" => "workflowTransition",
            "id",
        ], ["pass" => ["id"], "id" => "\d+"])
        ->setMethods(["PATCH", "DELETE"]);
    // Precificação / Gestão de Preços
    $routes->connect("/produtos/estoque-pdf/*", [
        "controller" => "Produtos",
        "action" => "estoquePdf",
    ]);
    // Orçamentos — URLs explícitas (prompt_cursor_cakephp.md PROMPT 7); o inflection padrão já cobre, isto documenta o contrato.
    $routes->connect("/orcamentos", [
        "controller" => "Orcamentos",
        "action" => "index",
    ]);
    $routes->connect("/orcamentos/add", [
        "controller" => "Orcamentos",
        "action" => "add",
    ]);
    $routes->connect("/orcamentos/solicitar", [
        "controller" => "Orcamentos",
        "action" => "solicitar",
    ]);
    $routes
        ->connect("/orcamentos/catalogo-sugestoes", [
            "controller" => "Orcamentos",
            "action" => "catalogoSugestoes",
        ])
        ->setMethods(["GET"]);
    $routes->connect("/orcamentos/catalogo", [
        "controller" => "Orcamentos",
        "action" => "catalogo",
    ]);
    $routes->connect(
        "/orcamentos/:id/pdf",
        ["controller" => "Orcamentos", "action" => "pdf"],
        ["pass" => ["id"], "id" => "\d+"],
    );
    // Faturamento — módulo de emissão de documentos fiscais / cobranças
    $routes->connect("/faturamento", [
        "controller" => "Faturamento",
        "action" => "index",
    ]);
    $routes->connect("/faturamento/index", [
        "controller" => "Faturamento",
        "action" => "index",
    ]);
    $routes->connect("/faturamento/add", [
        "controller" => "Faturamento",
        "action" => "add",
    ]);
    $routes->connect("/faturamento/view/*", [
        "controller" => "Faturamento",
        "action" => "view",
    ]);
    $routes->connect("/faturamento/edit/*", [
        "controller" => "Faturamento",
        "action" => "edit",
    ]);
    $routes->connect("/faturamento/delete/*", [
        "controller" => "Faturamento",
        "action" => "delete",
    ]);
    $routes
        ->connect("/faturamento/alterar-status/*", [
            "controller" => "Faturamento",
            "action" => "alterarStatus",
        ])
        ->setMethods(["POST"]);
    $routes->connect(
        "/faturamento/gerar-de-os/:idordem",
        ["controller" => "Faturamento", "action" => "gerarDeOS"],
        ["pass" => ["idordem"], "idordem" => "\d+"],
    );
    // Módulo Fiscal — URLs canônicas (equipe; templates/rotas explícitas)
    $routes->connect("/fiscal", [
        "controller" => "Fiscal",
        "action" => "index",
    ]);
    $routes
        ->connect("/fiscal/status-sefaz", [
            "controller" => "Fiscal",
            "action" => "statusSefaz",
        ])
        ->setMethods(["GET", "POST"]);
    $routes
        ->connect("/fiscal/distribuicao-dfe", [
            "controller" => "Fiscal",
            "action" => "distribuicaoDfe",
        ])
        ->setMethods(["GET"]);
    $routes->connect("/fiscal/dfe-recebidos", [
        "controller" => "Fiscal",
        "action" => "dfeRecebidos",
    ]);
    $routes->connect(
        "/fiscal/dfe-recebidos/xml/*",
        ["controller" => "Fiscal", "action" => "dfeRecebidoXml"],
        ["pass" => ["id"]],
    );
    $routes
        ->connect(
            "/fiscal/dfe-recebidos/ignorar/*",
            ["controller" => "Fiscal", "action" => "dfeRecebidoIgnorar"],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect(
            "/fiscal/dfe-recebidos/criar-entrada/*",
            ["controller" => "Fiscal", "action" => "dfeRecebidoCriarEntrada"],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect(
            "/fiscal/dfe-recebidos/baixar-completo/*",
            ["controller" => "Fiscal", "action" => "dfeRecebidoBaixarCompleto"],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes->connect("/fiscal/contingencia", [
        "controller" => "Fiscal",
        "action" => "contingencia",
    ]);
    $routes->connect("/fiscal/importar-xml-lote", [
        "controller" => "Fiscal",
        "action" => "importarXmlLote",
    ]);
    $routes->connect("/fiscal-notas", [
        "controller" => "FiscalNotas",
        "action" => "index",
    ]);
    $routes->connect("/fiscal-notas/index", [
        "controller" => "FiscalNotas",
        "action" => "index",
    ]);
    $routes->connect("/fiscal-notas/add", [
        "controller" => "FiscalNotas",
        "action" => "add",
    ]);
    $routes->connect(
        "/fiscal-notas/view/*",
        ["controller" => "FiscalNotas", "action" => "view"],
        ["pass" => ["id"]],
    );
    $routes->connect(
        "/fiscal-notas/edit/*",
        ["controller" => "FiscalNotas", "action" => "edit"],
        ["pass" => ["id"]],
    );
    $routes
        ->connect(
            "/fiscal-notas/delete/*",
            ["controller" => "FiscalNotas", "action" => "delete"],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes->connect(
        "/fiscal-notas/emitir/*",
        ["controller" => "FiscalNotas", "action" => "emitir"],
        ["pass" => ["id"]],
    );
    $routes->connect(
        "/fiscal-notas/cancelar/*",
        ["controller" => "FiscalNotas", "action" => "cancelar"],
        ["pass" => ["id"]],
    );
    $routes->connect(
        "/fiscal-notas/carta-correcao/*",
        ["controller" => "FiscalNotas", "action" => "cartaCorrecao"],
        ["pass" => ["id"]],
    );
    $routes
        ->connect(
            "/fiscal-notas/manifestar-destinatario/*",
            [
                "controller" => "FiscalNotas",
                "action" => "manifestarDestinatario",
            ],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes->connect("/fiscal-notas/inutilizar-numeracao", [
        "controller" => "FiscalNotas",
        "action" => "inutilizarNumeracao",
    ]);
    $routes->connect(
        "/fiscal-notas/danfe/*",
        ["controller" => "FiscalNotas", "action" => "danfe"],
        ["pass" => ["id"]],
    );
    $routes->connect(
        "/fiscal-notas/download-xml/*",
        ["controller" => "FiscalNotas", "action" => "downloadXml"],
        ["pass" => ["id"]],
    );
    $routes
        ->connect("/fiscal-notas/buscar-ncm", [
            "controller" => "FiscalNotas",
            "action" => "buscarNcm",
        ])
        ->setMethods(["GET"]);
    $routes
        ->connect("/fiscal-notas/buscar-cfop", [
            "controller" => "FiscalNotas",
            "action" => "buscarCfop",
        ])
        ->setMethods(["GET"]);
    $routes
        ->connect(
            "/fiscal-notas/enviar-email/*",
            ["controller" => "FiscalNotas", "action" => "enviarEmail"],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect(
            "/fiscal-notas/sincronizar-erp/*",
            ["controller" => "FiscalNotas", "action" => "sincronizarErp"],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect("/fiscal-notas/consultar-chave", [
            "controller" => "FiscalNotas",
            "action" => "consultarChave",
        ])
        ->setMethods(["GET", "POST"]);
    $routes
        ->connect("/fiscal-notas/consultar-cadastro", [
            "controller" => "FiscalNotas",
            "action" => "consultarCadastro",
        ])
        ->setMethods(["GET", "POST"]);
    $routes->connect("/fiscal-notas/controle-series", [
        "controller" => "FiscalNotas",
        "action" => "controleSeries",
    ]);
    $routes->connect("/fiscal-notas-entrada", [
        "controller" => "FiscalNotasEntrada",
        "action" => "index",
    ]);
    $routes->connect("/fiscal-notas-entrada/index", [
        "controller" => "FiscalNotasEntrada",
        "action" => "index",
    ]);
    $routes->connect("/fiscal-notas-entrada/add", [
        "controller" => "FiscalNotasEntrada",
        "action" => "add",
    ]);
    $routes->connect(
        "/fiscal-notas-entrada/view/*",
        ["controller" => "FiscalNotasEntrada", "action" => "view"],
        ["pass" => ["id"]],
    );
    $routes->connect(
        "/fiscal-notas-entrada/edit/*",
        ["controller" => "FiscalNotasEntrada", "action" => "edit"],
        ["pass" => ["id"]],
    );
    $routes
        ->connect(
            "/fiscal-notas-entrada/delete/*",
            ["controller" => "FiscalNotasEntrada", "action" => "delete"],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes->connect("/fiscal-notas-entrada/controle-series", [
        "controller" => "FiscalNotasEntrada",
        "action" => "controleSeries",
    ]);
    $routes->connect(
        "/fiscal-notas-entrada/emitir/*",
        ["controller" => "FiscalNotasEntrada", "action" => "emitir"],
        ["pass" => ["id"]],
    );
    $routes->connect(
        "/fiscal-notas-entrada/cancelar/*",
        ["controller" => "FiscalNotasEntrada", "action" => "cancelar"],
        ["pass" => ["id"]],
    );
    $routes->connect(
        "/fiscal-notas-entrada/carta-correcao/*",
        ["controller" => "FiscalNotasEntrada", "action" => "cartaCorrecao"],
        ["pass" => ["id"]],
    );
    $routes
        ->connect(
            "/fiscal-notas-entrada/manifestar-destinatario/*",
            [
                "controller" => "FiscalNotasEntrada",
                "action" => "manifestarDestinatario",
            ],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes->connect("/fiscal-notas-entrada/inutilizar-numeracao", [
        "controller" => "FiscalNotasEntrada",
        "action" => "inutilizarNumeracao",
    ]);
    $routes->connect(
        "/fiscal-notas-entrada/danfe/*",
        ["controller" => "FiscalNotasEntrada", "action" => "danfe"],
        ["pass" => ["id"]],
    );
    $routes->connect(
        "/fiscal-notas-entrada/download-xml/*",
        ["controller" => "FiscalNotasEntrada", "action" => "downloadXml"],
        ["pass" => ["id"]],
    );
    $routes
        ->connect("/fiscal-notas-entrada/buscar-ncm", [
            "controller" => "FiscalNotasEntrada",
            "action" => "buscarNcm",
        ])
        ->setMethods(["GET"]);
    $routes
        ->connect("/fiscal-notas-entrada/buscar-cfop", [
            "controller" => "FiscalNotasEntrada",
            "action" => "buscarCfop",
        ])
        ->setMethods(["GET"]);
    $routes
        ->connect(
            "/fiscal-notas-entrada/enviar-email/*",
            ["controller" => "FiscalNotasEntrada", "action" => "enviarEmail"],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect(
            "/fiscal-notas-entrada/sincronizar-erp/*",
            [
                "controller" => "FiscalNotasEntrada",
                "action" => "sincronizarErp",
            ],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect("/fiscal-notas-entrada/consultar-chave", [
            "controller" => "FiscalNotasEntrada",
            "action" => "consultarChave",
        ])
        ->setMethods(["GET", "POST"]);
    $routes
        ->connect("/fiscal-notas-entrada/consultar-cadastro", [
            "controller" => "FiscalNotasEntrada",
            "action" => "consultarCadastro",
        ])
        ->setMethods(["GET", "POST"]);
    $routes->connect("/fiscal-certificados", [
        "controller" => "FiscalCertificados",
        "action" => "index",
    ]);
    $routes->connect("/fiscal-certificados/add", [
        "controller" => "FiscalCertificados",
        "action" => "add",
    ]);
    // Path canónico: /view/123. Sem segmento, aceita ?id= (links antigos / reverse routing).
    $routes->connect(
        "/fiscal-certificados/view/*",
        ["controller" => "FiscalCertificados", "action" => "view"],
        ["pass" => ["id"]],
    );
    $routes
        ->connect("/fiscal-certificados/view", [
            "controller" => "FiscalCertificados",
            "action" => "view",
        ])
        ->setMethods(["GET"]);
    $routes
        ->connect(
            "/fiscal-certificados/toggle-ativo/*",
            ["controller" => "FiscalCertificados", "action" => "toggleAtivo"],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect(
            "/fiscal-certificados/delete/*",
            ["controller" => "FiscalCertificados", "action" => "delete"],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes->connect("/fiscal-config", [
        "controller" => "FiscalConfig",
        "action" => "index",
    ]);
    $routes->connect("/fiscal-config/naturezas", [
        "controller" => "FiscalConfig",
        "action" => "naturezas",
    ]);
    $routes->connect("/fiscal-config/natureza-add", [
        "controller" => "FiscalConfig",
        "action" => "naturezaAdd",
    ]);
    $routes->connect(
        "/fiscal-config/natureza-edit/*",
        ["controller" => "FiscalConfig", "action" => "naturezaEdit"],
        ["pass" => ["id"]],
    );
    $routes
        ->connect(
            "/fiscal-config/natureza-delete/*",
            ["controller" => "FiscalConfig", "action" => "naturezaDelete"],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes->connect("/fiscal-config/aliquotas", [
        "controller" => "FiscalConfig",
        "action" => "aliquotas",
    ]);
    $routes->connect("/fiscal-config/aliquota-add", [
        "controller" => "FiscalConfig",
        "action" => "aliquotaAdd",
    ]);
    $routes->connect(
        "/fiscal-config/aliquota-edit/*",
        ["controller" => "FiscalConfig", "action" => "aliquotaEdit"],
        ["pass" => ["id"]],
    );
    $routes
        ->connect(
            "/fiscal-config/aliquota-delete/*",
            ["controller" => "FiscalConfig", "action" => "aliquotaDelete"],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes->connect("/fiscal-config/cfop", [
        "controller" => "FiscalConfig",
        "action" => "cfop",
    ]);
    $routes->connect("/fiscal-config/cfop-add", [
        "controller" => "FiscalConfig",
        "action" => "cfopAdd",
    ]);
    $routes->connect(
        "/fiscal-config/cfop-edit/*",
        ["controller" => "FiscalConfig", "action" => "cfopEdit"],
        ["pass" => ["id"]],
    );
    $routes
        ->connect(
            "/fiscal-config/cfop-delete/*",
            ["controller" => "FiscalConfig", "action" => "cfopDelete"],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes->connect("/fiscal-config/ncm", [
        "controller" => "FiscalConfig",
        "action" => "ncm",
    ]);
    $routes->connect("/fiscal-config/ncm-add", [
        "controller" => "FiscalConfig",
        "action" => "ncmAdd",
    ]);
    $routes->connect(
        "/fiscal-config/ncm-edit/*",
        ["controller" => "FiscalConfig", "action" => "ncmEdit"],
        ["pass" => ["id"]],
    );
    $routes
        ->connect(
            "/fiscal-config/ncm-delete/*",
            ["controller" => "FiscalConfig", "action" => "ncmDelete"],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect("/fiscal-config/importar-ncm", [
            "controller" => "FiscalConfig",
            "action" => "importarNcm",
        ])
        ->setMethods(["POST"]);
    $routes->connect("/fiscal-relatorios", [
        "controller" => "FiscalRelatorios",
        "action" => "index",
    ]);
    $routes->connect("/fiscal-relatorios/livro-saidas", [
        "controller" => "FiscalRelatorios",
        "action" => "livroSaidas",
    ]);
    $routes->connect("/fiscal-relatorios/livro-entradas", [
        "controller" => "FiscalRelatorios",
        "action" => "livroEntradas",
    ]);
    $routes->connect("/fiscal-relatorios/resumo-mensal", [
        "controller" => "FiscalRelatorios",
        "action" => "resumoMensal",
    ]);
    $routes->connect("/fiscal-relatorios/por-cliente", [
        "controller" => "FiscalRelatorios",
        "action" => "porCliente",
    ]);
    $routes->connect("/fiscal-relatorios/por-numero-serie", [
        "controller" => "FiscalRelatorios",
        "action" => "porNumeroSerie",
    ]);
    $routes->connect("/fiscal-relatorios/exportar-sped", [
        "controller" => "FiscalRelatorios",
        "action" => "exportarSped",
    ]);
    $routes->connect("/fiscal-relatorios/exportar-excel", [
        "controller" => "FiscalRelatorios",
        "action" => "exportarExcel",
    ]);
    // Financeiro — dashboard e contas a receber/pagar
    $routes->connect("/financeiro", [
        "controller" => "Financeiro",
        "action" => "index",
    ]);
    $routes->connect("/financeiro/index", [
        "controller" => "Financeiro",
        "action" => "index",
    ]);
    $routes->connect("/financeiro/contas-receber", [
        "controller" => "Financeiro",
        "action" => "contasReceber",
    ]);
    $routes->connect("/financeiro/add-receita", [
        "controller" => "Financeiro",
        "action" => "addReceita",
    ]);
    $routes->connect(
        "/financeiro/edit-receita/:id",
        ["controller" => "Financeiro", "action" => "editReceita"],
        ["pass" => ["id"], "id" => "\d+"],
    );
    $routes->connect(
        "/financeiro/fatura/:id",
        ["controller" => "Financeiro", "action" => "fatura"],
        ["pass" => ["id"], "id" => "\d+"],
    );
    $routes->connect(
        "/financeiro/fatura/:id/exportar",
        ["controller" => "Financeiro", "action" => "exportarFatura"],
        ["pass" => ["id"], "id" => "\d+"],
    );
    $routes->connect(
        "/financeiro/fatura/:id/exportar-pdf",
        ["controller" => "Financeiro", "action" => "exportarFaturaPdf"],
        ["pass" => ["id"], "id" => "\d+"],
    );
    $routes
        ->connect("/financeiro/registrar-recebimento/*", [
            "controller" => "Financeiro",
            "action" => "registrarRecebimento",
        ])
        ->setMethods(["POST"]);
    $routes->connect("/financeiro/fluxo-caixa", [
        "controller" => "Financeiro",
        "action" => "fluxoCaixa",
    ]);
    $routes->connect("/financeiro/recorrentes", [
        "controller" => "Financeiro",
        "action" => "recorrentes",
    ]);
    $routes->connect("/financeiro/add-recorrente", [
        "controller" => "Financeiro",
        "action" => "addRecorrente",
    ]);
    $routes->connect(
        "/financeiro/edit-recorrente/:id",
        ["controller" => "Financeiro", "action" => "editRecorrente"],
        ["pass" => ["id"], "id" => "\d+"],
    );
    $routes
        ->connect("/financeiro/delete-recorrente/*", [
            "controller" => "Financeiro",
            "action" => "deleteRecorrente",
        ])
        ->setMethods(["POST"]);
    $routes->connect("/financeiro/dre", [
        "controller" => "Financeiro",
        "action" => "dre",
    ]);
    $routes->connect("/financeiro/conciliacao", [
        "controller" => "Financeiro",
        "action" => "conciliacao",
    ]);
    $routes
        ->connect("/financeiro/importar-extrato", [
            "controller" => "Financeiro",
            "action" => "importarExtrato",
        ])
        ->setMethods(["POST"]);
    $routes
        ->connect("/financeiro/conciliar-extrato/*", [
            "controller" => "Financeiro",
            "action" => "conciliarExtrato",
        ])
        ->setMethods(["POST"]);
    $routes->connect("/financeiro/contas-pagar", [
        "controller" => "Financeiro",
        "action" => "contasPagar",
    ]);
    $routes->connect("/financeiro/add-despesa", [
        "controller" => "Financeiro",
        "action" => "addDespesa",
    ]);
    $routes->connect(
        "/financeiro/edit-despesa/:id",
        ["controller" => "Financeiro", "action" => "editDespesa"],
        ["pass" => ["id"], "id" => "\d+"],
    );
    $routes
        ->connect("/financeiro/registrar-pagamento/*", [
            "controller" => "Financeiro",
            "action" => "registrarPagamento",
        ])
        ->setMethods(["POST"]);
    $routes
        ->connect("/financeiro/cancelar-despesa/*", [
            "controller" => "Financeiro",
            "action" => "cancelarDespesa",
        ])
        ->setMethods(["POST"]);
    // Financeiro — bancos
    $routes->connect("/financeiro-bancos", [
        "controller" => "FinanceiroBancos",
        "action" => "index",
    ]);
    $routes->connect("/financeiro-bancos/cadastrar", [
        "controller" => "FinanceiroBancos",
        "action" => "cadastrar",
    ]);
    $routes->connect("/financeiro-bancos/add", [
        "controller" => "FinanceiroBancos",
        "action" => "add",
    ]);
    $routes->connect("/financeiro-bancos/edit/*", [
        "controller" => "FinanceiroBancos",
        "action" => "edit",
    ]);
    $routes
        ->connect("/financeiro-bancos/delete/*", [
            "controller" => "FinanceiroBancos",
            "action" => "delete",
        ])
        ->setMethods(["POST", "DELETE"]);
    $routes
        ->connect("/financeiro-bancos/buscar-catalogo", [
            "controller" => "FinanceiroBancos",
            "action" => "buscarCatalogo",
        ])
        ->setMethods(["GET"]);
    $routes
        ->connect("/financeiro-bancos/bootstrap-banco-por-codigo", [
            "controller" => "FinanceiroBancos",
            "action" => "bootstrapBancoPorCodigo",
        ])
        ->setMethods(["POST"]);
    $routes->connect("/financeiro-bancos/remessa", [
        "controller" => "FinanceiroBancos",
        "action" => "remessa",
    ]);
    $routes->connect("/financeiro-bancos/remessa-multiempresas", [
        "controller" => "FinanceiroBancos",
        "action" => "remessaMultiempresas",
    ]);
    $routes->connect("/financeiro-bancos/retorno", [
        "controller" => "FinanceiroBancos",
        "action" => "retorno",
    ]);
    $routes->connect("/financeiro-bancos/relatorios", [
        "controller" => "FinanceiroBancos",
        "action" => "relatorios",
    ]);
    $routes->connect("/financeiro-bancos/relacao-bancos", [
        "controller" => "FinanceiroBancos",
        "action" => "relacaoBancos",
    ]);
    $routes->connect("/financeiro-bancos/relacao-remessas", [
        "controller" => "FinanceiroBancos",
        "action" => "relacaoRemessas",
    ]);
    $routes->connect("/financeiro-bancos/download-remessa/*", [
        "controller" => "FinanceiroBancos",
        "action" => "downloadRemessa",
    ]);
    $routes->connect("/financeiro-bancos/detalhe-remessa/*", [
        "controller" => "FinanceiroBancos",
        "action" => "detalheRemessa",
    ]);
    $routes->connect("/financeiro-bancos/historico-retorno", [
        "controller" => "FinanceiroBancos",
        "action" => "historicoRetorno",
    ]);
    $routes->connect("/financeiro-bancos/detalhe-retorno/*", [
        "controller" => "FinanceiroBancos",
        "action" => "detalheRetorno",
    ]);
    $routes->connect("/financeiro-bancos/download-retorno/*", [
        "controller" => "FinanceiroBancos",
        "action" => "downloadRetorno",
    ]);
    $routes->connect("/financeiro-bancos/previsao-recebimentos-por-banco", [
        "controller" => "FinanceiroBancos",
        "action" => "previsaoRecebimentosPorBanco",
    ]);
    $routes->connect("/financeiro-bancos/previsao-por-bancos", [
        "controller" => "FinanceiroBancos",
        "action" => "previsaoPorBancos",
    ]);
    $routes
        ->connect("/financeiro-bancos/api-lista", [
            "controller" => "FinanceiroBancos",
            "action" => "apiLista",
        ])
        ->setMethods(["GET"]);
    $routes
        ->connect("/financeiro-bancos/api-salvar", [
            "controller" => "FinanceiroBancos",
            "action" => "apiSalvar",
        ])
        ->setMethods(["POST", "PUT", "PATCH"]);
    $routes
        ->connect("/financeiro/remessas/listar-titulos", [
            "controller" => "Remessas",
            "action" => "listarTitulos",
        ])
        ->setMethods(["GET"]);
    $routes
        ->connect("/financeiro/remessas/gerar", [
            "controller" => "Remessas",
            "action" => "gerarRemessa",
        ])
        ->setMethods(["POST"]);
    $routes
        ->connect("/financeiro/retornos/processar", [
            "controller" => "Retornos",
            "action" => "processar",
        ])
        ->setMethods(["POST"]);
    // Configuração financeira (Plano de Contas + Centros de Custo)
    // Relatórios financeiros
    $routes->connect("/financeiro-relatorios", [
        "controller" => "FinanceiroRelatorios",
        "action" => "index",
    ]);
    $routes->connect("/financeiro-relatorios/aging", [
        "controller" => "FinanceiroRelatorios",
        "action" => "aging",
    ]);
    $routes->connect("/financeiro-relatorios/inadimplencia", [
        "controller" => "FinanceiroRelatorios",
        "action" => "inadimplencia",
    ]);
    $routes->connect("/financeiro-relatorios/por-centro-custo", [
        "controller" => "FinanceiroRelatorios",
        "action" => "porCentroCusto",
    ]);
    $routes->connect("/financeiro-relatorios/exportar-aging-excel", [
        "controller" => "FinanceiroRelatorios",
        "action" => "exportarAgingExcel",
    ]);
    $routes->connect("/financeiro-relatorios/exportar-inadimplencia-excel", [
        "controller" => "FinanceiroRelatorios",
        "action" => "exportarInadimplenciaExcel",
    ]);
    $routes->connect("/financeiro-relatorios/exportar-centro-custo-excel", [
        "controller" => "FinanceiroRelatorios",
        "action" => "exportarCentroCustoExcel",
    ]);
    $routes->connect("/financeiro-config/plano-contas", [
        "controller" => "FinanceiroConfig",
        "action" => "planoContas",
    ]);
    $routes->connect("/financeiro-config/plano-contas-add", [
        "controller" => "FinanceiroConfig",
        "action" => "planoContasAdd",
    ]);
    $routes->connect("/financeiro-config/plano-contas-edit/*", [
        "controller" => "FinanceiroConfig",
        "action" => "planoContasEdit",
    ]);
    $routes
        ->connect("/financeiro-config/plano-contas-delete/*", [
            "controller" => "FinanceiroConfig",
            "action" => "planoContasDelete",
        ])
        ->setMethods(["POST"]);
    $routes->connect("/financeiro-config/centros-custo", [
        "controller" => "FinanceiroConfig",
        "action" => "centrosCusto",
    ]);
    $routes->connect("/financeiro-config/centro-custo-add", [
        "controller" => "FinanceiroConfig",
        "action" => "centroCustoAdd",
    ]);
    $routes->connect("/financeiro-config/centro-custo-edit/*", [
        "controller" => "FinanceiroConfig",
        "action" => "centroCustoEdit",
    ]);
    $routes
        ->connect("/financeiro-config/centro-custo-delete/*", [
            "controller" => "FinanceiroConfig",
            "action" => "centroCustoDelete",
        ])
        ->setMethods(["POST"]);
    // Relatórios e Indicadores (ERP)
    $routes->connect("/relatorios", [
        "controller" => "Relatorios",
        "action" => "index",
    ]);
    $routes->connect("/relatorios/index", [
        "controller" => "Relatorios",
        "action" => "index",
    ]);
    // Portal do Cliente — Relatórios (tela simples, sem dados internos)
    $routes->connect("/cliente/relatorios", [
        "controller" => "PortalRelatorios",
        "action" => "index",
    ]);
    $routes->connect("/cliente/relatorios/index", [
        "controller" => "PortalRelatorios",
        "action" => "index",
    ]);
    $routes
        ->connect("/cliente/relatorios/exportar", [
            "controller" => "PortalRelatorios",
            "action" => "exportar",
        ])
        ->setMethods(["GET"]);
    $routes
        ->connect("/cliente/relatorios/exportar-excel", [
            "controller" => "PortalRelatorios",
            "action" => "exportarExcel",
        ])
        ->setMethods(["GET"]);
    // Módulo avançado — ERP (equipe role 0)
    // Modelos de contrato — URL canónica /contract-templates
    $routes->connect("/contract-templates", [
        "controller" => "ContractTemplates",
        "action" => "index",
    ]);
    $routes->connect("/contract-templates/add", [
        "controller" => "ContractTemplates",
        "action" => "add",
    ]);
    $routes->connect(
        "/contract-templates/edit/*",
        ["controller" => "ContractTemplates", "action" => "edit"],
        ["pass" => ["id"]],
    );
    $routes
        ->connect(
            "/contract-templates/delete/*",
            ["controller" => "ContractTemplates", "action" => "delete"],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes->connect(
        "/contract-templates/preview/*",
        ["controller" => "ContractTemplates", "action" => "preview"],
        ["pass" => ["id"]],
    );
    $routes->connect(
        "/contract-templates/clonar/*",
        ["controller" => "ContractTemplates", "action" => "clonar"],
        ["pass" => ["id"]],
    );
    // Compat: paths antigos /modulo-avancado/contratos/* → gestão (/modulo-contratos)
    $routes->redirect(
        "/modulo-avancado/contratos",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->redirect(
        "/modulo-avancado/contratos/view/*",
        ["controller" => "ContractManagement", "action" => "view"],
        ["persist" => true, "status" => 302],
    );
    $routes->redirect(
        "/modulo-avancado/contratos/export-pdf/*",
        ["controller" => "ContractManagement", "action" => "gerarPdf"],
        ["persist" => true, "status" => 302],
    );
    // Compat: modelos antigos → /contract-templates (mantém POST delete no path legado)
    $routes->redirect(
        "/modulo-avancado/modelos-contrato",
        ["controller" => "ContractTemplates", "action" => "index"],
        ["status" => 302],
    );
    $routes->redirect(
        "/modulo-avancado/modelos-contrato/add",
        ["controller" => "ContractTemplates", "action" => "add"],
        ["status" => 302],
    );
    $routes->redirect(
        "/modulo-avancado/modelos-contrato/edit/*",
        ["controller" => "ContractTemplates", "action" => "edit"],
        ["persist" => true, "status" => 302],
    );
    $routes
        ->connect(
            "/modulo-avancado/modelos-contrato/delete/*",
            ["controller" => "ContractTemplates", "action" => "delete"],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes->connect("/modulo-avancado/faturas", [
        "controller" => "AdvancedInvoices",
        "action" => "index",
    ]);
    $routes->connect(
        "/modulo-avancado/faturas/view/*",
        ["controller" => "AdvancedInvoices", "action" => "view"],
        ["pass" => ["id"]],
    );
    $routes
        ->connect("/modulo-avancado/faturas/exportar", [
            "controller" => "AdvancedInvoices",
            "action" => "export",
        ])
        ->setMethods(["GET"]);
    $routes
        ->connect(
            "/modulo-avancado/faturas/marcar-paga/*",
            ["controller" => "AdvancedInvoices", "action" => "markPaid"],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes->connect("/modulo-avancado/indicadores", [
        "controller" => "AdvancedReports",
        "action" => "index",
    ]);
    $routes
        ->connect("/modulo-avancado/indicadores/exportar", [
            "controller" => "AdvancedReports",
            "action" => "export",
        ])
        ->setMethods(["GET"]);
    // Compat: URL curta /indicadores (evita DashedRoute → IndicadoresController inexistente)
    $routes->connect("/indicadores", [
        "controller" => "AdvancedReports",
        "action" => "index",
    ]);
    $routes
        ->connect("/indicadores/exportar", [
            "controller" => "AdvancedReports",
            "action" => "export",
        ])
        ->setMethods(["GET"]);
    // Gestão de contratos (ERP) — spec /modulo-contratos
    $routes->connect("/modulo-contratos", [
        "controller" => "ContractManagement",
        "action" => "index",
    ]);
    $routes->connect(
        "/modulo-contratos/view/:id",
        ["controller" => "ContractManagement", "action" => "view"],
        ["pass" => ["id"], "id" => "\d+"],
    );
    $routes->redirect(
        "/modulo-contratos/view",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->redirect(
        "/modulo-contratos/view/",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->connect("/modulo-contratos/add", [
        "controller" => "ContractManagement",
        "action" => "add",
    ]);
    $routes->connect(
        "/modulo-contratos/edit/:id",
        ["controller" => "ContractManagement", "action" => "edit"],
        ["pass" => ["id"], "id" => "\d+"],
    );
    $routes->redirect(
        "/modulo-contratos/edit",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->redirect(
        "/modulo-contratos/edit/",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    // :id numérico evita URL sem id e ajuda o Router a gerar o mesmo path no redirect
    $routes->connect(
        "/modulo-contratos/servicos/:id",
        ["controller" => "ContractManagement", "action" => "addServicos"],
        ["pass" => ["id"], "id" => "\d+"],
    );
    $routes->connect(
        "/modulo-contratos/conferencia-consumo/:id",
        ["controller" => "ContractManagement", "action" => "conferenciaConsumo"],
        ["pass" => ["id"], "id" => "\d+"],
    );
    $routes
        ->connect(
            "/modulo-contratos/servicos/delete/:svcId/:contractId",
            ["controller" => "ContractManagement", "action" => "deleteServico"],
            ["pass" => ["svcId", "contractId"]],
        )
        ->setMethods(["POST"]);
    $routes->redirect(
        "/modulo-contratos/servicos",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->connect(
        "/modulo-contratos/signatarios/:id",
        ["controller" => "ContractManagement", "action" => "addSignatarios"],
        ["pass" => ["id"], "id" => "\d+"],
    );
    $routes
        ->connect(
            "/modulo-contratos/signatarios/delete/:sigId/:contractId",
            [
                "controller" => "ContractManagement",
                "action" => "deleteSignatario",
            ],
            ["pass" => ["sigId", "contractId"]],
        )
        ->setMethods(["POST"]);
    $routes->redirect(
        "/modulo-contratos/signatarios",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->redirect(
        "/modulo-contratos/signatarios/",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->connect(
        "/modulo-contratos/gerar-pdf/:id",
        ["controller" => "ContractManagement", "action" => "gerarPdf"],
        ["pass" => ["id"], "id" => "\d+"],
    );
    $routes->redirect(
        "/modulo-contratos/gerar-pdf",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->redirect(
        "/modulo-contratos/gerar-pdf/",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->connect(
        "/modulo-contratos/enviar-assinatura/:id",
        ["controller" => "ContractManagement", "action" => "enviarAssinatura"],
        ["pass" => ["id"], "id" => "\d+"],
    );
    $routes->redirect(
        "/modulo-contratos/enviar-assinatura",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->redirect(
        "/modulo-contratos/enviar-assinatura/",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes
        ->connect(
            "/modulo-contratos/aprovar/:id",
            ["controller" => "ContractManagement", "action" => "aprovar"],
            ["pass" => ["id"], "id" => "\d+"],
        )
        ->setMethods(["POST"]);
    $routes->redirect(
        "/modulo-contratos/aprovar",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->redirect(
        "/modulo-contratos/aprovar/",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes
        ->connect(
            "/modulo-contratos/suspender/:id",
            ["controller" => "ContractManagement", "action" => "suspender"],
            ["pass" => ["id"], "id" => "\d+"],
        )
        ->setMethods(["POST"]);
    $routes->redirect(
        "/modulo-contratos/suspender",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->redirect(
        "/modulo-contratos/suspender/",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes
        ->connect(
            "/modulo-contratos/cancelar/:id",
            ["controller" => "ContractManagement", "action" => "cancelar"],
            ["pass" => ["id"], "id" => "\d+"],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect(
            "/modulo-contratos/update-status/:id",
            ["controller" => "ContractManagement", "action" => "updateStatus"],
            ["pass" => ["id"], "id" => "\d+"],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect(
            "/modulo-contratos/delete/:id",
            ["controller" => "ContractManagement", "action" => "delete"],
            ["pass" => ["id"], "id" => "\d+"],
        )
        ->setMethods(["POST"]);
    $routes->redirect(
        "/modulo-contratos/cancelar",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->redirect(
        "/modulo-contratos/cancelar/",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes
        ->connect(
            "/modulo-contratos/reenviar-link/:id",
            ["controller" => "ContractManagement", "action" => "reenviarLink"],
            ["pass" => ["id"], "id" => "\d+"],
        )
        ->setMethods(["POST"]);
    $routes->redirect(
        "/modulo-contratos/reenviar-link",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->redirect(
        "/modulo-contratos/reenviar-link/",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->connect(
        "/modulo-contratos/renovacoes/:id",
        ["controller" => "ContractManagement", "action" => "verRenovacoes"],
        ["pass" => ["id"], "id" => "\d+"],
    );
    $routes->redirect(
        "/modulo-contratos/renovacoes",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->redirect(
        "/modulo-contratos/renovacoes/",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->connect(
        "/modulo-contratos/aprovar-renovacao/:id",
        ["controller" => "ContractManagement", "action" => "aprovarRenovacao"],
        ["pass" => ["id"], "id" => "\d+"],
    );
    $routes->redirect(
        "/modulo-contratos/aprovar-renovacao",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->redirect(
        "/modulo-contratos/aprovar-renovacao/",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes
        ->connect(
            "/modulo-contratos/recusar-renovacao/:id",
            [
                "controller" => "ContractManagement",
                "action" => "recusarRenovacao",
            ],
            ["pass" => ["id"], "id" => "\d+"],
        )
        ->setMethods(["POST"]);
    $routes->redirect(
        "/modulo-contratos/recusar-renovacao",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->redirect(
        "/modulo-contratos/recusar-renovacao/",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes
        ->connect(
            "/modulo-contratos/solicitar-renovacao/:id",
            [
                "controller" => "ContractManagement",
                "action" => "solicitarRenovacao",
            ],
            ["pass" => ["id"], "id" => "\d+"],
        )
        ->setMethods(["POST"]);
    $routes->redirect(
        "/modulo-contratos/solicitar-renovacao",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->redirect(
        "/modulo-contratos/solicitar-renovacao/",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->connect(
        "/modulo-contratos/pdf/:id",
        ["controller" => "ContractManagement", "action" => "downloadPdf"],
        ["pass" => ["id"], "id" => "\d+"],
    );
    $routes->redirect(
        "/modulo-contratos/pdf",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->redirect(
        "/modulo-contratos/pdf/",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->connect(
        "/modulo-contratos/pdf-assinado/:id",
        ["controller" => "ContractManagement", "action" => "downloadSigned"],
        ["pass" => ["id"], "id" => "\d+"],
    );
    $routes->redirect(
        "/modulo-contratos/pdf-assinado",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes->redirect(
        "/modulo-contratos/pdf-assinado/",
        ["controller" => "ContractManagement", "action" => "index"],
        ["status" => 302],
    );
    $routes
        ->connect("/modulo-contratos/sla-api/:id", [
            "controller" => "ContractManagement",
            "action" => "contractSlaApi",
        ], ["pass" => ["id"], "id" => "\d+"])
        ->setMethods(["GET", "POST"]);
    $routes
        ->connect("/modulo-contratos/exportar", [
            "controller" => "ContractManagement",
            "action" => "exportar",
        ])
        ->setMethods(["GET"]);
    // POST = Autentique; GET/HEAD = verificação no browser. Fallback URL: /webhook-autentique.php (public/)
    $routes
        ->connect("/modulo-contratos/webhook/autentique", [
            "controller" => "ContractManagement",
            "action" => "webhookAutentique",
        ])
        ->setMethods(["POST", "GET", "HEAD"]);
    // Alguns vhosts enviam o path completo com /portal sem APP_BASE no Cake
    $routes
        ->connect("/portal/modulo-contratos/webhook/autentique", [
            "controller" => "ContractManagement",
            "action" => "webhookAutentique",
        ])
        ->setMethods(["POST", "GET", "HEAD"]);
    // Portal cliente — contratos (canónico /cliente/contratos)
    $routes->connect("/cliente/contratos", [
        "controller" => "PortalContratos",
        "action" => "index",
    ]);
    $routes->connect(
        "/cliente/contratos/ver/*",
        ["controller" => "PortalContratos", "action" => "view"],
        ["pass" => ["id"]],
    );
    $routes->connect(
        "/cliente/contratos/pdf/*",
        ["controller" => "PortalContratos", "action" => "downloadPdf"],
        ["pass" => ["id"]],
    );
    $routes->connect(
        "/cliente/contratos/pdf-assinado/*",
        ["controller" => "PortalContratos", "action" => "downloadSigned"],
        ["pass" => ["id"]],
    );
    $routes->connect("/cliente/contratos/faturas", [
        "controller" => "PortalContratos",
        "action" => "faturas",
    ]);
    $routes
        ->connect(
            "/cliente/contratos/renovar/*",
            [
                "controller" => "PortalContratos",
                "action" => "solicitarRenovacao",
            ],
            ["pass" => ["id"]],
        )
        ->setMethods(["POST"]);
    $routes->connect("/cliente/contratos/franquia", [
        "controller" => "PortalContratos",
        "action" => "franquia",
    ]);
    // Compat: URLs antigas → canónico
    $routes->redirect(
        "/cliente/contratos-avancados",
        ["controller" => "PortalContratos", "action" => "index"],
        ["status" => 302],
    );
    $routes->redirect(
        "/cliente/contratos-avancados/view/*",
        ["controller" => "PortalContratos", "action" => "view"],
        ["persist" => true, "status" => 302],
    );
    $routes->redirect(
        "/cliente/contratos-avancados/export-pdf/*",
        ["controller" => "PortalContratos", "action" => "downloadPdf"],
        ["persist" => true, "status" => 302],
    );
    $routes->redirect(
        "/cliente/contratos-avancados/franquia",
        ["controller" => "PortalContratos", "action" => "franquia"],
        ["status" => 302],
    );
    $routes->connect("/cliente/faturas-avancadas", [
        "controller" => "PortalAdvancedInvoices",
        "action" => "index",
    ]);
    $routes->connect(
        "/cliente/faturas-avancadas/view/*",
        ["controller" => "PortalAdvancedInvoices", "action" => "view"],
        ["pass" => ["id"]],
    );
    $routes
        ->connect("/cliente/faturas-avancadas/exportar", [
            "controller" => "PortalAdvancedInvoices",
            "action" => "exportar",
        ])
        ->setMethods(["GET"]);
    $routes->connect("/cliente/historico-atendimento-avancado", [
        "controller" => "PortalAdvancedAttendance",
        "action" => "index",
    ]);
    $routes->connect(
        "/cliente/historico-atendimento-avancado/view/*",
        ["controller" => "PortalAdvancedAttendance", "action" => "view"],
        ["pass" => ["id"]],
    );
    // CSS premium via Cake (leitura em WWW_ROOT/css) — evita 404 estático com APP_BASE=/portal e Alias Apache
    $routes->connect(
        "/pgm-assets/css/:name",
        ["controller" => "PgmAssets", "action" => "css"],
        ["pass" => ["name"]],
    );
    // Alias OGM (mesmas ações que pgm-assets) — HTML/deploy pode usar /ogm-assets/...
    $routes->connect(
        "/ogm-assets/css/:name",
        ["controller" => "PgmAssets", "action" => "css"],
        ["pass" => ["name"]],
    );
    // JS módulo Clientes/edit via Cake — evita 404 em /portal/js/... quando o estático não mapeia para webroot
    $routes->connect(
        "/pgm-assets/js/modules/clientes/:file",
        ["controller" => "PgmAssets", "action" => "clientesModuleJs"],
        [
            "pass" => ["file"],
            "file" => "(cliente-edit|cliente-edit-ficha|cliente-edit-ficha-acessos)\\.js",
        ],
    );
    $routes->connect(
        "/ogm-assets/js/modules/clientes/:file",
        ["controller" => "PgmAssets", "action" => "clientesModuleJs"],
        [
            "pass" => ["file"],
            "file" => "(cliente-edit|cliente-edit-ficha|cliente-edit-ficha-acessos)\\.js",
        ],
    );
    // Compat: HTML em cache / bookmarks ainda pedem /js/modules/clientes/*.js (mesma ação que pgm-assets)
    $routes->connect(
        "/js/modules/clientes/:file",
        ["controller" => "PgmAssets", "action" => "clientesModuleJs"],
        [
            "pass" => ["file"],
            "file" => "(cliente-edit|cliente-edit-ficha|cliente-edit-ficha-acessos)\\.js",
        ],
    );
    // Compat: HTML em cache / links antigos ainda pedem /css/*.css — mesma resposta que pgm-assets
    $routes->connect(
        "/css/:file",
        ["controller" => "PgmAssets", "action" => "legacyCss"],
        [
            "pass" => ["file"],
            "file" =>
                "(produtos-premium|clientes-premium|orcamentos-premium|pgm-action-buttons|pgm-estoque|ativos-premium)\.css",
        ],
    );
    // Notificações internas (equipe) — JSON. Prefixo /pgm-notifications/ evita URLs /portal/portal-notifications
    // que alguns proxies/JS tratam com strip de /^\/portal/ e quebram em "-notifications/...".
    $routes->connect("/pgm-notifications/unread-count", [
        "controller" => "PortalNotifications",
        "action" => "unreadCount",
    ]);
    $routes->connect("/pgm-notifications/list", [
        "controller" => "PortalNotifications",
        "action" => "listJson",
    ]);
    $routes
        ->connect(
            "/pgm-notifications/mark-read/:id",
            ["controller" => "PortalNotifications", "action" => "markRead"],
            ["pass" => ["id"], "id" => "\d+"],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect("/pgm-notifications/mark-all-read", [
            "controller" => "PortalNotifications",
            "action" => "markAllRead",
        ])
        ->setMethods(["POST"]);
    $routes->connect("/pgm-notifications/preferences", [
        "controller" => "PortalNotifications",
        "action" => "preferences",
    ]);
    $routes
        ->connect("/pgm-notifications/save-preferences", [
            "controller" => "PortalNotifications",
            "action" => "savePreferences",
        ])
        ->setMethods(["POST"]);
    // Compat: caminho antigo (integrações / bookmarks)
    $routes->connect("/portal-notifications/unread-count", [
        "controller" => "PortalNotifications",
        "action" => "unreadCount",
    ]);
    $routes->connect("/portal-notifications/list", [
        "controller" => "PortalNotifications",
        "action" => "listJson",
    ]);
    $routes
        ->connect(
            "/portal-notifications/mark-read/:id",
            ["controller" => "PortalNotifications", "action" => "markRead"],
            ["pass" => ["id"], "id" => "\d+"],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect("/portal-notifications/mark-all-read", [
            "controller" => "PortalNotifications",
            "action" => "markAllRead",
        ])
        ->setMethods(["POST"]);
    $routes->connect("/portal-notifications/preferences", [
        "controller" => "PortalNotifications",
        "action" => "preferences",
    ]);
    $routes
        ->connect("/portal-notifications/save-preferences", [
            "controller" => "PortalNotifications",
            "action" => "savePreferences",
        ])
        ->setMethods(["POST"]);
    // —— Ativos / CMDB ITSM ——
    $routes->connect("/ativos", [
        "controller" => "Ativos",
        "action" => "index",
    ]);
    $routes->connect("/ativos/index", [
        "controller" => "Ativos",
        "action" => "index",
    ]);
    $routes->connect("/ativos/add", [
        "controller" => "Ativos",
        "action" => "add",
    ]);
    $routes->connect("/ativos/edit/*", [
        "controller" => "Ativos",
        "action" => "edit",
    ]);
    $routes->connect("/ativos/view/*", [
        "controller" => "Ativos",
        "action" => "view",
    ]);
    $routes
        ->connect("/ativos/delete/*", [
            "controller" => "Ativos",
            "action" => "delete",
        ])
        ->setMethods(["POST", "DELETE"]);
    $routes
        ->connect("/ativos/inativar/*", [
            "controller" => "Ativos",
            "action" => "inativar",
        ])
        ->setMethods(["POST"]);
    $routes
        ->connect("/ativos/reativar/*", [
            "controller" => "Ativos",
            "action" => "reativar",
        ])
        ->setMethods(["POST"]);
    $routes->connect("/ativos/qr/*", [
        "controller" => "Ativos",
        "action" => "qr",
    ]);
    $routes
        ->connect(
            "/ativos/api/by-cliente/*",
            ["controller" => "Ativos", "action" => "apiAssetsByCliente"],
            ["pass" => ["idcliente"]],
        )
        ->setMethods(["GET"]);
    $routes
        ->connect(
            "/ativos/api/nfe-by-cliente/*",
            ["controller" => "Ativos", "action" => "apiNfeByCliente"],
            ["pass" => ["idcliente"]],
        )
        ->setMethods(["GET"]);
    $routes
        ->connect(
            "/ativos/anexo/*",
            ["controller" => "Ativos", "action" => "downloadAnexo"],
            ["pass" => ["id", "file"]],
        )
        ->setMethods(["GET"]);
    // —— Ticket ⇄ Asset (pivot) ——
    $routes
        ->connect(
            "/tickets/api-assets-attach/*",
            ["controller" => "Tickets", "action" => "apiTicketAssetsAttach"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["POST"]);
    $routes
        ->connect(
            "/tickets/api-assets-detach/*",
            ["controller" => "Tickets", "action" => "apiTicketAssetsDetach"],
            ["pass" => ["idticket"]],
        )
        ->setMethods(["POST"]);

    // IAM / pedidos RBAC
    $routes->connect("/users/access-denied", [
        "controller" => "Users",
        "action" => "accessDenied",
    ]);
    $routes->connect("/permissoes/diagnosticar-acesso", [
        "controller" => "Permissoes",
        "action" => "diagnosticarAcesso",
    ]);
    $routes->connect("/permissoes/simular-diagnostico-acesso", [
        "controller" => "Permissoes",
        "action" => "simularDiagnosticoAcesso",
    ]);
    $routes
        ->connect("/permissoes/solicitar-acesso/*", [
            "controller" => "RbacAccessRequests",
            "action" => "solicitarAcesso",
            "pass" => ["supportCode"],
        ])
        ->setMethods(["POST"]);
    $routes->connect("/permissoes/meus-pedidos-acesso", [
        "controller" => "RbacAccessRequests",
        "action" => "meusPedidosAcesso",
    ]);
    $routes->connect("/permissoes/pedidos-acesso", [
        "controller" => "RbacAccessRequests",
        "action" => "pedidosAcesso",
    ]);
    $routes->connect("/permissoes/visualizar-pedido-acesso/*", [
        "controller" => "RbacAccessRequests",
        "action" => "visualizarPedidoAcesso",
        "pass" => ["id"],
    ]);
    $routes
        ->connect("/permissoes/aprovar-pedido-acesso/*", [
            "controller" => "RbacAccessRequests",
            "action" => "aprovarAdminPedidoAcesso",
            "pass" => ["id"],
        ])
        ->setMethods(["POST"]);
    $routes
        ->connect("/permissoes/rejeitar-pedido-acesso/*", [
            "controller" => "RbacAccessRequests",
            "action" => "rejeitarAdminPedidoAcesso",
            "pass" => ["id"],
        ])
        ->setMethods(["POST"]);
    $routes->connect("/permissoes/pedidos-acesso-manager", [
        "controller" => "RbacAccessRequests",
        "action" => "pedidosAcessoManager",
    ]);
    $routes->connect("/permissoes/pedidos-acesso-admin", [
        "controller" => "RbacAccessRequests",
        "action" => "pedidosAcessoAdmin",
    ]);
    $routes
        ->connect("/permissoes/aprovar-manager-pedido-acesso/*", [
            "controller" => "RbacAccessRequests",
            "action" => "aprovarManagerPedidoAcesso",
            "pass" => ["id"],
        ])
        ->setMethods(["POST"]);
    $routes
        ->connect("/permissoes/rejeitar-manager-pedido-acesso/*", [
            "controller" => "RbacAccessRequests",
            "action" => "rejeitarManagerPedidoAcesso",
            "pass" => ["id"],
        ])
        ->setMethods(["POST"]);
    $routes
        ->connect("/permissoes/preview-grant-existing-role/*", [
            "controller" => "RbacAccessRequests",
            "action" => "previewGrantExistingRole",
            "pass" => ["id"],
        ])
        ->setMethods(["GET"]);
    $routes
        ->connect("/permissoes/execute-grant-existing-role/*", [
            "controller" => "RbacAccessRequests",
            "action" => "executeGrantExistingRole",
            "pass" => ["id"],
        ])
        ->setMethods(["POST"]);
    $routes->connect("/permissoes/rbac-audit-logs", [
        "controller" => "RbacAccessRequests",
        "action" => "auditLogs",
    ]);
    $routes->connect("/permissoes/matriz-visual", [
        "controller" => "Permissoes",
        "action" => "matrizVisual",
    ]);
    $routes
        ->connect("/permissoes/matriz-visual-csv", [
            "controller" => "Permissoes",
            "action" => "matrizVisualCsv",
        ])
        ->setMethods(["GET"]);
    $routes->connect("/permissoes/dashboard-acessos", [
        "controller" => "Permissoes",
        "action" => "dashboardAcessos",
    ]);
    $routes
        ->connect("/permissoes/dashboard-acessos-csv", [
            "controller" => "Permissoes",
            "action" => "dashboardAcessosCsv",
        ])
        ->setMethods(["GET"]);
    $routes->connect("/permissoes/acessos-ativos-grants", [
        "controller" => "RbacAccessGrants",
        "action" => "index",
    ]);
    $routes
        ->connect("/permissoes/acesso-grant-renovar/*", [
            "controller" => "RbacAccessGrants",
            "action" => "renovar",
            "pass" => ["id"],
        ])
        ->setMethods(["POST"]);
    $routes
        ->connect("/permissoes/acesso-grant-revogar/*", [
            "controller" => "RbacAccessGrants",
            "action" => "revogar",
            "pass" => ["id"],
        ])
        ->setMethods(["POST"]);

    $routes->fallbacks(DashedRoute::class);
});

// =============================================================================
// MÓDULO LAUDOS — Rotas web (views CTP)
// =============================================================================
Router::scope('/laudos', function (RouteBuilder $routes) {
    $routes->connect('/pareceres', ['controller' => 'Laudos', 'action' => 'index'])->setMethods(['GET']);
    $routes->connect('/pareceres/:id', ['controller' => 'Laudos', 'action' => 'view'], ['pass' => ['id'], 'id' => '[0-9]+'])->setMethods(['GET']);
    $routes->connect('/clientes-buscar', ['controller' => 'Laudos', 'action' => 'clientesBuscar'])->setMethods(['GET']);
});

// Rota pública de validação (sem autenticação)
Router::scope('/validar', function (RouteBuilder $routes) {
    $routes->connect('/:hash', ['controller' => 'Laudos', 'action' => 'validar'], ['pass' => ['hash']]);
});

// =============================================================================
// MÓDULO LAUDOS — API REST
// Controllers em App\Controller\Api\Laudos\ (prefix 'api/laudos')
// =============================================================================
Router::scope('/api/laudos', ['prefix' => 'api/laudos'], function (RouteBuilder $routes) {
    $routes->setExtensions(['json']);

    // Pareceres
    $routes->connect('/pareceres', ['controller' => 'LaudosPareceres', 'action' => 'index'])->setMethods(['GET']);
    $routes->connect('/pareceres', ['controller' => 'LaudosPareceres', 'action' => 'add'])->setMethods(['POST']);
    $routes->connect('/pareceres/:id', ['controller' => 'LaudosPareceres', 'action' => 'view'], ['pass' => ['id'], 'id' => '[0-9]+'])->setMethods(['GET']);
    $routes->connect('/pareceres/:id', ['controller' => 'LaudosPareceres', 'action' => 'edit'], ['pass' => ['id'], 'id' => '[0-9]+'])->setMethods(['PUT', 'PATCH']);
    $routes->connect('/pareceres/:id', ['controller' => 'LaudosPareceres', 'action' => 'delete'], ['pass' => ['id'], 'id' => '[0-9]+'])->setMethods(['DELETE']);
    $routes->connect('/pareceres/:id/duplicar', ['controller' => 'LaudosPareceres', 'action' => 'duplicar'], ['pass' => ['id'], 'id' => '[0-9]+'])->setMethods(['POST']);
    $routes->connect('/pareceres/:id/status', ['controller' => 'LaudosPareceres', 'action' => 'changeStatus'], ['pass' => ['id'], 'id' => '[0-9]+'])->setMethods(['POST']);
    $routes->connect('/pareceres/:id/historico', ['controller' => 'LaudosPareceres', 'action' => 'historico'], ['pass' => ['id'], 'id' => '[0-9]+'])->setMethods(['GET']);
    $routes->connect('/pareceres/:id/pdf', ['controller' => 'LaudosPdf', 'action' => 'pdf'], ['pass' => ['id'], 'id' => '[0-9]+'])->setMethods(['GET']);
    $routes->connect('/pareceres/:id/enviar-email', ['controller' => 'LaudosPdf', 'action' => 'enviarEmail'], ['pass' => ['id'], 'id' => '[0-9]+'])->setMethods(['POST']);

    // Produtos
    $routes->connect('/produtos', ['controller' => 'LaudosProdutos', 'action' => 'add'])->setMethods(['POST']);
    $routes->connect('/produtos/:id', ['controller' => 'LaudosProdutos', 'action' => 'edit'], ['pass' => ['id'], 'id' => '[0-9]+'])->setMethods(['PUT', 'PATCH']);
    $routes->connect('/produtos/:id', ['controller' => 'LaudosProdutos', 'action' => 'delete'], ['pass' => ['id'], 'id' => '[0-9]+'])->setMethods(['DELETE']);

    // Imagens e Anexos
    $routes->connect('/produto-imagens', ['controller' => 'LaudosUploads', 'action' => 'uploadImagem'])->setMethods(['POST']);
    $routes->connect('/produto-imagens/:id', ['controller' => 'LaudosUploads', 'action' => 'deleteImagem'], ['pass' => ['id'], 'id' => '[0-9]+'])->setMethods(['DELETE']);
    $routes->connect('/anexos', ['controller' => 'LaudosUploads', 'action' => 'uploadAnexo'])->setMethods(['POST']);
    $routes->connect('/anexos/:id/download', ['controller' => 'LaudosUploads', 'action' => 'downloadAnexo'], ['pass' => ['id'], 'id' => '[0-9]+'])->setMethods(['GET']);
    $routes->connect('/anexos/:id', ['controller' => 'LaudosUploads', 'action' => 'deleteAnexo'], ['pass' => ['id'], 'id' => '[0-9]+'])->setMethods(['DELETE']);

    $routes->connect('/empresas/:id/logo', ['controller' => 'LaudosEmpresas', 'action' => 'uploadLogo'], ['pass' => ['id'], 'id' => '[0-9]+'])->setMethods(['POST']);
    $routes->connect('/empresas/:id/logo', ['controller' => 'LaudosEmpresas', 'action' => 'deleteLogo'], ['pass' => ['id'], 'id' => '[0-9]+'])->setMethods(['DELETE']);
    $routes->connect('/empresas/:id/carimbo', ['controller' => 'LaudosEmpresas', 'action' => 'uploadCarimbo'], ['pass' => ['id'], 'id' => '[0-9]+'])->setMethods(['POST']);
    $routes->connect('/empresas/:id/carimbo', ['controller' => 'LaudosEmpresas', 'action' => 'deleteCarimbo'], ['pass' => ['id'], 'id' => '[0-9]+'])->setMethods(['DELETE']);
    $routes->connect('/empresas/:id', ['controller' => 'LaudosEmpresas', 'action' => 'edit'], ['pass' => ['id'], 'id' => '[0-9]+'])->setMethods(['PUT', 'PATCH']);

    // Catálogo e Templates
    $routes->connect('/catalogo/pecas', ['controller' => 'LaudosCatalogo', 'action' => 'pecas'])->setMethods(['GET']);
    $routes->connect('/catalogo/pecas', ['controller' => 'LaudosCatalogo', 'action' => 'addPeca'])->setMethods(['POST']);
    $routes->connect('/catalogo/servicos', ['controller' => 'LaudosCatalogo', 'action' => 'servicos'])->setMethods(['GET']);
    $routes->connect('/templates/:tipo', ['controller' => 'LaudosCatalogo', 'action' => 'templates'], ['pass' => ['tipo']])->setMethods(['GET']);

    // Validação pública (sem auth — liberada no ValidacaoController via Auth::allow)
    $routes->connect('/validar/:hash', ['controller' => 'Validacao', 'action' => 'publica'], ['pass' => ['hash']]);
});

// Util (CNPJ/CEP) — namespace App\Controller\Api\
// Scope em /api/util; rotas relativas sem repetir /util/
Router::scope('/api/util', ['prefix' => 'api'], function (RouteBuilder $routes) {
    $routes->connect('/cnpj/:cnpj', ['controller' => 'Util', 'action' => 'cnpj'], ['pass' => ['cnpj']]);
    $routes->connect('/cep/:cep', ['controller' => 'Util', 'action' => 'cep'], ['pass' => ['cep']]);
});

/**
 * Load all plugin routes. See the Plugin documentation on
 * how to customize the loading of plugin routes.
 */
//Plugin::routes();
