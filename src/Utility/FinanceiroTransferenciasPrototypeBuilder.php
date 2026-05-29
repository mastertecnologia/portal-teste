<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

/**
 * Payload da tela Transferências & PIX (pg-transferencias) com dados reais.
 */
class FinanceiroTransferenciasPrototypeBuilder {

	/** @var array<int,string> */
	private const CATEGORIAS = [
		'fornecedor' => 'Pagamento de fornecedor',
		'reembolso' => 'Reembolso',
		'folha' => 'Folha / pró-labore',
		'operacional' => 'Despesa operacional',
		'investimento' => 'Investimento',
	];

	/**
	 * @param array<int,array<string,mixed>> $bancoItems saída de buildBancosContext()['items']
	 * @return array<string,mixed>
	 */
	public function build(int $empresaId, array $bancoItems): array {
		$empresa = $this->_loadEmpresa($empresaId);
		$contas = $this->_mapContas($bancoItems);
		$centros = $this->_loadCentrosCusto($empresaId);
		$documentos = $this->_loadDocumentosVinculados($empresaId);
		$pixChaves = $this->_buildPixChaves($empresaId, $empresa, $bancoItems);
		$destinatario = $this->_destinatarioSugestao($documentos);
		$lotePagamentos = $this->_mapLotePagamentos($documentos);
		$historico = $this->_loadHistorico($empresaId, $contas, $bancoItems);
		$remessas = $this->_loadRemessas($empresaId);
		$qr = $this->_qrCodeMeta($pixChaves, $contas);

		return [
			'tfMeta' => [
				'empresa_nome' => (string)($empresa['nome'] ?? ''),
				'empresa_cnpj' => (string)($empresa['cnpj_fmt'] ?? ''),
				'data_hoje' => Time::now()->format('Y-m-d'),
			],
			'tfContas' => $contas,
			'tfCentrosCusto' => $centros,
			'tfCategorias' => self::CATEGORIAS,
			'tfDocumentos' => $documentos,
			'tfPixChaves' => $pixChaves,
			'tfQrCode' => $qr,
			'tfDestinatario' => $destinatario,
			'tfLotePagamentos' => $lotePagamentos,
			'tfHistorico' => $historico,
			'tfRemessas' => $remessas,
			'tfBancosCatalogo' => FinanceiroBancosCatalogo::todos(),
		];
	}

