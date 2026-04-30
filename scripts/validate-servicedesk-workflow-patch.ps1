param(
  [switch]$quiet,
  [switch]$verbose
)
$ErrorActionPreference = "Stop"

# ============================================================
# Service Desk PATCH status/workflow validator (PowerShell)
# Fill COOKIE and ticket IDs before running.
# ============================================================

$HOST_BASE = "https://portal.pgm.inf.br/portal"
$COOKIE = "CAKEPHP=SEU_COOKIE"
$TICKET_ID = 1174
$TICKET_NULL_WORKFLOW = "<id>"
$TICKET_FECHADO = "<id>"
$TICKET_OUTRA_EMPRESA = "<id>"

$PatchBase = "$HOST_BASE/tickets"
$PassCount = 0
$FailCount = 0
$QuietMode = [bool]$quiet
$VerboseMode = [bool]$verbose

if (-not $QuietMode -and $COOKIE -eq "CAKEPHP=SEU_COOKIE") {
  Write-Host "[WARN] Atualize COOKIE no topo do script." -ForegroundColor Yellow
}
if (-not $QuietMode -and ($TICKET_NULL_WORKFLOW -eq "<id>" -or $TICKET_FECHADO -eq "<id>" -or $TICKET_OUTRA_EMPRESA -eq "<id>")) {
  Write-Host "[WARN] Atualize TICKET_NULL_WORKFLOW/TICKET_FECHADO/TICKET_OUTRA_EMPRESA." -ForegroundColor Yellow
}

function Format-JsonSafe {
  param([string]$Body)
  try {
    $obj = $Body | ConvertFrom-Json
    return ($obj | ConvertTo-Json -Depth 20)
  } catch {
    return $Body
  }
}

function Get-JsonField {
  param(
    [string]$Body,
    [string]$Field
  )
  try {
    $obj = $Body | ConvertFrom-Json
    return $obj.$Field
  } catch {
    return $null
  }
}

function Invoke-PatchStatus {
  param(
    [string]$TicketId,
    [string]$Payload
  )

  $uri = "$PatchBase/$TicketId/status"
  $tsStart = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
  if ($VerboseMode -and -not $QuietMode) {
    Write-Host "[VERBOSE][START] $tsStart"
    Write-Host "[VERBOSE][ENDPOINT] PATCH $uri"
    Write-Host "[VERBOSE][PAYLOAD] $Payload"
  }
  $tmp = New-TemporaryFile
  try {
    $code = & curl.exe -sS -X PATCH `
      -H "Content-Type: application/json" `
      -H "Accept: application/json" `
      -H "Cookie: $COOKIE" `
      --data $Payload `
      -o $tmp.FullName `
      -w "%{http_code}" `
      $uri
    $body = Get-Content -Path $tmp.FullName -Raw
    $tsEnd = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    if ($VerboseMode -and -not $QuietMode) {
      Write-Host "[VERBOSE][END] $tsEnd"
      Write-Host "[VERBOSE][HTTP] $code"
      Write-Host "[VERBOSE][JSON]"
      Write-Host (Format-JsonSafe $body)
    }
    return [PSCustomObject]@{
      HttpCode = [string]$code
      Body = [string]$body
    }
  } finally {
    Remove-Item -Path $tmp.FullName -Force -ErrorAction SilentlyContinue
  }
}

function Assert-Test {
  param(
    [string]$Name,
    [string]$ExpectedHttp,
    [string]$ExpectedError,
    $Result
  )
  if (-not $QuietMode) {
    Write-Host "------------------------------------------------------------"
    Write-Host "TESTE: $Name"
    Write-Host "HTTP: $($Result.HttpCode)"
    if (-not $VerboseMode) {
      Write-Host "JSON:"
      Write-Host (Format-JsonSafe $Result.Body)
    }
  }

  $ok = $false
  if ($Result.HttpCode -eq $ExpectedHttp) {
    if ([string]::IsNullOrWhiteSpace($ExpectedError)) {
      $ok = $true
    } else {
      $errField = [string](Get-JsonField -Body $Result.Body -Field "error")
      if ($errField -eq $ExpectedError) {
        $ok = $true
      }
    }
  }

  if ($ok) {
    if ($QuietMode) {
      Write-Host "$Name: PASS" -ForegroundColor Green
    } else {
      Write-Host "RESULTADO: PASS" -ForegroundColor Green
    }
    $script:PassCount++
  } else {
    if ($QuietMode) {
      Write-Host "$Name: FAIL" -ForegroundColor Red
    } else {
      Write-Host "RESULTADO: FAIL (esperado HTTP=$ExpectedHttp$(if($ExpectedError){", error=$ExpectedError"}) )" -ForegroundColor Red
    }
    $script:FailCount++
  }
}

if (-not $QuietMode) {
  Write-Host "Iniciando validação PATCH status/workflow..."
}

# 1) Status válido
$r1 = Invoke-PatchStatus -TicketId "$TICKET_ID" -Payload '{"status":"Em execução"}'
Assert-Test -Name "1. Status válido (Em execução)" -ExpectedHttp "200" -ExpectedError "" -Result $r1

# 2) Status pendente
$r2 = Invoke-PatchStatus -TicketId "$TICKET_ID" -Payload '{"status":"Pendente"}'
Assert-Test -Name "2. Status pendente" -ExpectedHttp "200" -ExpectedError "" -Result $r2

# 3) Status inválido
$r3 = Invoke-PatchStatus -TicketId "$TICKET_ID" -Payload '{"status":"XYZ"}'
Assert-Test -Name "3. Status inválido" -ExpectedHttp "422" -ExpectedError "invalid_situacao" -Result $r3

# 4) Transição inválida
$r4 = Invoke-PatchStatus -TicketId "$TICKET_FECHADO" -Payload '{"status":"Em execução"}'
Assert-Test -Name "4. Transição inválida (Fechado -> Em execução)" -ExpectedHttp "422" -ExpectedError "invalid_transition" -Result $r4

# 5) Bootstrap/fallback sem 500
if (-not $QuietMode) {
  Write-Host "------------------------------------------------------------"
  Write-Host "TESTE: 5. Bootstrap (workflow_state_id null)"
}
$r5 = Invoke-PatchStatus -TicketId "$TICKET_NULL_WORKFLOW" -Payload '{"status":"Em execução"}'
if (-not $QuietMode) {
  Write-Host "HTTP: $($r5.HttpCode)"
  if (-not $VerboseMode) {
    Write-Host "JSON:"
    Write-Host (Format-JsonSafe $r5.Body)
  }
}
if ($r5.HttpCode -ne "500") {
  if ($QuietMode) {
    Write-Host "5. Bootstrap (workflow_state_id null): PASS" -ForegroundColor Green
  } else {
    Write-Host "RESULTADO: PASS" -ForegroundColor Green
  }
  $PassCount++
} else {
  if ($QuietMode) {
    Write-Host "5. Bootstrap (workflow_state_id null): FAIL" -ForegroundColor Red
  } else {
    Write-Host "RESULTADO: FAIL (não pode retornar 500)" -ForegroundColor Red
  }
  $FailCount++
}

# 6) Permissão
$r6 = Invoke-PatchStatus -TicketId "$TICKET_OUTRA_EMPRESA" -Payload '{"status":"Pendente"}'
Assert-Test -Name "6. Permissão (outra empresa)" -ExpectedHttp "403" -ExpectedError "forbidden" -Result $r6

# 7) Concorrência
if (-not $QuietMode) {
  Write-Host "------------------------------------------------------------"
  Write-Host "TESTE: 7. Concorrência (Em execução x Pendente no mesmo ticket)"
}
$jobA = Start-Job -ScriptBlock {
  param($TicketId, $Cookie, $PatchBase)
  $tmp = New-TemporaryFile
  try {
    $code = & curl.exe -sS -X PATCH `
      -H "Content-Type: application/json" `
      -H "Accept: application/json" `
      -H "Cookie: $Cookie" `
      --data '{"status":"Em execução"}' `
      -o $tmp.FullName `
      -w "%{http_code}" `
      "$PatchBase/$TicketId/status"
    $body = Get-Content -Path $tmp.FullName -Raw
    [PSCustomObject]@{ HttpCode = [string]$code; Body = [string]$body }
  } finally {
    Remove-Item -Path $tmp.FullName -Force -ErrorAction SilentlyContinue
  }
} -ArgumentList "$TICKET_ID", "$COOKIE", "$PatchBase"

