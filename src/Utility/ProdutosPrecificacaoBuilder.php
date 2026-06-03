<?php
declare(strict_types=1);

namespace App\Utility;

use App\Model\Table\ProdutosTable;
use App\Utility\Fiscal\FiscalRegimeHelper;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

/**
 * Dados reais para o Centro de Cálculo de Precificação (protótipo Produtos).
 */
class ProdutosPrecificacaoBuilder {

	public const MARGEM_ESTIMADA_PCT = 30.0;

	/** @var ProdutosTable */
	protected $Produtos;

	public function __construct(?ProdutosTable $produtos = null) {
		$this->Produtos = $produtos ?? TableRegistry::getTableLocator()->get('Produtos');
	}

	/**
	 * @param array<string,mixed> $query
	 * @return array<string,mixed>
	 */
	/**
	 * @return array<string,mixed>
	 */
	public function getEmpresaContext(int $empresaId): array {
		return $this->loadEmpresaContext($empresaId);
	}

	public function buildPayload(int $empresaId, array $query = []): array {
		$prodId = (int)($query['produto_id'] ?? 0);
		$empresaCtx = $this->loadEmpresaContext($empresaId);
		$precosBuilder = new ProdutosPrecosPrototypeBuilder($this->Produtos);
		$listaPrecos = $precosBuilder->buildLista($empresaId, $query);
		$tabelaId = (int)($listaPrecos['precosTabelaAtivaId'] ?? 0);
		$erpMap = $precosBuilder->fetchErpPrecosMap($empresaId);

		$opcoes = [];
		$produtosData = [];
		$inicial = $this->defaultsInicial($empresaCtx);

		try {
			foreach ($this->Produtos->find()
				->where(['Produtos.idempresa' => $empresaId, 'Produtos.ativo' => 1])
				->order(['Produtos.descricao' => 'ASC'])
				->limit(500)
				->all() as $p) {
				$id = (int)$p->get('id');
				$row = $precosBuilder->resolveProdutoPrecificacao($empresaId, $id, $tabelaId, $erpMap);
				if ($row === null) {
					continue;
				}
				$tipoNorm = $this->normalizarTipo($p->get('tipo'));
				$venda = (float)$row['venda'];
				$custo = (float)$row['custo'];
				$margem = $row['margem'];
				$margemLucro = $margem !== null && $margem > 0 ? $margem : 20.0;
				$operacao = $this->operacaoPorTipo($tipoNorm);
				$anexo = $this->anexoPorOperacao($operacao, $empresaCtx);
				$fonteVenda = (string)($row['fonte_venda'] ?? 'cadastro');
				$fonteCusto = (string)($row['fonte_custo'] ?? 'estimado');

				$payload = [
					'id' => $id,
					'codigo' => (string)$row['codigo'],
					'descricao' => (string)$row['descricao'],
					'tipo' => $tipoNorm,
					'tipo_label' => $this->labelTipo($tipoNorm),
					'custo' => $custo,
					'venda' => $venda,
					'margem' => $margem,
					'fonte_custo' => $fonteCusto,
					'fonte_venda' => $fonteVenda,
					'tem_custo' => $custo > 0,
					'custo_fmt' => $this->fmtMoeda($custo),
					'venda_fmt' => $this->fmtMoeda($venda),
					'frete_fmt' => '0,00',
					'margem_lucro_pct' => round($margemLucro, 2),
					'margem_fmt' => $margem !== null ? number_format($margem, 0, ',', '.') . '%' : '—',
					'operacao' => $operacao,
					'anexo' => $anexo,
					'regime' => (string)$empresaCtx['regime_ui'],
				];
				$opcoes[$id] = $id . ' · ' . trim(sprintf('%s · %s', (string)$row['codigo'], (string)$row['descricao']));
				$produtosData[$id] = $payload;
				if ($prodId > 0 && $id === $prodId) {
					$inicial = $this->produtoParaInicial($payload, $empresaCtx);
				}
			}
		} catch (\Throwable $e) {
			Log::warning('ProdutosPrecificacaoBuilder: ' . $e->getMessage());
		}

		if ($prodId > 0 && !isset($produtosData[$prodId])) {
			$inicial = $this->defaultsInicial($empresaCtx);
		}

		return [
			'precificOpcoes' => $opcoes,
			'precificProdutosJson' => json_encode($produtosData, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
			'precificEmpresaJson' => json_encode($empresaCtx, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
			'precificProdutoId' => $prodId,
			'precificInicial' => $inicial,
			'precificTabelaAtivaId' => $tabelaId,
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	protected function loadEmpresaContext(int $empresaId): array {
		$ctx = [
			'regime_ui' => 'simples',
			'regime_tributario' => 1,
			'rbt12' => 1420000.0,
			'rbt12_fmt' => $this->fmtMoedaBrl(1420000.0),
			'anexo' => 'III',
			'fator_r' => 32,
			'operacao' => 'servico',
			'pres_irpj' => 32,
			'pres_csll' => 32,
			'margem_real' => 18,
			'creditos_pct' => 35,
			'aliquota_simples_pct' => null,
			'icms_pct' => 18.0,
			'pis_pct' => 0.65,
			'cofins_pct' => 3.0,
			'uf' => 'RS',
			'fonte_rbt12' => 'padrao',
		];
		try {
			$Fiscal = TableRegistry::getTableLocator()->get('FiscalEmpresasConfig');
			$row = $Fiscal->find()->where(['FiscalEmpresasConfig.idempresa' => $empresaId])->first();
			if ($row !== null) {
				$cfg = $row->toArray();
				$rt = (int)($cfg['regime_tributario'] ?? 3);
				$ctx['regime_tributario'] = $rt;
				$ctx['regime_ui'] = $this->mapRegimeUi($rt, $cfg);
				$ctx['uf'] = (string)($cfg['uf'] ?? 'RS');
				if (!empty($cfg['aliquota_simples'])) {
					$ctx['aliquota_simples_pct'] = (float)$cfg['aliquota_simples'];
				}
				$enq = (int)($cfg['regime_normal_enquadramento'] ?? FiscalRegimeHelper::ENQUADRAMENTO_REAL);
				if ($rt === 3) {
					$ctx['pres_irpj'] = $enq === FiscalRegimeHelper::ENQUADRAMENTO_PRESUMIDO ? 8 : 32;
					$ctx['pres_csll'] = $enq === FiscalRegimeHelper::ENQUADRAMENTO_PRESUMIDO ? 12 : 32;
					$ctx['margem_real'] = 18;
				}
				$pisCof = FiscalRegimeHelper::pisCofinsAliquotasPadraoReceita($cfg);
				$ctx['pis_pct'] = (float)$pisCof['pis'];
				$ctx['cofins_pct'] = (float)$pisCof['cofins'];
				$cnae = (string)($cfg['cnae_fiscal'] ?? '');
				if (strpos($cnae, '47') === 0) {
					$ctx['operacao'] = 'comercio';
					$ctx['anexo'] = 'I';
				} elseif (strpos($cnae, '62') === 0 || strpos($cnae, '86') === 0) {
					$ctx['operacao'] = 'servico';
					$ctx['anexo'] = 'III';
				}
			}
		} catch (\Throwable $e) {
		}

		$rbt12 = $this->estimarRbt12($empresaId);
		if ($rbt12 >= 180000) {
			$ctx['rbt12'] = $rbt12;
			$ctx['rbt12_fmt'] = $this->fmtMoedaBrl($rbt12);
			$ctx['fonte_rbt12'] = 'faturamento_12m';
		}

		try {
			$Aliq = TableRegistry::getTableLocator()->get('FiscalAliquotas');
			$al = $Aliq->find()
				->where(['FiscalAliquotas.idempresa' => $empresaId])
				->order(['FiscalAliquotas.id' => 'ASC'])
				->first();
			if ($al !== null && $al->has('icms_aliquota') && (float)$al->get('icms_aliquota') > 0) {
				$ctx['icms_pct'] = (float)$al->get('icms_aliquota');
			}
		} catch (\Throwable $e) {
		}

		return $ctx;
	}

	protected function estimarRbt12(int $empresaId): float {
		try {
			$Fat = TableRegistry::getTableLocator()->get('Faturamento');
			$desde = FrozenTime::now()->subMonths(12)->format('Y-m-d');
			$row = $Fat->find()
				->select(['total' => $Fat->find()->func()->sum('valor_total')])
				->where([
					'Faturamento.idempresa' => $empresaId,
					'Faturamento.data_emissao >=' => $desde,
					'Faturamento.status NOT IN' => ['cancelado', 'rascunho'],
				])
				->enableHydration(false)
				->first();
			if ($row !== null && !empty($row['total'])) {
				return (float)$row['total'];
			}
		} catch (\Throwable $e) {
		}

		return 0.0;
	}

	/**
	 * @param array<string,mixed> $cfg
	 */
	protected function mapRegimeUi(int $regimeTributario, array $cfg): string {
		if (in_array($regimeTributario, [1, 2], true)) {
			return 'simples';
		}
		$enq = (int)($cfg['regime_normal_enquadramento'] ?? FiscalRegimeHelper::ENQUADRAMENTO_REAL);
		if ($enq === FiscalRegimeHelper::ENQUADRAMENTO_PRESUMIDO) {
			return 'presumido';
		}

		return 'real';
	}

	/**
	 * @param array<string,mixed> $empresaCtx
	 * @return array<string,string>
	 */
	protected function defaultsInicial(array $empresaCtx): array {
		return [
			'custo' => '0,00',
			'frete' => '0,00',
			'venda' => '0,00',
			'rbt12' => (string)$empresaCtx['rbt12_fmt'],
			'regime' => (string)$empresaCtx['regime_ui'],
		];
	}

	/**
	 * @param array<string,mixed> $p
	 * @param array<string,mixed> $empresaCtx
	 * @return array<string,string>
	 */
	protected function produtoParaInicial(array $p, array $empresaCtx): array {
		$custo = (float)$p['custo'];
		$venda = (float)$p['venda'];
		if ($custo <= 0 && $venda > 0) {
			$custo = round($venda * (1 - (self::MARGEM_ESTIMADA_PCT / 100)), 2);
		}

		return [
			'custo' => $this->fmtMoeda($custo),
			'frete' => (string)($p['frete_fmt'] ?? '0,00'),
			'venda' => $this->fmtMoeda($venda),
			'rbt12' => (string)$empresaCtx['rbt12_fmt'],
			'regime' => (string)($p['regime'] ?? $empresaCtx['regime_ui']),
			'operacao' => (string)$p['operacao'],
			'anexo' => (string)$p['anexo'],
			'margem' => $this->fmtPct((float)$p['margem_lucro_pct']),
			'produto_id' => (int)$p['id'],
		];
	}

	/**
	 * @param mixed $tipo
	 */
	protected function normalizarTipo($tipo): string {
		if (in_array($tipo, ['1', 1, 'prod', 'produto'], true)) {
			return 'prod';
		}
		if (in_array($tipo, ['2', 2, 'serv', 'servico'], true)) {
			return 'serv';
		}
		if (in_array($tipo, ['3', 3, 'loc', 'contrato'], true)) {
			return 'loc';
		}
		if ($tipo === 'lic') {
			return 'lic';
		}

		return 'serv';
	}

	protected function labelTipo(string $tipo): string {
		$labels = [
			'prod' => __('Produto'),
			'serv' => __('Serviço'),
			'loc' => __('Contrato/Locação'),
			'lic' => __('Licença'),
		];

		return $labels[$tipo] ?? __('Item');
	}

	protected function operacaoPorTipo(string $tipo): string {
		if ($tipo === 'prod') {
			return 'comercio';
		}
		if ($tipo === 'loc') {
			return 'locacao';
		}

		return 'servico';
	}

	/**
	 * @param array<string,mixed> $empresaCtx
	 */
	protected function anexoPorOperacao(string $operacao, array $empresaCtx): string {
		if ($operacao === 'comercio') {
			return 'I';
		}
		if ($operacao === 'industria') {
			return 'II';
		}
		if ($operacao === 'locacao') {
			return 'III';
		}

		return (string)($empresaCtx['anexo'] ?? 'III');
	}

	protected function fmtMoeda(float $v): string {
		return number_format($v, 2, ',', '.');
	}

	protected function fmtMoedaBrl(float $v): string {
		return 'R$ ' . number_format($v, 2, ',', '.');
	}

	protected function fmtPct(float $v): string {
		return number_format($v, 2, ',', '.');
	}

	/**
	 * Payload JSON para o simulador (dados do banco / tabela / ERP).
	 *
	 * @param array<string,mixed> $row Retorno de ProdutosPrecosPrototypeBuilder::resolveProdutoPrecificacao
	 * @return array<string,mixed>
	 */
	public function produtoParaJson(array $row, ?array $empresaCtx = null): array {
		$tipoNorm = $this->normalizarTipo($row['tipo'] ?? 'serv');
		$venda = (float)($row['venda'] ?? 0);
		$custo = (float)($row['custo'] ?? 0);
		$margem = $row['margem'] ?? null;
		$operacao = $this->operacaoPorTipo($tipoNorm);
		$ctx = $empresaCtx ?? ['anexo' => 'III', 'regime_ui' => 'simples'];

		return [
			'id' => (int)($row['id'] ?? 0),
			'codigo' => (string)($row['codigo'] ?? ''),
			'descricao' => (string)($row['descricao'] ?? ''),
			'tipo' => $tipoNorm,
			'tipo_label' => $this->labelTipo($tipoNorm),
			'custo' => $custo,
			'venda' => $venda,
			'margem' => $margem,
			'fonte_custo' => (string)($row['fonte_custo'] ?? 'estimado'),
			'fonte_venda' => (string)($row['fonte_venda'] ?? 'cadastro'),
			'tem_custo' => $custo > 0,
			'custo_fmt' => $this->fmtMoeda($custo),
			'venda_fmt' => $this->fmtMoeda($venda),
			'frete_fmt' => '0,00',
			'margem_lucro_pct' => $margem !== null && $margem > 0 ? round((float)$margem, 2) : 20.0,
			'margem_fmt' => $margem !== null ? number_format((float)$margem, 0, ',', '.') . '%' : '—',
			'operacao' => $operacao,
			'anexo' => $this->anexoPorOperacao($operacao, $ctx),
			'regime' => (string)($ctx['regime_ui'] ?? 'simples'),
			'vlunitario_bd' => $venda,
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	public function empresaParaJson(array $empresaCtx): array {
		return [
			'rbt12_fmt' => (string)($empresaCtx['rbt12_fmt'] ?? ''),
			'rbt12' => (float)($empresaCtx['rbt12'] ?? 0),
			'fonte_rbt12' => (string)($empresaCtx['fonte_rbt12'] ?? 'padrao'),
			'regime_ui' => (string)($empresaCtx['regime_ui'] ?? 'simples'),
			'operacao' => (string)($empresaCtx['operacao'] ?? 'servico'),
			'anexo' => (string)($empresaCtx['anexo'] ?? 'III'),
			'fator_r' => (int)($empresaCtx['fator_r'] ?? 32),
			'pres_irpj' => (int)($empresaCtx['pres_irpj'] ?? 32),
			'pres_csll' => (int)($empresaCtx['pres_csll'] ?? 32),
			'margem_real' => (int)($empresaCtx['margem_real'] ?? 18),
			'creditos_pct' => (int)($empresaCtx['creditos_pct'] ?? 35),
		];
	}
}
