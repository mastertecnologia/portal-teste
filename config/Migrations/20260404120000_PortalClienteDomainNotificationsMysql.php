<?php
/**
 * Mesmas tabelas de 20260403140000_PortalClienteDomainNotifications, para MySQL/MariaDB.
 *
 * Motivo: a migration anterior só executava DDL em PostgreSQL; em ambientes default=mysql o registro
 * em phinxlog pode existir sem tabelas. Esta versão aplica o CREATE apenas em mysql.
 *
 * Rodar: bin/cake migrations migrate
 */
use Migrations\AbstractMigration;

class PortalClienteDomainNotificationsMysql extends AbstractMigration {

	/** PDO + Phinx: cobre mysql/mysqli em ambientes diferentes. */
	protected function _isMysql(): bool {
		try {
			$c = $this->getAdapter()->getConnection();
			if ($c && $c->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql') {
				return true;
			}
		} catch (\Throwable $e) {
		}
		$t = strtolower((string)$this->getAdapter()->getAdapterType());

		return in_array($t, ['mysql', 'mysqli', 'mariadb'], true);
	}

	public function up() {
		if (!$this->_isMysql()) {
			return;
		}

		if (!$this->hasTable('portal_internal_notifications')) {
			$this->execute(<<<'SQL'
CREATE TABLE `portal_internal_notifications` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`user_id` int NOT NULL,
	`type` varchar(16) NOT NULL DEFAULT 'info',
	`title` varchar(255) NOT NULL,
	`message` text NULL,
	`entity_type` varchar(64) NULL,
	`entity_id` varchar(64) NULL,
	`action_url` varchar(512) NULL,
	`is_read` smallint NOT NULL DEFAULT 0,
	`metadata_json` text NULL,
	`created` datetime NULL DEFAULT CURRENT_TIMESTAMP,
	`modified` datetime NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `idx_portal_notif_user_unread` (`user_id`, `is_read`),
	KEY `idx_portal_notif_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
			);
		}

		if (!$this->hasTable('portal_notification_preferences')) {
			$this->execute(<<<'SQL'
CREATE TABLE `portal_notification_preferences` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`user_id` int NOT NULL,
	`event_type` varchar(128) NOT NULL,
	`send_in_app` smallint NOT NULL DEFAULT 1,
	`send_email` smallint NOT NULL DEFAULT 0,
	`created` datetime NULL DEFAULT CURRENT_TIMESTAMP,
	`modified` datetime NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_portal_notif_pref_user_event` (`user_id`, `event_type`),
	KEY `idx_portal_notif_pref_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
			);
		}

		if (!$this->hasTable('client_domain_events')) {
			$this->execute(<<<'SQL'
CREATE TABLE `client_domain_events` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`idcliente` int NOT NULL,
	`event_type` varchar(128) NOT NULL,
	`description` text NULL,
	`actor_user_id` int NULL,
	`metadata_json` text NULL,
	`created` datetime NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	KEY `idx_client_domain_ev_client` (`idcliente`, `created`),
	KEY `idx_client_domain_ev_type` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
			);
		}

		if (!$this->hasTable('portal_mail_automation_logs')) {
			$this->execute(<<<'SQL'
CREATE TABLE `portal_mail_automation_logs` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`event_type` varchar(128) NOT NULL,
	`recipient` varchar(255) NOT NULL,
	`subject` varchar(512) NULL,
	`status` varchar(32) NOT NULL,
	`error_message` text NULL,
	`metadata_json` text NULL,
	`created` datetime NULL DEFAULT CURRENT_TIMESTAMP,
	`sent_at` datetime NULL,
	PRIMARY KEY (`id`),
	KEY `idx_portal_mail_log_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL
			);
		}
	}

	public function down() {
		// Sem DROP automático em produção
	}
}
