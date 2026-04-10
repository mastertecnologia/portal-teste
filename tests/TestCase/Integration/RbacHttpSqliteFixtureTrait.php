<?php
declare(strict_types=1);
namespace App\Test\TestCase\Integration;

use Cake\Database\Connection;

/**
 * DDL + truncate + seed mínimos para integração HTTP RBAC com SQLite (:memory:).
 * Tabelas de domínio: areas, bancosenhas, clientes, contratos_horas, empresas (incl. urlerp), empresasusers, fiscal_* (index + itens/séries p/ controleSeries), faturamento, feriados, financeiro_lancamentos, listamembros, ordensservico (incl. idproblema, locacao, dataprevisao, contrato para Ordensservico::index), orcamentosnovosdes, produtos, problemas, visitas (+ rbac_*, users).
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
			'CREATE TABLE IF NOT EXISTS fiscal_empresas_config (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idempresa INTEGER NOT NULL UNIQUE,
				regime_tributario INTEGER NOT NULL DEFAULT 1,
				ambiente INTEGER NOT NULL DEFAULT 2,
				serie_nfe INTEGER NOT NULL DEFAULT 1,
				prox_numero_nfe INTEGER NOT NULL DEFAULT 1,
				serie_nfse INTEGER NOT NULL DEFAULT 1,
				prox_numero_nfse INTEGER NOT NULL DEFAULT 1,
				serie_nfce INTEGER NOT NULL DEFAULT 1,
				prox_numero_nfce INTEGER NOT NULL DEFAULT 1,
				certificado_id INTEGER NULL,
				dfe_ult_nsu VARCHAR(15) NULL,
				created DATETIME NULL,
				modified DATETIME NULL
			)',
			'CREATE TABLE IF NOT EXISTS fiscal_certificados (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idempresa INTEGER NOT NULL,
				nome VARCHAR(255) NOT NULL DEFAULT \'\',
				tipo VARCHAR(10) NOT NULL DEFAULT \'A1\',
				arquivo_pfx BLOB NULL,
				senha_hash VARCHAR(255) NULL,
				serial_number VARCHAR(100) NULL,
				cn_subject VARCHAR(500) NULL,
				cnpj_certificado VARCHAR(18) NULL,
				validade_inicio DATETIME NULL,
				validade_fim DATETIME NULL,
				ativo INTEGER NOT NULL DEFAULT 0,
				created DATETIME NULL,
				modified DATETIME NULL
			)',
			'CREATE TABLE IF NOT EXISTS fiscal_notas (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idempresa INTEGER NOT NULL,
				idcliente INTEGER NULL,
				modelo VARCHAR(5) NOT NULL DEFAULT \'55\',
				serie INTEGER NOT NULL DEFAULT 1,
				numero INTEGER NULL,
				tipo_operacao INTEGER NOT NULL DEFAULT 1,
				finalidade INTEGER NOT NULL DEFAULT 1,
				presenca INTEGER NOT NULL DEFAULT 9,
				data_emissao DATETIME NULL,
				data_saida DATETIME NULL,
				valor_produtos REAL NOT NULL DEFAULT 0,
				valor_frete REAL NOT NULL DEFAULT 0,
				valor_seguro REAL NOT NULL DEFAULT 0,
				valor_desconto REAL NOT NULL DEFAULT 0,
				valor_outras_despesas REAL NOT NULL DEFAULT 0,
				valor_total_impostos REAL NOT NULL DEFAULT 0,
				valor_total REAL NOT NULL DEFAULT 0,
				valor_icms REAL NOT NULL DEFAULT 0,
				valor_icms_st REAL NOT NULL DEFAULT 0,
				valor_ipi REAL NOT NULL DEFAULT 0,
				valor_pis REAL NOT NULL DEFAULT 0,
				valor_cofins REAL NOT NULL DEFAULT 0,
				valor_iss REAL NOT NULL DEFAULT 0,
				frete_modalidade INTEGER NOT NULL DEFAULT 9,
				status VARCHAR(30) NOT NULL DEFAULT \'rascunho\',
				created DATETIME NULL,
				modified DATETIME NULL
			)',
			'CREATE TABLE IF NOT EXISTS fiscal_notas_itens (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				fiscal_nota_id INTEGER NOT NULL,
				numero_item INTEGER NOT NULL DEFAULT 1,
				codigo_produto VARCHAR(60) NULL,
				descricao VARCHAR(500) NOT NULL DEFAULT \'\',
				ncm VARCHAR(10) NULL,
				cfop VARCHAR(5) NOT NULL DEFAULT \'5102\',
				unidade VARCHAR(10) NOT NULL DEFAULT \'UN\',
				quantidade REAL NOT NULL DEFAULT 1,
				valor_unitario REAL NOT NULL DEFAULT 0,
				valor_total REAL NOT NULL DEFAULT 0,
				created DATETIME NULL,
				modified DATETIME NULL
			)',
			'CREATE TABLE IF NOT EXISTS fiscal_notas_itens_series (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				fiscal_nota_item_id INTEGER NOT NULL,
				numero_serie VARCHAR(120) NOT NULL DEFAULT \'\',
				created DATETIME NULL,
				modified DATETIME NULL
			)',
			'CREATE TABLE IF NOT EXISTS fiscal_dfe_recebidos (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idempresa INTEGER NOT NULL,
				nsu_doc VARCHAR(20) NULL,
				schema VARCHAR(80) NOT NULL DEFAULT \'\',
				chave_acesso VARCHAR(44) NULL,
				tipo_documento VARCHAR(40) NOT NULL DEFAULT \'\',
				conteudo_hash VARCHAR(32) NOT NULL,
				xml_conteudo TEXT NOT NULL,
				status VARCHAR(20) NOT NULL DEFAULT \'pendente\',
				fiscal_nota_id INTEGER NULL,
				created DATETIME NULL,
				modified DATETIME NULL,
				UNIQUE(idempresa, conteudo_hash)
			)',
		];
		foreach ($stmts as $sql) {
			$conn->execute($sql);
		}
		try {
			$conn->execute('ALTER TABLE fiscal_empresas_config ADD COLUMN dfe_ult_nsu VARCHAR(15) NULL');
		} catch (\Throwable $e) {
			// SQLite: coluna já existe em esquemas antigos
		}
		try {
			$conn->execute('ALTER TABLE fiscal_empresas_config ADD COLUMN regime_normal_enquadramento INTEGER NULL');
		} catch (\Throwable $e) {
		}
		try {
			$conn->execute('CREATE TABLE IF NOT EXISTS fiscal_dfe_recebidos (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idempresa INTEGER NOT NULL,
				nsu_doc VARCHAR(20) NULL,
				schema VARCHAR(80) NOT NULL DEFAULT \'\',
				chave_acesso VARCHAR(44) NULL,
				tipo_documento VARCHAR(40) NOT NULL DEFAULT \'\',
				conteudo_hash VARCHAR(32) NOT NULL,
				xml_conteudo TEXT NOT NULL,
				status VARCHAR(20) NOT NULL DEFAULT \'pendente\',
				fiscal_nota_id INTEGER NULL,
				created DATETIME NULL,
				modified DATETIME NULL,
				UNIQUE(idempresa, conteudo_hash)
			)');
		} catch (\Throwable $e) {
		}
		self::rbacHttpSqliteEnsureFiscalSchemaForDfeImport($conn);
	}

	/**
	 * Colunas/tabelas extra para testes HTTP de importação DF-e → rascunho de nota de entrada (SQLite).
	 */
	protected static function rbacHttpSqliteEnsureFiscalSchemaForDfeImport(Connection $conn): void {
		$alters = [
			'ALTER TABLE empresas ADD COLUMN cnpj VARCHAR(18) NULL',
			'ALTER TABLE clientes ADD COLUMN cnpj VARCHAR(18) NULL',
			'ALTER TABLE fiscal_empresas_config ADD COLUMN uf VARCHAR(2) NULL',
			'ALTER TABLE fiscal_empresas_config ADD COLUMN regime_normal_enquadramento INTEGER NULL',
			'ALTER TABLE fiscal_notas ADD COLUMN user_id INTEGER NULL',
			'ALTER TABLE fiscal_notas ADD COLUMN chave_acesso VARCHAR(50) NULL',
			'ALTER TABLE fiscal_notas ADD COLUMN natureza_operacao VARCHAR(255) NULL',
			'ALTER TABLE fiscal_notas ADD COLUMN natureza_operacao_id INTEGER NULL',
			'ALTER TABLE fiscal_notas ADD COLUMN informacoes_complementares TEXT NULL',
			'ALTER TABLE fiscal_notas ADD COLUMN protocolo_autorizacao VARCHAR(20) NULL',
		];
		foreach ($alters as $sql) {
			try {
				$conn->execute($sql);
			} catch (\Throwable $e) {
				// coluna já existe
			}
		}
		$conn->execute(
			'CREATE TABLE IF NOT EXISTS fiscal_natureza_operacao (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idempresa INTEGER NOT NULL,
				codigo VARCHAR(20) NOT NULL DEFAULT \'\',
				descricao VARCHAR(255) NOT NULL DEFAULT \'\',
				tipo VARCHAR(20) NOT NULL DEFAULT \'saida\',
				cfop_padrao VARCHAR(5) NULL,
				gera_financeiro INTEGER NOT NULL DEFAULT 1,
				ativo INTEGER NOT NULL DEFAULT 1,
				created DATETIME NULL,
				modified DATETIME NULL
			)'
		);
		$conn->execute(
			'CREATE TABLE IF NOT EXISTS fiscal_notas_xmls (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				fiscal_nota_id INTEGER NOT NULL,
				tipo VARCHAR(30) NOT NULL,
				xml_envio TEXT NULL,
				xml_retorno TEXT NULL,
				created DATETIME NULL
			)'
		);
		$conn->execute(
			'CREATE TABLE IF NOT EXISTS fiscal_aliquotas (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				idempresa INTEGER NOT NULL,
				uf_origem VARCHAR(2) NOT NULL,
				uf_destino VARCHAR(2) NOT NULL,
				ncm_codigo VARCHAR(10) NULL,
				icms_aliquota REAL NULL,
				icms_reducao REAL NULL,
				icms_st_mva REAL NULL,
				ipi_aliquota REAL NULL,
				pis_aliquota REAL NULL,
				cofins_aliquota REAL NULL,
				iss_aliquota REAL NULL,
				created DATETIME NULL,
				modified DATETIME NULL
			)'
		);
	}

	protected static function rbacHttpSqliteTruncate(Connection $conn): void {
		$conn->execute('DELETE FROM rbac_roles_permissions');
		$conn->execute('DELETE FROM rbac_users_roles');
		$conn->execute('DELETE FROM rbac_permissions');
		$conn->execute('DELETE FROM rbac_roles');
		$conn->execute('DELETE FROM fiscal_notas_itens_series');
		$conn->execute('DELETE FROM fiscal_notas_itens');
		try {
			$conn->execute('DELETE FROM fiscal_notas_xmls');
		} catch (\Throwable $e) {
		}
		$conn->execute('DELETE FROM fiscal_dfe_recebidos');
		$conn->execute('DELETE FROM fiscal_notas');
		try {
			$conn->execute('DELETE FROM fiscal_natureza_operacao');
		} catch (\Throwable $e) {
		}
		try {
			$conn->execute('DELETE FROM fiscal_aliquotas');
		} catch (\Throwable $e) {
		}
		$conn->execute('DELETE FROM fiscal_certificados');
		$conn->execute('DELETE FROM fiscal_empresas_config');
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
