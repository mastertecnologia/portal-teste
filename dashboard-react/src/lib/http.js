import axios from 'axios';

function getFinanceiroBoot() {
  if (typeof window === 'undefined') return null;
  return window.__FINANCEIRO_BOOT__ || null;
}

function normalizeError(error) {
  const response = error?.response;
  const data = response?.data || {};

  return {
    ok: false,
    error:
      data.error ||
      data.message ||
      data.mensagem ||
      error?.message ||
      'Falha na comunicação com o servidor.',
    httpStatus: response?.status || 0,
    fields: data.fields || data.errors || null,
    data,
  };
}

const http = axios.create({
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

http.interceptors.request.use((config) => {
  const boot = getFinanceiroBoot();
  const next = { ...config };

  if (!next.baseURL && boot?.baseUrl) {
    next.baseURL = boot.baseUrl;
  }

  if (boot?.headers && typeof boot.headers === 'object') {
    next.headers = {
      ...boot.headers,
      ...(next.headers || {}),
    };
  }

  return next;
});

http.interceptors.response.use(
  (response) => response,
  (error) => Promise.reject(normalizeError(error)),
);

export { getFinanceiroBoot, normalizeError };
export default http;
