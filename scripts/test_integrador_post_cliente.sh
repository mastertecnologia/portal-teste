#!/usr/bin/env bash
set -u

# Simula o Integrador GridERP → Portal: POST /clientes/addAPI com JSON válido.
# Não commitar tokens: use variáveis de ambiente (iguais às do test_api_integrations.sh).
#
# Uso (empresa 2 = PGM, exemplo):
#   export BASE_URL="https://portal.pgm.inf.br/portal"
#   export EMPRESA_ID=2
#   export INTEGRADOR_TOKEN="cole_o_token_da_empresa_no_portal"
#   ./scripts/test_integrador_post_cliente.sh
#
# Opcionais:
#   CODIBGE     (default: 3550308 = São Paulo/SP — tem de existir em public.cidades)
#   CPF_CNPJ    (default: CPF do exemplo; vai no campo "cnpj" da API)
#   NOME_CLIENTE
#   IGNORE_TLS  (default: 1)

BASE_URL="${BASE_URL:-https://portal.pgm.inf.br/portal}"
EMPRESA_ID="${EMPRESA_ID:-2}"
INTEGRADOR_TOKEN="${INTEGRADOR_TOKEN:-${EMPRESA2_TOKEN:-}}"
CODIBGE="${CODIBGE:-3550308}"
CPF_CNPJ="${CPF_CNPJ:-02673342060}"
NOME_CLIENTE="${NOME_CLIENTE:-LEONARDO DE VARGAS PELLEGRINI}"
IGNORE_TLS="${IGNORE_TLS:-1}"

if [[ -z "$INTEGRADOR_TOKEN" ]]; then
  echo "ERRO: defina INTEGRADOR_TOKEN ou EMPRESA2_TOKEN (token da empresa no Portal)."
  echo "Exemplo: INTEGRADOR_TOKEN=\"...\" EMPRESA_ID=2 ./scripts/test_integrador_post_cliente.sh"
  exit 2
fi

CURL_OPTS=(-sS -w "\n--- HTTP %{http_code} ---\n" --connect-timeout 20 --max-time 90)
if [[ "$IGNORE_TLS" == "1" ]]; then
  CURL_OPTS+=(-k)
fi

# Payload alinhado com ClientesController::addAPI (cnpj = CPF ou CNPJ só dígitos; codibge obrigatório; Servicos pode ser []).
JSON_BODY=$(cat <<EOF
{
  "cnpj": "${CPF_CNPJ}",
  "nome": "${NOME_CLIENTE}",
  "endereco": "Rua Teste Integrador",
  "nroendereco": "100",
  "bairro": "Centro",
  "cep": "01310100",
  "codibge": ${CODIBGE},
  "email": "integrador.teste@example.invalid",
  "inscest": "",
  "fantasia": "",
  "telefone": "1133334444",
  "celular": "11988887777",
  "contrato": false,
  "Servicos": []
}
EOF
)

URL="${BASE_URL}/clientes/addAPI"

echo "POST ${URL}"
echo "Headers: empresa=${EMPRESA_ID} token=(oculto)"
echo "Body (codibge=${CODIBGE}):"
echo "$JSON_BODY" | head -c 400
echo ""
echo "------------------------------------------------------------"

curl "${CURL_OPTS[@]}" -X POST "$URL" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "empresa: ${EMPRESA_ID}" \
  -H "token: ${INTEGRADOR_TOKEN}" \
  -d "$JSON_BODY"

echo ""
