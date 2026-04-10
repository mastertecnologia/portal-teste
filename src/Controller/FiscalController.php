<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Utility\Fiscal\FiscalAudit;
use App\Utility\Fiscal\FiscalCertificadoSecret;
use App\Utility\Fiscal\FiscalContingencia;
use App\Utility\Fiscal\FiscalDfeDocZipSummary;
use App\Utility\Fiscal\FiscalImportacaoDfeEntrada;
use App\Utility\Fiscal\FiscalResNfeImportParser;
use App\Utility\Fiscal\FiscalSqlConditions;
use App\Utility\Fiscal\FiscalStorage;
use App\Utility\Fiscal\FiscalRegimeHelper;
use App\Utility\Fiscal\FiscalValidator;
use App\Utility\RbacChecker;
use Cake\Core\Configure;
use Cake\Database\Driver\Postgres;
use Cake\Event\Event;
use Cake\Log\Log;

/**
 * Módulo Fiscal — Dashboard e ações gerais.
 */
class FiscalController extends AppController {

    use FiscalRegimeViewTrait;

    public function initialize() {
        parent::initialize();
        $this->loadModel('FiscalNotas');
        $this->loadModel('FiscalCertificados');
        $this->loadModel('FiscalEmpresasConfig');
        $this->loadModel('FiscalDfeRecebidos');
        Configure::load('fiscal');
        FiscalStorage::ensureDirectories();
    }

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $this->set('title', 'Módulo Fiscal');
        $this->set('pgmAdvancedModuleStylesheet', true);
    }

    public function isAuthorized($user) {
        if ((int)($user['role'] ?? 1) === 1) {
            return false;
        }
        return parent::isAuthorized($user);
    }

    /**
     * Dashboard fiscal — visão geral.
     */
    public function index() {
        $idempresa = $this->Auth->user('idempresa');

        // Totais por status
        $totaisPorStatus = $this->FiscalNotas->totaisPorStatus($idempresa);

        // Certificado ativo
        $certificado = $this->FiscalCertificados->getAtivo($idempresa);
        $certValido = false;
        $certDiasRestantes = 0;
        if ($certificado) {
            $certValido = $certificado->validade_fim ? $certificado->validade_fim > new \DateTime() : false;
            if ($certificado->validade_fim) {
                $diff = $certificado->validade_fim->diff(new \DateTime());
                $certDiasRestantes = $certValido ? $diff->days : 0;
            }
        }

        // Config fiscal
        $configFiscal = $this->FiscalEmpresasConfig->getOrCreate($idempresa);

        // Últimas notas emitidas
        $ultimasNotas = $this->FiscalNotas->find()
            ->where(['FiscalNotas.idempresa' => $idempresa])
            ->contain(['Clientes' => ['fields' => ['id', 'razaosocial', 'nome']]])
            ->order(['FiscalNotas.created' => 'DESC'])
            ->limit(10)
            ->toArray();

        // Notas por mês (últimos 6 meses) — TO_CHAR em PostgreSQL; strftime em SQLite (testes HTTP); DATE_FORMAT em MySQL
        $mesExpr = $this->fiscalSqlMesAnoAgrupamento('FiscalNotas.data_emissao');
        $notasPorMes = $this->FiscalNotas->find()
            ->select([
                'mes' => $mesExpr,
                'total' => $this->FiscalNotas->find()->func()->count('*'),
                'valor' => $this->FiscalNotas->find()->func()->sum('valor_total'),
            ])
            ->where([
                'FiscalNotas.idempresa' => $idempresa,
                'FiscalNotas.status' => 'autorizada',
                'FiscalNotas.data_emissao >=' => date('Y-m-d', strtotime('-6 months')),
            ])
            ->group($mesExpr)
            ->order([$mesExpr => 'ASC'])
            ->disableHydration()
            ->toArray();

        $this->loadModel('FiscalNaturezaOperacao');
        $this->loadModel('FiscalCfop');
        $this->loadModel('FiscalNcm');
        $fiscalHomologacaoChecklist = $this->buildFiscalHomologacaoChecklist(
            $idempresa,
            $configFiscal,
            $certificado,
            $certValido
        );

        $dfeUltNsuSaved = $configFiscal->dfe_ult_nsu;
        $fpmDfeUltNsuLabel = ($dfeUltNsuSaved !== null && $dfeUltNsuSaved !== '')
            ? $this->normalizeDfeNsu((string)$dfeUltNsuSaved)
            : '';

        $dfeFilaContagens = ['pendente' => 0, 'ignorado' => 0, 'vinculado' => 0];
        $dfeFilaRows = $this->FiscalDfeRecebidos->find()
            ->select([
                'status' => 'FiscalDfeRecebidos.status',
                'c' => $this->FiscalDfeRecebidos->find()->func()->count('*'),
            ])
            ->where(['FiscalDfeRecebidos.idempresa' => $idempresa])
            ->group('FiscalDfeRecebidos.status')
            ->disableHydration()
            ->toArray();
        foreach ($dfeFilaRows as $row) {
            $st = (string)($row['status'] ?? '');
            if (isset($dfeFilaContagens[$st])) {
                $dfeFilaContagens[$st] = (int)($row['c'] ?? 0);
            }
        }

        $this->set(compact(
            'totaisPorStatus', 'certificado', 'certValido', 'certDiasRestantes',
            'configFiscal', 'ultimasNotas', 'notasPorMes', 'fiscalHomologacaoChecklist',
            'fpmDfeUltNsuLabel', 'dfeFilaContagens'
        ));
    }

    /**
     * Itens para homologação na SEFAZ (Fase 1): certificado, UF, ambiente, tabelas mínimas.
     *
     * @param int|string $idempresa
     * @param \Cake\Datasource\EntityInterface|object|null $configFiscal
     * @param \Cake\Datasource\EntityInterface|object|null $certificado
     * @param bool $certValido
     * @return array<int, array{level: string, message: string}>
     */
    protected function buildFiscalHomologacaoChecklist($idempresa, $configFiscal, $certificado, $certValido) {
        $items = [];
        if (!$certificado) {
            $items[] = [
                'level' => 'warn',
                'message' => 'Nenhum certificado digital A1 ativo. Instale um certificado para transmitir NF-e.',
            ];
        } elseif (!$certValido) {
            $items[] = [
                'level' => 'warn',
                'message' => 'Certificado vencido ou sem validade conhecida. Renove antes de transmitir em produção.',
            ];
        }
        $uf = $configFiscal ? trim((string)($configFiscal->get('uf') ?? '')) : '';
        if ($uf === '') {
            $items[] = [
                'level' => 'warn',
                'message' => 'UF da empresa não preenchida em Configuração fiscal — necessária para webservices e DANFE.',
            ];
        }
        try {
            $empresaRow = $this->Empresas->get($idempresa, ['fields' => ['id', 'cnpj']]);
            $cnpjLimpo = preg_replace('/\D/', '', (string)($empresaRow->cnpj ?? ''));
            if (strlen($cnpjLimpo) !== 14) {
                $items[] = [
                    'level' => 'warn',
                    'message' => 'CNPJ da empresa incompleto ou ausente no cadastro — obrigatório para NF-e, inutilização e XML.',
                ];
            } elseif (!FiscalValidator::validarCnpj($cnpjLimpo)) {
                $items[] = [
                    'level' => 'warn',
                    'message' => 'CNPJ da empresa não passa na validação de dígitos verificadores — corrija antes de transmitir.',
                ];
            }
        } catch (\Exception $e) {
        }
        $amb = $configFiscal ? (int)($configFiscal->get('ambiente') ?? 2) : 2;
        if ($amb === 1) {
            $items[] = [
                'level' => 'danger',
                'message' => 'Produção SEFAZ na configuração desta empresa (ambiente 1). Cada NF-e transmitida vale fiscalmente.',
            ];
        } elseif ($amb !== 2) {
            $items[] = [
                'level' => 'warn',
                'message' => 'Ambiente da empresa não está em Homologação (2). Confirme o valor antes de transmitir.',
            ];
        }
        $cfgAmb = (int)Configure::read('Fiscal.ambiente', 2);
        if ($cfgAmb === 1) {
            $items[] = [
                'level' => 'danger',
                'message' => 'FISCAL_AMBIENTE=1 (produção global). Garanta certificado, numeração e revisão antes de emitir.',
            ];
        } elseif ($cfgAmb !== 2) {
            $items[] = [
                'level' => 'warn',
                'message' => 'Variável global de ambiente SEFAZ não está em homologação (2).',
            ];
        }
        $retDays = (int)Configure::read('Fiscal.xml_retention_days_hint', 365);
        if ($retDays > 0 && ($cfgAmb === 1 || $amb === 1)) {
            $items[] = [
                'level' => 'info',
                'message' => 'Produção: planeje backup/retenção de XML (sugestão ' . $retDays . ' dias — FISCAL_XML_RETENTION_DAYS). Armazenamento em BD: fiscal_notas_xmls.',
            ];
        }
        try {
            if ($this->FiscalCfop->find()->count() === 0) {
                $items[] = [
                    'level' => 'warn',
                    'message' => 'Nenhum CFOP cadastrado. Use Configuração fiscal → CFOP ou importe a tabela.',
                ];
            }
        } catch (\Exception $e) {
            // Tabela ausente (ex.: SQLite de teste sem fiscal_cfop)
        }
        try {
            if ($this->FiscalNcm->find()->count() === 0) {
                $items[] = [
                    'level' => 'warn',
                    'message' => 'Nenhum NCM cadastrado. Cadastre NCMs de referência em Configuração fiscal.',
                ];
            }
        } catch (\Exception $e) {
        }
        try {
            $nNat = $this->FiscalNaturezaOperacao->find()
                ->where(['idempresa' => $idempresa])
                ->count();
            if ($nNat < 1) {
                $items[] = [
                    'level' => 'warn',
                    'message' => 'Nenhuma natureza de operação para esta empresa. Cadastre em Configuração fiscal → Naturezas.',
                ];
            }
        } catch (\Exception $e) {
        }

        if ($configFiscal && !FiscalRegimeHelper::empresaRegimeNormalProntaParaNfe($configFiscal->toArray())) {
            $items[] = [
                'level' => 'warn',
                'message' => FiscalRegimeHelper::mensagemChecklistHomologacaoRegimeNormalIncompleto(),
            ];
        }

        if (FiscalRegimeHelper::reformaTributariaEstudoIbscbsAtivo()) {
            $items[] = [
                'level' => 'info',
                'message' => 'Modo estudo reforma tributária (IBS/CBS) ativo — o XML da NF-e não é alterado até leiaute oficial.',
            ];
        }

        if ($items === []) {
            $items[] = [
                'level' => 'info',
                'message' => 'Nenhum alerta do checklist. Teste "Status SEFAZ", valide numeração e só então altere ambiente para produção (empresa + FISCAL_AMBIENTE).',
            ];
        }

        return $items;
    }

    /**
     * Consulta status do serviço SEFAZ.
     */
    public function statusSefaz() {
        $this->autoRender = false;
        $this->response = $this->response->withType('application/json');

        $idempresa = $this->Auth->user('idempresa');
        $configFiscal = $this->FiscalEmpresasConfig->getOrCreate($idempresa);
        $certificado = $this->FiscalCertificados->getAtivo($idempresa);

        if (!$certificado) {
            $this->response = $this->response->withStringBody(json_encode([
                'success' => false,
                'message' => 'Certificado digital não configurado.',
            ]));
            return $this->response;
        }

        try {
            $signer = new \App\Utility\Fiscal\FiscalSigner(
                FiscalCertificadoSecret::pfxBytes($certificado),
                FiscalCertificadoSecret::decryptForUse($certificado->senha_hash, $idempresa)
            );
            $client = new \App\Utility\Fiscal\FiscalSefazClient($signer, $configFiscal->toArray());
            $result = $client->statusServico();
            $this->response = $this->response->withStringBody(json_encode($result));
        } catch (\Exception $e) {
            Log::warning('Fiscal statusSefaz: ' . $e->getMessage());
            $this->response = $this->response->withStringBody(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
        }

        return $this->response;
    }

    /**
     * Consulta Distribuição DF-e (serviço nacional) — últimos documentos para o CNPJ da empresa.
     *
     * GET:
     * - sem `ult_nsu`: usa `dfe_ult_nsu` gravado na configuração fiscal (ou 0).
     * - `ult_nsu`: valor explícito (15 dígitos).
     * - `reset_nsu=1`: zera o NSU guardado; neste pedido usa `ult_nsu` se enviado, senão 0.
     */
    public function distribuicaoDfe() {
        $this->request->allowMethod(['get']);
        $this->autoRender = false;
        $this->response = $this->response->withType('application/json');

        $idempresa = $this->Auth->user('idempresa');
        $configFiscal = $this->FiscalEmpresasConfig->getOrCreate($idempresa);
        $resetNsu = (string)$this->request->getQuery('reset_nsu') === '1';
        if ($resetNsu) {
            $configFiscal->dfe_ult_nsu = null;
            $this->FiscalEmpresasConfig->save($configFiscal);
        }

        $qUlt = $this->request->getQuery('ult_nsu');
        if ($qUlt !== null) {
            $reqNsu = $this->normalizeDfeNsu((string)$qUlt);
        } elseif (!$resetNsu) {
            $saved = $configFiscal->dfe_ult_nsu;
            $reqNsu = ($saved !== null && $saved !== '')
                ? $this->normalizeDfeNsu((string)$saved)
                : $this->normalizeDfeNsu('0');
        } else {
            $reqNsu = $this->normalizeDfeNsu('0');
        }

        $certificado = $this->FiscalCertificados->getAtivo($idempresa);

        if (!$certificado) {
            $this->response = $this->response->withStringBody(json_encode([
                'success' => false,
                'message' => 'Certificado digital não configurado.',
            ]));
            return $this->response;
        }

        try {
            $empresa = $this->Empresas->get($idempresa, ['fields' => ['id', 'cnpj']]);
            $cnpj = preg_replace('/\D/', '', (string)($empresa->cnpj ?? ''));
            if (strlen($cnpj) !== 14) {
                $this->response = $this->response->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'CNPJ da empresa inválido para consulta DF-e.',
                ]));
                return $this->response;
            }

            $signer = new \App\Utility\Fiscal\FiscalSigner(
                FiscalCertificadoSecret::pfxBytes($certificado),
                FiscalCertificadoSecret::decryptForUse($certificado->senha_hash, $idempresa)
            );
            $client = new \App\Utility\Fiscal\FiscalSefazClient($signer, $configFiscal->toArray());
            $result = $client->distribuicaoDfeInteresse($cnpj, $reqNsu);
            if (!empty($result['xml_retorno'])) {
                FiscalStorage::writeDistribuicaoArtifact($idempresa, 'retorno', (string)$result['xml_retorno']);
            }

            $preview = FiscalDfeDocZipSummary::summarizeRetornoXml((string)($result['xml_retorno'] ?? ''));

            $out = [
                'success' => !empty($result['success']),
                'codigo' => $result['codigo'] ?? '',
                'mensagem' => $result['mensagem'] ?? '',
                'ult_nsu_solicitado' => $reqNsu,
                'ult_nsu' => $result['ult_nsu'] ?? null,
                'max_nsu' => $result['max_nsu'] ?? null,
                'doc_zip_count' => (int)($result['doc_zip_count'] ?? 0),
                'doc_zip_preview' => $preview,
            ];

            if (!empty($result['success'])) {
                $retUlt = $result['ult_nsu'] ?? null;
                if ($retUlt !== null && $retUlt !== '') {
                    $configFiscal->dfe_ult_nsu = $this->normalizeDfeNsu((string)$retUlt);
                    $this->FiscalEmpresasConfig->save($configFiscal);
                }
                $fresh = $this->FiscalEmpresasConfig->get($configFiscal->id);
                $out['dfe_ult_nsu_guardado'] = $fresh->dfe_ult_nsu;
                $xmlRet = (string)($result['xml_retorno'] ?? '');
                $out['dfe_ingest_count'] = 0;
                if ($xmlRet !== '') {
                    try {
                        $out['dfe_ingest_count'] = $this->FiscalDfeRecebidos->ingestarDeRetornoDistribuicao((int)$idempresa, $xmlRet);
                    } catch (\Throwable $e) {
                        Log::warning('Fiscal DF-e ingest fila: ' . $e->getMessage());
                        $out['dfe_ingest_aviso'] = 'Fila DF-e não atualizada (execute migrations ou verifique o log).';
                    }
                }
            }

            $this->response = $this->response->withStringBody(json_encode($out));
        } catch (\Exception $e) {
            Log::warning('Fiscal distribuicaoDfe: ' . $e->getMessage());
            $this->response = $this->response->withStringBody(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
        }

        return $this->response;
    }

    /**
     * Fila de documentos recebidos via Distribuição DF-e (conferência).
     */
    public function dfeRecebidos() {
        $idempresa = $this->Auth->user('idempresa');
        $statusFiltro = (string)$this->request->getQuery('status', 'pendente');
        $statusList = ['pendente', 'ignorado', 'vinculado', 'todos'];
        if (!in_array($statusFiltro, $statusList, true)) {
            $statusFiltro = 'pendente';
        }

        $chaveFiltro = trim((string)$this->request->getQuery('chave', ''));
        $chaveDigitos = preg_replace('/\D/', '', $chaveFiltro);

        $q = $this->FiscalDfeRecebidos->find()
            ->where(['FiscalDfeRecebidos.idempresa' => $idempresa]);
        if ($statusFiltro !== 'todos') {
            $q->where(['FiscalDfeRecebidos.status' => $statusFiltro]);
        }
        if ($chaveDigitos !== '') {
            $conn = $this->FiscalDfeRecebidos->getConnection();
            $q->where(FiscalSqlConditions::caseInsensitiveLike(
                $conn,
                'FiscalDfeRecebidos.chave_acesso',
                '%' . $chaveDigitos . '%'
            ));
        }
        $dfeRecebidos = $q->order(['FiscalDfeRecebidos.created' => 'DESC'])
            ->limit(200)
            ->toArray();

        $contagens = [
            'pendente' => $this->FiscalDfeRecebidos->find()
                ->where(['idempresa' => $idempresa, 'status' => 'pendente'])->count(),
            'ignorado' => $this->FiscalDfeRecebidos->find()
                ->where(['idempresa' => $idempresa, 'status' => 'ignorado'])->count(),
            'vinculado' => $this->FiscalDfeRecebidos->find()
                ->where(['idempresa' => $idempresa, 'status' => 'vinculado'])->count(),
        ];

        $podeImportarDfeParaEntrada = RbacChecker::podeImportarDfeParaRascunhoEntrada((int)$this->Auth->user('id'));
        $this->set(compact('dfeRecebidos', 'statusFiltro', 'contagens', 'podeImportarDfeParaEntrada', 'chaveFiltro'));
    }

    /**
     * Download do XML decodificado guardado na fila DF-e.
     */
    public function dfeRecebidoXml($id = null) {
        $this->request->allowMethod(['get']);
        $idempresa = $this->Auth->user('idempresa');
        $row = $this->FiscalDfeRecebidos->find()
            ->where(['id' => $id, 'idempresa' => $idempresa])
            ->first();
        if (!$row) {
            $this->Flash->error('Documento não encontrado.');
            return $this->redirect(['action' => 'dfeRecebidos']);
        }

        $this->autoRender = false;
        $chave = $row->chave_acesso ?: ('id' . $row->id);
        $fn = 'DFe_' . preg_replace('/\W/', '_', $chave) . '.xml';
        $this->response = $this->response
            ->withType('application/xml')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $fn . '"')
            ->withStringBody((string)$row->xml_conteudo);

        return $this->response;
    }

    /**
     * Marca registo DF-e como ignorado (conferência).
     */
    public function dfeRecebidoIgnorar($id = null) {
        $this->request->allowMethod(['post']);
        $idempresa = $this->Auth->user('idempresa');
        $row = $this->FiscalDfeRecebidos->find()
            ->where(['id' => $id, 'idempresa' => $idempresa])
            ->first();
        if (!$row) {
            $this->Flash->error('Documento não encontrado.');
            return $this->redirect(['action' => 'dfeRecebidos', '?' => $this->dfeRecebidosListQueryParams()]);
        }
        if ($row->status === 'vinculado') {
            $this->Flash->error('Documento já vinculado a uma nota.');
            return $this->redirect(['action' => 'dfeRecebidos', '?' => $this->dfeRecebidosListQueryParams()]);
        }
        $row->status = 'ignorado';
        if ($this->FiscalDfeRecebidos->save($row)) {
            $this->Flash->success('Documento marcado como ignorado.');
        } else {
            $this->Flash->error('Não foi possível atualizar o registo.');
        }

        return $this->redirect(['action' => 'dfeRecebidos', '?' => $this->dfeRecebidosListQueryParams()]);
    }

    /**
     * Gera rascunho de nota fiscal de entrada a partir do XML guardado na fila DF-e (resNFe / nfeProc).
     */
    public function dfeRecebidoCriarEntrada($id = null) {
        $this->request->allowMethod(['post']);
        $idempresa = (int)$this->Auth->user('idempresa');
        $userId = (int)$this->Auth->user('id');
        $listQuery = $this->dfeRecebidosListQueryParams();
        if (!RbacChecker::podeImportarDfeParaRascunhoEntrada($userId)) {
            $this->Flash->error(
                'Sua função não inclui permissão para criar notas de entrada a partir do DF-e (fiscal.notas_entrada).'
            );
            return $this->redirect(['action' => 'dfeRecebidos', '?' => $listQuery]);
        }
        $dfeId = (int)$id;
        if ($dfeId <= 0) {
            $this->Flash->error('Identificador do documento inválido.');
            return $this->redirect(['action' => 'dfeRecebidos', '?' => $listQuery]);
        }

        $result = FiscalImportacaoDfeEntrada::criarRascunhoDeDfe($idempresa, $userId, $dfeId);

        if (!empty($result['redirect'])) {
            if (!empty($result['flash'])) {
                $this->Flash->warning($result['flash']);
            }
            return $this->redirect($result['redirect']);
        }
        if (empty($result['ok'])) {
            foreach ($result['errors'] ?? ['Não foi possível criar o rascunho.'] as $msg) {
                $this->Flash->error($msg);
            }
            return $this->redirect(['action' => 'dfeRecebidos', '?' => $listQuery]);
        }

        foreach ($result['warnings'] ?? [] as $w) {
            $this->Flash->warning($w);
        }
        $this->Flash->success('Rascunho de nota de entrada criado a partir do DF-e.');
        return $this->redirect([
            'controller' => 'FiscalNotasEntrada',
            'action' => 'edit',
            $result['nota_id'],
        ]);
    }

    /**
     * Consulta Distribuição DF-e por chave (consChNFe) e substitui o XML na fila quando a AN devolver NF-e com infNFe.
     */
    public function dfeRecebidoBaixarCompleto($id = null) {
        $this->request->allowMethod(['post']);
        $idempresa = (int)$this->Auth->user('idempresa');
        $userId = (int)$this->Auth->user('id');
        $listQuery = $this->dfeRecebidosListQueryParams();
        $dfeId = (int)$id;
        if ($dfeId <= 0) {
            $this->Flash->error('Identificador do documento inválido.');
            return $this->redirect(['action' => 'dfeRecebidos', '?' => $listQuery]);
        }

        $row = $this->FiscalDfeRecebidos->find()
            ->where(['id' => $dfeId, 'idempresa' => $idempresa])
            ->first();
        if (!$row) {
            $this->Flash->error('Documento não encontrado.');
            return $this->redirect(['action' => 'dfeRecebidos', '?' => $listQuery]);
        }
        if ($row->status !== 'pendente') {
            $this->Flash->error('Apenas documentos pendentes podem ser atualizados desta forma.');
            return $this->redirect(['action' => 'dfeRecebidos', '?' => $listQuery]);
        }

        $chave = FiscalResNfeImportParser::chaveSeResumoResNfe((string)$row->xml_conteudo);
        if ($chave === null) {
            $this->Flash->error('Este registo não é um resumo resNFe só com chave; use a Distribuição DF-e normal ou importe o XML completo por outro meio.');
            return $this->redirect(['action' => 'dfeRecebidos', '?' => $listQuery]);
        }

        $certificado = $this->FiscalCertificados->getAtivo($idempresa);
        if (!$certificado) {
            $this->Flash->error('Certificado digital não configurado.');
            return $this->redirect(['action' => 'dfeRecebidos', '?' => $listQuery]);
        }

        try {
            $empresa = $this->Empresas->get($idempresa, ['fields' => ['id', 'cnpj']]);
            $cnpj = preg_replace('/\D/', '', (string)($empresa->cnpj ?? ''));
            if (strlen($cnpj) !== 14) {
                $this->Flash->error('CNPJ da empresa inválido para consulta DF-e.');
                return $this->redirect(['action' => 'dfeRecebidos', '?' => $listQuery]);
            }

            $configFiscal = $this->FiscalEmpresasConfig->getOrCreate($idempresa);
            $signer = new \App\Utility\Fiscal\FiscalSigner(
                FiscalCertificadoSecret::pfxBytes($certificado),
                FiscalCertificadoSecret::decryptForUse($certificado->senha_hash, $idempresa)
            );
            $client = new \App\Utility\Fiscal\FiscalSefazClient($signer, $configFiscal->toArray());
            $result = $client->distribuicaoDfePorChaveNfe($cnpj, $chave);
            $xmlRet = (string)($result['xml_retorno'] ?? '');
            if ($xmlRet !== '') {
                FiscalStorage::writeDistribuicaoArtifact($idempresa, 'retorno_chave_' . $chave, $xmlRet);
            }

            if (empty($result['success'])) {
                $motivo = trim((string)($result['mensagem'] ?? 'Falha na consulta por chave.'));
                $cod = (string)($result['codigo'] ?? '');
                $this->Flash->error($cod !== '' ? ($cod . ' — ' . $motivo) : $motivo);
                FiscalAudit::write('warning', 'dfe_fetch_nfe_por_chave', [
                    'idempresa' => $idempresa,
                    'user_id' => $userId,
                    'dfe_id' => $dfeId,
                    'chave_acesso' => $chave,
                    'codigo' => $cod,
                ]);
                return $this->redirect(['action' => 'dfeRecebidos', '?' => $listQuery]);
            }

            $doc = FiscalDfeDocZipSummary::primeiroDocumentoComInfNFe($xmlRet);
            if ($doc === null) {
                $this->Flash->warning(
                    'A Receita respondeu, mas ainda não há XML completo com itens para esta chave. '
                    . 'Confirme manifestação do destinatário (ciência/confirmação) e tente novamente mais tarde.'
                );
                FiscalAudit::write('info', 'dfe_fetch_nfe_por_chave_sem_inf', [
                    'idempresa' => $idempresa,
                    'user_id' => $userId,
                    'dfe_id' => $dfeId,
                    'chave_acesso' => $chave,
                    'doc_zip_count' => (int)($result['doc_zip_count'] ?? 0),
                ]);
                return $this->redirect(['action' => 'dfeRecebidos', '?' => $listQuery]);
            }

            $plain = (string)$doc['xml_plain'];
            $newHash = md5($plain);
            $dup = $this->FiscalDfeRecebidos->find()
                ->where([
                    'idempresa' => $idempresa,
                    'conteudo_hash' => $newHash,
                    'id !=' => $dfeId,
                ])
                ->first();
            if ($dup) {
                $this->Flash->error('O XML completo já existe na fila noutro registo (mesmo conteúdo).');
                return $this->redirect(['action' => 'dfeRecebidos', '?' => $listQuery]);
            }

            $row->xml_conteudo = $plain;
            $row->conteudo_hash = $newHash;
            $row->tipo_documento = (string)($doc['tipo'] ?? 'xml');
            $row->schema = (string)($doc['schema'] ?? '');
            if (!empty($doc['nsu'])) {
                $row->nsu_doc = (string)$doc['nsu'];
            }
            if (!empty($doc['chave'])) {
                $row->chave_acesso = preg_replace('/\D/', '', (string)$doc['chave']);
            }

            if ($this->FiscalDfeRecebidos->save($row)) {
                $this->Flash->success('XML completo obtido na Distribuição DF-e e atualizado na fila.');
                FiscalAudit::write('info', 'dfe_fetch_nfe_por_chave', [
                    'idempresa' => $idempresa,
                    'user_id' => $userId,
                    'dfe_id' => $dfeId,
                    'chave_acesso' => $chave,
                    'novo_tipo' => $row->tipo_documento,
                ]);
            } else {
                $this->Flash->error('Não foi possível gravar o XML atualizado.');
            }
        } catch (\Exception $e) {
            Log::warning('Fiscal dfeRecebidoBaixarCompleto: ' . $e->getMessage());
            $this->Flash->error('Erro ao consultar a SEFAZ: ' . $e->getMessage());
        }

        return $this->redirect(['action' => 'dfeRecebidos', '?' => $listQuery]);
    }

    /**
     * Gestão de contingência fiscal (EPEC / SVC).
     */
    public function contingencia() {
        $idempresa = $this->Auth->user('idempresa');
        $userId = (int)$this->Auth->user('id');
        $configFiscal = $this->FiscalEmpresasConfig->getOrCreate($idempresa);
        $modoAtual = FiscalContingencia::modoAtivo($idempresa);
        $modos = FiscalContingencia::modosDisponiveis();

        if ($this->request->is('post')) {
            $novoModo = $this->request->getData('modo');
            if ($novoModo === '' || $novoModo === null) {
                $novoModo = null;
            }
            $justificativa = trim((string)$this->request->getData('justificativa'));
            if ($novoModo !== null && strlen($justificativa) < 15) {
                $this->Flash->error('Justificativa deve ter pelo menos 15 caracteres para ativar contingência.');
            } else {
                if (FiscalContingencia::setModo($idempresa, $novoModo, $justificativa, $userId)) {
                    if ($novoModo === null) {
                        $this->Flash->success('Contingência desativada. Emissão retornada ao autorizador normal.');
                    } else {
                        $this->Flash->success('Contingência ativada: ' . ($modos[$novoModo] ?? $novoModo));
                    }
                } else {
                    $this->Flash->error('Erro ao alterar modo de contingência.');
                }
                return $this->redirect(['action' => 'contingencia']);
            }
        }

        $this->set(compact('modoAtual', 'modos', 'configFiscal'));
    }

    /**
     * Importação de XMLs em lote (upload de múltiplos arquivos).
     */
    public function importarXmlLote() {
        $idempresa = (int)$this->Auth->user('idempresa');
        $userId = (int)$this->Auth->user('id');
        $resultados = [];

        if ($this->request->is('post')) {
            $files = $this->request->getData('xml_files');
            if (empty($files)) {
                $files = $this->request->getUploadedFiles();
                $files = $files['xml_files'] ?? [];
            }
            if (!is_array($files)) {
                $files = [$files];
            }

            $this->loadModel('FiscalNotas');
            foreach ($files as $file) {
                $item = ['filename' => '', 'ok' => false, 'message' => ''];
                if ($file instanceof \Psr\Http\Message\UploadedFileInterface) {
                    $item['filename'] = $file->getClientFilename() ?? 'desconhecido';
                    if ($file->getError() !== UPLOAD_ERR_OK) {
                        $item['message'] = 'Erro no upload.';
                        $resultados[] = $item;
                        continue;
                    }
                    $xmlContent = (string)$file->getStream();
                } elseif (is_array($file) && !empty($file['tmp_name'])) {
                    $item['filename'] = $file['name'] ?? 'desconhecido';
                    if (($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
                        $item['message'] = 'Erro no upload.';
                        $resultados[] = $item;
                        continue;
                    }
                    $xmlContent = @file_get_contents($file['tmp_name']);
                } else {
                    continue;
                }

                if (empty($xmlContent)) {
                    $item['message'] = 'Arquivo vazio.';
                    $resultados[] = $item;
                    continue;
                }

                $result = FiscalImportacaoDfeEntrada::criarRascunhoDeXmlString($idempresa, $userId, $xmlContent);
                if (!empty($result['ok'])) {
                    $item['ok'] = true;
                    $item['message'] = 'Nota de entrada #' . ($result['nota_id'] ?? '') . ' criada.';
                } else {
                    $item['message'] = implode(' | ', $result['errors'] ?? ['Falha na importação.']);
                }
                $resultados[] = $item;
            }

            if (empty($resultados)) {
                $this->Flash->error('Nenhum arquivo enviado.');
            }
        }

        $this->set(compact('resultados'));
    }

    /**
     * Expressão SQL portável para agrupar por ano-mês (dashboard).
     *
     * @param string $qualifiedColumn ex.: FiscalNotas.data_emissao
     * @return string
     */
    protected function fiscalSqlMesAnoAgrupamento($qualifiedColumn) {
        $driver = $this->FiscalNotas->getConnection()->getDriver();
        if ($driver instanceof Postgres) {
            return "TO_CHAR({$qualifiedColumn}, 'YYYY-MM')";
        }
        $driverClass = get_class($driver);
        if (stripos($driverClass, 'Mysql') !== false) {
            return "DATE_FORMAT({$qualifiedColumn}, '%Y-%m')";
        }

        return "strftime('%Y-%m', {$qualifiedColumn})";
    }

    /**
     * NSU da Distribuição DF-e — 15 dígitos (padding à esquerda).
     *
     * @param string $raw
     * @return string
     */
    protected function normalizeDfeNsu($raw) {
        $d = preg_replace('/\D/', '', (string)$raw);

        return str_pad(substr($d, 0, 15), 15, '0', STR_PAD_LEFT);
    }

    /**
     * Query string para voltar à lista DF-e (status + chave opcional do pedido).
     *
     * @return array<string, string>
     */
    protected function dfeRecebidosListQueryParams(): array {
        $q = ['status' => (string)$this->request->getQuery('status', 'pendente')];
        $ch = trim((string)$this->request->getQuery('chave', ''));
        if ($ch !== '') {
            $q['chave'] = $ch;
        }

        return $q;
    }
}
