import React, { useState, useEffect } from 'react';
import { ProdutosAPI, CatalogoAPI, TemplatesAPI } from '../services/api';
import { formatBRL } from '../utils/masks';
import ImageUpload from './ImageUpload';

/**
 * Card de gestão de um equipamento dentro do parecer.
 *
 * Props:
 *   produto: { id, nome, tipo, serial_number, especificacoes, diagnostico, ...,
 *              laudos_produto_imagens, laudos_produto_pecas, laudos_produto_servicos }
 *   onChange: (produtoAtualizado) => void
 *   onRemove: () => void
 *   index: número de ordem
 */
export default function ProdutoCard({ produto, onChange, onRemove, index = 1 }) {
  const [collapsed, setCollapsed] = useState(false);
  const [diagnTemplates, setDiagnTemplates] = useState([]);
  const [pecasCatalog, setPecasCatalog] = useState([]);
  const [servicosCatalog, setServicosCatalog] = useState([]);

  // Carrega catálogos e templates uma vez
  useEffect(() => {
    TemplatesAPI.list('diagnostico').then((r) => setDiagnTemplates(r.data || []));
    CatalogoAPI.pecas().then((r) => setPecasCatalog(r.data || []));
    CatalogoAPI.servicos().then((r) => setServicosCatalog(r.data || []));
  }, []);

  const update = (changes) => {
    const updated = { ...produto, ...changes };
    onChange?.(updated);
    // Persiste no backend (auto-save é gerenciado pelo parent)
    if (produto.id) {
      ProdutosAPI.update(produto.id, changes).catch(console.error);
    }
  };

  const handleApplyDiagnTemplate = (tpl) => {
    update({ diagnostico: tpl.conteudo });
  };

  const addPeca = (catalogItem = null) => {
    const novaPeca = catalogItem ? {
      catalogo_id: catalogItem.id,
      nome: catalogItem.nome,
      codigo: catalogItem.codigo,
      quantidade: 1,
      unidade: catalogItem.unidade || 'un',
      preco_unitario: parseFloat(catalogItem.preco_default) || 0,
    } : {
      nome: '',
      quantidade: 1,
      unidade: 'un',
      preco_unitario: 0,
    };
    const pecas = [...(produto.laudos_produto_pecas || []), { ...novaPeca, _local_id: Date.now() }];
    update({ laudos_produto_pecas: pecas });
  };

  const updatePeca = (idx, changes) => {
    const pecas = [...(produto.laudos_produto_pecas || [])];
    pecas[idx] = { ...pecas[idx], ...changes };
    update({ laudos_produto_pecas: pecas });
  };

  const removePeca = (idx) => {
    const pecas = (produto.laudos_produto_pecas || []).filter((_, i) => i !== idx);
    update({ laudos_produto_pecas: pecas });
  };

  const addServico = (catalogItem = null) => {
    const novo = catalogItem ? {
      catalogo_id: catalogItem.id,
      descricao: catalogItem.descricao,
      horas: parseFloat(catalogItem.horas_default) || 1,
      valor_hora: parseFloat(catalogItem.valor_hora_default) || 0,
    } : {
      descricao: '',
      horas: 1,
      valor_hora: 0,
    };
    const servicos = [...(produto.laudos_produto_servicos || []), { ...novo, _local_id: Date.now() }];
    update({ laudos_produto_servicos: servicos });
  };

  const updateServico = (idx, changes) => {
    const servicos = [...(produto.laudos_produto_servicos || [])];
    servicos[idx] = { ...servicos[idx], ...changes };
    update({ laudos_produto_servicos: servicos });
  };

  const removeServico = (idx) => {
    const servicos = (produto.laudos_produto_servicos || []).filter((_, i) => i !== idx);
    update({ laudos_produto_servicos: servicos });
  };

  // Totais
  const totalPecas = (produto.laudos_produto_pecas || [])
    .reduce((s, p) => s + (parseFloat(p.quantidade) * parseFloat(p.preco_unitario || 0) || 0), 0);
  const totalServicos = (produto.laudos_produto_servicos || [])
    .reduce((s, p) => s + (parseFloat(p.horas) * parseFloat(p.valor_hora || 0) || 0), 0);

  return (
    <div style={{
      background: 'white', border: '1px solid #e5e7eb', borderRadius: 8,
      marginBottom: 16, overflow: 'hidden',
    }}>
      <div style={{
        padding: '12px 16px', background: '#f9fafb',
        borderBottom: '1px solid #e5e7eb',
        display: 'flex', justifyContent: 'space-between', alignItems: 'center',
      }}>
        <h3 style={{ margin: 0, fontSize: 15, fontWeight: 600 }}>
          Equipamento {index} {produto.nome ? `— ${produto.nome}` : ''}
        </h3>
        <div style={{ display: 'flex', gap: 8 }}>
          <button
            type="button"
            onClick={() => setCollapsed(!collapsed)}
            style={{ border: 'none', background: 'transparent', cursor: 'pointer', fontSize: 12, color: '#6b7280' }}
          >
            {collapsed ? '▼ Expandir' : '▲ Colapsar'}
          </button>
          <button
            type="button"
            onClick={onRemove}
            style={{ border: 'none', background: 'transparent', cursor: 'pointer', fontSize: 12, color: '#dc2626' }}
          >
            Remover
          </button>
        </div>
      </div>

      {!collapsed && (
        <div style={{ padding: 16 }}>
          {/* Identificação */}
          <Section title="Identificação">
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
              <Field label="Nome / Modelo">
                <input
                  type="text"
                  value={produto.nome || ''}
                  onChange={(e) => update({ nome: e.target.value })}
                  style={inputStyle}
                />
              </Field>
              <Field label="Tipo">
                <select
                  value={produto.tipo || ''}
                  onChange={(e) => update({ tipo: e.target.value })}
                  style={inputStyle}
                >
                  <option value="">Selecione...</option>
                  <option value="Servidor">Servidor</option>
                  <option value="Desktop">Desktop</option>
                  <option value="Notebook">Notebook</option>
                  <option value="Storage">Storage</option>
                  <option value="Nobreak">Nobreak</option>
                  <option value="Switch/Roteador">Switch/Roteador</option>
                  <option value="Outro">Outro</option>
                </select>
              </Field>
              <Field label="Número de Série">
                <input
                  type="text"
                  value={produto.serial_number || ''}
                  onChange={(e) => update({ serial_number: e.target.value })}
                  style={inputStyle}
                />
              </Field>
              <Field label="Recomendação">
                <select
                  value={produto.recomendacao || 'replace'}
                  onChange={(e) => update({ recomendacao: e.target.value })}
                  style={inputStyle}
                >
                  <option value="replace">Substituir</option>
                  <option value="repair">Reparar</option>
                  <option value="partial">Parcial</option>
                </select>
              </Field>
            </div>
            <Field label="Especificações" style={{ marginTop: 12 }}>
              <textarea
                value={produto.especificacoes || ''}
                onChange={(e) => update({ especificacoes: e.target.value })}
                rows={3}
                style={textareaStyle}
                placeholder="Configuração técnica detalhada do equipamento..."
              />
            </Field>
          </Section>

          {/* Diagnóstico */}
          <Section title="Diagnóstico Técnico">
            {diagnTemplates.length > 0 && (
              <div style={{ marginBottom: 8, display: 'flex', gap: 6, flexWrap: 'wrap' }}>
                <span style={{ fontSize: 11, color: '#6b7280', alignSelf: 'center' }}>Templates:</span>
                {diagnTemplates.map((t) => (
                  <button
                    key={t.id}
                    type="button"
                    onClick={() => handleApplyDiagnTemplate(t)}
                    style={{
                      fontSize: 11, padding: '3px 10px',
                      border: '1px solid #d1d5db', borderRadius: 999,
                      background: '#f9fafb', cursor: 'pointer',
                    }}
                  >
                    {t.nome}
                  </button>
                ))}
              </div>
            )}
            <textarea
              value={produto.diagnostico || ''}
              onChange={(e) => update({ diagnostico: e.target.value })}
              rows={6}
              style={textareaStyle}
              placeholder="Descreva o diagnóstico técnico do equipamento..."
            />
          </Section>

          {/* Fotos */}
          {produto.id && (
            <Section title="Fotos do Equipamento">
              <ImageUpload
                produtoId={produto.id}
                imagens={produto.laudos_produto_imagens || []}
                onChange={(imgs) => update({ laudos_produto_imagens: imgs })}
              />
            </Section>
          )}

          {/* Peças */}
          <Section title={`Peças (Total: ${formatBRL(totalPecas)})`}>
            <PecaPicker catalogo={pecasCatalog} onAdd={addPeca} />
            {(produto.laudos_produto_pecas || []).length > 0 && (
              <table style={tableStyle}>
                <thead>
                  <tr>
                    <th style={thStyle}>Item</th>
                    <th style={{ ...thStyle, width: 80 }}>Qtde</th>
                    <th style={{ ...thStyle, width: 60 }}>Un.</th>
                    <th style={{ ...thStyle, width: 110 }}>Preço un.</th>
                    <th style={{ ...thStyle, width: 110 }}>Subtotal</th>
                    <th style={{ ...thStyle, width: 40 }}></th>
                  </tr>
                </thead>
                <tbody>
                  {produto.laudos_produto_pecas.map((p, i) => (
                    <tr key={p.id || p._local_id || i}>
                      <td style={tdStyle}>
                        <input
                          type="text"
                          value={p.nome || ''}
                          onChange={(e) => updatePeca(i, { nome: e.target.value })}
                          style={inputStyle}
                        />
                      </td>
                      <td style={tdStyle}>
                        <input
                          type="number" min={0} step="0.01"
                          value={p.quantidade || ''}
                          onChange={(e) => updatePeca(i, { quantidade: parseFloat(e.target.value) || 0 })}
                          style={{ ...inputStyle, textAlign: 'right' }}
                        />
                      </td>
                      <td style={tdStyle}>
                        <input
                          type="text"
                          value={p.unidade || 'un'}
                          onChange={(e) => updatePeca(i, { unidade: e.target.value })}
                          style={inputStyle}
                        />
                      </td>
                      <td style={tdStyle}>
                        <input
                          type="number" min={0} step="0.01"
                          value={p.preco_unitario || ''}
                          onChange={(e) => updatePeca(i, { preco_unitario: parseFloat(e.target.value) || 0 })}
                          style={{ ...inputStyle, textAlign: 'right' }}
                        />
                      </td>
                      <td style={{ ...tdStyle, textAlign: 'right' }}>
                        {formatBRL((parseFloat(p.quantidade) || 0) * (parseFloat(p.preco_unitario) || 0))}
                      </td>
                      <td style={tdStyle}>
                        <button
                          type="button"
                          onClick={() => removePeca(i)}
                          style={{ border: 'none', background: 'transparent', color: '#dc2626', cursor: 'pointer' }}
                        >×</button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </Section>

          {/* Serviços */}
          <Section title={`Serviços (Total: ${formatBRL(totalServicos)})`}>
            <ServicoPicker catalogo={servicosCatalog} onAdd={addServico} />
            {(produto.laudos_produto_servicos || []).length > 0 && (
              <table style={tableStyle}>
                <thead>
                  <tr>
                    <th style={thStyle}>Descrição</th>
                    <th style={{ ...thStyle, width: 80 }}>Horas</th>
                    <th style={{ ...thStyle, width: 110 }}>Valor/h</th>
                    <th style={{ ...thStyle, width: 110 }}>Subtotal</th>
                    <th style={{ ...thStyle, width: 40 }}></th>
                  </tr>
                </thead>
                <tbody>
                  {produto.laudos_produto_servicos.map((s, i) => (
                    <tr key={s.id || s._local_id || i}>
                      <td style={tdStyle}>
                        <input
                          type="text"
                          value={s.descricao || ''}
                          onChange={(e) => updateServico(i, { descricao: e.target.value })}
                          style={inputStyle}
                        />
                      </td>
                      <td style={tdStyle}>
                        <input
                          type="number" min={0} step="0.5"
                          value={s.horas || ''}
                          onChange={(e) => updateServico(i, { horas: parseFloat(e.target.value) || 0 })}
                          style={{ ...inputStyle, textAlign: 'right' }}
                        />
                      </td>
                      <td style={tdStyle}>
                        <input
                          type="number" min={0} step="0.01"
                          value={s.valor_hora || ''}
                          onChange={(e) => updateServico(i, { valor_hora: parseFloat(e.target.value) || 0 })}
                          style={{ ...inputStyle, textAlign: 'right' }}
                        />
                      </td>
                      <td style={{ ...tdStyle, textAlign: 'right' }}>
                        {formatBRL((parseFloat(s.horas) || 0) * (parseFloat(s.valor_hora) || 0))}
                      </td>
                      <td style={tdStyle}>
                        <button
                          type="button"
                          onClick={() => removeServico(i)}
                          style={{ border: 'none', background: 'transparent', color: '#dc2626', cursor: 'pointer' }}
                        >×</button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </Section>
        </div>
      )}
    </div>
  );
}

// ============ subcomponentes auxiliares ============

function Section({ title, children }) {
  return (
    <div style={{ marginBottom: 20 }}>
      <h4 style={{ margin: '0 0 10px', fontSize: 13, fontWeight: 600, color: '#374151', textTransform: 'uppercase', letterSpacing: 0.4 }}>
        {title}
      </h4>
      {children}
    </div>
  );
}

function Field({ label, children, style }) {
  return (
    <div style={style}>
      <label style={{ display: 'block', fontSize: 12, color: '#6b7280', marginBottom: 4 }}>{label}</label>
      {children}
    </div>
  );
}

function PecaPicker({ catalogo, onAdd }) {
  const [search, setSearch] = useState('');
  const [open, setOpen] = useState(false);
  const filtered = catalogo
    .filter((p) => !search || (p.nome + ' ' + (p.codigo || '')).toLowerCase().includes(search.toLowerCase()))
    .slice(0, 20);

  return (
    <div style={{ position: 'relative', marginBottom: 8 }}>
      <div style={{ display: 'flex', gap: 8 }}>
        <input
          type="text"
          placeholder="Buscar peça no catálogo..."
          value={search}
          onChange={(e) => { setSearch(e.target.value); setOpen(true); }}
          onFocus={() => setOpen(true)}
          style={{ ...inputStyle, flex: 1 }}
        />
        <button
          type="button"
          onClick={() => { onAdd(null); setOpen(false); setSearch(''); }}
          style={btnSecondaryStyle}
        >+ Personalizada</button>
      </div>
      {open && filtered.length > 0 && (
        <div style={dropdownStyle}>
          {filtered.map((p) => (
            <button
              key={p.id}
              type="button"
              onClick={() => { onAdd(p); setOpen(false); setSearch(''); }}
              style={dropdownItemStyle}
            >
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span>{p.nome}</span>
                <span style={{ color: '#6b7280', fontSize: 11 }}>{formatBRL(p.preco_default)}</span>
              </div>
              {p.codigo && <div style={{ fontSize: 10, color: '#9ca3af' }}>{p.codigo}</div>}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}

function ServicoPicker({ catalogo, onAdd }) {
  const [search, setSearch] = useState('');
  const [open, setOpen] = useState(false);
  const filtered = catalogo
    .filter((s) => !search || s.descricao.toLowerCase().includes(search.toLowerCase()))
    .slice(0, 20);

  return (
    <div style={{ position: 'relative', marginBottom: 8 }}>
      <div style={{ display: 'flex', gap: 8 }}>
        <input
          type="text"
          placeholder="Buscar serviço no catálogo..."
          value={search}
          onChange={(e) => { setSearch(e.target.value); setOpen(true); }}
          onFocus={() => setOpen(true)}
          style={{ ...inputStyle, flex: 1 }}
        />
        <button
          type="button"
          onClick={() => { onAdd(null); setOpen(false); setSearch(''); }}
          style={btnSecondaryStyle}
        >+ Personalizado</button>
      </div>
      {open && filtered.length > 0 && (
        <div style={dropdownStyle}>
          {filtered.map((s) => (
            <button
              key={s.id}
              type="button"
              onClick={() => { onAdd(s); setOpen(false); setSearch(''); }}
              style={dropdownItemStyle}
            >
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span>{s.descricao}</span>
                <span style={{ color: '#6b7280', fontSize: 11 }}>{formatBRL(s.valor_hora_default)}/h</span>
              </div>
            </button>
          ))}
        </div>
      )}
    </div>
  );
}

// ============ estilos ============
const inputStyle = {
  width: '100%', padding: '6px 10px',
  border: '1px solid #d1d5db', borderRadius: 4,
  fontSize: 13, fontFamily: 'inherit',
};
const textareaStyle = { ...inputStyle, resize: 'vertical' };
const tableStyle = { width: '100%', borderCollapse: 'collapse', marginTop: 8 };
const thStyle = { textAlign: 'left', fontSize: 11, fontWeight: 600, color: '#6b7280', padding: '6px 4px', borderBottom: '1px solid #e5e7eb' };
const tdStyle = { padding: '4px', borderBottom: '1px solid #f3f4f6' };
const btnSecondaryStyle = {
  padding: '6px 12px', fontSize: 12, fontWeight: 500,
  background: 'white', border: '1px solid #d1d5db', borderRadius: 4,
  cursor: 'pointer', whiteSpace: 'nowrap',
};
const dropdownStyle = {
  position: 'absolute', top: '100%', left: 0, right: 90,
  marginTop: 4, background: 'white',
  border: '1px solid #d1d5db', borderRadius: 6,
  maxHeight: 240, overflowY: 'auto', zIndex: 50,
  boxShadow: '0 4px 12px rgba(0,0,0,0.1)',
};
const dropdownItemStyle = {
  display: 'block', width: '100%',
  padding: '8px 12px', textAlign: 'left',
  border: 'none', borderBottom: '1px solid #f3f4f6',
  background: 'white', cursor: 'pointer', fontSize: 12,
};
