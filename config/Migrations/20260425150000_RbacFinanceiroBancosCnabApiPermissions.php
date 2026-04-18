<?php
use Migrations\AbstractMigration;

/**
 * RBAC: permissões das APIs CNAB do módulo Financeiro > Bancos.
 *
 * Objetivos:
 * - Inserir permissões para RemessasController e RetornosController.
 * - Herdar esses acessos para os mesmos papéis que já possuem `financeiro.bancos`.
 *
 * Observações:
 * - Mantém paridade com `config/permissions_registry.php`.
 * - Não remove permissões em produção no `down()`.
 */
class RbacFinanceiroBancosCnabApiPermissions extends AbstractMigration
{
    public function up()
    {
        if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
            return;
        }

        if (
            !$this->hasTable('rbac_permissions') ||
            !$this->hasTable('rbac_roles_permissions')
        ) {
            return;
        }

        $permissionsTable = $this->table('rbac_permissions');
        $hasTimestamps =
            $permissionsTable->hasColumn('created') &&
            $permissionsTable->hasColumn('modified');

        if ($hasTimestamps) {
            $this->execute(<<<'SQL'
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order, created, modified)
SELECT
    'financeiro.remessas_api',
    'Financeiro — API CNAB de remessas bancárias',
    'Financeiro',
    'Remessas',
    'listarTitulos,listar_titulos,gerarRemessa,gerar_remessa',
    'rbac',
    'empresa',
    'APIs JSON para listar títulos elegíveis e gerar arquivos CNAB de remessa por banco/empresa.',
    0,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1
    FROM rbac_permissions
    WHERE code = 'financeiro.remessas_api'
);

INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order, created, modified)
SELECT
    'financeiro.retornos_api',
    'Financeiro — API CNAB de retornos bancários',
    'Financeiro',
    'Retornos',
    'processar',
    'rbac',
    'empresa',
    'API JSON para upload e processamento de arquivos CNAB de retorno com baixa, rejeição e log operacional.',
    0,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1
    FROM rbac_permissions
    WHERE code = 'financeiro.retornos_api'
);
SQL
            );
        } else {
            $this->execute(<<<'SQL'
INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order)
SELECT
    'financeiro.remessas_api',
    'Financeiro — API CNAB de remessas bancárias',
    'Financeiro',
    'Remessas',
    'listarTitulos,listar_titulos,gerarRemessa,gerar_remessa',
    'rbac',
    'empresa',
    'APIs JSON para listar títulos elegíveis e gerar arquivos CNAB de remessa por banco/empresa.',
    0
WHERE NOT EXISTS (
    SELECT 1
    FROM rbac_permissions
    WHERE code = 'financeiro.remessas_api'
);

INSERT INTO rbac_permissions (code, name, module, controller, action, perm_type, abac_scope, description, sort_order)
SELECT
    'financeiro.retornos_api',
    'Financeiro — API CNAB de retornos bancários',
    'Financeiro',
    'Retornos',
    'processar',
    'rbac',
    'empresa',
    'API JSON para upload e processamento de arquivos CNAB de retorno com baixa, rejeição e log operacional.',
    0
WHERE NOT EXISTS (
    SELECT 1
    FROM rbac_permissions
    WHERE code = 'financeiro.retornos_api'
);
SQL
            );
        }

        if ($this->hasTable('rbac_roles') && $this->hasTable('rbac_users_roles')) {
            $this->execute(<<<'SQL'
INSERT INTO rbac_roles_permissions (role_id, permission_id)
SELECT DISTINCT rp_base.role_id, p_new.id
FROM rbac_roles_permissions rp_base
JOIN rbac_permissions p_base
    ON p_base.id = rp_base.permission_id
JOIN rbac_permissions p_new
    ON p_new.code IN ('financeiro.remessas_api', 'financeiro.retornos_api')
WHERE p_base.code = 'financeiro.bancos'
ON CONFLICT DO NOTHING
SQL
            );
        }
    }

    public function down()
    {
        // Não remove permissões nem vínculos em produção.
    }
}
