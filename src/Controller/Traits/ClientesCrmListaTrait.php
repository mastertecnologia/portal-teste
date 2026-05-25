<?php
declare(strict_types=1);

namespace App\Controller\Traits;

use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;

/**
 * KPIs, linhas CRM e helpers da lista de clientes (legado + protótipo).
 */
trait ClientesCrmListaTrait {
	protected function _clientesIndexVendedoresLista() {
		$out = [];
		$idempresa = (int)$this->Auth->user('idempresa');
		try {
			$this->loadModel('Orcamentos');
			$rows = $this->Orcamentos->find()
				->select(['Users.id', 'Users.name'])
				->distinct(['Users.id'])
				->contain(['Users'])
				->where([
					'Orcamentos.idempresa' => $idempresa,
					'Orcamentos.idautor IS NOT' => null,
				])
				->order(['Users.name' => 'ASC'])
				->limit(200)
				->all();
			foreach ($rows as $r) {
				if (!empty($r->user) && !empty($r->user->id)) {
					$out[(int)$r->user->id] = trim((string)($r->user->name ?? ''));
				}
			}
		} catch (\Throwable $e) {
		}
		if ($out === []) {
			try {
				$this->loadModel('Users');
				$q = $this->Users->find('list', ['keyField' => 'id', 'valueField' => 'name'])
					->where(['Users.role' => 0, 'Users.inativo' => 0])
					->order(['Users.name' => 'ASC'])
					->limit(200);
				$out = $q->toArray();
			} catch (\Throwable $e) {
			}
		}

		return $out;
	}

	/**
	 * Último autor de orçamento por cliente (vendedor para filtro da lista).
	 *
	 * @return array<int,int>
	 */
	protected function _clientesVendedorPorCliente() {
		$map = [];
		try {
			$this->loadModel('Orcamentos');
			$idempresa = (int)$this->Auth->user('idempresa');
			$rows = $this->Orcamentos->find()
				->select(['Orcamentos.idcliente', 'Orcamentos.idautor'])
				->where([
					'Orcamentos.idempresa' => $idempresa,
					'Orcamentos.idcliente IS NOT' => null,
					'Orcamentos.idautor IS NOT' => null,
				])
				->order(['Orcamentos.id' => 'DESC'])
				->limit(8000)
				->all();
			foreach ($rows as $r) {
				$cid = (int)$r->idcliente;
				$aid = (int)$r->idautor;
				if ($cid > 0 && $aid > 0 && !isset($map[$cid])) {
					$map[$cid] = $aid;
				}
			}
		} catch (\Throwable $e) {
		}

		return $map;
	}

	/**
	 * Classifica segmento de mercado por nome/fantasia (heurística — sem coluna segmento no cadastro).
	 *
	 * @param \App\Model\Entity\Cliente $cliente
	 * @return array{slug:string,label:string,short:string,tone:string}
	 */
	protected function _clientesClassificarSegmento($cliente) {
		$blob = mb_strtolower(trim(implode(' ', array_filter([
			(string)($cliente->razaosocial ?? ''),
			(string)($cliente->nomefantasia ?? ''),
			(string)($cliente->nome ?? ''),
		]))), 'UTF-8');
		$rules = [
			['slug' => 'moveis', 'label' => __('Móveis & Decoração'), 'short' => __('Móveis'), 'tone' => 'teal', 'words' => ['móvel', 'moveis', 'moble', 'decora', 'ambiente', 'marcen', 'móveis']],
			['slug' => 'saude', 'label' => __('Saúde & Estética'), 'short' => __('Saúde'), 'tone' => 'blue', 'words' => ['saúde', 'saude', 'clín', 'clin', 'estét', 'estet', 'hospital', 'biosseg', 'medic', 'laborat']],
			['slug' => 'imob', 'label' => __('Imobiliário'), 'short' => __('Imobiliário'), 'tone' => 'rose', 'words' => ['imob', 'imóvel', 'imovel', 'corretor', 'incorp']],
			['slug' => 'industria', 'label' => __('Indústria'), 'short' => __('Indústria'), 'tone' => 'orange', 'words' => ['indúst', 'indust', 'fabri', 'metalúrg', 'metalurg', 'plást', 'plastic']],
		];
		foreach ($rules as $rule) {
			foreach ($rule['words'] as $w) {
				if ($w !== '' && mb_strpos($blob, $w, 0, 'UTF-8') !== false) {
					return [
						'slug' => $rule['slug'],
						'label' => $rule['label'],
						'short' => $rule['short'],
						'tone' => $rule['tone'],
					];
				}
			}
		}

		return ['slug' => 'outros', 'label' => __('Outros'), 'short' => __('Outros'), 'tone' => 'purple'];
	}

