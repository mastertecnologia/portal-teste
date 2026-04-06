-- Tabelas legadas ausentes em bases restauradas sem o DDL completo.
-- Uso: psql -U ... -d ... -f config/schema/postgres_ticketsservicos_ticketsmodulos_books_minimal.sql
--
-- ticketsservicos / ticketsmodulos: usados em TicketsController::viewfaturas (N:N ticket ↔ serviço/módulo).
-- books: só loadModel em UsersController; tabela mínima evita Auto-Table / falhas futuras.
-- Ajuste FKs se os nomes das tabelas servicos/modulos forem diferentes no teu ambiente.

SET search_path TO public;

CREATE TABLE IF NOT EXISTS ticketsservicos (
	id SERIAL PRIMARY KEY,
	idticket INTEGER NOT NULL,
	idservico INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS ix_ticketsservicos_idticket ON ticketsservicos (idticket);
CREATE INDEX IF NOT EXISTS ix_ticketsservicos_idservico ON ticketsservicos (idservico);
CREATE UNIQUE INDEX IF NOT EXISTS ux_ticketsservicos_ticket_servico ON ticketsservicos (idticket, idservico);

CREATE TABLE IF NOT EXISTS ticketsmodulos (
	id SERIAL PRIMARY KEY,
	idticket INTEGER NOT NULL,
	idmodulo INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS ix_ticketsmodulos_idticket ON ticketsmodulos (idticket);
CREATE INDEX IF NOT EXISTS ix_ticketsmodulos_idmodulo ON ticketsmodulos (idmodulo);
CREATE UNIQUE INDEX IF NOT EXISTS ux_ticketsmodulos_ticket_modulo ON ticketsmodulos (idticket, idmodulo);

CREATE TABLE IF NOT EXISTS books (
	id SERIAL PRIMARY KEY,
	iduser INTEGER NULL,
	titulo VARCHAR(255) NULL,
	created TIMESTAMP WITHOUT TIME ZONE NULL,
	modified TIMESTAMP WITHOUT TIME ZONE NULL
);
