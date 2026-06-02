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

DEFAULT_MODULES="clientes,orcamentos,produtos,servicedesk,ordens,financeiro,bancos,fornecedores,licencas,home"
MODULES="${PORTAL_PREMIUM_MODULES:-$DEFAULT_MODULES}"
MODE="${PORTAL_UI_MODE:-mixed}"

if [[ ! -f "$ENV_FILE" ]]; then
	echo "ERRO: $ENV_FILE não existe." >&2
	echo "Não copiamos .env.example automaticamente (DB_* ficaria sem valor e o Cake usaria localhost)." >&2
	echo "Restaure o .env de backup ou crie com DB_HOST=10.0.2.23 (ver docs/INFRAESTRUTURA_SERVIDORES.md)." >&2
	exit 1
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

if ! grep -qE '^DB_HOST=' "$ENV_FILE" 2>/dev/null; then
	echo "" >&2
	echo "AVISO: $ENV_FILE não define DB_HOST= — o portal pode tentar localhost." >&2
	echo "Adicione: DB_HOST=10.0.2.23 (e DB_USERNAME, DB_PASSWORD, DB_DATABASE)." >&2
fi

# PHP-FPM (www-data) precisa ler o .env; root-only quebra o site no browser.
if [[ -n "${FIX_ENV_PERMS:-}" ]] || [[ ! -r "$ENV_FILE" ]]; then
	:
elif command -v stat >/dev/null 2>&1; then
	perms="$(stat -c '%a' "$ENV_FILE" 2>/dev/null || true)"
	if [[ "$perms" == "600" ]] || [[ "$perms" == "400" ]]; then
		echo "" >&2
		echo "AVISO: .env com permissão $perms — se o site falhar e 'php bin/check_db_env' (root) passar," >&2
		echo "execute: chown root:www-data $ENV_FILE && chmod 640 $ENV_FILE" >&2
		echo "Teste como FPM: sudo -u www-data php bin/check_db_env.php" >&2
	fi
fi

echo "==> Concluído. Faça logout/login e acesse / ou /users/dashboard (redireciona para /erp-home-prototype se home estiver na lista)."
