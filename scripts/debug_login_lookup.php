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

use Cake\ORM\TableRegistry;

$login = isset($argv[1]) ? trim((string)$argv[1]) : '';
if ($login === '') {
	fwrite(STDERR, "Uso: php scripts/debug_login_lookup.php \"email@ou.username\"\n");
	exit(2);
}

$lower = strtolower($login);

$Users = TableRegistry::getTableLocator()->get('Users');
$user = $Users->find()
	->where(['inativo' => 0])
	->where(['OR' => [
		['LOWER(Users.email)' => $lower],
		['LOWER(Users.username)' => $lower],
	]])
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
echo "\n";

exit(0);
