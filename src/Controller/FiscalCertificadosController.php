<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Utility\Fiscal\FiscalCertificadoSecret;
use App\Utility\Fiscal\FiscalSigner;
use App\Utility\Fiscal\FiscalStorage;
use Cake\Core\Configure;
use Cake\Event\Event;

/**
 * Gestão de certificados digitais A1/A3.
 */
class FiscalCertificadosController extends AppController {

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
            $data = $this->request->getData();
            $arquivo = $data['arquivo_upload'] ?? null;

            if (!$arquivo || $arquivo->getError() !== UPLOAD_ERR_OK) {
                $this->Flash->error('Selecione um arquivo de certificado (.pfx ou .p12).');
                $this->set(compact('certificado'));
                return;
            }

            $ext = strtolower(pathinfo($arquivo->getClientFilename(), PATHINFO_EXTENSION));
            if (!in_array($ext, ['pfx', 'p12'])) {
                $this->Flash->error('Formato de arquivo inválido. Envie .pfx ou .p12.');
                $this->set(compact('certificado'));
                return;
            }

            $senha = $data['senha'] ?? '';
            $pfxContent = file_get_contents($arquivo->getStream()->getMetadata('uri'));

            // Validar certificado
            try {
                $signer = new FiscalSigner($pfxContent, $senha);
                $info = $signer->getCertificateInfo();

                if (!$signer->isValid()) {
                    $this->Flash->error('O certificado está vencido (validade até: ' . ($info['valid_to'] ?? 'desconhecida') . ').');
                    $this->set(compact('certificado'));
                    return;
                }
            } catch (\Exception $e) {
                $this->Flash->error('Erro ao ler o certificado: ' . $e->getMessage());
                $this->set(compact('certificado'));
                return;
            }

            // Desativar certificados anteriores
            $this->FiscalCertificados->updateAll(
                ['ativo' => false],
                ['idempresa' => $idempresa]
            );

            $certEntity = $this->FiscalCertificados->newEntity([
                'idempresa' => $idempresa,
                'nome' => $data['nome'] ?? $arquivo->getClientFilename(),
                'tipo' => 'A1',
                'arquivo_pfx' => $pfxContent,
                'senha_hash' => FiscalCertificadoSecret::encryptForStorage($senha, $idempresa),
                'serial_number' => $info['serial_number'] ?? null,
                'cn_subject' => $info['subject'] ?? null,
                'cnpj_certificado' => $info['cnpj'] ?? null,
                'validade_inicio' => $info['valid_from'] ?? null,
                'validade_fim' => $info['valid_to'] ?? null,
                'ativo' => true,
            ]);

            if ($this->FiscalCertificados->save($certEntity)) {
                // Vincular à configuração fiscal
                $config = $this->FiscalEmpresasConfig->getOrCreate($idempresa);
                $config->certificado_id = $certEntity->id;
                $this->FiscalEmpresasConfig->save($config);

                $this->Flash->success('Certificado digital instalado com sucesso. Válido até: ' . ($info['valid_to'] ?? ''));
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('Erro ao salvar certificado.');
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
}
