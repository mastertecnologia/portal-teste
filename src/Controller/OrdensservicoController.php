<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Service\OrdemServico\TicketOsPrefillService;
use App\Service\Ticket\TicketHistoryLogger;
use App\Utility\ErpGridUrl;
use App\Utility\ErpIntegrationRequest;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;
use Cake\Routing\Router;
use Cake\Mailer\Email;
use Cake\View\View;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'Utilities.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'TicketConstants.php');
require_once (ROOT . DS . 'vendor' . DS . 'queencitycodefactory/cakesoap/src/Network/CakeSoap.php');

use CakeSoap\Network\CakeSoap;
use Cake\Core\Configure;

//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/Utilities.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/TicketConstants.php';

class OrdensservicoController extends AppController {
	public function initialize() {
        parent::initialize();
		$this->loadModel('Clientes');
		$this->loadModel('Areas');
		$this->loadModel('Problemas');
		$this->loadModel('Produtos');
		$this->loadModel('Itensordem');
		$this->loadModel('Ordemservicositens');
		$this->loadModel('Ordemmovs');
		$this->loadModel('Ordemhoras');
		$this->loadModel('Ordemparcelas');
		$this->loadModel('Empresas');
		$this->loadModel('Cidades');
		$this->loadModel('Estados');
		$this->loadModel('Tickets');
		$this->loadModel('Ticketcomentarios');
		$this->loadModel('Ticketsmovs');
		$this->loadModel('TicketProducts');
		$this->loadModel('TechnicalReports');
		$this->loadModel('TicketHistories');
		$this->loadModel('Ticketsanexos');
		$this->loadModel('Ticketshoras');
		$this->loadModel('TicketAssets');
		$this->loadModel('Users');
	}

	/**
	 * Realinha a sequência da coluna id no PostgreSQL (evita duplicate key após importação / INSERT manual).
	 * Tenta pg_get_serial_sequence; se falhar, usa fallbacks: *_id_increment (padrão deste projeto) e *_id_seq.
	 * Chamar nextval manualmente no psql só consome números da sequence; não corrompe dados.
	 *
	 * @param string $table itensordem | ordemservicositens
	 */
	protected function fixPostgresIdSequence($table) {
		static $allowed = ['itensordem' => true, 'ordemservicositens' => true];
		static $fallbackSeqByTable = [
			'itensordem' => [
				'public.itensordem_id_increment',
				'itensordem_id_increment',
				'public.itensordem_id_seq',
				'itensordem_id_seq',
			],
			'ordemservicositens' => [
				'public.ordemservicositens_id_increment',
				'ordemservicositens_id_increment',
				'public.ordemservicositens_id_seq',
				'ordemservicositens_id_seq',
			],
		];
		if (empty($allowed[$table])) {
			return;
		}
		try {
			$conn = $this->Itensordem->getConnection();
			$d = $conn->getDriver();
			if (!($d instanceof \Cake\Database\Driver\Postgres)) {
				return;
			}
			$qi = $conn->quoteIdentifier($table);
			$base = str_replace(['"', "'"], '', $table);
			$maxSub = '(SELECT COALESCE(MAX(id), 0) FROM ' . $qi . ')';
			$lastErr = null;
			foreach (['public.' . $base, $base] as $rel) {
				try {
					$conn->execute(
						'SELECT setval((pg_get_serial_sequence(\'' . $rel . '\', \'id\'))::regclass, ' . $maxSub . ', true)'
					);

					return;
				} catch (\Throwable $e) {
					$lastErr = $e;
				}
			}
			foreach ($fallbackSeqByTable[$base] ?? [] as $seqLit) {
				try {
					$conn->execute('SELECT setval(\'' . $seqLit . '\'::regclass, ' . $maxSub . ', true)');

					return;
				} catch (\Throwable $e) {
					$lastErr = $e;
				}
			}
			if ($lastErr !== null) {
				$this->log('fixPostgresIdSequence(' . $table . '): ' . $lastErr->getMessage(), 'warning');
			}
		} catch (\Throwable $e) {
			$this->log('fixPostgresIdSequence(' . $table . '): ' . $e->getMessage(), 'warning');
		}
	}

	/**
	 * Converte valor monetário/qty do grid (pt-BR: 1.234,56) para float, evitando warning PHP 8+ em operações aritméticas.
	 */
	protected function parseDecimalBr($value, float $default = 0.0): float {
		if ($value === null || $value === '') {
			return $default;
		}
		if (is_int($value) || is_float($value)) {
			return (float)$value;
		}
		if (!is_scalar($value)) {
			return $default;
		}
		$s = trim((string)$value);
		if ($s === '') {
			return $default;
		}
		if (is_numeric($s)) {
			return (float)$s;
		}
		$s = preg_replace('/[^\d,.-]/u', '', $s);
		if ($s === '' || $s === '-') {
			return $default;
		}
		$s = str_replace('.', '', $s);
		$s = str_replace(',', '.', $s);
		if ($s === '' || !is_numeric($s)) {
			return $default;
		}

		return (float)$s;
	}

	/**
	 * Retorna iditens (chave do carrinho em itensordem) para a OS, ou null se não houver linha em ordemservicositens.
	 *
	 * @param int|string $idordem
	 * @return string|null
	 */
	protected function getOrdemIditensPk($idempresa, $idordem) {
		$rows = $this->Ordemservicositens->find('all')
			->where(['idordem' => $idordem])
			->toArray();
		foreach ($rows as $row) {
			if ((int)$row->idempresa !== (int)$idempresa) {
				continue;
			}
			$it = $row->iditens;
			if ($it !== null && trim((string)$it) !== '') {
				return (string)$it;
			}
		}

		return null;
	}

	/**
	 * Garante linha em ordemservicositens com iditens preenchido para o grid (itensordem.idordempk).
	 * Corrige OS antigas ou criadas sem esse vínculo.
	 *
	 * @param int|string $idordem ID da tabela ordensservico (não é idordempk do carrinho)
	 * @return string|null iditens gerado ou já existente; null se a OS não existir ou save falhar
	 */
	protected function ensureOrdemServicoItensVinculo($idempresa, $idordem): ?string {
		$idordem = (int)$idordem;
		$idempresa = (int)$idempresa;
		if ($idordem <= 0) {
			return null;
		}

		$ordem = $this->Ordensservico->find('all')
			->where(['id' => $idordem, 'idempresa' => $idempresa])
			->first();
		if ($ordem === null) {
			return null;
		}

		$pk = $this->getOrdemIditensPk($idempresa, $idordem);
		if ($pk !== null) {
			return $pk;
		}

		$row = $this->Ordemservicositens->find('all')
			->where(['idempresa' => $idempresa, 'idordem' => $idordem])
			->first();
		if ($row === null) {
			foreach ($this->Ordemservicositens->find('all')->where(['idordem' => $idordem]) as $c) {
				if ((int)$c->idempresa === $idempresa) {
					$row = $c;
					break;
				}
			}
		}

		$ultimo = $this->Empresas->prxOrdem($idempresa);
		if ($ultimo === null || (int)$ultimo === 0) {
			$iditens = (string)$idempresa . '1' . (string)(int)$this->Auth->user('id');
		} else {
			$iditens = (string)$idempresa . (string)(int)$ultimo . (string)(int)$this->Auth->user('id');
		}

		if ($row === null) {
			$row = $this->Ordemservicositens->newEntity();
			$row->idordem = $idordem;
			$row->idempresa = $idempresa;
		}
		$row->iditens = $iditens;

		$this->fixPostgresIdSequence('ordemservicositens');

		try {
			if (!$this->Ordemservicositens->save($row)) {
				$this->log(
					'ensureOrdemServicoItensVinculo: falha ao salvar ordemservicositens OS ' . $idordem . ' ' . json_encode($row->getErrors()),
					'error'
				);

				return null;
			}
		} catch (\Throwable $e) {
			$msg = $e->getMessage();
			$isDup = (strpos($msg, '23505') !== false || stripos($msg, 'duplicate key') !== false);
			if (!$isDup) {
				$this->log('ensureOrdemServicoItensVinculo: exceção OS ' . $idordem . ': ' . $msg, 'error');
				throw $e;
			}

			$this->log('ensureOrdemServicoItensVinculo: duplicate key ordemservicositens OS ' . $idordem . ', realinhando sequência', 'warning');
			$this->fixPostgresIdSequence('ordemservicositens');

			$againPk = $this->getOrdemIditensPk($idempresa, $idordem);
			if ($againPk !== null) {
				return $againPk;
			}

			if ($row->isNew()) {
				$row = $this->Ordemservicositens->newEntity([
					'idordem' => $idordem,
					'idempresa' => $idempresa,
					'iditens' => $iditens,
				]);
			}

			if (!$this->Ordemservicositens->save($row)) {
				$this->log(
					'ensureOrdemServicoItensVinculo: retry save falhou OS ' . $idordem . ' ' . json_encode($row->getErrors()),
					'error'
				);

				return null;
			}
		}

		$this->log(
			'ensureOrdemServicoItensVinculo: vínculo criado/atualizado para OS ' . $idordem . ' iditens=' . $iditens,
			'info'
		);

		return (string)$iditens;
	}

	/**
	 * Diagnóstico extra do grid (debug no JSON + detalhes no Bootbox): debug global do Cake
	 * ou sessão ativada com ?os_grid_diag=1 na página add/edit/addFromTicket (apenas funcionário).
	 */
	protected function osGridDebugVerbose(): bool {
		if (Configure::read('debug')) {
			return true;
		}
		if ((int)$this->Auth->user('role') !== C_RoleFuncionario) {
			return false;
		}

		return (bool)$this->request->getSession()->read('PGM.os_grid_diag');
	}

	/**
	 * Lê ?os_grid_diag=1|0 na URL das telas do grid e grava na sessão (só equipe interna).
	 */
	protected function syncOsGridDiagSession(): void {
		$q = $this->request->getQuery('os_grid_diag');
		if ($q === null) {
			return;
		}
		if ((int)$this->Auth->user('role') !== C_RoleFuncionario) {
			return;
		}
		if ($q === '1' || $q === 'true') {
			$this->request->getSession()->write('PGM.os_grid_diag', true);
		} elseif ($q === '0' || $q === 'false') {
			$this->request->getSession()->delete('PGM.os_grid_diag');
		}
	}

	/**
	 * Resposta JSON padronizada para o grid de itens da OS (diagnóstico no cliente).
	 *
	 * @param array $debug Só incluído no JSON se osGridDebugVerbose() for true.
	 */
	protected function osGridJsonError(int $status, string $code, string $msg, array $debug = []) {
		$out = [
			'ok' => false,
			'code' => $code,
			'msg' => $msg,
		];
		if ($this->osGridDebugVerbose()) {
			$out['debug'] = $debug;
		}

		return $this->jsonResponse($out, $status);
	}

	/**
	 * Sessão do carrinho (itensordem.idordempk) para nova OS — fluxo manual (só cria se ainda não existir).
	 */
	protected function initCarrinhoSessionNovaOsManual(int $idempresa, int $iduser): void {
		if (!empty($_SESSION['PGM_Ordem_Idcarrinhoadd'])) {
			return;
		}
		$ultimo = $this->Empresas->prxOrdem($idempresa);
		if ($ultimo == null || $ultimo == 0) {
			$_SESSION['PGM_Ordem_Idcarrinhoadd'] = $idempresa . 1 . $iduser;
			$_SESSION['PGM_Ordem_Idempresaadd'] = $idempresa;
		} else {
			$_SESSION['PGM_Ordem_Idcarrinhoadd'] = $idempresa . $ultimo . $iduser;
			$_SESSION['PGM_Ordem_Idempresaadd'] = $idempresa;
		}
	}

	/**
	 * Nova sessão de carrinho ao abrir OS a partir de ticket (alinhado ao fluxo antigo ticketordem).
	 */
	protected function initCarrinhoSessionNovaOsTicket(int $idempresa, int $iduser): void {
		$ultimo = $this->Empresas->prxOrdem($idempresa);
		if ($ultimo == null || $ultimo == 0) {
			$_SESSION['PGM_Ordem_Idcarrinhoadd'] = $idempresa . 1 . $iduser;
		} else {
			$_SESSION['PGM_Ordem_Idcarrinhoadd'] = $idempresa . $ultimo . $iduser;
		}
		$_SESSION['PGM_Ordem_Idempresaadd'] = $idempresa;
	}

	/**
	 * Copia produtos/serviços lançados no ticket para o carrinho da OS (itensordem).
	 */
	protected function seedCarrinhoComProdutosDoTicket(int $idticket, string $idordempk, int $idempresa): void {
		if (!$this->TicketProducts->getSchema()->hasColumn('ticket_id')) {
			return;
		}
		$qTp = $this->TicketProducts->find()
			->contain(['Produtos'])
			->where(['TicketProducts.ticket_id' => $idticket]);
		if ($this->TicketProducts->getSchema()->hasColumn('idempresa')) {
			$qTp->where(['TicketProducts.idempresa' => $idempresa]);
		}
		$rows = $qTp->toArray();
		$codigosJa = [];
		foreach ($rows as $tp) {
			$prod = $tp->produto ?? null;
			if ($prod === null) {
				continue;
			}
			$cod = trim((string)($prod->codigo ?? ''));
			if ($cod === '') {
				continue;
			}
			if (isset($codigosJa[$cod])) {
				continue;
			}
			$codigosJa[$cod] = true;
			$quantidade = (float)($tp->quantidade ?? 1);
			if ($quantidade <= 0) {
				$quantidade = 1.0;
			}
			$valorUnit = (float)($tp->preco_unitario ?? 0);
			if ($valorUnit <= 0) {
				$valorUnit = (float)($prod->vlunitario ?? 0);
			}
			$valordesconto = 0.0;
			$valortotal = ($quantidade * $valorUnit) - $valordesconto;
			$item = $this->Itensordem->newEntity([
				'idordempk' => $idordempk,
				'idempresa' => $idempresa,
				'tipo' => (int)($prod->tipo ?? 0),
				'codproduto' => $cod,
				'descricao' => (string)($prod->descricao ?? ''),
				'observacao' => '',
				'unidade' => (string)($prod->unidade ?? ''),
				'quantidade' => $quantidade,
				'serialnumber' => '',
				'modelo' => '',
				'productkey' => '',
				'obsinterna' => '',
				'valorunitario' => $valorUnit,
				'valordesconto' => $valordesconto,
				'valortotal' => $valortotal,
			]);
			$item->unsetProperty('id');
			$this->fixPostgresIdSequence('itensordem');
			$this->Itensordem->save($item);
		}
	}

	protected function logTicketHistoricoOsCriada(int $idticket, int $idordem): void {
		try {
			$userId = (int)$this->Auth->user('id');
			$userNome = trim((string)($this->Auth->user('name') ?? 'Usuário #' . $userId));
			$agora = date('d/m/Y H:i:s');
			$linkOs = Router::url(['controller' => 'Ordensservico', 'action' => 'edit', $idordem], true);
			$msg = sprintf(
				'Ordem de Serviço #%d criada por %s em %s. Link: %s',
				$idordem,
				$userNome,
				$agora,
				$linkOs
			);
			TicketHistoryLogger::log(
				$this->TicketHistories,
				$idticket,
				$userId,
				'os_criada',
				null,
				(string)$idordem,
				$msg,
				'sistema'
			);
		} catch (\Throwable $e) {
		}
	}

