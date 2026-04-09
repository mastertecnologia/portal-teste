#!/usr/bin/env bash
# Verificação RBAC/ABAC sem prompts: PHPUnit (rbac + rbac-integration + rbac-http).
# Opcional: RBAC_RUN_PRE_DEPLOY=1 para correr bin/cake rbac_rollout pre_deploy (exige PostgreSQL configurado).
#
# Em produção costuma-se usar `composer install --no-dev`, que NÃO instala phpunit — as suites são saltadas
# com aviso; use RBAC_RUN_PRE_DEPLOY=1 para validar só o pre_deploy, ou `composer install` (com dev) numa máquina de CI/staging.
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

HAS_PHPUNIT=0
if [[ -f vendor/bin/phpunit ]]; then
	HAS_PHPUNIT=1
else
	echo "Aviso: vendor/bin/phpunit ausente (típico com composer install --no-dev). A saltar suites PHPUnit." >&2
fi

if [[ "$HAS_PHPUNIT" -eq 1 ]]; then
	php vendor/bin/phpunit --colors=always --testsuite rbac
	php vendor/bin/phpunit --colors=always --testsuite rbac-integration
	php vendor/bin/phpunit --colors=always --bootstrap tests/bootstrap_http.php --testsuite rbac-http
fi

if [[ "${RBAC_RUN_PRE_DEPLOY:-}" == "1" ]]; then
	php bin/cake rbac_rollout pre_deploy
fi

if [[ "$HAS_PHPUNIT" -eq 0 && "${RBAC_RUN_PRE_DEPLOY:-}" != "1" ]]; then
	echo "Erro: sem PHPUnit e RBAC_RUN_PRE_DEPLOY não está definido — nada a executar." >&2
	echo "  Produção: RBAC_RUN_PRE_DEPLOY=1 bash bin/rbac_verify_noninteractive.sh" >&2
	echo "  CI/dev:     composer install && bash bin/rbac_verify_noninteractive.sh" >&2
	exit 1
fi

echo "rbac_verify_noninteractive: concluído com sucesso."
