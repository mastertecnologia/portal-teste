<?php
/**
 * Constantes legado: tickets, ordens de serviço, produtos, visitas, notificações.
 *
 * IMPORTANTE: valores numéricos de situacao (OS e tickets) devem coincidir com o banco
 * existente. Se após deploy algo não bater, compare com SELECT DISTINCT situacao nas tabelas
 * tickets / ordensservico e ajuste apenas os defines escalares (não os rótulos HTML).
 */

// --- Tickets: situacao (tabela tickets.situacao) ---
define('C_TicketSituacaoPendente', 0);
define('C_TicketSituacaoEmandamento', 1);
define('C_TicketSituacaoResolvido', 2);
define('C_TicketSituacaoFechado', 3);
define('C_TicketSituacaoRespondido', 4);
define('C_TicketSituacaoCancelado', 5);

// --- Tickets: flags em ticketsmovs.sitnova (eventos / metadados) ---
define('C_TicketMovTransferencia', 20);
define('C_TicketMovMudancaFila', 21);
define('C_TicketAnexoAdicionado', 22);
define('C_TicketAnexoDeletado', 23);
define('C_TicketTimerIniciado', 24);
define('C_TicketTimerPausado', 25);
define('C_TicketTimerFinalizado', 26);

// --- Tickets: acao passada a TicketsTable::email($id, $acao, ...) ---
define('C_TicketCriado', -1);
define('C_TicketsAcaoPendente', 10);
define('C_TicketsAcaoEmandamento', 11);
define('C_TicketsAcaoFechado', 12);
define('C_TicketsAcaoAddComentario', 13);

// --- Notificacoes.tipo (mantém grafia Tikcet do legado) ---
define('C_NotificacaoTipoTikcet', 1);
define('C_NotificacaoTipoTikcetComentario', 2);

// --- Visitas.situacao ---
define('C_UserSituacaoAgendada', 0);
define('C_UserSituacaoFinalizada', 1);
define('C_UserSituacaoPendende', 2);
define('C_UserSituacaoCancelada', 3);

// --- Ordens de serviço: situacao (ordensservico.situacao) ---
define('C_OrdensSituacaoAberta', 0);
define('C_OrdensSituacaoEmExecucao', 1);
define('C_OrdensSituacaoCancelada', 2);
define('C_OrdensSituacaoLiberadaParaFaturamento', 3);
define('C_OrdensSituacaoFinalizada', 4);
define('C_OrdensSituacaoSincronizadaPeloGrid', 5);
define('C_OrdensSituacaoFaturada', 6);
define('C_OrdensSituacaoAtendInterno', 7);
define('C_OrdensSituacaoAtendExterno', 8);

define('C_OrdensContratoNao', 0);
define('C_OrdensContratoSim', 1);
define('C_OrdensPrioridadeNormal', 1);

define('C_ProdutosTipoProduto', 0);
define('C_ProdutosTipoServico', 1);
define('C_ProdutosTipoLicenca', 2);
define('C_ProdutosTipoLocacao', 3);

define('C_OrcamentoStatusPendente', 0);
define('C_OrcamentoStatusEnviado', 1);
define('C_OrcamentoStatusAprovado', 2);
define('C_OrcamentoStatusRecusado', 3);
define('C_OrcamentoStatusArquivado', 4);
define('C_OrcamentoStatusRascunho', -1);

define('C_LocacaoStatusPendente', 1);
define('C_LocacaoStatusAprovado', 2);
define('C_LocacaoStatusRejeitado', 3);
define('C_LocacaoStatusFinalizado', 4);

define('C_TicketCategoriaVisita', 3);

define('C_OrdensSituacao', [
	C_OrdensSituacaoAberta => 'Aberta',
	C_OrdensSituacaoEmExecucao => 'Em execução',
	C_OrdensSituacaoCancelada => 'Cancelada',
	C_OrdensSituacaoLiberadaParaFaturamento => 'Liberada para sincronização',
	C_OrdensSituacaoFinalizada => 'Finalizada',
	C_OrdensSituacaoSincronizadaPeloGrid => 'Sincronizada pelo Grid',
	C_OrdensSituacaoFaturada => 'Faturada',
	C_OrdensSituacaoAtendInterno => 'Atendimento interno',
	C_OrdensSituacaoAtendExterno => 'Atendimento externo',
]);

