# Fluxo Completo — Módulo Parecer Técnico

## 1. Fluxo de uso (estados e transições)

```
        ┌─────────────┐
        │  RASCUNHO   │  ← criado pelo técnico
        └──────┬──────┘
               │ técnico finaliza preenchimento
               ▼
        ┌─────────────┐
        │ EM ANÁLISE  │  ← supervisor revisa
        └──────┬──────┘
               │
       ┌───────┴───────┐
       ▼               ▼
┌─────────────┐  ┌─────────────┐
│  APROVADO   │  │  RASCUNHO   │ (rejeitado, volta a editar)
└──────┬──────┘  └─────────────┘
       │
       ▼
┌─────────────┐
│  CONCLUÍDO  │  ← parecer assinado, PDF gerado
└──────┬──────┘
       │ envio ao cliente
       ▼
┌─────────────┐
│   ENVIADO   │  ← e-mail disparado
└─────────────┘
```

### Quem pode mudar status

| Transição                 | Papel necessário          |
|---------------------------|---------------------------|
| Rascunho → Em análise     | Técnico (autor)           |
| Em análise → Aprovado     | Supervisor / Admin        |
| Em análise → Rascunho     | Supervisor (rejeição)     |
| Aprovado → Concluído      | Técnico ou Supervisor     |
| Concluído → Enviado       | Qualquer usuário          |

## 2. Atores do sistema

- **Técnico** — cria, edita pareceres, adiciona equipamentos/diagnósticos
- **Supervisor** — revisa e aprova pareceres
- **Administrador** — gerencia catálogos (peças, serviços, templates)
- **Cliente** (externo, sem login) — pode validar autenticidade via QR Code (rota pública)

## 3. Casos de uso principais

### 3.1 Criar novo parecer

1. Técnico clica em **Laudos > Parecer Técnico** no menu
2. Lista de pareceres carrega (filtrável)
3. Clica em **Novo Parecer**
4. Sistema gera número automático no formato `0001/2026`
5. Tela de edição abre com status **Rascunho**
6. Técnico preenche cliente (busca na base ou cadastra)
7. Adiciona um ou mais equipamentos com:
   - Identificação (modelo, S/N, configuração)
   - Diagnóstico (pode usar template)
   - Fotos (otimizadas automaticamente)
   - Peças sugeridas (do catálogo ou customizado)
   - Serviços prestados
8. Preenche conclusão (pode usar template)
9. Assina digitalmente
10. Salvamento é automático (debounced 600ms)

### 3.2 Buscar cliente existente

1. No campo "Buscar cliente", digita parte do nome/CNPJ
2. Sistema chama `GET /api/clientes/search?q=...` (existente no seu sistema)
3. Lista de resultados aparece em dropdown
4. Ao selecionar, todos os campos do requerente são preenchidos
5. `requester_client_id` é gravado para vínculo com a base

### 3.3 Consultar CNPJ via BrasilAPI

1. Usuário digita CNPJ (com máscara automática)
2. Validação dos dígitos verificadores em tempo real
3. Clica em **Buscar CNPJ**
4. Backend faz proxy para `https://brasilapi.com.br/api/cnpj/v1/{cnpj}`
   - **Por que via backend?** Para não expor IP do cliente final, fazer cache, e poder logar/limitar
5. Backend retorna dados normalizados
6. Campos são preenchidos (não sobrescreve se já tem valor)

### 3.4 Adicionar foto com otimização

1. Usuário arrasta/seleciona imagem
2. Frontend redimensiona via Canvas para max 1200px
3. Comprime para JPEG 75%
4. Envia base64 ou multipart para `POST /api/laudos/produto-imagens`
5. Backend salva em `webroot/uploads/laudos/{parecer_id}/{produto_id}/{filename}.jpg`
6. Registra metadados na tabela `laudos_produto_imagens`
7. Retorna URL pública da imagem

### 3.5 Gerar PDF do parecer

1. Usuário clica **Gerar PDF**
2. Backend renderiza view Twig/Php do parecer
3. CakePDF + dompdf gera PDF com:
   - Cabeçalho da empresa emissora
   - Todos os dados do parecer
   - Imagens otimizadas dos equipamentos
   - Assinatura e carimbo
   - QR Code de validação no rodapé
4. Retorna o arquivo para download
5. Registra evento no histórico

### 3.6 Enviar por e-mail

1. Usuário clica **Enviar por E-mail**
2. Modal abre com:
   - Para: e-mail do cliente (preenchido)
   - Assunto: padrão "Parecer Técnico nº X — empresa"
   - Corpo: template editável
3. Ao confirmar, backend:
   - Gera o PDF
   - Envia via `Mailer` do CakePHP com PDF anexado
   - Atualiza status para **Enviado**
   - Registra evento no histórico

### 3.7 Validação pública (QR Code)

Rota pública (sem autenticação): `GET /validar/{hash}`

1. Cliente final escaneia QR Code do PDF
2. Página simples mostra:
   - "Parecer Técnico nº X emitido em DD/MM/AAAA"
   - "Por: PGM Soluções em TI"
   - "Para: COMPOMAQ IND DE MAQ PARA BEBIDAS EIRELI"
   - "Status: Concluído / Enviado"
3. **Não expõe** detalhes técnicos, valores, ou diagnóstico
4. Apenas confirma autenticidade

## 4. Regras de negócio

