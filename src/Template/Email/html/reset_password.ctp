<?php
/**
 * Variáveis esperadas:
 * @var string $name
 * @var string $resetUrl
 * @var string $expirationText
 * @var string|int $currentYear
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Redefinição de senha</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; padding:0; background-color:#edf2f5; font-family:Arial, Helvetica, sans-serif; color:#173042;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#edf2f5; margin:0; padding:0; width:100%;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 8px 24px rgba(23,48,66,0.08);">

                    <tr>
                        <td style="padding:32px 32px 16px 32px; background:linear-gradient(135deg, #0ea5a4, #07b889);">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <div style="width:52px; height:52px; line-height:52px; text-align:center; border-radius:14px; background-color:rgba(255,255,255,0.16); color:#ffffff; font-weight:bold; font-size:18px;">
                                            PGM
                                        </div>
                                    </td>
                                    <td style="padding-left:14px; vertical-align:middle;">
                                        <div style="font-size:14px; font-weight:bold; letter-spacing:1px; color:#eafffb;">PORTAL PGM</div>
                                        <div style="font-size:13px; color:#d9fffa;">Acesso seguro do usuário</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">
                            <h1 style="margin:0 0 16px 0; font-size:28px; line-height:1.2; color:#173042;">
                                Redefina sua senha
                            </h1>

                            <p style="margin:0 0 16px 0; font-size:16px; line-height:1.6; color:#425466;">
                                Olá, <strong><?= h($name) ?></strong>,
                            </p>

                            <p style="margin:0 0 16px 0; font-size:16px; line-height:1.6; color:#425466;">
                                Recebemos uma solicitação para redefinir a senha da sua conta no <strong>Portal PGM</strong>.
                            </p>

                            <p style="margin:0 0 24px 0; font-size:16px; line-height:1.6; color:#425466;">
                                Para continuar com segurança, clique no botão abaixo:
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px 0;">
                                <tr>
                                    <td align="center" bgcolor="#07b889" style="border-radius:12px;">
                                        <a href="<?= h($resetUrl) ?>" target="_blank" style="display:inline-block; padding:14px 28px; font-size:16px; font-weight:bold; color:#ffffff; text-decoration:none; border-radius:12px;">
                                            Redefinir senha
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 24px 0; background-color:#f5fbfa; border:1px solid #d7f3ed; border-radius:12px;">
                                <tr>
                                    <td style="padding:16px;">
                                        <p style="margin:0 0 8px 0; font-size:14px; line-height:1.6; color:#0f766e;">
                                            🔒 Este link é válido por <strong><?= h($expirationText) ?></strong>.
                                        </p>
                                        <p style="margin:0; font-size:14px; line-height:1.6; color:#0f766e;">
                                            Se você não solicitou esta alteração, ignore este e-mail. Sua senha atual continuará segura até a redefinição ser concluída.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 12px 0; font-size:14px; line-height:1.6; color:#64748b;">
                                Se o botão acima não funcionar, copie e cole o link abaixo no seu navegador:
                            </p>

                            <p style="margin:0 0 24px 0; word-break:break-all; font-size:14px; line-height:1.6; color:#0ea5a4;">
                                <a href="<?= h($resetUrl) ?>" target="_blank" style="color:#0ea5a4; text-decoration:underline;"><?= h($resetUrl) ?></a>
                            </p>

                            <hr style="border:none; border-top:1px solid #e2e8f0; margin:24px 0;">

                            <p style="margin:0 0 8px 0; font-size:14px; line-height:1.6; color:#64748b;">
                                Atenciosamente,
                            </p>
                            <p style="margin:0; font-size:14px; line-height:1.6; color:#173042; font-weight:bold;">
                                Equipe Portal PGM
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 32px 32px 32px; background-color:#fafcfc;">
                            <p style="margin:0 0 8px 0; font-size:12px; line-height:1.6; color:#94a3b8;">
                                Este é um e-mail automático. Não responda esta mensagem.
                            </p>
                            <p style="margin:0; font-size:12px; line-height:1.6; color:#94a3b8;">
                                © <?= h($currentYear) ?> Portal PGM. Todos os direitos reservados.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
