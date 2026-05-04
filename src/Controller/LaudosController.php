<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\AppController;

/**
 * Controller das views web do módulo Laudos / Parecer Técnico.
 * As actions apenas servem as páginas CTP; toda a lógica de dados
 * é executada via chamadas AJAX aos endpoints /api/laudos/*.
 */
class LaudosController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        // /validar/:hash é pública — libera do Auth aqui antes de qualquer beforeFilter
        $this->Auth->allow(['validar']);
    }

    /**
     * GET /laudos/pareceres
     * Lista de pareceres técnicos.
     */
    public function index()
    {
        $empresaId = (int)($this->Auth->user('idempresa') ?? 1);
        $this->set('empresaId', $empresaId);
        $this->set('laudosActive', 'active');
        $this->set('title_for_layout', 'Pareceres Técnicos');
        $this->set('topbarParentLabel', 'Laudos');
        $this->set('topbarCurrentLabel', 'Parecer Técnico');
    }

    /**
     * GET /laudos/pareceres/:id
     * Edição / visualização de um parecer técnico.
     */
    public function view($id = null)
    {
        if (!$id) {
            $this->Flash->error('Parecer não informado.');
            return $this->redirect(['action' => 'index']);
        }

        $this->set('parecerId', (int)$id);
        $this->set('laudosActive', 'active');
        $this->set('title_for_layout', 'Parecer Técnico');
        $this->set('topbarParentLabel', 'Laudos');
        $this->set('topbarCurrentLabel', 'Parecer Técnico');
    }

    /**
     * GET /validar/:hash (pública — sem login)
     * Página de validação pública via QR Code.
     */
    public function validar($hash = null)
    {
        $this->set('publicHash', $hash);
        $this->set('title_for_layout', 'Validação de Parecer Técnico');
        $this->layout = false; // página standalone com HTML próprio; sem sidebar/topbar
    }
}
