<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

/**
 * Payload pg-nfe — gestão de NF-e / NFS-e (fiscal_notas).
 */
class FinanceiroNfePrototypeBuilder {

	/**
	 * @param array{tab?:string,busca?:string,modelo?:string,periodo?:string} $filters
	 * @return array<string,mixed>
	 */
	public function build(int $empresaId, array $filters = []): array {
		$now = Time::now();
		$tab = (string)($filters['tab'] ?? 'todas');
		$busca = trim((string)($filters['busca'] ?? ''));
		$modelo = trim((string)($filters['modelo'] ?? ''));
		$monthStart = $now->copy()->startOfMonth();
		$monthEnd = $now->copy()->endOfMonth();

		$kpi = [
			'emitidas_mes' => 0,
			'valor_mes' => 0.0,
			'nfe_qtd' => 0,
			'nfse_qtd' => 0,
			'aguardando' => 0,
			'rejeitadas_24h' => 0,
			'canceladas_mes' => 0,
		];
		$tabCounts = [
			'todas' => 0,
			'autorizadas' => 0,
			'aguardando' => 0,
			'rejeitadas' => 0,
			'canceladas' => 0,
		];
		$items = [];
		$certificado = ['valido' => false, 'label' => '—', 'vence' => ''];
		$proximoNumero = '—';
		$sefaz = ['status' => '—', 'ultima' => '—'];

		try {
			$tbl = TableRegistry::getTableLocator()->get('FiscalNotas');
			$rows = $tbl->find()
				->where(['FiscalNotas.idempresa' => $empresaId])
				->contain([
					'Clientes' => ['fields' => ['id', 'razaosocial', 'nome']],
				])
				->order(['FiscalNotas.data_emissao' => 'DESC'])
				->limit(200)
				->all();

			foreach ($rows as $n) {
				$st = strtolower((string)$n->get('status'));
				$modeloNota = strtoupper((string)$n->get('modelo'));
				$v = (float)$n->get('valor_total');
				$emissao = $n->get('data_emissao');
				$cls = $this->_classifyStatus($st);
				$tabCounts['todas']++;

				if ($emissao instanceof \DateTimeInterface && $emissao >= $monthStart && $emissao <= $monthEnd) {
					$kpi['emitidas_mes']++;
					$kpi['valor_mes'] += $v;
					if ($modeloNota === '55' || $modeloNota === '65') {
						$kpi['nfe_qtd']++;
					} else {
						$kpi['nfse_qtd']++;
					}
				}

				if ($cls['tab'] === 'autorizadas') {
					$tabCounts['autorizadas']++;
				} elseif ($cls['tab'] === 'aguardando') {
					$tabCounts['aguardando']++;
					$kpi['aguardando']++;
				} elseif ($cls['tab'] === 'rejeitadas') {
					$tabCounts['rejeitadas']++;
					if ($emissao instanceof \DateTimeInterface && $emissao >= $now->copy()->subDay()) {
						$kpi['rejeitadas_24h']++;
					}
				} elseif ($cls['tab'] === 'canceladas') {
					$tabCounts['canceladas']++;
					if ($emissao instanceof \DateTimeInterface && $emissao >= $monthStart) {
						$kpi['canceladas_mes']++;
					}
				}

				$cliente = FinanceiroPrototypeSupport::clienteInfo($n->cliente ?? null);
				$numero = (string)$n->get('numero');
				$serie = (string)$n->get('serie');
				$chave = (string)$n->get('chave_acesso');

				if ($busca !== '') {
					$hay = strtolower($numero . ' ' . $cliente['nome'] . ' ' . $chave);
					if (strpos($hay, strtolower($busca)) === false) {
						continue;
					}
				}
				if ($modelo !== '') {
					if ($modelo === '55' && $modeloNota !== '55' && $modeloNota !== '65') {
						continue;
					}
					if ($modelo === 'NFSE' && $modeloNota === '55') {
						continue;
					}
				}
				if ($tab !== 'todas' && $cls['tab'] !== $tab) {
					continue;
				}

				$tipoLabel = ($modeloNota === '55' || $modeloNota === '65') ? 'NF-e' : 'NFS-e';
				$tipoBadge = ($modeloNota === '55' || $modeloNota === '65') ? 'aprov' : 'nfse';

				$items[] = [
					'id' => (int)$n->get('id'),
					'numero' => $numero,
					'serie' => $serie,
					'cliente' => $cliente['nome'],
					'tipo_label' => $tipoLabel,
					'tipo_badge' => $tipoBadge,
					'emissao' => $emissao,
					'valor' => $v,
					'chave' => $chave,
					'chave_curta' => $this->_chaveCurta($chave, $cls['tab']),
					'status' => $cls,
					'motivo_rejeicao' => (string)$n->get('motivo_rejeicao'),
					'view_url' => ['controller' => 'FiscalNotas', 'action' => 'view', (int)$n->get('id')],
				];
			}

			$ultima = $tbl->find()
				->where(['FiscalNotas.idempresa' => $empresaId, 'FiscalNotas.modelo' => '55'])
				->order(['FiscalNotas.numero' => 'DESC'])
				->select(['numero'])
				->first();
			if ($ultima !== null) {
				$seq = (int)preg_replace('/\D/', '', (string)$ultima->get('numero'));
				$proximoNumero = str_pad((string)($seq + 1), 3, '0', STR_PAD_LEFT) . '.' . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
			}
		} catch (\Throwable $e) {
		}

		try {
			$cfg = TableRegistry::getTableLocator()->get('FiscalConfiguracoes')
				->find()
				->where(['idempresa' => $empresaId])
				->first();
			if ($cfg !== null) {
				$validade = $cfg->get('certificado_validade');
				if ($validade instanceof \DateTimeInterface) {
					$certificado = [
						'valido' => $validade >= $now,
						'label' => (string)($cfg->get('certificado_titular') ?? 'Certificado A1'),
						'vence' => $validade->format('d/m/Y'),
					];
				}
			}
		} catch (\Throwable $e) {
		}

		$sefaz = [
			'status' => 'SEFAZ operando normalmente',
			'ultima' => $now->format('d/m/Y H:i'),
		];

		$pctNfe = $kpi['emitidas_mes'] > 0
			? round(($kpi['nfe_qtd'] / $kpi['emitidas_mes']) * 100, 1)
			: 0.0;

		return [
			'nfeKpi' => $kpi,
			'nfeKpiPctNfe' => $pctNfe,
			'nfeKpiPctNfse' => $kpi['emitidas_mes'] > 0 ? round(100 - $pctNfe, 1) : 0.0,
			'nfeTabCounts' => $tabCounts,
			'nfeItems' => array_slice($items, 0, 80),
			'nfeFiltros' => ['tab' => $tab, 'busca' => $busca, 'modelo' => $modelo],
			'nfeCertificado' => $certificado,
			'nfeProximoNumero' => $proximoNumero,
			'nfeSefaz' => $sefaz,
		];
	}

