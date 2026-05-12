<?php
declare(strict_types=1);

namespace App\Controller\Api\Laudos;

use App\Controller\AppController;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\I18n\FrozenDate;

/**
 * LaudosPareceres Controller — endpoints REST do recurso "parecer técnico".
 * Rotas montadas sob /api/laudos/pareceres
 * Auth: usa o componente Auth padrão do sistema (CakePHP 3).
 */
class LaudosPareceresController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('LaudosPareceres');
        $this->loadModel('LaudosHistorico');
        $this->loadModel('LaudosProdutos');

        $this->RequestHandler->renderAs($this, 'json');
        $this->viewBuilder()->setClassName('Json');
    }

    /**
     * GET /api/laudos/pareceres
     * Lista paginada com filtros: ?status=&q=&page=&limit=
     */
    public function index()
    {
        $this->request->allowMethod('GET');

        $options = [
            'status' => $this->request->getQuery('status'),
            'q' => $this->request->getQuery('q'),
            'empresa_id' => $this->getEmpresaId(),
        ];

        $query = $this->LaudosPareceres->find()->find('filtered', $options);

        $this->paginate = [
            'limit' => (int)($this->request->getQuery('limit') ?? 20),
            'maxLimit' => 100,
        ];

        $pareceres = $this->paginate($query);

        $result = [];
        foreach ($pareceres as $p) {
            $totais = $this->LaudosPareceres->calcularTotais($p->id);
            $result[] = [
                'id' => $p->id,
                'numero' => $p->numero,
                'titulo' => $p->titulo,
                'status' => $p->status,
                'status_label' => $p->status_label,
                'tecnico_nome' => $p->tecnico_nome ?: (($p->tecnico !== null && $p->tecnico !== false) ? $p->tecnico->name : null),
                'requester_company_name' => $p->requester_company_name,
                'requester_cnpj' => $p->requester_cnpj,
                'data_emissao' => $p->data_emissao,
                'created' => $p->created,
                'modified' => $p->modified,
                'total_geral' => $totais['total_geral'],
                'public_hash' => $p->public_hash,
            ];
        }

        $this->set([
            'success' => true,
            'data' => $result,
            'pagination' => $this->request->getParam('paging')['LaudosPareceres'] ?? [],
        ]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data', 'pagination']);
    }

    /**
     * GET /api/laudos/pareceres/:id
     */
    public function view($id)
    {
        $this->request->allowMethod('GET');

        $parecer = $this->LaudosPareceres->getCompleto((int)$id);
        if (!$parecer) {
            throw new NotFoundException('Parecer não encontrado');
        }

        $this->checkEmpresaAccess($parecer);

        $totais = $this->LaudosPareceres->calcularTotais($parecer->id);

        $this->set([
            'success' => true,
            'data' => $parecer,
            'totais' => $totais,
        ]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data', 'totais']);
    }

    /**
     * POST /api/laudos/pareceres
     */
    public function add()
    {
        $this->request->allowMethod('POST');

        $empresaId = $this->getEmpresaId();
        $userId = $this->getCurrentUserId();
        $userName = $this->getCurrentUserName();

        $data = $this->request->getData();

        $data['empresa_id'] = $empresaId;
        $data['numero'] = $this->LaudosPareceres->gerarProximoNumero($empresaId);
        $data['public_hash'] = $this->LaudosPareceres->gerarHashPublico();
        $data['status'] = 'rascunho';
        $data['titulo'] = $data['titulo'] ?? 'Parecer Técnico ' . $data['numero'];
        $data['tecnico_user_id'] = $data['tecnico_user_id'] ?? $userId;
        $data['tecnico_nome'] = $data['tecnico_nome'] ?? $userName;
        $data['data_emissao'] = $data['data_emissao'] ?? FrozenDate::now()->format('Y-m-d');
        $data['created_by'] = $userId;
        $data['modified_by'] = $userId;

        $parecer = $this->LaudosPareceres->newEntity($data);

        if (!$this->LaudosPareceres->save($parecer)) {
            $this->set(['success' => false, 'errors' => $parecer->getErrors()]);
            $this->viewBuilder()->setOption('serialize', ['success', 'errors']);
            $this->response = $this->response->withStatus(422);
            return;
        }

        $this->LaudosHistorico->logEvent($parecer->id, $userId, $userName, 'parecer.created');

        $this->set([
            'success' => true,
            'data' => $this->LaudosPareceres->getCompleto($parecer->id),
        ]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    /**
     * PUT/PATCH /api/laudos/pareceres/:id
     */
    public function edit($id)
    {
        $this->request->allowMethod(['PUT', 'PATCH']);

        $parecer = $this->LaudosPareceres->get((int)$id, ['contain' => []]);
        $this->checkEmpresaAccess($parecer);

        if (!$parecer->pode_editar) {
            throw new ForbiddenException('Parecer não pode ser editado neste status');
        }

        $data = $this->request->getData();

        unset(
            $data['id'],
            $data['public_hash'],
            $data['numero'],
            $data['empresa_id'],
            $data['created'],
            $data['created_by'],
            $data['deleted'],
            $data['deleted_by'],
            $data['status'],
            $data['tecnico_user_id'],
            $data['modified']
        );

        $data['modified_by'] = $this->getCurrentUserId();

        $parecer = $this->LaudosPareceres->patchEntity($parecer, $data);

        if (!$this->LaudosPareceres->save($parecer)) {
            $this->set(['success' => false, 'errors' => $parecer->getErrors()]);
            $this->viewBuilder()->setOption('serialize', ['success', 'errors']);
            $this->response = $this->response->withStatus(422);
            return;
        }

        $this->set([
            'success' => true,
            'data' => $this->LaudosPareceres->getCompleto($parecer->id),
        ]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    /**
     * DELETE /api/laudos/pareceres/:id
     */
    public function delete($id)
    {
        $this->request->allowMethod('DELETE');

        $parecer = $this->LaudosPareceres->get((int)$id);
        $this->checkEmpresaAccess($parecer);

        $userId = $this->getCurrentUserId();
        $userName = $this->getCurrentUserName();

        $this->LaudosPareceres->softDelete($parecer->id, $userId);
        $this->LaudosHistorico->logEvent($parecer->id, $userId, $userName, 'parecer.deleted');

        $this->set(['success' => true]);
        $this->viewBuilder()->setOption('serialize', ['success']);
    }

    /**
     * POST /api/laudos/pareceres/:id/duplicar
     */
    public function duplicar($id)
    {
        $this->request->allowMethod('POST');

        $original = $this->LaudosPareceres->getCompleto((int)$id);
        if (!$original) {
            throw new NotFoundException();
        }
        $this->checkEmpresaAccess($original);

        $empresaId = $this->getEmpresaId();
        $userId = $this->getCurrentUserId();
        $userName = $this->getCurrentUserName();

        $data = $original->toArray();
        unset($data['id'], $data['created'], $data['modified'], $data['deleted'], $data['deleted_by']);
        $data['numero'] = $this->LaudosPareceres->gerarProximoNumero($empresaId);
        $data['public_hash'] = $this->LaudosPareceres->gerarHashPublico();
        $data['titulo'] = $original->titulo . ' (cópia)';
        $data['status'] = 'rascunho';
        $data['assinatura_path'] = null;
        $data['created_by'] = $userId;
        $data['modified_by'] = $userId;

        if (!empty($data['laudos_produtos'])) {
            foreach ($data['laudos_produtos'] as &$prod) {
                unset($prod['id'], $prod['created'], $prod['modified']);
                if (!empty($prod['laudos_produto_imagens'])) {
                    foreach ($prod['laudos_produto_imagens'] as &$img) {
                        unset($img['id'], $img['created']);
                    }
                }
                if (!empty($prod['laudos_produto_pecas'])) {
                    foreach ($prod['laudos_produto_pecas'] as &$pe) {
                        unset($pe['id'], $pe['created']);
                    }
                }
                if (!empty($prod['laudos_produto_servicos'])) {
                    foreach ($prod['laudos_produto_servicos'] as &$sv) {
                        unset($sv['id'], $sv['created']);
                    }
                }
            }
        }

        $copy = $this->LaudosPareceres->newEntity($data, ['associated' => [
            'LaudosProdutos.LaudosProdutoImagens',
            'LaudosProdutos.LaudosProdutoPecas',
            'LaudosProdutos.LaudosProdutoServicos',
        ]]);

        if (!$this->LaudosPareceres->save($copy)) {
            throw new BadRequestException(json_encode($copy->getErrors()));
        }

        $this->LaudosHistorico->logEvent($copy->id, $userId, $userName, 'parecer.duplicated', [
            'origem_id' => $original->id,
            'origem_numero' => $original->numero,
        ]);

        $this->set([
            'success' => true,
            'data' => $this->LaudosPareceres->getCompleto($copy->id),
        ]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    /**
     * POST /api/laudos/pareceres/:id/status
     * Body: {"status": "aprovado"}
     */
    public function changeStatus($id)
    {
        $this->request->allowMethod('POST');

        $parecer = $this->LaudosPareceres->get((int)$id);
        $this->checkEmpresaAccess($parecer);

        $newStatus = $this->request->getData('status');
        if (!array_key_exists($newStatus, \App\Model\Table\LaudosPareceresTable::STATUSES)) {
            throw new BadRequestException('Status inválido');
        }

        $oldStatus = $parecer->status;
        $parecer->status = $newStatus;
        $parecer->modified_by = $this->getCurrentUserId();

        if (!$this->LaudosPareceres->save($parecer)) {
            $this->set(['success' => false, 'errors' => $parecer->getErrors()]);
            $this->viewBuilder()->setOption('serialize', ['success', 'errors']);
            $this->response = $this->response->withStatus(422);
            return;
        }

        $this->LaudosHistorico->logEvent(
            $parecer->id,
            $this->getCurrentUserId(),
            $this->getCurrentUserName(),
            'parecer.status_changed',
            ['from' => $oldStatus, 'to' => $newStatus]
        );

        $this->set(['success' => true, 'data' => $parecer]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    /**
     * GET /api/laudos/pareceres/:id/historico
     */
    public function historico($id)
    {
        $this->request->allowMethod('GET');

        $parecer = $this->LaudosPareceres->get((int)$id);
        $this->checkEmpresaAccess($parecer);

        $items = $this->LaudosHistorico->find()
            ->where(['parecer_id' => $parecer->id])
            ->contain(['Users'])
            ->order(['created' => 'DESC'])
            ->limit(100)
            ->all()
            ->toArray();

        $this->set(['success' => true, 'data' => $items]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    // =====================================================================
    // HELPERS — auth usa o componente CakePHP 3 Auth
    // =====================================================================

    protected function getEmpresaId(): int
    {
        return (int)($this->Auth->user('idempresa') ?? 1);
    }

    protected function getCurrentUserId(): ?int
    {
        $id = $this->Auth->user('id');
        return $id !== null ? (int)$id : null;
    }

    protected function getCurrentUserName(): ?string
    {
        return $this->Auth->user('name');
    }

    protected function checkEmpresaAccess($parecer): void
    {
        if ((int)$parecer->empresa_id !== $this->getEmpresaId()) {
            throw new ForbiddenException('Acesso negado a este parecer');
        }
    }
}
