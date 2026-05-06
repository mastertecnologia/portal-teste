<?php
/**
 * TRECHO PARA ADICIONAR EM: config/routes.php
 *
 * Adicione as linhas abaixo dentro do `Router::scope('/', function (RouteBuilder $builder) {})`
 * existente, OU como um scope novo dedicado à API.
 */

declare(strict_types=1);

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

// =============================================================================
// API LAUDOS — adicione este bloco em config/routes.php
// =============================================================================
Router::scope('/api', function (RouteBuilder $builder) {
    $builder->setExtensions(['json']);

    // Pareceres (CRUD)
    $builder->scope('/laudos/pareceres', function (RouteBuilder $r) {
        $r->connect('', ['controller' => 'LaudosPareceres', 'action' => 'index'], ['_method' => 'GET']);
        $r->connect('', ['controller' => 'LaudosPareceres', 'action' => 'add'], ['_method' => 'POST']);
        $r->connect('/{id}', ['controller' => 'LaudosPareceres', 'action' => 'view'], ['_method' => 'GET', 'pass' => ['id']]);
        $r->connect('/{id}', ['controller' => 'LaudosPareceres', 'action' => 'edit'], ['_method' => ['PUT', 'PATCH'], 'pass' => ['id']]);
        $r->connect('/{id}', ['controller' => 'LaudosPareceres', 'action' => 'delete'], ['_method' => 'DELETE', 'pass' => ['id']]);

        // Ações específicas
        $r->connect('/{id}/duplicar', ['controller' => 'LaudosPareceres', 'action' => 'duplicar'], ['_method' => 'POST', 'pass' => ['id']]);
        $r->connect('/{id}/status', ['controller' => 'LaudosPareceres', 'action' => 'changeStatus'], ['_method' => 'POST', 'pass' => ['id']]);
        $r->connect('/{id}/historico', ['controller' => 'LaudosPareceres', 'action' => 'historico'], ['_method' => 'GET', 'pass' => ['id']]);
        $r->connect('/{id}/pdf', ['controller' => 'LaudosPdf', 'action' => 'pdf'], ['_method' => 'GET', 'pass' => ['id']]);
        $r->connect('/{id}/enviar-email', ['controller' => 'LaudosPdf', 'action' => 'enviarEmail'], ['_method' => 'POST', 'pass' => ['id']]);
    });

    // Produtos / equipamentos
    $builder->scope('/laudos/produtos', function (RouteBuilder $r) {
        $r->connect('', ['controller' => 'LaudosProdutos', 'action' => 'add'], ['_method' => 'POST']);
        $r->connect('/{id}', ['controller' => 'LaudosProdutos', 'action' => 'edit'], ['_method' => ['PUT', 'PATCH'], 'pass' => ['id']]);
        $r->connect('/{id}', ['controller' => 'LaudosProdutos', 'action' => 'delete'], ['_method' => 'DELETE', 'pass' => ['id']]);
    });

    // Imagens dos produtos
    $builder->scope('/laudos/produto-imagens', function (RouteBuilder $r) {
        $r->connect('', ['controller' => 'LaudosUploads', 'action' => 'uploadImagem'], ['_method' => 'POST']);
        $r->connect('/{id}', ['controller' => 'LaudosUploads', 'action' => 'deleteImagem'], ['_method' => 'DELETE', 'pass' => ['id']]);
    });

    // Anexos
    $builder->scope('/laudos/anexos', function (RouteBuilder $r) {
        $r->connect('', ['controller' => 'LaudosUploads', 'action' => 'uploadAnexo'], ['_method' => 'POST']);
        $r->connect('/{id}/download', ['controller' => 'LaudosUploads', 'action' => 'downloadAnexo'], ['_method' => 'GET', 'pass' => ['id']]);
    });

    // Catálogo
    $builder->scope('/laudos/catalogo', function (RouteBuilder $r) {
        $r->connect('/pecas', ['controller' => 'LaudosCatalogo', 'action' => 'pecas'], ['_method' => 'GET']);
        $r->connect('/pecas', ['controller' => 'LaudosCatalogo', 'action' => 'addPeca'], ['_method' => 'POST']);
        $r->connect('/servicos', ['controller' => 'LaudosCatalogo', 'action' => 'servicos'], ['_method' => 'GET']);
    });

    // Templates
    $builder->scope('/laudos/templates', function (RouteBuilder $r) {
        $r->connect('/{tipo}', ['controller' => 'LaudosCatalogo', 'action' => 'templates'], ['_method' => 'GET', 'pass' => ['tipo']]);
    });

    // Util — proxies para BrasilAPI / ViaCEP
    $builder->connect('/util/cnpj/{cnpj}', ['controller' => 'Util', 'action' => 'cnpj'], ['pass' => ['cnpj']]);
    $builder->connect('/util/cep/{cep}', ['controller' => 'Util', 'action' => 'cep'], ['pass' => ['cep']]);
});

// =============================================================================
// ROTA PÚBLICA DE VALIDAÇÃO — sem autenticação
// =============================================================================
Router::scope('/', function (RouteBuilder $builder) {
    $builder->connect('/validar/{hash}',
        ['controller' => 'Validacao', 'action' => 'publica'],
        ['pass' => ['hash']]
    );
});

/* ============================================================================
   IMPORTANTE: configurar o Authentication para liberar a rota pública.
   No seu AppController.php, dentro de initialize() ou beforeFilter():

   $this->Authentication->allowUnauthenticated(['publica']);

   Ou no controller específico (ValidacaoController) já está configurado.
============================================================================ */
