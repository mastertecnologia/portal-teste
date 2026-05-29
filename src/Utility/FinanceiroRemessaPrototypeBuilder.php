<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

/**
 * Payload da tela Remessa CNAB (modal-remessa / criação de remessa).
 */
class FinanceiroRemessaPrototypeBuilder {

	/**
	 * @return array<string,mixed>
	 */
	public function build(int $empresaId, int $bancoId = 0, string $busca = ''): array {
		$bancos = $this->_loadBancos($empresaId);
		$titulos = [];
		$bancoSelecionado = null;
		$total = 0.0;
		$seqArquivo = '000001';

		if ($bancoId > 0) {
			foreach ($bancos as $b) {
				if ((int)$b['id'] === $bancoId) {
					$bancoSelecionado = $b;
					$seqArquivo = str_pad((string)($b['proxima_remessa'] ?? 1), 6, '0', STR_PAD_LEFT);
					break;
				}
			}
			$titulos = $this->_loadTitulos($empresaId, $bancoId, $busca);
			foreach ($titulos as $t) {
				$total += (float)$t['valor'];
			}
		}

		return [
			'rmBancos' => $bancos,
			'rmTitulos' => $titulos,
			'rmFiltros' => [
				'banco_id' => $bancoId,
				'busca' => $busca,
			],
			'rmKpi' => [
				'banco_label' => $bancoSelecionado['label_curta'] ?? '—',
				'qtd_titulos' => count($titulos),
				'total' => $total,
				'seq_arquivo' => $seqArquivo,
			],
			'rmMeta' => [
				'data_hoje' => Time::now()->format('Y-m-d'),
				'nome_arquivo' => $this->_nomeArquivoSugerido($bancoSelecionado),
			],
		];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private function _loadBancos(int $empresaId): array {
		$out = [];
		try {
			$rows = TableRegistry::getTableLocator()->get('FinanceiroBancos')
				->find()
				->where(['FinanceiroBancos.idempresa' => $empresaId, 'FinanceiroBancos.ativo' => true])
				->order(['FinanceiroBancos.codigo_banco' => 'ASC', 'FinanceiroBancos.nome' => 'ASC'])
				->limit(80)
				->all();
			foreach ($rows as $b) {
				$codigo = (string)($b->get('codigo_banco') ?? $b->get('numero_banco') ?? '');
				$nome = (string)$b->get('nome');
				$brand = FinanceiroBancosPrototypeUi::branding($codigo, $nome);
				[$ag, $cc] = FinanceiroBancosPrototypeUi::formatAgenciaConta($b);
				$carteira = trim((string)$b->get('carteira'));
				$label = $brand['sigla'] . ' · Ag.' . $ag . ' · CC.' . $cc;
				if ($carteira !== '') {
					$label .= ' · Carteira ' . $carteira;
				}
				$out[] = [
					'id' => (int)$b->get('id'),
					'label' => $label,
					'label_curta' => $brand['sigla'] . ' · ' . $cc,
					'sigla' => $brand['sigla'],
					'proxima_remessa' => (int)$b->get('proxima_remessa'),
					'codigo_banco' => $codigo,
				];
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private function _loadTitulos(int $empresaId, int $bancoId, string $busca): array {
		$out = [];
		try {
			$where = [
				'FinanceiroLancamentos.idempresa' => $empresaId,
				'FinanceiroLancamentos.tipo' => 'receita',
				'FinanceiroLancamentos.status' => 'aberto',
				'FinanceiroLancamentos.financeiro_banco_id' => $bancoId,
			];
			$q = TableRegistry::getTableLocator()->get('FinanceiroLancamentos')
				->find()
				->contain(['Clientes'])
				->where($where)
				->order(['FinanceiroLancamentos.data_vencimento' => 'ASC', 'FinanceiroLancamentos.id' => 'ASC'])
				->limit(200);
			if ($busca !== '') {
				$q->where([
					'OR' => [
						'FinanceiroLancamentos.descricao ILIKE' => '%' . $busca . '%',
						'Clientes.razaosocial ILIKE' => '%' . $busca . '%',
						'Clientes.nome ILIKE' => '%' . $busca . '%',
					],
				]);
			}
			foreach ($q->all() as $row) {
				$cliente = $row->cliente ?? null;
				$nomeCli = '';
				if ($cliente !== null) {
					$nomeCli = trim((string)($cliente->get('razaosocial') ?? $cliente->get('nome') ?? ''));
				}
				$venc = $row->get('data_vencimento');
				$out[] = [
					'id' => (int)$row->get('id'),
					'codigo' => sprintf('TIT-%d', (int)$row->get('id')),
					'cliente' => $nomeCli !== '' ? $nomeCli : __('Sem cliente'),
					'descricao' => (string)$row->get('descricao'),
					'vencimento' => $venc instanceof \DateTimeInterface ? $venc->format('d/m/Y') : '—',
					'valor' => (float)$row->get('valor'),
				];
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @param array<string,mixed>|null $banco
	 */
	private function _nomeArquivoSugerido(?array $banco): string {
		if ($banco === null) {
			return 'REM_' . date('Y-m-d') . '_000001.REM';
		}
		$sigla = preg_replace('/[^A-Z0-9]/', '', strtoupper((string)($banco['sigla'] ?? 'BNK')));

		return sprintf('REM_%s_%s_%06d.REM', $sigla ?: 'BNK', date('Y-m-d'), (int)($banco['proxima_remessa'] ?? 1));
	}
}
