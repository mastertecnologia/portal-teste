<?php
/**
 * Diagnóstico de login (sem validar senha): localiza usuário por e-mail/username (case-insensitive)
 * e mostra role, inativo, bloqueado e se o hash de senha parece bcrypt.
 *
 * Uso na raiz do projeto (com .env / app_local e vendor):
 *   php scripts/debug_login_lookup.php "usuario@email.com"
 *
 * Não grava log de senha; não altera o banco.
 */
$root = dirname(__DIR__);
chdir($root);

require $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'bootstrap.php';

require_once ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';
if (!defined('C_RoleCliente'))             define('C_RoleCliente', 1);
if (!defined('C_RoleFuncionario'))          define('C_RoleFuncionario', 0);
if (!defined('C_EmpresaPGM'))               define('C_EmpresaPGM', 2);

use App\Utility\Fiscal\FiscalSqlConditions;
use Cake\ORM\TableRegistry;

$login = isset($argv[1]) ? trim((string)$argv[1]) : '';
if ($login === '') {
	fwrite(STDERR, "Uso: php scripts/debug_login_lookup.php \"email@ou.username\"\n");
	exit(2);
}

$loginMatch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $login);

$Users = TableRegistry::getTableLocator()->get('Users');
$conn = $Users->getConnection();
$user = $Users->find()
	->where([
		'OR' => [
			'Users.inativo IS' => null,
			'Users.inativo IS NOT' => true,
		],
	])
	->where([
		'OR' => array_merge(
			FiscalSqlConditions::caseInsensitiveLike($conn, 'Users.email', $loginMatch),
			FiscalSqlConditions::caseInsensitiveLike($conn, 'Users.username', $loginMatch)
		),
	])
	->first();

echo "\n=== debug_login_lookup ===\n";
echo "Entrada: {$login}\n\n";

if ($user === null) {
	echo "RESULTADO: nenhum usuário ATIVO (inativo=0) encontrado por LOWER(email) ou LOWER(username).\n";
	echo "Dica: verifique inativo=1 no banco, ou e-mail diferente do digitado.\n\n";
	exit(1);
}

$pwd = (string)$user->get('password');
$hashOk = (strlen($pwd) >= 60 && strpos($pwd, '$2') === 0);

echo "RESULTADO: usuário encontrado.\n";
echo "  id:         " . $user->get('id') . "\n";
echo "  username:   " . $user->get('username') . "\n";
echo "  email:      " . ($user->get('email') !== null ? $user->get('email') : '(null)') . "\n";
echo "  role:       " . json_encode($user->get('role')) . " (ERP/equipe costuma ser 0; cliente = C_RoleCliente)\n";
echo "  inativo:    " . json_encode($user->get('inativo')) . "\n";
echo "  bloqueado:  " . json_encode($user->get('bloqueado')) . "\n";
echo "  senha hash: " . ($hashOk ? 'parece bcrypt ($2...)' : 'NÃO parece bcrypt — login com DefaultPasswordHasher pode falhar') . "\n";

echo "\nConstantes (UserConstants + fallback):\n";
echo "  C_RoleFuncionario (equipe ERP): " . C_RoleFuncionario . "\n";
echo "  C_RoleCliente (portal cliente):   " . C_RoleCliente . "\n";
echo "  C_EmpresaPGM:                     " . C_EmpresaPGM . "\n";

$ur = (int)$user->get('role');
echo "\nURL de login esperada para este usuário:\n";
if ($ur === (int)C_RoleFuncionario) {
	echo "  Equipe -> POST /portal/users/acesso-empresa (não use /users/login).\n";
} elseif ($ur === (int)C_RoleCliente) {
	echo "  Cliente -> POST /portal/users/login (não use acesso-empresa).\n";
} else {
	echo "  Role " . $ur . " não é 0 nem 1 — conferir coluna users.role no banco.\n";
}
echo "  (Um único banco PostgreSQL; idempresa na sessão após login — não há outro host por empresa.)\n";
echo "\n";

exit(0);
