-- =============================================================================
-- SEEDS — Dados iniciais para o módulo Laudos
-- =============================================================================

BEGIN;

-- Empresa emissora padrão (PGM)
INSERT INTO laudos_empresas (
    id, razao_social, cnpj, email, telefone, telefone2, cep, endereco, site,
    public_validation_url
) VALUES (
    1,
    'PGM Soluções em TI LTDA',
    '42.881.640/0001-06',
    'contato@pgm.inf.br',
    '(54) 3698-9594',
    '(54) 99684-0112',
    '95.703-062',
    'R. Garibaldi, 756 - Sala 02 - São Francisco - Bento Gonçalves - RS',
    'www.pgm.inf.br',
    'https://pgm.inf.br/validar'
) ON CONFLICT (id) DO NOTHING;

-- Reset da sequência se ainda estava em 1
SELECT setval('laudos_empresas_id_seq', GREATEST((SELECT MAX(id) FROM laudos_empresas), 1));

-- =============================================================================
-- CATÁLOGO DE PEÇAS
-- =============================================================================
INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria) VALUES
    (1, 'Memória RAM DDR4 16GB 2666MHz', 'MEM-DDR4-16-2666', 320.00, 'un', 'Memória'),
    (1, 'Memória RAM DDR4 8GB 2666MHz', 'MEM-DDR4-8-2666', 180.00, 'un', 'Memória'),
    (1, 'Memória RAM DDR3L 8GB ECC', 'MEM-DDR3L-8-ECC', 240.00, 'un', 'Memória'),
    (1, 'SSD SATA 480GB', 'SSD-SATA-480', 290.00, 'un', 'Armazenamento'),
    (1, 'SSD SATA 1TB', 'SSD-SATA-1T', 450.00, 'un', 'Armazenamento'),
    (1, 'SSD NVMe 1TB', 'SSD-NVME-1T', 590.00, 'un', 'Armazenamento'),
    (1, 'HD SATA 2TB Server', 'HD-SATA-2T-SRV', 580.00, 'un', 'Armazenamento'),
    (1, 'Fonte ATX 600W 80 Plus Bronze', 'PSU-600-BR', 410.00, 'un', 'Fonte'),
    (1, 'Fonte Servidor 750W Hot-Swap', 'PSU-750-HS', 1200.00, 'un', 'Fonte'),
    (1, 'Placa-mãe Intel LGA 1200', 'MB-LGA-1200', 920.00, 'un', 'Placa-mãe'),
    (1, 'Processador Intel Core i5 12ª geração', 'CPU-I5-12', 1450.00, 'un', 'Processador'),
    (1, 'Processador Intel Xeon E-2336', 'CPU-XEON-E2336', 3200.00, 'un', 'Processador'),
    (1, 'Servidor torre Xeon 32GB RAID', 'SRV-NEW-XEON', 12500.00, 'un', 'Equipamento Completo'),
    (1, 'Desktop Corporativo i5/16GB/SSD', 'PC-CORP-I5', 4500.00, 'un', 'Equipamento Completo'),
    (1, 'Notebook Corporativo i5/16GB', 'NB-CORP-I5', 5800.00, 'un', 'Equipamento Completo'),
    (1, 'Licença Windows Server 2022 Standard', 'WIN-SRV-2022', 4800.00, 'un', 'Software'),
    (1, 'Licença Windows 11 Pro', 'WIN-11-PRO', 1200.00, 'un', 'Software'),
    (1, 'Licença Microsoft 365 Business Standard (anual)', 'M365-BS', 720.00, 'un', 'Software'),
    (1, 'Nobreak 1500VA Senoidal', 'NB-1500VA', 1100.00, 'un', 'Energia'),
    (1, 'Nobreak 3000VA Senoidal', 'NB-3000VA', 2400.00, 'un', 'Energia'),
    (1, 'Bateria 12V 7Ah para Nobreak', 'BAT-12V-7AH', 95.00, 'un', 'Energia'),
    (1, 'Cooler/Dissipador para CPU', 'COOL-CPU', 180.00, 'un', 'Refrigeração'),
    (1, 'Pasta Térmica Premium 4g', 'PASTA-T-4G', 35.00, 'un', 'Refrigeração'),
    (1, 'Tela LCD/LED Notebook 14"', 'TELA-NB-14', 480.00, 'un', 'Notebook'),
    (1, 'Bateria Notebook 6 células', 'BAT-NB-6C', 320.00, 'un', 'Notebook'),
    (1, 'Teclado Notebook (varia por modelo)', 'TEC-NB', 180.00, 'un', 'Notebook')
