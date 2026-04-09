# Verificação RBAC/ABAC sem prompts: PHPUnit (rbac + rbac-integration + rbac-http).
# Opcional: $env:RBAC_RUN_PRE_DEPLOY = "1" para correr `bin/cake rbac_rollout pre_deploy` (exige PostgreSQL configurado como em Cake).
# Uso: .\bin\rbac_verify_noninteractive.ps1
$ErrorActionPreference = "Stop"
$Root = Resolve-Path (Join-Path $PSScriptRoot "..")
Set-Location $Root
if (-not (Test-Path "vendor\autoload.php")) {
	Write-Error "Execute composer install no diretório do projeto."
}
$php = Get-Command php -ErrorAction SilentlyContinue
if (-not $php) {
	Write-Error "PHP não está no PATH (instale PHP 7.4+)."
}
& php vendor/bin/phpunit --colors=always --testsuite rbac
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
& php vendor/bin/phpunit --colors=always --testsuite rbac-integration
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
& php vendor/bin/phpunit --colors=always --bootstrap tests/bootstrap_http.php --testsuite rbac-http
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
if ($env:RBAC_RUN_PRE_DEPLOY -eq "1") {
	& php bin/cake rbac_rollout pre_deploy
	if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}
Write-Host "rbac_verify_noninteractive: concluído com sucesso."
