<?php
// ===========================================================================
// CONTROLLERS COMPLEMENTARES
// Cada classe deve estar em: src/Controller/Api/Laudos/{Nome}Controller.php
// ===========================================================================

declare(strict_types=1);

// ===========================================================================
// FILE: src/Controller/Api/Laudos/LaudosProdutosController.php
// ===========================================================================
namespace App\Controller\Api\Laudos;

use App\Controller\AppController;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\ForbiddenException;

class LaudosProdutosController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('LaudosProdutos');
        $this->loadModel('LaudosPareceres');
        $this->loadModel('LaudosHistorico');
        $this->viewBuilder()->setClassName('Json');
    }

    /**
     * POST /api/laudos/produtos
     * Body: { parecer_id, ...campos }
     */
    public function add()
    {
        $this->request->allowMethod('POST');
        $data = $this->request->getData();

        $parecer = $this->LaudosPareceres->get($data['parecer_id']);
        $this->checkAccess($parecer);

        // calcula próxima ordem
        $maxOrdem = $this->LaudosProdutos->find()
            ->where(['parecer_id' => $data['parecer_id']])
            ->select(['max_ordem' => 'MAX(ordem)'])
            ->first();
        $data['ordem'] = ($maxOrdem->max_ordem ?? 0) + 1;

        $produto = $this->LaudosProdutos->newEntity($data);

        if (!$this->LaudosProdutos->save($produto)) {
            $this->set(['success' => false, 'errors' => $produto->getErrors()]);
            $this->viewBuilder()->setOption('serialize', ['success', 'errors']);
            $this->response = $this->response->withStatus(422);
            return;
        }

        $this->LaudosHistorico->logEvent(
            $parecer->id, $this->getUserId(), $this->getUserName(),
            'produto.added', ['produto_id' => $produto->id, 'nome' => $produto->nome]
        );

        $this->set(['success' => true, 'data' => $produto]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    /**
     * PUT /api/laudos/produtos/{id}
     */
    public function edit($id)
    {
        $this->request->allowMethod(['PUT', 'PATCH']);

        $produto = $this->LaudosProdutos->get((int)$id, ['contain' => ['LaudosPareceres']]);
        $this->checkAccess($produto->laudos_parecer);

        $produto = $this->LaudosProdutos->patchEntity($produto, $this->request->getData());

        if (!$this->LaudosProdutos->save($produto)) {
            $this->set(['success' => false, 'errors' => $produto->getErrors()]);
            $this->viewBuilder()->setOption('serialize', ['success', 'errors']);
            $this->response = $this->response->withStatus(422);
            return;
        }

        $this->set(['success' => true, 'data' => $produto]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    /**
     * DELETE /api/laudos/produtos/{id}
     */
    public function delete($id)
    {
        $this->request->allowMethod('DELETE');

        $produto = $this->LaudosProdutos->get((int)$id, ['contain' => ['LaudosPareceres']]);
        $this->checkAccess($produto->laudos_parecer);

        $this->LaudosProdutos->delete($produto);

        $this->LaudosHistorico->logEvent(
            $produto->parecer_id, $this->getUserId(), $this->getUserName(),
            'produto.removed', ['nome' => $produto->nome]
        );

        $this->set(['success' => true]);
        $this->viewBuilder()->setOption('serialize', ['success']);
    }

    protected function checkAccess($parecer): void
    {
        $user = $this->Authentication->getIdentity();
        $empresaId = $user->empresa_id ?? 1;
        if ($parecer->empresa_id !== $empresaId) {
            throw new ForbiddenException();
        }
    }

    protected function getUserId(): ?int
    {
        return $this->Authentication->getIdentity()->id ?? null;
    }

    protected function getUserName(): ?string
    {
        return $this->Authentication->getIdentity()->name ?? null;
    }
}

// ===========================================================================
// FILE: src/Controller/Api/Laudos/LaudosUploadsController.php
// ===========================================================================
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
     *
     * NOTA: A compressão da imagem deve ocorrer no FRONTEND antes do upload.
     * Aqui apenas validamos e salvamos. Se quiser comprimir no backend também,
     * use Intervention/Image (composer require intervention/image).
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

        // valida mime
        $mime = $file->getClientMediaType();
        if (!in_array($mime, self::ALLOWED_IMAGE_MIMES)) {
            throw new BadRequestException('Tipo de arquivo não suportado: ' . $mime);
        }

        // valida tamanho (max 10MB pré-compressão)
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new BadRequestException('Arquivo maior que 10MB');
        }

        // monta caminho
        $parecerId = $produto->laudos_parecer->id;
        $dir = WWW_ROOT . 'uploads' . DS . 'laudos' . DS . $parecerId . DS . $produtoId;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ext = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION) ?: 'jpg';
        $filename = uniqid('img_') . '.' . $ext;
        $relativePath = 'uploads/laudos/' . $parecerId . '/' . $produtoId . '/' . $filename;
        $absolutePath = $dir . DS . $filename;

        $file->moveTo($absolutePath);

        // detecta dimensões
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
            $this->Authentication->getIdentity()->id ?? null,
            $this->Authentication->getIdentity()->name ?? null,
            'imagem.added',
            ['imagem_id' => $imagem->id, 'produto_id' => $produtoId]
        );

        $this->set(['success' => true, 'data' => $imagem]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    /**
     * DELETE /api/laudos/produto-imagens/{id}
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
            throw new BadRequestException();
        }

        $mime = $file->getClientMediaType();
        if (!in_array($mime, self::ALLOWED_DOC_MIMES)) {
            throw new BadRequestException('Tipo de arquivo não suportado');
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new BadRequestException('Arquivo maior que 5MB');
        }

        $dir = WWW_ROOT . 'uploads' . DS . 'laudos' . DS . $parecerId . DS . 'anexos';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $safeName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $file->getClientFilename());
        $filename = uniqid() . '_' . $safeName;
        $relativePath = 'uploads/laudos/' . $parecerId . '/anexos/' . $filename;

        $file->moveTo($dir . DS . $filename);

        $userId = $this->Authentication->getIdentity()->id ?? null;

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
            $parecerId, $userId,
            $this->Authentication->getIdentity()->name ?? null,
            'attachment.added', ['nome' => $file->getClientFilename()]
        );

        $this->set(['success' => true, 'data' => $anexo]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    /**
     * GET /api/laudos/anexos/{id}/download
     */
    public function downloadAnexo($id)
    {
        $anexo = $this->LaudosAnexos->get((int)$id);
        $path = WWW_ROOT . str_replace('/', DS, $anexo->file_path);
        if (!file_exists($path)) {
            throw new NotFoundException();
        }
        return $this->response
            ->withFile($path, ['name' => $anexo->nome_original, 'download' => true]);
    }
}

