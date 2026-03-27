# Regra de Negocio - Busca de Produtos por Descricao (Estoque)

## Objetivo

Garantir uma busca mais precisa e consistente na tela de estoque, reduzindo resultados ruidosos.

## Escopo

Aplica-se a listagem de estoque em:
- tela `Produtos > Estoque`;
- geracao de PDF da listagem de estoque.

## Regra funcional

1. A busca por descricao deve ignorar:
   - caixa alta/baixa;
   - acentos e cedilha;
   - caracteres especiais extras.
2. A consulta digitada deve ser quebrada em termos.
3. Termos com menos de 2 caracteres e stopwords comuns (ex.: `de`, `da`, `do`, `e`) devem ser desconsiderados.
4. Para o produto ser retornado, **todos os termos relevantes** devem existir na descricao normalizada (regra AND).
5. Ordenacao de relevancia:
   - descricao exatamente igual a consulta (maior prioridade);
   - descricao iniciando com a consulta;
   - descricao contendo os termos no inicio de palavra;
   - demais correspondencias validas.

## Beneficios

- reduz falsos positivos;
- melhora previsibilidade para o usuario;
- padroniza o mesmo criterio entre tela e PDF.

## Exemplo

Busca: `access point huawei`

Comportamento esperado:
- retorna itens cuja descricao contem `access`, `point` e `huawei`;
- nao retorna itens que tenham apenas parte dos termos.
