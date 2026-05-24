#!/usr/bin/env bash
# Smoke: sintaxe PHP nos controllers *Prototype* e presença de redirects de bridge.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

PHP_BIN="${PHP_BIN:-php}"
if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
	echo "AVISO: php não encontrado; apenas checagem de padrões em grep."
	PHP_BIN=""
fi

FAIL=0

if [[ -n "$PHP_BIN" ]]; then
	echo "=== PHP lint (controllers *Prototype*) ==="
	while IFS= read -r f; do
		if ! "$PHP_BIN" -l "$f" >/dev/null 2>&1; then
			echo "ERRO sintaxe: $f"
			"$PHP_BIN" -l "$f" || true
			FAIL=1
		fi
	done < <(find src/Controller -name '*Prototype*.php' -type f | sort)
	echo "OK: sintaxe"
fi

echo "=== Redirects esperados (grep) ==="
check() {
	local label="$1"
	local pattern="$2"
	local file="$3"
	if grep -qE "$pattern" "$file" 2>/dev/null; then
		echo "OK  $label"
	else
		echo "FALHA $label ($file)"
		FAIL=1
	fi
}

check "Financeiro Faturamento" "Faturamento.*index" "src/Controller/FinanceiroPrototypeController.php"
check "Bancos contas→lista" "page === 'contas'" "src/Controller/BancosPrototypeController.php"
check "Orcamentos faturamento" "Faturamento.*index" "src/Controller/OrcamentosPrototypeController.php"
check "OS kanban" "page === 'kanban'" "src/Controller/OrdensservicoPrototypeController.php"
check "SD perm legado" "'perm'.*Permissoes" "src/Controller/ServicedeskPrototypeController.php"
check "Produtos novo→add" "Produtos.*add" "src/Controller/ProdutosPrototypeController.php"
check "Clientes novo→add" "Clientes.*add" "src/Controller/ClientesPrototypeController.php"
check "Sistema config" "Config.*index" "src/Controller/SistemaPrototypeController.php"
check "PortalUi legacy map" "'legacy_actions'" "config/portal_ui.php"
check "AppController switchover" "PortalUi::legacyRedirectRoute" "src/Controller/AppController.php"
check "Rotas v2 clientes" "/v2/clientes" "config/routes.php"

if [[ -f bin/audit_pgm_erp_mock.php ]] && [[ -n "$PHP_BIN" ]]; then
	echo "=== Auditoria mock ==="
	"$PHP_BIN" bin/audit_pgm_erp_mock.php || FAIL=1
fi

if [[ "$FAIL" -ne 0 ]]; then
	echo "verify_prototype_bridges: FALHOU"
	exit 1
fi
echo "verify_prototype_bridges: OK"
