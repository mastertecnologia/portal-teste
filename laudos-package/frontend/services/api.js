/**
 * API client para o módulo Laudos / Parecer Técnico.
 *
 * Usa axios. Se seu projeto usa fetch, substitua os métodos pelos equivalentes.
 * Configure baseURL e interceptors no arquivo de boot do seu sistema.
 */
import axios from 'axios';

// =============================================================================
// CLIENTE AXIOS
// =============================================================================
// Se você já tem um axios configurado no projeto, importe-o em vez de criar um novo.
// import { api } from '@/services/api'  // <-- exemplo

const api = axios.create({
  baseURL: '/api',
  timeout: 30000,
  withCredentials: true,  // envia cookies de sessão CakePHP
  headers: {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

// Interceptor de response: trata erros padronizados
api.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response?.status === 401) {
      // sessão expirada — redireciona para login do sistema principal
      window.location.href = '/login';
      return Promise.reject(error);
    }

    const msg = error.response?.data?.message
      || error.response?.data?.errors
      || error.message
      || 'Erro desconhecido';
    return Promise.reject({ ...error, friendlyMessage: msg });
  }
);

// =============================================================================
// PARECERES
// =============================================================================
export const PareceresAPI = {
  /** Lista pareceres com filtros opcionais. */
  list(params = {}) {
    return api.get('/laudos/pareceres', { params });
  },

  /** Detalhe completo de um parecer. */
  get(id) {
    return api.get(`/laudos/pareceres/${id}`);
  },

  /** Cria novo parecer. Retorna o parecer com numero gerado. */
  create(data = {}) {
    return api.post('/laudos/pareceres', data);
  },

  /** Atualiza parecer (auto-save usa esse endpoint). */
  update(id, data) {
    return api.put(`/laudos/pareceres/${id}`, data);
  },

  /** Soft delete. */
  remove(id) {
    return api.delete(`/laudos/pareceres/${id}`);
  },

  /** Duplica parecer existente. */
  duplicate(id) {
    return api.post(`/laudos/pareceres/${id}/duplicar`);
  },

  /** Muda status. */
  changeStatus(id, status) {
    return api.post(`/laudos/pareceres/${id}/status`, { status });
  },

  /** Histórico do parecer. */
  history(id) {
    return api.get(`/laudos/pareceres/${id}/historico`);
  },

  /** URL para download do PDF (use com window.open). */
  pdfUrl(id) {
    return `/api/laudos/pareceres/${id}/pdf`;
  },

  /** Envia parecer por e-mail. */
  sendEmail(id, payload) {
    return api.post(`/laudos/pareceres/${id}/enviar-email`, payload);
  },
};

// =============================================================================
// PRODUTOS / EQUIPAMENTOS
// =============================================================================
export const ProdutosAPI = {
  create(data) {
    return api.post('/laudos/produtos', data);
  },
  update(id, data) {
    return api.put(`/laudos/produtos/${id}`, data);
  },
  remove(id) {
    return api.delete(`/laudos/produtos/${id}`);
  },
};

// =============================================================================
// IMAGENS
// =============================================================================
export const ImagensAPI = {
  /**
   * Upload de imagem (já comprimida no frontend).
   * @param {File|Blob} file - já comprimido via imageCompression.js
   * @param {number} produtoId
   */
  upload(produtoId, file) {
    const formData = new FormData();
    formData.append('produto_id', produtoId);
    formData.append('file', file);
    return api.post('/laudos/produto-imagens', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  remove(id) {
    return api.delete(`/laudos/produto-imagens/${id}`);
  },
};

// =============================================================================
// ANEXOS
// =============================================================================
export const AnexosAPI = {
  upload(parecerId, file, descricao = '') {
    const formData = new FormData();
    formData.append('parecer_id', parecerId);
    formData.append('file', file);
    formData.append('descricao', descricao);
    return api.post('/laudos/anexos', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  downloadUrl(id) {
    return `/api/laudos/anexos/${id}/download`;
  },
};

// =============================================================================
// CATÁLOGO
// =============================================================================
export const CatalogoAPI = {
  pecas(query = '') {
    return api.get('/laudos/catalogo/pecas', { params: { q: query } });
  },
  addPeca(data) {
    return api.post('/laudos/catalogo/pecas', data);
  },
  servicos(query = '') {
    return api.get('/laudos/catalogo/servicos', { params: { q: query } });
  },
};

// =============================================================================
// TEMPLATES
// =============================================================================
export const TemplatesAPI = {
  /**
   * @param {'diagnostico'|'conclusao'|'objetivo'|'documentacao'} tipo
   */
  list(tipo) {
    return api.get(`/laudos/templates/${tipo}`);
  },
};

// =============================================================================
// CLIENTES (do sistema principal — endpoint que você já tem)
// =============================================================================
export const ClientesAPI = {
  /**
   * AJUSTE este endpoint para corresponder ao seu cadastro de clientes.
   * Esperado retornar: [{ id, razao_social, cnpj, telefone, email, cep, endereco, ... }]
   */
  search(query) {
    return api.get('/clientes/search', { params: { q: query } });
  },
};

// =============================================================================
// UTIL — CNPJ e CEP via backend (proxy para BrasilAPI/ViaCEP)
// =============================================================================
export const UtilAPI = {
  consultarCNPJ(cnpj) {
    const clean = cnpj.replace(/\D/g, '');
    return api.get(`/util/cnpj/${clean}`);
  },
  consultarCEP(cep) {
    const clean = cep.replace(/\D/g, '');
    return api.get(`/util/cep/${clean}`);
  },
};

export default api;
