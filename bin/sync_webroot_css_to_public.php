#!/usr/bin/env php
<?php
/**
 * Copia webroot/css/*.css → public/css/ (obrigatório quando WEBROOT_DIR=public).
 *
 * Uso: php bin/sync_webroot_css_to_public.php
 * Deploy só com git pull: executar este script após cada atualização.
 */

$rootDir = dirname(__DIR__);

require $rootDir . '/vendor/autoload.php';

use App\Console\Installer;

$count = Installer::syncWebrootCssToPublic($rootDir);
echo 'sync_webroot_css_to_public: ' . $count . " ficheiro(s) copiados para public/css/\n";
