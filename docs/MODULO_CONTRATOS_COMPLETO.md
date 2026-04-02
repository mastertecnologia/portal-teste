# Módulo de Gestão de Contratos — Especificação Completa para Implementação

**Projeto:** Portal PGM / Portal do Cliente  
**Stack:** CakePHP 3.7 · PostgreSQL · Bootstrap 3 · jQuery · mPDF  
**Data:** 2026-04-01  
**Status:** Pronto para desenvolvimento no Cursor

---

## 0. Resumo Executivo

Este documento especifica o módulo completo de Gestão de Contratos.  
Fluxo end-to-end: **criação → template → PDF (mPDF) → aprovação interna → envio Autentique → assinatura eletrônica → armazenamento → vigência → renovação → encerramento**, com notificações automáticas por e-mail e portal.

### O que JÁ EXISTE — não reescrever

| Arquivo | Função |
|---------|--------|
| `src/Controller/ClicontratosController.php` | CRUD itens legado + sync ERP |
| `src/Controller/ContratosHorasController.php` | Contratos de horas técnicas |
| `src/Controller/AdvancedContractsController.php` | Listagem avançada (tabela `contracts`) |
| `src/Controller/PortalAdvancedContractsController.php` | Contratos portal cliente atual |
| `src/Service/ContratoHorasService.php` | Débito de horas |
| `src/Service/PortalAdvanced/ContractSlaIntegrationService.php` | Integração SLA |
| `src/Model/Table/ContractConsumptionsTable.php` | Consumo de franquia |
| Templates em `Clicontratos/` e `ContratosHoras/` | Manter intactos |

### Alinhamento com schema atual da tabela `contracts`

A migration `20260405130000_PortalAdvancedAttendanceContractsInvoicesAudit.php` já criou `contracts` com colunas em inglês. Mapear antes de aplicar `ALTER`:

| Conceito da spec (PT) | Coluna existente | Ação |
|-----------------------|-----------------|------|
| `numero_contrato` | `code` | Usar `code`; alias ORM |
| `tipo_contrato` | `type` | Usar `type`; alias ORM |
| `vigencia_inicio/fim` | `start_date/end_date` | Usar existentes |
| `valor_mensal` | `monthly_value` | Usar existente |
| `horas_incluidas` | `included_hours` | Usar existente |
| `auto_renovar` | `auto_renew` | Usar existente |
| `observacoes_int` | `notes` | Manter; ADD `notes_client` (nova) |
| SLA | `sla_hours` | ADD `nivel_sla` varchar (nova) |

**Regra:** usar `ADD COLUMN IF NOT EXISTS` para o que não existe. Nunca duplicar colunas existentes.

---

## 0.1 Estado atual do repositório (Git / `main`)

Esta secção alinha a **especificação** ao que está **implementado no código**. As secções **§1–§16** abaixo continuam a descrever o **alvo de produto**; para *deliverables* já merged, usar esta tabela como referência.

### Implementado (resumo)

| Área | Situação |
|------|----------|
| **BD** | `config/Migrations/20260407100000_ContractModulePhase1Expand.php` (PostgreSQL): novas tabelas + expansão de `contracts` / `contract_services`. **Não existe** no repo o ficheiro `20260407100001_ContractNewTables.php` mencionado na §1.2 — o DDL “extra” da spec foi condensado/adaptado na Phase1. |
| **ORM** | `ContractsTable` com associações (templates, signatários, renovações, notificações, pai/filhos, etc.), entities dedicadas, `ContractServicesTable` + `ContractService`. |
| **ERP** | `AdvancedContractsController`: `index`, `view`, `exportPdf` (mPDF, grava `pdf_path`). `ContractTemplatesController`: CRUD. Rotas: **`/modulo-avancado/contratos`**, **`/modulo-avancado/modelos-contrato`**. |
| **Portal** | `PortalAdvancedContractsController`: `index`, `view`, `exportPdf` (se `pdf_path` legível), **`franquia`**. Rotas: **`/cliente/contratos-avancados/*`**. |
| **Config** | `config/app.php` → chave **`Contract`**: `autentique`, `pdf`, `notifications`, `alerts` (valores via `env`). |
| **Services** | `ContractPdfService`, `ContractNotificationService`, `ContractRenewalService`; `AutentiqueService` **stub** (sem GraphQL real). |
| **Cron** | `src/Shell/ContractAlertsShell.php` — `bin/cake contract_alerts`, `bin/cake contract_alerts sincronizarAutentique`. |
| **E-mail** | Sete templates `src/Template/Email/html/contract_*.ctp`. |
| **UI global** | `sidebar.ctp` / `sidebarcli.ctp` com links para contratos avançados, modelos e franquia. |

### Ainda não implementado (conforme corpo deste documento)

- `ContractManagementController` e toda a árvore **`/modulo-contratos/*`**.
- `PortalContratosController` e **`/cliente/contratos`** (com redirect a partir de `contratos-avancados`).
- SQL **RBAC** da §10 aplicado na base.
- **Autentique** completo: `criarDocumento`, webhook, sincronização real no `AutentiqueService`.
- **`deploy-portal.sh`** e árvore **`uploads/contratos/`** da §11 (por defeito o código usa `TMP/contracts` ou `CONTRACT_PDF_STORAGE_PATH`).
- Views ERP “ricas”: KPIs na lista, wizard de criação, ecrã dedicado **enviar assinatura**, **preview/clonar** de template, TinyMCE nos modelos.

---

## 0.2 Divergências: spec (§1–§16) vs código atual

| Tema | Documento (alvo) | Repositório atual |
|------|------------------|-------------------|
| Snippet SQL da §1.1 | Muitas colunas novas em `contracts` (`notes_client`, segundo `status`, `signature_provider`, `signed_file_url`, `fully_signed_at`, …) | Phase1 **não** duplica `status` (já existe na migration base); usa **`observacoes_cli`** em vez de `notes_client`; sem parte das colunas “extras” do snippet |
| `contract_signatories` §1.2 | Campos `auth_type`, `action_type`, `autentique_signer_id`, várias datas | Versão **simplificada**: `autentique_id`, fluxo básico de assinatura |
| `config/app.php` §2.1 | `Contract.Autentique`, `Contract.Pdf`, `Contract.Notifications` (aninhamento PascalCase no exemplo) | Chaves **`autentique`**, **`pdf`**, **`notifications`**, **`alerts`** (lowercase) e nomes de `env` próprios |
| Estados do contrato | Vocabulário PT (`rascunho`, `ativo`, …) | Convivência com **`active`** / **`ativo`** (ex.: geração de faturas) |
| Entrada ERP na spec | `/modulo-contratos` | **`/modulo-avancado/contratos`** |

**Recomendação:** evoluir o schema e o código a partir da **Phase1 real** + esta tabela, e ir **atualizando** os snippets SQL deste doc ou marcando-os como “referência histórica” quando deixarem de refletir a BD.

---

## 0.3 Backlog priorizado (o que falta fazer)

Ordenação por **dependência** e **valor operacional**. Itens podem ser tickets independentes desde que se respeitem pré-requisitos (ex.: webhook depois de API Autentique).

### P0 — Operacional e alinhamento

1. **Configurar ambiente:** `CONTRACT_NOTIFY_FROM_EMAIL`, `CONTRACT_NOTIFY_TEAM_EMAIL`, transporte de e-mail Cake, e `CONTRACT_PDF_STORAGE_PATH` em produção (fora de `TMP` se for requisito).
2. **URLs:** decidir se se mantém só `/modulo-avancado/...` ou se se introduz `/modulo-contratos` com **redirect** / canonical para não partir links.
3. **Migration opcional:** `ADD COLUMN IF NOT EXISTS` para fechar gaps face à §1.1 **sem** colisão com colunas existentes (`status`, `notes` vs `observacoes_cli` — escolher um nome canónico no ORM).
4. **RBAC §10:** executar SQL (ou adaptar `ON CONFLICT`) e associar permissões a perfis; até lá, rotas actuais seguem **role 0** / regras já usadas em `AdvancedContracts`.

### P1 — Gestão ERP completa (`ContractManagement` ou extensão forte de `AdvancedContracts`)

5. **Lista + KPIs** (§7.1) e filtros; export CSV opcional.
6. **Detalhe** (§7.2): acções aprovar, suspender, cancelar, renovações, download PDF/assinado com **caminho não exposto** na URL.
7. **Criação / edição** mínima (dados + serviços); depois **wizard** 4 passos (§7.3).
8. **CRUD de signatários** por contrato; alinhar nomes de colunas quando a BD Autentique for alargada.

### P2 — Portal cliente (spec §5.3 e §7.6)

9. **`PortalContratosController`** com ABAC: nunca expor `monthly_value`, `valor_total`, `notes`, IDs internos Autentique ao cliente.
10. **Redirect** de `/cliente/contratos-avancados` → `/cliente/contratos` (mantendo compatibilidade temporária).
11. **`faturas`** no portal de contratos: reutilizar ou encapsular `PortalAdvancedInvoices`; **solicitar renovação** exposto ao cliente com validações.

### P3 — Templates e UX

12. **`ContractTemplates`:** acções `preview`, `clonar`; **TinyMCE** e painel de variáveis `{{...}}` (§7.5).
13. **E-mails:** reforçar layout (logo, CTA, suporte) conforme §7.7.

### P4 — Autentique e produção (Fase 7)

14. **`AutentiqueService`** completo (§2.3): multipart, `criarDocumento`, `statusDocumento`, download, `validarWebhook`.
15. **`webhookAutentique`** + `Auth->allow` + rota pública com HMAC.
16. **Crontab** em servidor (§12) e pastas persistentes (§11) alinhadas a `Contract.pdf.storage_path`.

### P5 — Qualidade e modelo de dados

