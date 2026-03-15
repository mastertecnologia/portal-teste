#!/bin/bash
# Diagnóstico completo do portal no Linux (login, assets, Apache, CakePHP)
# Uso: sudo -u www-data bash scripts/diagnostico_portal_linux.sh
# Ou: cd /var/www/portal && bash scripts/diagnostico_portal_linux.sh

set -e
ROOT="${1:-/var/www/portal}"
cd "$ROOT" 2>/dev/null || { echo "Diretório $ROOT não encontrado."; exit 1; }

echo "=============================================="
echo "DIAGNÓSTICO PORTAL - $(date)"
echo "ROOT=$ROOT"
echo "=============================================="

echo ""
echo "--- 1. Últimas 30 linhas do log do CakePHP (error.log) ---"
if [ -f logs/error.log ]; then
    tail -30 logs/error.log
else
    echo "(arquivo logs/error.log não existe)"
fi

echo ""
echo "--- 2. Últimas 15 linhas do log SSL do Apache (portal) ---"
for log in /var/log/apache2/portal_error_ssl.log /var/log/apache2/portal-error.log; do
    if [ -f "$log" ]; then
        tail -15 "$log" 2>/dev/null && break
    fi
done
[ ! -f /var/log/apache2/portal_error_ssl.log ] && [ ! -f /var/log/apache2/portal-error.log ] && echo "(nenhum log portal encontrado em /var/log/apache2)"

echo ""
echo "--- 2b. Últimas 10 requisições no access log (portal) ---"
for log in /var/log/apache2/portal_access_ssl.log /var/log/apache2/portal-access.log; do
    if [ -f "$log" ]; then
        tail -10 "$log" 2>/dev/null && break
    fi
done

echo ""
echo "--- 3. Arquivos estáticos existem e permissões? ---"
for f in public/dist/css/style.min.css public/assets/node_modules/jquery/jquery-3.2.1.min.js public/assets/images/pgm.png public/plugins/bootbox/bootbox.min.js; do
    if [ -f "$f" ]; then
        ls -la "$f"
    else
        echo "AUSENTE: $f"
    fi
done

echo ""
echo "--- 4. .env (só APP_BASE e WEBROOT_DIR, sem senhas) ---"
if [ -f .env ]; then
    grep -E '^APP_BASE|^WEBROOT_DIR|^APP_DIR' .env 2>/dev/null || echo "(nenhuma dessas variáveis no .env)"
else
    echo "(.env não existe)"
fi

echo ""
echo "--- 5. config/app_local.php existe? (não mostrar conteúdo) ---"
ls -la config/app_local.php 2>/dev/null || echo "config/app_local.php não encontrado"

echo ""
echo "--- 6. Teste de URL estática (curl) ---"
echo "Rode manualmente no servidor (ou de outro PC):"
echo "  curl -sS -o /dev/null -w '%{http_code}' -H 'Host: portal.pgm.inf.br' http://127.0.0.1/portal/dist/css/style.min.css"
echo "  curl -sS -o /dev/null -w '%{http_code}' -k -H 'Host: portal.pgm.inf.br' https://127.0.0.1/portal/dist/css/style.min.css"
CODE=$(curl -sS -o /dev/null -w '%{http_code}' -H 'Host: portal.pgm.inf.br' "http://127.0.0.1/portal/dist/css/style.min.css" 2>/dev/null || echo "erro")
echo "  Resultado HTTP (porta 80): $CODE (200 = OK)"

echo ""
echo "--- 7. mod_rewrite habilitado? ---"
apache2ctl -M 2>/dev/null | grep rewrite || true

echo ""
echo "--- 8. VirtualHost portal (só Rewrite e Alias) ---"
grep -A2 "RewriteEngine\|RewriteCond\|RewriteRule\|Alias /portal" /etc/apache2/sites-enabled/portal.conf 2>/dev/null || \
grep -A2 "RewriteEngine\|RewriteCond\|RewriteRule\|Alias /portal" /etc/apache2/sites-enabled/portal*.conf 2>/dev/null || \
echo "(arquivo portal.conf não encontrado ou sem permissão)"

echo ""
echo "--- 9. Usuário e permissões de public/ ---"
ls -la public/ | head -5

echo ""
echo "=============================================="
echo "Fim do diagnóstico. Envie esta saída para análise."
echo "=============================================="