$jobB = Start-Job -ScriptBlock {
  param($TicketId, $Cookie, $PatchBase)
  $tmp = New-TemporaryFile
  try {
    $code = & curl.exe -sS -X PATCH `
      -H "Content-Type: application/json" `
      -H "Accept: application/json" `
      -H "Cookie: $Cookie" `
      --data '{"status":"Pendente"}' `
      -o $tmp.FullName `
      -w "%{http_code}" `
      "$PatchBase/$TicketId/status"
    $body = Get-Content -Path $tmp.FullName -Raw
    [PSCustomObject]@{ HttpCode = [string]$code; Body = [string]$body }
  } finally {
    Remove-Item -Path $tmp.FullName -Force -ErrorAction SilentlyContinue
  }
} -ArgumentList "$TICKET_ID", "$COOKIE", "$PatchBase"

Wait-Job -Job $jobA, $jobB | Out-Null
$ra = Receive-Job -Job $jobA
$rb = Receive-Job -Job $jobB
Remove-Job -Job $jobA, $jobB -Force

if (-not $QuietMode) {
  Write-Host "Resposta A: HTTP $($ra.HttpCode)"
  Write-Host (Format-JsonSafe $ra.Body)
  Write-Host "Resposta B: HTTP $($rb.HttpCode)"
  Write-Host (Format-JsonSafe $rb.Body)
}

$combined = "$($ra.Body)`n$($rb.Body)"
if ($ra.HttpCode -ne "500" -and $rb.HttpCode -ne "500" -and $combined -notmatch "25P02") {
  if ($QuietMode) {
    Write-Host "7. Concorrência (Em execução x Pendente no mesmo ticket): PASS" -ForegroundColor Green
  } else {
    Write-Host "RESULTADO: PASS" -ForegroundColor Green
  }
  $PassCount++
} else {
  if ($QuietMode) {
    Write-Host "7. Concorrência (Em execução x Pendente no mesmo ticket): FAIL" -ForegroundColor Red
  } else {
    Write-Host "RESULTADO: FAIL (houve 500 e/ou 25P02)" -ForegroundColor Red
  }
  $FailCount++
}

Write-Host "============================================================"
Write-Host "Resumo: PASS=$PassCount | FAIL=$FailCount"
if ($FailCount -gt 0) {
  exit 1
}
exit 0
