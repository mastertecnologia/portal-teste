#!/usr/bin/env bash
# Testa login HTTP POST como o browser (localhost HTTPS, sem passar pelo CDN externo).
set -euo pipefail
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
COOKIE="$(mktemp)"
BODY="$(mktemp)"
HDR="$(mktemp)"
trap 'rm -f "$COOKIE" "$BODY" "$HDR"' EXIT

HOST_HDR='Host: portal.pgm.inf.br'
# HTTP em 127.0.0.1 devolve 301 → usar HTTPS local (como o browser em produção).
BASE="https://127.0.0.1/portal/users/acesso-empresa"

CODE_GET=$(curl -sk -o "$BODY" -w '%{http_code}' -D "$HDR" -c "$COOKIE" -H "$HOST_HDR" "$BASE" || true)
echo "GET login: HTTP $CODE_GET"
LOC_GET=$(grep -i '^[Ll]ocation:' "$HDR" | tail -1 | tr -d '\r' || true)
[[ -n "$LOC_GET" ]] && echo "  Location: $LOC_GET"

CODE_POST=$(curl -sk -o "$BODY" -w '%{http_code}' -D "$HDR" -b "$COOKIE" -c "$COOKIE" \
	-H "$HOST_HDR" \
	-H 'Content-Type: application/x-www-form-urlencoded' \
	-X POST "$BASE" \
	--data-urlencode "username=${EMAIL}" \
	--data-urlencode "password=${CLI_LOGIN_PASSWORD}" \
	|| true)
echo "POST login: HTTP $CODE_POST"
LOC_POST=$(grep -i '^[Ll]ocation:' "$HDR" | tail -1 | tr -d '\r' || true)
[[ -n "$LOC_POST" ]] && echo "  Location: $LOC_POST"

if [[ "$CODE_POST" == "302" || "$CODE_POST" == "303" ]] && echo "$LOC_POST" | grep -qi 'dashboard'; then
	echo "RESULTADO: login OK (redirect para dashboard)."
elif grep -qi 'usuário e/ou senha incorretos\|usuario e/ou senha incorretos' "$BODY" 2>/dev/null; then
	echo "RESULTADO: credenciais rejeitadas (igual ao browser)."
	echo "Diagnóstico completo: bash scripts/sh/debug_login_acesso_empresa_trace.sh \"${EMAIL}\""
elif [[ "$CODE_POST" == "301" || "$CODE_POST" == "302" ]] && echo "$LOC_POST" | grep -qi 'https://'; then
	echo "RESULTADO: só redirect (verifique URL/HTTPS) — não é resposta de login."
else
	echo "RESULTADO: resposta ambígua (HTTP $CODE_POST). Trecho HTML:"
	head -c 500 "$BODY" | tr '\n' ' '
	echo ""
fi

unset CLI_LOGIN_PASSWORD
