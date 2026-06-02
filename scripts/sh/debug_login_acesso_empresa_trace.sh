#!/usr/bin/env bash
# Diagnóstico completo: banco + fingerprint + HTTP local (uma única digitação de senha).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
EMAIL="${1:-}"
EXTRA=()
shift || true
while [[ $# -gt 0 ]]; do
	EXTRA+=("$1")
	shift
done
if [[ -z "$EMAIL" ]]; then
	echo "Uso: bash scripts/sh/debug_login_acesso_empresa_trace.sh \"email@exemplo.com\" [--no-http]" >&2
	exit 2
fi
read -rsp "Senha (a mesma que você usa no browser): " CLI_LOGIN_PASSWORD
echo
if [[ -z "${CLI_LOGIN_PASSWORD}" ]]; then
	echo "Senha vazia." >&2
	exit 2
fi
export CLI_LOGIN_PASSWORD
php "${ROOT}/scripts/debug_login_acesso_empresa_trace.php" "$EMAIL" "${EXTRA[@]}"
unset CLI_LOGIN_PASSWORD
