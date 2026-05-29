<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

/**
 * Monta payload da tela pg-conciliacao (BancosPrototype) com dados reais.
 */
class FinanceiroConciliacaoPrototypeBuilder {

	/** @var bool */
	protected $disponivel;

	/** @var object|null */
	protected $extratoTable;

	/** @var object|null */
	protected $lancamentosTable;

	/** @var object|null */
	protected $bancosTable;

	public function __construct(bool $disponivel = true) {
		$this->disponivel = $disponivel;
		if ($disponivel) {
			$this->extratoTable = TableRegistry::getTableLocator()->get('FinanceiroExtratoBancario');
			$this->lancamentosTable = TableRegistry::getTableLocator()->get('FinanceiroLancamentos');
			$this->bancosTable = TableRegistry::getTableLocator()->get('FinanceiroBancos');
		}
	}

	/**
	 * @return array<string,mixed>
	 */
	public function build(int $empresa): array {
		$kpi = [
			'conciliados' => 0,
			'pendentes' => 0,
			'divergentes' => 0,
			'valor_conciliados' => 0.0,
			'valor_pendentes' => 0.0,
			'valor_divergentes' => 0.0,
			'saldo_extrato' => 0.0,
			'pct_conciliados' => 0.0,
			'total_extrato' => 0,
			'total_lancamentos' => 0,
		];
		$items = [];
		$meta = [
			'conta_label' => __('Todas as contas'),
			'periodo_label' => Time::now()->i18nFormat('MMMM/yyyy'),
			'coluna_extrato' => __('Extrato bancário'),
		];

		if (!$this->disponivel) {
			return ['concKpi' => $kpi, 'concItems' => $items, 'concMeta' => $meta];
		}

		try {
			$mapaConta = $this->mapaContaParaBanco($empresa);
			$meta = $this->montarMeta($empresa, $mapaConta);

			$rows = $this->extratoTable->find()
				->where(['FinanceiroExtratoBancario.idempresa' => $empresa])
				->order(['FinanceiroExtratoBancario.data' => 'DESC', 'FinanceiroExtratoBancario.id' => 'DESC'])
				->limit(80)
				->all();

			$kpi['total_extrato'] = (int)$this->extratoTable->find()
				->where(['FinanceiroExtratoBancario.idempresa' => $empresa])
				->count();
			$kpi['total_lancamentos'] = (int)$this->lancamentosTable->find()
				->where(['FinanceiroLancamentos.idempresa' => $empresa])
				->count();

			$lancCache = [];

			foreach ($rows as $e) {
				if (strpos((string)$e->get('descricao'), '[IGNORED]') !== false) {
					continue;
				}
				$valor = (float)$e->get('valor');
				$valorAbs = abs($valor);
				$isEntrada = $this->isEntrada((string)$e->get('tipo'));
				$sinal = $isEntrada ? 1 : -1;
				$kpi['saldo_extrato'] += $valorAbs * $sinal;

				$data = $e->get('data');
				$descExt = (string)$e->get('descricao');
				$contaRef = (string)$e->get('conta_bancaria');
				$lid = (int)$e->get('financeiro_lancamento_id');
				$conciliado = (int)$e->get('conciliado') === 1 || $lid > 0;

				$matchSuggest = null;
				if (!$conciliado) {
					$matchSuggest = $this->sugerirMatch($e, $empresa);
				}

				$lanRow = null;
				if ($lid > 0) {
					if (!isset($lancCache[$lid])) {
						$lancCache[$lid] = $this->lancamentosTable->find()
							->where(['FinanceiroLancamentos.id' => $lid, 'FinanceiroLancamentos.idempresa' => $empresa])
							->first();
					}
					$lanRow = $lancCache[$lid];
				} elseif ($conciliado && $matchSuggest !== null) {
					$lanRow = $this->lancamentosTable->find()
						->where(['FinanceiroLancamentos.id' => (int)$matchSuggest['id'], 'FinanceiroLancamentos.idempresa' => $empresa])
						->first();
				}

				if ($conciliado && $lanRow !== null) {
					$status = 'matched';
					$kpi['conciliados']++;
					$kpi['valor_conciliados'] += $valorAbs;
				} elseif ($matchSuggest !== null && (int)$matchSuggest['score'] >= 70) {
					$status = 'pendente';
					$kpi['pendentes']++;
					$kpi['valor_pendentes'] += $valorAbs;
				} else {
					$status = 'divergente';
					$kpi['divergentes']++;
					$kpi['valor_divergentes'] += $valorAbs;
				}

				$items[] = $this->montarItem(
					$e,
					$status,
					$isEntrada,
					$valorAbs,
					$data,
					$descExt,
					$contaRef,
					$mapaConta,
					$lanRow,
					$matchSuggest
				);
			}

			$totalItens = $kpi['conciliados'] + $kpi['pendentes'] + $kpi['divergentes'];
			$kpi['pct_conciliados'] = $totalItens > 0
				? round(100 * $kpi['conciliados'] / $totalItens, 0)
				: 0.0;
		} catch (\Throwable $ex) {
		}

		return [
			'concKpi' => $kpi,
			'concItems' => $items,
			'concMeta' => $meta,
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function montarItem(
		$extrato,
		string $status,
		bool $isEntrada,
		float $valorAbs,
		$data,
		string $descExt,
		string $contaRef,
		array $mapaConta,
		$lanRow,
		?array $matchSuggest
	): array {
		$border = $status === 'matched' ? 'var(--teal)' : ($status === 'pendente' ? 'var(--amber)' : 'var(--red)');
		$rowBg = $status === 'matched' ? '' : ($status === 'pendente' ? '#FFFBF0' : '#FFF5F5');
		$arrow = $status === 'matched' ? '→' : ($status === 'pendente' ? '?' : '⚠');
		$arrowColor = $status === 'matched' ? 'var(--teal)' : ($status === 'pendente' ? 'var(--amber)' : 'var(--red)');

		$extratoSide = $this->formatarLadoExtrato($extrato, $data, $descExt, $contaRef, $mapaConta, $isEntrada, $valorAbs, $border);

		$item = [
			'id' => (int)$extrato->get('id'),
			'status' => $status,
			'row_class' => $status === 'matched' ? 'matched' : ($status === 'divergente' ? 'divergent' : ''),
			'row_bg' => $rowBg,
			'arrow' => $arrow,
			'arrow_color' => $arrowColor,
			'extrato' => $extratoSide,
			'match' => $matchSuggest,
		];

		if ($status === 'matched' && $lanRow !== null) {
			$item['lancamento'] = $this->formatarLadoLancamento($lanRow, $border);
			$item['panel'] = null;
		} elseif ($status === 'matched') {
			$lid = (int)$extrato->get('financeiro_lancamento_id');
			$item['lancamento'] = [
				'meta' => '',
				'titulo' => __('Lançamento #{0}', $lid),
				'subtitulo' => __('Vinculado ao extrato'),
				'valor_label' => ($isEntrada ? '+ ' : '- ') . $this->brl($valorAbs),
				'is_entrada' => $isEntrada,
				'border' => $border,
			];
			$item['panel'] = null;
		} elseif ($status === 'pendente' && $matchSuggest !== null) {
			$item['lancamento'] = null;
			$item['panel'] = [
				'type' => 'aguardando',
				'bg' => '#FAEEDA',
				'border' => 'var(--amber)',
				'titulo' => '⏰ ' . __('Aguardando match'),
				'texto' => __('Sugestão: {0} (score {1}%)', $this->resumoMatch($matchSuggest), (int)$matchSuggest['score']),
				'lancamento_id' => (int)$matchSuggest['id'],
			];
		} else {
			$item['lancamento'] = null;
			$item['panel'] = [
				'type' => 'divergente',
				'bg' => '#F8D8DA',
				'border' => 'var(--red)',
				'titulo' => '⚠ ' . __('Cliente não cadastrado'),
				'texto' => __('Não há título correspondente no sistema. Você pode:'),
			];
		}

		return $item;
	}

	/**
	 * @param array<string,array<string,string>> $mapaConta
	 * @return array<string,mixed>
	 */
	protected function formatarLadoExtrato($extrato, $data, string $desc, string $contaRef, array $mapaConta, bool $isEntrada, float $valorAbs, string $border): array {
		$hora = $extrato->get('created');
		$horaFmt = $hora instanceof \DateTimeInterface ? $hora->format('H:i') : '00:00';
		$dataFmt = $data instanceof \DateTimeInterface ? $data->format('d/m/Y') : '';
		$tipoMov = $this->rotuloTipoExtrato($desc, $isEntrada, $contaRef, $mapaConta);
		$fitid = trim((string)$extrato->get('fitid'));

		$sub = [];
		$sigla = $mapaConta[$contaRef]['sigla'] ?? '';
		if ($sigla !== '' && stripos($desc, $sigla) === false) {
			$sub[] = $sigla;
		}
		if ($fitid !== '') {
			$sub[] = __('Doc {0}', $fitid);
		}
		if ($statusHint = $this->extrairCnpjOuRef($desc)) {
			$sub[] = $statusHint;
		}

		return [
			'meta' => trim($dataFmt . ' ' . $horaFmt . ' · ' . $tipoMov),
			'titulo' => $this->tituloExtrato($desc),
			'subtitulo' => $sub !== [] ? implode(' · ', $sub) : $contaRef,
			'valor_label' => ($isEntrada ? '+ ' : '- ') . $this->brl($valorAbs),
			'is_entrada' => $isEntrada,
			'border' => $border,
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function formatarLadoLancamento($lan, string $border): array {
		$valor = abs((float)$lan->get('valor'));
		$tipo = strtolower((string)$lan->get('tipo'));
		$isReceita = strpos($tipo, 'rec') !== false || $tipo === 'r';
		$data = $lan->get('data_lancamento');
		$dataFmt = $data instanceof \DateTimeInterface ? $data->format('d/m/Y') : '';
		$desc = (string)$lan->get('descricao');
		$status = (string)$lan->get('status');
		$id = (int)$lan->get('id');

		$metaTipo = $isReceita ? __('Recebimento') : __('Contas a pagar');
		if (stripos($desc, 'obrig') !== false || stripos($desc, 'darf') !== false || stripos($desc, 'inss') !== false) {
			$metaTipo = __('Obrigação fiscal');
		} elseif (stripos($desc, 'folha') !== false) {
			$metaTipo = __('Folha de pagamento');
		}

		$sub = [];
		if ($status !== '') {
			$sub[] = __('Status: {0}', $status);
		}
		$obs = trim((string)$lan->get('observacoes'));
		if ($obs !== '') {
			$sub[] = \Cake\Utility\Text::truncate($obs, 60, ['ellipsis' => '…']);
		}
		if ((int)$lan->get('idfaturamento') > 0) {
			$sub[] = __('Faturamento #{0}', (int)$lan->get('idfaturamento'));
		}

		return [
			'meta' => trim($dataFmt . ' · ' . $metaTipo),
			'titulo' => $desc !== '' ? $desc : ('#' . $id),
			'subtitulo' => $sub !== [] ? implode(' · ', $sub) : ('#' . $id),
			'valor_label' => ($isReceita ? '+ ' : '- ') . $this->brl($valor),
			'is_entrada' => $isReceita,
			'border' => $border,
		];
	}

	/**
	 * @param object $extrato
	 * @return array<string,mixed>|null
	 */
	protected function sugerirMatch($extrato, int $empresa): ?array {
		$data = $extrato->get('data');
		if (!$data instanceof \DateTimeInterface) {
			return null;
		}
		$valorAbs = abs((float)$extrato->get('valor'));
		$descExt = (string)$extrato->get('descricao');
		$ini = $data->copy()->subDays(5);
		$fim = $data->copy()->addDays(5);
		$tol = max(1.0, $valorAbs * 0.01);
		$rejeitados = [];
		if (preg_match_all('/\[NO-MATCH:lid=(\d+)\]/', $descExt, $mm)) {
			$rejeitados = array_map('intval', $mm[1]);
		}

		$bestScore = 0.0;
		$matchSuggest = null;
		try {
			$candidatos = $this->lancamentosTable->find()
				->where([
					'FinanceiroLancamentos.idempresa' => $empresa,
					'FinanceiroLancamentos.data_lancamento >=' => $ini,
					'FinanceiroLancamentos.data_lancamento <=' => $fim,
				])
				->all();
			foreach ($candidatos as $c) {
				$cid = (int)$c->get('id');
				if (in_array($cid, $rejeitados, true)) {
					continue;
				}
				$vc = abs((float)$c->get('valor'));
				$diff = abs($vc - $valorAbs);
				if ($diff > $tol) {
					continue;
				}
				$score = 60.0;
				$dt = $c->get('data_lancamento');
				if ($dt instanceof \DateTimeInterface) {
					$days = abs((int)$data->diffInDays($dt));
					$score += max(0, 30 - $days * 5);
				}
				$descLan = (string)$c->get('descricao');
				if ($descExt !== '' && $descLan !== '') {
					$pct = 0.0;
					similar_text(strtoupper($descExt), strtoupper($descLan), $pct);
					$score += min(10, $pct / 10);
				}
				if ($score > $bestScore) {
					$bestScore = $score;
					$matchSuggest = [
						'id' => $cid,
						'descricao' => $descLan,
						'data' => $dt,
						'valor' => (float)$c->get('valor'),
						'score' => (int)round($score),
						'diff_valor' => round($diff, 2),
					];
				}
			}
		} catch (\Throwable $e) {
		}

		return $matchSuggest;
	}

	/**
	 * @param array<string,array<string,string>> $mapaConta
	 * @return array<string,string>
	 */
	protected function montarMeta(int $empresa, array $mapaConta): array {
		$contaLabel = __('Todas as contas');
		$coluna = __('Extrato bancário');
		try {
			$b = $this->bancosTable->find()
				->where(['FinanceiroBancos.idempresa' => $empresa, 'FinanceiroBancos.ativo' => true])
				->order(['FinanceiroBancos.nome' => 'ASC'])
				->first();
			if ($b !== null) {
				$codigo = (string)($b->get('codigo_banco') ?? $b->get('numero_banco') ?? '');
				$nome = (string)($b->get('nome') ?? '');
				$brand = FinanceiroBancosPrototypeUi::branding($codigo, $nome);
				[, $ccFmt] = FinanceiroBancosPrototypeUi::formatAgenciaConta($b);
				$contaLabel = $brand['sigla'];
				if ($ccFmt !== '—') {
					$contaLabel .= ' · ' . $ccFmt;
				}
				$coluna = __('Extrato bancário ({0})', $contaLabel);
			}
		} catch (\Throwable $e) {
		}

		return [
			'conta_label' => $contaLabel,
			'periodo_label' => Time::now()->i18nFormat('MMMM/yyyy'),
			'coluna_extrato' => $coluna,
		];
	}

	/**
	 * @return array<string,array<string,string>>
	 */
	protected function mapaContaParaBanco(int $empresa): array {
		$map = [];
		try {
			foreach ($this->bancosTable->find()->where(['FinanceiroBancos.idempresa' => $empresa])->limit(80)->all() as $b) {
				$codigo = (string)($b->get('codigo_banco') ?? $b->get('numero_banco') ?? '');
				$nome = (string)($b->get('nome') ?? '');
				$brand = FinanceiroBancosPrototypeUi::branding($codigo, $nome);
				$info = ['sigla' => $brand['sigla'], 'nome' => $nome];
				[$ag, $cc] = FinanceiroBancosPrototypeUi::formatAgenciaConta($b);
				if ($ag !== '—' && $cc !== '—') {
					$map[$ag . ' / ' . $cc] = $info;
					$map[$ag . '/' . $cc] = $info;
				}
				if ($nome !== '') {
					$map[$nome] = $info;
				}
			}
		} catch (\Throwable $e) {
		}

		return $map;
	}

	protected function isEntrada(string $tipo): bool {
		$tipo = strtolower($tipo);

		return in_array($tipo, ['c', 'credito', 'cr', 'receita'], true);
	}

	/**
	 * @param array<string,array<string,string>> $mapaConta
	 */
	protected function rotuloTipoExtrato(string $desc, bool $isEntrada, string $contaRef, array $mapaConta): string {
		$u = strtoupper($desc);
		$prefix = $isEntrada ? __('Crédito') : __('Débito');
		if (strpos($u, 'PIX') !== false) {
			$sigla = $mapaConta[$contaRef]['sigla'] ?? '';
			$suf = $sigla !== '' ? ' (' . $sigla . ')' : '';

			return $prefix . ' PIX' . $suf;
		}
		if (strpos($u, 'TED') !== false) {
			return $prefix . ' TED';
		}
		if (strpos($u, 'BOLETO') !== false) {
			return $prefix . ' boleto';
		}
		if (strpos($u, 'GPS') !== false || strpos($u, 'DARF') !== false) {
			return $prefix . ' GPS';
		}
		if (strpos($u, 'CNAB') !== false || strpos($u, 'FOLHA') !== false) {
			return $prefix . ' CNAB';
		}
		if (strpos($u, 'TARIFA') !== false) {
			return $prefix . ' tarifa';
		}

		return $prefix;
	}

	protected function tituloExtrato(string $desc): string {
		$desc = trim($desc);
		if ($desc === '') {
			return __('Movimentação bancária');
		}
		if (strpos($desc, ' · ') !== false) {
			$parts = explode(' · ', $desc, 2);

			return trim($parts[1] ?? $parts[0]);
		}

		return $desc;
	}

	protected function extrairCnpjOuRef(string $desc): string {
		if (preg_match('/\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}/', $desc, $m)) {
			return 'CNPJ ' . $m[0];
		}
		if (stripos($desc, 'não cadastrado') !== false || stripos($desc, 'nao cadastrado') !== false) {
			return __('não cadastrado');
		}

		return '';
	}

	/**
	 * @param array<string,mixed> $match
	 */
	protected function resumoMatch(array $match): string {
		$desc = trim((string)($match['descricao'] ?? ''));
		if ($desc !== '') {
			return $desc;
		}

		return '#' . (int)($match['id'] ?? 0);
	}

	protected function brl(float $v): string {
		return 'R$ ' . number_format($v, 2, ',', '.');
	}
}
