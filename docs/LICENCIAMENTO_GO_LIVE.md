# Licenciamento (pg-lic-*) — go-live

## Deploy

```bash
cd /var/www/portal
git pull origin main
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data bin/cake cache clear_all
sudo -u www-data bin/cake migrations migrate
```

Migrations esperadas: `LicModuleFoundation`, `LicLicencasProdutoLabel`, `LicModuloConfig`, `RbacLicencasModulePermissions`, `RbacEquipeRolesLicencasModule`.

## PostgreSQL

```bash
psql -h 10.0.2.23 -U postgres -d pgm -c "\dt lic_*"
```

## RBAC

Após migrate, papéis `super_admin`, `admin_equipe` e `operacao` recebem `licencas.view`, `licencas.manage`, `licencas.cofre.view`.  
`licencas.cofre.secret` só em `super_admin` e `admin_equipe`.

Alternativa manual: **Permissões → Sincronizar catálogo** e atribuir na matriz.

## URLs

| Público | URL |
|---------|-----|
| Equipe | `/licencas-prototype` |
| Portal | `/cliente/licencas` |

## `.env` (opcional)

```env
LIC_COFRE_CIPHER_KEY=chave-longa-aleatoria
LICENCAS_CANONICAL_ROUTES=0
```

## Git em produção

- Branch de trabalho: `main`
- Não commitar `config/app_local.php` nem `.env`
- Antes de `git pull`, descartar alterações locais em `docs/generated/*` se necessário
