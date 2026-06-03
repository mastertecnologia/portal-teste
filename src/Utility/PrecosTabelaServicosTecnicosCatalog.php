<?php
declare(strict_types=1);

namespace App\Utility;

/**
 * Catálogo oficial — Tabela de Serviços Técnicos PGM (preços informados).
 */
class PrecosTabelaServicosTecnicosCatalog {

	public const TABELA_CODIGO = 'TAB-SERV-TECNICOS';
	public const TABELA_NOME = 'Tabela de Serviços Técnicos';

	/**
	 * @return array<int,array{categoria:string,codigo:string,descricao:string,preco:float,unidade:string}>
	 */
	public static function items(): array {
		return [
			// Formatações
			['categoria' => 'Formatações', 'codigo' => 'SRV-FMT-01', 'descricao' => 'Formatação com cópia de dados com busca e entrega', 'preco' => 300.00, 'unidade' => 'UN'],
			['categoria' => 'Formatações', 'codigo' => 'SRV-FMT-02', 'descricao' => 'Formatação sem cópia de dados com busca e entrega', 'preco' => 240.00, 'unidade' => 'UN'],
			['categoria' => 'Formatações', 'codigo' => 'SRV-FMT-03', 'descricao' => 'Formatação com cópia de dados interno', 'preco' => 280.00, 'unidade' => 'UN'],
			['categoria' => 'Formatações', 'codigo' => 'SRV-FMT-04', 'descricao' => 'Formatação sem cópia de dados interno', 'preco' => 220.00, 'unidade' => 'UN'],
			['categoria' => 'Formatações', 'codigo' => 'SRV-FMT-05', 'descricao' => 'Formatação com cópia de dados com programas fora padrão com busca e entrega', 'preco' => 380.00, 'unidade' => 'UN'],
			['categoria' => 'Formatações', 'codigo' => 'SRV-FMT-06', 'descricao' => 'Formatação sem cópia de dados com programas fora padrão com busca e entrega', 'preco' => 320.00, 'unidade' => 'UN'],
			// Upgrade de HD com clone
			['categoria' => 'Upgrade de HD', 'codigo' => 'SRV-HD-01', 'descricao' => 'Upgrade de HD com clone — HD para SSD', 'preco' => 240.00, 'unidade' => 'UN'],
			['categoria' => 'Upgrade de HD', 'codigo' => 'SRV-HD-02', 'descricao' => 'Upgrade de HD com clone — HD ou SSD para NVMe', 'preco' => 260.00, 'unidade' => 'UN'],
			// Instalações de programas
			['categoria' => 'Instalações de programas', 'codigo' => 'SRV-INS-01', 'descricao' => 'Instalação de programas — pacote básicos', 'preco' => 240.00, 'unidade' => 'UN'],
			['categoria' => 'Instalações de programas', 'codigo' => 'SRV-INS-02', 'descricao' => 'Instalação de programas — específicos', 'preco' => 340.00, 'unidade' => 'UN'],
			// Acesso remoto
			['categoria' => 'Acesso remoto', 'codigo' => 'SRV-REM-01', 'descricao' => 'Acesso remoto — 30 minutos', 'preco' => 100.00, 'unidade' => 'UN'],
			['categoria' => 'Acesso remoto', 'codigo' => 'SRV-REM-02', 'descricao' => 'Acesso remoto — 60 minutos', 'preco' => 200.00, 'unidade' => 'UN'],
			['categoria' => 'Acesso remoto', 'codigo' => 'SRV-REM-03', 'descricao' => 'Acesso remoto adicional — cada 30 min', 'preco' => 100.00, 'unidade' => 'UN'],
			// Atendimento presencial
			['categoria' => 'Atendimento técnico presencial', 'codigo' => 'SRV-ATE-01', 'descricao' => 'Atendimento técnico presencial — 30 minutos', 'preco' => 150.00, 'unidade' => 'UN'],
			['categoria' => 'Atendimento técnico presencial', 'codigo' => 'SRV-ATE-02', 'descricao' => 'Atendimento técnico presencial — 60 minutos', 'preco' => 300.00, 'unidade' => 'UN'],
			['categoria' => 'Atendimento técnico presencial', 'codigo' => 'SRV-ATE-03', 'descricao' => 'Atendimento técnico presencial adicional — cada 30 min', 'preco' => 150.00, 'unidade' => 'UN'],
			// Eletrônicos
			['categoria' => 'Serviço mínimo eletrônicos', 'codigo' => 'SRV-ELE-01', 'descricao' => 'Serviço mínimo — nobreak / monitor / estabilizador / troca de fonte / upgrade memória', 'preco' => 120.00, 'unidade' => 'UN'],
			// Notebooks
			['categoria' => 'Notebooks', 'codigo' => 'SRV-NB-01', 'descricao' => 'Notebook — taxa de abertura e diagnóstico', 'preco' => 150.00, 'unidade' => 'UN'],
			// Impressoras
			['categoria' => 'Impressoras', 'codigo' => 'SRV-IMP-01', 'descricao' => 'Impressora mono laser — análise e orçamento (taxa se não aprovado)', 'preco' => 150.00, 'unidade' => 'UN'],
			['categoria' => 'Impressoras', 'codigo' => 'SRV-IMP-02', 'descricao' => 'Impressora color laser — análise e orçamento (taxa se não aprovado)', 'preco' => 220.00, 'unidade' => 'UN'],
			['categoria' => 'Impressoras', 'codigo' => 'SRV-IMP-03', 'descricao' => 'Impressora ecotank — taxa de análise e orçamento', 'preco' => 200.00, 'unidade' => 'UN'],
			['categoria' => 'Impressoras', 'codigo' => 'SRV-IMP-04', 'descricao' => 'Impressora ecotank — somente limpeza', 'preco' => 320.00, 'unidade' => 'UN'],
			['categoria' => 'Impressoras', 'codigo' => 'SRV-IMP-05', 'descricao' => 'Impressora mono laser — somente limpeza', 'preco' => 250.00, 'unidade' => 'UN'],
			['categoria' => 'Impressoras', 'codigo' => 'SRV-IMP-06', 'descricao' => 'Impressora color laser — somente limpeza', 'preco' => 350.00, 'unidade' => 'UN'],
		];
	}
}
