# PROMPT PARA O CURSOR
## Cole isso no chat do Cursor junto com o arquivo dashboard_pgm.html

---

Tenho um arquivo HTML chamado `dashboard_pgm.html` que é o **modelo visual exato** do dashboard que preciso implementar.

Quero que você:

1. **Abra e leia o arquivo `dashboard_pgm.html`** — ele já está funcionando e tem o visual correto
2. **Replique esse visual** no meu projeto, adaptando para a tecnologia que estou usando (React / Vue / seu framework)
3. **Mantenha exatamente:**
   - Todas as cores (variáveis CSS no `:root`)
   - O layout de 3 linhas: stat cards → middle row → bottom row
   - O comportamento one-click dos cards (expandem tabela abaixo, sem abrir nova página)
   - O gráfico de linha com Chart.js
   - O sino de notificações com painel deslizante
   - Os tickets estagnados com fundo vermelho e tag `+24h`
   - O simulador de nova requisição (setTimeout 8s)
   - A sidebar com nav items e estado `.active`
   - Tela 100% sem scroll externo (`html, body { height: 100%; overflow: hidden }`)

4. **NÃO mude:**
   - Paleta de cores dark mode
   - Tipografia IBM Plex Sans + IBM Plex Mono
   - Estrutura de grid das seções
   - Lógica de filtro por card

5. **Pode adaptar:**
   - Os dados das tabelas (conectar com API real se houver)
   - Os nomes dos técnicos e clientes (dados reais do sistema)
   - A contagem dos cards (dados reais)

---

## Referência rápida das seções

```
SIDEBAR (196px fixo)
  └── logo, busca, nav items, avatar

TOPBAR (46px)
  └── título + relógio + sino com notificações

STAT CARDS (4 colunas iguais)
  └── Aguardando técnico | Em execução | Finalizados | Requisições
  └── Clique no card → expande tabela filtrada ABAIXO (não abre nova página)

FILTER SECTION (aparece/some com animação)
  └── barra com título + botão fechar + tabela dinâmica

MIDDLE ROW (3 colunas: 210px | 190px | resto)
  └── SLA em tempo real | Saldo do dia | Gráfico 30 dias

BOTTOM ROW (4 colunas: 1fr | 1fr | 185px | 185px)
  └── Tabela aguardando | Tabela em execução | Ranking técnicos | Top clientes
```

## Comportamentos obrigatórios

| Ação | Resultado |
|------|-----------|
| Clicar num stat card | Expande seção de filtro abaixo com tabela correspondente |
| Clicar no mesmo card ativo | Fecha o filtro |
| Clicar no sino | Abre/fecha painel de notificações |
| Clicar fora do painel | Fecha painel |
| Clicar numa linha da tabela | Destaca linha com borda azul esquerda |
| Após 8 segundos | Chega nova requisição: card pisca roxo, contador +1, item no painel |
| Tickets #1093 e #1088 | Fundo vermelho suave + tag "+24h" na coluna cliente |

---

**O arquivo `dashboard_pgm.html` é a fonte da verdade. Se tiver dúvida sobre algum detalhe visual ou comportamento, consulte ele.**
