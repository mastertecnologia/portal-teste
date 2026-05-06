import { useEffect, useRef, useState, useCallback } from 'react';

/**
 * Hook para auto-save com debounce.
 *
 * Uso:
 *   const { saveStatus, triggerSave, savedAt } = useDebounceSave({
 *     data: parecer,
 *     onSave: async (data) => await PareceresAPI.update(parecer.id, data),
 *     delay: 600,
 *   });
 *
 * @returns {{
 *   saveStatus: 'idle' | 'pending' | 'saving' | 'saved' | 'error',
 *   savedAt: Date | null,
 *   error: any,
 *   triggerSave: () => void,
 *   isPending: boolean,
 * }}
 */
export default function useDebounceSave({ data, onSave, delay = 600, enabled = true }) {
  const [saveStatus, setSaveStatus] = useState('idle');
  const [savedAt, setSavedAt] = useState(null);
  const [error, setError] = useState(null);

  const timerRef = useRef(null);
  const lastSavedRef = useRef(null);
  const isMountedRef = useRef(true);

  useEffect(() => {
    return () => {
      isMountedRef.current = false;
      if (timerRef.current) clearTimeout(timerRef.current);
    };
  }, []);

  const performSave = useCallback(async (currentData) => {
    if (!isMountedRef.current) return;
    setSaveStatus('saving');
    try {
      await onSave(currentData);
      if (!isMountedRef.current) return;
      lastSavedRef.current = JSON.stringify(currentData);
      setSavedAt(new Date());
      setError(null);
      setSaveStatus('saved');
    } catch (err) {
      if (!isMountedRef.current) return;
      setError(err);
      setSaveStatus('error');
    }
  }, [onSave]);

  // Detecta mudanças e agenda save
  useEffect(() => {
    if (!enabled || !data) return;

    const serialized = JSON.stringify(data);
    if (serialized === lastSavedRef.current) return;  // sem mudanças

    setSaveStatus('pending');

    if (timerRef.current) clearTimeout(timerRef.current);
    timerRef.current = setTimeout(() => {
      performSave(data);
    }, delay);

    return () => {
      if (timerRef.current) clearTimeout(timerRef.current);
    };
  }, [data, delay, enabled, performSave]);

  // Aviso ao fechar a aba se houver pendência
  useEffect(() => {
    if (!['pending', 'saving', 'error'].includes(saveStatus)) return;
    const handler = (e) => {
      e.preventDefault();
      e.returnValue = '';
    };
    window.addEventListener('beforeunload', handler);
    return () => window.removeEventListener('beforeunload', handler);
  }, [saveStatus]);

  const triggerSave = useCallback(() => {
    if (timerRef.current) clearTimeout(timerRef.current);
    performSave(data);
  }, [data, performSave]);

  return {
    saveStatus,
    savedAt,
    error,
    triggerSave,
    isPending: ['pending', 'saving'].includes(saveStatus),
  };
}
