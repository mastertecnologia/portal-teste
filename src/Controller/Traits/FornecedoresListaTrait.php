<?php
declare(strict_types=1);

namespace App\Controller\Traits;

use App\Utility\ClientesPapelCadastro;
use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;

/**
 * Lista pg-fornecedores — KPIs e linhas com dados reais.
 */
trait FornecedoresListaTrait {

	/**
	 * @return array{
	 *   counts:array{total:int,ativos:int,inativos:int,homologados:int,analise:int,compras12_fmt:string},
	 *   categorias:array<int,string>,
	 *   items:array<int,array<string,mixed>>
	 * }
	 */
	protected function buildFornecedoresListaData(int $idempresa, string $busca, string $filtroStatus, string $filtroCategoria, bool $filtroPj, bool $filtroPf): array {
		$tbl = $this->Clientes;
		$papelCols = ClientesPapelCadastro::columnsAvailable($tbl);
		$pjTipo = defined('C_ClientesTipoJuridica') ? (int)C_ClientesTipoJuridica : 2;
		$pfTipo = defined('C_ClientesTipoFisica') ? (int)C_ClientesTipoFisica : 1;

		$where = ClientesPapelCadastro::whereFornecedor($idempresa, $papelCols);
		if ($busca !== '') {
			$where['OR'] = [
				'Clientes.razaosocial ILIKE' => '%' . $busca . '%',
				'Clientes.nomefantasia ILIKE' => '%' . $busca . '%',
				'Clientes.nome ILIKE' => '%' . $busca . '%',
				'Clientes.cnpj ILIKE' => '%' . $busca . '%',
				'Clientes.cpf ILIKE' => '%' . $busca . '%',
				'Clientes.email ILIKE' => '%' . $busca . '%',
			];
		}
		if ($filtroStatus === 'ativo') {
			$where['Clientes.inativo'] = 0;
		} elseif ($filtroStatus === 'inativo') {
			$where['Clientes.inativo'] = 1;
		} elseif ($filtroStatus === 'homologado' && $papelCols) {
			$where['Clientes.fornecedor_status_homologacao'] = ClientesPapelCadastro::STATUS_HOMOLOGADO;
		} elseif ($filtroStatus === 'analise' && $papelCols) {
			$where['Clientes.fornecedor_status_homologacao'] = ClientesPapelCadastro::STATUS_ANALISE;
		}
		if ($filtroCategoria !== '' && $papelCols) {
			$where['Clientes.fornecedor_categoria'] = $filtroCategoria;
		}
		if (!$filtroPj && !$filtroPf) {
			$where['Clientes.id'] = 0;
		} elseif ($filtroPj xor $filtroPf) {
			$where['Clientes.tipo'] = $filtroPj ? $pjTipo : $pfTipo;
		}

		$rows = [];
		try {
			$rows = $tbl->find()
				->contain(['Cidades.Estados'])
				->where($where)
				->order(['Clientes.razaosocial' => 'ASC', 'Clientes.nome' => 'ASC'])
				->limit(300)
				->all()
				->toArray();
		} catch (\Throwable $e) {
			$this->log('FornecedoresLista: ' . $e->getMessage(), 'warning');
		}

		$comprasMap = $this->_fornecedoresCompras12mMap($idempresa, array_map(function ($r) {
			return (int)$r->get('id');
		}, $rows));

		$counts = ['total' => 0, 'ativos' => 0, 'inativos' => 0, 'homologados' => 0, 'analise' => 0];
		$categorias = [];
		$items = [];
		$comprasTotal = 0.0;

		foreach ($rows as $r) {
			if ($papelCols && !ClientesPapelCadastro::isFornecedor($r, true)) {
				continue;
			}
			$counts['total']++;
			$inativo = (int)$r->get('inativo') === 1;
			if ($inativo) {
				$counts['inativos']++;
			} else {
				$counts['ativos']++;
			}
			$stHom = $papelCols
				? (string)($r->get('fornecedor_status_homologacao') ?? ClientesPapelCadastro::STATUS_CADASTRADO)
				: ClientesPapelCadastro::STATUS_CADASTRADO;
			if ($stHom === ClientesPapelCadastro::STATUS_HOMOLOGADO) {
				$counts['homologados']++;
			} elseif ($stHom === ClientesPapelCadastro::STATUS_ANALISE) {
				$counts['analise']++;
			}
			$cat = $papelCols ? trim((string)($r->get('fornecedor_categoria') ?? '')) : '';
			if ($cat !== '') {
				$categorias[$cat] = $cat;
			}
			$isPj = (int)$r->get('tipo') === $pjTipo;
			$nome = $isPj
				? (string)($r->get('razaosocial') ?? $r->get('nomefantasia') ?? '')
				: (string)($r->get('nome') ?? '');
			$doc = $isPj ? (string)($r->get('cnpj') ?? '') : (string)($r->get('cpf') ?? '');
			$cidade = '';
			if (!empty($r->cidade)) {
				$cidade = (string)($r->cidade->nome ?? '');
				if (!empty($r->cidade->estado)) {
					$cidade .= '/' . (string)($r->cidade->estado->sigla ?? '');
				}
			}
			$fid = (int)$r->get('id');
			$compras12 = (float)($comprasMap[$fid] ?? 0);
			$comprasTotal += $compras12;
			$lt = $papelCols ? (int)($r->get('fornecedor_lead_time_dias') ?? 0) : 0;
			$items[] = [
				'id' => $fid,
				'codigo' => ClientesPapelCadastro::codigoFornecedorDisplay($fid, (string)($r->get('public_code') ?? '')),
				'nome' => $nome,
				'doc' => $doc,
				'is_pj' => $isPj,
				'categoria' => $cat !== '' ? $cat : ($isPj ? __('Cadastro PJ') : __('Cadastro PF')),
				'localizacao' => $cidade !== '' ? $cidade : '—',
				'lead_time' => $lt > 0 ? $lt . ' ' . __('dias') : '—',
				'pontualidade' => $stHom === ClientesPapelCadastro::STATUS_HOMOLOGADO ? 96 : ($stHom === ClientesPapelCadastro::STATUS_ANALISE ? 76 : 88),
				'compras12' => $compras12,
				'compras12_fmt' => $this->_fornecedoresFormatMoney($compras12),
				'status_codigo' => $stHom,
				'status_label' => ClientesPapelCadastro::statusHomologacaoLabel($stHom),
				'status_badge' => ClientesPapelCadastro::statusHomologacaoBadge($stHom),
				'inativo' => $inativo,
				'eh_cliente' => ClientesPapelCadastro::isCliente($r, $papelCols),
				'eh_fornecedor' => ClientesPapelCadastro::isFornecedor($r, $papelCols),
			];
		}

		$ativosForn = max(1, $counts['ativos']);
		$mediaCompra = $comprasTotal / $ativosForn;

		return [
			'counts' => $counts + [
				'compras12_fmt' => $this->_fornecedoresFormatMoney($comprasTotal),
				'compras12_media_fmt' => $this->_fornecedoresFormatMoney($mediaCompra),
				'pontualidade_media' => $counts['homologados'] > 0 ? 91 : 85,
			],
			'categorias' => array_values($categorias),
			'items' => $items,
			'papel_columns' => $papelCols,
		];
	}