17. **Entity `Contract`:** virtual fields `status_label`, `dias_para_vencer` (§6.3).
18. **Validação `status`:** `inList` coerente com valores **reais** na BD (`active` + PT) quando o fluxo for unificado.
19. **Testes de fumo:** PDF, shell com flags `CONTRACT_ALERTS_*`, fluxo `aprovarRenovacao` via UI.

---

## 1. Banco de Dados — Migrations

### 1.1 `config/Migrations/20260407100000_ContractModulePhase1Expand.php`

> **Repo atual:** a migration real está em PHP e **não replica linha-a-linha** o `ALTER` abaixo (evita duplicar `status`, usa `observacoes_cli`, etc.). Use o **ficheiro em `config/Migrations/`** como fonte de verdade para o DDL aplicado; o bloco SQL serve como **modelo conceptual**.

```sql
-- ============================================================
-- Expandir tabela contracts com colunas que NÃO existem ainda
-- ============================================================
ALTER TABLE contracts
    ADD COLUMN IF NOT EXISTS template_id           INTEGER       DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS status                VARCHAR(30)   NOT NULL DEFAULT 'rascunho',
    ADD COLUMN IF NOT EXISTS nivel_sla             VARCHAR(30)   DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS notes_client          TEXT          DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS clausulas             JSONB         DEFAULT '[]',
    ADD COLUMN IF NOT EXISTS modulos_cobertos      JSONB         DEFAULT '[]',
    ADD COLUMN IF NOT EXISTS cobre_remoto          BOOLEAN       NOT NULL DEFAULT FALSE,
    ADD COLUMN IF NOT EXISTS cobre_presencial      BOOLEAN       NOT NULL DEFAULT FALSE,
    ADD COLUMN IF NOT EXISTS cobre_manutencao      BOOLEAN       NOT NULL DEFAULT FALSE,
    ADD COLUMN IF NOT EXISTS cobre_backup          BOOLEAN       NOT NULL DEFAULT FALSE,
    ADD COLUMN IF NOT EXISTS cobre_monitoramento   BOOLEAN       NOT NULL DEFAULT FALSE,
    ADD COLUMN IF NOT EXISTS limite_chamados       INTEGER       DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS dias_aviso_vencimento INTEGER       DEFAULT 30,
    -- Assinatura eletrônica
    ADD COLUMN IF NOT EXISTS signature_provider    VARCHAR(50)   DEFAULT 'autentique',
    ADD COLUMN IF NOT EXISTS autentique_doc_id     VARCHAR(255)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS autentique_status     VARCHAR(30)   DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS autentique_url        TEXT          DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS signed_file_url       TEXT          DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS sent_for_signature_at TIMESTAMP     DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS fully_signed_at       TIMESTAMP     DEFAULT NULL,
    -- PDFs locais
    ADD COLUMN IF NOT EXISTS pdf_path              VARCHAR(500)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS signed_pdf_path       VARCHAR(500)  DEFAULT NULL,
    -- Aprovação interna
    ADD COLUMN IF NOT EXISTS aprovado_por          INTEGER       DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS aprovado_em           TIMESTAMP     DEFAULT NULL,
    -- Ciclo de vida
    ADD COLUMN IF NOT EXISTS assinado_em           TIMESTAMP     DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS cancelado_em          TIMESTAMP     DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS motivo_cancelamento   TEXT          DEFAULT NULL,
    -- Versionamento / renovação
    ADD COLUMN IF NOT EXISTS versao                INTEGER       NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS contrato_pai_id       INTEGER       DEFAULT NULL;

-- Índices novos
CREATE INDEX IF NOT EXISTS idx_contracts_status     ON contracts(status);
CREATE INDEX IF NOT EXISTS idx_contracts_aut_doc    ON contracts(autentique_doc_id);
CREATE INDEX IF NOT EXISTS idx_contracts_pai        ON contracts(contrato_pai_id);

-- status possíveis:
-- rascunho | revisao | aguardando_assinatura | ativo | a_vencer |
-- em_renovacao | suspenso | encerrado | cancelado | recusado | assinatura_expirada

-- ============================================================
-- Expandir contract_services com colunas novas
-- ============================================================
ALTER TABLE contract_services
    ADD COLUMN IF NOT EXISTS tipo_item      VARCHAR(40)   DEFAULT 'servico',
    -- servico | licenca | hardware | cloud | suporte
    ADD COLUMN IF NOT EXISTS unidade        VARCHAR(30)   DEFAULT 'unid',
    ADD COLUMN IF NOT EXISTS franquia_horas DECIMAL(8,2)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS valor_unitario DECIMAL(12,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS valor_total    DECIMAL(12,2) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS vigencia_inicio DATE         DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS vigencia_fim   DATE          DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS ativo          BOOLEAN       NOT NULL DEFAULT TRUE,
    ADD COLUMN IF NOT EXISTS observacoes    TEXT          DEFAULT NULL;
```

### 1.2 `config/Migrations/20260407100001_ContractNewTables.php`

> **Repo atual:** este ficheiro de migration **não está presente**; tabelas análogas foram criadas pela **`20260407100000_ContractModulePhase1Expand.php`** com DDL ligeiramente diferente (ver **§0.2**). O SQL abaixo permanece como **referência** para futuras migrations incrementais ou para harmonizar colunas em falta.

```sql
-- ============================================================
-- Templates de contrato
-- ============================================================
CREATE TABLE IF NOT EXISTS contract_templates (
    id              SERIAL PRIMARY KEY,
    idempresa       INTEGER NOT NULL REFERENCES empresas(id),
    nome            VARCHAR(150) NOT NULL,
    tipo_contrato   VARCHAR(40)  NOT NULL DEFAULT 'servico',
    descricao       TEXT,
    conteudo_html   TEXT NOT NULL,
    clausulas_padrao JSONB DEFAULT '[]',
    variaveis       JSONB DEFAULT '[]',
    ativo           BOOLEAN NOT NULL DEFAULT TRUE,
    versao          INTEGER NOT NULL DEFAULT 1,
    created         TIMESTAMP NOT NULL DEFAULT NOW(),
    modified        TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Variáveis disponíveis nos templates (usar {{nome_var}} no HTML):
-- {{cliente_razaosocial}}  {{cliente_cnpj}}  {{cliente_endereco}}
-- {{empresa_razaosocial}}  {{empresa_cnpj}}
-- {{numero_contrato}}      {{vigencia_inicio}}  {{vigencia_fim}}
-- {{valor_mensal}}         {{valor_total}}
-- {{horas_incluidas}}      {{nivel_sla}}
-- {{servicos_contratados}} → tabela HTML gerada automaticamente
-- {{clausulas}}            → lista HTML gerada automaticamente
-- {{data_hoje}}

-- ============================================================
-- Signatários de cada contrato
-- ============================================================
CREATE TABLE IF NOT EXISTS contract_signatories (
    id              SERIAL PRIMARY KEY,
    contract_id     INTEGER NOT NULL REFERENCES contracts(id) ON DELETE CASCADE,
    nome            VARCHAR(200) NOT NULL,
    email           VARCHAR(200) NOT NULL,
    cpf             VARCHAR(20)  DEFAULT NULL,
    tipo            VARCHAR(30)  NOT NULL DEFAULT 'cliente',
    -- cliente | fornecedor | testemunha | juridico | gestor | aprovador
    ordem           INTEGER      NOT NULL DEFAULT 1,
    obrigatorio     BOOLEAN      NOT NULL DEFAULT TRUE,
    auth_type       VARCHAR(50)  NOT NULL DEFAULT 'email',
    -- email | sms | pix | selfie | icp_brasil
    action_type     VARCHAR(30)  NOT NULL DEFAULT 'SIGN',
    -- Autentique actions: SIGN | APPROVE | WITNESS | ENDORSE | PARTY | INTERVENING
    autentique_signer_id VARCHAR(255) DEFAULT NULL,
    status          VARCHAR(30)  DEFAULT 'pendente',
    -- pendente | enviado | visualizado | assinado | recusado
    link_assinatura TEXT         DEFAULT NULL,
    assinado_em     TIMESTAMP    DEFAULT NULL,
    visualizado_em  TIMESTAMP    DEFAULT NULL,
    recusado_em     TIMESTAMP    DEFAULT NULL,
    motivo_recusa   TEXT         DEFAULT NULL,
    ip_assinatura   VARCHAR(50)  DEFAULT NULL,
    created         TIMESTAMP NOT NULL DEFAULT NOW(),
    modified        TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ============================================================
-- Log de eventos Autentique (webhooks + chamadas API)
-- ============================================================
CREATE TABLE IF NOT EXISTS contract_autentique_logs (
    id              SERIAL PRIMARY KEY,
    contract_id     INTEGER NOT NULL REFERENCES contracts(id),
    evento          VARCHAR(100) NOT NULL,
    -- document_created | document_all_signed | signer_signed
    -- document_rejected | document_canceled | document_expired
    -- signer_reminder_sent | webhook_recebido
    payload         JSONB        DEFAULT NULL,
    resposta_api    JSONB        DEFAULT NULL,
    user_id         INTEGER      DEFAULT NULL,
    created         TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ============================================================
-- Renovações de contrato
-- ============================================================
CREATE TABLE IF NOT EXISTS contract_renewals (
    id                   SERIAL PRIMARY KEY,
    contract_id          INTEGER NOT NULL REFERENCES contracts(id),
    novo_contract_id     INTEGER DEFAULT NULL REFERENCES contracts(id),
    status               VARCHAR(30) NOT NULL DEFAULT 'pendente',
    -- pendente | aprovada | recusada | expirada
    solicitado_por       INTEGER     DEFAULT NULL,
    solicitado_em        TIMESTAMP   DEFAULT NOW(),
    nova_vigencia_inicio DATE        DEFAULT NULL,
    nova_vigencia_fim    DATE        DEFAULT NULL,
    novo_valor_mensal    DECIMAL(12,2) DEFAULT NULL,
    observacoes          TEXT        DEFAULT NULL,
    aprovado_por         INTEGER     DEFAULT NULL,
    aprovado_em          TIMESTAMP   DEFAULT NULL,
    created              TIMESTAMP NOT NULL DEFAULT NOW(),
    modified             TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ============================================================
-- Notificações enviadas sobre contratos
-- ============================================================
CREATE TABLE IF NOT EXISTS contract_notifications (
    id              SERIAL PRIMARY KEY,
    contract_id     INTEGER NOT NULL REFERENCES contracts(id),
    tipo            VARCHAR(60) NOT NULL,
    -- vencimento_30d | vencimento_15d | vencimento_7d | vencimento_1d
    -- assinatura_pendente | contrato_assinado | renovacao_disponivel
    -- inadimplencia | cancelamento | novo_contrato | lembrete_assinatura
    destinatario    VARCHAR(30) NOT NULL DEFAULT 'cliente',
    canal           VARCHAR(20) NOT NULL DEFAULT 'email',
    enviado         BOOLEAN     NOT NULL DEFAULT FALSE,
    enviado_em      TIMESTAMP   DEFAULT NULL,
    erro            TEXT        DEFAULT NULL,
    created         TIMESTAMP NOT NULL DEFAULT NOW()
);
```

