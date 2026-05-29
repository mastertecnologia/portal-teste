<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

/**
 * Payload pg-relatorios-fin — hub de relatórios financeiros.
 */
class FinanceiroRelatoriosPrototypeBuilder {

	/**
	 * @return array<string,mixed>
	 */
	public function build(int $empresaId): array {
		$cards = [
			[
				'icon' => '📊',
				'gradient' => 'linear-gradient(135deg,var(--teal),var(--teal-dark))',
				'title' => 'DRE Gerencial',
				'tags' => 'mensal · trimestral · anual',
				'desc' => 'Demonstrativo de resultados estruturado · receita até lucro líquido · comparativo com período anterior',
				'url' => ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'dre'],
			],
			[
				'icon' => '💰',
				'gradient' => 'linear-gradient(135deg,var(--blue),#0C447C)',
				'title' => 'Fluxo de Caixa',
				'tags' => 'realizado vs projetado',
				'desc' => 'Entradas e saídas · saldo diário · projeção 90 dias · alertas de caixa negativo',
				'url' => ['controller' => 'BancosPrototype', 'action' => 'view', 'fluxo-caixa'],
			],
			[
				'icon' => '📈',
				'gradient' => 'linear-gradient(135deg,#D946A0,#7A1B5C)',
				'title' => 'Balanço Patrimonial',
				'tags' => 'ativo · passivo · PL',
				'desc' => 'Posição patrimonial completa · contas a receber/pagar · estoque · imobilizado · capital de giro',
				'url' => ['controller' => 'FinanceiroRelatorios', 'action' => 'index'],
			],
			[
				'icon' => '📥',
				'gradient' => 'linear-gradient(135deg,var(--amber),#8A4D02)',
				'title' => 'Posição Receber',
				'tags' => 'aging por cliente',
				'desc' => 'Contas a receber por faixa (vencer/0-30/30-60/60-90/+90) · risco de inadimplência',
				'url' => ['controller' => 'FinanceiroRelatorios', 'action' => 'aging', '?' => ['tipo' => 'receita']],
			],
			[
				'icon' => '📤',
				'gradient' => 'linear-gradient(135deg,var(--red),#7A1822)',
				'title' => 'Posição Pagar',
				'tags' => 'compromissos por categoria',
				'desc' => 'Obrigações por fornecedor/categoria · projeção de saídas · necessidade de caixa',
				'url' => ['controller' => 'FinanceiroPrototype', 'action' => 'contasPagar'],
			],
			[
				'icon' => '🧾',
				'gradient' => 'linear-gradient(135deg,#6B5B95,#3D2D63)',
				'title' => 'SPED Contribuições',
				'tags' => 'PIS · COFINS · ICMS',
				'desc' => 'Apuração tributária mensal · arquivos SPED · livros fiscais para contabilidade',
				'url' => ['controller' => 'FiscalRelatorios', 'action' => 'exportarSped'],
			],
			[
				'icon' => '🏦',
				'gradient' => 'linear-gradient(135deg,#06B6D4,#0C4A6E)',
				'title' => 'Conciliação Bancária',
				'tags' => 'batimento OFX',
				'desc' => 'Relatório de conciliação · diferenças identificadas · pendências de ajuste',
				'url' => ['controller' => 'BancosPrototype', 'action' => 'view', 'conciliacao'],
			],
			[
				'icon' => '🎯',
				'gradient' => 'linear-gradient(135deg,#F59E0B,#8A4D02)',
				'title' => 'Custo por Centro',
				'tags' => 'rateio · departamentos',
				'desc' => 'Análise de custos por centro de custo · rateio de overhead · margem por área',
				'url' => ['controller' => 'FinanceiroRelatorios', 'action' => 'porCentroCusto'],
			],
		];

		$recentes = [];
		try {
			$rows = TableRegistry::getTableLocator()->get('Atividades')
				->find()
				->where([
					'Atividades.modulo IN' => ['Financeiro', 'FinanceiroRelatorios', 'FiscalSped'],
				])
				->contain(['Users' => ['fields' => ['id', 'name']]])
				->order(['Atividades.created' => 'DESC'])
				->limit(8)
				->all();
			foreach ($rows as $a) {
				$acao = (string)$a->get('acao');
				$rel = $this->_mapAtividadeRelatorio($acao);
				if ($rel === null) {
					continue;
				}
				$recentes[] = [
					'relatorio' => $rel['label'],
					'periodo' => Time::now()->subMonth()->i18nFormat('MMMM yyyy'),
					'gerado' => $a->get('created'),
					'por' => (string)($a->user->name ?? 'Sistema'),
					'url' => $rel['url'],
				];
				if (count($recentes) >= 3) {
					break;
				}
			}
		} catch (\Throwable $e) {
		}

		if ($recentes === []) {
			$recentes = [
				[
					'relatorio' => '📊 DRE Gerencial',
					'periodo' => Time::now()->subMonth()->i18nFormat('MMMM yyyy'),
					'gerado' => null,
					'por' => '—',
					'url' => ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'dre'],
				],
			];
		}

		return [
			'relCards' => $cards,
			'relRecentes' => $recentes,
		];
	}

	/**
	 * @return array{label:string,url:array}|null
	 */
	private function _mapAtividadeRelatorio(string $acao): ?array {
		$map = [
			'dre' => ['label' => '📊 DRE Gerencial', 'url' => ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'dre']],
			'aging' => ['label' => '📥 Posição Receber', 'url' => ['controller' => 'FinanceiroRelatorios', 'action' => 'aging']],
			'fluxoCaixa' => ['label' => '💰 Fluxo de Caixa', 'url' => ['controller' => 'BancosPrototype', 'action' => 'view', 'fluxo-caixa']],
		];
		foreach ($map as $key => $item) {
			if (stripos($acao, $key) !== false) {
				return $item;
			}
		}

		return null;
	}
}
