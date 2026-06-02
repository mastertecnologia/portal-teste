#!/usr/bin/env bash
# Testa login HTTP POST como o browser (localhost, sem CDN). Não loga a senha.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
EMAIL="${1:-}"
if [[ -z "$EMAIL" ]]; then
	echo "Uso: bash scripts/sh/debug_login_http_post.sh \"email@exemplo.com\"" >&2
	exit 2
fi
read -rsp "Senha (mesma do browser): " CLI_LOGIN_PASSWORD
echo
if [[ -z "${CLI_LOGIN_PASSWORD}" ]]; then
	echo "Senha vazia." >&2
	exit 2
fi
export CLI_LOGIN_PASSWORD
COOKIE="$(mktemp)"
BODY="$(mktemp)"
trap 'rm -f "$COOKIE" "$BODY"' EXIT

BASE="http://127.0.0.1/portal/users/acesso-empresa"
CODE_GET=$(curl -sS -o "$BODY" -w '%{http_code}' -c "$COOKIE" -H 'Host: portal.pgm.inf.br' "$BASE" || true)
echo "GET login: HTTP $CODE_GET"

CODE_POST=$(curl -sS -o "$BODY" -w '%{http_code}' -b "$COOKIE" -c "$COOKIE" \
	-H 'Host: portal.pgm.inf.br' \
	-H 'Content-Type: application/x-www-form-urlencoded' \
	-X POST "$BASE" \
	--data-urlencode "username=${EMAIL}" \
	--data-urlencode "password=${CLI_LOGIN_PASSWORD}" \
	|| true)
echo "POST login: HTTP $CODE_POST"

if grep -qi 'usuário e/ou senha incorretos\|usuario e/ou senha incorretos' "$BODY" 2>/dev/null; then
	echo "RESULTADO: HTML com erro de credenciais (como no browser)."
elif grep -qi 'dashboard\|Location:.*dashboard' "$BODY" 2>/dev/null || [[ "$CODE_POST" == "302" ]]; then
	echo "RESULTADO: parece login OK (redirect/dashboard)."
else
	echo "RESULTADO: resposta ambígua — confira trecho:"
	head -c 400 "$BODY" | tr '\n' ' '
	echo ""
fi

unset CLI_LOGIN_PASSWORD
