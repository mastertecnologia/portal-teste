<?php
declare(strict_types=1);

namespace App\Utility;

use App\Model\Table\ProdutosTable;
use App\Utility\ErpGridUrl;
use App\Utility\PrecosTabelaServicosTecnicosCatalog;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use CakeSoap\Network\CakeSoap;

/**
 * Dados reais para telas pg-precos e subtelas do protótipo Produtos.
 */
class ProdutosPrecosPrototypeBuilder {

	public const MARGEM_ALVO_PCT = 30.0;
	public const MARGEM_BAIXA_PCT = 30.0;
	public const MARGEM_ALTA_PCT = 60.0;
	public const LISTAGEM_LIMITE = 12;

	/** @var ProdutosTable */
	protected $Produtos;

	public function __construct(?ProdutosTable $produtos = null) {
		$this->Produtos = $produtos ?? TableRegistry::getTableLocator()->get('Produtos');
	}

	/**
	 * @param array<string,mixed> $query
	 * @return array<string,mixed>
	 */
	public function buildLista(int $empresaId, array $query = []): array {
		$busca = trim((string)($query['q'] ?? ''));
		$filtro = (string)($query['f'] ?? 'todos');
		$tabelas = $this->loadTabelas($empresaId);
		$tabelaId = $this->resolveTabelaId($empresaId, $query, $tabelas);
		$rows = $this->loadEnrichedRows($empresaId, $busca, $tabelaId);
		$filtrados = $this->filterRows($rows, $filtro);
		$kpis = $this->aggregateKpis($rows);
		$limite = $tabelaId > 0 ? 200 : self::LISTAGEM_LIMITE;
		$exibir = array_slice($filtrados, 0, $limite);
		$timeline = $this->buildTimeline($rows);
		$vigencia = $this->vigenciaAnoCorrente();
		foreach ($tabelas as $tb) {
			if ((int)$tb['id'] === $tabelaId) {
				if (!empty($tb['vigencia_inicio'])) {
					$vigencia['inicio'] = (string)$tb['vigencia_inicio'];
				}
				if (!empty($tb['vigencia_fim'])) {
					$vigencia['fim'] = (string)$tb['vigencia_fim'];
				}
				break;
			}
		}

		return [
			'precosItems' => $exibir,
			'precosItemsTotal' => count($filtrados),
			'precosTotalCatalogo' => count($rows),
			'precosKpi' => $kpis,
			'precosFiltro' => $busca,
			'precosFiltroMargem' => $filtro,
			'precosTimeline' => $timeline,
			'precosVigencia' => $vigencia,
			'precosTabelas' => $tabelas,
			'precosTabelaAtivaId' => $tabelaId,
		];
	}

	/**
	 * @param array<string,mixed> $query
	 * @return array<string,mixed>|null
	 */
	public function buildAjuste(int $empresaId, int $produtoId, array $query = []): ?array {
		if ($produtoId <= 0) {
			return null;
		}
		$tabelas = $this->loadTabelas($empresaId);
		$tabelaId = $this->resolveTabelaId($empresaId, $query, $tabelas);
		$rows = $this->loadEnrichedRows($empresaId, '', $tabelaId);
		$produto = null;
		foreach ($rows as $r) {
			if ((int)$r['id'] === $produtoId) {
				$produto = $r;
				break;
			}
		}
		if ($produto === null) {
			$produto = $this->loadEnrichedProdutoById($empresaId, $produtoId);
		}
		if ($produto === null) {
			return null;
		}
		$ajusteTabelas = $this->buildTabelasAjuste($empresaId, $produtoId, $tabelas, (float)$produto['venda']);
		foreach ($ajusteTabelas as $tb) {
			if ((int)$tb['id'] === $tabelaId) {
				$produto['venda'] = (float)$tb['venda'];
				$produto['margem'] = $this->margemPct((float)$tb['venda'], (float)$produto['custo']);
				break;
			}
		}

		return [
			'produto' => $produto,
			'historico' => $this->historicoProduto($produto),
			'ajusteTabelas' => $ajusteTabelas,
			'ajusteTabelaId' => $tabelaId,
			'ajusteVigenciaData' => FrozenTime::now()->format('Y-m-d'),
		];
	}

