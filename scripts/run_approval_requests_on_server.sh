#!/usr/bin/env bash
#
# Rodar no servidor Portal (10.0.2.25), na raiz do repositório:
#   sudo -u www-data bash scripts/run_approval_requests_on_server.sh
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

echo "=== approval_requests — deploy servidor ==="
echo "ROOT=$ROOT"
echo "PHP=$(php -r 'echo PHP_VERSION;')"

if [[ ! -f vendor/autoload.php ]]; then
	echo "ERRO: vendor/ ausente — rode: composer install --no-dev" >&2
	exit 1
fi

php bin/verify_approval_requests_schema.php || {
	echo "Schema ausente — aplicando migration..."
	if php bin/cake.php migrations migrate 2>/dev/null; then
		echo "cake migrations migrate: OK"
	else
		php bin/apply_migration_approval_requests.php
	fi
}

php bin/verify_aprovacoes_stack.php

if php bin/cake.php migrations status 2>/dev/null | grep -q '20260519120000'; then
	php bin/cake.php migrations status 2>/dev/null | grep '20260519120000' || true
else
	echo "(Migrations plugin indisponível — schema verificado via PDO)"
fi

php bin/approval_requests_smoke.php
echo "Concluído."
