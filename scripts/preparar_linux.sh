#!/bin/bash
#
# Prepara o portal no Linux após copiar os arquivos.
# Estrutura: /var/www/portal com public/, config/, logs/, src/ (sem app).
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

# Pasta pública: public (estrutura Linux) ou webroot (legado)
if [ -f .env ] && grep -q '^WEBROOT_DIR=' .env 2>/dev/null; then
    WEBROOT_DIR=$(grep '^WEBROOT_DIR=' .env | cut -d= -f2 | tr -d '\r\n ')
else
    WEBROOT_DIR="public"
fi
[ -z "$WEBROOT_DIR" ] && WEBROOT_DIR="public"
# fallback se não existir public
[ ! -d "$WEBROOT_DIR" ] && [ -d "webroot" ] && WEBROOT_DIR="webroot"

echo "=== Preparando portal em $ROOT (WEBROOT=$WEBROOT_DIR) ==="

# 1. Criar diretórios necessários (tmp e logs podem estar no .gitignore)
for dir in tmp tmp/cache tmp/cache/models tmp/cache/persistent logs "$WEBROOT_DIR/arquivos" "$WEBROOT_DIR/arquivos/tickets"; do
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
    chown -R "$WEB_USER:$WEB_USER" tmp logs "$WEBROOT_DIR/arquivos" 2>/dev/null || true
fi
chmod -R 775 tmp logs "$WEBROOT_DIR/arquivos" 2>/dev/null || true
echo "  Permissões 775 em tmp, logs, $WEBROOT_DIR/arquivos"

# 4. Teste do ambiente
echo ""
echo "=== Executando teste do ambiente ==="
php scripts/test_ambiente_linux.php

echo ""
echo "Pronto. Para testar no navegador:"
echo "  php -S 0.0.0.0:8765 -t $WEBROOT_DIR"
echo "  Acesse: http://IP-DO-SERVIDOR:8765 (ou https:// se SSL estiver configurado)"
echo ""
