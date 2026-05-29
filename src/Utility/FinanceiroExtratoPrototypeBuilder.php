<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Http\ServerRequest;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

/**
 * Monta payload da tela pg-extrato (BancosPrototype) com dados reais.
 */
class FinanceiroExtratoPrototypeBuilder {

	/** @var object */
	protected $extratoTable;

	/** @var object */
	protected $bancosTable;

	/** @var object */
	protected $lancamentosTable;

	/** @var bool */
	protected $disponivel;

	public function __construct(bool $disponivel = true) {
		$this->disponivel = $disponivel;
		if ($disponivel) {
			$this->extratoTable = TableRegistry::getTableLocator()->get('FinanceiroExtratoBancario');
			$this->bancosTable = TableRegistry::getTableLocator()->get('FinanceiroBancos');
			$this->lancamentosTable = TableRegistry::getTableLocator()->get('FinanceiroLancamentos');
		}
	}

	/**
	 * @return array<string,mixed>
	 */
	public function build(int $empresa, ServerRequest $request): array {
		$query = $request->getQueryParams();
		$periodo = $this->parsePeriodo($query);
		$de = $periodo['de'];
		$ate = $periodo['ate'];

		$aba = (string)($query['aba'] ?? '');
		if ($aba === '' && !empty($query['tipo']) && in_array($query['tipo'], ['c', 'd'], true)) {
			$aba = $query['tipo'] === 'c' ? 'in' : 'out';
		}
		if (!in_array($aba, ['todos', 'in', 'out', 'pendente'], true)) {
			$aba = 'todos';
		}

		$bancoId = (int)($query['banco'] ?? 0);
		$contaLegacy = trim((string)($query['conta'] ?? ''));
		$categoria = trim((string)($query['categoria'] ?? ''));
		$busca = trim((string)($query['q'] ?? ''));
		$pagina = max(1, (int)($query['pagina'] ?? 1));
		$porPagina = 12;

		$contasTabs = $this->carregarContasTabs($empresa);
		$refsConta = $this->refsFiltroConta($empresa, $bancoId, $contaLegacy);
		$mapaContaBanco = $this->mapaContaParaBanco($empresa);

		$kpi = [
			'saldo_inicial' => 0.0,
			'saldo_inicial_em' => $de,
			'entradas' => 0.0,
			'saidas' => 0.0,
			'mov_entradas' => 0,
			'mov_saidas' => 0,
			'saldo_atual' => 0.0,
			'delta_periodo' => 0.0,
		];
		$items = [];
		$totalFiltrado = 0;

		if (!$this->disponivel) {
			return $this->emptyPayload($query, $periodo, $aba, $bancoId, $categoria, $busca, $pagina, $contasTabs, $porPagina);
		}

		try {
			$kpi['saldo_inicial'] = $this->saldoAcumulado($empresa, $de, $refsConta);

			$where = [
				'FinanceiroExtratoBancario.idempresa' => $empresa,
				'FinanceiroExtratoBancario.data >=' => $de,
				'FinanceiroExtratoBancario.data <=' => $ate,
			];
			if ($refsConta !== []) {
				$where['FinanceiroExtratoBancario.conta_bancaria IN'] = $refsConta;
			}

			$rows = $this->extratoTable->find()
				->where($where)
				->order(['FinanceiroExtratoBancario.data' => 'DESC', 'FinanceiroExtratoBancario.id' => 'DESC'])
				->limit(5000)
				->all();

			foreach ($rows as $r) {
				$valorAbs = abs((float)$r->get('valor'));
				$tipo = strtolower((string)$r->get('tipo'));
				if (in_array($tipo, ['c', 'credito', 'cr'], true)) {
					$kpi['entradas'] += $valorAbs;
					$kpi['mov_entradas']++;
				} else {
					$kpi['saidas'] += $valorAbs;
					$kpi['mov_saidas']++;
				}
			}
			$kpi['delta_periodo'] = $kpi['entradas'] - $kpi['saidas'];
			$kpi['saldo_atual'] = $kpi['saldo_inicial'] + $kpi['delta_periodo'];

			$preparados = [];
			foreach ($rows as $r) {
				$valorAbs = abs((float)$r->get('valor'));
				$tipo = strtolower((string)$r->get('tipo'));
				$isEntrada = in_array($tipo, ['c', 'credito', 'cr'], true);
				$descExt = (string)$r->get('descricao');
				$data = $r->get('data');
				$statusInfo = $this->resolverStatus($r, $empresa);

				if ($categoria !== '' && !$this->categoriaMatch($descExt, $categoria)) {
					continue;
				}
				if ($busca !== '' && stripos($descExt, $busca) === false && stripos((string)$r->get('conta_bancaria'), $busca) === false) {
					continue;
				}
				if ($aba === 'in' && !$isEntrada) {
					continue;
				}
				if ($aba === 'out' && $isEntrada) {
					continue;
				}
				if ($aba === 'pendente' && !in_array($statusInfo['status'], ['pendente', 'divergente'], true)) {
					continue;
				}

				$contaRef = (string)$r->get('conta_bancaria');
				$bancoInfo = $mapaContaBanco[$contaRef] ?? null;
				$icon = $this->classificarIcone($descExt, $isEntrada);
				$hora = $r->get('created');
				$horaFmt = $hora instanceof \DateTimeInterface ? $hora->format('H:i') : '';

				$preparados[] = [
					'id' => (int)$r->get('id'),
					'data' => $data,
					'data_dia' => $data instanceof \DateTimeInterface ? $data->format('d/m') : '',
					'data_hora' => $horaFmt,
					'titulo' => $descExt !== '' ? $descExt : __('Movimentação bancária'),
					'historico' => $this->historico($descExt, $contaRef, $bancoInfo, $r, $statusInfo),
					'icon' => $icon,
					'icon_char' => $icon === 'in' ? '↓' : ($icon === 'out' ? '↑' : ($icon === 'tar' ? '$' : '⇄')),
					'is_entrada' => $isEntrada,
					'is_transferencia' => $icon === 'tra',
					'valor' => $valorAbs,
					'valor_label' => $this->formatarValor($valorAbs, $isEntrada, $icon === 'tra'),
					'status' => $statusInfo['status'],
					'status_label' => $statusInfo['label'],
					'status_badge' => $statusInfo['badge'],
				];
			}

			$totalFiltrado = count($preparados);
			$offset = ($pagina - 1) * $porPagina;
			$items = array_slice($preparados, $offset, $porPagina);
		} catch (\Throwable $e) {
		}

		$totalPaginas = max(1, (int)ceil($totalFiltrado / $porPagina));
		if ($pagina > $totalPaginas) {
			$pagina = $totalPaginas;
		}

		return [
			'extKpi' => $kpi,
			'extItems' => $items,
			'extContasTabs' => $contasTabs,
			'extFiltros' => $this->filtrosView($query, $periodo, $aba, $bancoId, $categoria, $busca, $pagina),
			'extPaginacao' => [
				'pagina' => $pagina,
				'total_paginas' => $totalPaginas,
				'por_pagina' => $porPagina,
				'total' => $totalFiltrado,
				'mostrando' => count($items),
			],
			'extCategorias' => $this->categoriasOptions(),
		];
	}