	/**
	 * @param \App\Model\Entity\Cliente[] $todos
	 * @return array<int,array{slug:string,label:string,count:int,pct:int,tone:string}>
	 */
	protected function _clientesSegmentosDistribuicao(array $todos) {
		$defs = [
			'moveis' => ['label' => __('Móveis & Decoração'), 'tone' => 'teal'],
			'saude' => ['label' => __('Saúde & Estética'), 'tone' => 'blue'],
			'imob' => ['label' => __('Imobiliário'), 'tone' => 'rose'],
			'industria' => ['label' => __('Indústria'), 'tone' => 'orange'],
			'outros' => ['label' => __('Outros'), 'tone' => 'purple'],
		];
		$counts = array_fill_keys(array_keys($defs), 0);
		foreach ($todos as $c) {
			if ((int)$c->inativo === 1) {
				continue;
			}
			$seg = $this->_clientesClassificarSegmento($c);
			$slug = $seg['slug'];
			if (!isset($counts[$slug])) {
				$counts['outros']++;
			} else {
				$counts[$slug]++;
			}
		}
		$total = array_sum($counts);
		$out = [];
		foreach ($defs as $slug => $meta) {
			$cnt = (int)($counts[$slug] ?? 0);
			$out[] = [
				'slug' => $slug,
				'label' => $meta['label'],
				'count' => $cnt,
				'pct' => $total > 0 ? (int)round(100 * $cnt / $total) : 0,
				'tone' => $meta['tone'],
			];
		}

		return $out;
	}

