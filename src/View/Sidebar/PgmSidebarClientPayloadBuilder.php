<?php
declare(strict_types=1);

namespace App\View\Sidebar;

use Cake\Http\ServerRequest;
use Cake\Routing\Router;

/**
 * JSON para window.__PGM_SIDEBAR_PROPS__ — portal cliente (sidebarcli.ctp).
 */
final class PgmSidebarClientPayloadBuilder
{
    /**
     * @param array<string, mixed> $v viewVars
     * @return array<string, mixed>
     */
    public static function build(array $v, ServerRequest $request): array
    {
        $activePath = self::requestActivePath($request);
        $controllerCli = strtolower((string) $request->getParam('controller'));
        $actionCli = strtolower((string) $request->getParam('action'));

        $permissao = !empty($v['permissaoacesso']);
        $canSolicitar = !empty($v['canClienteSolicitarOrcamento']);
        $idcliente = (int) ($v['idcliente'] ?? 0);

        $relatoriosCliActive = ($controllerCli === 'portalrelatorios' && $actionCli === 'index');
        $advCtrls = ['portalcontratos', 'portaladvancedcontracts', 'portaladvancedinvoices'];
        $portalAdvOpen = in_array($controllerCli, $advCtrls, true);
        $advContrActive = in_array($controllerCli, ['portalcontratos', 'portaladvancedcontracts'], true) && $actionCli !== 'franquia';
        $advFranqActive = in_array($controllerCli, ['portalcontratos', 'portaladvancedcontracts'], true) && $actionCli === 'franquia';
        $advInvActive = ($controllerCli === 'portaladvancedinvoices');

        $ticketsAction = strtolower((string) $request->getParam('action'));
        $ticketsSubIndex = ($ticketsAction === 'indexcliente');
        $ticketsSubAdd = ($ticketsAction === 'add');
        $ticketsSubHist = ($controllerCli === 'portaladvancedattendance');

        $empresasOpt = $v['empresasOptSidebar'] ?? [];
        if (!is_array($empresasOpt)) {
            $empresasOpt = [];
        }
        $companies = self::buildWorkspaceCompanies($empresasOpt);
        $currentEmpresaId = (string) ($v['empresa'] ?? '');
        $current = self::findCompanyById($companies, $currentEmpresaId) ?? ($companies[0] ?? ['id' => '', 'name' => 'PGM', 'initials' => 'PG']);

        $nameTrim = trim((string) ($v['name'] ?? ''));
        $partsName = $nameTrim !== '' ? preg_split('/\s+/', $nameTrim, -1, PREG_SPLIT_NO_EMPTY) : [];
        $u0 = $partsName[0] ?? '';
        $u1 = $partsName[1] ?? '';
        $userInitials = '';
        if ($u0 !== '') {
            $userInitials .= strtoupper($u0[0]);
        }
        if ($u1 !== '') {
            $userInitials .= strtoupper($u1[0]);
        } elseif (strlen($u0) > 1) {
            $userInitials = strtoupper(substr($u0, 0, 2));
        }

        $navBlocks = [];

        if ($permissao) {
            $navBlocks[] = [
                'type' => 'link',
                'href' => self::u('/users/dashboard'),
                'label' => 'Dashboard',
                'iconFa' => 'fa-columns',
                'active' => !empty($v['dashboard']),
            ];
            if ($idcliente > 0) {
                $navBlocks[] = [
                    'type' => 'link',
                    'href' => self::u('/clientes/edit/' . $idcliente),
                    'label' => 'Empresa',
                    'iconFa' => 'fa-building',
                    'active' => !empty($v['clientesActive']),
                ];
            }

            $orcItems = [
                ['href' => self::u('/orcamentos/index'), 'label' => 'Meus Orçamentos', 'active' => false],
            ];
            if ($canSolicitar) {
                $orcItems[] = ['href' => self::u('/orcamentos/solicitar'), 'label' => 'Solicitar Orçamento', 'active' => false];
            }
            $orcGroupActive = !empty($v['orcamentosActive']);
            $navBlocks[] = [
                'type' => 'group',
                'id' => 'orcamentos',
                'label' => 'Orçamentos',
                'iconFa' => 'fa-file-invoice-dollar',
                'defaultOpen' => $orcGroupActive,
                'active' => $orcGroupActive,
                'items' => $orcItems,
            ];

            $navBlocks[] = [
                'type' => 'link',
                'href' => self::u('/cliente/relatorios'),
                'label' => 'Relatórios',
                'iconFa' => 'fa-chart-bar',
                'active' => $relatoriosCliActive,
            ];

            $navBlocks[] = [
                'type' => 'group',
                'id' => 'contratos-faturas',
                'label' => 'Contratos & faturas',
                'iconFa' => 'fa-layer-group',
                'defaultOpen' => $portalAdvOpen,
                'active' => $portalAdvOpen,
                'items' => [
                    ['href' => self::u('/cliente/contratos'), 'label' => 'Contratos', 'active' => $advContrActive],
                    ['href' => self::u('/cliente/contratos/franquia'), 'label' => 'Franquia de horas', 'active' => $advFranqActive],
                    ['href' => self::u('/cliente/faturas-avancadas'), 'label' => 'Faturas', 'active' => $advInvActive],
                ],
            ];
        }

        $ticketsGroupActive = !empty($v['ticketsActive']);
        $navBlocks[] = [
            'type' => 'group',
            'id' => 'tickets',
            'label' => 'Tickets',
            'iconFa' => 'fa-ticket-alt',
            'defaultOpen' => $ticketsGroupActive,
            'active' => $ticketsGroupActive,
            'items' => [
                ['href' => self::u('/servicedesk'), 'label' => 'Meus tickets', 'active' => $ticketsSubIndex],
                ['href' => self::u('/servicedesk/add'), 'label' => 'Abrir chamado', 'active' => $ticketsSubAdd],
                ['href' => self::u('/cliente/historico-atendimento-avancado'), 'label' => 'Histórico de atendimento', 'active' => $ticketsSubHist],
            ],
        ];

        return [
            'variant' => 'client',
            'activePath' => $activePath,
            'navBlocks' => $navBlocks,
            'permissaoacesso' => $permissao,
            'workspace' => [
                'sub' => 'ERP Enterprise',
                'currentId' => $currentEmpresaId,
                'currentName' => $current['name'],
                'currentInitials' => $current['initials'],
                'companies' => $companies,
                'empresaSelectOptions' => $empresasOpt,
                'empresaSelectValue' => $v['empresa'] ?? '',
                'multiEmpresa' => count($empresasOpt) > 1,
            ],
            'user' => [
                'name' => (string) ($v['name'] ?? ''),
                'initials' => (string) ($userInitials !== '' ? $userInitials : '?'),
                'roleLabel' => 'Cliente',
            ],
            'footerLinks' => [
                ['label' => 'Meu Perfil', 'href' => self::u(['controller' => 'Users', 'action' => 'change_profile'])],
                ['label' => 'Alterar Senha', 'href' => self::u(['controller' => 'Users', 'action' => 'change_password'])],
                ['label' => 'Verificação em 2 etapas', 'href' => self::u(['controller' => 'users', 'action' => 'loginduasetapas'])],
                ['label' => 'Sair', 'href' => self::u(['controller' => 'Users', 'action' => 'logout']), 'danger' => true],
            ],
            'notificationsBell' => false,
            'dashboardItem' => null,
            'sections' => [],
        ];
    }

