<?php
namespace App\Controller;

use Cake\Event\Event;

/**
 * Notas fiscais de entrada (compra) — mesmo modelo de dados, tipo_operacao = 0.
 */
class FiscalNotasEntradaController extends FiscalNotasController {

    protected $tipoOperacaoPadrao = 0;

    protected $tipoOperacaoFixo = 0;

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $this->set('title', 'Notas fiscais de entrada');
    }

    public function beforeRender(Event $event) {
        $this->viewBuilder()->setTemplatePath('FiscalNotas');
        parent::beforeRender($event);
    }
}
