#!/usr/bin/env bash
#
# Deploy do portal (produção): Git, Composer, bundle React dos tickets, permissões.
#
# Uso:
#   sudo ./scripts/deploy-portal.sh
#
# Variáveis opcionais:
#   DEPLOY_USER=www-data   usuário que roda git/composer/npm (padrão: www-data)
#   GIT_RESET=1            em vez de pull, faz fetch + reset --hard origin/main
#                          (apaga alterações locais não commitadas — use só se souber o que faz)
#
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEPLOY_USER="${DEPLOY_USER:-www-data}"
ROOT_Q=$(printf '%q' "$REPO_ROOT")

echo "Repositório: $REPO_ROOT"
echo "Usuário deploy: $DEPLOY_USER"

if [[ "${EUID:-0}" -ne 0 ]]; then
	echo "Execute como root, por exemplo: sudo $0" >&2
	exit 1
fi

if ! id "$DEPLOY_USER" &>/dev/null; then
	echo "Usuário não encontrado: $DEPLOY_USER" >&2
	exit 1
fi

mkdir -p /var/www/.npm
chown -R "$DEPLOY_USER:$DEPLOY_USER" /var/www/.npm

git_sync() {
	if [[ "${GIT_RESET:-0}" == "1" ]]; then
		echo "==> Git: fetch + reset --hard origin/main (GIT_RESET=1)"
		sudo -u "$DEPLOY_USER" -H bash -c "set -euo pipefail; cd $ROOT_Q && git fetch origin && git reset --hard origin/main"
	else
		echo "==> Git: fetch + pull origin main"
		sudo -u "$DEPLOY_USER" -H bash -c "set -euo pipefail; cd $ROOT_Q && git fetch origin && git pull origin main"
	fi
}

composer_install() {
	echo "==> Composer install"
	if sudo -u "$DEPLOY_USER" -H bash -c "command -v composer >/dev/null 2>&1"; then
		sudo -u "$DEPLOY_USER" -H bash -c "set -euo pipefail; cd $ROOT_Q && composer install --no-dev --optimize-autoloader --no-interaction"
	else
		echo "    Composer não está no PATH do usuário $DEPLOY_USER — etapa ignorada."
	fi
}

npm_tickets() {
	echo "==> npm ci + npm run build (dashboard-react)"
	sudo -u "$DEPLOY_USER" -H bash -c "set -euo pipefail; cd $ROOT_Q/dashboard-react && npm ci && npm run build"
}

fix_perms() {
	echo "==> Permissões: public/tickets-app, dashboard-react/node_modules, /var/www/.npm"
	chown -R "$DEPLOY_USER:$DEPLOY_USER" "$REPO_ROOT/public/tickets-app"
	if [[ -d "$REPO_ROOT/dashboard-react/node_modules" ]]; then
		chown -R "$DEPLOY_USER:$DEPLOY_USER" "$REPO_ROOT/dashboard-react/node_modules"
	fi
	chown -R "$DEPLOY_USER:$DEPLOY_USER" /var/www/.npm
}

git_sync
composer_install
npm_tickets
fix_perms

echo "OK — deploy concluído."
