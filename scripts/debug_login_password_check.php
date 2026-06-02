<?php
/**
 * Testa se a senha confere com algum usuário ativo (e-mail/username), sem gravar a senha em log.
 *
 * Uso (senha pelo stdin — não passe na linha de comando):
 *   read -rs LOGIN_PW && printf '%s' "$LOGIN_PW" | php scripts/debug_login_password_check.php "email@exemplo.com"
 *
 * Não altera o banco.
 */
$root = dirname(__DIR__);
chdir($root);

require $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Utility\Fiscal\FiscalSqlConditions;
use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\TableRegistry;

$login = isset($argv[1]) ? trim((string)$argv[1]) : '';
if ($login === '') {
	fwrite(STDERR, "Uso: printf 'senha' | php scripts/debug_login_password_check.php \"email@exemplo.com\"\n");
	exit(2);
}

$plain = stream_get_contents(STDIN);
if ($plain === false || $plain === '') {
	fwrite(STDERR, "Informe a senha via stdin (ex.: read -rs P && printf '%s' \"\$P\" | php ...).\n");
	exit(2);
}

$matches = static function (string $plain, string $stored): bool {
	$stored = (string)$stored;
	if ($stored === '') {
		return false;
	}
	if ((new DefaultPasswordHasher())->check($plain, $stored)) {
		return true;
	}
	if (strlen($stored) === 40 && ctype_xdigit($stored)) {
		return hash_equals(strtolower($stored), sha1($plain));
	}
	if (strlen($stored) === 32 && ctype_xdigit($stored)) {
		return hash_equals(strtolower($stored), md5($plain));
	}

	return false;
};

$loginMatch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $login);
$Users = TableRegistry::getTableLocator()->get('Users');
$conn = $Users->getConnection();
$candidates = $Users->find()
	->where($Users->loginActiveInativoCondition())
	->where([
		'OR' => array_merge(
			FiscalSqlConditions::caseInsensitiveLike($conn, 'Users.email', $loginMatch),
			FiscalSqlConditions::caseInsensitiveLike($conn, 'Users.username', $loginMatch)
		),
	])
	->order(['Users.id' => 'ASC'])
	->all();

echo "\n=== debug_login_password_check ===\n";
echo "Login: {$login}\n";
echo "Candidatos ativos: " . $candidates->count() . "\n\n";

if ($candidates->count() === 0) {
	echo "RESULTADO: nenhum usuário ativo para este login.\n\n";
	exit(1);
}

$any = false;
foreach ($candidates as $user) {
	$hash = (string)$user->get('password');
	$ok = $matches($plain, $hash);
	$prefix = strlen($hash) >= 4 ? substr($hash, 0, 4) : '(vazio)';
	echo sprintf(
		"  id=%s role=%s email=%s hash_prefix=%s => %s\n",
		$user->get('id'),
		json_encode($user->get('role')),
		$user->get('email'),
		$prefix,
		$ok ? 'SENHA OK' : 'senha não confere'
	);
	if ($ok) {
		$any = true;
	}
}

echo "\nRESULTADO: " . ($any ? "pelo menos um candidato aceitou a senha (login deveria funcionar)." : "nenhum candidato aceitou a senha — use Recuperar senha ou confira o teclado/caps.") . "\n\n";

exit($any ? 0 : 1);
