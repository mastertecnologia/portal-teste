<?php
namespace App\Controller;

/**
 * ExampleBaseController - Exemplo de uso do BaseController
 * 
 * Este controller demonstra como estender BaseController para obter
 * automaticamente todos os modelos comuns sem necessidade de repetir
 * chamadas loadModel.
 */
class ExampleBaseController extends BaseController
{
    /**
     * Initialize callback
     * 
     * Note que não precisamos chamar loadModel para os modelos comuns!
     * Eles já estão disponíveis graças ao BaseController.
     */
    public function initialize(): void
    {
        parent::initialize();
        
        // Se precisarmos de modelos adicionais específicos deste controller:
        $this->loadAdditionalModels([
            'Bancosenhas',  // Modelo específico para este controller
            'Cidades',      // Modelo específico para este controller
            'Estados'       // Modelo específico para este controller
        ]);
    }

    /**
     * Exemplo de método usando modelos já carregados
     */
    public function example()
    {
        // Todos estes modelos estão disponíveis automaticamente:
        $users = $this->Users->find('all')->limit(10);
        $clientes = $this->Clientes->find('all')->limit(10);
        $tickets = $this->Tickets->find('all')->limit(10);
        $empresas = $this->Empresas->find('all')->limit(10);
        
        // Modelos adicionais carregados via loadAdditionalModels:
        $bancosenhas = $this->Bancosenhas->find('all')->limit(10);
        $cidades = $this->Cidades->find('all')->limit(10);
        
        // Verificação segura de modelos:
        if ($this->hasModel('Estados')) {
            $estados = $this->Estados->find('list')->toArray();
        }
        
        // Ou usando getModel() que retorna null se não existir:
        $estados = $this->getModel('Estados');
        
        $this->set([
            'users' => $users,
            'clientes' => $clientes,
            'tickets' => $tickets,
            'empresas' => $empresas,
            'bancosenhas' => $bancosenhas,
            'cidades' => $cidades,
            'estados' => $estados
        ]);
    }
}
