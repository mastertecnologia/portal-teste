<?php
/**
 * Diagnóstico avançado do login equipe (acessoEmpresa): banco, extração de POST e HTTP local.
 * Não grava a senha em log; usa fingerprint sha256 (12 chars) para comparar com logs/error.log.
 *
 * Uso:
 *   bash scripts/sh/debug_login_acesso_empresa_trace.sh "email@exemplo.com"
 */
$root = dirname(__DIR__);
chdir($root);

require $root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'cli_password_stdin_helper.php';

$login = isset($argv[1]) ? trim((string)$argv[1]) : '';
$doHttp = !in_array('--no-http', $argv ?? [], true);
if ($login === '') {
	fwrite(STDERR, "Uso: bash scripts/sh/debug_login_acesso_empresa_trace.sh \"email@exemplo.com\" [--no-http]\n");
	exit(2);
}

$plain = cli_read_password_from_stdin('debug_login_acesso_empresa_trace.php');
$pwdLen = strlen($plain);
$pwdFp = $pwdLen > 0 ? substr(hash('sha256', $plain), 0, 12) : '';

fwrite(STDERR, "Conectando ao banco (bootstrap Cake)...\n");
require $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'bootstrap.php';

require_once ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';
if (!defined('C_RoleCliente')) {
	define('C_RoleCliente', 1);
}
if (!defined('C_RoleFuncionario')) {
	define('C_RoleFuncionario', 0);
}

use App\Utility\Fiscal\FiscalSqlConditions;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;

$matchesPassword = static function (string $plain, string $stored): bool {
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

$simulateExtract = static function (array $post): array {
	$username = $post['username'] ?? ($post['Users']['username'] ?? null);
	$password = $post['password'] ?? ($post['Users']['password'] ?? null);
	$passwordOut = null;
	if ($password !== null && $password !== '') {
		$passwordOut = (string)$password;
	}

	return [
		'username' => strtolower(trim((string)$username)),
		'password' => $passwordOut,
	];
};

$conn = ConnectionManager::get('default');
$cfg = $conn->config();
$dbLabel = ($cfg['host'] ?? '?') . '/' . ($cfg['database'] ?? '?');

$loginMatch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $login);
$loginNorm = strtolower(trim($login));

$Users = TableRegistry::getTableLocator()->get('Users');
$connOrm = $Users->getConnection();

echo "\n=== debug_login_acesso_empresa_trace ===\n";
echo "Login informado: {$login}\n";
echo "Login normalizado (como acessoEmpresa): {$loginNorm}\n";
echo "Senha: len={$pwdLen} fingerprint_sha256_12={$pwdFp}\n";
echo "Datasource CLI: {$dbLabel}\n";
echo "  (Compare pwd_fp com logs/error.log após falha no browser.)\n\n";

// --- Todos os usuários com e-mail/username parecido (ativos e inativos) ---
$allLike = $Users->find()
	->where([
		'OR' => array_merge(
			FiscalSqlConditions::caseInsensitiveLike($connOrm, 'Users.email', $loginMatch),
			FiscalSqlConditions::caseInsensitiveLike($connOrm, 'Users.username', $loginMatch)
		),
	])
	->order(['Users.id' => 'ASC'])
	->all();

echo "--- Registros no banco (ativos e inativos) ---\n";
if ($allLike->count() === 0) {
	echo "Nenhuma linha com este e-mail/username.\n\n";
} else {
	foreach ($allLike as $u) {
		$hash = (string)$u->get('password');
		$prefix = strlen($hash) >= 4 ? substr($hash, 0, 7) : '(vazio)';
		$active = $Users->loginActiveInativoCondition();
		echo sprintf(
			"  id=%s role=%s inativo=%s bloqueado=%s email=%s hash=%s...\n",
			$u->get('id'),
			json_encode($u->get('role')),
			json_encode($u->get('inativo')),
			json_encode($u->get('bloqueado')),
			$u->get('email'),
			$prefix
		);
	}
	echo "\n";
}

$candidates = $Users->find()
	->where($Users->loginActiveInativoCondition())
	->where([
		'OR' => array_merge(
			FiscalSqlConditions::caseInsensitiveLike($connOrm, 'Users.email', $loginMatch),
			FiscalSqlConditions::caseInsensitiveLike($connOrm, 'Users.username', $loginMatch)
		),
	])
	->order(['Users.id' => 'ASC'])
	->all();

echo "--- Candidatos ATIVOS (mesma query do login) ---\n";
echo "Quantidade: " . $candidates->count() . "\n";
$matchedId = null;
foreach ($candidates as $user) {
	$hash = (string)$user->get('password');
	$ok = $matchesPassword($plain, $hash);
	$role = (int)$user->get('role');
	$roleOk = $role === (int)C_RoleFuncionario;
	echo sprintf(
		"  id=%s role=%s (equipe=%s) bcrypt=%s acessoEmpresa=%s\n",
		$user->get('id'),
		$role,
		$roleOk ? 'sim' : 'NAO — use /users/login',
		$ok ? 'OK' : 'FALHA',
		($ok && $roleOk) ? 'deveria entrar' : 'bloqueado por senha ou role'
	);
	if ($ok) {
		$matchedId = (int)$user->get('id');
	}
}
echo "\n";

