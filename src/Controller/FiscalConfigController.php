<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Utility\Fiscal\FiscalSqlConditions;
use App\Utility\Fiscal\FiscalStorage;
use Cake\Core\Configure;
use Cake\Event\Event;

/**
 * Configuração fiscal da empresa (regime, série, ambiente, alíquotas, naturezas, CFOP).
 */
class FiscalConfigController extends AppController {

    use FiscalRegimeViewTrait;

    public function initialize() {
        parent::initialize();
        $this->loadModel('FiscalEmpresasConfig');
        $this->loadModel('FiscalNaturezaOperacao');
        $this->loadModel('FiscalAliquotas');
        $this->loadModel('FiscalCfop');
        $this->loadModel('FiscalNcm');
        $this->loadModel('FiscalCertificados');
        Configure::load('fiscal');
        FiscalStorage::ensureDirectories();
    }

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $this->set('title', 'Configuração Fiscal');
        $this->set('pgmAdvancedModuleStylesheet', true);
    }

    public function isAuthorized($user) {
        if ((int)($user['role'] ?? 1) === 1) {
            return false;
        }
        return parent::isAuthorized($user);
    }

    /**
     * Configuração geral fiscal da empresa.
     */
    public function index() {
        $idempresa = $this->Auth->user('idempresa');
        $config = $this->FiscalEmpresasConfig->getOrCreate($idempresa);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $config = $this->FiscalEmpresasConfig->patchEntity($config, $this->request->getData());
            if ($this->FiscalEmpresasConfig->save($config)) {
                $this->Flash->success('Configuração fiscal atualizada.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('Erro ao atualizar configuração.');
        }

        $regimes = Configure::read('Fiscal.regimes');
        $regimeNormalEnquadramentoOptions = Configure::read('Fiscal.regime_normal_enquadramento') ?: [];
        $ufsMap = Configure::read('Fiscal.ufs');
        $ufs = is_array($ufsMap) ? array_keys($ufsMap) : [];
        $ufsOptions = $ufs !== [] ? array_combine($ufs, $ufs) : [];

        $certificados = $this->FiscalCertificados->find('list', [
            'keyField' => 'id',
            'valueField' => 'nome',
        ])
            ->where(['idempresa' => $idempresa])
            ->order(['nome' => 'ASC'])
            ->toArray();

        $this->set(compact('config', 'regimes', 'regimeNormalEnquadramentoOptions', 'ufsOptions', 'certificados'));
    }

    // ── Naturezas de operação ────────────────────────────────────────

    public function naturezas() {
        $idempresa = $this->Auth->user('idempresa');
        $naturezas = $this->FiscalNaturezaOperacao->find()
            ->where(['idempresa' => $idempresa])
            ->order(['descricao' => 'ASC'])
            ->toArray();
        $this->set(compact('naturezas'));
    }

    public function naturezaAdd() {
        $idempresa = $this->Auth->user('idempresa');
        $natureza = $this->FiscalNaturezaOperacao->newEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $data['idempresa'] = $idempresa;
            $natureza = $this->FiscalNaturezaOperacao->patchEntity($natureza, $data);
            if ($this->FiscalNaturezaOperacao->save($natureza)) {
                $this->Flash->success('Natureza de operação cadastrada.');
                return $this->redirect(['action' => 'naturezas']);
            }
            $this->Flash->error('Erro ao cadastrar.');
        }

        $this->loadModel('FiscalCfop');
        $cfops = $this->FiscalCfop->listByTipo('entrada') + $this->FiscalCfop->listByTipo('saida');
        $this->set(compact('natureza', 'cfops'));
    }

    public function naturezaEdit($id = null) {
        $idempresa = $this->Auth->user('idempresa');
        $natureza = $this->FiscalNaturezaOperacao->get($id);

        if ((int)$natureza->idempresa !== (int)$idempresa) {
            return $this->redirect(['action' => 'naturezas']);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $natureza = $this->FiscalNaturezaOperacao->patchEntity($natureza, $this->request->getData());
            if ($this->FiscalNaturezaOperacao->save($natureza)) {
                $this->Flash->success('Natureza atualizada.');
                return $this->redirect(['action' => 'naturezas']);
            }
        }

        $cfops = $this->FiscalCfop->listByTipo('entrada') + $this->FiscalCfop->listByTipo('saida');
        $this->set(compact('natureza', 'cfops'));
    }

    public function naturezaDelete($id = null) {
        $this->request->allowMethod(['post', 'delete']);
        $idempresa = $this->Auth->user('idempresa');
        $natureza = $this->FiscalNaturezaOperacao->get($id);

        if ((int)$natureza->idempresa === (int)$idempresa) {
            $this->FiscalNaturezaOperacao->delete($natureza);
            $this->Flash->success('Natureza excluída.');
        }
        return $this->redirect(['action' => 'naturezas']);
    }

    // ── Alíquotas ────────────────────────────────────────────────────

    public function aliquotas() {
        $idempresa = $this->Auth->user('idempresa');
        $aliquotas = $this->FiscalAliquotas->find()
            ->where(['idempresa' => $idempresa])
            ->order(['uf_origem' => 'ASC', 'uf_destino' => 'ASC'])
            ->toArray();

        $ufsMap = Configure::read('Fiscal.ufs');
        $ufs = is_array($ufsMap) ? array_keys($ufsMap) : [];
        $this->set(compact('aliquotas', 'ufs'));
    }

    public function aliquotaAdd() {
        $idempresa = $this->Auth->user('idempresa');
        $aliquota = $this->FiscalAliquotas->newEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $data['idempresa'] = $idempresa;
            $aliquota = $this->FiscalAliquotas->patchEntity($aliquota, $data);
            if ($this->FiscalAliquotas->save($aliquota)) {
                $this->Flash->success('Alíquota cadastrada.');
                return $this->redirect(['action' => 'aliquotas']);
            }
        }

        $ufsMap = Configure::read('Fiscal.ufs');
        $ufs = is_array($ufsMap) ? array_keys($ufsMap) : [];
        $ufsOptions = $ufs !== [] ? array_combine($ufs, $ufs) : [];
        $this->set(compact('aliquota', 'ufsOptions'));
    }

    public function aliquotaEdit($id = null) {
        $idempresa = $this->Auth->user('idempresa');
        $aliquota = $this->FiscalAliquotas->get($id);

        if ((int)$aliquota->idempresa !== (int)$idempresa) {
            return $this->redirect(['action' => 'aliquotas']);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $aliquota = $this->FiscalAliquotas->patchEntity($aliquota, $this->request->getData());
            if ($this->FiscalAliquotas->save($aliquota)) {
                $this->Flash->success('Alíquota atualizada.');
                return $this->redirect(['action' => 'aliquotas']);
            }
        }

        $ufsMap = Configure::read('Fiscal.ufs');
        $ufs = is_array($ufsMap) ? array_keys($ufsMap) : [];
        $ufsOptions = $ufs !== [] ? array_combine($ufs, $ufs) : [];
        $this->set(compact('aliquota', 'ufsOptions'));
    }

    public function aliquotaDelete($id = null) {
        $this->request->allowMethod(['post', 'delete']);
        $idempresa = $this->Auth->user('idempresa');
        $aliquota = $this->FiscalAliquotas->get($id);

        if ((int)$aliquota->idempresa === (int)$idempresa) {
            $this->FiscalAliquotas->delete($aliquota);
            $this->Flash->success('Alíquota excluída.');
        }
        return $this->redirect(['action' => 'aliquotas']);
    }

    // ── Tabela CFOP (referência) ─────────────────────────────────────

    public function cfop() {
        $query = $this->FiscalCfop->find()->order(['codigo' => 'ASC']);
        $tipo = $this->request->getQuery('tipo');
        if ($tipo) {
            $query->where(['tipo' => $tipo]);
        }
        $cfops = $this->paginate($query, ['limit' => 50]);
        $this->set(compact('cfops'));
    }

    // ── Tabela NCM (referência) ──────────────────────────────────────

    public function ncm() {
        $query = $this->FiscalNcm->find()->order(['codigo' => 'ASC']);
        $busca = $this->request->getQuery('q');
        if ($busca) {
            $or = [
                'codigo LIKE' => "%{$busca}%",
            ];
            $or = array_merge(
                $or,
                FiscalSqlConditions::caseInsensitiveLike($this->FiscalNcm->getConnection(), 'descricao', "%{$busca}%")
            );
            $query->where(['OR' => $or]);
        }
        $ncms = $this->paginate($query, ['limit' => 50]);
        $this->set(compact('ncms', 'busca'));
    }

    // ── CFOP — cadastro manual (além da consulta / importação em massa) ─

    public function cfopAdd() {
        $cfop = $this->FiscalCfop->newEntity();
        if ($this->request->is('post')) {
            $cfop = $this->FiscalCfop->patchEntity($cfop, $this->request->getData());
            if ($this->FiscalCfop->save($cfop)) {
                $this->Flash->success('CFOP cadastrado.');
                return $this->redirect(['action' => 'cfop']);
            }
            $this->Flash->error('Não foi possível salvar o CFOP.');
        }
        $tiposCfop = ['entrada' => 'Entrada', 'saida' => 'Saída'];
        $this->set(compact('cfop', 'tiposCfop'));
    }

    public function cfopEdit($id = null) {
        $cfop = $this->FiscalCfop->get($id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            unset($data['codigo']);
            $cfop = $this->FiscalCfop->patchEntity($cfop, $data);
            if ($this->FiscalCfop->save($cfop)) {
                $this->Flash->success('CFOP atualizado.');
                return $this->redirect(['action' => 'cfop']);
            }
            $this->Flash->error('Não foi possível atualizar.');
        }
        $tiposCfop = ['entrada' => 'Entrada', 'saida' => 'Saída'];
        $this->set(compact('cfop', 'tiposCfop'));
    }

    public function cfopDelete($id = null) {
        $this->request->allowMethod(['post', 'delete']);
        $cfop = $this->FiscalCfop->get($id);
        if ($this->FiscalCfop->delete($cfop)) {
            $this->Flash->success('CFOP excluído.');
        } else {
            $this->Flash->error('Não foi possível excluir.');
        }
        return $this->redirect(['action' => 'cfop']);
    }

    // ── NCM — cadastro manual ─────────────────────────────────────────

    public function ncmAdd() {
        $ncm = $this->FiscalNcm->newEntity();
        if ($this->request->is('post')) {
            $ncm = $this->FiscalNcm->patchEntity($ncm, $this->request->getData());
            if ($this->FiscalNcm->save($ncm)) {
                $this->Flash->success('NCM cadastrado.');
                return $this->redirect(['action' => 'ncm']);
            }
            $this->Flash->error('Não foi possível salvar o NCM.');
        }
        $this->set(compact('ncm'));
    }

    public function ncmEdit($id = null) {
        $ncm = $this->FiscalNcm->get($id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            unset($data['codigo']);
            $ncm = $this->FiscalNcm->patchEntity($ncm, $data);
            if ($this->FiscalNcm->save($ncm)) {
                $this->Flash->success('NCM atualizado.');
                return $this->redirect(['action' => 'ncm']);
            }
            $this->Flash->error('Não foi possível atualizar.');
        }
        $this->set(compact('ncm'));
    }

    public function ncmDelete($id = null) {
        $this->request->allowMethod(['post', 'delete']);
        $ncm = $this->FiscalNcm->get($id);
        if ($this->FiscalNcm->delete($ncm)) {
            $this->Flash->success('NCM excluído.');
        } else {
            $this->Flash->error('Não foi possível excluir.');
        }
        return $this->redirect(['action' => 'ncm']);
    }

    /**
     * Importa tabela NCM completa via BrasilAPI (upsert).
     */
    public function importarNcm() {
        $this->request->allowMethod(['post']);

        $apiUrl = 'https://brasilapi.com.br/api/ncm/v1';
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $json = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($json === false || $httpCode !== 200) {
            $this->Flash->error('Falha ao consultar BrasilAPI (HTTP ' . $httpCode . '). Verifique a conectividade do servidor.');
            return $this->redirect(['action' => 'ncm']);
        }

        $items = json_decode($json, true);
        if (!is_array($items) || empty($items)) {
            $this->Flash->error('Resposta vazia ou inválida da BrasilAPI.');
            return $this->redirect(['action' => 'ncm']);
        }

        $inserted = 0;
        $updated = 0;

        foreach ($items as $item) {
            $codigo = preg_replace('/[^0-9]/', '', trim($item['codigo'] ?? ''));
            $descricao = trim($item['descricao'] ?? '');
            if ($codigo === '' || $descricao === '' || strlen($codigo) < 2 || strlen($codigo) > 10) {
                continue;
            }
            if (mb_strlen($descricao) > 500) {
                $descricao = mb_substr($descricao, 0, 497) . '...';
            }

            $existing = $this->FiscalNcm->find()->where(['codigo' => $codigo])->first();
            if ($existing) {
                if ($existing->descricao !== $descricao) {
                    $existing->descricao = $descricao;
                    $this->FiscalNcm->save($existing);
                    $updated++;
                }
            } else {
                $entity = $this->FiscalNcm->newEntity(['codigo' => $codigo, 'descricao' => $descricao]);
                if ($this->FiscalNcm->save($entity)) {
                    $inserted++;
                }
            }
        }

        $this->Flash->success(sprintf(
            'Importação concluída: %d novos, %d atualizados (total da API: %d).',
            $inserted, $updated, count($items)
        ));
        return $this->redirect(['action' => 'ncm']);
    }
}
