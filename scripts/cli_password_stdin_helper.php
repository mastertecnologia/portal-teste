<?php
/**
 * Leitura de senha via stdin para scripts CLI (evita travar sem feedback).
 */
function cli_stdin_is_tty(): bool {
	return function_exists('posix_isatty') && @posix_isatty(STDIN);
}

function cli_print_password_stdin_help(string $scriptName): void {
	fwrite(STDERR, "Este script precisa da senha pelo stdin (pipe ou heredoc).\n\n");
	fwrite(STDERR, "Se o terminal \"travou\" no comando read -rs: digite a senha (não aparece) e pressione Enter.\n");
	fwrite(STDERR, "Para cancelar: Ctrl+C\n\n");
	fwrite(STDERR, "Exemplo (uma linha, após digitar a senha + Enter no read):\n");
	fwrite(STDERR, "  read -rs LOGIN_PW; echo; printf '%s' \"\$LOGIN_PW\" | php scripts/{$scriptName} \"email@exemplo.com\"; unset LOGIN_PW\n\n");
	fwrite(STDERR, "Alternativa (heredoc — pode ficar no histórico do shell):\n");
	fwrite(STDERR, "  php scripts/{$scriptName} \"email@exemplo.com\" <<'EOF'\n");
	fwrite(STDERR, "  sua_senha_aqui\n");
	fwrite(STDERR, "  EOF\n\n");
}

/**
 * Senha via CLI_LOGIN_PASSWORD (wrapper .sh) ou stdin. Nunca logar o valor.
 *
 * @return string
 */
function cli_read_password_from_stdin(string $scriptName): string {
	$fromEnv = getenv('CLI_LOGIN_PASSWORD');
	if ($fromEnv !== false && $fromEnv !== '') {
		putenv('CLI_LOGIN_PASSWORD');
		fwrite(STDERR, "Senha recebida (CLI_LOGIN_PASSWORD).\n");

		return $fromEnv;
	}

	if (cli_stdin_is_tty()) {
		cli_print_password_stdin_help($scriptName);
		fwrite(STDERR, "Recomendado: bash scripts/sh/debug_login_password_check.sh \"email@exemplo.com\"\n\n");
		exit(2);
	}
	fwrite(STDERR, "Lendo senha do stdin...\n");
	$plain = stream_get_contents(STDIN);
	if ($plain === false || $plain === '') {
		fwrite(STDERR, "Senha vazia no pipe (Enter sem digitar no read -rs?).\n");
		fwrite(STDERR, "Use: bash scripts/sh/debug_login_password_check.sh \"email@exemplo.com\"\n");
		exit(2);
	}

	return $plain;
}
