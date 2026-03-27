# Hardening de Producao - Portal (CakePHP)

Este documento consolida um baseline de seguranca e operacao para ambiente de producao do Portal.

Objetivos:
- reduzir risco operacional e de seguranca;
- padronizar deploy e rollback;
- facilitar auditoria e manutencao.

---

## 1) Dependencias e Composer

### 1.1 Nao executar Composer como root

Sempre que possivel, use um usuario de deploy (ex.: `deploy`) ou o usuario do servico web.

### 1.2 Instalar dependencias em producao

```bash
composer install --no-dev --optimize-autoloader --classmap-authoritative
```

### 1.3 Auditoria de vulnerabilidades

```bash
composer audit
```

Tratar por prioridade:
1. Criticas/Altas
2. Medias
3. Baixas

### 1.4 Observacao do projeto

O aviso sobre `cakephp/plugin-installer` legado deve ser tratado em plano de atualizacao da stack para compatibilidade futura com Composer 2.x.

---

## 2) Configuracao de PHP (producao)

No `php.ini` de producao:

```ini
display_errors = Off
log_errors = On
expose_php = Off
```

Tambem revisar:
- `date.timezone` (de acordo com o ambiente);
- `memory_limit`;
- `max_execution_time`;
- `post_max_size` e `upload_max_filesize` conforme necessidade real.

---

## 3) Permissoes de arquivos e pastas

Recomendacao geral:
- `tmp/` e `logs/`: escrita para usuario do webserver;
- `src/`, `config/`, `vendor/`: leitura para runtime;
- evitar `chmod 777`.

Exemplo (Ubuntu):

```bash
chown -R www-data:www-data /var/www/portal/tmp /var/www/portal/logs
find /var/www/portal/tmp -type d -exec chmod 775 {} \;
find /var/www/portal/logs -type d -exec chmod 775 {} \;
find /var/www/portal/tmp -type f -exec chmod 664 {} \;
find /var/www/portal/logs -type f -exec chmod 664 {} \;
```

---

## 4) Web server (Nginx/Apache)

### 4.1 Bloquear arquivos sensiveis

Bloquear acesso externo a:
- `.env`
- `composer.json`
- `composer.lock`
- backups (`*.sql`, `*.bak`, etc.)

### 4.2 HTTPS e headers de seguranca

Aplicar:
- TLS 1.2+
- redirect HTTP -> HTTPS
- HSTS (quando dominio estiver validado)
- headers:
  - `X-Frame-Options: SAMEORIGIN`
  - `X-Content-Type-Options: nosniff`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Content-Security-Policy` (iniciar em report-only)

---

## 5) Sessao e autenticacao

Garantir cookies de sessao com:
- `Secure`
- `HttpOnly`
- `SameSite=Lax` (ou `Strict` quando viavel)

Boas praticas:
- rotacionar sessao no login;
- timeout de sessao compativel com criticidade da operacao.

---

## 6) Logs, monitoracao e alertas

Monitorar:
- erros 5xx em volume;
- falhas SOAP de integracao ERP;
- falhas na geracao de PDF (mPDF).

Nunca logar:
- senha;
- tokens;
- dados sensiveis sem mascaramento.

---

## 7) Backup e rollback

Antes de deploy:
1. backup de banco;
2. backup de arquivos criticos/config;
3. validar procedimento de rollback.

Rollback de codigo (sem reescrever historico):

```bash
git revert <commit>
git push
```

Rollback local de teste (nao recomendado apos push em branch compartilhada):

```bash
git reset --soft HEAD~1
# ou
git reset HEAD~1
```

---

## 8) Checklist rapido pre-deploy

- [ ] `composer install --no-dev` executado
- [ ] `composer audit` revisado
- [ ] migracoes/rotas validadas
- [ ] permissao de `tmp/` e `logs/` ok
- [ ] teste funcional: estoque, impressao, PDF atual e PDF completo
- [ ] monitoramento ativo apos deploy

---

## 9) Checklist rapido pos-deploy (modulo Estoque)

- [ ] Tela `Produtos > Estoque` abre sem erro
- [ ] Botao **Imprimir** funciona
- [ ] Botao **PDF atual** gera arquivo com filtros atuais
- [ ] Botao **PDF completo** gera arquivo com todos os produtos
- [ ] Logs sem erro de `Mpdf`/`SoapFault`

---

## 10) Comandos uteis (Ubuntu)

```bash
cd /var/www/portal
git pull
composer install --no-dev --optimize-autoloader --classmap-authoritative
composer audit
```

Verificacao mPDF:

```bash
composer show mpdf/mpdf
```

---

## 11) Responsabilidades

- Time de aplicacao: codigo, validacoes funcionais e rollback de app.
- Time de infraestrutura: TLS, firewall, backups e observabilidade.
- Time de operacao: execucao do checklist e registro de mudancas.