### 4.1 Numeração

- Formato: `NNNN/AAAA` (ex: `0042/2026`)
- Sequencial por ano, único por empresa emissora
- Resetada todo dia 1º de janeiro
- Configurável via `companies.numbering_format`

### 4.2 Salvamento automático

- Frontend debounce de 600ms após última digitação
- Indicador visual: "Salvando..." → "Tudo salvo" / "Erro"
- `beforeunload` se houver alterações pendentes
- Backup local em IndexedDB para fallback offline (opcional)

### 4.3 Permissões de edição

- **Rascunho**: autor pode editar tudo
- **Em análise**: somente supervisor pode editar campos críticos
- **Aprovado / Concluído / Enviado**: somente leitura, exceto status
- Histórico imutável após criação

### 4.4 Hash de validação

- Gerado no momento da criação: `hash('sha256', uuid . secret_salt)` truncado em 12 chars
- Salvo no campo `public_hash`
- Único e imutável

### 4.5 Comparativo reparo × substituição

Cálculo automático:
- `reparo_total = sum(produtos.parts.qty * price + services.hours * rate)`
- `substituicao_total = parecer.estimated_new_equipment`
- Se `reparo_total / substituicao_total > 0.6` → recomendar substituição
- Threshold (0.6) configurável em `companies.repair_threshold`

### 4.6 Compressão de imagens

- Max width: **1200px** (configurável em `companies.image_max_width`)
- Quality: **75%** JPEG
- Limite por upload: **10MB** antes da compressão
- Tipos aceitos: `image/jpeg`, `image/png`, `image/webp`

### 4.7 Anexos

- Tipos aceitos: PDF, DOCX, XLSX, JPG, PNG
- Limite por arquivo: **5MB**
- Total por parecer: **50MB**
- Storage: filesystem (não base64)

## 5. Endpoints da API

| Método | Rota                                      | Descrição                          |
|--------|-------------------------------------------|------------------------------------|
| GET    | `/api/laudos/pareceres`                   | Lista pareceres (paginado, filtrável) |
| POST   | `/api/laudos/pareceres`                   | Cria novo parecer                  |
| GET    | `/api/laudos/pareceres/{id}`              | Detalhe do parecer                 |
| PUT    | `/api/laudos/pareceres/{id}`              | Atualiza parecer                   |
| DELETE | `/api/laudos/pareceres/{id}`              | Exclui parecer (soft delete)       |
| POST   | `/api/laudos/pareceres/{id}/duplicar`     | Duplica parecer                    |
| POST   | `/api/laudos/pareceres/{id}/status`       | Muda status                        |
| GET    | `/api/laudos/pareceres/{id}/pdf`          | Gera PDF                           |
| POST   | `/api/laudos/pareceres/{id}/enviar-email` | Envia por e-mail                   |
| GET    | `/api/laudos/pareceres/{id}/historico`    | Lista histórico                    |
| POST   | `/api/laudos/produtos`                    | Cria produto/equipamento           |
| PUT    | `/api/laudos/produtos/{id}`               | Atualiza produto                   |
| DELETE | `/api/laudos/produtos/{id}`               | Remove produto                     |
| POST   | `/api/laudos/produto-imagens`             | Upload de imagem                   |
| DELETE | `/api/laudos/produto-imagens/{id}`        | Remove imagem                      |
| POST   | `/api/laudos/anexos`                      | Upload de anexo                    |
| DELETE | `/api/laudos/anexos/{id}`                 | Remove anexo                       |
| GET    | `/api/laudos/catalogo/pecas`              | Lista catálogo de peças            |
| POST   | `/api/laudos/catalogo/pecas`              | Cria peça no catálogo              |
| GET    | `/api/laudos/catalogo/servicos`           | Lista catálogo de serviços         |
| GET    | `/api/laudos/templates/diagnostico`       | Lista templates de diagnóstico     |
| GET    | `/api/laudos/templates/conclusao`         | Lista templates de conclusão       |
| GET    | `/api/util/cnpj/{cnpj}`                   | Proxy BrasilAPI                    |
| GET    | `/api/util/cep/{cep}`                     | Proxy ViaCEP                       |
| GET    | `/validar/{hash}`                         | Validação pública (sem auth)       |

## 6. Eventos registrados no histórico

Todo evento é gravado em `laudos_historico` com:
- `parecer_id`
- `user_id` (quem fez)
- `action` (string padronizada)
- `details` (JSON com detalhes)
- `created` (timestamp)

Lista de actions:

- `parecer.created` — parecer criado
- `parecer.duplicated` — parecer duplicado de outro
- `parecer.deleted` — soft delete
- `parecer.status_changed` — mudança de status (details: `{from, to}`)
- `produto.added` — equipamento adicionado
- `produto.removed` — equipamento removido
- `imagem.added` — foto adicionada
- `imagem.removed` — foto removida
- `peca.added` — peça adicionada
- `servico.added` — serviço adicionado
- `cliente.linked` — cliente vinculado da base
- `cnpj.consulted` — consulta a CNPJ
- `cep.consulted` — consulta a CEP
- `template.applied` — template aplicado (details: `{type, name}`)
- `pdf.generated` — PDF gerado
- `email.sent` — e-mail enviado (details: `{to}`)
- `signed` — parecer assinado
- `attachment.added` — anexo adicionado
- `attachment.removed` — anexo removido