	/**
	 * @return array{tab:string,badge:string,label:string,row_bg:string,strike:bool}
	 */
	private function _classifyStatus(string $st): array {
		if (strpos($st, 'autoriz') !== false) {
			return ['tab' => 'autorizadas', 'badge' => 'paga', 'label' => '✓ Autorizada', 'row_bg' => '', 'strike' => false];
		}
		if (strpos($st, 'rejeit') !== false) {
			return ['tab' => 'rejeitadas', 'badge' => 'recus', 'label' => '⚠ Rejeitada', 'row_bg' => '#FEF2F2', 'strike' => false];
		}
		if (strpos($st, 'cancel') !== false) {
			return ['tab' => 'canceladas', 'badge' => 'arq', 'label' => '✗ Cancelada', 'row_bg' => '#EDE9F8', 'strike' => true];
		}
		if (strpos($st, 'pend') !== false || strpos($st, 'process') !== false || strpos($st, 'aguard') !== false) {
			return ['tab' => 'aguardando', 'badge' => 'vencendo', 'label' => '⏰ Aguardando', 'row_bg' => '#FFFBF0', 'strike' => false];
		}

		return ['tab' => 'autorizadas', 'badge' => 'paga', 'label' => ucfirst($st ?: '—'), 'row_bg' => '', 'strike' => false];
	}

	private function _chaveCurta(string $chave, string $tab): string {
		if ($tab === 'aguardando') {
			return 'processando...';
		}
		if ($chave === '') {
			return '—';
		}
		if (strlen($chave) <= 12) {
			return $chave;
		}

		return substr($chave, 0, 4) . '...' . substr($chave, -4);
	}
}
