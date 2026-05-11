import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { PareceresAPI, ProdutosAPI } from '../api';
import useDebounceSave from '../hooks/useDebounceSave';
import ParecerForm from '../components/ParecerForm';
import ProdutoCard from '../components/ProdutoCard';
import StatusBar from '../components/StatusBar';
import SaveIndicator from '../components/SaveIndicator';
import SignaturePad from '../components/SignaturePad';
import { formatBRL, formatDateTime } from '../utils/masks';

/**
 * Página de edição de um parecer (integração CakePHP: recebe `boot` com parecerId).
 * UI e fluxo alinhados a `laudos-package/frontend/pages/ParecerEditPage.jsx`.
 */
export default function ParecerEditPage({ boot }) {
  const id = boot?.parecerId;
  const onBack = () => {
    window.location.href = '/laudos/pareceres';
  };

  const [parecer, setParecer] = useState(null);
  const [totais, setTotais] = useState({ total_pecas: 0, total_servicos: 0, total_geral: 0 });
  const [loading, setLoading] = useState(true);
  const [showHistory, setShowHistory] = useState(false);
  const [history, setHistory] = useState([]);
  const [showEmailModal, setShowEmailModal] = useState(false);

  const load = useCallback(async () => {
    if (!id) return;
    setLoading(true);
    try {
      const resp = await PareceresAPI.get(id);
      setParecer(resp.data);
      setTotais(resp.totais || {});
    } catch (err) {
      console.error(err);
      alert('Erro ao carregar parecer');
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => { load(); }, [load]);

  const saveData = useMemo(() => {
    if (!parecer) return null;
    const {
      laudos_produtos, laudos_anexos, laudos_historico, laudos_empresa, tecnico, clientes,
      ...rest
    } = parecer;
    return rest;
  }, [parecer]);

  const { saveStatus, savedAt } = useDebounceSave({
    data: saveData,
    onSave: async (data) => {
      const resp = await PareceresAPI.update(id, data);
      if (resp.data) {
        setTotais((prev) => ({ ...prev, total_novo: resp.data.estimated_new_equipment }));
      }
    },
    delay: 600,
    enabled: !loading && parecer?.pode_editar !== false,
  });

  const handleChange = (changes) => {
    setParecer((prev) => ({ ...prev, ...changes }));
  };

  const handleStatusChange = async (novoStatus) => {
    if (!confirm(`Mudar status para "${novoStatus}"?`)) return;
    try {
      await PareceresAPI.changeStatus(id, novoStatus);
      load();
    } catch (err) {
      alert('Erro ao mudar status: ' + (err.friendlyMessage || err.message));
    }
  };

  const handleAddProduto = async () => {
    try {
      await ProdutosAPI.create({
        parecer_id: id,
        nome: 'Novo equipamento',
        recomendacao: 'replace',
      });
      load();
    } catch (err) {
      alert('Erro ao adicionar equipamento: ' + (err.friendlyMessage || err.message));
    }
  };

  const handleProdutoChange = (idx, produtoUpdated) => {
    const novos = [...(parecer.laudos_produtos || [])];
    novos[idx] = produtoUpdated;
    setParecer((prev) => ({ ...prev, laudos_produtos: novos }));
    const totalPecas = novos.reduce((s, p) =>
      s + (p.laudos_produto_pecas || []).reduce((ss, pe) =>
        ss + (parseFloat(pe.quantidade) || 0) * (parseFloat(pe.preco_unitario) || 0), 0), 0);
    const totalServicos = novos.reduce((s, p) =>
      s + (p.laudos_produto_servicos || []).reduce((ss, sv) =>
        ss + (parseFloat(sv.horas) || 0) * (parseFloat(sv.valor_hora) || 0), 0), 0);
    setTotais({ ...totais, total_pecas: totalPecas, total_servicos: totalServicos, total_geral: totalPecas + totalServicos });
  };

  const handleProdutoRemove = async (produtoId) => {
    if (!confirm('Remover este equipamento e todas suas peças/fotos?')) return;
    try {
      await ProdutosAPI.remove(produtoId);
      load();
    } catch (err) {
      alert('Erro ao remover: ' + (err.friendlyMessage || err.message));
    }
  };

  const loadHistory = async () => {
    try {
      const resp = await PareceresAPI.history(id);
      setHistory(resp.data || []);
      setShowHistory(true);
    } catch (err) {
      alert('Erro ao carregar histórico');
    }
  };

  if (!id) {
    return <div style={{ padding: 40, textAlign: 'center' }}>ID do parecer não informado.</div>;
  }
  if (loading) {
    return <div style={{ padding: 40, textAlign: 'center' }}>Carregando...</div>;
  }
  if (!parecer) {
    return <div style={{ padding: 40, textAlign: 'center' }}>Parecer não encontrado.</div>;
  }

  const percentual = totais.total_novo > 0
    ? (totais.total_geral / totais.total_novo) * 100
    : null;

  return (
    <div style={{ background: '#f3f4f6', minHeight: '100vh' }}>
      {/* Toolbar topo */}
      <div style={{
        background: 'white', borderBottom: '1px solid #e5e7eb',
        padding: '12px 24px',
        display: 'flex', justifyContent: 'space-between', alignItems: 'center',
        position: 'sticky', top: 0, zIndex: 10,
      }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 16 }}>
          <button
            type="button"
            onClick={onBack}
            style={{ border: 'none', background: 'transparent', cursor: 'pointer',
                     fontSize: 14, color: '#3b82f6' }}
          >
            ← Voltar
          </button>
          <div>
            <div style={{ fontSize: 18, fontWeight: 600 }}>
              Parecer Técnico {parecer.numero}
            </div>
            <div style={{ fontSize: 12, color: '#6b7280' }}>{parecer.titulo}</div>
          </div>
        </div>
        <div style={{ display: 'flex', gap: 12, alignItems: 'center' }}>
          <SaveIndicator status={saveStatus} savedAt={savedAt} />
          <button type="button" onClick={loadHistory} style={btnSecondaryStyle}>
            🕐 Histórico
          </button>
          <button
            type="button"
            onClick={() => window.open(PareceresAPI.pdfUrl(id), '_blank')}
            style={btnSecondaryStyle}
          >
            📄 PDF
          </button>
          <button
            type="button"
            onClick={() => setShowEmailModal(true)}
            style={btnPrimaryStyle}
          >
            ✉ Enviar
          </button>
        </div>
      </div>

      {/* Status bar */}
      <div style={{ background: 'white', padding: '12px 24px', borderBottom: '1px solid #e5e7eb' }}>
        <StatusBar
          value={parecer.status}
          onChange={handleStatusChange}
        />
      </div>

      {/* Conteúdo */}
      <div style={{ maxWidth: 1100, margin: '0 auto', padding: 24 }}>
        <ParecerForm parecer={parecer} onChange={handleChange} />

        {/* Equipamentos */}
        <div style={{ marginTop: 24 }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 }}>
            <h2 style={{ margin: 0, fontSize: 16, fontWeight: 600 }}>
              Equipamentos Avaliados
              {parecer.laudos_produtos?.length > 0 && (
                <span style={{ fontSize: 13, color: '#6b7280', marginLeft: 8 }}>
                  ({parecer.laudos_produtos.length})
                </span>
              )}
            </h2>
            <button type="button" onClick={handleAddProduto} style={btnPrimaryStyle}>
              + Adicionar Equipamento
            </button>
          </div>

          {(parecer.laudos_produtos || []).map((p, i) => (
            <ProdutoCard
              key={p.id}
              produto={p}
              index={i + 1}
              onChange={(updated) => handleProdutoChange(i, updated)}
              onRemove={() => handleProdutoRemove(p.id)}
            />
          ))}

          {(!parecer.laudos_produtos || parecer.laudos_produtos.length === 0) && (
            <div style={{
              background: 'white', padding: 40, textAlign: 'center',
              borderRadius: 8, border: '2px dashed #d1d5db',
              color: '#6b7280',
            }}>
              {'Nenhum equipamento adicionado ainda. Clique em "Adicionar Equipamento" acima.'}
            </div>
          )}
        </div>

        {/* Totalizadores */}
        <div style={{
          marginTop: 24, padding: 20,
          background: 'white', borderRadius: 8, border: '1px solid #e5e7eb',
        }}>
          <h3 style={{ margin: '0 0 16px', fontSize: 14, fontWeight: 600 }}>Totalizadores</h3>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 16 }}>
            <Total label="Peças" value={totais.total_pecas} />
            <Total label="Serviços" value={totais.total_servicos} />
            <Total label="Total Reparo" value={totais.total_geral} highlight />
            <Total label="Equipamento Novo" value={totais.total_novo || 0} />
          </div>

          {parecer.show_comparison && totais.total_novo > 0 && (
            <div style={{
              marginTop: 16, padding: 14,
              background: percentual > 60 ? '#fef3c7' : '#dcfce7',
              borderRadius: 6, fontSize: 13,
              color: percentual > 60 ? '#a16207' : '#166534',
            }}>
              <strong>Comparativo:</strong> O reparo representa <strong>{percentual.toFixed(1)}%</strong> do valor de equipamento novo.{' '}
              {percentual > 60
                ? '→ Recomenda-se SUBSTITUIÇÃO (limiar de 60%).'
                : '→ Reparo é economicamente viável.'}
            </div>
          )}
        </div>

        {/* Assinatura */}
        <div style={{
          marginTop: 24, padding: 20,
          background: 'white', borderRadius: 8, border: '1px solid #e5e7eb',
        }}>
          <h3 style={{ margin: '0 0 16px', fontSize: 14, fontWeight: 600 }}>
            Assinatura Digital do Técnico
          </h3>
          <SignaturePad
            value={parecer.assinatura_path}
            onChange={(dataURL) => handleChange({ assinatura_path: dataURL })}
          />
        </div>
      </div>

      {/* Modal Histórico */}
      {showHistory && (
        <Modal title="Histórico de Alterações" onClose={() => setShowHistory(false)}>
          {history.length === 0 ? (
            <p style={{ color: '#6b7280' }}>Nenhuma alteração registrada.</p>
          ) : (
            <ul style={{ listStyle: 'none', padding: 0, margin: 0 }}>
              {history.map((h) => (
                <li key={h.id} style={{
                  padding: '10px 0', borderBottom: '1px solid #f3f4f6',
                  fontSize: 13,
                }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                    <strong>{actionLabel(h.action)}</strong>
                    <span style={{ color: '#6b7280', fontSize: 11 }}>
                      {formatDateTime(h.created)}
                    </span>
                  </div>
                  <div style={{ color: '#6b7280', fontSize: 12 }}>
                    por {h.user_name_snapshot || h.user?.name || 'Sistema'}
                  </div>
                </li>
              ))}
            </ul>
          )}
        </Modal>
      )}

      {/* Modal Email */}
      {showEmailModal && (
        <EmailModal
          parecer={parecer}
          onClose={() => setShowEmailModal(false)}
          onSent={() => { setShowEmailModal(false); load(); }}
        />
      )}
    </div>
  );
}

