<?php
declare(strict_types=1);

namespace App\Controller\Api\Laudos;

use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\BadRequestException;
use Cake\Mailer\Email;
use Cake\View\View;

/**
 * Geração de PDF e envio de e-mail para pareceres.
 * Usa mPDF (mpdf/mpdf ^8.2) já presente no projeto.
 */
class LaudosPdfController extends LaudosApiController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('LaudosPareceres');
        $this->loadModel('LaudosHistorico');
    }

    /**
     * GET /api/laudos/pareceres/:id/pdf
     */
    public function pdf($id)
    {
        $parecer = $this->LaudosPareceres->getCompleto((int)$id);
        if (!$parecer) {
            throw new NotFoundException('Parecer não encontrado');
        }

        $this->checkAccess($parecer);

        $totais = $this->LaudosPareceres->calcularTotais($parecer->id);
        $html = $this->renderHtml($parecer, $totais);
        $pdfContent = $this->htmlToPdf($html, $parecer->numero ?? 'parecer');

        $this->LaudosHistorico->logEvent(
            $parecer->id,
            (int)($this->Auth->user('id') ?? 0) ?: null,
            $this->Auth->user('name'),
            'pdf.generated'
        );

        $filename = 'parecer_' . str_replace('/', '_', $parecer->numero ?? 'sem_numero') . '.pdf';

        $this->response = $this->response
            ->withType('application/pdf')
            ->withHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->withStringBody($pdfContent);

        return $this->response;
    }

    /**
     * POST /api/laudos/pareceres/:id/enviar-email
     * Body: { to, cc, subject, message }
     */
    public function enviarEmail($id)
    {
        $this->request->allowMethod('POST');

        $parecer = $this->LaudosPareceres->getCompleto((int)$id);
        if (!$parecer) {
            throw new NotFoundException('Parecer não encontrado');
        }
        $this->checkAccess($parecer);

        $to = $this->request->getData('to');
        $cc = $this->request->getData('cc');
        $subject = $this->request->getData('subject') ?: 'Parecer Técnico nº ' . $parecer->numero;
        $message = $this->request->getData('message') ?: $this->defaultEmailBody($parecer);

        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestException('E-mail destinatário inválido');
        }

        $totais = $this->LaudosPareceres->calcularTotais($parecer->id);
        $html = $this->renderHtml($parecer, $totais);
        $pdfContent = $this->htmlToPdf($html, $parecer->numero ?? 'parecer');

        $tmpFile = tempnam(sys_get_temp_dir(), 'parecer_') . '.pdf';
        file_put_contents($tmpFile, $pdfContent);

        try {
            $filename = 'parecer_' . str_replace('/', '_', $parecer->numero ?? 'sem_numero') . '.pdf';

            $email = new Email('default');
            $email->setTo($to)
                ->setSubject($subject)
                ->setEmailFormat('text')
                ->setAttachments([
                    $filename => [
                        'file' => $tmpFile,
                        'mimetype' => 'application/pdf',
                    ],
                ]);

            if (!empty($cc)) {
                $email->setCc($cc);
            }

            $email->send($message);

            if (in_array($parecer->status, ['concluido', 'aprovado'], true)) {
                $parecer->status = 'enviado';
                $this->LaudosPareceres->save($parecer);
            }

            $this->LaudosHistorico->logEvent(
                $parecer->id,
                (int)($this->Auth->user('id') ?? 0) ?: null,
                $this->Auth->user('name'),
                'email.sent',
                ['to' => $to, 'cc' => $cc]
            );

            $this->set(['success' => true, 'message' => 'E-mail enviado para ' . $to]);
        } catch (\Exception $e) {
            $this->set(['success' => false, 'message' => 'Falha ao enviar: ' . $e->getMessage()]);
            $this->response = $this->response->withStatus(500);
        } finally {
            @unlink($tmpFile);
        }

        $this->viewBuilder()->setClassName('Json');
        $this->viewBuilder()->setOption('serialize', ['success', 'message']);
    }

    // =====================================================================

    private function renderHtml($parecer, array $totais): string
    {
        $view = new View(
            $this->request,
            $this->response,
            null,
            [
                'template' => 'parecer_pdf',
                'templatePath' => 'Laudos/Pdf',
                'layout' => false,
            ]
        );
        $view->set('parecer', $parecer);
        $view->set('totais', $totais);
        $view->set('qrUrl', $this->buildValidationUrl($parecer));
        return $view->render();
    }

    /**
     * Converte HTML em PDF usando mPDF (já disponível no projeto: mpdf/mpdf ^8.2).
     */
    private function htmlToPdf(string $html, string $numero): string
    {
        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4',
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
            'default_font' => 'dejavusans',
        ]);
        $mpdf->SetTitle('Parecer Técnico ' . $numero);
        $mpdf->WriteHTML($html);
        return $mpdf->Output('', 'S');
    }

    private function buildValidationUrl($parecer): string
    {
        $baseUrl = !empty($parecer->laudos_empresa->public_validation_url)
            ? $parecer->laudos_empresa->public_validation_url
            : \Cake\Routing\Router::url('/validar', true);
        return rtrim($baseUrl, '/') . '/' . $parecer->public_hash;
    }

    private function defaultEmailBody($parecer): string
    {
        $contato = $parecer->requester_attention_to ?: 'cliente';
        $produtos = array_map(function ($p) { return $p->nome; }, $parecer->laudos_produtos ?? []);
        $produtosStr = implode(', ', array_filter($produtos));

        $emitente = $parecer->laudos_empresa->razao_social ?? '';
        $tecnico = $this->Auth->user('name') ?? 'Equipe Técnica';

        return "Prezado(a) {$contato},\n\n"
            . "Segue em anexo o parecer técnico nº {$parecer->numero} referente à avaliação técnica do(s) equipamento(s) {$produtosStr}.\n\n"
            . "A autenticidade deste parecer pode ser verificada pelo código: {$parecer->public_hash}\n\n"
            . "Permanecemos à disposição para esclarecimentos.\n\n"
            . "Atenciosamente,\n{$tecnico}\n{$emitente}";
    }

    private function checkAccess($parecer): void
    {
        $empresaId = (int)($this->Auth->user('idempresa') ?? 1);
        if ((int)$parecer->empresa_id !== $empresaId) {
            throw new \Cake\Http\Exception\ForbiddenException('Acesso negado a este parecer');
        }
    }
}
