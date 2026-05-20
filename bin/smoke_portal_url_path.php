<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Utility\PortalUrlPath;

$ok = PortalUrlPath::sanitizeInternalRedirect('/portal/x') === '/portal/x';
$ok = $ok && PortalUrlPath::sanitizeInternalRedirect('//evil.com') === null;
$ok = $ok && PortalUrlPath::sanitizeInternalRedirect('https://evil.com') === null;

echo $ok ? "OK\n" : "FAIL\n";
exit($ok ? 0 : 1);