---

## 2. Integração Autentique

### Sobre a API

A Autentique usa **GraphQL** sobre HTTPS com Bearer Token.  
- URL única: `POST https://api.autentique.com.br/v2/graphql`
- Upload de arquivo: `multipart/form-data` com spec `graphql-multipart-request-spec`
- Auth: header `Authorization: Bearer {TOKEN}`

**Status dos documentos na Autentique:**
`DRAFT` | `PENDING` | `SIGNED` | `REJECTED` | `CANCELED` | `EXPIRED`

**Eventos de webhook:**
`document_created` | `document_viewed` | `document_signed` | `document_all_signed` | `document_rejected` | `document_canceled` | `document_expired` | `signer_reminder_sent`

### 2.1 `config/app.php` — adicionar chave `Contract`

```php
'Contract' => [
    'Autentique' => [
        'api_url'        => 'https://api.autentique.com.br/v2/graphql',
        'token'          => env('AUTENTIQUE_TOKEN', ''),
        'sandbox'        => (bool)env('AUTENTIQUE_SANDBOX', false),
        'webhook_secret' => env('AUTENTIQUE_WEBHOOK_SECRET', ''),
    ],
    'Pdf' => [
        'storage_path' => ROOT . DS . 'uploads' . DS . 'contratos',
    ],
    'Notifications' => [
        'from_email' => env('CONTRACT_EMAIL_FROM', 'contratos@pgm.com.br'),
        'from_name'  => env('CONTRACT_EMAIL_FROM_NAME', 'PGM — Contratos'),
        'team_email' => env('CONTRACT_TEAM_EMAIL', 'financeiro@pgm.com.br'),
    ],
],
```

### 2.2 `.env` — variáveis necessárias

```
AUTENTIQUE_TOKEN=seu_token_aqui
AUTENTIQUE_SANDBOX=false
AUTENTIQUE_WEBHOOK_SECRET=string_aleatoria_segura_32chars
CONTRACT_EMAIL_FROM=contratos@pgm.com.br
CONTRACT_EMAIL_FROM_NAME="PGM — Contratos"
CONTRACT_TEAM_EMAIL=financeiro@pgm.com.br
```

### 2.3 `src/Service/AutentiqueService.php`

```php
<?php
namespace App\Service;

use Cake\Core\Configure;

/**
 * Integração com a API GraphQL v2 da Autentique.com.br
 * Docs: https://docs.autentique.com.br/api
 *
 * Usa cURL nativo (sem Guzzle) — compatível com PHP 5.6+.
 * Upload de PDF usa multipart/form-data (graphql-multipart-request-spec).
 */
class AutentiqueService
{
    private $token;
    private $apiUrl;

    public function __construct()
    {
        $cfg         = Configure::read('Contract.Autentique');
        $this->token = $cfg['token'];
        $this->apiUrl = $cfg['api_url'];
    }

    /**
     * Cria documento no Autentique, faz upload do PDF e envia convites.
     *
     * @param  string $pdfPath  Caminho absoluto do PDF
     * @param  array  $signers  [{nome, email, action_type, auth_type, ordem}]
     * @param  string $docName  Nome do documento
     * @param  array  $options  [message, reminder_days, expiration_days]
     * @return array  {doc_id, signers: [{autentique_signer_id, email, link_assinatura}]}
     */
    public function criarDocumento(string $pdfPath, array $signers, string $docName, array $options = []): array
    {
        $mutation = <<<'GQL'
mutation CreateDocumentMutation(
  $document: DocumentInput!,
  $signers: [SignerInput!]!,
  $file: Upload!
) {
  createDocument(document: $document, signers: $signers, file: $file) {
    id name
    signers {
      public_id name email
      action { name }
      link { short_link }
      signed_at rejected_at
    }
  }
}
GQL;

        $signersInput = [];
        foreach ($signers as $s) {
            $auth = $s['auth_type'] ?? 'email';
            $signersInput[] = [
                'name'               => $s['nome'],
                'email'              => $s['email'],
                'action'             => $s['action_type'] ?? 'SIGN',
                'auth_pix'           => $auth === 'pix',
                'auth_selfie'        => $auth === 'selfie',
                'auth_itcp'          => $auth === 'icp_brasil',
                'sendAutomaticEmail' => true,
            ];
        }

        $docInput = [
            'name'          => $docName,
            'message'       => $options['message'] ?? null,
            'reminder'      => $options['reminder_days'] ?? 3,
            'sortable'      => count($signers) > 1,
            'refusable'     => true,
        ];
        if (!empty($options['expiration_days'])) {
            $docInput['expiration_at'] = date('Y-m-d\TH:i:s\Z', strtotime('+' . (int)$options['expiration_days'] . ' days'));
        }

        $operations = json_encode([
            'query'     => $mutation,
            'variables' => ['document' => $docInput, 'signers' => $signersInput, 'file' => null],
        ]);

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->token],
            CURLOPT_POSTFIELDS     => [
                'operations' => $operations,
                'map'        => json_encode(['0' => ['variables.file']]),
                '0'          => new \CURLFile($pdfPath, 'application/pdf', basename($pdfPath)),
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err) throw new \RuntimeException("cURL: {$err}");
        if ($httpCode !== 200) throw new \RuntimeException("Autentique HTTP {$httpCode}: {$response}");

        $data = json_decode($response, true);
        if (!empty($data['errors'])) throw new \RuntimeException('GraphQL error: ' . json_encode($data['errors']));

        $doc    = $data['data']['createDocument'];
        $result = ['doc_id' => $doc['id'], 'signers' => []];
        foreach ($doc['signers'] as $sig) {
            $result['signers'][] = [
                'autentique_signer_id' => $sig['public_id'],
                'email'                => $sig['email'],
                'link_assinatura'      => $sig['link']['short_link'] ?? null,
            ];
        }
        return $result;
    }

    /**
     * Consulta status do documento.
     * Retorna: {status (string lowercase), signed_url, signers: [{public_id,email,assinado,assinado_em,...}]}
     */
    public function statusDocumento(string $docId): array
    {
        $query = <<<GQL
query {
  document(id: "{$docId}") {
    id name status
    files { original signed }
    signers {
      public_id name email
      signed_at rejected_at
      views { created_at }
      link { short_link }
    }
    events { created_at type author { name email } }
  }
}
GQL;
        $data = $this->graphql($query);
        $doc  = $data['data']['document'] ?? null;
        if (!$doc) return ['status' => 'erro', 'signers' => []];

        $signers = [];
        foreach ($doc['signers'] as $sig) {
            $signers[] = [
                'public_id'    => $sig['public_id'],
                'email'        => $sig['email'],
                'link'         => $sig['link']['short_link'] ?? null,
                'assinado'     => !empty($sig['signed_at']),
                'assinado_em'  => $sig['signed_at'] ?? null,
                'recusado'     => !empty($sig['rejected_at']),
                'visualizado'  => !empty($sig['views']),
            ];
        }

        return [
            'status'       => strtolower($doc['status']),
            'signed_url'   => $doc['files']['signed'] ?? null,
            'original_url' => $doc['files']['original'] ?? null,
            'signers'      => $signers,
            'events'       => $doc['events'] ?? [],
        ];
    }

    /** Reenvia e-mail de convite para um signatário específico */
    public function reenviarConvite(string $docId, string $signerId): bool
    {
        $mut  = "mutation { resendDocument(document_id: \"{$docId}\", signer_id: \"{$signerId}\") }";
        $data = $this->graphql($mut);
        return !empty($data['data']['resendDocument']);
    }

    /** Cancela documento no Autentique */
    public function cancelarDocumento(string $docId): bool
    {
        $mut  = "mutation { cancelDocument(id: \"{$docId}\") }";
        $data = $this->graphql($mut);
        return !empty($data['data']['cancelDocument']);
    }

    /** Baixa PDF assinado para um caminho local */
    public function downloadSignedPdf(string $docId, string $destPath): bool
    {
        $status = $this->statusDocumento($docId);
        $url    = $status['signed_url'] ?? null;
        if (!$url) return false;

        $dir = dirname($destPath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $content = @file_get_contents($url);
        if (!$content) return false;
        return file_put_contents($destPath, $content) !== false;
    }

    /**
     * Valida HMAC-SHA256 do webhook.
     * Header esperado: X-Autentique-Signature
     */
    public function validarWebhook(string $payload, string $signature): bool
    {
        $secret = Configure::read('Contract.Autentique.webhook_secret');
        if (empty($secret)) return true; // dev: aceitar sem secret
        return hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
    }

    private function graphql(string $q): array
    {
        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->token, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode(['query' => $q]),
        ]);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err) throw new \RuntimeException("cURL: {$err}");
        return json_decode($res, true) ?: [];
    }
}
```

---

## 3. Services

### 3.1 `src/Service/ContractPdfService.php`

