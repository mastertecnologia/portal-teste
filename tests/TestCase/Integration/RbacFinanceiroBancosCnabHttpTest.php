<?php
declare(strict_types=1);

namespace App\Test\TestCase\Integration;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Stack HTTP + RBAC para APIs CNAB do módulo Financeiro > Bancos.
 *
 * Cobre:
 * - GET /financeiro/remessas/listar-titulos
 * - POST /financeiro/retornos/processar
 *
 * Objetivo:
 * - garantir redirect para login quando guest
 * - garantir access-denied sem permissão RBAC
 * - garantir acesso quando as permissões CNAB estão presentes
 *
 * Requer pdo_sqlite e bootstrap HTTP.
 */
class RbacFinanceiroBancosCnabHttpTest extends TestCase
{
    use IntegrationTestTrait;
    use RbacHttpSqliteFixtureTrait;

    /** @var \Cake\Database\Connection */
    protected static $conn;

    /** @var bool */
    protected static $schemaReady = false;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('Extensão pdo_sqlite necessária para este teste.');
        }

        if (!defined('PGM_HTTP_TEST_DATASOURCE') || !PGM_HTTP_TEST_DATASOURCE) {
            self::markTestSkipped(
                'Correr com bootstrap HTTP: phpunit --bootstrap tests/bootstrap_http.php --testsuite rbac-http'
            );
        }

        self::$conn = ConnectionManager::get('default');

        if (!self::$schemaReady) {
            self::rbacHttpSqliteCreateBaseSchema(self::$conn);
            self::ensureFinanceiroBancosCnabSchema(self::$conn);
            self::$schemaReady = true;
        }

        TableRegistry::clear();
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->configApplication('App\Application', [ROOT . DS . 'config']);
        $this->useHttpServer(true);

        TableRegistry::clear();
        self::rbacHttpSqliteTruncate(self::$conn);
        self::ensureFinanceiroBancosCnabSchema(self::$conn);
    }

    public function testGuestRemessasListarTitulosRedirectsToLogin(): void
    {
        $this->get($this->remessasListarTitulosPath());

        $this->assertResponseCode(302);
        $this->assertRedirectContains('users/login');
    }

    public function testGuestRetornosProcessarRedirectsToLogin(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post($this->retornosProcessarPath(), []);

        $this->assertResponseCode(302);
        $this->assertRedirectContains('users/login');
    }

    public function testEquipeWithoutCnabPermissionsDeniedOnRemessasApi(): void
    {
        self::rbacHttpSqliteSeedEmpresaMin(self::$conn, 1);
        self::rbacHttpSqliteSeedUser(self::$conn, 200, [
            'admin' => 0,
            'role' => 0,
            'idempresa' => 1,
        ]);

        $this->seedPermissionForUser(
            200,
            'financeiro.view',
            'Financeiro — dashboard',
            'Financeiro',
            'Financeiro',
            'index'
        );

        $this->session([
            'Auth' => [
                'User' => $this->authUser(200, 1),
            ],
        ]);

        $this->get($this->remessasListarTitulosPath());
        $this->assertResponseCode(302);
        $this->assertRedirectContains('access-denied');
    }

    public function testEquipeWithoutCnabPermissionsDeniedOnRetornosApi(): void
    {
        self::rbacHttpSqliteSeedEmpresaMin(self::$conn, 1);
        self::rbacHttpSqliteSeedUser(self::$conn, 201, [
            'admin' => 0,
            'role' => 0,
            'idempresa' => 1,
        ]);

        $this->seedPermissionForUser(
            201,
            'financeiro.view',
            'Financeiro — dashboard',
            'Financeiro',
            'Financeiro',
            'index'
        );

        $this->session([
            'Auth' => [
                'User' => $this->authUser(201, 1),
            ],
        ]);

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post($this->retornosProcessarPath(), []);

        $this->assertResponseCode(302);
        $this->assertRedirectContains('access-denied');
    }

    public function testEquipeWithRemessasApiPermissionCanListarTitulos(): void
    {
        self::rbacHttpSqliteSeedEmpresaMin(self::$conn, 1);
        self::rbacHttpSqliteSeedUser(self::$conn, 202, [
            'admin' => 0,
            'role' => 0,
            'idempresa' => 1,
        ]);
        $this->seedClienteMin(1, 1, 10, 'Cliente CNAB');
        $this->seedBancoMin(1, 1, 5, '756', 'Sicoob');

        self::$conn->execute(
            "INSERT INTO financeiro_lancamentos (
                id, idempresa, idcliente, tipo, valor, status, data_vencimento,
                data_recebimento, data_lancamento, idfaturamento, idautor,
                financeiro_banco_id, descricao, nosso_numero, status_cobranca
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                1001,
                1,
                10,
                'receita',
                150.75,
                'aberto',
                '2026-05-10',
                null,
                '2026-04-25',
                null,
                202,
                5,
                'Mensalidade cliente CNAB',
                '',
                'pendente_remessa',
            ]
        );

        $this->seedPermissionForUser(
            202,
            'financeiro.remessas_api',
            'Financeiro — API CNAB de remessas bancárias',
            'Financeiro',
            'Remessas',
            'listarTitulos,listar_titulos,gerarRemessa,gerar_remessa'
        );

        $this->session([
            'Auth' => [
                'User' => $this->authUser(202, 1),
            ],
        ]);

        $this->get($this->remessasListarTitulosPath());

        $this->assertResponseOk();
        $this->assertContentType('application/json');
        $this->assertResponseContains('"ok":true');
        $this->assertResponseContains('"items"');
        $this->assertResponseContains('"Mensalidade cliente CNAB"');
    }

    public function testEquipeWithRetornosApiPermissionReachesControllerValidation(): void
    {
        self::rbacHttpSqliteSeedEmpresaMin(self::$conn, 1);
        self::rbacHttpSqliteSeedUser(self::$conn, 203, [
            'admin' => 0,
            'role' => 0,
            'idempresa' => 1,
        ]);

        $this->seedPermissionForUser(
            203,
            'financeiro.retornos_api',
            'Financeiro — API CNAB de retornos bancários',
            'Financeiro',
            'Retornos',
            'processar'
        );

        $this->session([
            'Auth' => [
                'User' => $this->authUser(203, 1),
            ],
        ]);

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post($this->retornosProcessarPath(), []);

        $this->assertResponseCode(400);
        $this->assertContentType('application/json');
        $this->assertResponseContains('"ok":false');
        $this->assertResponseContains('Arquivo de retorno não informado');
    }

    protected static function ensureFinanceiroBancosCnabSchema($conn): void
    {
        $stmts = [
            "CREATE TABLE IF NOT EXISTS financeiro_bancos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                idempresa INTEGER NOT NULL,
                codigo_banco VARCHAR(10) NULL,
                numero_banco VARCHAR(10) NULL,
                cnab VARCHAR(10) NULL,
                nome VARCHAR(255) NOT NULL DEFAULT '',
                numero_agencia VARCHAR(20) NULL,
                digito_agencia VARCHAR(5) NULL,
                numero_conta VARCHAR(30) NULL,
                digito_conta VARCHAR(5) NULL,
                codigo_banco_interno VARCHAR(50) NULL,
                verifica_receber VARCHAR(100) NULL,
                utiliza_endosso VARCHAR(10) NULL,
                convenio VARCHAR(50) NULL,
                carteira VARCHAR(20) NULL,
                cnab_tipo VARCHAR(10) NOT NULL DEFAULT '240',
                proxima_remessa INTEGER NOT NULL DEFAULT 1,
                logotipo VARCHAR(255) NULL,
                observacoes TEXT NULL,
                ativo INTEGER NOT NULL DEFAULT 1,
                created DATETIME NULL,
                modified DATETIME NULL
            )",
            "CREATE TABLE IF NOT EXISTS financeiro_remessas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                idempresa INTEGER NOT NULL,
                financeiro_banco_id INTEGER NOT NULL,
                usuario_id INTEGER NULL,
                cnab_layout VARCHAR(10) NOT NULL DEFAULT '240',
                sequencial_arquivo INTEGER NOT NULL DEFAULT 1,
                numero_remessa VARCHAR(30) NULL,
                data_geracao DATE NOT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'gerada',
                nome_arquivo VARCHAR(255) NOT NULL DEFAULT '',
                caminho_arquivo VARCHAR(255) NULL,
                quantidade_titulos INTEGER NOT NULL DEFAULT 0,
                valor_total REAL NOT NULL DEFAULT 0,
                observacoes TEXT NULL,
                created DATETIME NULL,
                modified DATETIME NULL
            )",
            "CREATE TABLE IF NOT EXISTS financeiro_remessa_titulos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                financeiro_remessa_id INTEGER NOT NULL,
                financeiro_lancamento_id INTEGER NOT NULL,
                nosso_numero_remessa VARCHAR(40) NULL,
                numero_documento VARCHAR(40) NULL,
                valor_titulo REAL NOT NULL DEFAULT 0,
                data_vencimento DATE NULL,
                status_item VARCHAR(30) NOT NULL DEFAULT 'incluido',
                codigo_ocorrencia VARCHAR(10) NULL,
                mensagem_ocorrencia TEXT NULL,
                created DATETIME NULL,
                modified DATETIME NULL
            )",
        ];

        foreach ($stmts as $sql) {
            $conn->execute($sql);
        }

        $alters = [
            'ALTER TABLE financeiro_lancamentos ADD COLUMN financeiro_banco_id INTEGER NULL',
            'ALTER TABLE financeiro_lancamentos ADD COLUMN descricao VARCHAR(255) NULL',
            'ALTER TABLE financeiro_lancamentos ADD COLUMN nosso_numero VARCHAR(40) NULL',
            'ALTER TABLE financeiro_lancamentos ADD COLUMN status_cobranca VARCHAR(30) NULL',
            'ALTER TABLE financeiro_lancamentos ADD COLUMN codigo_rejeicao VARCHAR(10) NULL',
            'ALTER TABLE financeiro_lancamentos ADD COLUMN mensagem_rejeicao TEXT NULL',
            'ALTER TABLE financeiro_lancamentos ADD COLUMN data_baixa DATE NULL',
            'ALTER TABLE financeiro_lancamentos ADD COLUMN plano_conta_id INTEGER NULL',
            'ALTER TABLE financeiro_lancamentos ADD COLUMN centro_custo_id INTEGER NULL',
            'ALTER TABLE financeiro_lancamentos ADD COLUMN valor_pago REAL NULL',
        ];

        foreach ($alters as $sql) {
            try {
                $conn->execute($sql);
            } catch (\Throwable $e) {
                // coluna já existe no schema de teste
            }
        }
    }

    protected function seedPermissionForUser(
        int $userId,
        string $code,
        string $name,
        string $module,
        string $controller,
        string $action
    ): void {
        self::$conn->execute(
            'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
            [$code, $name, $module, $controller, $action, 0]
        );
        $permissionId = (int)self::$conn->lastInsertId();

        self::$conn->execute(
            'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order, hierarchy_level) VALUES (?,?,?,?,?,?)',
            [
                'http_' . preg_replace('/[^a-z0-9_]+/i', '_', $code) . '_' . $userId,
                'HTTP ' . $code,
                0,
                1,
                1,
                100,
            ]
        );
        $roleId = (int)self::$conn->lastInsertId();

        self::$conn->execute(
            'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
            [$roleId, $permissionId]
        );
        self::$conn->execute(
            'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
            [$userId, $roleId]
        );
    }

    protected function seedBancoMin(
        int $empresaId,
        int $usuarioId,
        int $bancoId,
        string $codigoBanco,
        string $nome
    ): void {
        self::$conn->execute(
            "INSERT INTO financeiro_bancos (
                id, idempresa, codigo_banco, numero_banco, cnab, nome,
                numero_agencia, digito_agencia, numero_conta, digito_conta,
                convenio, carteira, cnab_tipo, proxima_remessa, ativo, created, modified
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $bancoId,
                $empresaId,
                $codigoBanco,
                $codigoBanco,
                str_pad($codigoBanco, 3, '0', STR_PAD_LEFT),
                $nome,
                '1234',
                '0',
                '98765',
                '1',
                '123456',
                '1',
                '240',
                1,
                1,
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s'),
            ]
        );
    }

    protected function seedClienteMin(
        int $empresaId,
        int $tipo,
        int $clienteId,
        string $nome
    ): void {
        $publicCode = 'P' . str_pad((string)$clienteId, 8, '0', STR_PAD_LEFT);
        self::$conn->execute(
            'INSERT INTO clientes (id, idempresa, razaosocial, tipo, nome, inativo, public_code) VALUES (?,?,?,?,?,?,?)',
            [
                $clienteId,
                $empresaId,
                $tipo === 1 ? '' : $nome,
                (string)$tipo,
                $tipo === 1 ? $nome : '',
                0,
                $publicCode,
            ]
        );
        self::$conn->execute(
            'INSERT INTO clientes_public_code_seq (idempresa, next_val) VALUES (?, ?) ON CONFLICT(idempresa) DO UPDATE SET next_val = CASE WHEN clientes_public_code_seq.next_val < excluded.next_val THEN excluded.next_val ELSE clientes_public_code_seq.next_val END',
            [$empresaId, $clienteId]
        );
    }

    protected function authUser(int $id, int $empresaId): array
    {
        return [
            'id' => $id,
            'username' => 'usuario_http_' . $id,
            'name' => 'Usuário HTTP ' . $id,
            'role' => 0,
            'admin' => 0,
            'idcliente' => null,
            'idempresa' => $empresaId,
            'setor' => null,
            'permissaoacesso' => null,
        ];
    }

    protected function remessasListarTitulosPath(): string
    {
        return $this->withBase('/financeiro/remessas/listar-titulos');
    }

    protected function retornosProcessarPath(): string
    {
        return $this->withBase('/financeiro/retornos/processar');
    }

    protected function withBase(string $suffix): string
    {
        $base = Configure::read('App.base');

        if ($base === false || $base === null || $base === '' || !is_string($base)) {
            return $suffix;
        }

        $base = rtrim($base, '/');
        if ($base === '') {
            return $suffix;
        }

        return $base . $suffix;
    }
}
