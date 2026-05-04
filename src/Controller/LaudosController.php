<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\AppController;

class LaudosController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->Auth->allow(['validar', 'clientesBuscar']);
        // clientesBuscar usa Auth via verificação de role, não precisa ser público
        // mas precisa estar no allow para funcionar sem CSRF (é GET AJAX)
    }

    /**
     * GET /laudos/pareceres
     * Renderiza o React: ParecerListPage (screen=laudos_list)
     */
    public function index()
    {
        $this->set('laudosActive', 'active');
        $this->set('title_for_layout', 'Pareceres Técnicos');
        $this->set('topbarParentLabel', 'Laudos');
        $this->set('topbarCurrentLabel', 'Pareceres Técnicos');
        $this->set('reactBoot', [
            'screen' => 'laudos_list',
        ]);
        $this->viewBuilder()->setTemplate('react_app');
    }

    /**
     * GET /laudos/pareceres/:id
     * Renderiza o React: ParecerEditPage (screen=laudos_edit)
     */
    public function view($id = null)
    {
        if (!$id) {
            $this->Flash->error('Parecer não informado.');
            return $this->redirect(['action' => 'index']);
        }

        $this->set('laudosActive', 'active');
        $this->set('title_for_layout', 'Parecer Técnico');
        $this->set('topbarParentLabel', 'Laudos');
        $this->set('topbarCurrentLabel', 'Parecer Técnico');
        $this->set('reactBoot', [
            'screen' => 'laudos_edit',
            'parecerId' => (int)$id,
        ]);
        $this->viewBuilder()->setTemplate('react_app');
    }

    /**
     * GET /validar/:hash (pública — sem login)
     * Página standalone de validação via QR Code.
     */
    public function validar($hash = null)
    {
        $this->set('publicHash', $hash);
        $this->set('title_for_layout', 'Validação de Parecer Técnico');
        $this->layout = false;
    }

    /**
     * GET /laudos/clientes-buscar?q=...
     * Endpoint JSON para autocomplete de clientes no React.
     * Retorna apenas os campos necessários para o ParecerForm.
     */
    public function clientesBuscar()
    {
        // Bloqueia usuário portal (role=1)
        if ($this->Auth->user('role') == 1) {
            $this->response = $this->response->withStatus(403);
            $this->set(['success' => false, 'data' => []]);
            $this->viewBuilder()->setClassName('Json');
            $this->viewBuilder()->setOption('serialize', ['success', 'data']);
            return;
        }

        $this->loadModel('Clientes');

        $q = (string)($this->request->getQuery('q') ?? '');
        $idempresa = (int)($this->Auth->user('idempresa') ?? 0);

        $data = [];
        if (strlen(trim($q)) >= 2) {
            $like = '%' . str_replace(' ', '%', trim($q)) . '%';

            $query = $this->Clientes->find()
                ->select(['id', 'razaosocial', 'nomefantasia', 'cnpj', 'telefone', 'email', 'cep', 'endereco'])
                ->where([
                    'idempresa' => $idempresa,
                    'inativo' => 0,
                    'OR' => [
                        'razaosocial ILIKE' => $like,
                        'cnpj LIKE' => $like,
                        'nomefantasia ILIKE' => $like,
                    ],
                ])
                ->order(['razaosocial' => 'ASC'])
                ->limit(20);

            $data = $query->all()->toArray();
        }

        $this->set(['success' => true, 'data' => $data]);
        $this->viewBuilder()->setClassName('Json');
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }
}
