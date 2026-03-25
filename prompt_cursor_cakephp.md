# PROMPTS PARA O CURSOR — PGM ERP (CakePHP)
# Cole cada prompt no chat do Cursor (Ctrl+L) na ordem indicada
# Aguarde terminar cada etapa antes de passar para a próxima

==============================================================
PROMPT 0 — LEITURA DO PROJETO (execute ANTES de qualquer coisa)
==============================================================

Analise o projeto atual e me responda:

1. Qual versão do CakePHP está sendo usada? (leia composer.json)
2. Qual é o sistema de autenticação atual? (leia src/Controller/AppController.php)
3. Qual é o banco de dados configurado? (leia config/app_local.php ou config/app.php)
4. Quais tabelas já existem? (leia as migrations em config/Migrations/ ou src/Model/Table/)
5. O módulo de orçamentos já existe parcialmente? (verifique src/Controller/ e templates/)
6. Quais plugins já estão instalados? (leia composer.json e src/Application.php)

Com base nisso, me diga o que já existe e o que precisa ser criado do zero.


==============================================================
PROMPT 1 — MIGRATIONS (tabelas do banco)
==============================================================

@cursorrules
@composer.json
@src/Controller/AppController.php

Com base na análise do projeto CakePHP existente, crie as migrations para o módulo de orçamentos.

Crie as seguintes migrations usando bin/cake bake migration:

1. CreateProdutos
Campos: id, codigo (string 20, único), nome (string 200), descricao (text, nullable),
tipo (enum: produto/servico/licenca/locacao), preco_venda (decimal 15,2),
preco_custo (decimal 15,2, default 0), estoque (integer, default 0),
ativo (boolean, default true), created, modified

2. CreateOrcamentos
Campos: id, numero (string 20, único), cliente_id (integer, FK para tabela de clientes existente),
status (enum: rascunho/enviado/aprovado/recusado/arquivado, default rascunho),
versao_atual (integer, default 1), criado_por (string 100),
created, modified

3. CreateOrcamentoVersoes
Campos: id, orcamento_id (integer, FK), versao (integer),
itens (json — array de itens do orçamento), total_venda (decimal 15,2),
total_custo (decimal 15,2), desconto (decimal 15,2, default 0),
condicao_pagamento (string 100), validade (date), obs (text, nullable),
aprovacao_interna (boolean, default false), aprovado_por (string 100, nullable),
aprovado_em (datetime, nullable), assinado_em (datetime, nullable),
assinado_por (string 200, nullable), cpf_signatario (string 14, nullable),
ip_assinatura (string 45, nullable), hash_documento (string 64, nullable),
audit_log (json, nullable), created

4. CreatePortalTokens
Campos: id, orcamento_id (integer, FK), token (string 64, único),
usado (boolean, default false), usado_em (datetime, nullable),
expira_em (datetime), bloqueado (boolean, default false),
tentativas (integer, default 0), created

5. CreateOtpTokens
Campos: id, portal_token_id (integer, FK), otp_hash (string 64),
expira_em (datetime), usado (boolean, default false), created

Após criar as migrations, rode:
bin/cake migrations migrate

Se a tabela de clientes já existir com nome diferente, ajuste a FK corretamente.


==============================================================
PROMPT 2 — MODELS (Tables e Entities)
==============================================================

@cursorrules
@config/Migrations/

Com base nas migrations criadas, gere os Models do CakePHP para o módulo de orçamentos.

1. src/Model/Table/ProdutosTable.php
- Validações: codigo (obrigatório, único, max 20 chars), nome (obrigatório, max 200),
  tipo (obrigatório, deve ser um dos valores do enum), preco_venda (obrigatório, >= 0),
  preco_custo (>= 0)
- Finder customizado: findAtivos() — filtra ativo = true
- Finder customizado: findCatalogo($query, $busca) — busca por nome, codigo e descricao

2. src/Model/Table/OrcamentosTable.php
- Associações: belongsTo Clientes, hasMany OrcamentoVersoes, hasMany PortalTokens
- Validações: numero (obrigatório, único), cliente_id (obrigatório), status (valor válido)
- Behavior: Timestamp
- Método: gerarNumero() — gera próximo número sequencial no formato ORC-2026-0001

