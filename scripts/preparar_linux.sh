#!/bin/bash
#
# Prepara o portal no Linux após copiar os arquivos.
# Uso: na raiz do projeto (ex.: /var/www/portal):
#   chmod +x scripts/preparar_linux.sh
#   ./scripts/preparar_linux.sh
#
# Opcional: passar o usuário do servidor web (ex.: www-data)
#   ./scripts/preparar_linux.sh www-data
#
set -e
cd "$(dirname "$0")/.."
ROOT="$(pwd)"
WEB_USER="${1:-}"

echo "=== Preparando portal em $ROOT ==="

# 1. Criar diretórios necessários (tmp e logs podem estar no .gitignore)
for dir in tmp tmp/cache tmp/cache/models tmp/cache/persistent logs webroot/arquivos webroot/arquivos/tickets; do
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
        echo "  Criado: $dir"
    fi
done

# 2. Config local: criar a partir do exemplo se não existir
if [ ! -f config/app_local.php ]; then
    cp config/app_local_linux.example config/app_local.php
    echo "  Criado: config/app_local.php (edite SECURITY_SALT e DB se necessário)"
else
    echo "  config/app_local.php já existe"
fi

# 3. Permissões
if [ -n "$WEB_USER" ]; then
    echo "  Ajustando dono para $WEB_USER..."
    chown -R "$WEB_USER:$WEB_USER" tmp logs webroot/arquivos 2>/dev/null || true
fi
chmod -R 775 tmp logs webroot/arquivos 2>/dev/null || true
echo "  Permissões 775 em tmp, logs, webroot/arquivos"

# 4. Teste do ambiente
echo ""
echo "=== Executando teste do ambiente ==="
php scripts/test_ambiente_linux.php

echo ""
echo "Pronto. Para testar no navegador:"
echo "  php -S 0.0.0.0:8765 -t webroot"
echo "  Acesse: http://IP-DO-SERVIDOR:8765"
echo ""
