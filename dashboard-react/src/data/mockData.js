/**
 * Dados de demonstração. Substituir por fetch() em src/lib/api.js
 * quando expuser endpoints JSON no CakePHP (Tickets, Ticketcomentarios).
 */

export const MOCK_SESSION_TECNICO = {
  role: 'tecnico',
  name: 'Equipe PGM',
  empresa: 'Master Empresa',
};

export const MOCK_SESSION_CLIENTE = {
  role: 'cliente',
  name: 'Cristiane Lazarotto',
  empresa: 'MOBLES FAB DE MOV LTDA',
  clienteId: 'c_mobles',
};

export const mockTickets = [
  {
    id: 1184,
    clienteId: 'c_mobles',
    cliente: 'MOBLES FAB DE MOV LTDA',
    assunto: 'Falha de autenticação em portal',
    descricao: 'Após a última atualização não consigo concluir o login no portal.',
    categoria: 'Acesso',
    prioridade: 'Alta',
    status: 'Em execução',
    responsavel: 'NOC 02',
    atualizado: 'há 14 min',
    comentarios: [
      { id: 1, autor: 'Cristiane Lazarotto', papel: 'cliente', texto: 'Segue o print do erro na tela de login.', quando: 'há 3 h' },
      { id: 2, autor: 'NOC 02', papel: 'tecnico', texto: 'Verificamos o certificado; teste novamente em modo anônimo.', quando: 'há 1 h' },
    ],
    anexos: [{ id: 'a1', nome: 'erro-login.png', quando: 'há 3 h' }],
  },
  {
    id: 1180,
    clienteId: 'c_kiwify',
    cliente: 'Kiwify',
    assunto: 'Lentidão no ambiente financeiro',
    descricao: 'Relatórios demoram mais de 2 minutos para abrir.',
    categoria: 'Performance',
    prioridade: 'Crítica',
    status: 'Aguardando técnico',
    responsavel: '—',
    atualizado: 'há 26 min',
    comentarios: [{ id: 1, autor: 'Financeiro Kiwify', papel: 'cliente', texto: 'Ocorre principalmente após as 14h.', quando: 'há 26 min' }],
    anexos: [],
  },
  {
    id: 1172,
    clienteId: 'c_mobles',
    cliente: 'MOBLES FAB DE MOV LTDA',
    assunto: 'Solicitação de novo usuário',
    descricao: 'Precisamos liberar acesso para mais um colaborador do financeiro.',
    categoria: 'Cadastro',
    prioridade: 'Baixa',
    status: 'Aguardando cliente',
    responsavel: 'Service Desk',
    atualizado: 'há 1 h',
    comentarios: [
      { id: 1, autor: 'Service Desk', papel: 'tecnico', texto: 'Envie nome completo e e-mail corporativo para cadastro.', quando: 'há 2 h' },
    ],
    anexos: [],
  },
  {
    id: 1169,
    clienteId: 'c_altaro',
    cliente: 'Altaro Partners',
    assunto: 'Erro ao enviar e-mail automático',
    descricao: 'Notificações do módulo X não chegam aos destinatários.',
    categoria: 'Correio',
    prioridade: 'Média',
    status: 'Resolvido',
    responsavel: 'Suporte N2',
    atualizado: 'há 2 h',
    comentarios: [
      { id: 1, autor: 'Suporte N2', papel: 'tecnico', texto: 'Ajustamos o SMTP na instância; favor validar o próximo disparo.', quando: 'há 2 h' },
    ],
    anexos: [],
  },
];

export function listTicketsForTecnico() {
  return [...mockTickets].sort((a, b) => b.id - a.id);
}

export function listTicketsForCliente(clienteId) {
  return mockTickets.filter((t) => t.clienteId === clienteId).sort((a, b) => b.id - a.id);
}

export function getTicketById(id) {
  const n = Number(id);
  return mockTickets.find((t) => t.id === n) ?? null;
}
