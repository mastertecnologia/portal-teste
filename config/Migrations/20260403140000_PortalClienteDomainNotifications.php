<?php
/**
 * Notificações internas, preferências, histórico de eventos do cliente e log de e-mail automático.
 * Camada nova e desacoplada; não altera tabela legada `notificacoes` (tickets).
 *
 * Rodar: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class PortalClienteDomainNotifications extends AbstractMigration {

	public function up() {
		if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
			return;
		}

		if (!$this->hasTable('portal_internal_notifications')) {
			$this->execute(<<<'SQL'
CREATE TABLE portal_internal_notifications (
	id              SERIAL PRIMARY KEY,
	user_id         INTEGER NOT NULL,
	type            VARCHAR(16) NOT NULL DEFAULT 'info',
	title           VARCHAR(255) NOT NULL,
	message         TEXT NULL,
	entity_type     VARCHAR(64) NULL,
	entity_id       VARCHAR(64) NULL,
	action_url      VARCHAR(512) NULL,
	is_read         SMALLINT NOT NULL DEFAULT 0,
	metadata_json   TEXT NULL,
	created         TIMESTAMP WITHOUT TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
	modified        TIMESTAMP WITHOUT TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_portal_notif_user_unread ON portal_internal_notifications (user_id, is_read);
CREATE INDEX idx_portal_notif_created ON portal_internal_notifications (created DESC);
SQL
			);
		}

		if (!$this->hasTable('portal_notification_preferences')) {
			$this->execute(<<<'SQL'
CREATE TABLE portal_notification_preferences (
	id              SERIAL PRIMARY KEY,
	user_id         INTEGER NOT NULL,
	event_type      VARCHAR(128) NOT NULL,
	send_in_app     SMALLINT NOT NULL DEFAULT 1,
	send_email      SMALLINT NOT NULL DEFAULT 0,
	created         TIMESTAMP WITHOUT TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
	modified        TIMESTAMP WITHOUT TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
	UNIQUE (user_id, event_type)
);
CREATE INDEX idx_portal_notif_pref_user ON portal_notification_preferences (user_id);
SQL
			);
		}

		if (!$this->hasTable('client_domain_events')) {
			$this->execute(<<<'SQL'
CREATE TABLE client_domain_events (
	id              SERIAL PRIMARY KEY,
	idcliente       INTEGER NOT NULL,
	event_type      VARCHAR(128) NOT NULL,
	description     TEXT NULL,
	actor_user_id   INTEGER NULL,
	metadata_json   TEXT NULL,
	created         TIMESTAMP WITHOUT TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_client_domain_ev_client ON client_domain_events (idcliente, created DESC);
CREATE INDEX idx_client_domain_ev_type ON client_domain_events (event_type);
SQL
			);
		}

		if (!$this->hasTable('portal_mail_automation_logs')) {
			$this->execute(<<<'SQL'
CREATE TABLE portal_mail_automation_logs (
	id              SERIAL PRIMARY KEY,
	event_type      VARCHAR(128) NOT NULL,
	recipient       VARCHAR(255) NOT NULL,
	subject         VARCHAR(512) NULL,
	status          VARCHAR(32) NOT NULL,
	error_message   TEXT NULL,
	metadata_json   TEXT NULL,
	created         TIMESTAMP WITHOUT TIME ZONE NULL DEFAULT CURRENT_TIMESTAMP,
	sent_at         TIMESTAMP WITHOUT TIME ZONE NULL
);
CREATE INDEX idx_portal_mail_log_created ON portal_mail_automation_logs (created DESC);
SQL
			);
		}
	}

	public function down() {
		// Sem DROP em produção via rollback automático
	}
}
