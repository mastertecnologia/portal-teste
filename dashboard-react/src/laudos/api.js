/**
 * API client do módulo Laudos — usa axios com credenciais de sessão CakePHP.
 * Usa o mesmo padrão de http do projeto (withCredentials + X-Requested-With).
 * Com APP_BASE=/portal, `window.__TICKETS_BOOT__.webroot` (definido no react_app.ctp) evita pedidos a /api em vez de /portal/api.
 */
import axios from 'axios';

/** @returns {string} ex.: "/" ou "/portal/" */
function pgmWebrootPrefix() {
  if (typeof window === 'undefined') {
    return '/';
  }
  const w = window.__TICKETS_BOOT__?.webroot;
  if (typeof w !== 'string' || w === '') {
    return '/';
  }
  return w.endsWith('/') ? w : `${w}/`;
}

/** @returns {string} ex.: "/api" ou "/portal/api" */
function pgmLaudosApiBase() {
  const root = pgmWebrootPrefix();
  const trimmed = root === '/' ? '' : root.replace(/\/+$/, '');
  return `${trimmed}/api`;
}

const api = axios.create({
  baseURL: '/api',
  timeout: 30000,
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

api.interceptors.request.use((config) => {
  config.baseURL = pgmLaudosApiBase();
  return config;
});

api.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response?.status === 401) {
      window.location.href = `${pgmWebrootPrefix()}users/login`;
      return Promise.reject(error);
    }
    const msg =
      error.response?.data?.message ||
      (typeof error.response?.data?.errors === 'string' ? error.response.data.errors : null) ||
      error.message ||
      'Erro desconhecido';
    return Promise.reject({ ...error, friendlyMessage: msg });
  }
);

// ---- Pareceres ----
export const PareceresAPI = {
  list(params = {}) { return api.get('/laudos/pareceres', { params }); },
  get(id) { return api.get(`/laudos/pareceres/${id}`); },
  create(data = {}) { return api.post('/laudos/pareceres', data); },
  update(id, data) { return api.put(`/laudos/pareceres/${id}`, data); },
  remove(id) { return api.delete(`/laudos/pareceres/${id}`); },
  duplicate(id) { return api.post(`/laudos/pareceres/${id}/duplicar`); },
  changeStatus(id, status) { return api.post(`/laudos/pareceres/${id}/status`, { status }); },
  history(id) { return api.get(`/laudos/pareceres/${id}/historico`); },
  pdfUrl(id) { return `${pgmLaudosApiBase()}/laudos/pareceres/${id}/pdf`; },
  sendEmail(id, payload) { return api.post(`/laudos/pareceres/${id}/enviar-email`, payload); },
};

// ---- Produtos ----
export const ProdutosAPI = {
  create(data) { return api.post('/laudos/produtos', data); },
  update(id, data) { return api.put(`/laudos/produtos/${id}`, data); },
  remove(id) { return api.delete(`/laudos/produtos/${id}`); },
};

// ---- Imagens ----
export const ImagensAPI = {
  upload(produtoId, file) {
    const fd = new FormData();
    fd.append('produto_id', produtoId);
    fd.append('file', file);
    return api.post('/laudos/produto-imagens', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  remove(id) { return api.delete(`/laudos/produto-imagens/${id}`); },
};

// ---- Anexos ----
export const AnexosAPI = {
  upload(parecerId, file, descricao = '') {
    const fd = new FormData();
    fd.append('parecer_id', parecerId);
    fd.append('file', file);
    fd.append('descricao', descricao);
    return api.post('/laudos/anexos', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  downloadUrl(id) { return `${pgmLaudosApiBase()}/laudos/anexos/${id}/download`; },
  remove(id) { return api.delete(`/laudos/anexos/${id}`); },
};

// ---- Empresa emissora (laudos_empresas) ----
export const EmpresasAPI = {
  update(id, data) { return api.put(`/laudos/empresas/${id}`, data); },
  uploadLogo(empresaId, file) {
    const fd = new FormData();
    fd.append('file', file);
    return api.post(`/laudos/empresas/${empresaId}/logo`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  deleteLogo(empresaId) { return api.delete(`/laudos/empresas/${empresaId}/logo`); },
  uploadCarimbo(empresaId, file) {
    const fd = new FormData();
    fd.append('file', file);
    return api.post(`/laudos/empresas/${empresaId}/carimbo`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  deleteCarimbo(empresaId) { return api.delete(`/laudos/empresas/${empresaId}/carimbo`); },
};

// ---- Catálogo ----
export const CatalogoAPI = {
  pecas(query = '') { return api.get('/laudos/catalogo/pecas', { params: { q: query } }); },
  addPeca(data) { return api.post('/laudos/catalogo/pecas', data); },
  servicos(query = '') { return api.get('/laudos/catalogo/servicos', { params: { q: query } }); },
};

// ---- Templates ----
export const TemplatesAPI = {
  list(tipo) { return api.get(`/laudos/templates/${tipo}`); },
};

// ---- Clientes (busca JSON via LaudosController) ----
export const ClientesAPI = {
  search(query) {
    const path = `${pgmWebrootPrefix()}laudos/clientes-buscar`;
    return axios.get(path, {
      params: { q: query },
      withCredentials: true,
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    }).then((r) => r.data);
  },
};

// ---- Util (CNPJ / CEP) ----
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