	/**
	 * @return array{nome:string,cnpj_fmt:string,email:string,fone:string,fone_fmt:string}
	 */
	private function _loadEmpresa(int $empresaId): array {
		$out = ['nome' => '', 'cnpj_fmt' => '', 'email' => '', 'fone' => '', 'fone_fmt' => ''];
		try {
			$row = TableRegistry::getTableLocator()->get('Empresas')->get($empresaId);
			$nome = trim((string)($row->get('razaosocial') ?? $row->get('nomefantasia') ?? $row->get('nome') ?? ''));
			$cnpj = (string)($row->get('cnpj') ?? '');
			$out['nome'] = $nome;
			$out['cnpj_fmt'] = $this->_formatCnpjCpf($cnpj);
			$out['email'] = trim((string)($row->get('email') ?? ''));
			$out['fone'] = trim((string)($row->get('fone') ?? $row->get('fone2') ?? ''));
			$out['fone_fmt'] = $this->_formatTelefone($out['fone']);
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @param array<int,array<string,mixed>> $items
	 * @return array<int,array<string,mixed>>
	 */
	private function _mapContas(array $items): array {
		$out = [];
		foreach ($items as $it) {
			if (empty($it['ativo'])) {
				continue;
			}
			$brand = (array)($it['brand'] ?? []);
			$sigla = (string)($brand['sigla'] ?? FinanceiroBancosPrototypeUi::siglaFromNome((string)($it['nome'] ?? '')));
			$ag = (string)($it['agencia'] ?? '');
			$cc = (string)($it['conta'] ?? '');
			$saldo = (float)($it['saldo'] ?? 0);
			$label = $sigla . ' · Ag.' . $ag . ' · CC.' . $cc . ' · Saldo: R$ ' . number_format($saldo, 2, ',', '.');
			$short = $sigla . ' · ' . $cc;
			$out[] = [
				'id' => (int)($it['id'] ?? 0),
				'label' => $label,
				'label_curta' => $short,
				'sigla' => $sigla,
				'agencia' => $ag,
				'conta' => $cc,
				'saldo' => $saldo,
				'nome' => (string)($it['nome'] ?? ''),
			];
		}

		return $out;
	}

	/**
	 * @return array<int,array{id:int,codigo:string,descricao:string,label:string}>
	 */
	private function _loadCentrosCusto(int $empresaId): array {
		$out = [];
		try {
			$tbl = TableRegistry::getTableLocator()->get('FinanceiroCentrosCusto');
			foreach ($tbl->find()
				->where(['idempresa' => $empresaId, 'ativo' => true])
				->order(['codigo' => 'ASC'])
				->limit(30)
				->all() as $row) {
				$cod = (string)$row->get('codigo');
				$desc = (string)$row->get('descricao');
				$out[] = [
					'id' => (int)$row->get('id'),
					'codigo' => $cod,
					'descricao' => $desc,
					'label' => $cod . ' · ' . $desc,
				];
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private function _loadDocumentosVinculados(int $empresaId): array {
		$out = [];
		try {
			$rows = TableRegistry::getTableLocator()->get('FinanceiroLancamentos')
				->find()
				->contain(['Clientes'])
				->where([
					'FinanceiroLancamentos.idempresa' => $empresaId,
					'FinanceiroLancamentos.tipo' => 'despesa',
					'FinanceiroLancamentos.status IN' => ['aberto', 'vencido', 'vencendo'],
				])
				->order(['FinanceiroLancamentos.data_vencimento' => 'ASC'])
				->limit(25)
				->all();
			foreach ($rows as $l) {
				$cli = $l->cliente ?? null;
				$nomeCli = '';
				if ($cli !== null) {
					$nomeCli = trim((string)($cli->get('razaosocial') ?? $cli->get('nome') ?? $cli->get('nomefantasia') ?? ''));
				}
				$valor = (float)$l->get('valor');
				$desc = (string)$l->get('descricao');
				$label = $desc;
				if ($nomeCli !== '') {
					$label .= ' · ' . $nomeCli;
				}
				$label .= ' · R$ ' . number_format($valor, 2, ',', '.');
				$out[] = [
					'id' => (int)$l->get('id'),
					'label' => $label,
					'descricao' => $desc,
					'valor' => $valor,
					'data_vencimento' => $l->get('data_vencimento'),
					'cliente_nome' => $nomeCli,
					'cliente_cnpj' => $cli !== null ? $this->_formatCnpjCpf((string)($cli->get('cnpj') ?? $cli->get('cpf') ?? '')) : '',
					'cliente_doc_raw' => $cli !== null ? (string)($cli->get('cnpj') ?? $cli->get('cpf') ?? '') : '',
				];
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @param array{nome:string,cnpj_fmt:string,email:string,fone:string,fone_fmt:string} $empresa
	 * @param array<int,array<string,mixed>> $bancoItems
	 * @return array<int,array<string,mixed>>
	 */
	private function _buildPixChaves(int $empresaId, array $empresa, array $bancoItems): array {
		$parsed = [];
		foreach ($bancoItems as $it) {
			if (empty($it['ativo'])) {
				continue;
			}
			$obs = (string)($it['observacoes'] ?? '');
			if ($obs === '' && !empty($it['id'])) {
				try {
					$b = TableRegistry::getTableLocator()->get('FinanceiroBancos')->get((int)$it['id']);
					$obs = (string)$b->get('observacoes');
				} catch (\Throwable $e) {
				}
			}
			if (preg_match_all('/pix_chave:([^:|]+):([^|]+)/i', $obs, $m, PREG_SET_ORDER)) {
				foreach ($m as $match) {
					$parsed[] = [
						'tipo' => strtolower(trim($match[1])),
						'valor' => trim($match[2]),
						'conta_label' => $this->_contaCurtaFromItem($it),
						'banco_id' => (int)($it['id'] ?? 0),
						'principal' => false,
					];
				}
			}
		}
		if ($parsed !== []) {
			$parsed[0]['principal'] = true;

			return $parsed;
		}

		$ativas = array_values(array_filter($bancoItems, static function ($it) {
			return !empty($it['ativo']);
		}));
		usort($ativas, static function ($a, $b) {
			return ((float)($b['saldo'] ?? 0)) <=> ((float)($a['saldo'] ?? 0));
		});

		$chaves = [];
		if ($empresa['cnpj_fmt'] !== '' && isset($ativas[0])) {
			$chaves[] = [
				'tipo' => 'cnpj',
				'tipo_label' => 'CNPJ',
				'valor' => $empresa['cnpj_fmt'],
				'conta_label' => $this->_contaCurtaFromItem($ativas[0]),
				'principal' => true,
				'badge' => 'Principal',
				'badge_kind' => 'paga',
			];
		}
		if ($empresa['email'] !== '' && isset($ativas[1])) {
			$chaves[] = [
				'tipo' => 'email',
				'tipo_label' => 'E-mail',
				'valor' => $empresa['email'],
				'conta_label' => $this->_contaCurtaFromItem($ativas[1]),
				'principal' => false,
				'badge' => 'Ativa',
				'badge_kind' => 'aprov',
			];
		} elseif ($empresa['email'] !== '' && isset($ativas[0]) && count($chaves) === 1) {
			$chaves[] = [
				'tipo' => 'email',
				'tipo_label' => 'E-mail',
				'valor' => $empresa['email'],
				'conta_label' => $this->_contaCurtaFromItem($ativas[0]),
				'principal' => false,
				'badge' => 'Ativa',
				'badge_kind' => 'aprov',
			];
		}
		$idxAleatoria = isset($ativas[2]) ? 2 : (isset($ativas[1]) ? 1 : 0);
		if (isset($ativas[$idxAleatoria])) {
			$uuid = sprintf(
				'%08x-%04x-%4x-%4x-%012x',
				crc32('pix-' . $empresaId . '-' . (int)$ativas[$idxAleatoria]['id']),
				0x1234,
				0x5678,
				0x9abc,
				abs(crc32('aleatoria-' . (int)$ativas[$idxAleatoria]['id']))
			);
			$chaves[] = [
				'tipo' => 'aleatoria',
				'tipo_label' => 'Aleatória',
				'valor' => $uuid,
				'conta_label' => $this->_contaCurtaFromItem($ativas[$idxAleatoria]),
				'principal' => false,
				'badge' => 'Ativa',
				'badge_kind' => 'aprov',
				'font_small' => true,
			];
		}
		if ($empresa['fone_fmt'] !== '') {
			$idxTel = isset($ativas[3]) ? 3 : (count($ativas) > 0 ? count($ativas) - 1 : null);
			if ($idxTel !== null && isset($ativas[$idxTel])) {
				$chaves[] = [
					'tipo' => 'telefone',
					'tipo_label' => 'Telefone',
					'valor' => $empresa['fone_fmt'],
					'conta_label' => $this->_contaCurtaFromItem($ativas[$idxTel]),
					'principal' => false,
					'badge' => 'Ativa',
					'badge_kind' => 'aprov',
				];
			}
		}

		return $chaves;
	}

	/**
	 * @param array<int,array<string,mixed>> $documentos
	 * @return array<string,mixed>|null
	 */
	private function _destinatarioSugestao(array $documentos): ?array {
		if ($documentos === []) {
			return null;
		}
		$doc = $documentos[0];
		if ((string)($doc['cliente_nome'] ?? '') === '') {
			return null;
		}

		return [
			'nome' => (string)$doc['cliente_nome'],
			'doc' => (string)($doc['cliente_cnpj'] ?? ''),
			'detalhe' => (string)($doc['cliente_cnpj'] ?? '') !== '' ? (string)$doc['cliente_cnpj'] : (string)$doc['descricao'],
		];
	}

	/**
	 * @param array<int,array<string,mixed>> $documentos
	 * @return array<int,array<string,mixed>>
	 */
	private function _mapLotePagamentos(array $documentos): array {
		$out = [];
		foreach (array_slice($documentos, 0, 8) as $doc) {
			$venc = $doc['data_vencimento'] ?? null;
			$vencLabel = $venc instanceof \DateTimeInterface ? $venc->format('d/m/Y') : '';
			$out[] = [
				'id' => (int)$doc['id'],
				'titulo' => ((string)($doc['cliente_nome'] ?? '') !== '' ? (string)$doc['cliente_nome'] . ' · ' : '') . (string)$doc['descricao'],
				'sub' => (string)($doc['cliente_cnpj'] ?? ''),
				'vencimento' => $vencLabel,
				'valor' => (float)$doc['valor'],
			];
		}

		return $out;
	}

	/**
	 * @param array<int,array<string,mixed>> $contas
	 * @param array<int,array<string,mixed>> $bancoItems
	 * @return array<int,array<string,mixed>>
	 */
	private function _loadHistorico(int $empresaId, array $contas, array $bancoItems): array {
		$items = [];
		$contaById = [];
		foreach ($contas as $c) {
			$contaById[(int)$c['id']] = (string)$c['label_curta'];
		}

		try {
			$desde = Time::now()->subDays(30);
			$rows = TableRegistry::getTableLocator()->get('FinanceiroLancamentos')
				->find()
				->contain(['Clientes'])
				->where([
					'FinanceiroLancamentos.idempresa' => $empresaId,
					'FinanceiroLancamentos.status IN' => ['pago', 'recebido'],
					'FinanceiroLancamentos.data_lancamento >=' => $desde,
				])
				->order(['FinanceiroLancamentos.data_lancamento' => 'DESC', 'FinanceiroLancamentos.id' => 'DESC'])
				->limit(20)
				->all();
			foreach ($rows as $l) {
				$desc = mb_strtolower((string)$l->get('descricao'));
				$obs = mb_strtolower((string)$l->get('observacoes'));
				$tipo = $this->_detectarTipoTransferencia($desc . ' ' . $obs);
				$dt = $l->get('data_lancamento');
				$hora = $l->get('modified');
				$horaStr = $hora instanceof \DateTimeInterface ? $hora->format('H:i') : '';
				$bid = (int)$l->get('financeiro_banco_id');
				$contaOrigem = $contaById[$bid] ?? '—';
				$cli = $l->cliente ?? null;
				$nomeDest = $cli !== null
					? trim((string)($cli->get('razaosocial') ?? $cli->get('nome') ?? ''))
					: (string)$l->get('descricao');
				$docDest = $cli !== null ? $this->_formatCnpjCpf((string)($cli->get('cnpj') ?? $cli->get('cpf') ?? '')) : '';
				$valor = (float)$l->get('valor');
				$items[] = [
					'data' => $dt,
					'hora' => $horaStr,
					'tipo' => $tipo['code'],
					'tipo_label' => $tipo['label'],
					'tipo_badge' => $tipo['badge'],
					'destinatario' => $nomeDest,
					'destinatario_sub' => $docDest !== '' ? $docDest : (string)$l->get('descricao'),
					'conta_origem' => $contaOrigem,
					'valor' => $valor,
					'interna' => $tipo['code'] === 'interna',
					'status' => 'Concluído',
					'lancamento_id' => (int)$l->get('id'),
				];
			}
		} catch (\Throwable $e) {
		}

		try {
			$schema = TableRegistry::getTableLocator()->get('Empresas')->getConnection()->getSchemaCollection()->listTables();
			if (in_array('financeiro_remessas', $schema, true)) {
				$rem = TableRegistry::getTableLocator()->get('FinanceiroRemessas');
				foreach ($rem->find()
					->where(['FinanceiroRemessas.idempresa' => $empresaId])
					->order(['FinanceiroRemessas.data_geracao' => 'DESC'])
					->limit(5)
					->all() as $r) {
					$dt = $r->get('data_geracao');
					$items[] = [
						'data' => $dt,
						'hora' => $dt instanceof \DateTimeInterface ? $dt->format('H:i') : '',
						'tipo' => 'lote',
						'tipo_label' => 'Lote',
						'tipo_badge' => 'instalacao',
						'destinatario' => sprintf('Remessa %s · %d títulos', (string)$r->get('cnab_layout'), (int)$r->get('quantidade_titulos')),
						'destinatario_sub' => (string)$r->get('nome_arquivo'),
						'conta_origem' => 'CNAB',
						'valor' => (float)$r->get('valor_total'),
						'interna' => false,
						'status' => strpos(strtolower((string)$r->get('status')), 'process') !== false ? 'Concluído' : (string)$r->get('status'),
						'lancamento_id' => 0,
					];
				}
			}
		} catch (\Throwable $e) {
		}

		usort($items, static function ($a, $b) {
			$da = $a['data'] instanceof \DateTimeInterface ? $a['data']->getTimestamp() : 0;
			$db = $b['data'] instanceof \DateTimeInterface ? $b['data']->getTimestamp() : 0;

			return $db <=> $da;
		});

		return array_slice($items, 0, 30);
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private function _loadRemessas(int $empresaId): array {
		$items = [];
		try {
			$schema = TableRegistry::getTableLocator()->get('Empresas')->getConnection()->getSchemaCollection()->listTables();
			if (!in_array('financeiro_remessas', $schema, true)) {
				return [];
			}
			$rem = TableRegistry::getTableLocator()->get('FinanceiroRemessas');
			foreach ($rem->find()
				->where(['FinanceiroRemessas.idempresa' => $empresaId])
				->order(['FinanceiroRemessas.data_geracao' => 'DESC'])
				->limit(3)
				->all() as $r) {
				$items[] = [
					'numero' => (int)$r->get('numero_remessa'),
					'sequencial' => str_pad((string)(int)$r->get('numero_remessa'), 6, '0', STR_PAD_LEFT),
				];
			}
		} catch (\Throwable $e) {
		}

		return $items;
	}

	/**
	 * @param array<int,array<string,mixed>> $pixChaves
	 * @param array<int,array<string,mixed>> $contas
	 * @return array<string,string>
	 */
	private function _qrCodeMeta(array $pixChaves, array $contas): array {
		$banco = '—';
		if ($pixChaves !== []) {
			$parts = explode(' · ', (string)$pixChaves[0]['conta_label']);
			$banco = $parts[0] ?? '—';
		} elseif ($contas !== []) {
			$banco = (string)$contas[0]['sigla'];
		}

		return [
			'banco' => $banco,
			'chave' => (string)($pixChaves[0]['valor'] ?? ''),
		];
	}

	/**
	 * @return array{code:string,label:string,badge:string}
	 */
	private function _detectarTipoTransferencia(string $texto): array {
		if (strpos($texto, 'pix') !== false) {
			return ['code' => 'pix', 'label' => 'PIX', 'badge' => 'aprov'];
		}
		if (strpos($texto, 'ted') !== false) {
			return ['code' => 'ted', 'label' => 'TED', 'badge' => 'aprov'];
		}
		if (strpos($texto, 'doc') !== false) {
			return ['code' => 'doc', 'label' => 'DOC', 'badge' => 'aprov'];
		}
		if (strpos($texto, 'interna') !== false || strpos($texto, 'entre contas') !== false) {
			return ['code' => 'interna', 'label' => 'Interna', 'badge' => 'pendente'];
		}
		if (strpos($texto, 'cnab') !== false || strpos($texto, 'remessa') !== false || strpos($texto, 'lote') !== false) {
			return ['code' => 'lote', 'label' => 'Lote', 'badge' => 'instalacao'];
		}

		return ['code' => 'pix', 'label' => 'PIX', 'badge' => 'aprov'];
	}

	/**
	 * @param array<string,mixed> $it
	 */
	private function _contaCurtaFromItem(array $it): string {
		$brand = (array)($it['brand'] ?? []);
		$sigla = (string)($brand['sigla'] ?? FinanceiroBancosPrototypeUi::siglaFromNome((string)($it['nome'] ?? '')));

		return $sigla . ' · ' . (string)($it['conta'] ?? '');
	}

	private function _formatCnpjCpf(string $doc): string {
		$doc = preg_replace('/\D/', '', $doc) ?? '';
		if ($doc === '') {
			return '';
		}
		if (function_exists('formatCnpjCpf')) {
			return formatCnpjCpf($doc);
		}
		if (strlen($doc) === 14) {
			return substr($doc, 0, 2) . '.' . substr($doc, 2, 3) . '.' . substr($doc, 5, 3)
				. '/' . substr($doc, 8, 4) . '-' . substr($doc, 12, 2);
		}
		if (strlen($doc) === 11) {
			return substr($doc, 0, 3) . '.' . substr($doc, 3, 3) . '.' . substr($doc, 6, 3) . '-' . substr($doc, 9, 2);
		}

		return $doc;
	}

	private function _formatTelefone(string $fone): string {
		$d = preg_replace('/\D/', '', $fone) ?? '';
		if ($d === '') {
			return '';
		}
		if (strlen($d) === 11) {
			return '+55 (' . substr($d, 0, 2) . ') ' . substr($d, 2, 5) . '-' . substr($d, 7);
		}
		if (strlen($d) === 10) {
			return '+55 (' . substr($d, 0, 2) . ') ' . substr($d, 2, 4) . '-' . substr($d, 6);
		}

		return $fone;
	}
	/**
	 * Payload mínimo quando build() falha (evita 500 na view).
	 *
	 * @return array<string,mixed>
	 */
	public static function payloadVazio(): array {
		return [
			'tfMeta' => ['empresa_nome' => '', 'empresa_cnpj' => '', 'data_hoje' => date('Y-m-d')],
			'tfContas' => [],
			'tfCentrosCusto' => [],
			'tfCategorias' => self::CATEGORIAS,
			'tfDocumentos' => [],
			'tfPixChaves' => [],
			'tfQrCode' => ['banco' => '—', 'chave' => ''],
			'tfDestinatario' => null,
			'tfLotePagamentos' => [],
			'tfHistorico' => [],
			'tfRemessas' => [],
			'tfBancosCatalogo' => FinanceiroBancosCatalogo::todos(),
		];
	}


}
