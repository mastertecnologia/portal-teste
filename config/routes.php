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
    // API integração ERP: clientes e contratos
    $routes->connect('/clientes/add-api', ['controller' => 'Clientes', 'action' => 'addAPI'])->setMethods(['POST']);
    $routes->connect('/clientes/addAPI', ['controller' => 'Clientes', 'action' => 'addAPI'])->setMethods(['POST']);
    $routes->connect('/clientes/list-api', ['controller' => 'Clientes', 'action' => 'listAPI'])->setMethods(['GET']);
    $routes->connect('/clientes/listAPI', ['controller' => 'Clientes', 'action' => 'listAPI'])->setMethods(['GET']);
    $routes->fallbacks(DashedRoute::class);
});

/**
 * Load all plugin routes. See the Plugin documentation on
 * how to customize the loading of plugin routes.
 */
//Plugin::routes();
