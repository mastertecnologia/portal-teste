/**
 * Mensagem legível para erros das APIs Laudos (axios).
 */
export function friendlyLaudosError(err) {
  const status = err?.response?.status;
  const body = err?.response?.data;
  let msg = err?.friendlyMessage;
  if (!msg && typeof body?.message === 'string') {
    msg = body.message;
  }
  if (!msg && body?.errors) {
    if (typeof body.errors === 'string') {
      msg = body.errors;
    } else {
      try {
        msg = JSON.stringify(body.errors);
      } catch {
        msg = null;
      }
    }
  }
  if (!msg) {
    msg = err?.message;
  }
  if (status === 403) {
    return 'Sem permissão para esta operação neste parecer ou empresa.';
  }
  if (status === 404) {
    return 'Registo ou ficheiro não encontrado.';
  }
  if (status === 413) {
    return 'Ficheiro demasiado grande para o servidor.';
  }
  if (status === 422) {
    return msg ? `Dados inválidos: ${msg}` : 'Dados inválidos. Verifique os campos.';
  }
  return msg || 'Não foi possível concluir a operação. Tente novamente.';
}