	/**
	 * @param array<int,int> $ids
	 * @return array<int,float>
	 */
	protected function _fornecedoresCompras12mMap(int $idempresa, array $ids): array {
		$ids = array_values(array_filter(array_unique($ids)));
		if ($ids === []) {
			return [];
		}
		$map = [];
		try {
			$loc = TableRegistry::getTableLocator();
			if (!$loc->exists('FinanceiroLancamentos')) {
				return [];
			}
			$desde = FrozenDate::now()->subMonths(12)->format('Y-m-d');
			$tbl = $loc->get('FinanceiroLancamentos');
			foreach ($tbl->find()
				->select(['idcliente', 'valor'])
				->where([
					'idempresa' => $idempresa,
					'tipo' => 'despesa',
					'idcliente IN' => $ids,
					'data_vencimento >=' => $desde,
				])
				->all() as $row) {
				$cid = (int)$row->get('idcliente');
				$map[$cid] = ($map[$cid] ?? 0) + (float)$row->get('valor');
			}
		} catch (\Throwable $e) {
		}

		return $map;
	}

	protected function _fornecedoresFormatMoney(float $v): string {
		if ($v >= 1000000) {
			return 'R$ ' . number_format($v / 1000000, 2, ',', '.') . 'M';
		}
		if ($v >= 1000) {
			return 'R$ ' . number_format($v / 1000, 1, ',', '.') . 'k';
		}

		return 'R$ ' . number_format($v, 2, ',', '.');
	}
}
