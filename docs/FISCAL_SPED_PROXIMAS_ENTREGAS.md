# SPED Fiscal (EFD-ICMS/IPI) — próximas entregas de código

O gerador atual (`App\Utility\Fiscal\FiscalSpedGenerator`) escreve os blocos na ordem **0 → B → C → D → E → G → H → K → 1 → 9**. Os blocos **B, D, G, K e 1** saem apenas com **abertura + encerramento** e indicador **sem dados** (`B001`/`D001`/`G001`/`1001` com `1`; no **K001** o campo **IND_MOV** = `1`), até haver escrituração de serviços, doc. II, CIAP, RCPE/produção-estoque ou complementos. **H** com inventário opcional ou `H001=1`. **9** com `9900`/`9990`/`9999` por contagem real; **0990/C990/E990/H990** (e encerramentos B/D/G/K/1) com `QTD_LIN`. Demais detalhes do bloco **0** e **C** como na versão anterior (`0150`…`0460`, `C100`…`C190`, etc.).

## Prioridade sugerida

1. ~~**Bloco 9**~~ — contagens `9900` / `9990` / `9999` alinhadas ao total de linhas.
2. ~~**Registro 0100**~~ — cadastro em Configuração fiscal + `FISCAL_SPED_0100_MODO` (`omitir_sem_dados` \| `sempre_stub`).
3. ~~**Bloco H**~~ — com `sped_inventario_declarar` + data + `MOT_INV` + JSON de itens em config fiscal → `H001=0`, `H005`, `H010`; senão `H001=1`. Validar no PVA e alinhar `COD_ITEM` ao 0200 quando necessário.
4. ~~**Bloco C + cadastro de itens**~~ — `C100`/`C170`/`C190` com `fiscal_notas_impostos` (ICMS, ICMS_ST, FCP/FCP_ST, IPI, PIS, COFINS), **`0190`/`0200`** + **`0150` + `COD_PART`** (exceto NFC-e 65), frete/saída/pagamento; NFC-e (65) limpa ST/IPI/PIS/COFINS no mestre. `E110` usa os mesmos totais de ICMS por item agrupando débito (saída) e crédito (entrada) por `tipo_operacao`.
5. ~~**Bloco E — E111**~~ — ajustes de apuração via `fiscal_empresas_config.sped_e111_ajustes_json` (campos permitidos somam no `E110`; linhas `E111` após o `E110`). Outros registros (`E220`, etc.) permanecem fora de escopo até necessidade explícita.
6. ~~**Bloco 0 — 0400 + 0450 + C110**~~ — `0400` + `COD_NAT` no **C170** (cadastro `fiscal_natureza_operacao.codigo` normalizado, ou `N`+id, ou `T`+hash do texto); `0450` + `C110` para infos complementares NF-e **55**.
7. **Validação (processo operacional)** — não há automação no portal; seguir checklist abaixo no **PVA** oficial.
8. ~~**0460 + COD_OBS no C190**~~ — opcional por UF: JSON `sped_0460_c190_json` em config fiscal (`observacoes` + `c190` com `cst`, `cfop`, `aliq_icms`, `cod_obs`).
9. ~~**Blocos B, D, G e 1 (estrutura mínima)**~~ — `B001`/`B990`, `D001`/`D990`, `G001`/`G990`, `1001`/`1990` sem movimento, alinhados à sequência usual do leiaute e ao PVA.
10. ~~**Bloco K (estrutura mínima)**~~ — `K001`/`K990` sem movimento (`K001` com **IND_MOV** = `1`), entre **H** e o bloco **1**, conforme sequência do Guia Prático.

## Escopo de código — encerrado

Para o roteiro numerado acima, **tudo o que é desenvolvimento no repositório** está tratado (itens ~~1–6~~ e ~~8–10~~). Resta apenas o **item 7 — validação no PVA** (processo operacional com o contador), sem automação adicional prevista no portal. Evoluções futuras (ex.: escrituração completa do **RCPE** no bloco K, mais registros do bloco **E**, **D** com serviços, etc.) entram como novo roteiro quando houver requisito de negócio.

### Checklist PVA (EFD-ICMS/IPI)

1. Instalar o **PVA** na versão compatível com o **COD_VER** informado no registro `0000` (padrão do gerador: `015`; ajustável por `FISCAL_SPED_COD_VER` em `config/fiscal.php` / `.env`).
2. Importar o `.txt` gerado em **Relatórios fiscais** → SPED Fiscal (mês de referência).
3. Corrigir erros de estrutura (contagens `0990`/`C990`/`E990`/`H990`/`K990`, bloco 9, chaves duplicadas) — o gerador já alinha `9900`/`9999` ao conteúdo.
4. Validar regras de negócio do Fisco: **CFOP** x entrada/saída (`IND_OPER`), **CST**, totais **C100** x soma **C170**, **COD_ITEM** no `0200`, **COD_NAT** no `0400`, **COD_OBS** no `C190` coerente com **0460** (se informado), participantes **0150**/`COD_PART`, **E110**/`E111` com o contador.
5. Registrar exceções aceitas (avisos que a UF/PVA tolera) e repetir após cada alteração de cadastro ou de layout.

**Teste automatizado:** `tests/TestCase/Utility/FiscalSpedGeneratorGerarSmokeTest.php` chama `gerar()` com nota mockada (subclasse que não acessa o BD), cobrindo blocos 0/C/E/H/**K**/9 e presença de `0400`/`C170`/`9999` (incluído na suite `fiscal` do PHPUnit).

## Documentação

- Sped Fiscal — layouts e tabelas dinâmicas na versão vigente da Receita Federal ([SPED](https://www.gov.br/receitafederal/pt-br/assuntos/orientacao-tributaria/declaracoes-e-demonstrativos/sped-fiscal)).
- Contador responsável pela **assinatura** e entrega do arquivo.
