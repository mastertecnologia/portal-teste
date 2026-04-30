#!/usr/bin/env bash
set -u

# ============================================================
# Service Desk PATCH status/workflow validator (Bash + curl)
# Fill COOKIE and ticket IDs before running.
# ============================================================

HOST="https://portal.pgm.inf.br/portal"
COOKIE="CAKEPHP=SEU_COOKIE"
TICKET_ID=1174
TICKET_NULL_WORKFLOW=<id>
TICKET_FECHADO=<id>
TICKET_OUTRA_EMPRESA=<id>

PATCH_URL="${HOST}/tickets"
QUIET=0
VERBOSE=0

for arg in "$@"; do
  case "$arg" in
    --quiet) QUIET=1 ;;
    --verbose) VERBOSE=1 ;;
    *) ;;
  esac
done

if [[ "$COOKIE" == "CAKEPHP=SEU_COOKIE" && $QUIET -eq 0 ]]; then
  echo "[WARN] Atualize COOKIE no topo do script."
fi

if [[ $QUIET -eq 0 && ("${TICKET_NULL_WORKFLOW}" == "<id>" || "${TICKET_FECHADO}" == "<id>" || "${TICKET_OUTRA_EMPRESA}" == "<id>") ]]; then
  echo "[WARN] Atualize TICKET_NULL_WORKFLOW/TICKET_FECHADO/TICKET_OUTRA_EMPRESA."
fi

HAVE_JQ=0
if command -v jq >/dev/null 2>&1; then
  HAVE_JQ=1
fi

pass_count=0
fail_count=0

print_json() {
  local body="$1"
  if [[ $HAVE_JQ -eq 1 ]]; then
    printf '%s' "$body" | jq . 2>/dev/null || printf '%s\n' "$body"
  else
    printf '%s\n' "$body"
  fi
}

log_line() {
  if [[ $QUIET -eq 0 ]]; then
    echo "$1"
  fi
}

extract_error() {
  local body="$1"
  if [[ $HAVE_JQ -eq 1 ]]; then
    printf '%s' "$body" | jq -r '.error // ""' 2>/dev/null
  else
    printf '%s' "$body" | sed -n 's/.*"error"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' | head -n 1
  fi
}

extract_ok() {
  local body="$1"
  if [[ $HAVE_JQ -eq 1 ]]; then
    printf '%s' "$body" | jq -r '.ok // empty' 2>/dev/null
  else
    printf '%s' "$body" | sed -n 's/.*"ok"[[:space:]]*:[[:space:]]*\(true\|false\).*/\1/p' | head -n 1
  fi
}

request_patch() {
  local ticket_id="$1"
  local payload="$2"
  local endpoint="${PATCH_URL}/${ticket_id}/status"
  local ts_start ts_end
  ts_start="$(date '+%Y-%m-%d %H:%M:%S')"
  if [[ $VERBOSE -eq 1 && $QUIET -eq 0 ]]; then
    echo "[VERBOSE][START] ${ts_start}"
    echo "[VERBOSE][ENDPOINT] PATCH ${endpoint}"
    echo "[VERBOSE][PAYLOAD] ${payload}"
  fi
  local tmp_body
  tmp_body="$(mktemp)"
  local http_code
  http_code=$(curl -sS -X PATCH \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -H "Cookie: ${COOKIE}" \
    --data "$payload" \
    -o "$tmp_body" \
    -w "%{http_code}" \
    "$endpoint")
  local body
  body="$(cat "$tmp_body")"
  rm -f "$tmp_body"
  ts_end="$(date '+%Y-%m-%d %H:%M:%S')"
  if [[ $VERBOSE -eq 1 && $QUIET -eq 0 ]]; then
    echo "[VERBOSE][END] ${ts_end}"
    echo "[VERBOSE][HTTP] ${http_code}"
    echo "[VERBOSE][JSON]"
    print_json "$body"
  fi
  printf '%s\n%s\n' "$http_code" "$body"
}

assert_test() {
  local name="$1"
  local http_code="$2"
  local body="$3"
  local expected_http="$4"
  local expected_error="${5:-}"

  if [[ $QUIET -eq 0 ]]; then
    echo "------------------------------------------------------------"
    echo "TESTE: ${name}"
    echo "HTTP: ${http_code}"
    if [[ $VERBOSE -eq 0 ]]; then
      echo "JSON:"
      print_json "$body"
    fi
  fi

  local ok=0
  if [[ "$http_code" == "$expected_http" ]]; then
    if [[ -n "$expected_error" ]]; then
      local got_error
      got_error="$(extract_error "$body")"
      if [[ "$got_error" == "$expected_error" ]]; then
        ok=1
      fi
    else
      ok=1
    fi
  fi

  if [[ $ok -eq 1 ]]; then
    if [[ $QUIET -eq 1 ]]; then
      echo "${name}: PASS"
    else
      echo "RESULTADO: PASS"
    fi
    pass_count=$((pass_count + 1))
  else
    if [[ $QUIET -eq 1 ]]; then
      echo "${name}: FAIL"
    else
      echo "RESULTADO: FAIL (esperado HTTP=${expected_http}${expected_error:+, error=${expected_error}})"
    fi
    fail_count=$((fail_count + 1))
  fi
}

