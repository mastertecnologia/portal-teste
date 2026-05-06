# Módulo Laudos / Parecer Técnico

Documento técnico completo para implementação do módulo de Pareceres Técnicos em sistema CakePHP 4.x + React.

## 📋 Sumário

1. [Visão geral](#visão-geral)
2. [Stack e arquitetura](#stack-e-arquitetura)
3. [Estrutura do pacote](#estrutura-do-pacote)
4. [Ordem de implementação](#ordem-de-implementação)
5. [Como usar este pacote com Cursor](#como-usar-este-pacote-com-cursor)

---

## Visão geral

Módulo para emissão de **Pareceres Técnicos** de equipamentos informáticos, com:

- Cadastro de múltiplos pareceres com status (rascunho → enviado)
- Múltiplos equipamentos por parecer, cada um com diagnóstico, fotos, peças e serviços
- Integração com cadastro de clientes existente
- Catálogos reutilizáveis de peças e serviços
- Templates de diagnóstico e conclusão
- Assinatura digital + carimbo
- Histórico de auditoria
- Comparativo reparo × substituição
- QR Code de validação pública
- Exportação para PDF
- Envio por e-mail

## Stack e arquitetura

- **Backend**: CakePHP 4.x (PHP 8.1+), PostgreSQL 13+
- **Frontend**: React 18, integrado ao sistema existente
- **Autenticação**: a do sistema atual (sessão CakePHP)
- **Storage de arquivos**: filesystem em `/webroot/uploads/laudos/{parecer_id}/` (recomendado) ou base64 no banco (mais simples para começar)
- **PDF**: dompdf ou wkhtmltopdf via CakePDF
- **APIs externas**: BrasilAPI (CNPJ) e ViaCEP (CEP) — públicas e gratuitas

## Estrutura do pacote

```
laudos-package/
├── README.md                          # este arquivo
├── docs/
│   ├── 01-fluxo-completo.md          # fluxo de uso e regras de negócio
│   ├── 02-prompt-cursor.md           # prompt pronto para colar no Cursor
│   ├── 03-permissoes.md              # ACL e papéis
│   └── 04-deploy-checklist.md        # checklist de deploy
├── database/
│   ├── schema.sql                    # CREATE TABLEs PostgreSQL
│   ├── seeds.sql                     # dados iniciais (catálogos, templates)
│   └── migrations.php                # migration CakePHP equivalente
├── backend/
│   ├── Controller/                   # 7 controllers
│   ├── Model/Table/                  # 9 tables
│   ├── Model/Entity/                 # 9 entities
│   └── routes.php                    # config/routes.php (trecho)
└── frontend/
    ├── pages/                        # páginas principais
    ├── components/                   # componentes reutilizáveis
    ├── hooks/                        # hooks customizados
    ├── services/                     # API client
    └── utils/                        # máscaras, validações
```

## Ordem de implementação

Se for implementar incrementalmente, esta é a ordem recomendada:

1. **Banco de dados** — rodar `database/schema.sql` no PostgreSQL
2. **Backend models e controllers** — copiar arquivos de `backend/`
3. **Backend routes** — adicionar trecho de `backend/routes.php` no seu `config/routes.php`
4. **Menu** — adicionar item "Laudos > Parecer Técnico" na sua sidebar
5. **Frontend listagem** — implementar página `ParecerListPage.jsx`
6. **Frontend edição** — implementar página `ParecerEditPage.jsx` com componentes
7. **PDF** — instalar `cakephp/pdf` e implementar action `pdf()`
8. **Templates iniciais** — rodar `database/seeds.sql`
9. **Testes manuais** — checklist em `docs/04-deploy-checklist.md`

## Como usar este pacote com Cursor

### Opção A — De uma vez só
1. Abra o projeto no Cursor
2. Anexe a pasta `laudos-package/` inteira ao chat
3. Cole o prompt de `docs/02-prompt-cursor.md`
4. Revise as alterações sugeridas, arquivo por arquivo

### Opção B — Por partes (recomendado para projetos grandes)
1. **Etapa 1 — Banco**: anexe `database/schema.sql` + peça pra rodar
2. **Etapa 2 — Backend**: anexe a pasta `backend/` + peça pra adaptar ao padrão do projeto
3. **Etapa 3 — Menu**: peça especificamente para adicionar "Laudos > Parecer Técnico" na sidebar
4. **Etapa 4 — Frontend**: anexe `frontend/` + peça pra integrar com seu sistema de roteamento e auth

### Convenções assumidas
Esta documentação assume que seu sistema já tem:
- Tabela `users` com `id`, `name`, `email`
- Tabela `clientes` (ou `customers`) com `id`, `razao_social`, `cnpj`, `telefone`, `email`, `cep`, `endereco`
- Sistema de autenticação CakePHP padrão (Authentication plugin)
- Frontend React com axios/fetch e algum sistema de rotas

Se os nomes das suas tabelas forem diferentes, ajuste os FOREIGN KEYs no `schema.sql`.

---

**Próximo arquivo a ler:** [`docs/01-fluxo-completo.md`](docs/01-fluxo-completo.md)
