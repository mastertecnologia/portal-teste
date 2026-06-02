<?php
/**
 * Redefine a senha de um usuário ativo (CLI, servidor). Usa o mesmo bcrypt do portal.
 *
 * Uso:
 *   CONFIRM=yes read -rs NEW_PW && printf '%s' "$NEW_PW" | \
 *     php scripts/admin_set_user_password.php "email@exemplo.com"
 *
 * Não passe a senha na linha de comando. Não registra a senha em log.
 */
$root = dirname(__DIR__);
chdir($root);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "Somente CLI.\n");
	exit(1);
}

if (getenv('CONFIRM') !== 'yes') {
	fwrite(STDERR, "Defina CONFIRM=yes para executar (evita uso acidental).\n");
	exit(1);
}

require $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Utility\Fiscal\FiscalSqlConditions;
use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\TableRegistry;

$login = isset($argv[1]) ? trim((string)$argv[1]) : '';
if ($login === '') {
	fwrite(STDERR, "Uso: CONFIRM=yes printf 'senha' | php scripts/admin_set_user_password.php \"email@exemplo.com\"\n");
	exit(2);
}

$plain = stream_get_contents(STDIN);
if ($plain === false || $plain === '') {
	fwrite(STDERR, "Informe a nova senha via stdin.\n");
	exit(2);
}

if (strlen($plain) < 4) {
	fwrite(STDERR, "Senha muito curta (mínimo 4 caracteres, alinhado ao portal).\n");
	exit(2);
}

$loginMatch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $login);
$Users = TableRegistry::getTableLocator()->get('Users');
$conn = $Users->getConnection();
$user = $Users->find()
	->where($Users->loginActiveInativoCondition())
	->where([
		'OR' => array_merge(
			FiscalSqlConditions::caseInsensitiveLike($conn, 'Users.email', $loginMatch),
			FiscalSqlConditions::caseInsensitiveLike($conn, 'Users.username', $loginMatch)
		),
	])
	->order(['Users.id' => 'ASC'])
	->first();

if ($user === null) {
	fwrite(STDERR, "Nenhum usuário ATIVO encontrado para: {$login}\n");
	exit(1);
}

$user = $Users->patchEntity($user, ['password' => $plain], ['validate' => false]);
if (!$Users->save($user, ['validate' => false, 'checkRules' => false])) {
	fwrite(STDERR, "Falha ao gravar senha no banco.\n");
	exit(1);
}

$reloaded = $Users->get($user->id);
$hash = (string)$reloaded->get('password');
$ok = (new DefaultPasswordHasher())->check($plain, $hash);

echo "OK: senha atualizada para user id=" . $user->id . " (" . $reloaded->get('email') . ").\n";
echo "Verificação bcrypt após save: " . ($ok ? 'OK' : 'FALHOU — investigar') . "\n";
echo "Faça login em /portal/users/acesso-empresa com a nova senha.\n";

exit($ok ? 0 : 1);
