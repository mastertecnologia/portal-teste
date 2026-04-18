import { useCallback, useEffect, useMemo, useState } from "react";
import RemessaGrid from "./RemessaGrid.jsx";
import RetornoUpload from "./RetornoUpload.jsx";
import {
    carregarModuloBancos,
    createBancoPayload,
    extractItems,
    fetchBancos,
    getFinanceiroBoot,
    saveBanco,
} from "../../lib/financeiroApi.js";

const TAB_KEYS = {
    cadastro: "cadastro",
    remessa: "remessa",
    retorno: "retorno",
};

const EMPTY_FORM = {
    id: null,
    codigo_banco: "",
    numero_banco: "",
    cnab: "",
    nome: "",
    numero_agencia: "",
    digito_agencia: "",
    numero_conta: "",
    digito_conta: "",
    convenio: "",
    carteira: "",
    cnab_tipo: "240",
    proxima_remessa: 1,
    codigo_banco_interno: "",
    verifica_receber: "",
    utiliza_endosso: "",
    logotipo: "",
    observacoes: "",
    ativo: true,
};

function formatMoney(value) {
    const amount = Number(value || 0);
    return amount.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL",
    });
}

function cx(...parts) {
    return parts.filter(Boolean).join(" ");
}

function normalizeFields(fields) {
    if (!fields || typeof fields !== "object") return [];
    const out = [];

    Object.entries(fields).forEach(([field, errors]) => {
        if (Array.isArray(errors)) {
            errors.forEach((message) =>
                out.push(`${field}: ${String(message)}`),
            );
            return;
        }

        if (errors && typeof errors === "object") {
            Object.values(errors).forEach((message) => {
                if (Array.isArray(message)) {
                    message.forEach((item) =>
                        out.push(`${field}: ${String(item)}`),
                    );
                } else {
                    out.push(`${field}: ${String(message)}`);
                }
            });
            return;
        }

        if (errors) {
            out.push(`${field}: ${String(errors)}`);
        }
    });

    return out;
}

function inferEmpresaOptions(boot) {
    const empresas = boot?.empresas || boot?.empresaOptions || [];
    if (!Array.isArray(empresas)) return [];
    return empresas
        .map((item) => {
            if (typeof item === "number") {
                return { value: item, label: `Empresa ${item}` };
            }
            return {
                value: Number(item?.id ?? item?.value ?? 0),
                label:
                    item?.label ||
                    item?.nome ||
                    item?.razaosocial ||
                    item?.fantasia ||
                    `Empresa ${item?.id ?? item?.value ?? ""}`,
            };
        })
        .filter((item) => item.value > 0);
}

function initialEmpresaSelection(boot) {
    const fromBoot =
        boot?.empresaAtualId ??
        boot?.empresaId ??
        boot?.empresa?.id ??
        boot?.empresa_atual_id ??
        null;

    if (fromBoot) return [Number(fromBoot)];

    const empresas = inferEmpresaOptions(boot);
    if (empresas.length > 0) {
        return [empresas[0].value];
    }

    return [];
}

function BancoStatusBadge({ ativo, contaIncompleta }) {
    if (!ativo) {
        return (
            <span className="inline-flex rounded-full border border-[var(--pgm-badge-red-ring)] bg-[var(--pgm-badge-red-bg)] px-2 py-1 text-[11px] font-semibold text-[var(--pgm-badge-red-text)]">
                Inativo
            </span>
        );
    }

    if (contaIncompleta) {
        return (
            <span className="inline-flex rounded-full border border-[var(--pgm-badge-amber-ring)] bg-[var(--pgm-badge-amber-bg)] px-2 py-1 text-[11px] font-semibold text-[var(--pgm-badge-amber-text)]">
                Cadastro incompleto
            </span>
        );
    }

    return (
        <span className="inline-flex rounded-full border border-[var(--pgm-badge-green-ring)] bg-[var(--pgm-badge-green-bg)] px-2 py-1 text-[11px] font-semibold text-[var(--pgm-badge-green-text)]">
            Ativo
        </span>
    );
}

