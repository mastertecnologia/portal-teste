#!/usr/bin/env bash
# Redefine senha de usuário ativo (servidor).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
EMAIL="${1:-}"
if [[ -z "$EMAIL" ]]; then
	echo "Uso: CONFIRM=yes bash scripts/sh/admin_set_user_password.sh \"email@exemplo.com\"" >&2
	exit 2
fi
if [[ "${CONFIRM:-}" != "yes" ]]; then
	echo "Defina CONFIRM=yes para executar." >&2
	exit 1
fi
read -rsp "Nova senha (não aparece): " CLI_LOGIN_PASSWORD
echo
if [[ -z "${CLI_LOGIN_PASSWORD}" ]]; then
	echo "Senha vazia." >&2
	exit 2
fi
export CLI_LOGIN_PASSWORD
php "${ROOT}/scripts/admin_set_user_password.php" "$EMAIL"
unset CLI_LOGIN_PASSWORD
