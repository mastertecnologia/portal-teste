<?php
namespace App\Service\ClienteIntegration;

require_once ROOT . DS . 'vendor' . DS . 'queencitycodefactory' . DS . 'cakesoap' . DS . 'src' . DS . 'Network' . DS . 'CakeSoap.php';

use App\Service\ClienteDomain\ClienteDomainBridge;
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
		$wsdl = $emp->urlerp . 'WSPGMPessoas.wso?wsdl';
		$token = $emp->token;

		try {
			@$soap = new CakeSoap(['wsdl' => $wsdl]);
			if ($soap === null) {
				throw new \Exception('Erro');
			}
		} catch (\Exception $e) {
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

		$response = $soap->sendRequest('GerenciaCliente', [
			'Data' => [
				'iEmpresa' => $idempresa,
				'sToken' => $token,
				'sJSON' => $json,
			],
		]);

		if (!in_array($response->GerenciaClienteResult->cStatus, [201, 200], true)) {
			ClienteDomainBridge::emit(ClienteDomainEventType::ERP_SINCRONIZACAO_FALHA, [
				'idcliente' => $idcliente,
				'idempresa' => $idempresa,
				'actor_user_id' => $actorUserId,
				'title' => __('Falha na sincronização com o ERP'),
				'message' => (string)$response->GerenciaClienteResult->sMsg,
				'action_url' => Router::url(['controller' => 'Clientes', 'action' => 'edit', $idcliente]),
				'entity_type' => 'Cliente',
				'entity_id' => $idcliente,
				'metadata' => ['status' => (string)$response->GerenciaClienteResult->cStatus],
			]);

			return (string)$response->GerenciaClienteResult->sMsg;
		}

		return null;
	}
}
