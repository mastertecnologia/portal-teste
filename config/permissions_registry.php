<?php
/**
 * Catálogo mestre de permissões (RBAC + ABAC).
 * RBAC: acesso por papel a controller/ação.
 * ABAC: escopo (empresa, cliente, próprio) para filtrar recurso no runtime.
 *
 * Sincronizar com a base: PermissoesController::adminSyncRegistry
 */
$order = 0;
$n = static function () use (&$order) {
	$order += 10;
	return $order;
};

return [
	// —— Painel administrativo / Config ——
	['code' => 'config.index', 'name' => 'Painel administrativo (visão geral)', 'module' => 'Painel administrativo', 'controller' => 'Config', 'action' => 'index', 'perm_type' => 'rbac', 'abac_scope' => null, 'description' => 'Acesso ao hub de configurações.', 'sort_order' => $n()],
	['code' => 'config.acessos', 'name' => 'Login externo e horários', 'module' => 'Painel administrativo', 'controller' => 'Config', 'action' => 'acessos', 'perm_type' => 'rbac', 'abac_scope' => null, 'description' => 'URL pública, e-mails diretores, horário comercial.', 'sort_order' => $n()],
	['code' => 'config.emailsuporte', 'name' => 'E-mail suporte (tickets)', 'module' => 'Painel administrativo', 'controller' => 'Config', 'action' => 'emailsuporte', 'perm_type' => 'rbac', 'abac_scope' => null, 'description' => 'Destino de e-mails do Service Desk.', 'sort_order' => $n()],
	['code' => 'config.pastas', 'name' => 'Diretórios do sistema', 'module' => 'Painel administrativo', 'controller' => 'Config', 'action' => 'pastas', 'perm_type' => 'rbac', 'abac_scope' => null, 'description' => 'Paths de origem/destino no servidor.', 'sort_order' => $n()],
	['code' => 'config.financeiro', 'name' => 'Parâmetros financeiros', 'module' => 'Painel administrativo', 'controller' => 'Config', 'action' => 'financeiro', 'perm_type' => 'rbac', 'abac_scope' => null, 'description' => 'Configurações do módulo financeiro.', 'sort_order' => $n()],
	['code' => 'permissoes.admin', 'name' => 'Catálogo RBAC/ABAC e matriz', 'module' => 'Painel administrativo', 'controller' => 'Permissoes', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => null, 'description' => 'Gestão de permissões e papéis.', 'sort_order' => $n()],

	// —— Empresas operadora & equipe (PGM/Master) ——
	['code' => 'empresas.manage', 'name' => 'Empresas operadoras', 'module' => 'Empresas e equipe', 'controller' => 'Empresas', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Cadastro de empresas do grupo operador.', 'sort_order' => $n()],
	['code' => 'users.equipe', 'name' => 'Usuários da equipe (PGM/Master)', 'module' => 'Empresas e equipe', 'controller' => 'Users', 'action' => 'index', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Lista de usuários internos.', 'sort_order' => $n()],
	['code' => 'users.equipe_add', 'name' => 'Incluir usuário da equipe', 'module' => 'Empresas e equipe', 'controller' => 'Users', 'action' => 'add', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Criar login interno.', 'sort_order' => $n()],
	['code' => 'users.equipe_edit', 'name' => 'Editar usuário da equipe', 'module' => 'Empresas e equipe', 'controller' => 'Users', 'action' => 'edit', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Alterar dados, filas e nível.', 'sort_order' => $n()],
	['code' => 'empresasusers.manage', 'name' => 'Vínculos empresa ↔ usuário', 'module' => 'Empresas e equipe', 'controller' => 'Empresasusers', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Quem acessa qual empresa.', 'sort_order' => $n()],
	['code' => 'queues.admin', 'name' => 'Filas, níveis e técnicos', 'module' => 'Empresas e equipe', 'controller' => 'Queues', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Filas de atendimento por empresa.', 'sort_order' => $n()],

	// —— Portal: clientes e usuários cliente ——
	['code' => 'clientes.manage', 'name' => 'Clientes (cadastro)', 'module' => 'Portal clientes', 'controller' => 'Clientes', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'CRUD de clientes; ABAC: restringe por empresa do workspace.', 'sort_order' => $n()],
	['code' => 'users.clientes_index', 'name' => 'Usuários do portal (cliente)', 'module' => 'Portal clientes', 'controller' => 'Users', 'action' => 'indexClientes', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Lista logins de cliente.', 'sort_order' => $n()],
	['code' => 'users.cliente_add', 'name' => 'Incluir usuário cliente', 'module' => 'Portal clientes', 'controller' => 'Users', 'action' => 'addcliente', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Criar acesso ao portal para cliente.', 'sort_order' => $n()],
	['code' => 'users.cliente_edit', 'name' => 'Editar usuário cliente', 'module' => 'Portal clientes', 'controller' => 'Users', 'action' => 'editcliente', 'perm_type' => 'rbac', 'abac_scope' => 'cliente', 'description' => 'ABAC: apenas clientes da carteira permitida.', 'sort_order' => $n()],
	['code' => 'orcamentos.solicitar', 'name' => 'Portal — solicitar orçamento', 'module' => 'Portal clientes', 'controller' => 'Orcamentos', 'action' => 'solicitar', 'perm_type' => 'rbac', 'abac_scope' => 'cliente', 'description' => 'Pedido de proposta com sugestão do catálogo de produtos; atribuir aos papéis de cliente autorizados.', 'sort_order' => $n()],
	['code' => 'orcamentos.portal_cliente', 'name' => 'Portal — orçamentos (cliente)', 'module' => 'Portal clientes', 'controller' => 'Orcamentos', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => 'cliente', 'description' => 'Listagem e demais ações de orçamento no portal; combine com orcamentos.solicitar se quiser só consulta.', 'sort_order' => $n()],
	['code' => 'tickets.portal_cliente', 'name' => 'Portal — tickets (cliente)', 'module' => 'Portal clientes', 'controller' => 'Tickets', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => 'cliente', 'description' => 'Chamados no portal do cliente.', 'sort_order' => $n()],
	['code' => 'clientes.portal_edit', 'name' => 'Portal — cadastro da empresa', 'module' => 'Portal clientes', 'controller' => 'Clientes', 'action' => 'edit', 'perm_type' => 'rbac', 'abac_scope' => 'cliente', 'description' => 'Editar ficha do próprio cliente no portal.', 'sort_order' => $n()],

	// —— Menu principal (operacional) ——
	['code' => 'dashboard.view', 'name' => 'Dashboard', 'module' => 'Menu principal', 'controller' => 'Users', 'action' => 'dashboard', 'perm_type' => 'rbac', 'abac_scope' => null, 'description' => 'Painel inicial.', 'sort_order' => $n()],
	['code' => 'produtos.manage', 'name' => 'Produtos', 'module' => 'Menu principal', 'controller' => 'Produtos', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Catálogo e precificação.', 'sort_order' => $n()],
	['code' => 'ordensservico.list', 'name' => 'Ordens de serviço — listar', 'module' => 'Menu principal', 'controller' => 'Ordensservico', 'action' => 'index', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Listagem de OS.', 'sort_order' => $n()],
	['code' => 'ordensservico.create', 'name' => 'Ordens de serviço — criar', 'module' => 'Menu principal', 'controller' => 'Ordensservico', 'action' => 'add', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Abrir nova OS.', 'sort_order' => $n()],
	['code' => 'ordensservico.full', 'name' => 'Ordens de serviço — completo', 'module' => 'Menu principal', 'controller' => 'Ordensservico', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Todas as ações de OS (edição, relatórios, APIs conforme política).', 'sort_order' => $n()],
	['code' => 'servicedesk.tickets', 'name' => 'Tickets / Service Desk', 'module' => 'Menu principal', 'controller' => 'Servicedesk', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Interface de tickets.', 'sort_order' => $n()],
	['code' => 'tickets.api', 'name' => 'Tickets — APIs JSON', 'module' => 'Menu principal', 'controller' => 'Tickets', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Endpoints usados pela UI React / integrações.', 'sort_order' => $n()],

	// —— Operações ——
	['code' => 'orcamentos.manage', 'name' => 'Orçamentos', 'module' => 'Operações', 'controller' => 'Orcamentos', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Propostas comerciais.', 'sort_order' => $n()],
	['code' => 'faturas.locacao', 'name' => 'Locação / faturas', 'module' => 'Operações', 'controller' => 'Faturas', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Contratos de locação e faturamento.', 'sort_order' => $n()],
	['code' => 'prefaturamento.queue', 'name' => 'Pré-faturamento — fila OS', 'module' => 'Operações', 'controller' => 'Prefaturamento', 'action' => 'index', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Lista OS liberadas para faturamento.', 'sort_order' => $n()],
	['code' => 'prefaturamento.conferencia', 'name' => 'Pré-faturamento — conferências', 'module' => 'Operações', 'controller' => 'Prefaturamento', 'action' => 'conferencia', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Marcar conferência execução/comercial/fiscal na OS.', 'sort_order' => $n()],
	['code' => 'agenda.visitas', 'name' => 'Agenda / visitas', 'module' => 'Operações', 'controller' => 'Visitas', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Calendário e visitas técnicas.', 'sort_order' => $n()],
	['code' => 'agenda.alias', 'name' => 'Agenda (rota /agenda)', 'module' => 'Operações', 'controller' => 'Agenda', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Alias de agenda se utilizado.', 'sort_order' => $n()],
	['code' => 'bancosenhas.manage', 'name' => 'Banco de senhas', 'module' => 'Operações', 'controller' => 'Bancosenhas', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Credenciais — requer papel elevado em produção.', 'sort_order' => $n()],

	// —— Parâmetros OS ——
	['code' => 'problemas.os_tipos', 'name' => 'Tipos de OS (problemas)', 'module' => 'Parâmetros OS', 'controller' => 'Problemas', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => null, 'description' => 'Catálogo de tipos.', 'sort_order' => $n()],
	['code' => 'areas.os_status', 'name' => 'Status de OS (áreas)', 'module' => 'Parâmetros OS', 'controller' => 'Areas', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => null, 'description' => 'Fluxo de status.', 'sort_order' => $n()],

	// —— Suporte & operação (config hub) ——
	['code' => 'feriados.manage', 'name' => 'Feriados', 'module' => 'Suporte e operação', 'controller' => 'Feriados', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Calendário de feriados.', 'sort_order' => $n()],
	['code' => 'contratos.horas', 'name' => 'Contratos de horas', 'module' => 'Operações', 'controller' => 'ContratosHoras', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Gestão de contratos por hora.', 'sort_order' => $n()],
	['code' => 'clicontratos.manage', 'name' => 'Contratos cliente', 'module' => 'Operações', 'controller' => 'Clicontratos', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => 'empresa', 'description' => 'Contratos vinculados a clientes.', 'sort_order' => $n()],

	// —— Perfil & conta ——
	['code' => 'users.profile', 'name' => 'Meu perfil / senha', 'module' => 'Conta', 'controller' => 'Users', 'action' => 'change_profile', 'perm_type' => 'rbac', 'abac_scope' => 'own', 'description' => 'ABAC: apenas o próprio usuário.', 'sort_order' => $n()],
	['code' => 'users.password', 'name' => 'Alterar senha', 'module' => 'Conta', 'controller' => 'Users', 'action' => 'change_password', 'perm_type' => 'rbac', 'abac_scope' => 'own', 'description' => 'ABAC: próprio usuário ou admin conforme regra legada.', 'sort_order' => $n()],
	['code' => 'users.twofactor', 'name' => 'Verificação em duas etapas (conta própria)', 'module' => 'Conta', 'controller' => 'Users', 'action' => 'loginduasetapas', 'perm_type' => 'rbac', 'abac_scope' => 'own', 'description' => 'Ativar MFA (Google Authenticator). Já liberado na whitelist RBAC; entrada no catálogo para matriz.', 'sort_order' => $n()],
	['code' => 'users.twofactor_off', 'name' => 'Desativar 2FA (senha + código)', 'module' => 'Conta', 'controller' => 'Users', 'action' => 'desativaverificacao', 'perm_type' => 'rbac', 'abac_scope' => 'own', 'description' => 'Fluxo autenticado para desligar MFA.', 'sort_order' => $n()],

	// —— APIs integração (RBAC técnico) ——
	['code' => 'api.ordensservico', 'name' => 'API — Ordens de serviço (ERP)', 'module' => 'Integração API', 'controller' => 'Ordensservico', 'action' => 'listAPI', 'perm_type' => 'rbac', 'abac_scope' => null, 'description' => 'Token/header — mapear para service account.', 'sort_order' => $n()],
	['code' => 'api.produtos', 'name' => 'API — Produtos', 'module' => 'Integração API', 'controller' => 'Produtos', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => null, 'description' => 'Cadastro/listagem via API.', 'sort_order' => $n()],
	['code' => 'api.clientes', 'name' => 'API — Clientes', 'module' => 'Integração API', 'controller' => 'Clientes', 'action' => '*', 'perm_type' => 'rbac', 'abac_scope' => null, 'description' => 'Integração cadastro clientes.', 'sort_order' => $n()],

	// —— Papéis cliente (portal) ——
	['code' => 'portal.cliente_dashboard', 'name' => 'Portal — dashboard cliente', 'module' => 'Papel cliente', 'controller' => 'Users', 'action' => 'dashboard', 'perm_type' => 'abac', 'abac_scope' => 'cliente', 'description' => 'Usuário role=1: escopo dados do próprio cliente.', 'sort_order' => $n()],
];