	/**
	 * @param array<string,mixed> $query
	 * @return array{de:Time,ate:Time,de_str:string,ate_str:string}
	 */
	public function parsePeriodo(array $query): array {
		$hoje = Time::now();
		$deDefault = $hoje->copy()->startOfMonth()->startOfDay();
		$ateDefault = $hoje->copy()->endOfDay();

		$deStr = trim((string)($query['de'] ?? ''));
		$ateStr = trim((string)($query['ate'] ?? ''));
		if ($deStr === '' && !empty($query['dias'])) {
			$dias = max(1, min(365, (int)$query['dias']));
			$de = $hoje->copy()->subDays($dias)->startOfDay();
		} else {
			$de = $this->parseData($deStr, $deDefault);
		}
		$ate = $this->parseData($ateStr, $ateDefault)->endOfDay();
		if ($de > $ate) {
			$tmp = $de;
			$de = $ate->copy()->startOfDay();
			$ate = $tmp->copy()->endOfDay();
		}

		return [
			'de' => $de,
			'ate' => $ate,
			'de_str' => $de->format('Y-m-d'),
			'ate_str' => $ate->format('Y-m-d'),
		];
	}

	/**
	 * @return array<int,string>
	 */
	public function refsFiltroConta(int $empresa, int $bancoId, string $contaLegacy): array {
		if ($contaLegacy !== '') {
			return [$contaLegacy];
		}
		if ($bancoId <= 0) {
			return [];
		}
		try {
			$b = $this->bancosTable->find()
				->where(['FinanceiroBancos.id' => $bancoId, 'FinanceiroBancos.idempresa' => $empresa])
				->first();
			if ($b === null) {
				return [];
			}

			return $this->contasReferencia($b);
		} catch (\Throwable $e) {
			return [];
		}
	}

