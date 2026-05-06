# Prompt para o Cursor — Implementação do Módulo Laudos / Parecer Técnico

## Como usar este prompt

1. Abra seu projeto no Cursor
2. Anexe ao chat a pasta `laudos-package/` inteira (drag & drop ou `@laudos-package`)
3. Cole o prompt abaixo
4. Revise as alterações arquivo por arquivo antes de aceitar

---

## PROMPT (cole tudo abaixo)

```
Preciso que você implemente o módulo "Laudos / Parecer Técnico" no meu sistema CakePHP 4.x + React.

## Contexto do meu sistema

- Backend: CakePHP 4.x, PHP 8.1+, PostgreSQL
- Frontend: React 18, integrado ao mesmo projeto
- Já existe sistema de autenticação (Authentication plugin do CakePHP)
- Já existe tabela de clientes — descubra o nome correto (clientes, customers, etc.)
- Já existe tabela users com id, name, email
- A sidebar do sistema fica em [DETECTE: o arquivo de configuração de menu/navegação do React]

## Pacote anexo

A pasta `laudos-package/` contém:
- `README.md` — visão geral
- `docs/01-fluxo-completo.md` — fluxo completo, regras de negócio, lista de endpoints
- `docs/03-permissoes.md` — papéis e ACL
- `docs/04-deploy-checklist.md` — checklist de deploy
- `database/schema.sql` — schema PostgreSQL completo
- `database/seeds.sql` — dados iniciais (catálogos e templates)
- `database/migrations.php` — alternativa ao SQL puro (CakePHP migration)
- `backend/Controller/` — 4 controllers (LaudosPareceresController, _OutrosControllers, LaudosPdfController)
- `backend/Model/Table/` — 9 tables (LaudosPareceresTable + _OutrasTabelas com 8 a mais)
- `backend/Model/Entity/` — 9 entities em _AllEntities.php
- `backend/routes.php` — trecho a inserir em config/routes.php
- `frontend/services/api.js` — cliente axios para todos os endpoints
- `frontend/utils/` — masks, validators, imageCompression
- `frontend/hooks/useDebounceSave.js` — hook de auto-save
- `frontend/components/` — 7 componentes (ParecerForm, ProdutoCard, ImageUpload, SignaturePad, ClientSearch, StatusBar, SaveIndicator)
- `frontend/pages/` — ParecerListPage e ParecerEditPage

## O que fazer (em ordem)

### 1. Banco de dados
- Rode `database/schema.sql` no meu PostgreSQL
- Rode `database/seeds.sql` para popular catálogos e templates
- Verifique se as FKs apontam para as tabelas corretas (especialmente `requester_client_id`)
- Se minha tabela de clientes tiver outro nome, ajuste o schema.sql ANTES de executar

### 2. Backend CakePHP
- Os arquivos `_OutrasTabelas.php` e `_AllEntities.php` agrupam várias classes — DIVIDA cada classe em seu próprio arquivo seguindo o cabeçalho indicado em comentário (`// FILE: src/Model/Table/...`)
- O mesmo para `_OutrosControllers.php`
- Coloque os controllers em `src/Controller/Api/Laudos/` (não no namespace raiz)
- Coloque tables em `src/Model/Table/`
- Coloque entities em `src/Model/Entity/`
- Adicione o conteúdo de `backend/routes.php` ao meu `config/routes.php` (no escopo `/api`)
- Configure `Authentication->allowUnauthenticated(['publica'])` no AppController OU no ValidacaoController para a rota pública `/validar/{hash}`
- Instale `composer require dompdf/dompdf` para a geração de PDF
- Confirme que o Mailer do CakePHP está configurado para envio de e-mails

### 3. Menu da sidebar (IMPORTANTE)
- Localize o arquivo de configuração de menu/navegação no meu projeto React (provavelmente algo como `src/config/menu.js`, `src/components/Sidebar.jsx`, `src/routes.js` ou `src/layouts/MainLayout.jsx`)
- ADICIONE um item de menu chamado "Laudos" como pai
- Como SUBMENU dele, adicione "Parecer Técnico" apontando para a rota `/laudos/pareceres`
- Use ícone apropriado (ex: clipboard, file-text, document)
- Posicione o item de menu em local lógico (próximo a outros documentos/relatórios, se existirem)
- Mantenha o estilo visual consistente com os outros itens da sidebar

### 4. Frontend React
- Coloque pages em `src/pages/Laudos/` (ou onde você organiza páginas no projeto)
- Coloque components em `src/components/Laudos/` ou `src/features/laudos/components/`
- Coloque hooks em `src/hooks/` (junto com os existentes)
- Coloque utils em `src/utils/` ou `src/features/laudos/utils/`
- Adicione as rotas no React Router:
  - `/laudos/pareceres` → ParecerListPage
  - `/laudos/pareceres/:id` → ParecerEditPage
- Adapte o `api.js` ao cliente HTTP que já uso (se for um axios diferente, importe-o em vez de criar novo)
- Adapte o ClientSearch.jsx para usar o endpoint correto de busca de clientes do meu sistema

