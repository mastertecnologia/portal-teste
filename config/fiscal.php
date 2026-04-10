<?php
/**
 * Configuração do Módulo Fiscal — PGM Portal.
 *
 * Valores padrão; sobrescrever com env vars FISCAL_* (ver .env.example).
 *
 * Roadmap entregue (fases 1–3):
 * - Fase 1 — Homologação: checklist no painel /fiscal, Status SEFAZ, ambiente 2 por defeito, tabelas CFOP/NCM/naturezas.
 * - Fase 2 — Produção segura: confirmação explícita em produção (emitir/cancelar/CC-e/inutilização), FiscalAudit + logs,
 *   retentativas SOAP em falha de rede (soap_retry_max / soap_retry_delay_ms), retenção XML (hint + purge_xmls em BD).
 * - Fase 3 — Operação: inutilização de numeração (SEFAZ + artefactos em xml/inutilizacao), validação CNPJ emitente na emissão,
 *   RBAC/ABAC (fiscal.*, mapa FiscalNotasEntrada), purge_inutilizacao no disco (FiscalMaintenanceShell), atalhos no dashboard.
 *
 * Fases 4–6 (entregues neste roadmap):
 * - Fase 4 — Manifestação do destinatário: eventos 210200–210240 em notas de entrada autorizadas (NF-e/NFC-e), confirmação em produção,
 *   registo em fiscal_notas_eventos + XML tipo evento.
 * - Fase 5 — Distribuição DF-e nacional: consulta NFeDistribuicaoDFe (AN), botão no painel /fiscal, resposta JSON e artefactos em xml/distribuicao;
 *   NSU persistido em fiscal_empresas_config.dfe_ult_nsu (continuação de consultas + pré-visualização docZip);
 *   fila fiscal_dfe_recebidos (ingestão após consulta com sucesso — conferência, download XML, ignorar,
 *   consChNFe para obter XML completo quando só veio resNFe com chave, rascunho de entrada a partir de nfeProc/infNFe).
 *   Manutenção: bin/cake fiscal_maintenance purge_dfe_recebidos (fila BD, só pendente/ignorado antigos; preserva vinculado).
 * - Fase 6 — NFS-e: modelo NFSE não usa SOAP NF-e; mapa Fiscal.nfse_emissor_map (nfse_provedor → NfseEmissorInterface).
 *   Por defeito NfseEmissorStub; registe classe por município/provedor. Emissão: FiscalNotasController::emitirNfseMunicipal.
 */
