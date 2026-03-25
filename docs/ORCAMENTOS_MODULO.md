# Módulo de orçamentos — mapa técnico (CakePHP 3.7)

Documento de referência para evolução alinhada a `pgm_orcamentos_premium.html` (protótipo visual).

## Persistência

| Modelo (Table)        | Tabela física (ORM)   | Observação |
|-----------------------|------------------------|------------|
| `OrcamentosTable`     | **`orcamentosnovosdes`** | Definido em `setTable()` / `table()` |
| `OrcamentositensTable` | (convenção Cake)     | Itens / vínculo carrinho-orçamento |
| `OrcamentosservicosTable` | idem              | Serviços no orçamento |
| `OrcamentosmovsTable` | idem                  | Histórico de situações |

| Associação principal | Detalhe |
|---------------------|---------|
| Orçamento → Cliente | `belongsTo Clientes`, FK `idcliente` |
| Orçamento → Autor   | `belongsTo Users`, FK `idautor` |

Tabela de clientes: **`clientes`** (`ClientesTable`).

## Status (`Orcamentos.status`)

Mapeamento usado em `index()`:

- `0` — Pendentes (em andamento)
- `1` — Enviados
- `2` — Aprovados
- `3` — Recusados
- `4` — Arquivados

## Actions — `OrcamentosController`

| Action | Papel resumido |
|--------|----------------|
| `index` | Listas por status (funcionário) ou lista cliente |
| `add` | Novo orçamento; carrinho em sessão; incremento número por empresa |
| `edit` | Edição (funcionário); cliente redirecionado |
| `view` / `viewhash` | Visualização autenticada / por hash público |
| `carrinho`, `carrinhoedit`, `addservico`, itens carrinho | Montagem de itens |
| `imprimir` | View HTML para impressão (`imprimir.ctp`) |
| `aprovar` / `aprovarhash` | Aprovação (logado / link) |
| `recusar`, `arquivar`, `alterarsituacao` | Fluxo de status |
| `enviar`, `email` | Envio / e-mail com anexos (`Cake\Mailer\Email`, transports `pgm` / `master`) |
| `novaordem` | Gera OS a partir do orçamento |
| `criarMov` | Registro em `Orcamentosmovs` |

## Autenticação / permissões

- `AppController`: `Auth` Form + `authorize` Controller.
- `beforeFilter`: cliente (`role == 1`) sem `permissaoacesso` é bloqueado; `Auth->allow` para `viewhash`, `carrinhoedit`, `aprovarhash`.

## E-mail e PDF

- E-mail: **`Cake\Mailer\Email`** (não há pacote PDF no `composer.json`).
- PDF: flag `pdfgerado` no fluxo; impressão atual via **HTML** (`imprimir.ctp`), não TCPDF/mPDF.

## Próximos passos sugeridos

1. Aplicar tokens visuais premium nas telas já existentes (`index`, depois `add`/`edit`).
2. Opcional: adicionar `mpdf/mpdf` ou `tecnickcom/tcpdf` se for obrigatório PDF binário.
3. Manter CSRF e `Form` helper em novos formulários.
