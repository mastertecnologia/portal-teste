import { useCallback, useEffect, useState } from 'react';
import {
  deleteWorkflowSlaPolicy,
  deleteWorkflowTransition,
  duplicateWorkflowSlaPolicy,
  fetchWorkflowSlaEmpresas,
  fetchWorkflowSlaLogs,
  fetchWorkflowSlaPolicies,
  fetchWorkflowStates,
  fetchWorkflowTransitions,
  saveWorkflowSlaPolicy,
  saveWorkflowTransition,
} from '../../lib/api';
import WorkflowSlaPolicyForm from './WorkflowSlaPolicyForm.jsx';
import WorkflowStateList from './WorkflowStateList.jsx';
import WorkflowTransitionList from './WorkflowTransitionList.jsx';

const toolbarField =
  'h-8 min-w-0 rounded-lg border border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] px-2.5 text-sm text-[var(--pgm-text)] outline-none transition focus:border-[var(--pgm-primary)]';

function wfSdHomeUrl(b) {
  const p = b?.paths || {};
  const fromBoot = p.servicedeskUrl || p.indexTecnico;
  if (typeof fromBoot === 'string' && fromBoot.trim() !== '') return fromBoot.trim();
  const w = b?.webroot;
  if (typeof w === 'string' && w !== '') {
    const base = w.endsWith('/') ? w : `${w}/`;
    return `${base}servicedesk`;
  }
  return '/servicedesk';
}

