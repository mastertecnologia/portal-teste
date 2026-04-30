#!/usr/bin/env bash
# IAM/RBAC smoke: go_live_check + dry-run de expiração/notificação
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

./bin/cake rbac_go_live_check
c1=$?

./bin/cake rbac_access_expiry_notify --dry-run
c2=$?

./bin/cake rbac_access_expire --dry-run
c3=$?

code=0
(( c1 > code )) && code=$c1
(( c2 > code )) && code=$c2
(( c3 > code )) && code=$c3

exit "$code"