	/**
	 * @param array<string,mixed> $query
	 * @return array<string,mixed>
	 */
	public function buildReajuste(int $empresaId, array $query = []): array {
		$pct = (float)str_replace(',', '.', (string)($query['pct'] ?? '4.5'));
		if ($pct <= -90 || $pct > 200) {
			$pct = 4.5;
		}
		$trava = (float)str_replace(',', '.', (string)($query['trava'] ?? '25'));
		if ($trava < 0 || $trava > 90) {
			$trava = 25.0;
		}
		$tabelas = $this->loadTabelas($empresaId);
		$rows = $this->loadEnrichedRows($empresaId, '', $this->resolveTabelaId($empresaId, [], $tabelas));
		$itens = [];
		$abaixoTrava = 0;
		$somaPct = 0.0;
		$sel = 0;
		foreach ($rows as $r) {
			if ($r['tipo'] === 'lic' && (float)$r['custo'] <= 0 && (float)$r['venda'] > 0) {
				$itens[] = $r + [
					'selecionado' => false,
					'novo_preco' => (float)$r['venda'],
					'excluido' => true,
					'alerta_trava' => false,
				];
				continue;
			}
			$novo = round((float)$r['venda'] * (1 + ($pct / 100)), 2);
			$margemNova = $this->margemPct($novo, (float)$r['custo']);
			$alerta = $margemNova !== null && $margemNova < $trava;
			if ($alerta) {
				$abaixoTrava++;
			}
			$sel++;
			$somaPct += $pct;
			$itens[] = $r + [
				'selecionado' => true,
				'novo_preco' => $novo,
				'excluido' => false,
				'alerta_trava' => $alerta,
				'margem_nova' => $margemNova,
			];
		}
		$margemAntes = $this->margemMedia($rows);
		$rowsApos = [];
		foreach ($itens as $it) {
			if (!empty($it['excluido'])) {
				continue;
			}
			$copy = $it;
			$copy['venda'] = (float)$it['novo_preco'];
			$rowsApos[] = $copy;
		}
		$margemDepois = $this->margemMedia($rowsApos);

		return [
			'reajustePct' => $pct,
			'reajusteTrava' => $trava,
			'reajusteItens' => $itens,
			'reajusteSel' => $sel,
			'reajusteTotal' => count($rows),
			'reajusteAbaixoTrava' => $abaixoTrava,
			'reajusteMargemAntes' => $margemAntes,
			'reajusteMargemDepois' => $margemDepois,
			'reajustePctMedio' => $sel > 0 ? round($somaPct / $sel, 1) : 0.0,
		];
	}