```php
<?php
namespace App\Service;

use Cake\Core\Configure;

/**
 * Gera PDF de contrato usando mPDF (já instalado no projeto).
 * Substitui variáveis {{nome}} no template HTML.
 */
class ContractPdfService
{
    public function gerar($contract, $template, array $servicos = []): string
    {
        $storagePath = Configure::read('Contract.Pdf.storage_path');
        $pdfDir = $storagePath . DS . 'pdfs';
        if (!is_dir($pdfDir)) mkdir($pdfDir, 0755, true);

        $html = $this->substituir($template->conteudo_html, $contract, $servicos);

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8', 'format' => 'A4',
            'margin_top' => 20, 'margin_bottom' => 20,
            'margin_left' => 25, 'margin_right' => 25,
        ]);
        $mpdf->SetTitle('Contrato ' . h($contract->code ?? (string)$contract->id));
        $mpdf->SetAuthor('PGM Soluções em TI');
        $mpdf->WriteHTML($html);

        $path = $pdfDir . DS . sprintf('contrato_%d_v%d_%s.pdf', $contract->id, $contract->versao ?? 1, date('Ymd'));
        $mpdf->Output($path, 'F');
        return $path;
    }

    private function substituir(string $html, $contract, array $servicos): string
    {
        $c = $contract->cliente ?? (object)[];
        $e = $contract->empresa  ?? (object)[];

        $vars = [
            '{{cliente_razaosocial}}'  => h($c->razaosocial ?? $c->nome ?? ''),
            '{{cliente_cnpj}}'         => h($c->cnpj ?? $c->cpf ?? ''),
            '{{cliente_endereco}}'     => h(implode(', ', array_filter([$c->logradouro ?? '', $c->numero ?? '', $c->bairro ?? '', $c->cidade ?? '', $c->estado ?? '']))),
            '{{empresa_razaosocial}}'  => h($e->razaosocial ?? ''),
            '{{empresa_cnpj}}'         => h($e->cnpj ?? ''),
            '{{numero_contrato}}'      => h($contract->code ?? ''),
            '{{vigencia_inicio}}'      => $this->fmt($contract->start_date),
            '{{vigencia_fim}}'         => $this->fmt($contract->end_date, 'Indeterminado'),
            '{{valor_mensal}}'         => 'R$ ' . number_format((float)($contract->monthly_value ?? 0), 2, ',', '.'),
            '{{valor_total}}'          => 'R$ ' . number_format((float)($contract->valor_total ?? 0), 2, ',', '.'),
            '{{horas_incluidas}}'      => ($contract->included_hours ?? 0) . 'h/mês',
            '{{nivel_sla}}'            => h($contract->nivel_sla ?? '—'),
            '{{servicos_contratados}}' => $this->tabelaServicos($servicos),
            '{{clausulas}}'            => $this->listaClausulas((array)($contract->clausulas ?? [])),
            '{{data_hoje}}'            => date('d/m/Y'),
        ];

        return str_replace(array_keys($vars), array_values($vars), $html);
    }

    private function fmt($date, string $fallback = ''): string
    {
        if (empty($date)) return $fallback;
        if ($date instanceof \DateTimeInterface) return $date->format('d/m/Y');
        return date('d/m/Y', strtotime((string)$date));
    }

    private function tabelaServicos(array $servicos): string
    {
        if (empty($servicos)) return '<p><em>Conforme proposta comercial.</em></p>';
        $html = '<table border="1" cellpadding="5" style="width:100%;border-collapse:collapse;">';
        $html .= '<tr style="background:#f5f5f5"><th>Serviço</th><th>Qtde</th><th>Vl. Unit.</th><th>Total Mensal</th></tr>';
        foreach ($servicos as $s) {
            $nome = is_object($s) ? ($s->service_name ?? '') : ($s['service_name'] ?? '');
            $qty  = is_object($s) ? ($s->quantity ?? '1')  : ($s['quantity'] ?? '1');
            $und  = is_object($s) ? ($s->unidade ?? 'unid') : ($s['unidade'] ?? 'unid');
            $vu   = is_object($s) ? ($s->valor_unitario ?? 0) : ($s['valor_unitario'] ?? 0);
            $vt   = is_object($s) ? ($s->valor_total    ?? 0) : ($s['valor_total']    ?? 0);
            $html .= "<tr><td>".h($nome)."</td><td>".h($qty)." ".h($und)."</td><td>R$ ".number_format((float)$vu,2,',','.')."</td><td>R$ ".number_format((float)$vt,2,',','.')."</td></tr>";
        }
        return $html . '</table>';
    }

    private function listaClausulas(array $clausulas): string
    {
        if (empty($clausulas)) return '';
        $html = '<ol>';
        foreach ($clausulas as $c) {
            $html .= '<li><strong>' . h($c['titulo'] ?? '') . '</strong><br>' . nl2br(h($c['texto'] ?? '')) . '</li>';
        }
        return $html . '</ol>';
    }
}
```

### 3.2 `src/Service/ContractNotificationService.php`

```php
<?php
namespace App\Service;

use Cake\Mailer\Email;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * Envia e-mails sobre eventos de contratos.
 * Templates em: src/Template/Email/html/contract_*.ctp
 */
class ContractNotificationService
{
    private function email($from, $fromName, $to, $subject, $template, $vars): void
    {
        (new Email('default'))
            ->setFrom([$from => $fromName])
            ->setTo($to)
            ->setSubject($subject)
            ->setTemplate($template)
            ->setViewVars($vars)
            ->send();
    }

    public function avisarVencimento($contract, int $dias): void
    {
        $tipo  = 'vencimento_' . $dias . 'd';
        $table = TableRegistry::getTableLocator()->get('ContractNotifications');

        if ($table->find()->where(['contract_id' => $contract->id, 'tipo' => $tipo, 'enviado' => true])->count() > 0) return;

        $from  = Configure::read('Contract.Notifications.from_email');
        $name  = Configure::read('Contract.Notifications.from_name');
        $team  = Configure::read('Contract.Notifications.team_email');

        $this->email($from, $name, $team, "⚠️ Contrato {$contract->code} vence em {$dias} dia(s)", 'contract_vencimento', compact('contract', 'dias'));

        if (!empty($contract->cliente->email)) {
            $this->email($from, $name, $contract->cliente->email, "Seu contrato vence em {$dias} dia(s)", 'contract_vencimento_cliente', compact('contract', 'dias'));
        }

        $n = $table->newEntity(['contract_id' => $contract->id, 'tipo' => $tipo, 'destinatario' => 'ambos', 'canal' => 'email', 'enviado' => true, 'enviado_em' => date('Y-m-d H:i:s')]);
        $table->save($n);
    }

    public function notificarAssinado($contract): void
    {
        $from = Configure::read('Contract.Notifications.from_email');
        $name = Configure::read('Contract.Notifications.from_name');
        if (!empty($contract->cliente->email)) {
            $this->email($from, $name, $contract->cliente->email, "✅ Contrato {$contract->code} assinado!", 'contract_assinado_cliente', compact('contract'));
        }
    }

    public function notificarNovoContrato($contract): void
    {
        $from = Configure::read('Contract.Notifications.from_email');
        $name = Configure::read('Contract.Notifications.from_name');
        if (!empty($contract->cliente->email)) {
            $this->email($from, $name, $contract->cliente->email, "Novo contrato disponível — {$contract->code}", 'contract_novo_cliente', compact('contract'));
        }
    }

    public function lembrarAssinatura($contract, $signatory): void
    {
        if (empty($signatory->email) || empty($signatory->link_assinatura)) return;
        $from = Configure::read('Contract.Notifications.from_email');
        $name = Configure::read('Contract.Notifications.from_name');
        $this->email($from, $name, $signatory->email, "Lembrete: sua assinatura é necessária — {$contract->code}", 'contract_lembrar_assinatura', compact('contract', 'signatory'));
    }
}
```

### 3.3 `src/Service/ContractRenewalService.php`

```php
<?php
namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * Fluxo de renovação:
 * 1. Shell detecta "a_vencer" → solicitarRenovacao()
 * 2. Financeiro edita e aprova → aprovarRenovacao()
 * 3. Novo contrato clonado (contrato_pai_id = original)
 * 4. Novo contrato segue fluxo normal (rascunho → ativo)
 * 5. Ao ficar ativo, original recebe status=encerrado
 */
class ContractRenewalService
{
    public function solicitarRenovacao($contract, ?int $userId = null)
    {
        $table    = TableRegistry::getTableLocator()->get('ContractRenewals');
        $existing = $table->find()->where(['contract_id' => $contract->id, 'status IN' => ['pendente', 'aprovada']])->first();
        if ($existing) return $existing;

        $vf    = $contract->end_date;
        $vfStr = ($vf instanceof \DateTimeInterface) ? $vf->format('Y-m-d') : (string)$vf;

        return $table->save($table->newEntity([
            'contract_id'          => $contract->id,
            'status'               => 'pendente',
            'solicitado_por'       => $userId,
            'solicitado_em'        => date('Y-m-d H:i:s'),
            'nova_vigencia_inicio' => date('Y-m-d'),
            'nova_vigencia_fim'    => $vfStr ? date('Y-m-d', strtotime($vfStr . ' +1 year')) : null,
            'novo_valor_mensal'    => $contract->monthly_value,
        ]));
    }

    public function aprovarRenovacao($renewal, array $novos, int $userId)
    {
        $contracts = TableRegistry::getTableLocator()->get('Contracts');
        $original  = $contracts->get($renewal->contract_id, ['contain' => ['ContractServices']]);

        $novoNumero = $this->proximoNumero($original->idempresa);

        $novoData = array_merge($original->toArray(), [
            'id'               => null,
            'code'             => $novoNumero,
            'status'           => 'rascunho',
            'start_date'       => $novos['vigencia_inicio'] ?? $renewal->nova_vigencia_inicio,
            'end_date'         => $novos['vigencia_fim']    ?? $renewal->nova_vigencia_fim,
            'monthly_value'    => $novos['valor_mensal']    ?? $renewal->novo_valor_mensal,
            'versao'           => 1,
            'contrato_pai_id'  => $original->id,
            'autentique_doc_id' => null, 'autentique_status' => null,
            'pdf_path' => null, 'signed_pdf_path' => null,
            'assinado_em' => null, 'aprovado_por' => null, 'aprovado_em' => null,
        ]);

        $novo = $contracts->saveOrFail($contracts->newEntity($novoData));

        $services = TableRegistry::getTableLocator()->get('ContractServices');
        foreach ($original->contract_services ?? [] as $svc) {
            $services->save($services->newEntity(array_merge($svc->toArray(), ['id' => null, 'contract_id' => $novo->id])));
        }

        $table = TableRegistry::getTableLocator()->get('ContractRenewals');
        $table->patchEntity($renewal, ['status' => 'aprovada', 'novo_contract_id' => $novo->id, 'aprovado_por' => $userId, 'aprovado_em' => date('Y-m-d H:i:s')]);
        $table->save($renewal);

        $contracts->patchEntity($original, ['status' => 'em_renovacao']);
        $contracts->save($original);

        return $novo;
    }

    private function proximoNumero(int $idempresa): string
    {
        $contracts = TableRegistry::getTableLocator()->get('Contracts');
        $ultimo = $contracts->find()->where(['idempresa' => $idempresa, 'code IS NOT' => null])->order(['id' => 'DESC'])->select(['code'])->first();
        $ano = date('Y');
        if (!$ultimo || !$ultimo->code) return "CONT-0001/{$ano}";
        preg_match('/(\d+)/', $ultimo->code, $m);
        return sprintf('CONT-%04d/%s', isset($m[1]) ? (int)$m[1] + 1 : 1, $ano);
    }
}
```

