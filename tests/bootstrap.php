<?php
/**
 * Bootstrap mínimo para testes unitários sem app completa (ex.: RbacChecker).
 */
declare(strict_types=1);

$root = dirname(__DIR__);
if (!is_file($root . '/vendor/autoload.php')) {
	throw new RuntimeException('Execute composer install antes de phpunit.');
}

require $root . '/vendor/autoload.php';