    private static function requestActivePath(ServerRequest $request): string
    {
        if (method_exists($request, 'getUri') && $request->getUri()) {
            $uri = $request->getUri();
            $path = $uri->getPath();
            $q = $uri->getQuery();
            if ($q !== '') {
                $path .= '?' . $q;
            }

            return $path;
        }
        $t = $request->getRequestTarget();
        if (is_string($t) && $t !== '') {
            return $t;
        }

        return '/';
    }

    /**
     * @param array|string $url
     */
    private static function u($url): string
    {
        return Router::url($url, false);
    }

    /**
     * @param array<string|int, string> $empresasOpt
     * @return list<array{id: string, name: string, initials: string}>
     */
    private static function buildWorkspaceCompanies(array $empresasOpt): array
    {
        $out = [];
        foreach ($empresasOpt as $empresaId => $empresaNome) {
            $nomeAtual = (string) $empresaNome;
            $nomeAtualDisplay = function_exists('mb_strtoupper') ? mb_strtoupper($nomeAtual, 'UTF-8') : strtoupper($nomeAtual);
            $parts = preg_split('/\s+/', trim($nomeAtual), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $ini = '';
            if (!empty($parts[0])) {
                $ini .= strtoupper(substr($parts[0], 0, 1));
            }
            if (!empty($parts[1])) {
                $ini .= strtoupper(substr($parts[1], 0, 1));
            }
            if ($ini === '' && $nomeAtual !== '') {
                $ini = strtoupper(substr($nomeAtual, 0, 2));
            }
            $out[] = [
                'id' => (string) $empresaId,
                'name' => $nomeAtualDisplay,
                'initials' => $ini !== '' ? $ini : 'PG',
            ];
        }

        return $out;
    }

    /**
     * @param list<array{id: string, name: string, initials: string}> $companies
     * @return array{id: string, name: string, initials: string}|null
     */
    private static function findCompanyById(array $companies, string $id): ?array
    {
        foreach ($companies as $c) {
            if ($c['id'] === $id) {
                return $c;
            }
        }

        return null;
    }
}
