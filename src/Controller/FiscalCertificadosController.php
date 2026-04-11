<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Utility\Fiscal\FiscalCertificadoSecret;
use App\Utility\Fiscal\FiscalSigner;
use App\Utility\Fiscal\FiscalStorage;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Log\Log;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Gestão de certificados digitais A1/A3.
 */
class FiscalCertificadosController extends AppController {

    use FiscalRegimeViewTrait;

    public function initialize() {
        parent::initialize();
        $this->loadModel('FiscalCertificados');
        $this->loadModel('FiscalEmpresasConfig');
        Configure::load('fiscal');
        FiscalStorage::ensureDirectories();
    }

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $this->set('title', 'Certificados Digitais');
        $this->set('pgmAdvancedModuleStylesheet', true);
    }

    public function isAuthorized($user) {
        if ((int)($user['role'] ?? 1) === 1) {
            return false;
        }
        return parent::isAuthorized($user);
    }

    /**
     * Lista certificados da empresa.
     */
    public function index() {
        $idempresa = $this->Auth->user('idempresa');
        $certificados = $this->FiscalCertificados->find()
            ->where(['idempresa' => $idempresa])
            ->order(['created' => 'DESC'])
            ->toArray();

        $this->set(compact('certificados'));
    }

    /**
     * Upload de novo certificado A1 (.pfx/.p12).
     */
    public function add() {
        $idempresa = $this->Auth->user('idempresa');
        $certificado = $this->FiscalCertificados->newEntity();

        if ($this->request->is('post')) {
            if ($idempresa === null || $idempresa === '') {
                $this->Flash->error('Sessão sem empresa vinculada. Selecione a empresa e tente novamente.');
                $this->set(compact('certificado'));
                return;
            }

            $data = $this->request->getData();
            $arquivo = $data['arquivo_upload'] ?? null;

            $resolved = $this->_resolveUploadedCertificado($arquivo);
            if ($resolved['err'] !== null) {
                $this->Flash->error($resolved['err']);
                $this->set(compact('certificado'));
                return;
            }

            $clientFilename = $resolved['name'];
            $pfxContent = $resolved['binary'];

            $ext = strtolower(pathinfo($clientFilename, PATHINFO_EXTENSION));
            if (!in_array($ext, ['pfx', 'p12'], true)) {
                $this->Flash->error('Formato de arquivo inválido. Envie .pfx ou .p12.');
                $this->set(compact('certificado'));
                return;
            }

            $senha = $data['senha'] ?? '';

            // Validar certificado
            try {
                $signer = new FiscalSigner($pfxContent, $senha);
                $info = $signer->getCertificateInfo();

                if (!$signer->isValid()) {
                    $this->Flash->error('O certificado está vencido (validade até: ' . ($info['valid_to'] ?? 'desconhecida') . ').');
                    $this->set(compact('certificado'));
                    return;
                }
            } catch (\Throwable $e) {
                $this->Flash->error('Erro ao ler o certificado: ' . $e->getMessage());
                $this->set(compact('certificado'));
                return;
            }

            // Desativar certificados anteriores
            $this->FiscalCertificados->updateAll(
                ['ativo' => false],
                ['idempresa' => $idempresa]
            );

            try {
                $senhaStored = FiscalCertificadoSecret::encryptForStorage($senha, $idempresa);
                $serial = isset($info['serial_number']) ? (string)$info['serial_number'] : '';
                if (strlen($serial) > 255) {
                    $serial = substr($serial, 0, 255);
                }
                $cn = isset($info['subject']) ? (string)$info['subject'] : '';
                if (strlen($cn) > 1000) {
                    $cn = substr($cn, 0, 1000);
                }
                $cnpjCert = isset($info['cnpj']) ? (string)$info['cnpj'] : '';
                if (strlen($cnpjCert) > 18) {
                    $cnpjCert = substr($cnpjCert, 0, 18);
                }

                $certEntity = $this->FiscalCertificados->newEntity([
                    'idempresa' => $idempresa,
                    'nome' => $data['nome'] ?? ($clientFilename !== '' ? $clientFilename : 'Certificado A1'),
                    'tipo' => 'A1',
                    'arquivo_pfx' => $pfxContent,
                    'senha_hash' => $senhaStored,
                    'serial_number' => $serial !== '' ? $serial : null,
                    'cn_subject' => $cn !== '' ? $cn : null,
                    'cnpj_certificado' => $cnpjCert !== '' ? $cnpjCert : null,
                    'validade_inicio' => $info['valid_from'] ?? null,
                    'validade_fim' => $info['valid_to'] ?? null,
                    'ativo' => true,
                ]);

                if ($this->FiscalCertificados->save($certEntity)) {
                    $config = $this->FiscalEmpresasConfig->getOrCreate($idempresa);
                    $config->certificado_id = $certEntity->id;
                    if (!$this->FiscalEmpresasConfig->save($config)) {
                        Log::warning(sprintf(
                            'FiscalCertificados: certificado %s gravado mas falha ao vincular fiscal_empresas_config (empresa %s): %s',
                            (string)$certEntity->id,
                            (string)$idempresa,
                            json_encode($config->getErrors(), JSON_UNESCAPED_UNICODE)
                        ));
                        $this->Flash->warning('Certificado gravado, mas não foi possível atualizar a configuração fiscal automaticamente. Ajuste em Configurações fiscais se necessário.');
                    }

                    $this->Flash->success('Certificado digital instalado com sucesso. Válido até: ' . ($info['valid_to'] ?? ''));
                    return $this->redirect(['action' => 'index']);
                }
                Log::warning('FiscalCertificados::add validação ao salvar: ' . json_encode($certEntity->getErrors(), JSON_UNESCAPED_UNICODE));
                $this->Flash->error('Erro ao salvar certificado. Confira o nome e tente novamente; detalhes foram registrados no log.');
            } catch (\Throwable $e) {
                Log::error('FiscalCertificados::add falha ao persistir: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                $this->Flash->error('Não foi possível gravar o certificado. Verifique os logs do servidor ou contacte o suporte.');
            }
        }

        $this->set(compact('certificado'));
    }

    /**
     * Visualizar detalhes do certificado.
     */
    public function view($id = null) {
        $idempresa = $this->Auth->user('idempresa');
        $certificado = $this->FiscalCertificados->get($id);

        if ((int)$certificado->idempresa !== (int)$idempresa) {
            $this->Flash->error('Certificado não encontrado.');
            return $this->redirect(['action' => 'index']);
        }

        $diasRestantes = 0;
        $valido = false;
        if ($certificado->validade_fim) {
            $valido = $certificado->validade_fim > new \DateTime();
            if ($valido) {
                $diff = (new \DateTime())->diff($certificado->validade_fim);
                $diasRestantes = $diff->days;
            }
        }

        $this->set(compact('certificado', 'diasRestantes', 'valido'));
    }

    /**
     * Ativar/desativar certificado.
     */
    public function toggleAtivo($id = null) {
        $this->request->allowMethod(['post']);
        $idempresa = $this->Auth->user('idempresa');
        $certificado = $this->FiscalCertificados->get($id);

        if ((int)$certificado->idempresa !== (int)$idempresa) {
            $this->Flash->error('Certificado não encontrado.');
            return $this->redirect(['action' => 'index']);
        }

        if (!$certificado->ativo) {
            // Desativar outros
            $this->FiscalCertificados->updateAll(
                ['ativo' => false],
                ['idempresa' => $idempresa]
            );
        }

        $certificado->ativo = !$certificado->ativo;
        $this->FiscalCertificados->save($certificado);

        $this->Flash->success('Status do certificado atualizado.');
        return $this->redirect(['action' => 'index']);
    }

    /**
     * Excluir certificado.
     */
    public function delete($id = null) {
        $this->request->allowMethod(['post', 'delete']);
        $idempresa = $this->Auth->user('idempresa');
        $certificado = $this->FiscalCertificados->get($id);

        if ((int)$certificado->idempresa !== (int)$idempresa) {
            $this->Flash->error('Certificado não encontrado.');
            return $this->redirect(['action' => 'index']);
        }

        if ($this->FiscalCertificados->delete($certificado)) {
            $this->Flash->success('Certificado excluído.');
        }
        return $this->redirect(['action' => 'index']);
    }

    /**
     * CakePHP 3 entrega o campo file como array estilo $_FILES; ambientes PSR-7 usam UploadedFileInterface.
     *
     * @param mixed $arquivo
     * @return array{err:?string,name:string,binary:string}
     */
    protected function _resolveUploadedCertificado($arquivo): array {
        $missing = ['err' => 'Selecione um arquivo de certificado (.pfx ou .p12).', 'name' => '', 'binary' => ''];

        if ($arquivo instanceof UploadedFileInterface) {
            $code = $arquivo->getError();
            $name = (string)($arquivo->getClientFilename() ?: '');
            if ($code !== UPLOAD_ERR_OK) {
                return ['err' => $this->_phpUploadErrorMessage($code), 'name' => $name, 'binary' => ''];
            }
            $stream = $arquivo->getStream();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }
            $binary = (string)$stream->getContents();
            if ($binary === '') {
                return ['err' => 'O arquivo enviado está vazio ou não pôde ser lido. Tente outro .pfx/.p12.', 'name' => $name, 'binary' => ''];
            }

            return ['err' => null, 'name' => $name, 'binary' => $binary];
        }

        if (is_array($arquivo) && !empty($arquivo['tmp_name'])) {
            $code = (int)($arquivo['error'] ?? UPLOAD_ERR_NO_FILE);
            $name = (string)($arquivo['name'] ?? '');
            if ($code !== UPLOAD_ERR_OK) {
                return ['err' => $this->_phpUploadErrorMessage($code), 'name' => $name, 'binary' => ''];
            }
            $binary = @file_get_contents($arquivo['tmp_name']);
            if ($binary === false) {
                $binary = '';
            }
            if ($binary === '') {
                return ['err' => 'O arquivo enviado está vazio ou não pôde ser lido. Tente outro .pfx/.p12.', 'name' => $name, 'binary' => ''];
            }

            return ['err' => null, 'name' => $name, 'binary' => $binary];
        }

        return $missing;
    }

    /**
     * @param int $code Constante UPLOAD_ERR_*
     */
    protected function _phpUploadErrorMessage(int $code): string {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'O arquivo excede o tamanho máximo permitido pelo servidor.';
            case UPLOAD_ERR_PARTIAL:
                return 'O upload foi interrompido (arquivo parcial). Tente novamente.';
            case UPLOAD_ERR_NO_FILE:
                return 'Selecione um arquivo de certificado (.pfx ou .p12).';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Servidor sem pasta temporária para upload. Contacte o suporte.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Não foi possível gravar o arquivo temporário no servidor.';
            case UPLOAD_ERR_EXTENSION:
                return 'Uma extensão PHP bloqueou o upload.';
            default:
                return 'Falha no envio do arquivo.';
        }
    }
}
