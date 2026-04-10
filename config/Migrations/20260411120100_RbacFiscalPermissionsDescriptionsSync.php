<?php
use Migrations\AbstractMigration;

/**
 * Alinha descrições fiscal.* em rbac_permissions ao catálogo config/permissions_registry.php
 * (instalações que já tinham corrido RbacFiscalModulePermissions com texto mais curto).
 */
class RbacFiscalPermissionsDescriptionsSync extends AbstractMigration {

    public function up() {
        if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
            return;
        }
        if (!$this->hasTable('rbac_permissions')) {
            return;
        }
        $this->execute("UPDATE rbac_permissions SET description = 'Dashboard fiscal, status SEFAZ, Distribuição DF-e (nacional), fila DF-e recebidos e consChNFe (baixar XML completo). O POST dfeRecebidoCriarEntrada exige também fiscal.notas_entrada quando o utilizador tem papéis RBAC.' WHERE code = 'fiscal.dashboard'");
        $this->execute("UPDATE rbac_permissions SET description = 'NF-e de saída, emissão, séries e inutilização de numeração (FiscalNotas).' WHERE code = 'fiscal.notas'");
        $this->execute("UPDATE rbac_permissions SET description = 'NF-e de entrada (compra), séries e inutilização (rota entrada).' WHERE code = 'fiscal.notas_entrada'");
    }

    public function down() {
        // Irreversível sem guardar texto anterior
    }
}
