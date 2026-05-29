<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\ORM\TableRegistry;

/**
 * Payload da tela Retornos bancários (painel operacional + import CNAB).
 */
class FinanceiroRetornoPrototypeBuilder {

	/**
	 * @return array<string,mixed>
	 */
	public function build(int $empresaId): array {
		$bancos = [];
		try {
			$bancos = TableRegistry::getTableLocator()->get('FinanceiroBancos')
				->find()
				->where(['FinanceiroBancos.idempresa' => $empresaId])
				->order(['FinanceiroBancos.codigo_banco' => 'ASC'])
				->limit(80)
				->all()
				->toArray();
		} catch (\Throwable $e) {
		}

		$resumoExtrato = $this->_resumoExtratoPorBanco($empresaId, $bancos);
		$linhas = [];
		$kpi = [
			'bancos' => count($bancos),
			'com_conta' => 0,
			'com_extrato' => 0,
			'pendentes' => 0,
			'conciliados' => 0,
			'eventos' => 0,
			'ultimo_movimento' => null,
		];

		foreach ($bancos as $banco) {
			$id = (int)$banco->get('id');
			$codigo = (string)($banco->get('codigo_banco') ?? $banco->get('numero_banco') ?? '');
			$nome = (string)$banco->get('nome');
			$brand = FinanceiroBancosPrototypeUi::branding($codigo, $nome);
			[$ag, $cc] = FinanceiroBancosPrototypeUi::formatAgenciaConta($banco);
			$temConta = trim((string)$banco->get('numero_agencia')) !== '' && trim((string)$banco->get('numero_conta')) !== '';

			$ext = $resumoExtrato[$id] ?? ['quantidade' => 0, 'conciliados' => 0, 'pendentes' => 0, 'ultimo' => null];
			$quantidade = (int)$ext['quantidade'];
			$conciliados = (int)$ext['conciliados'];
			$pendentes = (int)$ext['pendentes'];
			$ultimo = $ext['ultimo'];

			if ($temConta) {
				$kpi['com_conta']++;
			}
			if ($quantidade > 0) {
				$kpi['com_extrato']++;
			}
			$kpi['pendentes'] += $pendentes;
			$kpi['conciliados'] += $conciliados;
			$kpi['eventos'] += $quantidade;

			if ($ultimo instanceof \DateTimeInterface) {
				if ($kpi['ultimo_movimento'] === null || $ultimo > $kpi['ultimo_movimento']) {
					$kpi['ultimo_movimento'] = $ultimo;
				}
			}

			if (!$temConta) {
				$statusLabel = __('Sem conta bancária');
				$statusKind = 'arq';
				$statusPeso = 4;
				$statusDesc = __('Cadastre agência e conta para permitir relacionamento com extratos importados.');
			} elseif ($quantidade <= 0) {
				$statusLabel = __('Aguardando extrato');
				$statusKind = 'pendente';
				$statusPeso = 3;
				$statusDesc = __('Conta configurada, mas ainda sem movimentos importados no extrato.');
			} elseif ($pendentes > 0) {
				$statusLabel = __('Com pendências');
				$statusKind = 'vencendo';
				$statusPeso = 1;
				$statusDesc = __('Há movimentos importados aguardando conciliação.');
			} else {
				$statusLabel = __('Conciliado');
				$statusKind = 'paga';
				$statusPeso = 2;
				$statusDesc = __('Todos os movimentos vinculados a esta conta estão conciliados.');
			}

			$linhas[] = [
				'id' => $id,
				'nome' => $nome,
				'brand' => $brand,
				'agencia' => $ag,
				'conta' => $cc,
				'tem_conta' => $temConta,
				'quantidade' => $quantidade,
				'conciliados' => $conciliados,
				'pendentes' => $pendentes,
				'ultimo_evento' => $ultimo,
				'status_label' => $statusLabel,
				'status_kind' => $statusKind,
				'status_peso' => $statusPeso,
				'status_desc' => $statusDesc,
				'label_select' => $brand['sigla'] . ' · ' . $nome . ' · CNAB 240',
			];
		}

		usort($linhas, static function ($a, $b) {
			if ($a['status_peso'] !== $b['status_peso']) {
				return $a['status_peso'] <=> $b['status_peso'];
			}
			if ($a['pendentes'] !== $b['pendentes']) {
				return $b['pendentes'] <=> $a['pendentes'];
			}

			return strcmp((string)$a['nome'], (string)$b['nome']);
		});

		$bancosSelect = array_map(static function ($l) {
			return ['id' => $l['id'], 'label' => $l['label_select']];
		}, array_filter($linhas, static function ($l) {
			return !empty($l['tem_conta']);
		}));

		return [
			'rtKpi' => $kpi,
			'rtLinhas' => $linhas,
			'rtBancosSelect' => $bancosSelect,
		];
	}

	/**
	 * @param array<int,object> $bancos
	 * @return array<int,array{quantidade:int,conciliados:int,pendentes:int,ultimo:?\\DateTimeInterface}>
	 */
	private function _resumoExtratoPorBanco(int $empresaId, array $bancos): array {
		$out = [];
		foreach ($bancos as $b) {
			$out[(int)$b->get('id')] = ['quantidade' => 0, 'conciliados' => 0, 'pendentes' => 0, 'ultimo' => null];
		}
		if ($bancos === []) {
			return $out;
		}
		try {
			$schema = TableRegistry::getTableLocator()->get('FinanceiroBancos')->getConnection()->getSchemaCollection()->listTables();
			if (!in_array('financeiro_extrato_bancario', $schema, true)) {
				return $out;
			}
			$ext = TableRegistry::getTableLocator()->get('FinanceiroExtratoBancario');
			$rows = $ext->find()
				->where(['FinanceiroExtratoBancario.idempresa' => $empresaId])
				->order(['FinanceiroExtratoBancario.data' => 'DESC'])
				->limit(5000)
				->all();
			foreach ($bancos as $banco) {
				$id = (int)$banco->get('id');
				$refs = $this->_contasReferencia($banco);
				if ($refs === []) {
					continue;
				}
				foreach ($rows as $r) {
					$conta = (string)$r->get('conta_bancaria');
					if (!in_array($conta, $refs, true)) {
						continue;
					}
					$out[$id]['quantidade']++;
					$dt = $r->get('data');
					if ($dt instanceof \DateTimeInterface && ($out[$id]['ultimo'] === null || $dt > $out[$id]['ultimo'])) {
						$out[$id]['ultimo'] = $dt;
					}
					if ((int)$r->get('conciliado') === 1 || (int)$r->get('financeiro_lancamento_id') > 0) {
						$out[$id]['conciliados']++;
					} else {
						$out[$id]['pendentes']++;
					}
				}
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @param object $banco
	 * @return array<int,string>
	 */
	private function _contasReferencia($banco): array {
		[$ag, $cc] = FinanceiroBancosPrototypeUi::formatAgenciaConta($banco);
		$refs = [];
		if ($ag !== '—' && $cc !== '—') {
			$refs[] = $ag . ' / ' . $cc;
			$refs[] = str_replace(' / ', '/', $ag . ' / ' . $cc);
		}
		$nome = trim((string)$banco->get('nome'));
		if ($nome !== '') {
			$refs[] = $nome;
		}

		return array_values(array_unique(array_filter($refs)));
	}
}
