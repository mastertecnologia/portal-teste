<?php
/**
 * Verifica se a tabela atendimento_timer existe no PostgreSQL e, se não existir, cria.
 * Usa os dados de conexão de config/app.php e config/app_local.php (se existir).
 *
 * Uso no servidor (na pasta do portal):
 *   php scripts/verificar_criar_atendimento_timer.php
 * Ou pelo .bat (Windows):
 *   verificar_atendimento_timer.bat
 */
$scriptDir = __DIR__;
$root = dirname($scriptDir);
chdir($root);

// Carregar paths do CakePHP (define ROOT, CONFIG, APP, WWW_ROOT, etc.)
require $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'paths.php';

// env() é usada em app.php mas só existe no CakePHP; definir aqui para o script rodar sozinho
if (!function_exists('env')) {
    function env($key, $default = null) {
        $v = getenv($key);
        return $v !== false ? $v : (isset($_ENV[$key]) ? $_ENV[$key] : $default);
    }
}

// Carregar config: app.php + app_local.php (override em produção)
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

$tableName = 'atendimento_timer';

$dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "Conectado ao banco: {$dbname} ({$host}:{$port})\n";

    // Verificar se a tabela existe
    $stmt = $pdo->prepare("
        SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = :table
        ) AS existe
    ");
    $stmt->execute(['table' => $tableName]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $existe = isset($row['existe']) && ($row['existe'] === 't' || $row['existe'] === true || $row['existe'] === '1');

    if ($existe) {
        echo "Tabela '{$tableName}' já existe. Nada a fazer.\n";
        exit(0);
    }

    echo "Tabela '{$tableName}' não encontrada. Criando...\n";

    $pdo->exec("
        CREATE TABLE atendimento_timer (
            id SERIAL PRIMARY KEY,
            idticket INTEGER NOT NULL,
            idusuario INTEGER NOT NULL,
            idempresa INTEGER NOT NULL,
            hora_inicio TIMESTAMP WITHOUT TIME ZONE NOT NULL,
            hora_pausa TIMESTAMP WITHOUT TIME ZONE NULL,
            hora_fim TIMESTAMP WITHOUT TIME ZONE NULL,
            duracao_calculada INTEGER NULL,
            created TIMESTAMP WITHOUT TIME ZONE NULL,
            modified TIMESTAMP WITHOUT TIME ZONE NULL
        )
    ");
    $pdo->exec("CREATE INDEX idx_atendimento_timer_idticket ON atendimento_timer (idticket)");
    $pdo->exec("CREATE INDEX idx_atendimento_timer_idusuario ON atendimento_timer (idusuario)");
    $pdo->exec("CREATE INDEX idx_atendimento_timer_hora_fim ON atendimento_timer (hora_fim)");
    $pdo->exec("COMMENT ON TABLE atendimento_timer IS 'Timer de horas técnicas por ticket (início/pausa/fim).'");

    echo "Tabela '{$tableName}' criada com sucesso.\n";
    exit(0);

} catch (PDOException $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