	/**
	 * Linhas da tabela CRM com métricas por cliente.
	 *
	 * @param \App\Model\Entity\Cliente[] $todos
	 * @param array<string,mixed> $crm
	 * @return array<int,array<string,mixed>>
	 */
	protected function _clientesIndexRows(array $todos, array $crm) {
		$receitaPorCliente = (array)($crm['receita_por_cliente'] ?? []);
		$aReceberPorCliente = (array)($crm['a_receber_por_cliente'] ?? []);
		$atrasoDias = (array)($crm['atraso_dias_por_cliente'] ?? []);
		$ultimaOs = (array)($crm['ultima_os_por_cliente'] ?? []);
		$vipIds = (array)($crm['vip_ids'] ?? []);
		$top10Ids = (array)($crm['top10_ids'] ?? []);
		$vendedorPorCliente = (array)($crm['vendedor_por_cliente'] ?? []);
		$cutoffContato = (new \DateTimeImmutable('today'))->modify('-30 days');
		$mesAtual = (int)date('n');
		$anoAtual = (int)date('Y');
		$avTones = ['teal', 'blue', 'rose', 'orange', 'purple', 'navy', 'wine'];
		$rows = [];
		foreach ($todos as $reg) {
			$cid = (int)$reg->id;
			$isPj = (int)$reg->tipo === (int)C_ClientesTipoJuridica;
			$nome = $this->_clientesIndexNomeExibicao($reg);
			$doc = $isPj ? (string)($reg->cnpj ?? '') : (string)($reg->cpf ?? '');
			$seg = $this->_clientesClassificarSegmento($reg);
			$contato = trim((string)($reg->nomeresponsavel ?? ''));
			if ($contato === '' && !$isPj) {
				$contato = trim((string)($reg->nome ?? ''));
			}
			$email = trim((string)($reg->email ?? ''));
			$sub = $contato;
			if ($email !== '') {
				$sub = $sub !== '' ? $sub . ' (' . $email . ')' : $email;
			}
			$cidadeDisplay = '';
			if (!empty($reg->cidade) && !empty($reg->cidade->nome)) {
				$cidadeDisplay = (string)$reg->cidade->nome;
				$uf = '';
				if (!empty($reg->cidade->estado) && !empty($reg->cidade->estado->sigla)) {
					$uf = strtoupper(trim((string)$reg->cidade->estado->sigla));
				}
				if ($uf !== '') {
					$cidadeDisplay .= '/' . $uf;
				}
			}
			$codigo = trim((string)($reg->public_code ?? ''));
			if ($codigo === '') {
				$codigo = '—';
			}
			$rec12 = isset($receitaPorCliente[$cid]) ? (float)$receitaPorCliente[$cid] : 0.0;
			$aRec = isset($aReceberPorCliente[$cid]) ? (float)$aReceberPorCliente[$cid] : 0.0;
			$diasAtraso = (int)($atrasoDias[$cid] ?? 0);
			$ultima = (string)($ultimaOs[$cid] ?? '');
			if ($ultima === '' && !empty($reg->membrodesde) && $reg->membrodesde instanceof \DateTimeInterface) {
				$ultima = $reg->membrodesde->format('d/m/Y');
			}
			if ($ultima === '') {
				$ultima = '—';
			}
			$statusUi = 'ativo';
			$statusLabel = __('Ativo');
			$statusClass = 'on';
			if ((int)$reg->inativo === 1) {
				$statusUi = 'inativo';
				$statusLabel = __('Bloqueado');
				$statusClass = 'blocked';
			} elseif ($diasAtraso > 0) {
				$statusUi = 'atraso';
				$statusLabel = __('Atraso {0}d', $diasAtraso);
				$statusClass = 'warn';
			} elseif (isset($vipIds[$cid])) {
				$statusUi = 'vip';
				$statusLabel = __('VIP · Ativo');
				$statusClass = 'vip';
			}
			$isNovo = false;
			$isAniv = false;
			if (!empty($reg->membrodesde) && $reg->membrodesde instanceof \DateTimeInterface) {
				$isNovo = ((int)$reg->membrodesde->format('n') === $mesAtual && (int)$reg->membrodesde->format('Y') === $anoAtual);
				$isAniv = ((int)$reg->membrodesde->format('n') === $mesAtual);
			}
			$semContato = 0;
			if ((int)$reg->inativo === 0) {
				$ultimaDt = null;
				if ($ultima !== '' && $ultima !== '—') {
					$ultimaDt = \DateTime::createFromFormat('d/m/Y', $ultima) ?: null;
				}
				if ($ultimaDt === null || $ultimaDt < $cutoffContato) {
					$semContato = 1;
				}
			}
			$rows[] = [
				'entity' => $reg,
				'id' => $cid,
				'codigo' => $codigo,
				'nome' => $nome,
				'subline' => $sub,
				'doc' => $doc,
				'segmento' => $seg,
				'cidade' => $cidadeDisplay,
				'av_tone' => $avTones[$cid % count($avTones)],
				'receita12' => $rec12,
				'a_receber' => $aRec,
				'status_ui' => $statusUi,
				'status_label' => $statusLabel,
				'status_class' => $statusClass,
				'ultima' => $ultima,
				'is_pj' => $isPj,
				'status_key' => (int)$reg->inativo === 1 ? 'inativos' : 'ativos',
				'tipo_key' => $isPj ? 'pj' : 'pf',
				'atraso' => $diasAtraso > 0 ? 1 : 0,
				'vip' => isset($vipIds[$cid]) ? 1 : 0,
				'segmento_slug' => $seg['slug'],
				'novo_mes' => $isNovo ? 1 : 0,
				'aniversariante' => $isAniv ? 1 : 0,
				'top_receita' => isset($top10Ids[$cid]) ? 1 : 0,
				'sem_contato' => $semContato,
				'vendedor_id' => (int)($vendedorPorCliente[$cid] ?? 0),
			];
		}

		return $rows;
	}

