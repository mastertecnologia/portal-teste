<?php
declare(strict_types=1);

namespace App\Controller\Api\Laudos;

use App\Controller\AppController;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;

/**
 * Dados da empresa emissora (tabela laudos_empresas), alinhada ao idempresa da sessão.
 */
class LaudosEmpresasController extends AppController
{
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];

    private const MAX_IMAGE_BYTES = 3145728; // 3 MB

    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('LaudosEmpresas');
        $this->RequestHandler->renderAs($this, 'json');
        $this->viewBuilder()->setClassName('Json');
    }

    /**
     * PUT/PATCH /api/laudos/empresas/:id
     * Só permite editar o registro cujo id coincide com idempresa do utilizador autenticado.
     */
    public function edit($id): void
    {
        $this->request->allowMethod(['PUT', 'PATCH']);

        $empresaId = (int)$id;
        $this->assertEmpresaScope($empresaId);

        try {
            $empresa = $this->LaudosEmpresas->get($empresaId);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            throw new NotFoundException('Empresa emissora não encontrada');
        }

        $data = $this->request->getData();
        // Caminhos de ficheiro só via endpoints dedicados de upload/delete (evita path traversal / hijack).
        unset($data['id'], $data['created'], $data['modified'], $data['logo_path'], $data['carimbo_path']);

        $empresa = $this->LaudosEmpresas->patchEntity($empresa, $data);
        if (!$this->LaudosEmpresas->save($empresa)) {
            $this->set(['success' => false, 'errors' => $empresa->getErrors()]);
            $this->viewBuilder()->setOption('serialize', ['success', 'errors']);
            $this->response = $this->response->withStatus(422);

            return;
        }

        $this->set(['success' => true, 'data' => $empresa]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    /**
     * POST /api/laudos/empresas/:id/logo
     * multipart: file
     */
    public function uploadLogo($id): void
    {
        $this->request->allowMethod('POST');
        $this->handleImageUpload((int)$id, 'logo_path', 'logo');
    }

    /**
     * DELETE /api/laudos/empresas/:id/logo
     */
    public function deleteLogo($id): void
    {
        $this->request->allowMethod('DELETE');
        $this->clearImageField((int)$id, 'logo_path');
    }

    /**
     * POST /api/laudos/empresas/:id/carimbo
     */
    public function uploadCarimbo($id): void
    {
        $this->request->allowMethod('POST');
        $this->handleImageUpload((int)$id, 'carimbo_path', 'carimbo');
    }

    /**
     * DELETE /api/laudos/empresas/:id/carimbo
     */
    public function deleteCarimbo($id): void
    {
        $this->request->allowMethod('DELETE');
        $this->clearImageField((int)$id, 'carimbo_path');
    }

    protected function handleImageUpload(int $empresaId, string $field, string $prefix): void
    {
        $this->assertEmpresaScope($empresaId);

        try {
            $empresa = $this->LaudosEmpresas->get($empresaId);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            throw new NotFoundException('Empresa emissora não encontrada');
        }

        $file = $this->request->getUploadedFile('file');
        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            throw new BadRequestException('Ficheiro inválido ou em falta');
        }

        $mime = $file->getClientMediaType();
        if (!in_array($mime, self::IMAGE_MIMES, true)) {
            throw new BadRequestException('Apenas imagens JPEG, PNG ou WebP são permitidas');
        }

        if ($file->getSize() > self::MAX_IMAGE_BYTES) {
            throw new BadRequestException('Imagem maior que 3 MB');
        }

        $ext = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION) ?: 'jpg');
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            throw new BadRequestException('Extensão de ficheiro não permitida');
        }

        $dir = WWW_ROOT . 'uploads' . DS . 'laudos' . DS . 'empresas' . DS . $empresaId;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = $prefix . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $absolutePath = $dir . DS . $filename;
        $file->moveTo($absolutePath);

        $info = @getimagesize($absolutePath);
        if ($info === false) {
            @unlink($absolutePath);
            throw new BadRequestException('O ficheiro não é uma imagem válida');
        }

        $relative = 'uploads/laudos/empresas/' . $empresaId . '/' . $filename;
        $this->deleteStoredFileIfOwned($empresa->{$field});

        $empresa->{$field} = $relative;
        if (!$this->LaudosEmpresas->save($empresa)) {
            @unlink($absolutePath);
            $this->set(['success' => false, 'errors' => $empresa->getErrors()]);
            $this->viewBuilder()->setOption('serialize', ['success', 'errors']);
            $this->response = $this->response->withStatus(422);

            return;
        }

        $this->set(['success' => true, 'data' => $empresa]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    protected function clearImageField(int $empresaId, string $field): void
    {
        $this->assertEmpresaScope($empresaId);

        try {
            $empresa = $this->LaudosEmpresas->get($empresaId);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            throw new NotFoundException('Empresa emissora não encontrada');
        }

        $this->deleteStoredFileIfOwned($empresa->{$field});
        $empresa->{$field} = null;
        $this->LaudosEmpresas->save($empresa);

        $this->set(['success' => true, 'data' => $empresa]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    protected function deleteStoredFileIfOwned(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }
        if (strpos($relativePath, '..') !== false) {
            return;
        }
        if (strpos($relativePath, 'uploads/laudos/empresas/') !== 0) {
            return;
        }
        $abs = WWW_ROOT . str_replace('/', DS, $relativePath);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }

    protected function assertEmpresaScope(int $empresaId): void
    {
        if ($empresaId !== $this->getEmpresaId()) {
            throw new ForbiddenException('Sem permissão para alterar esta empresa emissora');
        }
    }

    protected function getEmpresaId(): int
    {
        return (int)($this->Auth->user('idempresa') ?? 1);
    }
}
