import { useMemo, useState } from 'react';
import {
  extractItems,
  extractLogRetorno,
  extractResumoRetorno,
  fetchBancos,
  processarRetorno,
} from '../../lib/financeiroApi.js';

function formatMoney(value) {
  const amount = Number(value || 0);
  return amount.toLocaleString('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  });
}

function statusTone(status) {
  switch (status) {
    case 'baixado':
      return 'border-[var(--pgm-badge-green-ring)] bg-[var(--pgm-badge-green-bg)] text-[var(--pgm-badge-green-text)]';
    case 'rejeitado':
      return 'border-[var(--pgm-badge-red-ring)] bg-[var(--pgm-badge-red-bg)] text-[var(--pgm-badge-red-text)]';
    case 'erro':
      return 'border-[var(--pgm-badge-red-ring)] bg-[var(--pgm-badge-red-bg)] text-[var(--pgm-badge-red-text)]';
    case 'ignorado':
      return 'border-[var(--pgm-badge-amber-ring)] bg-[var(--pgm-badge-amber-bg)] text-[var(--pgm-badge-amber-text)]';
    default:
      return 'border-[var(--pgm-badge-blue-ring)] bg-[var(--pgm-badge-blue-bg)] text-[var(--pgm-badge-blue-text)]';
  }
}

function SummaryCard({ label, value, tone = 'default' }) {
  const toneClass =
    tone === 'success'
      ? 'bg-[var(--pgm-badge-green-bg)] text-[var(--pgm-badge-green-text)]'
      : tone === 'danger'
        ? 'bg-[var(--pgm-badge-red-bg)] text-[var(--pgm-badge-red-text)]'
        : tone === 'warn'
          ? 'bg-[var(--pgm-badge-amber-bg)] text-[var(--pgm-badge-amber-text)]'
          : 'bg-[var(--pgm-badge-teal-bg)] text-[var(--pgm-badge-teal-text)]';

  return (
    <div className="rounded-xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-surface,#1a1f28)] p-4 shadow-[var(--pgm-shadow-sm)]">
      <div className="text-[0.7rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
        {label}
      </div>
      <div className={`mt-3 inline-flex rounded-full px-3 py-1 text-lg font-bold ${toneClass}`}>
        {value}
      </div>
    </div>
  );
}

function LogRow({ item }) {
  const status = item?.status || 'info';
  const tituloId = item?.titulo_id ? `#${item.titulo_id}` : '—';
  const nossoNumero = item?.nosso_numero || '—';
  const codigoOcorrencia = item?.codigo_ocorrencia || '—';
  const valorPago =
    item?.valor_pago !== undefined && item?.valor_pago !== null
      ? formatMoney(item.valor_pago)
      : null;

  return (
    <div className="rounded-xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-surface,#1a1f28)] p-4 shadow-[var(--pgm-shadow-sm)]">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div className="min-w-0">
          <div className="flex flex-wrap items-center gap-2">
            <span
              className={`inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.08em] ${statusTone(
                status,
              )}`}
            >
              {status}
            </span>
            <span className="font-mono text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
              Título {tituloId}
            </span>
          </div>

          <div className="mt-3 grid gap-2 sm:grid-cols-3">
            <div>
              <div className="text-[11px] uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                Nosso número
              </div>
              <div className="mt-1 font-mono text-sm text-[var(--pgm-text,#e8eaed)]">{nossoNumero}</div>
            </div>
            <div>
              <div className="text-[11px] uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                Ocorrência
              </div>
              <div className="mt-1 font-mono text-sm text-[var(--pgm-text,#e8eaed)]">{codigoOcorrencia}</div>
            </div>
            <div>
              <div className="text-[11px] uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                Valor pago
              </div>
              <div className="mt-1 font-mono text-sm text-[var(--pgm-text,#e8eaed)]">{valorPago || '—'}</div>
            </div>
          </div>

          <div className="mt-4 rounded-lg border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-base,#0c0f14)] px-3 py-2 text-sm leading-relaxed text-[var(--pgm-text-secondary,#c4c9d1)]">
            {item?.mensagem || 'Sem mensagem detalhada para este registro.'}
          </div>
        </div>
      </div>
    </div>
  );
}

export default function RetornoUpload({
  bancos: bancosProp = [],
  onProcessed,
}) {
  const [bancosState, setBancosState] = useState(bancosProp);
  const [arquivo, setArquivo] = useState(null);
  const [bancoId, setBancoId] = useState('');
  const [observacoes, setObservacoes] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [loadingBancos, setLoadingBancos] = useState(false);
  const [erro, setErro] = useState(null);
  const [resultado, setResultado] = useState(null);

  const bancos = useMemo(() => {
    if (Array.isArray(bancosProp) && bancosProp.length > 0) {
      return bancosProp;
    }
    return bancosState;
  }, [bancosProp, bancosState]);

  async function ensureBancosLoaded() {
    if (bancos.length > 0) return;
    setLoadingBancos(true);
    const resp = await fetchBancos();
    setLoadingBancos(false);

    if (!resp.ok) {
      setErro(resp.error || 'Não foi possível carregar os bancos.');
      return;
    }

    setBancosState(extractItems(resp));
  }

  async function handleFocusBanco() {
    if (bancos.length === 0 && !loadingBancos) {
      await ensureBancosLoaded();
    }
  }

  function handleFileChange(event) {
    const file = event.target.files?.[0] || null;
    setArquivo(file);
    setErro(null);
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setErro(null);

    if (!arquivo) {
      setErro('Selecione um arquivo .RET ou .TXT para processamento.');
      return;
    }

    setSubmitting(true);
    const resp = await processarRetorno({
      arquivo,
      bancoId: bancoId ? Number(bancoId) : null,
      observacoes,
    });
    setSubmitting(false);

    if (!resp.ok) {
      setResultado(null);
      setErro(resp.error || 'Falha ao processar o retorno bancário.');
      return;
    }

    setResultado(resp);
    if (typeof onProcessed === 'function') {
      onProcessed(resp);
    }
  }

  const resumo = extractResumoRetorno(resultado);
  const log = extractLogRetorno(resultado);
  const hasResult = Boolean(resultado?.ok);

  return (
    <div className="space-y-6">
      <div className="rounded-[var(--pgm-radius-2xl,20px)] border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[var(--pgm-bg-raised,#141820)] p-6 shadow-[var(--pgm-shadow-md)]">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <div className="inline-flex rounded-full bg-[var(--pgm-badge-blue-bg)] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-badge-blue-text)]">
              Retorno CNAB
            </div>
            <h2 className="mt-3 text-xl font-bold text-[var(--pgm-text,#e8eaed)]">
              Processar retorno bancário
            </h2>
            <p className="mt-2 max-w-3xl text-sm leading-relaxed text-[var(--pgm-text-secondary,#c4c9d1)]">
              Faça o upload do arquivo de retorno para aplicar baixas automáticas,
              marcar rejeições e visualizar um log amigável dos títulos liquidados,
              recusados ou ignorados.
            </p>
          </div>
        </div>

        <form className="mt-6 grid gap-4 lg:grid-cols-[1.2fr,0.8fr]" onSubmit={handleSubmit}>
          <div className="space-y-4">
            <div>
              <label className="mb-2 block text-xs font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                Arquivo de retorno
              </label>
              <label className="flex min-h-[110px] cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-base,#0c0f14)] px-4 py-6 text-center transition hover:border-[var(--pgm-primary,#1d9e75)] hover:bg-[rgba(29,158,117,0.04)]">
                <div className="text-sm font-semibold text-[var(--pgm-text,#e8eaed)]">
                  {arquivo ? arquivo.name : 'Clique para selecionar um arquivo .RET ou .TXT'}
                </div>
                <div className="mt-2 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
                  O processamento identifica segmentos T e U e atualiza os títulos automaticamente.
                </div>
                <input
                  type="file"
                  className="hidden"
                  accept=".ret,.txt"
                  onChange={handleFileChange}
                />
              </label>
            </div>

            <div>
              <label className="mb-2 block text-xs font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                Observações
              </label>
              <textarea
                value={observacoes}
                onChange={(e) => setObservacoes(e.target.value)}
                rows={4}
                className="w-full rounded-xl border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-base,#0c0f14)] px-4 py-3 text-sm text-[var(--pgm-text,#e8eaed)] outline-none transition placeholder:text-[var(--pgm-text-muted,#9aa0a8)] focus:border-[var(--pgm-primary,#1d9e75)] focus:ring-2 focus:ring-[rgba(29,158,117,0.18)]"
                placeholder="Ex.: Retorno recebido do banco no fechamento do dia."
              />
            </div>
          </div>

          <div className="space-y-4 rounded-xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[rgba(255,255,255,0.02)] p-4">
            <div>
              <label className="mb-2 block text-xs font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                Banco vinculado
              </label>
              <select
                value={bancoId}
                onChange={(e) => setBancoId(e.target.value)}
                onFocus={handleFocusBanco}
                className="w-full rounded-xl border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-base,#0c0f14)] px-4 py-3 text-sm text-[var(--pgm-text,#e8eaed)] outline-none transition focus:border-[var(--pgm-primary,#1d9e75)] focus:ring-2 focus:ring-[rgba(29,158,117,0.18)]"
              >
                <option value="">Detectar pelo retorno / qualquer banco</option>
                {bancos.map((banco) => (
                  <option key={banco.id} value={banco.id}>
                    {banco.codigo_banco || '—'} — {banco.nome || 'Banco'}
                  </option>
                ))}
              </select>
              <p className="mt-2 text-xs leading-relaxed text-[var(--pgm-text-muted,#9aa0a8)]">
                Se informado, restringe a conciliação ao banco selecionado. Caso contrário,
                o sistema tenta localizar o título pelo nosso número e histórico de remessa.
              </p>
            </div>

            <div className="rounded-lg border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-base,#0c0f14)] px-4 py-3 text-sm text-[var(--pgm-text-secondary,#c4c9d1)]">
              <div className="font-semibold text-[var(--pgm-text,#e8eaed)]">Regras do processamento</div>
              <ul className="mt-2 list-disc space-y-1 pl-5 text-xs leading-relaxed">
                <li>Liquidações atualizam status de cobrança e valor pago.</li>
                <li>Rejeições são traduzidas para mensagens legíveis.</li>
                <li>Ocorrências não conclusivas permanecem registradas no log.</li>
              </ul>
            </div>

            <button
              type="submit"
              disabled={submitting}
              className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-b from-[var(--pgm-primary,#1d9e75)] to-[#168a64] px-4 py-3 text-sm font-semibold text-white shadow-[var(--pgm-shadow-sm)] transition hover:-translate-y-px hover:shadow-[var(--pgm-shadow-md)] disabled:cursor-not-allowed disabled:opacity-60"
            >
              {submitting ? 'Processando retorno…' : 'Processar retorno'}
            </button>
          </div>
        </form>

        {erro ? (
          <div className="mt-4 rounded-xl border border-[var(--pgm-badge-red-ring)] bg-[var(--pgm-badge-red-bg)] px-4 py-3 text-sm text-[var(--pgm-badge-red-text)]">
            {erro}
          </div>
        ) : null}
      </div>

      {hasResult ? (
        <>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <SummaryCard label="Processados" value={resumo.processados || 0} />
            <SummaryCard label="Baixados" value={resumo.baixados || 0} tone="success" />
            <SummaryCard label="Rejeitados" value={resumo.rejeitados || 0} tone="danger" />
            <SummaryCard label="Ignorados" value={resumo.ignorados || 0} tone="warn" />
            <SummaryCard label="Erros" value={resumo.erros || 0} tone="danger" />
          </div>

          <div className="rounded-[var(--pgm-radius-2xl,20px)] border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[var(--pgm-bg-raised,#141820)] p-6 shadow-[var(--pgm-shadow-md)]">
            <div className="flex flex-wrap items-start justify-between gap-4">
              <div>
                <h3 className="text-lg font-bold text-[var(--pgm-text,#e8eaed)]">Log amigável do processamento</h3>
                <p className="mt-1 text-sm text-[var(--pgm-text-secondary,#c4c9d1)]">
                  Resultado consolidado dos títulos baixados, rejeitados, ignorados ou com erro.
                </p>
              </div>
              <div className="rounded-full bg-[var(--pgm-badge-teal-bg)] px-3 py-1 text-xs font-semibold uppercase tracking-[0.08em] text-[var(--pgm-badge-teal-text)]">
                {log.length} ocorrência(s)
              </div>
            </div>

            {log.length === 0 ? (
              <div className="mt-6 rounded-xl border border-dashed border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-base,#0c0f14)] px-4 py-6 text-center text-sm text-[var(--pgm-text-muted,#9aa0a8)]">
                O arquivo foi processado sem ocorrências detalhadas para exibir.
              </div>
            ) : (
              <div className="mt-6 space-y-4">
                {log.map((item, index) => (
                  <LogRow key={`${item?.status || 'item'}-${item?.titulo_id || index}-${index}`} item={item} />
                ))}
              </div>
            )}
          </div>
        </>
      ) : null}
    </div>
  );
}
