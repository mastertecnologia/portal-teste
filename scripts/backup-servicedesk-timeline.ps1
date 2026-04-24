<#
  Backup reversível (Git) antes de aplicar migrations / deploy Service Desk Timeline B.
  - Com working tree limpo: cria tag anotada em HEAD.
  - Com alterações: avisa; use git stash -u ou commit antes de tag, ou -ForceTag para tag mesmo com sujo
    (a tag ainda NÃO inclui ficheiros não commitados; por isso o stash é o método seguro).
#>
param(
    [switch] $Stash,
    [switch] $ForceTag
)
$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..')
$ts = Get-Date -Format 'yyyyMMdd-HHmmss'
$tagName = "backup/Pre-Deploy-Servicedesk-TimelineB-$ts"

if ($Stash) {
    $msg = "WIP: Service Desk Timeline B (stash automático $ts)"
    git stash push -u -m $msg
    Write-Host "Stash criado. Recuperar: git stash list && git stash pop" -ForegroundColor Green
    exit 0
}

$dirty = git status --porcelain 2>$null
if ($dirty -and -not $ForceTag) {
    Write-Host "Working tree com alterações não commitadas." -ForegroundColor Yellow
    Write-Host "Opções: (1) git add / commit, (2) executar: .\scripts\backup-servicedesk-timeline.ps1 -Stash" -ForegroundColor Yellow
    Write-Host "ou (3) -ForceTag para marcar o último commit de mesma linha (ficheiros locais fora do tag)." -ForegroundColor Yellow
    exit 1
}

if ($dirty -and $ForceTag) {
    Write-Host "Atenção: existem ficheiros não commitados. A tag referencia só o último commit (HEAD), não o working copy." -ForegroundColor Yellow
}

git tag -a $tagName -m "Estado congelado do repositório (HEAD) para rollback. Reverter código: git checkout <este-commit-ou-HEAD-da-tag> ou reset conforme politica de equipa."
Write-Host "Tag criada: $tagName" -ForegroundColor Green
Write-Host "Ver: git show $tagName" -ForegroundColor Cyan
Write-Host "Lembrar: dump da base (pg_dump) separadamente em produção." -ForegroundColor Cyan
