<?php
declare(strict_types=1);

namespace App\Controller\Api\Laudos;

use App\Controller\AppController;
use Cake\Event\Event;

/**
 * Base das APIs JSON de Laudos (sessão Auth).
 * Garante que exista linha em laudos_empresas com o mesmo id que empresas.id (idempresa),
 * para não violar FKs ao listar/criar pareceres ou contadores.
 */
abstract class LaudosApiController extends AppController
{
    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        if ($this->Auth->user('id') === null) {
            return;
        }
        $portalEmpresaId = (int)($this->Auth->user('idempresa') ?? 1);
        if ($portalEmpresaId < 1) {
            return;
        }
        $this->loadModel('LaudosEmpresas');
        $this->LaudosEmpresas->ensureForPortalEmpresa($portalEmpresaId);
    }
}
