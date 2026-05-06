import React, { useState, useEffect, useCallback } from 'react';
import { PareceresAPI } from '../services/api';
import { StatusBadge } from '../components/StatusBar';
import { formatBRL, formatDate } from '../utils/masks';

/**
 * Página de listagem de Pareceres Técnicos.
 *
 * Rota sugerida: /laudos/pareceres
 * Adapte os imports/exports ao seu sistema de rotas (React Router, etc.).
 */
export default function ParecerListPage({ onOpen, onNew }) {
  const [pareceres, setPareceres] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [page, setPage] = useState(1);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const resp = await PareceresAPI.list({
        q: search,
        status: statusFilter,
        page,
        limit: 20,
      });
      setPareceres(resp.data || []);
    } catch (err) {
      console.error('Erro ao carregar pareceres:', err);
      alert('Erro ao carregar lista');
    } finally {
      setLoading(false);
    }
  }, [search, statusFilter, page]);

  useEffect(() => {
    const timer = setTimeout(load, 300);
    return () => clearTimeout(timer);
  }, [load]);

  const handleNew = async () => {
    try {
      const resp = await PareceresAPI.create({});
      onNew?.(resp.data);
    } catch (err) {
      alert('Erro ao criar parecer: ' + (err.friendlyMessage || err.message));
    }
  };

  const handleDuplicate = async (id) => {
    if (!confirm('Duplicar este parecer?')) return;
    try {
      const resp = await PareceresAPI.duplicate(id);
      load();
      onOpen?.(resp.data.id);
    } catch (err) {
      alert('Erro ao duplicar: ' + (err.friendlyMessage || err.message));
    }
  };

  const handleDelete = async (id) => {
    if (!confirm('Excluir este parecer? Esta ação pode ser revertida no banco.')) return;
    try {
      await PareceresAPI.remove(id);
      load();
    } catch (err) {
      alert('Erro ao excluir: ' + (err.friendlyMessage || err.message));
    }
  };

  return (
    <div style={{ padding: 24, maxWidth: 1200, margin: '0 auto' }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 }}>
        <h1 style={{ margin: 0, fontSize: 24 }}>Pareceres Técnicos</h1>
        <button
          type="button"
          onClick={handleNew}
          style={{
            padding: '10px 20px',
            background: '#3b82f6', color: 'white',
            border: 'none', borderRadius: 6, cursor: 'pointer',
            fontSize: 14, fontWeight: 500,
          }}
        >
          + Novo Parecer
        </button>
      </div>

      {/* Filtros */}
      <div style={{
        display: 'flex', gap: 12, marginBottom: 16,
        background: 'white', padding: 12, borderRadius: 8,
        border: '1px solid #e5e7eb',
      }}>
        <input
          type="text"
          placeholder="Buscar por título, número, cliente ou CNPJ..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          style={{
            flex: 1, padding: '8px 12px',
            border: '1px solid #d1d5db', borderRadius: 6,
            fontSize: 14,
          }}
        />
        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value)}
          style={{
            padding: '8px 12px',
            border: '1px solid #d1d5db', borderRadius: 6,
            fontSize: 14,
          }}
        >
          <option value="all">Todos os status</option>
          <option value="rascunho">Rascunho</option>
          <option value="em_analise">Em análise</option>
          <option value="aprovado">Aprovado</option>
          <option value="concluido">Concluído</option>
          <option value="enviado">Enviado</option>
        </select>
      </div>

      {/* Tabela */}
      <div style={{ background: 'white', borderRadius: 8, border: '1px solid #e5e7eb', overflow: 'hidden' }}>
        {loading ? (
          <div style={{ padding: 40, textAlign: 'center', color: '#6b7280' }}>Carregando...</div>
        ) : pareceres.length === 0 ? (
          <div style={{ padding: 40, textAlign: 'center', color: '#6b7280' }}>
            Nenhum parecer encontrado.
          </div>
        ) : (
          <table style={{ width: '100%', borderCollapse: 'collapse' }}>
            <thead style={{ background: '#f9fafb' }}>
              <tr>
                <th style={thStyle}>Número</th>
                <th style={thStyle}>Título</th>
                <th style={thStyle}>Cliente</th>
                <th style={thStyle}>Técnico</th>
                <th style={thStyle}>Data</th>
                <th style={thStyle}>Status</th>
                <th style={{ ...thStyle, textAlign: 'right' }}>Total</th>
                <th style={thStyle}></th>
              </tr>
            </thead>
            <tbody>
              {pareceres.map((p) => (
                <tr
                  key={p.id}
                  onClick={() => onOpen?.(p.id)}
                  style={{ cursor: 'pointer' }}
                  onMouseEnter={(e) => e.currentTarget.style.background = '#f9fafb'}
                  onMouseLeave={(e) => e.currentTarget.style.background = 'white'}
                >
                  <td style={tdStyle}><strong>{p.numero}</strong></td>
                  <td style={tdStyle}>{p.titulo}</td>
                  <td style={tdStyle}>
                    <div>{p.requester_company_name || '—'}</div>
                    <div style={{ fontSize: 11, color: '#6b7280' }}>{p.requester_cnpj}</div>
                  </td>
                  <td style={tdStyle}>{p.tecnico_nome || '—'}</td>
                  <td style={tdStyle}>{formatDate(p.data_emissao || p.created)}</td>
                  <td style={tdStyle}><StatusBadge status={p.status} /></td>
                  <td style={{ ...tdStyle, textAlign: 'right' }}>{formatBRL(p.total_geral)}</td>
                  <td style={tdStyle} onClick={(e) => e.stopPropagation()}>
                    <div style={{ display: 'flex', gap: 4 }}>
                      <IconBtn title="Duplicar" onClick={() => handleDuplicate(p.id)}>📋</IconBtn>
                      <IconBtn
                        title="PDF"
                        onClick={() => window.open(PareceresAPI.pdfUrl(p.id), '_blank')}
                      >📄</IconBtn>
                      <IconBtn
                        title="Excluir"
                        onClick={() => handleDelete(p.id)}
                        color="#dc2626"
                      >🗑</IconBtn>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
}

function IconBtn({ title, onClick, color = '#6b7280', children }) {
  return (
    <button
      type="button"
      title={title}
      onClick={onClick}
      style={{
        border: 'none', background: 'transparent',
        cursor: 'pointer', padding: '4px 8px',
        color, fontSize: 14,
      }}
    >
      {children}
    </button>
  );
}

const thStyle = {
  textAlign: 'left', padding: '10px 12px',
  fontSize: 12, fontWeight: 600, color: '#374151',
  borderBottom: '1px solid #e5e7eb',
};
const tdStyle = {
  padding: '12px', fontSize: 13,
  borderBottom: '1px solid #f3f4f6',
};
