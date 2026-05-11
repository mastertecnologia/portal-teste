import React, { useState, useEffect, useRef } from 'react';
import { ClientesAPI } from '../api';

/**
 * Busca de clientes com autocomplete.
 * Alinhado a `laudos-package/frontend/components/ClientSearch.jsx`;
 * resposta do Portal: `{ success, data }` com `razaosocial` nos itens.
 */
export default function ClientSearch({ value, onSelect, placeholder = 'Buscar cliente por nome ou CNPJ...' }) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState([]);
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const debounceRef = useRef(null);
  const containerRef = useRef(null);

  useEffect(() => {
    const handler = (e) => {
      if (containerRef.current && !containerRef.current.contains(e.target)) {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, []);

  useEffect(() => {
    if (debounceRef.current) clearTimeout(debounceRef.current);

    if (!query || query.length < 2) {
      setResults([]);
      return;
    }

    debounceRef.current = setTimeout(async () => {
      setLoading(true);
      try {
        const resp = await ClientesAPI.search(query);
        const list = resp?.data ?? (Array.isArray(resp) ? resp : []);
        setResults(list);
        setOpen(true);
      } catch (err) {
        console.error('Erro ao buscar clientes:', err);
      } finally {
        setLoading(false);
      }
    }, 300);

    return () => clearTimeout(debounceRef.current);
  }, [query]);

  const handleSelect = (cliente) => {
    onSelect?.(cliente);
    setQuery('');
    setOpen(false);
    setResults([]);
  };

  return (
    <div ref={containerRef} style={{ position: 'relative' }}>
      <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
        <input
          type="text"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder={placeholder}
          style={{
            flex: 1, padding: '8px 12px',
            border: '1px solid #d1d5db', borderRadius: 6,
            fontSize: 14,
          }}
        />
        {loading && <span style={{ fontSize: 12, color: '#6b7280' }}>buscando...</span>}
      </div>

      {value && (
        <div style={{
          marginTop: 8, padding: '8px 12px',
          background: '#f0f9ff', border: '1px solid #bae6fd',
          borderRadius: 6, fontSize: 13,
          display: 'flex', justifyContent: 'space-between', alignItems: 'center',
        }}>
          <div>
            <strong>{value.razao_social || value.requester_company_name || value.razaosocial}</strong>
            <div style={{ fontSize: 11, color: '#6b7280' }}>
              CNPJ: {value.cnpj || value.requester_cnpj}
            </div>
          </div>
          <button
            type="button"
            onClick={() => onSelect?.(null)}
            style={{
              border: 'none', background: 'transparent',
              color: '#dc2626', cursor: 'pointer', fontSize: 12,
            }}
          >
            Desvincular
          </button>
        </div>
      )}

      {open && results.length > 0 && (
        <div style={{
          position: 'absolute', top: '100%', left: 0, right: 0,
          background: 'white', border: '1px solid #d1d5db',
          borderRadius: 6, marginTop: 4,
          maxHeight: 280, overflowY: 'auto', zIndex: 100,
          boxShadow: '0 4px 12px rgba(0,0,0,0.1)',
        }}>
          {results.map((c) => (
            <button
              key={c.id}
              type="button"
              onClick={() => handleSelect(c)}
              style={{
                display: 'block', width: '100%',
                padding: '10px 14px', textAlign: 'left',
                border: 'none', borderBottom: '1px solid #f3f4f6',
                background: 'white', cursor: 'pointer',
                fontSize: 13,
              }}
              onMouseEnter={(e) => { e.currentTarget.style.background = '#f9fafb'; }}
              onMouseLeave={(e) => { e.currentTarget.style.background = 'white'; }}
            >
              <div style={{ fontWeight: 500 }}>{c.razao_social || c.razaosocial || c.nome}</div>
              <div style={{ fontSize: 11, color: '#6b7280' }}>
                CNPJ: {c.cnpj} {c.cidade && `• ${c.cidade}`}
              </div>
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
