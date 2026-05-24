<?php
declare(strict_types=1);

namespace App\Controller\Traits;

use App\Utility\PortalUi;
use Cake\Http\Response;

/**
 * Switchover legado → protótipo (mock pgm_erp_completo_2) e topbar do shell premium.
 */
trait PremiumUiTrait {

    /**
     * Redireciona para *-prototype quando o módulo está em PORTAL_PREMIUM_MODULES.
     * Respeita ?legacy_ui=1 na query (força UI legada).
     *
     * @param array<string,mixed> $params parâmetros extra do redirect Cake
     * @return \Cake\Http\Response|null
     */
    protected function maybeRedirectPremiumPrototype(
        string $module,
        string $prototypeController,
        string $prototypeAction,
        array $params = []
    ): ?Response {
        if ($this->request->is('ajax') || $this->request->is('json')) {
            return null;
        }
        if (PortalUi::isLegacyUiForced($this->request)) {
            return null;
        }
        $route = PortalUi::redirectToPrototypeIfEnabled(
            $module,
            $prototypeController,
            $prototypeAction,
            $params
        );
        if ($route === null) {
            return null;
        }

        return $this->redirect($route);
    }

    /**
     * Oculta título/breadcrumb AdminLTE duplicado e preenche a topbar premium.
     *
     * @param array<string,mixed> $extra variáveis adicionais para $this->set()
     */
    protected function configurePgmAppShellTopbar(
        string $parentLabel,
        string $currentLabel,
        array $extra = []
    ): void {
        $this->set(array_merge([
            'hideLayoutPageTitle' => true,
            'topbarParentLabel' => $parentLabel,
            'topbarCurrentLabel' => $currentLabel,
        ], $extra));
    }
}
