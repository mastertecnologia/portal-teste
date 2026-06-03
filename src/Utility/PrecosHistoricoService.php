<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;

/**
 * Registro e consulta do histórico de alterações de preço.
 */
class PrecosHistoricoService {

	/**
	 * @param array<string,mixed> $ctx
	 */
	public static function registrar(array $ctx): bool {
		try {
			$Hist = TableRegistry::getTableLocator()->get('PrecosHistorico');
		} catch (\Throwable $e) {
			return false;
		}
		$empresaId = (int)($ctx['idempresa'] ?? 0);
		if ($empresaId <= 0) {
			return false;
		}
		$anterior = isset($ctx['preco_anterior']) ? (float)$ctx['preco_anterior'] : null;
		$novo = (float)($ctx['preco_novo'] ?? 0);
		$variacao = self::calcVariacaoPct($anterior, $novo);
		$tipo = (string)($ctx['tipo'] ?? self::inferTipo($anterior, $novo, $variacao));
		$custo = isset($ctx['custo_na_epoca']) ? (float)$ctx['custo_na_epoca'] : null;
		$margem = null;
		if ($custo !== null && $custo > 0 && $novo > 0) {
			$margem = round((1 - ($custo / $novo)) * 100, 2);
		}
		$row = $Hist->newEntity([
			'idempresa' => $empresaId,
			'produto_id' => !empty($ctx['produto_id']) ? (int)$ctx['produto_id'] : null,
			'precos_tabela_id' => !empty($ctx['precos_tabela_id']) ? (int)$ctx['precos_tabela_id'] : null,
			'codigo_produto' => (string)($ctx['codigo_produto'] ?? ''),
			'descricao_produto' => (string)($ctx['descricao_produto'] ?? ''),
			'tabela_nome' => (string)($ctx['tabela_nome'] ?? ''),
			'preco_anterior' => $anterior,
			'preco_novo' => $novo,
			'variacao_pct' => $variacao,
			'tipo' => $tipo,
			'motivo' => (string)($ctx['motivo'] ?? ''),
			'user_id' => !empty($ctx['user_id']) ? (int)$ctx['user_id'] : null,
			'autor_nome' => (string)($ctx['autor_nome'] ?? ''),
			'custo_na_epoca' => $custo,
			'margem_apos' => $margem,
			'ip_origem' => (string)($ctx['ip_origem'] ?? ''),
			'created' => FrozenTime::now(),
		]);

		return (bool)$Hist->save($row);
	}

	protected static function calcVariacaoPct(?float $anterior, float $novo): ?float {
		if ($anterior === null || $anterior <= 0) {
			return null;
		}

		return round((($novo / $anterior) - 1) * 100, 4);
	}

	protected static function inferTipo(?float $anterior, float $novo, ?float $variacao): string {
		if ($anterior === null || $anterior <= 0) {
			return 'criacao';
		}
		if ($variacao === null) {
			return 'ajuste';
		}
		if ($variacao > 0.05) {
			return 'aumento';
		}
		if ($variacao < -0.05) {
			return 'reducao';
		}

		return 'promocao';
	}

