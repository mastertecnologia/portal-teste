# SPED Fiscal (EFD-ICMS/IPI) — próximas entregas de código

O gerador atual (`App\Utility\Fiscal\FiscalSpedGenerator`) cobre blocos **0, C, E** (E simplificado), **H** vazio e **9** com contagem a rever.

## Prioridade sugerida

1. **Bloco 9** — alinhar contagens `9900` / `9990` / `9999` ao Guia Prático e ao PVA (hoje há aproximações).
2. **Registro 0100** — contabilista: preencher a partir de cadastro ou tornar opcional conforme perfil.
3. **Bloco H** — se a empresa for obrigada a inventário: `H001=0` e registros `H005`/`H010` com saldos/quantidades (fonte: estoque ou planilha importada).
4. **Bloco C** — revisar `C100`/`C170` para entradas, ST, FCP e NFC-e conforme operações reais.
5. **Bloco E** — créditos, ajustes (`E111`, `E220`, etc.) conforme apuração do contador.
6. **Validação** — importação no **PVA** oficial e correção iterativa até “sem erro” ou lista de exceções aceites.

## Documentação

- Sped Fiscal — layouts e tabelas dinâmicas na versão vigente da Receita Federal.
- Contador responsável pela **assinatura** e entrega do arquivo.