### 5. Permissões
- Leia `docs/03-permissoes.md` e implemente o controle de acesso usando o Authorization plugin do CakePHP, OU adicione uma policy/middleware compatível com o que já uso

### 6. Página pública de validação (QR Code)
- Crie uma página simples em `/validar/:hash` (rota PÚBLICA, sem login)
- Pode ser tanto uma rota CakePHP renderizando view, quanto uma rota React fazendo fetch ao endpoint público
- Mostrar apenas: número do parecer, data, emissor, cliente, status

### 7. Testes manuais
- Use `docs/04-deploy-checklist.md` como guia de validação
- Rode os comandos de migration
- Crie um parecer de teste, adicione um equipamento, gere PDF, envie e-mail

## Adaptações esperadas

Você precisará adaptar:

1. **Nome da tabela de clientes**: minha tabela pode se chamar `clientes`, `customers`, ou outro nome — ajuste no schema.sql E no relacionamento `belongsTo Clientes` em LaudosPareceresTable
2. **Endpoint de busca de clientes**: ajuste em `api.js` o método `ClientesAPI.search`
3. **Multi-tenant**: se eu tiver multi-tenant (campo empresa_id em users), use isso. Senão, deixe `empresa_id = 1` fixo
4. **Sistema de menu**: detecte e adapte como já mencionei
5. **Padrão de chamadas API**: se já uso fetch em vez de axios, ou se tem algum interceptor de auth, use o existente
6. **Estilo visual**: os componentes vêm com estilos inline básicos. Adapte ao Tailwind/styled-components/CSS do meu projeto se for o caso

## Restrições

- NÃO gere migration nova se eu já tiver schema.sql aplicado
- NÃO sobrescreva minha configuração de routes existente — APENDE ao arquivo
- NÃO crie cliente axios novo se já tem um global no projeto — IMPORTE o existente
- NÃO mude estrutura de pastas/convenções já estabelecidas no projeto

## Saída esperada

Ao final, eu devo ter:
- Tabelas `laudos_*` criadas no PostgreSQL com seeds carregados
- Endpoints da API funcionando (teste com curl ou Postman)
- Menu lateral com "Laudos > Parecer Técnico" visível
- Página de listagem em `/laudos/pareceres`
- Página de edição em `/laudos/pareceres/:id`
- PDF sendo gerado corretamente
- E-mail sendo enviado (se SMTP configurado)
- Rota pública `/validar/{hash}` funcionando

Confirme passo a passo o que vai fazer ANTES de executar mudanças.
```

---

## Notas adicionais

### Se o Cursor pedir mais contexto

Caso o Cursor não consiga detectar automaticamente a estrutura do seu menu, dê estas pistas adicionais:

```
Para encontrar o menu da sidebar, procure por arquivos com nomes como:
- src/components/Sidebar.{jsx,tsx}
- src/components/Navigation.{jsx,tsx}
- src/layouts/MainLayout.{jsx,tsx}
- src/config/menu.{js,ts}
- src/routes.{js,ts,jsx,tsx}

Procure por arrays de objetos como:
[
  { label: 'Dashboard', icon: ..., path: '/dashboard' },
  { label: 'Clientes', ... },
  ...
]

Adicione um item assim:
{
  label: 'Laudos',
  icon: 'FileText',  // ou ícone equivalente
  children: [
    { label: 'Parecer Técnico', path: '/laudos/pareceres' }
  ]
}
```

### Se houver conflito de rotas

Caso já exista alguma rota `/laudos` no sistema, use prefixo alternativo:
- `/relatorios/pareceres-tecnicos`
- `/documentos/pareceres`
- `/ti/pareceres`

Atualize tanto as rotas do React Router quanto os endpoints PHP correspondentes.

### Validação após implementação

Execute estes testes manualmente após o Cursor concluir:

1. **Banco**: `SELECT * FROM laudos_empresas;` deve retornar 1 linha
2. **API**: `curl -b cookies.txt http://localhost/api/laudos/pareceres` deve retornar JSON
3. **Menu**: o item "Laudos" deve aparecer na sidebar após login
4. **Lista**: clicar em "Parecer Técnico" deve abrir a listagem (vazia inicialmente)
5. **Criar**: botão "Novo Parecer" deve criar um com número automático no formato `0001/2026`
6. **Auto-save**: digitar campos deve mostrar "Salvando..." e depois "Tudo salvo"
7. **Equipamento**: adicionar um equipamento, preencher diagnóstico (use template), adicionar peça do catálogo
8. **Foto**: tentar fazer upload de uma imagem grande (>2MB) — deve comprimir e mostrar redução de tamanho
9. **PDF**: clicar em "PDF" deve abrir o arquivo em nova aba
10. **Validação pública**: copiar a URL do QR code e abrir em modo anônimo — deve mostrar dados básicos sem login

Se algum desses passos falhar, peça ao Cursor para revisar especificamente a parte que não funcionou.
