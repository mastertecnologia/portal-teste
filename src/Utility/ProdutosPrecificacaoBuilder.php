<?php
declare(strict_types=1);

namespace App\Utility;

use App\Model\Table\ProdutosTable;
use App\Utility\ErpGridUrl;
use App\Utility\Fiscal\FiscalRegimeHelper;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use CakeSoap\Network\CakeSoap;

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
	public function buildPayload(int $empresaId, array $query = []): array {
		$prodId = (int)($query['produto_id'] ?? 0);
		$empresaCtx = $this->loadEmpresaContext($empresaId);
		$erpEstoque = $this->fetchErpEstoqueMap($empresaId);
		$precosBuilder = new ProdutosPrecosPrototypeBuilder($this->Produtos);
		$listaPrecos = $precosBuilder->buildLista($empresaId, $query);
		$tabelaId = (int)($listaPrecos['precosTabelaAtivaId'] ?? 0);
		$precosTabela = $this->loadPrecosTabelaMaps($tabelaId);

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
				$codigo = trim((string)$p->get('codigo'));
				$tipoNorm = $this->normalizarTipo($p->get('tipo'));
				$resolvido = $this->resolverVendaCusto(
					$id,
					$codigo,
					$tipoNorm,
					(float)$p->get('vlunitario'),
					(float)$p->get('vllocdiario'),
					$precosTabela,
					$erpEstoque
				);
				$venda = $resolvido['venda'];
				$custo = $resolvido['custo'];
				$fonteVenda = $resolvido['fonte_venda'];
				$fonteCusto = $resolvido['fonte_custo'];
				$margem = $this->margemPct($venda, $custo);
				$margemLucro = $margem !== null && $margem > 0 ? $margem : 20.0;
				$operacao = $this->operacaoPorTipo($tipoNorm);
				$anexo = $this->anexoPorOperacao($operacao, $empresaCtx);

				$payload = [
					'id' => $id,
					'codigo' => $codigo,
					'descricao' => (string)$p->get('descricao'),
					'tipo' => $tipoNorm,
					'tipo_label' => $this->labelTipo($tipoNorm),
					'custo' => $custo,
					'venda' => $venda,
					'margem' => $margem,
					'fonte_custo' => $fonteCusto,
					'fonte_venda' => $fonteVenda ?? 'cadastro',
					'tem_custo' => $custo > 0,
					'custo_fmt' => $this->fmtMoeda($custo > 0 ? $custo : 0),
					'venda_fmt' => $this->fmtMoeda($venda),
					'frete_fmt' => '0,00',
					'margem_lucro_pct' => round($margemLucro, 2),
					'margem_fmt' => $margem !== null ? number_format($margem, 0, ',', '.') . '%' : '—',
					'operacao' => $operacao,
					'anexo' => $anexo,
					'regime' => (string)$empresaCtx['regime_ui'],
				];
				$opcoes[$id] = $id . ' · ' . trim(sprintf('%s · %s', $codigo, (string)$p->get('descricao')));
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
		if ($rbt12 > 0) {
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
	 * Preços da tabela vigente por produto_id e codigo_item.
	 *
	 * @return array{by_produto: array<int,float>, by_codigo: array<string,float>}
	 */
	protected function loadPrecosTabelaMaps(int $tabelaId): array {
		$empty = ['by_produto' => [], 'by_codigo' => []];
		if ($tabelaId <= 0) {
			return $empty;
		}
		$byProduto = [];
		$byCodigo = [];
		try {
			$Itens = TableRegistry::getTableLocator()->get('PrecosTabelaItens');
			foreach ($Itens->find()
				->where([
					'PrecosTabelaItens.precos_tabela_id' => $tabelaId,
					'PrecosTabelaItens.ativo' => 1,
				])
				->all() as $item) {
				$vl = (float)$item->get('vlunitario');
				if ($vl <= 0) {
					continue;
				}
				$pid = (int)$item->get('produto_id');
				if ($pid > 0) {
					$byProduto[$pid] = $vl;
				}
				$cod = trim((string)$item->get('codigo_item'));
				if ($cod !== '') {
					$byCodigo[$cod] = $vl;
				}
			}
		} catch (\Throwable $e) {
			Log::warning('ProdutosPrecificacaoBuilder::loadPrecosTabelaMaps: ' . $e->getMessage());
		}

		return ['by_produto' => $byProduto, 'by_codigo' => $byCodigo];
	}

	/**
	 * @param array{by_produto: array<int,float>, by_codigo: array<string,float>} $precosTabela
	 * @param array<string,array{custo:float,venda:float}> $erpEstoque
	 * @return array{venda:float,custo:float,fonte_venda:string,fonte_custo:string}
	 */
	protected function resolverVendaCusto(
		int $produtoId,
		string $codigo,
		string $tipoNorm,
		float $vlCadastro,
		float $vlLocDiario,
		array $precosTabela,
		array $erpEstoque
	): array {
		$venda = $vlCadastro;
		$fonteVenda = 'cadastro';
		$byProduto = $precosTabela['by_produto'] ?? [];
		$byCodigo = $precosTabela['by_codigo'] ?? [];
		if ($codigo !== '' && isset($byCodigo[$codigo]) && (float)$byCodigo[$codigo] > 0) {
			$venda = (float)$byCodigo[$codigo];
			$fonteVenda = 'tabela';
		} elseif (isset($byProduto[$produtoId]) && (float)$byProduto[$produtoId] > 0) {
			$venda = (float)$byProduto[$produtoId];
			$fonteVenda = 'tabela';
		}
		$custo = 0.0;
		$fonteCusto = 'estimado';
		if ($codigo !== '' && isset($erpEstoque[$codigo])) {
			$erpRow = $erpEstoque[$codigo];
			if ((float)($erpRow['custo'] ?? 0) > 0) {
				$custo = (float)$erpRow['custo'];
				$fonteCusto = 'erp';
			}
			if ($fonteVenda !== 'tabela' && (float)($erpRow['venda'] ?? 0) > 0) {
				$venda = (float)$erpRow['venda'];
				$fonteVenda = 'erp';
			}
		}
		if ($tipoNorm === 'loc' && $vlLocDiario > 0) {
			$custo = $vlLocDiario;
			$fonteCusto = 'cadastro';
		} elseif ($custo <= 0 && $venda > 0 && $tipoNorm !== 'lic') {
			$custo = round($venda * (1 - (self::MARGEM_ESTIMADA_PCT / 100)), 2);
			$fonteCusto = 'estimado_margem';
		}

		return [
			'venda' => $venda,
			'custo' => $custo,
			'fonte_venda' => $fonteVenda,
			'fonte_custo' => $fonteCusto,
		];
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
			'custo' => '1.000,00',
			'frete' => '0,00',
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
		if ($custo <= 0 && (float)$p['venda'] > 0) {
			$custo = round((float)$p['venda'] * 0.7, 2);
		}

		return [
			'custo' => $this->fmtMoeda($custo > 0 ? $custo : 1000),
			'frete' => (string)($p['frete_fmt'] ?? '0,00'),
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

	protected function margemPct(float $venda, float $custo): ?float {
		if ($venda <= 0) {
			return null;
		}
		if ($custo <= 0) {
			return 100.0;
		}

		return round((1 - ($custo / $venda)) * 100, 0);
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
	 * @return array<string,array{custo:float,venda:float}>
	 */
	protected function fetchErpEstoqueMap(int $empresaId): array {
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
						$out[$cod] = [
							'custo' => (float)($item->nPrecoCusto ?? 0),
							'venda' => (float)($item->nPrecoVenda ?? 0),
						];
					}
				}
			} catch (\Throwable $e) {
				Log::warning('ProdutosPrecificacaoBuilder SOAP: ' . $e->getMessage());
			}
			$buf = ob_get_clean();
			if ($buf !== false && $buf !== '') {
				Log::warning('ProdutosPrecificacaoBuilder SOAP buffer: ' . substr($buf, 0, 200));
			}
		} catch (\Throwable $e) {
			$ob = ob_get_clean();
			if ($ob !== false && $ob !== '') {
				Log::warning('ProdutosPrecificacaoBuilder: ' . substr($ob, 0, 200));
			}
		}

		return $out;
	}
}
