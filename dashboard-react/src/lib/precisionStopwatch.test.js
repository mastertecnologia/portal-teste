import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { PRECISION_STOPWATCH_STATE, RENDER_INTERVAL_MS, createPrecisionStopwatch, formatElapsedHms } from './precisionStopwatch.js';

describe('formatElapsedHms', () => {
  it('formata 00:00:00 e horas com padding', () => {
    expect(formatElapsedHms(0)).toBe('00:00:00');
    expect(formatElapsedHms(3661000)).toBe('01:01:01');
    expect(formatElapsedHms(7323000)).toBe('02:02:03');
  });
});

describe('createPrecisionStopwatch', () => {
  let mockNow;
  let sw;

  beforeEach(() => {
    mockNow = 1_000_000;
    sw = createPrecisionStopwatch({ nowMs: () => mockNow });
  });

  it('inicia em idle e getElapsedMs é 0', () => {
    expect(sw.getState()).toBe(PRECISION_STOPWATCH_STATE.IDLE);
    expect(sw.getElapsedMs()).toBe(0);
  });

  it('start: elapsed segue onlyMs() (sem incremento manual)', () => {
    sw.start();
    expect(sw.getState()).toBe(PRECISION_STOPWATCH_STATE.RUNNING);
    mockNow += 5_000;
    expect(sw.getElapsedMs()).toBe(5_000);
  });

  it('múltiplos start() em running não aceleram o elapsed', () => {
    sw.start();
    mockNow = 1_100_000;
    expect(sw.getElapsedMs()).toBe(100_000);
    sw.start();
    sw.start();
    mockNow = 1_150_000;
    expect(sw.getElapsedMs()).toBe(150_000);
  });

  it('pause congela; resume continua; stop zera', () => {
    sw.start();
    mockNow += 20_000;
    expect(sw.getElapsedMs()).toBe(20_000);
    sw.pause();
    expect(sw.getState()).toBe(PRECISION_STOPWATCH_STATE.PAUSED);
    mockNow += 9_000_000;
    expect(sw.getElapsedMs()).toBe(20_000);
    sw.resume();
    mockNow += 3_000;
    expect(sw.getElapsedMs()).toBe(23_000);
    sw.stop();
    expect(sw.getState()).toBe(PRECISION_STOPWATCH_STATE.IDLE);
    expect(sw.getElapsedMs()).toBe(0);
  });

  it('syncRunningFromAnchor: elapsed = nowMs() - anchor', () => {
    const anchor = 500_000;
    mockNow = 510_000;
    sw.syncRunningFromAnchor(anchor);
    expect(sw.getElapsedMs()).toBe(10_000);
    expect(sw.getState()).toBe(PRECISION_STOPWATCH_STATE.RUNNING);
  });

  it('syncPaused e syncIdle', () => {
    sw.syncPaused(12_345);
    expect(sw.getState()).toBe(PRECISION_STOPWATCH_STATE.PAUSED);
    expect(sw.getElapsedMs()).toBe(12_345);
    sw.syncIdle();
    expect(sw.getState()).toBe(PRECISION_STOPWATCH_STATE.IDLE);
    expect(sw.getElapsedMs()).toBe(0);
  });
});

describe('createPrecisionStopwatch + setInterval (fake timers)', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });
  afterEach(() => {
    vi.useRealTimers();
  });

  it('só chama onRender enquanto running; pause para os ticks', () => {
    let mockNow = 0;
    const sw = createPrecisionStopwatch({ nowMs: () => mockNow });
    let ticks = 0;
    sw.setOnRender(() => {
      ticks += 1;
    });
    sw.start();
    vi.advanceTimersByTime(RENDER_INTERVAL_MS * 4);
    expect(ticks).toBe(4);
    sw.pause();
    const frozen = ticks;
    vi.advanceTimersByTime(10_000);
    expect(ticks).toBe(frozen);
  });

  it('não cria múltiplos intervals ao setOnRender de novo em running', () => {
    let mockNow = 0;
    const sw = createPrecisionStopwatch({ nowMs: () => mockNow });
    let ticks = 0;
    sw.setOnRender(() => {
      ticks += 1;
    });
    sw.start();
    sw.setOnRender(() => {
      ticks += 1;
    });
    vi.advanceTimersByTime(RENDER_INTERVAL_MS * 2);
    expect(ticks).toBe(2);
  });

  it('dispose remove o intervalo', () => {
    let mockNow = 0;
    const sw = createPrecisionStopwatch({ nowMs: () => mockNow });
    let ticks = 0;
    sw.setOnRender(() => {
      ticks += 1;
    });
    sw.start();
    sw.dispose();
    vi.advanceTimersByTime(RENDER_INTERVAL_MS * 5);
    expect(ticks).toBe(0);
  });
});
