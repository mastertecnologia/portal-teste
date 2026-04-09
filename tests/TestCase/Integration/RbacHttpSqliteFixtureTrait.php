<?php
declare(strict_types=1);
namespace App\Test\TestCase\Integration;

use Cake\Database\Connection;

/**
 * DDL + truncate + seed mínimos para integração HTTP RBAC com SQLite (:memory:).
 * Tabelas de domínio: areas, bancosenhas, clientes, contratos_horas, empresas (incl. urlerp), empresasusers, faturamento, feriados, financeiro_lancamentos, listamembros, ordensservico (incl. idproblema, locacao, dataprevisao, contrato para Ordensservico::index), orcamentosnovosdes, produtos, problemas, visitas (+ rbac_*, users).
 */
trait RbacHttpSqliteFixtureTrait {

	protected static function rbacHttpSqliteCreateBaseSchema(Connection $conn): void {
		$stmts = [
			'CREATE TABLE IF NOT EXISTS users (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				username VARCHAR(255) NOT NULL DEFAULT \'\',
				name VARCHAR(255) NULL,
				email VARCHAR(255) NULL,
				password VARCHAR(255) NULL,
				role INTEGER NOT NULL DEFAULT 0,
				admin INTEGER NOT NULL DEFAULT 0,
				idcliente INTEGER NULL,
				idempresa INTEGER NULL,
				skin VARCHAR(32) NULL,
				sidebar INTEGER NULL DEFAULT 1,
				pagelength INTEGER NULL DEFAULT 25,
				setor VARCHAR(64) NULL,
				permissaoacesso INTEGER NULL
			)',
			'CREATE TABLE IF NOT EXISTS empresasusers (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				iduser INTEGER NOT NULL,
				idempresa INTEGER NOT NULL
			)',
			'CREATE TABLE IF NOT EXISTS areas (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idempresa INTEGER NOT NULL,
				descricao VARCHAR(255) NOT NULL DEFAULT \'\'
			)',
			'CREATE TABLE IF NOT EXISTS bancosenhas (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idempresa INTEGER NOT NULL,
				nomeservico VARCHAR(255) NOT NULL DEFAULT \'\',
				provedor VARCHAR(255) NULL,
				ip VARCHAR(64) NULL,
				porta VARCHAR(32) NULL,
				usuario VARCHAR(255) NULL,
				url TEXT NULL,
				protocolo VARCHAR(64) NULL,
				senha TEXT NULL
			)',
			'CREATE TABLE IF NOT EXISTS empresas (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				nomefantasia VARCHAR(255) NOT NULL DEFAULT \'\',
				email VARCHAR(255) NULL,
				token VARCHAR(255) NULL,
				nrousuarios INTEGER NOT NULL DEFAULT 0,
				created DATETIME NULL,
				inativa INTEGER NOT NULL DEFAULT 0,
				urlerp VARCHAR(512) NULL
			)',
			'CREATE TABLE IF NOT EXISTS clientes (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idempresa INTEGER NOT NULL,
				razaosocial VARCHAR(255) NOT NULL DEFAULT \'\',
				tipo VARCHAR(32) NULL,
				nome VARCHAR(255) NULL,
				inativo INTEGER NOT NULL DEFAULT 0
			)',
			'CREATE TABLE IF NOT EXISTS orcamentosnovosdes (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idempresa INTEGER NOT NULL,
				idcliente INTEGER NULL,
				idautor INTEGER NULL,
				status INTEGER NOT NULL DEFAULT 0
			)',
			'CREATE TABLE IF NOT EXISTS produtos (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idempresa INTEGER NOT NULL,
				tipo INTEGER NOT NULL DEFAULT 1,
				codigo VARCHAR(64) NULL,
				vlunitario REAL NULL,
				descricao VARCHAR(255) NULL,
				ativo INTEGER NOT NULL DEFAULT 1
			)',
			'CREATE TABLE IF NOT EXISTS problemas (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idempresa INTEGER NOT NULL DEFAULT 1,
				descricao VARCHAR(255) NOT NULL DEFAULT \'\'
			)',
			'CREATE TABLE IF NOT EXISTS feriados (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				data DATE NOT NULL,
				descricao VARCHAR(255) NOT NULL DEFAULT \'\',
				idempresa INTEGER NULL
			)',
			'CREATE TABLE IF NOT EXISTS contratos_horas (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idcliente INTEGER NOT NULL,
				idempresa INTEGER NOT NULL,
				data_inicio DATE NOT NULL,
				data_fim DATE NOT NULL,
				horas_contratadas REAL NOT NULL DEFAULT 0,
				saldo_horas REAL NOT NULL DEFAULT 0,
				valor_hora_comercial REAL NULL,
				ativo INTEGER NOT NULL DEFAULT 1
			)',
			'CREATE TABLE IF NOT EXISTS visitas (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idempresa INTEGER NOT NULL,
				idcliente INTEGER NULL,
				nomecliente VARCHAR(255) NULL,
				data DATETIME NULL,
				horaini DATETIME NULL,
				horafim DATETIME NULL,
				situacao INTEGER NOT NULL DEFAULT 0,
				motivo TEXT NULL
			)',
			'CREATE TABLE IF NOT EXISTS listamembros (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idvisita INTEGER NOT NULL,
				iduser INTEGER NOT NULL
			)',
			'CREATE TABLE IF NOT EXISTS ordensservico (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idempresa INTEGER NOT NULL,
				idcliente INTEGER NULL,
				iduser INTEGER NULL,
				idproblema INTEGER NULL,
				situacao INTEGER NOT NULL DEFAULT 0,
				locacao INTEGER NOT NULL DEFAULT -1,
				dataabertura DATETIME NULL,
				dataprevisao DATETIME NULL,
				contrato INTEGER NULL,
				valortotal REAL NULL,
				prefat_conf_exec INTEGER NULL,
				prefat_conf_comercial INTEGER NULL,
				prefat_conf_fiscal INTEGER NULL,
				prefat_conf_em DATETIME NULL,
				prefat_conf_iduser INTEGER NULL
			)',
			'CREATE TABLE IF NOT EXISTS faturamento (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idempresa INTEGER NOT NULL,
				idcliente INTEGER NULL,
				idautor INTEGER NULL,
				idordem INTEGER NULL,
				status VARCHAR(32) NOT NULL DEFAULT \'rascunho\',
				numero VARCHAR(64) NULL
			)',
			'CREATE TABLE IF NOT EXISTS financeiro_lancamentos (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idempresa INTEGER NOT NULL,
				idcliente INTEGER NULL,
				tipo VARCHAR(32) NOT NULL DEFAULT \'receita\',
				valor REAL NOT NULL DEFAULT 0,
				status VARCHAR(32) NOT NULL DEFAULT \'aberto\',
				data_vencimento DATE NULL,
				data_recebimento DATE NULL,
				data_lancamento DATE NULL,
				idfaturamento INTEGER NULL,
				idautor INTEGER NULL
			)',
			"CREATE TABLE IF NOT EXISTS rbac_permissions (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				code VARCHAR(120) NOT NULL UNIQUE,
				name VARCHAR(255) NOT NULL,
				module VARCHAR(100) NOT NULL DEFAULT '',
				controller VARCHAR(80) NOT NULL DEFAULT '',
				action VARCHAR(80) NOT NULL DEFAULT '*',
				perm_type VARCHAR(16) NOT NULL DEFAULT 'rbac',
				abac_scope VARCHAR(32) NULL,
				description TEXT NULL,
				sort_order INTEGER NOT NULL DEFAULT 0,
				created DATETIME NULL,
				modified DATETIME NULL
			)",
			'CREATE TABLE IF NOT EXISTS rbac_roles (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				slug VARCHAR(64) NOT NULL UNIQUE,
				name VARCHAR(120) NOT NULL,
				description TEXT NULL,
				is_system INTEGER NOT NULL DEFAULT 1,
				active INTEGER NOT NULL DEFAULT 1,
				sort_order INTEGER NOT NULL DEFAULT 0,
				hierarchy_level INTEGER NOT NULL DEFAULT 0,
				created DATETIME NULL,
				modified DATETIME NULL
			)',
			'CREATE TABLE IF NOT EXISTS rbac_roles_permissions (
				role_id INTEGER NOT NULL,
				permission_id INTEGER NOT NULL,
				PRIMARY KEY (role_id, permission_id)
			)',
			'CREATE TABLE IF NOT EXISTS rbac_users_roles (
				user_id INTEGER NOT NULL,
				role_id INTEGER NOT NULL,
				PRIMARY KEY (user_id, role_id)
			)',
		];
		foreach ($stmts as $sql) {
			$conn->execute($sql);
		}
	}

	protected static function rbacHttpSqliteTruncate(Connection $conn): void {
		$conn->execute('DELETE FROM rbac_roles_permissions');
		$conn->execute('DELETE FROM rbac_users_roles');
		$conn->execute('DELETE FROM rbac_permissions');
		$conn->execute('DELETE FROM rbac_roles');
		$conn->execute('DELETE FROM bancosenhas');
		$conn->execute('DELETE FROM financeiro_lancamentos');
		$conn->execute('DELETE FROM faturamento');
		$conn->execute('DELETE FROM ordensservico');
		$conn->execute('DELETE FROM contratos_horas');
		$conn->execute('DELETE FROM listamembros');
		$conn->execute('DELETE FROM visitas');
		$conn->execute('DELETE FROM orcamentosnovosdes');
		$conn->execute('DELETE FROM clientes');
		$conn->execute('DELETE FROM feriados');
		$conn->execute('DELETE FROM problemas');
		$conn->execute('DELETE FROM areas');
		$conn->execute('DELETE FROM empresasusers');
		$conn->execute('DELETE FROM produtos');
		$conn->execute('DELETE FROM empresas');
		$conn->execute('DELETE FROM users');
	}

	/**
	 * @param array{admin: int, role: int, idempresa?: int|null, idcliente?: int|null} $flags
	 */
	protected static function rbacHttpSqliteSeedUser(Connection $conn, int $id, array $flags): void {
		$admin = (int)($flags['admin'] ?? 0);
		$role = (int)($flags['role'] ?? 0);
		$idempresa = array_key_exists('idempresa', $flags) ? $flags['idempresa'] : null;
		$idcliente = array_key_exists('idcliente', $flags) ? $flags['idcliente'] : null;
		$conn->execute(
			'INSERT INTO users (id, username, name, email, password, role, admin, idcliente, idempresa, skin, sidebar, pagelength) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
			[
				$id,
				'u' . (string)$id,
				'User ' . (string)$id,
				'u' . (string)$id . '@test.local',
				'unused',
				$role,
				$admin,
				$idcliente,
				$idempresa,
				'',
				1,
				25,
			]
		);
	}

	/** Empresa id=1 para controllers que fazem Empresas->get(idempresa) (ex.: Produtos::index + SOAP). */
	protected static function rbacHttpSqliteSeedEmpresaMin(Connection $conn, int $id = 1): void {
		$conn->execute(
			'INSERT INTO empresas (id, nomefantasia, email, token, nrousuarios, created, inativa, urlerp) VALUES (?,?,?,?,?,datetime(\'now\'),?,?)',
			[$id, 'Empresa RBAC HTTP', null, null, 0, 0, '']
		);
	}

}