	/**
	 * @param array<string,mixed> $query
	 * @return array<int,array<string,mixed>>
	 */
	public static function listar(int $empresaId, array $query = [], int $limite = 100): array {
		try {
			$Hist = TableRegistry::getTableLocator()->get('PrecosHistorico');
		} catch (\Throwable $e) {
			return [];
		}
		$busca = trim((string)($query['q'] ?? ''));
		$tipoFiltro = (string)($query['tipo'] ?? '');
		$tabelaId = (int)($query['tabela'] ?? 0);
		$desde = trim((string)($query['desde'] ?? ''));
		$ate = trim((string)($query['ate'] ?? ''));

		$q = $Hist->find()
			->where(['PrecosHistorico.idempresa' => $empresaId])
			->order(['PrecosHistorico.created' => 'DESC'])
			->limit($limite);

		if ($busca !== '') {
			$q->where([
				'OR' => [
					'PrecosHistorico.codigo_produto ILIKE' => '%' . $busca . '%',
					'PrecosHistorico.descricao_produto ILIKE' => '%' . $busca . '%',
					'PrecosHistorico.motivo ILIKE' => '%' . $busca . '%',
					'PrecosHistorico.autor_nome ILIKE' => '%' . $busca . '%',
				],
			]);
		}
		if ($tipoFiltro !== '' && $tipoFiltro !== 'todos') {
			$q->where(['PrecosHistorico.tipo' => $tipoFiltro]);
		}
		if ($tabelaId > 0) {
			$q->where(['PrecosHistorico.precos_tabela_id' => $tabelaId]);
		}
		if ($desde !== '') {
			$q->where(['PrecosHistorico.created >=' => $desde . ' 00:00:00']);
		}
		if ($ate !== '') {
			$q->where(['PrecosHistorico.created <=' => $ate . ' 23:59:59']);
		}

		$out = [];
		foreach ($q->all() as $row) {
			$out[] = self::formatRow($row);
		}

		return $out;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public static function obter(int $empresaId, int $id): ?array {
		try {
			$Hist = TableRegistry::getTableLocator()->get('PrecosHistorico');
			$row = $Hist->find()
				->where(['PrecosHistorico.id' => $id, 'PrecosHistorico.idempresa' => $empresaId])
				->first();
			if ($row === null) {
				return null;
			}

			return self::formatRow($row);
		} catch (\Throwable $e) {
			return null;
		}
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function timelineProduto(int $empresaId, int $produtoId, int $limite = 10): array {
		if ($produtoId <= 0) {
			return [];
		}
		try {
			$Hist = TableRegistry::getTableLocator()->get('PrecosHistorico');
			$out = [];
			foreach ($Hist->find()
				->where([
					'PrecosHistorico.idempresa' => $empresaId,
					'PrecosHistorico.produto_id' => $produtoId,
				])
				->order(['PrecosHistorico.created' => 'DESC'])
				->limit($limite)
				->all() as $row) {
				$out[] = self::formatRow($row);
			}

			return $out;
		} catch (\Throwable $e) {
			return [];
		}
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function kpi30d(int $empresaId): array {
		$desde = FrozenTime::now()->subDays(30);
		$defaults = [
			'alteracoes_30d' => 0,
			'aumentos' => 0,
			'reducoes' => 0,
			'promocoes' => 0,
			'reajuste_medio' => '—',
			'proximo_reajuste' => FrozenTime::now()->addMonths(6)->format('d/m/Y'),
		];
		try {
			$Hist = TableRegistry::getTableLocator()->get('PrecosHistorico');
			$rows = $Hist->find()
				->where([
					'PrecosHistorico.idempresa' => $empresaId,
					'PrecosHistorico.created >=' => $desde,
				])
				->all();
			$somaPct = 0.0;
			$cntPct = 0;
			foreach ($rows as $r) {
				$defaults['alteracoes_30d']++;
				$tipo = (string)$r->get('tipo');
				if ($tipo === 'aumento' || $tipo === 'massa') {
					$defaults['aumentos']++;
				} elseif ($tipo === 'reducao') {
					$defaults['reducoes']++;
				} elseif ($tipo === 'promocao') {
					$defaults['promocoes']++;
				}
				$vp = $r->get('variacao_pct');
				if ($vp !== null && (float)$vp !== 0.0) {
					$somaPct += (float)$vp;
					$cntPct++;
				}
			}
			if ($cntPct > 0) {
				$med = $somaPct / $cntPct;
				$defaults['reajuste_medio'] = ($med >= 0 ? '+' : '') . number_format($med, 1, ',', '.') . '%';
			}
		} catch (\Throwable $e) {
		}

		return $defaults;
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $row
	 * @return array<string,mixed>
	 */
	protected static function formatRow($row): array {
		$created = $row->get('created');
		$anterior = $row->get('preco_anterior');
		$novo = (float)$row->get('preco_novo');
		$variacao = $row->get('variacao_pct');
		$tipo = (string)$row->get('tipo');

		return [
			'id' => (int)$row->get('id'),
			'data' => $created instanceof FrozenTime ? $created : FrozenTime::now(),
			'produto_id' => (int)($row->get('produto_id') ?? 0),
			'codigo' => (string)$row->get('codigo_produto'),
			'descricao' => (string)$row->get('descricao_produto'),
			'tabela' => (string)$row->get('tabela_nome'),
			'preco_anterior' => $anterior !== null ? (float)$anterior : null,
			'preco_novo' => $novo,
			'variacao_pct' => $variacao !== null ? (float)$variacao : null,
			'tipo' => $tipo,
			'motivo' => (string)$row->get('motivo'),
			'autor' => (string)$row->get('autor_nome'),
			'custo_na_epoca' => $row->get('custo_na_epoca') !== null ? (float)$row->get('custo_na_epoca') : null,
			'margem_apos' => $row->get('margem_apos') !== null ? (float)$row->get('margem_apos') : null,
			'ip_origem' => (string)$row->get('ip_origem'),
			'seta' => self::setaLabel($tipo, $variacao !== null ? (float)$variacao : null),
			'row_bg' => self::rowBg($tipo, $variacao !== null ? (float)$variacao : null),
		];
	}

	protected static function setaLabel(string $tipo, ?float $variacao): string {
		if ($tipo === 'massa' || $tipo === 'criacao') {
			return '★';
		}
		if ($variacao === null) {
			return '↔';
		}
		if ($variacao > 0.05) {
			return '↑';
		}
		if ($variacao < -0.05) {
			return '↓';
		}

		return '↔';
	}

	protected static function rowBg(string $tipo, ?float $variacao): string {
		if ($tipo === 'massa') {
			return '#fff';
		}
		if ($variacao !== null && $variacao < -0.05) {
			return '#FEF2F2';
		}
		if ($tipo === 'promocao') {
			return '#EDE9F8';
		}

		return '#F0FDF4';
	}
}
