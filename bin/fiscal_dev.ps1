# Desenvolvimento módulo fiscal: Phinx migrate + PHPUnit (suite fiscal + integração HTTP fiscal).
# Requer PHP no PATH e composer install (vendor/autoload.php).
# Uso:
#   .\bin\fiscal_dev.ps1              # migrate + testes fiscal + testes HTTP fiscal
#   .\bin\fiscal_dev.ps1 -Action migrate
#   .\bin\fiscal_dev.ps1 -Action test
#   .\bin\fiscal_dev.ps1 -Action test-http
# Em Linux/macOS: bash bin/fiscal_dev.sh [migrate|test|test-http|all]
param(
	[ValidateSet("migrate", "test", "test-http", "all")]
	[string]$Action = "all"
)
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
$hasPhpunit = Test-Path "vendor\bin\phpunit"
if (-not $hasPhpunit) {
	Write-Error "vendor\bin\phpunit ausente. Execute composer install (com require-dev)."
}

function Invoke-FiscalMigrate {
	& php bin/cake migrations migrate
	if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}

function Invoke-FiscalUnitTests {
	& php vendor/bin/phpunit --colors=always --testsuite fiscal
	if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}

function Invoke-FiscalHttpTests {
	$fiscalHttp = @(
		"tests/TestCase/Integration/RbacFiscalHttpTest.php",
		"tests/TestCase/Integration/RbacFiscalMoreHttpTest.php",
		"tests/TestCase/Integration/RbacFiscalNotasHttpTest.php"
	)
	& php vendor/bin/phpunit --colors=always --bootstrap tests/bootstrap_http.php @fiscalHttp
	if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}

switch ($Action) {
	"migrate" { Invoke-FiscalMigrate }
	"test" { Invoke-FiscalUnitTests }
	"test-http" { Invoke-FiscalHttpTests }
	"all" {
		Invoke-FiscalMigrate
		Invoke-FiscalUnitTests
		Invoke-FiscalHttpTests
	}
}
Write-Host "fiscal_dev.ps1 ($Action): concluído com sucesso."
