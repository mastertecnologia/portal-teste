<?php
declare(strict_types=1);

namespace App\Controller\Api\Laudos;

use App\Controller\AppController;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;

class LaudosUploadsController extends AppController
{
    private const ALLOWED_IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    private const ALLOWED_DOC_MIMES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/jpeg', 'image/png',
    ];

    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('LaudosProdutoImagens');
        $this->loadModel('LaudosAnexos');
        $this->loadModel('LaudosProdutos');
        $this->loadModel('LaudosPareceres');
        $this->loadModel('LaudosHistorico');
        $this->viewBuilder()->setClassName('Json');
    }

    /**
     * POST /api/laudos/produto-imagens
     * Form-data: produto_id, file
     */
    public function uploadImagem()
    {
        $this->request->allowMethod('POST');

        $produtoId = (int)$this->request->getData('produto_id');
        $file = $this->request->getUploadedFile('file');

        if (!$produtoId || !$file || $file->getError() !== UPLOAD_ERR_OK) {
            throw new BadRequestException('Arquivo ou produto inválido');
        }

        $produto = $this->LaudosProdutos->get($produtoId, ['contain' => ['LaudosPareceres']]);

        $mime = $file->getClientMediaType();
        if (!in_array($mime, self::ALLOWED_IMAGE_MIMES, true)) {
            throw new BadRequestException('Tipo de arquivo não suportado: ' . $mime);
        }

        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new BadRequestException('Arquivo maior que 10MB');
        }

        $parecerId = $produto->laudos_parecer->id;
        $dir = WWW_ROOT . 'uploads' . DS . 'laudos' . DS . $parecerId . DS . $produtoId;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ext = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION) ?: 'jpg';
        $filename = uniqid('img_') . '.' . strtolower($ext);
        $relativePath = 'uploads/laudos/' . $parecerId . '/' . $produtoId . '/' . $filename;
        $absolutePath = $dir . DS . $filename;

        $file->moveTo($absolutePath);

        $imgInfo = @getimagesize($absolutePath);

        $imagem = $this->LaudosProdutoImagens->newEntity([
            'produto_id' => $produtoId,
            'nome_original' => $file->getClientFilename(),
            'file_path' => $relativePath,
            'mime_type' => $mime,
            'file_size' => $file->getSize(),
            'width' => $imgInfo[0] ?? null,
            'height' => $imgInfo[1] ?? null,
        ]);

        if (!$this->LaudosProdutoImagens->save($imagem)) {
            unlink($absolutePath);
            throw new BadRequestException(json_encode($imagem->getErrors()));
        }

        $this->LaudosHistorico->logEvent(
            $parecerId,
            (int)($this->Auth->user('id') ?? 0) ?: null,
            $this->Auth->user('name'),
            'imagem.added',
            ['imagem_id' => $imagem->id, 'produto_id' => $produtoId]
        );

        $this->set(['success' => true, 'data' => $imagem]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    /**
     * DELETE /api/laudos/produto-imagens/:id
     */
    public function deleteImagem($id)
    {
        $this->request->allowMethod('DELETE');

        $imagem = $this->LaudosProdutoImagens->get((int)$id, ['contain' => ['LaudosProdutos']]);
        $absolutePath = WWW_ROOT . str_replace('/', DS, $imagem->file_path);

        if (file_exists($absolutePath)) {
            unlink($absolutePath);
        }

        $this->LaudosProdutoImagens->delete($imagem);

        $this->set(['success' => true]);
        $this->viewBuilder()->setOption('serialize', ['success']);
    }

    /**
     * POST /api/laudos/anexos
     * Form-data: parecer_id, file, descricao
     */
    public function uploadAnexo()
    {
        $this->request->allowMethod('POST');

        $parecerId = (int)$this->request->getData('parecer_id');
        $file = $this->request->getUploadedFile('file');

        if (!$parecerId || !$file || $file->getError() !== UPLOAD_ERR_OK) {
            throw new BadRequestException('Arquivo ou parecer inválido');
        }

        $mime = $file->getClientMediaType();
        if (!in_array($mime, self::ALLOWED_DOC_MIMES, true)) {
            throw new BadRequestException('Tipo de arquivo não suportado');
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new BadRequestException('Arquivo maior que 5MB');
        }

        $dir = WWW_ROOT . 'uploads' . DS . 'laudos' . DS . $parecerId . DS . 'anexos';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $file->getClientFilename());
        $filename = uniqid() . '_' . $safeName;
        $relativePath = 'uploads/laudos/' . $parecerId . '/anexos/' . $filename;

        $file->moveTo($dir . DS . $filename);

        $userId = (int)($this->Auth->user('id') ?? 0) ?: null;

        $anexo = $this->LaudosAnexos->newEntity([
            'parecer_id' => $parecerId,
            'nome_original' => $file->getClientFilename(),
            'file_path' => $relativePath,
            'mime_type' => $mime,
            'file_size' => $file->getSize(),
            'descricao' => $this->request->getData('descricao'),
            'created_by' => $userId,
        ]);

        $this->LaudosAnexos->save($anexo);

        $this->LaudosHistorico->logEvent(
            $parecerId,
            $userId,
            $this->Auth->user('name'),
            'attachment.added',
            ['nome' => $file->getClientFilename()]
        );

        $this->set(['success' => true, 'data' => $anexo]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    /**
     * GET /api/laudos/anexos/:id/download
     */
    public function downloadAnexo($id)
    {
        $anexo = $this->LaudosAnexos->get((int)$id);
        $path = WWW_ROOT . str_replace('/', DS, $anexo->file_path);
        if (!file_exists($path)) {
            throw new NotFoundException('Arquivo não encontrado');
        }
        return $this->response
            ->withFile($path, ['name' => $anexo->nome_original, 'download' => true]);
    }
}