if ($candidates->count() === 0) {
	echo "RESULTADO DB: sem candidato ativo — login sempre falha.\n\n";
	exit(1);
}
if ($matchedId === null) {
	echo "RESULTADO DB: senha NÃO confere com nenhum candidato ativo.\n";
	echo "  → A senha digitada aqui não é a gravada no banco (ou outro host/banco no Apache).\n\n";
} else {
	echo "RESULTADO DB: senha OK no user id={$matchedId}.\n";
	echo "  → Se HTTP/browser falhar, o POST não está enviando a mesma senha (autocomplete) ou o PHP web usa outro banco.\n\n";
}

// --- Simulação extração POST (como UsersController) ---
$postFlat = ['username' => $login, 'password' => $plain];
$postNorm = ['username' => $loginNorm, 'password' => $plain];
$extFlat = $simulateExtract($postFlat);
$extNorm = $simulateExtract($postNorm);

echo "--- Simulação _extractLoginCredentials ---\n";
echo "  POST username literal: len=" . strlen($extFlat['username']) . " password_len=" . ($extFlat['password'] !== null ? strlen($extFlat['password']) : 0) . "\n";
echo "  POST username já lower:  password_len=" . ($extNorm['password'] !== null ? strlen($extNorm['password']) : 0) . "\n\n";

if (!$doHttp) {
	exit($matchedId === null ? 1 : 0);
}

// --- HTTP local (HTTPS + Host, como debug_login_http_post.sh) ---
echo "--- HTTP POST local (https://127.0.0.1) ---\n";
$base = 'https://127.0.0.1/portal/users/acesso-empresa';
$host = 'portal.pgm.inf.br';
$cookieFile = tempnam(sys_get_temp_dir(), 'pgm_login_');
$bodyFile = tempnam(sys_get_temp_dir(), 'pgm_login_b_');
$hdrFile = tempnam(sys_get_temp_dir(), 'pgm_login_h_');
if ($cookieFile === false || $bodyFile === false || $hdrFile === false) {
	echo "  Não foi possível criar arquivos temporários.\n\n";
	exit(1);
}
register_shutdown_function(static function () use ($cookieFile, $bodyFile, $hdrFile) {
	@unlink($cookieFile);
	@unlink($bodyFile);
	@unlink($hdrFile);
});

$hostArg = escapeshellarg('Host: ' . $host);
$dataUser = escapeshellarg('username=' . $login);
$dataPass = escapeshellarg('password=' . $plain);

$codeGet = (int)trim((string)shell_exec(
	"curl -sk -o " . escapeshellarg($bodyFile) . " -w '%{http_code}' -D " . escapeshellarg($hdrFile)
	. " -c " . escapeshellarg($cookieFile) . " -H {$hostArg} " . escapeshellarg($base) . " 2>/dev/null"
));
echo "  GET: HTTP {$codeGet}\n";

$codePost = (int)trim((string)shell_exec(
	"curl -sk -o " . escapeshellarg($bodyFile) . " -w '%{http_code}' -D " . escapeshellarg($hdrFile)
	. " -b " . escapeshellarg($cookieFile) . " -c " . escapeshellarg($cookieFile)
	. " -H {$hostArg} -H 'Content-Type: application/x-www-form-urlencoded' -X POST "
	. escapeshellarg($base) . " --data-urlencode {$dataUser} --data-urlencode {$dataPass} 2>/dev/null"
));
$bodyHttp = is_readable($bodyFile) ? (string)file_get_contents($bodyFile) : '';
$hdrHttp = is_readable($hdrFile) ? (string)file_get_contents($hdrFile) : '';
$loc = '';
if (preg_match('/^[Ll]ocation:\s*(.+)$/m', $hdrHttp, $m)) {
	$loc = trim($m[1]);
}

echo "  POST: HTTP {$codePost}\n";
if ($loc !== '') {
	echo "  Location: {$loc}\n";
}
if (($codePost === 302 || $codePost === 303) && stripos($loc, 'dashboard') !== false) {
	echo "  RESULTADO HTTP: login OK (redirect dashboard).\n\n";
	exit(0);
}
if (stripos($bodyHttp, 'usuário e/ou senha incorretos') !== false || stripos($bodyHttp, 'usuario e/ou senha incorretos') !== false) {
	echo "  RESULTADO HTTP: credenciais rejeitadas.\n";
	if ($matchedId !== null) {
		echo "  ATENÇÃO: DB aceitou a senha mas HTTP rejeitou — compare pwd_fp={$pwdFp} no error.log;\n";
		echo "    confira se Apache usa o mesmo .env/app_local (host " . ($cfg['host'] ?? '?') . ").\n";
	} else {
		echo "  Senha também falhou no DB — use a mesma senha que acabou de definir no admin_set_user_password.\n";
	}
	echo "\n";
	exit(1);
}
echo "  RESULTADO HTTP: resposta ambígua (trecho): " . substr(preg_replace('/\s+/', ' ', $bodyHttp), 0, 200) . "\n\n";

exit($matchedId === null ? 1 : 0);
