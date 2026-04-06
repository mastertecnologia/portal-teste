<?php
namespace App\Service\ClienteIntegration;

require_once ROOT . DS . 'vendor' . DS . 'queencitycodefactory' . DS . 'cakesoap' . DS . 'src' . DS . 'Network' . DS . 'CakeSoap.php';

use App\Service\ClienteDomain\ClienteDomainBridge;
use App\Utility\ErpGridUrl;
use App\Utility\ClienteDomainEventType;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use CakeSoap\Network\CakeSoap;

/**
 * Sincronização cliente → ERP (SOAP GerenciaCliente). Mantém o mesmo contrato de erro/eventos do fluxo legado.
 */
class ClienteErpSyncService {

	/**
	 * @param object $clienteEnt Entidade Cliente
	 * @return string|null Mensagem de erro para Flash; null se não houve falha explícita do ERP/WS
	 */
	public static function sincronizarCliente($clienteEnt, int $idcliente, int $idempresa, int $actorUserId): ?string {
		$Clientes = TableRegistry::get('Clientes');
		$Empresas = TableRegistry::get('Empresas');

		$cliente = ['Cliente' => $Clientes->clientesArr($clienteEnt)];
		$json = json_encode($cliente, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		$emp = $Empresas->get($idempresa);
		$wsdl = ErpGridUrl::wsdl($emp->urlerp, ErpGridUrl::WSDL_PESSOAS);
		$token = $emp->token;

		try {
			@$soap = new CakeSoap(['wsdl' => $wsdl]);
			if ($soap === null) {
				throw new \Exception('Erro');
			}
		} catch (\Exception $e) {
			\Cake\Log\Log::warning(sprintf(
				'ClienteErpSyncService: WSDL/init falhou idcliente=%d idempresa=%d err=%s',
				$idcliente,
				$idempresa,
				$e->getMessage()
			));
			ClienteDomainBridge::emit(ClienteDomainEventType::ERP_INTEGRACAO_ERRO, [
				'idcliente' => $idcliente,
				'idempresa' => $idempresa,
				'actor_user_id' => $actorUserId,
				'title' => __('Integração ERP indisponível'),
				'message' => __('Não foi possível acessar o Web Service do ERP para sincronizar o cliente.'),
				'action_url' => Router::url(['controller' => 'Clientes', 'action' => 'edit', $idcliente]),
				'entity_type' => 'Cliente',
				'entity_id' => $idcliente,
				'metadata' => ['ws_error' => $e->getMessage()],
			]);

			return __('O WS não pode ser acessado no momento!');
		}

		try {
			$response = $soap->sendRequest('GerenciaCliente', [
				'Data' => [
					'iEmpresa' => $idempresa,
					'sToken' => $token,
					'sJSON' => $json,
				],
			]);
		} catch (\Throwable $e) {
			\Cake\Log\Log::warning(sprintf(
				'ClienteErpSyncService: sendRequest GerenciaCliente exceção idcliente=%d idempresa=%d err=%s',
				$idcliente,
				$idempresa,
				$e->getMessage()
			));
			ClienteDomainBridge::emit(ClienteDomainEventType::ERP_INTEGRACAO_ERRO, [
				'idcliente' => $idcliente,
				'idempresa' => $idempresa,
				'actor_user_id' => $actorUserId,
				'title' => __('Integração ERP indisponível'),
				'message' => __('Erro ao enviar dados ao ERP. Tente novamente ou contate o suporte.'),
				'action_url' => Router::url(['controller' => 'Clientes', 'action' => 'edit', $idcliente]),
				'entity_type' => 'Cliente',
				'entity_id' => $idcliente,
				'metadata' => ['soap_error' => $e->getMessage()],
			]);

			return __('Erro ao comunicar com o ERP. Tente novamente.');
		}

		$result = isset($response->GerenciaClienteResult) ? $response->GerenciaClienteResult : null;
		if ($result === null || !isset($result->cStatus)) {
			\Cake\Log\Log::warning(sprintf(
				'ClienteErpSyncService: resposta SOAP sem cStatus idcliente=%d idempresa=%d',
				$idcliente,
				$idempresa
			));
			ClienteDomainBridge::emit(ClienteDomainEventType::ERP_SINCRONIZACAO_FALHA, [
				'idcliente' => $idcliente,
				'idempresa' => $idempresa,
				'actor_user_id' => $actorUserId,
				'title' => __('Falha na sincronização com o ERP'),
				'message' => __('Resposta inválida do serviço de integração.'),
				'action_url' => Router::url(['controller' => 'Clientes', 'action' => 'edit', $idcliente]),
				'entity_type' => 'Cliente',
				'entity_id' => $idcliente,
				'metadata' => [],
			]);

			return __('Resposta inválida do ERP.');
		}

		if (!in_array($result->cStatus, [201, 200], true)) {
			$statusStr = (string)$result->cStatus;
			$msgStr = isset($result->sMsg) ? (string)$result->sMsg : '';
			\Cake\Log\Log::warning(sprintf(
				'ClienteErpSyncService: GerenciaCliente negado idcliente=%d idempresa=%d status=%s msg=%s',
				$idcliente,
				$idempresa,
				$statusStr,
				$msgStr
			));
			ClienteDomainBridge::emit(ClienteDomainEventType::ERP_SINCRONIZACAO_FALHA, [
				'idcliente' => $idcliente,
				'idempresa' => $idempresa,
				'actor_user_id' => $actorUserId,
				'title' => __('Falha na sincronização com o ERP'),
				'message' => $msgStr !== '' ? $msgStr : __('Falha na sincronização.'),
				'action_url' => Router::url(['controller' => 'Clientes', 'action' => 'edit', $idcliente]),
				'entity_type' => 'Cliente',
				'entity_id' => $idcliente,
				'metadata' => ['status' => $statusStr],
			]);

			return $msgStr !== '' ? $msgStr : __('Falha na sincronização.');
		}

		return null;
	}
}
