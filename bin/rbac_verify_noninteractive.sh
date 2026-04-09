#!/usr/bin/env bash
# Verificação RBAC/ABAC sem prompts: PHPUnit (rbac + rbac-integration + rbac-http).
# Opcional: RBAC_RUN_PRE_DEPLOY=1 para correr bin/cake rbac_rollout pre_deploy (exige PostgreSQL configurado).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
if [[ ! -f vendor/autoload.php ]]; then
	echo "Execute composer install no diretório do projeto." >&2
	exit 1
fi
if ! command -v php >/dev/null 2>&1; then
	echo "PHP não encontrado no PATH." >&2
	exit 1
fi
php vendor/bin/phpunit --colors=always --testsuite rbac
php vendor/bin/phpunit --colors=always --testsuite rbac-integration
php vendor/bin/phpunit --colors=always --bootstrap tests/bootstrap_http.php --testsuite rbac-http
if [[ "${RBAC_RUN_PRE_DEPLOY:-}" == "1" ]]; then
	php bin/cake rbac_rollout pre_deploy
fi
echo "rbac_verify_noninteractive: concluído com sucesso."