---

## 4. Shell de Alertas (Cron)

### `src/Shell/ContractAlertsShell.php`

```php
<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use App\Service\AutentiqueService;
use App\Service\ContractNotificationService;
use App\Service\ContractRenewalService;

/**
 * Crontab:
 *   0 8    * * * cd /var/www/portal && php bin/cake contract_alerts
 *   0 */2  * * * cd /var/www/portal && php bin/cake contract_alerts sincronizarAutentique
 */
class ContractAlertsShell extends Shell
{
    public function main()
    {
        $this->out('[ContractAlerts] Iniciando...');
        $this->verificarVencimentos();
        $this->verificarRenovacoesAuto();
        $this->sincronizarAutentique();
        $this->out('[ContractAlerts] Concluído.');
    }

    public function sincronizarAutentique()
    {
        $contracts  = TableRegistry::getTableLocator()->get('Contracts');
        $notif      = new ContractNotificationService();
        $autentique = new AutentiqueService();
        $storage    = \Cake\Core\Configure::read('Contract.Pdf.storage_path');

        $pendentes = $contracts->find()->contain(['Clientes'])->where([
            'status' => 'aguardando_assinatura', 'autentique_doc_id IS NOT' => null,
        ])->all();

        foreach ($pendentes as $c) {
            try {
                $status = $autentique->statusDocumento($c->autentique_doc_id);
                if ($status['status'] === 'signed') {
                    $signedPath = $storage . DS . 'signed' . DS . 'signed_' . $c->id . '.pdf';
                    $autentique->downloadSignedPdf($c->autentique_doc_id, $signedPath);
                    $contracts->patchEntity($c, [
                        'status' => 'ativo', 'autentique_status' => 'signed',
                        'signed_pdf_path' => $signedPath, 'signed_file_url' => $status['signed_url'] ?? null,
                        'assinado_em' => date('Y-m-d H:i:s'), 'fully_signed_at' => date('Y-m-d H:i:s'),
                    ]);
                    $contracts->save($c);
                    $notif->notificarAssinado($c);
                    $this->out("  ✅ Assinado: #{$c->id} ({$c->code})");
                } elseif (in_array($status['status'], ['rejected', 'canceled', 'expired'])) {
                    $newStatus = $status['status'] === 'expired' ? 'assinatura_expirada' : 'recusado';
                    $contracts->patchEntity($c, ['status' => $newStatus, 'autentique_status' => $status['status']]);
                    $contracts->save($c);
                    $this->out("  ❌ {$status['status']}: #{$c->id}");
                }
            } catch (\Exception $e) {
                $this->err("  Erro #{$c->id}: " . $e->getMessage());
            }
        }
    }

    private function verificarVencimentos(): void
    {
        $contracts = TableRegistry::getTableLocator()->get('Contracts');
        $notif     = new ContractNotificationService();
        $hoje      = date('Y-m-d');
        $d30       = date('Y-m-d', strtotime('+30 days'));

        // Encerrar vencidos
        foreach ($contracts->find()->where(['status IN' => ['ativo', 'a_vencer'], 'end_date <' => $hoje])->all() as $c) {
            $contracts->patchEntity($c, ['status' => 'encerrado']);
            $contracts->save($c);
            $this->out("  Encerrado: #{$c->id}");
        }

        // Alertas de vencimento iminente
        foreach ($contracts->find()->contain(['Clientes'])->where(['status IN' => ['ativo', 'a_vencer'], 'end_date >=' => $hoje, 'end_date <=' => $d30])->all() as $c) {
            $vf   = $c->end_date instanceof \DateTimeInterface ? $c->end_date->format('Y-m-d') : (string)$c->end_date;
            $dias = (int)ceil((strtotime($vf) - strtotime($hoje)) / 86400);
            if ($c->status === 'ativo') {
                $contracts->patchEntity($c, ['status' => 'a_vencer']);
                $contracts->save($c);
            }
            if (in_array($dias, [30, 15, 7, 1])) {
                $notif->avisarVencimento($c, $dias);
                $this->out("  Aviso {$dias}d: #{$c->id}");
            }
        }
    }

    private function verificarRenovacoesAuto(): void
    {
        $contracts = TableRegistry::getTableLocator()->get('Contracts');
        $renewal   = new ContractRenewalService();
        foreach ($contracts->find()->where(['auto_renew' => true, 'status' => 'a_vencer'])->all() as $c) {
            $renewal->solicitarRenovacao($c);
            $this->out("  Auto-renovação solicitada: #{$c->id}");
        }
    }
}
```

---

## 5. Controllers

### 5.1 `ContractManagementController.php` — Actions

| Action | Método | URL | Quem pode |
|--------|--------|-----|-----------|
| `index()` | GET | `/modulo-contratos` | role=0 + contracts.view |
| `view($id)` | GET | `/modulo-contratos/view/:id` | role=0 + contracts.view |
| `add()` | GET/POST | `/modulo-contratos/add` | contracts.manage |
| `edit($id)` | GET/POST | `/modulo-contratos/edit/:id` | contracts.manage |
| `addServicos($id)` | GET/POST | `/modulo-contratos/servicos/:id` | contracts.manage |
| `addSignatarios($id)` | GET/POST | `/modulo-contratos/signatarios/:id` | contracts.manage |
| `gerarPdf($id)` | POST | `/modulo-contratos/gerar-pdf/:id` | contracts.manage |
| `enviarAssinatura($id)` | GET/POST | `/modulo-contratos/enviar-assinatura/:id` | contracts.assinar |
| `aprovar($id)` | POST | `/modulo-contratos/aprovar/:id` | contracts.aprovar |
| `suspender($id)` | POST | `/modulo-contratos/suspender/:id` | contracts.cancelar |
| `cancelar($id)` | POST | `/modulo-contratos/cancelar/:id` | contracts.cancelar |
| `reenviarLink($id)` | POST | `/modulo-contratos/reenviar-link/:id` | contracts.assinar |
| `verRenovacoes($id)` | GET | `/modulo-contratos/renovacoes/:id` | contracts.view |
| `aprovarRenovacao($id)` | GET/POST | `/modulo-contratos/aprovar-renovacao/:id` | contracts.renovar |
| `recusarRenovacao($id)` | POST | `/modulo-contratos/recusar-renovacao/:id` | contracts.renovar |
| `downloadPdf($id)` | GET | `/modulo-contratos/pdf/:id` | contracts.view |
| `downloadSigned($id)` | GET | `/modulo-contratos/pdf-assinado/:id` | contracts.view |
| `exportar()` | GET | `/modulo-contratos/exportar` | contracts.view |
| `webhookAutentique()` | POST | `/modulo-contratos/webhook/autentique` | público (HMAC) |

**KPIs no `index()`:**
```php
$kpis = [
    'ativos'                => /* count status=ativo */,
    'a_vencer'              => /* count status=a_vencer */,
    'aguardando_assinatura' => /* count status=aguardando_assinatura */,
    'em_renovacao'          => /* count status=em_renovacao */,
    'valor_mensal_total'    => /* SUM(monthly_value) WHERE status IN (ativo,a_vencer) */,
];
```

**`enviarAssinatura($id)` — lógica principal:**
1. Verificar que contract tem `contract_signatories` cadastrados
2. Gerar PDF via `ContractPdfService` se `pdf_path` vazio ou arquivo não existe
3. Chamar `AutentiqueService::criarDocumento()` com PDF + signatários
4. Salvar `autentique_signer_id` e `link_assinatura` em cada `ContractSignatory`
5. Atualizar contrato: `status=aguardando_assinatura`, `autentique_doc_id`, `sent_for_signature_at`
6. Inserir em `ContractAutentiqueLogs`
7. Chamar `ContractNotificationService::notificarNovoContrato()`

**`webhookAutentique()` — liberar do Auth:**
```php
// Em beforeFilter() do controller:
$this->Auth->allow('webhookAutentique');
```
Lógica:
1. Validar HMAC via `AutentiqueService::validarWebhook()`
2. Parsear `$data['event']` e `$data['document']['id']`
3. Buscar contrato por `autentique_doc_id`
4. `document_all_signed` → status=ativo, baixar PDF, notificar
5. `signer_signed` → atualizar `ContractSignatory.status=assinado`
6. `document_rejected` → status=recusado
7. `document_expired` → status=assinatura_expirada
8. Inserir em `ContractAutentiqueLogs`
9. Responder HTTP 200 `{"ok": true}`