function Total({ label, value, highlight }) {
  return (
    <div style={{
      padding: 12, borderRadius: 6,
      background: highlight ? '#dbeafe' : '#f9fafb',
    }}>
      <div style={{ fontSize: 11, color: '#6b7280', marginBottom: 4 }}>{label}</div>
      <div style={{ fontSize: 18, fontWeight: 700, color: highlight ? '#1e40af' : '#111827' }}>
        {formatBRL(value)}
      </div>
    </div>
  );
}

function Modal({ title, children, onClose }) {
  return (
    <div style={{
      position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)',
      display: 'flex', alignItems: 'center', justifyContent: 'center',
      zIndex: 100,
    }}>
      <div style={{
        background: 'white', borderRadius: 8,
        maxWidth: 600, width: '90%', maxHeight: '80vh',
        overflow: 'hidden', display: 'flex', flexDirection: 'column',
      }}>
        <div style={{
          padding: 16, borderBottom: '1px solid #e5e7eb',
          display: 'flex', justifyContent: 'space-between', alignItems: 'center',
        }}>
          <h3 style={{ margin: 0, fontSize: 16 }}>{title}</h3>
          <button
            type="button"
            onClick={onClose}
            style={{ border: 'none', background: 'transparent', fontSize: 20, cursor: 'pointer' }}
          >×</button>
        </div>
        <div style={{ padding: 16, overflowY: 'auto', flex: 1 }}>
          {children}
        </div>
      </div>
    </div>
  );
}

