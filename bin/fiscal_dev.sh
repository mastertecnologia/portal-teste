#!/usr/bin/env bash
# Desenvolvimento módulo fiscal: Phinx migrate + PHPUnit (suite fiscal + integração HTTP fiscal).
# Após o primeiro git add: git update-index --chmod=+x bin/fiscal_dev.sh (modo executável no índice).
# Requer PHP no PATH e composer install (vendor/autoload.php).
# Os testes HTTP fiscal também entram em `composer test-rbac`; este script agrupa tudo para smoke local.
#
# Uso:
#   bash bin/fiscal_dev.sh              # migrate + suite fiscal + HTTP fiscal
#   bash bin/fiscal_dev.sh migrate
#   bash bin/fiscal_dev.sh test
#   bash bin/fiscal_dev.sh test-http
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
ACTION="${1:-all}"

if [[ ! -f vendor/autoload.php ]]; then
	echo "Execute composer install no diretório do projeto." >&2
	exit 1
fi
if ! command -v php >/dev/null 2>&1; then
	echo "PHP não encontrado no PATH." >&2
	exit 1
fi
if [[ ! -f vendor/bin/phpunit ]]; then
	echo "vendor/bin/phpunit ausente. Execute composer install (com require-dev)." >&2
	exit 1
fi

run_migrate() {
	php bin/cake migrations migrate
}

run_test() {
	php vendor/bin/phpunit --colors=always --testsuite fiscal
}

run_test_http() {
	php vendor/bin/phpunit --colors=always --bootstrap tests/bootstrap_http.php \
		tests/TestCase/Integration/RbacFiscalHttpTest.php \
		tests/TestCase/Integration/RbacFiscalMoreHttpTest.php \
		tests/TestCase/Integration/RbacFiscalNotasHttpTest.php
}

case "$ACTION" in
	migrate) run_migrate ;;
	test) run_test ;;
	test-http) run_test_http ;;
	all)
		run_migrate
		run_test
		run_test_http
		;;
	*)
		echo "Uso: bash bin/fiscal_dev.sh [migrate|test|test-http|all]" >&2
		exit 1
		;;
esac

echo "fiscal_dev.sh ($ACTION): concluído com sucesso."
