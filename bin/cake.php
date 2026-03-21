#!/usr/bin/php -q
<?php
/**
 * Command-line code generation utility to automate programmer chores.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of this file must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

$root = dirname(__DIR__);
$minVersion = '5.6.0';
if (file_exists($root . DIRECTORY_SEPARATOR . 'composer.json')) {
    $composer = json_decode(file_get_contents($root . DIRECTORY_SEPARATOR . 'composer.json'));
    if (isset($composer->require->php)) {
        $minVersion = preg_replace('/([^0-9\.])/', '', $composer->require->php);
    }
}
if (version_compare(phpversion(), $minVersion, '<')) {
    fwrite(STDERR, sprintf("Minimum PHP version: %s. You are using: %s.\n", $minVersion, phpversion()));
    exit(-1);
}

require $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
include $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'bootstrap.php';

exit(Cake\Console\ShellDispatcher::run($argv));
