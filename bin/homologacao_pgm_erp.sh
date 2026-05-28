#!/usr/bin/env bash
# Homologação rápida: cobertura mock + bridges protótipo + lint PHP.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
FAIL=0

echo "=== Cobertura PGM ERP (Python) ==="
if command -v python3 >/dev/null 2>&1; then
	python3 bin/generate_pgm_erp_coverage.py || FAIL=1
else
	echo "AVISO: python3 ausente"
	FAIL=1
fi

echo "=== Bridges protótipo ==="
bash bin/verify_prototype_bridges.sh || FAIL=1

PHP_BIN=""
for c in php php8.2 php8.1 php8.0 php7.4; do
	if command -v "$c" >/dev/null 2>&1; then
		PHP_BIN="$c"
		break
	fi
done

if [[ -n "$PHP_BIN" ]]; then
	echo "=== Auditoria mock (PHP) ==="
	"$PHP_BIN" bin/audit_pgm_erp_mock.php || FAIL=1
	echo "=== Lint controllers *Prototype* ==="
	while IFS= read -r f; do
		if ! "$PHP_BIN" -l "$f" >/dev/null 2>&1; then
			echo "ERRO: $f"
			FAIL=1
		fi
	done < <(find src/Controller -name '*Prototype*.php' -o -name 'ErpHomePrototypeController.php' | sort)
else
	echo "AVISO: PHP não encontrado — pulando audit_pgm_erp_mock.php"
fi

if [[ -f docs/generated/pgm_erp_coverage_report.json ]]; then
	echo "=== Resumo coverage report ==="
	python3 - <<'PY' || true
import json
from pathlib import Path
p = Path("docs/generated/pgm_erp_coverage_report.json")
d = json.loads(p.read_text())
print("status:", d.get("status_counts"))
print("placeholders:", len(d.get("placeholder_screens", [])))
PY
fi

if [[ "$FAIL" -ne 0 ]]; then
	echo "homologacao_pgm_erp: FALHOU"
	exit 1
fi
echo "homologacao_pgm_erp: OK"
