<?php
declare(strict_types=1);

namespace App\Controller\Traits;

use App\Service\ClienteDomain\InfrastructureGuard;
use App\Utility\ClientesPapelCadastro;
use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;

/**
 * Payload e helpers da Visão 360° (legado + protótipo).
 */
trait ClientesVisao360Trait {
	protected function _clientesFmtTelefoneBr($raw) {
		$digits = preg_replace('/\D+/', '', (string)$raw);
		if ($digits === '') {
			return '';
		}
		if (strlen($digits) === 10) {
			return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6, 4));
		}
		if (strlen($digits) === 11) {
			return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7, 4));
		}

		return $digits;
	}

	/**
	 * Primeiro e-mail de um campo que pode conter vários (; ou ,).
	 */
	protected function _clientesPrimeiroEmail($raw) {
		$raw = trim((string)$raw);
		if ($raw === '') {
			return '';
		}
		foreach (preg_split('/[;,]+/', $raw) as $em) {
			$em = trim($em);
			if ($em !== '') {
				return $em;
			}
		}

		return '';
	}

	protected function _clientesVisao360EnderecoLinha($cliente, $cidadeDisplay) {
		$rua = trim((string)($cliente->endereco ?? ''));
		$nro = trim((string)($cliente->nroendereco ?? ''));
		$bairro = trim((string)($cliente->bairro ?? ''));
		$cepRaw = preg_replace('/\D+/', '', (string)($cliente->cep ?? ''));
		$logParts = [];
		if ($rua !== '') {
			$logParts[] = $nro !== '' ? $rua . ', ' . $nro : $rua;
		} elseif ($nro !== '') {
			$logParts[] = $nro;
		}
		if ($bairro !== '') {
			$logParts[] = $bairro;
		}
		$parts = [];
		$log = trim(implode(', ', $logParts));
		if ($log !== '') {
			$parts[] = $log;
		}
		if ($cidadeDisplay !== '') {
			$parts[] = $cidadeDisplay;
		}
		if ($cepRaw !== '') {
			$cepFmt = strlen($cepRaw) === 8
				? substr($cepRaw, 0, 5) . '-' . substr($cepRaw, 5, 3)
				: $cepRaw;
			$parts[] = 'CEP ' . $cepFmt;
		}

		return implode(' · ', $parts);
	}

	/**
	 * Contatos do hero Visão 360° (telefone, WhatsApp/celular, e-mail).
	 *
	 * @return array<int,array{kind:string,label:string,label_upper:string,icon:string}>
	 */
	protected function _clientesVisao360HeroContatos($cliente) {
		$out = [];
		$fone = $this->_clientesFmtTelefoneBr($cliente->fone ?? '');
		if ($fone !== '') {
			$out[] = [
				'kind' => 'phone',
				'label' => $fone,
				'label_upper' => mb_strtoupper($fone, 'UTF-8'),
				'icon' => 'fas fa-phone',
			];
		}
		$fone2 = $this->_clientesFmtTelefoneBr($cliente->fone2 ?? '');
		if ($fone2 !== '') {
			$out[] = [
				'kind' => 'whatsapp',
				'label' => $fone2,
				'label_upper' => mb_strtoupper($fone2, 'UTF-8'),
				'icon' => 'fab fa-whatsapp',
			];
		}
		$emails = [];
		$emFat = $this->_clientesPrimeiroEmail($cliente->email ?? '');
		if ($emFat !== '') {
			$emails[] = $emFat;
		}
		$emResp = $this->_clientesPrimeiroEmail($cliente->emailresponsavel ?? '');
		if ($emResp !== '' && !in_array(mb_strtolower($emResp, 'UTF-8'), array_map(static function ($e) {
			return mb_strtolower($e, 'UTF-8');
		}, $emails), true)) {
			$emails[] = $emResp;
		}
		foreach ($emails as $em) {
			$out[] = [
				'kind' => 'email',
				'label' => $em,
				'label_upper' => mb_strtoupper($em, 'UTF-8'),
				'icon' => 'fas fa-envelope',
			];
		}
		$site = trim((string)($cliente->site ?? ''));
		if ($site !== '') {
			$out[] = [
				'kind' => 'web',
				'label' => $site,
				'label_upper' => mb_strtoupper($site, 'UTF-8'),
				'icon' => 'fas fa-globe',
			];
		}

		return $out;
	}
	protected function _clientesVisao360Payload($cliente, bool $contextoFornecedor = false) {
		$cid = (int)$cliente->id;
		$idempresa = (int)$this->Auth->user('idempresa');
		$isPj = (int)$cliente->tipo === (int)C_ClientesTipoJuridica;
		$papelCols = ClientesPapelCadastro::columnsAvailable($this->Clientes);
		$codigo = ClientesPapelCadastro::codigoExibicao($cliente, $papelCols, $contextoFornecedor);
		$seg = $this->_clientesClassificarSegmento($cliente);
		$cidadeDisplay = '';
		if (!empty($cliente->cidade) && !empty($cliente->cidade->nome)) {
			$cidadeDisplay = (string)$cliente->cidade->nome;
			if (!empty($cliente->cidade->estado) && !empty($cliente->cidade->estado->sigla)) {
				$cidadeDisplay .= '/' . strtoupper(trim((string)$cliente->cidade->estado->sigla));
			}
		}
		$endereco = $this->_clientesVisao360EnderecoLinha($cliente, $cidadeDisplay);
		$heroContatos = $this->_clientesVisao360HeroContatos($cliente);
		$heroIniciaisNome = trim((string)($cliente->nomefantasia ?? ''));
		if ($heroIniciaisNome === '') {
			$heroIniciaisNome = $this->_clientesIndexNomeExibicao($cliente);
		}
		$membroLabel = '';
		$anosCliente = '';
		if (!empty($cliente->membrodesde) && $cliente->membrodesde instanceof \DateTimeInterface) {
			$membroLabel = $cliente->membrodesde->i18nFormat('MMMM yyyy');
			$diff = $cliente->membrodesde->diff(new \DateTimeImmutable('today'));
			$anos = (int)$diff->y;
			$anosCliente = $anos > 0 ? __('{0} anos', $anos) : __('menos de 1 ano');
		}

		$payload = [
			'codigo' => $codigo,
			'nome' => $this->_clientesIndexNomeExibicao($cliente),
			'fantasia' => trim((string)($cliente->nomefantasia ?? '')),
			'doc' => $isPj ? (string)($cliente->cnpj ?? '') : (string)($cliente->cpf ?? ''),
			'ie' => trim((string)($cliente->inscricaoestadual ?? '')),
			'segmento' => $seg,
			'endereco' => $endereco,
			'cidade' => $cidadeDisplay,
			'hero_contacts' => $heroContatos,
			'hero_initials_name' => $heroIniciaisNome,
			'fone' => $this->_clientesFmtTelefoneBr($cliente->fone ?? ''),
			'fone2' => $this->_clientesFmtTelefoneBr($cliente->fone2 ?? ''),
			'email' => $this->_clientesPrimeiroEmail($cliente->email ?? ''),
			'site' => trim((string)($cliente->site ?? '')),
			'membro_label' => $membroLabel,
			'anos_cliente' => $anosCliente,
			'inativo' => (int)$cliente->inativo === 1,
			'is_vip' => false,
			'kpis' => [
				'receita12' => 0.0,
				'receita12_fmt' => $this->_clientesFmtBrl(0),
				'receita12_pct' => null,
				'receita_total' => 0.0,
				'receita_total_fmt' => $this->_clientesFmtBrlCompact(0),
				'a_receber' => 0.0,
				'a_receber_fmt' => $this->_clientesFmtBrl(0),
				'parcelas_abertas' => 0,
				'a_receber_hint' => '',
				'ticket_medio' => 0.0,
				'ticket_medio_fmt' => $this->_clientesFmtBrl(0),
				'has_fin' => false,
			],
			'receita_mensal' => [],
			'saude' => [],
			'counts' => [
				'orcamentos' => 0,
				'os' => 0,
				'contratos' => 0,
				'tickets_abertos' => 0,
				'arquivos' => 0,
			],
			'timeline' => [],
			'orcamentos' => [],
			'os_list' => [],
			'financeiro' => [],
			'contratos' => [],
			'arquivos_list' => [],
			'arquivos_filtros' => [
				'todos' => 0,
				'tickets' => 0,
				'financeiro' => 0,
				'fotos' => 0,
				'pdf' => 0,
				'doc' => 0,
			],
			'anexo_ticket_options' => [],
			'domain_events' => [],
			'domain_events_ready' => InfrastructureGuard::isReady(),
		];

		$hoje = FrozenDate::today();
		$ini12 = $hoje->subMonths(12);
		$iniPrev = $ini12->subMonths(12);
		$fimPrev = $ini12->subDay(1);

		try {
			$finTable = TableRegistry::getTableLocator()->get('FinanceiroLancamentos');
			$payload['kpis']['has_fin'] = true;
			$baseWhere = [
				'FinanceiroLancamentos.idempresa' => $idempresa,
				'FinanceiroLancamentos.idcliente' => $cid,
				'FinanceiroLancamentos.tipo' => 'receita',
			];

			$q12 = $finTable->find();
			$q12->select(['s' => $q12->func()->sum('FinanceiroLancamentos.valor')])
				->where($baseWhere + [
					'FinanceiroLancamentos.data_lancamento >=' => $ini12->format('Y-m-d'),
					'FinanceiroLancamentos.data_lancamento <=' => $hoje->format('Y-m-d'),
				]);
			$row12 = $q12->first();
			$receita12 = $row12 && $row12->s !== null ? (float)$row12->s : 0.0;

			$qPrev = $finTable->find();
			$qPrev->select(['s' => $qPrev->func()->sum('FinanceiroLancamentos.valor')])
				->where($baseWhere + [
					'FinanceiroLancamentos.data_lancamento >=' => $iniPrev->format('Y-m-d'),
					'FinanceiroLancamentos.data_lancamento <=' => $fimPrev->format('Y-m-d'),
				]);
			$rowPrev = $qPrev->first();
			$receitaPrev = $rowPrev && $rowPrev->s !== null ? (float)$rowPrev->s : 0.0;

			$qTot = $finTable->find();
			$qTot->select(['s' => $qTot->func()->sum('FinanceiroLancamentos.valor')])
				->where($baseWhere);
			$rowTot = $qTot->first();
			$receitaTotal = $rowTot && $rowTot->s !== null ? (float)$rowTot->s : 0.0;

			$qAb = $finTable->find();
			$qAb->select(['s' => $qAb->func()->sum('FinanceiroLancamentos.valor')])
				->where($baseWhere + ['FinanceiroLancamentos.status' => 'aberto']);
			$rowAb = $qAb->first();
			$aReceber = $rowAb && $rowAb->s !== null ? (float)$rowAb->s : 0.0;

			$parcelasAbertas = (int)$finTable->find()
				->where($baseWhere + ['FinanceiroLancamentos.status' => 'aberto'])
				->count();

			$atraso = false;
			$qInad = $finTable->find()
				->where($baseWhere + [
					'FinanceiroLancamentos.status' => 'aberto',
					'FinanceiroLancamentos.data_vencimento IS NOT' => null,
					'FinanceiroLancamentos.data_vencimento <' => $hoje->format('Y-m-d'),
				])
				->limit(1);
			if ($qInad->count() > 0) {
				$atraso = true;
			}

			$receitaPct = null;
			if ($receitaPrev > 0.0001) {
				$receitaPct = (int)round(100 * ($receita12 - $receitaPrev) / $receitaPrev);
			}

			$mesesPt = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
			$receitaMensal = [];
			for ($i = 11; $i >= 0; $i--) {
				$ref = $hoje->subMonths($i);
				$ym = $ref->format('Y-m');
				$qMes = $finTable->find();
				$qMes->select(['s' => $qMes->func()->sum('FinanceiroLancamentos.valor')])
					->where($baseWhere + [
						'FinanceiroLancamentos.data_lancamento >=' => $ym . '-01',
						'FinanceiroLancamentos.data_lancamento <=' => $ref->format('Y-m-t'),
					]);
				$rMes = $qMes->first();
				$valMes = $rMes && $rMes->s !== null ? (float)$rMes->s : 0.0;
				$receitaMensal[] = [
					'label' => $mesesPt[(int)$ref->format('n') - 1],
					'valor' => $valMes,
					'pct' => 0,
				];
			}
			$maxMes = 0.0;
			foreach ($receitaMensal as $rm) {
				if ($rm['valor'] > $maxMes) {
					$maxMes = $rm['valor'];
				}
			}
			if ($maxMes > 0) {
				foreach ($receitaMensal as $idx => $rm) {
					$receitaMensal[$idx]['pct'] = (int)round(100 * $rm['valor'] / $maxMes);
				}
			}
			$mediaMensal = $receita12 > 0 ? $receita12 / 12 : 0.0;
			$pico = ['valor' => 0.0, 'label' => '—'];
			foreach ($receitaMensal as $rm) {
				if ($rm['valor'] >= $pico['valor']) {
					$pico = ['valor' => $rm['valor'], 'label' => $rm['label']];
				}
			}
			$tendencia = __('Estável');
			if (count($receitaMensal) >= 6) {
				$ult3 = array_slice($receitaMensal, -3);
				$ant3 = array_slice($receitaMensal, -6, 3);
				$sUlt = array_sum(array_column($ult3, 'valor'));
				$sAnt = array_sum(array_column($ant3, 'valor'));
				if ($sAnt > 0.0001) {
					$delta = ($sUlt - $sAnt) / $sAnt;
					if ($delta > 0.08) {
						$tendencia = __('↑ Crescente');
					} elseif ($delta < -0.08) {
						$tendencia = __('↓ Em queda');
					}
				}
			}

			$ticketMedio = 0.0;

			$hintReceber = $parcelasAbertas > 0
				? __(
					'{0} parcela(s) · {1}',
					$parcelasAbertas,
					$atraso ? __('em atraso') : __('em dia')
				)
				: __('sem títulos em aberto');

			$payload['kpis'] = [
				'receita12' => $receita12,
				'receita12_fmt' => $this->_clientesFmtBrlCompact($receita12),
				'receita12_pct' => $receitaPct,
				'receita_total' => $receitaTotal,
				'receita_total_fmt' => $this->_clientesFmtBrlCompact($receitaTotal),
				'a_receber' => $aReceber,
				'a_receber_fmt' => $this->_clientesFmtBrl($aReceber),
				'parcelas_abertas' => $parcelasAbertas,
				'a_receber_hint' => $hintReceber,
				'em_atraso' => $atraso,
				'ticket_medio' => $ticketMedio,
				'ticket_medio_fmt' => $this->_clientesFmtBrl($ticketMedio),
				'has_fin' => true,
				'desde_hint' => $membroLabel !== '' ? __('desde {0}', $membroLabel) : '',
			];
			$payload['receita_mensal'] = $receitaMensal;
			$payload['receita_chart'] = [
				'media_fmt' => $this->_clientesFmtBrl($mediaMensal),
				'pico_fmt' => $this->_clientesFmtBrl($pico['valor']),
				'pico_label' => $pico['label'],
				'tendencia' => $tendencia,
			];

			$finRows = $finTable->find()
				->where($baseWhere)
				->order(['FinanceiroLancamentos.data_vencimento' => 'DESC', 'FinanceiroLancamentos.id' => 'DESC'])
				->limit(30)
				->all();
			foreach ($finRows as $fr) {
				$venc = $fr->get('data_vencimento');
				$vencStr = $venc instanceof \DateTimeInterface ? $venc->format('d/m/Y') : '—';
				$payload['financeiro'][] = [
					'id' => (int)$fr->get('id'),
					'descricao' => trim((string)($fr->get('descricao') ?? $fr->get('historico') ?? __('Lançamento'))),
					'valor_fmt' => $this->_clientesFmtBrl((float)($fr->get('valor') ?? 0)),
					'status' => (string)($fr->get('status') ?? ''),
					'vencimento' => $vencStr,
				];
				$stFin = strtolower((string)($fr->get('status') ?? ''));
				$isPago = $stFin !== '' && $stFin !== 'aberto';
				$payload['timeline'][] = [
					'kind' => 'financeiro',
					'icon' => 'fa-coins',
					'tone' => $isPago ? 'teal' : ($atraso ? 'orange' : 'blue'),
					'label' => ($isPago ? __('Pagamento recebido') : __('Título em aberto')) . ' · ' . $this->_clientesFmtBrl((float)($fr->get('valor') ?? 0)),
					'sub' => $vencStr . ' · ' . (string)($fr->get('descricao') ?? ''),
					'data' => $venc instanceof \DateTimeInterface ? $venc : ($fr->get('data_lancamento') instanceof \DateTimeInterface ? $fr->get('data_lancamento') : null),
					'url' => null,
				];
			}
		} catch (\Throwable $e) {
			$this->log('Clientes::visao360 financeiro: ' . $e->getMessage(), 'warning');
		}

		try {
			$this->loadModel('Orcamentos');
			$payload['counts']['orcamentos'] = (int)$this->Orcamentos->find()
				->where(['Orcamentos.idempresa' => $idempresa, 'Orcamentos.idcliente' => $cid])
				->count();
			foreach ($this->Orcamentos->find()
				->where(['Orcamentos.idempresa' => $idempresa, 'Orcamentos.idcliente' => $cid])
				->order(['Orcamentos.id' => 'DESC'])
				->limit(12)
				->all() as $orc) {
				$oid = (int)$orc->get('id');
				$payload['orcamentos'][] = [
					'id' => $oid,
					'label' => __('Orçamento #{0}', $oid),
					'status' => (string)($orc->get('status') ?? ''),
					'data' => $orc->get('created'),
					'url' => ['controller' => 'Orcamentos', 'action' => 'edit', $oid],
				];
				$payload['timeline'][] = [
					'kind' => 'orcamento',
					'icon' => 'fa-file-invoice',
					'tone' => 'purple',
					'label' => __('Orçamento #{0}', $oid),
					'sub' => (string)($orc->get('status') ?? ''),
					'data' => $orc->get('created'),
					'url' => ['controller' => 'Orcamentos', 'action' => 'edit', $oid],
				];
			}
		} catch (\Throwable $e) {
		}

		try {
			$this->loadModel('Ordensservico');
			$wOs = ['Ordensservico.idempresa' => $idempresa, 'Ordensservico.idcliente' => $cid];
			$payload['counts']['os'] = (int)$this->Ordensservico->find()->where($wOs)->count();
			foreach ($this->Ordensservico->find()->where($wOs)->order(['Ordensservico.id' => 'DESC'])->limit(12)->all() as $os) {
				$oid = (int)$os->get('id');
				$rel = \Cake\Utility\Text::truncate((string)($os->get('relato') ?? $os->get('descricao') ?? ''), 80, ['ellipsis' => '…']);
				$payload['os_list'][] = [
					'id' => $oid,
					'label' => sprintf('OS-%05d', $oid),
					'sub' => $rel,
					'situacao' => (string)($os->get('situacao') ?? ''),
					'data' => $os->get('dataabertura') ?? $os->get('created'),
					'url' => ['controller' => 'Ordensservico', 'action' => 'view', $oid],
				];
				$payload['timeline'][] = [
					'kind' => 'os',
					'icon' => 'fa-wrench',
					'tone' => 'blue',
					'label' => sprintf('OS-%05d', $oid) . ($rel !== '' ? ' · ' . $rel : ''),
					'sub' => (string)($os->get('situacao') ?? ''),
					'data' => $os->get('dataabertura') ?? $os->get('created'),
					'url' => ['controller' => 'Ordensservico', 'action' => 'view', $oid],
				];
			}
		} catch (\Throwable $e) {
		}

		try {
			$payload['counts']['contratos'] = (int)$this->Clicontratos->find()
				->where(['Clicontratos.idcliente' => $cid])
				->count();
			foreach ($this->Clicontratos->find()
				->where(['Clicontratos.idcliente' => $cid])
				->order(['Clicontratos.id' => 'DESC'])
				->limit(12)
				->all() as $ct) {
				$payload['contratos'][] = [
					'id' => (int)$ct->get('id'),
					'label' => trim((string)($ct->get('descricao') ?? $ct->get('servico') ?? __('Contrato #{0}', (int)$ct->get('id')))),
					'validade' => $this->_clicontratoValidadeYmd($ct),
					'url' => ['controller' => 'Clientes', 'action' => 'edit', $cid, '#' => 'contratos'],
				];
			}
			if ($payload['kpis']['ticket_medio'] <= 0 && $payload['counts']['contratos'] > 0 && $payload['kpis']['receita_total'] > 0) {
				$payload['kpis']['ticket_medio'] = $payload['kpis']['receita_total'] / $payload['counts']['contratos'];
				$payload['kpis']['ticket_medio_fmt'] = $this->_clientesFmtBrl($payload['kpis']['ticket_medio']);
			}
		} catch (\Throwable $e) {
		}

		try {
			$tickets = $this->loadModel('Tickets');
			$closed = [];
			if (defined('C_TicketSituacaoFechado')) {
				$closed[] = (int)C_TicketSituacaoFechado;
			}
			if (defined('C_TicketSituacaoResolvido')) {
				$closed[] = (int)C_TicketSituacaoResolvido;
			}
			$wT = ['Tickets.idempresa' => $idempresa, 'Tickets.idcliente' => $cid];
			$wAb = $wT;
			if ($closed !== []) {
				$wAb['Tickets.situacao NOT IN'] = $closed;
			}
			$payload['counts']['tickets_abertos'] = (int)$tickets->find()->where($wAb)->count();
			foreach ($tickets->find()->where($wT)->order(['Tickets.id' => 'DESC'])->limit(40)->all() as $t) {
				$tid = (int)$t->get('id');
				$assunto = trim((string)$t->get('assunto'));
				$solic = trim((string)$t->get('solicitacao'));
				$lbl = '#' . $tid;
				if ($assunto !== '') {
					$lbl .= ' · ' . \Cake\Utility\Text::truncate($assunto, 48, ['ellipsis' => '…']);
				} elseif ($solic !== '') {
					$lbl .= ' · ' . \Cake\Utility\Text::truncate($solic, 48, ['ellipsis' => '…']);
				}
				$payload['anexo_ticket_options'][] = ['id' => $tid, 'label' => $lbl];
			}
			foreach ($tickets->find()->where($wT)->order(['Tickets.created' => 'DESC'])->limit(8)->all() as $t) {
				$tid = (int)$t->get('id');
				$payload['timeline'][] = [
					'kind' => 'ticket',
					'icon' => 'fa-headset',
					'tone' => 'indigo',
					'label' => '#' . $tid . ' · ' . \Cake\Utility\Text::truncate((string)$t->get('solicitacao'), 60, ['ellipsis' => '…']),
					'sub' => (string)$t->get('situacao'),
					'data' => $t->get('created'),
					'url' => ['controller' => 'Tickets', 'action' => 'view', $tid],
				];
			}
		} catch (\Throwable $e) {
		}

		try {
			$faturas = $this->loadModel('Faturas');
			$wF = ['Faturas.idempresa' => $idempresa, 'Faturas.idcliente' => $cid];
			foreach ($faturas->find()->where($wF)->order(['Faturas.vencimento' => 'DESC'])->limit(8)->all() as $f) {
				$fid = (int)$f->get('id');
				$v = (float)($f->get('valor') ?? 0);
				$venc = $f->get('vencimento');
				$payload['timeline'][] = [
					'kind' => 'fatura',
					'icon' => 'fa-file-invoice-dollar',
					'tone' => 'teal',
					'label' => __('Fatura {0}', (string)($f->get('nro') ?? '#' . $fid)) . ' · ' . $this->_clientesFmtBrl($v),
					'sub' => $venc instanceof \DateTimeInterface ? $venc->format('d/m/Y') : '',
					'data' => $venc,
					'url' => ['controller' => 'Faturas', 'action' => 'view', $fid],
				];
			}
		} catch (\Throwable $e) {
		}

		if ($payload['domain_events_ready']) {
			try {
				$payload['domain_events'] = TableRegistry::get('ClientDomainEvents')
					->find()
					->where(['idcliente' => $cid])
					->order(['created' => 'DESC'])
					->limit(200)
					->toArray();
				foreach ($payload['domain_events'] as $ev) {
					$payload['timeline'][] = [
						'kind' => 'evento',
						'icon' => 'fa-history',
						'tone' => 'gray',
						'label' => (string)($ev->event_type ?? __('Evento')),
						'sub' => \Cake\Utility\Text::truncate((string)($ev->description ?? ''), 120, ['ellipsis' => '…']),
						'data' => $ev->created,
						'url' => null,
					];
				}
			} catch (\Throwable $e) {
				$payload['domain_events'] = [];
			}
		}

		usort($payload['timeline'], static function ($a, $b) {
			$ta = $a['data'] instanceof \DateTimeInterface ? $a['data']->getTimestamp() : 0;
			$tb = $b['data'] instanceof \DateTimeInterface ? $b['data']->getTimestamp() : 0;

			return $tb <=> $ta;
		});
		$payload['timeline'] = array_slice($payload['timeline'], 0, 40);
		$payload['timeline_preview'] = array_slice($payload['timeline'], 0, 8);

		$interacoes30 = 0;
		$cut = (new \DateTimeImmutable('today'))->modify('-30 days')->getTimestamp();
		foreach ($payload['timeline'] as $tl) {
			if ($tl['data'] instanceof \DateTimeInterface && $tl['data']->getTimestamp() >= $cut) {
				$interacoes30++;
			}
		}
		$engLabel = $interacoes30 >= 5 ? __('Alto') : ($interacoes30 >= 2 ? __('Médio') : __('Baixo'));
		$engPct = min(100, $interacoes30 * 15);
		$payload['saude'] = [
			[
				'label' => __('Engajamento (30 dias)'),
				'valor' => $engLabel,
				'pct' => $engPct,
				'hint' => __('{0} interações', $interacoes30),
			],
		];
		if ($payload['kpis']['has_fin'] && $payload['kpis']['parcelas_abertas'] > 0) {
			$pontPct = !empty($payload['kpis']['em_atraso']) ? 40 : 95;
			$payload['saude'][] = [
				'label' => __('Situação financeira'),
				'valor' => $payload['kpis']['a_receber_hint'],
				'pct' => $pontPct,
				'hint' => $payload['kpis']['a_receber_fmt'],
			];
		}
		if (!empty($payload['receita_chart']['tendencia'])) {
			$payload['saude'][] = [
				'label' => __('Tendência de receita'),
				'valor' => (string)$payload['receita_chart']['tendencia'],
				'pct' => strpos((string)$payload['receita_chart']['tendencia'], 'Crescente') !== false ? 85 : 50,
				'hint' => (string)$payload['receita_chart']['media_fmt'] . ' ' . __('média/mês'),
			];
		}

		$crmOne = $this->_clientesIndexCrmMetrics([$cliente], (int)$cliente->inativo === 0 ? 1 : 0);
		$payload['is_vip'] = !empty($crmOne['vip_ids'][$cid]);
		$payload['contatos'] = $this->_clientesVisao360Contatos($cliente);
		$arquivosList = $this->_clientesListarArquivosCliente($cid, $idempresa);
		$payload['arquivos_list'] = $arquivosList;
		$payload['arquivos_filtros'] = $this->_clientesArquivosFiltros($arquivosList);
		$payload['counts']['arquivos'] = count($arquivosList);
		$limite = $this->_clientesCrmFinanceReady() ? (float)($cliente->limite_credito ?? 0) : 0.0;
		$score = null;
		if ($this->_clientesCrmFinanceReady() && $cliente->get('score_interno') !== null && $cliente->get('score_interno') !== '') {
			$score = (float)$cliente->score_interno;
		}
		$aRec = (float)($payload['kpis']['a_receber'] ?? 0);
		$disp = $limite > 0 ? max(0.0, $limite - $aRec) : 0.0;
		$scoreHint = __('Não informado');
		if ($score !== null) {
			$scoreHint = $score >= 8.0 ? __('Pagador exemplar') : __('Cadastro do cliente');
		}
		$payload['finance_crm'] = [
			'has_limite' => $limite > 0,
			'has_score' => $score !== null,
			'limite_fmt' => $this->_clientesFmtBrl($limite),
			'disponivel_fmt' => $this->_clientesFmtBrl($disp),
			'score_fmt' => $score !== null ? number_format($score, 1, ',', '.') : '—',
			'score_hint' => $scoreHint,
			'limite_pct' => $limite > 0 ? (int)round(100 * min(1.0, $aRec / $limite)) : 0,
		];

		return $payload;
	}

	/**
	 * Contatos exibidos na Visão 360° (cadastro + usuários do portal; sem dados fictícios).
	 *
	 * @param \App\Model\Entity\Cliente $cliente
	 * @return array<int,array<string,mixed>>
	 */
	protected function _clientesVisao360Contatos($cliente) {
		$cid = (int)$cliente->id;
		if ($this->_clientesContatosReady()) {
			$dbRows = $this->_clientesContatosList($cid);
			if ($dbRows !== []) {
				$out = [];
				foreach ($dbRows as $c) {
					$row = $this->_clientesContatoJsonRow($c);
					$out[] = [
						'id' => $row['id'],
						'nome' => $row['nome'],
						'cargo' => $row['cargo'],
						'email' => $row['email'],
						'fone' => $row['fone'],
						'iniciais' => $row['iniciais'],
						'av_tone' => $row['av_tone'],
						'principal' => $row['principal'],
					];
				}

				return $out;
			}
		}
		$isPj = (int)$cliente->tipo === (int)C_ClientesTipoJuridica;
		$avTones = ['teal', 'blue', 'rose', 'orange', 'purple', 'navy'];
		$contatos = [];
		$seen = [];
		$push = function ($nome, $cargo, $email, $fone) use (&$contatos, &$seen, $avTones) {
			$nome = trim((string)$nome);
			$email = trim((string)$email);
			$fone = trim((string)$fone);
			if ($nome === '' && $email === '' && $fone === '') {
				return;
			}
			if ($nome === '') {
				$nome = $email !== '' ? $email : ($fone !== '' ? $fone : __('Contato'));
			}
			$key = mb_strtolower($nome . '|' . $email, 'UTF-8');
			if (isset($seen[$key])) {
				return;
			}
			$seen[$key] = true;
			$parts = preg_split('/\s+/', $nome, -1, PREG_SPLIT_NO_EMPTY);
			$ini = strtoupper(substr($parts[0] ?? 'C', 0, 1)) . strtoupper(substr($parts[1] ?? '', 0, 1));
			$contatos[] = [
				'nome' => $nome,
				'cargo' => trim((string)$cargo),
				'email' => $email,
				'fone' => $fone,
				'iniciais' => $ini !== '' ? $ini : 'C',
				'av_tone' => $avTones[count($contatos) % count($avTones)],
			];
		};

		if ($isPj) {
			$resp = trim((string)($cliente->nomeresponsavel ?? ''));
			if ($resp !== '') {
				$push($resp, __('Representante legal'), '', (string)($cliente->fone2 ?? $cliente->fone ?? ''));
			}
		} else {
			$push((string)($cliente->nome ?? ''), __('Titular'), (string)($cliente->email ?? ''), (string)($cliente->fone ?? ''));
		}

		$emailsContato = trim((string)($cliente->emailresponsavel ?? ''));
		if ($emailsContato !== '') {
			foreach (preg_split('/[;,]+/', $emailsContato) as $em) {
				$em = trim($em);
				if ($em !== '') {
					$push($em, __('Contato operacional'), $em, '');
				}
			}
		}
		$emailFat = trim((string)($cliente->email ?? ''));
		if ($emailFat !== '') {
			foreach (preg_split('/[;,]+/', $emailFat) as $em) {
				$em = trim($em);
				if ($em !== '') {
					$push($em, __('Faturamento'), $em, '');
				}
			}
		}
		if ((string)($cliente->fone ?? '') !== '' && count($contatos) > 0) {
			if ($contatos[0]['fone'] === '') {
				$contatos[0]['fone'] = (string)$cliente->fone;
			}
		}

		try {
			$users = $this->Users->find()
				->select(['id', 'name', 'email', 'username'])
				->where(['Users.idcliente' => $cid])
				->order(['Users.name' => 'ASC'])
				->limit(20)
				->all();
			foreach ($users as $u) {
				$push(
					(string)($u->name ?? $u->username ?? ''),
					__('Usuário portal'),
					(string)($u->email ?? ''),
					''
				);
			}
		} catch (\Throwable $e) {
		}

		return $contatos;
	}
}
