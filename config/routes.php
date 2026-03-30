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
    $routes->connect('/clientes/add-api', ['controller' => 'Clientes', 'action' => 'addAPI'])->setMethods(['POST']);
    $routes->connect('/clientes/addAPI', ['controller' => 'Clientes', 'action' => 'addAPI'])->setMethods(['POST']);
    $routes->connect('/clientes/list-api', ['controller' => 'Clientes', 'action' => 'listAPI'])->setMethods(['GET']);
    $routes->connect('/clientes/listAPI', ['controller' => 'Clientes', 'action' => 'listAPI'])->setMethods(['GET']);
    // API cadastro consolidado: dados empresa por CNPJ (Receita + IE + IM)
    $routes->connect('/api/cadastro/empresa/consultar', ['controller' => 'Cadastro', 'action' => 'consultar'])->setMethods(['POST']);
    $routes->connect('/api/cadastro/empresa/:cnpj', ['controller' => 'Cadastro', 'action' => 'empresa', 'cnpj'])->setPass(['cnpj'])->setMethods(['GET']);
    // Tickets — UI React (JSON com sessão; desbloqueado no Security)
    $routes->connect('/tickets/operacional', ['controller' => 'Tickets', 'action' => 'operacional']);
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
    $routes->connect('/orcamentos/catalogo', ['controller' => 'Orcamentos', 'action' => 'catalogo']);
    $routes->connect('/orcamentos/:id/pdf', ['controller' => 'Orcamentos', 'action' => 'pdf'], ['pass' => ['id'], 'id' => '\d+']);
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
    $routes->fallbacks(DashedRoute::class);
});

/**
 * Load all plugin routes. See the Plugin documentation on
 * how to customize the loading of plugin routes.
 */
//Plugin::routes();
