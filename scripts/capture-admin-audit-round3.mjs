/**
 * Auditoria visual round3: login + 3 telas (Financeiro, Indicadores, Agenda).
 *
 * Uso (PowerShell):
 *   $env:PGM_BASE_URL="https://host/portal"   # origem + path base do app (sem barra final)
 *   $env:PGM_EMAIL="email@empresa.com"
 *   $env:PGM_PASSWORD="***"
 *   node scripts/capture-admin-audit-round3.mjs
 *
 * Requisitos: `npx playwright install chromium` (uma vez).
 * Conta sem 2FA no clique de login (senão o script termina com erro).
 */
import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const outDir = path.join(__dirname, '..', 'artifacts', 'screenshots', 'admin-audit-round3');

function joinUrl(base, rel) {
  const b = String(base).replace(/\/+$/, '');
  const r = rel.startsWith('/') ? rel : `/${rel}`;
  return b + r;
}

async function main() {
  const base = (process.env.PGM_BASE_URL || '').trim();
  const email = (process.env.PGM_EMAIL || '').trim();
  const password = (process.env.PGM_PASSWORD || '').trim();
  if (!base || !email || !password) {
    console.error(
      'Defina PGM_BASE_URL (ex.: https://servidor/portal), PGM_EMAIL e PGM_PASSWORD.',
    );
    process.exit(1);
  }

  fs.mkdirSync(outDir, { recursive: true });

  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();

  try {
    await page.goto(joinUrl(base, '/users/login'), {
      waitUntil: 'domcontentloaded',
      timeout: 120000,
    });
    await page.waitForSelector('#username', { timeout: 60000 });
    await page.fill('#username', email);
    await page.fill('#password', password);
    await page.click('.btn-login');

    const twoFa = page.locator('#modal-duasetapas');
    try {
      await twoFa.waitFor({ state: 'visible', timeout: 4000 });
      console.error(
        'Modal de duas etapas visível: use conta sem 2FA no login por clique ou faça captura manual.',
      );
      process.exitCode = 1;
      return;
    } catch {
      // segue
    }

    await page.waitForFunction(
      () => !/\/users\/login/i.test(window.location.pathname),
      { timeout: 120000 },
    );

    const targets = [
      ['financeiro', '/financeiro'],
      ['indicadores', '/indicadores'],
      ['agenda', '/agenda'],
    ];

    for (const [name, rel] of targets) {
      await page.goto(joinUrl(base, rel), {
        waitUntil: 'networkidle',
        timeout: 120000,
      });
      await page.reload({ waitUntil: 'networkidle' });
      const file = path.join(outDir, `audit-${name}.png`);
      await page.screenshot({ path: file, fullPage: true });
      console.log('Wrote', file);
    }
  } finally {
    await browser.close();
  }
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
