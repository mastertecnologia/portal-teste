<?php
declare(strict_types=1);
namespace App\Test\TestCase\Integration;

use App\Utility\Fiscal\FiscalCalculator;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Stack HTTP + RBAC sobre Fiscal::index (dashboard NF-e).
 * Requer pdo_sqlite e tests/bootstrap_http.php (suite rbac-http).
 */
class RbacFiscalHttpTest extends TestCase {

	use IntegrationTestTrait;
	use RbacHttpSqliteFixtureTrait;

	/** @var \Cake\Database\Connection */
	protected static $conn;

	/** @var bool */
	protected static $schemaReady = false;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		if (!extension_loaded('pdo_sqlite')) {
			self::markTestSkipped('Extensão pdo_sqlite necessária para este teste.');
		}
		if (!defined('PGM_HTTP_TEST_DATASOURCE') || !PGM_HTTP_TEST_DATASOURCE) {
			self::markTestSkipped('Correr com bootstrap HTTP: phpunit --bootstrap tests/bootstrap_http.php --testsuite rbac-http');
		}
		self::$conn = ConnectionManager::get('default');
		if (!self::$schemaReady) {
			self::rbacHttpSqliteCreateBaseSchema(self::$conn);
			self::$schemaReady = true;
		}
		TableRegistry::clear();
	}

	public function setUp(): void {
		parent::setUp();
		$this->configApplication('App\Application', [ROOT . DS . 'config']);
		$this->useHttpServer(true);
		TableRegistry::clear();
		self::rbacHttpSqliteTruncate(self::$conn);
	}

	public function testGuestFiscalIndexRedirectsToLogin(): void {
		$this->get($this->fiscalIndexPath());
		$this->assertResponseCode(302);
		$this->assertRedirectContains('users/login');
	}

	public function testEquipeWithFiscalDashboardLoadsModule(): void {
		self::rbacHttpSqliteSeedUser(self::$conn, 110, [
			'admin' => 0,
			'role' => 0,
			'idempresa' => 1,
		]);

		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			['fiscal.dashboard', 'Fiscal painel', 'Financeiro', 'Fiscal', '*', 0]
		);
		$pid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order, hierarchy_level) VALUES (?,?,?,?,?,?)',
			['http_fiscal_dash', 'HTTP fiscal dashboard', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[110, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 110,
					'username' => 'equipe_fiscal_http',
					'name' => 'Equipe fiscal',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);

		$this->get($this->fiscalIndexPath());
		$this->assertResponseOk();
		$this->assertResponseContains('Módulo Fiscal');
		$this->assertResponseContains('Status SEFAZ');
		$this->assertResponseContains('Inutilizar numeração');
		$this->assertResponseContains('Séries (entrada)');
		$this->assertResponseContains('DF-e (AN)');
		$this->assertResponseContains('DF-e recebidos');
	}

	public function testGuestFiscalDfeRecebidosRedirectsToLogin(): void {
		$this->get($this->fiscalDfeRecebidosPath());
		$this->assertResponseCode(302);
		$this->assertRedirectContains('users/login');
	}

	public function testEquipeDfeRecebidosChaveFilterShowsEmptyKeyMessage(): void {
		$uid = 115;
		self::rbacHttpSqliteSeedUser(self::$conn, $uid, [
			'admin' => 0,
			'role' => 0,
			'idempresa' => 1,
		]);
		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			['fiscal.dashboard', 'Fiscal painel', 'Financeiro', 'Fiscal', '*', 0]
		);
		$pid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order, hierarchy_level) VALUES (?,?,?,?,?,?)',
			['http_fiscal_dfe_chave', 'HTTP fiscal DF-e chave', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[$uid, $rid]
		);

		self::$conn->execute(
			'INSERT INTO fiscal_dfe_recebidos (idempresa, schema, chave_acesso, tipo_documento, conteudo_hash, xml_conteudo, status, created)
			 VALUES (?,?,?,?,?,?,?,?)',
			[
				1,
				'nfe',
				'35200123456789012345678901234567890123456789',
				'resNFe',
				'098f6bcd4621d373cade4e832627b4f6',
				'<nfeProc/>',
				'pendente',
				'2026-01-01 10:00:00',
			]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => $uid,
					'username' => 'equipe_dfe_chave',
					'name' => 'Equipe DF-e chave',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);

		$this->get($this->fiscalPathWithSuffix('/fiscal/dfe-recebidos?status=pendente&chave=999'));
		$this->assertResponseOk();
		$this->assertResponseContains('Nenhum documento corresponde à chave indicada');
		$this->assertResponseContains('Limpar filtro de chave');
	}

	public function testGuestPostDfeCriarEntradaRedirectsToLogin(): void {
		$this->enableCsrfToken();
		$this->enableSecurityToken();
		$this->post($this->fiscalPathWithSuffix('/fiscal/dfe-recebidos/criar-entrada/1'), []);
		$this->assertResponseCode(302);
		$this->assertRedirectContains('users/login');
	}

	public function testEquipeDashboardOnlyPostImportDfeNegadoSemNotasEntrada(): void {
		$uid = 120;
		self::rbacHttpSqliteSeedUser(self::$conn, $uid, [
			'admin' => 0,
			'role' => 0,
			'idempresa' => 1,
		]);
		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			['fiscal.dashboard', 'Fiscal painel', 'Financeiro', 'Fiscal', '*', 0]
		);
		$pid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order, hierarchy_level) VALUES (?,?,?,?,?,?)',
			['http_fiscal_dfe_import', 'HTTP fiscal dfe import', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[$uid, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => $uid,
					'username' => 'equipe_dfe_so_dashboard',
					'name' => 'Equipe DF-e',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);
		$this->enableRetainFlashMessages();
		$this->enableCsrfToken();
		$this->enableSecurityToken();
		$this->post($this->fiscalPathWithSuffix('/fiscal/dfe-recebidos/criar-entrada/1'), []);

		$this->assertResponseCode(302);
		$this->assertRedirectContains('dfe-recebidos');
		$this->assertFlashMessage(
			'Sua função não inclui permissão para criar notas de entrada a partir do DF-e (fiscal.notas_entrada).'
		);
	}

	public function testEquipeComNotasEntradaPostImportDfeNaoNegadoPorPermissao(): void {
		$uid = 121;
		self::rbacHttpSqliteSeedUser(self::$conn, $uid, [
			'admin' => 0,
			'role' => 0,
			'idempresa' => 1,
		]);
		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			['fiscal.dashboard', 'Fiscal painel', 'Financeiro', 'Fiscal', '*', 0]
		);
		$pidDash = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			['fiscal.notas_entrada', 'Fiscal entrada', 'Financeiro', 'FiscalNotasEntrada', '*', 0]
		);
		$pidEnt = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order, hierarchy_level) VALUES (?,?,?,?,?,?)',
			['http_fiscal_dfe_full', 'HTTP fiscal dfe full', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pidDash]
		);
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pidEnt]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[$uid, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => $uid,
					'username' => 'equipe_dfe_com_entrada',
					'name' => 'Equipe DF-e entrada',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);
		$this->enableRetainFlashMessages();
		$this->enableCsrfToken();
		$this->enableSecurityToken();
		$this->post($this->fiscalPathWithSuffix('/fiscal/dfe-recebidos/criar-entrada/1'), []);

		$this->assertResponseCode(302);
		$this->assertRedirectContains('dfe-recebidos');
		$this->assertFlashMessage('Documento DF-e não encontrado.');
	}

	public function testEquipePostImportDfeXmlValidoCriaRascunhoERedirecionaEdit(): void {
		self::rbacHttpSqliteSeedEmpresaMin(self::$conn, 1);
		self::$conn->execute('UPDATE empresas SET cnpj = ? WHERE id = 1', ['99888777000166']);
		self::$conn->execute(
			'INSERT INTO fiscal_empresas_config (idempresa, regime_tributario, regime_normal_enquadramento, ambiente, serie_nfe, prox_numero_nfe, serie_nfse, prox_numero_nfse, serie_nfce, prox_numero_nfce, uf, created, modified) VALUES (1, 3, 2, 2, 1, 1, 1, 1, 1, 1, \'SP\', datetime(\'now\'), datetime(\'now\'))'
		);
		self::$conn->execute(
			'INSERT INTO fiscal_natureza_operacao (idempresa, codigo, descricao, tipo, gera_financeiro, ativo, created, modified) VALUES (1,\'E1\',\'Compra mercadoria\',\'entrada\',1,1,datetime(\'now\'),datetime(\'now\'))'
		);

		$emitCnpj = '11222333000181';
		$destCnpj = '99888777000166';
		$chave = FiscalCalculator::gerarChaveAcesso('35', '2401', $emitCnpj, '55', '1', '10', '1', '12345678');
		$xml = '<?xml version="1.0" encoding="UTF-8"?>'
			. '<nfeProc xmlns="http://www.portalfiscal.inf.br/nfe">'
			. '<NFe><infNFe Id="NFe' . $chave . '">'
			. '<ide><cUF>35</cUF><natOp>Compra para industrialização</natOp><mod>55</mod><serie>1</serie><nNF>10</nNF>'
			. '<dhEmi>2024-01-15T10:00:00-03:00</dhEmi></ide>'
			. '<emit><CNPJ>' . $emitCnpj . '</CNPJ><xNome>Fornecedor Teste</xNome></emit>'
			. '<dest><CNPJ>' . $destCnpj . '</CNPJ><xNome>Destinatário</xNome></dest>'
			. '<det nItem="1"><prod><cProd>P1</cProd><xProd>Produto A</xProd><NCM>12345678</NCM><CFOP>1102</CFOP>'
			. '<uCom>UN</uCom><qCom>2.0000</qCom><vUnCom>5.50</vUnCom><vProd>11.00</vProd></prod></det>'
			. '<total><ICMSTot><vProd>11.00</vProd><vNF>11.00</vNF></ICMSTot></total>'
			. '</infNFe></NFe>'
			. '<protNFe><infProt><chNFe>' . $chave . '</chNFe><nProt>135240000000000</nProt></infProt></protNFe>'
			. '</nfeProc>';
		$hash = md5($xml);
		self::$conn->execute(
			'INSERT INTO fiscal_dfe_recebidos (idempresa, nsu_doc, schema, chave_acesso, tipo_documento, conteudo_hash, xml_conteudo, status, created, modified) VALUES (?,?,?,?,?,?,?,?,datetime(\'now\'),datetime(\'now\'))',
			[1, '1', 'procNFe_v4.00', $chave, 'nfeProc', $hash, $xml, 'pendente']
		);
		$dfeId = (int)self::$conn->lastInsertId();

		$uid = 130;
		self::rbacHttpSqliteSeedUser(self::$conn, $uid, [
			'admin' => 0,
			'role' => 0,
			'idempresa' => 1,
		]);
		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			['fiscal.dashboard', 'Fiscal painel', 'Financeiro', 'Fiscal', '*', 0]
		);
		$pidDash = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			['fiscal.notas_entrada', 'Fiscal entrada', 'Financeiro', 'FiscalNotasEntrada', '*', 0]
		);
		$pidEnt = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order, hierarchy_level) VALUES (?,?,?,?,?,?)',
			['http_fiscal_dfe_e2e', 'HTTP fiscal dfe e2e', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pidDash]
		);
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pidEnt]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[$uid, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => $uid,
					'username' => 'equipe_dfe_e2e',
					'name' => 'Equipe DF-e E2E',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);
		$this->enableRetainFlashMessages();
		$this->enableCsrfToken();
		$this->enableSecurityToken();
		$this->post($this->fiscalPathWithSuffix('/fiscal/dfe-recebidos/criar-entrada/' . $dfeId), []);

		$this->assertResponseCode(302);
		$this->assertRedirectContains('fiscal-notas-entrada');
		$this->assertRedirectContains('edit');
		$this->assertFlashMessage('Rascunho de nota de entrada criado a partir do DF-e.');
	}

	public function testEquipeWithoutFiscalPermissionDenied(): void {
		self::rbacHttpSqliteSeedUser(self::$conn, 111, [
			'admin' => 0,
			'role' => 0,
			'idempresa' => 1,
		]);
		self::$conn->execute(
			'INSERT INTO rbac_permissions (code, name, module, controller, action, sort_order) VALUES (?,?,?,?,?,?)',
			['permissoes.matrix.view', 'Só matriz', 'Painel administrativo', 'Permissoes', 'adminMatrix,admin_matrix', 0]
		);
		$pid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles (slug, name, is_system, active, sort_order, hierarchy_level) VALUES (?,?,?,?,?,?)',
			['http_fiscal_denied', 'HTTP sem fiscal', 0, 1, 1, 100]
		);
		$rid = (int)self::$conn->lastInsertId();
		self::$conn->execute(
			'INSERT INTO rbac_roles_permissions (role_id, permission_id) VALUES (?,?)',
			[$rid, $pid]
		);
		self::$conn->execute(
			'INSERT INTO rbac_users_roles (user_id, role_id) VALUES (?,?)',
			[111, $rid]
		);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 111,
					'username' => 'equipe_sem_fiscal_http',
					'name' => 'Equipe sem fiscal',
					'role' => 0,
					'admin' => 0,
					'idcliente' => null,
					'idempresa' => 1,
					'setor' => null,
					'permissaoacesso' => null,
				],
			],
		]);

		$this->get($this->fiscalIndexPath());
		$this->assertResponseCode(302);
		$this->assertRedirectContains('access-denied');
	}

	protected function fiscalIndexPath(): string {
		return $this->fiscalPathWithSuffix('/fiscal');
	}

	protected function fiscalDfeRecebidosPath(): string {
		return $this->fiscalPathWithSuffix('/fiscal/dfe-recebidos');
	}

	protected function fiscalPathWithSuffix(string $suffix): string {
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
