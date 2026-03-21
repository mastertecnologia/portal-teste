# Simula o Integrador GridERP -> Portal no Windows: POST /clientes/addAPI com JSON valido.
# Evita BodyParserMiddleware 400 causado por corpo vazio/encoding ao passar JSON grande em -d na linha de comando.
#
# Uso:
#   $env:INTEGRADOR_TOKEN = "token_da_empresa"
#   .\scripts\test_integrador_post_cliente.ps1
#
# Opcionais: $env:BASE_URL, $env:EMPRESA_ID, $env:CODIBGE, $env:CPF_CNPJ, $env:IGNORE_TLS=1

$ErrorActionPreference = "Stop"

$baseUrl = if ($env:BASE_URL) { $env:BASE_URL } else { "https://portal.pgm.inf.br/portal" }
$empresaId = if ($env:EMPRESA_ID) { $env:EMPRESA_ID } else { "2" }
$token = $env:INTEGRADOR_TOKEN
if (-not $token) { $token = $env:EMPRESA2_TOKEN }
if (-not $token) {
    Write-Error "Defina INTEGRADOR_TOKEN ou EMPRESA2_TOKEN (token da empresa no Portal)."
}

$codibge = if ($env:CODIBGE) { $env:CODIBGE } else { "3550308" }
$cpfCnpj = if ($env:CPF_CNPJ) { $env:CPF_CNPJ } else { "02673342060" }
$nome = if ($env:NOME_CLIENTE) { $env:NOME_CLIENTE } else { "LEONARDO DE VARGAS PELLEGRINI" }

$jsonBody = @"
{
  "cnpj": "$cpfCnpj",
  "nome": "$nome",
  "endereco": "Rua Teste Integrador",
  "nroendereco": "100",
  "bairro": "Centro",
  "cep": "01310100",
  "codibge": $codibge,
  "email": "integrador.teste@example.invalid",
  "inscest": "",
  "fantasia": "",
  "telefone": "1133334444",
  "celular": "11988887777",
  "contrato": false,
  "Servicos": []
}
"@

$tmp = [System.IO.Path]::Combine(
    [System.IO.Path]::GetTempPath(),
    "portal_integrador_" + [System.Guid]::NewGuid().ToString("N") + ".json"
)
try {
    $utf8NoBom = New-Object System.Text.UTF8Encoding $false
    [System.IO.File]::WriteAllText($tmp, $jsonBody.Trim(), $utf8NoBom)

    $url = "$baseUrl/clientes/addAPI"
    Write-Host "POST $url"
    Write-Host "Headers: empresa=$empresaId token=(oculto)"
    Write-Host "Body file: $tmp"
    Write-Host "------------------------------------------------------------"

    $curlArgs = @(
        "-sS", "-w", "`n--- HTTP %{http_code} ---`n",
        "--connect-timeout", "20", "--max-time", "90",
        "-X", "POST", $url,
        "-H", "Content-Type: application/json; charset=UTF-8",
        "-H", "Accept: application/json",
        "-H", "empresa: $empresaId",
        "-H", "token: $token",
        "--data-binary", "@$tmp"
    )
    if ($env:IGNORE_TLS -eq "1") {
        $curlArgs = @("-k") + $curlArgs
    }

    & curl.exe @curlArgs
    Write-Host ""
}
finally {
    if (Test-Path $tmp) { Remove-Item -LiteralPath $tmp -Force -ErrorAction SilentlyContinue }
}