ON CONFLICT DO NOTHING;

-- =============================================================================
-- CATÁLOGO DE SERVIÇOS
-- =============================================================================
INSERT INTO laudos_catalogo_servicos (empresa_id, descricao, valor_hora_default, horas_default, categoria) VALUES
    (1, 'Diagnóstico em laboratório', 180.00, 4, 'Diagnóstico'),
    (1, 'Diagnóstico em campo', 220.00, 2, 'Diagnóstico'),
    (1, 'Reparo de placa-mãe (BGA)', 250.00, 6, 'Reparo'),
    (1, 'Reparo de fonte', 180.00, 3, 'Reparo'),
    (1, 'Migração de dados e configuração', 180.00, 8, 'Configuração'),
    (1, 'Instalação e configuração de servidor', 200.00, 8, 'Configuração'),
    (1, 'Instalação Windows Server (S.O. + roles)', 200.00, 6, 'Configuração'),
    (1, 'Configuração Active Directory', 220.00, 6, 'Configuração'),
    (1, 'Visita técnica em campo (deslocamento incluso)', 220.00, 2, 'Campo'),
    (1, 'Backup e restore de sistema', 180.00, 4, 'Backup'),
    (1, 'Configuração de backup automatizado', 200.00, 4, 'Backup'),
    (1, 'Recuperação de dados (HD/SSD)', 280.00, 6, 'Recuperação'),
    (1, 'Limpeza interna e troca de pasta térmica', 150.00, 1.5, 'Manutenção'),
    (1, 'Manutenção preventiva (servidor)', 200.00, 4, 'Manutenção'),
    (1, 'Hora técnica de TI (geral)', 180.00, 1, 'Geral')
ON CONFLICT DO NOTHING;

-- =============================================================================
-- TEMPLATES DE DIAGNÓSTICO
-- =============================================================================
INSERT INTO laudos_templates (empresa_id, tipo, nome, conteudo, ordem) VALUES
(1, 'diagnostico', 'Falha no chipset PCH',
'Durante a inspeção técnica, foram realizados procedimentos de verificação física e análise dos componentes eletrônicos internos do equipamento. Foi constatado que o equipamento apresenta dano no chipset PCH (Platform Controller Hub) da placa principal (placa-mãe), ocasionando a interrupção completa do funcionamento do sistema. Os componentes afetados apresentam falha de caráter irreversível, impossibilitando o restabelecimento do funcionamento do equipamento por meio de reparo convencional.', 1),

(1, 'diagnostico', 'HD/SSD com bad blocks',
'Durante a análise técnica do equipamento, foi identificada falha no dispositivo de armazenamento principal, com a presença de setores defeituosos (bad blocks) que comprometem a integridade dos dados e a estabilidade operacional do sistema. A análise SMART do dispositivo confirmou degradação acelerada e iminência de falha total. Recomenda-se substituição imediata e migração de dados antes de perda definitiva.', 2),

(1, 'diagnostico', 'Fonte queimada',
'Foi constatada falha completa na unidade de alimentação (fonte) do equipamento, sem fornecimento de tensão aos componentes internos. Os capacitores e circuitos de proteção apresentam sinais visíveis de comprometimento (estufamento e marcas de oxidação). A substituição da fonte é necessária para restabelecer o funcionamento.', 3),

(1, 'diagnostico', 'Superaquecimento crônico',
'Durante os testes de inspeção foi observado superaquecimento crítico em condições normais de operação, mesmo após limpeza interna e troca da pasta térmica. A análise revelou desgaste do sistema de dissipação de calor, sendo recomendada a substituição do conjunto cooler/dissipador e revisão da ventilação do gabinete.', 4),

