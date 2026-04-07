<?php
/**
 * Variáveis esperadas:
 * @var string $name
 * @var string $resetUrl
 * @var string $expirationText
 * @var string|int $currentYear
 */
?>
Olá, <?= h($name) ?>,

Recebemos uma solicitação para redefinir a senha da sua conta no Portal PGM.

Para continuar, acesse o link abaixo:
<?= h($resetUrl) ?>


Este link é válido por <?= h($expirationText) ?>.

Se você não solicitou esta alteração, ignore este e-mail. Sua senha atual continuará segura até que a redefinição seja concluída.

Atenciosamente,
Equipe Portal PGM

© <?= h($currentYear) ?> Portal PGM
