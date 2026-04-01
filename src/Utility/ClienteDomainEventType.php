<?php
namespace App\Utility;

/**
 * Eventos do domínio cliente/contrato/token/integração (códigos estáveis para prefs e logs).
 */
class ClienteDomainEventType {

	public const CLIENTE_CRIADO = 'cliente.criado';
	public const CLIENTE_ATUALIZADO = 'cliente.atualizado';
	public const CLIENTE_ATIVADO = 'cliente.ativado';
	public const CLIENTE_INATIVADO = 'cliente.inativado';

	public const CONTRATO_CRIADO = 'contrato.criado';
	public const CONTRATO_ATUALIZADO = 'contrato.atualizado';
	public const CONTRATO_VENCENDO = 'contrato.vencendo';
	public const CONTRATO_VENCIDO = 'contrato.vencido';

	public const TOKEN_GERADO = 'token.gerado';
	public const TOKEN_EXPIRANDO = 'token.expirando';

	public const ERP_INTEGRACAO_ERRO = 'erp.integracao_erro';
	public const ERP_SINCRONIZACAO_FALHA = 'erp.sincronizacao_falha';

	public const USUARIO_VINCULADO_CLIENTE = 'usuario.vinculado_cliente';
	public const USUARIO_REMOVIDO_CLIENTE = 'usuario.removido_cliente';

	/**
	 * Tipos exibidos na tela de preferências (código estável => rótulo).
	 *
	 * @return array<string, string>
	 */
	public static function preferenceEventTypes(): array {
		return [
			self::CLIENTE_CRIADO => 'Cliente criado',
			self::CLIENTE_ATUALIZADO => 'Cliente atualizado',
			self::CLIENTE_ATIVADO => 'Cliente ativado',
			self::CLIENTE_INATIVADO => 'Cliente inativado',
			self::CONTRATO_CRIADO => 'Contrato criado',
			self::CONTRATO_ATUALIZADO => 'Contrato atualizado',
			self::CONTRATO_VENCENDO => 'Contrato prestes a vencer',
			self::CONTRATO_VENCIDO => 'Contrato vencido',
			self::TOKEN_GERADO => 'Token gerado / renovado',
			self::TOKEN_EXPIRANDO => 'Token expirando',
			self::ERP_INTEGRACAO_ERRO => 'Erro de integração ERP/API',
			self::ERP_SINCRONIZACAO_FALHA => 'Falha de sincronização',
			self::USUARIO_VINCULADO_CLIENTE => 'Usuário vinculado ao cliente',
			self::USUARIO_REMOVIDO_CLIENTE => 'Usuário removido do cliente',
		];
	}
}
