<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Utility\Fiscal\FiscalAudit;
use App\Utility\Fiscal\FiscalCalculator;
use App\Utility\Fiscal\FiscalNotaRecalculo;
use App\Utility\Fiscal\FiscalCertificadoSecret;
use App\Utility\Fiscal\FiscalDanfe;
use App\Utility\Fiscal\FiscalEmailService;
use App\Utility\Fiscal\FiscalErpSync;
use App\Utility\Fiscal\FiscalProducaoGate;
use App\Utility\Fiscal\FiscalSefazClient;
use App\Utility\Fiscal\FiscalSigner;
use App\Utility\Fiscal\FiscalStorage;
use App\Utility\Fiscal\FiscalValidator;
use App\Utility\Fiscal\FiscalXmlBuilder;
use App\Utility\Fiscal\Nfse\NfseEmissorResolver;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Log\Log;

/**
 * CRUD de Notas Fiscais Eletrônicas (NFe, NFCe, NFSe).
 */
class FiscalNotasController extends AppController {

    use FiscalRegimeViewTrait;

    /** @var int 1 = saída (padrão), 0 = entrada */
    protected $tipoOperacaoPadrao = 1;

    /**
     * Quando definido (ex.: controller de entrada), restringe listagem e notas a este tipo.
     *
     * @var int|null
     */
    protected $tipoOperacaoFixo = null;

