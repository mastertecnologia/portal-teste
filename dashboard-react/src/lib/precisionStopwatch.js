/**
 * Cronômetro de precisão: única base de tempo via função `nowMs` (tipicamente Date.now + offset de servidor).
 * Nenhum acúmulo por incremento; elapsed = acumulado + (agora - início do segmento em execução).
 * setInterval é usado só para re-render (≤ 50 ms), nunca para avançar o tempo.
 */

export const RENDER_INTERVAL_MS = 50;

const STATES = Object.freeze({ IDLE: 'idle', RUNNING: 'running', PAUSED: 'paused' });

/**
 * @param {object} [opts]
 * @param {() => number} [opts.nowMs] - Fonte de tempo (padrão: Date.now)
 */
export function createPrecisionStopwatch({ nowMs = () => Date.now() } = {}) {
  let state = STATES.IDLE;
  let accumulatedMs = 0;
  /** Início do segmento atual (running), em ms no mesmo reloj que nowMs */
  let segmentStartAt = null;
  let renderIntervalId = null;
  let onRender = null;

  function getElapsedMs() {
    if (state === STATES.IDLE) return 0;
    if (state === STATES.PAUSED) return Math.max(0, accumulatedMs);
    if (state === STATES.RUNNING && segmentStartAt != null) {
      return Math.max(0, accumulatedMs + (nowMs() - segmentStartAt));
    }
    return 0;
  }

  function clearRenderInterval() {
    if (renderIntervalId != null) {
      clearInterval(renderIntervalId);
      renderIntervalId = null;
    }
  }

  function ensureRenderInterval() {
    if (renderIntervalId != null) return;
    if (typeof onRender !== 'function') return;
    if (state !== STATES.RUNNING) return;
    renderIntervalId = setInterval(() => {
      onRender();
    }, RENDER_INTERVAL_MS);
  }

  function setState(next) {
    state = next;
  }

  return {
    getState: () => state,

    getElapsedMs,

    /**
     * Callback chamado a cada RENDER_INTERVAL_MS enquanto running (para a UI atualizar o texto).
     */
    setOnRender: (cb) => {
      onRender = typeof cb === 'function' ? cb : null;
      if (state !== STATES.RUNNING) {
        clearRenderInterval();
        return;
      }
      /* Novo callback em running: (re)liga o interval de forma idempotente */
      clearRenderInterval();
      ensureRenderInterval();
    },

    /**
     * idle → running a partir de zero, ou se já running não faz nada (evita aceleração por cliques).
     */
    start: () => {
      if (state === STATES.RUNNING) return;
      if (state === STATES.PAUSED) {
        setState(STATES.RUNNING);
        segmentStartAt = nowMs();
        ensureRenderInterval();
        return;
      }
      setState(STATES.RUNNING);
      accumulatedMs = 0;
      segmentStartAt = nowMs();
      ensureRenderInterval();
    },

    /**
     * running → paused, interrompe qualquer contagem; remove intervalo de render.
     */
    pause: () => {
      if (state !== STATES.RUNNING) return;
      accumulatedMs += nowMs() - segmentStartAt;
      segmentStartAt = null;
      setState(STATES.PAUSED);
      clearRenderInterval();
    },

    /**
     * paused → running, continua do ponto congelado.
     */
    resume: () => {
      if (state !== STATES.PAUSED) return;
      setState(STATES.RUNNING);
      segmentStartAt = nowMs();
      ensureRenderInterval();
    },

    /**
     * Qualquer → idle, zera tudo e limpa o intervalo.
     */
    stop: () => {
      clearRenderInterval();
      setState(STATES.IDLE);
      accumulatedMs = 0;
      segmentStartAt = null;
    },

    /**
     * Sincroniza com sessão em execução: elapsed = nowMs() - anchorMs (acumulado 0, um segmento).
     * @param {number} anchorMs - instante (ms) em que o cronômetro “passou a correr”
     */
    syncRunningFromAnchor: (anchorMs) => {
      clearRenderInterval();
      if (typeof anchorMs !== 'number' || !Number.isFinite(anchorMs)) {
        setState(STATES.IDLE);
        accumulatedMs = 0;
        segmentStartAt = null;
        return;
      }
      setState(STATES.RUNNING);
      accumulatedMs = 0;
      segmentStartAt = anchorMs;
      ensureRenderInterval();
    },

    /**
     * Sincroniza estado pausado com elapsed total conhecido (ex.: de timestamps do servidor).
     * @param {number} totalElapsedMs
     */
    syncPaused: (totalElapsedMs) => {
      clearRenderInterval();
      const v = Math.max(0, Math.floor(Number(totalElapsedMs) || 0));
      setState(STATES.PAUSED);
      accumulatedMs = v;
      segmentStartAt = null;
    },

    /**
     * Volta a idle (sem ação de API); útil se o estado remoto deixou de existir.
     */
    syncIdle: () => {
      clearRenderInterval();
      setState(STATES.IDLE);
      accumulatedMs = 0;
      segmentStartAt = null;
    },

    /**
     * Libera o intervalo (ex.: unmount do componente).
     */
    dispose: () => {
      clearRenderInterval();
    },
  };
}

/**
 * @param {number} ms
 * @returns {string} HH:MM:SS
 */
export function formatElapsedHms(ms) {
  const safe = Math.max(0, Math.floor(ms));
  const totalSeconds = Math.floor(safe / 1000);
  const s = totalSeconds % 60;
  const m = Math.floor(totalSeconds / 60) % 60;
  const h = Math.floor(totalSeconds / 3600);
  const pad = (n) => String(n).padStart(2, '0');
  return `${pad(h)}:${pad(m)}:${pad(s)}`;
}

export { STATES as PRECISION_STOPWATCH_STATE };
