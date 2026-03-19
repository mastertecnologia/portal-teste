#!/usr/bin/env bash
set -u

# Testa as integrações/API principais do portal para duas empresas.
# - Endpoints de leitura: espera 200.
# - Endpoints de escrita: envia payload inválido e espera 400 (teste não destrutivo).
#
# Uso:
#   EMPRESA1_TOKEN=... EMPRESA2_TOKEN=... ./scripts/test_api_integrations.sh
#
# Variáveis opcionais:
#   BASE_URL   (default: https://portal.pgm.inf.br/portal)
#   SITUACAO   (default: 4)
#   IGNORE_TLS (default: 1) -> 1 usa -k no curl
#
# Exemplo:
#   BASE_URL="https://portal.pgm.inf.br/portal" \
#   EMPRESA1_TOKEN="token_master" \
#   EMPRESA2_TOKEN="token_pgm" \
#   ./scripts/test_api_integrations.sh

BASE_URL="${BASE_URL:-https://portal.pgm.inf.br/portal}"
SITUACAO="${SITUACAO:-4}"
IGNORE_TLS="${IGNORE_TLS:-1}"

EMPRESA1_ID="${EMPRESA1_ID:-1}"
EMPRESA2_ID="${EMPRESA2_ID:-2}"
EMPRESA1_TOKEN="${EMPRESA1_TOKEN:-}"
EMPRESA2_TOKEN="${EMPRESA2_TOKEN:-}"

if [[ -z "$EMPRESA1_TOKEN" || -z "$EMPRESA2_TOKEN" ]]; then
  echo "ERRO: defina EMPRESA1_TOKEN e EMPRESA2_TOKEN antes de executar."
  echo "Exemplo:"
  echo "  EMPRESA1_TOKEN=... EMPRESA2_TOKEN=... ./scripts/test_api_integrations.sh"
  exit 2
fi

CURL_OPTS=(-sS --connect-timeout 15 --max-time 60)
if [[ "$IGNORE_TLS" == "1" ]]; then
  CURL_OPTS+=(-k)
fi

TOTAL=0
OK=0
FAIL=0

print_sep() {
  echo "------------------------------------------------------------"
}

http_request() {
  local method="$1"
  local url="$2"
  local body="${3:-}"

  local tmp_body
  tmp_body="$(mktemp)"
  local code

  if [[ "$method" == "GET" ]]; then
    code="$(curl "${CURL_OPTS[@]}" -X "$method" "$url" -H "Accept: application/json" -o "$tmp_body" -w "%{http_code}")"
  else
    code="$(curl "${CURL_OPTS[@]}" -X "$method" "$url" -H "Accept: application/json" -H "Content-Type: application/json" -d "$body" -o "$tmp_body" -w "%{http_code}")"
  fi

  echo "$code|$tmp_body"
}

assert_status() {
  local test_name="$1"
  local method="$2"
  local url="$3"
  local expected_csv="$4"
  local body="${5:-}"

  TOTAL=$((TOTAL + 1))
  local result
  result="$(http_request "$method" "$url" "$body")"
  local code="${result%%|*}"
  local body_file="${result#*|}"

  local pass=1
  IFS=',' read -r -a expected_arr <<< "$expected_csv"
  for exp in "${expected_arr[@]}"; do
    if [[ "$code" == "$exp" ]]; then
      pass=0
      break
    fi
  done

  if [[ $pass -eq 0 ]]; then
    OK=$((OK + 1))
    echo "[OK]   $test_name -> HTTP $code"
  else
    FAIL=$((FAIL + 1))
    echo "[FAIL] $test_name -> HTTP $code (esperado: $expected_csv)"
    echo "       URL: $url"
    echo "       Body (primeiros 300 chars):"
    head -c 300 "$body_file" | tr '\n' ' '; echo
  fi

  rm -f "$body_file"
}

run_company_suite() {
  local empresa="$1"
  local token="$2"

  print_sep
  echo "Testando empresa=$empresa"

  # Leitura (espera 200)
  assert_status \
    "Ordens listAPI (GET)" \
    "GET" \
    "${BASE_URL}/ordensservico/listAPI?empresa=${empresa}&token=${token}&situacao=${SITUACAO}" \
    "200"

  assert_status \
    "Clientes listAPI (GET)" \
    "GET" \
    "${BASE_URL}/clientes/listAPI?empresa=${empresa}&token=${token}" \
    "200"

  assert_status \
    "Produtos listAPI (GET)" \
    "GET" \
    "${BASE_URL}/produtos/listAPI?empresa=${empresa}&token=${token}" \
    "200"

  # Escrita não-destrutiva: payload inválido deve retornar 400 (não 401/405)
  assert_status \
    "Clientes addAPI (POST inválido, esperado 400)" \
    "POST" \
    "${BASE_URL}/clientes/addAPI?empresa=${empresa}&token=${token}" \
    "400" \
    "{}"

  assert_status \
    "Produtos addAPI (POST inválido, esperado 400)" \
    "POST" \
    "${BASE_URL}/produtos/addAPI?empresa=${empresa}&token=${token}" \
    "400" \
    "{}"

  assert_status \
    "Ordens refreshAPI (PUT inválido, esperado 400)" \
    "PUT" \
    "${BASE_URL}/ordensservico/refreshAPI?empresa=${empresa}&token=${token}" \
    "400" \
    "{}"
}

echo "BASE_URL=$BASE_URL"
echo "SITUACAO=$SITUACAO"
echo "IGNORE_TLS=$IGNORE_TLS"

run_company_suite "$EMPRESA1_ID" "$EMPRESA1_TOKEN"
run_company_suite "$EMPRESA2_ID" "$EMPRESA2_TOKEN"

print_sep
echo "Resumo:"
echo "  Total: $TOTAL"
echo "  OK:    $OK"
echo "  FAIL:  $FAIL"
print_sep

if [[ "$FAIL" -gt 0 ]]; then
  exit 1
fi

exit 0

