/**
 * Garante que `public/tickets-app` espelha `webroot/tickets-app` (o CakePHP
 * serve o bundle a partir de WWW_ROOT, mas alguns deploys/validações
 * referem-se ainda a public/).
 */
const { cpSync, existsSync, rmSync, mkdirSync } = require('fs');
const { join, dirname } = require('path');

const root = join(__dirname, '..');
const src = join(root, 'webroot', 'tickets-app');
const dest = join(root, 'public', 'tickets-app');

if (!existsSync(src)) {
  console.error('postbuild: origem inexistente:', src);
  process.exit(1);
}
if (existsSync(dest)) {
  rmSync(dest, { recursive: true, force: true });
}
mkdirSync(dirname(dest), { recursive: true });
cpSync(src, dest, { recursive: true });
console.log('postbuild: public/tickets-app <- webroot/tickets-app');