function EmailModal({ parecer, onClose, onSent }) {
  const [to, setTo] = useState(parecer.requester_email || '');
  const [cc, setCc] = useState('');
  const [subject, setSubject] = useState(`Parecer Técnico nº ${parecer.numero}`);
  const [message, setMessage] = useState(
    `Prezado(a) ${parecer.requester_attention_to || 'cliente'},\n\n` +
    `Segue em anexo o parecer técnico nº ${parecer.numero}.\n\n` +
    `Permanecemos à disposição.\n\nAtenciosamente.`
  );
  const [sending, setSending] = useState(false);

  const handleSend = async () => {
    if (!to) { alert('Informe o destinatário'); return; }
    setSending(true);
    try {
      await PareceresAPI.sendEmail(parecer.id, { to, cc, subject, message });
      alert('E-mail enviado com sucesso!');
      onSent?.();
    } catch (err) {
      alert('Erro ao enviar: ' + (err.friendlyMessage || err.message));
    } finally {
      setSending(false);
    }
  };

  return (
    <Modal title="Enviar Parecer por E-mail" onClose={onClose}>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
        <input
          type="email"
          placeholder="Para"
          value={to}
          onChange={(e) => setTo(e.target.value)}
          style={inputStyle}
        />
        <input
          type="text"
          placeholder="CC (opcional)"
          value={cc}
          onChange={(e) => setCc(e.target.value)}
          style={inputStyle}
        />
        <input
          type="text"
          placeholder="Assunto"
          value={subject}
          onChange={(e) => setSubject(e.target.value)}
          style={inputStyle}
        />
        <textarea
          rows={8}
          value={message}
          onChange={(e) => setMessage(e.target.value)}
          style={{ ...inputStyle, resize: 'vertical' }}
        />
        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8 }}>
          <button type="button" onClick={onClose} style={btnSecondaryStyle} disabled={sending}>
            Cancelar
          </button>
          <button type="button" onClick={handleSend} style={btnPrimaryStyle} disabled={sending}>
            {sending ? 'Enviando...' : 'Enviar'}
          </button>
        </div>
      </div>
    </Modal>
  );
}

