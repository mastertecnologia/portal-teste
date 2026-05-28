#!/usr/bin/env bash
#
# Ativa UI premium (mock pgm_erp_completo) no .env do servidor.
#
# Uso (na raiz do portal, ex. /var/www/portal):
#   bash scripts/enable-portal-premium-ui.sh
#   bash scripts/enable-portal-premium-ui.sh /var/www/portal/.env
#
# Variáveis opcionais:
#   PORTAL_PREMIUM_MODULES=clientes,orcamentos   (sobrescreve lista padrão)
#   PORTAL_UI_MODE=mixed|premium|legacy
#   SKIP_CACHE=1                                 (não roda bin/cake cache clear_all)
#
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${1:-$REPO_ROOT/.env}"

DEFAULT_MODULES="clientes,orcamentos,produtos,servicedesk,ordens,financeiro,bancos,fornecedores,home"
MODULES="${PORTAL_PREMIUM_MODULES:-$DEFAULT_MODULES}"
MODE="${PORTAL_UI_MODE:-mixed}"

if [[ ! -f "$ENV_FILE" ]]; then
	if [[ -f "$REPO_ROOT/.env.example" ]]; then
		echo "==> Criando $ENV_FILE a partir de .env.example"
		cp "$REPO_ROOT/.env.example" "$ENV_FILE"
	else
		echo "Arquivo não encontrado: $ENV_FILE" >&2
		exit 1
	fi
fi

upsert_env() {
	local key="$1"
	local value="$2"
	local file="$3"
	if grep -qE "^${key}=" "$file" 2>/dev/null; then
		local tmp
		tmp="$(mktemp)"
		sed -E "s|^${key}=.*|${key}=${value}|" "$file" >"$tmp"
		mv "$tmp" "$file"
	else
		printf '\n%s=%s\n' "$key" "$value" >>"$file"
	fi
}

echo "==> Atualizando $ENV_FILE"
upsert_env "PORTAL_UI_MODE" "$MODE" "$ENV_FILE"
upsert_env "PORTAL_PREMIUM_MODULES" "$MODULES" "$ENV_FILE"
upsert_env "PORTAL_ERP_PREMIUM_NAV" "1" "$ENV_FILE"

echo "    PORTAL_UI_MODE=$MODE"
echo "    PORTAL_PREMIUM_MODULES=$MODULES"
echo "    PORTAL_ERP_PREMIUM_NAV=1"

if [[ "${SKIP_CACHE:-0}" != "1" ]] && [[ -x "$REPO_ROOT/bin/cake" ]]; then
	echo "==> Limpando cache CakePHP"
	(cd "$REPO_ROOT" && php bin/cake.php cache clear_all 2>/dev/null) \
		|| (cd "$REPO_ROOT" && php bin/cake cache clear_all 2>/dev/null) \
		|| echo "    (cache clear ignorado — verifique PHP/bin/cake no servidor)"
fi

echo "==> Concluído. Faça logout/login e acesse / ou /users/dashboard (redireciona para /erp-home-prototype se home estiver na lista)."
