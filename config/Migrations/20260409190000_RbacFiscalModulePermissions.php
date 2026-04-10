<?php
use Migrations\AbstractMigration;

/**
 * RBAC: permissões do módulo fiscal (paridade com config/permissions_registry.php).
 * Não atribui papéis — apenas insere linhas em rbac_permissions se ainda não existirem.
 */
class RbacFiscalModulePermissions extends AbstractMigration {

    public function up() {
        if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
            return;
        }
        if (!$this->hasTable('rbac_permissions')) {
            return;
        }

        $t = $this->table('rbac_permissions');
        $hasTs = $t->hasColumn('created') && $t->hasColumn('modified');

        if ($hasTs) {
            $this->execute(<<<'SQL'
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order, created, modified)
SELECT 'fiscal.dashboard', 'Fiscal — painel', 'Financeiro', 'Fiscal', '*', 'rbac', 'empresa', 'Dashboard fiscal, status SEFAZ, Distribuição DF-e (nacional), fila DF-e recebidos e consChNFe (baixar XML completo). O POST dfeRecebidoCriarEntrada exige também fiscal.notas_entrada quando o utilizador tem papéis RBAC.', 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'fiscal.dashboard');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order, created, modified)
SELECT 'fiscal.notas', 'Fiscal — notas de saída', 'Financeiro', 'FiscalNotas', '*', 'rbac', 'empresa', 'NF-e de saída, emissão, séries, inutilização de numeração e consultas SEFAZ (chave de acesso, cadastro CNPJ/IE) em FiscalNotas.', 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'fiscal.notas');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order, created, modified)
SELECT 'fiscal.notas_entrada', 'Fiscal — notas de entrada', 'Financeiro', 'FiscalNotasEntrada', '*', 'rbac', 'empresa', 'NF-e de entrada (compra), séries, inutilização e mesmas consultas SEFAZ na rota FiscalNotasEntrada.', 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'fiscal.notas_entrada');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order, created, modified)
SELECT 'fiscal.certificados', 'Fiscal — certificados digitais', 'Financeiro', 'FiscalCertificados', '*', 'rbac', 'empresa', 'A1/A3 e gestão de certificados por empresa.', 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'fiscal.certificados');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order, created, modified)
SELECT 'fiscal.config', 'Fiscal — configuração (CFOP, NCM, naturezas)', 'Financeiro', 'FiscalConfig', '*', 'rbac', 'empresa', 'Tabelas fiscais e alíquotas.', 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'fiscal.config');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order, created, modified)
SELECT 'fiscal.relatorios', 'Fiscal — relatórios', 'Financeiro', 'FiscalRelatorios', '*', 'rbac', 'empresa', 'Livros, resumos e busca por número de série.', 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'fiscal.relatorios');
SQL
            );
        } else {
            $this->execute(<<<'SQL'
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order)
SELECT 'fiscal.dashboard', 'Fiscal — painel', 'Financeiro', 'Fiscal', '*', 'rbac', 'empresa', 'Dashboard fiscal, status SEFAZ, Distribuição DF-e (nacional), fila DF-e recebidos e consChNFe (baixar XML completo). O POST dfeRecebidoCriarEntrada exige também fiscal.notas_entrada quando o utilizador tem papéis RBAC.', 0
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'fiscal.dashboard');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order)
SELECT 'fiscal.notas', 'Fiscal — notas de saída', 'Financeiro', 'FiscalNotas', '*', 'rbac', 'empresa', 'NF-e de saída, emissão, séries, inutilização de numeração e consultas SEFAZ (chave de acesso, cadastro CNPJ/IE) em FiscalNotas.', 0
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'fiscal.notas');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order)
SELECT 'fiscal.notas_entrada', 'Fiscal — notas de entrada', 'Financeiro', 'FiscalNotasEntrada', '*', 'rbac', 'empresa', 'NF-e de entrada (compra), séries, inutilização e mesmas consultas SEFAZ na rota FiscalNotasEntrada.', 0
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'fiscal.notas_entrada');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order)
SELECT 'fiscal.certificados', 'Fiscal — certificados digitais', 'Financeiro', 'FiscalCertificados', '*', 'rbac', 'empresa', 'A1/A3 e gestão de certificados por empresa.', 0
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'fiscal.certificados');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order)
SELECT 'fiscal.config', 'Fiscal — configuração (CFOP, NCM, naturezas)', 'Financeiro', 'FiscalConfig', '*', 'rbac', 'empresa', 'Tabelas fiscais e alíquotas.', 0
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'fiscal.config');
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order)
SELECT 'fiscal.relatorios', 'Fiscal — relatórios', 'Financeiro', 'FiscalRelatorios', '*', 'rbac', 'empresa', 'Livros, resumos e busca por número de série.', 0
WHERE NOT EXISTS (SELECT 1 FROM rbac_permissions WHERE code = 'fiscal.relatorios');
SQL
            );
        }
    }

    public function down() {
        // Não remove permissões em produção
    }
}