define('C_OrdensSituacaoOpcoes', [
	C_OrdensSituacaoAberta => 'Aberta',
	C_OrdensSituacaoEmExecucao => 'Em execução',
	C_OrdensSituacaoCancelada => 'Cancelada',
	C_OrdensSituacaoLiberadaParaFaturamento => 'Liberada para sincronização',
	C_OrdensSituacaoFinalizada => 'Finalizada',
	C_OrdensSituacaoSincronizadaPeloGrid => 'Sincronizada pelo Grid',
]);

define('C_OrdensLocacao', [
	0 => 'Próprio',
	1 => 'Locado',
]);

define('C_OrdensContrato', [
	C_OrdensContratoNao => 'Não',
	C_OrdensContratoSim => 'Sim',
]);

define('C_OrdensPrioridade', [
	0 => 'Baixa',
	1 => 'Normal',
	2 => 'Alta',
	3 => 'Urgente',
]);

define('C_OrdensAtendimento', [
	0 => 'Interno',
	1 => 'Externo',
	2 => 'Remoto',
]);

define('C_OrdensPagamento', [
	0 => 'À vista',
	1 => 'Parcelado',
	2 => 'Boleto',
	3 => 'Cartão',
]);

define('C_OrdensParcelas', [
	1 => '1',
	2 => '2',
	3 => '3',
	4 => '4',
	5 => '5',
	6 => '6',
	7 => '7',
	8 => '8',
	9 => '9',
	10 => '10',
	11 => '11',
	12 => '12',
]);

define('C_ProdutosTipo', [
	C_ProdutosTipoProduto => 'Produto',
	C_ProdutosTipoServico => 'Serviço',
	C_ProdutosTipoLicenca => 'Licença',
	C_ProdutosTipoLocacao => 'Locação',
]);

define('C_ProdutosAtivo', [
	0 => 'Não',
	1 => 'Sim',
]);

define('C_ClientesTipo', [
	C_ClientesTipoFisica => 'Pessoa Física',
	C_ClientesTipoJuridica => 'Pessoa Jurídica',
]);

define('C_VisitasSituacaoQuery', [
	C_UserSituacaoAgendada => 'Agendada',
	C_UserSituacaoFinalizada => 'Finalizada',
	C_UserSituacaoPendende => 'Pendente',
	C_UserSituacaoCancelada => 'Cancelada',
]);

define('C_ProtocolosArray', [
	'RDP',
	'SSH',
	'HTTP',
	'HTTPS',
	'FTP',
	'SFTP',
	'VPN',
	'TeamViewer',
	'AnyDesk',
]);

define('C_TicketCategoria', [
	1 => 'Dúvida',
	2 => 'Incidente',
	3 => 'Visita técnica',
	4 => 'Requisição',
	5 => 'Outros',
]);

define('C_TicketCategoriaClienteQuery', [
	1 => 'Dúvida',
	2 => 'Incidente',
	3 => 'Visita técnica',
	4 => 'Requisição',
	5 => 'Outros',
]);

define('C_TicketSituacoes', [
	C_TicketSituacaoPendente => 'Aguardando técnico',
	C_TicketSituacaoEmandamento => 'Em execução',
	C_TicketSituacaoResolvido => 'Resolvido',
	C_TicketSituacaoFechado => 'Fechado',
]);

define('C_TicketSituacoesCliente', [
	C_TicketSituacaoPendente => 'Aguardando técnico',
	C_TicketSituacaoEmandamento => 'Em execução',
	C_TicketSituacaoResolvido => 'Resolvido',
]);

define('C_TicketSituacoesFuncionario', [
	C_TicketSituacaoPendente => 'Aguardando técnico',
	C_TicketSituacaoEmandamento => 'Em execução',
	C_TicketSituacaoResolvido => 'Resolvido',
	C_TicketSituacaoFechado => 'Fechado',
	C_TicketSituacaoRespondido => 'Respondido',
]);

define('C_TicketSituacoesFechado', [
	C_TicketSituacaoPendente => 'Reabrir (aguardando técnico)',
]);

define('C_TicketSituacoesResolvidoRespondido', [
	C_TicketSituacaoFechado => 'Encerrar',
	C_TicketSituacaoEmandamento => 'Voltar para em execução',
	C_TicketSituacaoPendente => 'Voltar para aguardando técnico',
]);
