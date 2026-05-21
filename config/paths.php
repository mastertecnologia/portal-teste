<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Use the DS to separate the directories in other defines
 */
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

/**
 * These defines should only be edited if you have cake installed in
 * a directory layout other than the way it is distributed.
 * When using custom settings be sure to use the DS and do not add a trailing DS.
 */

/**
 * The full path to the directory which holds "src", WITHOUT a trailing DS.
 */
define('ROOT', dirname(__DIR__));

/**
 * The actual directory name for the application directory. Normally
 * named 'src'. No Linux use 'src' ou 'app' conforme a pasta em /var/www/portal.
 * Definir APP_DIR via variável de ambiente se a pasta tiver outro nome.
 */
define('APP_DIR', getenv('APP_DIR') ?: 'src');

/**
 * Path to the application's directory.
 */
define('APP', ROOT . DS . APP_DIR . DS);

/**
 * Path to the config directory.
 */
define('CONFIG', ROOT . DS . 'config' . DS);

/**
 * File path to the webroot directory.
 * No Linux: use 'webroot' ou 'public' conforme a estrutura em /var/www/portal.
 * Definir WEBROOT_DIR no .env (ex.: WEBROOT_DIR=public) se a pasta for "public".
 *
 * Predefinição: se existir public/index.php (layout seguro Linux), usar "public";
 * caso contrário "webroot" — evita login em branco (preloader) quando dist/ só está em public/.
 */
$_pgmWebrootDir = getenv('WEBROOT_DIR');
if ($_pgmWebrootDir === false || $_pgmWebrootDir === '') {
	$_pgmWebrootDir = is_file(ROOT . DS . 'public' . DS . 'index.php') ? 'public' : 'webroot';
}
define('WWW_ROOT', ROOT . DS . $_pgmWebrootDir . DS);
unset($_pgmWebrootDir);

/**
 * Path to the tests directory.
 */
define('TESTS', ROOT . DS . 'tests' . DS);

/**
 * Path to the temporary files directory.
 *
 * WSL + projeto em /mnt/c: drvfs não suporta chmod em ficheiros de cache → avisos no topo
 * da página. Em dev, usar tmp em ext4 (/tmp). Sobrescrever com CAKE_TMP no .env se precisar.
 */
$_pgmTmp = ROOT . DS . 'tmp' . DS;
if (
	DIRECTORY_SEPARATOR === '/'
	&& strpos(ROOT, '/mnt/') === 0
	&& is_readable('/proc/version')
	&& stripos((string)@file_get_contents('/proc/version'), 'microsoft') !== false
) {
	$_pgmTmpOverride = getenv('CAKE_TMP');
	$_pgmTmp = ($_pgmTmpOverride !== false && $_pgmTmpOverride !== '')
		? rtrim($_pgmTmpOverride, '/\\') . DS
		: '/tmp/pgm-portal-' . substr(hash('sha256', ROOT), 0, 12) . DS;
	foreach (['cache', 'cache' . DS . 'persistent', 'cache' . DS . 'models', 'cache' . DS . 'views', 'sessions'] as $_pgmTmpSub) {
		$_pgmTmpDir = $_pgmTmp . $_pgmTmpSub;
		if (!is_dir($_pgmTmpDir)) {
			@mkdir($_pgmTmpDir, 0775, true);
		}
	}
}
define('TMP', $_pgmTmp);
unset($_pgmTmp, $_pgmTmpOverride, $_pgmTmpSub, $_pgmTmpDir);

/**
 * Path to the logs directory.
 */
define('LOGS', ROOT . DS . 'logs' . DS);

/**
 * Path to the cache files directory. It can be shared between hosts in a multi-server setup.
 */
define('CACHE', TMP . 'cache' . DS);

/**
 * The absolute path to the "cake" directory, WITHOUT a trailing DS.
 *
 * CakePHP should always be installed with composer, so look there.
 */
define('CAKE_CORE_INCLUDE_PATH', ROOT . DS . 'vendor' . DS . 'cakephp' . DS . 'cakephp');

/**
 * Path to the cake directory.
 */
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . 'src' . DS);