// ===========================================================================
// FILE: src/Controller/Api/Laudos/LaudosCatalogoController.php
// ===========================================================================
namespace App\Controller\Api\Laudos;

use App\Controller\AppController;

class LaudosCatalogoController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('LaudosCatalogoPecas');
        $this->loadModel('LaudosCatalogoServicos');
        $this->loadModel('LaudosTemplates');
        $this->viewBuilder()->setClassName('Json');
    }

    /**
     * GET /api/laudos/catalogo/pecas?q=&limit=
     */
    public function pecas()
    {
        $empresaId = $this->Authentication->getIdentity()->empresa_id ?? 1;
        $q = $this->request->getQuery('q');
        $limit = (int)($this->request->getQuery('limit') ?? 50);

        $items = $this->LaudosCatalogoPecas->buscar($empresaId, $q, $limit);

        $this->set(['success' => true, 'data' => $items]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    /**
     * POST /api/laudos/catalogo/pecas
     */
    public function addPeca()
    {
        $this->request->allowMethod('POST');
        $data = $this->request->getData();
        $data['empresa_id'] = $this->Authentication->getIdentity()->empresa_id ?? 1;

        $peca = $this->LaudosCatalogoPecas->newEntity($data);
        if (!$this->LaudosCatalogoPecas->save($peca)) {
            $this->set(['success' => false, 'errors' => $peca->getErrors()]);
            $this->response = $this->response->withStatus(422);
        } else {
            $this->set(['success' => true, 'data' => $peca]);
        }
        $this->viewBuilder()->setOption('serialize', ['success', 'data', 'errors']);
    }

    /**
     * GET /api/laudos/catalogo/servicos?q=
     */
    public function servicos()
    {
        $empresaId = $this->Authentication->getIdentity()->empresa_id ?? 1;
        $items = $this->LaudosCatalogoServicos->buscar($empresaId, $this->request->getQuery('q'));

        $this->set(['success' => true, 'data' => $items]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }

    /**
     * GET /api/laudos/templates/{tipo}
     * tipo = diagnostico | conclusao | objetivo | documentacao
     */
    public function templates($tipo)
    {
        $empresaId = $this->Authentication->getIdentity()->empresa_id ?? 1;
        $items = $this->LaudosTemplates->porTipo($empresaId, $tipo);

        $this->set(['success' => true, 'data' => $items]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }
}

// ===========================================================================
// FILE: src/Controller/Api/UtilController.php
// ===========================================================================
namespace App\Controller\Api;

use App\Controller\AppController;
use Cake\Cache\Cache;
use Cake\Http\Client;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;

/**
 * Endpoints utilitários: proxy para BrasilAPI (CNPJ) e ViaCEP.
 * Por que via backend?
 *  - Cache (evita estourar rate limits das APIs públicas)
 *  - Não expõe IP do cliente final
 *  - Pode logar/auditar consultas
 */
class UtilController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->viewBuilder()->setClassName('Json');
    }

    /**
     * GET /api/util/cnpj/{cnpj}
     */
    public function cnpj($cnpj)
    {
        $clean = preg_replace('/\D/', '', $cnpj);
        if (strlen($clean) !== 14) {
            throw new BadRequestException('CNPJ inválido (precisa 14 dígitos)');
        }

        // Cache de 24h
        $cacheKey = 'cnpj_' . $clean;
        $cached = Cache::read($cacheKey, 'default');
        if ($cached) {
            $this->set(['success' => true, 'data' => $cached, 'cached' => true]);
            $this->viewBuilder()->setOption('serialize', ['success', 'data', 'cached']);
            return;
        }

        $http = new Client();
        $response = $http->get("https://brasilapi.com.br/api/cnpj/v1/{$clean}", [], ['timeout' => 8]);

        if (!$response->isOk()) {
            throw new NotFoundException('CNPJ não encontrado na base');
        }

        $raw = $response->getJson();

        // Normaliza para o formato que o frontend espera
        $data = [
            'razao_social' => $raw['razao_social'] ?? $raw['nome_fantasia'] ?? '',
            'nome_fantasia' => $raw['nome_fantasia'] ?? '',
            'cnpj' => $cnpj,
            'telefone' => !empty($raw['ddd_telefone_1'])
                ? '(' . substr($raw['ddd_telefone_1'], 0, 2) . ') ' . substr($raw['ddd_telefone_1'], 2)
                : '',
            'email' => $raw['email'] ?? '',
            'cep' => !empty($raw['cep']) ? preg_replace('/(\d{5})(\d{3})/', '$1-$2', $raw['cep']) : '',
            'endereco' => trim(implode(', ', array_filter([
                $raw['logradouro'] ?? '',
                $raw['numero'] ?? '',
                $raw['bairro'] ?? '',
                $raw['municipio'] ?? '',
                $raw['uf'] ?? '',
            ]))),
            'situacao' => $raw['descricao_situacao_cadastral'] ?? '',
        ];

        Cache::write($cacheKey, $data, 'default');

        $this->set(['success' => true, 'data' => $data, 'cached' => false]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data', 'cached']);
    }

    /**
     * GET /api/util/cep/{cep}
     */
    public function cep($cep)
    {
        $clean = preg_replace('/\D/', '', $cep);
        if (strlen($clean) !== 8) {
            throw new BadRequestException('CEP inválido');
        }

        $cacheKey = 'cep_' . $clean;
        $cached = Cache::read($cacheKey, 'default');
        if ($cached) {
            $this->set(['success' => true, 'data' => $cached, 'cached' => true]);
            $this->viewBuilder()->setOption('serialize', ['success', 'data', 'cached']);
            return;
        }

        $http = new Client();
        $response = $http->get("https://viacep.com.br/ws/{$clean}/json/", [], ['timeout' => 5]);

        if (!$response->isOk()) {
            throw new NotFoundException('CEP não encontrado');
        }

        $raw = $response->getJson();
        if (!empty($raw['erro'])) {
            throw new NotFoundException('CEP não encontrado');
        }

        $data = [
            'cep' => $cep,
            'logradouro' => $raw['logradouro'] ?? '',
            'bairro' => $raw['bairro'] ?? '',
            'cidade' => $raw['localidade'] ?? '',
            'uf' => $raw['uf'] ?? '',
            'endereco_completo' => trim(implode(', ', array_filter([
                $raw['logradouro'] ?? '',
                $raw['bairro'] ?? '',
                $raw['localidade'] ?? '',
                $raw['uf'] ?? '',
            ]))),
        ];

        Cache::write($cacheKey, $data, 'default');

        $this->set(['success' => true, 'data' => $data]);
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }
}

// ===========================================================================
// FILE: src/Controller/Api/Laudos/ValidacaoController.php
// ===========================================================================
namespace App\Controller\Api\Laudos;

use App\Controller\AppController;
use Cake\Http\Exception\NotFoundException;

/**
 * Controller PÚBLICO (sem autenticação).
 * Adicione ao Authentication->allowUnauthenticated em config/routes.php
 *
 * Permite que qualquer pessoa (cliente, seguradora) valide um parecer
 * via QR Code ou link público.
 */
class ValidacaoController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('LaudosPareceres');
        $this->Authentication->allowUnauthenticated(['publica']);
    }

    /**
     * GET /validar/{hash}
     *
     * Retorna apenas dados públicos do parecer — não expõe valores,
     * diagnóstico técnico ou anexos.
     */
    public function publica($hash)
    {
        $parecer = $this->LaudosPareceres->find()
            ->where(['public_hash' => $hash, 'deleted IS' => null])
            ->contain(['LaudosEmpresas'])
            ->first();

        if (!$parecer) {
            throw new NotFoundException('Parecer não localizado');
        }

        // Apenas dados públicos!
        $publicData = [
            'numero' => $parecer->numero,
            'data_emissao' => $parecer->data_emissao,
            'status' => $parecer->status,
            'status_label' => $parecer->status_label,
            'emitido_por' => $parecer->laudos_empresa->razao_social,
            'cnpj_emitente' => $parecer->laudos_empresa->cnpj,
            'cliente_nome' => $parecer->requester_company_name,
            'cliente_cnpj' => $parecer->requester_cnpj,
            'tecnico' => $parecer->tecnico_nome,
            'cidade' => $parecer->cidade,
            'autenticado' => true,
        ];

        $this->set(['success' => true, 'data' => $publicData]);
        $this->viewBuilder()->setClassName('Json');
        $this->viewBuilder()->setOption('serialize', ['success', 'data']);
    }
}