    public function initialize() {
        parent::initialize();
        $this->loadModel('FiscalNotas');
        $this->loadModel('FiscalNotasItens');
        $this->loadModel('FiscalNotasImpostos');
        $this->loadModel('FiscalNotasPagamentos');
        $this->loadModel('FiscalNotasEventos');
        $this->loadModel('FiscalNotasXmls');
        $this->loadModel('FiscalEmpresasConfig');
        $this->loadModel('FiscalCertificados');
        $this->loadModel('FiscalNaturezaOperacao');
        $this->loadModel('FiscalAliquotas');
        $this->loadModel('Clientes');
        $this->loadModel('Produtos');
        $this->loadModel('FiscalNotasItensSeries');
        Configure::load('fiscal');
        FiscalStorage::ensureDirectories();
    }

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $this->set('title', 'Notas Fiscais');
        $this->set('pgmAdvancedModuleStylesheet', true);
        if (isset($this->Security)) {
            $unlocked = (array)$this->Security->getConfig('unlockedActions');
            $this->Security->setConfig('unlockedActions', array_merge($unlocked, ['ajaxSugerirNcm', 'ajaxAuditoriaIa']));
        }
    }

    public function isAuthorized($user) {
        if ((int)($user['role'] ?? 1) === 1) {
            return false;
        }
        return parent::isAuthorized($user);
    }

    /**
     * Listagem de notas fiscais com filtros.
     */
    public function index() {
        $idempresa = $this->Auth->user('idempresa');
        $filters = [
            'status' => $this->request->getQuery('status'),
            'modelo' => $this->request->getQuery('modelo'),
            'idcliente' => $this->request->getQuery('cliente'),
            'data_inicio' => $this->request->getQuery('data_inicio'),
            'data_fim' => $this->request->getQuery('data_fim'),
            'numero' => $this->request->getQuery('numero'),
            'numero_serie' => $this->request->getQuery('numero_serie'),
        ];
        $filters = array_filter($filters, function ($v) {
            return $v !== null && $v !== '';
        });
        $filters['tipo_operacao'] = $this->resolvedTipoOperacaoFiltro();

        $query = $this->FiscalNotas->findByEmpresa($idempresa, $filters);
        $notas = $this->paginate($query, ['limit' => 25]);

        $clientes = $this->Clientes->find('list', [
            'keyField' => 'id',
            'valueField' => 'razaosocial',
        ])->where(['Clientes.idempresa' => $idempresa])->order(['razaosocial'])->toArray();

        $statusList = Configure::read('Fiscal.status_nota');
        $modelos = Configure::read('Fiscal.modelos');

        $this->set(compact('notas', 'filters', 'clientes', 'statusList', 'modelos'));
    }

    /**
     * Controle e busca por número de série (linha a linha, todas as notas da empresa).
     */
    public function controleSeries() {
        $idempresa = $this->Auth->user('idempresa');
        $filters = [
            'numero_serie' => $this->request->getQuery('numero_serie'),
            'tipo_operacao' => $this->request->getQuery('tipo_operacao'),
            'codigo_produto' => $this->request->getQuery('codigo_produto'),
            'data_inicio' => $this->request->getQuery('data_inicio'),
            'data_fim' => $this->request->getQuery('data_fim'),
        ];
        $filters = array_filter($filters, function ($v) {
            return $v !== null && $v !== '';
        });

        $query = $this->FiscalNotasItensSeries->findControlePorEmpresa($idempresa, $filters);
        $linhas = $this->paginate($query, ['limit' => 50]);

        $statusList = Configure::read('Fiscal.status_nota');
        $this->set(compact('linhas', 'filters', 'statusList'));
    }

    /**
     * Visualizar nota fiscal.
     */
    public function view($id = null) {
        $idempresa = $this->Auth->user('idempresa');
        $nota = $this->FiscalNotas->get($id, [
            'contain' => [
                'Clientes',
                'Users',
                'FiscalNotasItens' => ['FiscalNotasImpostos', 'FiscalNotasItensSeries'],
                'FiscalNotasPagamentos',
                'FiscalNotasEventos' => ['Users'],
                'FiscalNotasXmls',
            ],
        ]);

        if ((int)$nota->idempresa !== (int)$idempresa) {
            $this->Flash->error('Nota fiscal não encontrada.');
            return $this->redirect(['action' => 'index']);
        }

        if (!$this->assertTipoOperacaoNota($nota)) {
            return $this->redirect(['action' => 'index']);
        }

        $statusList = Configure::read('Fiscal.status_nota');
        $formasPagamento = Configure::read('Fiscal.formas_pagamento');
        $cfgEmpresaFiscal = $this->FiscalEmpresasConfig->getOrCreate($idempresa);
        $fiscalAmbienteProducao = ((int)Configure::read('Fiscal.ambiente', 2)) === 1
            || ((int)($cfgEmpresaFiscal->ambiente ?? 2) === 1);
        $chaveLimpaView = preg_replace('/\D/', '', (string)($nota->chave_acesso ?? ''));
        $podeManifestar = (int)$nota->tipo_operacao === 0
            && $nota->status === 'autorizada'
            && in_array((string)$nota->modelo, ['55', '65'], true)
            && strlen($chaveLimpaView) === 44
            && FiscalValidator::validarChaveAcesso($chaveLimpaView);
        $tiposManifestacao = [
            'confirmacao' => 'Confirmação da operação',
            'ciencia' => 'Ciência da operação',
            'desconhecimento' => 'Desconhecimento da operação',
            'nao_realizada' => 'Operação não realizada',
        ];
        $fiscalDfeRecebidoOrigem = null;
        if ((int)$nota->tipo_operacao === 0) {
            $this->loadModel('FiscalDfeRecebidos');
            $fiscalDfeRecebidoOrigem = $this->FiscalDfeRecebidos->find()
                ->where([
                    'FiscalDfeRecebidos.fiscal_nota_id' => $nota->id,
                    'FiscalDfeRecebidos.idempresa' => $idempresa,
                ])
                ->first();
        }
        $this->set(compact(
            'nota',
            'statusList',
            'formasPagamento',
            'fiscalAmbienteProducao',
            'podeManifestar',
            'tiposManifestacao',
            'fiscalDfeRecebidoOrigem'
        ));
    }

    /**
     * Nova nota fiscal.
     */
    public function add() {
        $idempresa = $this->Auth->user('idempresa');
        $nota = $this->FiscalNotas->newEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $data['idempresa'] = $idempresa;
            $data['user_id'] = $this->Auth->user('id');
            $data['status'] = 'rascunho';
            $data['tipo_operacao'] = isset($this->tipoOperacaoFixo)
                ? (int)$this->tipoOperacaoFixo
                : (int)($data['tipo_operacao'] ?? $this->tipoOperacaoPadrao);

            $nota = $this->FiscalNotas->patchEntity($nota, $data, [
                'associated' => [
                    'FiscalNotasItens' => ['associated' => ['FiscalNotasItensSeries']],
                    'FiscalNotasPagamentos',
                ],
            ]);

            // Calcular impostos e totais
            $nota = $this->recalcularNota($nota, $idempresa);

            if ($this->FiscalNotas->save($nota, [
                'associated' => [
                    'FiscalNotasItens' => ['associated' => ['FiscalNotasItensSeries']],
                    'FiscalNotasPagamentos',
                ],
            ])) {
                $this->Flash->success('Nota fiscal criada como rascunho.');
                return $this->redirect(['action' => 'view', $nota->id]);
            }
            $this->Flash->error('Erro ao criar nota fiscal.');
        }

        $this->setFormData($idempresa);
        $this->set(compact('nota'));
    }

    /**
     * Editar nota fiscal (apenas rascunho/rejeitada).
     */
    public function edit($id = null) {
        $idempresa = $this->Auth->user('idempresa');
        $nota = $this->FiscalNotas->get($id, [
            'contain' => [
                'FiscalNotasItens' => ['FiscalNotasImpostos', 'FiscalNotasItensSeries'],
                'FiscalNotasPagamentos',
            ],
        ]);

        if ((int)$nota->idempresa !== (int)$idempresa) {
            $this->Flash->error('Nota fiscal não encontrada.');
            return $this->redirect(['action' => 'index']);
        }

        if (!$this->assertTipoOperacaoNota($nota)) {
            return $this->redirect(['action' => 'index']);
        }

        if (!in_array($nota->status, ['rascunho', 'rejeitada'])) {
            $this->Flash->error('Apenas notas em rascunho ou rejeitadas podem ser editadas.');
            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            if (isset($this->tipoOperacaoFixo)) {
                $data['tipo_operacao'] = (int)$this->tipoOperacaoFixo;
            }
            $nota = $this->FiscalNotas->patchEntity($nota, $data, [
                'associated' => [
                    'FiscalNotasItens' => ['associated' => ['FiscalNotasItensSeries']],
                    'FiscalNotasPagamentos',
                ],
            ]);

            $nota = $this->recalcularNota($nota, $idempresa);

            if ($this->FiscalNotas->save($nota, [
                'associated' => [
                    'FiscalNotasItens' => ['associated' => ['FiscalNotasItensSeries']],
                    'FiscalNotasPagamentos',
                ],
            ])) {
                $this->Flash->success('Nota fiscal atualizada.');
                return $this->redirect(['action' => 'view', $id]);
            }
            $this->Flash->error('Erro ao atualizar nota fiscal.');
        }

        $this->setFormData($idempresa);
        $this->set(compact('nota'));
    }

    /**
     * Excluir nota fiscal (apenas rascunho).
     */
    public function delete($id = null) {
        $this->request->allowMethod(['post', 'delete']);
        $idempresa = $this->Auth->user('idempresa');
        $nota = $this->FiscalNotas->get($id);

        if ((int)$nota->idempresa !== (int)$idempresa) {
            $this->Flash->error('Nota fiscal não encontrada.');
            return $this->redirect(['action' => 'index']);
        }

        if (!$this->assertTipoOperacaoNota($nota)) {
            return $this->redirect(['action' => 'index']);
        }

        if ($nota->status !== 'rascunho') {
            $this->Flash->error('Apenas notas em rascunho podem ser excluídas.');
            return $this->redirect(['action' => 'index']);
        }

        if ($this->FiscalNotas->delete($nota)) {
            $this->Flash->success('Nota fiscal excluída.');
        } else {
            $this->Flash->error('Erro ao excluir nota fiscal.');
        }
        return $this->redirect(['action' => 'index']);
    }

    /**
     * Emitir nota fiscal — assinar XML, enviar à SEFAZ e processar retorno.
     */
    public function emitir($id = null) {
        $this->request->allowMethod(['post']);
        $idempresa = $this->Auth->user('idempresa');

        $nota = $this->FiscalNotas->get($id, [
            'contain' => [
                'Clientes',
                'FiscalNotasItens' => ['FiscalNotasImpostos'],
                'FiscalNotasPagamentos',
            ],
        ]);

        if ((int)$nota->idempresa !== (int)$idempresa) {
            $this->Flash->error('Nota fiscal não encontrada.');
            return $this->redirect(['action' => 'index']);
        }

        if (!$this->assertTipoOperacaoNota($nota)) {
            return $this->redirect(['action' => 'index']);
        }

        if (!in_array($nota->status, ['rascunho', 'rejeitada'])) {
            $this->Flash->error('Esta nota não pode ser emitida no status atual.');
            return $this->redirect(['action' => 'view', $id]);
        }

        $configFiscal = $this->FiscalEmpresasConfig->getOrCreate($idempresa);
        $r = $this->bloquearSeProducaoSemConfirmacao(
            $id,
            $configFiscal,
            'Ambiente de produção SEFAZ: marque a confirmação antes de transmitir.'
        );
        if ($r !== null) {
            return $r;
        }

        $empresa = $this->Empresas->get($idempresa);

        // Validar dados
        $erros = FiscalValidator::validarNotaParaEmissao(
            $nota->toArray(),
            $configFiscal->toArray(),
            $empresa->toArray()
        );
        if (!empty($erros)) {
            $this->Flash->error('Erros na validação: ' . implode(' | ', $erros));
            return $this->redirect(['action' => 'view', $id]);
        }

        if (strtoupper((string)$nota->modelo) === 'NFSE') {
            return $this->emitirNfseMunicipal($id, $nota, $configFiscal, $empresa);
        }

        // Atribuir número e chave
        $numero = $this->FiscalEmpresasConfig->proximoNumero($idempresa, $nota->modelo);
        $nota->numero = $numero;
        $nota->data_emissao = new \DateTime();

        $cuf = Configure::read('Fiscal.ufs.' . ($configFiscal->uf ?? 'SP')) ?? '35';
        $aamm = date('ym');
        $cnpj = $empresa->cnpj;
        $codigoNumerico = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $nota->chave_acesso = FiscalCalculator::gerarChaveAcesso(
            $cuf, $aamm, $cnpj, $nota->modelo, $nota->serie, $numero, '1', $codigoNumerico
        );

        // Gerar XML
        $builder = new FiscalXmlBuilder(
            $nota->toArray(),
            $configFiscal->toArray(),
            $empresa->toArray(),
            $nota->cliente ? $nota->cliente->toArray() : []
        );
        $xmlNfe = $builder->build();

        // Assinar
        $certificado = $this->FiscalCertificados->getAtivo($idempresa);
        if (!$certificado) {
            $this->Flash->error('Certificado digital não encontrado.');
            return $this->redirect(['action' => 'view', $id]);
        }

        try {
            $signer = new FiscalSigner(
                FiscalCertificadoSecret::pfxBytes($certificado),
                FiscalCertificadoSecret::decryptForUse($certificado->senha_hash, $idempresa)
            );
            $xmlAssinado = $signer->sign($xmlNfe, 'infNFe');
        } catch (\Exception $e) {
            $this->Flash->error('Erro ao assinar XML: ' . $e->getMessage());
            return $this->redirect(['action' => 'view', $id]);
        }

        // Enviar à SEFAZ
        $uidEmit = (int)$this->Auth->user('id');
        $nota->user_id = $uidEmit;
        $nota->status = 'processando';
        $this->FiscalNotas->save($nota);

        try {
            $client = new FiscalSefazClient($signer, $configFiscal->toArray());
            $result = $client->autorizar($xmlAssinado);

            // Salvar XMLs
            $xmlEntity = $this->FiscalNotasXmls->newEntity([
                'fiscal_nota_id' => $nota->id,
                'tipo' => 'autorizacao',
                'xml_envio' => $xmlAssinado,
                'xml_retorno' => $result['xml_retorno'] ?? '',
            ]);
            $this->FiscalNotasXmls->save($xmlEntity);

            if ($result['success']) {
                $nota->status = 'autorizada';
                $nota->protocolo_autorizacao = $result['protocolo'] ?? '';
                $nota->data_autorizacao = new \DateTime();
                $nota->codigo_retorno_sefaz = $result['codigo'];
                $nota->mensagem_retorno_sefaz = $result['mensagem'];
                $this->FiscalNotas->save($nota);
                FiscalAudit::write('info', 'emitir_sucesso', [
                    'nota_id' => $nota->id,
                    'idempresa' => $idempresa,
                    'user_id' => $uidEmit,
                    'protocolo' => $result['protocolo'] ?? null,
                    'ambiente_empresa' => (int)($configFiscal->ambiente ?? 2),
                ]);
                $this->Flash->success('NFe autorizada com sucesso! Protocolo: ' . ($result['protocolo'] ?? ''));

                // Auto-geração de duplicatas financeiras
                try {
                    $this->loadModel('FiscalDuplicatas');
                    $notaComPag = $this->FiscalNotas->get($nota->id, [
                        'contain' => ['FiscalNotasPagamentos'],
                    ]);
                    $dups = $this->FiscalDuplicatas->gerarDeNota($notaComPag);
                    if (!empty($dups)) {
                        $nLanc = $this->FiscalDuplicatas->sincronizarFinanceiro($dups, $notaComPag);
                        $msg = count($dups) . ' duplicata(s) financeira(s) gerada(s).';
                        if ($nLanc > 0) {
                            $msg .= ' ' . $nLanc . ' lançamento(s) financeiro(s) criado(s).';
                        }
                        $this->Flash->success($msg);
                    }
                } catch (\Exception $e) {
                    \Cake\Log\Log::warning('FiscalDuplicatas auto: ' . $e->getMessage());
                }

                // Auto-envio por e-mail (se habilitado)
                if (Configure::read('Fiscal.email.auto_envio_autorizacao')) {
                    $emailResult = FiscalEmailService::enviarNotaAutorizada((int)$nota->id, (int)$idempresa);
                    if ($emailResult['ok']) {
                        $this->Flash->success($emailResult['message']);
                    } else {
                        $this->Flash->warning('Nota autorizada, mas e-mail não enviado: ' . $emailResult['message']);
                    }
                }
            } else {
                $nota->status = 'rejeitada';
                $nota->codigo_retorno_sefaz = $result['codigo'];
                $nota->mensagem_retorno_sefaz = $result['mensagem'];
                $nota->motivo_rejeicao = $result['mensagem'];
                $this->FiscalNotas->save($nota);
                FiscalAudit::write('warning', 'emitir_rejeitada', [
                    'nota_id' => $nota->id,
                    'idempresa' => $idempresa,
                    'user_id' => $uidEmit,
                    'codigo' => $result['codigo'] ?? '',
                ]);
                $this->Flash->error('NFe rejeitada pela SEFAZ: [' . $result['codigo'] . '] ' . $result['mensagem']);
            }
        } catch (\Exception $e) {
            $nota->status = 'rejeitada';
            $nota->motivo_rejeicao = $e->getMessage();
            $this->FiscalNotas->save($nota);
            Log::error('Fiscal emitir: ' . $e->getMessage());
            FiscalAudit::write('error', 'emitir_excecao', [
                'nota_id' => $nota->id,
                'idempresa' => $idempresa,
                'user_id' => $uidEmit,
                'erro' => $e->getMessage(),
            ]);
            $this->Flash->error('Erro na comunicação com a SEFAZ: ' . $e->getMessage());
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * NFS-e municipal: emissor configurado em Fiscal.nfse_emissor_map (não usa NF-e SEFAZ).
     *
     * @param string|int $id
     * @param \App\Model\Entity\FiscalNota $nota
     * @param \App\Model\Entity\FiscalEmpresasConfig $configFiscal
     * @param \App\Model\Entity\Empresa $empresa
     * @return \Cake\Http\Response|null
     */
    protected function emitirNfseMunicipal($id, $nota, $configFiscal, $empresa) {
        $idempresa = (int)$this->Auth->user('idempresa');
        $uidEmit = (int)$this->Auth->user('id');
        $emissor = NfseEmissorResolver::forConfig($configFiscal->toArray());

        $numero = $this->FiscalEmpresasConfig->proximoNumero($idempresa, $nota->modelo);
        $nota->numero = $numero;
        $nota->data_emissao = new \DateTime();
        $nota->user_id = $uidEmit;
        $nota->status = 'processando';
        if (!$this->FiscalNotas->save($nota)) {
            $this->Flash->error('Não foi possível preparar a NFS-e.');
            return $this->redirect(['action' => 'view', $id]);
        }

        $notaAtual = $this->FiscalNotas->get($nota->id, [
            'contain' => ['Clientes', 'FiscalNotasItens', 'FiscalNotasPagamentos'],
        ]);

        try {
            $result = $emissor->emitir(
                $notaAtual->toArray(),
                $configFiscal->toArray(),
                $empresa->toArray()
            );
        } catch (\Throwable $e) {
            Log::error('Fiscal emitir NFS-e: ' . $e->getMessage());
            $notaAtual->status = 'rejeitada';
            $notaAtual->motivo_rejeicao = $e->getMessage();
            $notaAtual->codigo_retorno_sefaz = 'NFSE';
            $notaAtual->mensagem_retorno_sefaz = $e->getMessage();
            $this->FiscalNotas->save($notaAtual);
            FiscalAudit::write('error', 'emitir_nfse_excecao', [
                'nota_id' => $notaAtual->id,
                'idempresa' => $idempresa,
                'user_id' => $uidEmit,
                'erro' => $e->getMessage(),
            ]);
            $this->Flash->error('Erro na emissão NFS-e: ' . $e->getMessage());

            return $this->redirect(['action' => 'view', $notaAtual->id]);
        }

        if (!empty($result['success'])) {
            $notaAtual->status = 'autorizada';
            $notaAtual->protocolo_autorizacao = (string)($result['protocolo'] ?? '');
            $notaAtual->codigo_retorno_sefaz = '0';
            $notaAtual->mensagem_retorno_sefaz = (string)($result['mensagem'] ?? 'Autorizada');
            $notaAtual->motivo_rejeicao = null;
            $this->FiscalNotas->save($notaAtual);
            FiscalAudit::write('info', 'emitir_nfse_sucesso', [
                'nota_id' => $notaAtual->id,
                'idempresa' => $idempresa,
                'user_id' => $uidEmit,
                'protocolo' => $result['protocolo'] ?? null,
            ]);
            $this->Flash->success('NFS-e autorizada. Protocolo: ' . ($result['protocolo'] ?? ''));

            return $this->redirect(['action' => 'view', $notaAtual->id]);
        }

        $notaAtual->status = 'rejeitada';
        $notaAtual->motivo_rejeicao = (string)($result['mensagem'] ?? 'Falha na emissão NFS-e.');
        $notaAtual->codigo_retorno_sefaz = 'NFSE';
        $notaAtual->mensagem_retorno_sefaz = $notaAtual->motivo_rejeicao;
        $this->FiscalNotas->save($notaAtual);
        FiscalAudit::write('warning', 'emitir_nfse_rejeitada', [
            'nota_id' => $notaAtual->id,
            'idempresa' => $idempresa,
            'user_id' => $uidEmit,
        ]);
        $this->Flash->error($notaAtual->motivo_rejeicao);

        return $this->redirect(['action' => 'view', $notaAtual->id]);
    }

    /**
     * Cancelar nota fiscal autorizada.
     */
    public function cancelar($id = null) {
        $this->request->allowMethod(['post']);
        $idempresa = $this->Auth->user('idempresa');
        $nota = $this->FiscalNotas->get($id);

        if ((int)$nota->idempresa !== (int)$idempresa) {
            $this->Flash->error('Esta nota não pode ser cancelada.');
            return $this->redirect(['action' => 'view', $id]);
        }
        if (!$this->assertTipoOperacaoNota($nota)) {
            return $this->redirect(['action' => 'index']);
        }
        if ($nota->status !== 'autorizada') {
            $this->Flash->error('Esta nota não pode ser cancelada.');
            return $this->redirect(['action' => 'view', $id]);
        }

        $justificativa = $this->request->getData('justificativa');
        if (empty($justificativa) || strlen($justificativa) < 15) {
            $this->Flash->error('Justificativa do cancelamento deve ter pelo menos 15 caracteres.');
            return $this->redirect(['action' => 'view', $id]);
        }

        $configFiscal = $this->FiscalEmpresasConfig->getOrCreate($idempresa);
        $r = $this->bloquearSeProducaoSemConfirmacao(
            $id,
            $configFiscal,
            'Produção SEFAZ: confirme o cancelamento na caixa indicada antes de enviar.'
        );
        if ($r !== null) {
            return $r;
        }

        $empresa = $this->Empresas->get($idempresa);
        $certificado = $this->FiscalCertificados->getAtivo($idempresa);

        if (!$certificado) {
            $this->Flash->error('Certificado digital não encontrado.');
            return $this->redirect(['action' => 'view', $id]);
        }

        try {
            $signer = new FiscalSigner(
                FiscalCertificadoSecret::pfxBytes($certificado),
                FiscalCertificadoSecret::decryptForUse($certificado->senha_hash, $idempresa)
            );

            $builder = new FiscalXmlBuilder([], $configFiscal->toArray(), [], []);
            $xmlCancel = $builder->buildCancelamento(
                $nota->chave_acesso,
                $nota->protocolo_autorizacao,
                $justificativa,
                $empresa->cnpj
            );
            $xmlAssinado = $signer->sign($xmlCancel, 'infEvento');

            $client = new FiscalSefazClient($signer, $configFiscal->toArray());
            $result = $client->enviarEvento($xmlAssinado);

            // Registrar evento
            $seq = $this->FiscalNotasEventos->proximaSequencia($nota->id);
            $evento = $this->FiscalNotasEventos->newEntity([
                'fiscal_nota_id' => $nota->id,
                'tipo_evento' => 'cancelamento',
                'sequencia' => $seq,
                'codigo_evento' => '110111',
                'motivo' => $justificativa,
                'protocolo' => $result['protocolo'] ?? null,
                'data_evento' => new \DateTime(),
                'status' => $result['success'] ? 'autorizado' : 'rejeitado',
                'codigo_retorno' => $result['codigo'],
                'mensagem_retorno' => $result['mensagem'],
                'user_id' => $this->Auth->user('id'),
            ]);
            $this->FiscalNotasEventos->save($evento);

            // Salvar XML
            $this->FiscalNotasXmls->save($this->FiscalNotasXmls->newEntity([
                'fiscal_nota_id' => $nota->id,
                'tipo' => 'cancelamento',
                'xml_envio' => $xmlAssinado,
                'xml_retorno' => $result['xml_retorno'] ?? '',
            ]));

            if ($result['success']) {
                $nota->status = 'cancelada';
                $this->FiscalNotas->save($nota);
                FiscalAudit::write('info', 'cancelar_sucesso', [
                    'nota_id' => $nota->id,
                    'idempresa' => $idempresa,
                    'user_id' => $this->Auth->user('id'),
                    'protocolo' => $result['protocolo'] ?? null,
                ]);
                $this->Flash->success('Nota fiscal cancelada com sucesso.');
            } else {
                FiscalAudit::write('warning', 'cancelar_rejeitado', [
                    'nota_id' => $nota->id,
                    'idempresa' => $idempresa,
                    'user_id' => $this->Auth->user('id'),
                    'codigo' => $result['codigo'] ?? '',
                ]);
                $this->Flash->error('Cancelamento rejeitado: [' . $result['codigo'] . '] ' . $result['mensagem']);
            }
        } catch (\Exception $e) {
            Log::error('Fiscal cancelar: ' . $e->getMessage());
            FiscalAudit::write('error', 'cancelar_excecao', [
                'nota_id' => $id,
                'idempresa' => $idempresa,
                'user_id' => $this->Auth->user('id'),
                'erro' => $e->getMessage(),
            ]);
            $this->Flash->error('Erro ao cancelar: ' . $e->getMessage());
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Carta de correção.
     */
    public function cartaCorrecao($id = null) {
        $this->request->allowMethod(['post']);
        $idempresa = $this->Auth->user('idempresa');
        $nota = $this->FiscalNotas->get($id);

        if ((int)$nota->idempresa !== (int)$idempresa) {
            $this->Flash->error('Carta de correção só pode ser emitida para notas autorizadas.');
            return $this->redirect(['action' => 'view', $id]);
        }
        if (!$this->assertTipoOperacaoNota($nota)) {
            return $this->redirect(['action' => 'index']);
        }
        if ($nota->status !== 'autorizada') {
            $this->Flash->error('Carta de correção só pode ser emitida para notas autorizadas.');
            return $this->redirect(['action' => 'view', $id]);
        }

        $correcao = $this->request->getData('correcao');
        if (empty($correcao) || strlen($correcao) < 15) {
            $this->Flash->error('Texto da correção deve ter pelo menos 15 caracteres.');
            return $this->redirect(['action' => 'view', $id]);
        }

        $configFiscal = $this->FiscalEmpresasConfig->getOrCreate($idempresa);
        $r = $this->bloquearSeProducaoSemConfirmacao(
            $id,
            $configFiscal,
            'Produção SEFAZ: confirme o envio da carta de correção na caixa indicada.'
        );
        if ($r !== null) {
            return $r;
        }

        $empresa = $this->Empresas->get($idempresa);
        $certificado = $this->FiscalCertificados->getAtivo($idempresa);

        try {
            $signer = new FiscalSigner(
                FiscalCertificadoSecret::pfxBytes($certificado),
                FiscalCertificadoSecret::decryptForUse($certificado->senha_hash, $idempresa)
            );

            $seq = $this->FiscalNotasEventos->proximaSequencia($nota->id);
            $builder = new FiscalXmlBuilder([], $configFiscal->toArray(), [], []);
            $xmlCCe = $builder->buildCartaCorrecao($nota->chave_acesso, $correcao, $empresa->cnpj, $seq);
            $xmlAssinado = $signer->sign($xmlCCe, 'infEvento');

            $client = new FiscalSefazClient($signer, $configFiscal->toArray());
            $result = $client->enviarEvento($xmlAssinado);

            $evento = $this->FiscalNotasEventos->newEntity([
                'fiscal_nota_id' => $nota->id,
                'tipo_evento' => 'carta_correcao',
                'sequencia' => $seq,
                'codigo_evento' => '110110',
                'motivo' => $correcao,
                'protocolo' => $result['protocolo'] ?? null,
                'data_evento' => new \DateTime(),
                'status' => $result['success'] ? 'autorizado' : 'rejeitado',
                'codigo_retorno' => $result['codigo'],
                'mensagem_retorno' => $result['mensagem'],
                'user_id' => $this->Auth->user('id'),
            ]);
            $this->FiscalNotasEventos->save($evento);

            $this->FiscalNotasXmls->save($this->FiscalNotasXmls->newEntity([
                'fiscal_nota_id' => $nota->id,
                'tipo' => 'carta_correcao',
                'xml_envio' => $xmlAssinado,
                'xml_retorno' => $result['xml_retorno'] ?? '',
            ]));

            if ($result['success']) {
                FiscalAudit::write('info', 'carta_correcao_sucesso', [
                    'nota_id' => $nota->id,
                    'idempresa' => $idempresa,
                    'user_id' => $this->Auth->user('id'),
                    'sequencia' => $seq,
                ]);
                $this->Flash->success('Carta de correção registrada com sucesso.');
            } else {
                FiscalAudit::write('warning', 'carta_correcao_rejeitada', [
                    'nota_id' => $nota->id,
                    'idempresa' => $idempresa,
                    'user_id' => $this->Auth->user('id'),
                    'codigo' => $result['codigo'] ?? '',
                ]);
                $this->Flash->error('CCe rejeitada: [' . $result['codigo'] . '] ' . $result['mensagem']);
            }
        } catch (\Exception $e) {
            Log::error('Fiscal carta correção: ' . $e->getMessage());
            FiscalAudit::write('error', 'carta_correcao_excecao', [
                'nota_id' => $id,
                'idempresa' => $idempresa,
                'user_id' => $this->Auth->user('id'),
                'erro' => $e->getMessage(),
            ]);
            $this->Flash->error('Erro na carta de correção: ' . $e->getMessage());
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Manifestação do destinatário — apenas notas de entrada autorizadas (NF-e/NFC-e).
     */
    public function manifestarDestinatario($id = null) {
        $this->request->allowMethod(['post']);
        $idempresa = $this->Auth->user('idempresa');
        $ctrl = $this->request->getParam('controller');
        $redirectView = ['controller' => $ctrl, 'action' => 'view', $id];

        $nota = $this->FiscalNotas->get($id);
        if ((int)$nota->idempresa !== (int)$idempresa) {
            $this->Flash->error('Nota fiscal não encontrada.');
            return $this->redirect(['action' => 'index']);
        }
        if (!$this->assertTipoOperacaoNota($nota)) {
            return $this->redirect(['action' => 'index']);
        }
        if ((int)$nota->tipo_operacao !== 0) {
            $this->Flash->error('Manifestação do destinatário aplica-se apenas a notas de entrada.');
            return $this->redirect($redirectView);
        }
        if ($nota->status !== 'autorizada') {
            $this->Flash->error('Apenas notas autorizadas podem ser manifestadas.');
            return $this->redirect($redirectView);
        }
        if (!in_array((string)$nota->modelo, ['55', '65'], true)) {
            $this->Flash->error('Modelo não suportado para manifestação neste fluxo.');
            return $this->redirect($redirectView);
        }

        $chave = preg_replace('/\D/', '', (string)($nota->chave_acesso ?? ''));
        if (strlen($chave) !== 44 || !FiscalValidator::validarChaveAcesso($chave)) {
            $this->Flash->error('Chave de acesso inválida para manifestação.');
            return $this->redirect($redirectView);
        }

        $tipo = (string)$this->request->getData('tipo_manifestacao');
        $mapTipos = ['confirmacao', 'ciencia', 'desconhecimento', 'nao_realizada'];
        if (!in_array($tipo, $mapTipos, true)) {
            $this->Flash->error('Tipo de manifestação inválido.');
            return $this->redirect($redirectView);
        }

        $just = trim((string)$this->request->getData('justificativa_manifestacao'));
        if (in_array($tipo, ['desconhecimento', 'nao_realizada'], true) && strlen($just) < 15) {
            $this->Flash->error('Justificativa deve ter pelo menos 15 caracteres para este tipo de manifestação.');
            return $this->redirect($redirectView);
        }

        $configFiscal = $this->FiscalEmpresasConfig->getOrCreate($idempresa);
        $r = $this->bloquearSeProducaoSemConfirmacao(
            $id,
            $configFiscal,
            'Produção SEFAZ: confirme a manifestação do destinatário na caixa indicada.',
            $redirectView
        );
        if ($r !== null) {
            return $r;
        }

        $empresa = $this->Empresas->get($idempresa);
        $certificado = $this->FiscalCertificados->getAtivo($idempresa);
        if (!$certificado) {
            $this->Flash->error('Certificado digital não encontrado.');
            return $this->redirect($redirectView);
        }

        $codigos = [
            'confirmacao' => '210200',
            'ciencia' => '210210',
            'desconhecimento' => '210220',
            'nao_realizada' => '210240',
        ];
        $codTp = $codigos[$tipo];

        try {
            $seq = $this->FiscalNotasEventos->proximaSequenciaPorCodigoEvento($nota->id, $codTp);
            $signer = new FiscalSigner(
                FiscalCertificadoSecret::pfxBytes($certificado),
                FiscalCertificadoSecret::decryptForUse($certificado->senha_hash, $idempresa)
            );
            $builder = new FiscalXmlBuilder([], $configFiscal->toArray(), [], []);
            $xmlManifest = $builder->buildManifestacaoDestinatario(
                $chave,
                $empresa->cnpj,
                $tipo,
                $seq,
                $just
            );
            $xmlAssinado = $signer->sign($xmlManifest, 'infEvento');
            $client = new FiscalSefazClient($signer, $configFiscal->toArray());
            $result = $client->enviarEvento($xmlAssinado);

            $motivoGravar = $just !== '' ? $just : $tipo;
            $evento = $this->FiscalNotasEventos->newEntity([
                'fiscal_nota_id' => $nota->id,
                'tipo_evento' => 'manifestacao',
                'sequencia' => $seq,
                'codigo_evento' => $codTp,
                'motivo' => $motivoGravar,
                'protocolo' => $result['protocolo'] ?? null,
                'data_evento' => new \DateTime(),
                'status' => $result['success'] ? 'autorizado' : 'rejeitado',
                'codigo_retorno' => $result['codigo'],
                'mensagem_retorno' => $result['mensagem'],
                'user_id' => $this->Auth->user('id'),
            ]);
            $this->FiscalNotasEventos->save($evento);

            $this->FiscalNotasXmls->save($this->FiscalNotasXmls->newEntity([
                'fiscal_nota_id' => $nota->id,
                'tipo' => 'evento',
                'xml_envio' => $xmlAssinado,
                'xml_retorno' => $result['xml_retorno'] ?? '',
            ]));

            if ($result['success']) {
                FiscalAudit::write('info', 'manifestacao_destinatario_sucesso', [
                    'nota_id' => $nota->id,
                    'idempresa' => $idempresa,
                    'user_id' => $this->Auth->user('id'),
                    'tipo' => $tipo,
                    'protocolo' => $result['protocolo'] ?? null,
                ]);
                $this->Flash->success('Manifestação registrada na SEFAZ.');
            } else {
                FiscalAudit::write('warning', 'manifestacao_destinatario_rejeitada', [
                    'nota_id' => $nota->id,
                    'idempresa' => $idempresa,
                    'codigo' => $result['codigo'] ?? '',
                ]);
                $this->Flash->error('Manifestação rejeitada: [' . ($result['codigo'] ?? '') . '] ' . ($result['mensagem'] ?? ''));
            }
        } catch (\Exception $e) {
            Log::error('Fiscal manifestarDestinatario: ' . $e->getMessage());
            FiscalAudit::write('error', 'manifestacao_destinatario_excecao', [
                'nota_id' => $id,
                'idempresa' => $idempresa,
                'erro' => $e->getMessage(),
            ]);
            $this->Flash->error('Erro ao manifestar: ' . $e->getMessage());
        }

        return $this->redirect($redirectView);
    }

    /**
     * Inutilização de numeração NF-e/NFC-e (faixa na SEFAZ).
     */
    public function inutilizarNumeracao() {
        $idempresa = $this->Auth->user('idempresa');
        $configFiscal = $this->FiscalEmpresasConfig->getOrCreate($idempresa);
        $empresa = $this->Empresas->get($idempresa);
        $fiscalAmbienteProducao = ((int)Configure::read('Fiscal.ambiente', 2)) === 1
            || ((int)($configFiscal->ambiente ?? 2) === 1);
        $modelos = Configure::read('Fiscal.modelos');

        if ($this->request->is('post')) {
            $redirectForm = ['action' => 'inutilizarNumeracao'];
            $r = $this->bloquearSeProducaoSemConfirmacao(
                0,
                $configFiscal,
                'Produção SEFAZ: confirme a inutilização na caixa indicada.',
                $redirectForm
            );
            if ($r !== null) {
                return $r;
            }

            $data = $this->request->getData();
            $modelo = (string)($data['modelo'] ?? '');
            $serie = (int)($data['serie'] ?? -1);
            $nIni = (int)($data['numero_inicial'] ?? 0);
            $nFin = (int)($data['numero_final'] ?? 0);
            $just = trim((string)($data['justificativa'] ?? ''));
            $anoRef = trim((string)($data['ano_referencia'] ?? ''));

            if (!in_array($modelo, ['55', '65'], true)) {
                $this->Flash->error('Modelo deve ser 55 (NF-e) ou 65 (NFC-e).');
                return $this->redirect($redirectForm);
            }
            if ($serie < 0 || $serie > 999) {
                $this->Flash->error('Série inválida (0 a 999).');
                return $this->redirect($redirectForm);
            }
            if ($nIni < 1 || $nFin < 1 || $nFin < $nIni) {
                $this->Flash->error('Números inicial e final inválidos.');
                return $this->redirect($redirectForm);
            }
            if (($nFin - $nIni) > 99) {
                $this->Flash->error('Limite de 100 números por pedido (ajuste a faixa).');
                return $this->redirect($redirectForm);
            }
            if (strlen($just) < 15) {
                $this->Flash->error('Justificativa deve ter pelo menos 15 caracteres.');
                return $this->redirect($redirectForm);
            }

            $cnpjEmpresa = preg_replace('/\D/', '', (string)($empresa->cnpj ?? ''));
            if (strlen($cnpjEmpresa) !== 14) {
                $this->Flash->error('CNPJ da empresa deve ter 14 dígitos (cadastro da empresa) para inutilizar numeração.');
                return $this->redirect($redirectForm);
            }
            if (!FiscalValidator::validarCnpj($cnpjEmpresa)) {
                $this->Flash->error('CNPJ da empresa inválido (dígitos verificadores). Corrija o cadastro antes de inutilizar.');
                return $this->redirect($redirectForm);
            }

            if ($anoRef === '') {
                $ano2 = date('y');
            } elseif (strlen($anoRef) <= 2) {
                $ano2 = str_pad(preg_replace('/\D/', '', $anoRef), 2, '0', STR_PAD_LEFT);
            } else {
                $y = (int)preg_replace('/\D/', '', $anoRef);
                $ano2 = str_pad((string)($y % 100), 2, '0', STR_PAD_LEFT);
            }

            $certificado = $this->FiscalCertificados->getAtivo($idempresa);
            if (!$certificado) {
                $this->Flash->error('Certificado digital não encontrado.');
                return $this->redirect($redirectForm);
            }

            $uid = (int)$this->Auth->user('id');
            try {
                $builder = new FiscalXmlBuilder([], $configFiscal->toArray(), $empresa->toArray(), []);
                $xmlRaw = $builder->buildInutilizacao($ano2, $empresa->cnpj, $modelo, $serie, $nIni, $nFin, $just);
                $signer = new FiscalSigner(
                    FiscalCertificadoSecret::pfxBytes($certificado),
                    FiscalCertificadoSecret::decryptForUse($certificado->senha_hash, $idempresa)
                );
                $xmlAssinado = $signer->sign($xmlRaw, 'infInut');
                $client = new FiscalSefazClient($signer, $configFiscal->toArray());
                $result = $client->inutilizar($xmlAssinado);

                FiscalStorage::writeInutilizacaoArtifact($idempresa, 'envio', $xmlAssinado);
                FiscalStorage::writeInutilizacaoArtifact($idempresa, 'retorno', $result['xml_retorno'] ?? '');

                if ($result['success']) {
                    FiscalAudit::write('info', 'inutilizar_sucesso', [
                        'idempresa' => $idempresa,
                        'user_id' => $uid,
                        'modelo' => $modelo,
                        'serie' => $serie,
                        'n_ini' => $nIni,
                        'n_fin' => $nFin,
                        'ano_yy' => $ano2,
                        'protocolo' => $result['protocolo'] ?? null,
                        'ambiente_empresa' => (int)($configFiscal->ambiente ?? 2),
                    ]);
                    $this->Flash->success(
                        'Inutilização homologada pela SEFAZ. Verifique o próximo número na configuração fiscal se necessário.'
                    );
                } else {
                    FiscalAudit::write('warning', 'inutilizar_rejeitada', [
                        'idempresa' => $idempresa,
                        'user_id' => $uid,
                        'codigo' => $result['codigo'] ?? '',
                        'modelo' => $modelo,
                        'serie' => $serie,
                        'n_ini' => $nIni,
                        'n_fin' => $nFin,
                    ]);
                    $this->Flash->error(
                        'Inutilização rejeitada: [' . ($result['codigo'] ?? '') . '] ' . ($result['mensagem'] ?? '')
                    );
                }
            } catch (\Exception $e) {
                Log::error('Fiscal inutilizar: ' . $e->getMessage());
                FiscalAudit::write('error', 'inutilizar_excecao', [
                    'idempresa' => $idempresa,
                    'user_id' => $uid,
                    'erro' => $e->getMessage(),
                ]);
                $this->Flash->error('Erro na inutilização: ' . $e->getMessage());
            }

            return $this->redirect($redirectForm);
        }

        $this->set(compact('configFiscal', 'fiscalAmbienteProducao', 'modelos', 'empresa'));
    }

    /**
     * Gerar e baixar DANFE (PDF).
     */
    public function danfe($id = null) {
        $idempresa = $this->Auth->user('idempresa');
        $nota = $this->FiscalNotas->get($id, [
            'contain' => [
                'Clientes',
                'FiscalNotasItens' => ['FiscalNotasImpostos'],
                'FiscalNotasPagamentos',
            ],
        ]);

        if ((int)$nota->idempresa !== (int)$idempresa) {
            $this->Flash->error('Nota fiscal não encontrada.');
            return $this->redirect(['action' => 'index']);
        }

        if (!$this->assertTipoOperacaoNota($nota)) {
            return $this->redirect(['action' => 'index']);
        }

        $configFiscal = $this->FiscalEmpresasConfig->getOrCreate($idempresa);
        $empresa = $this->Empresas->get($idempresa);

        $danfe = new FiscalDanfe(
            $nota->toArray(),
            $empresa->toArray(),
            $nota->cliente ? $nota->cliente->toArray() : [],
            $configFiscal->toArray()
        );

        $this->autoRender = false;
        $pdf = $danfe->generate();
        $this->response = $this->response
            ->withType('application/pdf')
            ->withHeader('Content-Disposition', 'inline; filename="DANFE_' . ($nota->numero ?? 'rascunho') . '.pdf"')
            ->withStringBody($pdf);
        return $this->response;
    }

    /**
     * Download do XML da nota.
     */
    public function downloadXml($id = null) {
        $idempresa = $this->Auth->user('idempresa');
        $nota = $this->FiscalNotas->get($id);

        if ((int)$nota->idempresa !== (int)$idempresa) {
            $this->Flash->error('Nota fiscal não encontrada.');
            return $this->redirect(['action' => 'index']);
        }

        if (!$this->assertTipoOperacaoNota($nota)) {
            return $this->redirect(['action' => 'index']);
        }

        $tipo = $this->request->getQuery('tipo', 'autorizacao');
        $xml = $this->FiscalNotasXmls->find()
            ->where(['fiscal_nota_id' => $id, 'tipo' => $tipo])
            ->order(['created' => 'DESC'])
            ->first();

        // Fallback: notas importadas de outro ERP têm tipo xml_import ou dfe_import
        if (!$xml && $tipo === 'autorizacao') {
            $xml = $this->FiscalNotasXmls->find()
                ->where(['fiscal_nota_id' => $id, 'tipo IN' => ['xml_import', 'dfe_import']])
                ->order(['created' => 'DESC'])
                ->first();
        }

        if (!$xml) {
            $this->Flash->error('XML não encontrado.');
            return $this->redirect(['action' => 'view', $id]);
        }

        $this->autoRender = false;
        $content = $xml->xml_retorno ?: $xml->xml_envio;
        $filename = 'NFe_' . ($nota->chave_acesso ?? $nota->id) . '_' . $tipo . '.xml';

        $this->response = $this->response
            ->withType('application/xml')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withStringBody($content);
        return $this->response;
    }

    /**
     * Enviar DANFE + XML por e-mail ao cliente (manual).
     */
    public function enviarEmail($id = null) {
        $this->request->allowMethod(['post']);
        $idempresa = $this->Auth->user('idempresa');
        $nota = $this->FiscalNotas->get($id);

        if ((int)$nota->idempresa !== (int)$idempresa) {
            $this->Flash->error('Nota fiscal não encontrada.');
            return $this->redirect(['action' => 'index']);
        }
        if (!$this->assertTipoOperacaoNota($nota)) {
            return $this->redirect(['action' => 'index']);
        }

        $emailOverride = trim((string)$this->request->getData('email_destinatario'));
        $result = FiscalEmailService::enviarNotaAutorizada(
            (int)$nota->id,
            (int)$idempresa,
            $emailOverride !== '' ? $emailOverride : null
        );

        if ($result['ok']) {
            $this->Flash->success($result['message']);
        } else {
            $this->Flash->error($result['message']);
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Sincronizar nota com o ERP (WebGridPGM).
     */
    public function sincronizarErp($id = null) {
        $this->request->allowMethod(['post']);
        $idempresa = $this->Auth->user('idempresa');
        $nota = $this->FiscalNotas->get($id);

        if ((int)$nota->idempresa !== (int)$idempresa) {
            $this->Flash->error('Nota fiscal não encontrada.');
            return $this->redirect(['action' => 'index']);
        }
        if (!$this->assertTipoOperacaoNota($nota)) {
            return $this->redirect(['action' => 'index']);
        }

        $result = FiscalErpSync::enviarNotaParaErp((int)$nota->id, (int)$idempresa);
        if ($result['ok']) {
            $this->Flash->success($result['message'] . (!empty($result['erp_id']) ? ' (ERP ID: ' . $result['erp_id'] . ')' : ''));
        } else {
            $this->Flash->error($result['message']);
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Consultar NF-e na SEFAZ por chave de acesso (44 dígitos).
     */
    public function consultarChave() {
        $idempresa = $this->Auth->user('idempresa');
        $resultado = null;
        $chaveInput = '';

        if ($this->request->is('post')) {
            $chaveInput = preg_replace('/\D/', '', (string)$this->request->getData('chave_acesso'));
            if (strlen($chaveInput) !== 44) {
                $this->Flash->error('Chave de acesso deve ter 44 dígitos.');
            } else {
                $certificado = $this->FiscalCertificados->getAtivo($idempresa);
                if (!$certificado) {
                    $this->Flash->error('Certificado digital não configurado.');
                } else {
                    $configFiscal = $this->FiscalEmpresasConfig->getOrCreate($idempresa);
                    try {
                        $signer = new FiscalSigner(
                            FiscalCertificadoSecret::pfxBytes($certificado),
                            FiscalCertificadoSecret::decryptForUse($certificado->senha_hash, $idempresa)
                        );
                        $client = new FiscalSefazClient($signer, $configFiscal->toArray());
                        $resultado = $client->consultarProtocolo($chaveInput);
                    } catch (\Exception $e) {
                        $this->Flash->error('Erro na consulta: ' . $e->getMessage());
                    }
                }
            }
        }

        $this->set(compact('resultado', 'chaveInput'));
    }

    /**
     * Consultar cadastro de contribuinte (CNPJ/IE) na SEFAZ.
     */
    public function consultarCadastro() {
        $idempresa = $this->Auth->user('idempresa');
        $resultado = null;
        $docInput = '';
        $ufInput = '';
        $tipoInput = 'cnpj';

        if ($this->request->is('post')) {
            $docInput = trim((string)$this->request->getData('documento'));
            $ufInput = strtoupper(trim((string)$this->request->getData('uf')));
            $tipoInput = (string)$this->request->getData('tipo') === 'ie' ? 'ie' : 'cnpj';

            $docLimpo = preg_replace('/\D/', '', $docInput);
            if ($tipoInput === 'cnpj' && strlen($docLimpo) !== 14) {
                $this->Flash->error('CNPJ deve ter 14 dígitos.');
            } elseif ($tipoInput === 'ie' && strlen($docLimpo) < 2) {
                $this->Flash->error('Inscrição Estadual inválida.');
            } elseif (strlen($ufInput) !== 2) {
                $this->Flash->error('UF deve ter 2 caracteres (ex.: SP, RJ, MG).');
            } else {
                $certificado = $this->FiscalCertificados->getAtivo($idempresa);
                if (!$certificado) {
                    $this->Flash->error('Certificado digital não configurado.');
                } else {
                    $configFiscal = $this->FiscalEmpresasConfig->getOrCreate($idempresa);
                    try {
                        $signer = new FiscalSigner(
                            FiscalCertificadoSecret::pfxBytes($certificado),
                            FiscalCertificadoSecret::decryptForUse($certificado->senha_hash, $idempresa)
                        );
                        $client = new FiscalSefazClient($signer, $configFiscal->toArray());
                        $resultado = $client->consultarCadastro($docLimpo, $ufInput, $tipoInput);
                    } catch (\Exception $e) {
                        $this->Flash->error('Erro na consulta: ' . $e->getMessage());
                    }
                }
            }
        }

        $ufsMap = Configure::read('Fiscal.ufs');
        $ufs = is_array($ufsMap) ? array_keys($ufsMap) : [];
        $this->set(compact('resultado', 'docInput', 'ufInput', 'tipoInput', 'ufs'));
    }

    /**
     * Busca AJAX de NCM.
     */
    public function buscarNcm() {
        $this->autoRender = false;
        $this->response = $this->response->withType('application/json');
        $this->loadModel('FiscalNcm');

        $termo = $this->request->getQuery('q', '');
        $results = $this->FiscalNcm->buscar($termo);

        $this->response = $this->response->withStringBody(json_encode($results));
        return $this->response;
    }

    /**
     * Busca AJAX de CFOP.
     */
    public function buscarCfop() {
        $this->autoRender = false;
        $this->response = $this->response->withType('application/json');
        $this->loadModel('FiscalCfop');

        $tipo = $this->request->getQuery('tipo', 'saida');
        $results = $this->FiscalCfop->listByTipo($tipo);

        $this->response = $this->response->withStringBody(json_encode($results));
        return $this->response;
    }

    // ── Helpers privados ─────────────────────────────────────────────

    private function setFormData($idempresa) {
        $clientes = $this->Clientes->find('list', [
            'keyField' => 'id',
            'valueField' => 'razaosocial',
        ])->where(['Clientes.idempresa' => $idempresa])->order(['razaosocial'])->toArray();

        $naturezaTipo = null;
        if (isset($this->tipoOperacaoFixo)) {
            $naturezaTipo = ((int)$this->tipoOperacaoFixo === 0) ? 'entrada' : 'saida';
        } else {
            $naturezaTipo = ((int)$this->tipoOperacaoPadrao === 0) ? 'entrada' : 'saida';
        }
        $naturezas = $this->FiscalNaturezaOperacao->listByEmpresa($idempresa, $naturezaTipo);
        $cfopListTipo = ($naturezaTipo === 'entrada') ? 'entrada' : 'saida';
        $modelos = Configure::read('Fiscal.modelos');
        $formasPagamento = Configure::read('Fiscal.formas_pagamento');
        $bandeirasCartao = Configure::read('Fiscal.bandeiras_cartao');
        $origens = Configure::read('Fiscal.origens_mercadoria');
        $cstIcms = Configure::read('Fiscal.cst_icms');
        $csosn = Configure::read('Fiscal.csosn');
        $cstPisCofins = Configure::read('Fiscal.cst_pis_cofins');
        $cstIpi = Configure::read('Fiscal.cst_ipi');

        $configFiscal = $this->FiscalEmpresasConfig->getOrCreate($idempresa);
        $regime = (int)($configFiscal->regime_tributario ?? 3);
        $this->set(compact(
            'clientes', 'naturezas', 'modelos', 'formasPagamento', 'bandeirasCartao',
            'origens', 'cstIcms', 'csosn', 'cstPisCofins', 'cstIpi', 'configFiscal', 'regime',
            'cfopListTipo'
        ));
    }

    /**
     * Produção SEFAZ ativa (global ou por empresa).
     *
     * @param \App\Model\Entity\FiscalEmpresasConfig|\Cake\Datasource\EntityInterface $configFiscal
     */
    protected function fiscalProducaoAtivoFromConfig($configFiscal): bool {
        return FiscalProducaoGate::ambienteEhProducao(
            (int)Configure::read('Fiscal.ambiente', 2),
            (int)($configFiscal->ambiente ?? 2)
        );
    }

    /**
     * Em produção exige POST `confirmar_producao` (checkbox nas views).
     *
     * @param int|string $notaId
     * @param \App\Model\Entity\FiscalEmpresasConfig|\Cake\Datasource\EntityInterface $configFiscal
     * @param string|null $mensagem
     * @param array<string,mixed>|null $redirectTarget URL alternativa (ex.: inutilização sem nota)
     * @return \Cake\Http\Response|null redirect ou null se OK
     */
    protected function bloquearSeProducaoSemConfirmacao($notaId, $configFiscal, $mensagem = null, ?array $redirectTarget = null) {
        if (!$this->fiscalProducaoAtivoFromConfig($configFiscal)) {
            return null;
        }
        if (FiscalProducaoGate::confirmacaoProducaoMarcada($this->request->getData())) {
            return null;
        }
        $this->Flash->error($mensagem ?? 'Ambiente de produção SEFAZ: marque a confirmação antes de continuar.');

        return $this->redirect($redirectTarget ?? ['action' => 'view', $notaId]);
    }

    /**
     * @param \App\Model\Entity\FiscalNota|\Cake\Datasource\EntityInterface $nota
     */
    protected function assertTipoOperacaoNota($nota) {
        if ($this->tipoOperacaoFixo === null) {
            return true;
        }
        if ((int)$nota->tipo_operacao !== (int)$this->tipoOperacaoFixo) {
            $this->Flash->error('Esta nota não pertence a este módulo.');
            return false;
        }
        return true;
    }

    protected function resolvedTipoOperacaoFiltro() {
        if ($this->tipoOperacaoFixo !== null) {
            return (int)$this->tipoOperacaoFixo;
        }
        $q = $this->request->getQuery('tipo_operacao');
        if ($q === null || $q === '') {
            return (int)$this->tipoOperacaoPadrao;
        }
        return (int)$q;
    }

    private function recalcularNota($nota, $idempresa) {
        return FiscalNotaRecalculo::aplicar($nota, $idempresa, $this->FiscalEmpresasConfig, $this->FiscalAliquotas);
    }

    public function ajaxSugerirNcm() {
        $this->request->allowMethod(['post']);
        $this->autoRender = false;
        $descricao = $this->request->getData('descricao');
        if (empty($descricao)) {
            return $this->response->withType('application/json')->withStringBody(json_encode(['error' => 'Descrição vazia']));
        }
        try {
            $json = \App\Utility\Fiscal\FiscalAI::sugerirNcm($descricao);
            return $this->response->withType('application/json')->withStringBody(json_encode($json));
        } catch (\Throwable $e) {
            return $this->response->withStatus(500)->withType('application/json')->withStringBody(json_encode(['error' => $e->getMessage()]));
        }
    }

    public function ajaxAuditoriaIa($id = null) {
        $this->request->allowMethod(['post']);
        $this->autoRender = false;
        
        $idempresa = $this->Auth->user('idempresa');
        $nota = $this->FiscalNotas->get($id, [
            'contain' => ['Clientes', 'FiscalNotasItens']
        ]);

        if ((int)$nota->idempresa !== (int)$idempresa) {
            return $this->response->withStatus(403)->withType('application/json')->withStringBody(json_encode(['error' => 'Acesso negado']));
        }

        $this->loadModel('FiscalEmpresasConfig');
        $config = $this->FiscalEmpresasConfig->getOrCreate($idempresa);
        $regime = ((int)$config->regime_tributario === 1) ? 'Simples Nacional' : 'Regime Normal (Lucro Presumido/Real)';

        try {
            $itensLimp = [];
            foreach ($nota->fiscal_notas_itens as $i) {
                $itensLimp[] = [
                    'cfop' => $i->cfop,
                    'ncm' => $i->ncm,
                    'descricao' => $i->descricao,
                    'cst_csosn' => $i->cst_csosn_icms ?? 'Padrao da emissao'
                ];
            }
            $jsonSchema = json_encode([
                'tipo_operacao' => $nota->tipo_operacao === 0 ? 'Entrada' : 'Saida',
                'natureza_operacao' => $nota->natureza_operacao,
                'cliente_uf' => $nota->cliente->estado ?? 'Desconhecido',
                'cliente_tipo' => $nota->cliente->tipo ?? 1,
                'itens' => $itensLimp
            ], JSON_UNESCAPED_UNICODE);

            $result = \App\Utility\Fiscal\FiscalAI::auditarNota($jsonSchema, $regime);
            return $this->response->withType('application/json')->withStringBody(json_encode($result));
        } catch (\Throwable $e) {
            return $this->response->withStatus(500)->withType('application/json')->withStringBody(json_encode(['error' => $e->getMessage()]));
        }
    }
}
