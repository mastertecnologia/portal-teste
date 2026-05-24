<?php
use Migrations\AbstractMigration;

/**
 * Switchover UI premium por empresa (sobrescreve .env quando preenchido).
 */
class AddEmpresasPortalUi extends AbstractMigration {

    public function up() {
        if (!$this->hasTable('empresas')) {
            return;
        }
        $t = $this->table('empresas');
        if (!$t->hasColumn('portal_ui_mode')) {
            $t->addColumn('portal_ui_mode', 'string', [
                'limit' => 16,
                'null' => true,
                'default' => null,
                'comment' => 'legacy|premium|mixed; NULL = herdar PORTAL_UI_MODE',
            ])->update();
        }
        if (!$t->hasColumn('portal_ui_premium_modules')) {
            $t->addColumn('portal_ui_premium_modules', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'CSV módulos premium; NULL = herdar PORTAL_PREMIUM_MODULES',
            ])->update();
        }
    }

    public function down() {
        if (!$this->hasTable('empresas')) {
            return;
        }
        $t = $this->table('empresas');
        if ($t->hasColumn('portal_ui_premium_modules')) {
            $t->removeColumn('portal_ui_premium_modules')->update();
        }
        if ($t->hasColumn('portal_ui_mode')) {
            $t->removeColumn('portal_ui_mode')->update();
        }
    }
}
