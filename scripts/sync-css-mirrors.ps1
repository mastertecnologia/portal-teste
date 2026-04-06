#Requires -Version 5.1
<#
.SYNOPSIS
  Fase 6 — alinha espelhos de CSS (public <-> webroot) documentados em docs/DESIGN_TOKENS_FASE1.md §8 e registo §10.1.

.DESCRIPTION
  - Espelha todo public/dist/css/pages/*.css para webroot/dist/css/pages/
  - Se vault-cofre, faturas-locacao-doc ou orcamentos-premium (css + dist) diferirem entre
    public e webroot, copia o ficheiro mais recente (LastWriteTimeUtc) para o outro.

.EXAMPLE
  pwsh -File scripts/sync-css-mirrors.ps1
#>
$ErrorActionPreference = 'Stop'
# Repositório = pasta pai de /scripts
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
if (-not (Test-Path (Join-Path $root 'public'))) {
	throw "Pasta 'public' não encontrada em $root"
}

$pubPages = Join-Path $root 'public\dist\css\pages'
$wwPages = Join-Path $root 'webroot\dist\css\pages'
if ((Test-Path $pubPages) -and (Test-Path $wwPages)) {
	Copy-Item (Join-Path $pubPages '*') $wwPages -Force
	Write-Host "OK: mirrored $pubPages -> $wwPages"
}

function Sync-PairIfDiff([string]$a, [string]$b) {
	if (-not ((Test-Path $a) -and (Test-Path $b))) { return }
	$ha = (Get-FileHash $a -Algorithm SHA256).Hash
	$hb = (Get-FileHash $b -Algorithm SHA256).Hash
	if ($ha -eq $hb) { return }
	$ta = (Get-Item $a).LastWriteTimeUtc
	$tb = (Get-Item $b).LastWriteTimeUtc
	if ($ta -ge $tb) {
		Copy-Item $a $b -Force
		Write-Host "OK: synced newer $a -> $b"
	} else {
		Copy-Item $b $a -Force
		Write-Host "OK: synced newer $b -> $a"
	}
}

$pairs = @(
	@((Join-Path $root 'public\css\vault-cofre.css'), (Join-Path $root 'webroot\css\vault-cofre.css')),
	@((Join-Path $root 'public\css\faturas-locacao-doc.css'), (Join-Path $root 'webroot\css\faturas-locacao-doc.css')),
	@((Join-Path $root 'public\css\orcamentos-premium.css'), (Join-Path $root 'webroot\css\orcamentos-premium.css')),
	@((Join-Path $root 'public\dist\css\orcamentos-premium.css'), (Join-Path $root 'webroot\dist\css\orcamentos-premium.css'))
)
foreach ($p in $pairs) { Sync-PairIfDiff $p[0] $p[1] }

Write-Host "Done."
