# Piloto NFS-e — GINFES / GISS Online (Itu/SP referência)

## Fixado no projeto

| Campo | Valor |
|--------|--------|
| **Provedor** | GISS Online (evolução do ecossistema GINFES; URLs `*.giss.com.br`) |
| **Município de referência** | **Itu — SP** |
| **IBGE** | **3523909** |
| **Slug WS produção (exemplo)** | `itu` → `https://ws-itu.giss.com.br/service-ws/nf/nfse-ws?wsdl` |
| **Homologação (padrão divulgado)** | `https://v2-ws-homologacao.giss.com.br/service-ws/nf/nfse-ws?wsdl` |

**Obrigatório:** validar WSDL, certificado digital e layout exatos com a **prefeitura** ou suporte GISS — os endpoints mudam por município e ao longo do tempo.

## Configuração no Portal

1. **Configuração fiscal da empresa** (`fiscal_empresas_config`):
   - `nfse_provedor` = `ginfes_giss` (ou alias `itu_sp_giss`, mesma classe).
   - `inscricao_municipal`, `codigo_municipio_ibge` = **3523909** (ou o município real do cliente).
   - Credenciais `nfse_usuario` / `nfse_senha` se o provedor exigir (a classe atual usa sobretudo TLS com certificado).

2. **Ambiente (`.env`)** — ver também `.env.example`:

   - `NFSE_GINFES_WSDL` — URL completa do WSDL (homologação ou produção).
   - `NFSE_GINFES_LOCAL_CERT_PEM` — ficheiro **.pem** (certificado + chave privada) para autenticação mTLS, se exigido.
   - `NFSE_GINFES_MODE` — `recepcionar_lote_v3` (padrão GINFES v3 / GISS) ou `abrasf_gerar_nfse` (padrão tipo WEBISS / `GerarNfse`).
   - `NFSE_GINFES_SOAP_OPERATION` — por defeito `RecepcionarLoteRpsV3`; ajustar conforme WSDL (ex.: outro nome de operação).

3. **Código:** `App\Utility\Fiscal\Nfse\NfseEmissorGinfes` — monta XML mínimo (serviço genérico `01.01`, valores da nota). Ajuste **ItemListaServico**, tributos e campos obrigatórios do município após o primeiro retorno do validador.

## Leitura em runtime

- `Configure::read('Fiscal.nfse_piloto')` — metadados do piloto.
- `Configure::read('Fiscal.nfse_ginfes')` — opções do emissor.

## Próximos passos técnicos

1. Primeira chamada em **homologação** com certificado de teste da prefeitura.
2. Ajustar XML aos XSD do município (mensagens de rejeição do SOAP).
3. Persistir XML de envio/retorno em `fiscal_notas_xmls` (hoje o fluxo NFS-e não duplica o armazenamento NF-e; pode ser evolução).
4. **Cancelamento** — não está no `NfseEmissorInterface`; acrescentar método e UI quando o município exigir.

## Referências externas (validar datas)

- Comunicações de migração de URLs GINFES → GISS (ex.: materiais CIGAM / GISS).
- Manual ABRASF 2.04 e WSDL do município.
