<?php
declare(strict_types=1);

namespace App\Controller\Api\Laudos;

use App\Controller\AppController;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\BadRequestException;
use Cake\Mailer\Mailer;
use Cake\View\ViewBuilder;

/**
 * Geração de PDF e envio de e-mail para pareceres.
 *
 * DEPENDÊNCIAS:
 *   composer require dompdf/dompdf
 *   (ou alternativamente: composer require friendsofcake/cakepdf)
 *
 * Este controller usa dompdf diretamente para máximo controle.
 */
class LaudosPdfController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('LaudosPareceres');
        $this->loadModel('LaudosHistorico');
    }

    /**
     * GET /api/laudos/pareceres/{id}/pdf
     * Gera PDF e retorna como download.
     */
    public function pdf($id)
    {
        $parecer = $this->LaudosPareceres->getCompleto((int)$id);
        if (!$parecer) throw new NotFoundException();

        $this->checkAccess($parecer);

        $totais = $this->LaudosPareceres->calcularTotais($parecer->id);

        // Renderiza HTML do parecer
        $html = $this->renderHtml($parecer, $totais);

        // Gera PDF
        $pdfContent = $this->htmlToPdf($html);

        // Log
        $this->LaudosHistorico->logEvent(
            $parecer->id,
            $this->Authentication->getIdentity()->id ?? null,
            $this->Authentication->getIdentity()->name ?? null,
            'pdf.generated'
        );

        $this->response = $this->response
            ->withType('application/pdf')
            ->withHeader('Content-Disposition', 'inline; filename="parecer_' . str_replace('/', '_', $parecer->numero) . '.pdf"')
            ->withStringBody($pdfContent);

        return $this->response;
    }

    /**
     * POST /api/laudos/pareceres/{id}/enviar-email
     * Body: { to, cc, subject, message }
     */
    public function enviarEmail($id)
    {
        $this->request->allowMethod('POST');

        $parecer = $this->LaudosPareceres->getCompleto((int)$id);
        if (!$parecer) throw new NotFoundException();
        $this->checkAccess($parecer);

        $to = $this->request->getData('to');
        $cc = $this->request->getData('cc');
        $subject = $this->request->getData('subject') ?: 'Parecer Técnico nº ' . $parecer->numero;
        $message = $this->request->getData('message') ?: $this->defaultEmailBody($parecer);

        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestException('E-mail destinatário inválido');
        }

        // gera PDF para anexar
        $totais = $this->LaudosPareceres->calcularTotais($parecer->id);
        $html = $this->renderHtml($parecer, $totais);
        $pdfContent = $this->htmlToPdf($html);

        // Salva temporariamente
        $tmpFile = tempnam(sys_get_temp_dir(), 'parecer_') . '.pdf';
        file_put_contents($tmpFile, $pdfContent);

        try {
            $mailer = new Mailer('default');
            $mailer
                ->setTo($to)
                ->setSubject($subject)
                ->setEmailFormat('text')
                ->setAttachments([
                    'parecer_' . str_replace('/', '_', $parecer->numero) . '.pdf' => [
                        'file' => $tmpFile,
                        'mimetype' => 'application/pdf',
                    ],
                ]);

            if (!empty($cc)) {
                $mailer->setCc($cc);
            }

            $mailer->deliver($message);

            // Atualiza status para 'enviado' se ainda não estava
            if (in_array($parecer->status, ['concluido', 'aprovado'])) {
                $parecer->status = 'enviado';
                $this->LaudosPareceres->save($parecer);
            }

            $this->LaudosHistorico->logEvent(
                $parecer->id,
                $this->Authentication->getIdentity()->id ?? null,
                $this->Authentication->getIdentity()->name ?? null,
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
    // RENDER HTML
    // =====================================================================

    /**
     * Renderiza o HTML do parecer usando uma view dedicada.
     * Crie templates/Element/Laudos/parecer_pdf.php (veja exemplo no fim).
     */
    private function renderHtml($parecer, array $totais): string
    {
        $viewBuilder = new ViewBuilder();
        $viewBuilder->setLayout(false)
            ->setTemplate('parecer_pdf')
            ->setTemplatePath('Laudos/Pdf');

        $view = $viewBuilder->build([
            'parecer' => $parecer,
            'totais' => $totais,
            'qrUrl' => $this->buildValidationUrl($parecer),
        ]);

        return $view->render();
    }

    /**
     * Converte HTML em PDF usando dompdf.
     */
    private function htmlToPdf(string $html): string
    {
        // Lazy-loading do dompdf para evitar dependência se não usar
        if (!class_exists(\Dompdf\Dompdf::class)) {
            throw new \Exception('Dompdf não instalado. Rode: composer require dompdf/dompdf');
        }

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);  // permite carregar imagens
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // adiciona numeração de página
        $canvas = $dompdf->getCanvas();
        $canvas->page_text(
            520, 815,
            "Página {PAGE_NUM} de {PAGE_COUNT}",
            null, 9, [0.4, 0.4, 0.4]
        );

        return $dompdf->output();
    }

    private function buildValidationUrl($parecer): string
    {
        $baseUrl = $parecer->laudos_empresa->public_validation_url ?? '';
        if (empty($baseUrl)) {
            $baseUrl = \Cake\Routing\Router::url('/validar', true);
        }
        return rtrim($baseUrl, '/') . '/' . $parecer->public_hash;
    }

    private function defaultEmailBody($parecer): string
    {
        $contato = $parecer->requester_attention_to ?: 'cliente';
        $produtos = array_map(fn($p) => $p->nome, $parecer->laudos_produtos ?? []);
        $produtosStr = implode(', ', array_filter($produtos));

        return "Prezado(a) {$contato},

Segue em anexo o parecer técnico nº {$parecer->numero} referente à avaliação técnica do(s) equipamento(s) {$produtosStr}.

A autenticidade deste parecer pode ser verificada pelo código: {$parecer->public_hash}

Permanecemos à disposição para esclarecimentos.

Atenciosamente,
" . ($this->Authentication->getIdentity()->name ?? 'Equipe Técnica') . "
" . $parecer->laudos_empresa->razao_social;
    }

    private function checkAccess($parecer): void
    {
        $user = $this->Authentication->getIdentity();
        $empresaId = $user->empresa_id ?? 1;
        if ($parecer->empresa_id !== $empresaId) {
            throw new \Cake\Http\Exception\ForbiddenException();
        }
    }
}
