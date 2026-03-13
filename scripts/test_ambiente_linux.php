<?php
/**
 * Teste do ambiente (Linux ou local) após migração.
 * Verifica paths, constantes, diretórios e conexão com PostgreSQL.
 *
 * Uso (na raiz do projeto):
 *   php scripts/test_ambiente_linux.php
 *
 * Ou no Linux:
 *   cd /var/www/portal && php scripts/test_ambiente_linux.php
 */
$scriptDir = __DIR__;
$root = dirname($scriptDir);
chdir($root);

$errors = [];
$ok = [];

// 1. Carregar .env antes de paths (bootstrap faz isso)
$envFile = $root . DIRECTORY_SEPARATOR . '.env';
if (is_file($envFile)) {
    $ok[] = '.env encontrado: ' . $envFile;
} else {
    $ok[] = '.env não encontrado (variáveis vêm de app_local.php ou do servidor)';
}

require $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'bootstrap.php';

// 2. Paths
$ok[] = 'ROOT = ' . ROOT;
$ok[] = 'WWW_ROOT = ' . WWW_ROOT;
$ok[] = 'APP_DIR = ' . APP_DIR;
$ok[] = 'APP = ' . APP;
$ok[] = 'CONFIG = ' . CONFIG;
$ok[] = 'LOGS = ' . LOGS;

$dirs = [
    'ROOT' => ROOT,
    'WWW_ROOT' => WWW_ROOT,
    'APP' => APP,
    'CONFIG' => CONFIG,
    'LOGS' => LOGS,
    'TMP' => TMP,
];
foreach ($dirs as $name => $path) {
    if (!is_dir($path)) {
        $errors[] = "Diretório não existe: $name = $path";
    } else {
        if (in_array($name, ['LOGS', 'TMP']) && !is_writable($path)) {
            $errors[] = "Diretório não gravável: $name = $path";
        }
    }
}

// 3. Conexão com o banco
try {
    $conn = \Cake\Datasource\ConnectionManager::get('default');
    $conn->connect();
    $ok[] = 'Conexão PostgreSQL: OK (host=' . $conn->config()['host'] . ', database=' . ($conn->config()['database'] ?? '') . ')';
    $stmt = $conn->execute('SELECT 1 AS n');
    $row = $stmt->fetch('assoc');
    if (!empty($row['n'])) {
        $ok[] = 'Query de teste (SELECT 1): OK';
    }
} catch (\Exception $e) {
    $errors[] = 'Banco de dados: ' . $e->getMessage();
}

// 4. Vendor/PGMPackages (case-sensitive no Linux)
$vendorPgm = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';
if (is_file($vendorPgm)) {
    $ok[] = 'vendor/PGMPackages/UserConstants.php: encontrado (case OK)';
} else {
    $errors[] = 'Arquivo não encontrado (verifique case): ' . $vendorPgm;
}

// Saída
echo "\n=== Teste do ambiente (portal) ===\n\n";
foreach ($ok as $line) {
    echo "  [OK] " . $line . "\n";
}
if (!empty($errors)) {
    echo "\n  [ERRO]\n";
    foreach ($errors as $line) {
        echo "  - " . $line . "\n";
    }
    echo "\nCorrija os erros acima e execute novamente.\n";
    exit(1);
}
echo "\nAmbiente OK. Pode testar no navegador ou com: php bin/cake.php\n";
exit(0);
