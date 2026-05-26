import { useMemo, useState } from 'react';

const fieldClass =
  'mt-1 w-full rounded-lg border border-[var(--pgm-border)] bg-[var(--pgm-bg-elevated)] px-2.5 py-2 text-sm text-[var(--pgm-text)] outline-none focus:border-[var(--pgm-primary)]';

function slugifyCodigo(raw) {
  return String(raw || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/\s+/g, '_')
    .replace(/[^a-z0-9_]/g, '')
    .replace(/^_+/, '');
}

/** @param {{ initial?: object|null, submitting?: boolean, onCancel: () => void, onSubmit: (payload: object) => void }} props */
export default function WorkflowStateForm({ initial, submitting, onCancel, onSubmit }) {
  const [nome, setNome] = useState(initial?.nome || '');
  const [codigo, setCodigo] = useState(initial?.codigo || '');
  const [codigoTouched, setCodigoTouched] = useState(Boolean(initial?.codigo));
  const [isInicial, setIsInicial] = useState(Boolean(initial?.is_inicial));
  const [isFinal, setIsFinal] = useState(Boolean(initial?.is_final));

  const legacyHint = useMemo(() => {
    const c = slugifyCodigo(codigo);
    const known = ['aberto', 'emandamento', 'pendente', 'resolvido', 'fechado', 'aguardando_cliente', 'aguardandocliente'];
    if (!c) return null;
    if (known.some((k) => c === k || c.startsWith('aguardando_cliente'))) {
      return 'Este código sincroniza com a situação legada do ticket (tickets.situacao).';
    }
    return 'Códigos novos não alteram tickets.situacao automaticamente; use transições de workflow e políticas SLA por estado.';
  }, [codigo]);

  const handleNome = (v) => {
    setNome(v);
    if (!codigoTouched) {
      setCodigo(slugifyCodigo(v));
    }
  };

  const submit = (e) => {
    e.preventDefault();
    onSubmit({
      nome: nome.trim(),
      codigo: slugifyCodigo(codigo),
      is_inicial: isInicial,
      is_final: isFinal,
    });
  };

  return (
    <form onSubmit={submit} className="space-y-3">
      <label className="block text-xs font-semibold text-[var(--pgm-text-muted)]">
        Nome
        <input
          className={fieldClass}
          value={nome}
          onChange={(e) => handleNome(e.target.value)}
          required
          maxLength={120}
          autoFocus
        />
      </label>
      <label className="block text-xs font-semibold text-[var(--pgm-text-muted)]">
        Código (slug)
        <input
          className={`${fieldClass} font-mono`}
          value={codigo}
          onChange={(e) => {
            setCodigoTouched(true);
            setCodigo(slugifyCodigo(e.target.value));
          }}
          required
          maxLength={80}
          pattern="[a-z][a-z0-9_]*"
          title="Letras minúsculas, números e underscore"
        />
      </label>
      {legacyHint ? <p className="text-[11px] leading-snug text-[var(--pgm-text-muted)]">{legacyHint}</p> : null}
      <div className="flex flex-wrap gap-4 text-sm">
        <label className="flex items-center gap-2">
          <input
            type="checkbox"
            checked={isInicial}
            onChange={(e) => {
              setIsInicial(e.target.checked);
              if (e.target.checked) setIsFinal(false);
            }}
          />
          Estado inicial
        </label>
        <label className="flex items-center gap-2">
          <input
            type="checkbox"
            checked={isFinal}
            onChange={(e) => {
              setIsFinal(e.target.checked);
              if (e.target.checked) setIsInicial(false);
            }}
          />
          Estado final
        </label>
      </div>
      <div className="flex justify-end gap-2 pt-2">
        <button
          type="button"
          className="rounded-lg border border-[var(--pgm-border)] px-3 py-2 text-xs font-medium text-[var(--pgm-text)]"
          onClick={onCancel}
          disabled={submitting}
        >
          Cancelar
        </button>
        <button
          type="submit"
          className="rounded-lg bg-[var(--pgm-primary)] px-4 py-2 text-xs font-semibold text-white disabled:opacity-50"
          disabled={submitting}
        >
          {submitting ? 'Salvando…' : 'Salvar'}
        </button>
      </div>
    </form>
  );
}
