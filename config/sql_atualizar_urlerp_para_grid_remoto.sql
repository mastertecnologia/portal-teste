-- Atualizar URL do ERP (Grid) para a infraestrutura com servidores separados.
-- Portal: 10.0.2.25 | PostgreSQL: 10.0.2.23 | ERP/Grid: 10.0.2.7 (ECS-MASTER)
--
-- Execute no PostgreSQL do Portal, ex.:
--   psql -h 10.0.2.23 -U postgres -d pgm -f config/sql_atualizar_urlerp_para_grid_remoto.sql
--
-- Ajuste o WHERE se quiser apenas uma empresa (ex.: WHERE id = 1).

UPDATE empresas
SET urlerp = 'http://10.0.2.7:85/WebGridPGM/'
WHERE urlerp IS NULL
   OR urlerp = ''
   OR urlerp LIKE '%localhost%'
   OR urlerp LIKE '%ECS-MASTER%'
   OR urlerp LIKE '%127.0.0.1%';