**Servir PDFs (nunca expor caminho real):**
```php
$this->response = $this->response
    ->withType('application/pdf')
    ->withHeader('Content-Disposition', 'attachment; filename="Contrato-' . h($contract->code) . '.pdf"')
    ->withFile($path);
return $this->response;
```

### 5.2 `ContractTemplatesController.php`
Actions: `index`, `add`, `edit`, `delete`, `preview`, `clonar` — rotas sob `/contract-templates/`.

### 5.3 `PortalContratosController.php` — Portal Cliente
Actions: `index`, `view`, `faturas`, `downloadPdf`, `solicitarRenovacao`, `franquia`.

**ABAC obrigatória em todos os métodos:**
- Resolver `idcliente` via CPF/CNPJ por empresa ativa (padrão de `TicketsController`)
- Nunca expor: `monthly_value`, `valor_total`, `notes` (interno)
- Verificar `contract.idcliente == clienteAtual->id` antes de servir qualquer recurso

---

## 6. Models

### 6.1 Atualizar `ContractsTable.php`

Adicionar no `initialize()`:
```php
$this->belongsTo('ContractTemplates', ['foreignKey' => 'template_id']);
$this->hasMany('ContractSignatories', ['dependent' => true]);
$this->hasMany('ContractAutentiqueLogs');
$this->hasMany('ContractRenewals');
$this->hasMany('ContractNotifications');
$this->belongsTo('ContratoPai', ['className' => 'Contracts', 'foreignKey' => 'contrato_pai_id']);
$this->hasMany('ContratosFilhos', ['className' => 'Contracts', 'foreignKey' => 'contrato_pai_id']);
```

Adicionar no validator:
```php
$validator->inList('status', [
    'rascunho','revisao','aguardando_assinatura','ativo','a_vencer',
    'em_renovacao','suspenso','encerrado','cancelado','recusado','assinatura_expirada'
]);
```

### 6.2 Criar Tables novas

Criar um arquivo para cada:
- `ContractTemplatesTable` → `contract_templates`, `belongsTo('Empresas')`, `hasMany('Contracts', ['foreignKey'=>'template_id'])`
- `ContractSignatoriesTable` → `contract_signatories`, `belongsTo('Contracts')`
- `ContractAutentiqueLogsTable` → `contract_autentique_logs`, `belongsTo('Contracts')`
- `ContractRenewalsTable` → `contract_renewals`, `belongsTo('Contracts')`, `belongsTo('ContratosNovos',['className'=>'Contracts','foreignKey'=>'novo_contract_id'])`
- `ContractNotificationsTable` → `contract_notifications`, `belongsTo('Contracts')`

### 6.3 `Contract.php` Entity — adicionar virtual fields

```php
protected function _getStatusLabel(): string {
    return ['rascunho'=>'Rascunho','revisao'=>'Em Revisão','aguardando_assinatura'=>'Aguard. Assinatura',
            'ativo'=>'Ativo','a_vencer'=>'A Vencer','em_renovacao'=>'Em Renovação','suspenso'=>'Suspenso',
            'encerrado'=>'Encerrado','cancelado'=>'Cancelado','recusado'=>'Recusado',
            'assinatura_expirada'=>'Assin. Expirada'][$this->status ?? ''] ?? $this->status ?? '';
}

protected function _getDiasParaVencer(): ?int {
    $vf = $this->end_date;
    if (empty($vf)) return null;
    $vfStr = $vf instanceof \DateTimeInterface ? $vf->format('Y-m-d') : (string)$vf;
    return (int)ceil((strtotime($vfStr) - time()) / 86400);
}
```

---

## 7. Views (Templates `.ctp`)

### 7.1 `ContractManagement/index.ctp` — Lista + KPIs

**URL:** `/modulo-contratos` | **Layout:** `pgm_premium.ctp` (tema teal)

```
┌─────────────────────────────────────────────────────────────────────┐
│  GESTÃO DE CONTRATOS                              [+ Novo Contrato] │
│                                                       [Exportar CSV]│
├────────┬────────────┬────────────────────┬────────────────────────┤
│ ATIVOS │  A VENCER  │ AGUARD. ASSINATURA │  RECEITA MENSAL TOTAL  │
│  [42]  │    [5]     │       [3]          │   R$ 48.500,00         │
│ teal   │ amarelo    │     azul           │     verde              │
├─────────────────────────────────────────────────────────────────────┤
│ [Status ▼] [Tipo ▼] [Cliente ▼] [Vigência De][Até] [🔍 Buscar]   │
├──┬──────────┬──────────────┬──────────┬────────────┬────────┬──────┤
│# │ Número   │ Cliente      │ Tipo     │ Vigência   │ R$/mês │Status│
│. │ CONT-42  │ Empresa ABC  │ Serviço  │ jan–dez/26 │ 2.500  │ATIVO │
│. │ CONT-40  │ Datamais     │ Serviço  │ jan–abr/26 │ 1.200  │⚠VENC │
└─────────────────────────────────────────────────────────────────────┘
```

**Badges de status:**

| Status | Classe Bootstrap |
|--------|-----------------|
| rascunho | `label-default` |
| revisao | `label-info` |
| aguardando_assinatura | `label-primary` |
| ativo | `label-success` |
| a_vencer | `label-warning` |
| em_renovacao | `label-warning` |
| suspenso | `label-default` |
| encerrado | `label-default` |
| cancelado | `label-danger` |
| recusado | `label-danger` |
| assinatura_expirada | `label-danger` |

---

### 7.2 `ContractManagement/view.ctp` — Detalhe Completo

Estrutura em **painéis Bootstrap colapsáveis** (`panel panel-default` + `collapse`):

```
┌─────────────────────────────────────────────────────────────────────┐
│ [← Contratos]  CONT-0042 — Empresa ABC Ltda         [ATIVO ✅]    │
│ Tipo: Serviço · Versão: 1 · Criado: 01/01/2026                    │
├─────────────────────────────────────────────────────────────────────┤
│  [Editar] [Gerar PDF] [Enviar p/ Assinatura] [Aprovar]             │
│  [Reenviar Link] [Solicitar Renovação] [Suspender ▼] [Cancelar ▼] │
├─────────────────────────────────────────────────────────────────────┤
│ ▼ CLIENTE & VIGÊNCIA                                                │
│   Empresa ABC · CNPJ: 12.345.678/0001-99                          │
│   Vigência: 01/01/2026 a 31/12/2026 · SLA: Suporte P3             │
│   Auto-renovar: Sim · Aviso: 30 dias antes                         │
├─────────────────────────────────────────────────────────────────────┤
│ ▼ SERVIÇOS CONTRATADOS                             [+ Serviço]     │
│   🔧 Suporte N1     10h   R$150/h    R$1.500/mês  [✏][🗑]        │
│   📋 Licença ERP     1ud  R$800/ud   R$800/mês    [✏][🗑]        │
│                                 Total: R$ 2.300,00/mês             │
├─────────────────────────────────────────────────────────────────────┤
│ ▼ COBERTURA                                                         │
│   ✅ Suporte Remoto  ✅ Backup  ❌ Presencial  ❌ Monitoramento    │
├─────────────────────────────────────────────────────────────────────┤
│ ▼ FRANQUIA E CONSUMO — abril/2026                                   │
│   [████████████░░░░░░░░░] 65% · 6h30min de 10h · Saldo: 3h30min  │
├─────────────────────────────────────────────────────────────────────┤
│ ▼ ASSINATURA DIGITAL                                                │
│   Autentique · Doc: abc-123 · ✅ Todos assinaram                   │
│   João (fornecedor) ✅ 05/01 10:32 · Maria (cliente) ✅ 06/01     │
│   [Download PDF Assinado] [Ver no Autentique ↗]                    │
├─────────────────────────────────────────────────────────────────────┤
│ ▼ SIGNATÁRIOS                                     [+ Signatário]   │
│   (tabela: nome, e-mail, tipo, status, link, ações)                │
├─────────────────────────────────────────────────────────────────────┤
│ ▼ DOCUMENTOS ANEXADOS                             [+ Documento]    │
├─────────────────────────────────────────────────────────────────────┤
│ ▼ RENOVAÇÕES                               [Solicitar Renovação]   │
│   #1 · Renovação 2027 · Pendente · 01/03/2026                     │
├─────────────────────────────────────────────────────────────────────┤
│ ▼ HISTÓRICO DE FATURAMENTO                                          │
│   FT-0089 · R$ 2.300,00 · Pago · fev/2026  [Ver]                 │
├─────────────────────────────────────────────────────────────────────┤
│ ▼ SLA CONTRATUAL — Suporte Padrão P3                                │
│   P1 Crítico: 30min / 4h  ·  P2 Alto: 2h / 8h                    │
│ ► P3 Médio: 8h / 24h  (nível do contrato)                         │
│   P4 Baixo: 24h / 72h                                              │
├─────────────────────────────────────────────────────────────────────┤
│ ▼ LOG DE ATIVIDADES                                                 │
│   06/01 ✅ Assinado por todos                                       │
│   05/01    Enviado para Autentique                                  │
│   04/01    PDF gerado pelo usuário João                             │
└─────────────────────────────────────────────────────────────────────┘
```

---

### 7.3 `ContractManagement/add.ctp` — Wizard 4 Passos

Abas Bootstrap (`nav-tabs`) numeradas:

**Passo 1 — Dados Básicos:**
Cliente (Select2), número (auto), tipo, template (select), vigência (datepicker), SLA (select), valor mensal, valor total, horas incluídas, limite chamados, auto-renovar (checkbox), dias aviso, observações internas, observações para cliente.

