import axios from 'axios';

const DEFAULT_TIMEOUT_MS = 30000;

function getBoot() {
  if (typeof window === 'undefined') return null;
  return window.__FINANCEIRO_BOOT__ || null;
}

function buildBaseUrl() {
  const boot = getBoot();
  if (boot?.paths?.apiBase) return boot.paths.apiBase;
  return '';
}

const http = axios.create({
  baseURL: buildBaseUrl(),
  withCredentials: true,
  timeout: DEFAULT_TIMEOUT_MS,
  headers: {
    Accept: 'application/json',
  },
});

http.interceptors.response.use(
  (response) => response,
  (error) => {
    const httpStatus = error?.response?.status ?? 0;
    const payload = error?.response?.data ?? {};
    const message =
      payload?.error ||
      payload?.message ||
      error?.message ||
      'Falha na comunicação com o módulo financeiro.';

    return Promise.reject({
      ok: false,
      httpStatus,
      error: message,
      fields: payload?.fields || null,
      payload,
    });
  },
);

function normalizeSuccess(response) {
  const payload = response?.data ?? {};
  if (payload?.ok === false) {
    return {
      ok: false,
      httpStatus: response?.status ?? 200,
      error: payload?.error || payload?.message || 'Operação recusada pelo servidor.',
      fields: payload?.fields || null,
      data: payload?.data ?? null,
      meta: payload?.meta ?? null,
      payload,
    };
  }

  return {
    ok: true,
    httpStatus: response?.status ?? 200,
    data: payload?.data ?? payload,
    meta: payload?.meta ?? null,
    payload,
  };
}

function normalizeError(error) {
  if (error?.ok === false) {
    return error;
  }

  return {
    ok: false,
    httpStatus: error?.response?.status ?? 0,
    error:
      error?.response?.data?.error ||
      error?.response?.data?.message ||
      error?.message ||
      'Erro inesperado no módulo financeiro.',
    fields: error?.response?.data?.fields || null,
    payload: error?.response?.data ?? null,
  };
}

function ensureArray(value) {
  if (Array.isArray(value)) return value;
  if (value == null || value === '') return [];
  return [value];
}

function appendArray(formData, key, values) {
  ensureArray(values).forEach((value) => {
    if (value !== undefined && value !== null && value !== '') {
      formData.append(key, String(value));
    }
  });
}

function buildQuery(params = {}) {
  const query = new URLSearchParams();

  Object.entries(params).forEach(([key, value]) => {
    if (Array.isArray(value)) {
      value.forEach((item) => {
        if (item !== undefined && item !== null && item !== '') {
          query.append(key, String(item));
        }
      });
      return;
    }

    if (value !== undefined && value !== null && value !== '') {
      query.set(key, String(value));
    }
  });

  const qs = query.toString();
  return qs ? `?${qs}` : '';
}

function inferPaths() {
  const boot = getBoot();
  const fromBoot = boot?.paths || {};

  return {
    bancosList:
      fromBoot.apiBancosList ||
      fromBoot.apiBancos ||
      '/financeiro-bancos/api-lista',
    bancosSave:
      fromBoot.apiBancosSave ||
      '/financeiro-bancos/api-salvar',
    remessasListarTitulos:
      fromBoot.apiRemessasListarTitulos ||
      '/financeiro/remessas/listar-titulos',
    remessasGerar:
      fromBoot.apiRemessasGerar ||
      '/financeiro/remessas/gerar',
    retornosProcessar:
      fromBoot.apiRetornosProcessar ||
      '/financeiro/retornos/processar',
  };
}

export function getFinanceiroBoot() {
  return getBoot();
}

export function getFinanceiroPaths() {
  return inferPaths();
}

export async function fetchBancos(params = {}) {
  const paths = inferPaths();

  try {
    const response = await http.get(`${paths.bancosList}${buildQuery(params)}`);
    return normalizeSuccess(response);
  } catch (error) {
    return normalizeError(error);
  }
}

export async function saveBanco(payload = {}) {
  const paths = inferPaths();

  try {
    const response = await http.post(paths.bancosSave, payload, {
      headers: {
        'Content-Type': 'application/json',
      },
    });
    return normalizeSuccess(response);
  } catch (error) {
    return normalizeError(error);
  }
}

