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

Router::scope('/', function ($routes) {
    $routes->connect('/', ['controller' => 'Users', 'action' => 'dashboard']);
    $routes->connect('/agenda', ['controller' => 'Visitas', 'action' => 'index']);
    $routes->connect('/agenda/index', ['controller' => 'Visitas', 'action' => 'index']);
    $routes->connect('/agenda/indexcliente', ['controller' => 'Visitas', 'action' => 'indexcliente']);
    $routes->connect('/agenda/calendario', ['controller' => 'Visitas', 'action' => 'calendario']);
    $routes->connect('/agenda/add', ['controller' => 'Visitas', 'action' => 'add']);
    $routes->connect('/agenda/edit/*', ['controller' => 'Visitas', 'action' => 'edit']);
    $routes->connect('/agenda/delete/*', ['controller' => 'Visitas', 'action' => 'delete']);
    $routes->connect('/agenda/view/*', ['controller' => 'Visitas', 'action' => 'view']);
    $routes->connect('/prefaturamento', ['controller' => 'Prefaturamento', 'action' => 'index']);
    $routes->connect('/prefaturamento/index', ['controller' => 'Prefaturamento', 'action' => 'index']);
    $routes->connect('/prefaturamento/conferencia', ['controller' => 'Prefaturamento', 'action' => 'conferencia'])->setMethods(['POST']);
    $routes->connect('/locacao', ['controller' => 'Faturas', 'action' => 'index']);
    $routes->connect('/locacao/index', ['controller' => 'Faturas', 'action' => 'index']);
    $routes->connect('/locacao/view/*', ['controller' => 'Faturas', 'action' => 'view']);
    $routes->connect('/locacao/add', ['controller' => 'Faturas', 'action' => 'add']);
    $routes->connect('/locacao/edit/*', ['controller' => 'Faturas', 'action' => 'edit']);
    $routes->connect('/locacao/imprimir/*', ['controller' => 'Faturas', 'action' => 'imprimir']);
    $routes->connect('/locacao/aprovar/*', ['controller' => 'Faturas', 'action' => 'aprovar']);
    $routes->connect('/locacao/rejeitar/*', ['controller' => 'Faturas', 'action' => 'rejeitar']);
    $routes->connect('/locacao/receber/*', ['controller' => 'Faturas', 'action' => 'receber']);
    $routes->connect('/locacao/devolveritem/*', ['controller' => 'Faturas', 'action' => 'devolveritem']);
    $routes->connect('/locacao/recibo/*', ['controller' => 'Faturas', 'action' => 'recibo']);
    // API integração ERP: listar ordens (ex.: liberadas para faturamento) e atualizar situação
    $routes->connect('/ordensservico/list-api', ['controller' => 'Ordensservico', 'action' => 'listAPI'])->setMethods(['GET', 'POST']);
    $routes->connect('/ordensservico/listAPI', ['controller' => 'Ordensservico', 'action' => 'listAPI'])->setMethods(['GET', 'POST']);
    $routes->connect('/ordensservico/refresh-api', ['controller' => 'Ordensservico', 'action' => 'refreshAPI'])->setMethods(['PUT']);
    $routes->connect('/ordensservico/refreshAPI', ['controller' => 'Ordensservico', 'action' => 'refreshAPI'])->setMethods(['PUT']);
    // API integração ERP: cadastro de produtos (Integrador GridERP + Web → Portal)
    $routes->connect('/produtos/add-api', ['controller' => 'Produtos', 'action' => 'addAPI'])->setMethods(['POST']);
    $routes->connect('/produtos/addAPI', ['controller' => 'Produtos', 'action' => 'addAPI'])->setMethods(['POST']);
    $routes->connect('/produtos/list-api', ['controller' => 'Produtos', 'action' => 'listAPI'])->setMethods(['GET']);
    $routes->connect('/produtos/listAPI', ['controller' => 'Produtos', 'action' => 'listAPI'])->setMethods(['GET']);
    $routes->connect('/produtos/precificacao', ['controller' => 'Produtos', 'action' => 'precificacao'])->setMethods(['GET']);
    $routes->connect('/produtos/salvar-precos', ['controller' => 'Produtos', 'action' => 'salvarPrecos'])->setMethods(['POST']);
    $routes->connect('/produtos/salvarPrecos', ['controller' => 'Produtos', 'action' => 'salvarPrecos'])->setMethods(['POST']);
    // API integração ERP: clientes e contratos
    // Contratos (itens) — detalhe no ERP
    $routes->connect('/clicontratos/view/*', ['controller' => 'Clicontratos', 'action' => 'view'], ['pass' => ['id']]);
    $routes->connect('/clicontratos/renovar/*', ['controller' => 'Clicontratos', 'action' => 'renovar'], ['pass' => ['id']]);
    $routes->connect('/clientes/add-api', ['controller' => 'Clientes', 'action' => 'addAPI'])->setMethods(['POST']);
    $routes->connect('/clientes/addAPI', ['controller' => 'Clientes', 'action' => 'addAPI'])->setMethods(['POST']);
    $routes->connect('/clientes/list-api', ['controller' => 'Clientes', 'action' => 'listAPI'])->setMethods(['GET']);
    $routes->connect('/clientes/listAPI', ['controller' => 'Clientes', 'action' => 'listAPI'])->setMethods(['GET']);
    // UI ERP: mesmas URLs que DashedRoute geraria; explícito para não depender só do fallback e documentar o contrato.
    $routes->connect('/clientes/reativar/*', ['controller' => 'Clientes', 'action' => 'reativar']);
    $routes->connect('/clientes/inativar/*', ['controller' => 'Clientes', 'action' => 'inativar']);
    // API cadastro consolidado: dados empresa por CNPJ (Receita + IE + IM)
    $routes->connect('/api/cadastro/empresa/consultar', ['controller' => 'Cadastro', 'action' => 'consultar'])->setMethods(['POST']);
    $routes->connect('/api/cadastro/empresa/:cnpj', ['controller' => 'Cadastro', 'action' => 'empresa', 'cnpj'])->setPass(['cnpj'])->setMethods(['GET']);
    // Tickets — UI React (JSON com sessão; desbloqueado no Security)
    $routes->connect('/tickets/operacional', ['controller' => 'Tickets', 'action' => 'operacional']);
    $routes->connect('/tickets/historico', ['controller' => 'Tickets', 'action' => 'historico']);
    $routes->connect('/tickets/api-index', ['controller' => 'Tickets', 'action' => 'apiIndex'])->setMethods(['GET']);
    $routes->connect('/tickets/api-dashboard-operacional', ['controller' => 'Tickets', 'action' => 'apiDashboardOperacional'])->setMethods(['GET']);
    $routes->connect('/tickets/api-index-cliente', ['controller' => 'Tickets', 'action' => 'apiIndexCliente'])->setMethods(['GET']);
    $routes->connect('/tickets/api-view/*', ['controller' => 'Tickets', 'action' => 'apiView'], ['pass' => ['idticket']])->setMethods(['GET']);
    $routes->connect('/tickets/api-comments/*', ['controller' => 'Tickets', 'action' => 'apiComments'], ['pass' => ['idticket']])->setMethods(['GET']);
    $routes->connect('/tickets/api-save/*', ['controller' => 'Tickets', 'action' => 'apiSaveTicket'], ['pass' => ['idticket']])->setMethods(['POST', 'PUT']);
    $routes->connect('/tickets/api-anexo-upload/*', ['controller' => 'Tickets', 'action' => 'apiAnexoUpload'], ['pass' => ['idticket']])->setMethods(['POST']);
    $routes->connect('/tickets/api-anexo-delete/*', ['controller' => 'Tickets', 'action' => 'apiAnexoDelete'], ['pass' => ['idanexo']])->setMethods(['POST']);
    $routes->connect('/tickets/start-ticket/*', ['controller' => 'Tickets', 'action' => 'startTicket'], ['pass' => ['idticket']])->setMethods(['POST', 'PUT']);
    $routes->connect('/tickets/startTicket/*', ['controller' => 'Tickets', 'action' => 'startTicket'], ['pass' => ['idticket']])->setMethods(['POST', 'PUT']);
    $routes->connect('/queues/get-available-queues/*', ['controller' => 'Queues', 'action' => 'getAvailableQueues'], ['pass' => ['ticketId']])->setMethods(['GET']);
    $routes->connect('/queues/getAvailableQueues/*', ['controller' => 'Queues', 'action' => 'getAvailableQueues'], ['pass' => ['ticketId']])->setMethods(['GET']);
    $routes->connect('/ticket-comentarios/api-add/*', ['controller' => 'Ticketcomentarios', 'action' => 'apiAdd'], ['pass' => ['idticket']])->setMethods(['POST']);
    // Central de Atendimento (layout dedicado; mesma sessão e APIs de tickets)
    $routes->connect('/servicedesk', ['controller' => 'Servicedesk', 'action' => 'index']);
    $routes->connect('/servicedesk/', ['controller' => 'Servicedesk', 'action' => 'index']);
    $routes->connect('/servicedesk/operacional', ['controller' => 'Servicedesk', 'action' => 'operacional']);
    // Precificação / Gestão de Preços
    $routes->connect('/produtos/estoque-pdf/*', ['controller' => 'Produtos', 'action' => 'estoquePdf']);
    // Orçamentos — URLs explícitas (prompt_cursor_cakephp.md PROMPT 7); o inflection padrão já cobre, isto documenta o contrato.
    $routes->connect('/orcamentos', ['controller' => 'Orcamentos', 'action' => 'index']);
    $routes->connect('/orcamentos/add', ['controller' => 'Orcamentos', 'action' => 'add']);
    $routes->connect('/orcamentos/solicitar', ['controller' => 'Orcamentos', 'action' => 'solicitar']);
    $routes->connect('/orcamentos/catalogo-sugestoes', ['controller' => 'Orcamentos', 'action' => 'catalogoSugestoes'])->setMethods(['GET']);
    $routes->connect('/orcamentos/catalogo', ['controller' => 'Orcamentos', 'action' => 'catalogo']);
    $routes->connect('/orcamentos/:id/pdf', ['controller' => 'Orcamentos', 'action' => 'pdf'], ['pass' => ['id'], 'id' => '\d+']);
    // Faturamento — módulo de emissão de documentos fiscais / cobranças
    $routes->connect('/faturamento', ['controller' => 'Faturamento', 'action' => 'index']);
    $routes->connect('/faturamento/index', ['controller' => 'Faturamento', 'action' => 'index']);
    $routes->connect('/faturamento/add', ['controller' => 'Faturamento', 'action' => 'add']);
    $routes->connect('/faturamento/view/*', ['controller' => 'Faturamento', 'action' => 'view']);
    $routes->connect('/faturamento/edit/*', ['controller' => 'Faturamento', 'action' => 'edit']);
    $routes->connect('/faturamento/delete/*', ['controller' => 'Faturamento', 'action' => 'delete']);
    $routes->connect('/faturamento/alterar-status/*', ['controller' => 'Faturamento', 'action' => 'alterarStatus'])->setMethods(['POST']);
    $routes->connect(
        '/faturamento/gerar-de-os/:idordem',
        ['controller' => 'Faturamento', 'action' => 'gerarDeOS'],
        ['pass' => ['idordem'], 'idordem' => '\d+']
    );
    // Financeiro — dashboard e contas a receber/pagar
    $routes->connect('/financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
    $routes->connect('/financeiro/index', ['controller' => 'Financeiro', 'action' => 'index']);
    $routes->connect('/financeiro/contas-receber', ['controller' => 'Financeiro', 'action' => 'contasReceber']);
    $routes->connect('/financeiro/fatura/:id', ['controller' => 'Financeiro', 'action' => 'fatura'], ['pass' => ['id'], 'id' => '\d+']);
    $routes->connect('/financeiro/fatura/:id/exportar', ['controller' => 'Financeiro', 'action' => 'exportarFatura'], ['pass' => ['id'], 'id' => '\d+']);
    $routes->connect('/financeiro/fatura/:id/exportar-pdf', ['controller' => 'Financeiro', 'action' => 'exportarFaturaPdf'], ['pass' => ['id'], 'id' => '\d+']);
    $routes->connect('/financeiro/registrar-recebimento/*', ['controller' => 'Financeiro', 'action' => 'registrarRecebimento'])->setMethods(['POST']);
    // Relatórios e Indicadores (ERP)
    $routes->connect('/relatorios', ['controller' => 'Relatorios', 'action' => 'index']);
    $routes->connect('/relatorios/index', ['controller' => 'Relatorios', 'action' => 'index']);
    // Portal do Cliente — Relatórios (tela simples, sem dados internos)
    $routes->connect('/cliente/relatorios', ['controller' => 'PortalRelatorios', 'action' => 'index']);
    $routes->connect('/cliente/relatorios/index', ['controller' => 'PortalRelatorios', 'action' => 'index']);
    $routes->connect('/cliente/relatorios/exportar', ['controller' => 'PortalRelatorios', 'action' => 'exportar'])->setMethods(['GET']);
    $routes->connect('/cliente/relatorios/exportar-excel', ['controller' => 'PortalRelatorios', 'action' => 'exportarExcel'])->setMethods(['GET']);
    // Módulo avançado — ERP (equipe role 0)
    // Modelos de contrato — URL canónica /contract-templates
    $routes->connect('/contract-templates', ['controller' => 'ContractTemplates', 'action' => 'index']);
    $routes->connect('/contract-templates/add', ['controller' => 'ContractTemplates', 'action' => 'add']);
    $routes->connect('/contract-templates/edit/*', ['controller' => 'ContractTemplates', 'action' => 'edit'], ['pass' => ['id']]);
    $routes->connect('/contract-templates/delete/*', ['controller' => 'ContractTemplates', 'action' => 'delete'], ['pass' => ['id']])->setMethods(['POST']);
    $routes->connect('/contract-templates/preview/*', ['controller' => 'ContractTemplates', 'action' => 'preview'], ['pass' => ['id']]);
    $routes->connect('/contract-templates/clonar/*', ['controller' => 'ContractTemplates', 'action' => 'clonar'], ['pass' => ['id']]);
    // Compat: paths antigos /modulo-avancado/contratos/* → gestão (/modulo-contratos)
    $routes->redirect('/modulo-avancado/contratos', ['controller' => 'ContractManagement', 'action' => 'index'], ['status' => 302]);
    $routes->redirect('/modulo-avancado/contratos/view/*', ['controller' => 'ContractManagement', 'action' => 'view'], ['persist' => true, 'status' => 302]);
    $routes->redirect('/modulo-avancado/contratos/export-pdf/*', ['controller' => 'ContractManagement', 'action' => 'gerarPdf'], ['persist' => true, 'status' => 302]);
    // Compat: modelos antigos → /contract-templates (mantém POST delete no path legado)
    $routes->redirect('/modulo-avancado/modelos-contrato', ['controller' => 'ContractTemplates', 'action' => 'index'], ['status' => 302]);
    $routes->redirect('/modulo-avancado/modelos-contrato/add', ['controller' => 'ContractTemplates', 'action' => 'add'], ['status' => 302]);
    $routes->redirect('/modulo-avancado/modelos-contrato/edit/*', ['controller' => 'ContractTemplates', 'action' => 'edit'], ['persist' => true, 'status' => 302]);
    $routes->connect('/modulo-avancado/modelos-contrato/delete/*', ['controller' => 'ContractTemplates', 'action' => 'delete'], ['pass' => ['id']])->setMethods(['POST']);
    $routes->connect('/modulo-avancado/faturas', ['controller' => 'AdvancedInvoices', 'action' => 'index']);
    $routes->connect('/modulo-avancado/faturas/view/*', ['controller' => 'AdvancedInvoices', 'action' => 'view'], ['pass' => ['id']]);
    $routes->connect('/modulo-avancado/faturas/exportar', ['controller' => 'AdvancedInvoices', 'action' => 'export'])->setMethods(['GET']);
    $routes->connect('/modulo-avancado/faturas/marcar-paga/*', ['controller' => 'AdvancedInvoices', 'action' => 'markPaid'], ['pass' => ['id']])->setMethods(['POST']);
    $routes->connect('/modulo-avancado/indicadores', ['controller' => 'AdvancedReports', 'action' => 'index']);
    $routes->connect('/modulo-avancado/indicadores/exportar', ['controller' => 'AdvancedReports', 'action' => 'export'])->setMethods(['GET']);
    // Gestão de contratos (ERP) — spec /modulo-contratos
    $routes->connect('/modulo-contratos', ['controller' => 'ContractManagement', 'action' => 'index']);
    $routes->connect('/modulo-contratos/view/*', ['controller' => 'ContractManagement', 'action' => 'view'], ['pass' => ['id']]);
    $routes->connect('/modulo-contratos/add', ['controller' => 'ContractManagement', 'action' => 'add']);
    $routes->connect('/modulo-contratos/edit/*', ['controller' => 'ContractManagement', 'action' => 'edit'], ['pass' => ['id']]);
    // :id numérico evita URL sem id e ajuda o Router a gerar o mesmo path no redirect
    $routes->connect('/modulo-contratos/servicos/:id', ['controller' => 'ContractManagement', 'action' => 'addServicos'], ['pass' => ['id'], 'id' => '\d+']);
    $routes->redirect('/modulo-contratos/servicos', ['controller' => 'ContractManagement', 'action' => 'index'], ['status' => 302]);
    $routes->connect('/modulo-contratos/signatarios/*', ['controller' => 'ContractManagement', 'action' => 'addSignatarios'], ['pass' => ['id']]);
    $routes->connect('/modulo-contratos/gerar-pdf/*', ['controller' => 'ContractManagement', 'action' => 'gerarPdf'], ['pass' => ['id']]);
    $routes->connect('/modulo-contratos/enviar-assinatura/*', ['controller' => 'ContractManagement', 'action' => 'enviarAssinatura'], ['pass' => ['id']]);
    $routes->connect('/modulo-contratos/aprovar/*', ['controller' => 'ContractManagement', 'action' => 'aprovar'], ['pass' => ['id']])->setMethods(['POST']);
    $routes->connect('/modulo-contratos/suspender/*', ['controller' => 'ContractManagement', 'action' => 'suspender'], ['pass' => ['id']])->setMethods(['POST']);
    $routes->connect('/modulo-contratos/cancelar/*', ['controller' => 'ContractManagement', 'action' => 'cancelar'], ['pass' => ['id']])->setMethods(['POST']);
    $routes->connect('/modulo-contratos/reenviar-link/*', ['controller' => 'ContractManagement', 'action' => 'reenviarLink'], ['pass' => ['id']])->setMethods(['POST']);
    $routes->connect('/modulo-contratos/renovacoes/*', ['controller' => 'ContractManagement', 'action' => 'verRenovacoes'], ['pass' => ['id']]);
    $routes->connect('/modulo-contratos/aprovar-renovacao/*', ['controller' => 'ContractManagement', 'action' => 'aprovarRenovacao'], ['pass' => ['id']]);
    $routes->connect('/modulo-contratos/recusar-renovacao/*', ['controller' => 'ContractManagement', 'action' => 'recusarRenovacao'], ['pass' => ['id']])->setMethods(['POST']);
    $routes->connect('/modulo-contratos/solicitar-renovacao/*', ['controller' => 'ContractManagement', 'action' => 'solicitarRenovacao'], ['pass' => ['id']])->setMethods(['POST']);
    $routes->connect('/modulo-contratos/pdf/*', ['controller' => 'ContractManagement', 'action' => 'downloadPdf'], ['pass' => ['id']]);
    $routes->connect('/modulo-contratos/pdf-assinado/*', ['controller' => 'ContractManagement', 'action' => 'downloadSigned'], ['pass' => ['id']]);
    $routes->connect('/modulo-contratos/exportar', ['controller' => 'ContractManagement', 'action' => 'exportar'])->setMethods(['GET']);
    $routes->connect('/modulo-contratos/webhook/autentique', ['controller' => 'ContractManagement', 'action' => 'webhookAutentique'])->setMethods(['POST']);
    // Portal cliente — contratos (canónico /cliente/contratos)
    $routes->connect('/cliente/contratos', ['controller' => 'PortalContratos', 'action' => 'index']);
    $routes->connect('/cliente/contratos/ver/*', ['controller' => 'PortalContratos', 'action' => 'view'], ['pass' => ['id']]);
    $routes->connect('/cliente/contratos/pdf/*', ['controller' => 'PortalContratos', 'action' => 'downloadPdf'], ['pass' => ['id']]);
    $routes->connect('/cliente/contratos/pdf-assinado/*', ['controller' => 'PortalContratos', 'action' => 'downloadSigned'], ['pass' => ['id']]);
    $routes->connect('/cliente/contratos/faturas', ['controller' => 'PortalContratos', 'action' => 'faturas']);
    $routes->connect('/cliente/contratos/renovar/*', ['controller' => 'PortalContratos', 'action' => 'solicitarRenovacao'], ['pass' => ['id']])->setMethods(['POST']);
    $routes->connect('/cliente/contratos/franquia', ['controller' => 'PortalContratos', 'action' => 'franquia']);
    // Compat: URLs antigas → canónico
    $routes->redirect('/cliente/contratos-avancados', ['controller' => 'PortalContratos', 'action' => 'index'], ['status' => 302]);
    $routes->redirect('/cliente/contratos-avancados/view/*', ['controller' => 'PortalContratos', 'action' => 'view'], ['persist' => true, 'status' => 302]);
    $routes->redirect('/cliente/contratos-avancados/export-pdf/*', ['controller' => 'PortalContratos', 'action' => 'downloadPdf'], ['persist' => true, 'status' => 302]);
    $routes->redirect('/cliente/contratos-avancados/franquia', ['controller' => 'PortalContratos', 'action' => 'franquia'], ['status' => 302]);
    $routes->connect('/cliente/faturas-avancadas', ['controller' => 'PortalAdvancedInvoices', 'action' => 'index']);
    $routes->connect('/cliente/faturas-avancadas/view/*', ['controller' => 'PortalAdvancedInvoices', 'action' => 'view'], ['pass' => ['id']]);
    $routes->connect('/cliente/faturas-avancadas/exportar', ['controller' => 'PortalAdvancedInvoices', 'action' => 'exportar'])->setMethods(['GET']);
    $routes->connect('/cliente/historico-atendimento-avancado', ['controller' => 'PortalAdvancedAttendance', 'action' => 'index']);
    $routes->connect('/cliente/historico-atendimento-avancado/view/*', ['controller' => 'PortalAdvancedAttendance', 'action' => 'view'], ['pass' => ['id']]);
    // CSS premium via Cake (leitura em WWW_ROOT/css) — evita 404 estático com APP_BASE=/portal e Alias Apache
    $routes->connect(
        '/pgm-assets/css/:name',
        ['controller' => 'PgmAssets', 'action' => 'css'],
        ['pass' => ['name']]
    );
    // Compat: HTML em cache / links antigos ainda pedem /css/*.css — mesma resposta que pgm-assets
    $routes->connect(
        '/css/:file',
        ['controller' => 'PgmAssets', 'action' => 'legacyCss'],
        [
            'pass' => ['file'],
            'file' => '(produtos-premium|clientes-premium|orcamentos-premium|pgm-action-buttons)\.css',
        ]
    );
    // Notificações internas (equipe) — JSON; não altera APIs ERP existentes
    $routes->connect('/portal-notifications/unread-count', ['controller' => 'PortalNotifications', 'action' => 'unreadCount']);
    $routes->connect('/portal-notifications/list', ['controller' => 'PortalNotifications', 'action' => 'listJson']);
    $routes->connect('/portal-notifications/mark-read/:id', ['controller' => 'PortalNotifications', 'action' => 'markRead'], ['pass' => ['id'], 'id' => '\d+'])->setMethods(['POST']);
    $routes->connect('/portal-notifications/mark-all-read', ['controller' => 'PortalNotifications', 'action' => 'markAllRead'])->setMethods(['POST']);
    $routes->connect('/portal-notifications/preferences', ['controller' => 'PortalNotifications', 'action' => 'preferences']);
    $routes->connect('/portal-notifications/save-preferences', ['controller' => 'PortalNotifications', 'action' => 'savePreferences'])->setMethods(['POST']);
    $routes->fallbacks(DashedRoute::class);
});

/**
 * Load all plugin routes. See the Plugin documentation on
 * how to customize the loading of plugin routes.
 */
//Plugin::routes();
