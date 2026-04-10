<?php
namespace App\Utility\Fiscal;

use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\Mailer\Email;
use Cake\ORM\TableRegistry;

/**
 * Envio de DANFE (PDF) + XML por e-mail ao cliente/destinatário.
 *
 * Utiliza o transport SMTP configurado em FISCAL_SMTP_TRANSPORT (default: pgm).
 * O remetente é alinhado automaticamente ao utilizador SMTP quando necessário
 * (mesmo comportamento de ContractNotificationService).
 */
class FiscalEmailService {

    /**
     * Envia DANFE + XML da nota autorizada por e-mail.
     *
     * @param int $notaId
     * @param int $idempresa
     * @param string|null $destinatarioOverride E-mail alternativo (se null, usa cliente da nota)
     * @return array{ok: bool, message: string}
     */
    public static function enviarNotaAutorizada(int $notaId, int $idempresa, ?string $destinatarioOverride = null): array {
        $FiscalNotas = TableRegistry::getTableLocator()->get('FiscalNotas');
        $FiscalNotasXmls = TableRegistry::getTableLocator()->get('FiscalNotasXmls');
        $FiscalCertificados = TableRegistry::getTableLocator()->get('FiscalCertificados');
        $FiscalEmpresasConfig = TableRegistry::getTableLocator()->get('FiscalEmpresasConfig');
        $Empresas = TableRegistry::getTableLocator()->get('Empresas');

        $nota = $FiscalNotas->find()
            ->contain([
                'Clientes',
                'FiscalNotasItens' => ['FiscalNotasImpostos'],
                'FiscalNotasPagamentos',
            ])
            ->where(['FiscalNotas.id' => $notaId, 'FiscalNotas.idempresa' => $idempresa])
            ->first();

        if (!$nota) {
            return ['ok' => false, 'message' => 'Nota fiscal não encontrada.'];
        }
        if ($nota->status !== 'autorizada') {
            return ['ok' => false, 'message' => 'Apenas notas autorizadas podem ser enviadas por e-mail.'];
        }

        // Determinar destinatário
        $emailDest = $destinatarioOverride;
        if ($emailDest === null || $emailDest === '') {
            $emailDest = trim((string)($nota->cliente->email ?? ''));
        }
        if ($emailDest === '' || !filter_var($emailDest, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'E-mail do destinatário não encontrado ou inválido.'];
        }

        // Gerar DANFE PDF
        try {
            $empresa = $Empresas->get($idempresa);
            $configFiscal = $FiscalEmpresasConfig->getOrCreate($idempresa);

            $danfe = new FiscalDanfe(
                $nota->toArray(),
                $empresa->toArray(),
                $nota->cliente ? $nota->cliente->toArray() : [],
                $configFiscal->toArray()
            );
            $pdfContent = $danfe->generate();
        } catch (\Exception $e) {
            Log::warning('FiscalEmailService DANFE: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Erro ao gerar DANFE: ' . $e->getMessage()];
        }

        // Obter XML de autorização
        $xmlEntity = $FiscalNotasXmls->find()
            ->where(['fiscal_nota_id' => $notaId, 'tipo' => 'autorizacao'])
            ->order(['created' => 'DESC'])
            ->first();
        $xmlContent = $xmlEntity ? ($xmlEntity->xml_retorno ?: $xmlEntity->xml_envio) : null;

        // Montar nomes de arquivo
        $chaveOuId = $nota->chave_acesso ?: ('nota_' . $nota->id);
        $pdfFilename = 'DANFE_' . ($nota->numero ?? 'rascunho') . '.pdf';
        $xmlFilename = 'NFe_' . $chaveOuId . '.xml';

        // Dados para template
        $empresaNome = $empresa->razaosocial ?? $empresa->nome ?? 'Empresa';
        $numero = $nota->numero ?? '-';
        $serie = $nota->serie ?? '-';
        $modelo = $nota->modelo ?? '55';
        $valorTotal = number_format((float)($nota->valor_total ?? 0), 2, ',', '.');

        // Enviar
        try {
            $transport = trim((string)Configure::read('Fiscal.email.smtp_transport'));
            if ($transport === '') {
                $transport = trim((string)env('FISCAL_SMTP_TRANSPORT', 'pgm'));
            }

            $fromEmail = trim((string)Configure::read('Fiscal.email.from_email'));
            $fromName = trim((string)Configure::read('Fiscal.email.from_name'));
            if ($fromEmail === '') {
                $fromEmail = trim((string)env('FISCAL_EMAIL_FROM', ''));
            }
            if ($fromName === '') {
                $fromName = (string)env('FISCAL_EMAIL_FROM_NAME', $empresaNome);
            }

            // Alinhar From ao utilizador SMTP (Skymail/PGM: 554 se diferente)
            $smtpUser = trim((string)Configure::read('EmailTransport.' . $transport . '.username'));
            if ($smtpUser !== '' && ($fromEmail === '' || strcasecmp($fromEmail, $smtpUser) !== 0)) {
                $fromEmail = $smtpUser;
            }
            if ($fromEmail === '') {
                return ['ok' => false, 'message' => 'Remetente de e-mail fiscal não configurado (FISCAL_EMAIL_FROM ou transport SMTP).'];
            }

            $subject = 'NF-e ' . $numero . ' (Série ' . $serie . ') - ' . $empresaNome;

            $email = new Email('default');
            $email->setTransport($transport);
            $email->setFrom([$fromEmail => $fromName ?: $fromEmail])
                ->setTo($emailDest)
                ->setSubject($subject)
                ->setEmailFormat('html')
                ->setTemplate('fiscal_nota_autorizada')
                ->setLayout('default')
                ->setViewVars([
                    'empresaNome' => $empresaNome,
                    'numero' => $numero,
                    'serie' => $serie,
                    'modelo' => $modelo,
                    'valorTotal' => $valorTotal,
                    'chaveAcesso' => $nota->chave_acesso ?? '',
                    'dataEmissao' => $nota->data_emissao ? $nota->data_emissao->format('d/m/Y H:i') : '-',
                    'clienteNome' => $nota->cliente ? ($nota->cliente->razaosocial ?: $nota->cliente->nome) : '-',
                    'protocolo' => $nota->protocolo_autorizacao ?? '',
                ]);

            // Anexar DANFE PDF
            $email->attachments([
                $pdfFilename => [
                    'data' => $pdfContent,
                    'mimetype' => 'application/pdf',
                ],
            ]);

            // Anexar XML (se disponível)
            if ($xmlContent) {
                $attach = $email->getAttachments();
                $attach[$xmlFilename] = [
                    'data' => $xmlContent,
                    'mimetype' => 'application/xml',
                ];
                $email->setAttachments($attach);
            }

            $email->send();

            FiscalAudit::write('info', 'email_nota_enviado', [
                'nota_id' => $notaId,
                'idempresa' => $idempresa,
                'destinatario' => $emailDest,
                'numero' => $numero,
            ]);

            return ['ok' => true, 'message' => 'E-mail enviado para ' . $emailDest];
        } catch (\Exception $e) {
            Log::warning('FiscalEmailService envio: ' . $e->getMessage());
            FiscalAudit::write('error', 'email_nota_erro', [
                'nota_id' => $notaId,
                'idempresa' => $idempresa,
                'destinatario' => $emailDest,
                'erro' => $e->getMessage(),
            ]);
            return ['ok' => false, 'message' => 'Erro ao enviar e-mail: ' . $e->getMessage()];
        }
    }
}
