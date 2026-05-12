import React, { useState, useEffect } from 'react';
import { TemplatesAPI, UtilAPI } from '../api';
import { maskCNPJ, maskCEP, maskPhone } from '../utils/masks';
import { validateCNPJ, validateEmail, validateCEP } from '../utils/validators';
import ClientSearch from './ClientSearch';

/**
 * Formulário com dados do requerente, objetivo, conclusão.
 * Alinhado a `laudos-package/frontend/components/ParecerForm.jsx`.
 * Campos de cliente adaptados ao retorno do Portal (`razaosocial`, etc.).
 */
export default function ParecerForm({ parecer, onChange }) {
  const [objetivoTpls, setObjetivoTpls] = useState([]);
  const [conclusaoTpls, setConclusaoTpls] = useState([]);
  const [loadingCNPJ, setLoadingCNPJ] = useState(false);
  const [loadingCEP, setLoadingCEP] = useState(false);

  useEffect(() => {
    TemplatesAPI.list('objetivo').then((r) => setObjetivoTpls(r.data || []));
    TemplatesAPI.list('conclusao').then((r) => setConclusaoTpls(r.data || []));
  }, []);

  const cnpjValid = !parecer.requester_cnpj || validateCNPJ(parecer.requester_cnpj);
  const emailValid = !parecer.requester_email || validateEmail(parecer.requester_email);
  const cepValid = !parecer.requester_cep || validateCEP(parecer.requester_cep);

  const handleClientSelect = (cliente) => {
    if (!cliente) {
      onChange({
        requester_client_id: null,
        requester_company_name: '',
        requester_cnpj: '',
        requester_phone: '',
        requester_email: '',
        requester_cep: '',
        requester_address: '',
      });
      return;
    }
    onChange({
      requester_client_id: cliente.id,
      requester_company_name: cliente.razao_social || cliente.razaosocial || cliente.nome,
      requester_cnpj: cliente.cnpj || '',
      requester_phone: cliente.telefone || cliente.phone || '',
      requester_email: cliente.email || '',
      requester_cep: cliente.cep || '',
      requester_address: cliente.endereco || cliente.endereco_completo || '',
    });
  };

  const handleConsultarCNPJ = async () => {
    if (!parecer.requester_cnpj || !validateCNPJ(parecer.requester_cnpj)) {
      alert('CNPJ inválido');
      return;
    }
    setLoadingCNPJ(true);
    try {
      const resp = await UtilAPI.consultarCNPJ(parecer.requester_cnpj);
      const d = resp.data;
      const updates = {};
      if (d.razao_social && !parecer.requester_company_name) updates.requester_company_name = d.razao_social;
      if (d.telefone && !parecer.requester_phone) updates.requester_phone = d.telefone;
      if (d.email && !parecer.requester_email) updates.requester_email = d.email;
      if (d.cep && !parecer.requester_cep) updates.requester_cep = d.cep;
      if (d.endereco && !parecer.requester_address) updates.requester_address = d.endereco;
      if (Object.keys(updates).length > 0) onChange(updates);
      else alert('Dados do CNPJ já estão preenchidos.');
    } catch (err) {
      alert('Erro ao consultar CNPJ: ' + (err.friendlyMessage || err.message));
    } finally {
      setLoadingCNPJ(false);
    }
  };

  const handleConsultarCEP = async () => {
    if (!validateCEP(parecer.requester_cep)) {
      alert('CEP inválido');
      return;
    }
    setLoadingCEP(true);
    try {
      const resp = await UtilAPI.consultarCEP(parecer.requester_cep);
      onChange({ requester_address: resp.data.endereco_completo });
    } catch (err) {
      alert('Erro ao consultar CEP: ' + (err.friendlyMessage || err.message));
    } finally {
      setLoadingCEP(false);
    }
  };

  return (
    <div>
      {/* Identificação do parecer */}
      <Card title="Identificação do Parecer">
        <Grid cols={3}>
          <Field label="Número">
            <input
              type="text"
              value={parecer.numero || ''}
              readOnly
              style={{ ...inputStyle, background: '#f9fafb' }}
            />
          </Field>
          <Field label="Data de Emissão">
            <input
              type="date"
              value={parecer.data_emissao || ''}
              onChange={(e) => onChange({ data_emissao: e.target.value })}
              style={inputStyle}
            />
          </Field>
          <Field label="Cidade">
            <input
              type="text"
              value={parecer.cidade || ''}
              onChange={(e) => onChange({ cidade: e.target.value })}
              style={inputStyle}
            />
          </Field>
        </Grid>
        <Field label="Título do Parecer" style={{ marginTop: 12 }}>
          <input
            type="text"
            value={parecer.titulo || ''}
            onChange={(e) => onChange({ titulo: e.target.value })}
            style={inputStyle}
          />
        </Field>
        <Grid cols={2} style={{ marginTop: 12 }}>
          <Field label="Técnico Responsável">
            <input
              type="text"
              value={parecer.tecnico_nome || ''}
              onChange={(e) => onChange({ tecnico_nome: e.target.value })}
              style={inputStyle}
            />
          </Field>
          <Field label="Registro (CRT/CRA)">
            <input
              type="text"
              value={parecer.tecnico_registro || ''}
              onChange={(e) => onChange({ tecnico_registro: e.target.value })}
              style={inputStyle}
            />
          </Field>
        </Grid>
      </Card>

      {/* Requerente */}
      <Card title="Requerente">
        <ClientSearch
          value={parecer.requester_client_id ? {
            id: parecer.requester_client_id,
            razao_social: parecer.requester_company_name,
            cnpj: parecer.requester_cnpj,
          } : null}
          onSelect={handleClientSelect}
        />

        <Grid cols={2} style={{ marginTop: 16 }}>
          <Field label="Razão Social / Nome">
            <input
              type="text"
              value={parecer.requester_company_name || ''}
              onChange={(e) => onChange({ requester_company_name: e.target.value })}
              style={inputStyle}
            />
          </Field>
          <Field label="A/C (Aos cuidados de)">
            <input
              type="text"
              value={parecer.requester_attention_to || ''}
              onChange={(e) => onChange({ requester_attention_to: e.target.value })}
              style={inputStyle}
              placeholder="Nome do contato"
            />
          </Field>
          <Field label="CNPJ" error={!cnpjValid && 'CNPJ inválido'}>
            <div style={{ display: 'flex', gap: 6 }}>
              <input
                type="text"
                value={parecer.requester_cnpj || ''}
                onChange={(e) => onChange({ requester_cnpj: maskCNPJ(e.target.value) })}
                style={{ ...inputStyle, borderColor: !cnpjValid ? '#dc2626' : '#d1d5db' }}
                placeholder="00.000.000/0000-00"
              />
              <button
                type="button"
                onClick={handleConsultarCNPJ}
                disabled={loadingCNPJ || !cnpjValid}
                style={btnSecondaryStyle}
              >
                {loadingCNPJ ? '...' : 'Consultar'}
              </button>
            </div>
          </Field>
          <Field label="Telefone">
            <input
              type="text"
              value={parecer.requester_phone || ''}
              onChange={(e) => onChange({ requester_phone: maskPhone(e.target.value) })}
              style={inputStyle}
              placeholder="(00) 00000-0000"
            />
          </Field>
          <Field label="E-mail" error={!emailValid && 'E-mail inválido'}>
            <input
              type="email"
              value={parecer.requester_email || ''}
              onChange={(e) => onChange({ requester_email: e.target.value })}
              style={{ ...inputStyle, borderColor: !emailValid ? '#dc2626' : '#d1d5db' }}
            />
          </Field>
          <Field label="CEP" error={!cepValid && 'CEP inválido'}>
            <div style={{ display: 'flex', gap: 6 }}>
              <input
                type="text"
                value={parecer.requester_cep || ''}
                onChange={(e) => onChange({ requester_cep: maskCEP(e.target.value) })}
                style={{ ...inputStyle, borderColor: !cepValid ? '#dc2626' : '#d1d5db' }}
                placeholder="00000-000"
              />
              <button
                type="button"
                onClick={handleConsultarCEP}
                disabled={loadingCEP || !cepValid}
                style={btnSecondaryStyle}
              >
                {loadingCEP ? '...' : 'Buscar'}
              </button>
            </div>
          </Field>
        </Grid>
        <Field label="Endereço" style={{ marginTop: 12 }}>
          <input
            type="text"
            value={parecer.requester_address || ''}
            onChange={(e) => onChange({ requester_address: e.target.value })}
            style={inputStyle}
          />
        </Field>
      </Card>

      {/* Objetivo */}
      <Card title="Objetivo do Parecer">
        {objetivoTpls.length > 0 && (
          <TemplatePills
            templates={objetivoTpls}
            onApply={(t) => onChange({ objetivo: t.conteudo })}
          />
        )}
        <textarea
          value={parecer.objetivo || ''}
          onChange={(e) => onChange({ objetivo: e.target.value })}
          rows={4}
          style={textareaStyle}
        />
      </Card>

      {/* Documentação */}
      <Card title="Documentação Considerada">
        <textarea
          value={parecer.documentacao || ''}
          onChange={(e) => onChange({ documentacao: e.target.value })}
          rows={3}
          style={textareaStyle}
          placeholder="Listar documentos, notas fiscais, e-mails, etc. considerados na análise"
        />
      </Card>

      {/* Conclusão */}
      <Card title="Conclusão">
        {conclusaoTpls.length > 0 && (
          <TemplatePills
            templates={conclusaoTpls}
            onApply={(t) => onChange({ conclusao: t.conteudo })}
          />
        )}
        <textarea
          value={parecer.conclusao || ''}
          onChange={(e) => onChange({ conclusao: e.target.value })}
          rows={6}
          style={textareaStyle}
        />
      </Card>

      {/* Comparativo Reparo × Substituição */}
      <Card title="Comparativo Reparo × Substituição">
        <Grid cols={2}>
          <Field label="Valor estimado de equipamento novo">
            <input
              type="number"
              min={0}
              step="0.01"
              value={parecer.estimated_new_equipment || ''}
              onChange={(e) => onChange({ estimated_new_equipment: parseFloat(e.target.value) || 0 })}
              style={inputStyle}
              placeholder="0,00"
            />
          </Field>
          <Field label="Mostrar comparativo no PDF">
            <label style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '8px 0', fontSize: 13 }}>
              <input
                type="checkbox"
                checked={!!parecer.show_comparison}
                onChange={(e) => onChange({ show_comparison: e.target.checked })}
              />
              Sim, exibir comparativo
            </label>
          </Field>
        </Grid>
      </Card>
    </div>
  );
}

