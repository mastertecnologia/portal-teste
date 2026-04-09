<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     3.3.0
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App;

use Cake\Core\Configure;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use App\Middleware\CollapseDuplicatePortalPathMiddleware;
use App\Middleware\PortalNotificationsBasePathMiddleware;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Error\Middleware\ErrorHandlerMiddleware;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 */
class Application extends BaseApplication
{

    public function bootstrap() {
        parent::bootstrap();
        // Load the contact manager plugin by class name
        if (Configure::read('debug') && is_dir(ROOT . DS . 'vendor' . DS . 'cakephp' . DS . 'debug_kit')) {
            $this->addPlugin('DebugKit');
        }
    }

     /**
     * Setup the middleware your application will use.
     *
     * @param \Cake\Http\MiddlewareQueue $middleware The middleware queue to setup.
     * @return \Cake\Http\MiddlewareQueue The updated middleware.
     */
    public function middleware($middleware)
    {
        $middleware
            // Catch any exceptions in the lower layers,
            // and make an error page/response
            ->add(new ErrorHandlerMiddleware())

            // Parse JSON/XML body (POST/PUT) para APIs de integração ERP
            ->add(new BodyParserMiddleware())

            // Handle plugin/theme assets like CakePHP normally does.
            ->add(new AssetMiddleware())

            // Corrige /portal/portal/... antes do roteamento (links relativos mal formados).
            ->add(new CollapseDuplicatePortalPathMiddleware())

            // /pgm-notifications/* (e legado /portal-notifications/*) sem App.base → redireciona com prefixo
            ->add(new PortalNotificationsBasePathMiddleware())

            // Apply routing
            ->add(new RoutingMiddleware($this));

        return $middleware;
    }
}