3. src/Model/Table/OrcamentoVersoesTable.php
- Associações: belongsTo Orcamentos, hasMany (itens são JSON, não tabela separada)
- Validações: orcamento_id, versao, total_venda (obrigatórios e numéricos)
- Método calcularMargem(array $itens): float — calcula margem % total dos itens

4. src/Model/Table/PortalTokensTable.php
- Associações: belongsTo Orcamentos, hasOne OtpTokens
- Validações: token (obrigatório, único), expira_em (obrigatório)
- Método isValido($token): bool — verifica se não expirou, não foi usado, não está bloqueado
- Método incrementarTentativas($id): void — incrementa e bloqueia se >= 3

5. src/Model/Entity/Orcamento.php
- Campo virtual: total_formatado — retorna total_venda em formato R$ xx,xx
- Campo virtual: status_label — retorna o label em PT-BR do status

Gere também os arquivos de Entity correspondentes para cada Table.


==============================================================
PROMPT 3 — CONTROLLER DE ORÇAMENTOS (painel interno)
==============================================================

@cursorrules
@src/Controller/AppController.php
@src/Model/Table/OrcamentosTable.php
@pgm_orcamentos_premium.html

Analise o arquivo pgm_orcamentos_premium.html como referência visual e crie o
OrcamentosController completo em src/Controller/OrcamentosController.php.

O controller deve herdar de AppController e usar o sistema de autenticação existente.

Actions necessárias:

1. index()
- Lista todos os orçamentos paginados (20 por página)
- Filtros via query string: status, busca (empresa/numero), page
- Conta totais por status para os cards do dashboard
- Ordena por created DESC por padrão
- Passa para a view: orcamentos, totais por status, filtros ativos

2. view($id)
- Carrega orçamento com cliente e todas as versões
- Carrega versão atual (maior número de versão)
- Passa itens da versão atual parseados do JSON
- Registra visualização no audit_log

3. add()
- GET: carrega lista de clientes ativos para o select
- POST: valida dados, gera número sequencial, cria Orcamento + primeira OrcamentoVersao
- Redireciona para view($id) após sucesso com Flash::success

4. edit($id)
- GET: carrega orçamento, versão atual, clientes
- POST: NUNCA edita versão existente — cria nova OrcamentoVersao incrementando versao
- Atualiza orcamento.versao_atual
- Redireciona para view($id) com Flash::success

5. enviar($id) [POST only]
- Verifica se o orçamento tem aprovação interna (se desconto > 15% ou total > 50000)
- Muda status para 'enviado'
- Gera PortalToken: hash('sha256', random_bytes(32) . env('PORTAL_TOKEN_SECRET') . $id . time())
- Define expira_em = now + 7 dias
- Envia e-mail para cliente com link /portal/{token} via CakePHP Mailer
- Redireciona para view($id) com Flash::success

6. aprovarInterno($id) [POST only]
- Registra aprovação interna na OrcamentoVersao atual
- Salva: aprovacao_interna=true, aprovado_por=nome do usuário logado, aprovado_em=now
- Flash::success e redirect para view($id)

7. pdf($id)
- Carrega orçamento com dados completos
- Renderiza template templates/Orcamentos/pdf/orcamento.php
- Gera PDF usando TCPDF com layout do protótipo pgm_orcamentos_premium.html
- Força download com nome Orcamento_{numero}_v{versao}.pdf

8. catalogo() [AJAX/JSON]
- Busca produtos ativos com filtro por nome/codigo
- Retorna JSON: [{id, codigo, nome, tipo, preco_venda, preco_custo, estoque, margem}]
- Usado pelo modal de catálogo no frontend


==============================================================
PROMPT 4 — TEMPLATES DO PAINEL (views PHP)
==============================================================

@cursorrules
@pgm_orcamentos_premium.html
@src/Controller/OrcamentosController.php