(1, 'diagnostico', 'Tela danificada (notebook)',
'Foi identificado dano físico no painel LCD/LED do equipamento, com presença de manchas, linhas verticais ou trincas que inviabilizam a visualização correta do conteúdo. A substituição do painel é a única solução técnica viável.', 5),

(1, 'diagnostico', 'Bateria sem retenção (notebook)',
'A bateria do equipamento apresenta degradação avançada, retendo menos de 30% de sua capacidade original. Tempo de autonomia abaixo de 15 minutos. Recomenda-se substituição da bateria por unidade compatível.', 6),

(1, 'diagnostico', 'Equipamento sem POST',
'Durante a inspeção, foi constatado que o equipamento não realiza o POST (Power-On Self Test). Não há sinal de vídeo nem emissão de bipes diagnósticos. Foram testados componentes individualmente (memória, fonte, processador), sendo identificado o defeito na placa-mãe, com falha em circuito de alimentação dos slots de memória. Reparo da placa-mãe não é viável devido à indisponibilidade de componentes específicos.', 7);

-- =============================================================================
-- TEMPLATES DE CONCLUSÃO
-- =============================================================================
INSERT INTO laudos_templates (empresa_id, tipo, nome, conteudo, ordem) VALUES
(1, 'conclusao', 'Substituição integral recomendada',
'Diante das constatações técnicas apresentadas, conclui-se que o reparo do equipamento é tecnicamente inviável, em razão da indisponibilidade de componentes originais para reposição e da necessidade de substituição de múltiplos componentes, o que demandaria adaptação a tecnologias mais recentes.

Adicionalmente, considerando os custos envolvidos na substituição de hardware, bem como a necessidade de aquisição de novas licenças de software (em função do licenciamento OEM vinculado ao equipamento original), conclui-se que a alternativa mais adequada é a substituição integral do equipamento.

Dessa forma, a substituição por um novo equipamento configura-se como a solução tecnicamente recomendada e economicamente mais viável.', 1),

(1, 'conclusao', 'Reparo viável e recomendado',
'Diante das constatações técnicas apresentadas, conclui-se que o reparo do equipamento é tecnicamente viável e economicamente vantajoso. As peças necessárias estão disponíveis no mercado, e os custos envolvidos representam fração significativamente menor do que a aquisição de equipamento novo equivalente.

Recomenda-se a execução dos serviços e substituição de peças conforme orçamento apresentado, com garantia de funcionamento conforme especificações originais.', 2),

(1, 'conclusao', 'Equipamento sem reparo (perda total)',
'Diante das constatações técnicas apresentadas, conclui-se que o equipamento encontra-se em condição de perda total, sem possibilidade técnica de reparo. Componentes essenciais sofreram danos irreversíveis e não há disponibilidade no mercado para substituição.

Recomenda-se o descarte do equipamento conforme legislação ambiental aplicável (Política Nacional de Resíduos Sólidos – Lei 12.305/2010) e a aquisição de equipamento substituto com configuração equivalente ou superior.', 3);

-- =============================================================================
-- TEMPLATES DE OBJETIVO
-- =============================================================================
INSERT INTO laudos_templates (empresa_id, tipo, nome, conteudo, ordem) VALUES
(1, 'objetivo', 'Apresentação à seguradora',
'O presente parecer técnico tem como finalidade registrar a avaliação técnica do equipamento informático, realizada mediante solicitação do requerente, para fins de documentação e eventual apresentação à seguradora.', 1),

(1, 'objetivo', 'Justificativa de aquisição',
'O presente parecer técnico tem como finalidade registrar a avaliação técnica do equipamento informático, com vistas a fundamentar a necessidade de aquisição de equipamento substituto, bem como subsidiar processos administrativos internos do requerente.', 2),

(1, 'objetivo', 'Documentação para baixa de patrimônio',
'O presente parecer técnico tem como finalidade documentar a inviabilidade técnica de uso do equipamento, fornecendo subsídios para o processo de baixa patrimonial perante o setor contábil/administrativo do requerente.', 3);

COMMIT;