	protected function parseData(string $raw, \DateTimeInterface $fallback): Time {
		if ($raw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
			return new Time($raw);
		}
		if ($raw !== '') {
			$ts = strtotime($raw);
			if ($ts !== false) {
				return new Time('@' . $ts);
			}
		}

		return new Time($fallback->format('Y-m-d H:i:s'));
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function carregarContasTabs(int $empresa): array {
		$tabs = [[
			'id' => 0,
			'label' => __('Todas as contas'),
			'bar' => '',
		]];
		try {
			$rows = $this->bancosTable->find()
				->where(['FinanceiroBancos.idempresa' => $empresa, 'FinanceiroBancos.ativo' => true])
				->order(['FinanceiroBancos.nome' => 'ASC'])
				->limit(30)
				->all();
			foreach ($rows as $b) {
				$codigo = (string)($b->get('codigo_banco') ?? $b->get('numero_banco') ?? '');
				$nome = (string)($b->get('nome') ?? '');
				$brand = FinanceiroBancosPrototypeUi::branding($codigo, $nome);
				[, $ccFmt] = FinanceiroBancosPrototypeUi::formatAgenciaConta($b);
				$label = $brand['sigla'];
				if ($ccFmt !== '—') {
					$label .= ' · ' . $ccFmt;
				} elseif ($nome !== '') {
					$label = $nome;
				}
				$tabs[] = [
					'id' => (int)$b->get('id'),
					'label' => $label,
					'bar' => $brand['bar'],
				];
			}
		} catch (\Throwable $e) {
		}

		return $tabs;
	}

	/**
	 * @return array<string,array<string,string>>
	 */
	protected function mapaContaParaBanco(int $empresa): array {
		$map = [];
		try {
			$rows = $this->bancosTable->find()
				->where(['FinanceiroBancos.idempresa' => $empresa])
				->limit(80)
				->all();
			foreach ($rows as $b) {
				$codigo = (string)($b->get('codigo_banco') ?? $b->get('numero_banco') ?? '');
				$nome = (string)($b->get('nome') ?? '');
				$brand = FinanceiroBancosPrototypeUi::branding($codigo, $nome);
				$info = ['sigla' => $brand['sigla'], 'nome' => $nome];
				foreach ($this->contasReferencia($b) as $ref) {
					$map[$ref] = $info;
				}
			}
		} catch (\Throwable $e) {
		}

		return $map;
	}

	/**
	 * @param object $banco
	 * @return array<int,string>
	 */
	protected function contasReferencia($banco): array {
		$refs = [];
		[$ag, $cc] = FinanceiroBancosPrototypeUi::formatAgenciaConta($banco);
		if ($ag !== '—' && $cc !== '—') {
			$refs[] = $ag . ' / ' . $cc;
			$refs[] = $ag . '/' . $cc;
		}
		$nome = trim((string)$banco->get('nome'));
		if ($nome !== '') {
			$refs[] = $nome;
		}

		return array_values(array_unique(array_filter($refs)));
	}

	/**
	 * @param array<int,string> $refsConta
	 */
	protected function saldoAcumulado(int $empresa, \DateTimeInterface $ateExclusive, array $refsConta): float {
		$where = [
			'FinanceiroExtratoBancario.idempresa' => $empresa,
			'FinanceiroExtratoBancario.data <' => $ateExclusive,
		];
		if ($refsConta !== []) {
			$where['FinanceiroExtratoBancario.conta_bancaria IN'] = $refsConta;
		}
		$saldo = 0.0;
		try {
			foreach ($this->extratoTable->find()->where($where)->limit(10000)->all() as $r) {
				$valor = abs((float)$r->get('valor'));
				$tipo = strtolower((string)$r->get('tipo'));
				$saldo += in_array($tipo, ['c', 'credito', 'cr'], true) ? $valor : -$valor;
			}
		} catch (\Throwable $e) {
		}

		return $saldo;
	}

	/**
	 * @param object $extrato
	 * @return array{status:string,label:string,badge:string}
	 */
	protected function resolverStatus($extrato, int $empresa): array {
		$conciliado = (int)$extrato->get('conciliado') === 1 || (int)$extrato->get('financeiro_lancamento_id') > 0;
		if ($conciliado) {
			return ['status' => 'conciliado', 'label' => '✓ ' . __('Conciliado'), 'badge' => 'conciliada'];
		}

		$data = $extrato->get('data');
		$valorAbs = abs((float)$extrato->get('valor'));
		$descExt = (string)$extrato->get('descricao');
		$bestScore = 0.0;
		if ($data instanceof \DateTimeInterface) {
			$ini = $data->copy()->subDays(5);
			$fim = $data->copy()->addDays(5);
			$tol = max(1.0, $valorAbs * 0.01);
			try {
				foreach ($this->lancamentosTable->find()
					->where([
						'FinanceiroLancamentos.idempresa' => $empresa,
						'FinanceiroLancamentos.data_lancamento >=' => $ini,
						'FinanceiroLancamentos.data_lancamento <=' => $fim,
					])
					->all() as $c) {
					$vc = abs((float)$c->get('valor'));
					if (abs($vc - $valorAbs) > $tol) {
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
					$bestScore = max($bestScore, $score);
				}
			} catch (\Throwable $e) {
			}
		}

		if ($bestScore >= 70) {
			return ['status' => 'divergente', 'label' => '⚠ ' . __('Divergência'), 'badge' => 'divergente'];
		}

		return ['status' => 'pendente', 'label' => '⏰ ' . __('Pendente'), 'badge' => 'pendente-conc'];
	}

	protected function classificarIcone(string $desc, bool $isEntrada): string {
		$u = strtoupper($desc);
		if (strpos($u, 'TRANSFER') !== false || strpos($u, 'INTERNA') !== false) {
			return 'tra';
		}
		if (strpos($u, 'TARIFA') !== false || strpos($u, 'JUROS') !== false || strpos($u, 'IOF') !== false) {
			return 'tar';
		}

		return $isEntrada ? 'in' : 'out';
	}

	/**
	 * @param array<string,string>|null $bancoInfo
	 * @param object $extrato
	 * @param array{status:string,label:string,badge:string} $statusInfo
	 */
	protected function historico(string $desc, string $contaRef, ?array $bancoInfo, $extrato, array $statusInfo): string {
		$partes = [];
		$sigla = $bancoInfo['sigla'] ?? '';
		if ($sigla !== '') {
			$partes[] = $sigla;
		} elseif ($contaRef !== '') {
			$partes[] = $contaRef;
		}
		$fitid = trim((string)$extrato->get('fitid'));
		if ($fitid !== '') {
			$partes[] = __('Doc {0}', $fitid);
		}
		if ($statusInfo['status'] === 'divergente') {
			$partes[] = __('associar manualmente');
		} elseif ($statusInfo['status'] === 'pendente') {
			$partes[] = __('Origem desconhecida no sistema');
		}
		if ($partes === []) {
			return $desc;
		}

		return implode(' · ', $partes);
	}

	protected function formatarValor(float $valor, bool $isEntrada, bool $isTransferencia): string {
		$fmt = number_format($valor, 2, ',', '.');
		if ($isTransferencia) {
			return '⇄ ' . $fmt;
		}

		return ($isEntrada ? '+ ' : '- ') . $fmt;
	}

	protected function categoriaMatch(string $desc, string $categoria): bool {
		$u = strtoupper($desc);
		$map = [
			'recebimentos' => ['PIX', 'BOLETO', 'TED RECEB', 'RECEBID', 'CREDITO'],
			'fornecedores' => ['FORNEC', 'COMPRA', ' NF ', 'PAGAMENTO'],
			'folha' => ['FOLHA', 'SALÁRIO', 'SALARIO', 'COLABORAD'],
			'tarifas' => ['TARIFA', 'TAXA', 'MANUTEN'],
			'impostos' => ['DARF', 'GPS', 'IMPOST', 'INSS', 'ISS', 'ICMS'],
			'transferencias' => ['TRANSFER', 'INTERNA', ' TED ', ' DOC '],
		];
		$key = strtolower($categoria);
		if (!isset($map[$key])) {
			return true;
		}
		foreach ($map[$key] as $needle) {
			if (strpos($u, $needle) !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array<string,string>
	 */
	protected function categoriasOptions(): array {
		return [
			'' => __('Todas categorias'),
			'recebimentos' => __('Recebimentos'),
			'fornecedores' => __('Fornecedores'),
			'folha' => __('Folha de pagamento'),
			'tarifas' => __('Tarifas'),
			'impostos' => __('Impostos'),
			'transferencias' => __('Transferências'),
		];
	}

	/**
	 * @param array<string,mixed> $query
	 * @param array{de:Time,ate:Time,de_str:string,ate_str:string} $periodo
	 * @return array<string,mixed>
	 */
	protected function filtrosView(array $query, array $periodo, string $aba, int $bancoId, string $categoria, string $busca, int $pagina): array {
		return [
			'aba' => $aba,
			'banco' => $bancoId,
			'categoria' => $categoria,
			'q' => $busca,
			'pagina' => $pagina,
			'de' => $periodo['de_str'],
			'ate' => $periodo['ate_str'],
		];
	}

	/**
	 * @param array<string,mixed> $query
	 * @param array{de:Time,ate:Time,de_str:string,ate_str:string} $periodo
	 * @return array<string,mixed>
	 */
	protected function emptyPayload(array $query, array $periodo, string $aba, int $bancoId, string $categoria, string $busca, int $pagina, array $contasTabs, int $porPagina): array {
		return [
			'extKpi' => [
				'saldo_inicial' => 0.0,
				'saldo_inicial_em' => $periodo['de'],
				'entradas' => 0.0,
				'saidas' => 0.0,
				'mov_entradas' => 0,
				'mov_saidas' => 0,
				'saldo_atual' => 0.0,
				'delta_periodo' => 0.0,
			],
			'extItems' => [],
			'extContasTabs' => $contasTabs,
			'extFiltros' => $this->filtrosView($query, $periodo, $aba, $bancoId, $categoria, $busca, $pagina),
			'extPaginacao' => ['pagina' => 1, 'total_paginas' => 1, 'por_pagina' => $porPagina, 'total' => 0, 'mostrando' => 0],
			'extCategorias' => $this->categoriasOptions(),
		];
	}
}
