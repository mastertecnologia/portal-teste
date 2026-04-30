<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Service\Common\ModelService;
use App\Service\Ticket\DashboardService;
use App\Service\Ticket\SlaService;
use App\Service\Ticket\TicketHistoryLogger;
use App\Service\Ticket\TicketInternalNotificationHelper;
use App\Service\Ticket\ServiceDeskAlertService;
use App\Service\Ticket\ServiceDeskContractHoursService;
use App\Service\Ticket\TicketServiceDeskApiService;
use App\Service\Ticket\TicketAttendimentoTimerService;
use App\Service\Ticket\TicketClassificationService;
use App\Service\Ticket\WorkflowService;
use App\Service\Ticket\TicketWorklogEventHelper;
use App\Service\Clientes\ClienteCorrelatedIds;
use App\Utility\ErpGridUrl;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Core\Configure;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\Mailer\Email;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use CakeSoap\Network\CakeSoap;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'Utilities.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'TicketConstants.php');
$__cakeSoap = ROOT . DS . 'vendor' . DS . 'queencitycodefactory' . DS . 'cakesoap' . DS . 'src' . DS . 'Network' . DS . 'CakeSoap.php';
if (is_file($__cakeSoap)) {
	require_once $__cakeSoap;
}
require_once ROOT . DS . 'config' . DS . 'ticket_workflow_constants.php';

//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/Utilities.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/TicketConstants.php';

class TicketsController extends AppController {
	public function initialize() {
		parent::initialize();
		// Usar ModelService em vez de 28 loadModel repetidos
		// Reduz drasticamente a duplicação de código e melhora performance via cache
		ModelService::loadModelsIntoController($this, [
			'Tickets', 'Users', 'Ticketsusers', 'Ticketsanexos', 'Ticketcomentarios',
			'Ticketshoras', 'Ticketsmovs', 'Notificacoes', 'Ticketsservicos', 'Ticketsmodulos',
			'Ticketslogemail', 'Clientes', 'Cliservicos', 'Climodulos', 'Homologacoes',
			'Servicos', 'Modulos', 'Faturas', 'Faturaparcelas', 'Cancelamento',
			'Empresas', 'Empresasusers', 'Ordensservico', 'Config', 'Queues',
			'QueuesUsers', 'SupportLevels', 'TicketEvents', 'TicketProducts', 'Produtos',
			'TechnicalReports', 'TicketChecklists', 'Assets', 'Holidays', 'TicketMessages', 'ContratosHoras',
		]);
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		// Endpoints JSON de vínculo/desvínculo de CI usam fetch() sem _Token de form.
		$existingUnlocked = (array)$this->Security->getConfig('unlockedActions');
		$this->Security->setConfig('unlockedActions', array_values(array_unique(array_merge(
			$existingUnlocked,
			[
				'apiTicketAssetsAttach',
				'apiTicketAssetsDetach',
				'apiTimeEntries',
				'apiPatchAssignment',
				'apiPatchTicketStatus',
				'apiPatchTicketPriority',
				'apiPatchTicketSubject',
			]
		))));
	}

	public function isAuthorized($user) {
		$action = $this->request->getParam('action');

		if ($action === 'apiIndex' || $action === 'apiDashboardOperacional') {
			return (int)$user['role'] === 0;
		}
		if ($action === 'apiIndexCliente') {
			return (int)$user['role'] === 1;
		}
		if (in_array($action, ['apiView', 'apiSaveTicket', 'apiComments', 'apiAnexoUpload', 'apiAnexoDelete'], true)) {
			return in_array((int)$user['role'], [0, 1], true);
		}
		if (in_array($action, [
			'apiTecnicosLista',
			'apiTicketSubjectOptions',
			'apiTransferirTicket',
			'apiStartTicket',
			'startTicket',
			'apiTimer',
			'apiAlterarSituacao',
			'apiPatchAssignment',
			'apiPatchTicketStatus',
			'apiPatchTicketPriority',
			'apiPatchTicketSubject',
		], true)) {
			return (int)$user['role'] === 0;
		}
		if ($action === 'apiTimeEntries') {
			return (int)$user['role'] === 0;
		}
		if (in_array($action, ['apiTimeline', 'apiTicketMessages', 'apiRealtimeToken', 'apiServicedeskData'], true)) {
			return in_array((int)$user['role'], [0, 1], true);
		}
		if (in_array($action, ['apiValidateGeolocation'], true)) {
			return (int)$user['role'] === 0;
		}
		if (in_array($action, ['apiTicketSignature', 'apiAddTicketProduct', 'apiTicketProductSearch', 'apiAddEvidencePhoto', 'apiPdfTicketOs', 'apiPdfLaudo'], true)) {
			return (int)$user['role'] === 0;
		}
		if (in_array($action, ['apiTicketAssetsAttach', 'apiTicketAssetsDetach'], true)) {
			return (int)$user['role'] === 0;
		}
		if ($action === 'operacional') {
			return (int)$user['role'] === 0;
		}

		if ($user['role'] == 0 and $action != 'indexcliente') return true;
		else if ($user['role'] == 1 and in_array($action, [
			'indexcliente', 'add', 'assuntoTicket', 'view', 'cancelar', 'downloadAnexo', 'imprimir',
		])) return true;

		return false;
	}

	/** Query string para manter o shell Service Desk em links gerados pela API. */
	protected function _ticketUiQuery(): array {
		return $this->request->getQuery('sd') === '1' ? ['sd' => '1'] : [];
	}

	protected function _ticketUrl(array $parts): string {
		$extra = $this->_ticketUiQuery();
		if ($extra === []) {
			return Router::url($parts);
		}
		$q = isset($parts['?']) && is_array($parts['?']) ? array_merge($extra, $parts['?']) : $extra;
		$parts['?'] = $q;

		return Router::url($parts);
	}

	/** Corpo JSON (PATCH) como array; evita TypeError se getData() não for array. */
	protected function _jsonPatchRequestBody(): array {
		$body = $this->request->input('json_decode', true);
		if (!is_array($body)) {
			$body = $this->request->getData();
		}

		return is_array($body) ? $body : [];
	}

	/**
	 * ISO-8601 para o payload da grid; TIMESTAMPTZ pode hidratar como string no PostgreSQL.
	 */
	protected function _ticketTimerIsoForJson($value): ?string {
		if ($value === null || $value === '') {
			return null;
		}
		if ($value instanceof \DateTimeInterface) {
			return $value->format('c');
		}
		if (is_string($value)) {
			try {
				return (new \DateTimeImmutable($value))->format('c');
			} catch (\Throwable $e) {
				return null;
			}
		}
		if (is_object($value) && method_exists($value, 'format')) {
			try {
				return $value->format('c');
			} catch (\Throwable $e) {
				return null;
			}
		}

		return null;
	}

	/**
	 * PostgreSQL (25P02): após um comando falhar dentro de transação, a conexão fica
	 * abortada até ROLLBACK; sem isso, o próximo find/save no mesmo pedido falha.
	 */
	protected function _ticketDbRollbackSafe(): void {
		try {
			$this->Tickets->getConnection()->rollback();
		} catch (\Throwable $e) {
			// Sem transação ativa ou driver: ignorar.
		}
	}

	/** Log da exceção raiz (cadeia getPrevious) nos PATCH Service Desk. */
	protected function _logTicketPatchRootCause(string $label, \Throwable $e): void {
		$parts = [$label, $e->getMessage(), basename($e->getFile()) . ':' . $e->getLine()];
		$w = $e->getPrevious();
		$d = 0;
		while ($w !== null && $d < 6) {
			$parts[] = 'prev: ' . $w->getMessage();
			$w = $w->getPrevious();
			$d++;
		}
		$this->log(implode(' | ', $parts), 'error');
	}

	/** PATCH inline: basta coluna de responsável (não exige workflow/fila legada como apiTransferir). */
	protected function _ticketInlineAssignmentAllowed(): bool {
		$cols = $this->Tickets->getSchema()->columns();

		return in_array('idtecnico_responsavel', $cols, true) || in_array('owner_id', $cols, true);
	}

	/**
	 * Colunas a atualizar no UPDATE de responsável (idtecnico_responsavel tem precedência sobre owner_id).
	 *
	 * @return array<string,int>
	 */
	protected function _ticketAssignResponsavelSetArray(int $tecnicoId, array $tcols): array {
		if (in_array('idtecnico_responsavel', $tcols, true)) {
			return ['idtecnico_responsavel' => $tecnicoId];
		}
		if (in_array('owner_id', $tcols, true)) {
			return ['owner_id' => $tecnicoId];
		}

		return [];
	}

	/** datafinalizado conforme tipo da coluna (PostgreSQL date/timestamp vs string legada). */
	protected function _ticketSetDatafinalizadoPorSituacao($ticket, int $situacaoNovaInt, array $tcols): void {
		if (!in_array('datafinalizado', $tcols, true)) {
			return;
		}
		if ($situacaoNovaInt !== (int)C_TicketSituacaoResolvido && $situacaoNovaInt !== (int)C_TicketSituacaoFechado) {
			return;
		}
		$colDef = $this->Tickets->getSchema()->getColumn('datafinalizado');
		$ct = '';
		if (is_array($colDef) && !empty($colDef['type'])) {
			$ct = strtolower((string)$colDef['type']);
		} elseif (is_object($colDef) && method_exists($colDef, 'getType')) {
			$ct = strtolower((string)$colDef->getType());
		}
		if ($ct === 'date') {
			$ticket->datafinalizado = (new \DateTimeImmutable('today'))->format('Y-m-d');
		} elseif ($ct === 'datetime' || $ct === 'timestamp' || $ct === 'timestampfractional' || strpos($ct, 'time') !== false) {
			$ticket->datafinalizado = date('Y-m-d H:i:s');
		} else {
			$ticket->datafinalizado = date('d/m/Y');
		}
	}

	/**
	 * @param mixed $rawInput
	 * @param mixed $normalizedValue
	 */
	protected function _ticketPatchSaveDebug(\Cake\Datasource\EntityInterface $entity, string $primaryField, $rawInput, $normalizedValue): array {
		return [
			'field' => $primaryField,
			'attemptedValue' => $rawInput,
			'normalizedValue' => $normalizedValue,
			'dirtyFields' => array_values($entity->getDirty()),
		];
	}

	/** debug mínimo quando não há entidade (erros pós-parse, movimentos, etc.). */
	protected function _ticketPatchDebugStub(string $field, $attemptedValue, $normalizedValue = null): array {
		return [
			'field' => $field,
			'attemptedValue' => $attemptedValue,
			'normalizedValue' => $normalizedValue,
			'dirtyFields' => [],
		];
	}

	/**
	 * Decodifica exceção ticket_validation:{json} (formato novo com errors+debug ou legado só errors).
	 *
	 * @return array{errors:array,debug:?array}|null
	 */
	protected function _ticketParseTicketValidationMessage(string $msg): ?array {
		if (strpos($msg, 'ticket_validation:') !== 0) {
			return null;
		}
		$tail = substr($msg, strlen('ticket_validation:'));
		$decoded = json_decode($tail, true);
		if (!is_array($decoded)) {
			return ['errors' => ['_root' => [$tail]], 'debug' => null];
		}
		if (isset($decoded['errors']) || isset($decoded['debug'])) {
			$err = isset($decoded['errors']) && is_array($decoded['errors']) ? $decoded['errors'] : [];

			return ['errors' => $err, 'debug' => isset($decoded['debug']) && is_array($decoded['debug']) ? $decoded['debug'] : null];
		}

		return ['errors' => $decoded, 'debug' => null];
	}

	/**
	 * URL do socket.io para o browser. Se env aponta a loopback, substitui pelo host
	 * do pedido (nunca enviar 127.0.0.1/localhost no JSON — o cliente resolve isso
	 * no PC do utilizador). Esquema segue a página (HTTPS) ou X-Forwarded-Proto.
	 */
	protected function _publicServiceDeskSocketUrl(): string {
		$url = (string)env('PGM_SERVICE_DESK_SOCKET', 'http://127.0.0.1:3331');
		$parts = parse_url($url);
		if (empty($parts['host'])) {
			return $url;
		}
		$localHosts = ['127.0.0.1', 'localhost', '::1'];
		if (!in_array($parts['host'], $localHosts, true)) {
			return $url;
		}
		$port = isset($parts['port']) ? (int)$parts['port'] : 3331;
		$xfProto = strtolower((string)$this->request->getHeaderLine('X-Forwarded-Proto'));
		$secure = $this->request->is('ssl') || $xfProto === 'https';
		$scheme = $secure ? 'https' : ($parts['scheme'] ?? 'http');
		$host = (string)$this->request->getUri()->getHost();
		// Página HTTPS: o servidor Node em 3331 é HTTP puro; WSS em :3331 falha. O cliente
		// deve usar wss na mesma origem (443) e Apache/Nginx fazer proxy de /socket.io → :3331.
		if ($secure) {
			return $scheme . '://' . $host;
		}
		if ($port === 80) {
			return $scheme . '://' . $host;
		}

		return $scheme . '://' . $host . ':' . $port;
	}

	/**
	 * Socket.io do Service Desk ativo (PGM_SERVICE_DESK_REALTIME). Falso → o React usa só polling.
	 * Padrão 0: instalação só PHP (sem Node/proxy) não tenta wss. Com relay Node, definir =1.
	 */
	protected function _isServiceDeskRealtimeEnabled(): bool {
		$raw = env('PGM_SERVICE_DESK_REALTIME', '0');
		$rt = strtolower(trim((string)$raw));
		if ($rt === '') {
			return false;
		}

		return !in_array($rt, ['0', 'false', 'off', 'no'], true);
	}

	// #region agent log
	/** @internal debug session d63dd9 — não remover antes de verificação pós-correção */
	protected function _agentDebugLog(string $hypothesisId, string $location, string $message, array $data = []): void {
		$line = json_encode([
			'sessionId' => 'd63dd9',
			'hypothesisId' => $hypothesisId,
			'location' => $location,
			'message' => $message,
			'data' => $data,
			'timestamp' => (int) round(microtime(true) * 1000),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
		@file_put_contents(ROOT . DS . 'debug-d63dd9.log', $line, FILE_APPEND);
		@file_put_contents(TMP . 'debug-d63dd9.log', $line, FILE_APPEND);
	}
	// #endregion

	// #region agent log
	protected function _agentDebugLog18a583(string $runId, string $hypothesisId, string $location, string $message, array $data = []): void {
		$line = json_encode([
			'sessionId' => '18a583',
			'runId' => $runId,
			'hypothesisId' => $hypothesisId,
			'location' => $location,
			'message' => $message,
			'data' => $data,
			'timestamp' => (int) round(microtime(true) * 1000),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
		@file_put_contents(ROOT . DS . 'debug-18a583.log', $line, FILE_APPEND);
	}
	// #endregion

	// #region agent log
	protected function _agentDebugLog48685b(string $hypothesisId, string $location, string $message, array $data = []): void {
		$line = json_encode([
			'sessionId' => '48685b',
			'hypothesisId' => $hypothesisId,
			'location' => $location,
			'message' => $message,
			'data' => $data,
			'timestamp' => (int) round(microtime(true) * 1000),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
		@file_put_contents(ROOT . DS . 'debug-48685b.log', $line, FILE_APPEND);
	}
	// #endregion

	/** Mescla paths/layout do Service Desk quando ?sd=1 (edição/visualização a partir do /servicedesk). */
	protected function _servicedeskBootMerge(): array {
		if ($this->request->getQuery('sd') !== '1') {
			return [];
		}
		$this->viewBuilder()->setLayout('servicedesk');

		return [
			'servicedesk' => true,
			'paths' => [
				'indexTecnico' => Router::url(['controller' => 'Servicedesk', 'action' => 'index']),
				'indexCliente' => Router::url(['controller' => 'Servicedesk', 'action' => 'index']),
				'servicedeskUrl' => Router::url(['controller' => 'Servicedesk', 'action' => 'index']),
				'ticketsOperacional' => Router::url(['controller' => 'Servicedesk', 'action' => 'operacional']),
				'addTicket' => Router::url(['controller' => 'Servicedesk', 'action' => 'add']),
				'erpDashboard' => Router::url(['controller' => 'Users', 'action' => 'dashboard']),
				'ticketsClassicIndex' => Router::url(['controller' => 'Tickets', 'action' => 'index']),
				'ticketsClassicCliente' => Router::url(['controller' => 'Tickets', 'action' => 'indexcliente']),
				'ticketEditQuery' => '?sd=1',
				'ticketViewQuery' => '?sd=1',
			],
		];
	}

	public function criarMov($idticket = null, $sitantiga = null, $sitnova = null, $observacao = null) {
		$mov = $this->Ticketsmovs->newEntity();
		$mov->idticket = $idticket;
		$mov->sitantiga = $sitantiga;
		$mov->sitnova = $sitnova;
		$mov->idusuario = $this->Auth->user('id');
		$mov->idempresa = $this->Auth->user('idempresa');
		// PostgreSQL recusa "d/m/Y H:i:s" no timestamp (SQLSTATE[22008]); usar ISO 8601.
		$mov->datetime = date('Y-m-d H:i:s', time());

		if (!empty($observacao)) $mov->observacao = $observacao;

		return $this->Ticketsmovs->save($mov);
	}

	public function criaLogEmail($idticket, $acao) {
		$log = $this->Ticketslogemail->newEntity();
		$log->idticket = $idticket;
		$log->acao = $acao;
		$log->iduser = $this->Auth->user('id');
		$log->datetime = date('d/m/Y H:i:s', time());


		return $this->Ticketslogemail->save($log);
	}

	public function criaNot($situacao, $idticket, $idcliente = null) {
		$not = $this->Notificacoes->newEntity();
		$not->titulo = 'Ticket '.$idticket;
		$not->texto = 'Ticket Aberto';
		$not->situacao = $situacao;
		$not->tipo = C_NotificacaoTipoTikcet;
		$not->idacao = $idticket;
		$not->idcliente = $idcliente;
		$not->data = date('d/m/Y');

		return $this->Notificacoes->save($not);
	}

	public function dirAnexos($idempresa = null, $idticket = null) {
		if ($idempresa === null || $idempresa === '' || $idempresa == 0) {
			$idempresa = $this->Auth->user('idempresa');
		}

		return (WWW_ROOT . 'arquivos' . DS . 'tickets' . DS . $idempresa . DS . $idticket);
	}

	/**
	 * Normaliza campo upload file-3 (um ou vários arquivos) para lista de entradas compatíveis com moveFile().
	 *
	 * @param array|null $raw Valor de $this->request->getData('file-3')
	 * @return array<int, array<string, mixed>>
	 */
	protected function _normalizeUploadFilesList($raw): array {
		if (empty($raw) || !is_array($raw)) {
			return [];
		}
		// Um arquivo: name é string
		if (isset($raw['name']) && !is_array($raw['name'])) {
			$err = (int)($raw['error'] ?? UPLOAD_ERR_NO_FILE);
			if ($err === UPLOAD_ERR_NO_FILE || empty($raw['tmp_name'])) {
				return [];
			}

			return [$raw];
		}
		// Vários: name é array (mesmo com um único elemento)
		if (isset($raw['name']) && is_array($raw['name'])) {
			$out = [];
			foreach ($raw['name'] as $i => $name) {
				if ($name === '' || $name === null) {
					continue;
				}
				$err = (int)($raw['error'][$i] ?? UPLOAD_ERR_NO_FILE);
				if ($err === UPLOAD_ERR_NO_FILE) {
					continue;
				}
				$out[] = [
					'name' => $name,
					'type' => $raw['type'][$i] ?? '',
					'tmp_name' => $raw['tmp_name'][$i] ?? '',
					'error' => $err,
					'size' => $raw['size'][$i] ?? 0,
				];
			}

			return $out;
		}

		return [];
	}

	public function moveFile($file, $idempresa, $idticket) {
		//Ignora, se não tiver nada selecionado.
		if (!isset($file['tmp_name']) || !isset($file['name'])) {
			return 1;
		}

		if (empty($file['name'])) {
			return 1;
		}

		// Evita path traversal ou nomes maliciosos contendo separadores de diretório
		$nomeArquivo = (string)$file['name'];
		if (strpos($nomeArquivo, '..') !== false || strpos($nomeArquivo, '/') !== false || strpos($nomeArquivo, '\\') !== false) {
			return 0;
		}

		$diretorio = $this->dirAnexos($idempresa, $idticket);

		//  Cria a pasta caso ela não exista
		if (!file_exists($diretorio)) {
			mkdir($diretorio, 0755, true);
		}

		$arquivo = $diretorio . DS . $nomeArquivo;

		//Move o arquivo para a pasta.
		if (move_uploaded_file($file['tmp_name'], $arquivo)) return 1;
		else return 0;
	}

	public function downloadFile($arquivo) {
		$this->autoRender = false;

		return $this->response->withFile($arquivo, [
			'download' => true,
			'name' => basename($arquivo),
		]);
	}

	/**
	 * MIME a partir dos primeiros bytes (finfo em Linux costuma devolver application/octet-stream para JPEG/PNG).
	 *
	 * @param string $fullPath Caminho absoluto no disco
	 * @return string|null
	 */
	protected function _mimeFromMagicBytes($fullPath) {
		$h = @fopen($fullPath, 'rb');
		if ($h === false) {
			return null;
		}
		$buf = fread($h, 16);
		fclose($h);
		if (!is_string($buf) || strlen($buf) < 3) {
			return null;
		}
		// JPEG
		if (strncmp($buf, "\xFF\xD8\xFF", 3) === 0) {
			return 'image/jpeg';
		}
		// PNG
		if (strncmp($buf, "\x89PNG\r\n\x1a\n", 8) === 0) {
			return 'image/png';
		}
		// GIF
		if (strncmp($buf, 'GIF87a', 6) === 0 || strncmp($buf, 'GIF89a', 6) === 0) {
			return 'image/gif';
		}
		// WebP (RIFF....WEBP)
		if (strlen($buf) >= 12 && strncmp($buf, 'RIFF', 4) === 0 && substr($buf, 8, 4) === 'WEBP') {
			return 'image/webp';
		}
		// BMP
		if (strncmp($buf, 'BM', 2) === 0) {
			return 'image/bmp';
		}
		// ICO / CUR
		if (strlen($buf) >= 4 && $buf[0] === "\x00" && $buf[1] === "\x00" && $buf[3] === "\x00" && ($buf[2] === "\x01" || $buf[2] === "\x02")) {
			return 'image/x-icon';
		}
		// PDF
		if (strncmp($buf, '%PDF', 4) === 0) {
			return 'application/pdf';
		}
		// ISO BMFF: HEIC/HEIF (iPhone) e AVIF — Chrome desktop pode não renderizar HEIC, mas o tipo fica correto.
		if (strlen($buf) >= 12 && substr($buf, 4, 4) === 'ftyp') {
			$brand = substr($buf, 8, 4);
			if (in_array($brand, ['heic', 'heix', 'hevc', 'heim', 'heis', 'mif1', 'msf1'], true)) {
				return 'image/heic';
			}
			if ($brand === 'avif' || $brand === 'avis') {
				return 'image/avif';
			}
		}
		// TIFF (little-endian II+42, big-endian MM+42)
		if (strlen($buf) >= 4 && (strncmp($buf, "II\x2a\x00", 4) === 0 || strncmp($buf, "MM\x00\x2a", 4) === 0)) {
			return 'image/tiff';
		}

		return null;
	}

	/**
	 * Extensão do path no disco → MIME (fallback quando finfo devolve octet-stream).
	 *
	 * @param string $fullPath Caminho absoluto no disco
	 * @return string|null
	 */
	protected function _mimeFromPathExtension($fullPath) {
		$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
		$map = [
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpe' => 'image/jpeg',
			'jfif' => 'image/jpeg',
			'png' => 'image/png',
			'gif' => 'image/gif',
			'webp' => 'image/webp',
			'bmp' => 'image/bmp',
			'tif' => 'image/tiff',
			'tiff' => 'image/tiff',
			'pdf' => 'application/pdf',
			'svg' => 'image/svg+xml',
			'ico' => 'image/x-icon',
		];

		return isset($map[$ext]) ? $map[$ext] : null;
	}

	/**
	 * MIME pelo conteúdo do arquivo para inline (Chrome falha com tela preta se o tipo não for image/* ou PDF).
	 * O withFile() do CakePHP 3 infere só pela extensão do nome salvo.
	 *
	 * Ordem: assinatura binária primeiro (finfo devolve valores estranhos que não caem na lista "fraca"),
	 * depois finfo quando já for image/* ou PDF, por último extensão no disco.
	 *
	 * @param string $fullPath Caminho absoluto no disco
	 * @return string|null MIME completo ou null para manter o que withFile() definiu
	 */
	protected function _mimeForInlineDisplay($fullPath) {
		if (!is_readable($fullPath)) {
			return null;
		}
		$fromMagic = $this->_mimeFromMagicBytes($fullPath);
		if ($fromMagic !== null) {
			return $fromMagic;
		}
		// GD/lib: reconhece JPEG/PNG/GIF/WebP/BMP/ICO válidos mesmo quando finfo falha.
		$gis = @getimagesize($fullPath);
		if (is_array($gis) && !empty($gis['mime']) && strpos($gis['mime'], 'image/') === 0) {
			return $gis['mime'];
		}
		$mime = '';
		if (class_exists('finfo')) {
			$f = new \finfo(FILEINFO_MIME_TYPE);
			$detected = @$f->file($fullPath);
			if (is_string($detected) && $detected !== '') {
				$mime = $detected;
			}
		}
		if ($mime === '' && function_exists('mime_content_type')) {
			$mc = @mime_content_type($fullPath);
			$mime = is_string($mc) ? $mc : '';
		}
		if (strpos($mime, 'image/') === 0 || $mime === 'application/pdf') {
			return $mime;
		}

		return $this->_mimeFromPathExtension($fullPath);
	}

	/**
	 * Abre no navegador (imagens/PDF) em vez de forçar download.
	 * Envia com header() + readfile: evita compressão automática do PHP (zlib.output_compression)
	 * ou buffers que corrompem binário no fluxo via Response::withFile().
	 */
	protected function _sendFileInline($fullPath, $downloadName) {
		$this->autoRender = false;
		if (!is_readable($fullPath) || !is_file($fullPath)) {
			return $this->response->withStatus(404);
		}
		$mime = $this->_mimeForInlineDisplay($fullPath);
		if ($mime === null) {
			$mime = $this->_mimeFromPathExtension($fullPath);
		}
		if ($mime === null) {
			$mime = 'application/octet-stream';
		}
		$safeName = (string)preg_replace('/[\r\n"]/', '', basename($downloadName));
		$safeName = (string)preg_replace('/[^\x20-\x7E]/', '_', $safeName);
		if ($safeName === '') {
			$safeName = 'anexo';
		}
		$lenRaw = filesize($fullPath);
		if ($lenRaw === false) {
			return $this->response->withStatus(500);
		}
		$len = (int)$lenRaw;

		if (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
			session_write_close();
		}
		if (function_exists('ini_set')) {
			@ini_set('zlib.output_compression', '0');
		}
		while (ob_get_level() > 0) {
			ob_end_clean();
		}

		header('Content-Type: ' . $mime);
		header('Content-Length: ' . (string)$len);
		header('Content-Disposition: inline; filename="' . str_replace('"', '\\"', $safeName) . '"');
		header('Cache-Control: private, max-age=3600');
		header('X-Content-Type-Options: nosniff');

		$ok = @readfile($fullPath);
		if ($ok === false) {
			return $this->response->withStatus(500);
		}
		exit(0);
	}

	public function downloadAnexo($idanexo) {
		$this->autoRender = false;
		// Não usar layout ajax aqui: misturar saída/layout com header() manual
		// podia gerar dois Content-Disposition (Chrome: ERR_RESPONSE_HEADERS_MULTIPLE_CONTENT_DISPOSITION).
		// withFile() define um único conjunto de cabeçalhos via Response.

		if ($this->request->is('get')) {
			$anexo = $this->Ticketsanexos->get($idanexo);
			$inline = in_array((string)$this->request->getQuery('inline'), ['1', 'true', 'yes'], true);

			// Garantir que o anexo pertence à mesma empresa do usuário logado
			if ($anexo->idempresa != $this->Auth->user('idempresa')) {
				$this->Flash->error('Você não possui permissão para acessar este anexo.');
				return $this->redirect($this->referer());
			}

			// Regras adicionais para clientes
			if ($this->Auth->user('role') == C_RoleCliente) {
				$ticket = $this->Tickets->get($anexo->idticket);

				if ($ticket->idautor != $this->Auth->user('id')
					&& $ticket->idcliente != $this->Auth->user('idcliente')
					&& !$this->Auth->user('permissaoacesso')) {
					$this->Flash->error('Você não possui permissão para acessar este anexo.');
					return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
				}
			}

			// Arquivo para download
			$arquivo = $this->dirAnexos($anexo->idempresa, $anexo->idticket) . DS . $anexo->arquivo;
			if (file_exists($arquivo)) {
				if ($inline) {
					return $this->_sendFileInline($arquivo, $anexo->arquivo);
				}

				return $this->downloadFile($arquivo);
			}
			$this->Flash->error('O arquivo solicitado para download não foi localizado!', ['params' => ['title' => 'Erro ao fazer download do anexo']]);

			return $this->redirect($this->referer());
		}
	}

	public function deleteFile($arquivo) {
		if (file_exists($arquivo)) return unlink($arquivo);
		else return -1;
	}

	public function deleteAnexo($idticket) {
		if ($this->request->is('get')) {
			// $idticket aqui é o ID do anexo
			$anexo = $this->Ticketsanexos->get($idticket);
			$idticket = $anexo->idticket;

			// Garantir que o anexo pertence à mesma empresa do usuário logado
			if ($anexo->idempresa != $this->Auth->user('idempresa')) {
				$this->Flash->error('Você não possui permissão para excluir este anexo.');
				return $this->redirect($this->referer());
			}

			// Regras adicionais para clientes
			if ($this->Auth->user('role') == C_RoleCliente) {
				$ticket = $this->Tickets->get($anexo->idticket);

				if ($ticket->idautor != $this->Auth->user('id')
					&& $ticket->idcliente != $this->Auth->user('idcliente')
					&& !$this->Auth->user('permissaoacesso')) {
					$this->Flash->error('Você não possui permissão para excluir este anexo.');
					return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
				}
			}

			// Arquivo para download
			$arquivo = $this->dirAnexos($anexo->idempresa, $anexo->idticket) . DS . $anexo->arquivo;

			$ret = $this->deleteFile($arquivo);

			if ($ret != 0) {
				if ($this->Ticketsanexos->delete($anexo)) {
					$this->criarMov($idticket, 0, C_TicketAnexoDeletado, $anexo->arquivo);
					$this->Flash->success('O anexo foi deletado com sucesso!');
					return $this->redirect(['action' => 'edit', $idticket]);
				}
			} else {
				$this->Flash->error('O arquivo solicitado para deleção não foi localizado!', ['params' => ['title' => 'Erro ao excluir o anexo']]);
				return $this->redirect(['action' => 'edit', $idticket]);
			}
		}
	}

	public function index(){
		$this->viewBuilder()->setLayout('default');
		$this->viewBuilder()->setTemplate('react_app');
		$this->set('title', 'Listagem de Tickets');
		$this->set('reactBoot', $this->_reactBoot('tech_index', null));
	}

	/** Painel operacional (React) — mesmo shell que `index`; só técnico (role 0). */
	public function operacional() {
		$this->viewBuilder()->setLayout('default');
		$this->viewBuilder()->setTemplate('react_app');
		$this->set('title', 'Painel operacional — Tickets');
		$this->set('reactBoot', $this->_reactBoot('tech_operacional', null));
	}

	public function finalizados() {
		$this->set('title', 'Tickets finalizados');
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
		}

		$empresa = $this->Auth->user('idempresa');
		$ticketsFinalizados = $this->Tickets
			->findByIdempresa($empresa)
			->contain(['Users', 'Clientes'])
			->where(['situacao IN' => [C_TicketSituacaoResolvido, C_TicketSituacaoFechado]])
			->order(['Tickets.id DESC'])
			->limit(500)
			->toArray();

		$solicitantesMap = [];
		try {
			$solicitantesIds = [];
			foreach ($ticketsFinalizados as $t) {
				if (!empty($t->idsolicitante)) $solicitantesIds[] = (int)$t->idsolicitante;
			}
			$solicitantesIds = array_values(array_unique(array_filter($solicitantesIds)));
			if (!empty($solicitantesIds)) {
				$solicitantesMap = $this->Users
					->find('list', ['keyField' => 'id', 'valueField' => 'name'])
					->where(['id IN' => $solicitantesIds])
					->toArray();
			}
		} catch (\Throwable $e) {}

		$responsaveisMap = $this->_responsaveisMapForTicketEntities($ticketsFinalizados);

		$this->set('ticketsFinalizados', $ticketsFinalizados);
		$this->set('solicitantesMap', $solicitantesMap);
		$this->set('responsaveisMap', $responsaveisMap);
	}

	/** @var int */
	protected $_historicoLimiteLista = 500;

	protected function _historicoParseBrDate($s) {
		if ($s === null) {
			return null;
		}
		$s = trim((string)$s);
		if ($s === '') {
			return null;
		}
		if (!preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $s, $m)) {
			return null;
		}
		$d = (int)$m[1];
		$mo = (int)$m[2];
		$y = (int)$m[3];
		if (!checkdate($mo, $d, $y)) {
			return null;
		}

		return \DateTimeImmutable::createFromFormat('!Y-n-j', sprintf('%04d-%d-%d', $y, $mo, $d));
	}

	/**
	 * Filtros do histórico (GET). Sem datas informadas: últimos 60 dias.
	 *
	 * @return array<string,mixed>
	 */
	protected function _historicoFiltrosFromRequest() {
		$req = $this->request->getQueryParams();
		$iniStr = isset($req['periodo_ini']) ? trim((string)$req['periodo_ini']) : '';
		$fimStr = isset($req['periodo_fim']) ? trim((string)$req['periodo_fim']) : '';
		$dtIni = $this->_historicoParseBrDate($iniStr);
		$dtFim = $this->_historicoParseBrDate($fimStr);
		$periodoPadrao = false;
		if ($dtIni === null && $dtFim === null) {
			$periodoPadrao = true;
			$dtFim = new \DateTimeImmutable('today');
			$dtIni = $dtFim->modify('-60 days');
		} elseif ($dtIni === null) {
			$dtIni = $dtFim->modify('-60 days');
		} elseif ($dtFim === null) {
			$dtFim = new \DateTimeImmutable('today');
		}
		if ($dtIni > $dtFim) {
			$t = $dtIni;
			$dtIni = $dtFim;
			$dtFim = $t;
		}

		$sit = null;
		if (isset($req['situacao']) && $req['situacao'] !== '') {
			$si = (int)$req['situacao'];
			$permitidas = [
				(int)C_TicketSituacaoPendente,
				(int)C_TicketSituacaoEmandamento,
				(int)C_TicketSituacaoResolvido,
				(int)C_TicketSituacaoFechado,
			];
			if (in_array($si, $permitidas, true)) {
				$sit = $si;
			}
		}

		$idCliente = null;
		if (isset($req['idcliente']) && $req['idcliente'] !== '' && ctype_digit((string)$req['idcliente'])) {
			$cid = (int)$req['idcliente'];
			if ($cid > 0) {
				$idCliente = $cid;
			}
		}

		$q = isset($req['q']) ? trim((string)$req['q']) : '';

		return [
			'created_start' => $dtIni->format('Y-m-d') . ' 00:00:00',
			'created_end' => $dtFim->format('Y-m-d') . ' 23:59:59',
			'situacao' => $sit,
			'idcliente' => $idCliente,
			'q' => $q,
			'periodo_padrao' => $periodoPadrao,
		];
	}

	/**
	 * Clientes ativos visíveis ao usuário (ABAC), para o filtro.
	 *
	 * @return array<int,string>
	 */
	protected function _historicoClientesList() {
		$clientesFis = $this->Clientes->find('all')
			->where(['AND' => ['inativo' => '0', 'tipo' => '1']])
			->order(['nome']);
		$this->Abac->applyToQuery($clientesFis, 'Clientes');
		$clientesJur = $this->Clientes->find('all')
			->where(['AND' => ['inativo' => '0', 'tipo' => '2']])
			->order(['razaosocial']);
		$this->Abac->applyToQuery($clientesJur, 'Clientes');
		$list = [];
		foreach ($clientesJur->all() as $reg) {
			$list[(int)$reg->id] = (string)$reg->razaosocial;
		}
		foreach ($clientesFis->all() as $reg) {
			$list[(int)$reg->id] = (string)$reg->nome;
		}

		return $list;
	}

	/**
	 * Busca livre: assunto/descrição, id numérico, autor ou nome de cliente (subconsultas com ABAC).
	 *
	 * @param \Cake\ORM\Query $query
	 * @param string $qraw
	 */
	protected function _historicoApplyBuscaSubquery($query, $qraw) {
		$t = trim((string)$qraw);
		if ($t === '') {
			return;
		}
		$term = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $t) . '%';
		$cols = $this->Tickets->getSchema()->columns();
		$parts = [['Tickets.assunto LIKE' => $term]];
		if (in_array('solicitacao', $cols, true)) {
			$parts[] = ['Tickets.solicitacao LIKE' => $term];
		}
		if (ctype_digit($t)) {
			$parts[] = ['Tickets.id' => (int)$t];
		}

		$qU = $this->Users->find()->select(['id'])
			->where(['OR' => [
				'Users.name LIKE' => $term,
				'Users.username LIKE' => $term,
			]]);
		$this->Abac->applyToQuery($qU, 'Users', 'Users');
		$uids = [];
		foreach ($qU->enableHydration(false)->toArray() as $row) {
			if (!empty($row['id'])) {
				$uids[] = (int)$row['id'];
			}
		}
		$uids = array_values(array_unique(array_filter($uids)));
		if (!empty($uids)) {
			$parts[] = ['Tickets.idautor IN' => $uids];
		}

		$qC = $this->Clientes->find()->select(['id'])
			->where(['OR' => [
				'Clientes.razaosocial LIKE' => $term,
				'Clientes.nome LIKE' => $term,
			]]);
		$this->Abac->applyToQuery($qC, 'Clientes');
		$cids = [];
		foreach ($qC->enableHydration(false)->toArray() as $row) {
			if (!empty($row['id'])) {
				$cids[] = (int)$row['id'];
			}
		}
		$cids = array_values(array_unique(array_filter($cids)));
		if (!empty($cids)) {
			$parts[] = ['Tickets.idcliente IN' => $cids];
		}

		$query->where(['OR' => $parts]);
	}

	/**
	 * Query base do histórico (ABAC + filtros de tela + filtros opcionais de workflow na query string).
	 *
	 * @param array<string,mixed> $f
	 * @param array<string,mixed> $opts
	 * @return \Cake\ORM\Query
	 */
	protected function _historicoQueryComFiltros(array $f, array $opts = []) {
		$q = $this->Tickets->find();
		if (!empty($opts['contain'])) {
			$q->contain($opts['contain']);
		}
		$this->Abac->applyToQuery($q, 'Tickets', 'Tickets');
		$q->where([
			'Tickets.created >=' => $f['created_start'],
			'Tickets.created <=' => $f['created_end'],
		]);
		if ($f['situacao'] !== null) {
			$q->where(['Tickets.situacao' => $f['situacao']]);
		}
		if ($f['idcliente'] !== null) {
			$q->where(['Tickets.idcliente' => $f['idcliente']]);
		}
		if ($f['q'] !== '') {
			$this->_historicoApplyBuscaSubquery($q, $f['q']);
		}
		$this->_applyApiIndexWorkflowFilters($q);

		return $q;
	}

	/**
	 * @param array<string,mixed> $f
	 */
	protected function _historicoKpiSlaPct(array $f) {
		$cols = $this->Tickets->getSchema()->columns();
		if (!in_array('sla_status', $cols, true)) {
			return '—';
		}
		$tracked = $this->_historicoQueryComFiltros($f)
			->where(function (QueryExpression $exp) {
				return $exp->isNotNull('Tickets.sla_status');
			})
			->count();
		if ($tracked === 0) {
			return 'n/d';
		}
		$viol = $this->_historicoQueryComFiltros($f)
			->where(['Tickets.sla_status' => 'violado'])
			->count();

		return (string)(int)round(100 * ($tracked - $viol) / $tracked) . '%';
	}

	/**
	 * Histórico de atendimentos (ERP) — listagem com filtros, KPIs e escopo ABAC.
	 */
	public function historico() {
		$this->viewBuilder()->setLayout('default');
		$this->set('title', 'Histórico de atendimentos');
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
		}

		$filtros = $this->_historicoFiltrosFromRequest();
		$clientesList = $this->_historicoClientesList();
		if ($filtros['idcliente'] !== null && !isset($clientesList[$filtros['idcliente']])) {
			$filtros['idcliente'] = null;
		}

		$total = $this->_historicoQueryComFiltros($filtros)->count();
		$emExec = $this->_historicoQueryComFiltros($filtros)
			->where(['Tickets.situacao' => C_TicketSituacaoEmandamento])
			->count();
		$finaliz = $this->_historicoQueryComFiltros($filtros)
			->where(['Tickets.situacao IN' => [C_TicketSituacaoResolvido, C_TicketSituacaoFechado]])
			->count();
		$slaPct = $this->_historicoKpiSlaPct($filtros);

		$ticketsHistorico = $this->_historicoQueryComFiltros($filtros, ['contain' => ['users', 'Clientes']])
			->order(['Tickets.id' => 'DESC'])
			->limit($this->_historicoLimiteLista)
			->toArray();

		$this->set('historicoKpis', [
			'total_periodo' => (string)(int)$total,
			'em_execucao' => (string)(int)$emExec,
			'finalizados' => (string)(int)$finaliz,
			'sla_atendido_pct' => $slaPct,
		]);
		$this->set('ticketsHistorico', $ticketsHistorico);
		$this->set('historicoClientesList', $clientesList);
		$this->set('historicoPeriodoPadrao', (bool)$filtros['periodo_padrao']);
		$this->set('historicoQuery', $this->request->getQueryParams());
		$this->set('historicoTotalMatched', (int)$total);
		$this->set('historicoLimiteLista', (int)$this->_historicoLimiteLista);
	}

	/**
	 * Visualização enxuta para modal (sem menu lateral/abas).
	 */
	public function viewModal($idticket = null) {
		$idempresa = $this->Auth->user('idempresa');
		$idcliente = $this->Auth->user('idcliente');
		$role = $this->Auth->user('role');
		$admin = (bool)$this->Auth->user('admin');
		$permissaoacesso = (bool)$this->Auth->user('permissaoacesso');
		$iduser = $this->Auth->user('id');

		$this->set('title', "Ticket $idticket");
		$this->viewBuilder()->setLayout('clear');

		// Impede vazamento entre empresas / escopo ABAC (empresa ou cliente).
		$ticket = $this->Tickets->find('all', ['contain' => ['Clientes', 'Users']])
			->where(['tickets.id' => $idticket]);
		$this->Abac->applyToQuery($ticket, 'Tickets', 'tickets');
		$ticket = $ticket->first();
		if (empty($ticket)) {
			$this->autoRender = false;
			return $this->response->withStringBody('Ticket não encontrado.')->withStatus(404);
		}

		// Permissões: manter regra do view()
		if ($role == C_RoleCliente) {
			// Valida permissões usando somente a empresa atual (não empresadominante).
			$clienteBase = $this->Clientes->findById($idcliente)->first();
			$clienteVerifica = null;

			if ($clienteBase && (int)$clienteBase->idempresa !== (int)$idempresa) {
				if ($clienteBase->tipo == C_ClientesTipoJuridica) {
					$qCv = $this->Clientes->findByCnpj(removeCaracteres($clienteBase->cnpj));
					$this->Abac->applyToQuery($qCv, 'Clientes');
					$clienteVerifica = $qCv->first();
				} else {
					$qCv = $this->Clientes->findByCpf(removeCaracteres($clienteBase->cpf));
					$this->Abac->applyToQuery($qCv, 'Clientes');
					$clienteVerifica = $qCv->first();
				}
			} else {
				$clienteVerifica = $clienteBase;
			}

			if (empty($clienteVerifica)) {
				$this->autoRender = false;
				return $this->response->withStringBody('Sem permissão.')->withStatus(403);
			}

			if ($clienteVerifica->cpf != $clienteBase->cpf && $clienteBase->cnpj != $clienteVerifica->cnpj) {
				$this->autoRender = false;
				return $this->response->withStringBody('Sem permissão.')->withStatus(403);
			}
			if ($ticket->idautor != $iduser && !$permissaoacesso) {
				$this->autoRender = false;
				return $this->response->withStringBody('Sem permissão.')->withStatus(403);
			}
		}

		// Cliente
		$cliente = $this->Clientes->findById($ticket->idcliente)->select(['razaosocial', 'nomefantasia', 'nome', 'tipo'])->first();
		$clienteNome = $cliente && $cliente->tipo == C_ClientesTipoFisica ? $cliente->nome : ($cliente->razaosocial ?? ($ticket->cliente->razaosocial ?? ''));

		// Solicitante
		$solicitante = $this->Users->findById($ticket->idsolicitante)->select(['name'])->first();

		// Comentários (somente leitura no modal)
		$ticketcomentarios = $this->Ticketcomentarios->find('all', [
			'contain' => ['users'],
			'fields' => ['Users.name', 'Users.role', 'Ticketcomentarios.comentario', 'Ticketcomentarios.created']
		])->where(['Ticketcomentarios.idticket' => $idticket])->order(['Ticketcomentarios.id'])->toArray();

		// Anexos (somente leitura no modal)
		$ticketanexos = $this->Ticketsanexos->find('all')
			->where(['idticket' => $idticket])
			->toArray();

		// Movimentações e horas (somente leitura no modal)
		$ticketshoras = $this->Ticketshoras->find('all', ['contain' => 'Users'])->where(['idticket' => $idticket])->toArray();
		$ticketsmovs = $this->Ticketsmovs->find('all', ['contain' => ['users']])->where(['idticket' => $ticket->id])->order('ticketsmovs.id')->toArray();

		foreach (array_reverse($ticketsmovs) as $reg) {
			if ($reg['sitnova'] == C_TicketSituacaoFechado && $reg['sitnova'] != $reg['sitantiga']) {
				$this->set('bMovCancelada', true);
				break;
			}
		}

		$this->set('role', $role);
		$this->set('admin', $admin);
		$this->set('permissaoacesso', $permissaoacesso);
		$this->set('iduser', $iduser);

		$this->set('ticketsmovs', $ticketsmovs);
		$this->set('ticketanexos', $ticketanexos);
		$this->set('ticketshoras', $ticketshoras);
		$this->set('ticketcomentarios', $ticketcomentarios);
		$this->set('ticket', $ticket);
		$this->set('clienteNome', $clienteNome);
		if (isset($solicitante->name)) $this->set('solicitante', $solicitante->name);
	}

	public function indexcliente(){
		$assunto = $this->request->getQuery('assunto');
		$situacao = $this->request->getQuery('situacao');

		// Debug server-side opcional (para identificar por que o clique não dispara).
		// Use: /tickets/indexcliente?debug=1
		$debug = (string)$this->request->getQuery('debug');
		if ($debug === '1') {
			try {
				@file_put_contents(
					ROOT . DS . 'debug-tickets-indexcliente.log',
					date('Y-m-d H:i:s') .
						' debug=1 userId=' . (int)($this->Auth->user('id') ?? 0) .
						' idcliente=' . (int)($this->Auth->user('idcliente') ?? 0) .
						' assunto=' . (string)($assunto ?? '') .
						' situacao=' . (string)($situacao ?? '') .
						PHP_EOL,
					FILE_APPEND
				);
			} catch (\Throwable $e) {}
		}

		$this->viewBuilder()->setLayout('default');
		$this->viewBuilder()->setTemplate('react_app');
		$this->set('title', 'Tickets');
		$this->set('reactBoot', $this->_reactBoot('client_index', null, [
			'queryAssunto' => $assunto,
			'querySituacao' => $situacao,
		]));
	}

	public function meustickets(){
		$meustickets = $this->Tickets->find('all',['contain' => ['Users', 'Ticketsusers', 'Clientes' => ['fields' => ['razaosocial', 'id', 'nomefantasia']]]])
			->where(['AND' => ['idautor' => $this->Auth->user('id')], ['Ticketsusers.iduser' => $this->Auth->user('id')]])
			->distinct(['Tickets.id']);
		$this->Abac->applyToQuery($meustickets, 'Tickets', 'Tickets');
		$meustickets = $meustickets->toArray();
		
		$this->set('title', 'Meus Tickets');
		$this->set(compact('meustickets'));
	}

	public function empresatickets(){
		$empresatickets = [];

		$empresatickets = $this->Tickets->find('all',['contain' => ['Users', 'Ticketsusers', 'Clientes' => ['fields' => ['razaosocial', 'id', 'nomefantasia']]]])
			->where(['AND' => ['idautor' => $this->Auth->user('id')], ['Ticketsusers.iduser' => $this->Auth->user('id')]])
			->distinct(['Tickets.id']);
		$this->Abac->applyToQuery($empresatickets, 'Tickets', 'Tickets');
		$empresatickets = $empresatickets->toArray();
		
		$this->set('title', 'Tickets da Empresa');
		$this->set(compact('empresatickets'));
	}

	public function add($assunto = null) {
		$this->set('bodyPageClass', 'tickets-add-page');
		$this->set('title', 'Abertura de Ticket');
		$ticket = $this->Tickets->newEntity();

		// Cliente
		if($this->Auth->user('role') == C_RoleCliente){
			$this->set('email', $this->Auth->user(['email']));

			$empresaAtual = (int)$this->Auth->user('idempresa');
			$cliente = $this->Clientes->findById($this->Auth->user('idcliente'))->order(['idempresa ASC'])->first();
			if (empty($cliente)) {
				$this->Flash->error('Cliente não encontrado para a empresa atual.');
				return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
			}

			// Seleciona o cadastro do cliente dentro da empresa atual (ABAC).
			if ($cliente->tipo == C_ClientesTipoJuridica) {
				$qCa = $this->Clientes->findByCnpj(removeCaracteres($cliente->cnpj));
				$this->Abac->applyToQuery($qCa, 'Clientes');
				$clienteAtual = $qCa->first();
			} else {
				$qCa = $this->Clientes->findByCpf(removeCaracteres($cliente->cpf));
				$this->Abac->applyToQuery($qCa, 'Clientes');
				$clienteAtual = $qCa->first();
			}

			if (!empty($clienteAtual)) {
				$ticket->idempresa = $empresaAtual;
				$ticket->idcliente = $clienteAtual->id;
			} else {
				$this->Flash->error('Não existe cadastro do cliente na empresa atual para abrir o ticket.');
				return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
			}
		}

		// Formulário novo: não herdar DEFAULT 'media' da coluna (usuário deve escolher a urgência).
		if (!$this->request->is('post') && in_array('severidade', $this->Tickets->getSchema()->columns(), true)) {
			$ticket->set('severidade', '');
		}

		if ($this->request->is('post')) {
			$post = $this->request->getData();
			$anexos = [];
			if (!empty($post['file-3'])) {
				$anexos = $this->_normalizeUploadFilesList($post['file-3']);
			}
			unset($post['file-3']);
			$__sevColAdd = in_array('severidade', $this->Tickets->getSchema()->columns(), true);
			if (!$__sevColAdd) {
				unset($post['severidade']);
			}

			$__queueIdCol = in_array('queue_id', $this->Tickets->getSchema()->columns(), true);
			$__needQueueId = $this->_queuesRelacionalReady() && $__queueIdCol;
			if ($__needQueueId) {
				$qCnt = $this->Queues->find()->order(['sort_order' => 'ASC', 'id' => 'ASC']);
				$this->Abac->applyToQuery($qCnt, 'Queues', 'Queues');
				$__needQueueId = $qCnt->count() > 0;
			}
			$__addRole = (int)$this->Auth->user('role');
			$missingLabels = [];
			if ($__addRole === C_RoleCliente) {
				// idcliente vem do contexto; não exigir no POST
			} elseif (trim((string)($post['idcliente'] ?? '')) === '') {
				$missingLabels[] = __('Cliente');
			}
			if (trim((string)($post['email'] ?? '')) === '') {
				$missingLabels[] = ($__addRole === C_RoleCliente) ? __('E-mail') : __('E-mail para contato');
			}
			if (trim((string)($post['assunto'] ?? '')) === '') {
				$missingLabels[] = __('Assunto / Categoria');
			}
			if ($__sevColAdd && trim((string)($post['severidade'] ?? '')) === '') {
				$missingLabels[] = __('Urgência (severidade)');
			}
			if ($__needQueueId && (int)($post['queue_id'] ?? 0) <= 0) {
				$missingLabels[] = __('Destino (fila)');
			}
			if (trim((string)($post['solicitacao'] ?? '')) === '') {
				$missingLabels[] = __('Descrição do problema / solicitação');
			}
			$__addFormValid = $missingLabels === [];

			if (!$__addFormValid) {
				$this->Flash->error(__(
					'Preencha ou selecione os campos obrigatórios: {0}.',
					[implode('; ', $missingLabels)]
				));
				$ticket = $this->Tickets->patchEntity($ticket, $post);
				if ($__sevColAdd && trim((string)($post['severidade'] ?? '')) === '') {
					$ticket->set('severidade', '');
				}
			} else {

			if (!$this->_queuesRelacionalReady() || !$__queueIdCol) {
				unset($post['queue_id']);
			} else {
				$__qid = isset($post['queue_id']) ? (int)$post['queue_id'] : 0;
				if ($__qid <= 0) {
					unset($post['queue_id']);
				} else {
					$__okQ = $this->Queues->find()->where(['id' => $__qid]);
					$this->Abac->applyToQuery($__okQ, 'Queues', 'Queues');
					$__okQ = $__okQ->first();
					if (empty($__okQ)) {
						unset($post['queue_id']);
					}
				}
			}

			// Caso não tenha email preenchido
			if ($this->Auth->user('role') == 1 && ($post['email'] ?? '') === '' && isset($clientequetememail->email)) {
				$ticket->email = $clientequetememail->email;
			}

			$ticket = $this->Tickets->patchEntity($ticket, $post);
			if (in_array('severidade', $this->Tickets->getSchema()->columns(), true)) {
				$ticket->severidade = $this->_normalizeTicketSeveridade((string)($post['severidade'] ?? ''));
			}
			$ticket->idautor = $this->Auth->user('id');
			$ticket->situacao = 0;
			$ticket->resolvido = 0;
			// Garante idempresa correto (empresa atual no dropdown).
			$ticket->idempresa = $this->Auth->user('idempresa');

			// Para cliente, também garante idcliente correto (CPF/CNPJ dentro da empresa atual).
			if($this->Auth->user('role') == C_RoleCliente) {
				$empresaAtual = (int)$this->Auth->user('idempresa');
				$clienteBase = $this->Clientes->findById($this->Auth->user('idcliente'))->first();

				$clienteAtual = null;
				if (!empty($clienteBase)) {
					if ($clienteBase->tipo == C_ClientesTipoJuridica) {
						$qCa = $this->Clientes->findByCnpj(removeCaracteres($clienteBase->cnpj));
						$this->Abac->applyToQuery($qCa, 'Clientes');
						$clienteAtual = $qCa->first();
					} else {
						$qCa = $this->Clientes->findByCpf(removeCaracteres($clienteBase->cpf));
						$this->Abac->applyToQuery($qCa, 'Clientes');
						$clienteAtual = $qCa->first();
					}
				}

				if (empty($clienteAtual)) {
					$this->Flash->error('Não existe cadastro do cliente na empresa atual para abrir o ticket.');
					return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
				}

				$ticket->idcliente = $clienteAtual->id;
			}
		
			if ($this->Tickets->save($ticket)) {
				if ($this->_ticketWorkflowSchemaReady()) {
					$fila = 'n1';
					$nivel = 1;
					if ($this->_queuesRelacionalReady() && in_array('queue_id', $this->Tickets->getSchema()->columns(), true) && !empty($ticket->queue_id)) {
						$qPick = $this->Queues->find()->where(['id' => (int)$ticket->queue_id]);
						$this->Abac->applyToQuery($qPick, 'Queues', 'Queues');
						$qPick = $qPick->first();
						if (!empty($qPick) && $qPick->codigo !== null && $qPick->codigo !== '') {
							$cat = $this->_filaSuporteCatalog();
							$cd = (string)$qPick->codigo;
							if (isset($cat[$cd])) {
								$fila = $cd;
								$nivel = $cat[$cd]['nivel'];
							}
						}
					}
					$ticket->fila_suporte = $fila;
					$ticket->nivel_atendimento = $nivel;
					$ticket->idtecnico_responsavel = null;
					$f = $this->_ticketFieldsComResponsavel(['fila_suporte', 'nivel_atendimento', 'idtecnico_responsavel']);
					$this->Tickets->save($ticket, ['fields' => $f]);
				}
				$this->_syncTicketQueueAfterCreate((int)$ticket->id);
				$this->_applyEnterpriseTicketOnCreate($ticket);
				// Anexos (vários arquivos: input multiple ou lista normalizada)
					foreach ($anexos as $file) {
						$idempresa = $this->Auth->user('idempresa');
						$ret = $this->moveFile($file, $idempresa, $ticket->id);
						if ($ret != 1) $this->Flash->error('Ocorreu um erro ao enviar o arquivo "' . $file['name'] . '"! Tente novamente mais tarde.');
						else {
							if (!empty($file['name'])) {
								$anexo = $this->Ticketsanexos->newEntity();
								$anexo->arquivo = $file['name'];
								$anexo->idticket = $ticket->id;
								$anexo->idempresa = $ticket->idempresa;
								if (!$this->Ticketsanexos->save($anexo)) {
									$this->Flash->error('Ocorreu um erro ao salvar o anexo "' . $file['name'] . '"! Tente novamente mais tarde.');
								}
							}
						}
					}
				// Mov
					$mov = $this->Ticketsmovs->newEntity();
					$mov->idticket = $ticket->id;
					$mov->sitantiga = 0;
					$mov->sitnova = 0;
					$mov->idusuario = $this->Auth->user('id');
					$mov->datetime = date('d/m/Y H:i:s', time());
					$this->Ticketsmovs->save($mov);
				// E-mail (cliente): notificar suporte — chamar a Table diretamente.
				// NÃO usar $this->email() aqui: o request ainda é POST do formulário "add" e o action email()
				// interpretaria como envio manual (para/sugestoes vazios), gerava Flash de erro e descartava o redirect.
					$emailSuporteOk = true;
					if ($this->Auth->user('role') == C_RoleCliente) {
						$emailSuporteOk = false;
						try {
							$sent = $this->Tickets->email(
								$ticket->id,
								C_TicketCriado,
								null,
								$this->Auth->user('idempresa')
							);
							$emailSuporteOk = !empty($sent);
							if (!$emailSuporteOk) {
								$this->log('[Tickets::add] Falha ao enviar e-mail de ticket criado (cliente).', 'warning');
							}
						} catch (\Throwable $e) {
							$this->log('[Tickets::add] Exceção ao enviar e-mail de ticket criado: ' . $e->getMessage(), 'error');
							$emailSuporteOk = false;
						}
					}
				// Not
					$this->criaNot($ticket->situacao, $ticket->id, $ticket->idcliente);
					try {
						TicketInternalNotificationHelper::afterTicketAberto(
							$ticket,
							(int)$this->Auth->user('role'),
							(int)$this->Auth->user('id')
						);
					} catch (\Throwable $e) {
						$this->log('[Tickets::add] TicketInternalNotificationHelper: ' . $e->getMessage(), 'warning');
					}
				// 

				if ($this->Auth->user('role') == C_RoleCliente && !$emailSuporteOk) {
					$this->Flash->warning(__(
						'O Ticket nº ' . $ticket->id . ' foi aberto com sucesso. A notificação por e-mail ao suporte não foi enviada — confira o campo "e-mail de tickets" (formato válido), senha MAIL_MASTER_PASSWORD ou MAIL_PGM_PASSWORD no servidor e, na porta 587, TLS (MAIL_*_TLS). Detalhes em logs/error.log.'
					));
				} else {
					$this->Flash->success(__("O Ticket nº $ticket->id foi aberto com sucesso"));
				}
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ticket->id);
				if ($this->Auth->user('role') == C_RoleCliente) return $this->redirect(['action' => 'view', $ticket->id]);
				else return $this->redirect(['action' => 'edit', $ticket->id]);
			}
			$this->Flash->error(__('Não foi possível enviar o ticket.'));
			}
		}

		$clientesFis = $this->Clientes->find('all')
			->where(['AND' => ['inativo' => '0', 'tipo' => '1']])
			->order(['nome']);
		$this->Abac->applyToQuery($clientesFis, 'Clientes');
		$clientesFis = $clientesFis->toArray();
		$clientesJur = $this->Clientes->find('all')
			->where(['AND' => ['inativo' => '0', 'tipo' => '2']])
			->order(['razaosocial']);
		$this->Abac->applyToQuery($clientesJur, 'Clientes');
		$clientesJur = $clientesJur->toArray();
		
		$clientesList = [];
		foreach($clientesJur as $reg){
		$clientesList[$reg->id] = $reg->razaosocial;}
		foreach($clientesFis as $reg){
		$clientesList[$reg->id] = $reg->nome;}

		
		$this->set('assunto', $assunto);
		$__taOpts = $this->_ticketAssuntoClienteOptions();
		$this->set('ticketAssuntoOptions', $__taOpts);
		$this->set('clientes', $clientesList);
		$this->set('authUserName', (string)($this->Auth->user('name') ?? ''));
		$this->set('severidadeColumnReady', in_array('severidade', $this->Tickets->getSchema()->columns(), true));
		$ticketAddQueueFieldReady = $this->_queuesRelacionalReady() && in_array('queue_id', $this->Tickets->getSchema()->columns(), true);
		$ticketAddQueues = [];
		$ticketAddDefaultQueueId = null;
		if ($ticketAddQueueFieldReady) {
			$qAddQueues = $this->Queues->find()->order(['sort_order' => 'ASC', 'id' => 'ASC']);
			$this->Abac->applyToQuery($qAddQueues, 'Queues', 'Queues');
			foreach ($qAddQueues->all() as $__row) {
				$ticketAddQueues[(int)$__row->id] = (string)($__row->name ?? ('Fila #' . $__row->id));
			}
		}
		$this->set(compact('ticket', 'ticketAddQueues', 'ticketAddQueueFieldReady', 'ticketAddDefaultQueueId'));
	}

	public function edit($idticket = null){
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para visualizar esta página.');
			return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
		}
		$qMu = $this->Ticketsusers->find('all', ['contain' => ['Tickets']]);
		$this->Abac->applyToQuery($qMu, 'Ticketsusers', 'Ticketsusers');
		$meustickets = $qMu->toArray();
		$qTu = $this->Ticketsusers->find('all', ['contain' => ['users'], 'fields' => ['Users.name', 'Users.id', 'Ticketsusers.id']])
			->where(['idticket' => $idticket])
			->autoFields(true);
		$this->Abac->applyToQuery($qTu, 'Ticketsusers', 'Ticketsusers');
		$ticketsusers = $qTu->toArray();
		$qTicket = $this->Tickets->findById($idticket)->contain(['users']);
		$this->Abac->applyToQuery($qTicket, 'Tickets', 'Tickets');
		$ticket = $qTicket->first();
			if(empty($ticket)) {
				$this->Flash->error('Não foi encontrado um ticket com o Id informado na Empresa selecionada.');
				return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
			}
		// Cliente e Solicitante 
			$solicitante = $this->Users->findById($ticket->idsolicitante)->select(['name'])->first();
			$cliente = $this->Clientes->findById($ticket->idcliente)->select(['razaosocial', 'nomefantasia', 'nome', 'tipo', 'idempresa'])->first();
			$clienteNome = $cliente->tipo == C_ClientesTipoFisica ? $cliente->nome : $cliente->razaosocial;
		// Comentarios 
			$ticketcomentarios = $this->Ticketcomentarios->find('all', [
				'contain' => ['users', 'Tickets'],
				'fields' => ['Users.name', 'Users.role', 'Ticketcomentarios.comentario', 'Ticketcomentarios.created', 'Tickets.idempresa']
			])->where(['Ticketcomentarios.idticket' => $idticket])->order(['Ticketcomentarios.id'])->toArray();
		// Anexos 
			$qAn = $this->Ticketsanexos->find('all')->where(['idticket' => $idticket]);
			$this->Abac->applyToQuery($qAn, 'Ticketsanexos', 'Ticketsanexos');
			$ticketanexos = $qAn->toArray();
		// Movs e horas 
			$ticketsmovs = $this->Ticketsmovs->find('all', ['contain' => ['users']])->where(['idticket' => $ticket->id])->order('ticketsmovs.id')->toArray();
			$ticketshoras = $this->Ticketshoras->find('all', ['contain' => 'Users'])->where(['idticket' => $idticket])->toArray();
		// Users 
			$users = $this->Users->find('list', ['keyField' => 'id', 'valueField' => 'name'])->order(['name'])->where(['role' => 0,'inativo' => 0,])->toArray();
			//verifica se o usuário já tá no ticket pra não add ele de novo
			foreach($users as $key => $user) foreach($ticketsusers as $jatanoticket) if($key == $jatanoticket->Users['id']) unset($users[$key]);
		// 

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			$this->Tickets->patchEntity($ticket, $data);
			if ($this->Tickets->save($ticket)) {
				$this->Flash->success('A descrição foi salva com sucesso!');
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $idticket);
				return $this->redirect(['action' => 'edit', $idticket]);
			}
			$this->Flash->error('Ocorreu um erro ao editar o ticket.');
		}

		$ordem = $this->Ordensservico->findByIdticket($idticket)->first();
		$ordem = empty($ordem) ? false : $ordem->id;

		$timerAtivo = null;
		$timerPausado = false;
		try {
			$this->loadModel('AtendimentoTimer');
			$tUserCol = $this->_atendimentoTimerUserColumn();
			$timerAtivo = $this->AtendimentoTimer->find()
				->where([
					'idticket' => $idticket,
					$tUserCol => $this->Auth->user('id'),
					'hora_fim IS' => null,
				])
				->orderDesc('id')
				->first();
			if ($timerAtivo) {
				$horaPausa = $timerAtivo->get('hora_pausa');
				$timerPausado = !empty($horaPausa);
			}
		} catch (\Throwable $e) {
			// Tabela pode não existir; view mostra só Iniciar ou esconde o bloco
		}

		$timerPausadoElapsedTexto = null;
		if ($timerAtivo && $timerPausado) {
			$hi = $timerAtivo->get('hora_inicio');
			$hp = $timerAtivo->get('hora_pausa');
			if ($hi && $hp) {
				$tIni = is_object($hi) && method_exists($hi, 'getTimestamp') ? $hi->getTimestamp() : strtotime($hi);
				$tPausa = is_object($hp) && method_exists($hp, 'getTimestamp') ? $hp->getTimestamp() : strtotime($hp);
				$segundos = max(0, (int)($tPausa - $tIni));
				$h = (int)floor($segundos / 3600);
				$m = (int)floor(($segundos % 3600) / 60);
				$s = $segundos % 60;
				$timerPausadoElapsedTexto = sprintf('%02d:%02d:%02d', $h, $m, $s);
			}
		}

		$minutosTicket = 0;
		$minutosClienteMes = 0;
		$horasContratoTexto = null;
		$saldoContratoMinutos = null;
		try {
			$inicioMes = (new \DateTime('first day of this month', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
			$fimMes = (new \DateTime('last day of this month', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
			$minutosTicket = $this->Ticketshoras->minutosTicket($idticket, '2000-01-01', '2099-12-31');
			$minutosClienteMes = $this->Ticketshoras->minutosCliente($ticket->idcliente, $inicioMes, $fimMes);
		} catch (\Throwable $e) {}
		try {
			$table = \Cake\ORM\TableRegistry::getTableLocator()->get('ContratosHoras');
			$qCh = $table->find()->where(['idcliente' => $ticket->idcliente]);
			$this->Abac->applyToQuery($qCh, 'ContratosHoras', 'ContratosHoras');
			$contrato = $qCh->first();
			if (!$contrato) {
				$contrato = $table->find()->where(['idcliente' => $ticket->idcliente])->first();
			}
			if ($contrato) {
				// Formato do módulo em produção: horas_contratadas e saldo (em horas, decimal) ou horas_consumidas
				if ($contrato->get('horas_contratadas') !== null && $contrato->get('saldo') !== null) {
					$hContratadas = (float)str_replace(',', '.', $contrato->get('horas_contratadas'));
					$saldoH = (float)str_replace(',', '.', $contrato->get('saldo'));
					$horasContratoTexto = number_format($hContratadas, 2, ',', '.') . ' h contratadas; saldo: ' . number_format(max(0, $saldoH), 2, ',', '.') . ' h';
				} elseif ($contrato->get('horas_contratadas') !== null && $contrato->get('saldo_horas') !== null) {
					$hContratadas = (float)str_replace(',', '.', $contrato->get('horas_contratadas'));
					$saldoH = (float)str_replace(',', '.', $contrato->get('saldo_horas'));
					$horasContratoTexto = number_format($hContratadas, 2, ',', '.') . ' h contratadas; saldo: ' . number_format(max(0, $saldoH), 2, ',', '.') . ' h';
				} elseif ($contrato->get('horas_contratadas') !== null && $contrato->get('horas_consumidas') !== null) {
					$hContratadas = (float)str_replace(',', '.', $contrato->get('horas_contratadas'));
					$hConsumidas = (float)str_replace(',', '.', $contrato->get('horas_consumidas'));
					$saldoH = max(0, $hContratadas - $hConsumidas);
					$horasContratoTexto = number_format($hContratadas, 2, ',', '.') . ' h contratadas; saldo: ' . number_format($saldoH, 2, ',', '.') . ' h';
				} elseif ($contrato->get('minutos_contratados') !== null && $contrato->get('minutos_consumidos') !== null) {
					$saldoContratoMinutos = (int)$contrato->get('minutos_contratados') - (int)$contrato->get('minutos_consumidos');
					$horasContratoTexto = number_format((int)$contrato->get('minutos_contratados') / 60, 1, ',', '.') . ' h contratadas; saldo: ' . number_format(max(0, $saldoContratoMinutos) / 60, 1, ',', '.') . ' h';
				} elseif ($contrato->get('saldo_minutos') !== null) {
					$saldoContratoMinutos = (int)$contrato->get('saldo_minutos');
					$horasContratoTexto = 'Saldo: ' . number_format($saldoContratoMinutos / 60, 1, ',', '.') . ' h';
				} elseif ($contrato->get('horas_contratadas') !== null) {
					$hContratadas = (float)str_replace(',', '.', $contrato->get('horas_contratadas'));
					$horasContratoTexto = number_format($hContratadas, 2, ',', '.') . ' h contratadas';
				} elseif ($contrato->get('saldo') !== null) {
					$saldoH = (float)str_replace(',', '.', $contrato->get('saldo'));
					$horasContratoTexto = 'Saldo: ' . number_format(max(0, $saldoH), 2, ',', '.') . ' h';
				}
			}
		} catch (\Throwable $e) {}

		$tecnicoResponsavelLabel = $this->_tecnicoResponsavelDisplayLabel($ticket);
		$this->set('tecnicoResponsavelLabel', $tecnicoResponsavelLabel);

		$this->set('title', "Ticket $idticket" );
		$this->set('users', $users);
		$this->set('ticketsmovs', $ticketsmovs);
		$this->set('ticketsusers', $ticketsusers);
		$this->set('ticketanexos', $ticketanexos);
		$this->set('ticketcomentarios', $ticketcomentarios);
		$this->set('ticket', $ticket);
		$this->set('ticketshoras', $ticketshoras);
		$this->set('ordem', $ordem);
		$this->set('timerAtivo', $timerAtivo);
		$this->set('timerPausado', $timerPausado);
		$this->set('timerPausadoElapsedTexto', $timerPausadoElapsedTexto);
		$this->set('minutosTicket', $minutosTicket);
		$this->set('minutosClienteMes', $minutosClienteMes);
		$this->set('horasContratoTexto', $horasContratoTexto);

		$this->set('cliente', $clienteNome);
		@$this->set('solicitante', $solicitante->name);

		if (!$this->request->is(['post', 'put']) && $this->request->getQuery('classic') !== '1') {
			$this->viewBuilder()->setLayout('default');
			$this->viewBuilder()->setTemplate('react_app');
			// Evita tarja duplicada: o React já mostra "Ticket #…" no conteúdo.
			$this->set('hideLayoutPageTitle', true);
			$this->set('reactBoot', $this->_reactBoot('tech_edit', (int)$idticket, array_replace_recursive([
				'classicEditUrl' => Router::url(['action' => 'edit', $idticket, '?' => ['classic' => '1']]),
			], $this->_servicedeskBootMerge())));
			if ($this->request->getQuery('sd') === '1') {
				$this->set('hideServicedeskOpenTicketCta', true);
			}
		}
	}

	public function view($idticket = null){
		$idempresa = $this->Auth->user('idempresa');
		$idcliente = $this->Auth->user('idcliente');
		// Ticket 
			// Impede vazamento entre empresas.
			$ticket = $this->Tickets->find('all',['contain' => ['Clientes', 'Users']])
				->where(['tickets.id' => $idticket]);
			$this->Abac->applyToQuery($ticket, 'Tickets', 'tickets');
			$ticket = $ticket->first();
			if(empty($ticket)) {
				$this->Flash->error('Não foi encontrado um ticket com o Id informado na Empresa selecionada.');
				return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
			}
		// Solicitante 
			$solicitante = $this->Users->findById($ticket->idsolicitante)->select(['name'])->first();
		// Permissões 
			if(empty($idticket)) {
				$this->Flash->error('Selecione um ticket para editar.');
				return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
			}

			if ($this->Auth->user('role') == C_RoleCliente) {
				$clienteBase = $this->Clientes->findById($this->Auth->user('idcliente'))->first();
				$clienteVerifica = null;

				if (!empty($clienteBase)) {
					if($clienteBase->tipo == C_ClientesTipoJuridica) {
						$qCv = $this->Clientes->findByCnpj(removeCaracteres($clienteBase->cnpj));
						$this->Abac->applyToQuery($qCv, 'Clientes');
						$clienteVerifica = $qCv->first();
					} else {
						$qCv = $this->Clientes->findByCpf(removeCaracteres($clienteBase->cpf));
						$this->Abac->applyToQuery($qCv, 'Clientes');
						$clienteVerifica = $qCv->first();
					}
				}

				if (empty($clienteVerifica) || ($clienteVerifica->cpf != $clienteBase->cpf && $clienteBase->cnpj != $clienteVerifica->cnpj)) {
					$this->Flash->error('Você não possui permissão para visualizar este ticket.');
					return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
				}
			}

			if ($this->Auth->user('role') == C_RoleCliente && !$this->Auth->user('permissaoacesso')
				&& (int)$ticket->idautor !== (int)$this->Auth->user('id')
				&& (int)$ticket->idcliente !== (int)$this->Auth->user('idcliente')) {
				$this->Flash->error('Você não possui permissão para visualizar este ticket.');
				return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
			}
		// Comentar 
			$bComentar = false;

			if($this->Auth->user('admin') != 1){
				// Verifica se é um ticket sem outros funcionários, pois nesses casos, o ticket não entra em na consulta retornada de 'Ticketsusers'
				$qMt = $this->Tickets->findById($idticket);
				$this->Abac->applyToQuery($qMt, 'Tickets', 'Tickets');
				$meuticket = $qMt->toArray();
				if ($this->Auth->user('id') != $meuticket[0]->idautor && $this->Auth->user('idcliente') != $meuticket[0]->idcliente) {
					$this->Flash->error('Você não possui permissões para visualizar este Ticket. Contate um administrador do sistema.');
					return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
				} else $bComentar = true;
			}

		$this->viewBuilder()->setLayout('default');
		$this->viewBuilder()->setTemplate('react_app');
		$this->set('title', "Ticket $idticket");
		$this->set('hideLayoutPageTitle', true);
		$this->set('reactBoot', $this->_reactBoot('client_view', (int)$idticket, $this->_servicedeskBootMerge()));
	}

	public function imprimir($idticket = null){
		$idempresa = $this->Auth->user('idempresa');
		$idcliente = $this->Auth->user('idcliente');
		$this->viewBuilder()->setLayout('print');
		
		// Ticket 
			// Impede vazamento entre empresas.
			$ticket = $this->Tickets->find('all',['contain' => ['Clientes', 'Users']])
				->where(['tickets.id' => $idticket]);
			$this->Abac->applyToQuery($ticket, 'Tickets', 'tickets');
			$ticket = $ticket->first();
			if(empty($ticket)) {
				$this->Flash->error('Não foi encontrado um ticket com o Id informado na Empresa selecionada.');
				return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
			}
		// Solicitante 
			$solicitante = $this->Users->findById($ticket->idsolicitante)->select(['name'])->first();
		// Permissões 
			if(empty($idticket)) {
				$this->Flash->error('Selecione um ticket para editar.');
				return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
			}

			if ($this->Auth->user('role') == C_RoleCliente) {
				$clienteBase = $this->Clientes->findById($this->Auth->user('idcliente'))->first();
				$clienteVerifica = null;

				if (!empty($clienteBase)) {
					if($clienteBase->tipo == C_ClientesTipoJuridica) {
						$qCv = $this->Clientes->findByCnpj(removeCaracteres($clienteBase->cnpj));
						$this->Abac->applyToQuery($qCv, 'Clientes');
						$clienteVerifica = $qCv->first();
					} else {
						$qCv = $this->Clientes->findByCpf(removeCaracteres($clienteBase->cpf));
						$this->Abac->applyToQuery($qCv, 'Clientes');
						$clienteVerifica = $qCv->first();
					}
				}

				if (empty($clienteVerifica) || ($clienteVerifica->cpf != $clienteBase->cpf && $clienteBase->cnpj != $clienteVerifica->cnpj)) {
					$this->Flash->error('Você não possui permissão para visualizar este ticket.');
					return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
				}
			}

			if ($this->Auth->user('role') == C_RoleCliente && !$this->Auth->user('permissaoacesso')
				&& (int)$ticket->idautor !== (int)$this->Auth->user('id')
				&& (int)$ticket->idcliente !== (int)$this->Auth->user('idcliente')) {
				$this->Flash->error('Você não possui permissão para visualizar este ticket.');
				return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
			}
				// Cliente 
			$cliente = $this->Clientes->findById($ticket->idcliente)->select(['razaosocial', 'nomefantasia', 'nome', 'tipo'])->first();
		// 

		
		$clienteNome = $cliente->tipo == C_ClientesTipoFisica ? $cliente->nome : $cliente->razaosocial;
		
		if(isset($solicitante->name)) $this->set('solicitante', $solicitante->name);

		$ticketcomentarios = $this->Ticketcomentarios->find('all', [
			'contain' => ['users'],
		])->where(['Ticketcomentarios.idticket' => $idticket])->order(['Ticketcomentarios.id' => 'ASC'])->toArray();

		$qAnxPrint = $this->Ticketsanexos->find('all')->where(['idticket' => $idticket]);
		$this->Abac->applyToQuery($qAnxPrint, 'Ticketsanexos', 'Ticketsanexos');
		$ticketanexos = $qAnxPrint->order(['Ticketsanexos.id' => 'ASC'])->toArray();

		$ticketsmovs = $this->Ticketsmovs->find('all', ['contain' => ['users']])
			->where(['Ticketsmovs.idticket' => $ticket->id])
			->order(['ticketsmovs.id' => 'ASC'])
			->toArray();

		$severidadeLabel = null;
		if (in_array('severidade', $this->Tickets->getSchema()->columns(), true) && $ticket->get('severidade') !== null && $ticket->get('severidade') !== '') {
			$severidadeLabel = $this->_ticketSeveridadeLabel((string)$ticket->get('severidade'));
		}

		$this->set('cliente', $clienteNome);
		$this->set('ticket', $ticket);
		$this->set('ticketcomentarios', $ticketcomentarios);
		$this->set('ticketanexos', $ticketanexos);
		$this->set('ticketsmovs', $ticketsmovs);
		$this->set('severidadeLabel', $severidadeLabel);
		$this->set('title', "Ticket $idticket" );
	}

	public function cancelar($idticket = null){
		if ($this->request->getQuery('sd') === '1') {
			$this->viewBuilder()->setLayout('servicedesk');
		}
		$ticket = $this->Tickets->get($idticket);

		if($ticket->idautor != $this->Auth->user('id') && !$this->Auth->user('admin') && ($this->Auth->user('role') == 1 && !$this->Auth->user('permissaoacesso'))) {
			$this->Flash->error('Você não possui permissões para cancelar este Ticket. Contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$observacao = "";

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();

			if (isset($data['observacao'])) $observacao = $data['observacao'];

			$sitantiga = $ticket->situacao;
			$ticket->situacao = C_TicketSituacaoFechado;
			$sd = $this->request->getQuery('sd') === '1' || (isset($data['sd']) && (string)$data['sd'] === '1');

			if ($this->Tickets->save($ticket)) {
				$this->criarMov($idticket, $sitantiga, C_TicketSituacaoFechado, $observacao);
				$this->Flash->success("Ticket cancelado.");
			} else $this->Flash->error("Erro ao cancelar Ticket.");

			return $this->redirect(['action' => $this->Auth->user('role') == 0 ? 'edit' : 'view', $idticket, '?' => $sd ? ['sd' => '1'] : []]);
		}
		$this->set('title', 'Ticket ' . $idticket);
		$this->set('ticket', $ticket);
	}

	public function reabrir($idticket = null){
		$ticket = $this->Tickets->get($idticket);

		$observacao = "";

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();

			if (isset($data['observacao'])) $observacao = $data['observacao'];

			$sitantiga = $ticket->situacao;

			$ticket->situacao = C_TicketSituacaoPendente;

			if ($this->Tickets->save($ticket)) {
				//Cria a movimentação.
				$this->criarMov($idticket, $sitantiga, C_TicketSituacaoPendente, $observacao);
				$this->Flash->success("Ticket Reaberto.");

				return $this->redirect(['action' => 'edit', $idticket]);
			} else {
				$this->Flash->error("Erro ao reabrir Ticket.");
				return $this->redirect(['action' => 'edit', $idticket]);
			}
		}
		$this->set('title', 'Ticket ' . $idticket);
		$this->set('ticket', $ticket);
	}

	public function cadhoras($idticket = null) {
		$this->set('title', 'Ticket ' . $idticket);

		$horas = $this->Ticketshoras->horasTicket($idticket);

		$this->set('horas', $horas);
		$this->set('idticket', $idticket);
	}

	public function delete($id = null) {
		//Verifica permissões
		if (!$this->Auth->user('admin')){
			$this->Flash->error('Você não possui permissões para deletar este Ticket. Contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$entity = $this->Tickets->get($id);

		if ($this->Tickets->delete($entity)) {
			$this->Flash->success(__('Ticket apagado com sucesso!'));
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
			return $this->redirect(['controller' => 'Tickets', 'action' => 'index']);
		}else $this->Flash->error(__('Não foi possível deletar o ticket.'));
	}

	public function alterarsituacao($idticket = null, $sit = null) {
		$qAlt = $this->Tickets->find('all')->where(['id' => $idticket]);
		$this->Abac->applyToQuery($qAlt, 'Tickets', 'Tickets');
		$ticket = $qAlt->first();
		if (!$ticket) {
			$this->Flash->error('Ticket não encontrado.');
			return $this->redirect(['action' => 'index']);
		}
		$situacao = $sit;
		$sitantiga = $ticket->situacao;
		$ticket->situacao = $situacao;

		if ($situacao == C_TicketSituacaoResolvido || $situacao == C_TicketSituacaoFechado) $ticket->datafinalizado = date('d/m/Y');

		if ((int)$situacao === (int)C_TicketSituacaoEmandamento && (int)$situacao !== (int)$sitantiga) {
			$this->_assignTecnicoEmExecucao($ticket, (int)$idticket);
		}
		if (($situacao == C_TicketSituacaoResolvido || $situacao == C_TicketSituacaoFechado) && (int)$situacao !== (int)$sitantiga) {
			$this->_ensureTecnicoResponsavelAoFechamento($ticket);
		}

		if ($this->Tickets->save($ticket)) {
			try {
				$this->criarMov($ticket->id, $sitantiga, $ticket->situacao);
			} catch (\Throwable $e) {
				$this->log('Tickets::alterarsituacao criarMov: ' . $e->getMessage(), 'error');
			}
			$this->Flash->success("Situação do ticket alterada.");
			try {
				if ($situacao == C_TicketSituacaoPendente && $situacao != $sitantiga) $this->email($idticket, C_TicketsAcaoPendente, null, $this->Auth->user('idempresa'));
				else if ($situacao == C_TicketSituacaoEmandamento && $situacao != $sitantiga) $this->email($idticket, C_TicketsAcaoEmandamento, null, $this->Auth->user('idempresa'));
				else if ($situacao == C_TicketSituacaoFechado && $situacao != $sitantiga) $this->email($idticket, C_TicketsAcaoFechado, null, $this->Auth->user('idempresa'));
				else if ($situacao == C_TicketSituacaoResolvido && $situacao != $sitantiga) $this->email($idticket, null, null, $this->Auth->user('idempresa'));
			} catch (\Throwable $e) {
				$this->log('Tickets::alterarsituacao email: ' . $e->getMessage(), 'error');
			}
			if ($this->request->getHeaderLine('HX-Request')) {
				return $this->redirect(['controller' => 'Tickets', 'action' => 'panelLeftFragment', $idticket]);
			}
			$sd = $this->request->getQuery('sd') === '1';
			if (in_array($ticket->situacao, [C_TicketSituacaoPendente, C_TicketSituacaoResolvido], true)) {
				return $this->redirect($sd ? ['controller' => 'Servicedesk', 'action' => 'index'] : ['controller' => 'Tickets', 'action' => 'index']);
			}

			return $this->redirect(['controller' => 'Tickets', 'action' => 'edit', $idticket, '?' => $sd ? ['sd' => '1'] : []]);
		}
	}

	/**
	 * Retorna apenas o HTML do painel esquerdo do ticket (para HTMX swap).
	 * GET; requer autenticação.
	 */
	public function panelLeftFragment($idticket = null) {
		if (!$idticket) return $this->redirect(['action' => 'index']);
		$qPl = $this->Tickets->findById($idticket)->contain(['users']);
		$this->Abac->applyToQuery($qPl, 'Tickets', 'Tickets');
		$ticket = $qPl->first();
		if (!$ticket) {
			return $this->response->withStatus(404);
		}
		$this->_setEditPanelLeftVars($idticket);
		$this->viewBuilder()->setLayout('ajax');
		$this->viewBuilder()->setTemplate('edit_panel_left');
	}

	public function viewhomologacoes($idticket) {
		$homologacao = $this->Homologacoes->findByIdticket($idticket)->toArray();

		$this->set('homologacao', $homologacao[0]);
	}

	public function poderesolver($idchamado){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');

		$user = $this->Users->get($this->Auth->user('id'));

		if($idchamado == $user->ticketemand) echo 'poderesolver';
		else if( $user->ticketemand != '' && $user->ticketemand != null ) echo $user->ticketemand;
		else echo 'poderesolver';
	}

	public function faturas($idcliente = null) {
		// Lista de serviços
		$optServicos = $this->Servicos->find('list', ['keyField' => 'id', 'valueField' => 'nome'])->order(['nome'])->toArray();
		$this->set('optServicos', $optServicos);
		// Lista de módulos
		$optModulos = $this->Modulos->find('list', ['keyField' => 'id', 'valueField' => 'nome'])->order(['nome'])->toArray();
		$this->set('optModulos', $optModulos);

		if($idcliente == 61) $this->set('terceiros', 'terceiros');
		else $this->set('terceiros', 'naotemterceiros');
	}

	public function viewfaturas($idticket) {
		$faturas = $this->Faturas->findByIdticket($idticket)->toArray();

		$parcelas = $this->Faturaparcelas->findByIdfatura($faturas[0]->id) ->order([ 'Faturaparcelas.id' => 'ASC' ])->toArray();

		$ticketsservicos = $this->Ticketsservicos->findByIdticket($idticket)->contain(['Servicos' => ['fields' => ['Servicos.id', 'Servicos.nome']]])->toArray();
		$ticketsmodulos = $this->Ticketsmodulos->findByIdticket($idticket)->contain(['Modulos' => ['fields' => ['Modulos.id', 'Modulos.nome']]])->toArray();

		$this->set('fatura', $faturas[0]);
		$this->set('parcelas', $parcelas);
		$this->set('ticketsservicos', $ticketsservicos);
		$this->set('ticketsmodulos', $ticketsmodulos);
	}

	public function cancelamento($idcliente) {
		$cliservicos = $this->Cliservicos->findByIdcliente($idcliente)->contain(['Servicos' => ['fields' => ['Servicos.id', 'Servicos.nome']]])->toArray();
		$climodulos = $this->Climodulos->findByIdcliente($idcliente)->contain(['Modulos' => ['fields' => ['Modulos.id', 'Modulos.nome']]])->toArray();
		$mensalidade = $this->Clientes->get($idcliente)->valormensalidade;

		$this->set('mensalidade', $mensalidade);
		$this->set('cliservicos', $cliservicos);
		$this->set('climodulos', $climodulos);
	}

	public function cancelamentoview($idticket) {
		$ticket = $this->Tickets->get($idticket);
		$servicos = $this->Cancelamento->findByIdticket($idticket)->contain(['Servicos' => ['fields' => ['Servicos.id', 'Servicos.nome']]])->where(['idservico is not' => null,])->toArray();
		$modulos = $this->Cancelamento->findByIdticket($idticket)->contain([ 'Modulos' => ['fields' => ['Modulos.id', 'Modulos.nome']]])->where(['idmodulo is not' => null,])->toArray();
		$mensalidadefinal = $this->Cancelamento->findByIdticket($idticket)->where(['valormensalidade is not' => null,])->first();
		$mensalidade = $this->Clientes->get($ticket->idcliente)->valormensalidade;

		$this->set('mensalidade', $mensalidade);
		if(!empty($mensalidadefinal)) $this->set('mensalidadefinal', $mensalidadefinal->valorfinalmensalidade);
		$this->set('cliservicos', $servicos);
		$this->set('climodulos', $modulos);
	}

	public function checkboxesParcelas($idparcela, $marcou){
		$this->autoRender = false;

		$parcela = $this->Faturaparcelas->get($idparcela, 
			['contain' => [
				'Faturas' => ['fields' => ['Faturas.id', 'Faturas.idticket', 'Faturas.tipopagamento']],
				'Faturas.Tickets' => ['fields' => ['Faturas.id', 'Faturas.idticket', 'Tickets.id', 'Tickets.idcliente']],
		]]);		
		
		$parcela->faturado = $marcou;
		$this->Faturaparcelas->save($parcela);

		$parcelasdessafaturapraversemarcoutodas = $this->Faturaparcelas->findByIdfatura($parcela->idfatura);		
		$resolvido = 'sim';

		foreach($parcelasdessafaturapraversemarcoutodas as $reg){
			echo $reg->faturado;
			if($reg->faturado != 1) $resolvido = 'nao';
		}


		if($parcela->ticket->situacao == C_TicketSituacaoPendente && $marcou == 1 && $resolvido == 'nao'){
			$ticket = $this->Tickets->get($parcela->fatura->idticket);
			$sitantiga = $ticket->situacao;

			$ticket->situacao = C_TicketSituacaoEmandamento;
			$ticket->datafinalizado = date('d/m/Y');

			if ($this->Tickets->save($ticket)) {
				//Cria a movimentação.
				$this->criarMov($ticket->id, $sitantiga, C_TicketSituacaoEmandamento, '');
			}
		}


		if($resolvido == 'sim'){
			$ticket = $this->Tickets->get($parcela->fatura->idticket);
			$sitantiga = $ticket->situacao;

			$ticket->situacao = C_TicketSituacaoResolvido;
			$ticket->datafinalizado = date('d/m/Y');
			$this->_ensureTecnicoResponsavelAoFechamento($ticket);

			if ($this->Tickets->save($ticket)) {
				// Desmarca nos user q tavam fazendo ele
				$users = $this->Users->findByTicketemand($ticket->id)->toArray();
				foreach($users as $reg){
					$user = $this->Users->get($reg->id);
					$user->ticketemand = null;
					$this->Users->save($user);
				}
				//Cria a movimentação.
				$this->criarMov($ticket->id, $sitantiga, C_TicketSituacaoResolvido, '');
			}
		}
	}

	public function mudasituacaofatura($idfatura, $situacao){
		$this->autoRender = false;

		$fatura = $this->Faturas->get($idfatura);		
		$fatura->situacao = $situacao;
		$this->Faturas->save($fatura);
	}

	public function email($idticket, $situacao = null, $redirect = null) {
		if($situacao == 'redirect') {
			$situacao = null;
			$redirect = 'redirect';
		}

		// GET: exibe tela para selecionar/digitar destinatário (sem envio automático)
		if (!$this->request->is(['post', 'put'])) {
			$this->set('title', "Enviar e-mail - Ticket $idticket");

			$ticket = $this->Tickets->find()
				->contain(['Users', 'Clientes'])
				->where(['Tickets.id' => $idticket]);
			$this->Abac->applyToQuery($ticket, 'Tickets', 'Tickets');
			$ticket = $ticket->first();
			if (!$ticket) {
				$this->Flash->error('Ticket não encontrado.');
				return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
			}

			$cliente = null;
			try {
				// prioridade: usar associação que já vem no contain()
				$cliente = $ticket->cliente ?? null;
				if (!is_object($cliente)) {
					// fallback: carregar do banco pelo id
					$this->loadModel('Clientes');
					$cliente = $this->Clientes->findById($ticket->idcliente)->first();
				}
			} catch (\Throwable $e) {}

			// Extrai e-mails de forma consistente (mesma ideia do parseEmailList em TicketsTable).
			$parseEmailList = function ($value) {
				$value = (string)$value;
				if (trim($value) === '') return [];

				$value = str_replace(["\r", "\n", "\t"], ' ', $value);
				$parts = preg_split('/[;,\\s]+/', $value, -1, PREG_SPLIT_NO_EMPTY);

				$out = [];
				foreach ($parts as $p) {
					$p = trim((string)$p);
					if ($p === '') continue;
					if (filter_var($p, FILTER_VALIDATE_EMAIL)) $out[] = $p;
				}

				// Fallback: caso o conteúdo venha com algum formato inesperado,
				// ainda assim tenta listar tokens que parecem conter e-mail.
				if (empty($out)) {
					foreach ($parts as $p) {
						$p = trim((string)$p);
						if ($p === '') continue;
						if (strpos($p, '@') !== false) $out[] = $p;
					}
				}

				return array_values(array_unique($out));
			};

			$sugestoes = [];

			// Sugestões devem ser SOMENTE dos e-mails cadastrados no cliente
			// monta a partir do objeto de Cliente disponível (contain ou findById)
			if (is_object($cliente)) {
				$sugestoes = array_merge(
					$sugestoes,
					$parseEmailList($cliente->email ?? ''),
					$parseEmailList($cliente->emailresponsavel ?? '')
				);
			}

			// Inclui também os e-mails cadastrados no cliente na guia "Usuários"
			// (Users.email vinculados ao idcliente do ticket).
			$idclienteDoTicket = (int)($ticket->idcliente ?? 0);
			if (!empty($idclienteDoTicket)) {
				try {
					$usuariosQueryAtivos = $this->Users
						->find()
						->select(['email' => 'Users.email'])
						->where(['Users.idcliente' => $idclienteDoTicket, 'Users.inativo' => 0]);

					$usuariosEmails = $usuariosQueryAtivos->toArray();

					// Se não houver ativos, lista também inativos (para não ficar vazio).
					if (empty($usuariosEmails)) {
						$usuariosEmails = $this->Users
							->find()
							->select(['email' => 'Users.email'])
							->where(['Users.idcliente' => $idclienteDoTicket])
							->toArray();
					}

					foreach ($usuariosEmails as $u) {
						$email = is_object($u) ? ($u->email ?? '') : ($u['email'] ?? '');
						if (trim((string)$email) === '') continue;
						$sugestoes = array_merge($sugestoes, $parseEmailList($email));
					}
				} catch (\Throwable $e) {}
			}

			// Fallback: caso o findById falhe ou venha sem emailresponsavel,
			// tenta extrair do cliente vindo via contain() do ticket.
			if (empty($sugestoes) && is_object($ticket->cliente ?? null)) {
				$sugestoes = array_merge(
					$sugestoes,
					$parseEmailList($ticket->cliente->email ?? ''),
					$parseEmailList($ticket->cliente->emailresponsavel ?? '')
				);
			}

			$sugestoes = array_values(array_unique($sugestoes));

			$this->set('ticket', $ticket);
			$this->set('sugestoes', $sugestoes);
			$this->set('defaultPara', (string)($ticket->user->email ?? $ticket->email ?? ''));
			$this->set('situacao', $situacao);
			$this->set('redirectAfter', $redirect);
			return;
		}

		// POST: envia para destinatário(s) informado(s)
		$para = (string)($this->request->getData('para') ?? '');
		$selecionados = (array)($this->request->getData('sugestoes') ?? []);
		$selecionados = array_values(array_filter(array_map('trim', $selecionados)));
		$emailInput = trim($para . ';' . implode(';', $selecionados));

		$emailDest = $this->Tickets->email($idticket, $situacao, $emailInput, $this->Auth->user('idempresa'));

		if(!empty($emailDest)){
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $idticket);
			if($this->Auth->user('role') == 0) $this->Flash->success("E-mail enviado com sucesso para '$emailDest'!");
		} else {
			$this->Flash->error('Erro ao enviar e-mail.');
		}

		$redir = $this->request->getData('redirect') ?: $redirect;
		if(!empty($redir)) return $this->redirect(['action' => 'edit', $idticket]);
		return $this->redirect(['action' => 'finalizados']);
	}

	public function emailvarios() {
		$data = $this->request->getData();
		$idticket = $data['idticket'];
		foreach($data['email'] as $dest) $this->Tickets->email($idticket, null, $dest, $this->Auth->user('idempresa'));

		$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $idticket);
		if($this->Auth->user('role') == 0) $this->Flash->success("E-mail enviado com sucesso!");
		return $this->redirect(['action' => 'edit', $idticket]);
	}

	/**
	 * Timer (Horas Técnicas) – todas as ações com try/catch para evitar "An Internal Error Has Occurred".
	 */
	public function timerIniciar($idticket = null) {
		$this->request->allowMethod(['post']);
		if (!$idticket) {
			$this->Flash->error('Ticket não informado.');
			return $this->redirect(['action' => 'index']);
		}
		try {
			$qTm = $this->Tickets->find()->where(['id' => $idticket]);
			$this->Abac->applyToQuery($qTm, 'Tickets', 'Tickets');
			$ticket = $qTm->first();
			if (!$ticket) {
				$this->Flash->error('Ticket não encontrado.');
				return $this->redirect(['action' => 'index']);
			}
			$this->loadModel('AtendimentoTimer');
			$tUserCol = $this->_atendimentoTimerUserColumn();
			$ativo = $this->AtendimentoTimer->find()->where(['idticket' => $idticket, $tUserCol => $this->Auth->user('id'), 'hora_fim IS' => null])->first();
			if ($ativo) {
				$this->Flash->warning('Já existe um timer em andamento para este ticket.');
				return $this->redirect(['action' => 'edit', $idticket]);
			}
			$agora = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
			$novo = $this->AtendimentoTimer->newEntity([
				'idticket' => (int)$idticket,
				$tUserCol => (int)$this->Auth->user('id'),
				'idempresa' => (int)$this->Auth->user('idempresa'),
				'hora_inicio' => $agora->format('Y-m-d H:i:s'),
			]);
			if (!$this->AtendimentoTimer->save($novo)) {
				$this->log('timerIniciar save: ' . json_encode($novo->getErrors()), 'error');
				$this->Flash->error('Erro ao iniciar o timer. Verifique logs/error.log ou ative debug em app_local.php.');
				return $this->redirect(['action' => 'edit', $idticket]);
			}
			$this->criarMov($idticket, $ticket->situacao, C_TicketTimerIniciado, 'Timer de horas técnicas iniciado.');
			$this->Flash->success('Timer iniciado.');
		} catch (\Throwable $e) {
			$this->log($e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
			$msg = $e->getMessage();
			if (stripos($msg, 'does not exist') !== false || stripos($msg, 'relation') !== false || stripos($msg, 'undefined table') !== false) {
				$this->Flash->error('Tabela atendimento_timer não existe. Na pasta do portal execute: verificar_atendimento_timer.bat ou php scripts/verificar_criar_atendimento_timer.php');
			} elseif (stripos($msg, 'column') !== false && stripos($msg, 'exist') !== false) {
				$this->Flash->error('Tabela atendimento_timer com colunas incorretas. Execute verificar_atendimento_timer.bat ou veja docs/CONFIRMAR_TABELA_ATENDIMENTO_TIMER.md');
			} else {
				$this->Flash->error('Erro ao iniciar o timer. Verifique logs/error.log ou ative debug em app_local.php.');
			}
			return $this->redirect(['action' => 'edit', $idticket]);
		}
		if ($this->request->getHeaderLine('HX-Request')) {
			return $this->redirect(['action' => 'panelLeftFragment', $idticket]);
		}
		return $this->redirect(['action' => 'edit', $idticket]);
	}

	public function timerPausar($idticket = null) {
		$this->request->allowMethod(['post']);
		if (!$idticket) {
			$this->Flash->error('Ticket não informado.');
			return $this->redirect(['action' => 'index']);
		}
		try {
			$qTm = $this->Tickets->find()->where(['id' => $idticket]);
			$this->Abac->applyToQuery($qTm, 'Tickets', 'Tickets');
			$ticket = $qTm->first();
			if (!$ticket) {
				$this->Flash->error('Ticket não encontrado.');
				return $this->redirect(['action' => 'index']);
			}
			$this->loadModel('AtendimentoTimer');
			$tUserCol = $this->_atendimentoTimerUserColumn();
			$timer = $this->AtendimentoTimer->find()->where(['idticket' => $idticket, $tUserCol => $this->Auth->user('id'), 'hora_fim IS' => null])->orderDesc('id')->first();
			if (!$timer) {
				$this->Flash->error('Nenhum timer em andamento para este ticket.');
				return $this->redirect(['action' => 'edit', $idticket]);
			}
			$agora = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
			$timer->set('hora_pausa', $agora->format('Y-m-d H:i:s'));
			if (!$this->AtendimentoTimer->save($timer)) {
				$this->log('timerPausar save: ' . json_encode($timer->getErrors()), 'error');
				$this->Flash->error('Erro ao pausar o timer. Verifique logs/error.log ou ative debug em app_local.php.');
				return $this->redirect(['action' => 'edit', $idticket]);
			}
			$this->criarMov($idticket, $ticket->situacao, C_TicketTimerPausado, 'Timer de horas técnicas pausado.');
			$this->Flash->success('Timer pausado.');
		} catch (\Throwable $e) {
			$this->log($e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
			$msg = $e->getMessage();
			if (stripos($msg, 'does not exist') !== false || stripos($msg, 'relation') !== false || stripos($msg, 'undefined table') !== false) {
				$this->Flash->error('Tabela atendimento_timer não existe. Execute: verificar_atendimento_timer.bat ou php scripts/verificar_criar_atendimento_timer.php');
			} elseif (stripos($msg, 'column') !== false && stripos($msg, 'exist') !== false) {
				$this->Flash->error('Tabela atendimento_timer com colunas incorretas. Veja docs/CONFIRMAR_TABELA_ATENDIMENTO_TIMER.md');
			} else {
				$this->Flash->error('Erro ao pausar o timer. Verifique logs/error.log ou ative debug em app_local.php.');
			}
			return $this->redirect(['action' => 'edit', $idticket]);
		}
		if ($this->request->getHeaderLine('HX-Request')) {
			return $this->redirect(['action' => 'panelLeftFragment', $idticket]);
		}
		return $this->redirect(['action' => 'edit', $idticket]);
	}

	public function timerRetomar($idticket = null) {
		$this->request->allowMethod(['post']);
		if (!$idticket) {
			$this->Flash->error('Ticket não informado.');
			return $this->redirect(['action' => 'index']);
		}
		try {
			$qTm = $this->Tickets->find()->where(['id' => $idticket]);
			$this->Abac->applyToQuery($qTm, 'Tickets', 'Tickets');
			$ticket = $qTm->first();
			if (!$ticket) {
				$this->Flash->error('Ticket não encontrado.');
				return $this->redirect(['action' => 'index']);
			}
			$this->loadModel('AtendimentoTimer');
			$tUserCol = $this->_atendimentoTimerUserColumn();
			$timer = $this->AtendimentoTimer->find()->where(['idticket' => $idticket, $tUserCol => $this->Auth->user('id'), 'hora_fim IS' => null])->orderDesc('id')->first();
			if (!$timer) {
				$this->Flash->error('Nenhum timer em andamento para este ticket.');
				return $this->redirect(['action' => 'edit', $idticket]);
			}
			$agora = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
			$novaHi = $this->_timerRetomarShiftInicio($timer, $agora);
			if ($novaHi === null) {
				$this->Flash->error('Não é possível retomar: estado do timer inválido (pausa não registrada).');
				return $this->redirect(['action' => 'edit', $idticket]);
			}
			$timer->set('hora_inicio', $novaHi);
			$timer->set('hora_pausa', null);
			if (!$this->AtendimentoTimer->save($timer)) {
				$this->log('timerRetomar save: ' . json_encode($timer->getErrors()), 'error');
				$this->Flash->error('Erro ao retomar o timer. Verifique logs/error.log ou ative debug em app_local.php.');
				return $this->redirect(['action' => 'edit', $idticket]);
			}
			$this->criarMov($idticket, $ticket->situacao, C_TicketTimerIniciado, 'Timer de horas técnicas retomado.');
			$this->Flash->success('Timer retomado.');
		} catch (\Throwable $e) {
			$this->log($e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
			$msg = $e->getMessage();
			if (stripos($msg, 'does not exist') !== false || stripos($msg, 'relation') !== false || stripos($msg, 'undefined table') !== false) {
				$this->Flash->error('Tabela atendimento_timer não existe. Execute: verificar_atendimento_timer.bat ou php scripts/verificar_criar_atendimento_timer.php');
			} elseif (stripos($msg, 'column') !== false && stripos($msg, 'exist') !== false) {
				$this->Flash->error('Tabela atendimento_timer com colunas incorretas. Veja docs/CONFIRMAR_TABELA_ATENDIMENTO_TIMER.md');
			} else {
				$this->Flash->error('Erro ao retomar o timer. Verifique logs/error.log ou ative debug em app_local.php.');
			}
			return $this->redirect(['action' => 'edit', $idticket]);
		}
		if ($this->request->getHeaderLine('HX-Request')) {
			return $this->redirect(['action' => 'panelLeftFragment', $idticket]);
		}
		return $this->redirect(['action' => 'edit', $idticket]);
	}

	public function timerFinalizar($idticket = null) {
		$this->request->allowMethod(['post']);
		if (!$idticket) {
			$this->Flash->error('Ticket não informado.');
			return $this->redirect(['action' => 'index']);
		}
		try {
			$qTm = $this->Tickets->find()->where(['id' => $idticket]);
			$this->Abac->applyToQuery($qTm, 'Tickets', 'Tickets');
			$ticket = $qTm->first();
			if (!$ticket) {
				$this->Flash->error('Ticket não encontrado.');
				return $this->redirect(['action' => 'index']);
			}
			$this->loadModel('AtendimentoTimer');
			$tUserCol = $this->_atendimentoTimerUserColumn();
			$timer = $this->AtendimentoTimer->find()->where(['idticket' => $idticket, $tUserCol => $this->Auth->user('id'), 'hora_fim IS' => null])->orderDesc('id')->first();
			if (!$timer) {
				$this->Flash->error('Nenhum timer em andamento para este ticket.');
				return $this->redirect(['action' => 'edit', $idticket]);
			}
			$agora = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
			$timer->set('hora_fim', $agora->format('Y-m-d H:i:s'));
			$horaInicioSql = $this->_timerEntityHoraInicioSqlString($timer);
			$horaFimRaw = $timer->get('hora_fim') ?: $timer->get('horafim');
			$horaFimSql = $this->_ormTimeToString($horaFimRaw);
			$inicio = null;
			$fim = null;
			$duracaoSegundos = 0;
			$duracaoMinutos = 0;
			if ($horaInicioSql && $horaFimSql !== null && $horaFimSql !== '') {
				$inicio = $this->_parseSqlDateTimeForTimer($horaInicioSql);
				$fim = $this->_parseSqlDateTimeForTimer($horaFimSql);
				if ($inicio && $fim) {
					$duracaoSegundos = (int)($fim->getTimestamp() - $inicio->getTimestamp());
					$duracaoMinutos = $duracaoSegundos > 0 ? (int)ceil($duracaoSegundos / 60) : 0;
					$timer->set('duracao_calculada', $duracaoMinutos);
				}
			}
			if (!$this->AtendimentoTimer->save($timer)) {
				$this->log('timerFinalizar save: ' . json_encode($timer->getErrors()), 'error');
				$this->Flash->error('Erro ao finalizar o timer. Verifique logs/error.log ou ative debug em app_local.php para ver o detalhe.');
				return $this->redirect(['action' => 'edit', $idticket]);
			}

			// Registra as horas em Ticketshoras (Horas Cadastradas) para o ticket
			if ($inicio && $fim) {
				try {
					$regHora = $this->Ticketshoras->newEntity([
						'idticket' => (int)$idticket,
						'iduser' => (int)$this->Auth->user('id'),
						'idempresa' => (int)$this->Auth->user('idempresa'),
						'data' => $inicio->format('Y-m-d'),
						'horaini' => $inicio->format('Y-m-d H:i:s'),
						'horafin' => $fim->format('Y-m-d H:i:s'),
					]);
					if ($this->Ticketshoras->save($regHora)) {
						TicketWorklogEventHelper::afterHoraLancada(
							$regHora,
							(int)$this->Auth->user('idempresa'),
							(int)$idticket,
							(int)$this->Auth->user('id'),
							$regHora->horaini,
							$regHora->horafin
						);
					}
				} catch (\Throwable $e) {
					$this->log('Timer: falha ao registrar em Ticketshoras: ' . $e->getMessage(), 'error');
				}
				$this->criarMov($idticket, $ticket->situacao, C_TicketTimerFinalizado, 'Duração: ' . $duracaoMinutos . ' min. Horas registradas em Horas Cadastradas.');
				$billSec = TicketServiceDeskApiService::billingSecondsFromRaw($duracaoSegundos);
				$this->subtrairHorasContrato($ticket->idcliente, $this->Auth->user('idempresa'), $billSec, $duracaoMinutos, (int)$idticket);
			}

			$this->Flash->success('Timer finalizado. Horas registradas. Você pode iniciar um novo timer para continuar o atendimento.');
		} catch (\Throwable $e) {
			$this->log($e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
			$msg = $e->getMessage();
			if (stripos($msg, 'does not exist') !== false || stripos($msg, 'relation') !== false || stripos($msg, 'undefined table') !== false) {
				$this->Flash->error('Tabela atendimento_timer não existe. Execute: verificar_atendimento_timer.bat ou php scripts/verificar_criar_atendimento_timer.php');
			} elseif (stripos($msg, 'column') !== false && stripos($msg, 'exist') !== false) {
				$this->Flash->error('Tabela atendimento_timer com colunas incorretas. Veja docs/CONFIRMAR_TABELA_ATENDIMENTO_TIMER.md');
			} else {
				$this->Flash->error('Erro ao finalizar o timer. Verifique logs/error.log ou ative debug em app_local.php para ver o detalhe.');
			}
			return $this->redirect(['action' => 'edit', $idticket]);
		}
		// HTMX: redirecionar para o fragmento do painel (evita render aqui e possível saída indesejada)
		if ($this->request->getHeaderLine('HX-Request')) {
			return $this->redirect(['action' => 'panelLeftFragment', $idticket]);
		}
		return $this->redirect(['action' => 'edit', $idticket]);
	}

	/**
	 * Subtrai tempo do contrato do cliente (segundos/minutos/horas conforme coluna na tabela).
	 * Ordem: segundos_consumidos → horas_consumidas → saldo → saldo_horas (+ horas_utilizadas) → minutos_consumidos → saldo_minutos.
	 */
	protected function subtrairHorasContrato($idcliente, $idempresa, $duracaoSegundos, $duracaoMinutos = null, $idticket = null) {
		if ($duracaoSegundos <= 0) {
			return;
		}
		if ($duracaoMinutos === null) {
			$duracaoMinutos = $duracaoSegundos > 0 ? (int)ceil($duracaoSegundos / 60) : 0;
		}
		$horasUsadas = round($duracaoSegundos / 3600.0, 4);
		try {
			$table = \Cake\ORM\TableRegistry::getTableLocator()->get('ContratosHoras');
			$contrato = $table->find()->where(['idcliente' => $idcliente, 'idempresa' => $idempresa])->first();
			if (!$contrato) $contrato = $table->find()->where(['idcliente' => $idcliente])->first();
			if (!$contrato) {
				$this->log("subtrairHorasContrato: contrato não encontrado idcliente=$idcliente idempresa=$idempresa", 'error');
				return;
			}
			$saved = false;
			if ($contrato->get('segundos_consumidos') !== null) {
				$atual = (int) $contrato->get('segundos_consumidos');
				$contrato->set('segundos_consumidos', $atual + (int) $duracaoSegundos);
				$table->save($contrato);
				$saved = true;
			}
			if (!$saved && $contrato->get('horas_consumidas') !== null) {
				$atual = $contrato->get('horas_consumidas');
				$atual = is_string($atual) ? (float)str_replace(',', '.', $atual) : (float)$atual;
				$contrato->set('horas_consumidas', round($atual + $horasUsadas, 4));
				$table->save($contrato);
				$saved = true;
			}
			if (!$saved && $contrato->get('saldo') !== null) {
				$saldoAtual = $contrato->get('saldo');
				$saldoAtual = is_string($saldoAtual) ? (float)str_replace(',', '.', $saldoAtual) : (float)$saldoAtual;
				$contrato->set('saldo', max(0, round($saldoAtual - $horasUsadas, 4)));
				$table->save($contrato);
				$saved = true;
			}
			if (!$saved && $contrato->get('saldo_horas') !== null) {
				$saldoH = $contrato->get('saldo_horas');
				$saldoH = is_string($saldoH) ? (float)str_replace(',', '.', $saldoH) : (float)$saldoH;
				$contrato->set('saldo_horas', max(0, round($saldoH - $horasUsadas, 4)));
				if ($contrato->get('horas_utilizadas') !== null) {
					$hu = $contrato->get('horas_utilizadas');
					$hu = is_string($hu) ? (float)str_replace(',', '.', $hu) : (float)$hu;
					$contrato->set('horas_utilizadas', round($hu + $horasUsadas, 4));
				}
				$table->save($contrato);
				$saved = true;
			}
			if (!$saved && $contrato->get('minutos_consumidos') !== null) {
				$contrato->set('minutos_consumidos', (int)$contrato->get('minutos_consumidos') + $duracaoMinutos);
				$table->save($contrato);
				$saved = true;
			}
			if (!$saved && $contrato->get('saldo_minutos') !== null) {
				$contrato->set('saldo_minutos', max(0, (int)$contrato->get('saldo_minutos') - $duracaoMinutos));
				$table->save($contrato);
				$saved = true;
			}
			if ($saved) {
				$this->log("subtrairHorasContrato: atualizado idcliente=$idcliente, -{$duracaoSegundos}s ({$horasUsadas}h)", 'debug');
			} else {
				$this->log("subtrairHorasContrato: nenhuma coluna editável. idcliente=$idcliente", 'error');
			}
			if ($saved && $idticket) {
				TicketWorklogEventHelper::attachContractSnapshotToLatestWorklog(
					(int)$idticket,
					(int)$idempresa,
					(int)$idcliente
				);
				ServiceDeskAlertService::afterContractDebit(
					(int)$this->Auth->user('idempresa'),
					(int)$idticket,
					(int)$this->Auth->user('id'),
					(int)$idcliente,
					(int)$idempresa
				);
			}
		} catch (\Throwable $e) {
			$this->log('subtrairHorasContrato: ' . $e->getMessage() . ' (idcliente=' . $idcliente . ')', 'error');
		}
	}

	/**
	 * Normaliza campo datetime do ORM para string Y-m-d H:i:s.
	 */
	protected function _ormTimeToString($v): ?string {
		if ($v === null || $v === '') {
			return null;
		}
		if (is_object($v) && method_exists($v, 'format')) {
			return $v->format('Y-m-d H:i:s');
		}

		return is_string($v) ? $v : null;
	}

	/**
	 * Converte datetime SQL (Y-m-d H:i:s, com ou sem fração) para DateTime.
	 */
	protected function _parseSqlDateTimeForTimer($v): ?\DateTime {
		$str = $this->_ormTimeToString($v);
		if ($str === null) {
			return null;
		}
		$str = trim($str);
		if (preg_match('/^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2}:\d{2})/', $str, $m)) {
			$tz = new \DateTimeZone('America/Sao_Paulo');
			$dt = \DateTime::createFromFormat('Y-m-d H:i:s', $m[1] . ' ' . $m[2], $tz);

			return $dt ?: null;
		}

		return null;
	}

	/**
	 * Início da sessão em atendimento_timer: hora_inicio primeiro; started_at só se as legadas vazias.
	 *
	 * @param \Cake\Datasource\EntityInterface|object $timer
	 */
	protected function _timerEntityHoraInicioSqlString($timer): ?string {
		foreach (['hora_inicio', 'horainicio', 'started_at'] as $field) {
			try {
				$s = $this->_ormTimeToString($timer->get($field));
			} catch (\Throwable $e) {
				$s = null;
			}
			if ($s !== null && $s !== '') {
				return $s;
			}
		}

		return null;
	}

	/**
	 * Grava minutos calculados na coluna existente no schema (instalações variam).
	 */
	protected function _atendimentoTimerApplyDuracaoMinutos($timer, int $minutos): void {
		try {
			$cols = $this->AtendimentoTimer->getSchema()->columns();
		} catch (\Throwable $e) {
			$timer->set('duracao_calculada', $minutos);

			return;
		}
		if (in_array('duracao_calculada', $cols, true)) {
			$timer->set('duracao_calculada', $minutos);
		} elseif (in_array('duracao_calculada_minutos', $cols, true)) {
			$timer->set('duracao_calculada_minutos', $minutos);
		} elseif (in_array('duracaominutos', $cols, true)) {
			$timer->set('duracaominutos', $minutos);
		}
	}

	/**
	 * Preenche campos canônicos (started_at/ended_at/status etc.) quando existirem no schema.
	 *
	 * @param array<string,mixed> $values
	 */
	protected function _atendimentoTimerApplyCanonicalFields($timer, array $values): void {
		try {
			$cols = $this->AtendimentoTimer->getSchema()->columns();
		} catch (\Throwable $e) {
			return;
		}
		foreach ($values as $col => $value) {
			if (in_array($col, $cols, true)) {
				$timer->set($col, $value);
			}
		}
	}

	protected function _timerCriarMovSafe($idticket, $sitantiga, $sitnova, $observacao): void {
		try {
			$this->criarMov($idticket, $sitantiga, $sitnova, $observacao);
		} catch (\Throwable $e) {
			$this->log('apiTimer criarMov: ' . $e->getMessage(), 'error');
		}
	}

	/**
	 * Coluna do usuário em atendimento_timer (idusuario vs iduser conforme schema).
	 */
	protected function _atendimentoTimerUserColumn(): string {
		static $cached = null;
		if ($cached !== null) {
			return $cached;
		}
		try {
			$this->loadModel('AtendimentoTimer');
			$cached = $this->AtendimentoTimer->usuarioColumn();
		} catch (\Throwable $e) {
			$cached = 'idusuario';
		}

		return $cached;
	}

	/**
	 * Último lançamento em ticketshoras para o modal de auditoria.
	 * Prioriza o utilizador da sessão; se não houver linhas dele, usa o último registo do ticket (horas antigas podem ter iduser vazio ou outro técnico).
	 *
	 * @return array{duracaoHms:string,periodoInicio:string,periodoFim:string}|null
	 */
	protected function _apiUltimaFinalizacaoTicketshoras(int $idticket): ?array {
		$uid = (int)$this->Auth->user('id');
		$row = null;
		if ($uid >= 1) {
			$row = $this->Ticketshoras->find()
				->where(['Ticketshoras.idticket' => $idticket, 'Ticketshoras.iduser' => $uid])
				->orderDesc('Ticketshoras.id')
				->first();
		}
		if ($row === null) {
			$row = $this->Ticketshoras->find()
				->where(['Ticketshoras.idticket' => $idticket])
				->orderDesc('Ticketshoras.id')
				->first();
		}
		if ($row === null) {
			return null;
		}
		$hiRaw = $row->horaini ?? $row->get('horaini');
		$hfRaw = $row->horafin ?? $row->get('horafin');
		$hiS = $this->_ormTimeToString($hiRaw);
		$hfS = $this->_ormTimeToString($hfRaw);
		$ini = $this->_parseSqlDateTimeForTimer($hiS);
		$fim = $this->_parseSqlDateTimeForTimer($hfS);
		if (!$ini || !$fim) {
			return null;
		}
		$sec = max(0, (int)($fim->getTimestamp() - $ini->getTimestamp()));
		$h = intdiv($sec, 3600);
		$m = intdiv($sec % 3600, 60);
		$s = $sec % 60;
		$duracaoHms = sprintf('%02d:%02d:%02d', $h, $m, $s);

		return [
			'duracaoHms' => $duracaoHms,
			'periodoInicio' => $ini->format('d/m/Y H:i'),
			'periodoFim' => $fim->format('d/m/Y H:i'),
		];
	}

	/**
	 * Soma dos minutos contabilizados (cada lançamento: arredondamento para cima da duração real em minutos).
	 */
	protected function _apiMinutosRegistradosTicketCeiling(int $idticket): int {
		$rows = $this->Ticketshoras->find()->where(['idticket' => $idticket])->all();
		$sum = 0;
		foreach ($rows as $h) {
			$sec = TicketServiceDeskApiService::resolveSecondsFromTicketshorasRow($this->Ticketshoras, $h);
			if ($sec > 0) {
				$sum += (int)ceil($sec / 60);
			}
		}

		return $sum;
	}

	/** Soma exata em segundos das entradas em Ticketshoras para o ticket. */
	protected function _apiSegundosRegistradosTicket(int $idticket): int {
		$rows = $this->Ticketshoras->find()->where(['idticket' => $idticket])->all();
		$sum = 0;
		foreach ($rows as $h) {
			$sec = TicketServiceDeskApiService::resolveSecondsFromTicketshorasRow($this->Ticketshoras, $h);
			if ($sec > 0) {
				$sum += (int)$sec;
			}
		}

		return max(0, (int)$sum);
	}

	/**
	 * Estado do timer de horas técnicas + total já registrado em Ticketshoras (para o Service Desk React).
	 */
	protected function _apiHorasTecnicasPayload(int $idticket, $ticket): array {
		$role = (int)$this->Auth->user('role');
		$base = [
			'canUseTimer' => $role === 0,
			'minutosRegistrados' => 0,
			'accumulatedSeconds' => 0,
			'sessao' => null,
			'status' => 'idle',
			'serverUnix' => time(),
			'timerDisponivel' => true,
			'ultimaFinalizacao' => null,
			'ultimaSessao' => null,
		];
		if ($role !== 0) {
			return $base;
		}
		try {
			$base['minutosRegistrados'] = $this->_apiMinutosRegistradosTicketCeiling($idticket);
			$base['accumulatedSeconds'] = $this->_apiSegundosRegistradosTicket($idticket);
		} catch (\Throwable $e) {
			$base['timerDisponivel'] = false;

			return $base;
		}
		try {
			$base['ultimaFinalizacao'] = $this->_apiUltimaFinalizacaoTicketshoras($idticket);
		} catch (\Throwable $e) {
			$base['ultimaFinalizacao'] = null;
		}
		$tUserCol = null;
		try {
			$this->loadModel('AtendimentoTimer');
			$tUserCol = $this->_atendimentoTimerUserColumn();
			$timerAtivo = $this->AtendimentoTimer->find()
				->where([
					'idticket' => $idticket,
					$tUserCol => $this->Auth->user('id'),
					'hora_fim IS' => null,
				])
				->orderDesc('id')
				->first();
			if ($timerAtivo) {
				$hi = $this->_timerEntityHoraInicioSqlString($timerAtivo);
				$hp = $this->_ormTimeToString($timerAtivo->get('hora_pausa') ?: $timerAtivo->get('horapausa'));
				$base['status'] = ($hp !== null && $hp !== '') ? 'paused' : 'running';
				$base['sessao'] = [
					'id' => (int)$timerAtivo->id,
					'status' => $base['status'],
					'startedAt' => $hi,
					'pausedAt' => $hp,
					'horaInicio' => $hi,
					'horaPausa' => $hp,
					'pausado' => $hp !== null && $hp !== '',
				];
			} else {
				$ultimaSessao = $this->AtendimentoTimer->find()
					->where(['idticket' => $idticket, $tUserCol => $this->Auth->user('id')])
					->orderDesc('id')
					->first();
				if ($ultimaSessao) {
					$hi = $this->_timerEntityHoraInicioSqlString($ultimaSessao);
					$hf = $this->_ormTimeToString($ultimaSessao->get('hora_fim') ?: $ultimaSessao->get('horafim'));
					$hpU = $this->_ormTimeToString($ultimaSessao->get('hora_pausa') ?: $ultimaSessao->get('horapausa'));
					$status = strtolower((string)($ultimaSessao->get('status') ?? ''));
					if ($status === 'paused' || $status === 'finished') {
						$base['status'] = $status;
					} elseif (($hf !== null && $hf !== '') && ($hpU !== null && $hpU !== '')) {
						// Pausa via API JSON (hora_fim + hora_pausa) sem coluna status canónica preenchida.
						$base['status'] = 'paused';
					}
					$duracaoSec = 0;
					$iniDt = $this->_parseSqlDateTimeForTimer($hi);
					$fimDt = $this->_parseSqlDateTimeForTimer($hf);
					if ($iniDt && $fimDt) {
						$duracaoSec = max(0, (int)($fimDt->getTimestamp() - $iniDt->getTimestamp()));
					}
					$base['ultimaSessao'] = [
						'id' => (int)$ultimaSessao->id,
						'status' => $status !== '' ? $status : 'idle',
						'startedAt' => $hi,
						'endedAt' => $hf,
						'durationSeconds' => $duracaoSec,
					];
				}
			}
		} catch (\Throwable $e) {
			$base['timerDisponivel'] = false;
		}

		return $base;
	}

	/**
	 * Ao retomar, desloca hora_inicio para que (agora - início) = tempo ativo já decorrido até a pausa.
	 *
	 * @return string|null Nova hora_inicio (Y-m-d H:i:s) ou null se estado inválido.
	 */
	protected function _timerRetomarShiftInicio($timer, \DateTime $agora): ?string {
		$hiRaw = $this->_timerEntityHoraInicioSqlString($timer);
		$hpRaw = $timer->get('hora_pausa') ?: $timer->get('horapausa');
		$inicio = $this->_parseSqlDateTimeForTimer($hiRaw);
		$pausa = $this->_parseSqlDateTimeForTimer($hpRaw);
		if (!$inicio || !$pausa) {
			return null;
		}
		$elapsedSec = (int)($pausa->getTimestamp() - $inicio->getTimestamp());
		if ($elapsedSec < 0) {
			$elapsedSec = 0;
		}
		$novo = clone $agora;
		$novo->modify('-' . $elapsedSec . ' seconds');

		return $novo->format('Y-m-d H:i:s');
	}

	/**
	 * Se o cliente tem coordenadas, exige lat/lng no corpo e valida raio (m).
	 *
	 * @return array{ok?:bool,error?:string,message?:string,distanceM?:float}
	 */
	protected function _timerValidateGeoInicio($ticket, array $body): array {
		try {
			$cl = $this->Clientes->findById((int)$ticket->idcliente)->first();
		} catch (\Throwable $e) {
			return ['ok' => true];
		}
		if (empty($cl)) {
			return ['ok' => true];
		}
		$cols = $this->Clientes->getSchema()->columns();
		$hasLat = in_array('latitude', $cols, true) && $cl->get('latitude') !== null && $cl->get('latitude') !== '';
		$hasLon = in_array('longitude', $cols, true) && $cl->get('longitude') !== null && $cl->get('longitude') !== '';
		if (!$hasLat || !$hasLon) {
			return ['ok' => true];
		}
		$lat = isset($body['lat']) ? (float)$body['lat'] : (isset($body['latitude']) ? (float)$body['latitude'] : null);
		$lng = isset($body['lng']) ? (float)$body['lng'] : (isset($body['longitude']) ? (float)$body['longitude'] : null);
		if ($lat === null || $lng === null || (abs($lat) < 1e-9 && abs($lng) < 1e-9)) {
			return [
				'ok' => false,
				'error' => 'geo_required',
				'message' => 'Informe lat e lng (geolocalização) para iniciar o timer neste cliente.',
			];
		}
		$refLat = (float)$cl->get('latitude');
		$refLng = (float)$cl->get('longitude');
		$raioM = 500.0;
		if (in_array('geo_validacao_raio_m', $cols, true) && $cl->get('geo_validacao_raio_m') !== null && (int)$cl->get('geo_validacao_raio_m') > 0) {
			$raioM = (float)(int)$cl->get('geo_validacao_raio_m');
		}
		$km = TicketServiceDeskApiService::haversineKm($lat, $lng, $refLat, $refLng);
		$distM = $km * 1000.0;
		if ($distM > $raioM) {
			return [
				'ok' => false,
				'error' => 'geo_outside',
				'message' => 'Localização fora do raio permitido para este cliente.',
				'distanceM' => $distM,
			];
		}

		return ['ok' => true, 'distanceM' => $distM];
	}

	/**
	 * Timer (JSON): mesma regra das ações POST legadas, sem redirect/Flash.
	 *
	 * @return array{ok:bool,error?:string,message?:string,duracaoMinutosFinal?:int,durationSecondsFinal?:int}
	 */
	protected function _timerServiceExecute(int $idticket, $ticket, string $acao, array $body = []): array {
		$acao = strtolower(trim($acao));
		$allowed = ['iniciar', 'pausar', 'retomar', 'finalizar', 'editar_duracao_sessao'];
		if (!in_array($acao, $allowed, true)) {
			return ['ok' => false, 'error' => 'invalid_action', 'message' => 'Ação inválida. Use: iniciar, pausar, retomar ou finalizar.'];
		}
		$uid = (int)$this->Auth->user('id');
		$agora = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
		$agoraStr = $agora->format('Y-m-d H:i:s');

		try {
			$this->loadModel('AtendimentoTimer');
		} catch (\Throwable $e) {
			return ['ok' => false, 'error' => 'timer_unavailable', 'message' => 'Timer indisponível.'];
		}

		try {
			$tUserCol = $this->_atendimentoTimerUserColumn();
			if ($acao === 'iniciar' || $acao === 'retomar') {
				if ($acao === 'iniciar') {
					$geo = $this->_timerValidateGeoInicio($ticket, $body);
					if (!empty($geo['ok']) && $geo['ok'] === false) {
						return $geo;
					}
				}
				$ativo = $this->AtendimentoTimer->find()
					->where(['idticket' => $idticket, $tUserCol => $uid, 'hora_fim IS' => null])
					->orderDesc('id')
					->first();
				if ($ativo) {
					$hpStr = $this->_ormTimeToString($ativo->get('hora_pausa') ?: $ativo->get('horapausa'));
					$isPausedOpen = ($hpStr !== null && $hpStr !== '');
					// Fluxo clássico (timerPausar): só grava hora_pausa; hora_fim fica null — retomar deve
					// ajustar esta linha (como timerRetomar), não criar outra nem devolver already_running.
					if ($isPausedOpen) {
						$novaHi = $this->_timerRetomarShiftInicio($ativo, $agora);
						if ($novaHi === null) {
							return ['ok' => false, 'error' => 'invalid_state', 'message' => 'Não é possível retomar: estado do timer inválido (pausa não registrada).'];
						}
						$ativo->set('hora_inicio', $novaHi);
						$ativo->set('hora_pausa', null);
						$this->_atendimentoTimerApplyCanonicalFields($ativo, [
							'status' => 'running',
							'started_at' => $novaHi,
							'paused_at' => null,
						]);
						if (!$this->AtendimentoTimer->save($ativo)) {
							$this->log('apiTimer retomar save (linha aberta pausada): ' . json_encode($ativo->getErrors()), 'error');

							return ['ok' => false, 'error' => 'save_failed', 'message' => 'Não foi possível retomar o timer.'];
						}
						$this->_timerCriarMovSafe($idticket, $ticket->situacao, C_TicketTimerIniciado, 'Timer de horas técnicas retomado.');

						return ['ok' => true, 'message' => 'Timer retomado.', 'status' => 'running', 'startedAt' => $novaHi];
					}
					// Sessão já em execução (sem pausa): idempotente para iniciar/retomar.
					$hiStr = $this->_timerEntityHoraInicioSqlString($ativo);
					if ($hiStr === null || $hiStr === '') {
						return ['ok' => false, 'error' => 'invalid_state', 'message' => 'Estado do timer inválido (início não registrado).'];
					}
					$msg = $acao === 'retomar' ? 'Timer já estava em andamento.' : 'Timer já em andamento.';

					return ['ok' => true, 'message' => $msg, 'status' => 'running', 'startedAt' => $hiStr];
				}
				$novo = $this->AtendimentoTimer->newEntity([
					'idticket' => $idticket,
					$tUserCol => $uid,
					'idempresa' => (int)$this->Auth->user('idempresa'),
					'hora_inicio' => $agoraStr,
				]);
				$this->_atendimentoTimerApplyCanonicalFields($novo, [
					'ticket_id' => $idticket,
					'customer_id' => (int)$ticket->idcliente,
					'user_id' => $uid,
					'status' => 'running',
					'started_at' => $agoraStr,
				]);
				if (!$this->AtendimentoTimer->save($novo)) {
					$this->log('apiTimer iniciar save: ' . json_encode($novo->getErrors()), 'error');

					return ['ok' => false, 'error' => 'save_failed', 'message' => 'Não foi possível gravar o timer. Verifique a tabela atendimento_timer.'];
				}
				$this->_timerCriarMovSafe($idticket, $ticket->situacao, C_TicketTimerIniciado, 'Timer de horas técnicas iniciado.');

				return ['ok' => true, 'message' => $acao === 'retomar' ? 'Timer retomado.' : 'Timer iniciado.', 'status' => 'running', 'startedAt' => $agoraStr];
			}

			$timer = $this->AtendimentoTimer->find()->where(['idticket' => $idticket, $tUserCol => $uid, 'hora_fim IS' => null])->orderDesc('id')->first();
			if (!$timer) {
				if ($acao === 'finalizar') {
					return ['ok' => true, 'message' => 'Atendimento finalizado com o total já acumulado.', 'status' => 'finished'];
				}
				return ['ok' => false, 'error' => 'no_timer', 'message' => 'Nenhum timer em andamento para este ticket.'];
			}

			if ($acao === 'pausar') {
				$horaInicioSql = $this->_timerEntityHoraInicioSqlString($timer);
				$inicio = $this->_parseSqlDateTimeForTimer($horaInicioSql);
				if (!$inicio) {
					return ['ok' => false, 'error' => 'invalid_state', 'message' => 'Não foi possível identificar o início da sessão.'];
				}
				$timer->set('hora_pausa', $agoraStr);
				$timer->set('hora_fim', $agoraStr);
				$this->_atendimentoTimerApplyCanonicalFields($timer, [
					'status' => 'paused',
					'paused_at' => $agoraStr,
					'ended_at' => $agoraStr,
				]);
				$duracaoSegundos = max(0, (int)($agora->getTimestamp() - $inicio->getTimestamp()));
				$duracaoMinutos = $duracaoSegundos > 0 ? (int)ceil($duracaoSegundos / 60) : 0;
				$this->_atendimentoTimerApplyDuracaoMinutos($timer, $duracaoMinutos);
				$this->_atendimentoTimerApplyCanonicalFields($timer, [
					'duration_seconds' => $duracaoSegundos,
				]);
				if (!$this->AtendimentoTimer->save($timer)) {
					$this->log('apiTimer pausar save: ' . json_encode($timer->getErrors()), 'error');

					return ['ok' => false, 'error' => 'save_failed', 'message' => 'Não foi possível pausar o timer.'];
				}
				try {
					$regHora = $this->Ticketshoras->newEntity([
						'idticket' => $idticket,
						'iduser' => (int)$this->Auth->user('id'),
						'idempresa' => (int)$this->Auth->user('idempresa'),
						'data' => $inicio->format('Y-m-d'),
						'horaini' => $inicio->format('Y-m-d H:i:s'),
						'horafin' => $agora->format('Y-m-d H:i:s'),
					]);
					if ($this->Ticketshoras->save($regHora)) {
						TicketWorklogEventHelper::afterHoraLancada(
							$regHora,
							(int)$this->Auth->user('idempresa'),
							$idticket,
							(int)$this->Auth->user('id'),
							$regHora->horaini,
							$regHora->horafin
						);
					}
				} catch (\Throwable $e) {
					$this->log('Timer JSON: falha ao registrar pausa em Ticketshoras: ' . $e->getMessage(), 'error');
				}
				$billSec = TicketServiceDeskApiService::billingSecondsFromRaw($duracaoSegundos);
				$this->subtrairHorasContrato($ticket->idcliente, $this->Auth->user('idempresa'), $billSec, $duracaoMinutos, $idticket);
				$this->_timerCriarMovSafe($idticket, $ticket->situacao, C_TicketTimerPausado, 'Timer de horas técnicas pausado.');

				return ['ok' => true, 'message' => 'Timer pausado.', 'status' => 'paused', 'duracaoMinutosFinal' => $duracaoMinutos, 'durationSecondsFinal' => $duracaoSegundos];
			}
			if ($acao === 'editar_duracao_sessao') {
				$horaInicioSql = $this->_timerEntityHoraInicioSqlString($timer);
				$inicio = $this->_parseSqlDateTimeForTimer($horaInicioSql);
				if (!$inicio) {
					return ['ok' => false, 'error' => 'invalid_state', 'message' => 'Sessão ativa inválida para edição.'];
				}
				$newDuration = (int)($body['durationSeconds'] ?? $body['duration_seconds'] ?? 0);
				if ($newDuration < 0) {
					$newDuration = 0;
				}
				$novoInicio = clone $agora;
				$novoInicio->modify('-' . $newDuration . ' seconds');
				$novoInicioStr = $novoInicio->format('Y-m-d H:i:s');
				$timer->set('hora_inicio', $novoInicioStr);
				$this->_atendimentoTimerApplyCanonicalFields($timer, [
					'started_at' => $novoInicioStr,
				]);
				if (!$this->AtendimentoTimer->save($timer)) {
					$this->log('apiTimer editar_duracao_sessao save: ' . json_encode($timer->getErrors()), 'error');
					return ['ok' => false, 'error' => 'save_failed', 'message' => 'Não foi possível editar a duração da sessão atual.'];
				}

				return ['ok' => true, 'message' => 'Duração da sessão atual atualizada.', 'status' => 'running', 'startedAt' => $novoInicioStr];
			}

			// finalizar
			$timer->set('hora_fim', $agoraStr);
			$this->_atendimentoTimerApplyCanonicalFields($timer, [
				'status' => 'finished',
				'ended_at' => $agoraStr,
			]);
			$horaInicioSql = $this->_timerEntityHoraInicioSqlString($timer);
			$horaFim = $timer->get('hora_fim') ?: $timer->get('horafim');
			$inicio = null;
			$fim = null;
			$duracaoSegundos = 0;
			$duracaoMinutos = 0;
			if ($horaInicioSql && $horaFim) {
				$inicio = $this->_parseSqlDateTimeForTimer($horaInicioSql);
				$fim = $this->_parseSqlDateTimeForTimer($horaFim);
				if ($inicio && $fim) {
					$duracaoSegundos = (int)($fim->getTimestamp() - $inicio->getTimestamp());
					$duracaoMinutos = $duracaoSegundos > 0 ? (int)ceil($duracaoSegundos / 60) : 0;
					$this->_atendimentoTimerApplyDuracaoMinutos($timer, $duracaoMinutos);
					$this->_atendimentoTimerApplyCanonicalFields($timer, [
						'duration_seconds' => $duracaoSegundos,
					]);
				}
			}
			if (!$this->AtendimentoTimer->save($timer)) {
				$this->log('apiTimer finalizar save: ' . json_encode($timer->getErrors()), 'error');

				return ['ok' => false, 'error' => 'save_failed', 'message' => 'Não foi possível finalizar o timer.'];
			}

			if ($inicio && $fim) {
				try {
					$regHora = $this->Ticketshoras->newEntity([
						'idticket' => $idticket,
						'iduser' => (int)$this->Auth->user('id'),
						'idempresa' => (int)$this->Auth->user('idempresa'),
						'data' => $inicio->format('Y-m-d'),
						'horaini' => $inicio->format('Y-m-d H:i:s'),
						'horafin' => $fim->format('Y-m-d H:i:s'),
					]);
					if ($this->Ticketshoras->save($regHora)) {
						TicketWorklogEventHelper::afterHoraLancada(
							$regHora,
							(int)$this->Auth->user('idempresa'),
							$idticket,
							(int)$this->Auth->user('id'),
							$regHora->horaini,
							$regHora->horafin
						);
					}
				} catch (\Throwable $e) {
					$this->log('Timer JSON: falha ao registrar em Ticketshoras: ' . $e->getMessage(), 'error');
				}
				$this->_timerCriarMovSafe($idticket, $ticket->situacao, C_TicketTimerFinalizado, 'Duração: ' . $duracaoMinutos . ' min. Horas registradas em Horas Cadastradas.');
				$billSec = TicketServiceDeskApiService::billingSecondsFromRaw($duracaoSegundos);
				$this->subtrairHorasContrato($ticket->idcliente, $this->Auth->user('idempresa'), $billSec, $duracaoMinutos, $idticket);
			}

			return [
				'ok' => true,
				'message' => 'Timer finalizado. Horas registradas.',
				'duracaoMinutosFinal' => $duracaoMinutos,
				'durationSecondsFinal' => $duracaoSegundos,
				'status' => 'finished',
				'endedAt' => $agoraStr,
			];
		} catch (\Throwable $e) {
			$this->log($e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
			$msg = $e->getMessage();
			if (
				stripos($msg, 'ux_atendimento_timer_open_ticket_user') !== false ||
				(stripos($msg, 'duplicate key') !== false && stripos($msg, 'atendimento_timer') !== false)
			) {
				return ['ok' => false, 'error' => 'already_running', 'message' => 'Já existe um timer em andamento para este ticket.'];
			}
			if (stripos($msg, 'does not exist') !== false || stripos($msg, 'relation') !== false || stripos($msg, 'undefined table') !== false) {
				return ['ok' => false, 'error' => 'table_missing', 'message' => 'Tabela atendimento_timer não existe ou está inacessível.'];
			}
			if (stripos($msg, 'column') !== false && stripos($msg, 'exist') !== false) {
				return ['ok' => false, 'error' => 'schema', 'message' => 'Tabela atendimento_timer com colunas incorretas.'];
			}

			return ['ok' => false, 'error' => 'exception', 'message' => 'Erro ao executar ação do timer.'];
		}
	}

	protected function _reactBoot(string $screen, $ticketId = null, array $extra = []): array {
		$w = $this->request->getAttribute('webroot');
		$empresa = (int)$this->Auth->user('idempresa');
		$base = [
			'screen' => $screen,
			'ticketId' => $ticketId !== null ? (int)$ticketId : null,
			'serviceDeskRealtimeSocket' => $this->_isServiceDeskRealtimeEnabled(),
			'webroot' => $w,
			'role' => (int)$this->Auth->user('role'),
			'admin' => (int)$this->Auth->user('admin'),
			'userId' => (int)$this->Auth->user('id'),
			'userName' => (string)($this->Auth->user('name') ?? ''),
			/**
			 * Só UI da grid técnica (React): inline + ocultar Transferir quando true.
			 * Não desativa endpoints PATCH; cliente nunca deve herdar true (Servicedesk força false no portal).
			 */
			'inlineAssignment' => false,
			'workflowEnabled' => $this->_ticketWorkflowEnabledForEmpresa($empresa),
			'ticketStatus' => [
				'pendente' => (int)C_TicketSituacaoPendente,
				'emandamento' => (int)C_TicketSituacaoEmandamento,
				'resolvido' => (int)C_TicketSituacaoResolvido,
				'fechado' => (int)C_TicketSituacaoFechado,
			],
			'paths' => [
				'apiIndex' => $w . 'tickets/api-index',
				'apiDashboardOperacional' => $w . 'tickets/api-dashboard-operacional',
				'apiIndexCliente' => $w . 'tickets/api-index-cliente',
				'apiView' => $w . 'tickets/api-view/',
				'apiComments' => $w . 'tickets/api-comments/',
				'apiSaveTicket' => $w . 'tickets/api-save/',
				'apiTimer' => $w . 'tickets/api-timer/',
				'apiAuditValidate' => $w . 'api/audit/validate',
				'apiSetUserAuditPassword' => $w . 'users/api-set-user-audit-password',
				'apiAlterarSituacao' => $w . 'tickets/api-alterar-situacao/',
				'apiTimeEntries' => $w . 'tickets/api-time-entries/',
				'apiAnexoUpload' => $w . 'tickets/api-anexo-upload/',
				'apiAnexoDelete' => $w . 'tickets/api-anexo-delete/',
				'apiTecnicosLista' => $w . 'tickets/api-tecnicos-lista',
				'apiTicketSubjectOptions' => $w . 'tickets/api-subject-options',
				'apiTransferirTicket' => $w . 'tickets/api-transferir-ticket/',
				'apiPatchTicketAssignment' => $w . 'tickets/',
				'apiPatchTicketStatus' => $w . 'tickets/',
				'apiPatchTicketPriority' => $w . 'tickets/',
				'apiPatchTicketSubject' => $w . 'tickets/',
				'apiStartTicket' => $w . 'tickets/api-start-ticket/',
				'apiStartTicketSlug' => $w . 'tickets/start-ticket/',
				'apiQueuesForTicket' => $w . 'queues/api-for-ticket/',
				'apiGetAvailableQueues' => $w . 'queues/get-available-queues/',
				'apiQueuesIndex' => $w . 'queues/api-index',
				'apiQueuesEnsureDefaults' => $w . 'queues/api-ensure-defaults',
				'apiSupportLevels' => $w . 'queues/api-support-levels',
				'apiQueuesSave' => $w . 'queues/api-save',
				'apiAddComentario' => $w . 'ticket-comentarios/api-add/',
				'apiEditComentario' => $w . 'ticket-comentarios/api-edit/',
				'apiDeleteComentario' => $w . 'ticket-comentarios/api-delete/',
				'apiTimeline' => $w . 'tickets/api-timeline/',
				'apiValidateGeolocation' => $w . 'tickets/api-validate-geolocation/',
				'apiTicketSignature' => $w . 'tickets/api-ticket-signature/',
				'apiAddTicketProduct' => $w . 'tickets/api-add-ticket-product/',
				'apiTicketProductSearch' => $w . 'tickets/api-ticket-product-search/',
				'apiAddEvidencePhoto' => $w . 'tickets/api-add-evidence-photo/',
				'apiPdfTicketOs' => $w . 'tickets/api-pdf-ticket-os/',
				'apiPdfLaudo' => $w . 'tickets/api-pdf-laudo/',
				'apiTicketMessages' => $w . 'tickets/api-ticket-messages/',
				'apiRealtimeToken' => $w . 'tickets/api-realtime-token/',
				'apiServicedeskData' => $w . 'tickets/api-servicedesk-data/',
				'apiTicketAssetsAttach' => $w . 'tickets/api-assets-attach/',
				'apiTicketAssetsDetach' => $w . 'tickets/api-assets-detach/',
				'indexTecnico' => Router::url(['action' => 'index']),
				'ticketsOperacional' => Router::url(['controller' => 'Tickets', 'action' => 'operacional']),
				'indexCliente' => Router::url(['action' => 'indexcliente']),
				'addTicket' => Router::url(['action' => 'add']),
				'dashboard' => Router::url(['controller' => 'Users', 'action' => 'dashboard']),
				'viewTicketBase' => $w . 'tickets/view/',
				'editTicketBase' => $w . 'tickets/edit/',
				'cancelarBase' => $w . 'tickets/cancelar/',
				'imprimirBase' => $w . 'tickets/imprimir/',
			],
		];

		return array_replace_recursive($base, $extra);
	}

	/**
	 * SituacaoTicket()/AssuntoTicket() no legado retornam HTML; a API JSON precisa de texto para o React.
	 */
	protected function _ticketSituacaoTexto($situacao) {
		$raw = SituacaoTicket($situacao);
		$t = trim(html_entity_decode(preg_replace('/\s+/u', ' ', strip_tags((string)$raw)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
		return $t !== '' ? $t : (string)$situacao;
	}

	protected function _ticketAssuntoTexto($assunto) {
		if ($this->Tickets && method_exists($this->Tickets, 'resolveTicketAssuntoTextoPublic')) {
			return $this->Tickets->resolveTicketAssuntoTextoPublic($assunto);
		}
		$s = trim((string)$assunto);
		return ($s !== '' && $s !== '0') ? $s : 'Não informado';
	}

	/**
	 * Opções do select "Assunto / categoria" na abertura do ticket.
	 * Prioridade: tabela dinâmica (quando existir) → C_TicketCategoriaClienteQuery → config/ticket_assunto_cliente.php.
	 *
	 * @return array<int|string, string>
	 */
	protected function _ticketAssuntoClienteOptions(): array {
		if ($this->Tickets && method_exists($this->Tickets, 'getTicketAssuntoOptions')) {
			$opts = $this->Tickets->getTicketAssuntoOptions();
			if (is_array($opts)) {
				return $opts;
			}
		}

		return [];
	}

	/** @return array<int,array<string,mixed>> */
	protected function _ticketAssuntoApiOptionsList(): array {
		$out = [];
		foreach ($this->_ticketAssuntoClienteOptions() as $id => $label) {
			$txt = trim((string)$label);
			if ($txt === '' || $txt === '0') {
				continue;
			}
			$key = is_numeric($id) ? (int)$id : (string)$id;
			$out[] = [
				'id' => $key,
				'value' => $key,
				'nome' => $txt,
				'label' => $txt,
			];
		}

		return $out;
	}

	/** Códigos persistidos em tickets.severidade */
	protected function _ticketSeveridadeCodigos(): array {
		return ['baixa', 'media', 'alta', 'urgente'];
	}

	protected function _normalizeTicketSeveridade($value): string {
		$v = is_string($value) ? strtolower(trim($value)) : '';
		if ($v === 'média') {
			$v = 'media';
		}
		if (!in_array($v, $this->_ticketSeveridadeCodigos(), true)) {
			$v = 'media';
		}

		return $v;
	}

	protected function _ticketSeveridadeLabel(?string $code): string {
		$map = [
			'baixa' => 'Baixa',
			'media' => 'Média',
			'alta' => 'Alta',
			'urgente' => 'Urgente',
		];
		$c = $code !== null && $code !== '' ? $this->_normalizeTicketSeveridade($code) : 'media';

		return $map[$c] ?? 'Média';
	}

	/** @return array<string,string> código => rótulo (Service Desk inline). */
	protected function _ticketPrioridadeCatalog(): array {
		return [
			'baixa' => 'Baixo',
			'media' => 'Médio',
			'alta' => 'Alto',
			'critica' => 'Crítico',
		];
	}

	protected function _normalizeTicketPrioridadeCode($value): string {
		$v = is_string($value) ? strtolower(trim($value)) : '';
		// P1–P4 (enterprise) → código semântico do catálogo (rótulos na grid / PATCH)
		if (preg_match('/^p([1-4])$/', $v, $m)) {
			$px = [
				'1' => 'critica',
				'2' => 'alta',
				'3' => 'media',
				'4' => 'baixa',
			];

			return $px[$m[1]] ?? 'media';
		}
		if ($v === 'médio' || $v === 'medio') {
			$v = 'media';
		}
		if ($v === 'baixo') {
			$v = 'baixa';
		}
		if ($v === 'alto') {
			$v = 'alta';
		}
		if ($v === 'crítico' || $v === 'critico') {
			$v = 'critica';
		}
		$allow = array_keys($this->_ticketPrioridadeCatalog());
		if (!in_array($v, $allow, true)) {
			$v = 'media';
		}

		return $v;
	}

	protected function _ticketPrioridadeLabelFromCode(string $code): string {
		$c = $this->_normalizeTicketPrioridadeCode($code);
		$m = $this->_ticketPrioridadeCatalog();

		return $m[$c] ?? 'Médio';
	}

	/**
	 * Modo estrito: aceita apenas labels canônicos e P1..P4.
	 *
	 * @return array{semantic:string,px:string}|null
	 */
	protected function _normalizeTicketPrioridadeFromRequest($raw): ?array {
		$s = is_string($raw) ? trim($raw) : '';
		if ($s === '') {
			return null;
		}
		$low = strtolower($s);
		$labels = [
			'crítico' => ['semantic' => 'critica', 'px' => TicketClassificationService::PRIORIDADE_P1],
			'critico' => ['semantic' => 'critica', 'px' => TicketClassificationService::PRIORIDADE_P1],
			'alto' => ['semantic' => 'alta', 'px' => TicketClassificationService::PRIORIDADE_P2],
			'médio' => ['semantic' => 'media', 'px' => TicketClassificationService::PRIORIDADE_P3],
			'medio' => ['semantic' => 'media', 'px' => TicketClassificationService::PRIORIDADE_P3],
			'baixo' => ['semantic' => 'baixa', 'px' => TicketClassificationService::PRIORIDADE_P4],
		];
		if (isset($labels[$low])) {
			return $labels[$low];
		}
		$px = strtoupper($s);
		$mapPx = [
			TicketClassificationService::PRIORIDADE_P1 => 'critica',
			TicketClassificationService::PRIORIDADE_P2 => 'alta',
			TicketClassificationService::PRIORIDADE_P3 => 'media',
			TicketClassificationService::PRIORIDADE_P4 => 'baixa',
		];
		if (isset($mapPx[$px])) {
			return ['semantic' => $mapPx[$px], 'px' => $px];
		}

		return null;
	}

	/**
	 * Rótulo de status (UI) → tickets.situacao.
	 *
	 * @return int|null
	 */
	protected function _mapServicedeskStatusRequestToSituacaoInt(string $raw): ?int {
		$t = trim(html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
		if ($t === '') {
			return null;
		}
		$tLow = mb_strtolower($t, 'UTF-8');
		$fold = strtr($tLow, [
			'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
			'é' => 'e', 'ê' => 'e',
			'í' => 'i',
			'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
			'ú' => 'u', 'ü' => 'u',
			'ç' => 'c',
		]);
		$fold = preg_replace('/\s+/u', ' ', trim((string)$fold));
		$pend = (int)C_TicketSituacaoPendente;
		$exec = (int)C_TicketSituacaoEmandamento;
		$res = (int)C_TicketSituacaoResolvido;
		$fec = (int)C_TicketSituacaoFechado;
		$allowed = [
			'em execucao' => $exec,
			'pendente' => $pend,
			'resolvido' => $res,
			'fechado' => $fec,
			// Compatibilidade opcional com códigos internos textuais já usados.
			'emandamento' => $exec,
			'em_andamento' => $exec,
		];
		if (isset($allowed[$fold])) {
			return $allowed[$fold];
		}
		// Qualquer outra variação é inválida no modo estrito.
		return null;
	}

	/**
	 * Resposta padronizada + log para status inválido no PATCH inline.
	 */
	protected function _ticketInvalidStatusResponse(int $idticket, $attemptedValue) {
		$data = [
			'ticket_id' => $idticket,
			'user_id' => (int)$this->Auth->user('id'),
			'status' => $attemptedValue,
		];
		$this->log('Status inválido: ' . json_encode($data, JSON_UNESCAPED_UNICODE), 'warning');

		return $this->jsonResponse([
			'ok' => false,
			'error' => 'invalid_situacao',
			'message' => 'Status inválido. Use: Em execução, Pendente, Resolvido ou Fechado.',
			'debug' => [
				'field' => 'status',
				'attemptedValue' => $attemptedValue,
			],
		], 422);
	}

	protected function _ticketForbiddenPatchResponse(): \Cake\Http\Response {
		return $this->jsonResponse([
			'ok' => false,
			'error' => 'forbidden',
			'message' => 'Você não tem permissão para alterar este ticket.',
		], 403);
	}

	protected function _ticketApplyForUpdateLock($query) {
		try {
			if (method_exists($query, 'epilog')) {
				$query->epilog('FOR UPDATE');
			}
		} catch (\Throwable $e) {
		}

		return $query;
	}

	protected function _ticketWorkflowEnabledForEmpresa(int $empresaId): bool {
		if ($empresaId <= 0) {
			return false;
		}
		if (!Configure::read('Workflow.workflowEnabled', false)) {
			return false;
		}
		$empresas = (array)Configure::read('Workflow.enabledEmpresas', []);
		if ($empresas === []) {
			return true;
		}

		return in_array($empresaId, array_map('intval', $empresas), true);
	}

	protected function _ticketWorkflowStateColumnReady(): bool {
		static $ok = null;
		if ($ok !== null) {
			return $ok;
		}
		try {
			$cols = $this->Tickets->getSchema()->columns();
			$ok = in_array('workflow_state_id', $cols, true);
		} catch (\Throwable $e) {
			$ok = false;
		}

		return $ok;
	}

	protected function _ticketWorkflowService(): WorkflowService {
		return new WorkflowService($this->Tickets);
	}

	/**
	 * @return array|null
	 */
	protected function _ticketWorkflowPayloadForRow($reg): array {
		static $service = null;
		static $empresaEnabled = [];
		static $empresaConfigured = [];
		static $stateCache = [];
		static $allowedCache = [];
		$disabledPayload = [
			'enabled' => false,
			'current' => null,
			'allowedTransitions' => [],
		];
		$empresa = (int)$this->Auth->user('idempresa');
		if (!isset($empresaEnabled[$empresa])) {
			$empresaEnabled[$empresa] = $this->_ticketWorkflowEnabledForEmpresa($empresa);
		}
		if (!$empresaEnabled[$empresa] || !$this->_ticketWorkflowStateColumnReady()) {
			return $disabledPayload;
		}
		$currentStateId = (int)($reg->workflow_state_id ?? 0);
		if ($service === null) {
			$service = $this->_ticketWorkflowService();
		}
		if (!isset($empresaConfigured[$empresa])) {
			$empresaConfigured[$empresa] = $service->hasConfiguredTransitionsForEmpresa($empresa);
		}
		if (!$empresaConfigured[$empresa]) {
			return $disabledPayload;
		}
		if ($currentStateId <= 0) {
			return [
				'enabled' => true,
				'current' => null,
				'allowedTransitions' => [],
			];
		}
		$stateKey = $empresa . ':' . $currentStateId;
		if (!array_key_exists($stateKey, $stateCache)) {
			$stateCache[$stateKey] = $service->getStateById($currentStateId);
		}
		$current = $stateCache[$stateKey];
		if ($current === null) {
			return [
				'enabled' => true,
				'current' => null,
				'allowedTransitions' => [],
			];
		}
		if (!array_key_exists($stateKey, $allowedCache)) {
			$allowedCache[$stateKey] = $service->getAllowedTransitions($empresa, $currentStateId);
		}
		$allowedRaw = $allowedCache[$stateKey];
		$allowed = [];
		foreach ($allowedRaw as $row) {
			$id = (int)($row['id'] ?? 0);
			if ($id <= 0) {
				continue;
			}
			$allowed[] = [
				'id' => $id,
				'label' => (string)($row['label'] ?? ''),
				'codigo' => (string)($row['codigo'] ?? ''),
			];
		}

		return [
			'enabled' => true,
			'current' => [
				'id' => (int)$current['id'],
				'label' => (string)$current['nome'],
				'codigo' => (string)$current['codigo'],
			],
			'allowedTransitions' => $allowed,
		];
	}

	protected function _servicedeskTicketRowPayload(int $idticket): ?array {
		$empresa = (int)$this->Auth->user('idempresa');
		if ($empresa <= 0 || $idticket <= 0) {
			return null;
		}
		$contain = ['users', 'Clientes'];
		if ($this->_queuesRelacionalReady()) {
			$contain['Queues'] = [];
		}
		$q = $this->Tickets->find('all', ['contain' => $contain])
			->where(['Tickets.id' => $idticket, 'Tickets.idempresa' => $empresa]);
		$this->Abac->applyToQuery($q, 'Tickets', 'Tickets');
		$reg = $q->first();
		if (empty($reg)) {
			return null;
		}
		if (!$this->_apiTicketViewAllowed($reg)) {
			return null;
		}
		$cols = $this->Tickets->getSchema()->columns();
		$hasRespCol = in_array('idtecnico_responsavel', $cols, true) || in_array('owner_id', $cols, true);
		$tecLabel = '—';
		if ($hasRespCol) {
			$rid = (int)($reg->idtecnico_responsavel ?? 0);
			if ($rid <= 0 && in_array('owner_id', $cols, true)) {
				$rid = (int)($reg->owner_id ?? 0);
			}
			if ($rid > 0) {
				$nm = $this->_batchUserDisplayNames([$rid]);
				$tecLabel = $nm[$rid] ?? ('Usuário #' . $rid);
			} else {
				$tecLabel = 'Não atribuído';
			}
		}
		$wf = $this->_ticketWorkflowSchemaReady();
		$queuesUi = $this->_queuesRelacionalReady();
		$transferEnabled = $this->_ticketTransferApiAllowed();
		$transfSet = $hasRespCol ? $this->_ticketIdsComTransferencia([$idticket]) : [];
		$hasSeveridadeCol = in_array('severidade', $cols, true);

		return $this->_ticketRowApiTecnico($reg, $tecLabel, [
			'workflow' => $wf,
			'queuesUi' => $queuesUi,
			'transferEnabled' => $transferEnabled,
			'transferido' => isset($transfSet[$idticket]),
			'hasSeveridadeCol' => $hasSeveridadeCol,
		]);
	}

	protected function _ticketWorkflowSchemaReady(): bool {
		static $ok = null;
		if ($ok !== null) {
			return $ok;
		}
		try {
			$cols = $this->Tickets->getSchema()->columns();
			$ok = in_array('idtecnico_responsavel', $cols, true)
				&& in_array('fila_suporte', $cols, true)
				&& in_array('nivel_atendimento', $cols, true);
		} catch (\Throwable $e) {
			$ok = false;
		}

		return $ok;
	}

	/**
	 * PostgreSQL pode listar tabelas como public.queues; normaliza para o sufixo.
	 *
	 * @return string[]
	 */
	protected function _schemaTableBaseNames(): array {
		static $cache = null;
		if ($cache !== null) {
			return $cache;
		}
		try {
			$raw = $this->Tickets->getConnection()->getSchemaCollection()->listTables();
		} catch (\Throwable $e) {
			$cache = [];

			return $cache;
		}
		$out = [];
		foreach ((array)$raw as $t) {
			if (!is_string($t) || $t === '') {
				continue;
			}
			$out[] = strpos($t, '.') !== false ? substr($t, strrpos($t, '.') + 1) : $t;
		}
		$cache = $out;

		return $cache;
	}

	protected function _queuesRelacionalReady(): bool {
		static $ok = null;
		if ($ok !== null) {
			return $ok;
		}
		try {
			$tables = $this->_schemaTableBaseNames();
			$ok = in_array('queues', $tables, true)
				&& in_array('queue_id', $this->Tickets->getSchema()->columns(), true);
		} catch (\Throwable $e) {
			$ok = false;
		}

		return $ok;
	}

	/**
	 * Ao entrar em execução, o técnico logado vira responsável (e entra em ticketsusers).
	 */
	protected function _assignTecnicoEmExecucao($ticket, int $idticket, bool $strictTransactional = false): void {
		if ((int)$this->Auth->user('role') !== 0) {
			return;
		}
		$cols = $this->Tickets->getSchema()->columns();
		if (!in_array('idtecnico_responsavel', $cols, true) && !in_array('owner_id', $cols, true)) {
			return;
		}
		$uid = (int)$this->Auth->user('id');
		if ($uid <= 0) {
			return;
		}
		if (in_array('idtecnico_responsavel', $cols, true)) {
			$ticket->idtecnico_responsavel = $uid;
		}
		// owner_id é preenchido em TicketsTable::beforeSave a partir de idtecnico_responsavel
		$emp = (int)$this->Auth->user('idempresa');
		$ja = $this->Ticketsusers->find()->where(['idticket' => $idticket, 'iduser' => $uid])->first();
		if (empty($ja)) {
			$tu = $this->Ticketsusers->newEntity();
			$tu->idticket = $idticket;
			$tu->iduser = $uid;
			$tu->idempresa = $emp;
			$saveOpts = $strictTransactional ? ['atomic' => false] : [];
			if (!$this->Ticketsusers->save($tu, $saveOpts)) {
				if ($strictTransactional) {
					throw new \RuntimeException('ticketsusers:' . json_encode($tu->getErrors(), JSON_UNESCAPED_UNICODE));
				}
			}
		}
	}

	/**
	 * Resolução/fechamento por funcionário sem técnico no registro: grava o logado em idtecnico_responsavel
	 * para o ranking PGM e telas de responsável (owner_id espelhado no beforeSave).
	 */
	protected function _ensureTecnicoResponsavelAoFechamento($ticket): void {
		if ((int)$this->Auth->user('role') !== 0) {
			return;
		}
		$cols = $this->Tickets->getSchema()->columns();
		if (!in_array('idtecnico_responsavel', $cols, true) && !in_array('owner_id', $cols, true)) {
			return;
		}
		$rid = 0;
		if (in_array('idtecnico_responsavel', $cols, true)) {
			$rid = (int)($ticket->idtecnico_responsavel ?? 0);
		}
		if ($rid <= 0 && in_array('owner_id', $cols, true)) {
			$rid = (int)($ticket->owner_id ?? 0);
		}
		if ($rid > 0) {
			return;
		}
		$uid = (int)$this->Auth->user('id');
		if ($uid <= 0) {
			return;
		}
		if (in_array('idtecnico_responsavel', $cols, true)) {
			$ticket->idtecnico_responsavel = $uid;
		}
	}

	/**
	 * Save com `fields`: ao persistir idtecnico_responsavel, incluir owner_id (espelho sincronizado no beforeSave).
	 */
	protected function _ticketFieldsComResponsavel(array $fields): array {
		return $this->Tickets->fieldsComEspelhoResponsavel($fields);
	}

	/** Só colunas que existem no schema (evita save falhar no PG com campo inexistente / typo). */
	protected function _ticketIntersectSchemaFields(array $fields): array {
		return array_values(array_intersect($fields, $this->Tickets->getSchema()->columns()));
	}

	/**
	 * Nível do ticket alinhado à fila; null se FK inválida (evita UPDATE quebrando no PostgreSQL).
	 */
	protected function _ticketSupportLevelIdFromQueue($queueEntity): ?int {
		if (!$this->_supportLevelsRoutingReady() || empty($queueEntity)) {
			return null;
		}
		$sid = isset($queueEntity->support_level_id) ? (int)$queueEntity->support_level_id : 0;
		if ($sid <= 0) {
			return null;
		}
		try {
			$ok = $this->SupportLevels->find()->select(['id'])->where(['id' => $sid])->first();

			return $ok !== null ? $sid : null;
		} catch (\Throwable $e) {
			return null;
		}
	}

	/**
	 * UPDATE direto (transferência): contorna falhas de save do ORM com entity carregada / dirty no PG.
	 * Espelha owner_id quando idtecnico_responsavel está no conjunto (como TicketsTable::beforeSave).
	 * No PostgreSQL, NULL em updateAll precisa de expressão explícita; rowCount pode ser 0 se nada mudou.
	 *
	 * @return array{0:int,1:array<string,mixed>} [linhas afetadas, valores para conferência]
	 */
	protected function _ticketTransferApplyUpdate(int $idticket, int $idempresa, array $set, ?string $modifiedSql = null): array {
		$cols = $this->Tickets->getSchema()->columns();
		$set = array_intersect_key($set, array_flip($cols));
		if (array_key_exists('idtecnico_responsavel', $set) && in_array('owner_id', $cols, true)) {
			$tid = $set['idtecnico_responsavel'];
			$set['owner_id'] = ($tid !== null && $tid !== '' && (int)$tid > 0) ? (int)$tid : null;
		}
		if (in_array('modified', $cols, true)) {
			$set['modified'] = $modifiedSql ?? date('Y-m-d H:i:s');
		}
		$plain = $set;
		$sqlSet = [];
		foreach ($set as $k => $v) {
			$sqlSet[$k] = $v === null ? new QueryExpression('NULL') : $v;
		}
		$n = (int)$this->Tickets->updateAll($sqlSet, ['id' => $idticket, 'idempresa' => $idempresa]);
		if ($n === 0 && !$this->_ticketTransferRowMatches($idticket, $idempresa, $plain)) {
			$row = $this->Tickets->find()->select(['id', 'idempresa'])->where(['id' => $idticket])->first();
			if ($row && (int)$row->idempresa === $idempresa) {
				$n = (int)$this->Tickets->updateAll($sqlSet, ['id' => $idticket]);
			}
		}

		return [$n, $plain];
	}

	/** Confere se o ticket já está com os valores pretendidos após o UPDATE. */
	protected function _ticketTransferRowMatches(int $idticket, int $idempresa, array $set): bool {
		if ($set === []) {
			return true;
		}
		$fields = array_keys($set);
		$row = $this->Tickets->find()->select($fields)->where(['id' => $idticket, 'idempresa' => $idempresa])->first();
		if (!$row) {
			return false;
		}
		foreach ($set as $col => $val) {
			if ($col === 'modified') {
				continue;
			}
			$cur = $row->get($col);
			if (in_array($col, ['idtecnico_responsavel', 'owner_id'], true)) {
				$ci = ($cur === null || $cur === '') ? 0 : (int)$cur;
				$vi = ($val === null || $val === '') ? 0 : (int)$val;
				if ($ci !== $vi) {
					return false;
				}
			} elseif ($col === 'support_level_id') {
				$ci = ($cur === null || $cur === '') ? null : (int)$cur;
				$vi = ($val === null || $val === '') ? null : (int)$val;
				if ($ci !== $vi) {
					return false;
				}
			} elseif ((string)$cur !== (string)$val) {
				return false;
			}
		}

		return true;
	}

	protected function _ticketTransferAssertUpdated(int $idticket, int $idempresa, array $set, int $rows, string $ctx): void {
		if ($rows === 1 || $this->_ticketTransferRowMatches($idticket, $idempresa, $set)) {
			return;
		}
		$this->log(
			'apiTransferirTicket ' . $ctx . ' rows=' . $rows . ' id=' . $idticket . ' set=' . json_encode($set, JSON_UNESCAPED_UNICODE),
			'error'
		);
		throw new \RuntimeException('update_ticket');
	}

	/** Limita observação ao tamanho da coluna (evita falha no save de ticketsmovs). */
	protected function _ticketsmovsObservacaoLimitada(string $texto): string {
		$texto = (string)$texto;
		$len = null;
		try {
			$sch = $this->Ticketsmovs->getSchema();
			if (in_array('observacao', $sch->columns(), true)) {
				$col = $sch->getColumn('observacao');
				if (is_array($col) && !empty($col['length'])) {
					$len = (int)$col['length'];
				} elseif (is_object($col) && method_exists($col, 'getLimit')) {
					$l = $col->getLimit();
					$len = $l !== null ? (int)$l : null;
				}
			}
		} catch (\Throwable $e) {
		}
		if ($len !== null && $len > 0 && function_exists('mb_strlen') && mb_strlen($texto) > $len) {
			return mb_substr($texto, 0, $len);
		}
		if (function_exists('mb_strlen') && mb_strlen($texto) > 8000) {
			return mb_substr($texto, 0, 8000);
		}

		return $texto;
	}

	protected function _ticketTransferApiAllowed(): bool {
		$cols = $this->Tickets->getSchema()->columns();
		$hasResp = in_array('idtecnico_responsavel', $cols, true);

		return $hasResp && ($this->_ticketWorkflowSchemaReady() || $this->_queuesRelacionalReady());
	}

	/** Tabela support_levels + colunas support_level_id em queues/tickets. */
	protected function _supportLevelsRoutingReady(): bool {
		static $ok = null;
		if ($ok !== null) {
			return $ok;
		}
		try {
			$tables = $this->_schemaTableBaseNames();
			$ok = in_array('support_levels', $tables, true)
				&& in_array('support_level_id', $this->Queues->getSchema()->columns(), true);
		} catch (\Throwable $e) {
			$ok = false;
		}

		return $ok;
	}

	protected function _supportLevelSortById(?int $levelId): int {
		if (!$this->_supportLevelsRoutingReady() || $levelId === null || $levelId <= 0) {
			return 0;
		}
		static $cache = [];
		if (isset($cache[$levelId])) {
			return $cache[$levelId];
		}
		$sl = $this->SupportLevels->find()->select(['sort_order'])->where(['id' => $levelId])->first();
		$cache[$levelId] = $sl ? (int)$sl->sort_order : 0;

		return $cache[$levelId];
	}

	protected function _queueLevelSortOrder(?int $queueId): int {
		if (!$this->_queuesRelacionalReady() || $queueId === null || $queueId <= 0) {
			return 0;
		}
		try {
			$contain = $this->_supportLevelsRoutingReady() ? ['SupportLevels'] : [];
			$q = $this->Queues->get($queueId, ['contain' => $contain]);
		} catch (\Throwable $e) {
			return 0;
		}
		if (!empty($q->support_level)) {
			return (int)$q->support_level->sort_order;
		}
		if ($this->_supportLevelsRoutingReady() && !empty($q->support_level_id)) {
			return $this->_supportLevelSortById((int)$q->support_level_id);
		}

		return (int)($q->sort_order ?? 0);
	}

	protected function _ticketQueueLevelSort($reg): int {
		if ($this->_supportLevelsRoutingReady() && isset($reg->support_level_id) && (int)$reg->support_level_id > 0) {
			$s = $this->_supportLevelSortById((int)$reg->support_level_id);
			if ($s > 0) {
				return $s;
			}
		}
		if (!empty($reg->queue_id)) {
			return $this->_queueLevelSortOrder((int)$reg->queue_id);
		}
		if ($this->_ticketWorkflowSchemaReady()) {
			return (int)($reg->nivel_atendimento ?? 1);
		}

		return 0;
	}

	protected function _userEffectiveLevelSortForQueue(int $userId, int $queueId): int {
		$uOrd = 0;
		try {
			$u = $this->Users->get($userId);
			if ($this->_supportLevelsRoutingReady() && !empty($u->support_level_id)) {
				$uOrd = $this->_supportLevelSortById((int)$u->support_level_id);
			}
		} catch (\Throwable $e) {
			$uOrd = 0;
		}
		$qu = $this->QueuesUsers->find()->where(['user_id' => $userId, 'queue_id' => $queueId])->first();
		if (!empty($qu) && !empty($qu->support_level_id) && $this->_supportLevelsRoutingReady()) {
			$qOrd = $this->_supportLevelSortById((int)$qu->support_level_id);

			return max($uOrd, $qOrd);
		}

		return $uOrd;
	}

	/** Técnico com nível efetivo >= exigência da fila (N3 atende N1–N3). */
	protected function _userCanWorkQueue(int $userId, int $queueId): bool {
		$need = $this->_queueLevelSortOrder($queueId);
		if ($need <= 0) {
			return true;
		}
		$eff = $this->_userEffectiveLevelSortForQueue($userId, $queueId);
		if ($eff <= 0) {
			return true;
		}

		return $eff >= $need;
	}

	protected function _userMayAssumeTicketTechnically($ticket): bool {
		$uid = (int)$this->Auth->user('id');
		$qid = (int)($ticket->queue_id ?? 0);
		if (!$this->_queuesRelacionalReady() || $qid <= 0) {
			return true;
		}
		$link = $this->QueuesUsers->find()->where(['user_id' => $uid, 'queue_id' => $qid])->first();
		if (empty($link)) {
			return false;
		}
		if (!$this->_supportLevelsRoutingReady()) {
			return true;
		}

		return $this->_userCanWorkQueue($uid, $qid);
	}

	protected function _supportLevelName(?int $levelId): string {
		if (!$this->_supportLevelsRoutingReady() || $levelId === null || $levelId <= 0) {
			return '';
		}
		static $cache = [];
		if (array_key_exists($levelId, $cache)) {
			return $cache[$levelId];
		}
		try {
			$sl = $this->SupportLevels->get($levelId);
			$cache[$levelId] = (string)$sl->name;
		} catch (\Throwable $e) {
			$cache[$levelId] = '';
		}

		return $cache[$levelId];
	}

	/** Rótulo de nível para histórico (support_level_id, fila ou legado nivel_atendimento). */
	protected function _ticketSupportLevelLabelForHistory($ticket): string {
		if ($this->_supportLevelsRoutingReady() && !empty($ticket->support_level_id) && (int)$ticket->support_level_id > 0) {
			$n = $this->_supportLevelName((int)$ticket->support_level_id);
			if ($n !== '') {
				return $n;
			}
		}
		if ($this->_queuesRelacionalReady() && !empty($ticket->queue_id)) {
			try {
				$q = $this->Queues->get((int)$ticket->queue_id, ['contain' => ['SupportLevels']]);
				if (!empty($q->support_level)) {
					return (string)$q->support_level->name;
				}
				if (!empty($q->support_level_id) && $this->_supportLevelsRoutingReady()) {
					$n = $this->_supportLevelName((int)$q->support_level_id);
					if ($n !== '') {
						return $n;
					}
				}
			} catch (\Throwable $e) {
			}
		}
		if ($this->_ticketWorkflowSchemaReady()) {
			return 'N' . (int)($ticket->nivel_atendimento ?? 1);
		}

		return '—';
	}

	/**
	 * Prioridade P1–P4, política de SLA e registro em ticket_histories (se migrations enterprise estiverem aplicadas).
	 */
	protected function _applyEnterpriseTicketOnCreate($ticket): void {
		if ($ticket === null || empty($ticket->id)) {
			return;
		}
		$cols = $this->Tickets->getSchema()->columns();
		if (!in_array('prioridade', $cols, true)) {
			return;
		}
		try {
			if (in_array('origem_ticket', $cols, true) && ($ticket->get('origem_ticket') === null || $ticket->get('origem_ticket') === '')) {
				$ticket->set('origem_ticket', (int)$this->Auth->user('role') === (int)C_RoleCliente ? 'portal' : 'manual');
			}
			$sev = in_array('severidade', $cols, true) ? $ticket->get('severidade') : null;
			$sla = new SlaService($this->Tickets);
			$fields = $sla->bootstrapNewTicket($ticket, (int)$this->Auth->user('idempresa'), is_string($sev) ? $sev : null);
			if (in_array('origem_ticket', $cols, true) && $ticket->get('origem_ticket')) {
				$fields[] = 'origem_ticket';
			}
			$fields = array_values(array_unique(array_filter($fields)));
			if (!empty($fields)) {
				$fields = $this->Tickets->fieldsComEspelhoResponsavel($fields);
				$this->Tickets->save($ticket, ['fields' => $fields]);
			}
			$snap = [
				'prioridade' => $ticket->get('prioridade'),
				'sla_status' => $ticket->get('sla_status'),
				'tipo_ticket' => $ticket->get('tipo_ticket'),
			];
			try {
				$hist = TableRegistry::get('TicketHistories');
				TicketHistoryLogger::log(
					$hist,
					(int)$ticket->id,
					(int)$this->Auth->user('id'),
					'criacao',
					null,
					json_encode($snap, JSON_UNESCAPED_UNICODE),
					'Ticket criado (classificação/SLA)',
					'usuario'
				);
			} catch (\Throwable $e) {
			}
		} catch (\Throwable $e) {
			$this->log('_applyEnterpriseTicketOnCreate: ' . $e->getMessage(), 'warning');
		}
	}

	/**
	 * Associa o ticket recém-criado à primeira fila da empresa (preferindo codigo n1).
	 */
	protected function _syncTicketQueueAfterCreate(int $idticket): void {
		if (!$this->_queuesRelacionalReady()) {
			return;
		}
		try {
			$t = $this->Tickets->get($idticket);
		} catch (\Throwable $e) {
			return;
		}
		$emp = (int)$t->idempresa;
		$q = null;
		$qid = (int)($t->queue_id ?? 0);
		if ($qid > 0) {
			$q = $this->Queues->find()->where(['id' => $qid, 'idempresa' => $emp])->first();
		}
		if (empty($q)) {
			$q = $this->Queues->find()->where(['idempresa' => $emp, 'codigo' => 'n1'])->first();
			if (empty($q)) {
				$q = $this->Queues->find()->where(['idempresa' => $emp])->order(['sort_order' => 'ASC', 'id' => 'ASC'])->first();
			}
			if (empty($q)) {
				return;
			}
			$t->queue_id = (int)$q->id;
		}
		$fields = ['queue_id'];
		if ($this->_supportLevelsRoutingReady() && in_array('support_level_id', $this->Tickets->getSchema()->columns(), true) && !empty($q->support_level_id)) {
			$t->support_level_id = (int)$q->support_level_id;
			$fields[] = 'support_level_id';
		}
		if ($this->_ticketWorkflowSchemaReady() && $q->codigo !== null && $q->codigo !== '') {
			$cat = $this->_filaSuporteCatalog();
			$cd = (string)$q->codigo;
			if (isset($cat[$cd])) {
				$t->fila_suporte = $cd;
				$t->nivel_atendimento = $cat[$cd]['nivel'];
				$fields[] = 'fila_suporte';
				$fields[] = 'nivel_atendimento';
			}
		}
		$this->Tickets->save($t, ['fields' => $fields]);
	}

	/**
	 * Catálogo de filas (código → rótulo e nível numérico para filtros).
	 *
	 * @return array<string, array{label: string, nivel: int}>
	 */
	protected function _filaSuporteCatalog(): array {
		return [
			'n1' => ['label' => 'Fila N1 — Suporte inicial / triagem', 'nivel' => 1],
			'n2' => ['label' => 'Fila N2 — Suporte avançado / field service', 'nivel' => 2],
			'n3' => ['label' => 'Fila N3 — Infraestrutura / especializado', 'nivel' => 3],
			'noc' => ['label' => 'Fila NOC — Monitoramento', 'nivel' => 4],
			'servico' => ['label' => 'Fila requisições de serviço', 'nivel' => 5],
		];
	}

	protected function _filaLabelFromCode(?string $code): string {
		$c = $code !== null && $code !== '' ? $code : 'n1';
		$cat = $this->_filaSuporteCatalog();

		return $cat[$c]['label'] ?? $c;
	}

	/**
	 * @param int[] $userIds
	 * @return array<int, string>
	 */
	protected function _batchUserDisplayNames(array $userIds): array {
		$userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
		if (empty($userIds)) {
			return [];
		}
		$out = [];
		foreach (
			$this->Users->find()
				->select(['id', 'name', 'username'])
				->where(['id IN' => $userIds])
				->all() as $usr
		) {
			$nm = trim((string)($usr->name ?? ''));
			if ($nm === '') {
				$nm = trim((string)($usr->username ?? ''));
			}
			if ($nm === '') {
				$nm = 'Usuário #' . (int)$usr->id;
			}
			$out[(int)$usr->id] = $nm;
		}

		return $out;
	}

	/**
	 * @param int[] $ticketIds
	 * @return array<int, true> ids que já tiveram transferência registrada
	 */
	protected function _ticketIdsComTransferencia(array $ticketIds): array {
		$ticketIds = array_values(array_unique(array_filter(array_map('intval', $ticketIds))));
		if (empty($ticketIds)) {
			return [];
		}
		$rows = $this->Ticketsmovs->find()
			->select(['idticket'])
			->where(['idticket IN' => $ticketIds, 'sitnova' => C_TicketMovTransferencia])
			->group(['idticket'])
			->all();
		$set = [];
		foreach ($rows as $r) {
			$set[(int)$r->idticket] = true;
		}

		return $set;
	}

	/**
	 * Aplica filtros GET opcionais na listagem técnica (fila, nível, responsável, transferidos).
	 *
	 * @param \Cake\ORM\Query $query
	 * @return \Cake\ORM\Query
	 */
	protected function _applyApiIndexWorkflowFilters($query) {
		if (!$this->_ticketWorkflowSchemaReady()) {
			return $query;
		}
		$f = $this->request->getQuery('fila_suporte');
		if ($f !== null && $f !== '') {
			$cat = $this->_filaSuporteCatalog();
			if (isset($cat[(string)$f])) {
				$query->where(['Tickets.fila_suporte' => (string)$f]);
			}
		}
		$n = $this->request->getQuery('nivel_atendimento');
		if ($n !== null && $n !== '' && ctype_digit((string)$n)) {
			$query->where(['Tickets.nivel_atendimento' => (int)$n]);
		}
		if ($this->request->getQuery('sem_responsavel') === '1') {
			$query->where([
				'OR' => [
					['Tickets.idtecnico_responsavel IS' => null],
					['Tickets.idtecnico_responsavel' => 0],
				],
			]);
		}
		$rid = $this->request->getQuery('idtecnico_responsavel');
		if ($rid !== null && $rid !== '' && ctype_digit((string)$rid)) {
			$query->where(['Tickets.idtecnico_responsavel' => (int)$rid]);
		}
		if ($this->request->getQuery('transferidos') === '1') {
			$subIds = $this->Ticketsmovs->find()
				->select(['idticket'])
				->where(['sitnova' => C_TicketMovTransferencia])
				->group(['idticket'])
				->enableHydration(false)
				->toArray();
			$ids = array_values(array_unique(array_filter(array_map('intval', array_column($subIds, 'idticket')))));
			if (empty($ids)) {
				$query->where(['Tickets.id' => -1]);
			} else {
				$query->where(['Tickets.id IN' => $ids]);
			}
		}
		$qid = $this->request->getQuery('queue_id');
		if ($qid !== null && $qid !== '' && ctype_digit((string)$qid) && in_array('queue_id', $this->Tickets->getSchema()->columns(), true)) {
			$query->where(['Tickets.queue_id' => (int)$qid]);
		}

		return $query;
	}

	/** Nome de exibição de quem abriu o ticket (idautor). */
	protected function _ticketAutorNome($reg): string {
		$name = '';
		if (!empty($reg->users)) {
			$u = $reg->users;
			if (is_object($u)) {
				$name = (string)($u->name ?? $u->username ?? '');
			} elseif (is_array($u)) {
				$name = (string)($u['name'] ?? $u['username'] ?? '');
			}
		}
		if ($name === '' && !empty($reg->user)) {
			$u = $reg->user;
			if (is_object($u)) {
				$name = (string)($u->name ?? $u->username ?? '');
			}
		}
		if ($name === '' && !empty($reg->idautor)) {
			$u = $this->Users->findById($reg->idautor)->select(['name', 'username'])->first();
			if ($u) {
				$name = trim((string)($u->name ?? ''));
				if ($name === '') {
					$name = trim((string)($u->username ?? ''));
				}
			}
		}

		return $name;
	}

	/**
	 * Nome para "Responsável solicitante" na API/React: contato do cliente (idsolicitante),
	 * texto livre (nomesolicitante), ou quem abriu o ticket (idautor).
	 *
	 * @param \Cake\Datasource\EntityInterface|object $ticket
	 * @param \Cake\Datasource\EntityInterface|object|null $solicitanteUser resultado de find por idsolicitante
	 */
	protected function _apiResponsavelSolicitanteDisplay($ticket, $solicitanteUser = null): string {
		if ($solicitanteUser) {
			$n = trim((string)($solicitanteUser->name ?? ''));
			if ($n !== '') {
				return $n;
			}
		}
		$livre = isset($ticket->nomesolicitante) ? trim((string)$ticket->nomesolicitante) : '';
		if ($livre !== '') {
			return $livre;
		}
		$autor = trim($this->_ticketAutorNome($ticket));

		return $autor !== '' ? $autor : '—';
	}

	/**
	 * Nomes dos técnicos vinculados ao ticket (tabela ticketsusers), separados por vírgula.
	 * Não depende de contain nem de idempresa em ticketsusers (legado pode ter NULL).
	 * Restringe ao escopo ABAC (empresa/cliente) nos ids solicitados.
	 *
	 * @param int[] $ids ids de ticket
	 * @return array<int,string> idticket => "Nome A, Nome B"
	 */
	protected function _ticketTecnicosLabelsByTicketIds(array $ids): array {
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		if (empty($ids)) {
			return [];
		}

		$allowedIds = [];
		$qAllow = $this->Tickets->find()
			->select(['id'])
			->where(['id IN' => $ids]);
		$this->Abac->applyToQuery($qAllow, 'Tickets', 'Tickets');
		foreach ($qAllow->all() as $t) {
			$allowedIds[] = (int)$t->id;
		}
		if (empty($allowedIds)) {
			return [];
		}

		$rows = $this->Ticketsusers->find('all')
			->where(['idticket IN' => $allowedIds])
			->toArray();

		$userIds = [];
		foreach ($rows as $r) {
			$uid = (int)$r->iduser;
			if ($uid > 0) {
				$userIds[$uid] = true;
			}
		}

		$usersMap = [];
		if (!empty($userIds)) {
			foreach (
				$this->Users->find()
					->select(['id', 'name', 'username'])
					->where(['id IN' => array_keys($userIds)])
					->all() as $usr
			) {
				$nm = trim((string)($usr->name ?? ''));
				if ($nm === '') {
					$nm = trim((string)($usr->username ?? ''));
				}
				if ($nm === '') {
					$nm = 'Usuário #' . (int)$usr->id;
				}
				$usersMap[(int)$usr->id] = $nm;
			}
		}

		$byTicket = [];
		foreach ($rows as $r) {
			$tid = (int)$r->idticket;
			$uid = (int)$r->iduser;
			if ($uid <= 0 || empty($usersMap[$uid])) {
				continue;
			}
			if (!isset($byTicket[$tid])) {
				$byTicket[$tid] = [];
			}
			$byTicket[$tid][$uid] = $usersMap[$uid];
		}

		$out = [];
		foreach ($byTicket as $tid => $map) {
			$out[$tid] = implode(', ', array_values($map));
		}

		return $out;
	}

	protected function _ticketRowApiTecnico($reg, string $tecnicosLabel = '', array $ctx = []): array {
		$c = $reg->cliente ?? null;
		$nomeCliente = '';
		if ($c) {
			$nomeCliente = (int)$c->tipo === (int)C_ClientesTipoFisica ? (string)$c->nome : (string)$c->razaosocial;
		}
		$id = (int)$reg->id;
		$sit = (int)$reg->situacao;
		$wf = !empty($ctx['workflow']);
		$queuesUi = !empty($ctx['queuesUi']);
		$transferOk = !empty($ctx['transferEnabled']);
		$qEnt = $reg->queue ?? $reg->queues ?? null;
		$filaCode = $wf ? (string)($reg->fila_suporte ?? 'n1') : '';
		$nivel = $wf ? (int)($reg->nivel_atendimento ?? 1) : null;
		$ordemId = null;
		$ticketId = (int)($reg->id ?? 0);
		if ($ticketId > 0) {
			if (isset($ctx['ordemByTicket']) && is_array($ctx['ordemByTicket']) && array_key_exists($ticketId, $ctx['ordemByTicket'])) {
				$oid = (int)$ctx['ordemByTicket'][$ticketId];
				$ordemId = $oid > 0 ? $oid : null;
			} elseif (method_exists($this, '_ticketOrdemId')) {
				$ordemId = $this->_ticketOrdemId($ticketId);
			}
		}

		$acoes = [];
		if ($sit !== (int)C_TicketSituacaoResolvido && $sit !== (int)C_TicketSituacaoFechado) {
			if ($sit !== (int)C_TicketSituacaoPendente) {
				$acoes[] = [
					'key' => 'pendente',
					'label' => 'Aguardando técnico',
					'behavior' => 'reactStatus',
					'situacaoDestino' => (int)C_TicketSituacaoPendente,
					'url' => $this->_ticketUrl(['action' => 'alterarsituacao', $id, (string)C_TicketSituacaoPendente]),
				];
			}
			if ($sit !== (int)C_TicketSituacaoEmandamento) {
				$acoes[] = [
					'key' => 'emandamento',
					'label' => 'Em execução',
					'behavior' => 'reactStatus',
					'situacaoDestino' => (int)C_TicketSituacaoEmandamento,
					'url' => $this->_ticketUrl(['action' => 'alterarsituacao', $id, (string)C_TicketSituacaoEmandamento]),
				];
			}
			if ($sit === (int)C_TicketSituacaoPendente) {
				$acoes[] = [
					'key' => 'iniciar',
					'label' => 'Iniciar atendimento',
					'behavior' => 'reactStart',
					'url' => $this->_ticketUrl(['action' => 'edit', $id]),
				];
			}
			if ($sit !== (int)C_TicketSituacaoResolvido) {
				$acoes[] = [
					'key' => 'resolvido',
					'label' => 'Resolvido',
					'behavior' => 'reactStatus',
					'situacaoDestino' => (int)C_TicketSituacaoResolvido,
					'url' => $this->_ticketUrl(['action' => 'alterarsituacao', $id, (string)C_TicketSituacaoResolvido]),
				];
			}
			if ($transferOk) {
				$acoes[] = [
					'key' => 'transferir',
					'label' => 'Transferir',
					'behavior' => 'reactTransfer',
					'url' => $this->_ticketUrl(['action' => 'edit', $id]),
				];
			}
			if ($sit !== (int)C_TicketSituacaoFechado) {
				$acoes[] = ['key' => 'cancelar', 'label' => 'Cancelar', 'url' => $this->_ticketUrl(['action' => 'cancelar', $id])];
			}
		}
		if ($ticketId > 0 && $ordemId === null) {
			$gerarOsUrl = $this->_ticketGerarOsUrl($ticketId);
			if ($gerarOsUrl !== '') {
				$acoes[] = [
					'key' => 'gerar_os',
					'label' => 'Gerar Ordem de Serviço',
					'url' => $gerarOsUrl,
					'target' => '_blank',
				];
			}
		} elseif ($ticketId > 0) {
			$acoes[] = [
				'key' => 'ver_os',
				'label' => 'Ver OS #' . $ordemId,
				'url' => $this->_ticketUrl(['controller' => 'ordensservico', 'action' => 'edit', $ordemId]),
				'target' => '_blank',
			];
		}
		$acoes[] = ['key' => 'imprimir', 'label' => 'Imprimir', 'url' => $this->_ticketUrl(['action' => 'imprimir', $id, '?' => ['autoprint' => 1]]), 'target' => '_blank'];

		$tecCol = $tecnicosLabel !== '' ? $tecnicosLabel : '—';
		if (!$wf && ($tecCol === '—' || $tecCol === '')) {
			$tecCol = '—';
		}

		$assuntoNome = trim((string)$this->_ticketAssuntoTexto($reg->assunto));
		if ($assuntoNome === '' || $assuntoNome === '0') {
			$assuntoNome = 'Não informado';
		}
		$row = [
			'id' => $id,
			'autor' => $this->_ticketAutorNome($reg),
			'created' => $reg->created ? $reg->created->format('d/m/Y') : '',
			'assunto_nome' => $assuntoNome,
			'assunto_id' => is_numeric($reg->assunto) ? (int)$reg->assunto : null,
			'assuntoCode' => $reg->assunto,
			'situacao' => $sit,
			'situacaoLabel' => $this->_ticketSituacaoTexto($reg->situacao),
			'cliente' => $nomeCliente,
			'tecnicos' => $tecCol,
			'solicitacaoPreview' => mb_strimwidth(strip_tags((string)($reg->solicitacao ?? '')), 0, 220, '…', 'UTF-8'),
			'urls' => [
				'edit' => $this->_ticketUrl(['action' => 'edit', $id]),
			],
			'acoes' => $acoes,
		];
		$row['workflow'] = $this->_ticketWorkflowPayloadForRow($reg);
		$canonicalResp = (int)($reg->idtecnico_responsavel ?? 0);
		if ($canonicalResp <= 0 && isset($reg->owner_id) && $reg->owner_id !== null && $reg->owner_id !== '') {
			$canonicalResp = (int)$reg->owner_id;
		}
		$row['idtecnico_responsavel'] = $canonicalResp > 0 ? $canonicalResp : null;
		$row['owner_id'] = $canonicalResp > 0 ? $canonicalResp : null;
		if ($wf || $queuesUi) {
			if ($qEnt && !empty($qEnt->name)) {
				$row['filaLabel'] = (string)$qEnt->name;
				$row['filaQueueId'] = (int)$qEnt->id;
				$row['filaSuporte'] = $qEnt->codigo !== null && $qEnt->codigo !== '' ? (string)$qEnt->codigo : $filaCode;
			} else {
				$row['filaSuporte'] = $filaCode ?: 'n1';
				$row['filaLabel'] = $wf ? $this->_filaLabelFromCode($filaCode) : '—';
			}
			if ($wf) {
				$row['nivelAtendimento'] = $nivel;
			} elseif ($queuesUi && $qEnt && $qEnt->codigo) {
				$cat = $this->_filaSuporteCatalog();
				$cd = (string)$qEnt->codigo;
				$row['nivelAtendimento'] = isset($cat[$cd]) ? $cat[$cd]['nivel'] : null;
			}
			$row['transferido'] = !empty($ctx['transferido']);
		}
		if ($this->_supportLevelsRoutingReady()) {
			$tl = '';
			if (isset($reg->support_level) && $reg->support_level) {
				$tl = (string)$reg->support_level->name;
			} elseif (isset($reg->support_level_id) && (int)$reg->support_level_id > 0) {
				$tl = $this->_supportLevelName((int)$reg->support_level_id);
			} elseif ($qEnt && !empty($qEnt->support_level)) {
				$tl = (string)$qEnt->support_level->name;
			} elseif ($qEnt && isset($qEnt->support_level_id) && (int)$qEnt->support_level_id > 0) {
				$tl = $this->_supportLevelName((int)$qEnt->support_level_id);
			}
			$row['supportLevelId'] = isset($reg->support_level_id) && (int)$reg->support_level_id > 0 ? (int)$reg->support_level_id : null;
			$row['supportLevelLabel'] = $tl !== '' ? $tl : null;
			$row['supportLevelSort'] = $this->_ticketQueueLevelSort($reg);
		}
		if (!empty($ctx['hasSeveridadeCol'])) {
			$row['severidade'] = $this->_ticketSeveridadeLabel((string)($reg->severidade ?? 'media'));
			$row['severidadeCode'] = $this->_normalizeTicketSeveridade($reg->severidade ?? 'media');
		}
		try {
			$colsAll = $this->Tickets->getSchema()->columns();
			if (in_array('prioridade', $colsAll, true)) {
				$pc = $this->_normalizeTicketPrioridadeCode((string)($reg->prioridade ?? ''));
				$row['prioridadeCode'] = $pc;
				$row['prioridadeLabel'] = $this->_ticketPrioridadeLabelFromCode($pc);
			}
			if (in_array('started_at', $colsAll, true) && in_array('total_seconds', $colsAll, true)) {
				$totalSeconds = (int)($reg->total_seconds ?? 0);
				if ($totalSeconds <= 0) {
					try {
						$totalSeconds = $this->_apiSegundosRegistradosTicket((int)$id);
					} catch (\Throwable $e) {
						$totalSeconds = 0;
					}
				}
				$elapsedSeconds = TicketAttendimentoTimerService::elapsedSecondsForDisplay(
					$this->Tickets,
					$reg,
					time()
				);
				if ($elapsedSeconds < $totalSeconds) {
					$elapsedSeconds = $totalSeconds;
				}
				$row['attendimentoTimer'] = [
					'started_at' => $this->_ticketTimerIsoForJson($reg->started_at ?? null),
					'paused_at' => $this->_ticketTimerIsoForJson($reg->paused_at ?? null),
					'finished_at' => $this->_ticketTimerIsoForJson($reg->finished_at ?? null),
					'total_seconds' => $totalSeconds,
					'elapsed_seconds' => $elapsedSeconds,
				];
			}
		} catch (\Throwable $e) {
		}

		return $row;
	}

	protected function _ticketGerarOsUrl(int $ticketId): string {
		if ($ticketId <= 0) {
			return '';
		}
		$hasAddFromTicket = method_exists(OrdensservicoController::class, 'addFromTicket');
		if ($hasAddFromTicket) {
			try {
				return $this->_ticketUrl(['_name' => 'ticketsGerarOs', 'id' => $ticketId]);
			} catch (\Throwable $e) {
				try {
					return $this->_ticketUrl(['controller' => 'Ordensservico', 'action' => 'addFromTicket', $ticketId]);
				} catch (\Throwable $e2) {
					try {
						return $this->_ticketUrl(['controller' => 'Ordensservico', 'action' => 'ticketordem', $ticketId]);
					} catch (\Throwable $e3) {
						return '';
					}
				}
			}
		}

		try {
			return $this->_ticketUrl(['controller' => 'Ordensservico', 'action' => 'ticketordem', $ticketId]);
		} catch (\Throwable $e) {
			return '';
		}
	}

	protected function _ticketOrdemId(int $ticketId): ?int {
		static $cache = [];
		if (array_key_exists($ticketId, $cache)) {
			return $cache[$ticketId];
		}
		try {
			$ordem = $this->Ordensservico->findByIdticket($ticketId)->select(['id'])->first();
			$oid = (int)($ordem->id ?? 0);
			$cache[$ticketId] = $oid > 0 ? $oid : null;
		} catch (\Throwable $e) {
			$cache[$ticketId] = null;
		}

		return $cache[$ticketId];
	}

	protected function _ticketRowApiCliente($reg, string $tecnicosLabel = '', array $ctx = []): array {
		$c = $reg->cliente ?? null;
		$nomeCliente = '';
		if ($c) {
			$nomeCliente = (int)$c->tipo === (int)C_ClientesTipoFisica ? (string)$c->nome : (string)($c->razaosocial ?? '');
		}
		$id = (int)$reg->id;
		$sit = (int)$reg->situacao;
		$acoes = [];
		if ($sit !== (int)C_TicketSituacaoResolvido && $sit !== (int)C_TicketSituacaoFechado) {
			$acoes[] = ['key' => 'cancelar', 'label' => 'Cancelar', 'url' => $this->_ticketUrl(['action' => 'cancelar', $id])];
		}
		$acoes[] = ['key' => 'imprimir', 'label' => 'Imprimir', 'url' => $this->_ticketUrl(['action' => 'imprimir', $id, '?' => ['autoprint' => 1]]), 'target' => '_blank'];

		$assuntoNome = trim((string)$this->_ticketAssuntoTexto($reg->assunto));
		if ($assuntoNome === '' || $assuntoNome === '0') {
			$assuntoNome = 'Não informado';
		}
		$row = [
			'id' => $id,
			'autor' => $this->_ticketAutorNome($reg),
			'created' => $reg->created ? $reg->created->format('d/m/Y') : '',
			'assunto_nome' => $assuntoNome,
			'assunto_id' => is_numeric($reg->assunto) ? (int)$reg->assunto : null,
			'assuntoCode' => $reg->assunto,
			'status' => $this->_ticketSituacaoTexto($reg->situacao),
			'situacao' => $sit,
			'situacaoLabel' => $this->_ticketSituacaoTexto($reg->situacao),
			'cliente' => $nomeCliente,
			'tecnicos' => $tecnicosLabel !== '' ? $tecnicosLabel : '—',
			'descricao' => mb_strimwidth(strip_tags((string)($reg->solicitacao ?? '')), 0, 160, '…', 'UTF-8'),
			'solicitacaoPreview' => mb_strimwidth(strip_tags((string)($reg->solicitacao ?? '')), 0, 220, '…', 'UTF-8'),
			'urls' => [
				'view' => $this->_ticketUrl(['action' => 'view', $id]),
			],
			'acoes' => $acoes,
		];
		if (!empty($ctx['hasSeveridadeCol'])) {
			$row['severidade'] = $this->_ticketSeveridadeLabel((string)($reg->severidade ?? 'media'));
			$row['severidadeCode'] = $this->_normalizeTicketSeveridade($reg->severidade ?? 'media');
		}

		return $row;
	}

	protected function _apiTicketViewAllowed($ticket): bool {
		if (empty($ticket)) {
			return false;
		}
		$role = (int)$this->Auth->user('role');
		$idempresa = $this->Auth->user('idempresa');
		if ((int)$ticket->idempresa !== (int)$idempresa) {
			return false;
		}
		if ($role === (int)C_RoleCliente) {
			$idcliente = $this->Auth->user('idcliente');
			$clienteBase = $this->Clientes->findById($idcliente)->first();
			if (empty($clienteBase)) {
				return false;
			}
			$clienteVerifica = null;
			if ($clienteBase->tipo == C_ClientesTipoJuridica) {
				$qCv = $this->Clientes->findByCnpj(removeCaracteres($clienteBase->cnpj));
				$this->Abac->applyToQuery($qCv, 'Clientes');
				$clienteVerifica = $qCv->first();
			} else {
				$qCv = $this->Clientes->findByCpf(removeCaracteres($clienteBase->cpf));
				$this->Abac->applyToQuery($qCv, 'Clientes');
				$clienteVerifica = $qCv->first();
			}
			if (empty($clienteVerifica) || ($clienteVerifica->cpf != $clienteBase->cpf && $clienteBase->cnpj != $clienteVerifica->cnpj)) {
				return false;
			}
			if ($this->Auth->user('permissaoacesso')) {
				return true;
			}
			if ((int)$ticket->idautor === (int)$this->Auth->user('id')) {
				return true;
			}
			if ((int)$ticket->idcliente === (int)$this->Auth->user('idcliente')) {
				return true;
			}
			return false;
		}
		// Técnico (role 0): mesmo critério do isAuthorized do edit — qualquer usuário técnico da empresa.
		if ($role === 0) {
			return true;
		}
		return false;
	}

	protected function _apiAnexoRow($a): array {
		$id = (int)$a->id;
		$base = Router::url(['action' => 'downloadAnexo', $id], true);

		return [
			'id' => $id,
			'nome' => (string)$a->arquivo,
			'url' => $base,
			'urlView' => $base . (strpos($base, '?') !== false ? '&' : '?') . 'inline=1',
		];
	}

	protected function _apiComentariosPayload($idticket): array {
		// Sem lista restrita de fields: no CakePHP, omitir FKs quebra o contain e a lista pode vir vazia.
		$rows = $this->Ticketcomentarios->find('all', [
			'contain' => ['Users', 'Tickets'],
		])->where([
			'Ticketcomentarios.idticket' => $idticket,
		])->order(['Ticketcomentarios.id' => 'ASC'])->toArray();

		$comentarios = [];
		foreach ($rows as $c) {
			$u = $c->user ?? null;
			$autor = '';
			$roleU = 1;
			if ($u) {
				$autor = trim((string)($u->name ?? $u->username ?? ''));
				$roleU = (int)($u->role ?? 1);
			}
			if ($autor === '' && !empty($c->idautor)) {
				$uf = $this->Users->findById($c->idautor)->select(['name', 'username', 'role'])->first();
				if ($uf) {
					$autor = trim((string)($uf->name ?: $uf->username));
					$roleU = (int)$uf->role;
				}
			}
			$papel = $roleU === 0 ? 'tecnico' : 'cliente';
			if ($autor === '') {
				$autor = $papel === 'tecnico' ? 'Técnico' : 'Cliente';
			}
			$quando = '';
			if (!empty($c->created) && $c->created instanceof \DateTimeInterface) {
				$ts = (int)$c->created->format('U');
				if ($ts > 86400) {
					$quando = $c->created->format('d/m/Y H:i');
				}
			}
			$comentarios[] = [
				'id' => (int)$c->id,
				'idautor' => (int)$c->idautor,
				'autor' => $autor,
				'papel' => $papel,
				'texto' => (string)($c->comentario ?? ''),
				'quando' => $quando,
			];
		}

		return $comentarios;
	}

	public function apiComments($idticket = null) {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		$ticket = $this->Tickets->find('all', ['contain' => ['Clientes', 'Users']])
			->where(['tickets.id' => $idticket]);
		$this->Abac->applyToQuery($ticket, 'Tickets', 'tickets');
		$ticket = $ticket->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$comentarios = $this->_apiComentariosPayload($idticket);
		$solicitante = null;
		if (!empty($ticket->idsolicitante)) {
			$solicitante = $this->Users->findById($ticket->idsolicitante)->select(['name'])->first();
		}
		$descAtend = isset($ticket->descricao_atendimento) ? (string)$ticket->descricao_atendimento : '';
		$idt = (int)$idticket;
		$payload = [
			'ok' => true,
			'comentarios' => $comentarios,
			'status' => $this->_ticketSituacaoTexto($ticket->situacao),
			'situacao' => (int)$ticket->situacao,
			'descricao' => (string)($ticket->solicitacao ?? ''),
			'descricaoAtendimento' => $descAtend,
			'horasTecnicas' => $this->_apiHorasTecnicasPayload($idt, $ticket),
			'responsavel' => $this->_apiResponsavelSolicitanteDisplay($ticket, $solicitante),
		];

		return $this->response
			->withType('application/json')
			->withHeader('Cache-Control', 'private, no-store, no-cache, must-revalidate')
			->withHeader('Pragma', 'no-cache')
			->withStringBody(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	protected function _apiTicketDetailPayload($ticket, $idticket): array {
		$role = (int)$this->Auth->user('role');
		$cliente = $this->Clientes->findById($ticket->idcliente)->select(['razaosocial', 'nomefantasia', 'nome', 'tipo', 'cnpj', 'cpf'])->first();
		// #region agent log
		$this->_agentDebugLog('H-cidade', 'TicketsController::_apiTicketDetailPayload:cliente', 'cliente select ok (no cidade column)', [
			'idcliente' => (int)$ticket->idcliente,
			'hasRow' => (bool)$cliente,
		]);
		// #endregion
		$clienteNome = $cliente && $cliente->tipo == C_ClientesTipoFisica ? $cliente->nome : ($cliente->razaosocial ?? '');
		$solicitante = null;
		if (!empty($ticket->idsolicitante)) {
			$solicitante = $this->Users->findById($ticket->idsolicitante)->select(['name'])->first();
		}
		$responsavelSolicitante = $this->_apiResponsavelSolicitanteDisplay($ticket, $solicitante);

		$comentarios = $this->_apiComentariosPayload($idticket);

		$qAnx = $this->Ticketsanexos->find('all')->where(['idticket' => $idticket]);
		$this->Abac->applyToQuery($qAnx, 'Ticketsanexos', 'Ticketsanexos');
		$anexosRows = $qAnx->toArray();
		$anexos = [];
		foreach ($anexosRows as $a) {
			$anexos[] = $this->_apiAnexoRow($a);
		}

		$createdFmt = $ticket->created ? $ticket->created->format('d/m/Y H:i') : '';
		$atualizadoEm = $createdFmt;
		try {
			$m = $ticket->get('modified');
			if ($m && is_object($m) && method_exists($m, 'format')) {
				$atualizadoEm = $m->format('d/m/Y H:i');
			}
		} catch (\Throwable $e) {
		}

		$descAtend = isset($ticket->descricao_atendimento) ? (string)$ticket->descricao_atendimento : '';
		$docCli = null;
		if ($cliente) {
			$docCli = (string)(($cliente->tipo == C_ClientesTipoFisica) ? ($cliente->cpf ?? '') : ($cliente->cnpj ?? ''));
		}
		$chContr = [
			'totalHours' => null,
			'usedHours' => null,
			'balanceHours' => null,
			'percentUsed' => null,
			'mode' => null,
			'label' => '—',
			'hasContract' => false,
		];
		$chEntity = null;
		try {
			$chEntity = ServiceDeskContractHoursService::findContractForClient((int)$ticket->idcliente, (int)$this->Auth->user('idempresa'));
			if ($chEntity) {
				$chContr = ServiceDeskContractHoursService::enrichContractHoursForApi(
					$chEntity,
					ServiceDeskContractHoursService::getSnapshot($chEntity)
				);
			}
		} catch (\Throwable $e) {
		}

		$urls = [
			'indexCliente' => $this->_ticketUrl(['action' => 'indexcliente']),
			'indexTecnico' => $role === 0 ? $this->_ticketUrl(['action' => 'index']) : null,
			'operacional' => $role === 0 ? $this->_ticketUrl(['action' => 'operacional']) : null,
			'edit' => $this->_ticketUrl(['action' => 'edit', $idticket]),
			'cancelar' => $this->_ticketUrl(['action' => 'cancelar', $idticket]),
			'imprimir' => $this->_ticketUrl(['action' => 'imprimir', $idticket, '?' => ['autoprint' => 1]]),
		];
		if ($chEntity) {
			$urls['contratoHoras'] = Router::url(['controller' => 'ContratosHoras', 'action' => 'edit', (int)$chEntity->get('id')]);
		}

		$assuntoNome = trim((string)$this->_ticketAssuntoTexto($ticket->assunto));
		if ($assuntoNome === '' || $assuntoNome === '0') {
			$assuntoNome = 'Não informado';
		}
		return [
			'id' => (int)$ticket->id,
			'assunto_nome' => $assuntoNome,
			'assunto_id' => is_numeric($ticket->assunto) ? (int)$ticket->assunto : null,
			'assunto_options' => $role === 0 ? $this->_ticketAssuntoApiOptionsList() : [],
			'status' => $this->_ticketSituacaoTexto($ticket->situacao),
			'situacao' => (int)$ticket->situacao,
			'descricao' => (string)($ticket->solicitacao ?? ''),
			'descricaoAtendimento' => $descAtend,
			'prioridade' => in_array('severidade', $this->Tickets->getSchema()->columns(), true)
				? $this->_ticketSeveridadeLabel((string)($ticket->severidade ?? 'media'))
				: '—',
			'responsavel' => $responsavelSolicitante,
			'abertoEm' => $createdFmt,
			'atualizadoEm' => $atualizadoEm,
			'atualizado' => $atualizadoEm,
			'cliente' => $clienteNome,
			'cnpj' => $docCli !== null && $docCli !== '' ? $docCli : null,
			'email' => $ticket->email ?? '',
			'contractHours' => $chContr,
			'comentarios' => $comentarios,
			'anexos' => $anexos,
			'urls' => $urls,
			'situacao' => (int)$ticket->situacao,
			'flags' => [
				'role' => $role,
				'canEditDescricao' => $role === 0 && (int)$this->Auth->user('admin') === 1,
				'canEditDescricaoAtendimento' => $role === 0,
				'canCancel' => (int)$ticket->situacao !== (int)C_TicketSituacaoResolvido
					&& (int)$ticket->situacao !== (int)C_TicketSituacaoFechado,
			],
			'horasTecnicas' => $this->_apiHorasTecnicasPayload((int)$idticket, $ticket),
		];
	}

	public function apiIndex() {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		$empresa = (int)$this->Auth->user('idempresa');
		if ($empresa <= 0) {
			return $this->jsonResponse([
				'ok' => false,
				'error' => 'session_empresa_invalida',
				'groups' => null,
				'workflow' => ['enabled' => false, 'filas' => [], 'queuesRelacional' => false, 'queues' => []],
			], 403);
		}
		// Não eager-load SupportLevels no ticket: no PG o ORM pode falhar ao montar o SQL (joins/alias).
		// Rótulos vêm de _supportLevelName (cache) em _ticketRowApiTecnico.
		$tierContains = [];
		$t0 = ['users', 'Clientes'];
		if ($this->_queuesRelacionalReady()) {
			$t0['Queues'] = [];
		}
		$tierContains[] = $t0;
		$tierContains[] = ['users', 'Clientes'];
		$tierContains[] = ['Clientes'];
		$tierContains[] = [];

		try {
		$loadEx = null;
		foreach ($tierContains as $tierIdx => $contain) {
			try {
				$base = ['contain' => $contain, 'order' => ['Tickets.id' => 'DESC']];
				$qPend = $this->Tickets->find('all', $base)->where(['situacao' => C_TicketSituacaoPendente]);
				$this->Abac->applyToQuery($qPend, 'Tickets', 'Tickets');
				$ticketsPendentes = $this->_applyApiIndexWorkflowFilters($qPend)->toArray();
				$qEm = $this->Tickets->find('all', $base)->where(['situacao' => C_TicketSituacaoEmandamento]);
				$this->Abac->applyToQuery($qEm, 'Tickets', 'Tickets');
				$ticketsEmandamento = $this->_applyApiIndexWorkflowFilters($qEm)->toArray();
				$qRes = $this->Tickets->find('all', $base)->where(['situacao' => C_TicketSituacaoResolvido]);
				$this->Abac->applyToQuery($qRes, 'Tickets', 'Tickets');
				$ticketsResolvidos = $this->_applyApiIndexWorkflowFilters($qRes)->toArray();
				$qFec = $this->Tickets->find('all', $base)->where(['situacao' => C_TicketSituacaoFechado]);
				$this->Abac->applyToQuery($qFec, 'Tickets', 'Tickets');
				$ticketsFechados = $this->_applyApiIndexWorkflowFilters($qFec)->toArray();
				$qAll = $this->Tickets->find('all', $base)->order(['Tickets.situacao' => 'ASC', 'Tickets.id' => 'DESC']);
				$this->Abac->applyToQuery($qAll, 'Tickets', 'Tickets');
				$tickets = $this->_applyApiIndexWorkflowFilters($qAll)->toArray();
				$loadEx = null;
				if ($tierIdx > 0) {
					$this->log('apiIndex: carregou com contain reduzido (tier ' . $tierIdx . ')', 'warning');
				}
				break;
			} catch (\Throwable $e) {
				$loadEx = $e;
				$this->log(
					'apiIndex tier_' . $tierIdx . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine(),
					'warning'
				);
			}
		}
		if ($loadEx !== null) {
			throw $loadEx;
		}

		$allForTec = array_merge($ticketsPendentes, $ticketsEmandamento, $ticketsResolvidos, $ticketsFechados, $tickets);
		$tecIds = [];
		foreach ($allForTec as $t) {
			$tecIds[] = (int)$t->id;
		}
		$wf = $this->_ticketWorkflowSchemaReady();
		$queuesUi = $this->_queuesRelacionalReady();
		$cols = $this->Tickets->getSchema()->columns();
		$hasRespCol = in_array('idtecnico_responsavel', $cols, true) || in_array('owner_id', $cols, true);
		$tecMap = $this->_ticketTecnicosLabelsByTicketIds($tecIds);
		$respIds = [];
		if ($hasRespCol) {
			foreach ($allForTec as $t) {
				$rid = (int)($t->idtecnico_responsavel ?? 0);
				if ($rid <= 0 && in_array('owner_id', $cols, true)) {
					$rid = (int)($t->owner_id ?? 0);
				}
				if ($rid > 0) {
					$respIds[] = $rid;
				}
			}
		}
		$respNames = $hasRespCol ? $this->_batchUserDisplayNames($respIds) : [];
		$transfSet = $hasRespCol ? $this->_ticketIdsComTransferencia($tecIds) : [];
		$transferEnabled = $this->_ticketTransferApiAllowed();
		$hasSeveridadeCol = in_array('severidade', $cols, true);
		$mapTec = function ($reg) use ($tecMap, $wf, $queuesUi, $hasRespCol, $respNames, $transfSet, $transferEnabled, $cols, $hasSeveridadeCol) {
			$tid = (int)$reg->id;
			if ($hasRespCol) {
				$rid = (int)($reg->idtecnico_responsavel ?? 0);
				if ($rid <= 0 && in_array('owner_id', $cols, true)) {
					$rid = (int)($reg->owner_id ?? 0);
				}
				$tecLabel = $rid > 0 ? ($respNames[$rid] ?? ('Usuário #' . $rid)) : 'Não atribuído';
			} else {
				$tecLabel = $tecMap[$tid] ?? '';
			}

			return $this->_ticketRowApiTecnico($reg, $tecLabel, [
				'workflow' => $wf,
				'queuesUi' => $queuesUi,
				'transferEnabled' => $transferEnabled,
				'transferido' => isset($transfSet[$tid]),
				'hasSeveridadeCol' => $hasSeveridadeCol,
			]);
		};
		$catalog = [];
		foreach ($this->_filaSuporteCatalog() as $code => $meta) {
			$catalog[] = ['code' => $code, 'label' => $meta['label'], 'nivel' => $meta['nivel']];
		}
		$dbQueues = [];
		if ($queuesUi) {
			try {
				$qf = $this->Queues->find()->where(['Queues.idempresa' => (int)$empresa])->order(['Queues.sort_order' => 'ASC', 'Queues.id' => 'ASC']);
				if ($this->_supportLevelsRoutingReady()) {
					$qf->contain(['SupportLevels']);
				}
				foreach ($qf->all() as $qr) {
					$e = (int)$empresa;
					$item = [
						'id' => (int)$qr->id,
						'name' => (string)$qr->name,
						'company_id' => $e,
						'idempresa' => $e,
						'codigo' => $qr->codigo !== null ? (string)$qr->codigo : null,
					];
					if ($this->_supportLevelsRoutingReady()) {
						$item['support_level_id'] = $qr->support_level_id !== null && $qr->support_level_id !== '' ? (int)$qr->support_level_id : null;
						$item['supportLevelName'] = !empty($qr->support_level) ? (string)$qr->support_level->name : null;
						$item['supportLevelSort'] = $this->_queueLevelSortOrder((int)$qr->id);
					}
					$dbQueues[] = $item;
				}
			} catch (\Throwable $e) {
				$this->log('apiIndex dbQueues: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine(), 'error');
				$dbQueues = [];
			}
		}
		$supportLevelsOut = [];
		if ($this->_supportLevelsRoutingReady()) {
			try {
				foreach ($this->SupportLevels->find()->order(['sort_order' => 'ASC'])->all() as $sx) {
					$supportLevelsOut[] = [
						'id' => (int)$sx->id,
						'name' => (string)$sx->name,
						'sort_order' => (int)$sx->sort_order,
					];
				}
			} catch (\Throwable $e) {
				$this->log('apiIndex supportLevelsOut: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine(), 'error');
				$supportLevelsOut = [];
			}
		}
		$out = [
			'ok' => true,
			'workflow' => [
				'enabled' => $wf || $queuesUi,
				'filas' => $catalog,
				'queuesRelacional' => $queuesUi,
				'queues' => $dbQueues,
				'assuntoOptions' => $this->_ticketAssuntoApiOptionsList(),
				'supportLevels' => $supportLevelsOut,
				'supportLevelsEnabled' => $this->_supportLevelsRoutingReady(),
			],
			'groups' => [
				'todos' => array_map($mapTec, $tickets),
				'pendentes' => array_map($mapTec, $ticketsPendentes),
				'emandamento' => array_map($mapTec, $ticketsEmandamento),
				'resolvidos' => array_map($mapTec, $ticketsResolvidos),
				'fechados' => array_map($mapTec, $ticketsFechados),
			],
		];
		return $this->jsonResponse($out);
		} catch (\Throwable $e) {
			$this->log(
				'apiIndex: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine(),
				'error'
			);

			return $this->jsonResponse([
				'ok' => false,
				'error' => 'api_index_failed',
				'groups' => null,
				'workflow' => ['enabled' => false, 'filas' => [], 'queuesRelacional' => false, 'queues' => []],
			], 500);
		}
	}

	public function apiTicketSubjectOptions() {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		if ((int)$this->Auth->user('role') !== 0) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}

		return $this->jsonResponse([
			'ok' => true,
			'options' => $this->_ticketAssuntoApiOptionsList(),
		]);
	}

	public function apiDashboardOperacional() {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		$empresa = (int)$this->Auth->user('idempresa');
		$svc = new DashboardService($this->Tickets);

		return $this->jsonResponse([
			'ok' => true,
			'dashboard' => $svc->operationalSnapshot($empresa),
		]);
	}

	public function apiTecnicosLista() {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		$empresa = (int)$this->Auth->user('idempresa');
		$qFilter = $this->request->getQuery('queue_id');
		$queueUserFilter = null;
		if ($qFilter !== null && $qFilter !== '' && ctype_digit((string)$qFilter) && $this->_queuesRelacionalReady()) {
			$qRow = $this->Queues->find()
				->where(['Queues.id' => (int)$qFilter, 'Queues.idempresa' => $empresa])
				->first();
			if (empty($qRow)) {
				return $this->jsonResponse(['ok' => true, 'tecnicos' => []]);
			}
			$linkedIds = $this->QueuesUsers->find()
				->select(['user_id'])
				->where(['QueuesUsers.queue_id' => (int)$qFilter])
				->extract('user_id')
				->toList();
			// Com vínculos: só esses usuários. Sem vínculos na fila: lista técnicos da empresa (a API de
			// transferência ainda exige queues_users ao atribuir alguém; “só fila” funciona).
			$queueUserFilter = !empty($linkedIds) ? $linkedIds : null;
		}
		$levelsReady = $this->_supportLevelsRoutingReady();
		$contain = $levelsReady ? ['Users' => ['SupportLevels']] : ['Users'];
		$qry = $this->Empresasusers->find('all', ['contain' => $contain])
			->where(['Empresasusers.idempresa' => $empresa, 'Users.role' => 0, 'Users.inativo' => 0]);
		if ($queueUserFilter !== null) {
			$qry->where(['Users.id IN' => $queueUserFilter]);
		}
		$rows = $qry->order(['Users.name' => 'ASC'])->toArray();
		$seen = [];
		$list = [];
		foreach ($rows as $r) {
			$u = $r->user ?? $r->users ?? null;
			if (!$u || isset($seen[(int)$u->id])) {
				continue;
			}
			$seen[(int)$u->id] = true;
			$nm = trim((string)($u->name ?? ''));
			if ($nm === '') {
				$nm = trim((string)($u->username ?? ''));
			}
			if ($nm === '') {
				$nm = 'Usuário #' . (int)$u->id;
			}
			$entry = ['id' => (int)$u->id, 'name' => $nm];
			if ($levelsReady) {
				$sl = $u->support_level ?? null;
				if ($sl) {
					$entry['nivel_id'] = (int)($sl->id ?? 0);
					$entry['nivel_label'] = (string)($sl->name ?? '');
					$entry['nivel_sort'] = (int)($sl->sort_order ?? 0);
				} elseif (!empty($u->support_level_id)) {
					$entry['nivel_id'] = (int)$u->support_level_id;
					$entry['nivel_label'] = $this->_supportLevelName((int)$u->support_level_id);
					$entry['nivel_sort'] = $this->_supportLevelSortById((int)$u->support_level_id);
				}
			}
			$list[] = $entry;
		}

		return $this->jsonResponse(['ok' => true, 'tecnicos' => $list]);
	}

	public function apiTransferirTicket($idticket = null) {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		if (!$this->_ticketTransferApiAllowed()) {
			return $this->jsonResponse(['ok' => false, 'error' => 'workflow_columns_missing'], 503);
		}
		$idticket = (int)$idticket;
		$qTm = $this->Tickets->find()->where(['id' => $idticket]);
		$this->Abac->applyToQuery($qTm, 'Tickets', 'Tickets');
		$ticket = $qTm->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		$body = $this->request->input('json_decode', true);
		if (!is_array($body)) {
			$body = $this->request->getData();
		}
		$destId = isset($body['iduser_destino']) ? (int)$body['iduser_destino'] : 0;
		$queueIdBody = isset($body['queue_id']) ? (int)$body['queue_id'] : 0;
		$motivo = isset($body['motivo']) ? trim((string)$body['motivo']) : '';
		$filaNova = isset($body['fila_suporte']) ? trim((string)$body['fila_suporte']) : '';
		$nivelNova = isset($body['nivel_atendimento']) ? (int)$body['nivel_atendimento'] : null;

		if (mb_strlen($motivo) < 3) {
			return $this->jsonResponse(['ok' => false, 'error' => 'motivo_obrigatorio'], 400);
		}

		$wf = $this->_ticketWorkflowSchemaReady();
		$cat = $this->_filaSuporteCatalog();
		$filaAnt = (string)($ticket->fila_suporte ?? 'n1');
		$nivelAnt = (int)($ticket->nivel_atendimento ?? 1);
		$filaBody = ($filaNova !== '' && isset($cat[$filaNova])) ? $filaNova : null;
		$filaWorkflowSemTecnicoOk = $wf && $filaBody !== null && $filaBody !== $filaAnt;

		if ($destId <= 0 && $queueIdBody <= 0 && !$filaWorkflowSemTecnicoOk) {
			return $this->jsonResponse(['ok' => false, 'error' => 'destino_ou_fila_obrigatorio'], 400);
		}

		$empresa = (int)$this->Auth->user('idempresa');
		$ticketEmpresa = (int)$ticket->idempresa;
		if ($ticketEmpresa !== $empresa) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		if (!$this->_userMayAssumeTicketTechnically($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'sem_permissao_transferir_fila'], 403);
		}
		$newQueue = null;
		if ($queueIdBody > 0) {
			if (!$this->_queuesRelacionalReady()) {
				return $this->jsonResponse(['ok' => false, 'error' => 'queues_not_installed'], 503);
			}
			$qFind = $this->Queues->find()->where(['Queues.id' => $queueIdBody]);
			if ($this->_supportLevelsRoutingReady()) {
				$qFind->contain(['SupportLevels']);
			}
			$this->Abac->applyToQuery($qFind, 'Queues', 'Queues');
			$newQueue = $qFind->first();
			if (empty($newQueue)) {
				return $this->jsonResponse(['ok' => false, 'error' => 'fila_invalida'], 400);
			}
			if ($destId <= 0 && (int)($ticket->queue_id ?? 0) === $queueIdBody) {
				return $this->jsonResponse(['ok' => false, 'error' => 'mesma_fila'], 400);
			}
			if ($this->_supportLevelsRoutingReady()) {
				$curOrd = $this->_ticketQueueLevelSort($ticket);
				$dstOrd = $this->_queueLevelSortOrder($queueIdBody);
				if ($curOrd > 0 && $dstOrd > 0 && $dstOrd <= $curOrd) {
					return $this->jsonResponse(['ok' => false, 'error' => 'escalacao_invalida'], 400);
				}
			}
		}

		$filaPost = $filaNova !== '' && isset($cat[$filaNova]) ? $filaNova : null;
		$nivelPost = $nivelNova !== null && $nivelNova >= 1 && $nivelNova <= 5 ? $nivelNova : null;
		if ($filaPost !== null) {
			$nivelPost = $cat[$filaPost]['nivel'];
		} elseif ($nivelPost !== null) {
			foreach ($cat as $c => $meta) {
				if ((int)$meta['nivel'] === $nivelPost) {
					$filaPost = $c;
					break;
				}
			}
		}
		if ($newQueue && $newQueue->codigo !== null && $newQueue->codigo !== '' && isset($cat[(string)$newQueue->codigo])) {
			$filaPost = (string)$newQueue->codigo;
			$nivelPost = $cat[$filaPost]['nivel'];
		}

		$oldId = (int)($ticket->idtecnico_responsavel ?? 0);
		$oldName = 'Não atribuído';
		if ($oldId > 0) {
			$oldName = ($this->_batchUserDisplayNames([$oldId])[$oldId]) ?? ('Usuário #' . $oldId);
		}
		$agoraDt = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
		$agora = $agoraDt->format('d/m/Y H:i:s');
		$agoraSql = $agoraDt->format('Y-m-d H:i:s');
		$sitAntes = (int)$ticket->situacao;
		$levelAntLabel = $this->_ticketSupportLevelLabelForHistory($ticket);
		$newLevelLabelForObs = '';
		if ($newQueue) {
			if (!empty($newQueue->support_level)) {
				$newLevelLabelForObs = (string)$newQueue->support_level->name;
			} elseif (!empty($newQueue->support_level_id) && $this->_supportLevelsRoutingReady()) {
				$newLevelLabelForObs = $this->_supportLevelName((int)$newQueue->support_level_id);
			}
		}

		// Workflow legado sem queue_id no POST: só fila/nível, sem técnico (espelha filas relacionais quando possível).
		if ($destId <= 0 && $queueIdBody <= 0 && $wf && $filaPost !== null && $filaPost !== $filaAnt) {
			$nDestWf = (int)($cat[$filaPost]['nivel'] ?? 0);
			if ($nDestWf > 0 && $nDestWf < $nivelAnt) {
				return $this->jsonResponse(['ok' => false, 'error' => 'escalacao_invalida'], 400);
			}
			$mappedQ = null;
			if ($this->_queuesRelacionalReady()) {
				$qf = $this->Queues->find()->where(['Queues.idempresa' => $ticketEmpresa, 'Queues.codigo' => $filaPost]);
				if ($this->_supportLevelsRoutingReady()) {
					$qf->contain(['SupportLevels']);
				}
				$mappedQ = $qf->first();
			}
			$newLevelObs = $newLevelLabelForObs;
			if ($newLevelObs === '') {
				$newLevelObs = $this->_filaLabelFromCode($filaPost);
			}
			$obsLinhas = [
				'Transferência de fila (sem técnico atribuído)',
				'Data/hora: ' . $agora,
				'Técnico que transferiu: ' . trim((string)($this->Auth->user('name') ?: $this->Auth->user('username') ?: 'id ' . (int)$this->Auth->user('id'))),
				'Técnico anterior (responsável): ' . $oldName . ($oldId > 0 ? ' (id ' . $oldId . ')' : ''),
				'Nova fila (workflow): ' . $this->_filaLabelFromCode($filaPost),
				'Motivo: ' . $motivo,
				'Nível anterior: ' . $levelAntLabel,
			];
			if ($newLevelObs !== '') {
				$obsLinhas[] = 'Novo nível / fila: ' . $newLevelObs;
			}

			try {
				$this->Tickets->getConnection()->transactional(function () use ($idticket, $empresa, $agoraSql, $obsLinhas, $wf, $cat, $filaPost, $sitAntes, $mappedQ) {
					$tcols = $this->Tickets->getSchema()->columns();
					$set = [
						'idtecnico_responsavel' => null,
						'situacao' => C_TicketSituacaoPendente,
					];
					if ($wf && $filaPost !== null) {
						$set['fila_suporte'] = $filaPost;
						$set['nivel_atendimento'] = $cat[$filaPost]['nivel'];
					}
					if ($mappedQ && in_array('queue_id', $tcols, true)) {
						$set['queue_id'] = (int)$mappedQ->id;
					}
					if ($this->_supportLevelsRoutingReady() && in_array('support_level_id', $tcols, true)) {
						$set['support_level_id'] = $this->_ticketSupportLevelIdFromQueue($mappedQ);
					}
					list($n, $plain) = $this->_ticketTransferApplyUpdate($idticket, $empresa, $set, $agoraSql);
					$this->_ticketTransferAssertUpdated($idticket, $empresa, $plain, $n, 'wf_queue_map');
					$this->Ticketsusers->deleteAll(['idticket' => $idticket]);
					$mov = $this->Ticketsmovs->newEntity();
					$mov->idticket = $idticket;
					$mov->sitantiga = $sitAntes;
					$mov->sitnova = C_TicketMovTransferencia;
					$mov->idusuario = $this->Auth->user('id');
					$mov->idempresa = $empresa;
					$mov->datetime = $agoraSql;
					$mov->observacao = $this->_ticketsmovsObservacaoLimitada(implode("\n", $obsLinhas));
					if (!$this->Ticketsmovs->save($mov)) {
						$this->log(
							'apiTransferirTicket mov_errors: ' . json_encode($mov->getErrors(), JSON_UNESCAPED_UNICODE),
							'error'
						);
						throw new \RuntimeException('save_mov');
					}
				});
			} catch (\Throwable $e) {
				$prev = $e->getPrevious();
				$this->log(
					'apiTransferirTicket: ' . $e->getMessage() . ($prev ? ' | ' . $prev->getMessage() : ''),
					'error'
				);

				return $this->jsonResponse(['ok' => false, 'error' => 'save_failed'], 500);
			}
			$this->Atividades->registrar($this->Auth->user('id'), 'Tickets', 'apiTransferirTicket', $idticket);

			return $this->jsonResponse(['ok' => true]);
		}

		// Só troca de fila (sem novo técnico): volta para aguardando e remove vínculos.
		if ($destId <= 0 && $queueIdBody > 0) {
			$qNameOld = '';
			if ($this->_queuesRelacionalReady() && !empty($ticket->queue_id)) {
				try {
					$qo = $this->Queues->get((int)$ticket->queue_id);
					$qNameOld = (string)$qo->name;
				} catch (\Throwable $e) {
					$qNameOld = '';
				}
			}
			$obsLinhas = [
				'Transferência para outra fila (sem técnico atribuído)',
				'Data/hora: ' . $agora,
				'Técnico que transferiu: ' . trim((string)($this->Auth->user('name') ?: $this->Auth->user('username') ?: 'id ' . (int)$this->Auth->user('id'))),
				'Técnico anterior (responsável): ' . $oldName . ($oldId > 0 ? ' (id ' . $oldId . ')' : ''),
				'Fila de destino: ' . $newQueue->name . ' (id ' . (int)$newQueue->id . ')',
				'Motivo: ' . $motivo,
			];
			if ($qNameOld !== '') {
				$obsLinhas[] = 'Fila anterior: ' . $qNameOld;
			}
			$obsLinhas[] = 'Nível anterior: ' . $levelAntLabel;
			if ($newLevelLabelForObs !== '') {
				$obsLinhas[] = 'Novo nível: ' . $newLevelLabelForObs;
			}

			try {
				$this->Tickets->getConnection()->transactional(function () use ($idticket, $empresa, $agoraSql, $obsLinhas, $newQueue, $wf, $cat, $filaPost, $nivelPost, $sitAntes) {
					$tcols = $this->Tickets->getSchema()->columns();
					$set = [
						'idtecnico_responsavel' => null,
						'situacao' => C_TicketSituacaoPendente,
						'queue_id' => (int)$newQueue->id,
					];
					if ($wf && $filaPost !== null) {
						$set['fila_suporte'] = $filaPost;
						$set['nivel_atendimento'] = $nivelPost ?? $cat[$filaPost]['nivel'];
					}
					if ($this->_supportLevelsRoutingReady() && in_array('support_level_id', $tcols, true)) {
						$set['support_level_id'] = $this->_ticketSupportLevelIdFromQueue($newQueue);
					}
					list($n, $plain) = $this->_ticketTransferApplyUpdate($idticket, $empresa, $set, $agoraSql);
					$this->_ticketTransferAssertUpdated($idticket, $empresa, $plain, $n, 'queue_only');
					$this->Ticketsusers->deleteAll(['idticket' => $idticket]);
					$mov = $this->Ticketsmovs->newEntity();
					$mov->idticket = $idticket;
					$mov->sitantiga = $sitAntes;
					$mov->sitnova = C_TicketMovTransferencia;
					$mov->idusuario = $this->Auth->user('id');
					$mov->idempresa = $empresa;
					$mov->datetime = $agoraSql;
					$mov->observacao = $this->_ticketsmovsObservacaoLimitada(implode("\n", $obsLinhas));
					if (!$this->Ticketsmovs->save($mov)) {
						$this->log(
							'apiTransferirTicket mov_errors: ' . json_encode($mov->getErrors(), JSON_UNESCAPED_UNICODE),
							'error'
						);
						throw new \RuntimeException('save_mov');
					}
				});
			} catch (\Throwable $e) {
				$prev = $e->getPrevious();
				$this->log(
					'apiTransferirTicket: ' . $e->getMessage() . ($prev ? ' | ' . $prev->getMessage() : ''),
					'error'
				);

				return $this->jsonResponse(['ok' => false, 'error' => 'save_failed'], 500);
			}
			$this->Atividades->registrar($this->Auth->user('id'), 'Tickets', 'apiTransferirTicket', $idticket);

			return $this->jsonResponse(['ok' => true]);
		}

		if ($destId > 0) {
			$qPerm = $newQueue ? (int)$newQueue->id : (int)($ticket->queue_id ?? 0);
			if ($qPerm > 0 && $this->_queuesRelacionalReady()) {
				$dlink = $this->QueuesUsers->find()
					->where(['QueuesUsers.user_id' => $destId, 'QueuesUsers.queue_id' => $qPerm])
					->first();
				$membersCount = (int)$this->QueuesUsers->find()
					->where(['QueuesUsers.queue_id' => $qPerm])
					->count();
				if (empty($dlink) && $membersCount === 0) {
					$ins = $this->QueuesUsers->newEntity(['queue_id' => $qPerm, 'user_id' => $destId]);
					if ($this->QueuesUsers->save($ins)) {
						$dlink = $ins;
					}
				}
				if (empty($dlink) && $membersCount > 0) {
					return $this->jsonResponse(['ok' => false, 'error' => 'destino_sem_vinculo_fila'], 400);
				}
				if ($this->_supportLevelsRoutingReady() && !$this->_userCanWorkQueue($destId, $qPerm)) {
					return $this->jsonResponse(['ok' => false, 'error' => 'destino_nivel_incompativel'], 400);
				}
			}
		}

		$vinculo = $this->Empresasusers->find()->where(['idempresa' => $empresa, 'iduser' => $destId])->first();
		$destUser = $this->Users->find()->where(['id' => $destId, 'role' => 0, 'inativo' => 0])->first();
		if (empty($vinculo) || empty($destUser)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'destino_invalido'], 400);
		}
		$destName = trim((string)($destUser->name ?? ''));
		if ($destName === '') {
			$destName = trim((string)($destUser->username ?? ''));
		}
		if ($destName === '') {
			$destName = 'Usuário #' . $destId;
		}
		$obsLinhas = [
			'Transferência de atendimento',
			'Data/hora: ' . $agora,
			'Técnico que transferiu: ' . trim((string)($this->Auth->user('name') ?: $this->Auth->user('username') ?: 'id ' . (int)$this->Auth->user('id'))),
			'Técnico anterior (responsável): ' . $oldName . ($oldId > 0 ? ' (id ' . $oldId . ')' : ''),
			'Novo técnico: ' . $destName . ' (id ' . $destId . ')',
			'Motivo: ' . $motivo,
		];
		$obsLinhas[] = 'Nível anterior: ' . $levelAntLabel;
		if ($newLevelLabelForObs !== '') {
			$obsLinhas[] = 'Novo nível: ' . $newLevelLabelForObs;
		}
		if ($newQueue) {
			$obsLinhas[] = 'Fila de destino: ' . $newQueue->name . ' (id ' . (int)$newQueue->id . ')';
		} elseif ($filaPost !== null && $filaPost !== $filaAnt) {
			$obsLinhas[] = 'Fila: ' . $this->_filaLabelFromCode($filaAnt) . ' → ' . $this->_filaLabelFromCode($filaPost);
		} elseif ($nivelPost !== null && $nivelPost !== $nivelAnt && $filaPost === null) {
			$obsLinhas[] = 'Nível de atendimento: ' . $nivelAnt . ' → ' . $nivelPost;
		}

		try {
			$this->Tickets->getConnection()->transactional(function () use ($ticket, $idticket, $destId, $empresa, $agoraSql, $obsLinhas, $filaPost, $oldId, $cat, $nivelPost, $newQueue, $wf) {
				$tcols = $this->Tickets->getSchema()->columns();
				$set = ['idtecnico_responsavel' => $destId];
				if ($newQueue && $this->_queuesRelacionalReady()) {
					$set['queue_id'] = (int)$newQueue->id;
				}
				if ($filaPost !== null) {
					$set['fila_suporte'] = $filaPost;
					$set['nivel_atendimento'] = $cat[$filaPost]['nivel'];
				} elseif ($nivelPost !== null) {
					$set['nivel_atendimento'] = $nivelPost;
				}
				if (!$wf) {
					unset($set['fila_suporte'], $set['nivel_atendimento']);
				}
				if ($this->_supportLevelsRoutingReady() && in_array('support_level_id', $tcols, true)) {
					$eq = $newQueue ? (int)$newQueue->id : (int)($ticket->queue_id ?? 0);
					$sl = null;
					if ($eq > 0) {
						try {
							$qq = $this->Queues->get($eq, ['contain' => ['SupportLevels']]);
							$sl = $this->_ticketSupportLevelIdFromQueue($qq);
						} catch (\Throwable $e) {
						}
					}
					$set['support_level_id'] = $sl;
				}
				list($n, $plain) = $this->_ticketTransferApplyUpdate($idticket, $empresa, $set, $agoraSql);
				$this->_ticketTransferAssertUpdated($idticket, $empresa, $plain, $n, 'assign_tec');
				$ja = $this->Ticketsusers->find()->where(['idticket' => $idticket, 'iduser' => $destId])->first();
				if (empty($ja)) {
					$tu = $this->Ticketsusers->newEntity();
					$tu->idticket = $idticket;
					$tu->iduser = $destId;
					$tu->idempresa = $empresa;
					if (!$this->Ticketsusers->save($tu)) {
						$this->log(
							'apiTransferirTicket ticketsusers_errors: ' . json_encode($tu->getErrors(), JSON_UNESCAPED_UNICODE),
							'error'
						);
						throw new \RuntimeException('save_ticketsusers');
					}
				}
				if ($oldId > 0 && $oldId !== $destId) {
					$oldRows = $this->Ticketsusers->find()->where(['idticket' => $idticket, 'iduser' => $oldId])->toArray();
					foreach ($oldRows as $ent) {
						$this->Ticketsusers->delete($ent);
					}
				}
				$mov = $this->Ticketsmovs->newEntity();
				$mov->idticket = $idticket;
				$mov->sitantiga = (int)$ticket->situacao;
				$mov->sitnova = C_TicketMovTransferencia;
				$mov->idusuario = $this->Auth->user('id');
				$mov->idempresa = $empresa;
				$mov->datetime = $agoraSql;
				$mov->observacao = $this->_ticketsmovsObservacaoLimitada(implode("\n", $obsLinhas));
				if (!$this->Ticketsmovs->save($mov)) {
					$this->log(
						'apiTransferirTicket mov_errors: ' . json_encode($mov->getErrors(), JSON_UNESCAPED_UNICODE),
						'error'
					);
					throw new \RuntimeException('save_mov');
				}
			});
		} catch (\Throwable $e) {
			$prev = $e->getPrevious();
			$this->log(
				'apiTransferirTicket: ' . $e->getMessage() . ($prev ? ' | ' . $prev->getMessage() : ''),
				'error'
			);

			return $this->jsonResponse(['ok' => false, 'error' => 'save_failed'], 500);
		}
		$this->Atividades->registrar($this->Auth->user('id'), 'Tickets', 'apiTransferirTicket', $idticket);

		return $this->jsonResponse(['ok' => true]);
	}

	public function apiStartTicket($idticket = null) {
		$this->request->allowMethod(['post', 'put']);
		$this->autoRender = false;

		return $this->_apiStartTicketResponse((int)$idticket);
	}

	/**
	 * Alias REST (/tickets/startTicket/:id) — mesmo comportamento de apiStartTicket.
	 */
	public function startTicket($idticket = null) {
		$this->request->allowMethod(['post', 'put']);
		$this->autoRender = false;

		return $this->_apiStartTicketResponse((int)$idticket);
	}

	protected function _apiStartTicketResponse(int $idticket) {
		try {
			return $this->_apiStartTicketResponseInternal($idticket);
		} catch (\Throwable $e) {
			$this->log('apiStartTicket exception: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine(), 'error');
			return $this->jsonResponse(['ok' => false, 'error' => 'exception', 'message' => $e->getMessage()], 500);
		}
	}

	protected function _apiStartTicketResponseInternal(int $idticket) {
		$qTm = $this->Tickets->find()->where(['id' => $idticket]);
		$this->Abac->applyToQuery($qTm, 'Tickets', 'Tickets');
		$ticket = $qTm->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if ((int)$this->Auth->user('role') !== 0) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		if (!$this->_userMayAssumeTicketTechnically($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'sem_permissao_fila'], 403);
		}
		$sitantiga = (int)$ticket->situacao;
		if ($sitantiga === (int)C_TicketSituacaoEmandamento
			&& (int)($ticket->idtecnico_responsavel ?? 0) === (int)$this->Auth->user('id')) {
			// Ticket já está em execução com este técnico — operação idempotente, nada a fazer.
			return $this->jsonResponse(['ok' => true, 'noop' => true]);
		}
		$ticket->situacao = C_TicketSituacaoEmandamento;
		$this->_assignTecnicoEmExecucao($ticket, $idticket);
		$fields = ['situacao'];
		$cols = $this->Tickets->getSchema()->columns();
		if (in_array('idtecnico_responsavel', $cols, true)) {
			$fields[] = 'idtecnico_responsavel';
		}
		$fields = $this->_ticketFieldsComResponsavel($fields);
		if ($this->_supportLevelsRoutingReady() && in_array('support_level_id', $cols, true)) {
			$qid = (int)($ticket->queue_id ?? 0);
			if ($qid > 0) {
				try {
					$qq = $this->Queues->get($qid);
					if (!empty($qq->support_level_id)) {
						$ticket->support_level_id = (int)$qq->support_level_id;
					}
				} catch (\Throwable $e) {
				}
			}
			if (empty($ticket->support_level_id)) {
				try {
					$uu = $this->Users->get((int)$this->Auth->user('id'));
					if (!empty($uu->support_level_id)) {
						$ticket->support_level_id = (int)$uu->support_level_id;
					}
				} catch (\Throwable $e) {
				}
			}
			if (!in_array('support_level_id', $fields, true)) {
				$fields[] = 'support_level_id';
			}
		}
		if ($this->Tickets->save($ticket, ['fields' => $fields])) {
			$uid = (int)$this->Auth->user('id');
			$nm = trim((string)($this->Auth->user('name') ?? ''));
			if ($nm === '') {
				$nm = trim((string)($this->Auth->user('username') ?? ''));
			}
			if ($nm === '') {
				$nm = 'Usuário #' . $uid;
			}
			$obsMov = "Início de atendimento\nData/hora: " . date('d/m/Y H:i:s')
				. "\nTécnico: {$nm} (id {$uid})\nSituação anterior: {$sitantiga}\nNova situação: " . (int)C_TicketSituacaoEmandamento;
			try {
				$this->criarMov($idticket, $sitantiga, C_TicketSituacaoEmandamento, $obsMov);
			} catch (\Throwable $e) {
				$this->log('apiStartTicket criarMov: ' . $e->getMessage(), 'error');
			}
			try {
				if ($sitantiga !== (int)C_TicketSituacaoEmandamento) {
					$this->email($idticket, C_TicketsAcaoEmandamento, null, $this->Auth->user('idempresa'));
				}
			} catch (\Throwable $e) {
				$this->log('apiStartTicket email: ' . $e->getMessage(), 'error');
			}
			$this->Atividades->registrar($this->Auth->user('id'), 'Tickets', 'apiStartTicket', $idticket);

			return $this->jsonResponse(['ok' => true]);
		}

		return $this->jsonResponse(['ok' => false, 'error' => 'save_failed'], 500);
	}

	public function apiIndexCliente() {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		$cliente = $this->Clientes->findById($this->Auth->user('idcliente'))->order(['idempresa ASC'])->first();
		if (empty($cliente)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'cliente_not_found'], 404);
		}
		$assunto = $this->request->getQuery('assunto');
		$situacao = $this->request->getQuery('situacao');
		$fila = $this->request->getQuery('fila');

		$tickets = $this->Tickets->find('all', ['contain' => ['users', 'Clientes']])->where([
			'Tickets.idempresa' => $this->Auth->user('idempresa'),
			'OR' => ['Clientes.cpf' => $cliente->cpf, 'Clientes.cnpj' => $cliente->cnpj],
		]);
		if ($assunto !== null && $assunto !== '') {
			$tickets = $tickets->where(['tickets.assunto' => $assunto]);
		}
		if ($fila !== null && $fila !== '') {
			switch ($fila) {
				case 'pendente':
					$tickets = $tickets->where(['tickets.situacao' => C_TicketSituacaoPendente]);
					break;
				case 'execucao':
					$tickets = $tickets->where(['tickets.situacao' => C_TicketSituacaoEmandamento]);
					break;
				case 'resolvido':
					$tickets = $tickets->where(['tickets.situacao' => C_TicketSituacaoResolvido]);
					break;
				case 'fechados':
					$tickets = $tickets->where(['tickets.situacao' => C_TicketSituacaoFechado]);
					break;
				case 'ativos':
					$tickets = $tickets->where(['tickets.situacao IN' => [C_TicketSituacaoPendente, C_TicketSituacaoEmandamento]]);
					break;
				case 'todos':
				default:
					break;
			}
		} elseif ($situacao !== null && $situacao !== '' && (int)$situacao != -1) {
			$tickets = $tickets->where(['tickets.situacao' => $situacao]);
		} elseif ($situacao != -1) {
			$tickets = $tickets->where(['tickets.situacao IN' => [C_TicketSituacaoPendente, C_TicketSituacaoEmandamento]]);
		}
		if (!$this->Auth->user('permissaoacesso')) {
			$tickets = $tickets->where(['idautor' => $this->Auth->user('id')]);
		}
		$tickets = $tickets->order(['Tickets.id' => 'DESC'])->toArray();

		$cliIds = [];
		foreach ($tickets as $t) {
			$cliIds[] = (int)$t->id;
		}
		$tecMapCli = $this->_ticketTecnicosLabelsByTicketIds($cliIds);
		$hasSeveridadeCol = in_array('severidade', $this->Tickets->getSchema()->columns(), true);
		$rows = [];
		foreach ($tickets as $reg) {
			$rows[] = $this->_ticketRowApiCliente($reg, $tecMapCli[(int)$reg->id] ?? '', ['hasSeveridadeCol' => $hasSeveridadeCol]);
		}
		return $this->jsonResponse([
			'ok' => true,
			'tickets' => $rows,
			'query' => ['assunto' => $assunto, 'situacao' => $situacao, 'fila' => $fila],
		]);
	}

	public function apiAnexoUpload($idticket = null) {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		// #region agent log
		$this->_agentDebugLog48685b('H0', 'apiAnexoUpload:entry', 'enter', ['idticket' => $idticket]);
		// #endregion
		$ticket = $this->Tickets->find('all', ['contain' => ['Clientes', 'Users']])
			->where(['tickets.id' => $idticket]);
		$this->Abac->applyToQuery($ticket, 'Tickets', 'tickets');
		$ticket = $ticket->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$file = $this->request->getData('file');
		if (empty($file) || !is_array($file) || empty($file['tmp_name'])) {
			return $this->jsonResponse(['ok' => false, 'error' => 'no_file'], 400);
		}
		$idempresa = (int)$this->Auth->user('idempresa');
		$ret = $this->moveFile($file, $idempresa, $idticket);
		if ($ret != 1) {
			return $this->jsonResponse(['ok' => false, 'error' => 'upload_failed'], 500);
		}
		if (empty($file['name'])) {
			return $this->jsonResponse(['ok' => false, 'error' => 'no_file'], 400);
		}
		$anexo = $this->Ticketsanexos->newEntity();
		$anexo->arquivo = $file['name'];
		$anexo->idticket = $idticket;
		$anexo->idempresa = $idempresa;
		if (!$this->Ticketsanexos->save($anexo)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'save_failed'], 500);
		}
		// #region agent log
		$this->_agentDebugLog48685b('H0', 'apiAnexoUpload:afterSave', 'save_ok', ['anexoId' => (int)$anexo->id]);
		// #endregion
		try {
			// #region agent log
			$this->_agentDebugLog48685b('H4', 'apiAnexoUpload:beforeCriarMov', 'check_const', [
				'C_TicketAnexoAdicionado_defined' => defined('C_TicketAnexoAdicionado'),
				'C_TicketAnexoAdicionado_value' => defined('C_TicketAnexoAdicionado') ? C_TicketAnexoAdicionado : null,
			]);
			// #endregion
			$this->criarMov((int)$idticket, 0, C_TicketAnexoAdicionado, $file['name']);
			// #region agent log
			$this->_agentDebugLog48685b('H1', 'apiAnexoUpload:afterCriarMov', 'ok');
			// #endregion
			$this->Atividades->registrar($this->Auth->user('id'), 'Tickets', 'apiAnexoUpload', (int)$anexo->id);
			// #region agent log
			$this->_agentDebugLog48685b('H2', 'apiAnexoUpload:afterAtividadesRegistrar', 'ok');
			// #endregion
			$row = $this->_apiAnexoRow($anexo);
			// #region agent log
			$this->_agentDebugLog48685b('H3', 'apiAnexoUpload:afterApiAnexoRow', 'ok', ['row' => $row]);
			// #endregion

			return $this->jsonResponse(['ok' => true, 'anexo' => $row]);
		} catch (\Throwable $e) {
			// #region agent log
			$this->_agentDebugLog48685b('Hx', 'apiAnexoUpload:exception', 'caught', [
				'class' => get_class($e),
				'message' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace0' => explode("\n", $e->getTraceAsString())[0] ?? '',
				'trace1' => explode("\n", $e->getTraceAsString())[1] ?? '',
			]);
			// #endregion
			$detail = sprintf('%s @ %s:%d — %s', get_class($e), basename($e->getFile()), $e->getLine(), $e->getMessage());

			return $this->jsonResponse(['ok' => false, 'error' => 'post_save_exception', 'detail' => $detail], 500);
		}
	}

	public function apiAnexoDelete($idanexo = null) {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		// #region agent log
		$this->_agentDebugLog48685b('H0', 'apiAnexoDelete:entry', 'enter', ['idanexo' => $idanexo]);
		// #endregion
		try {
			$anexo = $this->Ticketsanexos->get($idanexo);
		} catch (\Exception $e) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if ((int)$anexo->idempresa !== (int)$this->Auth->user('idempresa')) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$idticket = (int)$anexo->idticket;
		$ticket = $this->Tickets->find('all', ['contain' => ['Clientes', 'Users']])
			->where(['tickets.id' => $idticket]);
		$this->Abac->applyToQuery($ticket, 'Tickets', 'tickets');
		$ticket = $ticket->first();
		if (empty($ticket) || !$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$arquivo = $this->dirAnexos($anexo->idempresa, $anexo->idticket) . DS . $anexo->arquivo;
		if (file_exists($arquivo) && !@unlink($arquivo)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'unlink_failed'], 500);
		}
		if (!$this->Ticketsanexos->delete($anexo)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'delete_failed'], 500);
		}
		// #region agent log
		$this->_agentDebugLog48685b('H0', 'apiAnexoDelete:afterDelete', 'delete_ok', ['idticket' => $idticket]);
		// #endregion
		try {
			// #region agent log
			$this->_agentDebugLog48685b('H4', 'apiAnexoDelete:beforeCriarMov', 'check_const', [
				'C_TicketAnexoDeletado_defined' => defined('C_TicketAnexoDeletado'),
				'C_TicketAnexoDeletado_value' => defined('C_TicketAnexoDeletado') ? C_TicketAnexoDeletado : null,
			]);
			// #endregion
			$this->criarMov($idticket, 0, C_TicketAnexoDeletado, $anexo->arquivo);
			// #region agent log
			$this->_agentDebugLog48685b('H1', 'apiAnexoDelete:afterCriarMov', 'ok');
			// #endregion
			$this->Atividades->registrar($this->Auth->user('id'), 'Tickets', 'apiAnexoDelete', (int)$idanexo);
			// #region agent log
			$this->_agentDebugLog48685b('H2', 'apiAnexoDelete:afterAtividadesRegistrar', 'ok');
			// #endregion
			$qAnx = $this->Ticketsanexos->find('all')->where(['idticket' => $idticket]);
			$this->Abac->applyToQuery($qAnx, 'Ticketsanexos', 'Ticketsanexos');
			$anexosRows = $qAnx->toArray();
			$list = [];
			foreach ($anexosRows as $row) {
				$list[] = $this->_apiAnexoRow($row);
			}
			// #region agent log
			$this->_agentDebugLog48685b('H3', 'apiAnexoDelete:afterApiAnexoRow', 'ok', ['count' => count($list)]);
			// #endregion

			return $this->jsonResponse(['ok' => true, 'anexos' => $list]);
		} catch (\Throwable $e) {
			// #region agent log
			$this->_agentDebugLog48685b('Hx', 'apiAnexoDelete:exception', 'caught', [
				'class' => get_class($e),
				'message' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace0' => explode("\n", $e->getTraceAsString())[0] ?? '',
				'trace1' => explode("\n", $e->getTraceAsString())[1] ?? '',
			]);
			// #endregion
			$detail = sprintf('%s @ %s:%d — %s', get_class($e), basename($e->getFile()), $e->getLine(), $e->getMessage());

			return $this->jsonResponse(['ok' => false, 'error' => 'post_delete_exception', 'detail' => $detail], 500);
		}
	}

	public function apiView($idticket = null) {
		// #region agent log
		$this->_agentDebugLog('H4', 'TicketsController::apiView:entry', 'enter', ['idticket' => $idticket]);
		// #endregion
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		try {
			$ticket = $this->Tickets->find('all', ['contain' => ['Clientes', 'Users']])
				->where(['tickets.id' => $idticket]);
			$this->Abac->applyToQuery($ticket, 'Tickets', 'tickets');
			$ticket = $ticket->first();
			// #region agent log
			$this->_agentDebugLog('H4', 'TicketsController::apiView:afterQuery', 'first()', ['empty' => empty($ticket), 'id' => $ticket ? (int)$ticket->id : null]);
			// #endregion
			if (empty($ticket)) {
				return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
			}
			if (!$this->_apiTicketViewAllowed($ticket)) {
				return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
			}
			// #region agent log
			$this->_agentDebugLog('H1', 'TicketsController::apiView:beforePayload', 'calling _apiTicketDetailPayload', ['id' => (int)$ticket->id]);
			// #endregion
			$data = $this->_apiTicketDetailPayload($ticket, $idticket);
			// #region agent log
			$this->_agentDebugLog('H1', 'TicketsController::apiView:afterPayload', 'payload ok', ['hasContractHours' => isset($data['contractHours'])]);
			// #endregion
			$body = json_encode(['ok' => true, 'ticket' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			// #region agent log
			if ($body === false) {
				$this->_agentDebugLog('H2', 'TicketsController::apiView:json_encode', 'json_encode failed', ['json_last_error' => json_last_error(), 'json_last_error_msg' => json_last_error_msg()]);
			}
			// #endregion

			return $this->response
				->withType('application/json')
				->withHeader('Cache-Control', 'private, no-store, no-cache, must-revalidate')
				->withHeader('Pragma', 'no-cache')
				->withStringBody($body);
		} catch (\Throwable $e) {
			// #region agent log
			$this->_agentDebugLog('H1', 'TicketsController::apiView:exception', $e->getMessage(), [
				'class' => get_class($e),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
			]);
			// #endregion
			throw $e;
		}
	}

	public function apiTimer($idticket = null) {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		if ((int)$this->Auth->user('role') !== 0) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden', 'message' => 'Apenas usuários de suporte podem usar o timer.'], 403);
		}
		$qTm = $this->Tickets->find()->where(['id' => $idticket]);
		$this->Abac->applyToQuery($qTm, 'Tickets', 'Tickets');
		$ticket = $qTm->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$body = $this->request->input('json_decode', true);
		if (!is_array($body)) {
			$body = $this->request->getData();
		}
		$acao = '';
		if (!empty($body['action'])) {
			$acao = (string)$body['action'];
		} elseif (!empty($body['acao'])) {
			$acao = (string)$body['acao'];
		}
		$result = $this->_timerServiceExecute((int)$idticket, $ticket, $acao, is_array($body) ? $body : []);
		$payload = [
			'ok' => $result['ok'],
			'error' => $result['ok'] ? null : ($result['error'] ?? 'erro'),
			'message' => $result['message'] ?? null,
			'horasTecnicas' => $this->_apiHorasTecnicasPayload((int)$idticket, $ticket),
		];
		if (array_key_exists('duracaoMinutosFinal', $result)) {
			$payload['duracaoMinutosFinal'] = (int)$result['duracaoMinutosFinal'];
		}
		if (array_key_exists('durationSecondsFinal', $result)) {
			$payload['durationSecondsFinal'] = (int)$result['durationSecondsFinal'];
		}
		$status = $result['ok'] ? 200 : 400;

		return $this->jsonResponse($payload, $status);
	}

	/**
	 * Altera situação do ticket (JSON) — mesma regra de negócio de alterarsituacao, sem redirect.
	 */
	public function apiAlterarSituacao($idticket = null) {
		$this->request->allowMethod(['post', 'patch']);
		$this->autoRender = false;
		if ((int)$this->Auth->user('role') !== 0) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$qTm = $this->Tickets->find('all')->where(['id' => $idticket]);
		$this->Abac->applyToQuery($qTm, 'Tickets', 'Tickets');
		$ticket = $qTm->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$body = $this->request->input('json_decode', true);
		if (!is_array($body)) {
			$body = $this->request->getData();
		}
		$situacaoNova = $body['situacao'] ?? $body['sit'] ?? null;
		if ($situacaoNova === null || $situacaoNova === '') {
			return $this->jsonResponse(['ok' => false, 'error' => 'missing_situacao', 'message' => 'Informe situacao no corpo JSON.'], 400);
		}
		if (is_numeric($situacaoNova)) {
			$situacaoNova = (int)$situacaoNova;
		} else {
			$mapped = $this->_mapServicedeskStatusRequestToSituacaoInt((string)$situacaoNova);
			if ($mapped === null) {
				return $this->jsonResponse(['ok' => false, 'error' => 'invalid_situacao', 'message' => 'Situação não permitida nesta API.'], 400);
			}
			$situacaoNova = $mapped;
		}
		$allowed = [
			(int)C_TicketSituacaoPendente,
			(int)C_TicketSituacaoEmandamento,
			(int)C_TicketSituacaoResolvido,
			(int)C_TicketSituacaoFechado,
		];
		if (!in_array((int)$situacaoNova, $allowed, true)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'invalid_situacao', 'message' => 'Situação não permitida nesta API.'], 400);
		}
		$sitantiga = (int)$ticket->situacao;
		if ((int)$situacaoNova !== $sitantiga) {
			TicketAttendimentoTimerService::applyOnSituacaoChange($this->Tickets, $ticket, $sitantiga, (int)$situacaoNova);
		}
		$ticket->situacao = $situacaoNova;

		if ($situacaoNova == C_TicketSituacaoResolvido || $situacaoNova == C_TicketSituacaoFechado) {
			$ticket->datafinalizado = date('d/m/Y');
		}

		if ((int)$situacaoNova === (int)C_TicketSituacaoEmandamento && (int)$situacaoNova !== $sitantiga) {
			$this->_assignTecnicoEmExecucao($ticket, (int)$idticket);
		}
		if (($situacaoNova == C_TicketSituacaoResolvido || $situacaoNova == C_TicketSituacaoFechado) && (int)$situacaoNova !== $sitantiga) {
			$this->_ensureTecnicoResponsavelAoFechamento($ticket);
		}

		if (!$this->Tickets->save($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'save_failed', 'message' => 'Não foi possível gravar o ticket.'], 500);
		}
		try {
			$this->criarMov($ticket->id, $sitantiga, $ticket->situacao);
		} catch (\Throwable $e) {
			$this->log('Tickets::apiAlterarSituacao criarMov: ' . $e->getMessage(), 'error');
		}
		try {
			if ($situacaoNova == C_TicketSituacaoPendente && $situacaoNova != $sitantiga) {
				$this->email($idticket, C_TicketsAcaoPendente, null, $this->Auth->user('idempresa'));
			} elseif ($situacaoNova == C_TicketSituacaoEmandamento && $situacaoNova != $sitantiga) {
				$this->email($idticket, C_TicketsAcaoEmandamento, null, $this->Auth->user('idempresa'));
			} elseif ($situacaoNova == C_TicketSituacaoFechado && $situacaoNova != $sitantiga) {
				$this->email($idticket, C_TicketsAcaoFechado, null, $this->Auth->user('idempresa'));
			} elseif ($situacaoNova == C_TicketSituacaoResolvido && $situacaoNova != $sitantiga) {
				$this->email($idticket, null, null, $this->Auth->user('idempresa'));
			}
		} catch (\Throwable $e) {
			$this->log('Tickets::apiAlterarSituacao email: ' . $e->getMessage(), 'error');
		}

		return $this->jsonResponse([
			'ok' => true,
			'situacao' => (int)$ticket->situacao,
			'situacaoLabel' => $this->_ticketSituacaoTexto($ticket->situacao),
		]);
	}

	/**
	 * PATCH /tickets/:id/assignment — atribui técnico, sincroniza nível (support_level / workflow) e fila.
	 */
	public function apiPatchAssignment($id = null) {
		$this->request->allowMethod(['patch', 'post']);
		$this->autoRender = false;
		$idticket = (int)$id;
		if ($idticket <= 0) {
			return $this->jsonResponse(['ok' => false, 'error' => 'invalid_id'], 400);
		}
		if (!$this->_ticketInlineAssignmentAllowed()) {
			return $this->jsonResponse([
				'ok' => false,
				'error' => 'assignment_not_supported',
				'message' => 'Este ambiente não possui coluna de técnico responsável (idtecnico_responsavel ou owner_id).',
			], 503);
		}
		$qTm = $this->Tickets->find('all')->where(['Tickets.id' => $idticket]);
		$this->Abac->applyToQuery($qTm, 'Tickets', 'Tickets');
		$ticket = $qTm->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		if (!$this->_userMayAssumeTicketTechnically($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'sem_permissao_transferir_fila'], 403);
		}
		$empresa = (int)$this->Auth->user('idempresa');
		if ((int)$ticket->idempresa !== $empresa) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$body = $this->_jsonPatchRequestBody();
		$tecnicoId = isset($body['tecnico_id']) ? (int)$body['tecnico_id'] : (isset($body['iduser_destino']) ? (int)$body['iduser_destino'] : 0);
		$filaIdBody = array_key_exists('fila_id', $body) ? $body['fila_id'] : null;
		$filaId = ($filaIdBody === null || $filaIdBody === '') ? null : (int)$filaIdBody;
		if ($tecnicoId <= 0) {
			return $this->jsonResponse([
				'ok' => false,
				'error' => 'tecnico_obrigatorio',
				'message' => 'É necessário selecionar um técnico.',
			], 422);
		}
		$vinculo = $this->Empresasusers->find()->where(['idempresa' => $empresa, 'iduser' => $tecnicoId])->first();
		$destUser = $this->Users->find()
			->contain($this->_supportLevelsRoutingReady() ? ['SupportLevels'] : [])
			->where(['Users.id' => $tecnicoId, 'Users.role' => 0, 'Users.inativo' => 0])
			->first();
		if (empty($vinculo) || empty($destUser)) {
			return $this->jsonResponse([
				'ok' => false,
				'error' => 'tecnico_invalido',
				'message' => 'Técnico não encontrado ou sem vínculo com a empresa.',
			], 404);
		}
		$oldQid = (int)($ticket->queue_id ?? 0);
		$newQid = $oldQid;
		$newQueue = null;
		if ($filaId !== null && $filaId > 0) {
			$newQid = $filaId;
			$qf = $this->Queues->find()->where(['Queues.id' => $newQid]);
			if ($this->_supportLevelsRoutingReady()) {
				$qf->contain(['SupportLevels']);
			}
			$this->Abac->applyToQuery($qf, 'Queues', 'Queues');
			$newQueue = $qf->first();
			if (empty($newQueue)) {
				return $this->jsonResponse([
					'ok' => false,
					'error' => 'fila_invalida',
					'message' => 'Fila inválida ou sem permissão de acesso.',
				], 422);
			}
		}
		if ($newQid > 0 && $oldQid > 0 && $newQid !== $oldQid && $this->_supportLevelsRoutingReady()) {
			$curOrd = $this->_ticketQueueLevelSort($ticket);
			$dstOrd = $this->_queueLevelSortOrder($newQid);
			if ($curOrd > 0 && $dstOrd > 0 && $dstOrd <= $curOrd) {
				return $this->jsonResponse([
					'ok' => false,
					'error' => 'escalacao_invalida',
					'message' => 'Não é permitido mover o ticket para um nível igual ou inferior.',
				], 422);
			}
		}
		$qPerm = $newQid > 0 ? $newQid : $oldQid;
		$mustInsertQueuesLink = false;
		if ($qPerm > 0 && $this->_queuesRelacionalReady()) {
			$dlink = $this->QueuesUsers->find()
				->where(['QueuesUsers.user_id' => $tecnicoId, 'QueuesUsers.queue_id' => $qPerm])
				->first();
			$membersCount = (int)$this->QueuesUsers->find()->where(['QueuesUsers.queue_id' => $qPerm])->count();
			if (empty($dlink) && $membersCount === 0) {
				$mustInsertQueuesLink = true;
			}
			if (empty($dlink) && $membersCount > 0) {
				return $this->jsonResponse([
					'ok' => false,
					'error' => 'destino_sem_vinculo_fila',
					'message' => 'O técnico não pertence à fila selecionada.',
				], 422);
			}
			if ($this->_supportLevelsRoutingReady() && !$this->_userCanWorkQueue($tecnicoId, $qPerm)) {
				return $this->jsonResponse([
					'ok' => false,
					'error' => 'destino_nivel_incompativel',
					'message' => 'O nível de suporte do técnico é incompatível com a fila.',
				], 422);
			}
		}
		$tcols = $this->Tickets->getSchema()->columns();
		$wf = $this->_ticketWorkflowSchemaReady();
		$cat = $this->_filaSuporteCatalog();
		$agoraSql = date('Y-m-d H:i:s');
		$agoraDt = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
		$agora = $agoraDt->format('d/m/Y H:i:s');
		$nmDest = trim((string)($destUser->name ?? ''));
		if ($nmDest === '') {
			$nmDest = trim((string)($destUser->username ?? ''));
		}
		if ($nmDest === '') {
			$nmDest = 'Usuário #' . $tecnicoId;
		}
		$obsLinhas = [
			'Atribuição inline (API)',
			'Data/hora: ' . $agora,
			'Novo responsável: ' . $nmDest . ' (id ' . $tecnicoId . ')',
		];
		try {
			$this->Tickets->getConnection()->transactional(function () use (
				$ticket,
				$idticket,
				$tecnicoId,
				$empresa,
				$agoraSql,
				$obsLinhas,
				$newQueue,
				$newQid,
				$wf,
				$cat,
				$tcols,
				$destUser,
				$mustInsertQueuesLink,
				$qPerm
			) {
				if ($mustInsertQueuesLink && $qPerm > 0) {
					$insQu = $this->QueuesUsers->newEntity(['queue_id' => $qPerm, 'user_id' => $tecnicoId]);
					if (!$this->QueuesUsers->save($insQu, ['atomic' => false])) {
						throw new \RuntimeException('queues_users:' . json_encode($insQu->getErrors(), JSON_UNESCAPED_UNICODE));
					}
				}
				$set = $this->_ticketAssignResponsavelSetArray($tecnicoId, $tcols);
				if ($set === []) {
					throw new \RuntimeException('assignment_no_ra_column');
				}
				$slUser = (int)($destUser->support_level_id ?? 0);
				if ($this->_supportLevelsRoutingReady() && in_array('support_level_id', $tcols, true) && $slUser > 0) {
					$set['support_level_id'] = $slUser;
				}
				if ($this->_queuesRelacionalReady() && $newQid > 0 && in_array('queue_id', $tcols, true)) {
					$set['queue_id'] = $newQid;
				}
				if ($wf && $newQueue && $newQueue->codigo !== null && $newQueue->codigo !== '' && isset($cat[(string)$newQueue->codigo])) {
					$cd = (string)$newQueue->codigo;
					$set['fila_suporte'] = $cd;
					$set['nivel_atendimento'] = $cat[$cd]['nivel'];
				} elseif ($wf && in_array('nivel_atendimento', $tcols, true)) {
					$slId = (int)($set['support_level_id'] ?? 0);
					if ($slId > 0) {
						$ord = $this->_supportLevelSortById($slId);
						if ($ord > 0) {
							$set['nivel_atendimento'] = min(5, max(1, $ord));
						}
					}
				}
				if ($this->_supportLevelsRoutingReady() && in_array('support_level_id', $tcols, true)) {
					if (empty($set['support_level_id']) && $newQueue) {
						$slq = $this->_ticketSupportLevelIdFromQueue($newQueue);
						if ($slq !== null) {
							$set['support_level_id'] = $slq;
						}
					}
				}
				list($n, $plain) = $this->_ticketTransferApplyUpdate($idticket, $empresa, $set, $agoraSql);
				$this->_ticketTransferAssertUpdated($idticket, $empresa, $plain, $n, 'inline_assignment');
				$ja = $this->Ticketsusers->find()->where(['idticket' => $idticket, 'iduser' => $tecnicoId])->first();
				if (empty($ja)) {
					$tu = $this->Ticketsusers->newEntity();
					$tu->idticket = $idticket;
					$tu->iduser = $tecnicoId;
					$tu->idempresa = $empresa;
					if (!$this->Ticketsusers->save($tu, ['atomic' => false])) {
						throw new \RuntimeException('ticketsusers:' . json_encode($tu->getErrors(), JSON_UNESCAPED_UNICODE));
					}
				}
				$oldId = (int)($ticket->idtecnico_responsavel ?? 0);
				if ($oldId > 0 && $oldId !== $tecnicoId) {
					foreach ($this->Ticketsusers->find()->where(['idticket' => $idticket, 'iduser' => $oldId])->toArray() as $ent) {
						$this->Ticketsusers->delete($ent);
					}
				}
				$mov = $this->Ticketsmovs->newEntity();
				$mov->idticket = $idticket;
				$mov->sitantiga = (int)$ticket->situacao;
				$mov->sitnova = C_TicketMovTransferencia;
				$mov->idusuario = $this->Auth->user('id');
				$mov->idempresa = $empresa;
				$mov->datetime = $agoraSql;
				$mov->observacao = $this->_ticketsmovsObservacaoLimitada(implode("\n", $obsLinhas));
				if (!$this->Ticketsmovs->save($mov, ['atomic' => false])) {
					throw new \RuntimeException('ticketsmovs:' . json_encode($mov->getErrors(), JSON_UNESCAPED_UNICODE));
				}
			});
		} catch (\Throwable $e) {
			$this->_ticketDbRollbackSafe();
			$this->_logTicketPatchRootCause('apiPatchAssignment', $e);
			$msg = $e->getMessage();
			if ($msg === 'update_ticket') {
				return $this->jsonResponse([
					'ok' => false,
					'error' => 'update_not_applied',
					'message' => 'O ticket não foi atualizado (sem linhas afetadas ou divergência com o estado atual).',
				], 422);
			}
			if ($msg === 'assignment_no_ra_column') {
				return $this->jsonResponse([
					'ok' => false,
					'error' => 'assignment_not_supported',
					'message' => 'Schema sem coluna de responsável (idtecnico_responsavel ou owner_id).',
				], 503);
			}
			if (strpos($msg, 'queues_users:') === 0 || strpos($msg, 'ticketsusers:') === 0 || strpos($msg, 'ticketsmovs:') === 0) {
				$tail = substr($msg, strpos($msg, ':') + 1);
				$decoded = json_decode($tail, true);
				$field = 'assignment';
				if (strpos($msg, 'queues_users:') === 0) {
					$field = 'queues_users';
				} elseif (strpos($msg, 'ticketsusers:') === 0) {
					$field = 'ticketsusers';
				} elseif (strpos($msg, 'ticketsmovs:') === 0) {
					$field = 'ticketsmovs';
				}

				return $this->jsonResponse([
					'ok' => false,
					'error' => 'save_failed',
					'message' => 'Validação ao gravar vínculo ou movimento do ticket.',
					'errors' => is_array($decoded) ? $decoded : ['_root' => [$tail]],
					'debug' => $this->_ticketPatchDebugStub($field, ['tecnico_id' => $tecnicoId, 'fila_id' => $filaIdBody], null),
					'detail' => $tail,
				], 422);
			}

			return $this->jsonResponse([
				'ok' => false,
				'error' => 'save_failed',
				'message' => 'Não foi possível gravar a atribuição. Tente novamente ou use Transferir.',
				'errors' => ['_root' => [$msg]],
				'debug' => $this->_ticketPatchDebugStub('assignment', ['tecnico_id' => $tecnicoId, 'fila_id' => $filaIdBody], null),
				'detail' => $msg,
			], 500);
		}
		try {
			$this->Atividades->registrar($this->Auth->user('id'), 'Tickets', 'patch_assignment', $idticket);
			$row = $this->_servicedeskTicketRowPayload($idticket);
		} catch (\Throwable $e) {
			$this->_logTicketPatchRootCause('apiPatchAssignment.pos_tx', $e);

			return $this->jsonResponse([
				'ok' => false,
				'error' => 'post_save_failed',
				'message' => 'Atribuição pode ter sido gravada, mas a resposta da grid falhou. Atualize a página.',
				'errors' => ['_root' => [$e->getMessage()]],
				'debug' => $this->_ticketPatchDebugStub('ticket_row_payload', $idticket, null),
				'detail' => $e->getMessage(),
			], 500);
		}

		return $this->jsonResponse(['ok' => true, 'ticket' => $row]);
	}

	/**
	 * PATCH /tickets/:id/status — corpo { "status": "Em execução" } ou situacao numérica; devolve linha da grid.
	 */
	public function apiPatchTicketStatus($id = null) {
		$this->request->allowMethod(['patch', 'post']);
		$this->autoRender = false;
		$idticket = (int)$id;
		if ($idticket <= 0) {
			return $this->jsonResponse(['ok' => false, 'error' => 'invalid_id'], 400);
		}
		if ((int)$this->Auth->user('role') !== 0) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$qTm = $this->Tickets->find('all')->where(['id' => $idticket]);
		$this->Abac->applyToQuery($qTm, 'Tickets', 'Tickets');
		$ticket = $qTm->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$body = $this->_jsonPatchRequestBody();
		$workflowStateDestinoId = isset($body['workflow_state_id']) ? (int)$body['workflow_state_id'] : 0;
		$situacaoNova = $body['status'] ?? $body['situacao'] ?? $body['sit'] ?? null;
		$rawStatusForDebug = $body['status'] ?? $body['situacao'] ?? $body['sit'] ?? null;
		if (($situacaoNova === null || $situacaoNova === '') && $workflowStateDestinoId <= 0) {
			return $this->_ticketInvalidStatusResponse($idticket, $rawStatusForDebug);
		}
		$mapped = null;
		if ($situacaoNova !== null && $situacaoNova !== '') {
			$mapped = $this->_mapServicedeskStatusRequestToSituacaoInt((string)$situacaoNova);
		}
		if ($mapped === null && $workflowStateDestinoId <= 0) {
			return $this->_ticketInvalidStatusResponse($idticket, $rawStatusForDebug);
		}
		$situacaoNovaInt = $mapped !== null ? (int)$mapped : (int)$ticket->situacao;
		if ($mapped !== null) {
			$allowed = [
				(int)C_TicketSituacaoPendente,
				(int)C_TicketSituacaoEmandamento,
				(int)C_TicketSituacaoResolvido,
				(int)C_TicketSituacaoFechado,
			];
			if (!in_array($situacaoNovaInt, $allowed, true)) {
				return $this->_ticketInvalidStatusResponse($idticket, $rawStatusForDebug);
			}
		}
		$sitantigaAntes = (int)$ticket->situacao;
		$empresa = (int)$this->Auth->user('idempresa');
		$workflowFeatureEnabled = $this->_ticketWorkflowEnabledForEmpresa($empresa);
		$workflowColumnReady = $this->_ticketWorkflowStateColumnReady();
		try {
			$this->Tickets->getConnection()->transactional(function () use (
				$idticket,
				$situacaoNovaInt,
				$empresa,
				$rawStatusForDebug,
				$workflowStateDestinoId,
				$workflowFeatureEnabled,
				$workflowColumnReady
			) {
				$q = $this->Tickets->find('all')->where(['Tickets.id' => $idticket, 'Tickets.idempresa' => $empresa]);
				$this->Abac->applyToQuery($q, 'Tickets', 'Tickets');
				$q = $this->_ticketApplyForUpdateLock($q);
				$ticket = $q->first();
				if (empty($ticket)) {
					throw new \UnexpectedValueException('not_found');
				}
				if ((int)($ticket->idempresa ?? 0) !== $empresa) {
					throw new \UnexpectedValueException('forbidden_view');
				}
				if (!$this->_apiTicketViewAllowed($ticket)) {
					throw new \UnexpectedValueException('forbidden_view');
				}
				$sitantiga = (int)$ticket->situacao;
				$workflowApplied = false;
				$useWorkflow = false;
				$workflowSvc = null;
				if ($workflowFeatureEnabled && $workflowColumnReady) {
					$workflowSvc = $this->_ticketWorkflowService();
					if ($workflowSvc->hasConfiguredTransitionsForEmpresa($empresa)) {
						$bootState = $workflowSvc->bootstrapStateForTicket($ticket);
						$useWorkflow = $bootState !== null;
					}
				}
				if ($useWorkflow) {
					$fromStateId = (int)$ticket->workflow_state_id;
					$targetState = null;
					if ($workflowStateDestinoId > 0) {
						$targetState = $workflowSvc->getStateById($workflowStateDestinoId);
					} else {
						$targetState = $workflowSvc->getStateBySituacao($situacaoNovaInt);
					}
					if ($targetState === null || !$workflowSvc->canTransition($empresa, $fromStateId, (int)$targetState['id'])) {
						throw new \UnexpectedValueException('invalid_transition');
					}
					$workflowSvc->applyTransition($ticket, (int)$targetState['id'], $empresa);
					$situacaoNovaInt = (int)$ticket->situacao;
					$workflowApplied = true;
				}
				if (!$workflowApplied && $situacaoNovaInt !== $sitantiga) {
					TicketAttendimentoTimerService::applyOnSituacaoChange($this->Tickets, $ticket, $sitantiga, $situacaoNovaInt);
					$ticket->situacao = $situacaoNovaInt;
				}
				$tcolsAll = $this->Tickets->getSchema()->columns();
				$this->_ticketSetDatafinalizadoPorSituacao($ticket, $situacaoNovaInt, $tcolsAll);
				if ($situacaoNovaInt === (int)C_TicketSituacaoEmandamento && $situacaoNovaInt !== $sitantiga) {
					$this->_assignTecnicoEmExecucao($ticket, $idticket, true);
				}
				if (($situacaoNovaInt == C_TicketSituacaoResolvido || $situacaoNovaInt == C_TicketSituacaoFechado) && $situacaoNovaInt !== $sitantiga) {
					$this->_ensureTecnicoResponsavelAoFechamento($ticket);
				}
				$saveFields = ['situacao'];
				foreach ($ticket->getDirty() as $f) {
					if ($f !== 'situacao' && in_array($f, $tcolsAll, true)) {
						$saveFields[] = $f;
					}
				}
				$saveFields = array_values(array_unique($saveFields));
				$saveFields = $this->_ticketFieldsComResponsavel($this->_ticketIntersectSchemaFields($saveFields));
				if (!$this->Tickets->save($ticket, ['fields' => $saveFields, 'atomic' => false])) {
					$payload = [
						'errors' => $ticket->getErrors(),
						'debug' => $this->_ticketPatchSaveDebug($ticket, 'situacao', $rawStatusForDebug, $situacaoNovaInt),
					];
					throw new \RuntimeException('ticket_validation:' . json_encode($payload, JSON_UNESCAPED_UNICODE));
				}
				$mov = $this->Ticketsmovs->newEntity();
				$mov->idticket = $idticket;
				$mov->sitantiga = $sitantiga;
				$mov->sitnova = $situacaoNovaInt;
				$mov->idusuario = $this->Auth->user('id');
				$mov->idempresa = $empresa;
				$mov->datetime = date('Y-m-d H:i:s', time());
				if (!$this->Ticketsmovs->save($mov, ['atomic' => false])) {
					throw new \RuntimeException('ticketsmovs:' . json_encode($mov->getErrors(), JSON_UNESCAPED_UNICODE));
				}
			});
		} catch (\UnexpectedValueException $e) {
			$this->_ticketDbRollbackSafe();
			$this->_logTicketPatchRootCause('apiPatchTicketStatus', $e);
			if ($e->getMessage() === 'not_found') {
				return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
			}
			if ($e->getMessage() === 'forbidden_view') {
				return $this->_ticketForbiddenPatchResponse();
			}
			if ($e->getMessage() === 'invalid_transition') {
				return $this->jsonResponse([
					'ok' => false,
					'error' => 'invalid_transition',
					'message' => 'Transição de workflow inválida para este ticket.',
				], 422);
			}

			return $this->jsonResponse(['ok' => false, 'error' => 'invalid_state', 'message' => $e->getMessage()], 400);
		} catch (\Throwable $e) {
			$this->_ticketDbRollbackSafe();
			$this->_logTicketPatchRootCause('apiPatchTicketStatus', $e);
			$msg = $e->getMessage();
			$parsed = $this->_ticketParseTicketValidationMessage($msg);
			if ($parsed !== null) {
				return $this->jsonResponse([
					'ok' => false,
					'error' => 'save_failed',
					'message' => 'Validação ao gravar o status do ticket.',
					'errors' => $parsed['errors'],
					'debug' => $parsed['debug'],
				], 422);
			}
			if (strpos($msg, 'ticketsusers:') === 0 || strpos($msg, 'ticketsmovs:') === 0) {
				$tail = substr($msg, strpos($msg, ':') + 1);
				$decoded = json_decode($tail, true);
				$field = strpos($msg, 'ticketsusers:') === 0 ? 'ticketsusers' : 'ticketsmovs';

				return $this->jsonResponse([
					'ok' => false,
					'error' => 'save_failed',
					'message' => 'Erro ao gravar vínculo ou movimento do ticket.',
					'errors' => is_array($decoded) ? $decoded : ['_root' => [$tail]],
					'debug' => $this->_ticketPatchDebugStub($field, $rawStatusForDebug, $situacaoNovaInt),
					'detail' => $tail,
				], 422);
			}

			return $this->jsonResponse([
				'ok' => false,
				'error' => 'save_failed',
				'message' => 'Erro ao gravar o status do ticket.',
				'errors' => ['_root' => [$msg]],
				'debug' => $this->_ticketPatchDebugStub('situacao', $rawStatusForDebug, $situacaoNovaInt),
				'detail' => $msg,
			], 422);
		}
		try {
			if ($situacaoNovaInt == C_TicketSituacaoPendente && $situacaoNovaInt !== $sitantigaAntes) {
				$this->email($idticket, C_TicketsAcaoPendente, null, $this->Auth->user('idempresa'));
			} elseif ($situacaoNovaInt == C_TicketSituacaoEmandamento && $situacaoNovaInt !== $sitantigaAntes) {
				$this->email($idticket, C_TicketsAcaoEmandamento, null, $this->Auth->user('idempresa'));
			} elseif ($situacaoNovaInt == C_TicketSituacaoFechado && $situacaoNovaInt !== $sitantigaAntes) {
				$this->email($idticket, C_TicketsAcaoFechado, null, $this->Auth->user('idempresa'));
			} elseif ($situacaoNovaInt == C_TicketSituacaoResolvido && $situacaoNovaInt !== $sitantigaAntes) {
				$this->email($idticket, null, null, $this->Auth->user('idempresa'));
			}
		} catch (\Throwable $e) {
			$this->_logTicketPatchRootCause('apiPatchTicketStatus.email', $e);
		}
		try {
			$this->Atividades->registrar($this->Auth->user('id'), 'Tickets', 'patch_status', $idticket);
			$row = $this->_servicedeskTicketRowPayload($idticket);
		} catch (\Throwable $e) {
			$this->_logTicketPatchRootCause('apiPatchTicketStatus.pos_tx', $e);

			return $this->jsonResponse([
				'ok' => false,
				'error' => 'post_save_failed',
				'message' => 'Situação gravada; falha ao montar a linha da grid. Atualize a página.',
				'errors' => ['_root' => [$e->getMessage()]],
				'debug' => $this->_ticketPatchDebugStub('ticket_row_payload', $idticket, null),
				'detail' => $e->getMessage(),
			], 500);
		}

		return $this->jsonResponse([
			'ok' => true,
			'ticket' => $row,
			'situacao' => (int)($row['situacao'] ?? $situacaoNovaInt),
			'situacaoLabel' => $this->_ticketSituacaoTexto($row['situacao'] ?? $situacaoNovaInt),
		]);
	}

	/**
	 * PATCH /tickets/:id/priority — { "prioridade": "Alto" } (tickets.prioridade ou severidade legada).
	 */
	public function apiPatchTicketPriority($id = null) {
		$this->request->allowMethod(['patch', 'post']);
		$this->autoRender = false;
		$idticket = (int)$id;
		if ($idticket <= 0) {
			return $this->jsonResponse(['ok' => false, 'error' => 'invalid_id'], 400);
		}
		if ((int)$this->Auth->user('role') !== 0) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$qTm = $this->Tickets->find('all')->where(['id' => $idticket]);
		$this->Abac->applyToQuery($qTm, 'Tickets', 'Tickets');
		$ticket = $qTm->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$body = $this->_jsonPatchRequestBody();
		$raw = $body['prioridade'] ?? $body['priority'] ?? null;
		if ($raw === null || $raw === '') {
			return $this->jsonResponse([
				'ok' => false,
				'error' => 'missing_prioridade',
				'message' => 'Envie prioridade ou priority no JSON.',
			], 400);
		}
		$cols = $this->Tickets->getSchema()->columns();
		$prio = $this->_normalizeTicketPrioridadeFromRequest((string)$raw);
		if ($prio === null) {
			$data = [
				'ticket_id' => $idticket,
				'user_id' => (int)$this->Auth->user('id'),
				'priority' => $raw,
			];
			$this->log('Prioridade inválida: ' . json_encode($data, JSON_UNESCAPED_UNICODE), 'warning');

			return $this->jsonResponse([
				'ok' => false,
				'error' => 'invalid_priority',
				'message' => 'Prioridade inválida. Use: Crítico, Alto, Médio, Baixo ou P1–P4.',
				'debug' => [
					'field' => 'priority',
					'attemptedValue' => $raw,
				],
			], 422);
		}
		$code = $prio['semantic'];
		$saveFields = [];
		$usePrioridade = in_array('prioridade', $cols, true);
		if ($usePrioridade) {
			$saveFields[] = 'prioridade';
		} elseif (in_array('severidade', $cols, true)) {
			$saveFields[] = 'severidade';
		} else {
			return $this->jsonResponse(['ok' => false, 'error' => 'prioridade_not_supported'], 503);
		}
		$empresa = (int)$this->Auth->user('idempresa');
		try {
			$this->Tickets->getConnection()->transactional(function () use ($idticket, $empresa, $saveFields, $code, $raw, $usePrioridade, $prio) {
				$q = $this->Tickets->find('all')->where(['Tickets.id' => $idticket, 'Tickets.idempresa' => $empresa]);
				$this->Abac->applyToQuery($q, 'Tickets', 'Tickets');
				$ticket = $q->first();
				if (empty($ticket)) {
					throw new \UnexpectedValueException('not_found');
				}
				if (!$this->_apiTicketViewAllowed($ticket)) {
					throw new \UnexpectedValueException('forbidden_view');
				}
				$persistVal = null;
				if ($usePrioridade) {
					$curPrior = strtolower(trim((string)($ticket->prioridade ?? '')));
					// P1–P4 alinhados a TicketClassificationService / sla_policies; texto legado preservado
					$usePx = ($curPrior === '' || preg_match('/^p[1-4]$/', $curPrior));
					if ($usePx) {
						$persistVal = $prio['px'];
					} else {
						$persistVal = $code;
					}
					$ticket->prioridade = $persistVal;
				} else {
					$sevMap = ['baixa' => 'baixa', 'media' => 'media', 'alta' => 'alta', 'critica' => 'urgente', 'critico' => 'urgente'];
					$persistVal = $sevMap[$code] ?? $this->_normalizeTicketSeveridade((string)$raw);
					$ticket->severidade = $persistVal;
				}
				$fieldList = $saveFields;
				if ($usePrioridade) {
					$sit = (int)($ticket->situacao ?? 0);
					$fechado = ($sit === (int)C_TicketSituacaoResolvido || $sit === (int)C_TicketSituacaoFechado);
					if (!$fechado) {
						$sla = new SlaService($this->Tickets);
						$slaCols = $sla->syncPolicyForTicket($ticket, $empresa);
						if ($slaCols !== []) {
							$fieldList = array_values(array_unique(array_merge($fieldList, $slaCols)));
						}
					}
				}
				$fieldList = $this->_ticketIntersectSchemaFields($fieldList);
				if (!$this->Tickets->save($ticket, ['fields' => $fieldList, 'atomic' => false])) {
					$primaryField = $fieldList[0] ?? ($usePrioridade ? 'prioridade' : 'severidade');
					$payload = [
						'errors' => $ticket->getErrors(),
						'debug' => array_merge(
							$this->_ticketPatchSaveDebug($ticket, $primaryField, $raw, $persistVal),
							['semanticCode' => $code],
						),
					];
					throw new \RuntimeException('ticket_validation:' . json_encode($payload, JSON_UNESCAPED_UNICODE));
				}
			});
		} catch (\UnexpectedValueException $e) {
			$this->_ticketDbRollbackSafe();
			$this->_logTicketPatchRootCause('apiPatchTicketPriority', $e);
			if ($e->getMessage() === 'not_found') {
				return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
			}
			if ($e->getMessage() === 'forbidden_view') {
				return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
			}

			return $this->jsonResponse(['ok' => false, 'error' => 'invalid_state', 'message' => $e->getMessage()], 400);
		} catch (\Throwable $e) {
			$this->_ticketDbRollbackSafe();
			$this->_logTicketPatchRootCause('apiPatchTicketPriority', $e);
			$msg = $e->getMessage();
			$parsed = $this->_ticketParseTicketValidationMessage($msg);
			if ($parsed !== null) {
				return $this->jsonResponse([
					'ok' => false,
					'error' => 'save_failed',
					'message' => 'Validação ao gravar a prioridade.',
					'errors' => $parsed['errors'],
					'debug' => $parsed['debug'],
				], 422);
			}

			return $this->jsonResponse([
				'ok' => false,
				'error' => 'save_failed',
				'message' => 'Erro ao gravar a prioridade.',
				'errors' => ['_root' => [$msg]],
				'debug' => $this->_ticketPatchDebugStub($usePrioridade ? 'prioridade' : 'severidade', $raw, $code),
				'detail' => $msg,
			], 422);
		}
		try {
			$this->Atividades->registrar($this->Auth->user('id'), 'Tickets', 'patch_priority', $idticket);
			$row = $this->_servicedeskTicketRowPayload($idticket);
		} catch (\Throwable $e) {
			$this->_logTicketPatchRootCause('apiPatchTicketPriority.pos_tx', $e);

			return $this->jsonResponse([
				'ok' => false,
				'error' => 'post_save_failed',
				'message' => 'Prioridade gravada; falha ao montar a linha da grid. Atualize a página.',
				'errors' => ['_root' => [$e->getMessage()]],
				'debug' => $this->_ticketPatchDebugStub('ticket_row_payload', $idticket, null),
				'detail' => $e->getMessage(),
			], 500);
		}

		return $this->jsonResponse(['ok' => true, 'ticket' => $row]);
	}

	/**
	 * PATCH /tickets/:id/subject — { "assunto_id": 2 } (ou assunto/assuntoCode).
	 */
	public function apiPatchTicketSubject($id = null) {
		$this->request->allowMethod(['patch', 'post']);
		$this->autoRender = false;
		$idticket = (int)$id;
		if ($idticket <= 0) {
			return $this->jsonResponse(['ok' => false, 'error' => 'invalid_id'], 400);
		}
		if ((int)$this->Auth->user('role') !== 0) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$qTm = $this->Tickets->find('all')->where(['id' => $idticket]);
		$this->Abac->applyToQuery($qTm, 'Tickets', 'Tickets');
		$ticket = $qTm->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$cols = $this->Tickets->getSchema()->columns();
		if (!in_array('assunto', $cols, true)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'assunto_not_supported'], 503);
		}
		$body = $this->_jsonPatchRequestBody();
		$raw = $body['assunto_id'] ?? $body['assunto'] ?? $body['assuntoCode'] ?? null;
		$persistVal = null;
		if (!($raw === null || $raw === '')) {
			$opts = $this->_ticketAssuntoClienteOptions();
			if (!is_array($opts) || $opts === []) {
				return $this->jsonResponse([
					'ok' => false,
					'error' => 'assunto_options_unavailable',
					'message' => 'Lista de categorias indisponível para validação.',
				], 503);
			}
			if (is_numeric($raw)) {
				$code = (int)$raw;
				if (array_key_exists($code, $opts)) {
					$persistVal = $code;
				} elseif (array_key_exists((string)$code, $opts)) {
					$persistVal = (string)$code;
				}
			} else {
				$key = (string)$raw;
				if (array_key_exists($key, $opts)) {
					$persistVal = $key;
				}
			}
			if ($persistVal === null) {
				return $this->jsonResponse([
					'ok' => false,
					'error' => 'invalid_assunto',
					'message' => 'Categoria/assunto inválido para este ticket.',
					'debug' => $this->_ticketPatchDebugStub('assunto', $raw, null),
				], 422);
			}
		}

		$empresa = (int)$this->Auth->user('idempresa');
		try {
			$this->Tickets->getConnection()->transactional(function () use ($idticket, $empresa, $persistVal, $raw) {
				$q = $this->Tickets->find('all')->where(['Tickets.id' => $idticket, 'Tickets.idempresa' => $empresa]);
				$this->Abac->applyToQuery($q, 'Tickets', 'Tickets');
				$ticket = $q->first();
				if (empty($ticket)) {
					throw new \UnexpectedValueException('not_found');
				}
				if (!$this->_apiTicketViewAllowed($ticket)) {
					throw new \UnexpectedValueException('forbidden_view');
				}
				$ticket->assunto = $persistVal;
				if (!$this->Tickets->save($ticket, ['fields' => ['assunto'], 'atomic' => false])) {
					$payload = [
						'errors' => $ticket->getErrors(),
						'debug' => $this->_ticketPatchSaveDebug($ticket, 'assunto', $raw, $persistVal),
					];
					throw new \RuntimeException('ticket_validation:' . json_encode($payload, JSON_UNESCAPED_UNICODE));
				}
			});
		} catch (\UnexpectedValueException $e) {
			$this->_ticketDbRollbackSafe();
			$this->_logTicketPatchRootCause('apiPatchTicketSubject', $e);
			if ($e->getMessage() === 'not_found') {
				return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
			}
			if ($e->getMessage() === 'forbidden_view') {
				return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
			}

			return $this->jsonResponse(['ok' => false, 'error' => 'invalid_state', 'message' => $e->getMessage()], 400);
		} catch (\Throwable $e) {
			$this->_ticketDbRollbackSafe();
			$this->_logTicketPatchRootCause('apiPatchTicketSubject', $e);
			$msg = $e->getMessage();
			$parsed = $this->_ticketParseTicketValidationMessage($msg);
			if ($parsed !== null) {
				return $this->jsonResponse([
					'ok' => false,
					'error' => 'save_failed',
					'message' => 'Validação ao gravar categoria/assunto.',
					'errors' => $parsed['errors'],
					'debug' => $parsed['debug'],
				], 422);
			}

			return $this->jsonResponse([
				'ok' => false,
				'error' => 'save_failed',
				'message' => 'Erro ao gravar categoria/assunto.',
				'errors' => ['_root' => [$msg]],
				'debug' => $this->_ticketPatchDebugStub('assunto', $raw, $persistVal),
				'detail' => $msg,
			], 422);
		}

		try {
			$this->Atividades->registrar($this->Auth->user('id'), 'Tickets', 'patch_subject', $idticket);
			$row = $this->_servicedeskTicketRowPayload($idticket);
		} catch (\Throwable $e) {
			$this->_logTicketPatchRootCause('apiPatchTicketSubject.pos_tx', $e);

			return $this->jsonResponse([
				'ok' => false,
				'error' => 'post_save_failed',
				'message' => 'Categoria gravada; falha ao montar a linha da grid. Atualize a página.',
				'errors' => ['_root' => [$e->getMessage()]],
				'debug' => $this->_ticketPatchDebugStub('ticket_row_payload', $idticket, null),
				'detail' => $e->getMessage(),
			], 500);
		}

		return $this->jsonResponse(['ok' => true, 'ticket' => $row]);
	}

	public function apiSaveTicket($idticket = null) {
		$this->request->allowMethod(['post', 'put']);
		$this->autoRender = false;
		if ((int)$this->Auth->user('role') !== 0) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$qTm = $this->Tickets->find()->where(['id' => $idticket]);
		$this->Abac->applyToQuery($qTm, 'Tickets', 'Tickets');
		$ticket = $qTm->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$body = $this->request->input('json_decode', true);
		if (!is_array($body)) {
			$body = $this->request->getData();
		}

		$saveFields = [];

		if (array_key_exists('solicitacao', $body)) {
			if ((int)$this->Auth->user('admin') !== 1) {
				return $this->jsonResponse(['ok' => false, 'error' => 'only_admin_can_edit_descricao'], 403);
			}
			$ticket->solicitacao = (string)$body['solicitacao'];
			$saveFields[] = 'solicitacao';
		}

		$descAt = null;
		if (array_key_exists('descricao_atendimento', $body)) {
			$descAt = $body['descricao_atendimento'];
		} elseif (array_key_exists('descricaoAtendimento', $body)) {
			$descAt = $body['descricaoAtendimento'];
		}
		if ($descAt !== null) {
			$ticket->descricao_atendimento = (string)$descAt;
			$saveFields[] = 'descricao_atendimento';
		}

		if ($saveFields === []) {
			return $this->jsonResponse(['ok' => false, 'error' => 'missing_fields'], 400);
		}

		if ($this->Tickets->save($ticket, ['fields' => $saveFields])) {
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $idticket);
			return $this->jsonResponse(['ok' => true]);
		}
		return $this->jsonResponse(['ok' => false, 'error' => 'save_failed'], 500);
	}

	/**
	 * ID do usuário responsável no ticket (idtecnico_responsavel ou owner_id).
	 *
	 * @param \Cake\Datasource\EntityInterface|object $ticket
	 * @return int 0 se ausente
	 */
	protected function _responsavelUserIdFromTicket($ticket) {
		try {
			$cols = $this->Tickets->getSchema()->columns();
		} catch (\Throwable $e) {
			return 0;
		}
		$rid = 0;
		if (in_array('idtecnico_responsavel', $cols, true)) {
			$v = is_object($ticket) && method_exists($ticket, 'get') ? $ticket->get('idtecnico_responsavel') : (isset($ticket->idtecnico_responsavel) ? $ticket->idtecnico_responsavel : null);
			$rid = (int)($v ?? 0);
		}
		if ($rid <= 0 && in_array('owner_id', $cols, true)) {
			$v = is_object($ticket) && method_exists($ticket, 'get') ? $ticket->get('owner_id') : (isset($ticket->owner_id) ? $ticket->owner_id : null);
			$rid = (int)($v ?? 0);
		}

		return $rid > 0 ? $rid : 0;
	}

	/**
	 * Nome para exibir do técnico responsável (ranking PGM usa o mesmo vínculo).
	 *
	 * @param \Cake\Datasource\EntityInterface|object $ticket
	 * @return string|null null se não houver responsável
	 */
	protected function _tecnicoResponsavelDisplayLabel($ticket) {
		$rid = $this->_responsavelUserIdFromTicket($ticket);
		if ($rid <= 0) {
			return null;
		}
		$u = $this->Users->find()->select(['id', 'name', 'username'])->where(['id' => $rid])->first();
		if (!$u) {
			return 'Usuário #' . $rid;
		}
		$nm = trim((string)$u->name);
		if ($nm !== '') {
			return $nm;
		}
		$un = trim((string)$u->username);

		return $un !== '' ? $un : ('#' . $rid);
	}

	/**
	 * Mapa id usuário => nome para lista de tickets (evita N+1).
	 *
	 * @param array<int,\Cake\Datasource\EntityInterface|object> $tickets
	 * @return array<int,string>
	 */
	protected function _responsaveisMapForTicketEntities(array $tickets) {
		$ids = [];
		foreach ($tickets as $t) {
			$rid = $this->_responsavelUserIdFromTicket($t);
			if ($rid > 0) {
				$ids[] = $rid;
			}
		}
		$ids = array_values(array_unique($ids));
		if ($ids === []) {
			return [];
		}
		$rows = $this->Users->find()->select(['id', 'name', 'username'])->where(['id IN' => $ids])->all();
		$map = [];
		foreach ($rows as $u) {
			$rid = (int)$u->id;
			$nm = trim((string)$u->name);
			$map[$rid] = $nm !== '' ? $nm : (trim((string)$u->username) !== '' ? $u->username : ('#' . $rid));
		}

		return $map;
	}

	/**
	 * Preenche as variáveis de view necessárias para o fragmento do painel esquerdo (HTMX).
	 * Usado quando as ações do timer respondem com atualização parcial em vez de redirect.
	 */
	protected function _setEditPanelLeftVars($idticket) {
		$qT = $this->Tickets->findById($idticket)->contain(['users']);
		$this->Abac->applyToQuery($qT, 'Tickets', 'Tickets');
		$ticket = $qT->first();
		if (!$ticket) return;
		$qTu = $this->Ticketsusers->find('all', ['contain' => ['users'], 'fields' => ['Users.name', 'Users.id', 'Ticketsusers.id']])
			->where(['idticket' => $idticket])->autoFields(true);
		$this->Abac->applyToQuery($qTu, 'Ticketsusers', 'Ticketsusers');
		$ticketsusers = $qTu->toArray();
		$solicitante = $this->Users->findById($ticket->idsolicitante)->select(['name'])->first();
		$clienteRow = $this->Clientes->findById($ticket->idcliente)->select(['razaosocial', 'nomefantasia', 'nome', 'tipo', 'idempresa'])->first();
		$clienteNome = $clienteRow && $clienteRow->tipo == C_ClientesTipoFisica ? $clienteRow->nome : ($clienteRow ? $clienteRow->razaosocial : '');
		$ordem = $this->Ordensservico->findByIdticket($idticket)->first();
		$ordem = empty($ordem) ? false : $ordem->id;

		$timerAtivo = null;
		$timerPausado = false;
		$timerPausadoElapsedTexto = null;
		try {
			$this->loadModel('AtendimentoTimer');
			$tUserCol = $this->_atendimentoTimerUserColumn();
			$timerAtivo = $this->AtendimentoTimer->find()
				->where(['idticket' => $idticket, $tUserCol => $this->Auth->user('id'), 'hora_fim IS' => null])
				->orderDesc('id')->first();
			if ($timerAtivo) {
				$horaPausa = $timerAtivo->get('hora_pausa');
				$timerPausado = !empty($horaPausa);
				if ($timerPausado) {
					$hi = $timerAtivo->get('hora_inicio');
					$hp = $timerAtivo->get('hora_pausa');
					if ($hi && $hp) {
						$tIni = is_object($hi) && method_exists($hi, 'getTimestamp') ? $hi->getTimestamp() : strtotime($hi);
						$tPausa = is_object($hp) && method_exists($hp, 'getTimestamp') ? $hp->getTimestamp() : strtotime($hp);
						$segundos = max(0, (int)($tPausa - $tIni));
						$timerPausadoElapsedTexto = sprintf('%02d:%02d:%02d', (int)floor($segundos / 3600), (int)floor(($segundos % 3600) / 60), $segundos % 60);
					}
				}
			}
		} catch (\Throwable $e) {}
		$minutosTicket = 0;
		$minutosClienteMes = 0;
		$horasContratoTexto = null;
		try {
			$inicioMes = (new \DateTime('first day of this month', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
			$fimMes = (new \DateTime('last day of this month', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
			$minutosTicket = $this->Ticketshoras->minutosTicket($idticket, '2000-01-01', '2099-12-31');
			$minutosClienteMes = $this->Ticketshoras->minutosCliente($ticket->idcliente, $inicioMes, $fimMes);
		} catch (\Throwable $e) {}
		try {
			$table = \Cake\ORM\TableRegistry::getTableLocator()->get('ContratosHoras');
			$qCh = $table->find()->where(['idcliente' => $ticket->idcliente]);
			$this->Abac->applyToQuery($qCh, 'ContratosHoras', 'ContratosHoras');
			$contrato = $qCh->first();
			if (!$contrato) {
				$contrato = $table->find()->where(['idcliente' => $ticket->idcliente])->first();
			}
			if ($contrato) {
				if ($contrato->get('horas_contratadas') !== null && $contrato->get('saldo') !== null) {
					$hContratadas = (float)str_replace(',', '.', $contrato->get('horas_contratadas'));
					$saldoH = (float)str_replace(',', '.', $contrato->get('saldo'));
					$horasContratoTexto = number_format($hContratadas, 2, ',', '.') . ' h contratadas; saldo: ' . number_format(max(0, $saldoH), 2, ',', '.') . ' h';
				} elseif ($contrato->get('horas_contratadas') !== null && $contrato->get('saldo_horas') !== null) {
					$hContratadas = (float)str_replace(',', '.', $contrato->get('horas_contratadas'));
					$saldoH = (float)str_replace(',', '.', $contrato->get('saldo_horas'));
					$horasContratoTexto = number_format($hContratadas, 2, ',', '.') . ' h contratadas; saldo: ' . number_format(max(0, $saldoH), 2, ',', '.') . ' h';
				} elseif ($contrato->get('horas_contratadas') !== null && $contrato->get('horas_consumidas') !== null) {
					$hContratadas = (float)str_replace(',', '.', $contrato->get('horas_contratadas'));
					$hConsumidas = (float)str_replace(',', '.', $contrato->get('horas_consumidas'));
					$saldoH = max(0, $hContratadas - $hConsumidas);
					$horasContratoTexto = number_format($hContratadas, 2, ',', '.') . ' h contratadas; saldo: ' . number_format($saldoH, 2, ',', '.') . ' h';
				} elseif ($contrato->get('minutos_contratados') !== null && $contrato->get('minutos_consumidos') !== null) {
					$saldoContratoMinutos = (int)$contrato->get('minutos_contratados') - (int)$contrato->get('minutos_consumidos');
					$horasContratoTexto = number_format((int)$contrato->get('minutos_contratados') / 60, 1, ',', '.') . ' h contratadas; saldo: ' . number_format(max(0, $saldoContratoMinutos) / 60, 1, ',', '.') . ' h';
				} elseif ($contrato->get('saldo_minutos') !== null) {
					$horasContratoTexto = 'Saldo: ' . number_format((int)$contrato->get('saldo_minutos') / 60, 1, ',', '.') . ' h';
				} elseif ($contrato->get('horas_contratadas') !== null) {
					$horasContratoTexto = number_format((float)str_replace(',', '.', $contrato->get('horas_contratadas')), 2, ',', '.') . ' h contratadas';
				} elseif ($contrato->get('saldo') !== null) {
					$horasContratoTexto = 'Saldo: ' . number_format(max(0, (float)str_replace(',', '.', $contrato->get('saldo'))), 2, ',', '.') . ' h';
				}
			}
		} catch (\Throwable $e) {}

		$tecnicoResponsavelLabel = $this->_tecnicoResponsavelDisplayLabel($ticket);
		$this->set(compact('ticket', 'ticketsusers', 'ordem', 'timerAtivo', 'timerPausado', 'timerPausadoElapsedTexto', 'minutosTicket', 'minutosClienteMes', 'horasContratoTexto', 'tecnicoResponsavelLabel'));
		$this->set('cliente', $clienteNome);
		$this->set('solicitante', $solicitante ? $solicitante->name : null);
	}

	public function apiTimeline($idticket = null) {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		$ticket = $this->Tickets->find()->where(['Tickets.id' => $idticket]);
		$this->Abac->applyToQuery($ticket, 'Tickets', 'Tickets');
		$ticket = $ticket->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$pack = TicketServiceDeskApiService::buildTimelineRows($this, $ticket);

		return $this->jsonResponse(['ok' => true, 'events' => $pack->rows]);
	}

	public function apiValidateGeolocation($idticket = null) {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		$ticket = $this->Tickets->find()->where(['Tickets.id' => $idticket])->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$body = $this->request->input('json_decode', true);
		if (!is_array($body)) {
			$body = $this->request->getData();
		}
		$r = $this->_timerValidateGeoInicio($ticket, is_array($body) ? $body : []);
		if (empty($r['ok']) || $r['ok'] !== true) {
			return $this->jsonResponse($r, 400);
		}
		$out = ['ok' => true, 'message' => 'Dentro do raio permitido.'];
		if (isset($r['distanceM'])) {
			$out['distanceM'] = $r['distanceM'];
		}

		return $this->jsonResponse($out);
	}

	public function apiTicketSignature($idticket = null) {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		$ticket = $this->Tickets->find()->where(['Tickets.id' => $idticket])->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$body = $this->request->input('json_decode', true);
		if (!is_array($body)) {
			$body = $this->request->getData();
		}
		$raw = (string)($body['image'] ?? $body['data'] ?? '');
		$raw = preg_replace('#^data:image/[^;]+;base64,#', '', $raw);
		$bin = base64_decode($raw, true);
		if ($bin === false || strlen($bin) < 10) {
			return $this->jsonResponse(['ok' => false, 'error' => 'invalid_image'], 400);
		}
		$maxSig = 2621440;
		if (strlen($bin) > $maxSig) {
			return $this->jsonResponse(['ok' => false, 'error' => 'image_too_large'], 413);
		}
		$emp = (int)$this->Auth->user('idempresa');
		$dir = $this->dirAnexos($emp, (int)$idticket);
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
		$fn = 'assinatura_' . time() . '.png';
		$path = $dir . DS . $fn;
		if (file_put_contents($path, $bin) === false) {
			return $this->jsonResponse(['ok' => false, 'error' => 'write_failed'], 500);
		}
		$rel = 'arquivos/tickets/' . $emp . '/' . (int)$idticket . '/' . $fn;
		$te = $this->TicketEvents;
		$te->save($te->newEntity([
			'idempresa' => $emp,
			'ticket_id' => (int)$idticket,
			'user_id' => (int)$this->Auth->user('id'),
			'type' => 'signature',
			'description' => 'Assinatura digital no encerramento',
			'attachment' => $rel,
			'created' => \Cake\I18n\Time::now(),
		], ['validate' => false]), ['checkRules' => false, 'validate' => false, 'skipBillingClassify' => true]);

		return $this->jsonResponse(['ok' => true, 'url' => '/' . str_replace('\\', '/', $rel)]);
	}

	public function apiAddTicketProduct($idticket = null) {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		$ticket = $this->Tickets->find()->where(['Tickets.id' => $idticket])->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$body = $this->request->input('json_decode', true);
		if (!is_array($body)) {
			$body = $this->request->getData();
		}
		$pid = (int)($body['produto_id'] ?? $body['produtoId'] ?? 0);
		$q = isset($body['quantidade']) ? (float)$body['quantidade'] : 0;
		if ($pid <= 0 || $q <= 0) {
			return $this->jsonResponse(['ok' => false, 'error' => 'invalid_params'], 400);
		}
		$p = $this->Produtos->find()->where(['id' => $pid, 'idempresa' => (int)$this->Auth->user('idempresa')])->first();
		if (!$p) {
			return $this->jsonResponse(['ok' => false, 'error' => 'produto_not_found'], 404);
		}
		$tipoProduto = (int)($p->get('tipo') ?? 1);
		$cols = $this->Produtos->getSchema()->columns();
		$custo = in_array('vlcusto', $cols, true) ? $p->get('vlcusto') : null;
		$preco = in_array('vlunitario', $cols, true) ? $p->get('vlunitario') : null;
		$valorInformado = isset($body['valor_unitario']) ? (float)$body['valor_unitario'] : null;
		if ($valorInformado !== null && $valorInformado >= 0) {
			$preco = $valorInformado;
		}
		$emp = (int)$this->Auth->user('idempresa');
		$hasEstoque = in_array('estoque_atual', $cols, true);
		$confirmZeroStock = !empty($body['confirm_zero_stock']);
		$confirmOverStock = !empty($body['confirm_over_stock']);
		$estoqueAtual = $hasEstoque ? (float)($p->get('estoque_atual') ?? 0) : null;
		if ($tipoProduto === 1 && $hasEstoque) {
			if ($estoqueAtual <= 0 && !$confirmZeroStock) {
				return $this->jsonResponse([
					'ok' => false,
					'error' => 'confirm_zero_stock',
					'message' => 'Atenção: este item está com estoque zerado. Deseja adicionar mesmo assim?',
				], 409);
			}
			if ($q > $estoqueAtual && !$confirmOverStock) {
				return $this->jsonResponse([
					'ok' => false,
					'error' => 'confirm_over_stock',
					'message' => 'A quantidade informada é maior que o estoque disponível. Deseja continuar mesmo assim?',
				], 409);
			}
		}
		$conn = ConnectionManager::get('default');
		try {
			$out = $conn->transactional(function () use ($conn, $hasEstoque, $q, $pid, $emp, $idticket, $custo, $preco, $tipoProduto) {
				if ($hasEstoque && $tipoProduto === 1) {
					$st = $conn->execute(
						'UPDATE produtos SET estoque_atual = COALESCE(estoque_atual, 0) - :q WHERE id = :id AND idempresa = :eid',
						['q' => $q, 'id' => $pid, 'eid' => $emp]
					);
					$n = method_exists($st, 'rowCount') ? (int)$st->rowCount() : 0;
					if ($n < 1) {
						throw new \RuntimeException('produto_update_failed');
					}
				}
				$tp = $this->TicketProducts->newEntity([
					'idempresa' => $emp,
					'ticket_id' => (int)$idticket,
					'produto_id' => $pid,
					'quantidade' => $q,
					'custo_unitario' => $custo,
					'preco_unitario' => $preco,
					'user_id' => (int)$this->Auth->user('id'),
				], ['validate' => false]);
				if (!$this->TicketProducts->save($tp, ['_stockDeducted' => true])) {
					throw new \RuntimeException('save_failed');
				}

				return (int)$tp->id;
			});
		} catch (\RuntimeException $e) {
			if ($e->getMessage() === 'save_failed') {
				return $this->jsonResponse(['ok' => false, 'error' => 'save_failed'], 500);
			}
			if ($e->getMessage() === 'produto_update_failed') {
				return $this->jsonResponse(['ok' => false, 'error' => 'produto_update_failed'], 500);
			}
			throw $e;
		}

		return $this->jsonResponse(['ok' => true, 'id' => $out]);
	}

	public function apiTicketProductSearch($idticket = null) {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		$ticket = $this->Tickets->find()->where(['Tickets.id' => $idticket])->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$eid = (int)$this->Auth->user('idempresa');
		$tipoParam = strtolower(trim((string)$this->request->getQuery('tipo')));
		$sCodProduto = trim((string)$this->request->getQuery('sCodProduto'));
		$sDescricao = trim((string)$this->request->getQuery('sDescricao'));
		if ($sDescricao === '') {
			$sDescricao = trim((string)$this->request->getQuery('q'));
		}
		$apenasComSaldoRaw = strtolower(trim((string)$this->request->getQuery('apenasComSaldo', '0')));
		$bApenasComSaldo = in_array($apenasComSaldoRaw, ['1', 'true', 't', 'yes'], true);

		$produtosErp = [];
		try {
			$empresa = $this->Empresas->get($eid);
			$produtosErp = $this->loadErpEstoqueBaseForTicketSearch(
				(string)$empresa->urlerp,
				$bApenasComSaldo,
				$sCodProduto,
				$sDescricao
			);
		} catch (\Throwable $e) {
			// Evita 500 sem JSON no modal: mantém erro controlado.
			$this->safeTicketProductSearchLog('error', 'apiTicketProductSearch SOAP: ' . $e->getMessage());
			return $this->jsonResponse(['ok' => false, 'error' => 'erp_estoque_failed', 'message' => $e->getMessage()], 502);
		}

		$codigos = [];
		foreach ($produtosErp as $reg) {
			$codigo = trim((string)($reg->sCodProduto ?? ''));
			if ($codigo !== '') {
				$codigos[$codigo] = true;
			}
		}
		$localByCodigo = [];
		if (!empty($codigos)) {
			$cols = $this->Produtos->getSchema()->columns();
			$select = ['id', 'codigo'];
			if (in_array('tipo', $cols, true)) {
				$select[] = 'tipo';
			}
			if (in_array('vlunitario', $cols, true)) {
				$select[] = 'vlunitario';
			}
			if (in_array('vlcusto', $cols, true)) {
				$select[] = 'vlcusto';
			}
			if (in_array('ativo', $cols, true)) {
				$select[] = 'ativo';
			}
			$rows = $this->Produtos->find()
				->select($select)
				->where(['idempresa' => $eid, 'codigo IN' => array_keys($codigos)])
				->all();
			foreach ($rows as $row) {
				$ck = trim((string)$row->get('codigo'));
				if ($ck !== '') {
					$localByCodigo[$ck] = $row;
				}
			}
		}

		$tipoFiltro = null;
		if ($tipoParam === 'produto') {
			$tipoFiltro = 'produto';
		} elseif ($tipoParam === 'servico' || $tipoParam === 'serviço') {
			$tipoFiltro = 'servico';
		}
		$list = [];
		foreach ($produtosErp as $reg) {
			if (!is_object($reg)) {
				continue;
			}
			$codigo = trim((string)($reg->sCodProduto ?? ''));
			$local = $codigo !== '' ? ($localByCodigo[$codigo] ?? null) : null;
			$locArr = $local ? $local->toArray() : [];
			$tipoNum = array_key_exists('tipo', $locArr) ? (int)$locArr['tipo'] : 1;
			$tipo = $tipoNum === 2 ? 'servico' : 'produto';
			if ($tipoFiltro !== null && $tipoFiltro !== $tipo) {
				continue;
			}
			$estoque = $tipo === 'produto' ? (float)($reg->nQtdeAtual ?? 0) : null;
			$precoVenda = (float)($reg->nPrecoVenda ?? 0);
			$precoCusto = isset($reg->nPrecoCusto) ? (float)$reg->nPrecoCusto : null;
			$valorPadrao = $precoVenda;
			if ($local !== null && array_key_exists('vlunitario', $locArr) && $locArr['vlunitario'] !== null && (string)$locArr['vlunitario'] !== '') {
				$valorPadrao = (float)$locArr['vlunitario'];
			}
			$list[] = [
				'id' => $local ? (int)$local->get('id') : 0,
				'tipo' => $tipo,
				'codigo' => $codigo,
				'descricao' => (string)($reg->sDescProduto ?? ''),
				'valor' => $valorPadrao,
				'quantidade_atual' => $estoque,
				'preco_custo' => $precoCusto,
				'preco_venda' => $precoVenda,
				'estoque' => $estoque,
				'tem_cadastro_portal' => $local !== null,
			];
		}
		// Serviços não vêm do GetEstoqueProdutos; completa via cadastro local.
		if ($tipoFiltro !== 'produto') {
			$svcQ = $this->Produtos->find()
				->select(['id', 'codigo', 'descricao', 'nome', 'vlunitario', 'vlcusto', 'tipo'])
				->where(['idempresa' => $eid, 'tipo' => 2]);
			if ($sCodProduto !== '') {
				$svcQ->where(['codigo LIKE' => '%' . $sCodProduto . '%']);
			}
			if ($sDescricao !== '') {
				$svcQ->where(['OR' => [
					'descricao LIKE' => '%' . $sDescricao . '%',
					'nome LIKE' => '%' . $sDescricao . '%',
				]]);
			}
			foreach ($svcQ->all() as $svc) {
				$sid = (int)$svc->get('id');
				$exists = false;
				foreach ($list as $item) {
					if ((int)($item['id'] ?? 0) === $sid && (string)($item['tipo'] ?? '') === 'servico') {
						$exists = true;
						break;
					}
				}
				if ($exists) {
					continue;
				}
				$valorServico = $svc->get('vlunitario') !== null ? (float)$svc->get('vlunitario') : 0.0;
				$list[] = [
					'id' => $sid,
					'tipo' => 'servico',
					'codigo' => trim((string)$svc->get('codigo')),
					'descricao' => (string)($svc->get('descricao') ?: $svc->get('nome') ?: 'Serviço'),
					'valor' => $valorServico,
					'quantidade_atual' => null,
					'preco_custo' => $svc->get('vlcusto') !== null ? (float)$svc->get('vlcusto') : null,
					'preco_venda' => $valorServico,
					'estoque' => null,
					'tem_cadastro_portal' => true,
				];
			}
		}
		if ($sCodProduto !== '') {
			$needle = trim((string)$sCodProduto);
			$list = array_values(array_filter($list, function ($item) use ($needle) {
				$cod = trim((string)($item['codigo'] ?? ''));
				return $cod !== '' && mb_stripos($cod, $needle, 0, 'UTF-8') !== false;
			}));
		}

		return $this->jsonResponse(['ok' => true, 'items' => $list], 200);
	}

	private function loadErpEstoqueBaseForTicketSearch(string $erpUrl, bool $bApenasComSaldo, string $sCodProduto, string $sDescricao): array {
		$soapprodutos = ErpGridUrl::wsdl($erpUrl);
		$codigo = trim($sCodProduto);
		if ($codigo === '0') {
			$codigo = '';
		}

		$produtos = $this->runTicketSoapBuffered(function () use ($soapprodutos, $bApenasComSaldo, $codigo, $sDescricao) {
			$soap = new CakeSoap(['wsdl' => $soapprodutos]);
			if ($soap === null) {
				throw new \RuntimeException('soap_client_not_initialized');
			}
			$response = $soap->sendRequest('GetEstoqueProdutos', [
				'Data' => [
					'iFilial' => C_Filial,
					'sChave' => C_ChaveAcesso,
					'bApenasComSaldo' => $bApenasComSaldo,
					'sCodProduto' => $codigo !== '' ? $codigo : null,
					'sDescricao' => $sDescricao !== '' ? $sDescricao : null,
				]
			]);
			$result = $response->GetEstoqueProdutosResult ?? null;
			$lista = ($result && isset($result->tWsProdutosEstoque)) ? $result->tWsProdutosEstoque : null;
			if ($lista === null) {
				return [];
			}
			if (!is_array($lista)) {
				$lista = [$lista];
			}
			return array_values(array_filter($lista, function ($item) {
				return is_object($item);
			}));
		});

		usort($produtos, function ($a, $b) {
			$descA = $a->sDescProduto ?? '';
			$descB = $b->sDescProduto ?? '';
			if ($descA === $descB) {
				return 0;
			}
			return ($descA < $descB) ? -1 : 1;
		});

		return $this->applyDescricaoSearchRuleToTicketProducts($produtos, $sDescricao);
	}

	private function applyDescricaoSearchRuleToTicketProducts(array $produtos, string $sDescricao): array {
		$busca = trim($sDescricao);
		if ($busca === '') {
			return $produtos;
		}
		$buscaNorm = $this->normalizeTicketSearchText($busca);
		if ($buscaNorm === '') {
			return $produtos;
		}
		$tokens = preg_split('/\s+/', $buscaNorm, -1, PREG_SPLIT_NO_EMPTY);
		$stop = ['de', 'da', 'do', 'das', 'dos', 'e', 'em', 'com', 'para', 'por', 'a', 'o', 'as', 'os'];
		$tokens = array_values(array_filter($tokens, function ($t) use ($stop) {
			return mb_strlen($t) >= 2 && !in_array($t, $stop, true);
		}));
		if (empty($tokens)) {
			return $produtos;
		}
		$filtrados = array_filter($produtos, function ($p) use ($tokens) {
			$descNorm = $this->normalizeTicketSearchText((string)($p->sDescProduto ?? ''));
			foreach ($tokens as $tk) {
				if (mb_strpos($descNorm, $tk) === false) {
					return false;
				}
			}
			return true;
		});
		usort($filtrados, function ($a, $b) use ($buscaNorm, $tokens) {
			$da = $this->normalizeTicketSearchText((string)($a->sDescProduto ?? ''));
			$db = $this->normalizeTicketSearchText((string)($b->sDescProduto ?? ''));
			$score = function ($desc) use ($buscaNorm, $tokens) {
				$s = 0;
				if ($desc === $buscaNorm) {
					$s += 100;
				}
				if (mb_strpos($desc, $buscaNorm) === 0) {
					$s += 50;
				}
				foreach ($tokens as $tk) {
					$pos = mb_strpos($desc, $tk);
					if ($pos === 0) {
						$s += 12;
					} elseif ($pos !== false) {
						$s += 5;
					}
				}
				return $s;
			};
			$sa = $score($da);
			$sb = $score($db);
			if ($sa === $sb) {
				return strcmp($da, $db);
			}
			return ($sa > $sb) ? -1 : 1;
		});
		return array_values($filtrados);
	}

	private function normalizeTicketSearchText(string $txt): string {
		$txt = mb_strtolower(trim((string)$txt), 'UTF-8');
		$txt = strtr($txt, [
			'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
			'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
			'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
			'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
			'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
			'ç' => 'c',
		]);
		$txt = preg_replace('/[^a-z0-9\s\-]/u', ' ', $txt);
		$txt = preg_replace('/\s+/', ' ', $txt);
		return trim((string)$txt);
	}

	private function runTicketSoapBuffered(callable $fn) {
		ob_start();
		try {
			$result = $fn();
		} catch (\Throwable $e) {
			$this->discardTicketSoapBuffer();
			throw $e;
		}
		$this->discardTicketSoapBuffer();
		return $result;
	}

	private function discardTicketSoapBuffer() {
		$buf = ob_get_clean();
		if ($buf !== false && trim($buf) !== '') {
			$this->safeTicketProductSearchLog('warning', 'apiTicketProductSearch SOAP output suprimido: ' . trim($buf));
		}
	}

	private function safeTicketProductSearchLog(string $level, string $message): void {
		try {
			$this->log($message, $level);
		} catch (\Throwable $ignored) {
			@error_log($message);
		}
	}

	public function apiAddEvidencePhoto($idticket = null) {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		$ticket = $this->Tickets->find()->where(['Tickets.id' => $idticket])->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$body = $this->request->input('json_decode', true);
		if (!is_array($body)) {
			$body = $this->request->getData();
		}
		$raw = (string)($body['image'] ?? '');
		$raw = preg_replace('#^data:image/[^;]+;base64,#', '', $raw);
		$bin = base64_decode($raw, true);
		if ($bin === false) {
			return $this->jsonResponse(['ok' => false, 'error' => 'invalid_image'], 400);
		}
		$maxEvid = 5242880;
		if (strlen($bin) > $maxEvid) {
			return $this->jsonResponse(['ok' => false, 'error' => 'image_too_large'], 413);
		}
		$emp = (int)$this->Auth->user('idempresa');
		$dir = $this->dirAnexos($emp, (int)$idticket);
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
		$fn = 'evidencia_' . time() . '.png';
		$path = $dir . DS . $fn;
		file_put_contents($path, $bin);
		$rel = 'arquivos/tickets/' . $emp . '/' . (int)$idticket . '/' . $fn;
		$caption = (string)($body['caption'] ?? $body['legenda'] ?? '');
		$this->TicketEvents->save($this->TicketEvents->newEntity([
			'idempresa' => $emp,
			'ticket_id' => (int)$idticket,
			'user_id' => (int)$this->Auth->user('id'),
			'type' => 'technical_report',
			'description' => $caption !== '' ? $caption : 'Evidência fotográfica',
			'attachment' => $rel,
			'metadata' => ['caption' => $caption],
			'created' => \Cake\I18n\Time::now(),
		], ['validate' => false]), ['checkRules' => false, 'validate' => false, 'skipBillingClassify' => true]);

		return $this->jsonResponse(['ok' => true, 'url' => '/' . str_replace('\\', '/', $rel)]);
	}

	public function apiPdfTicketOs($idticket = null) {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		$ticket = $this->Tickets->find()->where(['Tickets.id' => $idticket])->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		if (!class_exists(\Mpdf\Mpdf::class)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'mpdf_missing'], 500);
		}
		$pack = TicketServiceDeskApiService::buildTimelineRows($this, $ticket);
		$sig = null;
		$sigDataUri = null;
		if ($this->TicketEvents) {
			$se = $this->TicketEvents->find()
				->where(['ticket_id' => (int)$idticket, 'type' => 'signature'])
				->orderDesc('id')
				->first();
			if ($se && (string)($se->get('attachment') ?? '') !== '') {
				$sig = (string)$se->get('attachment');
				$abs = WWW_ROOT . str_replace(['/', '\\'], DS, ltrim($sig, '/\\'));
				if (is_file($abs) && is_readable($abs)) {
					$raw = @file_get_contents($abs);
					if ($raw !== false) {
						$sigDataUri = 'data:image/png;base64,' . base64_encode($raw);
					}
				}
			}
		}
		$this->viewBuilder()->setTemplatePath('Servicedesk');
		$this->viewBuilder()->setTemplate('pdf_os');
		$this->viewBuilder()->setLayout(false);
		$this->set('ticket', $ticket);
		$this->set('idticket', (int)$idticket);
		$this->set('timeline', $pack->rows);
		$this->set('signatureRelPath', $sig);
		$this->set('signatureDataUri', $sigDataUri);
		$view = $this->createView();
		$html = $view->render();
		$tmp = TMP . 'mpdf' . DS;
		if (!is_dir($tmp)) {
			@mkdir($tmp, 0775, true);
		}
		$mpdf = new \Mpdf\Mpdf(['tempDir' => $tmp]);
		$mpdf->SetTitle('OS ' . (int)$idticket);
		$mpdf->WriteHTML($html);
		$pdf = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

		return $this->response
			->withType('application/pdf')
			->withHeader('Content-Disposition', 'inline; filename="os-' . (int)$idticket . '.pdf"')
			->withStringBody($pdf);
	}

	public function apiPdfLaudo($idticket = null) {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		$rid = (int)($this->request->getQuery('reportId') ?? 0);
		$ticket = $this->Tickets->find()->where(['Tickets.id' => $idticket])->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$rep = null;
		if ($rid > 0) {
			$rep = $this->TechnicalReports->find()->where(['id' => $rid, 'ticket_id' => (int)$idticket])->first();
		}
		if (empty($rep)) {
			$rep = $this->TechnicalReports->find()->where(['ticket_id' => (int)$idticket])->orderDesc('id')->first();
		}
		if (empty($rep)) {
			$emp = (int)$this->Auth->user('idempresa');
			$rep = $this->TechnicalReports->newEntity([
				'idempresa' => $emp,
				'ticket_id' => (int)$idticket,
				'conclusao_tecnica' => '',
			], ['validate' => false]);
			$this->TechnicalReports->save($rep, ['checkRules' => false]);
		}
		$check = $this->TicketChecklists->find()->where(['technical_report_id' => (int)$rep->id])->order(['sort_order' => 'ASC', 'id' => 'ASC'])->all()->toList();
		$evidenceUris = [];
		$teRows = $this->TicketEvents->find()
			->where(['ticket_id' => (int)$idticket, 'type' => 'technical_report', 'idempresa' => (int)$this->Auth->user('idempresa')])
			->order(['id' => 'ASC'])
			->all();
		foreach ($teRows as $ter) {
			$at = (string)($ter->get('attachment') ?? '');
			if ($at === '') {
				continue;
			}
			$abs = WWW_ROOT . str_replace(['/', '\\'], DS, ltrim($at, '/\\'));
			if (is_file($abs) && is_readable($abs)) {
				$raw = @file_get_contents($abs);
				if ($raw !== false) {
					$evidenceUris[] = 'data:image/png;base64,' . base64_encode($raw);
				}
			}
		}
		$this->viewBuilder()->setTemplatePath('Servicedesk');
		$this->viewBuilder()->setTemplate('pdf_laudo');
		$this->viewBuilder()->setLayout(false);
		$this->set('ticket', $ticket);
		$this->set('report', $rep);
		$this->set('checklist', $check);
		$this->set('evidenceUris', $evidenceUris);
		$view = $this->createView();
		$html = $view->render();
		if (!class_exists(\Mpdf\Mpdf::class)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'mpdf_missing'], 500);
		}
		$tmp = TMP . 'mpdf' . DS;
		if (!is_dir($tmp)) {
			@mkdir($tmp, 0775, true);
		}
		$mpdf = new \Mpdf\Mpdf(['tempDir' => $tmp]);
		$mpdf->SetTitle('Laudo');
		$mpdf->WriteHTML($html);
		$pdf = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

		return $this->response
			->withType('application/pdf')
			->withHeader('Content-Disposition', 'inline; filename="laudo-' . (int)$idticket . '.pdf"')
			->withStringBody($pdf);
	}

	public function apiTicketMessages($idticket = null) {
		$this->request->allowMethod(['get', 'post']);
		$this->autoRender = false;
		$ticket = $this->Tickets->find()->where(['Tickets.id' => $idticket]);
		$this->Abac->applyToQuery($ticket, 'Tickets', 'Tickets');
		$ticket = $ticket->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$emp = (int)$this->Auth->user('idempresa');
		$tables = ConnectionManager::get('default')->getSchemaCollection()->listTables();
		if (!in_array('ticket_messages', $tables, true)) {
			if ($this->request->is('get')) {
				return $this->jsonResponse(['ok' => true, 'messages' => []]);
			}

			return $this->jsonResponse(['ok' => false, 'error' => 'table_missing'], 503);
		}
		if ($this->request->is('get')) {
			$rows = $this->TicketMessages->find()
				->where(['ticket_id' => (int)$idticket, 'idempresa' => $emp])
				->order(['created' => 'ASC'])
				->all();
			$uidSet = [];
			foreach ($rows as $r) {
				if (!empty($r->user_id)) {
					$uidSet[(int)$r->user_id] = true;
				}
			}
			$names = [];
			if ($uidSet !== []) {
				foreach ($this->Users->find()->where(['id IN' => array_keys($uidSet)])->all() as $u) {
					$names[(int)$u->id] = (string)($u->name ?? '');
				}
			}
			$out = [];
			foreach ($rows as $r) {
				$uid = $r->user_id ? (int)$r->user_id : null;
				$out[] = [
					'id' => (string)$r->id,
					'message' => (string)$r->message,
					'type' => (string)($r->type ?? 'text'),
					'metadata' => $r->metadata,
					'created' => $r->created && is_object($r->created) && method_exists($r->created, 'format')
						? $r->created->format('c') : null,
					'userId' => $uid,
					'userName' => $uid ? ($names[$uid] ?? '') : '',
				];
			}

			return $this->jsonResponse(['ok' => true, 'messages' => $out]);
		}
		$body = $this->request->input('json_decode', true);
		if (!is_array($body)) {
			$body = $this->request->getData();
		}
		$text = trim((string)($body['message'] ?? $body['text'] ?? ''));
		if ($text === '') {
			return $this->jsonResponse(['ok' => false, 'error' => 'empty'], 400);
		}
		$typeIn = (string)($body['type'] ?? 'text');
		$typeOk = in_array($typeIn, ['text', 'file', 'image', 'system'], true) ? $typeIn : 'text';
		$e = $this->TicketMessages->newEntity([
			'idempresa' => $emp,
			'ticket_id' => (int)$idticket,
			'user_id' => (int)$this->Auth->user('id'),
			'message' => $text,
			'type' => $typeOk,
			'metadata' => is_array($body['metadata'] ?? null) ? $body['metadata'] : null,
			'created' => \Cake\I18n\Time::now(),
		], ['validate' => false]);
		if (!$this->TicketMessages->save($e)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'save_failed'], 500);
		}
		$nm = (string)($this->Auth->user('name') ?? '');

		return $this->jsonResponse([
			'ok' => true,
			'message' => [
				'id' => (string)$e->id,
				'message' => (string)$e->message,
				'type' => (string)$e->type,
				'metadata' => $e->metadata,
				'created' => $e->created && is_object($e->created) && method_exists($e->created, 'format')
					? $e->created->format('c') : null,
				'userId' => (int)$this->Auth->user('id'),
				'userName' => $nm,
			],
		]);
	}

	public function apiRealtimeToken($idticket = null) {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		$ticket = $this->Tickets->find()->where(['Tickets.id' => $idticket]);
		$this->Abac->applyToQuery($ticket, 'Tickets', 'Tickets');
		$ticket = $ticket->first();
		if (empty($ticket) || !$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		// Sem servidor Node (socket.io) e proxy de /socket.io: desative para evitar
		// tentativas wss:// na mesma origem; sem realtime, o cliente usa só polling.
		if (!$this->_isServiceDeskRealtimeEnabled()) {
			return $this->jsonResponse([
				'ok' => true,
				'url' => null,
				'token' => null,
				'expires' => null,
			]);
		}
		$exp = time() + 3600;
		$payload = base64_encode((string)json_encode([
			'uid' => (int)$this->Auth->user('id'),
			'tid' => (int)$idticket,
			'eid' => (int)$this->Auth->user('idempresa'),
			'exp' => $exp,
		], JSON_UNESCAPED_UNICODE));
		$salt = (string)Configure::read('Security.salt');
		$sig = hash_hmac('sha256', $payload, $salt);
		$url = $this->_publicServiceDeskSocketUrl();

		return $this->jsonResponse([
			'ok' => true,
			'url' => $url,
			'token' => $payload . '.' . $sig,
			'expires' => $exp,
		]);
	}

	/**
	 * Dados adicionais por aba do Service Desk (ativos, peças, laudos, finanças, contrato, alertas).
	 * GET ?tab=ativos|pecas|laudos|financeiro|contrato|alertas
	 */
	public function apiServicedeskData($idticket = null) {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		$tab = strtolower((string)$this->request->getQuery('tab', 'ativos'));
		// #region agent log
		$this->_agentDebugLog18a583('pre-fix', 'H4', 'TicketsController::apiServicedeskData:entry', 'entry', [
			'idticket' => (int)$idticket,
			'tab' => $tab,
			'auth_idempresa' => (int)$this->Auth->user('idempresa'),
		]);
		// #endregion
		$ticket = $this->Tickets->find()->where(['Tickets.id' => $idticket]);
		$this->Abac->applyToQuery($ticket, 'Tickets', 'Tickets');
		$ticket = $ticket->first();
		if (empty($ticket) || !$this->_apiTicketViewAllowed($ticket)) {
			// #region agent log
			$this->_agentDebugLog18a583('pre-fix', 'H4', 'TicketsController::apiServicedeskData:forbidden', 'ticket blocked or not found', [
				'idticket' => (int)$idticket,
				'tab' => $tab,
				'ticket_empty' => empty($ticket),
			]);
			// #endregion
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$eid = (int)($ticket->idempresa ?? $this->Auth->user('idempresa'));
		$idc = (int)$ticket->idcliente;
		// #region agent log
		$this->_agentDebugLog18a583('pre-fix', 'H1', 'TicketsController::apiServicedeskData:ticketScope', 'ticket scope resolved', [
			'ticket_id' => (int)$ticket->id,
			'ticket_idempresa' => (int)($ticket->idempresa ?? 0),
			'eid_used' => $eid,
			'ticket_idcliente' => $idc,
		]);
		// #endregion

		if ($tab === 'ativos') {
			$mapAsset = function ($a) {
				$res = $a->responsavel ?? null;
				$cli = $a->cliente ?? null;
				$clienteNome = '';
				if ($cli) {
					$clienteNome = (string)($cli->razaosocial ?? $cli->nomefantasia ?? $cli->nome ?? '');
				}
				$responsavelNome = '';
				if ($res) {
					$responsavelNome = (string)($res->name ?? $res->username ?? '');
				}
				return [
					'id' => (int)$a->id,
					'idcliente' => (int)($a->idcliente ?? 0),
					'descricao' => (string)($a->descricao ?? ''),
					'identificador' => (string)($a->identificador ?? ''),
					'codigo_qr' => (string)($a->codigo_qr ?? ''),
					'tipo' => (string)($a->tipo ?? ''),
					'categoria' => (string)($a->categoria ?? ''),
					'marca' => (string)($a->marca ?? ''),
					'modelo' => (string)($a->modelo ?? ''),
					'numero_serie' => (string)($a->numero_serie ?? ''),
					'patrimonio' => (string)($a->patrimonio ?? ''),
					'hostname' => (string)($a->hostname ?? ''),
					'ip' => (string)($a->ip ?? ''),
					'mac' => (string)($a->mac ?? ''),
					'sistema_operacional' => (string)($a->sistema_operacional ?? ''),
					'localizacao' => (string)($a->localizacao ?? ''),
					'responsavel_user_id' => (int)($a->responsavel_user_id ?? 0),
					'responsavel_nome' => $responsavelNome,
					'cliente_nome' => $clienteNome,
					'propriedade' => (string)($a->propriedade ?? ''),
					'status_operacional' => (string)($a->status_operacional ?? ''),
					'ativo' => (bool)($a->ativo ?? true),
					'dt_aquisicao' => $a->dt_aquisicao,
					'dt_instalacao' => $a->dt_instalacao,
					'dt_garantia_fim' => $a->dt_garantia_fim,
					'fornecedor' => (string)($a->fornecedor ?? ''),
					'custo_aquisicao' => $a->custo_aquisicao,
					'observacoes' => (string)($a->observacoes ?? ''),
					'created' => $a->created,
					'modified' => $a->modified,
				];
			};
			// CIs já vinculados ao ticket (via pivot ticket_assets).
			$linked = [];
			$linkedIds = [];
			try {
				$ta = $this->loadModel('TicketAssets');
				$pivotRows = $ta->find()
					->contain(['Assets' => ['Clientes', 'Responsavel']])
					->where(['TicketAssets.ticket_id' => (int)$idticket])
					->order(['TicketAssets.id' => 'DESC'])
					->limit(200)
					->all();
				foreach ($pivotRows as $p) {
					if (!$p->asset) {
						continue;
					}
					$row = $mapAsset($p->asset);
					$row['ticket_asset_id'] = (int)$p->id;
					$row['papel'] = (string)($p->papel ?: 'afetado');
					$row['vinculado_em'] = $p->created;
					$linked[] = $row;
					$linkedIds[] = (int)$p->asset_id;
				}
			} catch (\Throwable $e) {
				$linked = [];
				$linkedIds = [];
			}
			// CIs disponíveis: mesma empresa + mesmo cliente operacional (vários idcliente com mesmo CNPJ/CPF/código/nome).
			$clienteIdsCorrel = ClienteCorrelatedIds::forEmpresaCliente($this->Clientes, $eid, $idc);
			// #region agent log
			$this->_agentDebugLog18a583('pre-fix', 'H2', 'TicketsController::apiServicedeskData:clienteCorrel', 'cliente correlation result', [
				'eid_used' => $eid,
				'idcliente_ref' => $idc,
				'cliente_ids_count' => count($clienteIdsCorrel),
				'cliente_ids_sample' => array_slice(array_values($clienteIdsCorrel), 0, 10),
			]);
			// #endregion
			$diagSameEmpresaSameCliente = (int)$this->Assets->find()->where([
				'Assets.idempresa' => $eid,
				'Assets.idcliente' => $idc,
			])->count();
			$diagSameEmpresaAnyCliente = (int)$this->Assets->find()->where([
				'Assets.idempresa' => $eid,
			])->count();
			$diagAnyEmpresaSameCliente = (int)$this->Assets->find()->where([
				'Assets.idcliente' => $idc,
			])->count();
			$diagAnyEmpresaCorrel = (int)$this->Assets->find()->where([
				'Assets.idcliente IN' => $clienteIdsCorrel,
			])->count();
			$diagRows = $this->Assets->find()
				->select(['id', 'idempresa', 'idcliente'])
				->where(['Assets.idcliente IN' => $clienteIdsCorrel])
				->order(['Assets.id' => 'DESC'])
				->limit(10)
				->all();
			$diagSample = [];
			foreach ($diagRows as $dr) {
				$diagSample[] = [
					'id' => (int)($dr->id ?? 0),
					'idempresa' => (int)($dr->idempresa ?? 0),
					'idcliente' => (int)($dr->idcliente ?? 0),
				];
			}
			// #region agent log
			$this->_agentDebugLog18a583('pre-fix', 'H7', 'TicketsController::apiServicedeskData:assetsDiag', 'assets scope diagnostic', [
				'eid_used' => $eid,
				'idcliente_ref' => $idc,
				'same_empresa_same_cliente_count' => $diagSameEmpresaSameCliente,
				'same_empresa_any_cliente_count' => $diagSameEmpresaAnyCliente,
				'any_empresa_same_cliente_count' => $diagAnyEmpresaSameCliente,
				'any_empresa_correl_clientes_count' => $diagAnyEmpresaCorrel,
				'diag_sample' => $diagSample,
			]);
			// #endregion
			$ticketCli = $this->Clientes->find()
				->select(['id', 'public_code', 'razaosocial', 'nome', 'nomefantasia'])
				->where(['Clientes.id' => $idc, 'Clientes.idempresa' => $eid])
				->first();
			$ticketNomeRaw = $ticketCli ? (string)($ticketCli->razaosocial ?? $ticketCli->nome ?? $ticketCli->nomefantasia ?? '') : '';
			$ticketNomeKey = ClienteCorrelatedIds::normalizeNomeKey($ticketNomeRaw);
			$assetsSameEmpresaRows = $this->Assets->find()
				->contain(['Clientes'])
				->where(['Assets.idempresa' => $eid])
				->order(['Assets.id' => 'DESC'])
				->limit(10)
				->all();
			$assetsSameEmpresaSample = [];
			foreach ($assetsSameEmpresaRows as $ar) {
				$cliAr = $ar->cliente ?? null;
				$cliNomeRaw = $cliAr ? (string)($cliAr->razaosocial ?? $cliAr->nome ?? $cliAr->nomefantasia ?? '') : '';
				$cliNomeKey = ClienteCorrelatedIds::normalizeNomeKey($cliNomeRaw);
				$assetsSameEmpresaSample[] = [
					'asset_id' => (int)($ar->id ?? 0),
					'asset_idcliente' => (int)($ar->idcliente ?? 0),
					'cliente_public_code' => $cliAr ? (string)($cliAr->public_code ?? '') : '',
					'nome_key_sha1_8' => substr(sha1($cliNomeKey), 0, 8),
					'nome_key_eq_ticket' => ($ticketNomeKey !== '' && $cliNomeKey !== '' && $cliNomeKey === $ticketNomeKey),
				];
			}
			$invalidAssetClienteRefs = [];
			$invalidCount = 0;
			foreach ($assetsSameEmpresaRows as $ar) {
				$cliAr = $ar->cliente ?? null;
				$cliEmpresa = (int)($cliAr->idempresa ?? 0);
				if (!$cliAr || $cliEmpresa !== $eid) {
					$invalidCount++;
					if (count($invalidAssetClienteRefs) < 10) {
						$invalidAssetClienteRefs[] = [
							'asset_id' => (int)($ar->id ?? 0),
							'asset_idempresa' => (int)($ar->idempresa ?? 0),
							'asset_idcliente' => (int)($ar->idcliente ?? 0),
							'cliente_found' => (bool)$cliAr,
							'cliente_idempresa' => $cliEmpresa,
						];
					}
				}
			}
			// #region agent log
			$this->_agentDebugLog18a583('pre-fix', 'H8', 'TicketsController::apiServicedeskData:assetsSameEmpresaSample', 'sample of assets in ticket company', [
				'ticket_idcliente' => $idc,
				'ticket_public_code' => $ticketCli ? (string)($ticketCli->public_code ?? '') : '',
				'ticket_nome_key_sha1_8' => substr(sha1($ticketNomeKey), 0, 8),
				'sample' => $assetsSameEmpresaSample,
				'invalid_asset_cliente_ref_count' => $invalidCount,
				'invalid_asset_cliente_ref_sample' => $invalidAssetClienteRefs,
			]);
			// #endregion
			$availQ = $this->Assets->find()
				->innerJoinWith('Clientes', function ($q) {
					return $q->where(['Clientes.idempresa = Assets.idempresa']);
				})
				->where(['Assets.idempresa' => $eid, 'Assets.idcliente IN' => $clienteIdsCorrel])
				->order(['Assets.descricao' => 'ASC', 'Assets.id' => 'DESC'])
				->limit(200);
			if (!empty($linkedIds)) {
				$availQ->where(['Assets.id NOT IN' => $linkedIds]);
			}
			$available = [];
			foreach ($availQ as $a) {
				$available[] = $mapAsset($a);
			}
			// #region agent log
			$this->_agentDebugLog18a583('pre-fix', 'H3', 'TicketsController::apiServicedeskData:availableResult', 'available assets computed', [
				'eid_used' => $eid,
				'idcliente_ref' => $idc,
				'linked_ids_count' => count($linkedIds),
				'available_count' => count($available),
				'available_asset_ids_sample' => array_slice(array_map(static function ($x) { return (int)($x['id'] ?? 0); }, $available), 0, 10),
			]);
			// #endregion

			return $this->jsonResponse([
				'ok' => true,
				'tab' => $tab,
				'rows' => $linked, // compat: clientes antigos esperam "rows" — manter alias
				'linked' => $linked,
				'available' => $available,
				'cliente_id' => $idc,
			]);
		}
		if ($tab === 'pecas') {
			$q = $this->TicketProducts->find()
				->contain(['Produtos'])
				->where(['TicketProducts.ticket_id' => (int)$idticket, 'TicketProducts.idempresa' => $eid])
				->order(['TicketProducts.id' => 'ASC']);
			$rows = $q->all();
			$list = [];
			$tot = 0.0;
			foreach ($rows as $tp) {
				$qtd = (float)($tp->quantidade ?? 0);
				$pu = (float)($tp->preco_unitario ?? 0);
				$line = $qtd * $pu;
				$tot += $line;
				$ptipo = $tp->produto ? (int)($tp->produto->tipo ?? 1) : 1;
				$pnome = $tp->produto ? (string)($tp->produto->descricao ?? $tp->produto->nome ?? 'Produto') : '—';
				$list[] = [
					'id' => (int)$tp->id,
					'data' => $tp->created,
					'descricao' => $pnome,
					'tipo' => $ptipo === 2 ? 'Serviço' : 'Produto',
					'quantidade' => $qtd,
					'valorUnit' => $pu,
					'valorTotal' => $line,
				];
			}

			return $this->jsonResponse(['ok' => true, 'tab' => $tab, 'rows' => $list, 'total' => $tot]);
		}
		if ($tab === 'laudos') {
			$rows = $this->TechnicalReports->find()
				->where(['ticket_id' => (int)$idticket, 'idempresa' => $eid])
				->orderDesc('id')
				->all();
			$list = [];
			foreach ($rows as $r) {
				$list[] = [
					'id' => (int)$r->id,
					'data' => $r->created,
					'titulo' => 'Laudo #' . (int)$r->id,
					'tipo' => (string)($r->condition_status ?? 'Manutenção'),
					'responsavel' => '—',
					'conclusao' => (string)($r->conclusao_tecnica ?? ''),
				];
			}

			return $this->jsonResponse(['ok' => true, 'tab' => $tab, 'rows' => $list]);
		}
		if ($tab === 'financeiro') {
			$pecas = 0.0;
			foreach ($this->TicketProducts->find()->where(['TicketProducts.ticket_id' => (int)$idticket, 'TicketProducts.idempresa' => $eid]) as $x) {
				$q = (float)($x->quantidade ?? 0);
				$pu = (float)($x->preco_unitario ?? 0);
				$pecas += $q * $pu;
			}
			$workSec = 0;
			$te = $this->TicketEvents->find()
				->where(['ticket_id' => (int)$idticket, 'type' => 'worklog', 'idempresa' => $eid]);
			foreach ($te as $ev) {
				$workSec += (int)($ev->seconds_spent ?? 0);
			}
			$workHours = $workSec / 3600.0;
			$servVal = 0.0;
			$cont = ServiceDeskContractHoursService::getSnapshot(
				ServiceDeskContractHoursService::findContractForClient($idc, (int)$this->Auth->user('idempresa'))
			);
			$ch = ServiceDeskContractHoursService::findContractForClient($idc, (int)$this->Auth->user('idempresa'));
			if ($ch && $ch->get('valor_hora_comercial') !== null) {
				$vhc = (float)str_replace(',', '.', (string)$ch->get('valor_hora_comercial'));
				$servVal = $workHours * $vhc;
			}
			$geral = $pecas + $servVal;
			$ledger = [];
			foreach ($this->TicketProducts->find()->contain(['Produtos'])->where(['TicketProducts.ticket_id' => (int)$idticket, 'TicketProducts.idempresa' => $eid]) as $tp) {
				$qtd = (float)($tp->quantidade ?? 0);
				$pu = (float)($tp->preco_unitario ?? 0);
				$ledger[] = [
					'data' => $tp->created,
					'descricao' => $tp->produto ? (string)($tp->produto->descricao ?? 'Produto') : 'Peça',
					'tipo' => 'Peça',
					'valor' => $qtd * $pu,
					'status' => 'Faturado',
				];
			}
			if ($servVal > 0) {
				$ledger[] = [
					'data' => $ticket->modified ?? $ticket->created,
					'descricao' => 'Serviço (horas técnicas)',
					'tipo' => 'Serviço',
					'valor' => $servVal,
					'status' => 'Faturado',
				];
			}

			return $this->jsonResponse([
				'ok' => true,
				'tab' => $tab,
				'cards' => [
					'totalHorasSeg' => (int)round($workSec),
					'totalPecas' => $pecas,
					'totalServicos' => $servVal,
					'totalGeral' => $geral,
				],
				'ledger' => $ledger,
				'contract' => $cont,
			]);
		}
		if ($tab === 'contrato') {
			$ch = ServiceDeskContractHoursService::findContractForClient($idc, (int)$this->Auth->user('idempresa'));
			$sn = ServiceDeskContractHoursService::getSnapshot($ch);
			$debits = [];
			foreach ($this->TicketEvents->find()
				->where(['ticket_id' => (int)$idticket, 'type' => 'worklog', 'idempresa' => $eid])
				->order(['id' => 'ASC']) as $w) {
				$debits[] = [
					'ticket_id' => (int)$idticket,
					'seconds' => (int)($w->seconds_spent ?? 0),
					'data' => $w->created,
					'meta' => $w->metadata,
				];
			}

			return $this->jsonResponse(['ok' => true, 'tab' => $tab, 'snapshot' => $sn, 'debits' => $debits]);
		}
		if ($tab === 'alertas') {
			$rows = $this->TicketEvents->find()
				->where(['ticket_id' => (int)$idticket, 'type' => 'alert', 'idempresa' => $eid])
				->orderDesc('id')
				->all();
			$al = [];
			foreach ($rows as $r) {
				$m = $r->metadata;
				if (is_string($m)) {
					$m = json_decode($m, true) ?: [];
				}
				$m = is_array($m) ? $m : [];
				$al[] = [
					'id' => (int)$r->id,
					'created' => $r->created,
					'level' => (string)($m['level'] ?? 'info'),
					'message' => (string)($m['message'] ?? $r->description ?? ''),
				];
			}

			return $this->jsonResponse(['ok' => true, 'tab' => $tab, 'rows' => $al]);
		}

		return $this->jsonResponse(['ok' => false, 'error' => 'invalid_tab'], 400);
	}

	/**
	 * CRUD de entradas manuais de tempo (ticketshoras) para a UI React.
	 * GET: lista entradas do ticket.
	 * POST: cria/atualiza entrada (upsert por id opcional).
	 * DELETE: remove entrada por id (query/body).
	 */
	public function apiTimeEntries($idticket = null) {
		$this->request->allowMethod(['get', 'post', 'put', 'delete']);
		$this->autoRender = false;

		$ticketQ = $this->Tickets->find()->where(['Tickets.id' => (int)$idticket]);
		$this->Abac->applyToQuery($ticketQ, 'Tickets', 'Tickets');
		$ticket = $ticketQ->first();
		if (empty($ticket) || !$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$eid = (int)$this->Auth->user('idempresa');
		$uid = (int)$this->Auth->user('id');
		$thCols = [];
		try {
			$thCols = $this->Ticketshoras->getSchema()->columns();
		} catch (\Throwable $e) {
			$thCols = [];
		}

		if ($this->request->is('get')) {
			$rows = $this->Ticketshoras->find()
				->contain(['Users'])
				->where(['Ticketshoras.idticket' => (int)$idticket, 'Ticketshoras.idempresa' => $eid])
				->order(['Ticketshoras.id' => 'DESC'])
				->all();
			/** @var \App\Model\Entity\User[]|array<int,object> Mapa users.id → entidade para rótulos (independente de hidratar $r->user). */
			$userMap = [];
			$userIds = [];
			foreach ($rows as $r0) {
				$iu = (int)$r0->get('iduser');
				if ($iu > 0) {
					$userIds[$iu] = true;
				}
			}
			if ($userIds !== []) {
				foreach ($this->Users->find()
					->where(['Users.id IN' => array_keys($userIds)])
					->all() as $uTech) {
					$userMap[(int)$uTech->id] = $uTech;
				}
			}
			$out = [];
			foreach ($rows as $r) {
				$inicio = $r->get('horaini');
				$fim = $r->get('horafin');
				$sec = TicketServiceDeskApiService::resolveSecondsFromTicketshorasRow($this->Ticketshoras, $r);
				$idTec = (int)$r->iduser;
				$uDisp = isset($userMap[$idTec]) ? $userMap[$idTec] : null;
				$tecLabel = '';
				if ($uDisp) {
					$tecLabel = trim((string)(
						(trim((string)($uDisp->get('name') ?? '')) !== '')
							? $uDisp->get('name')
							: ((trim((string)($uDisp->get('username') ?? '')) !== '')
								? $uDisp->get('username')
								: (string)($uDisp->get('email') ?? ''))
					));
				}
				if ($tecLabel === '' && $idTec > 0) {
					$rp = $r->user ?? null;
					if ($rp) {
						$tecLabel = trim((string)(
							(trim((string)($rp->get('name') ?? '')) !== '')
								? $rp->get('name')
								: ((trim((string)($rp->get('username') ?? '')) !== '')
									? $rp->get('username')
									: (string)($rp->get('email') ?? ''))
						));
					}
				}
				$out[] = [
					'id' => (int)$r->id,
					'ticketId' => (int)$r->idticket,
					'technicianId' => (int)$r->iduser,
					'technicianName' => (string)$tecLabel,
					'technician_contact_id' => (int)$r->iduser,
					'technician_name' => (string)$tecLabel,
					'startWorkHour' => ($inicio && is_object($inicio) && method_exists($inicio, 'format')) ? $inicio->format('c') : null,
					'endWorkHour' => ($fim && is_object($fim) && method_exists($fim, 'format')) ? $fim->format('c') : null,
					'durationSeconds' => (int)$sec,
					'billable' => in_array('billable', $thCols, true) ? (bool)$r->get('billable')
						: (in_array('faturavel', $thCols, true) ? (bool)$r->get('faturavel') : true),
					'rate' => in_array('taxa', $thCols, true) ? (string)($r->get('taxa') ?? '')
						: (in_array('rate', $thCols, true) ? (string)($r->get('rate') ?? '') : ''),
					'note' => in_array('descricao', $thCols, true) ? (string)($r->get('descricao') ?? '')
						: (in_array('observacao', $thCols, true) ? (string)($r->get('observacao') ?? '')
						: (in_array('nota', $thCols, true) ? (string)($r->get('nota') ?? '') : '')),
				];
			}
			$technicians = [];
			$techSeen = [];
			try {
				$rowsTech = $this->Empresasusers->find('all', ['contain' => ['Users']])
					->where(['Empresasusers.idempresa' => $eid, 'Users.role' => 0, 'Users.inativo' => 0])
					->order(['Users.name' => 'ASC'])
					->all();
				foreach ($rowsTech as $rTech) {
					$uTech = $rTech->user ?? $rTech->users ?? null;
					if (!$uTech) {
						continue;
					}
					$techId = (int)$uTech->id;
					if ($techId <= 0 || isset($techSeen[$techId])) {
						continue;
					}
					$label = trim((string)(
						(trim((string)($uTech->get('name') ?? '')) !== '')
							? $uTech->get('name')
							: ((trim((string)($uTech->get('username') ?? '')) !== '')
								? $uTech->get('username')
								: (string)($uTech->get('email') ?? ''))
					));
					$technicians[] = ['id' => $techId, 'name' => $label];
					$techSeen[$techId] = true;
				}
			} catch (\Throwable $e) {
				// Não falhar o endpoint se houver variação de schema/relacionamento.
			}
			foreach ($out as $entryRow) {
				$techId = (int)($entryRow['technicianId'] ?? 0);
				if ($techId <= 0 || isset($techSeen[$techId])) {
					continue;
				}
				$label = trim((string)($entryRow['technicianName'] ?? ''));
				$technicians[] = ['id' => $techId, 'name' => $label];
				$techSeen[$techId] = true;
			}

			return $this->jsonResponse(['ok' => true, 'entries' => $out, 'technicians' => $technicians]);
		}

		$body = $this->request->input('json_decode', true);
		if (!is_array($body)) {
			$body = $this->request->getData();
		}

		if ($this->request->is('delete')) {
			$entryId = (int)($body['id'] ?? $this->request->getQuery('id') ?? 0);
			$auditReason = trim((string)($body['auditReason'] ?? $body['reason'] ?? ''));
			$auditAuthKey = trim((string)($body['auditAuthKey'] ?? $body['authKey'] ?? ''));
			if ($entryId <= 0) {
				return $this->jsonResponse(['ok' => false, 'error' => 'invalid_params'], 400);
			}
			$row = $this->Ticketshoras->find()
				->where(['id' => $entryId, 'idticket' => (int)$idticket, 'idempresa' => $eid])
				->first();
			if (!$row) {
				return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
			}
			$oldHms = $this->_ticketshorasDurationHms($row);
			$audit = $this->_validateAuditPasswordForCurrentUser((int)$idticket, $auditAuthKey, $auditReason, $oldHms, '00:00:00');
			if (!$audit['ok']) {
				return $this->jsonResponse(['ok' => false, 'error' => 'audit_required', 'message' => $audit['message']], 403);
			}
			if (!$this->Ticketshoras->delete($row)) {
				return $this->jsonResponse(['ok' => false, 'error' => 'delete_failed'], 500);
			}

			return $this->jsonResponse(['ok' => true]);
		}

		$entryId = (int)($body['id'] ?? 0);
		$startRaw = (string)($body['startWorkHour'] ?? $body['StartWorkHour'] ?? '');
		$endRaw = (string)($body['endWorkHour'] ?? $body['EndWorkHour'] ?? '');
		$technicianId = (int)($body['technicianContactId'] ?? $body['TechnicianContactID'] ?? $uid);
		$billable = (bool)($body['billable'] ?? $body['Billable'] ?? true);
		$rate = trim((string)($body['rate'] ?? $body['Rate'] ?? ''));
		$note = trim((string)($body['descricao'] ?? $body['Description'] ?? $body['note'] ?? $body['Note'] ?? ''));
		$auditReason = trim((string)($body['auditReason'] ?? $body['reason'] ?? ''));
		$auditAuthKey = trim((string)($body['auditAuthKey'] ?? $body['authKey'] ?? ''));
		if ($technicianId <= 0) {
			$technicianId = $uid;
		}
		try {
			$start = new \DateTime($startRaw ?: 'now');
			$end = new \DateTime($endRaw ?: 'now');
		} catch (\Throwable $e) {
			return $this->jsonResponse(['ok' => false, 'error' => 'invalid_datetime'], 400);
		}
		if ($end <= $start) {
			return $this->jsonResponse(['ok' => false, 'error' => 'invalid_interval'], 400);
		}

		if ($entryId > 0) {
			$row = $this->Ticketshoras->find()
				->where(['id' => $entryId, 'idticket' => (int)$idticket, 'idempresa' => $eid])
				->first();
			if (!$row) {
				return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
			}
			$oldHms = $this->_ticketshorasDurationHms($row);
			$newSec = max(0, (int)($end->getTimestamp() - $start->getTimestamp()));
			$newHms = gmdate('H:i:s', $newSec);
			$audit = $this->_validateAuditPasswordForCurrentUser((int)$idticket, $auditAuthKey, $auditReason, $oldHms, $newHms);
			if (!$audit['ok']) {
				return $this->jsonResponse(['ok' => false, 'error' => 'audit_required', 'message' => $audit['message']], 403);
			}
		} else {
			$row = $this->Ticketshoras->newEntity();
			$row->set('idticket', (int)$idticket);
			$row->set('idempresa', $eid);
		}
		$row->set('iduser', $technicianId);
		$row->set('data', $start->format('Y-m-d'));
		$row->set('horaini', $start->format('Y-m-d H:i:s'));
		$row->set('horafin', $end->format('Y-m-d H:i:s'));
		if (in_array('billable', $thCols, true)) {
			$row->set('billable', $billable);
		} elseif (in_array('faturavel', $thCols, true)) {
			$row->set('faturavel', $billable);
		}
		if (in_array('taxa', $thCols, true)) {
			$row->set('taxa', $rate);
		} elseif (in_array('rate', $thCols, true)) {
			$row->set('rate', $rate);
		}
		if (in_array('descricao', $thCols, true)) {
			$row->set('descricao', $note);
		} elseif (in_array('observacao', $thCols, true)) {
			$row->set('observacao', $note);
		} elseif (in_array('nota', $thCols, true)) {
			$row->set('nota', $note);
		}
		if (!$this->Ticketshoras->save($row)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'save_failed'], 500);
		}

		try {
			TicketWorklogEventHelper::afterHoraLancada(
				$row,
				$eid,
				(int)$idticket,
				$technicianId,
				$row->horaini,
				$row->horafin
			);
		} catch (\Throwable $e) {
			$this->log('apiTimeEntries afterHoraLancada: ' . $e->getMessage(), 'error');
		}

		return $this->jsonResponse(['ok' => true, 'id' => (int)$row->id]);
	}

	protected function _ticketshorasDurationHms($row): string {
		try {
			$sec = TicketServiceDeskApiService::resolveSecondsFromTicketshorasRow($this->Ticketshoras, $row);
			$sec = max(0, (int)$sec);
			return gmdate('H:i:s', $sec);
		} catch (\Throwable $e) {
			return '00:00:00';
		}
	}

	protected function _validateAuditPasswordForCurrentUser(int $ticketId, string $authKey, string $reason, string $oldTime, string $newTime): array {
		if ($authKey === '') {
			return ['ok' => false, 'message' => 'Senha de auditoria obrigatória para alterar horas.'];
		}
		if ($reason === '') {
			return ['ok' => false, 'message' => 'Motivo obrigatório para alterar horas.'];
		}
		$uid = (int)$this->Auth->user('id');
		try {
			$user = $this->Users->get($uid);
		} catch (\Throwable $e) {
			return ['ok' => false, 'message' => 'Utilizador inválido.'];
		}
		$hash = (string)($user->get('audit_password_hash') ?? '');
		if ($hash === '') {
			return ['ok' => false, 'message' => 'Senha de auditoria não definida.'];
		}
		$hasher = new DefaultPasswordHasher();
		if (!$hasher->check($authKey, $hash)) {
			return ['ok' => false, 'message' => 'Senha de auditoria inválida.'];
		}
		try {
			$logs = \Cake\ORM\TableRegistry::getTableLocator()->get('TicketAuditLogs');
			$logs->save($logs->newEntity([
				'ticket_id' => $ticketId,
				'user_id' => $uid,
				'old_time' => $oldTime,
				'new_time' => $newTime,
				'reason' => $reason,
				'created' => \Cake\I18n\FrozenTime::now(),
			]));
		} catch (\Throwable $e) {
			$this->log('apiTimeEntries audit log: ' . $e->getMessage(), 'error');
		}
		return ['ok' => true, 'message' => null];
	}

	/**
	 * Vincula um ativo (CI) a um ticket. POST com JSON ou form: { asset_id, papel? }.
	 */
	public function apiTicketAssetsAttach($idticket = null) {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		$ticket = $this->Tickets->find()->where(['Tickets.id' => (int)$idticket]);
		$this->Abac->applyToQuery($ticket, 'Tickets', 'Tickets');
		$ticket = $ticket->first();
		if (empty($ticket) || !$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		// Cliente portal não pode vincular CIs.
		if ((int)$this->Auth->user('role') === (int)C_RoleCliente) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$body = $this->request->input('json_decode', true);
		if (!is_array($body)) {
			$body = $this->request->getData();
		}
		$assetId = (int)($body['asset_id'] ?? $body['assetId'] ?? 0);
		$papel = (string)($body['papel'] ?? 'afetado');
		if (!in_array($papel, ['afetado', 'relacionado'], true)) {
			$papel = 'afetado';
		}
		if ($assetId <= 0) {
			return $this->jsonResponse(['ok' => false, 'error' => 'invalid_params'], 400);
		}
		$eid = (int)($ticket->idempresa ?? $this->Auth->user('idempresa'));
		$clienteIdsCorrel = ClienteCorrelatedIds::forEmpresaCliente($this->Clientes, $eid, (int)$ticket->idcliente);
		$asset = $this->Assets->find()
			->innerJoinWith('Clientes', function ($q) {
				return $q->where(['Clientes.idempresa = Assets.idempresa']);
			})
			->where([
				'Assets.id' => $assetId,
				'Assets.idempresa' => $eid,
				'Assets.idcliente IN' => $clienteIdsCorrel,
			])->first();
		if (!$asset) {
			$assetSameEmpresa = (bool)$this->Assets->find()
				->where(['Assets.id' => $assetId, 'Assets.idempresa' => $eid])
				->count();
			$this->_agentDebugLog18a583(
				'asset-link',
				'H9',
				'TicketsController::apiTicketAssetsAttach:assetValidationFailed',
				'asset link validation failed',
				[
					'ticket_id' => (int)$ticket->id,
					'ticket_idempresa' => $eid,
					'ticket_idcliente' => (int)$ticket->idcliente,
					'asset_id' => $assetId,
					'cliente_ids_permitidos' => array_values($clienteIdsCorrel),
					'asset_found_same_empresa' => $assetSameEmpresa,
				]
			);
			return $this->jsonResponse(['ok' => false, 'error' => 'asset_not_found'], 404);
		}
		try {
			$ta = $this->loadModel('TicketAssets');
			$exists = $ta->find()
				->where(['ticket_id' => (int)$idticket, 'asset_id' => $assetId])
				->first();
			if ($exists) {
				return $this->jsonResponse([
					'ok' => true,
					'id' => (int)$exists->id,
					'already_linked' => true,
				]);
			}
			$row = $ta->newEntity([
				'idempresa' => $eid,
				'ticket_id' => (int)$idticket,
				'asset_id' => $assetId,
				'papel' => $papel,
				'user_id' => (int)$this->Auth->user('id'),
			]);
			if (!$ta->save($row)) {
				return $this->jsonResponse([
					'ok' => false,
					'error' => 'save_failed',
					'errors' => $row->getErrors(),
				], 422);
			}

			return $this->jsonResponse(['ok' => true, 'id' => (int)$row->id, 'asset_id' => $assetId]);
		} catch (\Throwable $e) {
			return $this->jsonResponse(['ok' => false, 'error' => 'exception', 'message' => $e->getMessage()], 500);
		}
	}

	/**
	 * Desvincula um ativo (CI) de um ticket. POST com JSON ou form: { asset_id } ou { ticket_asset_id }.
	 */
	public function apiTicketAssetsDetach($idticket = null) {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		$ticket = $this->Tickets->find()->where(['Tickets.id' => (int)$idticket]);
		$this->Abac->applyToQuery($ticket, 'Tickets', 'Tickets');
		$ticket = $ticket->first();
		if (empty($ticket) || !$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		if ((int)$this->Auth->user('role') === (int)C_RoleCliente) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$body = $this->request->input('json_decode', true);
		if (!is_array($body)) {
			$body = $this->request->getData();
		}
		$assetId = (int)($body['asset_id'] ?? $body['assetId'] ?? 0);
		$pivotId = (int)($body['ticket_asset_id'] ?? $body['id'] ?? 0);
		if ($assetId <= 0 && $pivotId <= 0) {
			return $this->jsonResponse(['ok' => false, 'error' => 'invalid_params'], 400);
		}
		try {
			$ta = $this->loadModel('TicketAssets');
			$where = ['ticket_id' => (int)$idticket];
			if ($pivotId > 0) {
				$where['id'] = $pivotId;
			} else {
				$where['asset_id'] = $assetId;
			}
			$row = $ta->find()->where($where)->first();
			if (!$row) {
				return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
			}
			if (!$ta->delete($row)) {
				return $this->jsonResponse(['ok' => false, 'error' => 'delete_failed'], 500);
			}

			return $this->jsonResponse(['ok' => true, 'id' => (int)$row->id]);
		} catch (\Throwable $e) {
			return $this->jsonResponse(['ok' => false, 'error' => 'exception', 'message' => $e->getMessage()], 500);
		}
	}
}
