<?php
declare(strict_types=1);

namespace App\View\Sidebar;

use App\View\AppView;
use Cake\Http\ServerRequest;
use Cake\Http\Response;

/**
 * URLs do sino PortalNotifications para a sidebar React (sem jQuery no bundle).
 */
final class PgmPortalSidebarNotifUrls
{
    /**
     * @param array<string, mixed> $sidebarMenuGates
     * @return array<string, string>|null
     */
    public static function build(
        ServerRequest $request,
        Response $response,
        array $sidebarMenuGates,
        int $roleNav
    ): ?array {
        if ($roleNav !== 0 || empty($sidebarMenuGates['sidebar_notifications_bell'])) {
            return null;
        }
        $view = new AppView($request, $response);
        $h = $view->PgmPortalNotif;
        $urlMarkReadBase = rtrim($h->url(['controller' => 'PortalNotifications', 'action' => 'markRead']), '/');

        return [
            'urlCount' => $h->url(['controller' => 'PortalNotifications', 'action' => 'unreadCount']),
            'urlList' => $h->url(['controller' => 'PortalNotifications', 'action' => 'listJson']),
            'urlMarkAll' => $h->url(['controller' => 'PortalNotifications', 'action' => 'markAllRead']),
            'urlMarkReadBase' => $urlMarkReadBase,
            'urlPrefs' => $h->url(['controller' => 'PortalNotifications', 'action' => 'preferences']),
        ];
    }
}