function Card({ title, children }) {
  return (
    <div style={{
      background: 'white', border: '1px solid #e5e7eb', borderRadius: 8,
      marginBottom: 16, padding: 16,
    }}>
      <h3 style={{ margin: '0 0 12px', fontSize: 14, fontWeight: 600, color: '#111827',
                   textTransform: 'uppercase', letterSpacing: 0.4 }}>{title}</h3>
      {children}
    </div>
  );
}

function Grid({ cols = 2, children, style }) {
  return (
    <div style={{
      display: 'grid', gridTemplateColumns: `repeat(${cols}, 1fr)`, gap: 12,
      ...style,
    }}>
      {children}
    </div>
  );
}

function Field({ label, children, error, style }) {
  return (
    <div style={style}>
      <label style={{ display: 'block', fontSize: 12, color: '#6b7280', marginBottom: 4 }}>
        {label}
      </label>
      {children}
      {error && <div style={{ fontSize: 11, color: '#dc2626', marginTop: 2 }}>{error}</div>}
    </div>
  );
}

function TemplatePills({ templates, onApply }) {
  return (
    <div style={{ marginBottom: 8, display: 'flex', gap: 6, flexWrap: 'wrap' }}>
      <span style={{ fontSize: 11, color: '#6b7280', alignSelf: 'center' }}>Templates:</span>
      {templates.map((t) => (
        <button
          key={t.id}
          type="button"
          onClick={() => onApply(t)}
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
  );
}

const inputStyle = {
  width: '100%', padding: '7px 10px',
  border: '1px solid #d1d5db', borderRadius: 4,
  fontSize: 13, fontFamily: 'inherit',
};
const textareaStyle = { ...inputStyle, resize: 'vertical' };
const btnSecondaryStyle = {
  padding: '6px 12px', fontSize: 12, fontWeight: 500,
  background: 'white', border: '1px solid #d1d5db', borderRadius: 4,
  cursor: 'pointer', whiteSpace: 'nowrap',
};
