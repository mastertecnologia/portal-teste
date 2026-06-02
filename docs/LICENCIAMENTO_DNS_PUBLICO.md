# Licenciamento — DNS e URL pública (`portal.pgm.inf.br`)

Sintoma típico após deploy OK em `uat_check`:

- `curl -k https://portal.pgm.inf.br/portal/licencas-prototype` → **404** e `Server: Apache/2.4.43 (Win64)`
- No portal Linux (**10.0.2.25**): `curl -k --resolve portal.pgm.inf.br:443:127.0.0.1 ...` → **302** e `Server: Apache/2.4.66 (Debian)`

O módulo está no **Linux**; o nome público ainda pode apontar para o **ERP Windows** (ex. **10.0.2.7** / IP **179.96.229.210**).

## Arquitetura esperada

| Componente | IP / host | Função |
|------------|-----------|--------|
| Portal CakePHP | **10.0.2.25** (`app-erp-prod-01`) | `/portal/*`, licenciamento |
| PostgreSQL | 10.0.2.23 | BD `pgm`, tabelas `lic_*` |
| ERP Grid | 10.0.2.7:85 (Win64) | WebGridPGM — **não** serve `licencas-prototype` |

URL canónica equipe: **`https://portal.pgm.inf.br/portal/licencas-prototype`**  
(`APP_BASE=/portal` no `.env` do portal Linux.)

## Diagnóstico no servidor Linux (10.0.2.25)

```bash
hostname -I
getent hosts portal.pgm.inf.br

# Apache local (deve ser Debian)
curl -sI -H "Host: portal.pgm.inf.br" \
  "http://127.0.0.1/portal/licencas-prototype" | head -5

# Cake no mesmo host (deve ser 302 login, não 404)
curl -sI -k --resolve portal.pgm.inf.br:443:127.0.0.1 \
  "https://portal.pgm.inf.br/portal/licencas-prototype" | head -8

# IP público (hoje pode ir ao Windows → 404 Win64)
curl -sI -k "https://portal.pgm.inf.br/portal/licencas-prototype" | head -8
```

CLI automatizado:

```bash
bin/cake licencas url_check
```

## Correção (infra / rede)

Escolher **uma** abordagem:

### A) DNS público → portal Linux

`portal.pgm.inf.br` (179.96.229.210 ou novo IP) termina no **10.0.2.25**, TLS no Apache Debian do portal.

### B) Reverse proxy no IP público

O host em **179.96.229.210** faz proxy apenas do path `/portal/` para `https://10.0.2.25/portal/` (ou HTTP interno + TLS no edge).

Exemplo Apache (edge — ajustar certificados e backends):

```apache
ProxyPreserveHost On
ProxyPass        /portal/ https://10.0.2.25/portal/
ProxyPassReverse /portal/ https://10.0.2.25/portal/
```

### C) Split-horizon DNS

- Rede interna: `portal.pgm.inf.br` → **10.0.2.25**
- Internet: só após A ou proxy corrigido

## Após correção

```bash
curl -sI -k "https://portal.pgm.inf.br/portal/licencas-prototype" | head -8
```

Esperado:

- `Server: Apache/2.4.66 (Debian)` (ou proxy que não altere para Win64)
- `HTTP/1.1 302` para `/portal/users/acesso-empresa` (sem login)

Browser (equipe): login → painel licenciamento.

## `.env` recomendado no Linux

```env
APP_BASE=/portal
APP_FULL_BASE_URL=https://portal.pgm.inf.br/portal
```

`APP_FULL_BASE_URL` ajuda redirects e links absolutos (ver `docs/MAPEAMENTO_ARQUIVOS_CONTROLE_PORTAL.md`).

## Redirect pós-login

Com `APP_BASE=/portal`, o parâmetro `?redirect=` após login deve incluir o prefixo `/portal/...` (correção em `UsersController::_sanitizePostLoginRedirect`).

## Referências

- `docs/INFRAESTRUTURA_SERVIDORES.md`
- `docs/LICENCIAMENTO_GO_LIVE.md`
- `docs/EVITAR_301_INTEGRADOR.md` (Integrador deve usar HTTPS + `/portal`)