	/**
	 * KPIs e painéis da lista CRM (receita / inadimplência via financeiro_lancamentos quando existir).
	 *
	 * @param \App\Model\Entity\Cliente[] $todos
	 * @param int $cntAtivos
	 * @return array<string,mixed>
	 */
	protected function _clientesIndexCrmMetrics(array $todos, int $cntAtivos) {
		$cntTotal = count($todos);
		$cntPj = 0;
		$cntPf = 0;
		foreach ($todos as $c) {
			if ((int)$c->tipo === (int)C_ClientesTipoJuridica) {
				$cntPj++;
			} elseif ((int)$c->tipo === (int)C_ClientesTipoFisica) {
				$cntPf++;
			}
		}
		$cntInativos = $cntTotal - $cntAtivos;

		$receita12 = 0.0;
		$receitaPrev = 0.0;
		$inadValor = 0.0;
		$inadClientes = 0;
		$top5 = [];
		$receitaPorCliente = [];
		$aReceberPorCliente = [];
		$atrasoDiasPorCliente = [];
		$ultimaOsPorCliente = [];
		$hasFin = false;
		$novosMes = 0;
		$aniversariantes = 0;
		$mesAtual = (int)date('n');
		$anoAtual = (int)date('Y');
		foreach ($todos as $c) {
			if (!empty($c->membrodesde) && $c->membrodesde instanceof \DateTimeInterface) {
				$m = (int)$c->membrodesde->format('n');
				$y = (int)$c->membrodesde->format('Y');
				if ($m === $mesAtual && $y === $anoAtual) {
					$novosMes++;
				}
				if ($m === $mesAtual) {
					$aniversariantes++;
				}
			}
		}

		try {
			$finTable = TableRegistry::getTableLocator()->get('FinanceiroLancamentos');
			$hasFin = true;
			$idempresa = (int)$this->Auth->user('idempresa');
			$hoje = FrozenDate::today();
			$ini12 = $hoje->subMonths(12);
			$iniPrev = $ini12->subMonths(12);
			$fimPrev = $ini12->subDay(1);

			$qRec = $finTable->find();
			$qRec->select(['s' => $qRec->func()->sum('FinanceiroLancamentos.valor')])
				->where([
					'FinanceiroLancamentos.idempresa' => $idempresa,
					'FinanceiroLancamentos.tipo' => 'receita',
					'FinanceiroLancamentos.data_lancamento >=' => $ini12->format('Y-m-d'),
					'FinanceiroLancamentos.data_lancamento <=' => $hoje->format('Y-m-d'),
				]);
			$rowRec = $qRec->first();
			$receita12 = $rowRec && $rowRec->s !== null ? (float)$rowRec->s : 0.0;

			$qPrev = $finTable->find();
			$qPrev->select(['s' => $qPrev->func()->sum('FinanceiroLancamentos.valor')])
				->where([
					'FinanceiroLancamentos.idempresa' => $idempresa,
					'FinanceiroLancamentos.tipo' => 'receita',
					'FinanceiroLancamentos.data_lancamento >=' => $iniPrev->format('Y-m-d'),
					'FinanceiroLancamentos.data_lancamento <=' => $fimPrev->format('Y-m-d'),
				]);
			$rowPrev = $qPrev->first();
			$receitaPrev = $rowPrev && $rowPrev->s !== null ? (float)$rowPrev->s : 0.0;

			$qInad = $finTable->find();
			$qInad->select([
				'FinanceiroLancamentos.idcliente',
				'FinanceiroLancamentos.data_vencimento',
				'valor' => 'FinanceiroLancamentos.valor',
			])
				->where([
					'FinanceiroLancamentos.idempresa' => $idempresa,
					'FinanceiroLancamentos.tipo' => 'receita',
					'FinanceiroLancamentos.status' => 'aberto',
					'FinanceiroLancamentos.data_vencimento IS NOT' => null,
					'FinanceiroLancamentos.data_vencimento <' => $hoje->format('Y-m-d'),
				]);
			$inadIds = [];
			$hojeStr = $hoje->format('Y-m-d');
			foreach ($qInad->all() as $inadRow) {
				$cidInad = (int)$inadRow->idcliente;
				$inadIds[$cidInad] = true;
				$inadValor += (float)($inadRow->valor ?? 0);
				if (!empty($inadRow->data_vencimento)) {
					$dv = $inadRow->data_vencimento instanceof \DateTimeInterface
						? $inadRow->data_vencimento->format('Y-m-d')
						: (string)$inadRow->data_vencimento;
					try {
						$d1 = new \DateTimeImmutable($dv);
						$d2 = new \DateTimeImmutable($hojeStr);
						$diff = (int)$d1->diff($d2)->days;
						if (!isset($atrasoDiasPorCliente[$cidInad]) || $diff > $atrasoDiasPorCliente[$cidInad]) {
							$atrasoDiasPorCliente[$cidInad] = $diff;
						}
					} catch (\Throwable $e) {
					}
				}
			}
			$inadClientes = count($inadIds);

			$qTop = $finTable->find();
			$qTop->select([
				'FinanceiroLancamentos.idcliente',
				'total' => $qTop->func()->sum('FinanceiroLancamentos.valor'),
			])
				->where([
					'FinanceiroLancamentos.idempresa' => $idempresa,
					'FinanceiroLancamentos.tipo' => 'receita',
					'FinanceiroLancamentos.data_lancamento >=' => $ini12->format('Y-m-d'),
				])
				->group(['FinanceiroLancamentos.idcliente'])
				->order(['total' => 'DESC'])
				->limit(5);
			$topRows = $qTop->all()->toArray();

			$nomePorId = [];
			foreach ($todos as $c) {
				$nomePorId[(int)$c->id] = $this->_clientesIndexNomeExibicao($c);
			}
			foreach ($topRows as $tr) {
				$cid = (int)$tr->idcliente;
				$val = (float)($tr->total ?? 0);
				$top5[] = [
					'id' => $cid,
					'nome' => $nomePorId[$cid] ?? ('#' . $cid),
					'valor' => $val,
					'pct' => $receita12 > 0 ? (int)round(100 * $val / $receita12) : 0,
				];
			}

			$qPorCli = $finTable->find();
			$qPorCli->select([
				'FinanceiroLancamentos.idcliente',
				'total' => $qPorCli->func()->sum('FinanceiroLancamentos.valor'),
			])
				->where([
					'FinanceiroLancamentos.idempresa' => $idempresa,
					'FinanceiroLancamentos.tipo' => 'receita',
					'FinanceiroLancamentos.data_lancamento >=' => $ini12->format('Y-m-d'),
				])
				->group(['FinanceiroLancamentos.idcliente']);
			foreach ($qPorCli->all() as $pc) {
				$receitaPorCliente[(int)$pc->idcliente] = (float)($pc->total ?? 0);
			}

			$qAberto = $finTable->find();
			$qAberto->select([
				'FinanceiroLancamentos.idcliente',
				'total' => $qAberto->func()->sum('FinanceiroLancamentos.valor'),
			])
				->where([
					'FinanceiroLancamentos.idempresa' => $idempresa,
					'FinanceiroLancamentos.tipo' => 'receita',
					'FinanceiroLancamentos.status' => 'aberto',
				])
				->group(['FinanceiroLancamentos.idcliente']);
			foreach ($qAberto->all() as $ab) {
				$aReceberPorCliente[(int)$ab->idcliente] = (float)($ab->total ?? 0);
			}
		} catch (\Throwable $e) {
			$this->log('Clientes::index CRM financeiro: ' . $e->getMessage(), 'warning');
		}

		try {
			$this->loadModel('Ordensservico');
			$idempresa = (int)$this->Auth->user('idempresa');
			$osRows = $this->Ordensservico->find()
				->select(['Ordensservico.idcliente', 'Ordensservico.dataabertura'])
				->where(['Ordensservico.idempresa' => $idempresa])
				->order(['Ordensservico.dataabertura' => 'DESC'])
				->limit(5000)
				->all();
			foreach ($osRows as $os) {
				$cidOs = (int)$os->idcliente;
				if ($cidOs <= 0 || isset($ultimaOsPorCliente[$cidOs])) {
					continue;
				}
				$raw = $os->dataabertura;
				if ($raw instanceof \DateTimeInterface) {
					$ultimaOsPorCliente[$cidOs] = $raw->format('d/m/Y');
				} elseif (is_string($raw) && trim($raw) !== '') {
					$ultimaOsPorCliente[$cidOs] = trim($raw);
				}
			}
		} catch (\Throwable $e) {
		}

		$vipIds = [];
		$top10Ids = [];
		if ($receitaPorCliente !== []) {
			arsort($receitaPorCliente);
			$iTop = 0;
			foreach (array_keys($receitaPorCliente) as $tid) {
				if ($iTop >= 10) {
					break;
				}
				$top10Ids[(int)$tid] = true;
				$iTop++;
			}
			$topN = array_slice($receitaPorCliente, 0, max(1, (int)ceil(count($receitaPorCliente) * 0.1)), true);
			foreach (array_keys($topN) as $vid) {
				$vipIds[(int)$vid] = true;
			}
			foreach ($top5 as $t) {
				$vipIds[(int)$t['id']] = true;
			}
		}

		$receitaPct = null;
		if ($hasFin && $receitaPrev > 0.0001) {
			$receitaPct = (int)round(100 * ($receita12 - $receitaPrev) / $receitaPrev);
		}
		$ticketMedio = ($cntAtivos > 0 && $receita12 > 0) ? $receita12 / $cntAtivos : 0.0;

		$segPctPj = $cntTotal > 0 ? (int)round(100 * $cntPj / $cntTotal) : 0;
		$segPctPf = $cntTotal > 0 ? (int)round(100 * $cntPf / $cntTotal) : 0;
		$segmentos = $this->_clientesSegmentosDistribuicao($todos);
		$vendedorPorCliente = $this->_clientesVendedorPorCliente();

		$alertaConc = null;
		if ($top5 !== [] && $receita12 > 0 && $top5[0]['pct'] >= 30) {
			$alertaConc = [
				'nome' => $top5[0]['nome'],
				'pct' => $top5[0]['pct'],
			];
		}

		return [
			'has_fin' => $hasFin,
			'ativos' => $cntAtivos,
			'novos_mes' => $novosMes,
			'receita12_fmt' => $this->_clientesFmtBrlCompact($receita12),
			'receita12_pct' => $receitaPct,
			'ticket_fmt' => $this->_clientesFmtBrl($ticketMedio),
			'inadimplentes' => $inadClientes,
			'inadimplentes_valor_fmt' => $this->_clientesFmtBrl($inadValor),
			'bloqueados' => $cntInativos,
			'aniversariantes' => $aniversariantes,
			'top5' => $top5,
			'alerta_concentracao' => $alertaConc,
			'segmentos' => $segmentos,
			'pj_bar' => ['count' => $cntPj, 'pct' => $segPctPj],
			'pf_bar' => ['count' => $cntPf, 'pct' => $segPctPf],
			'receita_por_cliente' => $receitaPorCliente,
			'a_receber_por_cliente' => $aReceberPorCliente,
			'atraso_dias_por_cliente' => $atrasoDiasPorCliente,
			'ultima_os_por_cliente' => $ultimaOsPorCliente,
			'vip_ids' => $vipIds,
			'top10_ids' => $top10Ids,
			'vendedor_por_cliente' => $vendedorPorCliente,
		];
	}

	protected function _clientesIndexNomeExibicao($cliente) {
		if ((int)$cliente->tipo === (int)C_ClientesTipoJuridica) {
			$n = trim((string)($cliente->razaosocial ?? ''));
			if ($n === '') {
				$n = trim((string)($cliente->nomefantasia ?? ''));
			}

			return $n !== '' ? $n : __('(sem nome)');
		}

		$n = trim((string)($cliente->nome ?? ''));

		return $n !== '' ? $n : __('(sem nome)');
	}

	protected function _clientesFmtBrl($amount) {
		return 'R$ ' . number_format((float)$amount, 2, ',', '.');
	}

	protected function _clientesFmtBrlCompact($amount) {
		$v = (float)$amount;
		if ($v >= 1000000) {
			return 'R$ ' . number_format($v / 1000000, 2, ',', '.') . 'M';
		}
		if ($v >= 1000) {
			return 'R$ ' . number_format($v / 1000, 1, ',', '.') . 'k';
		}

		return $this->_clientesFmtBrl($v);
	}
}
