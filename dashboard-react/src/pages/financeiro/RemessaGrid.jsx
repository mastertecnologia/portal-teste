import { useEffect, useMemo, useState } from 'react';
import {
  createRemessaPayload,
  extractItems,
  extractTotais,
  gerarRemessa,
  listarTitulosRemessa,
} from '../../lib/financeiroApi.js';

function money(v) {
  const n = Number(v || 0);
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(n);
}

function dateBr(v) {
  if (!v) return '—';
  const d = new Date(`${v}T00:00:00`);
  if (Number.isNaN(d.getTime())) return v;
  return d.toLocaleDateString('pt-BR');
}

function cx(...classes) {
  return classes.filter(Boolean).join(' ');
}

function StatusBadge({ status, elegivel }) {
  const map = {
    sem_cobranca: 'bg-[var(--pgm-badge-muted-bg)] text-[var(--pgm-badge-muted-text)] ring-[var(--pgm-badge-muted-ring)]',
    pendente_remessa: 'bg-[var(--pgm-badge-amber-bg)] text-[var(--pgm-badge-amber-text)] ring-[var(--pgm-badge-amber-ring)]',
    remetido: 'bg-[var(--pgm-badge-blue-bg)] text-[var(--pgm-badge-blue-text)] ring-[var(--pgm-badge-blue-ring)]',
    registrado: 'bg-[var(--pgm-badge-teal-bg)] text-[var(--pgm-badge-teal-text)] ring-[var(--pgm-badge-teal-ring,var(--pgm-badge-teal-ring,rgba(29,158,117,0.30)))]',
    liquidado: 'bg-[var(--pgm-badge-green-bg)] text-[var(--pgm-badge-green-text)] ring-[var(--pgm-badge-green-ring)]',
    rejeitado: 'bg-[var(--pgm-badge-red-bg)] text-[var(--pgm-badge-red-text)] ring-[var(--pgm-badge-red-ring)]',
  };

  const labelMap = {
    sem_cobranca: 'Sem cobrança',
    pendente_remessa: 'Pendente remessa',
    remetido: 'Remetido',
    registrado: 'Registrado',
    liquidado: 'Liquidado',
    rejeitado: 'Rejeitado',
  };

  const tone = map[status] || map.sem_cobranca;
  const label = labelMap[status] || status || '—';

  return (
    <span
      className={cx(
        'inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset',
        tone,
      )}
      title={elegivel ? label : `${label} · título bloqueado para remessa`}
    >
      {label}
    </span>
  );
}