Crie os templates CakePHP para o módulo de orçamentos.
Use o arquivo pgm_orcamentos_premium.html como referência visual EXATA.
Mantenha as cores (#1D9E75), ícones SVG e layout do protótipo.
Converta a lógica JavaScript de cálculo para PHP onde possível,
e mantenha JavaScript apenas para interações do cliente (catálogo modal, cálculos em tempo real).

1. templates/Orcamentos/index.php
- Cards de contagem por status (dados de $totais)
- Abas de filtro por status
- Tabela com: ID, empresa, versão badge, status badge, margem%, total formatado, data, ações
- Busca por empresa/número
- Botão "Gerar Orçamento" que vai para /orcamentos/add
- Paginação do CakePHP ($this->Paginator->numbers())

2. templates/Orcamentos/add.php e edit.php
- Formulário completo usando FormHelper do CakePHP ($this->Form->*)
- Select de clientes com busca
- Campos: condição pagamento, validade, e-mail OTP, observações
- Tabela de itens dinâmica (adicionar/remover via JS)
- Botão "Buscar no catálogo" que abre modal (AJAX para /orcamentos/catalogo)
- Campo custo por item + cálculo automático de margem (JS)
- Desconto em % ou R$ com recálculo em tempo real (JS)
- Alerta de alçada: desconto >15% ou total >R$50.000
- Indicador visual de margem (verde/amarelo/vermelho)
- Painel de versões (no edit: lista versões existentes, botão "Nova versão")

3. templates/Orcamentos/view.php
- Dados completos do orçamento e cliente
- Tabela de itens da versão atual
- Totais (subtotal, desconto, total, margem)
- Histórico de versões
- Botões de ação: Editar, Enviar ao cliente, Baixar PDF, Aprovar internamente
- Status atual com badge colorido

4. templates/Orcamentos/pdf/orcamento.php
- Layout limpo para impressão (sem sidebar, sem nav)
- Cabeçalho PGM com dados da empresa
- Dados do cliente e orçamento
- Tabela de itens
- Totais
- Condições gerais
- Bloco de assinaturas

5. webroot/js/orcamentos.js
- Lógica do catálogo modal (fetch para /orcamentos/catalogo, renderiza resultados)
- Cálculo em tempo real de margens e totais
- Validação de alçada (desconto e valor total)
- Gerenciamento da tabela de itens (adicionar, remover, calcular)


==============================================================
PROMPT 5 — PORTAL PÚBLICO DO CLIENTE (MFA)
==============================================================

@cursorrules
@pgm_portal_autenticado.html
@src/Model/Table/PortalTokensTable.php

Analise o arquivo pgm_portal_autenticado.html como referência visual e crie o
PortalController em src/Controller/PortalController.php.

IMPORTANTE: Este controller é PÚBLICO — sem autenticação do sistema interno.
Adicione em initialize(): $this->Authentication->allowUnauthenticated(['acesso','verificar','otp','proposta','assinar']);
Ou o equivalente para o sistema de auth existente no projeto.

Actions:

1. acesso($token)
- GET: valida se o token existe, não expirou e não está bloqueado
- Se inválido: renderiza view de erro com contato do vendedor
- Se válido: renderiza formulário de verificação de identidade (CNPJ 4 dígitos + nome)
- POST: verifica CNPJ e nome contra os dados do cliente vinculado ao orçamento
  - Se correto: gera OTP (6 dígitos, random_int), armazena hash SHA-256 na tabela otp_tokens
    com TTL 10min, envia por e-mail para email_otp do cliente via Mailer, redireciona para /portal/{token}/otp
  - Se errado: incrementa tentativas no portal_token, bloqueia se >= 3, Flash::error

2. otp($token)
- GET: renderiza formulário de 6 inputs de OTP
- POST: valida OTP contra o hash armazenado
  - Se correto: cria sessão temporária (token de sessão PHP com TTL), marca otp como usado,
    redireciona para /portal/{token}/proposta
  - Se expirado: Flash::error 'Código expirado. Solicite novo.'
  - Se errado: incrementa tentativas, Flash::error

3. proposta($token)
- GET: verifica sessão ativa, carrega orçamento com itens da versão atual
- Renderiza a proposta completa para o cliente visualizar
- Exibe opções: Aprovar / Negociar / Recusar (botões que redirecionam para assinar com ?decisao=)

4. assinar($token)
- GET: renderiza formulário de assinatura (nome, CPF, canvas, checkbox termos)
- Lê decisão via query string: ?decisao=aprovado|recusado|negociacao
- POST: processa a decisão:
  APROVADO:
    - Gera hash SHA-256 do conteúdo da versão atual (json_encode dos itens + total + data)
    - Salva em OrcamentoVersao: assinado_em, assinado_por, cpf_signatario, ip_assinatura,
      hash_documento, audit_log (IP, user-agent, timestamp, decisão)
    - Atualiza Orcamento.status = 'aprovado'
    - Marca portal_token como usado
    - Envia e-mail de notificação para o vendedor
    - Renderiza tela de sucesso com número de protocolo (primeiros 16 chars do hash)
  RECUSADO:
    - Salva motivo no audit_log da versão
    - Atualiza status = 'recusado'
    - Envia e-mail de notificação para o vendedor
    - Renderiza tela de recusa
  NEGOCIACAO:
    - Salva observação do cliente no audit_log
    - Envia e-mail para o vendedor com observações
    - Renderiza tela de confirmação de solicitação

5. Templates do portal (templates/Portal/):
   - acesso.php   — verificação de identidade (visual do pgm_portal_autenticado.html tela 1)
   - otp.php      — entrada do código OTP (visual tela 2)
   - proposta.php — exibição da proposta (visual tela 3)
   - assinar.php  — formulário de assinatura (visual tela 4)
   - sucesso.php  — confirmação final
   - erro.php     — token inválido/expirado

   Manter fidelidade visual ao pgm_portal_autenticado.html.
   Adicionar canvas de assinatura com JavaScript (mouse + touch).


==============================================================
PROMPT 6 — E-MAILS (Mailer)
==============================================================

@cursorrules
@src/Controller/OrcamentosController.php
@src/Controller/PortalController.php

Crie o sistema de e-mails do módulo de orçamentos usando CakePHP Mailer.

1. src/Mailer/OrcamentoMailer.php
Métodos:
- propostaParaCliente(Orcamento $orc, OrcamentoVersao $versao, string $portalToken)
  Assunto: "Proposta de Orçamento Nº {numero} — PGM Soluções"
  Para: email_principal do cliente
  Conteúdo: link do portal, resumo dos itens, valor total, validade

- otpParaCliente(string $emailOtp, string $otp, string $empresa)
  Assunto: "Código de verificação — Proposta PGM Soluções"
  Para: email_otp do cliente
  Conteúdo: código OTP grande e legível, aviso de expiração em 10 minutos
  IMPORTANTE: enviar o OTP (não o hash) neste e-mail

- notificarVendedorAprovacao(Orcamento $orc, string $nomeSignatario, string $protocolo, string $emailVendedor)
  Assunto: "✓ Orçamento {numero} aprovado por {cliente}"
  Conteúdo: nome do aprovador, protocolo de assinatura, link para abrir no ERP

- notificarVendedorRecusa(Orcamento $orc, string $motivo, string $emailVendedor)
  Assunto: "Orçamento {numero} recusado — {cliente}"
  Conteúdo: motivo, link para abrir no ERP

- notificarVendedorNegociacao(Orcamento $orc, string $obs, string $contato, string $emailVendedor)
  Assunto: "Ajustes solicitados — Orçamento {numero}"
  Conteúdo: observação do cliente, contato, link para nova versão no ERP

- alertaBloqueioPortal(Orcamento $orc, string $ip, string $emailVendedor)
  Assunto: "⚠ Alerta de segurança — Tentativas no portal do orçamento {numero}"
  Conteúdo: IP de origem, timestamp, instrução para gerar novo token

2. templates/email/html/ — layouts dos e-mails
- Criar layout base com cores PGM (#1D9E75)
- Template para cada tipo de e-mail listado acima
- Design simples e responsivo (funciona em mobile)
- Botão CTA "Abrir no ERP" nos e-mails do vendedor


==============================================================
PROMPT 7 — ROTAS E MENU
==============================================================

@cursorrules
@config/routes.php
@templates/layout/default.php (ou o layout principal existente)

1. Adicione as rotas em config/routes.php:

Rotas do ERP interno (protegidas):
- /orcamentos              -> OrcamentosController::index
- /orcamentos/add          -> OrcamentosController::add
- /orcamentos/{id}         -> OrcamentosController::view
- /orcamentos/{id}/edit    -> OrcamentosController::edit
- /orcamentos/{id}/enviar  -> OrcamentosController::enviar (POST only)
- /orcamentos/{id}/aprovar -> OrcamentosController::aprovarInterno (POST only)
- /orcamentos/{id}/pdf     -> OrcamentosController::pdf
- /orcamentos/catalogo     -> OrcamentosController::catalogo (AJAX)

Rotas do Portal público (SEM autenticação):
- /portal/{token}          -> PortalController::acesso
- /portal/{token}/otp      -> PortalController::otp
- /portal/{token}/proposta -> PortalController::proposta
- /portal/{token}/assinar  -> PortalController::assinar

2. Adicione o item "Orçamentos" no menu lateral do layout principal.
   Verificar onde está o menu atual no layout e adicionar o link com ícone SVG
   igual ao do protótipo (ícone de documento/proposta).
   Destacar como ativo quando a URL começa com /orcamentos.


==============================================================
COMO USAR ESTES PROMPTS
==============================================================

1. Abra o Cursor no projeto portal-teste
2. Abra o chat (Ctrl+L ou Ctrl+Shift+L para novo agente)
3. Cole o PROMPT 0 primeiro — ele vai analisar o projeto existente
4. Com base na resposta, cole o PROMPT 1 e aguarde as migrations
5. Teste: bin/cake migrations migrate
6. Continue com PROMPT 2, 3, 4, 5, 6, 7 em ordem
7. Após cada prompt, teste a funcionalidade antes de prosseguir

DICA: Se o Cursor gerar algo errado, use:
"O código em [arquivo] gerou o erro: [cole o erro].
Corrija mantendo a mesma lógica e seguindo o cursorrules."

DICA 2: Para referenciar os HTMLs no Cursor:
"@pgm_orcamentos_premium.html Como referência visual,
implemente [funcionalidade específica]"


==============================================================
REVISÃO DE ADERÊNCIA — repositório `portal-teste` (legado)
==============================================================

O arquivo de prompts acima descreve um **módulo novo** (tabelas `orcamentos` com
`numero`, `OrcamentoVersoes` em JSON, `PortalTokens`, OTP, TCPDF, Mailer dedicado).
O **portal-teste** em produção usa o **módulo legado** (`orcamentosnovosdes`,
`Orcamentositens`, `Orcamentosservicos`, carrinho em sessão, `viewhash`, e-mail
via `Email` + template de empresa). **Não é aderência 100%** ao PROMPT 1–6 sem
migrar dados e reescrever regras de negócio.

| Prompt | No `portal-teste` | Observação |
|--------|-------------------|------------|
| **0** | Parcial | CakePHP 3.7, Auth Form, DB conforme `config/`; orçamentos já existem no legado. |
| **1** | **Não** | Não há migrations `CreateProdutos` / `CreateOrcamentos` / versões / tokens do prompt; produtos e orçamentos já vêm do schema atual. |
| **2** | **Não** | `OrcamentosTable` aponta para `orcamentosnovosdes`; não há `OrcamentoVersoesTable`, `PortalTokensTable`, finders `findCatalogo` no sentido do prompt. |
| **3** | **Parcial** | `index`, `add`, `edit`, `view`, `enviar` existem com semântica legada. **Não** há `aprovarInterno` (nome do prompt); há `aprovar` / fluxo por hash. **PDF:** `imprimirPdf` + alias **`pdf`** (mPDF, não TCPDF). **`catalogo`** (GET JSON) implementado para AJAX. |
| **4** | **Parcial** | Views em `src/Template/Orcamentos/*.ctp` (Cake 3), não `templates/Orcamentos/*.php`. `index` com KPIs/estilo premium (`orcamentos-premium.css`). **Não** há modal “catálogo” completo nem versões/OTP/alçada como no prompt; **`webroot/js/orcamentos.js`** expõe `fetchCatalogo` e helper de alçada. Template PDF do prompt: usar **`imprimir_pdf.ctp`** + mPDF (não `templates/Orcamentos/pdf/orcamento.php`). |
| **5** | **Não** | Não existe `PortalController` nem rotas `/portal/{token}/…` do MFA; cliente usa `viewhash` / links configurados. |
| **6** | **Não** | Não existe `OrcamentoMailer`; e-mails usam `Cake\Mailer\Email` em `email()` e fluxos existentes. |
| **7** | **Parcial** | Rotas explícitas: `/orcamentos`, `/orcamentos/add`, `/orcamentos/catalogo`, `/orcamentos/:id/pdf`. Menu **Orçamentos** já em `Element/sidebar.ctp` (ícone Font Awesome); ativo quando `controller === Orcamentos`. Rotas `/portal/...` **não** adicionadas (sem controller). |

**Resumo:** para **cumprir literalmente** os PROMPTs 1–2 e 5–6 é necessário projeto de
migração de dados + novo portal. O que está alinhado ao **espírito** do PROMPT 3–4–7
no legado: painel com visual premium, PDF servidor, endpoint de catálogo, JS base,
rotas nomeadas e item de menu.