export async function listarTitulosRemessa(params = {}) {
  const paths = inferPaths();

  try {
    const response = await http.get(
      `${paths.remessasListarTitulos}${buildQuery(params)}`,
    );
    return normalizeSuccess(response);
  } catch (error) {
    return normalizeError(error);
  }
}

export async function gerarRemessa(payload = {}) {
  const paths = inferPaths();

  try {
    const response = await http.post(paths.remessasGerar, payload, {
      headers: {
        'Content-Type': 'application/json',
      },
    });
    return normalizeSuccess(response);
  } catch (error) {
    return normalizeError(error);
  }
}

export async function processarRetorno({
  arquivo,
  bancoId,
  observacoes,
} = {}) {
  const paths = inferPaths();

  const formData = new FormData();
  if (arquivo) {
    formData.append('arquivo', arquivo);
  }
  if (bancoId) {
    formData.append('banco_id', String(bancoId));
  }
  if (observacoes) {
    formData.append('observacoes', String(observacoes));
  }

  try {
    const response = await http.post(paths.retornosProcessar, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return normalizeSuccess(response);
  } catch (error) {
    return normalizeError(error);
  }
}

export async function carregarModuloBancos({
  empresas = [],
  bancoId,
  busca,
} = {}) {
  const [bancosResp, titulosResp] = await Promise.all([
    fetchBancos({
      q: busca,
      empresas,
    }),
    listarTitulosRemessa({
      empresas,
      banco_id: bancoId,
      q: busca,
    }),
  ]);

  return {
    ok: bancosResp.ok && titulosResp.ok,
    bancos: bancosResp,
    titulos: titulosResp,
    error: !bancosResp.ok
      ? bancosResp.error
      : !titulosResp.ok
        ? titulosResp.error
        : null,
  };
}

export function createBancoPayload(values = {}) {
  return {
    id: values.id || null,
    codigo_banco: values.codigo_banco || '',
    numero_banco: values.numero_banco || values.codigo_banco || '',
    cnab: values.cnab || '',
    nome: values.nome || '',
    numero_agencia: values.numero_agencia || '',
    digito_agencia: values.digito_agencia || '',
    numero_conta: values.numero_conta || '',
    digito_conta: values.digito_conta || '',
    convenio: values.convenio || '',
    carteira: values.carteira || '',
    cnab_tipo: values.cnab_tipo || '240',
    proxima_remessa:
      values.proxima_remessa === undefined || values.proxima_remessa === null
        ? 1
        : Number(values.proxima_remessa),
    codigo_banco_interno: values.codigo_banco_interno || '',
    verifica_receber: values.verifica_receber || '',
    utiliza_endosso: values.utiliza_endosso || '',
    logotipo: values.logotipo || '',
    observacoes: values.observacoes || '',
    ativo: values.ativo !== false,
  };
}

export function createRemessaPayload({
  tituloIds = [],
  empresas = [],
  bancoId,
  multiempresa = false,
  observacoes = '',
} = {}) {
  return {
    titulo_ids: ensureArray(tituloIds).map((item) => Number(item)).filter(Boolean),
    empresas: ensureArray(empresas).map((item) => Number(item)).filter(Boolean),
    banco_id: bancoId ? Number(bancoId) : null,
    multiempresa: Boolean(multiempresa),
    observacoes: observacoes || '',
  };
}

export function createRetornoPayload({ arquivo, bancoId, observacoes } = {}) {
  return {
    arquivo: arquivo || null,
    bancoId: bancoId ? Number(bancoId) : null,
    observacoes: observacoes || '',
  };
}

export function extractItems(result) {
  if (!result?.ok) return [];
  const data = result.data || {};
  return data.items || [];
}

export function extractTotais(result) {
  if (!result?.ok) {
    return {
      titulos: 0,
      valor_total: 0,
    };
  }

  const data = result.data || {};
  return data.totais || {
    titulos: 0,
    valor_total: 0,
  };
}

export function extractLogRetorno(result) {
  if (!result?.ok) return [];
  const data = result.data || result.payload || {};
  return data.log || result.payload?.log || [];
}

export function extractResumoRetorno(result) {
  if (!result?.ok) {
    return {
      processados: 0,
      baixados: 0,
      rejeitados: 0,
      ignorados: 0,
      erros: 0,
    };
  }

  const payload = result.payload || {};
  return payload.resumo || result.data?.resumo || {
    processados: 0,
    baixados: 0,
    rejeitados: 0,
    ignorados: 0,
    erros: 0,
  };
}

export { http, appendArray };