export default function RemessaGrid({
  empresas = [],
  bancos = [],
  onRemessaGerada,
}) {
  const [loading, setLoading] = useState(true);
  const [enviando, setEnviando] = useState(false);
  const [erro, setErro] = useState('');
  const [feedback, setFeedback] = useState('');
  const [items, setItems] = useState([]);
  const [totais, setTotais] = useState({ titulos: 0, valor_total: 0 });
  const [selecionados, setSelecionados] = useState({});
  const [busca, setBusca] = useState('');
  const [bancoId, setBancoId] = useState('');
  const [empresasSelecionadas, setEmpresasSelecionadas] = useState(
    Array.isArray(empresas) ? empresas.map((e) => Number(e.id || e.value || e)).filter(Boolean) : [],
  );
  const [multiempresa, setMultiempresa] = useState(false);
  const [observacoes, setObservacoes] = useState('');

  const empresasDisponiveis = useMemo(() => {
    return (empresas || []).map((empresa) => {
      if (typeof empresa === 'number') {
        return { id: empresa, nome: `Empresa ${empresa}` };
      }
      return {
        id: Number(empresa.id || empresa.value || 0),
        nome: empresa.nome || empresa.label || `Empresa ${empresa.id || empresa.value || ''}`,
      };
    }).filter((empresa) => empresa.id > 0);
  }, [empresas]);

  const bancosDisponiveis = useMemo(() => {
    return (bancos || []).map((banco) => ({
      id: Number(banco.id || 0),
      nome: banco.nome || 'Banco',
      codigo_banco: banco.codigo_banco || '',
    })).filter((banco) => banco.id > 0);
  }, [bancos]);

  useEffect(() => {
    if (empresasDisponiveis.length > 0 && empresasSelecionadas.length === 0) {
      setEmpresasSelecionadas(empresasDisponiveis.map((empresa) => empresa.id));
    }
  }, [empresasDisponiveis, empresasSelecionadas.length]);

  useEffect(() => {
    let cancelled = false;

    async function load() {
      setLoading(true);
      setErro('');
      setFeedback('');

      const response = await listarTitulosRemessa({
        empresas: empresasSelecionadas,
        banco_id: bancoId || undefined,
        q: busca || undefined,
      });

      if (cancelled) return;

      if (!response.ok) {
        setItems([]);
        setTotais({ titulos: 0, valor_total: 0 });
        setErro(response.error || 'Falha ao carregar títulos para remessa.');
        setLoading(false);
        return;
      }

      setItems(extractItems(response));
      setTotais(extractTotais(response));
      setLoading(false);
    }

    load();

    return () => {
      cancelled = true;
    };
  }, [empresasSelecionadas, bancoId, busca]);

  const itensElegiveis = useMemo(
    () => items.filter((item) => item.elegivel),
    [items],
  );

  const selecionadosIds = useMemo(() => {
    return Object.entries(selecionados)
      .filter(([, ativo]) => Boolean(ativo))
      .map(([id]) => Number(id))
      .filter(Boolean);
  }, [selecionados]);

  const selecionadosRows = useMemo(() => {
    const setIds = new Set(selecionadosIds);
    return items.filter((item) => setIds.has(Number(item.id)));
  }, [items, selecionadosIds]);

  const totalSelecionado = useMemo(() => {
    return selecionadosRows.reduce((acc, item) => acc + Number(item.valor || 0), 0);
  }, [selecionadosRows]);

  const gruposSelecionados = useMemo(() => {
    const map = new Map();

    selecionadosRows.forEach((item) => {
      const banco = item.banco || {};
      const key = `${banco.id || 0}-${banco.convenio || ''}-${banco.carteira || ''}-${banco.cnab_tipo || ''}`;
      if (!map.has(key)) {
        map.set(key, {
          key,
          bancoId: banco.id || 0,
          bancoNome: banco.nome || 'Banco',
          bancoCodigo: banco.codigo_banco || '',
          convenio: banco.convenio || '—',
          carteira: banco.carteira || '—',
          cnabTipo: banco.cnab_tipo || '240',
          quantidade: 0,
          valor: 0,
          empresas: new Set(),
        });
      }
      const group = map.get(key);
      group.quantidade += 1;
      group.valor += Number(item.valor || 0);
      group.empresas.add(Number(item.empresa_id || 0));
    });

    return Array.from(map.values()).map((group) => ({
      ...group,
      empresas: Array.from(group.empresas).filter(Boolean).sort((a, b) => a - b),
    }));
  }, [selecionadosRows]);

  const allEligibleSelected =
    itensElegiveis.length > 0 &&
    itensElegiveis.every((item) => selecionados[Number(item.id)]);

  function toggleEmpresa(id) {
    const num = Number(id);
    setEmpresasSelecionadas((prev) => {
      const has = prev.includes(num);
      if (has) return prev.filter((item) => item !== num);
      return [...prev, num];
    });
  }

  function toggleTitulo(id) {
    const num = Number(id);
    setSelecionados((prev) => ({
      ...prev,
      [num]: !prev[num],
    }));
  }

  function toggleTodosElegiveis() {
    if (allEligibleSelected) {
      const next = { ...selecionados };
      itensElegiveis.forEach((item) => {
        delete next[Number(item.id)];
      });
      setSelecionados(next);
      return;
    }

    const next = { ...selecionados };
    itensElegiveis.forEach((item) => {
      next[Number(item.id)] = true;
    });
    setSelecionados(next);
  }

  async function onSubmitRemessa() {
    if (selecionadosIds.length === 0) {
      setErro('Selecione pelo menos um título elegível.');
      return;
    }

    setEnviando(true);
    setErro('');
    setFeedback('');

    const payload = createRemessaPayload({
      tituloIds: selecionadosIds,
      empresas: empresasSelecionadas,
      bancoId: bancoId || undefined,
      multiempresa,
      observacoes,
    });

    const response = await gerarRemessa(payload);
    setEnviando(false);

    if (!response.ok) {
      const itens = Array.isArray(response.payload?.items) ? response.payload.items : [];
      const detalhe = itens.length
        ? ` ${itens.map((item) => `#${item.id}: ${item.motivo}`).join(' | ')}`
        : '';
      setErro((response.error || 'Falha ao gerar remessa.') + detalhe);
      return;
    }

    const remessas = response.data?.items || [];
    const msg =
      remessas.length === 1
        ? `Remessa gerada com sucesso: ${remessas[0]?.arquivo?.nome || 'arquivo gerado'}.`
        : `${remessas.length} arquivo(s) de remessa gerados com sucesso.`;

    setFeedback(msg);
    setSelecionados({});
    setObservacoes('');

    if (typeof onRemessaGerada === 'function') {
      onRemessaGerada(response);
    }

    const refresh = await listarTitulosRemessa({
      empresas: empresasSelecionadas,
      banco_id: bancoId || undefined,
      q: busca || undefined,
    });

    if (refresh.ok) {
      setItems(extractItems(refresh));
      setTotais(extractTotais(refresh));
    }
  }

  return (
    <section className="rounded-[var(--pgm-radius-2xl,20px)] border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[color-mix(in_srgb,var(--pgm-bg-surface,#1a1f28)_96%,rgba(255,255,255,0.04))] p-5 shadow-[var(--pgm-shadow-md)]">
      <div className="flex flex-col gap-4 border-b border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] pb-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <p className="text-[0.7rem] font-semibold uppercase tracking-[0.12em] text-[var(--pgm-primary,#1d9e75)]">
            Remessa CNAB
          </p>
          <h2 className="mt-1 text-xl font-bold text-[var(--pgm-text,#e8eaed)]">
            Seleção múltipla de títulos
          </h2>
          <p className="mt-1 max-w-3xl text-sm text-[var(--pgm-text-secondary,#c4c9d1)]">
            Selecione títulos em aberto, filtre por múltiplas empresas e gere remessa simples
            ou multiempresas agrupando por banco, convênio, carteira e layout.
          </p>
        </div>

        <div className="grid gap-3 rounded-2xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-raised,#141820)] p-4 sm:grid-cols-3">
          <div>
            <div className="text-[10px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
              Títulos listados
            </div>
            <div className="mt-1 text-lg font-bold text-[var(--pgm-text,#e8eaed)]">
              {totais.titulos || 0}
            </div>
          </div>
          <div>
            <div className="text-[10px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
              Valor total
            </div>
            <div className="mt-1 text-lg font-bold text-[var(--pgm-text,#e8eaed)]">
              {money(totais.valor_total || 0)}
            </div>
          </div>
          <div>
            <div className="text-[10px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
              Selecionado
            </div>
            <div className="mt-1 text-lg font-bold text-[var(--pgm-badge-teal-text,#5cdbc0)]">
              {money(totalSelecionado)}
            </div>
          </div>
        </div>
      </div>

      <div className="mt-5 grid gap-4 xl:grid-cols-[1.45fr_minmax(320px,0.55fr)]">
        <div className="space-y-4">
          <div className="grid gap-3 rounded-2xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-raised,#141820)] p-4">
            <div className="grid gap-3 lg:grid-cols-[1.3fr_0.9fr]">
              <label className="grid gap-1">
                <span className="text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                  Buscar título
                </span>
                <input
                  type="text"
                  value={busca}
                  onChange={(e) => setBusca(e.target.value)}
                  placeholder="Descrição, ID ou nosso número"
                  className="rounded-xl border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-base,#0c0f14)] px-3 py-2 text-sm text-[var(--pgm-text,#e8eaed)] outline-none transition focus:border-[var(--pgm-primary,#1d9e75)] focus:ring-2 focus:ring-[var(--pgm-primary-muted,rgba(29,158,117,0.14))]"
                />
              </label>

              <label className="grid gap-1">
                <span className="text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                  Banco
                </span>
                <select
                  value={bancoId}
                  onChange={(e) => setBancoId(e.target.value)}
                  className="rounded-xl border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-base,#0c0f14)] px-3 py-2 text-sm text-[var(--pgm-text,#e8eaed)] outline-none transition focus:border-[var(--pgm-primary,#1d9e75)] focus:ring-2 focus:ring-[var(--pgm-primary-muted,rgba(29,158,117,0.14))]"
                >
                  <option value="">Todos os bancos</option>
                  {bancosDisponiveis.map((banco) => (
                    <option key={banco.id} value={banco.id}>
                      {banco.codigo_banco ? `${banco.codigo_banco} — ` : ''}
                      {banco.nome}
                    </option>
                  ))}
                </select>
              </label>
            </div>

            <div className="grid gap-3">
              <div>
                <div className="text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                  Empresas
                </div>
                <div className="mt-2 flex flex-wrap gap-2">
                  {empresasDisponiveis.length === 0 ? (
                    <span className="text-sm text-[var(--pgm-text-muted,#9aa0a8)]">
                      Nenhuma empresa disponível.
                    </span>
                  ) : (
                    empresasDisponiveis.map((empresa) => {
                      const active = empresasSelecionadas.includes(empresa.id);
                      return (
                        <button
                          key={empresa.id}
                          type="button"
                          onClick={() => toggleEmpresa(empresa.id)}
                          className={cx(
                            'rounded-full px-3 py-1.5 text-xs font-semibold transition',
                            active
                              ? 'bg-[var(--pgm-badge-teal-bg)] text-[var(--pgm-badge-teal-text)] ring-1 ring-[var(--pgm-badge-teal-ring,rgba(29,158,117,0.30))]'
                              : 'bg-[var(--pgm-bg-base,#0c0f14)] text-[var(--pgm-text-secondary,#c4c9d1)] ring-1 ring-[var(--pgm-border,#3d4554)] hover:bg-[var(--pgm-bg-overlay,#2a3140)]',
                          )}
                        >
                          {empresa.nome}
                        </button>
                      );
                    })
                  )}
                </div>
              </div>

              <label className="inline-flex items-center gap-2 text-sm text-[var(--pgm-text-secondary,#c4c9d1)]">
                <input
                  type="checkbox"
                  checked={multiempresa}
                  onChange={(e) => setMultiempresa(e.target.checked)}
                  className="h-4 w-4 rounded border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-base,#0c0f14)] text-[var(--pgm-primary,#1d9e75)] focus:ring-[var(--pgm-primary,#1d9e75)]"
                />
                Remessa multiempresas (agrupar títulos por banco quando convênio/carteira permitirem)
              </label>
            </div>
          </div>

          {erro ? (
            <div className="rounded-xl border border-[var(--pgm-badge-red-ring)] bg-[var(--pgm-badge-red-bg)] px-4 py-3 text-sm text-[var(--pgm-badge-red-text)]">
              {erro}
            </div>
          ) : null}

          {feedback ? (
            <div className="rounded-xl border border-[var(--pgm-badge-green-ring)] bg-[var(--pgm-badge-green-bg)] px-4 py-3 text-sm text-[var(--pgm-badge-green-text)]">
              {feedback}
            </div>
          ) : null}

          <div className="overflow-hidden rounded-2xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-raised,#141820)]">
            <div className="flex items-center justify-between gap-3 border-b border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] px-4 py-3">
              <div>
                <div className="text-sm font-semibold text-[var(--pgm-text,#e8eaed)]">
                  Grid de títulos
                </div>
                <div className="text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
                  Marque os títulos elegíveis e gere o arquivo CNAB.
                </div>
              </div>

              <button
                type="button"
                onClick={toggleTodosElegiveis}
                disabled={itensElegiveis.length === 0}
                className="rounded-lg border border-[var(--pgm-border,#3d4554)] px-3 py-2 text-xs font-semibold text-[var(--pgm-text,#e8eaed)] transition hover:bg-[var(--pgm-bg-overlay,#2a3140)] disabled:cursor-not-allowed disabled:opacity-50"
              >
                {allEligibleSelected ? 'Limpar elegíveis' : 'Selecionar elegíveis'}
              </button>
            </div>

            <div className="overflow-x-auto">
              <table className="min-w-full text-left text-sm">
                <thead className="bg-[var(--pgm-bg-base,#0c0f14)]">
                  <tr>
                    <th className="px-3 py-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                      Sel.
                    </th>
                    <th className="px-3 py-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                      Título
                    </th>
                    <th className="px-3 py-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                      Cliente
                    </th>
                    <th className="px-3 py-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                      Banco
                    </th>
                    <th className="px-3 py-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                      Vencimento
                    </th>
                    <th className="px-3 py-3 text-right text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                      Valor
                    </th>
                    <th className="px-3 py-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                      Cobrança
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {loading ? (
                    <tr>
                      <td
                        colSpan={7}
                        className="px-4 py-10 text-center text-sm text-[var(--pgm-text-muted,#9aa0a8)]"
                      >
                        Carregando títulos para remessa…
                      </td>
                    </tr>
                  ) : items.length === 0 ? (
                    <tr>
                      <td
                        colSpan={7}
                        className="px-4 py-10 text-center text-sm text-[var(--pgm-text-muted,#9aa0a8)]"
                      >
                        Nenhum título encontrado para os filtros informados.
                      </td>
                    </tr>
                  ) : (
                    items.map((item) => {
                      const checked = Boolean(selecionados[Number(item.id)]);
                      return (
                        <tr
                          key={item.id}
                          className={cx(
                            'border-t border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))]',
                            item.elegivel
                              ? 'hover:bg-[var(--pgm-bg-overlay,#2a3140)]'
                              : 'bg-[rgba(255,255,255,0.02)] opacity-80',
                          )}
                        >
                          <td className="px-3 py-3 align-top">
                            <input
                              type="checkbox"
                              checked={checked}
                              disabled={!item.elegivel}
                              onChange={() => toggleTitulo(item.id)}
                              className="mt-1 h-4 w-4 rounded border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-base,#0c0f14)] text-[var(--pgm-primary,#1d9e75)] focus:ring-[var(--pgm-primary,#1d9e75)] disabled:cursor-not-allowed disabled:opacity-50"
                            />
                          </td>
                          <td className="px-3 py-3 align-top">
                            <div className="font-semibold text-[var(--pgm-text,#e8eaed)]">
                              #{item.id} — {item.descricao || 'Sem descrição'}
                            </div>
                            <div className="mt-1 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
                              Nosso número: {item.nosso_numero || 'Será gerado automaticamente'}
                            </div>
                            <div className="mt-1 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
                              Empresa #{item.empresa_id}
                            </div>
                            {!item.elegivel && item.motivo_bloqueio ? (
                              <div className="mt-2 text-xs text-[var(--pgm-badge-red-text,#ff9492)]">
                                {item.motivo_bloqueio}
                              </div>
                            ) : null}
                          </td>
                          <td className="px-3 py-3 align-top text-[var(--pgm-text-secondary,#c4c9d1)]">
                            {item.cliente?.nome || '—'}
                          </td>
                          <td className="px-3 py-3 align-top">
                            <div className="font-medium text-[var(--pgm-text,#e8eaed)]">
                              {item.banco?.codigo_banco ? `${item.banco.codigo_banco} — ` : ''}
                              {item.banco?.nome || '—'}
                            </div>
                            <div className="mt-1 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
                              {item.banco?.conta || 'Conta não informada'}
                            </div>
                            <div className="mt-1 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
                              Convênio {item.banco?.convenio || '—'} · Carteira {item.banco?.carteira || '—'} · CNAB {item.banco?.cnab_tipo || '240'}
                            </div>
                          </td>
                          <td className="px-3 py-3 align-top text-[var(--pgm-text-secondary,#c4c9d1)]">
                            {dateBr(item.data_vencimento)}
                          </td>
                          <td className="px-3 py-3 align-top text-right font-mono text-[var(--pgm-text,#e8eaed)]">
                            {money(item.valor)}
                          </td>
                          <td className="px-3 py-3 align-top">
                            <StatusBadge
                              status={item.status_cobranca}
                              elegivel={item.elegivel}
                            />
                          </td>
                        </tr>
                      );
                    })
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <aside className="space-y-4">
          <div className="rounded-2xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-raised,#141820)] p-4">
            <div className="text-sm font-semibold text-[var(--pgm-text,#e8eaed)]">
              Resumo da remessa
            </div>
            <div className="mt-1 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
              Confira o agrupamento antes de gerar os arquivos.
            </div>

            <div className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
              <div className="rounded-xl border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-base,#0c0f14)] px-4 py-3">
                <div className="text-[10px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                  Títulos selecionados
                </div>
                <div className="mt-1 text-lg font-bold text-[var(--pgm-text,#e8eaed)]">
                  {selecionadosIds.length}
                </div>
              </div>
              <div className="rounded-xl border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-base,#0c0f14)] px-4 py-3">
                <div className="text-[10px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                  Valor selecionado
                </div>
                <div className="mt-1 text-lg font-bold text-[var(--pgm-badge-teal-text,#5cdbc0)]">
                  {money(totalSelecionado)}
                </div>
              </div>
            </div>

            <div className="mt-4">
              <label className="grid gap-1">
                <span className="text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                  Observações
                </span>
                <textarea
                  value={observacoes}
                  onChange={(e) => setObservacoes(e.target.value)}
                  rows={4}
                  placeholder="Opcional: observações para a geração da remessa"
                  className="rounded-xl border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-base,#0c0f14)] px-3 py-2 text-sm text-[var(--pgm-text,#e8eaed)] outline-none transition focus:border-[var(--pgm-primary,#1d9e75)] focus:ring-2 focus:ring-[var(--pgm-primary-muted,rgba(29,158,117,0.14))]"
                />
              </label>
            </div>

            <button
              type="button"
              onClick={onSubmitRemessa}
              disabled={enviando || selecionadosIds.length === 0}
              className="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-b from-[var(--pgm-primary,#1d9e75)] to-[#168a64] px-4 py-3 text-sm font-semibold text-white shadow-[var(--pgm-shadow-sm)] transition hover:-translate-y-px hover:shadow-[var(--pgm-shadow-md)] disabled:cursor-not-allowed disabled:opacity-50"
            >
              {enviando ? 'Gerando remessa…' : 'Gerar arquivo CNAB'}
            </button>
          </div>

          <div className="rounded-2xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-raised,#141820)] p-4">
            <div className="text-sm font-semibold text-[var(--pgm-text,#e8eaed)]">
              Agrupamento por banco
            </div>
            <div className="mt-1 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
              Em modo multiempresas, os títulos são agrupados por banco, convênio, carteira e layout.
            </div>

            <div className="mt-4 space-y-3">
              {gruposSelecionados.length === 0 ? (
                <div className="rounded-xl border border-dashed border-[var(--pgm-border,#3d4554)] px-4 py-6 text-center text-sm text-[var(--pgm-text-muted,#9aa0a8)]">
                  Nenhum título selecionado.
                </div>
              ) : (
                gruposSelecionados.map((grupo) => (
                  <div
                    key={grupo.key}
                    className="rounded-xl border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-base,#0c0f14)] px-4 py-3"
                  >
                    <div className="font-semibold text-[var(--pgm-text,#e8eaed)]">
                      {grupo.bancoCodigo ? `${grupo.bancoCodigo} — ` : ''}
                      {grupo.bancoNome}
                    </div>
                    <div className="mt-1 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
                      Convênio {grupo.convenio} · Carteira {grupo.carteira} · CNAB {grupo.cnabTipo}
                    </div>
                    <div className="mt-2 grid gap-2 sm:grid-cols-3">
                      <div className="text-xs text-[var(--pgm-text-secondary,#c4c9d1)]">
                        <span className="block text-[10px] uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                          Títulos
                        </span>
                        {grupo.quantidade}
                      </div>
                      <div className="text-xs text-[var(--pgm-text-secondary,#c4c9d1)]">
                        <span className="block text-[10px] uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                          Empresas
                        </span>
                        {grupo.empresas.length > 0 ? grupo.empresas.map((id) => `#${id}`).join(', ') : '—'}
                      </div>
                      <div className="text-xs text-[var(--pgm-text-secondary,#c4c9d1)]">
                        <span className="block text-[10px] uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                          Valor
                        </span>
                        {money(grupo.valor)}
                      </div>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        </aside>
      </div>
    </section>
  );
}
