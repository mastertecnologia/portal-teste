<?php
namespace App\Service\Common;

use Cake\ORM\TableRegistry;

/**
 * Service Layer for centralized model access.
 * 
 * Reduces code duplication across controllers by providing
 * static methods for accessing commonly used models.
 * Implements lazy loading pattern for better performance.
 */
class ModelService
{
    /**
     * Cache for model instances to avoid repeated TableRegistry calls
     * @var array
     */
    private static $modelCache = [];

    /**
     * Get Users table instance
     * 
     * @return \App\Model\Table\UsersTable
     */
    public static function getUsers()
    {
        return self::getModel('Users');
    }

    /**
     * Get Empresas table instance
     * 
     * @return \App\Model\Table\EmpresasTable
     */
    public static function getEmpresas()
    {
        return self::getModel('Empresas');
    }

    /**
     * Get Clientes table instance
     * 
     * @return \App\Model\Table\ClientesTable
     */
    public static function getClientes()
    {
        return self::getModel('Clientes');
    }

    /**
     * Get Empresasusers table instance
     * 
     * @return \App\Model\Table\EmpresasusersTable
     */
    public static function getEmpresasusers()
    {
        return self::getModel('Empresasusers');
    }

    /**
     * Get Atividades table instance
     * 
     * @return \App\Model\Table\AtividadesTable
     */
    public static function getAtividades()
    {
        return self::getModel('Atividades');
    }

    /**
     * Get Tickets table instance
     * 
     * @return \App\Model\Table\TicketsTable
     */
    public static function getTickets()
    {
        return self::getModel('Tickets');
    }

    /**
     * Get Ticketsusers table instance
     * 
     * @return \App\Model\Table\TicketsusersTable
     */
    public static function getTicketsusers()
    {
        return self::getModel('Ticketsusers');
    }

    /**
     * Get Config table instance
     * 
     * @return \App\Model\Table\ConfigTable
     */
    public static function getConfig()
    {
        return self::getModel('Config');
    }

    /**
     * Get Financeiro table instance
     * 
     * @return \App\Model\Table\FinanceiroTable
     */
    public static function getFinanceiro()
    {
        return self::getModel('Financeiro');
    }

    /**
     * Get Orcamentos table instance
     * 
     * @return \App\Model\Table\OrcamentosTable
     */
    public static function getOrcamentos()
    {
        return self::getModel('Orcamentos');
    }

    /**
     * Get Clicontratos table instance
     * 
     * @return \App\Model\Table\ClicontratosTable
     */
    public static function getClicontratos()
    {
        return self::getModel('Clicontratos');
    }

    /**
     * Get Visitas table instance
     * 
     * @return \App\Model\Table\VisitasTable
     */
    public static function getVisitas()
    {
        return self::getModel('Visitas');
    }

    /**
     * Get Queues table instance
     * 
     * @return \App\Model\Table\QueuesTable
     */
    public static function getQueues()
    {
        return self::getModel('Queues');
    }

    /**
     * Get QueuesUsers table instance
     * 
     * @return \App\Model\Table\QueuesUsersTable
     */
    public static function getQueuesUsers()
    {
        return self::getModel('QueuesUsers');
    }

    /**
     * Get SupportLevels table instance
     * 
     * @return \App\Model\Table\SupportLevelsTable
     */
    public static function getSupportLevels()
    {
        return self::getModel('SupportLevels');
    }

    /**
     * Generic method to get any model instance with caching
     * 
     * @param string $modelName The model name to retrieve
     * @return \Cake\ORM\Table The model instance
     */
    private static function getModel($modelName)
    {
        if (!isset(self::$modelCache[$modelName])) {
            self::$modelCache[$modelName] = TableRegistry::get($modelName);
        }
        
        return self::$modelCache[$modelName];
    }

    /**
     * Clear the model cache (useful for testing or long-running processes)
     * 
     * @return void
     */
    public static function clearCache()
    {
        self::$modelCache = [];
    }

    /**
     * Get all commonly used models as an array
     * Useful for controllers that need multiple models
     * 
     * @return array Associative array of model instances
     */
    public static function getCommonModels()
    {
        return [
            'Users' => self::getUsers(),
            'Empresas' => self::getEmpresas(),
            'Clientes' => self::getClientes(),
            'Empresasusers' => self::getEmpresasusers(),
            'Atividades' => self::getAtividades(),
            'Tickets' => self::getTickets(),
            'Ticketsusers' => self::getTicketsusers(),
            'Config' => self::getConfig(),
            'Queues' => self::getQueues(),
            'QueuesUsers' => self::getQueuesUsers(),
            'SupportLevels' => self::getSupportLevels(),
        ];
    }

    /**
     * Load common models into a controller instance
     * This method can be used to replace the repetitive loadModel calls
     * 
     * @param \Cake\Controller\Controller $controller The controller instance
     * @param array $models Array of model names to load (optional, defaults to common models)
     * @return void
     */
    public static function loadModelsIntoController($controller, array $models = null)
    {
        if ($models === null) {
            $models = ['Users', 'Empresas', 'Empresasusers', 'Atividades'];
        }

        foreach ($models as $model) {
            if (method_exists(self::class, 'get' . $model)) {
                $controller->loadModel($model);
            }
        }
    }
}