**Passo 2 — Serviços:**
Tabela dinâmica (add/remove via JS): tipo, descrição, quantidade, unidade, valor unitário, total (calculado). Total geral calculado em tempo real.

**Passo 3 — Cobertura:**
Checkboxes cobertura, módulos cobertos (tokenfield), cláusulas especiais (array: título + texto).

**Passo 4 — Signatários:**
Tabela com nome, e-mail, CPF, tipo, ordem, auth_type. Pré-preenchido via AJAX com contatos do cliente. Botões: `[Salvar Rascunho]` | `[Salvar e Gerar PDF]`.

---

### 7.4 `ContractManagement/enviar_assinatura.ctp`

```
┌─────────────────────────────────────────────────────────────────────┐
│  ENVIO PARA ASSINATURA — CONT-0042                                 │
├─────────────────────────────────────────────────────────────────────┤
│  📄 PDF gerado: 01/04/2026 10:30   [Baixar para conferir]          │
│  Signatários:                                                       │
│  Ordem │ Nome         │ E-mail          │ Tipo       │ Auth        │
│  1     │ João Silva   │ joao@pgm.com.br │ Fornecedor │ E-mail ✅   │
│  2     │ Maria Santos │ maria@cli.com   │ Cliente    │ E-mail ✅   │
├─────────────────────────────────────────────────────────────────────┤
│  Plataforma: [✅ Autentique] [○ ClickSign] [○ Manual]              │
│  Expiração: [30 dias ▼]  Lembrete automático: [3 dias ▼]          │
│  Mensagem opcional: [textarea]                                      │
├─────────────────────────────────────────────────────────────────────┤
│  [← Voltar]                  [Enviar para Assinatura →]            │
└─────────────────────────────────────────────────────────────────────┘
```

---

### 7.5 `ContractTemplates/add.ctp` e `edit.ctp` — Editor de Template

- Nome, tipo, descrição
- **Editor TinyMCE** (já instalado) para `conteudo_html`
- **Painel lateral** com botões de variáveis disponíveis — clicar insere via `tinyMCE.activeEditor.insertContent('{{nome_var}}')`
- Gerenciador de **cláusulas padrão**: tabela add/remove com título + textarea
- Botão `[Preview]` → abre `preview($id)` em nova aba com PDF de dados fictícios

---

### 7.6 `PortalContratos/index.ctp` — Portal Cliente

**URL:** `/cliente/contratos` | **Layout:** portal cliente (light mode)

```
┌─────────────────────────────────────────────────────────────────────┐
│  MEUS CONTRATOS — Empresa ABC Ltda                                 │
├──────────────────────────┬──────────────────────────────────────────┤
│  STATUS DO CONTRATO      │  CONSUMO — abril/2026                   │
│  🟢 ATIVO                │  [████████████░░░░░] 65%                │
│  Vence: 31/12/2026       │  6h 30min de 10h/mês · Saldo: 3h30min  │
│  (265 dias restantes)    │                                         │
├─────────────────────────────────────────────────────────────────────┤
│  O QUE ESTÁ NO SEU PLANO                                           │
│  ✅ Suporte técnico remoto — 10 horas mensais                       │
│  ✅ Licença de software ERP — 1 usuário                             │
│  ✅ Backup em nuvem — 1 TB                                          │
├─────────────────────────────────────────────────────────────────────┤
│  ACORDO DE NÍVEL DE SERVIÇO                                         │
│  🔴 CRÍTICO — Resposta: 30min · Resolução: 4h                      │
│  🟠 ALTO    — Resposta: 2h   · Resolução: 8h                       │
│  🟡 MÉDIO   — Resposta: 8h   · Resolução: 24h                      │
│  🟢 BAIXO   — Resposta: 24h  · Resolução: 72h                      │
├─────────────────────────────────────────────────────────────────────┤
│  [📄 Download Contrato] [💬 Abrir Chamado] [🔄 Solicitar Renovação]│
└─────────────────────────────────────────────────────────────────────┘
```

**Campos NUNCA exibidos para cliente:** `monthly_value`, `valor_total`, `notes`, `autentique_doc_id`, `autentique_url`.

---

### 7.7 Templates de E-mail — `src/Template/Email/html/`

| Arquivo | Assunto | Quando |
|---------|---------|--------|
| `contract_vencimento.ctp` | "⚠️ Contrato vence em Xd" | Para equipe |
| `contract_vencimento_cliente.ctp` | "Seu contrato vence em Xd" | Para cliente |
| `contract_assinado_cliente.ctp` | "✅ Contrato assinado!" | Após assinatura |
| `contract_novo_cliente.ctp` | "Novo contrato p/ assinatura" | Ao enviar |
| `contract_lembrar_assinatura.ctp` | "Lembrete: assine o contrato" | Para signatário |
| `contract_renovacao_aprovada.ctp` | "Renovação aprovada" | Ao aprovar |
| `contract_cancelado_cliente.ctp` | "Contrato cancelado" | Ao cancelar |

Todos devem usar o layout de e-mail padrão do sistema e incluir logo, número do contrato, CTA e contato de suporte.

---

## 8. Rotas — `config/routes.php`

Adicionar **após as rotas existentes**, dentro do `$routes->scope('/', ...)`:

```php
// === GESTÃO DE CONTRATOS (ERP) ===================================
$routes->connect('/modulo-contratos',
    ['controller' => 'ContractManagement', 'action' => 'index']);
$routes->connect('/modulo-contratos/view/*',
    ['controller' => 'ContractManagement', 'action' => 'view'], ['pass' => ['id']]);
$routes->connect('/modulo-contratos/add',
    ['controller' => 'ContractManagement', 'action' => 'add']);
$routes->connect('/modulo-contratos/edit/*',
    ['controller' => 'ContractManagement', 'action' => 'edit'], ['pass' => ['id']]);
$routes->connect('/modulo-contratos/servicos/*',
    ['controller' => 'ContractManagement', 'action' => 'addServicos'], ['pass' => ['id']]);
$routes->connect('/modulo-contratos/signatarios/*',
    ['controller' => 'ContractManagement', 'action' => 'addSignatarios'], ['pass' => ['id']]);
$routes->connect('/modulo-contratos/gerar-pdf/*',
    ['controller' => 'ContractManagement', 'action' => 'gerarPdf'], ['pass' => ['id']]);
$routes->connect('/modulo-contratos/enviar-assinatura/*',
    ['controller' => 'ContractManagement', 'action' => 'enviarAssinatura'], ['pass' => ['id']]);
$routes->connect('/modulo-contratos/aprovar/*',
    ['controller' => 'ContractManagement', 'action' => 'aprovar'], ['pass' => ['id']]);
$routes->connect('/modulo-contratos/suspender/*',
    ['controller' => 'ContractManagement', 'action' => 'suspender'], ['pass' => ['id']]);
$routes->connect('/modulo-contratos/cancelar/*',
    ['controller' => 'ContractManagement', 'action' => 'cancelar'], ['pass' => ['id']]);
$routes->connect('/modulo-contratos/reenviar-link/*',
    ['controller' => 'ContractManagement', 'action' => 'reenviarLink'], ['pass' => ['id']]);
$routes->connect('/modulo-contratos/renovacoes/*',
    ['controller' => 'ContractManagement', 'action' => 'verRenovacoes'], ['pass' => ['id']]);
$routes->connect('/modulo-contratos/aprovar-renovacao/*',
    ['controller' => 'ContractManagement', 'action' => 'aprovarRenovacao'], ['pass' => ['id']]);
$routes->connect('/modulo-contratos/recusar-renovacao/*',
    ['controller' => 'ContractManagement', 'action' => 'recusarRenovacao'], ['pass' => ['id']]);
$routes->connect('/modulo-contratos/pdf/*',
    ['controller' => 'ContractManagement', 'action' => 'downloadPdf'], ['pass' => ['id']]);
$routes->connect('/modulo-contratos/pdf-assinado/*',
    ['controller' => 'ContractManagement', 'action' => 'downloadSigned'], ['pass' => ['id']]);
$routes->connect('/modulo-contratos/exportar',
    ['controller' => 'ContractManagement', 'action' => 'exportar']);
// Webhook — sem sessão (liberar no beforeFilter do controller)
$routes->connect('/modulo-contratos/webhook/autentique',
    ['controller' => 'ContractManagement', 'action' => 'webhookAutentique']);

// === TEMPLATES DE CONTRATO =======================================
$routes->connect('/contract-templates',
    ['controller' => 'ContractTemplates', 'action' => 'index']);
$routes->connect('/contract-templates/add',
    ['controller' => 'ContractTemplates', 'action' => 'add']);
$routes->connect('/contract-templates/edit/*',
    ['controller' => 'ContractTemplates', 'action' => 'edit'], ['pass' => ['id']]);
$routes->connect('/contract-templates/delete/*',
    ['controller' => 'ContractTemplates', 'action' => 'delete'], ['pass' => ['id']]);
$routes->connect('/contract-templates/preview/*',
    ['controller' => 'ContractTemplates', 'action' => 'preview'], ['pass' => ['id']]);
$routes->connect('/contract-templates/clonar/*',
    ['controller' => 'ContractTemplates', 'action' => 'clonar'], ['pass' => ['id']]);

// === PORTAL CLIENTE — CONTRATOS ==================================
// (substituem /cliente/contratos-avancados — manter redirect no PortalAdvancedContractsController)
$routes->connect('/cliente/contratos',
    ['controller' => 'PortalContratos', 'action' => 'index']);
$routes->connect('/cliente/contratos/ver',
    ['controller' => 'PortalContratos', 'action' => 'view']);
$routes->connect('/cliente/contratos/faturas',
    ['controller' => 'PortalContratos', 'action' => 'faturas']);
$routes->connect('/cliente/contratos/pdf',
    ['controller' => 'PortalContratos', 'action' => 'downloadPdf']);
$routes->connect('/cliente/contratos/renovar',
    ['controller' => 'PortalContratos', 'action' => 'solicitarRenovacao']);
$routes->connect('/cliente/contratos/franquia',
    ['controller' => 'PortalContratos', 'action' => 'franquia']);
```

