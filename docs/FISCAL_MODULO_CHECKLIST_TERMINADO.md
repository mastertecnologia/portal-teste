# Checklist — módulo fiscal “terminado”

Documento operacional para fechar **NF-e (núcleo)**, **NFS-e municipal**, **SPED**, **refinos técnicos** e **go-live**. Ajuste prazos ao vosso município e ao contador.

---

## A — NF-e + entrada + DF-e + permissões (escopo docblock `config/fiscal.php`)

1. **Código versionado** — migrations fiscais, `config/fiscal.php`, controllers/utilitários, testes (`composer test-rbac`, `composer fiscal-verify`).
2. **Base de dados** — `bin/cake migrations migrate` em dev/staging/prod; conferir `fiscal_empresas_config`, certificados, fila `fiscal_dfe_recebidos` se usada.
3. **Ambiente** — `FISCAL_AMBIENTE=2` até fim da homologação; `VAULT_ENCRYPTION_KEY` para cofre de senhas de certificado quando aplicável.
4. **Certificado A1** — válido, CNPJ alinhado ao emitente, teste de assinatura e SOAP (status SEFAZ + transmissão de lote em homologação).
5. **Matriz RBAC** — papéis com `fiscal.*` necessários; `menu_filter_sidebar` e gates `fiscal_menu_*` validados com utilizadores reais.
6. **Fluxos críticos em homologação** — emissão 55/65, cancelamento, CC-e (se usado), inutilização, manifestação (entrada), distribuição DF-e e fila (se usado).
7. **Produção** — checklist do painel fiscal, confirmações explícitas (`FiscalProducaoGate`), `FISCAL_AMBIENTE=1` só após testes.
8. **Retenção e manutenção** — política de XML (`fiscal_maintenance purge_*`), backups e monitorização de erros SOAP/rejeições.
9. **Integrações satélite** — e-mail pós-autorização, duplicatas financeiras, ERP (`FiscalErpSync` se ativo): smoke em staging.
10. **Suporte** — runbook interno (quem altera certificado, como reprocessar rejeição, contacto contador).

### Regimes (Simples, Lucro presumido, Lucro real) e reforma tributária

- **NF-e CRT** — continua 1 (Simples), 2 (excesso de sublimite) ou 3 (regime normal). Lucro presumido e lucro real usam **CRT 3**; a distinção para PIS/COFINS de referência fica em `fiscal_empresas_config.regime_normal_enquadramento` (configuração fiscal da empresa).
- **Alíquotas** — linhas em **Alíquotas** prevalecem sobre os padrões do motor; o enquadramento só define fallback (ex.: 0,65%/3% presumido vs 1,65%/7,6% real).
- **Reforma (IBS/CBS)** — `Fiscal.reforma_tributaria` e `FISCAL_REFORMA_ESTUDO_IBSCBS` em `.env`; integração XML só após NT/Convênio vigente.

---

## B — NFS-e municipal (além do stub)

**Piloto fixado no repositório:** GISS Online (ex-GINFES), município referência **Itu/SP (IBGE 3523909)** — detalhes e URLs em [`docs/FISCAL_NFSE_PILOTO.md`](FISCAL_NFSE_PILOTO.md).

1. **Escopo por cidade** — escolher municípios/provedores (ABRASF 1.0/2.04, GINFES, ISSNet, etc.); um projeto por variante se APIs diferirem.
2. **Configuração** — `fiscal_empresas_config.nfse_provedor` = `ginfes_giss` (ou `itu_sp_giss`) + credenciais/certificado; mapa em `Fiscal.nfse_emissor_map` e `Fiscal.nfse_ginfes` / env `NFSE_GINFES_*`.
3. **Implementar `NfseEmissorInterface`** — `emitir()` com tratamento de erros, logs e persistência de protocolo/número conforme prefeitura.
4. **Modelo de dados** — campos da nota (serviço, LC 116, retenções) e armazenamento de XML/PDF se o município exigir.
5. **Cancelamento/substituição** — se o contrato exigir, estender interface (ex.: `cancelar`) e UI; hoje só `emitir` está no contrato.
6. **Homologação municipal** — ambiente de teste da prefeitura ou carta de conformidade; não confundir com SEFAZ NF-e.
7. **Produção** — go-live por empresa/município; monitorização e fila de rejeições.

---

## C — SPED Fiscal (EFD-ICMS/IPI) — `FiscalSpedGenerator`

Roteiro de evolução do código: [`docs/FISCAL_SPED_PROXIMAS_ENTREGAS.md`](FISCAL_SPED_PROXIMAS_ENTREGAS.md).

1. **Versão do layout** — confirmar `COD_VER` (ex. 015 vs exigência vigente do PVA).
2. **Bloco 0** — `0100` contabilista hoje é stub; preencher com dados reais ou omitir conforme regra do perfil.
3. **Bloco C** — revisar `C100`/itens vs operação de vocês (CFOP, CST, entradas/saídas, NFC-e).
4. **Bloco E** — `E110` hoje agrega de forma simplificada; validar com apuração e ajustes (créditos, ST, diferimento).
5. **Bloco H** — hoje `H001=1` (sem inventário); se obrigatório, implementar `H005`/`H010` (e movimentação física).
6. **Bloco 9** — conferir contagem de registros e linhas (`9900`, `9990`, `9999`) com o PVA (há simplificações no gerador).
7. **Validação oficial** — importar no Programa Validador e corrigir até “sem erro” (ou aceitar pendências documentadas).
8. **Processo contábil** — responsável técnico assina entrega; integração com escrituração.

---

## D — Refinos técnicos (hardcode / robustez)

1. **SOAP + certificado** — `FiscalSigner::exportSslPemBundle()` alimenta `local_cert` do SoapClient (correção de PEM vazio).
2. **Timeouts e retentativas** — `FISCAL_SOAP_*` alinhados à rede; logs em falhas intermitentes.
3. **Revisão `FiscalSefazClient`** — namespaces por método, tratamento de falha de rede vs rejeição de negócio.
4. **Cobertura de testes** — aumentar testes onde houver regressões frequentes (parser DF-e, manifestação, chave).

---

## E — Operação e rollout

1. **Staging espelhado** — mesma versão de código, migrations e env que produção (salvo segredos).
2. **Treino** — utilizadores finais em emissão, consultas SEFAZ, DF-e e relatórios.
3. **Plano de rollback** — versão anterior da app + estado da BD documentado.
4. **Pós go-live** — acompanhamento 2–4 semanas (rejeições, certificados a expirar, volume DF-e).

---

## Comandos úteis (repo)

- `composer fiscal-verify` — suite fiscal + HTTP fiscal.
- `composer test-rbac` — inclui integração HTTP ampla (RBAC).
- `bin/cake migrations migrate`
- `bash bin/fiscal_dev.sh` / `.\bin\fiscal_dev.ps1` — migrate + testes fiscal (dev local).

---

## Estimativa de esforço (ordem de grandeza)

| Faixa | Conteúdo |
|-------|-----------|
| **Já muito maduro** | NF-e + DF-e + RBAC no código atual (falta sobretudo validação real + deploy). |
| **Médio** | SPED até “passa no PVA” para um perfil de empresa definido. |
| **Grande / por município** | NFS-e com provedor real (cada cidade pode ser semanas). |

Use a secção **A** como critério mínimo de “módulo NF-e pronto para produção”; **B** e **C** como fases contratuais separadas se o prazo for apertado.
