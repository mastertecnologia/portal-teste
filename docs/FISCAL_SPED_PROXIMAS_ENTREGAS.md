# SPED Fiscal (EFD-ICMS/IPI) — próximas entregas de código

O gerador atual (`App\Utility\Fiscal\FiscalSpedGenerator`) cobre blocos **0, C, E** (E simplificado), **H** vazio; **9** com `9900`/`9990`/`9999` por contagem real; **0990/C990/E990/H990** com `QTD_LIN`; **0100** a partir de `fiscal_empresas_config.sped_contabilista_*` (ou omitido / stub via `Fiscal.sped.registro_0100_modo`); no bloco **0** também **`0150`**, **`0190`**, **`0200`**, **`0400`** (natureza da operação a partir de `FiscalNaturezaOperacao` + texto da NF) e **`0450`**; no **C**, **`C110`**, **`C170`** com **`COD_NAT`**, **`C190`** + **`COD_PART` no `C100`** para modelos que não são NFC-e (65), com `Clientes`, `Cidades` e **`FiscalNaturezaOperacao`** no `contain` das notas. `Fiscal.sped.tipo_item_padrao` / `FISCAL_SPED_TIPO_ITEM_PADRAO` define o **TIPO_ITEM** do `0200` (padrão `00`).

## Prioridade sugerida

1. ~~**Bloco 9**~~ — contagens `9900` / `9990` / `9999` alinhadas ao total de linhas.
2. ~~**Registro 0100**~~ — cadastro em Configuração fiscal + `FISCAL_SPED_0100_MODO` (`omitir_sem_dados` \| `sempre_stub`).
3. ~~**Bloco H**~~ — com `sped_inventario_declarar` + data + `MOT_INV` + JSON de itens em config fiscal → `H001=0`, `H005`, `H010`; senão `H001=1`. Validar no PVA e alinhar `COD_ITEM` ao 0200 quando necessário.
4. ~~**Bloco C + cadastro de itens**~~ — `C100`/`C170`/`C190` com `fiscal_notas_impostos` (ICMS, ICMS_ST, FCP/FCP_ST, IPI, PIS, COFINS), **`0190`/`0200`** + **`0150` + `COD_PART`** (exceto NFC-e 65), frete/saída/pagamento; NFC-e (65) limpa ST/IPI/PIS/COFINS no mestre. `E110` usa os mesmos totais de ICMS por item agrupando débito (saída) e crédito (entrada) por `tipo_operacao`.
5. ~~**Bloco E — E111**~~ — ajustes de apuração via `fiscal_empresas_config.sped_e111_ajustes_json` (campos permitidos somam no `E110`; linhas `E111` após o `E110`). Outros registros (`E220`, etc.) permanecem fora de escopo até necessidade explícita.
6. ~~**Bloco 0 — 0400 + 0450 + C110**~~ — `0400` + `COD_NAT` no **C170** (cadastro `fiscal_natureza_operacao.codigo` normalizado, ou `N`+id, ou `T`+hash do texto); `0450` + `C110` para infos complementares NF-e **55**.
7. **Validação** — importação no **PVA** oficial e correção iterativa até “sem erro” ou lista de exceções aceites.

## Documentação

- Sped Fiscal — layouts e tabelas dinâmicas na versão vigente da Receita Federal.
- Contador responsável pela **assinatura** e entrega do arquivo.
