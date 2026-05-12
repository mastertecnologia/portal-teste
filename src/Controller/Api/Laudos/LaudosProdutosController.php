<?php
declare(strict_types=1);

namespace App\Controller\Api\Laudos;

use App\Controller\AppController;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\ForbiddenException;

class LaudosProdutosController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('LaudosProdutos');
        $this->loadModel('LaudosPareceres');
        $this->loadModel('LaudosHistorico');
        $this->viewBuilder()->setClassName('Json');
    }

    /**
     * POST /api/laudos/produtos
     * Body: { parecer_id, ...campos }
     */
    public function add()
    {
        $this->request->allowMethod('POST');
        $data = $this->request->getData();

        $parecer = $this->LaudosPareceres->get($data['parecer_id']);
        $this->checkAccess($parecer);

        $maxOrdem = $this->LaudosProdutos->find()
            ->where(['parecer_id' => $data['parecer_id']])
            ->select(['max_ordem' => 'MAX(ordem)'])
            ->first();
        $data['ordem'] = ($maxOrdem->max_ordem ?? 0) + 1;

        $produto = $this->LaudosProdutos->newEntity($data);

        if (!$this->LaudosProdutos->save($produto)) {
            $this->set(['success' => false, 'errors' => $produto->getErrors()]);
            $this->viewBuilder()->setOption('serialize', ['success', 'errors']);
            $this->response = $this->response->withStatus(422);
            return;
        }

        $this->LaudosHistorico->logEvent(
            $parecer->id,
            $this->getUserId(),
            $this->getUserName(),
            'produto.added',
            ['produto_id' => $produto->id, 'nome' => $produto->nome]
        );

        $this->set(['success' => true, 'data' => $produto]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    /**
     * PUT/PATCH /api/laudos/produtos/:id
     * Suporta dados aninhados: laudos_produto_pecas e laudos_produto_servicos
     */
    public function edit($id)
    {
        $this->request->allowMethod(['PUT', 'PATCH']);

        $produto = $this->LaudosProdutos->get((int)$id, ['contain' => ['LaudosPareceres']]);
        $this->checkAccess($produto->laudos_parecer);

        $produto = $this->LaudosProdutos->patchEntity($produto, $this->request->getData(), [
            'associated' => ['LaudosProdutoPecas', 'LaudosProdutoServicos'],
        ]);

        if (!$this->LaudosProdutos->save($produto)) {
            $this->set(['success' => false, 'errors' => $produto->getErrors()]);
            $this->viewBuilder()->setOption('serialize', ['success', 'errors']);
            $this->response = $this->response->withStatus(422);
            return;
        }

        $this->set(['success' => true, 'data' => $produto]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    /**
     * DELETE /api/laudos/produtos/:id
     */
    public function delete($id)
    {
        $this->request->allowMethod('DELETE');

        $produto = $this->LaudosProdutos->get((int)$id, ['contain' => ['LaudosPareceres']]);
        $this->checkAccess($produto->laudos_parecer);

        $this->LaudosProdutos->delete($produto);

        $this->LaudosHistorico->logEvent(
            $produto->parecer_id,
            $this->getUserId(),
            $this->getUserName(),
            'produto.removed',
            ['nome' => $produto->nome]
        );

        $this->set(['success' => true]);
        $this->viewBuilder()->setOption('serialize', ['success']);
    }

    protected function checkAccess($parecer): void
    {
        $empresaId = (int)($this->Auth->user('idempresa') ?? 1);
        if ((int)$parecer->empresa_id !== $empresaId) {
            throw new ForbiddenException('Sem permissão para este parecer');
        }
    }

    protected function getUserId(): ?int
    {
        $id = $this->Auth->user('id');
        return $id !== null ? (int)$id : null;
    }

    protected function getUserName(): ?string
    {
        return $this->Auth->user('name');
    }
}