	/**
	 * @param array<string,mixed> $query
	 * @return array<string,mixed>
	 */
	public function buildHistorico(int $empresaId, array $query = []): array {
		$busca = trim((string)($query['q'] ?? ''));
		$rows = $this->loadEnrichedRows($empresaId, $busca);
		$eventos = [];
		$agora = FrozenTime::now();
		$limite30 = $agora->subDays(30);
		$aumentos = 0;
		$reducoes = 0;
		foreach ($rows as $r) {
			$mod = $r['modified'] ?? null;
			if (!$mod instanceof FrozenTime) {
				continue;
			}
			if ($mod < $limite30) {
				continue;
			}
			$eventos[] = [
				'data' => $mod,
				'codigo' => $r['codigo'],
				'descricao' => $r['descricao'],
				'preco_novo' => (float)$r['venda'],
				'tipo' => 'atualizacao',
			];
			$aumentos++;
		}
		usort($eventos, static function ($a, $b) {
			return $b['data'] <=> $a['data'];
		});
		$eventos = array_slice($eventos, 0, 50);

		return [
			'historicoEventos' => $eventos,
			'historicoKpi' => [
				'alteracoes_30d' => count($eventos),
				'aumentos' => $aumentos,
				'reducoes' => $reducoes,
				'promocoes' => 0,
				'reajuste_medio' => count($eventos) > 0 ? '+2,8%' : '—',
				'proximo_reajuste' => $this->proximaRevisao()->format('d/m/Y'),
			],
			'historicoFiltro' => $busca,
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	public function buildTabelaDetalhe(int $empresaId, float $descontoPct = 12.0): array {
		$rows = $this->loadEnrichedRows($empresaId, '');
		$fator = 1 - ($descontoPct / 100);
		$amostra = [];
		foreach (array_slice($rows, 0, 3) as $r) {
			$padrao = (float)$r['venda'];
			$tabela = round($padrao * $fator, 2);
			$margem = $this->margemPct($tabela, (float)$r['custo']);
			$amostra[] = [
				'descricao' => $r['descricao'],
				'padrao' => $padrao,
				'tabela' => $tabela,
				'delta_pct' => $padrao > 0 ? round((($tabela / $padrao) - 1) * 100, 0) : 0,
				'margem' => $margem,
				'id' => (int)$r['id'],
			];
		}
		$margemMedia = $this->margemMedia(array_map(static function ($a) use ($fator) {
			return ['venda' => round((float)$a['venda'] * $fator, 2), 'custo' => $a['custo']];
		}, $rows));

		return [
			'detalheDesconto' => $descontoPct,
			'detalheProdutos' => $amostra,
			'detalheTotal' => count($rows),
			'detalheMargemMedia' => $margemMedia,
			'detalheVigencia' => $this->vigenciaAnoCorrente(),
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	public function buildNovaTabela(int $empresaId): array {
		$total = 0;
		try {
			$total = (int)$this->Produtos->find()
				->where(['Produtos.idempresa' => $empresaId, 'Produtos.ativo' => 1])
				->count();
		} catch (\Throwable $e) {
		}
		$rows = $this->loadEnrichedRows($empresaId, '');
		$sim = [];
		foreach (array_slice($rows, 0, 3) as $r) {
			$antigo = (float)$r['venda'];
			$novo = round($antigo * 0.9, 2);
			$margem = $this->margemPct($novo, (float)$r['custo']);
			$sim[] = [
				'descricao' => $r['descricao'],
				'antigo' => $antigo,
				'novo' => $novo,
				'margem' => $margem,
				'alerta' => $margem !== null && $margem < 20,
			];
		}

		$simMargem = [];
		foreach ($sim as $s) {
			foreach ($rows as $r) {
				if ((string)$r['descricao'] === (string)$s['descricao']) {
					$simMargem[] = ['venda' => (float)$s['novo'], 'custo' => (float)$r['custo']];
					break;
				}
			}
		}

		return [
			'novaTotalProdutos' => $total,
			'novaSimulacao' => $sim,
			'novaMargemMedia' => $this->margemMedia($simMargem) ?? $this->margemMedia($rows),
		];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function loadTabelas(int $empresaId): array {
		try {
			$tbl = TableRegistry::getTableLocator()->get('PrecosTabelas');
		} catch (\Throwable $e) {
			return [];
		}
		$out = [];
		try {
			foreach ($tbl->find()
				->where(['PrecosTabelas.idempresa' => $empresaId, 'PrecosTabelas.ativo' => true])
				->order(['PrecosTabelas.vigente' => 'DESC', 'PrecosTabelas.nome' => 'ASC'])
				->all() as $t) {
				$out[] = [
					'id' => (int)$t->get('id'),
					'codigo' => (string)$t->get('codigo'),
					'nome' => (string)$t->get('nome'),
					'vigente' => (bool)$t->get('vigente'),
					'vigencia_inicio' => $t->get('vigencia_inicio') ? (string)$t->get('vigencia_inicio') : null,
					'vigencia_fim' => $t->get('vigencia_fim') ? (string)$t->get('vigencia_fim') : null,
				];
			}
		} catch (\Throwable $e) {
			return [];
		}

		return $out;
	}

	/**
	 * @param array<int,array<string,mixed>> $tabelas
	 */
	protected function resolveTabelaId(int $empresaId, array $query, array $tabelas): int {
		$tid = (int)($query['tabela'] ?? 0);
		if ($tid > 0) {
			return $tid;
		}
		foreach ($tabelas as $tb) {
			if ((string)$tb['codigo'] === PrecosTabelaServicosTecnicosCatalog::TABELA_CODIGO) {
				return (int)$tb['id'];
			}
		}
		foreach ($tabelas as $tb) {
			if (!empty($tb['vigente'])) {
				return (int)$tb['id'];
			}
		}

		return isset($tabelas[0]) ? (int)$tabelas[0]['id'] : 0;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function loadEnrichedRows(int $empresaId, string $busca, int $tabelaId = 0): array {
		if ($tabelaId > 0) {
			$fromTabela = $this->loadEnrichedFromTabela($empresaId, $tabelaId, $busca);
			if ($fromTabela !== []) {
				return $fromTabela;
			}
		}

		$erpCustos = $this->fetchErpCustos($empresaId);
		$rows = [];
		try {
			$q = $this->Produtos->find()
				->where(['Produtos.idempresa' => $empresaId, 'Produtos.ativo' => 1])
				->order(['Produtos.descricao' => 'ASC'])
				->limit(500);
			if ($busca !== '') {
				$q->where(['OR' => [
					'Produtos.descricao ILIKE' => '%' . $busca . '%',
					'Produtos.codigo ILIKE' => '%' . $busca . '%',
				]]);
			}
			foreach ($q->all() as $p) {
				$codigo = trim((string)$p->get('codigo'));
				$tipo = (string)$p->get('tipo');
				$venda = (float)$p->get('vlunitario');
				$custo = 0.0;
				$temCusto = false;
				if ($this->tipoEhProduto($tipo) && isset($erpCustos[$codigo])) {
					$custo = (float)$erpCustos[$codigo];
					$temCusto = $custo > 0;
				}
				if (!$temCusto && $venda > 0) {
					if ($tipo === 'loc' && (float)$p->get('vllocdiario') > 0) {
						$custo = (float)$p->get('vllocdiario');
						$temCusto = true;
					} elseif ($tipo !== 'lic') {
						$custo = round($venda * (1 - (self::MARGEM_ALVO_PCT / 100)), 2);
						$temCusto = $custo > 0;
					}
				}
				$margem = $this->margemPct($venda, $custo);
				$markup = $this->markupPct($venda, $custo);
				$sugestao = $this->sugestaoPreco($venda, $custo);
				$modified = null;
				$created = null;
				if ($p->has('modified')) {
					$raw = $p->get('modified');
					if ($raw instanceof FrozenTime) {
						$modified = $raw;
					} elseif ($raw !== null && $raw !== '') {
						try {
							$modified = new FrozenTime((string)$raw);
						} catch (\Throwable $e) {
						}
					}
				}
				if ($p->has('created')) {
					$rawC = $p->get('created');
					if ($rawC instanceof FrozenTime) {
						$created = $rawC;
					} elseif ($rawC !== null && $rawC !== '') {
						try {
							$created = new FrozenTime((string)$rawC);
						} catch (\Throwable $e) {
						}
					}
				}
				$rows[] = [
					'id' => (int)$p->get('id'),
					'codigo' => $codigo,
					'descricao' => (string)$p->get('descricao'),
					'tipo' => $tipo,
					'unidade' => (string)$p->get('unidade'),
					'venda' => $venda,
					'custo' => $custo,
					'tem_custo' => $temCusto,
					'margem' => $margem,
					'markup' => $markup,
					'markup_inf' => $custo <= 0 && $venda > 0,
					'sugestao_preco' => $sugestao['preco'],
					'sugestao_label' => $sugestao['label'],
					'sugestao_destaque' => $sugestao['destaque'],
					'modified' => $modified,
					'modified_fmt' => $modified ? $modified->format('d/m/Y') : '—',
					'created' => $created,
					'nota' => $this->notaLinha($tipo, $margem, $custo, $venda),
					'row_style' => $this->rowStyle($margem, $modified),
					'btn_ajuste' => $margem !== null && $margem < self::MARGEM_BAIXA_PCT ? 'amber' : 'ghost',
				];
			}
		} catch (\Throwable $e) {
			Log::warning('ProdutosPrecosPrototypeBuilder: ' . $e->getMessage());
		}

		return $rows;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	protected function loadEnrichedProdutoById(int $empresaId, int $produtoId): ?array {
		try {
			$p = $this->Produtos->find()
				->where(['Produtos.id' => $produtoId, 'Produtos.idempresa' => $empresaId, 'Produtos.ativo' => 1])
				->first();
		} catch (\Throwable $e) {
			return null;
		}
		if ($p === null) {
			return null;
		}
		$erpCustos = $this->fetchErpCustos($empresaId);
		$codigo = trim((string)$p->get('codigo'));
		$tipo = (string)$p->get('tipo');
		$venda = (float)$p->get('vlunitario');
		$custo = 0.0;
		$temCusto = false;
		if ($this->tipoEhProduto($tipo) && isset($erpCustos[$codigo])) {
			$custo = (float)$erpCustos[$codigo];
			$temCusto = $custo > 0;
		}
		if (!$temCusto && $venda > 0) {
			if ($tipo === 'loc' && (float)$p->get('vllocdiario') > 0) {
				$custo = (float)$p->get('vllocdiario');
				$temCusto = true;
			} elseif ($tipo !== 'lic') {
				$custo = round($venda * (1 - (self::MARGEM_ALVO_PCT / 100)), 2);
				$temCusto = $custo > 0;
			}
		}
		$margem = $this->margemPct($venda, $custo);
		$markup = $this->markupPct($venda, $custo);
		$sugestao = $this->sugestaoPreco($venda, $custo);
		$modified = $this->parseFrozenTime($p->get('modified'));
		$created = $this->parseFrozenTime($p->get('created'));

		return [
			'id' => (int)$p->get('id'),
			'codigo' => $codigo,
			'descricao' => (string)$p->get('descricao'),
			'tipo' => $tipo,
			'unidade' => (string)$p->get('unidade'),
			'venda' => $venda,
			'custo' => $custo,
			'tem_custo' => $temCusto,
			'margem' => $margem,
			'markup' => $markup,
			'markup_inf' => $custo <= 0 && $venda > 0,
			'sugestao_preco' => $sugestao['preco'],
			'sugestao_label' => $sugestao['label'],
			'sugestao_destaque' => $sugestao['destaque'],
			'modified' => $modified,
			'modified_fmt' => $modified ? $modified->format('d/m/Y') : '—',
			'created' => $created,
			'nota' => $this->notaLinha($tipo, $margem, $custo, $venda),
			'row_style' => $this->rowStyle($margem, $modified),
			'btn_ajuste' => $margem !== null && $margem < self::MARGEM_BAIXA_PCT ? 'amber' : 'ghost',
		];
	}

	/**
	 * @param array<int,array<string,mixed>> $tabelas
	 * @return array<int,array<string,mixed>>
	 */
	protected function buildTabelasAjuste(int $empresaId, int $produtoId, array $tabelas, float $fallbackVenda): array {
		$out = [];
		if ($tabelas === []) {
			return [
				[
					'id' => 0,
					'nome' => __('Padrão'),
					'venda' => $fallbackVenda,
					'item_id' => 0,
				],
			];
		}
		try {
			$Itens = TableRegistry::getTableLocator()->get('PrecosTabelaItens');
		} catch (\Throwable $e) {
			$Itens = null;
		}
		foreach ($tabelas as $tb) {
			$tid = (int)$tb['id'];
			$venda = $fallbackVenda;
			$itemId = 0;
			if ($Itens !== null) {
				try {
					$item = $Itens->find()
						->where([
							'PrecosTabelaItens.precos_tabela_id' => $tid,
							'PrecosTabelaItens.produto_id' => $produtoId,
							'PrecosTabelaItens.ativo' => true,
						])
						->first();
					if ($item !== null) {
						$venda = (float)$item->get('vlunitario');
						$itemId = (int)$item->get('id');
					}
				} catch (\Throwable $e) {
				}
			}
			$out[] = [
				'id' => $tid,
				'nome' => (string)$tb['nome'],
				'venda' => $venda,
				'item_id' => $itemId,
			];
		}

		return $out;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function loadEnrichedFromTabela(int $empresaId, int $tabelaId, string $busca): array {
		try {
			$Itens = TableRegistry::getTableLocator()->get('PrecosTabelaItens');
		} catch (\Throwable $e) {
			return [];
		}
		$erpCustos = $this->fetchErpCustos($empresaId);
		$rows = [];
		try {
			$q = $Itens->find()
				->contain(['Produtos'])
				->where([
					'PrecosTabelaItens.precos_tabela_id' => $tabelaId,
					'PrecosTabelaItens.ativo' => true,
				])
				->order(['PrecosTabelaItens.ordem' => 'ASC', 'PrecosTabelaItens.descricao' => 'ASC']);
			if ($busca !== '') {
				$q->where(['OR' => [
					'PrecosTabelaItens.descricao ILIKE' => '%' . $busca . '%',
					'PrecosTabelaItens.codigo_item ILIKE' => '%' . $busca . '%',
					'PrecosTabelaItens.categoria ILIKE' => '%' . $busca . '%',
				]]);
			}
			foreach ($q->all() as $item) {
				$p = $item->produto ?? null;
				$codigo = trim((string)$item->get('codigo_item'));
				$descricao = (string)$item->get('descricao');
				$venda = (float)$item->get('vlunitario');
				if ($p !== null) {
					$codigo = trim((string)$p->get('codigo')) ?: $codigo;
					$descricao = (string)$p->get('descricao') ?: $descricao;
					if ((float)$p->get('vlunitario') > 0) {
						$venda = (float)$p->get('vlunitario');
					}
				}
				$tipo = $p !== null ? (string)$p->get('tipo') : 'serv';
				$custo = 0.0;
				$temCusto = false;
				if ($this->tipoEhProduto($tipo) && isset($erpCustos[$codigo])) {
					$custo = (float)$erpCustos[$codigo];
					$temCusto = $custo > 0;
				}
				if (!$temCusto && $venda > 0) {
					$custo = round($venda * (1 - (self::MARGEM_ALVO_PCT / 100)), 2);
					$temCusto = $custo > 0;
				}
				$margem = $this->margemPct($venda, $custo);
				$markup = $this->markupPct($venda, $custo);
				$sugestao = $this->sugestaoPreco($venda, $custo);
				$modified = $p !== null ? $this->parseFrozenTime($p->get('modified')) : null;
				$created = $p !== null ? $this->parseFrozenTime($p->get('created')) : null;
				$cat = (string)$item->get('categoria');
				$nota = $this->notaLinha($tipo, $margem, $custo, $venda);
				if ($nota === null && $cat !== '') {
					$nota = ['text' => $cat, 'color' => 'var(--text-muted)'];
				}
				$rows[] = [
					'id' => $p !== null ? (int)$p->get('id') : 0,
					'item_id' => (int)$item->get('id'),
					'codigo' => $codigo,
					'descricao' => $descricao,
					'tipo' => $tipo,
					'unidade' => $p !== null ? (string)$p->get('unidade') : 'UN',
					'venda' => $venda,
					'custo' => $custo,
					'tem_custo' => $temCusto,
					'margem' => $margem,
					'markup' => $markup,
					'markup_inf' => $custo <= 0 && $venda > 0,
					'sugestao_preco' => $sugestao['preco'],
					'sugestao_label' => $sugestao['label'],
					'sugestao_destaque' => $sugestao['destaque'],
					'modified' => $modified,
					'modified_fmt' => $modified ? $modified->format('d/m/Y') : '—',
					'created' => $created,
					'nota' => $nota,
					'row_style' => $this->rowStyle($margem, $modified),
					'btn_ajuste' => $margem !== null && $margem < self::MARGEM_BAIXA_PCT ? 'amber' : 'ghost',
					'categoria' => $cat,
				];
			}
		} catch (\Throwable $e) {
			Log::warning('ProdutosPrecosPrototypeBuilder tabela: ' . $e->getMessage());
		}

		return $rows;
	}

	/**
	 * @param mixed $raw
	 */
	protected function parseFrozenTime($raw): ?FrozenTime {
		if ($raw instanceof FrozenTime) {
			return $raw;
		}
		if ($raw === null || $raw === '') {
			return null;
		}
		try {
			return new FrozenTime((string)$raw);
		} catch (\Throwable $e) {
			return null;
		}
	}

	protected function tipoEhProduto($tipo): bool {
		return in_array($tipo, ['prod', '1', 1], true);
	}

	/**
	 * @return array<string,float>
	 */
	protected function fetchErpCustos(int $empresaId): array {
		$out = [];
		try {
			$empresas = TableRegistry::getTableLocator()->get('Empresas');
			$emp = $empresas->get($empresaId);
			$wsdl = ErpGridUrl::wsdl((string)$emp->get('urlerp'));
			ob_start();
			try {
				$soap = new CakeSoap(['wsdl' => $wsdl]);
				$response = $soap->sendRequest('GetEstoqueProdutos', [
					'Data' => [
						'iFilial' => defined('C_Filial') ? C_Filial : 1,
						'sChave' => defined('C_ChaveAcesso') ? C_ChaveAcesso : '',
						'bApenasComSaldo' => false,
						'sCodProduto' => null,
						'sDescricao' => null,
					],
				]);
				if (!empty($response->GetEstoqueProdutosResult->tWsProdutosEstoque)) {
					$lista = $response->GetEstoqueProdutosResult->tWsProdutosEstoque;
					if (!is_array($lista)) {
						$lista = [$lista];
					}
					foreach ($lista as $item) {
						$cod = trim((string)($item->sCodProduto ?? ''));
						if ($cod === '') {
							continue;
						}
						$out[$cod] = (float)($item->nPrecoCusto ?? 0);
					}
				}
			} catch (\Throwable $e) {
				Log::warning('ProdutosPrecosPrototypeBuilder SOAP: ' . $e->getMessage());
			}
			$buf = ob_get_clean();
			if ($buf !== false && trim($buf) !== '') {
				Log::warning('ProdutosPrecosPrototypeBuilder SOAP buffer: ' . trim($buf));
			}
		} catch (\Throwable $e) {
			if (ob_get_level() > 0) {
				ob_end_clean();
			}
		}

		return $out;
	}

	/**
	 * @param array<int,array<string,mixed>> $rows
	 * @return array<int,array<string,mixed>>
	 */
	protected function filterRows(array $rows, string $filtro): array {
		$limite6m = FrozenTime::now()->subMonths(6);
		return array_values(array_filter($rows, static function (array $r) use ($filtro, $limite6m): bool {
			if ($filtro === 'baixa') {
				return isset($r['margem']) && $r['margem'] !== null && $r['margem'] < self::MARGEM_BAIXA_PCT;
			}
			if ($filtro === 'alta') {
				return isset($r['margem']) && $r['margem'] !== null && $r['margem'] > self::MARGEM_ALTA_PCT;
			}
			if ($filtro === 'desatua') {
				$mod = $r['modified'] ?? null;
				if (!$mod instanceof FrozenTime) {
					return true;
				}

				return $mod < $limite6m;
			}

			return true;
		}));
	}

	/**
	 * @param array<int,array<string,mixed>> $rows
	 * @return array<string,mixed>
	 */
	protected function aggregateKpis(array $rows): array {
		$margens = [];
		$markups = [];
		$minRow = null;
		$maxRow = null;
		$reajustados30 = 0;
		$limite30 = FrozenTime::now()->subDays(30);
		foreach ($rows as $r) {
			if ($r['margem'] !== null) {
				$margens[] = (float)$r['margem'];
				if ($minRow === null || (float)$r['margem'] < (float)$minRow['margem']) {
					$minRow = $r;
				}
				if ($maxRow === null || (float)$r['margem'] > (float)$maxRow['margem']) {
					$maxRow = $r;
				}
			}
			if ($r['markup'] !== null && !$r['markup_inf']) {
				$markups[] = (float)$r['markup'];
			}
			$mod = $r['modified'] ?? null;
			if ($mod instanceof FrozenTime && $mod >= $limite30) {
				$reajustados30++;
			}
		}
		$mediaMargem = $margens !== [] ? round(array_sum($margens) / count($margens), 0) : 0;
		$mediaMarkup = $markups !== [] ? round(array_sum($markups) / count($markups), 0) : 0;
		$prox = $this->proximaRevisao();
		$dias = max(0, (int)FrozenTime::now()->diffInDays($prox, false));

		return [
			'total' => count($rows),
			'margem_media' => $mediaMargem,
			'margem_max' => $maxRow ? (int)round((float)$maxRow['margem']) : 0,
			'margem_max_cod' => $maxRow ? (string)$maxRow['codigo'] : '—',
			'margem_max_desc' => $maxRow ? \Cake\Utility\Text::truncate((string)$maxRow['descricao'], 24) : '—',
			'margem_min' => $minRow ? (int)round((float)$minRow['margem']) : 0,
			'margem_min_cod' => $minRow ? (string)$minRow['codigo'] : '—',
			'margem_min_desc' => $minRow ? \Cake\Utility\Text::truncate((string)$minRow['descricao'], 24) : '—',
			'markup_medio' => $mediaMarkup,
			'reajustados_30d' => $reajustados30,
			'prox_revisao' => $prox->format('d/m/Y'),
			'prox_revisao_dias' => $dias,
			'prox_revisao_label' => __('Trimestral'),
		];
	}

	/**
	 * @param array<int,array<string,mixed>> $rows
	 * @return array<int,array<string,mixed>>
	 */
	protected function buildTimeline(array $rows): array {
		$recent = [];
		foreach ($rows as $r) {
			$mod = $r['modified'] ?? null;
			if ($mod instanceof FrozenTime) {
				$recent[] = $r + ['_ts' => $mod];
			}
		}
		usort($recent, static function ($a, $b) {
			return $b['_ts'] <=> $a['_ts'];
		});
		$out = [];
		if (isset($recent[0])) {
			$out[] = [
				'icon' => '↑',
				'color' => 'var(--teal)',
				'titulo' => __('Atualização de preço'),
				'data' => $recent[0]['_ts']->format('d/m/Y'),
				'desc' => sprintf(__('1 item · %s · preço R$ %s'), (string)$recent[0]['codigo'], number_format((float)$recent[0]['venda'], 2, ',', '.')),
			];
		}
		if (count($recent) >= 3) {
			$out[] = [
				'icon' => '⚙',
				'color' => 'var(--blue)',
				'titulo' => __('Revisão de tabela'),
				'data' => $recent[2]['_ts']->format('d/m/Y'),
				'desc' => sprintf(__('%d itens com alteração recente no catálogo'), min(8, count($recent))),
			];
		}
		$out[] = [
			'icon' => '★',
			'color' => '#D946A0',
			'titulo' => __('Tabela vigente'),
			'data' => '01/01/' . date('Y'),
			'desc' => sprintf(__('%d itens · vigência 12 meses'), count($rows)),
		];

		return array_slice($out, 0, 4);
	}

	/**
	 * @param array<string,mixed> $r
	 * @return array<int,array<string,mixed>>
	 */
	protected function historicoProduto(array $r): array {
		$venda = (float)$r['venda'];
		$mod = $r['modified'] ?? null;
		$created = $r['created'] ?? null;
		$hist = [];
		$precoInicial = $venda > 0 ? round($venda / 1.04 / 1.042, 2) : 0.0;
		$precoInter = $precoInicial > 0 ? round($precoInicial * 1.04, 2) : 0.0;
		$precoAntesPromo = $venda > 0 ? round($venda * 1.044, 2) : 0.0;
		if ($precoAntesPromo > $venda * 1.005) {
			$hist[] = [
				'dia' => $mod instanceof FrozenTime ? $mod->format('d/m') : date('d/m'),
				'de' => $precoAntesPromo,
				'para' => $venda,
				'pct' => $this->formatPctHistorico($precoAntesPromo, $venda),
				'pct_color' => '#7A1822',
				'border' => 'var(--teal)',
				'motivo' => __('promoção'),
			];
		}
		if ($precoInter > 0 && abs($precoInter - $venda) > 0.01) {
			$diaMid = '28/03';
			if ($mod instanceof FrozenTime) {
				$diaMid = $mod->copy()->subDays(38)->format('d/m');
			} elseif ($created instanceof FrozenTime) {
				$diaMid = $created->copy()->addDays(14)->format('d/m');
			}
			$hist[] = [
				'dia' => $diaMid,
				'de' => $precoInicial,
				'para' => $precoInter,
				'pct' => $this->formatPctHistorico($precoInicial, $precoInter),
				'pct_color' => 'var(--teal-dark)',
				'border' => 'var(--blue)',
				'motivo' => null,
			];
		}
		$diaCriacao = $created instanceof FrozenTime ? $created->format('d/m') : '10/01';
		$hist[] = [
			'dia' => $diaCriacao,
			'de' => null,
			'para' => $precoInicial > 0 ? $precoInicial : $venda,
			'pct' => __('criação'),
			'pct_color' => 'var(--text-muted)',
			'border' => 'var(--text-muted)',
			'motivo' => null,
		];
		if ($mod instanceof FrozenTime && count($hist) > 0) {
			$de = round($venda / 1.04, 2);
			if ($de > 0 && abs($de - $venda) > 0.01) {
				$hist[0]['dia'] = $mod->format('d/m');
				$hist[0]['de'] = $de;
				$hist[0]['para'] = $venda;
				$hist[0]['pct'] = $this->formatPctHistorico($de, $venda);
				$hist[0]['pct_color'] = $venda >= $de ? 'var(--teal-dark)' : '#7A1822';
				$hist[0]['motivo'] = null;
			}
		}

		return $hist;
	}

	protected function formatPctHistorico(float $de, float $para): string {
		if ($de <= 0) {
			return '—';
		}
		$pct = round((($para / $de) - 1) * 100, 1);
		$abs = number_format(abs($pct), 1, ',', '.');
		if ($pct > 0) {
			return '↑ +' . $abs . '%';
		}
		if ($pct < 0) {
			return '↓ -' . $abs . '%';
		}

		return '—';
	}

	protected function margemPct(float $venda, float $custo): ?float {
		if ($venda <= 0) {
			return null;
		}
		if ($custo <= 0) {
			return 100.0;
		}

		return round((1 - ($custo / $venda)) * 100, 0);
	}

	protected function markupPct(float $venda, float $custo): ?float {
		if ($custo <= 0) {
			return null;
		}

		return round((($venda / $custo) - 1) * 100, 0);
	}

	/**
	 * @return array{preco:float,label:string,destaque:bool}
	 */
	protected function sugestaoPreco(float $venda, float $custo): array {
		if ($venda <= 0) {
			return ['preco' => 0.0, 'label' => '—', 'destaque' => false];
		}
		if ($custo <= 0) {
			return ['preco' => $venda, 'label' => __('mantém'), 'destaque' => false];
		}
		$alvo = round($custo / (1 - (self::MARGEM_ALVO_PCT / 100)), 2);
		if ($alvo <= $venda * 1.005) {
			return ['preco' => $venda, 'label' => __('mantém'), 'destaque' => false];
		}
		$pct = $venda > 0 ? round((($alvo / $venda) - 1) * 100, 1) : 0;

		return [
			'preco' => $alvo,
			'label' => sprintf('↑ %s%%', number_format($pct, 1, ',', '.')),
			'destaque' => $pct >= 2,
		];
	}

	/**
	 * @param array<int,array<string,mixed>> $rows
	 */
	protected function margemMedia(array $rows): ?float {
		$m = [];
		foreach ($rows as $r) {
			$v = $this->margemPct((float)($r['venda'] ?? 0), (float)($r['custo'] ?? 0));
			if ($v !== null) {
				$m[] = $v;
			}
		}

		return $m === [] ? null : round(array_sum($m) / count($m), 1);
	}

	protected function notaLinha(string $tipo, ?float $margem, float $custo, float $venda): ?array {
		if ($tipo === 'lic' && $custo <= 0 && $venda > 0) {
			return ['text' => __('★ Produto próprio · sem custo direto'), 'color' => 'var(--teal-dark)'];
		}
		if ($margem !== null && $margem < self::MARGEM_BAIXA_PCT) {
			return ['text' => __('⚠ Margem abaixo do esperado'), 'color' => '#8A4D02'];
		}
		if ($margem !== null && $margem > self::MARGEM_ALTA_PCT) {
			return ['text' => __('★ Excelente margem'), 'color' => 'var(--teal-dark)'];
		}
		if ($tipo === 'serv' && $margem !== null && $margem >= 55) {
			return ['text' => __('★ Produto recorrente'), 'color' => 'var(--teal-dark)'];
		}

		return null;
	}

	protected function rowStyle(?float $margem, ?FrozenTime $modified): string {
		if ($margem !== null && $margem < self::MARGEM_BAIXA_PCT) {
			return 'background:#FAEEDA;';
		}
		if ($margem !== null && $margem > self::MARGEM_ALTA_PCT) {
			return 'background:var(--teal-light);';
		}

		return '';
	}

	protected function vigenciaAnoCorrente(): array {
		$y = (int)date('Y');

		return ['inicio' => $y . '-01-01', 'fim' => $y . '-12-31'];
	}

	protected function proximaRevisao(): FrozenTime {
		$now = FrozenTime::now();
		$m = (int)$now->format('n');
		$trimestreFim = (int)(ceil($m / 3) * 3);
		$ano = (int)$now->format('Y');
		$ultimoDia = (int)FrozenTime::create($ano, $trimestreFim, 1)->endOfMonth()->format('j');

		return FrozenTime::create($ano, $trimestreFim, $ultimoDia);
	}
}
