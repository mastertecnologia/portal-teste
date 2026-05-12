<?php
declare(strict_types=1);

namespace App\Controller\Api\Laudos;

class LaudosCatalogoController extends LaudosApiController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('LaudosCatalogoPecas');
        $this->loadModel('LaudosCatalogoServicos');
        $this->loadModel('LaudosTemplates');
        $this->viewBuilder()->setClassName('Json');
    }

    /**
     * GET /api/laudos/catalogo/pecas?q=&limit=
     */
    public function pecas()
    {
        $empresaId = (int)($this->Auth->user('idempresa') ?? 1);
        $q = $this->request->getQuery('q');
        $limit = (int)($this->request->getQuery('limit') ?? 50);

        $items = $this->LaudosCatalogoPecas->buscar($empresaId, $q, $limit);

        $this->set(['success' => true, 'data' => $items]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    /**
     * POST /api/laudos/catalogo/pecas
     */
    public function addPeca()
    {
        $this->request->allowMethod('POST');
        $data = $this->request->getData();
        $data['empresa_id'] = (int)($this->Auth->user('idempresa') ?? 1);

        $peca = $this->LaudosCatalogoPecas->newEntity($data);
        if (!$this->LaudosCatalogoPecas->save($peca)) {
            $this->set(['success' => false, 'errors' => $peca->getErrors()]);
            $this->response = $this->response->withStatus(422);
        } else {
            $this->set(['success' => true, 'data' => $peca]);
        }
        $this->viewBuilder()->setOption('serialize', ['success', 'data', 'errors']);
    }

    /**
     * GET /api/laudos/catalogo/servicos?q=
     */
    public function servicos()
    {
        $empresaId = (int)($this->Auth->user('idempresa') ?? 1);
        $items = $this->LaudosCatalogoServicos->buscar($empresaId, $this->request->getQuery('q'));

        $this->set(['success' => true, 'data' => $items]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    /**
     * GET /api/laudos/templates/:tipo
     * tipo = diagnostico | conclusao | objetivo | documentacao
     */
    public function templates($tipo)
    {
        $empresaId = (int)($this->Auth->user('idempresa') ?? 1);
        $items = $this->LaudosTemplates->porTipo($empresaId, $tipo);

        $this->set(['success' => true, 'data' => $items]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }
}
