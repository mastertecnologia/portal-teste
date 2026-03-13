<?php
/**
 * Diagnóstico: verifica se a tabela atendimento_timer existe no banco
 * que a config do portal usa. Execute no servidor de produção na pasta do portal:
 *   php scripts/diagnostico_tabela_timer.php
 */
$scriptDir = __DIR__;
$root = dirname($scriptDir);
chdir($root);

require $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'paths.php';
if (!function_exists('env')) {
    function env($key, $default = null) {
        $v = getenv($key);
        return $v !== false ? $v : (isset($_ENV[$key]) ? $_ENV[$key] : $default);
    }
}

$app = require CONFIG . 'app.php';
$local = file_exists(CONFIG . 'app_local.php') ? (require CONFIG . 'app_local.php') : [];
$default = $app['Datasources']['default'] ?? [];
if (!empty($local['Datasources']['default'])) {
    $default = array_merge($default, $local['Datasources']['default']);
}

$host = $default['host'] ?? 'localhost';
$port = $default['port'] ?? '5432';
$dbname = $default['database'] ?? 'pgm';
$user = $default['username'] ?? 'postgres';
$pass = $default['password'] ?? '';

echo "=== Diagnostico tabela atendimento_timer ===\n\n";
echo "Config (mesmo que o portal web usa):\n";
echo "  Host: {$host}\n";
echo "  Porta: {$port}\n";
echo "  Banco: {$dbname}\n";
echo "  Usuario: {$user}\n\n";

try {
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $stmt = $pdo->query("
        SELECT table_schema, table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
        AND table_name = 'atendimento_timer'
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo "RESULTADO: A tabela 'atendimento_timer' EXISTE no banco.\n\n";
        $cols = $pdo->query("
            SELECT column_name, data_type
            FROM information_schema.columns
            WHERE table_schema = 'public' AND table_name = 'atendimento_timer'
            ORDER BY ordinal_position
        ")->fetchAll(PDO::FETCH_ASSOC);
        echo "Colunas:\n";
        foreach ($cols as $c) {
            echo "  - {$c['column_name']} ({$c['data_type']})\n";
        }
        echo "\nSe o portal ainda mostra 'tabela nao existe':\n";
        echo "  1. Limpe o cache: apague o conteudo de tmp/cache ou execute limpar_cache.bat\n";
        echo "  2. Confirme que o servidor web usa o mesmo config (mesmo app_local.php)\n";
        echo "  3. Confirme que src/Model/Table/AtendimentoTimerTable.php esta no servidor\n";
    } else {
        echo "RESULTADO: A tabela 'atendimento_timer' NAO EXISTE neste banco.\n\n";
        echo "Execute: php scripts/verificar_criar_atendimento_timer.php\n";
        echo "Ou rode o SQL em config/sql/create_atendimento_timer.sql neste banco.\n";
    }
} catch (PDOException $e) {
    echo "ERRO ao conectar: " . $e->getMessage() . "\n";
}
echo "\n=== Fim do diagnostico ===\n";
