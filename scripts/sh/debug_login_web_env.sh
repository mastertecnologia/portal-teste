#!/usr/bin/env bash
# Verifica se o PHP web (www-data) consegue ler .env e se login-diag vê DB_PASSWORD.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"
ENV_FILE="${ROOT}/.env"
EMAIL="${1:-darli@mastertecnologia.inf.br}"

echo "=== debug_login_web_env ==="
echo "Projeto: ${ROOT}"
echo ""

if [[ ! -f "$ENV_FILE" ]]; then
	echo "ERRO: .env não encontrado em ${ENV_FILE}"
	exit 1
fi

ls -la "$ENV_FILE"
echo ""

for u in www-data apache root; do
	if id "$u" &>/dev/null; then
		if sudo -u "$u" test -r "$ENV_FILE" 2>/dev/null; then
			echo "Utilizador ${u}: pode LER .env"
		else
			echo "Utilizador ${u}: NÃO lê .env"
		fi
	fi
done
echo ""

ENC_EMAIL=$(php -r 'echo rawurlencode($argv[1]);' "$EMAIL")
echo "login-diag (contexto Apache/PHP-FPM):"
curl -sk -H 'Host: portal.pgm.inf.br' \
	"https://127.0.0.1/portal/users/login-diag?login=${ENC_EMAIL}"
echo ""
echo ""
echo "Correção típica se db_password_configured=false:"
echo "  chown root:www-data .env && chmod 640 .env"
echo "  systemctl restart php*-fpm apache2"
