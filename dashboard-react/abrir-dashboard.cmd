@echo off
chcp 65001 >nul
cd /d "%~dp0"

where node >nul 2>&1
if errorlevel 1 (
  echo.
  echo [ERRO] Node.js nao encontrado. Instale em https://nodejs.org/ e abra de novo este arquivo.
  echo.
  pause
  exit /b 1
)

if not exist "node_modules\" (
  echo Instalando dependencias ^(npm install^)...
  call npm install
  if errorlevel 1 (
    pause
    exit /b 1
  )
)

echo.
echo Abrindo o dashboard em http://localhost:5173/
echo Feche esta janela para parar o servidor.
echo.
call npm run dev
pause
