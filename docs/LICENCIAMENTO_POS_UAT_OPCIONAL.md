# Licenciamento — verificação pós-UAT (opcional)

Use este guia **depois** de concluir o checklist em `docs/LICENCIAMENTO_HOMOLOGACAO_UAT.md` (equipe + portal).

Pré-requisito CLI:

```bash
bin/cake licencas uat_check --idempresa=1 --strict
```

---

## 1. `LIC_COFRE_CIPHER_KEY` (recomendado em produção)

### O que faz

- **Com chave:** novos segredos do cofre gravam com prefixo `gcm:` (AES-256-GCM).
- **Sem chave:** gravação com prefixo `b64:` (apenas Base64 — adequado só a homologação).

Itens já gravados em `b64:` **continuam legíveis** após definir a chave; não é obrigatório regravar. Novos itens ou reedição passam a usar `gcm:` quando a chave estiver ativa.

### Gerar chave

No servidor (ou offline):

```bash
openssl rand -base64 48
```

### Configurar

Em `/var/www/portal/.env` (não versionar):

```env
LIC_COFRE_CIPHER_KEY=<valor gerado>
```

Permissões: ficheiro legível por `www-data`, não expor em repositório.

### Aplicar

```bash
sudo -u www-data bin/cake cache clear_all
# Se usar PHP-FPM: sudo systemctl reload php*-fpm
```

### Verificar

```bash
sudo -u www-data bin/cake licencas uat_check --idempresa=1
```

Esperado: `[OK] LIC_COFRE_CIPHER_KEY definido` (deixa de aparecer o aviso).

**UI (admin_equipe + `licencas.cofre.secret`):**

1. Cofre → criar item de teste com segredo conhecido.
2. Revelar segredo → deve mostrar o texto correto.
3. Auditoria → evento de revelação registado.

**Registo na BD (opcional):**

```sql
SELECT id, left(secret_blob, 8) AS prefix FROM lic_cofre_itens ORDER BY id DESC LIMIT 3;
```

- Itens novos com chave: prefixo `gcm:` (após decode na app, coluna guarda string `gcm:...`).
- Seed demo antigo: pode manter `b64:`.

---

## 2. Registo de UAT aprovada (processo)

Não há flag automática no código. Registar na equipa:

| Campo | Valor sugerido |
|-------|----------------|
| Ambiente | `app-erp-prod-01` / empresa `idempresa=1` |
| Commit `main` | `git rev-parse --short HEAD` após deploy |
| CLI | `uat_check --strict` OK; `stats` com dados esperados |
| UI | Checklist `LICENCIAMENTO_HOMOLOGACAO_UAT.md` assinado |
| RBAC | `operacao` sem `cofre.secret`; `admin_equipe` com `cofre.secret` |
| Data / responsável | preenchido pela equipa |

Opcional: anexar saída de `bin/cake licencas stats --idempresa=1` ao ticket de go-live.

---

## 3. `LICENCAS_CANONICAL_ROUTES` (só se quiser URL curta)

### Comportamento

| Valor | Efeito |
|-------|--------|
| `0` (default) | Apenas rotas `/licencas-prototype/*` e portal `/cliente/licencas/*` |
| `1` | Redireciona `/licencas` → `/licencas-prototype` e aceita `/licencas/:page` como alias |

O menu lateral ERP **continua** a gerar links `/licencas-prototype/...` (arrays `controller` => `LicencasPrototype`). O alias serve bookmarks, documentação externa e links manuais — **não** altera o menu sozinho.

### Quando ativar

- Equipe e integrações já validadas com `/licencas-prototype`.
- Desejo de expor `/licencas/dashboard` (etc.) em paralelo.

### Configurar

```env
LICENCAS_CANONICAL_ROUTES=1
```

```bash
sudo -u www-data bin/cake cache clear_all
```

### Verificar (ajuste `APP_BASE` se usar `/portal`)

```bash
# Deve responder 302 para o painel protótipo
curl -sI -o /dev/null -w "%{http_code} %{redirect_url}\n" \
  "https://<host>/licencas-prototype"

curl -sI -o /dev/null -w "%{http_code} %{redirect_url}\n" \
  "https://<host>/licencas"
```

Com `=1`, o segundo pedido deve redirecionar para `/licencas-prototype` (ou com prefixo `APP_BASE`).

CLI:

```bash
bin/cake licencas uat_check --idempresa=1
```

Linha esperada: `LICENCAS_CANONICAL_ROUTES=1` ou `=0`.

### Se não precisar

Manter `LICENCAS_CANONICAL_ROUTES=0` — é o estado recomendado até haver switchover completo de menus/documentação.

---

## 4. Dados demo em produção

O `seed_demo` é para homologação. Após UAT:

- **Manter** se o ambiente for só piloto interno.
- **Remover** antes de go-live real com clientes: apagar linhas `lic_*` da empresa de teste ou usar empresa dedicada sem seed.

Não executar `seed_demo` em produção com clientes reais sem `--dry-run` e alinhamento com negócio.

---

## Resumo rápido

| Item | Produção típica | Verificação |
|------|-----------------|-------------|
| `LIC_COFRE_CIPHER_KEY` | Definir | `uat_check` + revelar cofre na UI |
| UAT assinada | Processo | Checklist + ticket |
| `LICENCAS_CANONICAL_ROUTES` | `0` até decisão explícita | `curl` + linha no `uat_check` |
| Seed demo | Só piloto | `stats` / negócio |
