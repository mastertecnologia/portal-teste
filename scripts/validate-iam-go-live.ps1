# Validação automatizada IAM/RBAC (cron + GO LIVE check). Requer PHP/Cake no servidor.
# Uso: .\scripts\validate-iam-go-live.ps1
$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $Root
$cake = Join-Path $Root "bin\cake.bat"

if (-not (Test-Path $cake)) {
	Write-Error "Não encontrado: $cake"

}



& $cake rbac_go_live_check
$e1 = $LASTEXITCODE




& $cake rbac_access_expiry_notify --dry-run
$e2 = $LASTEXITCODE



& $cake rbac_access_expire --dry-run
$e3 = $LASTEXITCODE




$code = [Math]::Max($e1, [Math]::Max($e2, $e3))



if ($code -ne 0) {
	exit $code
}





exit 0
