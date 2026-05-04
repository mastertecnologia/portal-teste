<?php
use Migrations\AbstractMigration;

class SeedLaudosData extends AbstractMigration
{
    public function up()
    {
        if (!$this->hasTable('laudos_empresas')) {
            return;
        }

        // Empresa emissora padrão (PGM) — ON CONFLICT para ser idempotente
        $this->execute("INSERT INTO laudos_empresas (
            id, razao_social, cnpj, email, telefone, telefone2, cep, endereco, site,
            public_validation_url
        ) VALUES (
            1,
            'PGM Solucoes em TI LTDA',
            '42.881.640/0001-06',
            'contato@pgm.inf.br',
            '(54) 3698-9594',
            '(54) 99684-0112',
            '95.703-062',
            'R. Garibaldi, 756 - Sala 02 - Sao Francisco - Bento Goncalves - RS',
            'www.pgm.inf.br',
            'https://pgm.inf.br/validar'
        ) ON CONFLICT (id) DO NOTHING");

        $this->execute("SELECT setval('laudos_empresas_id_seq',
            GREATEST((SELECT MAX(id) FROM laudos_empresas), 1))");

        // Catálogo de peças — sem unique constraint: protege com NOT EXISTS
        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Memoria RAM DDR4 16GB 2666MHz','MEM-DDR4-16-2666',320.00,'un','Memoria'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='MEM-DDR4-16-2666')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Memoria RAM DDR4 8GB 2666MHz','MEM-DDR4-8-2666',180.00,'un','Memoria'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='MEM-DDR4-8-2666')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Memoria RAM DDR3L 8GB ECC','MEM-DDR3L-8-ECC',240.00,'un','Memoria'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='MEM-DDR3L-8-ECC')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'SSD SATA 480GB','SSD-SATA-480',290.00,'un','Armazenamento'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='SSD-SATA-480')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'SSD SATA 1TB','SSD-SATA-1T',450.00,'un','Armazenamento'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='SSD-SATA-1T')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'SSD NVMe 1TB','SSD-NVME-1T',590.00,'un','Armazenamento'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='SSD-NVME-1T')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'HD SATA 2TB Server','HD-SATA-2T-SRV',580.00,'un','Armazenamento'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='HD-SATA-2T-SRV')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Fonte ATX 600W 80 Plus Bronze','PSU-600-BR',410.00,'un','Fonte'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='PSU-600-BR')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Fonte Servidor 750W Hot-Swap','PSU-750-HS',1200.00,'un','Fonte'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='PSU-750-HS')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Placa-mae Intel LGA 1200','MB-LGA-1200',920.00,'un','Placa-mae'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='MB-LGA-1200')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Processador Intel Core i5 12a geracao','CPU-I5-12',1450.00,'un','Processador'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='CPU-I5-12')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Processador Intel Xeon E-2336','CPU-XEON-E2336',3200.00,'un','Processador'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='CPU-XEON-E2336')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Servidor torre Xeon 32GB RAID','SRV-NEW-XEON',12500.00,'un','Equipamento Completo'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='SRV-NEW-XEON')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Desktop Corporativo i5/16GB/SSD','PC-CORP-I5',4500.00,'un','Equipamento Completo'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='PC-CORP-I5')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Notebook Corporativo i5/16GB','NB-CORP-I5',5800.00,'un','Equipamento Completo'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='NB-CORP-I5')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Licenca Windows Server 2022 Standard','WIN-SRV-2022',4800.00,'un','Software'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='WIN-SRV-2022')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Licenca Windows 11 Pro','WIN-11-PRO',1200.00,'un','Software'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='WIN-11-PRO')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Licenca Microsoft 365 Business Standard anual','M365-BS',720.00,'un','Software'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='M365-BS')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Nobreak 1500VA Senoidal','NB-1500VA',1100.00,'un','Energia'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='NB-1500VA')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Nobreak 3000VA Senoidal','NB-3000VA',2400.00,'un','Energia'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='NB-3000VA')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Bateria 12V 7Ah para Nobreak','BAT-12V-7AH',95.00,'un','Energia'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='BAT-12V-7AH')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Cooler/Dissipador para CPU','COOL-CPU',180.00,'un','Refrigeracao'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='COOL-CPU')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Pasta Termica Premium 4g','PASTA-T-4G',35.00,'un','Refrigeracao'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='PASTA-T-4G')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Tela LCD/LED Notebook 14','TELA-NB-14',480.00,'un','Notebook'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='TELA-NB-14')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Bateria Notebook 6 celulas','BAT-NB-6C',320.00,'un','Notebook'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='BAT-NB-6C')");

        $this->execute("INSERT INTO laudos_catalogo_pecas (empresa_id, nome, codigo, preco_default, unidade, categoria)
            SELECT 1,'Teclado Notebook','TEC-NB',180.00,'un','Notebook'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_pecas WHERE empresa_id=1 AND codigo='TEC-NB')");

        // Catálogo de serviços
        $this->execute("INSERT INTO laudos_catalogo_servicos (empresa_id, descricao, valor_hora_default, horas_default, categoria)
            SELECT 1,'Diagnostico em laboratorio',180.00,4,'Diagnostico'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_servicos WHERE empresa_id=1 AND descricao='Diagnostico em laboratorio')");

        $this->execute("INSERT INTO laudos_catalogo_servicos (empresa_id, descricao, valor_hora_default, horas_default, categoria)
            SELECT 1,'Diagnostico em campo',220.00,2,'Diagnostico'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_servicos WHERE empresa_id=1 AND descricao='Diagnostico em campo')");

        $this->execute("INSERT INTO laudos_catalogo_servicos (empresa_id, descricao, valor_hora_default, horas_default, categoria)
            SELECT 1,'Reparo de placa-mae (BGA)',250.00,6,'Reparo'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_servicos WHERE empresa_id=1 AND descricao='Reparo de placa-mae (BGA)')");

        $this->execute("INSERT INTO laudos_catalogo_servicos (empresa_id, descricao, valor_hora_default, horas_default, categoria)
            SELECT 1,'Reparo de fonte',180.00,3,'Reparo'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_servicos WHERE empresa_id=1 AND descricao='Reparo de fonte')");

        $this->execute("INSERT INTO laudos_catalogo_servicos (empresa_id, descricao, valor_hora_default, horas_default, categoria)
            SELECT 1,'Migracao de dados e configuracao',180.00,8,'Configuracao'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_servicos WHERE empresa_id=1 AND descricao='Migracao de dados e configuracao')");

        $this->execute("INSERT INTO laudos_catalogo_servicos (empresa_id, descricao, valor_hora_default, horas_default, categoria)
            SELECT 1,'Instalacao e configuracao de servidor',200.00,8,'Configuracao'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_servicos WHERE empresa_id=1 AND descricao='Instalacao e configuracao de servidor')");

        $this->execute("INSERT INTO laudos_catalogo_servicos (empresa_id, descricao, valor_hora_default, horas_default, categoria)
            SELECT 1,'Instalacao Windows Server (S.O. + roles)',200.00,6,'Configuracao'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_servicos WHERE empresa_id=1 AND descricao='Instalacao Windows Server (S.O. + roles)')");

        $this->execute("INSERT INTO laudos_catalogo_servicos (empresa_id, descricao, valor_hora_default, horas_default, categoria)
            SELECT 1,'Configuracao Active Directory',220.00,6,'Configuracao'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_servicos WHERE empresa_id=1 AND descricao='Configuracao Active Directory')");

        $this->execute("INSERT INTO laudos_catalogo_servicos (empresa_id, descricao, valor_hora_default, horas_default, categoria)
            SELECT 1,'Visita tecnica em campo (deslocamento incluso)',220.00,2,'Campo'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_servicos WHERE empresa_id=1 AND descricao='Visita tecnica em campo (deslocamento incluso)')");

        $this->execute("INSERT INTO laudos_catalogo_servicos (empresa_id, descricao, valor_hora_default, horas_default, categoria)
            SELECT 1,'Backup e restore de sistema',180.00,4,'Backup'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_servicos WHERE empresa_id=1 AND descricao='Backup e restore de sistema')");

        $this->execute("INSERT INTO laudos_catalogo_servicos (empresa_id, descricao, valor_hora_default, horas_default, categoria)
            SELECT 1,'Configuracao de backup automatizado',200.00,4,'Backup'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_servicos WHERE empresa_id=1 AND descricao='Configuracao de backup automatizado')");

        $this->execute("INSERT INTO laudos_catalogo_servicos (empresa_id, descricao, valor_hora_default, horas_default, categoria)
            SELECT 1,'Recuperacao de dados (HD/SSD)',280.00,6,'Recuperacao'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_servicos WHERE empresa_id=1 AND descricao='Recuperacao de dados (HD/SSD)')");

        $this->execute("INSERT INTO laudos_catalogo_servicos (empresa_id, descricao, valor_hora_default, horas_default, categoria)
            SELECT 1,'Limpeza interna e troca de pasta termica',150.00,1.5,'Manutencao'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_servicos WHERE empresa_id=1 AND descricao='Limpeza interna e troca de pasta termica')");

        $this->execute("INSERT INTO laudos_catalogo_servicos (empresa_id, descricao, valor_hora_default, horas_default, categoria)
            SELECT 1,'Manutencao preventiva (servidor)',200.00,4,'Manutencao'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_servicos WHERE empresa_id=1 AND descricao='Manutencao preventiva (servidor)')");

        $this->execute("INSERT INTO laudos_catalogo_servicos (empresa_id, descricao, valor_hora_default, horas_default, categoria)
            SELECT 1,'Hora tecnica de TI (geral)',180.00,1,'Geral'
            WHERE NOT EXISTS (SELECT 1 FROM laudos_catalogo_servicos WHERE empresa_id=1 AND descricao='Hora tecnica de TI (geral)')");

        // Templates de diagnóstico
        $this->execute("INSERT INTO laudos_templates (empresa_id, tipo, nome, conteudo, ordem)
            SELECT 1,'diagnostico','Falha no chipset PCH',
            'Durante a inspecao tecnica, foram realizados procedimentos de verificacao fisica e analise dos componentes eletronicos internos do equipamento. Foi constatado que o equipamento apresenta dano no chipset PCH (Platform Controller Hub) da placa principal (placa-mae), ocasionando a interrupcao completa do funcionamento do sistema. Os componentes afetados apresentam falha de carater irreversivel, impossibilitando o restabelecimento do funcionamento do equipamento por meio de reparo convencional.',1
            WHERE NOT EXISTS (SELECT 1 FROM laudos_templates WHERE empresa_id=1 AND nome='Falha no chipset PCH')");

        $this->execute("INSERT INTO laudos_templates (empresa_id, tipo, nome, conteudo, ordem)
            SELECT 1,'diagnostico','HD/SSD com bad blocks',
            'Durante a analise tecnica do equipamento, foi identificada falha no dispositivo de armazenamento principal, com a presenca de setores defeituosos (bad blocks) que comprometem a integridade dos dados e a estabilidade operacional do sistema. A analise SMART do dispositivo confirmou degradacao acelerada e iminencia de falha total. Recomenda-se substituicao imediata e migracao de dados antes de perda definitiva.',2
            WHERE NOT EXISTS (SELECT 1 FROM laudos_templates WHERE empresa_id=1 AND nome='HD/SSD com bad blocks')");

        $this->execute("INSERT INTO laudos_templates (empresa_id, tipo, nome, conteudo, ordem)
            SELECT 1,'diagnostico','Fonte queimada',
            'Foi constatada falha completa na unidade de alimentacao (fonte) do equipamento, sem fornecimento de tensao aos componentes internos. Os capacitores e circuitos de protecao apresentam sinais visiveis de comprometimento (estufamento e marcas de oxidacao). A substituicao da fonte e necessaria para restabelecer o funcionamento.',3
            WHERE NOT EXISTS (SELECT 1 FROM laudos_templates WHERE empresa_id=1 AND nome='Fonte queimada')");

        $this->execute("INSERT INTO laudos_templates (empresa_id, tipo, nome, conteudo, ordem)
            SELECT 1,'diagnostico','Superaquecimento cronico',
            'Durante os testes de inspecao foi observado superaquecimento critico em condicoes normais de operacao, mesmo apos limpeza interna e troca da pasta termica. A analise revelou desgaste do sistema de dissipacao de calor, sendo recomendada a substituicao do conjunto cooler/dissipador e revisao da ventilacao do gabinete.',4
            WHERE NOT EXISTS (SELECT 1 FROM laudos_templates WHERE empresa_id=1 AND nome='Superaquecimento cronico')");

        $this->execute("INSERT INTO laudos_templates (empresa_id, tipo, nome, conteudo, ordem)
            SELECT 1,'diagnostico','Tela danificada (notebook)',
            'Foi identificado dano fisico no painel LCD/LED do equipamento, com presenca de manchas, linhas verticais ou trincas que inviabilizam a visualizacao correta do conteudo. A substituicao do painel e a unica solucao tecnica viavel.',5
            WHERE NOT EXISTS (SELECT 1 FROM laudos_templates WHERE empresa_id=1 AND nome='Tela danificada (notebook)')");

        $this->execute("INSERT INTO laudos_templates (empresa_id, tipo, nome, conteudo, ordem)
            SELECT 1,'diagnostico','Bateria sem retencao (notebook)',
            'A bateria do equipamento apresenta degradacao avancada, retendo menos de 30% de sua capacidade original. Tempo de autonomia abaixo de 15 minutos. Recomenda-se substituicao da bateria por unidade compativel.',6
            WHERE NOT EXISTS (SELECT 1 FROM laudos_templates WHERE empresa_id=1 AND nome='Bateria sem retencao (notebook)')");

        $this->execute("INSERT INTO laudos_templates (empresa_id, tipo, nome, conteudo, ordem)
            SELECT 1,'diagnostico','Equipamento sem POST',
            'Durante a inspecao, foi constatado que o equipamento nao realiza o POST (Power-On Self Test). Nao ha sinal de video nem emissao de bipes diagnosticos. Foram testados componentes individualmente (memoria, fonte, processador), sendo identificado o defeito na placa-mae, com falha em circuito de alimentacao dos slots de memoria. Reparo da placa-mae nao e viavel devido a indisponibilidade de componentes especificos.',7
            WHERE NOT EXISTS (SELECT 1 FROM laudos_templates WHERE empresa_id=1 AND nome='Equipamento sem POST')");

        // Templates de conclusão
        $this->execute("INSERT INTO laudos_templates (empresa_id, tipo, nome, conteudo, ordem)
            SELECT 1,'conclusao','Substituicao integral recomendada',
            'Diante das constatacoes tecnicas apresentadas, conclui-se que o reparo do equipamento e tecnicamente inviavel, em razao da indisponibilidade de componentes originais para reposicao e da necessidade de substituicao de multiplos componentes. Adicionalmente, considerando os custos envolvidos e a necessidade de aquisicao de novas licencas de software, conclui-se que a alternativa mais adequada e a substituicao integral do equipamento.',1
            WHERE NOT EXISTS (SELECT 1 FROM laudos_templates WHERE empresa_id=1 AND nome='Substituicao integral recomendada')");

        $this->execute("INSERT INTO laudos_templates (empresa_id, tipo, nome, conteudo, ordem)
            SELECT 1,'conclusao','Reparo viavel e recomendado',
            'Diante das constatacoes tecnicas apresentadas, conclui-se que o reparo do equipamento e tecnicamente viavel e economicamente vantajoso. As pecas necessarias estao disponiveis no mercado, e os custos envolvidos representam fracao significativamente menor do que a aquisicao de equipamento novo equivalente.',2
            WHERE NOT EXISTS (SELECT 1 FROM laudos_templates WHERE empresa_id=1 AND nome='Reparo viavel e recomendado')");

        $this->execute("INSERT INTO laudos_templates (empresa_id, tipo, nome, conteudo, ordem)
            SELECT 1,'conclusao','Equipamento sem reparo (perda total)',
            'Diante das constatacoes tecnicas apresentadas, conclui-se que o equipamento encontra-se em condicao de perda total, sem possibilidade tecnica de reparo. Componentes essenciais sofreram danos irreversiveis e nao ha disponibilidade no mercado para substituicao.',3
            WHERE NOT EXISTS (SELECT 1 FROM laudos_templates WHERE empresa_id=1 AND nome='Equipamento sem reparo (perda total)')");

        // Templates de objetivo
        $this->execute("INSERT INTO laudos_templates (empresa_id, tipo, nome, conteudo, ordem)
            SELECT 1,'objetivo','Apresentacao a seguradora',
            'O presente parecer tecnico tem como finalidade registrar a avaliacao tecnica do equipamento informatico, realizada mediante solicitacao do requerente, para fins de documentacao e eventual apresentacao a seguradora.',1
            WHERE NOT EXISTS (SELECT 1 FROM laudos_templates WHERE empresa_id=1 AND nome='Apresentacao a seguradora')");

        $this->execute("INSERT INTO laudos_templates (empresa_id, tipo, nome, conteudo, ordem)
            SELECT 1,'objetivo','Justificativa de aquisicao',
            'O presente parecer tecnico tem como finalidade registrar a avaliacao tecnica do equipamento informatico, com vistas a fundamentar a necessidade de aquisicao de equipamento substituto, bem como subsidiar processos administrativos internos do requerente.',2
            WHERE NOT EXISTS (SELECT 1 FROM laudos_templates WHERE empresa_id=1 AND nome='Justificativa de aquisicao')");

        $this->execute("INSERT INTO laudos_templates (empresa_id, tipo, nome, conteudo, ordem)
            SELECT 1,'objetivo','Documentacao para baixa de patrimonio',
            'O presente parecer tecnico tem como finalidade documentar a inviabilidade tecnica de uso do equipamento, fornecendo subsidios para o processo de baixa patrimonial perante o setor contabil/administrativo do requerente.',3
            WHERE NOT EXISTS (SELECT 1 FROM laudos_templates WHERE empresa_id=1 AND nome='Documentacao para baixa de patrimonio')");
    }

    public function down()
    {
        // Seeds não são reversíveis
    }
}
