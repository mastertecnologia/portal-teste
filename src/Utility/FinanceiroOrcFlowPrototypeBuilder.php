<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

/**
 * Payload pg-orc-faturamento / pg-orc-cobranca — fluxo por faturamento ou orçamento.
 */
class FinanceiroOrcFlowPrototypeBuilder {

	/**
	 * @return array<string,mixed>|null
	 */
	public function buildFaturamento(int $empresaId, int $idfaturamento = 0, int $idorcamento = 0): ?array {
		$fat = $this->_loadFaturamento($empresaId, $idfaturamento, $idorcamento);
		if ($fat === null) {
			return null;
		}
		$cliente = FinanceiroPrototypeSupport::clienteInfo($fat->cliente ?? null);
		$itens = [];
		$totalNfe = 0.0;
		$totalNfse = 0.0;
		try {
			foreach ($fat->faturamento_itens ?? [] as $item) {
				$tipo = strtolower((string)($item->get('tipo') ?? 'produto'));
				$isServ = strpos($tipo, 'serv') !== false;
				$v = (float)$item->get('valor_total');
				if ($isServ) {
					$totalNfse += $v;
				} else {
					$totalNfe += $v;
				}
				$itens[] = [
					'nf_tipo' => $isServ ? 'NFS-e' : 'NF-e',
					'nf_badge' => $isServ ? 'paga' : 'aprov',
					'codigo' => (string)($item->get('codigo') ?? ''),
					'descricao' => (string)($item->get('descricao') ?? ''),
					'tributacao' => (string)($item->get('observacoes') ?? '—'),
					'qtd' => (float)($item->get('quantidade') ?? 1),
					'unit' => (float)($item->get('valor_unitario') ?? 0),
					'total' => $v,
				];
			}
		} catch (\Throwable $e) {
		}
		$total = (float)($fat->get('valor_total') ?? ($totalNfe + $totalNfse));
		$idorc = (int)($fat->get('idorcamento') ?? 0);
		$bancos = $this->_loadBancosOptions($empresaId);

		return [
			'orcId' => $idorc,
			'fatId' => (int)$fat->get('id'),
			'fatNumero' => (string)($fat->get('numero') ?? ('FT-' . (int)$fat->get('id'))),
			'cliente' => $cliente,
			'valor_total' => $total,
			'itens' => $itens,
			'total_nfe' => $totalNfe,
			'total_nfse' => $totalNfse,
			'data_emissao' => $fat->get('data_emissao'),
			'bancos' => $bancos,
			'emitir_url' => ['controller' => 'Faturamento', 'action' => 'view', (int)$fat->get('id')],
			'steps' => $this->_orcSteps(7),
		];
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function buildCobranca(int $empresaId, int $idfaturamento = 0, int $idorcamento = 0): ?array {
		$fat = $this->_loadFaturamento($empresaId, $idfaturamento, $idorcamento);
		if ($fat === null) {
			return null;
		}
		$cliente = FinanceiroPrototypeSupport::clienteInfo($fat->cliente ?? null);
		$fatId = (int)$fat->get('id');
		$idorc = (int)($fat->get('idorcamento') ?? 0);

		$lancamentos = [];
		try {
			$lancamentos = TableRegistry::getTableLocator()->get('FinanceiroLancamentos')
				->find()
				->where([
					'FinanceiroLancamentos.idempresa' => $empresaId,
					'FinanceiroLancamentos.idfaturamento' => $fatId,
					'FinanceiroLancamentos.tipo' => 'receita',
				])
				->contain(['FinanceiroBancos'])
				->order(['FinanceiroLancamentos.data_vencimento' => 'ASC'])
				->all()
				->toArray();
		} catch (\Throwable $e) {
		}

		$now = Time::now();
		$parcelas = FinanceiroPrototypeSupport::parcelaMap($lancamentos);
		$titulos = [];
		$kpi = ['faturado' => 0.0, 'recebido' => 0.0, 'a_receber' => 0.0, 'atraso' => 0.0, 'qtd' => count($lancamentos)];
		foreach ($lancamentos as $l) {
			$v = (float)$l->get('valor');
			$kpi['faturado'] += $v;
			$id = (int)$l->get('id');
			$cls = FinanceiroPrototypeSupport::classifyReceita($l, $now);
			if ($cls['state'] === 'pago') {
				$kpi['recebido'] += $v;
			} else {
				$kpi['a_receber'] += $v;
				if ($cls['state'] === 'atraso') {
					$kpi['atraso'] += $v;
				}
			}
			$titulos[] = [
				'id' => $id,
				'codigo' => FinanceiroPrototypeSupport::tituloCodigo($id, $l->get('data_lancamento')),
				'parcela' => $parcelas[$id] ?? '1/1',
				'vencimento' => $l->get('data_vencimento'),
				'valor' => $v,
				'status' => $cls,
				'nosso_numero' => (string)($l->get('nosso_numero') ?? ''),
				'data_baixa' => $l->get('data_baixa') ?? $l->get('data_recebimento'),
				'banco_label' => FinanceiroPrototypeSupport::bancoLabel($l->financeiro_banco ?? null),
				'fatura_url' => ['controller' => 'Financeiro', 'action' => 'fatura', $id],
			];
		}

		$notas = [];
		try {
			$notas = TableRegistry::getTableLocator()->get('FiscalNotas')
				->find()
				->where(['FiscalNotas.idempresa' => $empresaId, 'FiscalNotas.idcliente' => (int)$fat->get('idcliente')])
				->order(['FiscalNotas.data_emissao' => 'DESC'])
				->limit(5)
				->all()
				->toArray();
		} catch (\Throwable $e) {
		}

		$bancoLabel = '—';
		if ($titulos !== [] && ($lancamentos[0]->financeiro_banco ?? null) !== null) {
			$bancoLabel = FinanceiroPrototypeSupport::bancoLabel($lancamentos[0]->financeiro_banco);
		}

		return [
			'orcId' => $idorc,
			'fatId' => $fatId,
			'fatNumero' => (string)($fat->get('numero') ?? ('FT-' . $fatId)),
			'cliente' => $cliente,
			'kpi' => $kpi,
			'titulos' => $titulos,
			'banco_label' => $bancoLabel,
			'notas' => $notas,
			'steps' => $this->_orcSteps(8),
			'remessa_url' => ['controller' => 'BancosPrototype', 'action' => 'view', 'remessa'],
			'retorno_url' => ['controller' => 'BancosPrototype', 'action' => 'view', 'retorno'],
		];
	}

	/**
	 * @return object|null
	 */
	private function _loadFaturamento(int $empresaId, int $idfaturamento, int $idorcamento) {
		try {
			$tbl = TableRegistry::getTableLocator()->get('Faturamento');
			$q = $tbl->find()
				->where(['Faturamento.idempresa' => $empresaId])
				->contain(['Clientes', 'FaturamentoItens']);
			if ($idfaturamento > 0) {
				$q->where(['Faturamento.id' => $idfaturamento]);
			} elseif ($idorcamento > 0) {
				$q->where(['Faturamento.idorcamento' => $idorcamento])->order(['Faturamento.id' => 'DESC']);
			} else {
				return null;
			}

			return $q->first();
		} catch (\Throwable $e) {
			return null;
		}
	}

	/**
	 * @return array<int,array{label:string,state:string}>
	 */
	private function _orcSteps(int $activeStep): array {
		$labels = ['Dados', 'Itens', 'Aprovação', 'Revisão', 'Impressão', 'Assinatura', 'Faturamento', 'Cobrança'];
		$steps = [];
		foreach ($labels as $i => $label) {
			$n = $i + 1;
			$state = 'pending';
			if ($n < $activeStep) {
				$state = 'done';
			} elseif ($n === $activeStep) {
				$state = 'active';
			}
			$steps[] = ['label' => $label, 'state' => $state];
		}

		return $steps;
	}

	/**
	 * @return array<int,array{id:int,label:string}>
	 */
	private function _loadBancosOptions(int $empresaId): array {
		$out = [];
		try {
			$rows = TableRegistry::getTableLocator()->get('FinanceiroBancos')
				->find()
				->where(['FinanceiroBancos.idempresa' => $empresaId, 'FinanceiroBancos.ativo' => true])
				->limit(20)
				->all();
			foreach ($rows as $b) {
				[$ag, $cc] = FinanceiroBancosPrototypeUi::formatAgenciaConta($b);
				$out[] = [
					'id' => (int)$b->get('id'),
					'label' => FinanceiroBancosPrototypeUi::branding(
						(string)($b->get('codigo_banco') ?? ''),
						(string)$b->get('nome')
					)['sigla'] . ' · Ag.' . $ag . ' · CC.' . $cc,
				];
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}
}
