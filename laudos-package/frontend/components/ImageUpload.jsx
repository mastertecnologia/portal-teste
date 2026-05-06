import React, { useRef, useState } from 'react';
import { ImagensAPI } from '../services/api';
import { compressImage, humanFileSize } from '../utils/imageCompression';

/**
 * Upload de imagens para um produto/equipamento.
 *
 * Props:
 *   produtoId: id do produto
 *   imagens: array atual de imagens
 *   onChange: (novasImagens) => void
 */
export default function ImageUpload({ produtoId, imagens = [], onChange }) {
  const inputRef = useRef(null);
  const [uploading, setUploading] = useState(false);
  const [progress, setProgress] = useState('');

  const handleFiles = async (files) => {
    if (!files?.length) return;
    setUploading(true);

    const novas = [...imagens];
    for (const file of files) {
      try {
        setProgress(`Comprimindo ${file.name}...`);
        const { file: compressed, originalSize, newSize, reduction } = await compressImage(file, {
          maxWidth: 1200,
          maxHeight: 1200,
          quality: 0.75,
        });

        setProgress(`Enviando ${file.name} (${humanFileSize(newSize)})...`);
        const resp = await ImagensAPI.upload(produtoId, compressed);

        novas.push({
          ...resp.data,
          _stats: { originalSize, newSize, reduction },
        });
      } catch (err) {
        console.error('Erro ao subir imagem:', err);
        alert(`Erro ao subir ${file.name}: ${err.friendlyMessage || err.message}`);
      }
    }

    onChange?.(novas);
    setUploading(false);
    setProgress('');
    if (inputRef.current) inputRef.current.value = '';
  };

  const handleRemove = async (img) => {
    if (!confirm('Remover esta foto?')) return;
    try {
      await ImagensAPI.remove(img.id);
      onChange?.(imagens.filter((i) => i.id !== img.id));
    } catch (err) {
      alert('Erro ao remover: ' + (err.friendlyMessage || err.message));
    }
  };

  return (
    <div>
      <div style={{
        border: '2px dashed #d1d5db', borderRadius: 8,
        padding: 16, textAlign: 'center',
        background: '#f9fafb',
      }}>
        <input
          ref={inputRef}
          type="file"
          accept="image/*"
          multiple
          onChange={(e) => handleFiles(Array.from(e.target.files))}
          style={{ display: 'none' }}
        />
        <button
          type="button"
          onClick={() => inputRef.current?.click()}
          disabled={uploading}
          style={{
            padding: '8px 16px',
            background: '#3b82f6', color: 'white',
            border: 'none', borderRadius: 6, cursor: 'pointer',
            fontSize: 13, fontWeight: 500,
          }}
        >
          {uploading ? 'Enviando...' : '+ Adicionar Fotos'}
        </button>
        {progress && (
          <div style={{ marginTop: 8, fontSize: 12, color: '#6b7280' }}>{progress}</div>
        )}
      </div>

      {imagens.length > 0 && (
        <div style={{
          marginTop: 12,
          display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(140px, 1fr))',
          gap: 10,
        }}>
          {imagens.map((img) => (
            <div key={img.id} style={{
              position: 'relative', borderRadius: 6, overflow: 'hidden',
              border: '1px solid #e5e7eb', background: '#fff',
            }}>
              <img
                src={img.url || `/${img.file_path}`}
                alt={img.descricao || 'foto'}
                style={{ width: '100%', height: 110, objectFit: 'cover', display: 'block' }}
              />
              <button
                type="button"
                onClick={() => handleRemove(img)}
                style={{
                  position: 'absolute', top: 4, right: 4,
                  width: 22, height: 22, borderRadius: '50%',
                  border: 'none', background: 'rgba(220,38,38,0.9)', color: 'white',
                  cursor: 'pointer', fontSize: 12, lineHeight: 1,
                }}
                title="Remover"
              >×</button>
              {img._stats && (
                <div style={{
                  fontSize: 10, padding: '4px 6px',
                  background: '#dcfce7', color: '#166534',
                  textAlign: 'center',
                }}>
                  -{img._stats.reduction}% ({humanFileSize(img._stats.newSize)})
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