export default function WorkflowSlaAdmin({ boot }) {
  const sdUrl = wfSdHomeUrl(boot);
  const [tab, setTab] = useState('policies');
  const [policies, setPolicies] = useState([]);
  const [prioridadeMsg, setPrioridadeMsg] = useState('');
  const [states, setStates] = useState([]);
  const [transitions, setTransitions] = useState([]);
  const [logs, setLogs] = useState([]);
  const [empresas, setEmpresas] = useState([]);
  const [loading, setLoading] = useState(true);
  const [err, setErr] = useState('');
  const [okHint, setOkHint] = useState('');
  const [filters, setFilters] = useState({
    empresa_id: '',
    workflow_state_id: '',
    auto_escalar: '',
    pausa_sla: '',
    is_final: '',
    q: '',
  });
  const [formOpen, setFormOpen] = useState(false);
  const [editingPolicy, setEditingPolicy] = useState(null);
  const [formBusy, setFormBusy] = useState(false);
  const [transBusy, setTransBusy] = useState(null);
  const [newTrans, setNewTrans] = useState({ from_state_id: '', to_state_id: '', is_global: false });

  const reloadPolicies = useCallback(async () => {
    const f = {};
    if (filters.empresa_id && filters.empresa_id !== 'all') f.empresa_id = filters.empresa_id;
    if (filters.workflow_state_id) f.workflow_state_id = filters.workflow_state_id;
    if (filters.auto_escalar) f.auto_escalar = filters.auto_escalar;
    if (filters.pausa_sla) f.pausa_sla = filters.pausa_sla;
    if (filters.is_final) f.is_final = filters.is_final;
    if (filters.q.trim()) f.q = filters.q.trim();
    const r = await fetchWorkflowSlaPolicies(f);
    if (r.ok) {
      setPolicies(r.policies || []);
      setPrioridadeMsg(r.prioridade || '');
    } else {
      const extra = r.error && r.error !== 'not_found' ? ` (${r.error})` : '';
      setErr(`Erro ao carregar políticas SLA${extra}`);
    }
  }, [filters]);

  const loadAll = useCallback(async () => {
    setLoading(true);
    setErr('');
    const [rs, rt, re, rp] = await Promise.all([
      fetchWorkflowStates(),
      fetchWorkflowTransitions(),
      fetchWorkflowSlaEmpresas(),
      fetchWorkflowSlaPolicies({}),
    ]);
    if (rs.ok) setStates(rs.states || []);
    if (rt.ok) setTransitions(rt.transitions || []);
    if (re.ok) setEmpresas(re.empresas || []);
    if (rp.ok) {
      setPolicies(rp.policies || []);
      setPrioridadeMsg(rp.prioridade || '');
    }
    const rl = await fetchWorkflowSlaLogs(100);
    if (rl.ok) setLogs(rl.logs || []);
    const parts = [];
    const appendErr = (label, err) => {
      if (!err || err === 'not_found') parts.push(label);
      else parts.push(`${label} (${err})`);
    };
    if (!rs.ok) appendErr('Erro ao carregar estados do workflow', rs.error);
    if (!rt.ok) appendErr('Erro ao carregar transições', rt.error);
    if (!re.ok) appendErr('Erro ao carregar empresas', re.error);
    if (!rp.ok) appendErr('Erro ao carregar políticas SLA', rp.error);
    if (!rl.ok) appendErr('Erro ao carregar logs SLA', rl.error);
    if (parts.length) setErr(parts.join('. '));
    setLoading(false);
  }, []);

  useEffect(() => {
    loadAll();
  }, [loadAll]);

  useEffect(() => {
    const t = setTimeout(() => {
      reloadPolicies();
    }, 300);
    return () => clearTimeout(t);
  }, [filters, reloadPolicies]);

  const openCreate = () => {
    setEditingPolicy(null);
    setFormOpen(true);
  };
  const openEdit = (p) => {
    setEditingPolicy(p);
    setFormOpen(true);
  };

  const onSavePolicy = async (payload) => {
    setFormBusy(true);
    setErr('');
    const id = editingPolicy?.id;
    const r = await saveWorkflowSlaPolicy(id, payload, id ? 'PATCH' : 'POST');
    setFormBusy(false);
    if (!r.ok) {
      setErr((r.errors && JSON.stringify(r.errors)) || r.error || 'Erro ao salvar');
      return;
    }
    setFormOpen(false);
    setOkHint('Política salva.');
    setTimeout(() => setOkHint(''), 4000);
    await reloadPolicies();
  };

  const onDeletePolicy = async (id) => {
    if (!window.confirm('Excluir esta política de SLA?')) return;
    const r = await deleteWorkflowSlaPolicy(id);
    if (!r.ok) {
      setErr(r.error || 'Erro ao excluir');
      return;
    }
    setOkHint('Política excluída.');
    setTimeout(() => setOkHint(''), 3000);
    await reloadPolicies();
  };

  const onDuplicatePolicy = async (p) => {
    const def = states.find((s) => Number(s.id) !== Number(p.workflow_state_id));
    const wid = window.prompt(
      'ID do estado de destino para a cópia (obrigatório — deve ser diferente do original):',
      def ? String(def.id) : '',
    );
    if (!wid) return;
    const r = await duplicateWorkflowSlaPolicy(p.id, { workflow_state_id: Number(wid) });
    if (!r.ok) {
      setErr((r.errors && JSON.stringify(r.errors)) || r.error || 'Duplicar falhou');
      return;
    }
    setOkHint('Política duplicada.');
    setTimeout(() => setOkHint(''), 3000);
    await reloadPolicies();
  };

  const onSaveTransition = async (id, body) => {
    setTransBusy(id);
    const r = await saveWorkflowTransition(body, id);
    setTransBusy(null);
    if (!r.ok) {
      window.alert(r.error || 'Erro ao salvar transição');
      return { ok: false };
    }
    const rt = await fetchWorkflowTransitions();
    if (rt.ok) setTransitions(rt.transitions || []);
    return { ok: true };
  };

  const onDeleteTransition = async (id) => {
    if (!window.confirm('Excluir transição?')) return;
    setTransBusy(id);
    const r = await deleteWorkflowTransition(id);
    setTransBusy(null);
    if (!r.ok) {
      window.alert(r.error || 'Erro');
      return;
    }
    const rt = await fetchWorkflowTransitions();
    if (rt.ok) setTransitions(rt.transitions || []);
  };

  const submitNewTransition = async () => {
    const r = await saveWorkflowTransition({
      from_state_id: Number(newTrans.from_state_id),
      to_state_id: Number(newTrans.to_state_id),
      is_global: newTrans.is_global,
    });
    if (!r.ok) {
      window.alert(r.error || 'Erro');
      return;
    }
    setNewTrans({ from_state_id: '', to_state_id: '', is_global: false });
    const rt = await fetchWorkflowTransitions();
    if (rt.ok) setTransitions(rt.transitions || []);
  };

  return (
    <div className="tickets-react-tech flex min-h-0 w-full min-w-0 max-w-full flex-1 flex-col overflow-visible px-4 pb-6 pt-4 text-[var(--pgm-text)] sm:px-5">
      <header className="mb-3 border-b border-[var(--pgm-border-subtle)] pb-3">
        <p className="m-0 text-[0.7rem] font-semibold uppercase tracking-[0.12em] text-[var(--pgm-primary)]">Service Desk</p>
        <div className="mt-1 flex flex-wrap items-center justify-between gap-2">
          <h2 className="m-0 text-[1.1rem] font-bold text-[var(--pgm-text)]">Workflow & SLA</h2>
          <a
            href={sdUrl}
            className="inline-flex items-center gap-1 rounded-lg border border-[var(--pgm-border)] px-3 py-1.5 text-[0.8125rem] font-medium text-[var(--pgm-text)] no-underline hover:bg-[var(--pgm-bg-overlay)]"
          >
            ← Voltar ao Service Desk
          </a>
        </div>
        {prioridadeMsg ? (
          <p className="mt-2 text-xs text-[var(--pgm-text-muted)]">{prioridadeMsg}</p>
        ) : null}
      </header>

      {okHint ? (
        <div
          className="mb-3 rounded-lg border border-[var(--pgm-badge-green-ring)] bg-[var(--pgm-badge-green-bg)] px-3 py-2 text-sm text-[var(--pgm-badge-green-text)]"
          role="status"
        >
          {okHint}
        </div>
      ) : null}
      {err ? (
        <div className="mb-3 rounded-lg border border-[var(--pgm-badge-red-ring)] bg-[rgba(220,51,15,0.12)] px-3 py-2 text-sm text-[var(--pgm-badge-red-text)]">
          {err}
        </div>
      ) : null}

      <div className="mb-3 flex flex-wrap gap-2">
        {['policies', 'states', 'transitions', 'logs'].map((k) => (
          <button
            key={k}
            type="button"
            onClick={() => setTab(k)}
            className={`rounded-full border px-3 py-1 text-xs font-semibold ${
              tab === k
                ? 'border-[var(--pgm-primary)] bg-[rgba(29,158,117,0.18)] text-[var(--pgm-primary-hover)]'
                : 'border-[var(--pgm-border)] text-[var(--pgm-text-muted)]'
            }`}
          >
            {k === 'policies' ? 'Políticas SLA' : k === 'states' ? 'Estados' : k === 'transitions' ? 'Transições' : 'Logs SLA'}
          </button>
        ))}
      </div>

      {tab === 'policies' ? (
        <>
          <div className="pgm-card mb-3 flex flex-wrap gap-2 border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-surface)] p-3">
            <select
              className={toolbarField}
              value={filters.empresa_id}
              onChange={(e) => setFilters((f) => ({ ...f, empresa_id: e.target.value }))}
            >
              <option value="all">Todas empresas (visão)</option>
              <option value="global">Só globais</option>
              {empresas.map((e) => (
                <option key={e.id} value={String(e.id)}>
                  Empresa #{e.id}
                </option>
              ))}
            </select>
            <select
              className={toolbarField}
              value={filters.workflow_state_id}
              onChange={(e) => setFilters((f) => ({ ...f, workflow_state_id: e.target.value }))}
            >
              <option value="">Todos estados</option>
              {states.map((s) => (
                <option key={s.id} value={String(s.id)}>
                  {s.nome}
                </option>
              ))}
            </select>
            <select
              className={toolbarField}
              value={filters.auto_escalar}
              onChange={(e) => setFilters((f) => ({ ...f, auto_escalar: e.target.value }))}
            >
              <option value="">Auto-escalar: todos</option>
              <option value="1">Sim</option>
              <option value="0">Não</option>
            </select>
            <select
              className={toolbarField}
              value={filters.pausa_sla}
              onChange={(e) => setFilters((f) => ({ ...f, pausa_sla: e.target.value }))}
            >
              <option value="">Pausa SLA: todos</option>
              <option value="1">Pausados</option>
              <option value="0">Não pausa</option>
            </select>
            <select
              className={toolbarField}
              value={filters.is_final}
              onChange={(e) => setFilters((f) => ({ ...f, is_final: e.target.value }))}
            >
              <option value="">Estado final: todos</option>
              <option value="1">Final</option>
              <option value="0">Não final</option>
            </select>
            <input
              className={`${toolbarField} min-w-[10rem] flex-1`}
              placeholder="Busca (estado, código, empresa)"
              value={filters.q}
              onChange={(e) => setFilters((f) => ({ ...f, q: e.target.value }))}
            />
            <button
              type="button"
              className="rounded-lg bg-gradient-to-b from-[var(--pgm-primary)] to-[#168a64] px-4 py-2 text-xs font-semibold text-white"
              onClick={() => openCreate()}
            >
              Nova política
            </button>
          </div>

          {!loading && policies.length === 0 ? (
            <div className="rounded-xl border border-dashed border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] p-8 text-center">
              <p className="text-sm text-[var(--pgm-text-muted)]">Nenhuma política cadastrada para os filtros.</p>
              <button
                type="button"
                className="mt-3 rounded-lg bg-[var(--pgm-primary)] px-4 py-2 text-sm font-semibold text-white"
                onClick={() => openCreate()}
              >
                Criar primeira regra
              </button>
            </div>
          ) : (
            <div className="overflow-x-auto rounded-lg border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-surface)]">
              <table className="pgm-table min-w-full text-[0.8125rem]">
                <thead className="bg-[var(--pgm-bg-elevated)] text-left text-[var(--pgm-text-muted)]">
                  <tr>
                    <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Empresa</th>
                    <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Estado</th>
                    <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Resp. min</th>
                    <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Resol. min</th>
                    <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Pausa</th>
                    <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Final</th>
                    <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Auto</th>
                    <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Escalar →</th>
                    <th className="px-3 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Toler. min</th>
                    <th className="px-3 py-2 text-right text-[0.65rem] font-semibold uppercase tracking-[0.08em]">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  {policies.map((p) => (
                    <tr key={p.id} className="border-b border-[var(--pgm-border-subtle)]">
                      <td className="px-3 py-2">
                        {p.scope === 'global' ? (
                          <span className="rounded-full bg-[rgba(45,170,225,0.15)] px-2 py-0.5 text-[10px] font-semibold text-[#2DAAE1]">
                            Global
                          </span>
                        ) : (
                          <span className="text-[var(--pgm-text)]">{p.empresa_nome || `#${p.empresa_id}`}</span>
                        )}
                      </td>
                      <td className="px-3 py-2">{p.estado_nome || p.workflow_state_id}</td>
                      <td className="px-3 py-2 font-mono text-[var(--pgm-text-muted)]">{p.resposta_minutos ?? '—'}</td>
                      <td className="px-3 py-2 font-mono text-[var(--pgm-text-muted)]">{p.resolucao_minutos ?? '—'}</td>
                      <td className="px-3 py-2">{p.pausa_sla ? 'Sim' : 'Não'}</td>
                      <td className="px-3 py-2">{p.is_final ? 'Sim' : 'Não'}</td>
                      <td className="px-3 py-2">{p.auto_escalar ? 'Sim' : 'Não'}</td>
                      <td className="max-w-[8rem] truncate px-3 py-2" title={p.escalate_to_nome || ''}>
                        {p.escalate_to_nome || '—'}
                      </td>
                      <td className="px-3 py-2 font-mono">{p.escalate_after_minutos ?? 0}</td>
                      <td className="px-3 py-2 text-right text-xs">
                        <button type="button" className="text-[var(--pgm-primary-hover)]" onClick={() => openEdit(p)}>
                          Editar
                        </button>
                        <span className="mx-1 text-[var(--pgm-text-muted)]">|</span>
                        <button type="button" className="text-[var(--pgm-text-secondary)]" onClick={() => onDuplicatePolicy(p)}>
                          Duplicar
                        </button>
                        <span className="mx-1 text-[var(--pgm-text-muted)]">|</span>
                        <button type="button" className="text-[var(--pgm-badge-red-text)]" onClick={() => onDeletePolicy(p.id)}>
                          Excluir
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </>
      ) : null}

      {tab === 'states' ? <WorkflowStateList states={states} loading={loading && !states.length} /> : null}

      {tab === 'transitions' ? (
        <div className="space-y-4">
          <div className="rounded-lg border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-elevated)] p-3">
            <p className="text-[0.65rem] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted)]">Nova transição</p>
            <div className="mt-2 flex flex-wrap items-end gap-2">
              <select
                className={toolbarField}
                value={newTrans.from_state_id}
                onChange={(e) => setNewTrans((t) => ({ ...t, from_state_id: e.target.value }))}
              >
                <option value="">Origem…</option>
                {states.map((s) => (
                  <option key={s.id} value={String(s.id)}>
                    {s.nome}
                  </option>
                ))}
              </select>
              <select
                className={toolbarField}
                value={newTrans.to_state_id}
                onChange={(e) => setNewTrans((t) => ({ ...t, to_state_id: e.target.value }))}
              >
                <option value="">Destino…</option>
                {states.map((s) => (
                  <option key={s.id} value={String(s.id)}>
                    {s.nome}
                  </option>
                ))}
              </select>
              <label className="flex items-center gap-2 text-xs">
                <input
                  type="checkbox"
                  checked={newTrans.is_global}
                  onChange={(e) => setNewTrans((t) => ({ ...t, is_global: e.target.checked }))}
                />
                Global
              </label>
              <button
                type="button"
                className="rounded-lg bg-[var(--pgm-primary)] px-3 py-2 text-xs font-semibold text-white"
                onClick={() => submitNewTransition()}
              >
                Adicionar
              </button>
            </div>
          </div>
          <WorkflowTransitionList
            transitions={transitions}
            states={states}
            loading={loading && !transitions.length}
            onSave={onSaveTransition}
            onDelete={onDeleteTransition}
            busyId={transBusy}
          />
        </div>
      ) : null}

      {tab === 'logs' ? (
        <div className="overflow-x-auto rounded-lg border border-[var(--pgm-border-subtle)] bg-[var(--pgm-bg-surface)]">
          <table className="pgm-table min-w-full text-[0.8125rem]">
            <thead className="bg-[var(--pgm-bg-elevated)]">
              <tr>
                <th className="px-3 py-2 text-left text-[0.65rem] font-semibold uppercase text-[var(--pgm-text-muted)]">Ticket</th>
                <th className="px-3 py-2 text-left text-[0.65rem] font-semibold uppercase text-[var(--pgm-text-muted)]">Origem→Destino</th>
                <th className="px-3 py-2 text-left text-[0.65rem] font-semibold uppercase text-[var(--pgm-text-muted)]">Motivo</th>
                <th className="px-3 py-2 text-left text-[0.65rem] font-semibold uppercase text-[var(--pgm-text-muted)]">Quando</th>
              </tr>
            </thead>
            <tbody>
              {logs.length === 0 ? (
                <tr>
                  <td colSpan={4} className="px-3 py-6 text-center text-sm text-[var(--pgm-text-muted)]">
                    Sem registros de escalonamento (ou tabela ainda não migrada).
                  </td>
                </tr>
              ) : (
                logs.map((l) => (
                  <tr key={l.id} className="border-b border-[var(--pgm-border-subtle)]">
                    <td className="px-3 py-2 font-mono">#{l.ticket_id}</td>
                    <td className="px-3 py-2 text-[var(--pgm-text-secondary)]">
                      {l.workflow_state_from ?? '—'} → {l.workflow_state_to ?? '—'}
                    </td>
                    <td className="px-3 py-2">{l.reason_code || '—'}</td>
                    <td className="px-3 py-2 text-[var(--pgm-text-muted)]">
                      {l.created_at ? new Date(l.created_at).toLocaleString('pt-BR') : '—'}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      ) : null}

      {formOpen ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" role="dialog">
          <div className="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-[var(--pgm-border)] bg-[var(--pgm-bg-surface)] p-5 shadow-xl">
            <h3 className="mb-3 text-lg font-bold text-[var(--pgm-text)]">{editingPolicy ? 'Editar política' : 'Nova política'}</h3>
            <WorkflowSlaPolicyForm
              empresas={empresas}
              states={states}
              initial={editingPolicy}
              submitting={formBusy}
              onCancel={() => setFormOpen(false)}
              onSubmit={onSavePolicy}
            />
          </div>
        </div>
      ) : null}
    </div>
  );
}
