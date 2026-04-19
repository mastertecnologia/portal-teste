<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Service\Common\ModelService;

/**
 * BaseController - Controller base com modelos compartilhados
 * 
 * Elimina duplicação de loadModels através do ModelService centralizado.
 * Controllers que precisam dos mesmos modelos podem estender esta classe
 * em vez de repetir as chamadas loadModels.
 */
abstract class BaseController extends AppController
{
    /**
     * Modelos comuns compartilhados entre múltiplos controllers
     * 
     * Esta lista contém os modelos mais frequentemente usados no sistema.
     * Controllers específicos podem adicionar modelos adicionais no próprio
     * initialize() se necessário.
     */
    protected $commonModels = [
        // Modelos de usuário e autenticação
        'Users',
        
        // Modelos de clientes e contratos
        'Clientes', 'Clicontratos', 'Cliacessos',
        
        // Modelos de tickets e suporte
        'Tickets', 'Ticketsusers', 'Ticketsanexos', 'Ticketcomentarios',
        'Ticketshoras', 'Ticketsmovs', 'Notificacoes', 'Ticketsservicos',
        'Ticketsmodulos', 'Ticketslogemail',
        
        // Modelos de empresas e configuração
        'Empresas', 'Empresasusers', 'Config',
        
        // Modelos de serviços e módulos
        'Servicos', 'Modulos', 'Cliservicos', 'Climodulos',
        
        // Modelos financeiros
        'Faturas', 'Faturaparcelas', 'Cancelamento',
        
        // Modelos de ordens e atividades
        'Ordensservico', 'Atividades', 'Homologacoes',
        
        // Modelos de filas e suporte
        'Queues', 'QueuesUsers', 'SupportLevels'
    ];

    /**
     * Initialize callback
     * 
     * Carrega automaticamente os modelos comuns usando ModelService.
     * Controllers filhos podem chamar parent::initialize() e adicionar
     * modelos específicos se necessário.
     */
    public function initialize(): void
    {
        parent::initialize();
        
        // Usar ModelService para carregar todos os modelos comuns de uma vez
        // Isso elimina a necessidade de múltiplas chamadas loadModel
        ModelService::loadModelsIntoController($this, $this->commonModels);
    }

    /**
     * Obtém a lista de modelos comuns
     * 
     * Útil para controllers que precisam verificar quais modelos
     * já foram carregados ou adicionar modelos específicos.
     * 
     * @return array Lista de modelos comuns
     */
    protected function getCommonModels(): array
    {
        return $this->commonModels;
    }

    /**
     * Adiciona modelos específicos ao controller
     * 
     * Para uso em controllers filhos que precisam de modelos adicionais
     * além dos modelos comuns já carregados.
     * 
     * @param array $additionalModels Modelos adicionais a carregar
     * @return void
     */
    protected function loadAdditionalModels(array $additionalModels): void
    {
        if (!empty($additionalModels)) {
            ModelService::loadModelsIntoController($this, $additionalModels);
        }
    }

    /**
     * Verifica se um modelo específico está disponível
     * 
     * Helper para verificar se um modelo foi carregado sem causar erros.
     * 
     * @param string $modelName Nome do modelo a verificar
     * @return bool True se o modelo está disponível
     */
    protected function hasModel(string $modelName): bool
    {
        return isset($this->{$modelName}) && $this->{$modelName} !== null;
    }

    /**
     * Obtém um modelo específico com verificação
     * 
     * Retorna o modelo se disponível, null caso contrário.
     * Evita erros de "Undefined property" quando o modelo não existe.
     * 
     * @param string $modelName Nome do modelo
     * @return mixed|null O modelo ou null se não disponível
     */
    protected function getModel(string $modelName)
    {
        return $this->hasModel($modelName) ? $this->{$modelName} : null;
    }
}