function actionLabel(action) {
  const labels = {
    'parecer.created': 'Parecer criado',
    'parecer.duplicated': 'Parecer duplicado',
    'parecer.deleted': 'Parecer excluído',
    'parecer.status_changed': 'Status alterado',
    'produto.added': 'Equipamento adicionado',
    'produto.removed': 'Equipamento removido',
    'imagem.added': 'Foto adicionada',
    'attachment.added': 'Anexo adicionado',
    'pdf.generated': 'PDF gerado',
    'email.sent': 'E-mail enviado',
    'cnpj.consulted': 'CNPJ consultado',
    'cep.consulted': 'CEP consultado',
  };
  return labels[action] || action;
}

const inputStyle = {
  width: '100%', padding: '8px 12px',
  border: '1px solid #d1d5db', borderRadius: 4,
  fontSize: 13, fontFamily: 'inherit',
};
const btnPrimaryStyle = {
  padding: '8px 16px', fontSize: 13, fontWeight: 500,
  background: '#3b82f6', color: 'white', border: 'none',
  borderRadius: 6, cursor: 'pointer',
};
const btnSecondaryStyle = {
  padding: '8px 14px', fontSize: 13, fontWeight: 500,
  background: 'white', color: '#374151', border: '1px solid #d1d5db',
  borderRadius: 6, cursor: 'pointer',
};