---

## 9. Atualizar Navegação

### 9.1 `sidebar.ctp` (~linha 261) — substituir link simples por submenu

```php
<?php $contOpen = in_array($this->request->getParam('controller'), ['ContractManagement', 'ContractTemplates']); ?>
<li class="<?= $contOpen ? 'active' : '' ?>">
  <a href="#menu-contratos" data-toggle="collapse" aria-expanded="<?= $contOpen ? 'true' : 'false' ?>">
    <i class="fa fa-file-contract"></i> Contratos
    <span class="caret pull-right"></span>
  </a>
  <ul id="menu-contratos" class="collapse list-unstyled <?= $contOpen ? 'in' : '' ?>">
    <li><a href="/modulo-contratos"><i class="fa fa-list fa-fw"></i> Todos os contratos</a></li>
    <li><a href="/modulo-contratos/add"><i class="fa fa-plus fa-fw"></i> Novo contrato</a></li>
    <li><a href="/contract-templates"><i class="fa fa-copy fa-fw"></i> Templates</a></li>
  </ul>
</li>
```

### 9.2 `sidebarcli.ctp` (~linhas 129-142) — atualizar URLs

```php
<a href="/cliente/contratos">Contratos</a>
<a href="/cliente/contratos/faturas">Faturas</a>
```

---

## 10. Permissões RBAC

```sql
INSERT INTO rbac_permissions (code, description, controller, action) VALUES
  ('contracts.view',    'Visualizar contratos',           'ContractManagement', 'index,view,verRenovacoes,downloadPdf,downloadSigned,exportar'),
  ('contracts.manage',  'Criar e editar contratos',       'ContractManagement', 'add,edit,addServicos,addSignatarios,gerarPdf'),
  ('contracts.aprovar', 'Aprovar contratos internamente', 'ContractManagement', 'aprovar'),
  ('contracts.assinar', 'Enviar para assinatura digital', 'ContractManagement', 'enviarAssinatura,reenviarLink'),
  ('contracts.cancelar','Suspender ou cancelar',          'ContractManagement', 'suspender,cancelar'),
  ('contracts.renovar', 'Gerenciar renovações',           'ContractManagement', 'aprovarRenovacao,recusarRenovacao'),
  ('contracts.templates','Gerenciar templates',           'ContractTemplates',  'index,add,edit,delete,preview,clonar'),
  ('contracts.cli_view','Cliente visualiza contratos',    'PortalContratos',    'index,view,faturas,franquia'),
  ('contracts.cli_down','Cliente baixa PDF assinado',     'PortalContratos',    'downloadPdf'),
  ('contracts.cli_renew','Cliente solicita renovação',    'PortalContratos',    'solicitarRenovacao')
ON CONFLICT (code) DO NOTHING;

-- Perfis:
-- admin      → todos
-- financeiro → view, manage, aprovar, assinar, cancelar, renovar, templates
-- gestor     → view
-- tecnico    → view
-- cliente    → cli_view, cli_down, cli_renew
```

---

## 11. Upload e Armazenamento

### Estrutura de pastas

```
/var/www/portal/uploads/contratos/
├── pdfs/          ← PDFs gerados (não assinados)
├── signed/        ← PDFs assinados (baixados do Autentique)
└── documents/     ← Documentos avulsos (ContractDocuments)
    └── {contract_id}/
```

### No `deploy-portal.sh`

```bash
mkdir -p /var/www/portal/uploads/contratos/{pdfs,signed,documents}
chmod -R 755 /var/www/portal/uploads/contratos
```

---

## 12. Crontab no Servidor

```bash
# Alertas diários — 08:00
0 8    * * * cd /var/www/portal && php bin/cake contract_alerts >> /var/log/portal/contract_alerts.log 2>&1

# Sincronizar status Autentique — a cada 2 horas
0 */2  * * * cd /var/www/portal && php bin/cake contract_alerts sincronizarAutentique >> /var/log/portal/contract_alerts.log 2>&1
```

---

## 13. Webhook no Autentique

Painel: `https://app.autentique.com.br` → Configurações → Webhooks

| Campo | Valor |
|-------|-------|
| URL | `https://seuportal.com/modulo-contratos/webhook/autentique` |
| Método | POST |
| Eventos | `document_signed`, `document_all_signed`, `signer_signed`, `document_rejected`, `document_canceled`, `document_expired`, `signer_reminder_sent` |
| Secret | Valor de `AUTENTIQUE_WEBHOOK_SECRET` no `.env` |

---

## 14. Fluxo de Status

```
[Criar]
  ↓
RASCUNHO → REVISÃO → AGUARD.ASSINATURA → ATIVO → A_VENCER → ENCERRADO (cron)
  ↓             ↓ (recusa)               ↓ (suspensão)
[cancelar]   RECUSADO                SUSPENSO → ATIVO
                ↓
         (editar e reenviar)
                ↓
           RASCUNHO

ATIVO/A_VENCER + auto_renew=true:
  → EM_RENOVAÇÃO → novo contrato RASCUNHO
                         ↓ (fluxo normal)
                   novo contrato ATIVO
                         ↓
                   original = ENCERRADO
```

---

## 15. Ordem de Implementação (Cursor)

**Estado mergeado e backlog vivo:** ver **§0.1–0.3** (o checklist abaixo é o plano original; cruza-se com P0–P5 para o que ainda falta).

### Fase 1 — Banco
1. Criar e rodar `20260407100000_ContractModulePhase1Expand.php`
2. Criar e rodar `20260407100001_ContractNewTables.php`
3. Executar SQL do RBAC

### Fase 2 — Models e Services
4. Atualizar `ContractsTable.php` (associations + validator)
5. Atualizar `Contract.php` Entity (virtual fields)
6. Criar 5 Tables novas + 5 Entities
7. Criar `AutentiqueService.php`
8. Criar `ContractPdfService.php`
9. Criar `ContractNotificationService.php`
10. Criar `ContractRenewalService.php`

### Fase 3 — Controllers e Rotas
11. Criar `ContractManagementController.php`
12. Criar `ContractTemplatesController.php`
13. Criar `PortalContratosController.php`
14. Adicionar rotas em `config/routes.php`
15. Atualizar `sidebar.ctp` e `sidebarcli.ctp`
16. Adicionar chave `Contract` em `config/app.php`

### Fase 4 — Views ERP
17. `ContractManagement/index.ctp`
18. `ContractManagement/view.ctp`
19. `ContractManagement/add.ctp` (wizard 4 passos)
20. `ContractManagement/edit.ctp`
21. `ContractManagement/add_signatarios.ctp`
22. `ContractManagement/gerar_pdf.ctp`
23. `ContractManagement/enviar_assinatura.ctp`
24. `ContractTemplates/index.ctp`
25. `ContractTemplates/add.ctp` (TinyMCE + painel variáveis)
26. `ContractTemplates/edit.ctp`
27. `ContractTemplates/preview.ctp`

### Fase 5 — Views Portal Cliente
28. `PortalContratos/index.ctp`
29. `PortalContratos/faturas.ctp`
30. `PortalContratos/franquia.ctp`

### Fase 6 — E-mails e Cron
31. 7 templates de e-mail
32. `ContractAlertsShell.php`
33. Pastas de upload + `deploy-portal.sh`
34. Configurar crontab

### Fase 7 — Integração Autentique
35. Preencher `.env` com token sandbox
36. Testar `criarDocumento()` em sandbox
37. Configurar webhook no painel Autentique (sandbox)
38. Testar fluxo completo: criar → PDF → enviar → assinar → webhook → ativo
39. Ativar produção (`AUTENTIQUE_SANDBOX=false`)

---

## 16. Resumo dos Arquivos

### Criar (42 arquivos)

**Migrations:** `20260407100000_ContractModulePhase1Expand.php`, `20260407100001_ContractNewTables.php`

**Tables (5):** `ContractTemplatesTable`, `ContractSignatoriesTable`, `ContractAutentiqueLogsTable`, `ContractRenewalsTable`, `ContractNotificationsTable`

**Entities (5):** `ContractTemplate`, `ContractSignatory`, `ContractAutentiqueLog`, `ContractRenewal`, `ContractNotification`

**Services (4):** `AutentiqueService`, `ContractPdfService`, `ContractNotificationService`, `ContractRenewalService`

**Controllers (3):** `ContractManagementController`, `ContractTemplatesController`, `PortalContratosController`

**Shell (1):** `ContractAlertsShell`

**Templates ERP (11):** index, view, add, edit, add_signatarios, gerar_pdf, enviar_assinatura, templates/index, templates/add, templates/edit, templates/preview

**Templates Portal (3):** PortalContratos/index, faturas, franquia

**E-mails (7):** contract_vencimento, contract_vencimento_cliente, contract_assinado_cliente, contract_novo_cliente, contract_lembrar_assinatura, contract_renovacao_aprovada, contract_cancelado_cliente

### Modificar (7 arquivos)

- `src/Model/Table/ContractsTable.php` — associations + validator
- `src/Model/Entity/Contract.php` — virtual fields
- `src/Model/Table/ContractServicesTable.php` — novos campos (já aplicados na migration)
- `config/routes.php` — adicionar ~30 rotas (não remover nenhuma)
- `config/app.php` — adicionar chave `Contract`
- `src/Template/Element/sidebar.ctp` — submenu contratos (~linha 261)
- `src/Template/Element/sidebarcli.ctp` — links cliente (~linhas 129-142)

### Não tocar

`ClicontratosController`, `ContratosHorasController`, `ContratoHorasService`, `ContractSlaIntegrationService`, `ContractConsumptionsTable`, `AdvancedContractsController` (deprecar gradualmente, não apagar), `PortalAdvancedContractsController` (adicionar redirect para `/cliente/contratos`), todos os templates em `Clicontratos/` e `ContratosHoras/`.
