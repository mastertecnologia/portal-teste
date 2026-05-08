<?php
use Migrations\AbstractMigration;

class ContractsRequireReasonForCancelAndDelete extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql' || !$this->hasTable('contracts')) {
			return;
		}

		// Backfill legado para permitir criar CHECK sem quebrar dados existentes.
		$this->execute("
UPDATE contracts
SET motivo_cancelamento = 'Motivo não informado (registro legado).'
WHERE status = 'cancelado'
  AND btrim(coalesce(motivo_cancelamento, '')) = ''
");

		$this->execute('ALTER TABLE contracts DROP CONSTRAINT IF EXISTS chk_contracts_cancelado_motivo');
		$this->execute("
ALTER TABLE contracts
ADD CONSTRAINT chk_contracts_cancelado_motivo
CHECK (
	status <> 'cancelado'
	OR btrim(coalesce(motivo_cancelamento, '')) <> ''
)
");

		$this->execute("
CREATE OR REPLACE FUNCTION fn_contracts_require_reason_before_delete()
RETURNS trigger AS $$
BEGIN
	IF btrim(coalesce(OLD.motivo_cancelamento, '')) = '' THEN
		RAISE EXCEPTION 'Motivo de exclusão é obrigatório para remover contratos.';
	END IF;
	RETURN OLD;
END;
$$ LANGUAGE plpgsql
");
		$this->execute('DROP TRIGGER IF EXISTS trg_contracts_require_reason_before_delete ON contracts');
		$this->execute("
CREATE TRIGGER trg_contracts_require_reason_before_delete
BEFORE DELETE ON contracts
FOR EACH ROW
EXECUTE FUNCTION fn_contracts_require_reason_before_delete()
");
	}

	public function down() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql' || !$this->hasTable('contracts')) {
			return;
		}

		$this->execute('DROP TRIGGER IF EXISTS trg_contracts_require_reason_before_delete ON contracts');
		$this->execute('DROP FUNCTION IF EXISTS fn_contracts_require_reason_before_delete()');
		$this->execute('ALTER TABLE contracts DROP CONSTRAINT IF EXISTS chk_contracts_cancelado_motivo');
	}
}