return [
    'Fiscal' => [
        // Ambiente SEFAZ: 1 = Produção, 2 = Homologação
        'ambiente' => (int)env('FISCAL_AMBIENTE', 2),

        // Registar emissão/cancelamento/CC-e em logs (FISCAL_AUDIT_LOG=0 para desligar)
        'audit_log' => env('FISCAL_AUDIT_LOG', '1') !== '0',

        // Retentativas extra só para erros de rede/timeout no SoapClient (0 = desligado; máx. 3)
        'soap_retry_max' => min(3, max(0, (int)env('FISCAL_SOAP_RETRY_MAX', 0))),
        'soap_retry_delay_ms' => min(5000, max(100, (int)env('FISCAL_SOAP_RETRY_DELAY_MS', 500))),

        // Versão do layout NFe (4.00 é obrigatório desde 2019)
        'versao_nfe' => '4.00',

        // Versão do layout NFCe
        'versao_nfce' => '4.00',

        // Timeout SOAP em segundos
        'soap_timeout' => (int)env('FISCAL_SOAP_TIMEOUT', 30),

        // Retenção sugerida (dias) para cópias em disco — uso futuro / política interna; XML em BD: fiscal_notas_xmls
        'xml_retention_days_hint' => (int)env('FISCAL_XML_RETENTION_DAYS', 365),

        // E-mail fiscal: envio de DANFE + XML ao cliente
        'email' => [
            'smtp_transport' => env('FISCAL_SMTP_TRANSPORT', 'pgm'),
            'from_email' => env('FISCAL_EMAIL_FROM', ''),
            'from_name' => env('FISCAL_EMAIL_FROM_NAME', ''),
            // Enviar automaticamente ao autorizar nota (true/false)
            'auto_envio_autorizacao' => filter_var(env('FISCAL_EMAIL_AUTO_ENVIO', false), FILTER_VALIDATE_BOOLEAN),
        ],

        // Diretório para armazenar XMLs e certificados
        'storage_path' => env('FISCAL_STORAGE_PATH', ROOT . DS . 'storage' . DS . 'fiscal'),

        // Sub-diretórios
        'paths' => [
            'certificados' => 'certificados',
            'xml_envio'    => 'xml' . DS . 'envio',
            'xml_retorno'  => 'xml' . DS . 'retorno',
            'xml_cancelamento' => 'xml' . DS . 'cancelamento',
            'xml_inutilizacao' => 'xml' . DS . 'inutilizacao',
            'xml_distribuicao' => 'xml' . DS . 'distribuicao',
            'danfe'        => 'danfe',
            'nfse'         => 'nfse',
        ],

        // UFs e seus códigos IBGE
        'ufs' => [
            'AC' => 12, 'AL' => 27, 'AP' => 16, 'AM' => 13, 'BA' => 29,
            'CE' => 23, 'DF' => 53, 'ES' => 32, 'GO' => 52, 'MA' => 21,
            'MT' => 51, 'MS' => 50, 'MG' => 31, 'PA' => 15, 'PB' => 25,
            'PR' => 41, 'PE' => 26, 'PI' => 22, 'RJ' => 33, 'RN' => 24,
            'RS' => 43, 'RO' => 11, 'RR' => 14, 'SC' => 42, 'SP' => 35,
            'SE' => 28, 'TO' => 17,
        ],

        // Webservices SEFAZ (Homologação e Produção)
        'webservices' => [
            // NFe 4.00 — SEFAZ Virtual SVRS (maioria dos estados)
            'svrs' => [
                1 => [ // Produção
                    'NfeAutorizacao'          => 'https://nfe.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx',
                    'NfeRetAutorizacao'       => 'https://nfe.svrs.rs.gov.br/ws/NfeRetAutorizacao/NFeRetAutorizacao4.asmx',
                    'NfeConsultaProtocolo'    => 'https://nfe.svrs.rs.gov.br/ws/NfeConsulta/NfeConsulta4.asmx',
                    'NfeStatusServico'        => 'https://nfe.svrs.rs.gov.br/ws/NfeStatusServico/NfeStatusServico4.asmx',
                    'NfeInutilizacao'         => 'https://nfe.svrs.rs.gov.br/ws/nfeinutilizacao/nfeinutilizacao4.asmx',
                    'RecepcaoEvento'          => 'https://nfe.svrs.rs.gov.br/ws/recepcaoevento/recepcaoevento4.asmx',
                    'CadConsultaCadastro'     => 'https://cad.svrs.rs.gov.br/ws/cadconsultacadastro/cadconsultacadastro4.asmx',
                ],
                2 => [ // Homologação
                    'NfeAutorizacao'          => 'https://nfe-homologacao.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx',
                    'NfeRetAutorizacao'       => 'https://nfe-homologacao.svrs.rs.gov.br/ws/NfeRetAutorizacao/NFeRetAutorizacao4.asmx',
                    'NfeConsultaProtocolo'    => 'https://nfe-homologacao.svrs.rs.gov.br/ws/NfeConsulta/NfeConsulta4.asmx',
                    'NfeStatusServico'        => 'https://nfe-homologacao.svrs.rs.gov.br/ws/NfeStatusServico/NfeStatusServico4.asmx',
                    'NfeInutilizacao'         => 'https://nfe-homologacao.svrs.rs.gov.br/ws/nfeinutilizacao/nfeinutilizacao4.asmx',
                    'RecepcaoEvento'          => 'https://nfe-homologacao.svrs.rs.gov.br/ws/recepcaoevento/recepcaoevento4.asmx',
                    'CadConsultaCadastro'     => 'https://cad.svrs.rs.gov.br/ws/cadconsultacadastro/cadconsultacadastro4.asmx',
                ],
            ],
            // SVC-RS — SEFAZ Virtual de Contingência (RS)
            'svc_rs' => [
                1 => [
                    'NfeAutorizacao'       => 'https://nfe.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx',
                    'NfeRetAutorizacao'    => 'https://nfe.svrs.rs.gov.br/ws/NfeRetAutorizacao/NFeRetAutorizacao4.asmx',
                    'NfeConsultaProtocolo' => 'https://nfe.svrs.rs.gov.br/ws/NfeConsulta/NfeConsulta4.asmx',
                    'NfeStatusServico'     => 'https://nfe.svrs.rs.gov.br/ws/NfeStatusServico/NfeStatusServico4.asmx',
                    'RecepcaoEvento'       => 'https://nfe.svrs.rs.gov.br/ws/recepcaoevento/recepcaoevento4.asmx',
                ],
                2 => [
                    'NfeAutorizacao'       => 'https://nfe-homologacao.svrs.rs.gov.br/ws/NfeAutorizacao/NFeAutorizacao4.asmx',
                    'NfeRetAutorizacao'    => 'https://nfe-homologacao.svrs.rs.gov.br/ws/NfeRetAutorizacao/NFeRetAutorizacao4.asmx',
                    'NfeConsultaProtocolo' => 'https://nfe-homologacao.svrs.rs.gov.br/ws/NfeConsulta/NfeConsulta4.asmx',
                    'NfeStatusServico'     => 'https://nfe-homologacao.svrs.rs.gov.br/ws/NfeStatusServico/NfeStatusServico4.asmx',
                    'RecepcaoEvento'       => 'https://nfe-homologacao.svrs.rs.gov.br/ws/recepcaoevento/recepcaoevento4.asmx',
                ],
            ],
            // SVC-AN — SEFAZ Virtual de Contingência (AN)
            'svc_an' => [
                1 => [
                    'NfeAutorizacao'       => 'https://www.svc.fazenda.gov.br/NFeAutorizacao4/NFeAutorizacao4.asmx',
                    'NfeRetAutorizacao'    => 'https://www.svc.fazenda.gov.br/NFeRetAutorizacao4/NFeRetAutorizacao4.asmx',
                    'NfeConsultaProtocolo' => 'https://www.svc.fazenda.gov.br/NFeConsultaProtocolo4/NFeConsultaProtocolo4.asmx',
                    'NfeStatusServico'     => 'https://www.svc.fazenda.gov.br/NFeStatusServico4/NFeStatusServico4.asmx',
                    'RecepcaoEvento'       => 'https://www.svc.fazenda.gov.br/NFeRecepcaoEvento4/NFeRecepcaoEvento4.asmx',
                ],
                2 => [
                    'NfeAutorizacao'       => 'https://hom.svc.fazenda.gov.br/NFeAutorizacao4/NFeAutorizacao4.asmx',
                    'NfeRetAutorizacao'    => 'https://hom.svc.fazenda.gov.br/NFeRetAutorizacao4/NFeRetAutorizacao4.asmx',
                    'NfeConsultaProtocolo' => 'https://hom.svc.fazenda.gov.br/NFeConsultaProtocolo4/NFeConsultaProtocolo4.asmx',
                    'NfeStatusServico'     => 'https://hom.svc.fazenda.gov.br/NFeStatusServico4/NFeStatusServico4.asmx',
                    'RecepcaoEvento'       => 'https://hom.svc.fazenda.gov.br/NFeRecepcaoEvento4/NFeRecepcaoEvento4.asmx',
                ],
            ],
            // Serviço nacional (AN) — Distribuição DF-e (independente do SVRS por UF)
            'nacional' => [
                1 => [
                    'NFeDistribuicaoDFe' => env(
                        'FISCAL_NACIONAL_DIST_DFE_URL',
                        'https://www1.nfe.fazenda.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    ),
                ],
                2 => [
                    'NFeDistribuicaoDFe' => env(
                        'FISCAL_NACIONAL_DIST_DFE_URL_HOM',
                        'https://hom1.nfe.fazenda.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx'
                    ),
                ],
            ],
        ],

        // Regimes tributários
        'regimes' => [
            1 => 'Simples Nacional',
            2 => 'Simples Nacional – Excesso de Sublimite',
            3 => 'Regime Normal (CRT 3 — indicar Lucro Presumido ou Real no cadastro)',
        ],

        // Com regime_tributario = 3: distingue PIS/COFINS de referência no motor (alíquotas em fiscal_aliquotas prevalecem).
        'regime_normal_enquadramento' => [
            1 => 'Lucro presumido (PIS/COFINS cumulativos típicos: 0,65% / 3%)',
            2 => 'Lucro real (PIS/COFINS não cumulativos típicos: 1,65% / 7,6%)',
        ],

        // LC 214/2025 — IBS/CBS: flags e documentação até NT/Convênio exigirem grupos no XML da NF-e.
        'reforma_tributaria' => [
            'habilitar_estudo_ibscbs' => env('FISCAL_REFORMA_ESTUDO_IBSCBS', '0') === '1',
            'observacao_layout' => 'Novos campos IBS/CBS no leiaute NF-e serão integrados quando vigentes.',
        ],

        // Formas de pagamento (NFe 4.00)
        'formas_pagamento' => [
            '01' => 'Dinheiro',
            '02' => 'Cheque',
            '03' => 'Cartão de Crédito',
            '04' => 'Cartão de Débito',
            '05' => 'Crédito Loja',
            '10' => 'Vale Alimentação',
            '11' => 'Vale Refeição',
            '12' => 'Vale Presente',
            '13' => 'Vale Combustível',
            '14' => 'Duplicata Mercantil',
            '15' => 'Boleto Bancário',
            '16' => 'Depósito Bancário',
            '17' => 'Pagamento Instantâneo (PIX)',
            '18' => 'Transferência bancária, Carteira Digital',
            '19' => 'Programa de fidelidade, Cashback, Crédito Virtual',
            '90' => 'Sem Pagamento',
            '99' => 'Outros',
        ],

        // Bandeiras de cartão
        'bandeiras_cartao' => [
            '01' => 'Visa',
            '02' => 'Mastercard',
            '03' => 'American Express',
            '04' => 'Sorocred',
            '05' => 'Diners Club',
            '06' => 'Elo',
            '07' => 'Hipercard',
            '08' => 'Aura',
            '09' => 'Cabal',
            '99' => 'Outros',
        ],

        // Origens da mercadoria
        'origens_mercadoria' => [
            '0' => 'Nacional — exceto os códigos 3,4,5 e 8',
            '1' => 'Estrangeira — Importação direta',
            '2' => 'Estrangeira — Adquirida no mercado interno',
            '3' => 'Nacional — conteúdo de importação > 40% e ≤ 70%',
            '4' => 'Nacional — processos produtivos básicos',
            '5' => 'Nacional — conteúdo de importação ≤ 40%',
            '6' => 'Estrangeira — importação direta, sem similar nacional (CAMEX)',
            '7' => 'Estrangeira — adquirida mercado interno, sem similar nacional (CAMEX)',
            '8' => 'Nacional — conteúdo de importação > 70%',
        ],

        // CST ICMS (Regime Normal)
        'cst_icms' => [
            '00' => 'Tributada integralmente',
            '10' => 'Tributada com cobrança de ICMS por ST',
            '20' => 'Com redução de base de cálculo',
            '30' => 'Isenta ou não tributada com cobrança de ICMS por ST',
            '40' => 'Isenta',
            '41' => 'Não tributada',
            '50' => 'Suspensão',
            '51' => 'Diferimento',
            '60' => 'ICMS cobrado anteriormente por ST',
            '70' => 'Com redução de base de cálculo e cobrança por ST',
            '90' => 'Outros',
        ],

        // CSOSN (Simples Nacional)
        'csosn' => [
            '101' => 'Tributada com permissão de crédito',
            '102' => 'Tributada sem permissão de crédito',
            '103' => 'Isenção do ICMS para faixa de receita bruta',
            '201' => 'Tributada com permissão de crédito e com cobrança de ICMS por ST',
            '202' => 'Tributada sem permissão de crédito e com cobrança de ICMS por ST',
            '203' => 'Isenção do ICMS para faixa de receita bruta e com cobrança de ICMS por ST',
            '300' => 'Imune',
            '400' => 'Não tributada',
            '500' => 'ICMS cobrado anteriormente por ST ou por antecipação',
            '900' => 'Outros',
        ],

        // CST PIS/COFINS
        'cst_pis_cofins' => [
            '01' => 'Operação Tributável — alíquota básica',
            '02' => 'Operação Tributável — alíquota diferenciada',
            '03' => 'Operação Tributável — alíquota por unidade de medida',
            '04' => 'Operação Tributável — tributação monofásica (alíquota zero)',
            '05' => 'Operação Tributável — ST',
            '06' => 'Operação Tributável — alíquota zero',
            '07' => 'Operação Isenta da Contribuição',
            '08' => 'Operação sem Incidência da Contribuição',
            '09' => 'Operação com Suspensão da Contribuição',
            '49' => 'Outras Operações de Saída',
            '50' => 'Operação com Direito a Crédito — vinculada exclusivamente a receita tributada',
            '99' => 'Outras Operações',
        ],

        // CST IPI
        'cst_ipi' => [
            '00' => 'Entrada com Recuperação de Crédito',
            '01' => 'Entrada Tributável com Alíquota Zero',
            '02' => 'Entrada Isenta',
            '03' => 'Entrada Não Tributada',
            '04' => 'Entrada Imune',
            '05' => 'Entrada com Suspensão',
            '49' => 'Outras Entradas',
            '50' => 'Saída Tributada',
            '51' => 'Saída Tributável com Alíquota Zero',
            '52' => 'Saída Isenta',
            '53' => 'Saída Não Tributada',
            '54' => 'Saída Imune',
            '55' => 'Saída com Suspensão',
            '99' => 'Outras Saídas',
        ],

        // Modelos de documento fiscal
        'modelos' => [
            '55'   => 'NF-e (Nota Fiscal Eletrônica)',
            '65'   => 'NFC-e (Nota Fiscal de Consumidor Eletrônica)',
            'NFSE' => 'NFS-e (Nota Fiscal de Serviço Eletrônica)',
        ],

        // Status possíveis da nota
        'status_nota' => [
            'rascunho'    => 'Rascunho',
            'pendente'    => 'Pendente de envio',
            'processando' => 'Processando na SEFAZ',
            'autorizada'  => 'Autorizada',
            'cancelada'   => 'Cancelada',
            'denegada'    => 'Denegada',
            'rejeitada'   => 'Rejeitada',
            'inutilizada' => 'Inutilizada',
        ],

        /**
         * NFS-e municipal: slug nfse_provedor → classe NfseEmissorInterface.
         * Acrescente chaves por município/provedor (ABRASF, GINFES, etc.). '' e 'stub' = bloqueio até integração.
         */
        'nfse_emissor_map' => [
            '' => \App\Utility\Fiscal\Nfse\NfseEmissorStub::class,
            'stub' => \App\Utility\Fiscal\Nfse\NfseEmissorStub::class,
            'ginfes_giss' => \App\Utility\Fiscal\Nfse\NfseEmissorGinfes::class,
            'itu_sp_giss' => \App\Utility\Fiscal\Nfse\NfseEmissorGinfes::class,
        ],

        /** Referência documental do piloto (não altera runtime sozinho). Ver docs/FISCAL_NFSE_PILOTO.md */
        'nfse_piloto' => [
            'provedor' => 'GISS Online (sucessor operacional GINFES)',
            'municipio_ref_nome' => 'Itu',
            'municipio_ref_uf' => 'SP',
            'municipio_ref_ibge' => '3523909',
            'ws_slug_exemplo' => 'itu',
            'url_homologacao_padrao' => 'https://v2-ws-homologacao.giss.com.br/service-ws/nf/nfse-ws?wsdl',
            'url_producao_exemplo' => 'https://ws-itu.giss.com.br/service-ws/nf/nfse-ws?wsdl',
        ],

        /** Parâmetros do emissor NfseEmissorGinfes (ginfes_giss / itu_sp_giss). */
        'nfse_ginfes' => [
            'wsdl' => env('NFSE_GINFES_WSDL', ''),
            'local_cert_pem' => env('NFSE_GINFES_LOCAL_CERT_PEM', ''),
            'mode' => env('NFSE_GINFES_MODE', 'recepcionar_lote_v3'),
            'soap_operation' => env('NFSE_GINFES_SOAP_OPERATION', 'RecepcionarLoteRpsV3'),
        ],
    ],
];