	protected function clearFiscalFieldsFromTicketPrefill($ordem): void {
		$schema = $this->Ordensservico->getSchema();
		$fiscalFields = [
			'pagamento',
			'condicao_pagamento',
			'condicaopagamento',
			'natureza_operacao',
			'naturezaoperacao',
			'centro_custo',
			'centrocusto',
			'tipo_faturamento',
			'tipofaturamento',
			'liberar_financeiro',
			'liberacao_financeiro',
			'nfe',
			'nfse',
		];
		foreach ($fiscalFields as $ff) {
			if ($schema->hasColumn($ff)) {
				$ordem->set($ff, null);
			}
		}
	}

	public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
		$gridDiagActions = ['add', 'edit', 'addFromTicket', 'ticketordem'];
		if (in_array($this->request->getParam('action'), $gridDiagActions, true)) {
			$this->syncOsGridDiagSession();
		}

        if($event->_subject->request->params['action'] == 'imprimir' && $this->Auth->user('role') == 1){
            $ordem = $this->Ordensservico->get($event->_subject->request->params['pass'][0])->idcliente;
            $cliente = $this->Clientes->get($this->Auth->user('idcliente'))->id;

            if ($ordem != $cliente) {
                $this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
                return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
            }
        }
        $this->Auth->allow(['refreshAPI', 'listAPI']);
        if (in_array('Security', $this->components()->loaded())) {
            $this->Security->setConfig('unlockedActions', ['refreshAPI', 'listAPI']);
        }
        if (in_array('Csrf', $this->components()->loaded())) {
            if (in_array($this->request->getParam('action'), ['refreshAPI', 'listAPI'])) {
                $this->getEventManager()->off($this->Csrf);
            }
        }
    }

	public function index() {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$idempresa = $this->Auth->user('idempresa');

		$cliente = $this->request->getQuery('cliente');
		$situacao = $this->request->getQuery('situacao');
		$problema = $this->request->getQuery('problema');
		$locacao = $this->request->getQuery('locacao');
		if ((string)$cliente === '0') $cliente = '';
		if ((string)$problema === '0') $problema = '';
		if ($locacao === null || $locacao === '') {
			$locacao = -1;
		}

		/* Consulta completa para permitir filtros/KPI instantâneos no cliente (sem reload). */
		$ordensQ = $this->Ordensservico->find('all')
			->select([
				'Ordensservico.id',
				'Ordensservico.idempresa',
				'Ordensservico.idcliente',
				'Ordensservico.iduser',
				'Ordensservico.idproblema',
				'Ordensservico.situacao',
				'Ordensservico.locacao',
				'Ordensservico.dataabertura',
				'Ordensservico.dataprevisao',
				'Ordensservico.contrato',
				'Ordensservico.valortotal',
			])
			->where(['Ordensservico.idempresa' => $idempresa])
			->contain([
				'Clientes' => ['fields' => ['Clientes.id', 'Clientes.razaosocial', 'Clientes.tipo', 'Clientes.nome']],
				'Users' => ['fields' => ['Users.id', 'Users.name']],
			]);
		if ($situacao !== '' && $situacao !== null) {
			$ordensQ->where(['Ordensservico.situacao' => $situacao]);
		}
		if ($cliente !== '' && $cliente !== null && (string)$cliente !== '0') {
			$ordensQ->where(['Ordensservico.idcliente' => $cliente]);
		}
		if ($problema !== '' && $problema !== null && (string)$problema !== '0') {
			$ordensQ->where(['Ordensservico.idproblema' => $problema]);
		}
		if ((string)$locacao !== '' && (string)$locacao !== '-1') {
			$ordensQ->where(['Ordensservico.locacao' => $locacao]);
		}
		$ordens = $ordensQ
			->order(['Ordensservico.id' => 'DESC'])
			->toArray();
		$problemas1 = [];
		$clientesOpt1 = [];
		$clientesOpt = [];

		$problemas = $this->Problemas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])->where(['idempresa' => $idempresa])->order(['descricao'])->toArray();
		foreach($problemas as $key=>$reg) $problemas1[$key] = $reg;

		$clientes = $this->Clientes->find('all', ['keyField' => 'id', 'valueField' => 'razaosocial'])->where(['idempresa' => $this->Auth->user('idempresa'), 'inativo' => 0])->order(['razaosocial'])->toArray();
		if(sizeof($clientes) > 0){
			foreach($clientes as $reg){
				if($reg->tipo == C_ClientesTipoJuridica) $clientesOpt[$reg->id] = $reg->razaosocial;
				else $clientesOpt[$reg->id] = $reg->nome;
			}
			asort($clientesOpt);
			foreach($clientesOpt as $key=>$reg) $clientesOpt1[$key] = $reg;
		}

		$this->set('problema', $problema);
		$this->set('cliente', $cliente);
		$this->set('situacao', $situacao);
		$this->set('problemas', $problemas1);
		$this->set('clientes', $clientesOpt1);
		$this->set('locacao', $locacao);
		$this->set('ordens',  $ordens);
		$this->set('title', 'Ordens de Serviço');
		$this->set('hideLayoutPageTitle', true);
	}

	public function add() {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$idempresa = $this->Auth->user('idempresa');
		$ordem = $this->Ordensservico->newEntity();

		$this->initCarrinhoSessionNovaOsManual($idempresa, (int)$this->Auth->user('id'));

		if ($this->request->is('post')) {
			$data = $this->request->getData();
			if($data['idEmpresaAtual'] != $this->Auth->user('idempresa')) {
				$this->Flash->error('Ocorreu um erro ao salvar a ordem de serviço. Verifique sua empresa atual e tente novamente');
				return $this->redirect(['action' => 'add']);
			}
            $ordem = $this->Ordensservico->patchEntity($ordem, $data);
			$ordem->idempresa = $idempresa;
			$ordem->iduser = $this->Auth->user('id');
			// $ordem->situacao = C_OrdensSituacaoEmExecucao;
			$ordem->situacao = C_OrdensSituacaoAberta;
			$ordem->valortotal = $data['valortotalordem'];
			$ordem->id = $this->Empresas->incrementOrdem($this->Auth->user('idempresa'));

			$idticketPost = (int)($data['idticket'] ?? 0);
			if ($idticketPost > 0) {
				$ticketRef = $this->Tickets->find()
					->where(['id' => $idticketPost, 'idempresa' => $idempresa])
					->first();
				if ($ticketRef === null) {
					$this->Empresas->decrementOrdem($this->Auth->user('idempresa'));
					$this->Flash->error(__('Ticket inválido ou de outra empresa. A ordem de serviço não foi salva.'));
					return $this->redirect(['action' => 'add']);
				}
				$jaOs = $this->Ordensservico->find()
					->where(['idticket' => $idticketPost, 'idempresa' => $idempresa])
					->count();
				if ($jaOs > 0) {
					$this->Empresas->decrementOrdem($this->Auth->user('idempresa'));
					$this->Flash->error(__('Já existe ordem de serviço vinculada a este ticket.'));
					return $this->redirect(['_name' => 'ticketsGerarOs', 'id' => $idticketPost]);
				}
				$ordem->idticket = $idticketPost;
			} else {
				$ordem->idticket = null;
			}

            if ($this->Ordensservico->save($ordem)) {
				$carrinho = $this->Ordemservicositens->newEntity();
				$idempresaCarrinho = $_SESSION['PGM_Ordem_Idempresaadd'] ?? null;
				if ($idempresaCarrinho === null && !empty($_SESSION['PGM_Ordem_Idcarrinhoadd'])) {
					$idempresaCarrinho = substr($_SESSION['PGM_Ordem_Idcarrinhoadd'], 0, strlen((string)$idempresa));
				}
				if($idempresa == $idempresaCarrinho){
					$carrinho->iditens = $_SESSION['PGM_Ordem_Idcarrinhoadd'];
				}  else {
						$carrinho->iditens = $idempresa . substr($_SESSION['PGM_Ordem_Idcarrinhoadd'], strlen((string)$idempresaCarrinho));
						$itemOrdens = $this->Itensordem->find('all')->where(['idordempk' => $_SESSION['PGM_Ordem_Idcarrinhoadd']])->toArray();

						foreach($itemOrdens as $item) {
							$item['idordempk'] = $carrinho->iditens;
							$this->Itensordem->save($item);
						}
				}

				$carrinho->idordem = $ordem->id;
				$carrinho->idempresa = $idempresa;
				$this->fixPostgresIdSequence('ordemservicositens');
				$this->Ordemservicositens->save($carrinho);
				unset($_SESSION['PGM_Ordem_Idcarrinhoadd']);
				unset($_SESSION['PGM_Ordem_Idempresaadd']);

				// Movimentação
				$this->Ordensservico->criarMov($ordem->id, 1, 1, $this->Auth->user('idempresa'), $this->Auth->user('id'));
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ordem->id);
				if (!empty($idticketPost)) {
					$this->logTicketHistoricoOsCriada($idticketPost, (int)$ordem->id);
				}
                $this->Flash->success(__('A ordem de serviço foi cadastrada com sucesso!'));
                return $this->redirect(['action' => 'edit', $ordem->id]);
			}

			// Decrementa o último id em caso de erro
			$this->Empresas->decrementOrdem($this->Auth->user('idempresa'));
            $this->Flash->error(__('Não foi possível cadastrar a ordem de serviço.'));
        }

		$this->_assignOsAddViewVars($ordem, $idempresa);
		$idticketReq = (int)($this->request->getData('idticket') ?? 0);
		if ($this->request->is('post') && $idticketReq > 0) {
			$ticketRef = $this->Tickets->findById($idticketReq)
				->contain([
					'Clientes' => ['fields' => ['id', 'contrato', 'tipo', 'cpf', 'cnpj', 'nome', 'razaosocial']],
				])
				->first();
			if ($ticketRef !== null && (int)$ticketRef->idempresa === (int)$idempresa) {
				$this->set('osOrigem', 'ticket');
				$this->set('osOrigemTicketId', $idticketReq);
				$this->set('osFormPostAdd', true);
				$this->set('idsolicitante', $this->request->getData('idsolicitante'));
				$this->set('ticketOrigemPanel', $this->buildTicketOrigemPanelView($ticketRef, $idempresa));
			} else {
				$this->_setOsAddViewContextManual();
			}
		} else {
			$this->_setOsAddViewContextManual();
		}
	}

	public function edit($id = null) {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		if ($id === null || $id === '' || !ctype_digit((string)$id)) {
			$this->Flash->error(
				'URL inválida: o endereço deve terminar com o número da ordem (ex.: …/ordensservico/edit/123), não com texto como "ID".'
			);

			return $this->redirect(['action' => 'index']);
		}
		$id = (int)$id;

		$idempresa = $this->Auth->user('idempresa');

		$data = $this->request->getData();


		$ordem = $this->Ordensservico->find('all')->where(['id' => $id, 'idempresa' => $idempresa])->first();

		if($this->request->is(['post', 'put']) && $data['idEmpresaAtual'] != $idempresa || empty($ordem) ) {
			$this->Flash->error('Ocorreu um erro ao editar a ordem de serviço. Verifique sua empresa atual e tente novamente');
			return $this->redirect(['action' => 'index']);
		}


		$ordem->dataabertura = date_format($ordem->dataabertura, 'd/m/Y');
		$ordem->dataprevisao = date_format($ordem->dataprevisao, 'd/m/Y');

		$movimentacoes = $this->Ordemmovs->find('all')->where(['idordem' => $id, 'Ordemmovs.idempresa' => $idempresa])->contain(['Users' => ['fields' => ['Users.name']]])->order(['data'])->toArray();
		$parcelas = $this->Ordemparcelas->find('all')->where(['idordem' => $id, 'Ordemparcelas.idempresa' => $idempresa])->contain(['Users' => ['fields' => ['Users.name']]])->order(['data'])->toArray();

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();

			if($data['idEmpresaAtual'] != $this->Auth->user('idempresa')) {
				$this->Flash->error('Ocorreu um erro ao editar a ordem de serviço. Verifique sua empresa atual e tente novamente');
				return $this->redirect(['action' => 'add']);
			}

			$atendAnt = $ordem['atendimento'];
			$ordem = $this->Ordensservico->patchEntity($ordem, $data);
			$atendNov = $ordem['atendimento'];
			$ordem->valortotal = $data['valortotalordem'];

            if ($this->Ordensservico->save($ordem)) {
				if($atendAnt != $atendNov){
					if($atendAnt == 0) $atendAnt = 7; else $atendAnt = 8;
					if($atendNov == 0) $atendNov = 7; else $atendNov = 8;
					$this->Ordensservico->criarMov($ordem->id, $atendAnt, $atendNov, $this->Auth->user('idempresa'), $this->Auth->user('id'));
				}
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ordem->id);
                $this->Flash->success(__('A ordem de serviço foi salva com sucesso!.'));
                return $this->redirect(['action' => 'edit', $ordem->id]);
            }
            $this->Flash->error(__('Não foi possível salvar a ordem de serviço.'));
        }

		$clientes = $this->Clientes->find('all')
			->select(['Clientes.id', 'Clientes.tipo', 'Clientes.razaosocial', 'Clientes.nome', 'Clientes.idcidade'])
			->where(['Clientes.idempresa' => $idempresa, 'Clientes.inativo' => 0])
			->contain(['Cidades' => ['fields' => ['Cidades.id', 'Cidades.nome']]])
			->order(['Clientes.razaosocial'])
			->toArray();

		$clientesOpt = [];
		foreach($clientes as $reg){
			$nomeCliente = ($reg->tipo == C_ClientesTipoJuridica) ? $reg->razaosocial : $reg->nome;
			$nomeCidade = (!empty($reg->cidade) && !empty($reg->cidade->nome)) ? $reg->cidade->nome : 'Sem Cidade';
			$clientesOpt[$reg->id] = $nomeCliente . ' (' . $nomeCidade . ')';
		}
		asort($clientesOpt);


		$areas = $this->Areas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])
			->where(['idempresa' => $idempresa])
			->order(['descricao'])
			->toArray();
		$problemas = $this->Problemas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])
			->where(['idempresa' => $idempresa])
			->order(['descricao'])
			->toArray();

		// Mantém o valor selecionado na edição mesmo quando cliente/problema ficou inativo.
		$idClienteAtual = (int)($ordem->idcliente ?? 0);
		if ($idClienteAtual > 0 && !isset($clientesOpt[$idClienteAtual])) {
			$clienteAtual = $this->Clientes->find('all')
				->select(['Clientes.id', 'Clientes.tipo', 'Clientes.razaosocial', 'Clientes.nome', 'Clientes.idcidade'])
				->where(['Clientes.idempresa' => $idempresa, 'Clientes.id' => $idClienteAtual])
				->contain(['Cidades' => ['fields' => ['Cidades.id', 'Cidades.nome']]])
				->first();
			if (!empty($clienteAtual)) {
				$nomeClienteAtual = ((int)$clienteAtual->tipo === C_ClientesTipoJuridica)
					? (string)$clienteAtual->razaosocial
					: (string)$clienteAtual->nome;
				$nomeCidadeAtual = (!empty($clienteAtual->cidade) && !empty($clienteAtual->cidade->nome))
					? (string)$clienteAtual->cidade->nome
					: 'Sem Cidade';
				$clientesOpt[$idClienteAtual] = trim($nomeClienteAtual) . ' (' . $nomeCidadeAtual . ')';
			}
		}

		$idProblemaAtual = (int)($ordem->idproblema ?? 0);
		if ($idProblemaAtual > 0 && !isset($problemas[$idProblemaAtual])) {
			$problemaAtual = $this->Problemas->find('all')
				->select(['id', 'descricao'])
				->where(['idempresa' => $idempresa, 'id' => $idProblemaAtual])
				->first();
			if (!empty($problemaAtual)) {
				$problemas[$idProblemaAtual] = (string)$problemaAtual->descricao;
			}
		}

		$produtosOpt = [];
		$tiposProdutosOs = $this->tiposValoresPermitidosListaProdutosOs();
		foreach ($this->Produtos->find('all')
			->select(['codigo', 'descricao'])
			->where(['idempresa' => $idempresa, 'tipo IN' => $tiposProdutosOs])
			->order(['descricao'])
			->toArray() as $reg) {
			$produtosOpt[] = ['codigo' => trim($reg->codigo), 'descricao' => trim($reg->descricao).' ('.trim($reg->codigo).')'];
		}

		$ordemhoras = $this->Ordemhoras->find('all')
			->contain([
				'Users' => ['fields' => ['Users.id', 'Users.name']],
				'Ordensservico' => ['fields' => ['Ordensservico.id', 'Ordensservico.idempresa']],
			])
			->where(['idordem' => $id, 'Ordensservico.idempresa' => $this->Auth->user('idempresa')])
			->toArray();
		$ordemparcelas = $this->Ordemparcelas->find('all')
			->contain([
				'Users' => ['fields' => ['Users.id', 'Users.name']],
				'Ordensservico' => ['fields' => ['Ordensservico.id', 'Ordensservico.idempresa']],
			])
			->where(['idordem' => $id, 'Ordensservico.idempresa' => $this->Auth->user('idempresa')])
			->first();

		$iditensPk = $this->getOrdemIditensPk($idempresa, $id);
		if ($iditensPk === null || empty($this->Itensordem->findByIdordempk($iditensPk)->order(['id'])->toArray())) {
			$this->set('finaliza', 'finaliza');
		}

		$this->set('produtosMobile', $produtosOpt);
		$this->set('produtosOpt', json_encode($produtosOpt, JSON_PRETTY_PRINT));
		$this->set('tiposMobile', $this->tiposMobileOrdemServico());
		$this->set('tiposOpt', json_encode($this->tiposOptOrdemServicoParaRotulos(), JSON_PRETTY_PRINT));
		$this->set('tiposOptGridItems', json_encode($this->tiposOptGridItemsForJsGrid(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		$this->set('tipoProdutoMercadoriaOs', $this->tipoProdutoMercadoriaOrdemServico());
		$this->set('problemas', $problemas);
		$this->set('areas', $areas);
		$this->set('clientes', $clientesOpt);
		$this->set('movimentacoes', $movimentacoes);
		$this->set('ordem', $ordem);
		$this->set('ordemhoras', $ordemhoras);
		$this->set('ordemparcelas', $ordemparcelas);
		$this->set('hideLayoutPageTitle', true);
		$this->set('title', 'Editar ordem de serviços');
		$this->set('authIdempresa', (int)$idempresa);
		$this->set('osGridAjaxVerbose', $this->osGridDebugVerbose());
	}

	public function view($id = null) {
		$idempresa = $this->Auth->user('idempresa');
		$idcliente = $this->Auth->user('idcliente');
		$ordem = $this->Ordensservico->find('all')->where(['id' => $id, 'idempresa' => $idempresa])->first();

		if ($this->Auth->user('role') == C_RoleCliente && $ordem->idcliente != $idcliente) {
			$this->Flash->error('Você não possui permissão para visualizar outras ordens.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$ordem->dataabertura = date_format($ordem->dataabertura, 'd/m/Y');
		$ordem->dataprevisao = date_format($ordem->dataprevisao, 'd/m/Y');

		$movimentacoes = $this->Ordemmovs->find('all')->where(['idordem' => $id, 'Ordemmovs.idempresa' => $idempresa])
			->contain(['Users' => ['fields' => ['Users.name']]])
		->toArray();

		$clientes = $this->Clientes->find('all', ['keyField' => 'id', 'valueField' => 'razaosocial'])->where(['idempresa' => $idempresa, 'inativo' => 0])->order(['razaosocial'])->toArray();
		$clientesOpt = [];
		foreach($clientes as $reg){
			if($reg->tipo == C_ClientesTipoJuridica) $clientesOpt[$reg->id] = $reg->razaosocial;
			else $clientesOpt[$reg->id] = $reg->nome;
		}
		asort($clientesOpt);

		$areas = $this->Areas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])->where(['idempresa' => $idempresa])->order(['descricao'])->toArray();
		$problemas = $this->Problemas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])->where(['idempresa' => $idempresa])->order(['descricao'])->toArray();
		$produtosOpt = [];
		$tiposProdutosOs = $this->tiposValoresPermitidosListaProdutosOs();
		$produtosOpt1 = $this->Produtos->find('all')
			->select(['codigo', 'descricao'])
			->where(['idempresa' => $idempresa, 'ativo' => 1, 'tipo IN' => $tiposProdutosOs])
			->order(['descricao'])
			->toArray();
		foreach ($produtosOpt1 as $reg) {
			$produtosOpt[$reg->codigo] = $reg->descricao.' ('.$reg->codigo.')';
		}

		$tiposOpt = $this->tiposOptOrdemServicoParaRotulos();

		$ordemhoras = $this->Ordemhoras->find('all', ['contain' => 'Users'])->where(['idordem' => $id])->toArray();
		$ordemparcelas = $this->Ordemparcelas->find('all', ['contain' => 'Users'])->where(['idordem' => $id])->first();

		$iditensPk = $this->getOrdemIditensPk($idempresa, $id);
		$carrinho = $iditensPk !== null
			? $this->Itensordem->findByIdordempk($iditensPk)->order(['id'])->toArray()
			: [];

		$this->loadModel('Faturamento');
		$faturamentos = $this->Faturamento->find('all')
			->where(['Faturamento.idordem' => $id, 'Faturamento.idempresa' => $idempresa])
			->order(['Faturamento.created' => 'DESC'])
			->toArray();

		$this->set('carrinho', $carrinho);
		$this->set('produtosMobile', $produtosOpt);
		$this->set('produtosOpt', json_encode($produtosOpt, JSON_PRETTY_PRINT));
		$this->set('tiposMobile', $this->tiposMobileOrdemServico());
		$this->set('tiposOpt', json_encode($tiposOpt, JSON_PRETTY_PRINT));
		$this->set('tipoProdutoMercadoriaOs', $this->tipoProdutoMercadoriaOrdemServico());
		$this->set('problemas', $problemas);
		$this->set('areas', $areas);
		$this->set('clientes', $clientesOpt);
		$this->set('movimentacoes', $movimentacoes);
		$this->set('ordem', $ordem);
		$this->set('ordemhoras', $ordemhoras);
		$this->set('ordemparcelas', $ordemparcelas);
		$this->set('faturamentos', $faturamentos);
		$this->set('title', 'Visualizar ordem de serviços');
	}

	public function cadhoras($idordem = null) {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$this->set('title', 'Cadastrar horas');

		$horas = $this->Ordemhoras->horasOrdem($idordem);

		$this->set('horas', $horas);
		$this->set('idordem', $idordem);
	}

	public function isAuthorized($user) {
		// Usa a mesma regra padrão do AppController (inclui verificação de prefixo admin)
		return parent::isAuthorized($user);
	}

	public function delete($id = null) {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$idempresa = $this->Auth->user('idempresa');
		$ordem = $this->Ordensservico->find('all')
			->where(['id' => $id, 'idempresa' => $idempresa])
			->first();

		if (empty($ordem)) {
			$this->Flash->error('Ordem de serviço não encontrada para a empresa atual.');
			return $this->redirect(['action' => 'index']);
		}

		// Decrementa contador apenas se a exclusão ocorrer
		if ($this->Ordensservico->delete($ordem)) {
			$this->Empresas->decrementOrdem($idempresa);
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
			$this->Flash->success(__('A ordem foi deletada com sucesso!.'));
			return $this->redirect(['action' => 'index']);
		}

		$this->Flash->error(__('Não foi possível deletar a ordem de serviço.'));
		return $this->redirect(['action' => 'index']);
	}

	public function carrinho($idordem = null){
		error_reporting(0);
		$this->autoRender = false;

		if ($idordem === null) {
			if (empty($_SESSION['PGM_Ordem_Idcarrinhoadd'])) {
				return $this->osGridJsonError(400, 'os_grid_sessao_carrinho', 'Sessão do carrinho não encontrada. Recarregue a página de cadastro da OS.', [
					'session_key' => 'PGM_Ordem_Idcarrinhoadd',
				]);
			}
			$idordem = $_SESSION['PGM_Ordem_Idcarrinhoadd'];
			$result = $this->Itensordem->findByIdordempk($idordem)->order(['id'])->toArray();
		} else {
			$idempresa = (int)$this->Auth->user('idempresa');
			$iditensPk = $this->getOrdemIditensPk($idempresa, $idordem);
			if ($iditensPk === null) {
				$iditensPk = $this->ensureOrdemServicoItensVinculo($idempresa, $idordem);
			}
			$result = $iditensPk !== null
				? $this->Itensordem->findByIdordempk($iditensPk)->order(['id'])->toArray()
				: [];
		}

		$output = [];
        foreach($result as $row)
        {
            $output[] = array(
            'id'            => $row->id,
            'tipo'          => $row->tipo,
            'codprodutosoocod' => $row->codproduto,
            'codproduto'    => $row->codproduto,
            'descricao'     => $row->descricao,
            'observacao'    => $row->observacao,
            'unidade'       => $row->unidade,
            'quantidade'    => $row->quantidade,
            'serialnumber'  => $row->serialnumber,
            'modelo'        => $row->modelo,
			'productkey'	=> $row->productkey,
			'obsinterna'	=> $row->obsinterna,
            'valorunitario' => number_format($row->valorunitario, 2, ",", "."),
            'valordesconto' => number_format( $row->valordesconto, 2, ",", "."),
            'valortotal'    => number_format( $row->valortotal   , 2, ",", "."),
            );
        }

		header("Content-Type: application/json");
		return $this->jsonResponse($output, 200);
	}

	public function carrinhoadd($idordem = null, $codproduto = null){
		$this->autoRender = false;

		/* Nova OS: add.ctp usa URL sem id → $idordem é null; edit passa id numérico ou às vezes "null" na rota. */
		$isCarrinhoSessao = ($idordem === null || $idordem === '' || (string)$idordem === 'null');
		if ($isCarrinhoSessao) {
			if (empty($_SESSION['PGM_Ordem_Idcarrinhoadd'])) {
				return $this->osGridJsonError(400, 'os_grid_sessao_carrinho', 'Sessão do carrinho expirada. Recarregue a página de cadastro.', []);
			}
			$idordem = $_SESSION['PGM_Ordem_Idcarrinhoadd'];
		} else {
			$idempresaOs = (int)$this->Auth->user('idempresa');
			$iditensPk = $this->getOrdemIditensPk($idempresaOs, $idordem);
			if ($iditensPk === null) {
				$iditensPk = $this->ensureOrdemServicoItensVinculo($idempresaOs, $idordem);
			}
			if ($iditensPk === null) {
				return $this->osGridJsonError(400, 'os_grid_sem_vinculo_itens', 'Não foi possível vincular o carrinho desta ordem (OS inexistente ou erro ao gravar ordemservicositens).', [
					'idordem_param' => $idordem,
				]);
			}
			$idordem = $iditensPk;
		}

		$carrinho = $this->Itensordem->findByIdordempk($idordem)->order(['id'])->toArray();

		$data = $this->request->getData();
		$empAuth = (int)$this->Auth->user('idempresa');
		$empPost = isset($data['idEmpresaAtual']) ? (int)$data['idEmpresaAtual'] : 0;
		if ($empPost !== $empAuth) {
			$this->Flash->error('Ocorreu um erro ao salvar os itens de ordem de serviço. Verifique sua empresa atual e tente novamente');

			return $this->osGridJsonError(400, 'os_grid_empresa', 'Empresa enviada (idEmpresaAtual) não confere com a empresa da sessão. Recarregue a página ou troque a empresa no menu.', [
				'idEmpresaAtual_enviado' => $data['idEmpresaAtual'] ?? null,
				'idEmpresaAtual_parseado' => $empPost,
				'idempresa_auth' => $empAuth,
			]);
		}

		// Código do produto pode vir na URL ou no POST (ex.: formulário mobile)
		if (empty($codproduto) && !empty($data['codproduto'])) {
			$codproduto = is_array($data['codproduto']) ? ($data['codproduto'][0] ?? null) : $data['codproduto'];
		}
		$codproduto = trim($codproduto ?? '');
		if ($codproduto === '') {
			return $this->osGridJsonError(422, 'os_grid_codigo_vazio', 'Código do produto/serviço está vazio. Selecione um item na pesquisa ou preencha o código antes de confirmar.', [
				'url_codigo' => $this->request->getParam('pass')[1] ?? null,
			]);
		}

		foreach ($carrinho as $reg) {
			if (trim((string)$reg->codproduto) === $codproduto) {
				return $this->jsonResponse([
					'ok' => false,
					'code' => 'os_grid_produto_duplicado',
					'msg' => 'Este produto já foi adicionado à ordem de serviço.',
				], 200);
			}
		}

		$idempresa = $this->Auth->user('idempresa');
		$valorunitario = $this->parseDecimalBr($data['valorunitario'] ?? 0);
		$descricao = $data['descricao'] ?? '';
		$unidade = $data['unidade'] ?? '';
		$tipo = isset($data['tipo']) ? (int)$data['tipo'] : 0;

		$valordesconto = $this->parseDecimalBr($data['valordesconto'] ?? 0);
		$quantidade = $this->parseDecimalBr($data['quantidade'] ?? 1, 1.0);
		if ($quantidade <= 0) {
			$quantidade = 1.0;
		}

		$tiposPerm = $this->tiposValoresPermitidosListaProdutosOs();
		$mapPerm = array_fill_keys($tiposPerm, true);
		if ($tipo === 0) {
			return $this->osGridJsonError(422, 'os_grid_tipo_nao_permitido', 'Selecione o tipo Produto ou Serviço antes de adicionar o item.', []);
		}
		if ($tipo === 3 || empty($mapPerm[$tipo])) {
			return $this->osGridJsonError(422, 'os_grid_tipo_nao_permitido', 'Tipo de cadastro não permitido neste item da ordem de serviço (ex.: contrato). Use apenas Produto ou Serviço.', []);
		}

		// Sempre buscar preço vigente: primeiro no ERP (Preço de Venda do estoque), senão no cadastro
		if ($codproduto !== '') {
			// Mesma regra que Produtos::produto: com tipo na linha, resolver cadastro por código+tipo (evita
			// findByCodigo()->first() pegar outra linha quando existem dois registros com o mesmo código).
			$produto = null;
			if (!empty($mapPerm[$tipo])) {
				$produto = $this->Produtos->find()->where([
					'codigo' => $codproduto,
					'idempresa' => $idempresa,
					'tipo' => $tipo,
				])->first();
			}
			if (!$produto) {
				// Não usar findByCodigo()->first(): o mesmo código pode existir em tipos diferentes; só considerar tipos permitidos na OS.
				$queryAlt = $this->Produtos->find()->where([
					'codigo' => $codproduto,
					'idempresa' => $idempresa,
					'tipo IN' => $tiposPerm,
				])->orderAsc('tipo');
				$cands = $queryAlt->toArray();
				if ($cands !== []) {
					foreach ($cands as $row) {
						if ((int)$row->tipo === $tipo) {
							$produto = $row;
							break;
						}
					}
					$produto = $produto ?? $cands[0];
				}
			}
			if ($produto) {
				$descricao = $produto->descricao;
				$unidade = $produto->unidade;
				$tipo = (int)$produto->tipo;
				$precoDoErp = null;
				try {
					$soapprodutos = ErpGridUrl::wsdl($this->Empresas->get($idempresa)->urlerp);
					$soap = new CakeSoap(['wsdl' => $soapprodutos]);
					$response = $soap->sendRequest('GetEstoqueProdutos', [
						'Data' => [
							'iFilial' => C_Filial,
							'sChave' => C_ChaveAcesso,
							'bApenasComSaldo' => false,
							'sCodProduto' => null,
							'sDescricao' => null,
						]
					]);
					$lista = $response->GetEstoqueProdutosResult->tWsProdutosEstoque ?? null;
					if ($lista !== null) {
						if (!is_array($lista)) $lista = [$lista];
						foreach ($lista as $item) {
							if (trim((string)($item->sCodProduto ?? '')) === $codproduto && isset($item->nPrecoVenda)) {
								$precoDoErp = (float) $item->nPrecoVenda;
								break;
							}
						}
					}
				} catch (\Exception $e) {
					// ERP indisponível: usa cadastro
				}
				$usaPrecoErp = ($precoDoErp !== null) && ((int)$produto->tipo === $this->tipoProdutoMercadoriaOrdemServico());
				if ($usaPrecoErp) {
					$valorunitario = $precoDoErp;
					// Atualiza o cadastro do produto para refletir o Preço de Venda do estoque
					if ((float)$produto->vlunitario != $precoDoErp) {
						$produto->vlunitario = $precoDoErp;
						$this->Produtos->save($produto);
					}
				} else {
					$valorunitario = (float) $produto->vlunitario;
				}
			}
		}

		if (!empty($mapPerm) && !isset($mapPerm[(int)$tipo])) {
			return $this->osGridJsonError(422, 'os_grid_tipo_nao_permitido', 'Tipo de cadastro não permitido neste item da ordem de serviço. Escolha produto ou serviço.', []);
		}

		$valortotal = ($quantidade * $valorunitario) - $valordesconto;

		$ordem = $this->Itensordem->newEntity();
        $ordem->idordempk = $idordem;
        $ordem->idempresa = $idempresa;
        $ordem->tipo = $tipo;
        $ordem->codproduto = $codproduto;
        $ordem->descricao = $descricao;
        $ordem->observacao = $data['observacao'] ?? '';
        $ordem->unidade = $unidade;
        $ordem->quantidade = $quantidade;
        $ordem->serialnumber = $data['serialnumber'] ?? '';
        $ordem->modelo = $data['modelo'] ?? '';
		$ordem->productkey = isset($data['productkey']) ? $data['productkey'] : '';
        $ordem->obsinterna = isset($data['obsinterna']) ? $data['obsinterna'] : '';
        $ordem->valorunitario = $valorunitario;
        $ordem->valordesconto = $valordesconto;
        $ordem->valortotal = $valortotal;

		$ordem->unsetProperty('id');

		$this->fixPostgresIdSequence('itensordem');
		$this->fixPostgresIdSequence('ordemservicositens');
		try {
			if ($this->Itensordem->save($ordem)) {
				return $this->jsonResponse(['ok' => true, 'code' => 'os_grid_item_ok'], 200);
			}
		} catch (\Throwable $e) {
			$msg = $e->getMessage();
			$this->log('Ordensservico::carrinhoadd save exception: ' . $msg, 'error');

			$isDupPk = (strpos($msg, '23505') !== false || stripos($msg, 'duplicate key') !== false)
				&& stripos($msg, 'itensordem') !== false;

			if ($isDupPk) {
				$this->fixPostgresIdSequence('itensordem');
				$retry = $this->Itensordem->newEntity([
					'idordempk' => $ordem->idordempk,
					'idempresa' => $ordem->idempresa,
					'tipo' => $ordem->tipo,
					'codproduto' => $ordem->codproduto,
					'descricao' => $ordem->descricao,
					'observacao' => $ordem->observacao,
					'unidade' => $ordem->unidade,
					'quantidade' => $ordem->quantidade,
					'serialnumber' => $ordem->serialnumber,
					'modelo' => $ordem->modelo,
					'productkey' => $ordem->productkey,
					'obsinterna' => $ordem->obsinterna,
					'valorunitario' => $ordem->valorunitario,
					'valordesconto' => $ordem->valordesconto,
					'valortotal' => $ordem->valortotal,
				]);
				$retry->unsetProperty('id');
				try {
					if ($this->Itensordem->save($retry)) {
						return $this->jsonResponse(['ok' => true, 'code' => 'os_grid_item_ok'], 200);
					}
				} catch (\Throwable $e2) {
					$this->log('Ordensservico::carrinhoadd retry após PK itensordem: ' . $e2->getMessage(), 'error');
				}
			}

			$this->fixPostgresIdSequence('itensordem');

			return $this->osGridJsonError(500, 'os_grid_save_excecao', 'Erro ao gravar o item no banco. Tente novamente ou recarregue a página.', $this->osGridDebugVerbose() ? ['exception' => $msg] : []);
		}
		$errFlat = $ordem->getErrors();
		$this->log('Ordensservico::carrinhoadd save failed: ' . json_encode($errFlat) . ' codproduto=' . $codproduto, 'error');

		$msgUser = 'Não foi possível salvar o item. Verifique código, quantidade e valores.';
		if (!empty($errFlat)) {
			$parts = [];
			foreach ($errFlat as $field => $errs) {
				if (is_array($errs)) {
					foreach ($errs as $rule => $text) {
						$parts[] = $field . ': ' . (is_string($text) ? $text : json_encode($text));
					}
				}
			}
			if ($parts !== []) {
				$msgUser .= ' Detalhes: ' . implode(' | ', $parts);
			}
		}

		$payload = [
			'ok' => false,
			'code' => 'os_grid_save_validacao',
			'msg' => $msgUser,
			'validation' => $errFlat,
		];
		if (!$this->osGridDebugVerbose()) {
			unset($payload['validation']);
		}

		return $this->response
			->withType('application/json')
			->withStatus(422)
			->withStringBody(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	/**
	 * Atualização de item existente no grid: mantido sem as mesmas validações de tipo de {@see carrinhoadd}.
	 * Linhas antigas podem exibir/editar tipo 3 (contrato) ou outros valores já gravados conforme fluxo atual da tela.
	 */
	public function carrinhoedititem(){
        $this->autoRender = false;
        $data = $this->request->getData();

        $empAuth = (int)$this->Auth->user('idempresa');
        $empPost = isset($data['idEmpresaAtual']) ? (int)$data['idEmpresaAtual'] : 0;
        if ($empPost !== $empAuth) {
            $this->Flash->error('Ocorreu um erro ao atualizar os itens. Verifique sua empresa.');

            return $this->osGridJsonError(400, 'os_grid_empresa', 'Empresa não confere ao atualizar item.', [
				'idEmpresaAtual_enviado' => $data['idEmpresaAtual'] ?? null,
				'idempresa_auth' => $empAuth,
			]);
        }

        $ordem = $this->Itensordem->findById($data['id'])->first();

        if ($ordem) {
            $ordem->tipo =  $data['tipo'];
            $ordem->codproduto = $data['codproduto'];
            $ordem->descricao =  $data['descricao'];
            $ordem->observacao = isset($data['observacao']) ? $data['observacao'] : '';
            $ordem->modelo = isset($data['modelo']) ? $data['modelo'] : '';
            $ordem->serialnumber = isset($data['serialnumber']) ? $data['serialnumber'] : '';
			$ordem->productkey = isset($data['productkey']) ? $data['productkey'] : '';
			$ordem->obsinterna = isset($data['obsinterna']) ? $data['obsinterna'] : '';
            $ordem->unidade =  $data['unidade'];
            $ordem->quantidade = $data['quantidade'];
            $ordem->valorunitario = str_replace('.', '', $data['valorunitario']);
            $ordem->valorunitario = str_replace(',', '.', $ordem->valorunitario);
            $ordem->valordesconto = str_replace('.', '', $data['valordesconto']);
            $ordem->valordesconto = str_replace(',', '.', $ordem->valordesconto);
            $ordem->valortotal = str_replace('.', '', $data['valortotal']);
            $ordem->valortotal = str_replace(',', '.', $ordem->valortotal);

            if( $this->Itensordem->save($ordem) ) {
                echo('boa');
            }
        }
    }

	public function carrinhodelitem(){
		$this->autoRender = false;
		$data = $this->request->getData();

		$empAuth = (int)$this->Auth->user('idempresa');
		$empPost = isset($data['idEmpresaAtual']) ? (int)$data['idEmpresaAtual'] : 0;
		if ($empPost !== $empAuth) {
			$this->Flash->error('Ocorreu um erro ao deletar o item da ordem de serviço. Verifique sua empresa atual e tente novamente');

			return $this->osGridJsonError(400, 'os_grid_empresa', 'Empresa não confere ao remover item.', []);
		}

		$ordem = $this->Itensordem->findById($data['id'])->first();

		if( $this->Itensordem->delete($ordem) ) echo('boa');
	}

	public function valortotal($idordem = null){
		$this->autoRender = false;
		if ($idordem == null) {
			if (empty($_SESSION['PGM_Ordem_Idcarrinhoadd'])) {
				return $this->jsonResponse(['valortotal' => 0, 'warning' => 'sessao_carrinho', 'msg' => 'Sessão do carrinho ausente; total zerado. Recarregue a página.'], 200);
			}
			$idordem = $_SESSION['PGM_Ordem_Idcarrinhoadd'];
			$carrinho = $this->Itensordem->findByIdordempk($idordem)->order(['id'])->toArray();
		} else {
			$idempresa = (int)$this->Auth->user('idempresa');
			$iditensPk = $this->getOrdemIditensPk($idempresa, $idordem);
			if ($iditensPk === null) {
				$iditensPk = $this->ensureOrdemServicoItensVinculo($idempresa, $idordem);
			}
			$carrinho = $iditensPk !== null
				? $this->Itensordem->findByIdordempk($iditensPk)->order(['id'])->toArray()
				: [];
		}

		$valortotal = 0;
		foreach($carrinho as $reg) {
			 $valortotal += $reg->valortotal;
		}

		return $this->jsonResponse(['valortotal' => $valortotal], 200);

	}

	public function pausar($id = null){
		$ordem = $this->Ordensservico->find('all')->where(['id' => $id, 'idempresa' => $this->Auth->user('idempresa')])->first();
		$data = $this->request->getData();
		$sitantiga = $ordem->situacao;

		$this->Ordensservico->patchEntity($ordem, $data);
		$ordem->situacao = C_OrdensSituacaoAberta;

		if ($this->Ordensservico->save($ordem)) {
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ordem->id);
			$this->Ordensservico->criarMov($id, $sitantiga, C_OrdensSituacaoAberta, $this->Auth->user('idempresa'), $this->Auth->user('id'));
			$this->Flash->success('A ordem de serviço foi pausada com sucesso!');
			return $this->redirect(['action' => 'edit', $id]);
		}
		$this->Flash->error('Ocorreu um erro ao pausar a ordem de serviço.');
	}

	public function cancelar($id = null){
		$ordem = $this->Ordensservico->find('all')->where(['id' => $id, 'idempresa' => $this->Auth->user('idempresa')])->first();
		$data = $this->request->getData();
		$sitantiga = $ordem->situacao;

		$this->Ordensservico->patchEntity($ordem, $data);
		$ordem->situacao = C_OrdensSituacaoCancelada;

		if ($this->Ordensservico->save($ordem)) {
			$ticket = $this->Tickets->findById($ordem->idticket)->first();
			if(!empty($ticket)) {
				$sitantiga = $ticket->situacao;
				$ticket->situacao = C_TicketSituacaoFechado;
				if($this->Tickets->save($ticket)) {
					$emailDest = $this->Tickets->email($ordem->idticket, C_TicketsAcaoFechado, null, $this->Auth->user('idempresa'));
					$mov = $this->Ticketsmovs->newEntity();
					$mov->idticket = $ordem->idticket;
					$mov->sitantiga = $sitantiga;
					$mov->sitnova = C_TicketSituacaoFechado;
					$mov->idusuario = $this->Auth->user('id');
					$mov->idempresa = $this->Auth->user('idempresa');
					$mov->datetime = date('d/m/Y H:i:s', time());
					$mov->observacao = "Ticket cancelado com o cancelamento da ordem de serviço nº $id";
					$this->Ticketsmovs->save($mov);
					$this->Flash->success("O ticket $ordem->idticket foi cancelado e um email foi enviado para $emailDest informando o cancelamento!");
				}
			}
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ordem->id);
			$this->Ordensservico->criarMov($id, $sitantiga, C_OrdensSituacaoCancelada, $this->Auth->user('idempresa'), $this->Auth->user('id'));
			$this->Flash->success('A ordem de serviço foi cancelada com sucesso!');
			return $this->redirect(['action' => 'edit', $id]);
		}
		$this->Flash->error('Ocorreu um erro ao cancelar a ordem de serviço.');
	}

	public function liberar($id = null){
		$ordem = $this->Ordensservico->find('all')->where(['id' => $id, 'idempresa' => $this->Auth->user('idempresa')])->first();

		if (empty($ordem)) {
			$this->Flash->error('Ordem de serviço não encontrada.');
			return $this->redirect(['action' => 'index']);
		}

		$statusPermitidos = [C_OrdensSituacaoAberta, C_OrdensSituacaoEmExecucao];
		if (!in_array($ordem->situacao, $statusPermitidos, true)) {
			$this->Flash->error('Não é possível liberar esta OS. Situação atual não permite a transição para faturamento.');
			return $this->redirect(['action' => 'edit', $id]);
		}

		$data = $this->request->getData();
		$sitantiga = $ordem->situacao;

		$this->Ordensservico->patchEntity($ordem, $data);
		$ordem->situacao = C_OrdensSituacaoLiberadaParaFaturamento;

		if ($this->Ordensservico->save($ordem)) {
			$ticket = $this->Tickets->findById($ordem->idticket)->first();
			if(!empty($ticket)) {
				$sitantiga = $ticket->situacao;
				$ticket->situacao = C_TicketSituacaoResolvido;
				if($this->Tickets->save($ticket)) {
					$emailDest = $this->Tickets->email($ordem->idticket, null, null, $this->Auth->user('idempresa'));
					$mov = $this->Ticketsmovs->newEntity();
					$mov->idticket = $ordem->idticket;
					$mov->sitantiga = $sitantiga;
					$mov->sitnova = C_TicketSituacaoResolvido;
					$mov->idusuario = $this->Auth->user('id');
					$mov->idempresa = $this->Auth->user('idempresa');
					$mov->datetime = date('d/m/Y H:i:s', time());
					$mov->observacao = "Ticket resolvido com a liberação da ordem de serviço nº $id";
					$this->Ticketsmovs->save($mov);
					$this->Flash->success("O ticket $ordem->idticket foi resolvido e um email foi enviado para $emailDest informando a resolução!");
				}
			}
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ordem->id);
			$this->Ordensservico->criarMov($id, $sitantiga, C_OrdensSituacaoLiberadaParaFaturamento, $this->Auth->user('idempresa'), $this->Auth->user('id'));
			$this->Flash->success('A ordem de serviço foi liberada para faturamento com sucesso!');
			return $this->redirect(['action' => 'edit', $id]);
		}
		$this->Flash->error('Ocorreu um erro ao liberar a ordem de serviço.');
	}

	public function finalizar($id = null){
		$ordem = $this->Ordensservico->find('all')->where(['id' => $id, 'idempresa' => $this->Auth->user('idempresa')])->first();
		$data = $this->request->getData();
		$sitantiga = $ordem->situacao;

		$this->Ordensservico->patchEntity($ordem, $data);
		$ordem->situacao = C_OrdensSituacaoFinalizada;

		if ($this->Ordensservico->save($ordem)) {

			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ordem->id);
			$this->Ordensservico->criarMov($id, $sitantiga, C_OrdensSituacaoFinalizada, $this->Auth->user('idempresa'), $this->Auth->user('id'));
			$this->Flash->success('A ordem de serviço foi finalizada com sucesso!');
			return $this->redirect(['action' => 'edit', $id]);
		}
		$this->Flash->error('Ocorreu um erro ao finalizar a ordem de serviço.');
	}

	public function emexec($id = null){
		$ordem = $this->Ordensservico->find('all')->where(['id' => $id, 'idempresa' => $this->Auth->user('idempresa')])->first();
		$data = $this->request->getData();
		$sitantiga = $ordem->situacao;

		$this->Ordensservico->patchEntity($ordem, $data);
		$ordem->situacao = C_OrdensSituacaoEmExecucao;

		if ($this->Ordensservico->save($ordem)) {
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ordem->id);
			$this->Ordensservico->criarMov($id, $sitantiga, C_OrdensSituacaoEmExecucao, $this->Auth->user('idempresa'), $this->Auth->user('id'));
			$this->Flash->success('A ordem de serviço foi movida para "em execução" com sucesso!');
			return $this->redirect(['action' => 'edit', $id]);
		}
		$this->Flash->error('Ocorreu um erro ao mover a ordem de serviço.');
	}

	public function listAPI() {
        $this->autoRender = false;
        if ($this->request->is('get')) {
			list($empresa, $token, $erpCredErr) = ErpIntegrationRequest::readEmpresaAndToken(
				$this->request,
			);
			$situacao = $this->request->getHeaderLine('situacao') ?: $this->request->getQuery('situacao');
			$id = $this->request->getHeaderLine('id') ?: $this->request->getQuery('id');

			$apiRet = function ($msg, $status = 200) {
				return $this->jsonResponse(['mensagem' => $msg, 'retorno' => $msg], $status);
			};

			if ($erpCredErr !== null) {
				return $apiRet($erpCredErr, 400);
			}

			$empresa = is_string($empresa) ? trim($empresa) : $empresa;
			$token = is_string($token) ? trim($token) : $token;
			$situacaoInt = is_numeric($situacao) ? (int)$situacao : null;

			if (empty($token) || empty($empresa) || $situacaoInt === null) {
				return $apiRet('Parâmetros da requisição inválidos. Envie empresa, token e situacao (ex.: situacao=4).', 400);
			}

			if(empty($this->Empresas->findById($empresa)->first())) return $apiRet('Parâmetros da requisição inválidos', 400);
			if($token == $this->Empresas->get($empresa)->token) {
				if(!empty($id)){
					$ordem = $this->Ordensservico->find('all')->where(['Ordensservico.idempresa' => $empresa, 'Ordensservico.id' => $id, 'situacao' => $situacaoInt])
						->contain([
							'Clientes' => ['fields' => ['Clientes.cnpj', 'Clientes.cpf', 'Clientes.razaosocial', 'Clientes.nome',
							'Clientes.inscricaoestadual', 'Clientes.endereco', 'Clientes.nroendereco', 'Clientes.complemento', 'Clientes.bairro', 'Clientes.idcidade',
							'Clientes.cep', 'Clientes.fone', 'Clientes.email', 'Clientes.contrato', 'Clientes.tipo']]
						])
					->toArray();
					if ($ordem == []) return $this->jsonResponse($ordem, 200);
				}else{
					$ordem = $this->Ordensservico->find('all')->where(['Ordensservico.idempresa' => $empresa, 'situacao' => $situacaoInt])
						->contain([
							'Clientes' => ['fields' => ['Clientes.cnpj', 'Clientes.cpf', 'Clientes.razaosocial', 'Clientes.nome',
							'Clientes.inscricaoestadual', 'Clientes.endereco', 'Clientes.nroendereco', 'Clientes.complemento', 'Clientes.bairro', 'Clientes.idcidade',
							'Clientes.cep', 'Clientes.fone', 'Clientes.email', 'Clientes.contrato', 'Clientes.tipo']]
						])
					->toArray();
				}
				foreach($ordem as $reg){
					// Itens
					$iditensPk = $this->getOrdemIditensPk($empresa, $reg->id);
					$itens = $iditensPk !== null
						? $this->Itensordem->findByIdordempk($iditensPk)->order(['id'])->toArray()
						: [];
					$reg->itens = $this->itensArr($itens);
					// Parcelas
					$parcelas = $this->Ordemparcelas->findByIdordem($reg->id)->where(['idempresa' => $empresa])->toArray();
					$reg->pagamento = $this->parcelasArr($parcelas);
					// Clientes
					$reg->cliente = $this->Clientes->clientesArr($reg->cliente);
					$reg = $this->ordensArr($reg);
				}
				return $this->jsonResponse($ordem, 200);
			} else {
				return $apiRet('Autenticação Inválida', 401);
			}
        }
	}

	public function ordensArr($ordem){
		$ordem->numero = $ordem->id;
		$ordem->dataabertura = date_format($ordem->dataabertura, 'Y-d-m');
		$ordem->dataprevisao = date_format($ordem->dataprevisao, 'Y-d-m');
		try {
			$ordem->cpftecnico = $ordem->iduser ? $this->Users->get($ordem->iduser)->cpf : '';
		} catch (\Throwable $e) {
			$ordem->cpftecnico = '';
		}
		// Manter situacao na resposta para o ERP exibir "Liberada para faturamento" (4) etc.
		if (isset($ordem->situacao) && function_exists('DescricaoSituacaoOrdem')) {
			$ordem->situacao_descricao = DescricaoSituacaoOrdem($ordem->situacao);
		}
		unset($ordem->id);
		unset($ordem->idcliente);
		unset($ordem->iduser);
		unset($ordem->valortotal);
		unset($ordem->relato);
		unset($ordem->idproblema);
		unset($ordem->idarea);
		unset($ordem->idempresa);
		unset($ordem->nrodestino);
		return $ordem;
	}

	public function parcelasArr($pag){
		$pagamentoarr = [];
		foreach($pag as $pagamento){
			$pagamentoarr[] = array(
				'id'    		=> $pagamento->id,
				'pagamento'   	=> $pagamento->pagamento,
				'nmrparcelas'	=> $pagamento->nmrparcelas,
				'entrada'    	=> $pagamento->entrada,
				'parcelas'		=> array(),
			);

			$pagamentoarr[0]['parcelas'][] = array('vencimento' => date_format($pagamento->dataval1, 'd/m/Y'), 'valor' => $pagamento->valor1);

			if($pagamento->dataval2 != null)
			$pagamentoarr[0]['parcelas'][] = array('vencimento' => date_format($pagamento->dataval2, 'd/m/Y'), 'valor' => $pagamento->valor2);
			if($pagamento->dataval3 != null)
			$pagamentoarr[0]['parcelas'][] = array('vencimento' => date_format($pagamento->dataval3, 'd/m/Y'), 'valor' => $pagamento->valor3);
			if($pagamento->dataval4 != null)
			$pagamentoarr[0]['parcelas'][] = array('vencimento' => date_format($pagamento->dataval4, 'd/m/Y'), 'valor' => $pagamento->valor4);
			if($pagamento->dataval5 != null)
			$pagamentoarr[0]['parcelas'][] = array('vencimento' => date_format($pagamento->dataval5, 'd/m/Y'), 'valor' => $pagamento->valor5);
		}
		return $pagamentoarr;
	}

	public function itensArr($itens){
		$itensarr = [];
		foreach($itens as $row){
			$itensarr[] = array(
			'id'    		=> $row->id,
			'tipo'   		=> $row->tipo,
			'codproduto'    => $row->codproduto,
			'descricao'  	=> $row->descricao,
			'observacao'  	=> $row->observacao,
			'unidade'   	=> $row->unidade,
			'quantidade'    => $row->quantidade,
			'valorunitario' => $row->valorunitario,
			'valordesconto' => $row->valordesconto,
			'valortotal'   	=> $row->valortotal,
			'serialnumber'	=> $row->serialnumber,
			'ativo'   		=> $row->ativo,
			);
		}
		return $itensarr;
	}

    public function refreshAPI() {
        $this->autoRender = false;
        $reqMethod = $this->request->getMethod();
        $reqPath = $this->request->getRequestTarget();
        $hEmpresa = $this->request->getHeaderLine('empresa');
        $hToken = $this->request->getHeaderLine('token');
        $this->log('[API-ORDENS refreshAPI] request method=' . $reqMethod . ' path=' . $reqPath . ' headers(empresa=' . ($hEmpresa ?: 'vazio') . ' token=' . (strlen($hToken ?? '') ? '***' : 'vazio') . ')', 'info');

		$apiRet = function ($msg, $status = 200) {
			return $this->jsonResponse(['mensagem' => $msg, 'retorno' => $msg], $status);
		};
		if (!$this->request->is('put')) {
            $this->log('[API-ORDENS refreshAPI] resposta 405 metodo nao permitido', 'info');
			return $apiRet('Método não permitido. Use PUT.', 405);
		}
		list($empresa, $token, $erpCredErr) = ErpIntegrationRequest::readEmpresaAndToken(
			$this->request,
		);
		if ($erpCredErr !== null) {
			$this->log('[API-ORDENS refreshAPI] resposta 400 ' . $erpCredErr, 'info');

			return $apiRet($erpCredErr, 400);
		}
		// Aceitar JSON do body: getData() ou input('json_decode')
		$json = $this->request->getData();
		if (empty($json) || !is_array($json)) {
			$raw = $this->request->input('json_decode');
			$json = is_string($raw) ? json_decode($raw) : (is_array($raw) ? (object)$raw : $raw);
		} else {
			$json = (object) $json;
		}

			if ($json === null || !is_object($json)) {
				$this->log('[API-ORDENS refreshAPI] resposta 400 parametros invalidos (empresa/token/body)', 'info');
				return $apiRet('Parâmetros da requisição inválidos.', 400);
			}
			if (empty($token) || empty($empresa)) {
				$this->log('[API-ORDENS refreshAPI] resposta 400 parametros invalidos (empresa/token/body)', 'info');
				return $apiRet('Parâmetros da requisição inválidos.', 400);
			}
			if (!isset($json->nroordem) || $json->nroordem === '' || $json->nroordem === null) {
				$this->log('[API-ORDENS refreshAPI] resposta 400 parametros invalidos (sem nroordem)', 'info');
				return $apiRet('Parâmetros da requisição inválidos.', 400);
			}
			if(empty($this->Empresas->findById($empresa)->first())) {
				$this->log('[API-ORDENS refreshAPI] resposta 400 empresa nao encontrada id=' . $empresa, 'info');
				return $apiRet('Parâmetros da requisição inválidos.', 400);
			}
			if($token == $this->Empresas->get($empresa)->token){
				$ordem = $this->Ordensservico->findById($json->nroordem)->where(['idempresa' => $empresa])->first();

				if ($ordem == null) {
					$this->log('[API-ORDENS refreshAPI] resposta 400 ordem nao encontrada nroordem=' . ($json->nroordem ?? '?') . ' empresa=' . $empresa, 'info');
					return $apiRet('Parâmetros da requisição inválidos.', 400);
				}

				$sitantiga = $ordem->situacao;
				$ordem->nrodestino = $json->nrodestino;
				$ordem->situacao = $json->situacao;

                if($this->Ordensservico->save($ordem)){
					// criarMov(idordem, sitantiga, sitnova, idempresa, iduser, obs) — API não tem usuário logado, usar 0
					$iduser = $this->Auth->user('id');
					if ($iduser === null || $iduser === '') $iduser = 0;
					try {
						$this->Ordensservico->criarMov($ordem->id, $sitantiga, $ordem->situacao, $empresa, $iduser, 'Sincronização ERP');
					} catch (\Throwable $e) {
						$this->log('Ordensservico::refreshAPI criarMov: ' . $e->getMessage(), 'error');
					}
					$this->log('[API-ORDENS refreshAPI] resposta 201 ok ordem=' . $ordem->id . ' empresa=' . $empresa . ' situacao=' . $ordem->situacao, 'info');
					return $apiRet('Situação da Ordem de Serviço alterada com sucesso', 201);
				}
                else {
					$this->log('[API-ORDENS refreshAPI] resposta 400 erro ao salvar ordem', 'info');
					return $apiRet('Ocorreu um erro ao atualizar a ordem!', 400);
				}
			}
			$this->log('[API-ORDENS refreshAPI] resposta 401 autenticacao invalida empresa=' . $empresa, 'info');
			return $apiRet('Autenticação Inválida', 401);
	}

	public function imprimir($id = null){
		$idempresa = $this->Auth->user('idempresa');
		$idcliente = $this->Auth->user('idcliente');
		$ordem = $this->Ordensservico->find('all', ['contain' => ['Users', 'Clientes']])->where(['Ordensservico.id' => $id, 'Ordensservico.idempresa' => $idempresa])->first();
		$cidade = $this->Cidades->get($ordem->cliente->idcidade);
		$estado = $this->Estados->get($cidade->idestado);

		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$iditensPk = $this->getOrdemIditensPk($idempresa, $id);
		$carrinho = $iditensPk !== null
			? $this->Itensordem->findByIdordempk($iditensPk)->order(['id'])->toArray()
			: [];

		$this->set('cidade', $cidade->nome);
		$this->set('estado', $estado->nome);
		$this->set('carrinho', $carrinho);
		$this->set('idcliente', $idcliente);
		$this->set('idempresa', $idempresa);
		$this->set('ordem', $ordem);
		$this->set('title', 'Imprimir ordem de serviços');
	}

	public function imprimirordens(){
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$data = $this->request->getData();
		$idsRaw = isset($data['idsimprimir']) ? trim((string)$data['idsimprimir']) : '';
		if ($idsRaw === '') {
			$this->Flash->error('Nenhuma ordem selecionada para impressão.');
			return $this->redirect(['action' => 'index']);
		}
		$ids = [];
		foreach (explode(',', $idsRaw) as $part) {
			$n = (int)trim($part);
			if ($n > 0) {
				$ids[$n] = true;
			}
		}
		$ids = array_keys($ids);
		if ($ids === []) {
			$this->Flash->error('Nenhuma ordem válida selecionada para impressão.');
			return $this->redirect(['action' => 'index']);
		}

		$idempresa = $this->Auth->user('idempresa');
		$idcliente = $this->Auth->user('idcliente');
		$ordens = $this->Ordensservico->find('all', ['contain' => ['Users', 'Clientes']])
			->where(['Ordensservico.id IN' => $ids, 'Ordensservico.idempresa' => $idempresa])
			->toArray();

		$cidades = [];
		$estados = [];
		$carrinhos = [];
		$placeholderGeo = (object)['nome' => '—'];

		foreach ($ordens as $ordem) {
			$oid = (int)$ordem->id;
			if (!empty($ordem->cliente) && !empty($ordem->cliente->idcidade)) {
				try {
					$cidades[$oid] = $this->Cidades->get($ordem->cliente->idcidade);
					$estados[$oid] = $this->Estados->get($cidades[$oid]->idestado);
				} catch (\Throwable $e) {
					$this->log('Ordensservico::imprimirordens cidade/estado OS ' . $oid . ': ' . $e->getMessage(), 'warning');
					$cidades[$oid] = $placeholderGeo;
					$estados[$oid] = $placeholderGeo;
				}
			} else {
				$cidades[$oid] = $placeholderGeo;
				$estados[$oid] = $placeholderGeo;
			}

			$idcarrinho = $this->Ordemservicositens->find('all')
				->where(['idempresa' => $idempresa, 'idordem' => $ordem->id])
				->first();
			if ($idcarrinho !== null && !empty($idcarrinho->iditens)) {
				$carrinhos[$oid] = $this->Itensordem->findByIdordempk($idcarrinho->iditens)->order(['id'])->toArray();
			} else {
				$carrinhos[$oid] = [];
			}
		}

		$this->set('cidades', $cidades);
		$this->set('estados', $estados);
		$this->set('carrinhos', $carrinhos);
		$this->set('idcliente', $idcliente);
		$this->set('idempresa', $idempresa);
		$this->set('ordens', $ordens);
		$this->set('title', 'Imprimir ordens de serviços');
	}

	/**
	 * Dados somente leitura do bloco “Origem do ticket” em add.ctp (GET gerar-os ou POST add com idticket).
	 *
	 * @param \Cake\Datasource\EntityInterface $ticket Ticket já carregado com Clientes (campos mínimos).
	 */
	protected function buildTicketOrigemPanelView($ticket, int $idempresa): array {
		$idticket = (int)$ticket->id;

		$minutos = 0;
		try {
			$minutos = (int)$this->Ticketshoras->minutosTicket($idticket, '01/01/2000', '31/12/2099');
		} catch (\Throwable $e) {
		}

		$anexosUi = [];
		try {
			$anRows = $this->Ticketsanexos->find('all')->where(['idticket' => $idticket])->order(['id' => 'ASC'])->limit(30)->toArray();
			foreach ($anRows as $an) {
				$aid = (int)($an->id ?? 0);
				if ($aid <= 0) {
					continue;
				}
				$nome = (string)($an->arquivo ?? 'Anexo');
				$anexosUi[] = [
					'id' => $aid,
					'nome' => $nome,
					'url' => Router::url(['controller' => 'Tickets', 'action' => 'downloadAnexo', $aid]),
				];
			}
		} catch (\Throwable $e) {
		}

		$docCliente = '';
		$clienteNome = '';
		if (!empty($ticket->cliente)) {
			$docCliente = (string)(($ticket->cliente->tipo == C_ClientesTipoJuridica)
				? ($ticket->cliente->cnpj ?? '')
				: ($ticket->cliente->cpf ?? ''));
			$clienteNome = TicketOsPrefillService::clienteDisplayName($ticket->cliente);
		}

		$solicitanteLabel = '';
		$sid = (int)($ticket->idsolicitante ?? 0);
		if ($sid > 0) {
			try {
				$solU = $this->Users->find()->select(['name'])->where(['id' => $sid])->first();
				if ($solU !== null) {
					$solicitanteLabel = trim((string)($solU->name ?? ''));
				}
			} catch (\Throwable $e) {
			}
		}
		if ($solicitanteLabel === '' && !empty($ticket->nomesolicitante)) {
			$solicitanteLabel = trim((string)$ticket->nomesolicitante);
		}
		if ($solicitanteLabel === '') {
			$solicitanteLabel = '—';
		}

		$tecUserId = 0;
		$tSchema = $this->Tickets->getSchema();
		if ($tSchema->hasColumn('idtecnico_responsavel')) {
			$tecUserId = (int)($ticket->idtecnico_responsavel ?? 0);
		} elseif ($tSchema->hasColumn('owner_id')) {
			$tecUserId = (int)($ticket->owner_id ?? 0);
		}
		$tecnicoTicketNome = '';
		if ($tecUserId > 0) {
			try {
				$tu = $this->Users->find()->select(['name'])->where(['id' => $tecUserId])->first();
				if ($tu !== null) {
					$tecnicoTicketNome = trim((string)($tu->name ?? ''));
				}
			} catch (\Throwable $e) {
			}
		}
		if ($tecnicoTicketNome === '') {
			$tecnicoTicketNome = '—';
		}

		$ativosTicketLabels = [];
		try {
			$qTa = $this->TicketAssets->find()->contain(['Assets'])->where(['ticket_id' => $idticket]);
			if ($this->TicketAssets->getSchema()->hasColumn('idempresa')) {
				$qTa->where(['idempresa' => $idempresa]);
			}
			$ativosTicketLabels = TicketOsPrefillService::labelsAtivosTicket($qTa->toArray());
		} catch (\Throwable $e) {
		}

		$ticketAbertura = '';
		try {
			if (!empty($ticket->created) && is_object($ticket->created) && method_exists($ticket->created, 'format')) {
				$ticketAbertura = $ticket->created->format('d/m/Y H:i');
			}
		} catch (\Throwable $e) {
		}

		return [
			'id' => $idticket,
			'situacaoLabel' => TicketOsPrefillService::situacaoTicketLabel((int)($ticket->situacao ?? 0)),
			'abertura' => $ticketAbertura,
			'tempoRegistrado' => TicketOsPrefillService::formatMinutosRegistrados($minutos),
			'voltarUrl' => Router::url(['controller' => 'Servicedesk', 'action' => 'edit', $idticket, '?' => ['sd' => '1']]),
			'documentoCliente' => $docCliente,
			'clienteNome' => $clienteNome,
			'solicitanteLabel' => $solicitanteLabel,
			'tecnicoTicketNome' => $tecnicoTicketNome,
			'emailTicket' => trim((string)($ticket->email ?? '')),
			'ativos' => $ativosTicketLabels,
			'anexos' => $anexosUi,
		];
	}

	protected function _setOsAddViewContextManual(): void {
		$this->set('osOrigem', 'manual');
		$this->set('osFormPostAdd', false);
		$this->set('osOrigemTicketId', null);
		$this->set('ticketOrigemPanel', null);
		$this->set('idsolicitante', null);
	}

	/**
	 * Abre o mesmo formulário de cadastro de OS (add.ctp) pré-preenchido a partir do ticket.
	 * URL canónica: GET /tickets/:id/gerar-os (rota em config/routes.php).
	 */
	public function addFromTicket($id = null) {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		if ($id === null || $id === '' || !ctype_digit((string)$id)) {
			$this->Flash->error(
				'URL inválida: use o número do ticket (ex.: …/tickets/456/gerar-os).'
			);
			return $this->redirect(['controller' => 'Servicedesk', 'action' => 'index']);
		}

		$idticket = (int)$id;
		$idempresa = (int)$this->Auth->user('idempresa');
		$iduser = (int)$this->Auth->user('id');

		$ticket = $this->Tickets->findById($idticket)
			->contain([
				'Clientes' => ['fields' => ['id', 'contrato', 'tipo', 'cpf', 'cnpj', 'nome', 'razaosocial']],
			])
			->first();

		if ($ticket === null || (int)$ticket->idempresa !== $idempresa) {
			$this->Flash->error(__('Ticket não encontrado.'));
			return $this->redirect(['controller' => 'Servicedesk', 'action' => 'index']);
		}

		$osExistente = $this->Ordensservico->find()
			->where(['idticket' => $idticket, 'idempresa' => $idempresa])
			->first();
		if ($osExistente !== null) {
			$this->Flash->warning(__('Este ticket já possui ordem de serviço vinculada. Redirecionando para a OS existente.'));
			return $this->redirect(['action' => 'edit', $osExistente->id]);
		}

		$this->initCarrinhoSessionNovaOsTicket($idempresa, $iduser);
		$pkCarrinho = (string)($_SESSION['PGM_Ordem_Idcarrinhoadd'] ?? '');
		if ($pkCarrinho === '') {
			$this->Flash->error(__('Não foi possível iniciar o carrinho da OS. Tente novamente.'));
			return $this->redirect(['controller' => 'Servicedesk', 'action' => 'index']);
		}

		$this->seedCarrinhoComProdutosDoTicket($idticket, $pkCarrinho, $idempresa);

		$ordem = $this->Ordensservico->newEntity();
		$ordem->idcliente = $ticket->idcliente;
		$ordem->idsolicitante = $ticket->idsolicitante;
		$ordem->relato = TicketOsPrefillService::trunc((string)($ticket->solicitacao ?? ''), 200);
		$ordem->prioridade = TicketOsPrefillService::mapPrioridadeTicketParaOs($ticket->prioridade ?? null);
		$ordem->dataabertura = date('d/m/Y');
		$ordem->dataprevisao = date('d/m/Y', strtotime('+7 days'));
		if (!empty($ticket->email)) {
			$ordem->email = (string)$ticket->email;
		}

		$rep = null;
		try {
			$rep = $this->TechnicalReports->find()
				->where(['ticket_id' => $idticket, 'idempresa' => $idempresa])
				->order(['TechnicalReports.id' => 'DESC'])
				->first();
		} catch (\Throwable $e) {
		}
		$ordem->observacao = TicketOsPrefillService::buildOperationalObservacao(
			isset($ticket->descricao_atendimento) ? (string)$ticket->descricao_atendimento : null,
			$rep
		);
		$ordem->atendimento = 0;
		$this->clearFiscalFieldsFromTicketPrefill($ordem);

		$this->set('osOrigem', 'ticket');
		$this->set('osOrigemTicketId', $idticket);
		$this->set('osFormPostAdd', true);
		$this->set('idsolicitante', $ordem->idsolicitante);
		$this->set('ticketOrigemPanel', $this->buildTicketOrigemPanelView($ticket, $idempresa));

		// Reaproveita o mesmo carregamento de listas da OS manual (add).
		$this->_assignOsAddViewVars($ordem, $idempresa);
		$this->render('add');
	}

	/**
	 * @deprecated Use addFromTicket ou a rota /tickets/:id/gerar-os. Mantido para links antigos.
	 */
	public function ticketordem($idticket) {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		if ($idticket === null || $idticket === '' || !ctype_digit((string)$idticket)) {
			return $this->redirect(['controller' => 'Servicedesk', 'action' => 'index']);
		}

		return $this->redirect(['_name' => 'ticketsGerarOs', 'id' => (int)$idticket]);
	}

	/**
	 * Monta variáveis de view compartilhadas entre add() manual e addFromTicket().
	 */
	protected function _assignOsAddViewVars($ordem, $idempresa): void {
		$clientes = $this->Clientes->find('all')
			->select(['Clientes.id', 'Clientes.tipo', 'Clientes.razaosocial', 'Clientes.nome', 'Clientes.idcidade'])
			->where(['Clientes.idempresa' => $idempresa, 'Clientes.inativo' => 0])
			->contain(['Cidades' => ['fields' => ['Cidades.id', 'Cidades.nome']]])
			->order(['Clientes.razaosocial'])
			->toArray();

		$clientesOpt = [];
		foreach ($clientes as $reg) {
			$nomeCliente = ($reg->tipo == C_ClientesTipoJuridica) ? $reg->razaosocial : $reg->nome;
			$nomeCidade = (!empty($reg->cidade) && !empty($reg->cidade->nome)) ? $reg->cidade->nome : 'Sem Cidade';
			$clientesOpt[$reg->id] = $nomeCliente . ' (' . $nomeCidade . ')';
		}
		asort($clientesOpt);

		$areas = $this->Areas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])
			->where(['idempresa' => $idempresa])
			->order(['descricao'])
			->toArray();
		$problemas = $this->Problemas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])
			->where(['idempresa' => $idempresa])
			->order(['descricao'])
			->toArray();

		$produtosOpt = [];
		$tiposProdutosOs = $this->tiposValoresPermitidosListaProdutosOs();
		foreach ($this->Produtos->find('all')
			->select(['codigo', 'descricao'])
			->where(['idempresa' => $idempresa, 'ativo' => 1, 'tipo IN' => $tiposProdutosOs])
			->order(['descricao'])
			->toArray() as $reg) {
			$produtosOpt[] = ['codigo' => trim($reg->codigo), 'descricao' => trim($reg->descricao) . ' (' . trim($reg->codigo) . ')'];
		}

		$this->set('produtosMobile', $produtosOpt);
		$this->set('produtosOpt', json_encode($produtosOpt, JSON_PRETTY_PRINT));
		$this->set('tiposMobile', $this->tiposMobileOrdemServico());
		$this->set('tiposOpt', json_encode($this->tiposOptOrdemServicoParaRotulos(), JSON_PRETTY_PRINT));
		$this->set('tiposOptGridItems', json_encode($this->tiposOptGridItemsForJsGrid(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		$this->set('tipoProdutoMercadoriaOs', $this->tipoProdutoMercadoriaOrdemServico());
		$this->set('problemas', $problemas);
		$this->set('areas', $areas);
		$this->set('clientes', $clientesOpt);
		$this->set('ordem', $ordem);
		$this->set('title', 'Nova ordem de serviço');
		$this->set('bodyPageClass', 'os-add-page');
		$this->set('authIdempresa', (int)$idempresa);
		$this->set('osGridAjaxVerbose', $this->osGridDebugVerbose());
	}

	public function acaoindex() {
		$data = $this->request->getData();
		foreach(explode(',', $data['ids']) as $id) {
			$ordem = $this->Ordensservico->findById($id)->where(['idempresa' => $this->Auth->user('idempresa')])->first();
			if(!empty($ordem)) {
				$ordem->situacao = $data['situacao'];
				$this->Ordensservico->save($ordem);
				if(in_array($data['situacao'], [C_OrdensSituacaoLiberadaParaFaturamento, C_OrdensSituacaoCancelada])) {
					$ticket = $this->Tickets->findById($ordem->idticket)->where(['idempresa' => $this->Auth->user('idempresa')])->first();
					if(!empty($ticket)) {
						$sitantiga = $ticket->situacao;
						if($data['situacao'] == C_OrdensSituacaoLiberadaParaFaturamento) {
							$sitnova = C_TicketSituacaoResolvido;
							$observacao = "Ticket resolvido com a liberação da ordem de serviço nº $id";
							$acao1 = 'resolvido';
							$acao2 = 'a resolução';
							$acaoEmail = null;
						} else {
							$sitnova = C_TicketSituacaoFechado;
							$observacao = "Ticket cancelado com o cancelamento da ordem de serviço nº $id";
							$acao1 = 'cancelado';
							$acao2 = 'o cancelamento';
							$acaoEmail = C_TicketsAcaoFechado;
						}

						$ticket->situacao = $sitnova;
						if($this->Tickets->save($ticket)) {
							$emailDest = $this->Tickets->email($ordem->idticket, $acaoEmail, null, $this->Auth->user('idempresa'));
							$mov = $this->Ticketsmovs->newEntity();
							$mov->idticket = $ordem->idticket;
							$mov->sitantiga = $sitantiga;
							$mov->sitnova = $sitnova;
							$mov->idusuario = $this->Auth->user('id');
							$mov->idempresa = $this->Auth->user('idempresa');
							$mov->datetime = date('d/m/Y H:i:s', time());
							$mov->observacao = $observacao;
							$this->Ticketsmovs->save($mov);
							$this->Flash->success("O ticket $ordem->idticket foi $acao1 e um email foi enviado para $emailDest informando $acao2!");
						}
					}
				}

				if($data['situacao'] == C_OrdensSituacaoLiberadaParaFaturamento) {
					$pagamentosAnt = $this->Ordemparcelas->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'idordem' => $id])->toArray();
					if(empty($pagamentosAnt)) {
						$pagamento = $this->Ordemparcelas->newEntity();
						$pagamento = $this->Ordemparcelas->patchEntity($pagamento, $data);
						$pagamento->idempresa = $this->Auth->user('idempresa');
						$pagamento->iduser = $this->Auth->user('id');
						$pagamento->idordem = intval($id);
						$pagamento->data = date('d/m/Y', time());

						switch ($pagamento->nmrparcelas) {
							case '1': $pagamento->dataval2 = null;
									$pagamento->dataval3 = null;
									$pagamento->dataval4 = null;
									$pagamento->dataval5 = null; break;
							case '2': $pagamento->dataval3 = null;
									$pagamento->dataval4 = null;
									$pagamento->dataval5 = null; break;
							case '3': $pagamento->dataval4 = null;
									$pagamento->dataval5 = null; break;
							case '4': $pagamento->dataval5 = null; break;
							default: break;
						}

						$this->Ordemparcelas->save($pagamento);
					}
				}
			}
		}
		$this->Flash->success('As ordem selecionadas foram movidas para "'. DescricaoSituacaoOrdem($data['situacao']) .'" com sucesso!');
		return $this->redirect(['action' => 'index']);
	}

	public function locacao($id, $locacao) {
		$ordem = $this->Ordensservico->find('all')->where(['id' => $id, 'idempresa' => $this->Auth->user('idempresa')])->first();
		$ordem->locacao = $locacao;

		if ($this->Ordensservico->save($ordem)) $this->Flash->success('Informações da ordem de serviço alteradas com sucesso!');
		else $this->Flash->error('Ocorreu um erro ao salvar as informações da ordem de serviço.');
		return $this->redirect(['action' => 'edit', $id]);
	}

	// —— Relatórios (modelos em config/ordens_servico_relatorios.php) ——

	protected function _osRelatorioModelosConfig() {
		$path = ROOT . DS . 'config' . DS . 'ordens_servico_relatorios.php';
		if (!is_file($path)) {
			return [];
		}
		$cfg = include $path;
		return is_array($cfg) ? $cfg : [];
	}

	protected function _osRelatorioMetaPorId($modelo) {
		foreach ($this->_osRelatorioModelosConfig() as $m) {
			if (!empty($m['id']) && $m['id'] === $modelo) {
				return $m;
			}
		}
		return null;
	}

	protected function _normalizarFiltrosRelatorioOsFromRequest() {
		$cliente = $this->request->getQuery('cliente');
		$situacao = $this->request->getQuery('situacao');
		$problema = $this->request->getQuery('problema');
		$locacao = $this->request->getQuery('locacao');
		$solicitante = $this->request->getQuery('solicitante');
		$data_ini = $this->request->getQuery('data_ini');
		$data_fim = $this->request->getQuery('data_fim');
		$mes = $this->request->getQuery('mes');
		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			if (is_array($data)) {
				$cliente = $data['cliente'] ?? $cliente;
				$situacao = $data['situacao'] ?? $situacao;
				$problema = $data['problema'] ?? $problema;
				$locacao = $data['locacao'] ?? $locacao;
				$solicitante = $data['solicitante'] ?? $solicitante;
				$data_ini = $data['data_ini'] ?? $data_ini;
				$data_fim = $data['data_fim'] ?? $data_fim;
				$mes = $data['mes'] ?? $mes;
			}
		}
		$toScalar = function ($v) {
			if (is_array($v)) {
				return '';
			}
			if ($v === null) {
				return '';
			}
			return trim((string)$v);
		};
		$toDateYmd = function ($v) use ($toScalar) {
			if (is_array($v)) {
				$y = isset($v['year']) ? (int)$v['year'] : 0;
				$m = isset($v['month']) ? (int)$v['month'] : 0;
				$d = isset($v['day']) ? (int)$v['day'] : 0;
				if ($y > 0 && $m > 0 && $d > 0 && checkdate($m, $d, $y)) {
					return sprintf('%04d-%02d-%02d', $y, $m, $d);
				}
				return '';
			}
			$s = $toScalar($v);
			if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $s, $m)) {
				$dd = (int)$m[1];
				$mm = (int)$m[2];
				$yy = (int)$m[3];
				if (checkdate($mm, $dd, $yy)) {
					return sprintf('%04d-%02d-%02d', $yy, $mm, $dd);
				}
				return '';
			}
			return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s : '';
		};
		$toMonthYm = function ($v) use ($toScalar) {
			$s = $toScalar($v);
			if (preg_match('/^(\d{2})\/(\d{4})$/', $s, $m)) {
				$mm = (int)$m[1];
				$yy = (int)$m[2];
				if ($mm >= 1 && $mm <= 12) {
					return sprintf('%04d-%02d', $yy, $mm);
				}
				return '';
			}
			return preg_match('/^\d{4}-\d{2}$/', $s) ? $s : '';
		};
		$cliente = $toScalar($cliente);
		$situacao = $toScalar($situacao);
		$problema = $toScalar($problema);
		$locacao = $toScalar($locacao);
		$solicitante = $toScalar($solicitante);
		$data_ini = $toDateYmd($data_ini);
		$data_fim = $toDateYmd($data_fim);
		$mes = $toMonthYm($mes);
		if ((string)$cliente === '0') {
			$cliente = '';
		}
		if ((string)$problema === '0') {
			$problema = '';
		}
		if ($locacao === null || $locacao === '') {
			$locacao = -1;
		}
		if ((string)$solicitante === '0') {
			$solicitante = '';
		}
		if ($mes !== '' && preg_match('/^\d{4}-\d{2}$/', $mes)) {
			if ($data_ini === null || $data_ini === '') {
				$data_ini = $mes . '-01';
			}
			if ($data_fim === null || $data_fim === '') {
				$data_fim = date('Y-m-t', strtotime($mes . '-01'));
			}
		}
		if ($data_ini !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_ini)) {
			$data_ini = '';
		}
		if ($data_fim !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_fim)) {
			$data_fim = '';
		}
		$idsRaw = $this->request->getQuery('ids');
		if ($idsRaw === null || $idsRaw === '') {
			$idsRaw = $this->request->getData('ids');
		}
		$ids = [];
		if (is_array($idsRaw)) {
			foreach ($idsRaw as $v) {
				$n = (int)$v;
				if ($n > 0) {
					$ids[$n] = $n;
				}
			}
		} elseif ($idsRaw !== null && $idsRaw !== '') {
			foreach (explode(',', (string)$idsRaw) as $part) {
				$n = (int)trim($part);
				if ($n > 0) {
					$ids[$n] = $n;
				}
			}
		}
		$ids = array_values($ids);
		return compact('cliente', 'situacao', 'problema', 'locacao', 'solicitante', 'data_ini', 'data_fim', 'mes', 'ids');
	}

	protected function _aplicarFiltrosRelatorioOs($q, array $filtros, bool $ignorarSituacao = false) {
		if (!$ignorarSituacao && $filtros['situacao'] !== '' && $filtros['situacao'] !== null) {
			$q->where(['Ordensservico.situacao' => $filtros['situacao']]);
		}
		if ($filtros['cliente'] !== '' && $filtros['cliente'] !== null && (string)$filtros['cliente'] !== '0') {
			$q->where(['Ordensservico.idcliente' => $filtros['cliente']]);
		}
		if ($filtros['problema'] !== '' && $filtros['problema'] !== null && (string)$filtros['problema'] !== '0') {
			$q->where(['Ordensservico.idproblema' => $filtros['problema']]);
		}
		if ((string)$filtros['locacao'] !== '' && (string)$filtros['locacao'] !== '-1') {
			$q->where(['Ordensservico.locacao' => $filtros['locacao']]);
		}
		if (($filtros['solicitante'] ?? '') !== '' && (string)$filtros['solicitante'] !== '0') {
			$q->where(['Ordensservico.iduser' => $filtros['solicitante']]);
		}
		if (($filtros['data_ini'] ?? '') !== '') {
			$q->where(['Ordensservico.dataabertura >=' => $filtros['data_ini'] . ' 00:00:00']);
		}
		if (($filtros['data_fim'] ?? '') !== '') {
			$q->where(['Ordensservico.dataabertura <=' => $filtros['data_fim'] . ' 23:59:59']);
		}
		if (!empty($filtros['ids']) && is_array($filtros['ids'])) {
			$q->where(['Ordensservico.id IN' => $filtros['ids']]);
		}
	}

	protected function _fetchOrdensRelatorioLista(array $filtros, ?int $limit = null) {
		$idempresa = $this->Auth->user('idempresa');
		$q = $this->Ordensservico->find('all')
			->select([
				'Ordensservico.id',
				'Ordensservico.idempresa',
				'Ordensservico.idcliente',
				'Ordensservico.iduser',
				'Ordensservico.idproblema',
				'Ordensservico.situacao',
				'Ordensservico.locacao',
				'Ordensservico.dataabertura',
				'Ordensservico.dataprevisao',
				'Ordensservico.valortotal',
			])
			->where(['Ordensservico.idempresa' => $idempresa])
			->contain([
				'Clientes' => ['fields' => ['Clientes.id', 'Clientes.razaosocial', 'Clientes.tipo', 'Clientes.nome']],
				'Users' => ['fields' => ['Users.id', 'Users.name']],
			]);
		$this->_aplicarFiltrosRelatorioOs($q, $filtros, false);
		$q->order(['Ordensservico.id' => 'DESC']);
		if ($limit !== null && $limit > 0) {
			$q->limit($limit);
		}
		return $q->toArray();
	}

	protected function _fetchOrdensRelatorioResumo(array $filtros) {
		$idempresa = $this->Auth->user('idempresa');
		$q = $this->Ordensservico->find('all')
			->select([
				'Ordensservico.id',
				'Ordensservico.situacao',
				'Ordensservico.idcliente',
				'Ordensservico.iduser',
				'Ordensservico.idproblema',
				'Ordensservico.locacao',
				'Ordensservico.dataabertura',
			])
			->where(['Ordensservico.idempresa' => $idempresa])
			->contain([
				'Clientes' => ['fields' => ['Clientes.id', 'Clientes.razaosocial', 'Clientes.tipo', 'Clientes.nome']],
				'Users' => ['fields' => ['Users.id', 'Users.name']],
			]);
		$this->_aplicarFiltrosRelatorioOs($q, $filtros, true);
		return $q->toArray();
	}

	protected function _agruparResumoSituacao(array $ordens) {
		$map = [];
		foreach ($ordens as $o) {
			$k = (string)$o->situacao;
			if (!isset($map[$k])) {
				$map[$k] = [
					'situacao' => $o->situacao,
					'label' => trim(strip_tags((string)SituacaoOrdem($o->situacao))),
					'total' => 0,
				];
			}
			$map[$k]['total']++;
		}
		$out = array_values($map);
		usort($out, function ($a, $b) {
			return $b['total'] <=> $a['total'];
		});
		return $out;
	}

	protected function _rotulosFiltrosRelatorio(array $filtros, array $clientesOpt, array $problemasOpt, array $usuariosOpt = []) {
		$s = $filtros['situacao'];
		if ($s === '' || $s === null) {
			$sitLbl = 'Todas';
		} elseif (function_exists('DescricaoSituacaoOrdem')) {
			$sitLbl = (string)DescricaoSituacaoOrdem($s);
		} else {
			$sitLbl = (string)(C_OrdensSituacao[$s] ?? C_OrdensSituacao[(int)$s] ?? $s);
		}
		$c = $filtros['cliente'];
		$cliLbl = ($c === '' || $c === null || (string)$c === '0') ? 'Todos' : (string)($clientesOpt[$c] ?? $c);
		$p = $filtros['problema'];
		$probLbl = ($p === '' || $p === null || (string)$p === '0') ? 'Todos' : (string)($problemasOpt[$p] ?? $p);
		$l = $filtros['locacao'];
		$locLbl = ((string)$l === '-1' || $l === '' || $l === null) ? 'Todos' : (string)(C_OrdensLocacao[$l] ?? $l);
		$u = $filtros['solicitante'] ?? '';
		$solLbl = ($u === '' || $u === null || (string)$u === '0') ? 'Todos' : (string)($usuariosOpt[$u] ?? $u);
		$di = trim((string)($filtros['data_ini'] ?? ''));
		$df = trim((string)($filtros['data_fim'] ?? ''));
		$perLbl = 'Todos';
		if ($di !== '' || $df !== '') {
			$perLbl = ($di !== '' ? $di : '...') . ' até ' . ($df !== '' ? $df : '...');
		}
		return [
			'situacao' => $sitLbl,
			'cliente' => $cliLbl,
			'problema' => $probLbl,
			'locacao' => $locLbl,
			'solicitante' => $solLbl,
			'periodo' => $perLbl,
		];
	}

	protected function _dadosRelatorioOs($modelo, array $filtros) {
		$idempresa = $this->Auth->user('idempresa');
		$problemas = $this->Problemas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])
			->where(['idempresa' => $idempresa])->order(['descricao'])->toArray();
		if ($modelo === 'lista_filtrada') {
			$ordens = $this->_fetchOrdensRelatorioLista($filtros);
			return [
				'ordens' => $ordens,
				'resumoSituacao' => [],
				'problemas' => $problemas,
			];
		}
		if ($modelo === 'resumo_situacao') {
			$ordensFull = $this->_fetchOrdensRelatorioResumo($filtros);
			return [
				'ordens' => [],
				'resumoSituacao' => $this->_agruparResumoSituacao($ordensFull),
				'problemas' => $problemas,
			];
		}
		return null;
	}

	protected function _renderRelatorioOsPdfHtml($modelo, array $filtros, $titulo, array $filtrosRotulo, array $payload) {
		$nomeEmp = '';
		try {
			$eid = $this->Auth->user('idempresa');
			if ($eid) {
				$nomeEmp = (string)$this->Empresas->get($eid)->razaosocial;
			}
		} catch (\Throwable $e) {
			$nomeEmp = '';
		}
		$view = new View($this->request, $this->response, $this->getEventManager(), ['layout' => false]);
		$view->setTemplatePath('Ordensservico');
		$view->set(array_merge(
			[
				'modeloRelatorio' => $modelo,
				'tituloRelatorio' => $titulo,
				'filtros' => $filtros,
				'filtrosRotulo' => $filtrosRotulo,
				'nomeempresa' => $nomeEmp,
			],
			$payload
		));
		return $view->render('relatorio_pdf');
	}

	public function relatorios() {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		$idempresa = $this->Auth->user('idempresa');
		$filtros = $this->_normalizarFiltrosRelatorioOsFromRequest();

		$problemas1 = $this->Problemas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])
			->where(['idempresa' => $idempresa])->order(['descricao'])->toArray();
		$clientesOpt = [];
		$clientes = $this->Clientes->find('all')->where(['idempresa' => $idempresa, 'inativo' => 0])->order(['razaosocial'])->toArray();
		foreach ($clientes as $reg) {
			$clientesOpt[$reg->id] = $reg->tipo == C_ClientesTipoJuridica ? $reg->razaosocial : $reg->nome;
		}
		asort($clientesOpt);
		$usuariosOpt = $this->Users->find('list', ['keyField' => 'id', 'valueField' => 'name'])
			->where(['role' => C_RoleFuncionario, 'inativo' => 0])
			->order(['name' => 'ASC'])
			->toArray();
		$ordensSelecionaveis = $this->_fetchOrdensRelatorioLista($filtros, 300);

		$this->set('modelosRelatorio', $this->_osRelatorioModelosConfig());
		$this->set('cliente', $filtros['cliente']);
		$this->set('situacao', $filtros['situacao']);
		$this->set('problema', $filtros['problema']);
		$this->set('locacao', $filtros['locacao']);
		$this->set('solicitante', $filtros['solicitante']);
		$this->set('data_ini', $filtros['data_ini']);
		$this->set('data_fim', $filtros['data_fim']);
		$this->set('mes', $filtros['mes']);
		$this->set('idsSelecionados', $filtros['ids']);
		$this->set('problemas', $problemas1);
		$this->set('clientes', $clientesOpt);
		$this->set('usuarios', $usuariosOpt);
		$this->set('ordensSelecionaveis', $ordensSelecionaveis);
		$this->set('title', 'Relatórios — Ordens de Serviço');
		$this->set('hideLayoutPageTitle', true);
	}

	public function relatorioVer($modelo = null) {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		$meta = $this->_osRelatorioMetaPorId((string)$modelo);
		if ($meta === null) {
			throw new NotFoundException(__('Modelo de relatório inválido.'));
		}
		$idempresa = $this->Auth->user('idempresa');
		$filtros = $this->_normalizarFiltrosRelatorioOsFromRequest();
		$problemasOpt = $this->Problemas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])
			->where(['idempresa' => $idempresa])->order(['descricao'])->toArray();
		$clientesOpt = [];
		foreach ($this->Clientes->find('all')->where(['idempresa' => $idempresa, 'inativo' => 0])->toArray() as $reg) {
			$clientesOpt[$reg->id] = $reg->tipo == C_ClientesTipoJuridica ? $reg->razaosocial : $reg->nome;
		}
		$usuariosOpt = $this->Users->find('list', ['keyField' => 'id', 'valueField' => 'name'])
			->where(['role' => C_RoleFuncionario, 'inativo' => 0])
			->order(['name' => 'ASC'])
			->toArray();
		$filtrosRotulo = $this->_rotulosFiltrosRelatorio($filtros, $clientesOpt, $problemasOpt, $usuariosOpt);
		$payload = $this->_dadosRelatorioOs((string)$modelo, $filtros);
		if ($payload === null) {
			throw new NotFoundException(__('Modelo de relatório inválido.'));
		}
		$this->set('modeloRelatorio', (string)$modelo);
		$this->set('tituloRelatorio', $meta['titulo']);
		$this->set('filtros', $filtros);
		$this->set('filtrosRotulo', $filtrosRotulo);
		$this->set($payload);
		$nomeEmp = '';
		try {
			if ($idempresa) {
				$nomeEmp = (string)$this->Empresas->get($idempresa)->razaosocial;
			}
		} catch (\Throwable $e) {
			$nomeEmp = '';
		}
		$this->set('nomeempresa', $nomeEmp);
		$this->set('hideLayoutPageTitle', true);
	}

	public function relatorioPdf($modelo = null) {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		$meta = $this->_osRelatorioMetaPorId((string)$modelo);
		if ($meta === null) {
			throw new NotFoundException(__('Modelo de relatório inválido.'));
		}
		if (!class_exists(\Mpdf\Mpdf::class)) {
			$this->Flash->error('Biblioteca mPDF não instalada. Execute: composer require mpdf/mpdf');
			return $this->redirect(['action' => 'relatorios', '?' => $this->request->getQueryParams()]);
		}
		$idempresa = $this->Auth->user('idempresa');
		$filtros = $this->_normalizarFiltrosRelatorioOsFromRequest();
		$problemasOpt = $this->Problemas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])
			->where(['idempresa' => $idempresa])->order(['descricao'])->toArray();
		$clientesOpt = [];
		foreach ($this->Clientes->find('all')->where(['idempresa' => $idempresa, 'inativo' => 0])->toArray() as $reg) {
			$clientesOpt[$reg->id] = $reg->tipo == C_ClientesTipoJuridica ? $reg->razaosocial : $reg->nome;
		}
		$usuariosOpt = $this->Users->find('list', ['keyField' => 'id', 'valueField' => 'name'])
			->where(['role' => C_RoleFuncionario, 'inativo' => 0])
			->order(['name' => 'ASC'])
			->toArray();
		$filtrosRotulo = $this->_rotulosFiltrosRelatorio($filtros, $clientesOpt, $problemasOpt, $usuariosOpt);
		$payload = $this->_dadosRelatorioOs((string)$modelo, $filtros);
		if ($payload === null) {
			throw new NotFoundException(__('Modelo de relatório inválido.'));
		}
		$html = $this->_renderRelatorioOsPdfHtml(
			(string)$modelo,
			$filtros,
			$meta['titulo'],
			$filtrosRotulo,
			$payload
		);
		$tmpDir = TMP . 'mpdf' . DS;
		if (!is_dir($tmpDir)) {
			mkdir($tmpDir, 0775, true);
		}
		$mpdf = new \Mpdf\Mpdf([
			'mode' => 'utf-8',
			'format' => 'A4-L',
			'tempDir' => $tmpDir,
		]);
		$mpdf->WriteHTML($html);
		$pdf = $mpdf->Output('', 'S');
		$fn = 'Relatorio-OS-' . preg_replace('/[^a-z0-9_-]+/i', '_', (string)$modelo) . '-' . date('Ymd-His') . '.pdf';
		return $this->response
			->withType('application/pdf')
			->withDownload($fn)
			->withStringBody($pdf);
	}

	public function relatorioEnviarEmail() {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		if (!$this->request->is('post')) {
			return $this->redirect(['action' => 'relatorios']);
		}
		$data = $this->request->getData();
		$to = trim((string)($data['email_destino'] ?? ''));
		$modelo = (string)($data['modelo'] ?? '');
		if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
			$this->Flash->error('Informe um e-mail destino válido.');
			$d = $this->request->getData();
			return $this->redirect(['action' => 'relatorios', '?' => [
				'cliente' => $d['cliente'] ?? '',
				'situacao' => $d['situacao'] ?? '',
				'problema' => $d['problema'] ?? '',
				'locacao' => $d['locacao'] ?? '',
				'solicitante' => $d['solicitante'] ?? '',
				'data_ini' => $d['data_ini'] ?? '',
				'data_fim' => $d['data_fim'] ?? '',
				'mes' => $d['mes'] ?? '',
			]]);
		}
		$meta = $this->_osRelatorioMetaPorId($modelo);
		if ($meta === null) {
			$this->Flash->error('Modelo de relatório inválido.');
			return $this->redirect(['action' => 'relatorios']);
		}
		if (!class_exists(\Mpdf\Mpdf::class)) {
			$this->Flash->error('mPDF não está disponível; não é possível anexar o PDF. Instale: composer require mpdf/mpdf');
			$d = $this->request->getData();
			return $this->redirect(['action' => 'relatorios', '?' => [
				'cliente' => $d['cliente'] ?? '',
				'situacao' => $d['situacao'] ?? '',
				'problema' => $d['problema'] ?? '',
				'locacao' => $d['locacao'] ?? '',
				'solicitante' => $d['solicitante'] ?? '',
				'data_ini' => $d['data_ini'] ?? '',
				'data_fim' => $d['data_fim'] ?? '',
				'mes' => $d['mes'] ?? '',
				'ids' => $d['ids'] ?? '',
			]]);
		}
		$idempresa = $this->Auth->user('idempresa');
		$filtros = $this->_normalizarFiltrosRelatorioOsFromRequest();
		$problemasOpt = $this->Problemas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])
			->where(['idempresa' => $idempresa])->order(['descricao'])->toArray();
		$clientesOpt = [];
		foreach ($this->Clientes->find('all')->where(['idempresa' => $idempresa, 'inativo' => 0])->toArray() as $reg) {
			$clientesOpt[$reg->id] = $reg->tipo == C_ClientesTipoJuridica ? $reg->razaosocial : $reg->nome;
		}
		$usuariosOpt = $this->Users->find('list', ['keyField' => 'id', 'valueField' => 'name'])
			->where(['role' => C_RoleFuncionario, 'inativo' => 0])
			->order(['name' => 'ASC'])
			->toArray();
		$filtrosRotulo = $this->_rotulosFiltrosRelatorio($filtros, $clientesOpt, $problemasOpt, $usuariosOpt);
		$payload = $this->_dadosRelatorioOs($modelo, $filtros);
		if ($payload === null) {
			$this->Flash->error('Modelo de relatório inválido.');
			$d = $this->request->getData();
			return $this->redirect(['action' => 'relatorios', '?' => [
				'cliente' => $d['cliente'] ?? '',
				'situacao' => $d['situacao'] ?? '',
				'problema' => $d['problema'] ?? '',
				'locacao' => $d['locacao'] ?? '',
				'solicitante' => $d['solicitante'] ?? '',
				'data_ini' => $d['data_ini'] ?? '',
				'data_fim' => $d['data_fim'] ?? '',
				'mes' => $d['mes'] ?? '',
				'ids' => $d['ids'] ?? '',
			]]);
		}
		$html = $this->_renderRelatorioOsPdfHtml($modelo, $filtros, $meta['titulo'], $filtrosRotulo, $payload);
		$tmpDir = TMP . 'mpdf' . DS;
		if (!is_dir($tmpDir)) {
			mkdir($tmpDir, 0775, true);
		}
		$mp = new \Mpdf\Mpdf([
			'mode' => 'utf-8',
			'format' => 'A4-L',
			'tempDir' => $tmpDir,
		]);
		$mp->WriteHTML($html);
		$pdfBinary = $mp->Output('', 'S');
		$attachName = 'Relatorio-OS-' . preg_replace('/[^a-z0-9_-]+/i', '_', $modelo) . '.pdf';
		$msg = trim((string)($data['mensagem'] ?? ''));
		if ($msg === '') {
			$msg = 'Segue em anexo o relatório de Ordens de Serviço (' . $meta['titulo'] . ').';
		}
		$transportEmail = ((int)$idempresa === (int)C_EmpresaMaster) ? 'master' : 'pgm';
		$nomeEmpresa = 'Portal';
		$from = 'helpdesk@pgm.inf.br';
		try {
			$empresa = $this->Empresas->find('all')
				->select(['id', 'nomefantasia', 'razaosocial'])
				->where(['id' => $idempresa])
				->first();
			if (!empty($empresa)) {
				if (!empty($empresa->nomefantasia)) {
					$nomeEmpresa = (string)$empresa->nomefantasia;
				} elseif (!empty($empresa->razaosocial)) {
					$nomeEmpresa = (string)$empresa->razaosocial;
				}
			}
		} catch (\Throwable $e) {
			// Mantem fallback do remetente caso falhe consulta da empresa.
		}
		try {
			$email = new Email();
			$email->transport($transportEmail);
			$email->from([$from => $nomeEmpresa])
				->to($to)
				->emailFormat('text')
				->subject('Relatório — Ordens de Serviço — ' . $meta['titulo'])
				->attachments([
					$attachName => [
						'data' => $pdfBinary,
						'mimetype' => 'application/pdf',
					],
				]);
			$email->send($msg);
			$this->Flash->success('Relatório enviado por e-mail para ' . $to . '.');
		} catch (\Throwable $e) {
			$this->log('Ordensservico::relatorioEnviarEmail transport=' . $transportEmail . ': ' . $e->getMessage(), 'error');
			$msgErro = 'Não foi possível enviar o e-mail. Verifique a configuração de e-mail do sistema.';
			if ((bool)\Cake\Core\Configure::read('debug')) {
				$msgErro .= ' Detalhes: [' . $transportEmail . '] ' . $e->getMessage();
			}
			$this->Flash->error($msgErro);
		}
		return $this->redirect(['action' => 'relatorios', '?' => $filtros]);
	}

	/**
	 * Tipos de `produtos.tipo` permitidos em linhas novas da OS (convenção real do banco PGM: 1 mercadoria, 2 serviço, 3 contrato).
	 * Não usa C_ProdutosTipoProduto/Servico: as constantes globais são 0-based e divergem desta base.
	 *
	 * @return array<int, string>
	 */
	protected function tiposItensPermitidosOrdemServico(): array {
		return [
			1 => 'Produto',
			2 => 'Serviço',
		];
	}

	/**
	 * Valores numéricos permitidos na listagem/pesquisa e no filtro `tipo IN` da OS.
	 *
	 * @return array<int, int>
	 */
	protected function tiposValoresPermitidosListaProdutosOs(): array {
		return array_keys($this->tiposItensPermitidosOrdemServico());
	}

	/**
	 * `produtos.tipo` da mercadoria para preço/estoque ERP na OS (somente tipo 1).
	 */
	protected function tipoProdutoMercadoriaOrdemServico(): int {
		return 1;
	}

	/**
	 * Rótulos para `tiposOpt` nas telas de OS: inclui tipo 3 para linhas antigas no carrinho; grid novo só oferece 1 e 2.
	 *
	 * @return array<int, string>
	 */
	protected function tiposOptOrdemServicoParaRotulos(): array {
		return $this->tiposItensPermitidosOrdemServico() + [3 => 'Contrato'];
	}

	/**
	 * Select de tipo no formulário mobile da OS — mesmo conjunto do grid (1 Produto, 2 Serviço).
	 *
	 * @return array<int, string>
	 */
	protected function tiposMobileOrdemServico(): array {
		return $this->tiposItensPermitidosOrdemServico();
	}

	/**
	 * Itens para o campo jsGrid `tipo` com valueField/textField (apenas 1 e 2).
	 *
	 * @return array<int, array{value: int, text: string}>
	 */
	protected function tiposOptGridItemsForJsGrid(): array {
		$out = [];
		foreach ($this->tiposItensPermitidosOrdemServico() as $id => $label) {
			$out[] = ['value' => (int)$id, 'text' => (string)$label];
		}

		return $out;
	}
}