function BancoTable({
    bancos,
    selectedId,
    onSelect,
    loading,
    totalTitulosPendentes,
    totalValorPendente,
}) {
    return (
        <div className="overflow-hidden rounded-[var(--pgm-radius-2xl,20px)] border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[var(--pgm-bg-raised,#141820)] shadow-[var(--pgm-shadow-md)]">
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-elevated,#222834)] px-5 py-4">
                <div>
                    <h3 className="text-sm font-semibold text-[var(--pgm-text,#e8eaed)]">
                        Bancos cadastrados
                    </h3>
                    <p className="mt-1 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
                        Use a lista para editar convênio, carteira e sequência
                        de remessa.
                    </p>
                </div>
                <div className="flex flex-wrap items-center gap-2 text-xs text-[var(--pgm-text-secondary,#c4c9d1)]">
                    <span className="inline-flex rounded-full border border-[var(--pgm-badge-blue-ring)] bg-[var(--pgm-badge-blue-bg)] px-2.5 py-1 font-semibold text-[var(--pgm-badge-blue-text)]">
                        Bancos: {bancos.length}
                    </span>
                    <span className="inline-flex rounded-full border border-[var(--pgm-badge-teal-ring)] bg-[var(--pgm-badge-teal-bg)] px-2.5 py-1 font-semibold text-[var(--pgm-badge-teal-text)]">
                        Títulos pendentes: {totalTitulosPendentes}
                    </span>
                    <span className="inline-flex rounded-full border border-[var(--pgm-badge-green-ring)] bg-[var(--pgm-badge-green-bg)] px-2.5 py-1 font-semibold text-[var(--pgm-badge-green-text)]">
                        {formatMoney(totalValorPendente)}
                    </span>
                </div>
            </div>

            <div className="overflow-x-auto">
                <table className="min-w-full text-left text-sm">
                    <thead className="bg-[color-mix(in_srgb,var(--pgm-bg-elevated,#222834)_85%,transparent)]">
                        <tr>
                            <th className="px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                                Banco
                            </th>
                            <th className="px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                                Conta
                            </th>
                            <th className="px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                                Cobrança
                            </th>
                            <th className="px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                                Próx. remessa
                            </th>
                            <th className="px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="px-4 py-8 text-center text-sm text-[var(--pgm-text-muted,#9aa0a8)]"
                                >
                                    Carregando bancos…
                                </td>
                            </tr>
                        ) : bancos.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="px-4 py-8 text-center text-sm text-[var(--pgm-text-muted,#9aa0a8)]"
                                >
                                    Nenhum banco encontrado com os filtros
                                    atuais.
                                </td>
                            </tr>
                        ) : (
                            bancos.map((banco) => {
                                const isSelected =
                                    Number(selectedId) === Number(banco.id);

                                return (
                                    <tr
                                        key={banco.id}
                                        onClick={() => onSelect?.(banco)}
                                        className={cx(
                                            "cursor-pointer border-t border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] transition-colors",
                                            isSelected
                                                ? "bg-[var(--pgm-primary-muted)]"
                                                : "hover:bg-[var(--pgm-bg-overlay,#2a3140)]",
                                        )}
                                    >
                                        <td className="px-4 py-3 align-top">
                                            <div className="font-semibold text-[var(--pgm-text,#e8eaed)]">
                                                {banco.codigo_banco || "—"} —{" "}
                                                {banco.nome || "Banco sem nome"}
                                            </div>
                                            <div className="mt-1 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
                                                CNAB base:{" "}
                                                {banco.cnab ||
                                                    banco.cnab_tipo ||
                                                    "240"}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 align-top text-sm text-[var(--pgm-text-secondary,#c4c9d1)]">
                                            <div>
                                                {banco.conta_formatada ||
                                                    "Conta não informada"}
                                            </div>
                                            <div className="mt-1 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
                                                Interno:{" "}
                                                {banco.codigo_banco_interno ||
                                                    "—"}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 align-top text-sm text-[var(--pgm-text-secondary,#c4c9d1)]">
                                            <div>
                                                Convênio:{" "}
                                                {banco.convenio || "—"}
                                            </div>
                                            <div className="mt-1 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
                                                Carteira:{" "}
                                                {banco.carteira || "—"} ·
                                                Layout:{" "}
                                                {banco.cnab_tipo || "240"}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 align-top font-mono text-sm text-[var(--pgm-text,#e8eaed)]">
                                            {String(
                                                banco.proxima_remessa || 1,
                                            ).padStart(6, "0")}
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            <BancoStatusBadge
                                                ativo={Boolean(banco.ativo)}
                                                contaIncompleta={Boolean(
                                                    banco.conta_incompleta,
                                                )}
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
    );
}

function BancoForm({
    form,
    saving,
    onChange,
    onSubmit,
    onNew,
    feedback,
    fieldErrors,
}) {
    const errors = normalizeFields(fieldErrors);

    return (
        <div className="rounded-[var(--pgm-radius-2xl,20px)] border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[var(--pgm-bg-raised,#141820)] shadow-[var(--pgm-shadow-md)]">
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-elevated,#222834)] px-5 py-4">
                <div>
                    <h3 className="text-sm font-semibold text-[var(--pgm-text,#e8eaed)]">
                        {form.id ? "Editar banco" : "Novo banco"}
                    </h3>
                    <p className="mt-1 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
                        Cadastro operacional para remessa simples, multiempresas
                        e retorno.
                    </p>
                </div>
                <button
                    type="button"
                    onClick={onNew}
                    className="inline-flex items-center gap-2 rounded-lg border border-[var(--pgm-border,#3d4554)] px-3 py-2 text-xs font-semibold text-[var(--pgm-text,#e8eaed)] transition hover:border-[var(--pgm-primary)] hover:bg-[var(--pgm-bg-overlay,#2a3140)]"
                >
                    Novo cadastro
                </button>
            </div>

            <form onSubmit={onSubmit} className="space-y-5 px-5 py-5">
                {feedback ? (
                    <div
                        className={cx(
                            "rounded-xl border px-4 py-3 text-sm",
                            feedback.type === "error"
                                ? "border-[var(--pgm-badge-red-ring)] bg-[var(--pgm-badge-red-bg)] text-[var(--pgm-badge-red-text)]"
                                : "border-[var(--pgm-badge-green-ring)] bg-[var(--pgm-badge-green-bg)] text-[var(--pgm-badge-green-text)]",
                        )}
                    >
                        {feedback.message}
                    </div>
                ) : null}

                {errors.length > 0 ? (
                    <div className="rounded-xl border border-[var(--pgm-badge-amber-ring)] bg-[var(--pgm-badge-amber-bg)] px-4 py-3 text-sm text-[var(--pgm-badge-amber-text)]">
                        <div className="mb-2 font-semibold">
                            Campos com inconsistência:
                        </div>
                        <ul className="list-disc space-y-1 pl-5">
                            {errors.map((item) => (
                                <li key={item}>{item}</li>
                            ))}
                        </ul>
                    </div>
                ) : null}

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Field
                        label="Código banco"
                        value={form.codigo_banco}
                        onChange={(value) => onChange("codigo_banco", value)}
                        placeholder="Ex.: 756"
                    />
                    <Field
                        label="Número banco"
                        value={form.numero_banco}
                        onChange={(value) => onChange("numero_banco", value)}
                        placeholder="Ex.: 756"
                    />
                    <Field
                        label="CNAB"
                        value={form.cnab}
                        onChange={(value) => onChange("cnab", value)}
                        placeholder="Ex.: 756"
                    />
                    <Field
                        label="Nome"
                        value={form.nome}
                        onChange={(value) => onChange("nome", value)}
                        placeholder="Ex.: Sicoob"
                    />
                    <Field
                        label="Agência"
                        value={form.numero_agencia}
                        onChange={(value) => onChange("numero_agencia", value)}
                    />
                    <Field
                        label="Dígito agência"
                        value={form.digito_agencia}
                        onChange={(value) => onChange("digito_agencia", value)}
                    />
                    <Field
                        label="Conta"
                        value={form.numero_conta}
                        onChange={(value) => onChange("numero_conta", value)}
                    />
                    <Field
                        label="Dígito conta"
                        value={form.digito_conta}
                        onChange={(value) => onChange("digito_conta", value)}
                    />
                    <Field
                        label="Convênio"
                        value={form.convenio}
                        onChange={(value) => onChange("convenio", value)}
                        placeholder="Convênio CNAB"
                    />
                    <Field
                        label="Carteira"
                        value={form.carteira}
                        onChange={(value) => onChange("carteira", value)}
                        placeholder="Ex.: 1"
                    />
                    <SelectField
                        label="Layout CNAB"
                        value={form.cnab_tipo}
                        onChange={(value) => onChange("cnab_tipo", value)}
                        options={[
                            { value: "240", label: "240 posições" },
                            { value: "400", label: "400 posições" },
                        ]}
                    />
                    <Field
                        label="Próxima remessa"
                        value={form.proxima_remessa}
                        onChange={(value) => onChange("proxima_remessa", value)}
                        type="number"
                        min="1"
                    />
                    <Field
                        label="Código interno"
                        value={form.codigo_banco_interno}
                        onChange={(value) =>
                            onChange("codigo_banco_interno", value)
                        }
                    />
                    <Field
                        label="Verifica receber"
                        value={form.verifica_receber}
                        onChange={(value) =>
                            onChange("verifica_receber", value)
                        }
                    />
                    <Field
                        label="Utiliza endosso"
                        value={form.utiliza_endosso}
                        onChange={(value) => onChange("utiliza_endosso", value)}
                        placeholder="S / N"
                    />
                    <Field
                        label="Logotipo"
                        value={form.logotipo}
                        onChange={(value) => onChange("logotipo", value)}
                        placeholder="/files/logo-banco.png"
                    />
                </div>

                <div className="grid gap-4 xl:grid-cols-[1fr_auto]">
                    <TextAreaField
                        label="Observações"
                        value={form.observacoes}
                        onChange={(value) => onChange("observacoes", value)}
                        rows={4}
                        placeholder="Configurações específicas de cobrança, convênio ou instruções bancárias."
                    />
                    <label className="flex items-center gap-3 rounded-xl border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-raised,#141820)] px-4 py-3 text-sm text-[var(--pgm-text,#e8eaed)]">
                        <input
                            type="checkbox"
                            checked={Boolean(form.ativo)}
                            onChange={(e) =>
                                onChange("ativo", e.target.checked)
                            }
                            className="h-4 w-4 rounded border-[var(--pgm-border,#3d4554)] bg-transparent text-[var(--pgm-primary)] focus:ring-[var(--pgm-primary)]"
                        />
                        Banco ativo para remessa e retorno
                    </label>
                </div>

                <div className="flex flex-wrap items-center justify-end gap-3">
                    <button
                        type="button"
                        onClick={onNew}
                        className="inline-flex items-center gap-2 rounded-lg border border-[var(--pgm-border,#3d4554)] px-4 py-2 text-sm font-medium text-[var(--pgm-text,#e8eaed)] transition hover:border-[var(--pgm-border-strong,#4f5869)] hover:bg-[var(--pgm-bg-overlay,#2a3140)]"
                    >
                        Limpar
                    </button>
                    <button
                        type="submit"
                        disabled={saving}
                        className="inline-flex items-center gap-2 rounded-lg bg-gradient-to-b from-[var(--pgm-primary,#1d9e75)] to-[#168a64] px-4 py-2 text-sm font-semibold text-white shadow-[var(--pgm-shadow-sm)] transition hover:-translate-y-px hover:shadow-[var(--pgm-shadow-md),0_0_16px_rgba(29,158,117,0.25)] disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {saving
                            ? "Salvando…"
                            : form.id
                              ? "Salvar alterações"
                              : "Cadastrar banco"}
                    </button>
                </div>
            </form>
        </div>
    );
}

function Field({ label, value, onChange, placeholder, type = "text", min }) {
    return (
        <label className="block">
            <span className="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                {label}
            </span>
            <input
                type={type}
                min={min}
                value={value ?? ""}
                onChange={(e) => onChange?.(e.target.value)}
                placeholder={placeholder}
                className="w-full rounded-xl border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-base,#0c0f14)] px-3 py-2.5 text-sm text-[var(--pgm-text,#e8eaed)] outline-none transition focus:border-[var(--pgm-primary)] focus:ring-2 focus:ring-[rgba(29,158,117,0.18)]"
            />
        </label>
    );
}

function TextAreaField({ label, value, onChange, placeholder, rows = 4 }) {
    return (
        <label className="block">
            <span className="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                {label}
            </span>
            <textarea
                value={value ?? ""}
                onChange={(e) => onChange?.(e.target.value)}
                placeholder={placeholder}
                rows={rows}
                className="w-full rounded-xl border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-base,#0c0f14)] px-3 py-2.5 text-sm text-[var(--pgm-text,#e8eaed)] outline-none transition focus:border-[var(--pgm-primary)] focus:ring-2 focus:ring-[rgba(29,158,117,0.18)]"
            />
        </label>
    );
}

function SelectField({ label, value, onChange, options = [] }) {
    return (
        <label className="block">
            <span className="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                {label}
            </span>
            <select
                value={value ?? ""}
                onChange={(e) => onChange?.(e.target.value)}
                className="w-full rounded-xl border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-base,#0c0f14)] px-3 py-2.5 text-sm text-[var(--pgm-text,#e8eaed)] outline-none transition focus:border-[var(--pgm-primary)] focus:ring-2 focus:ring-[rgba(29,158,117,0.18)]"
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </label>
    );
}

function FinanceiroTabs({ activeTab, onChange }) {
    const tabs = [
        { key: TAB_KEYS.cadastro, label: "Cadastro" },
        { key: TAB_KEYS.remessa, label: "Remessa" },
        { key: TAB_KEYS.retorno, label: "Retorno" },
    ];

    return (
        <div className="inline-flex flex-wrap items-center gap-2 rounded-2xl border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-[var(--pgm-bg-surface,#1a1f28)] p-1 shadow-[var(--pgm-shadow-sm)]">
            {tabs.map((tab) => {
                const active = activeTab === tab.key;
                return (
                    <button
                        key={tab.key}
                        type="button"
                        onClick={() => onChange(tab.key)}
                        className={cx(
                            "rounded-xl px-4 py-2 text-sm font-semibold transition",
                            active
                                ? "bg-[var(--pgm-primary-muted)] text-[var(--pgm-primary-hover,#5cdbc0)]"
                                : "text-[var(--pgm-text-secondary,#c4c9d1)] hover:bg-[var(--pgm-bg-overlay,#2a3140)] hover:text-[var(--pgm-text,#e8eaed)]",
                        )}
                    >
                        {tab.label}
                    </button>
                );
            })}
        </div>
    );
}

export default function ModuloBancos() {
    const boot = useMemo(() => getFinanceiroBoot(), []);
    const empresaOptions = useMemo(() => inferEmpresaOptions(boot), [boot]);

    const [activeTab, setActiveTab] = useState(TAB_KEYS.cadastro);
    const [empresasSelecionadas, setEmpresasSelecionadas] = useState(() =>
        initialEmpresaSelection(boot),
    );
    const [busca, setBusca] = useState("");
    const [bancos, setBancos] = useState([]);
    const [loadingBancos, setLoadingBancos] = useState(true);
    const [selectedBanco, setSelectedBanco] = useState(null);
    const [form, setForm] = useState(EMPTY_FORM);
    const [savingBanco, setSavingBanco] = useState(false);
    const [feedback, setFeedback] = useState(null);
    const [fieldErrors, setFieldErrors] = useState(null);
    const [tituloResumo, setTituloResumo] = useState({
        titulos: 0,
        valor_total: 0,
    });

    const activeBancoId = selectedBanco?.id || null;

    const bancosAtivos = useMemo(
        () => bancos.filter((item) => Boolean(item.ativo)),
        [bancos],
    );

    const loadCadastro = useCallback(async () => {
        setLoadingBancos(true);
        setFeedback(null);

        const result = await carregarModuloBancos({
            empresas: empresasSelecionadas,
            busca,
        });

        if (!result.ok) {
            setBancos([]);
            setTituloResumo({
                titulos: 0,
                valor_total: 0,
            });
            setFeedback({
                type: "error",
                message:
                    result.error || "Falha ao carregar o módulo de bancos.",
            });
            setLoadingBancos(false);
            return;
        }

        const bancosItems = extractItems(result.bancos);
        const titulosData = result.titulos?.data || {};
        const titulosTotais = titulosData.totais || {
            titulos: 0,
            valor_total: 0,
        };

        setBancos(bancosItems);
        setTituloResumo(titulosTotais);

        if (bancosItems.length === 0) {
            setSelectedBanco(null);
            setForm(EMPTY_FORM);
        } else {
            const persistedSelected =
                bancosItems.find(
                    (item) => Number(item.id) === Number(selectedBanco?.id),
                ) || bancosItems[0];
            setSelectedBanco(persistedSelected);
            setForm({
                ...EMPTY_FORM,
                ...persistedSelected,
            });
        }

        setLoadingBancos(false);
    }, [busca, empresasSelecionadas, selectedBanco?.id]);

    useEffect(() => {
        loadCadastro();
    }, [loadCadastro]);

    const handleFormChange = useCallback((field, value) => {
        setForm((current) => ({
            ...current,
            [field]:
                field === "proxima_remessa"
                    ? value
                    : field === "ativo"
                      ? Boolean(value)
                      : value,
        }));
    }, []);

    const handleNovoCadastro = useCallback(() => {
        setSelectedBanco(null);
        setForm(EMPTY_FORM);
        setFieldErrors(null);
        setFeedback(null);
    }, []);

    const handleSelecionarBanco = useCallback((banco) => {
        setSelectedBanco(banco);
        setForm({
            ...EMPTY_FORM,
            ...banco,
        });
        setFieldErrors(null);
        setFeedback(null);
    }, []);

    const handleSalvarBanco = useCallback(
        async (event) => {
            event.preventDefault();
            setSavingBanco(true);
            setFeedback(null);
            setFieldErrors(null);

            const payload = createBancoPayload(form);
            const result = await saveBanco(payload);

            if (!result.ok) {
                setSavingBanco(false);
                setFieldErrors(result.fields || null);
                setFeedback({
                    type: "error",
                    message: result.error || "Não foi possível salvar o banco.",
                });
                return;
            }

            const saved = result.data || {};
            setFeedback({
                type: "success",
                message: form.id
                    ? "Banco atualizado com sucesso."
                    : "Banco cadastrado com sucesso.",
            });

            const refreshed = await fetchBancos({
                empresas: empresasSelecionadas,
                q: busca,
            });

            if (refreshed.ok) {
                const items = extractItems(refreshed);
                setBancos(items);
                const selected =
                    items.find(
                        (item) => Number(item.id) === Number(saved.id),
                    ) || null;
                setSelectedBanco(selected);
                setForm({
                    ...EMPTY_FORM,
                    ...(selected || saved),
                });
            } else {
                setForm({
                    ...EMPTY_FORM,
                    ...saved,
                });
            }

            setSavingBanco(false);
        },
        [busca, empresasSelecionadas, form],
    );

    const handleToggleEmpresa = useCallback((empresaId) => {
        const numericId = Number(empresaId);
        setEmpresasSelecionadas((current) => {
            if (current.includes(numericId)) {
                const next = current.filter((item) => item !== numericId);
                return next.length > 0 ? next : [numericId];
            }
            return [...current, numericId];
        });
    }, []);

    return (
        <div className="min-h-screen bg-[var(--pgm-bg-base,#0c0f14)] px-4 py-8 text-[var(--pgm-text,#e8eaed)]">
            <div className="mx-auto max-w-7xl">
                <header className="mb-6 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div className="mb-2 inline-flex rounded-full bg-[var(--pgm-badge-teal-bg)] px-4 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--pgm-badge-teal-text)]">
                            Financeiro · Bancos
                        </div>
                        <h1 className="text-3xl font-bold tracking-tight text-[var(--pgm-text,#e8eaed)]">
                            Módulo de Bancos
                        </h1>
                        <p className="mt-3 max-w-3xl text-sm leading-relaxed text-[var(--pgm-text-secondary,#c4c9d1)]">
                            Gestão unificada de cadastro bancário, geração de
                            remessa simples ou multiempresas, processamento de
                            retorno e visão operacional dos títulos financeiros.
                        </p>
                    </div>

                    <div className="flex flex-col items-start gap-3">
                        <FinanceiroTabs
                            activeTab={activeTab}
                            onChange={setActiveTab}
                        />
                        <div className="flex flex-wrap items-center gap-2 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
                            <span className="rounded-full border border-[var(--pgm-badge-teal-ring)] bg-[var(--pgm-badge-teal-bg)] px-3 py-1 font-semibold text-[var(--pgm-badge-teal-text)]">
                                Bancos ativos: {bancosAtivos.length}
                            </span>
                            <span className="rounded-full border border-[var(--pgm-badge-green-ring)] bg-[var(--pgm-badge-green-bg)] px-3 py-1 font-semibold text-[var(--pgm-badge-green-text)]">
                                Pendentes: {tituloResumo.titulos}
                            </span>
                        </div>
                    </div>
                </header>

                <section className="mb-6 rounded-[var(--pgm-radius-2xl,20px)] border border-[var(--pgm-border-subtle,rgba(255,255,255,0.06))] bg-gradient-to-b from-[var(--pgm-bg-surface,#1a1f28)] to-[var(--pgm-bg-raised,#141820)] px-5 py-5 shadow-[var(--pgm-shadow-md)]">
                    <div className="grid gap-4 lg:grid-cols-[1fr_auto]">
                        <div>
                            <label className="block">
                                <span className="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                                    Busca operacional
                                </span>
                                <input
                                    value={busca}
                                    onChange={(e) => setBusca(e.target.value)}
                                    placeholder="Busque por código do banco, nome, nosso número ou descrição do título"
                                    className="w-full rounded-xl border border-[var(--pgm-border,#3d4554)] bg-[var(--pgm-bg-base,#0c0f14)] px-3 py-2.5 text-sm text-[var(--pgm-text,#e8eaed)] outline-none transition focus:border-[var(--pgm-primary)] focus:ring-2 focus:ring-[rgba(29,158,117,0.18)]"
                                />
                            </label>
                        </div>

                        <div>
                            <div className="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.08em] text-[var(--pgm-text-muted,#9aa0a8)]">
                                Empresas
                            </div>
                            <div className="flex max-w-full flex-wrap items-center gap-2">
                                {empresaOptions.length === 0 ? (
                                    <span className="rounded-full border border-[var(--pgm-border,#3d4554)] px-3 py-2 text-xs text-[var(--pgm-text-muted,#9aa0a8)]">
                                        Empresa atual implícita
                                    </span>
                                ) : (
                                    empresaOptions.map((empresa) => {
                                        const checked =
                                            empresasSelecionadas.includes(
                                                empresa.value,
                                            );
                                        return (
                                            <button
                                                key={empresa.value}
                                                type="button"
                                                onClick={() =>
                                                    handleToggleEmpresa(
                                                        empresa.value,
                                                    )
                                                }
                                                className={cx(
                                                    "rounded-full border px-3 py-2 text-xs font-semibold transition",
                                                    checked
                                                        ? "border-[var(--pgm-badge-teal-ring)] bg-[var(--pgm-badge-teal-bg)] text-[var(--pgm-badge-teal-text)]"
                                                        : "border-[var(--pgm-border,#3d4554)] text-[var(--pgm-text-secondary,#c4c9d1)] hover:bg-[var(--pgm-bg-overlay,#2a3140)] hover:text-[var(--pgm-text,#e8eaed)]",
                                                )}
                                            >
                                                {empresa.label}
                                            </button>
                                        );
                                    })
                                )}
                            </div>
                        </div>
                    </div>
                </section>

                {activeTab === TAB_KEYS.cadastro ? (
                    <div className="grid gap-6 xl:grid-cols-[1.2fr_0.9fr]">
                        <BancoTable
                            bancos={bancos}
                            selectedId={activeBancoId}
                            onSelect={handleSelecionarBanco}
                            loading={loadingBancos}
                            totalTitulosPendentes={tituloResumo.titulos}
                            totalValorPendente={tituloResumo.valor_total}
                        />
                        <BancoForm
                            form={form}
                            saving={savingBanco}
                            onChange={handleFormChange}
                            onSubmit={handleSalvarBanco}
                            onNew={handleNovoCadastro}
                            feedback={feedback}
                            fieldErrors={fieldErrors}
                        />
                    </div>
                ) : null}

                {activeTab === TAB_KEYS.remessa ? (
                    <RemessaGrid
                        empresas={empresaOptions}
                        bancos={bancosAtivos}
                        onRemessaGerada={loadCadastro}
                    />
                ) : null}

                {activeTab === TAB_KEYS.retorno ? (
                    <RetornoUpload
                        bancos={bancosAtivos}
                        onProcessed={loadCadastro}
                    />
                ) : null}
            </div>
        </div>
    );
}