run_test() {
  local name="$1"
  local ticket_id="$2"
  local payload="$3"
  local expected_http="$4"
  local expected_error="${5:-}"
  local result
  result="$(request_patch "$ticket_id" "$payload")"
  local http_code body
  http_code="$(printf '%s' "$result" | sed -n '1p')"
  body="$(printf '%s' "$result" | sed -n '2,$p')"
  assert_test "$name" "$http_code" "$body" "$expected_http" "$expected_error"
}

log_line "Iniciando validação PATCH status/workflow..."

# 1) Status válido
run_test "1. Status válido (Em execução)" "$TICKET_ID" '{"status":"Em execução"}' "200"

# 2) Status pendente
run_test "2. Status pendente" "$TICKET_ID" '{"status":"Pendente"}' "200"

# 3) Status inválido -> 422 invalid_situacao
run_test "3. Status inválido" "$TICKET_ID" '{"status":"XYZ"}' "422" "invalid_situacao"

# 4) Transição inválida -> 422 invalid_transition
run_test "4. Transição inválida (Fechado -> Em execução)" "$TICKET_FECHADO" '{"status":"Em execução"}' "422" "invalid_transition"

# 5) Bootstrap/fallback sem 500
if [[ $QUIET -eq 0 ]]; then
  echo "------------------------------------------------------------"
  echo "TESTE: 5. Bootstrap (workflow_state_id null)"
fi
res5="$(request_patch "$TICKET_NULL_WORKFLOW" '{"status":"Em execução"}')"
http5="$(printf '%s' "$res5" | sed -n '1p')"
body5="$(printf '%s' "$res5" | sed -n '2,$p')"
if [[ $QUIET -eq 0 ]]; then
  echo "HTTP: $http5"
  if [[ $VERBOSE -eq 0 ]]; then
    echo "JSON:"
    print_json "$body5"
  fi
fi
if [[ "$http5" != "500" ]]; then
  if [[ $QUIET -eq 1 ]]; then
    echo "5. Bootstrap (workflow_state_id null): PASS"
  else
    echo "RESULTADO: PASS"
  fi
  pass_count=$((pass_count + 1))
else
  if [[ $QUIET -eq 1 ]]; then
    echo "5. Bootstrap (workflow_state_id null): FAIL"
  else
    echo "RESULTADO: FAIL (não pode retornar 500)"
  fi
  fail_count=$((fail_count + 1))
fi

# 6) Permissão -> 403 forbidden
run_test "6. Permissão (outra empresa)" "$TICKET_OUTRA_EMPRESA" '{"status":"Pendente"}' "403" "forbidden"

# 7) Concorrência (quase simultâneo)
if [[ $QUIET -eq 0 ]]; then
  echo "------------------------------------------------------------"
  echo "TESTE: 7. Concorrência (Em execução x Pendente no mesmo ticket)"
fi
tmp_a="$(mktemp)"
tmp_b="$(mktemp)"

(
  request_patch "$TICKET_ID" '{"status":"Em execução"}' > "$tmp_a"
) &
pid_a=$!
(
  request_patch "$TICKET_ID" '{"status":"Pendente"}' > "$tmp_b"
) &
pid_b=$!

wait "$pid_a"
wait "$pid_b"

http_a="$(sed -n '1p' "$tmp_a")"
body_a="$(sed -n '2,$p' "$tmp_a")"
http_b="$(sed -n '1p' "$tmp_b")"
body_b="$(sed -n '2,$p' "$tmp_b")"
rm -f "$tmp_a" "$tmp_b"

if [[ $QUIET -eq 0 ]]; then
  echo "Resposta A: HTTP $http_a"
  print_json "$body_a"
  echo "Resposta B: HTTP $http_b"
  print_json "$body_b"
fi

combined="$(printf '%s\n%s' "$body_a" "$body_b")"
if [[ "$http_a" != "500" && "$http_b" != "500" && "$combined" != *"25P02"* ]]; then
  if [[ $QUIET -eq 1 ]]; then
    echo "7. Concorrência (Em execução x Pendente no mesmo ticket): PASS"
  else
    echo "RESULTADO: PASS"
  fi
  pass_count=$((pass_count + 1))
else
  if [[ $QUIET -eq 1 ]]; then
    echo "7. Concorrência (Em execução x Pendente no mesmo ticket): FAIL"
  else
    echo "RESULTADO: FAIL (houve 500 e/ou 25P02)"
  fi
  fail_count=$((fail_count + 1))
fi

echo "============================================================"
echo "Resumo: PASS=${pass_count} | FAIL=${fail_count}"
if [[ $fail_count -gt 0 ]]; then
  exit 1
fi
exit 0
