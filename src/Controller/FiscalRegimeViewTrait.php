<?php
namespace App\Controller;

use App\Utility\Fiscal\FiscalRegimeHelper;
use Cake\Event\Event;

/**
 * Injeta variáveis do elemento Template/Element/Fiscal/regime_context nas respostas HTML.
 * Ignora quando autoRender = false (JSON, PDF, XML, exportação SPED/Excel, etc.).
 */
trait FiscalRegimeViewTrait {

    public function beforeRender(Event $event) {
        $this->injectFiscalRegimeContextForCurrentUser();
        parent::beforeRender($event);
    }

    protected function injectFiscalRegimeContextForCurrentUser() {
        if ($this->autoRender === false) {
            return;
        }
        $idempresa = $this->Auth->user('idempresa');
        if (!$idempresa || (int)($this->Auth->user('role') ?? 1) === 1) {
            return;
        }
        if (!isset($this->FiscalEmpresasConfig)) {
            return;
        }
        $cfg = $this->FiscalEmpresasConfig->getOrCreate($idempresa);
        $this->set(FiscalRegimeHelper::viewContextFromEmpresaConfig($cfg->toArray()));
    }
}
