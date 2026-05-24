<?php
/**
 * Mapeamento telas pg-* (pgm_erp_completo_2.html) ↔ rotas Cake.
 */
return [
    'PortalUiScreens' => [
        'switchover' => [
            'clientes' => [
                'module' => 'clientes',
                'prototype' => ['controller' => 'ClientesPrototype', 'action' => 'lista'],
            ],
            'produtos' => [
                'module' => 'produtos',
                'prototype' => ['controller' => 'ProdutosPrototype', 'action' => 'lista'],
            ],
            'produtos_estoque' => [
                'module' => 'produtos',
                'prototype' => ['controller' => 'ProdutosPrototype', 'action' => 'estoque'],
            ],
            'orcamentos' => [
                'module' => 'orcamentos',
                'prototype' => ['controller' => 'OrcamentosPrototype', 'action' => 'lista'],
            ],
            'ordens' => [
                'module' => 'ordens',
                'prototype' => ['controller' => 'OrdensservicoPrototype', 'action' => 'lista'],
            ],
            'financeiro' => [
                'module' => 'financeiro',
                'prototype' => ['controller' => 'FinanceiroPrototype', 'action' => 'lista'],
            ],
            'bancos' => [
                'module' => 'bancos',
                'prototype' => ['controller' => 'BancosPrototype', 'action' => 'lista'],
            ],
            'fornecedores' => [
                'module' => 'fornecedores',
                'prototype' => ['controller' => 'FornecedoresPrototype', 'action' => 'lista'],
            ],
            'servicedesk' => [
                'module' => 'servicedesk',
                'prototype' => ['controller' => 'ServicedeskPrototype', 'action' => 'index'],
            ],
        ],
        'screens' => [
            'pg-home' => [
                'title' => 'Dashboard',
                'module' => 'home',
                'legacy' => ['controller' => 'Users', 'action' => 'dashboard'],
                'prototype' => null,
                'parity' => 'legacy_only',
            ],
            'pg-lista' => [
                'title' => 'Lista orçamentos',
                'module' => 'orcamentos',
                'legacy' => ['controller' => 'Orcamentos', 'action' => 'index'],
                'prototype' => ['controller' => 'OrcamentosPrototype', 'action' => 'lista'],
                'parity' => 'partial',
            ],
            'pg-novo' => [
                'title' => 'Novo orçamento',
                'module' => 'orcamentos',
                'legacy' => ['controller' => 'Orcamentos', 'action' => 'add'],
                'prototype' => ['controller' => 'OrcamentosPrototype', 'action' => 'view', 'novo'],
                'parity' => 'partial',
            ],
            'pg-os-lista' => [
                'title' => 'Lista OS',
                'module' => 'ordens',
                'legacy' => ['controller' => 'Ordensservico', 'action' => 'index'],
                'prototype' => ['controller' => 'OrdensservicoPrototype', 'action' => 'lista'],
                'parity' => 'partial',
            ],
            'pg-clientes' => [
                'title' => 'Clientes',
                'module' => 'clientes',
                'legacy' => ['controller' => 'Clientes', 'action' => 'index'],
                'prototype' => ['controller' => 'ClientesPrototype', 'action' => 'lista'],
                'parity' => 'partial',
            ],
            'pg-cliente-360' => [
                'title' => 'Cliente 360°',
                'module' => 'clientes',
                'legacy' => ['controller' => 'Clientes', 'action' => 'visao360'],
                'prototype' => ['controller' => 'ClientesPrototype', 'action' => 'cliente360'],
                'parity' => 'partial',
            ],
            'pg-produtos' => [
                'title' => 'Produtos',
                'module' => 'produtos',
                'legacy' => ['controller' => 'Produtos', 'action' => 'index'],
                'prototype' => ['controller' => 'ProdutosPrototype', 'action' => 'lista'],
                'parity' => 'partial',
            ],
            'pg-estoque' => [
                'title' => 'Estoque',
                'module' => 'produtos',
                'legacy' => ['controller' => 'Produtos', 'action' => 'estoque'],
                'prototype' => ['controller' => 'ProdutosPrototype', 'action' => 'estoque'],
                'parity' => 'partial',
            ],
            'pg-financeiro' => [
                'title' => 'Financeiro',
                'module' => 'financeiro',
                'legacy' => ['controller' => 'Financeiro', 'action' => 'index'],
                'prototype' => ['controller' => 'FinanceiroPrototype', 'action' => 'lista'],
                'parity' => 'partial',
            ],
            'pg-bancos' => [
                'title' => 'Bancos',
                'module' => 'bancos',
                'legacy' => ['controller' => 'FinanceiroBancos', 'action' => 'index'],
                'prototype' => ['controller' => 'BancosPrototype', 'action' => 'lista'],
                'parity' => 'partial',
            ],
            'pg-fornecedores' => [
                'title' => 'Fornecedores',
                'module' => 'fornecedores',
                'legacy' => ['controller' => 'Clientes', 'action' => 'index'],
                'prototype' => ['controller' => 'FornecedoresPrototype', 'action' => 'lista'],
                'parity' => 'partial',
            ],
            'pg-sd-dashboard' => [
                'title' => 'SD Dashboard',
                'module' => 'servicedesk',
                'legacy' => ['controller' => 'Servicedesk', 'action' => 'index'],
                'prototype' => ['controller' => 'ServicedeskPrototype', 'action' => 'index'],
                'parity' => 'partial',
            ],
        ],
    ],
];
