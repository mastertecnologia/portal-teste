#!/usr/bin/env bash
# Testa senha de login sem expor na linha de comando (evita problemas com $ no printf).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
EMAIL="${1:-}"
if [[ -z "$EMAIL" ]]; then
	echo "Uso: bash scripts/sh/debug_login_password_check.sh \"email@exemplo.com\"" >&2
	exit 2
fi
read -rsp "Senha (não aparece): " CLI_LOGIN_PASSWORD
echo
if [[ -z "${CLI_LOGIN_PASSWORD}" ]]; then
	echo "Senha vazia — digite a senha e pressione Enter." >&2
	exit 2
fi
export CLI_LOGIN_PASSWORD
php "${ROOT}/scripts/debug_login_password_check.php" "$EMAIL"
unset CLI_LOGIN_PASSWORD
