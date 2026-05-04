<?php
declare(strict_types=1);

namespace App\Controller\Api\Laudos;

use App\Controller\AppController;
use Cake\Http\Exception\NotFoundException;

/**
 * Controller PÚBLICO (sem autenticação).
 * Permite que qualquer pessoa valide um parecer via QR Code.
 */
class ValidacaoController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('LaudosPareceres');
        // Libera a action pública do Auth
        $this->Auth->allow(['publica']);
    }

    /**
     * GET /validar/:hash
     * Retorna apenas dados públicos do parecer.
     */
    public function publica($hash)
    {
        $parecer = $this->LaudosPareceres->find()
            ->where(['public_hash' => $hash, 'deleted IS' => null])
            ->contain(['LaudosEmpresas'])
            ->first();

        if (!$parecer) {
            throw new NotFoundException('Parecer não localizado');
        }

        $publicData = [
            'numero' => $parecer->numero,
            'data_emissao' => $parecer->data_emissao,
            'status' => $parecer->status,
            'status_label' => $parecer->status_label,
            'emitido_por' => $parecer->laudos_empresa->razao_social ?? '',
            'cnpj_emitente' => $parecer->laudos_empresa->cnpj ?? '',
            'cliente_nome' => $parecer->requester_company_name,
            'cliente_cnpj' => $parecer->requester_cnpj,
            'tecnico' => $parecer->tecnico_nome,
            'cidade' => $parecer->cidade,
            'autenticado' => true,
        ];

        $this->viewBuilder()->setClassName('Json');
        $this->set(['success' => true, 'data' => $publicData]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }
}
