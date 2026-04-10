<?php
use Migrations\AbstractMigration;

/**
 * Alinha descrições fiscal.notas / fiscal.notas_entrada em rbac_permissions a permissions_registry.php
 * (texto expandido com consultas SEFAZ).
 */
class RbacFiscalNotasPermissionsDescriptionsExpand extends AbstractMigration {

    public function up() {
        if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
            return;
        }
        if (!$this->hasTable('rbac_permissions')) {
            return;
        }
        $this->execute("UPDATE rbac_permissions SET description = 'NF-e de saída, emissão, séries, inutilização de numeração e consultas SEFAZ (chave de acesso, cadastro CNPJ/IE) em FiscalNotas.' WHERE code = 'fiscal.notas'");
        $this->execute("UPDATE rbac_permissions SET description = 'NF-e de entrada (compra), séries, inutilização e mesmas consultas SEFAZ na rota FiscalNotasEntrada.' WHERE code = 'fiscal.notas_entrada'");
    }

    public function down() {
    }
}
