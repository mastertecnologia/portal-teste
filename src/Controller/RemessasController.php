<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Service\Financeiro\CnabService;
use Cake\Event\Event;
use Cake\Filesystem\Folder;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

/**
 * API de remessas CNAB para o módulo financeiro.
 *
 * Premissas do projeto:
 * - Usa `financeiro_lancamentos` como base dos títulos
 * - Usa `financeiro_bancos` como cadastro bancário
 * - Persiste histórico em `financeiro_remessas` e `financeiro_remessa_titulos`
 * - Respostas JSON via AppController::jsonResponse()
 */
class RemessasController extends AppController
{
    /**
     * @var \App\Service\Financeiro\CnabService
     */
    protected $CnabService;

    public function initialize()
    {
        parent::initialize();

        $this->loadModel('FinanceiroLancamentos');
        $this->loadModel('FinanceiroBancos');
        $this->loadModel('Empresas');
        $this->loadModel('Users');

        try {
            $this->loadModel('FinanceiroRemessas');
        } catch (\Throwable $e) {
            $this->FinanceiroRemessas = TableRegistry::getTableLocator()->get('FinanceiroRemessas');
        }

        try {
            $this->loadModel('FinanceiroRemessaTitulos');
        } catch (\Throwable $e) {
            $this->FinanceiroRemessaTitulos = TableRegistry::getTableLocator()->get('FinanceiroRemessaTitulos');
        }

        $this->CnabService = new CnabService();
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->set('title', 'Remessas CNAB');
    }

    public function isAuthorized($user)
    {
        if ((int)($user['role'] ?? 1) !== 0) {
            return false;
        }

        return parent::isAuthorized($user);
    }

    /**
     * Endpoint JSON:
     * Lista títulos elegíveis para remessa.
     *
     * Parâmetros aceitos:
     * - empresas[] / empresas
     * - banco_id
     * - multiempresa (1/0)
     * - q
     *
     * Regras:
     * - baseia-se em `financeiro_lancamentos`
     * - considera apenas receitas
     * - considera status financeiro "aberto"
     * - exclui títulos já liquidados/cancelados na cobrança
     *
     * @return \Cake\Http\Response
     */
    public function listarTitulos()
    {
        $this->request->allowMethod(['get']);
        $this->autoRender = false;

        $empresaAtual = (int)$this->Auth->user('idempresa');
        $empresasSelecionadas = $this->_parseEmpresaIds(
            $this->request->getQuery('empresas'),
            $empresaAtual,
        );
        $bancoId = (int)$this->request->getQuery('banco_id', 0);
        $q = trim((string)$this->request->getQuery('q', ''));

        $conditions = [
            'FinanceiroLancamentos.idempresa IN' => $empresasSelecionadas,
            'FinanceiroLancamentos.tipo' => 'receita',
            'FinanceiroLancamentos.status' => 'aberto',
        ];

        if ($bancoId > 0) {
            $conditions['FinanceiroLancamentos.financeiro_banco_id'] = $bancoId;
        }

        $conditions[] = function ($exp) {
            return $exp->notIn('FinanceiroLancamentos.status_cobranca', [
                'liquidado',
                'baixado',
                'cancelado',
            ]);
        };

        if ($q !== '') {
            $conditions['OR'] = [
                'FinanceiroLancamentos.descricao ILIKE' => '%' . $q . '%',
                'FinanceiroLancamentos.nosso_numero ILIKE' => '%' . $q . '%',
            ];

            if (preg_match('/^\d+$/', $q)) {
                $conditions['OR']['FinanceiroLancamentos.id'] = (int)$q;
            }
        }

        $rows = $this->FinanceiroLancamentos
            ->find()
            ->where($conditions)
            ->contain([
                'Clientes' => [
                    'fields' => ['id', 'nome', 'razaosocial', 'tipo'],
                ],
                'FinanceiroBancos' => [
                    'fields' => [
                        'id',
                        'idempresa',
                        'codigo_banco',
                        'nome',
                        'numero_agencia',
                        'digito_agencia',
                        'numero_conta',
                        'digito_conta',
                        'convenio',
                        'carteira',
                        'cnab_tipo',
                    ],
                ],
            ])
            ->order([
                'FinanceiroLancamentos.idempresa' => 'ASC',
                'FinanceiroLancamentos.financeiro_banco_id' => 'ASC',
                'FinanceiroLancamentos.data_vencimento' => 'ASC',
                'FinanceiroLancamentos.id' => 'ASC',
            ])
            ->toArray();

        $items = [];
        $totais = [
            'titulos' => 0,
            'valor_total' => 0.0,
        ];

        foreach ($rows as $row) {
            $clienteNome = '—';
            if (!empty($row->cliente)) {
                $clienteNome = ((int)($row->cliente->tipo ?? 0) === 1)
                    ? (string)($row->cliente->nome ?? '—')
                    : (string)($row->cliente->razaosocial ?? '—');
            }

            $statusCobranca = (string)($row->status_cobranca ?? 'sem_cobranca');
            $elegivel = $this->_tituloElegivelRemessa($row);

            $items[] = [
                'id' => (int)$row->id,
                'empresa_id' => (int)$row->idempresa,
                'banco_id' => (int)($row->financeiro_banco_id ?? 0),
                'banco' => [
                    'id' => (int)($row->financeiro_banco->id ?? 0),
                    'codigo_banco' => (string)($row->financeiro_banco->codigo_banco ?? ''),
                    'nome' => (string)($row->financeiro_banco->nome ?? ''),
                    'conta' => $this->_formatarContaBanco($row->financeiro_banco),
                    'convenio' => (string)($row->financeiro_banco->convenio ?? ''),
                    'carteira' => (string)($row->financeiro_banco->carteira ?? ''),
                    'cnab_tipo' => (string)($row->financeiro_banco->cnab_tipo ?? '240'),
                ],
                'cliente' => [
                    'id' => (int)($row->cliente->id ?? 0),
                    'nome' => $clienteNome,
                ],
                'descricao' => (string)($row->descricao ?? ''),
                'nosso_numero' => (string)($row->nosso_numero ?? ''),
                'valor' => (float)$row->valor,
                'data_vencimento' => !empty($row->data_vencimento)
                    ? $row->data_vencimento->format('Y-m-d')
                    : null,
                'status' => (string)($row->status ?? ''),
                'status_cobranca' => $statusCobranca,
                'elegivel' => $elegivel,
                'motivo_bloqueio' => $elegivel ? null : $this->_motivoBloqueioTitulo($row),
            ];

            $totais['titulos']++;
            $totais['valor_total'] += (float)$row->valor;
        }

        return $this->jsonResponse([
            'ok' => true,
            'data' => [
                'items' => $items,
                'totais' => $totais,
                'filtros' => [
                    'empresas' => $empresasSelecionadas,
                    'banco_id' => $bancoId > 0 ? $bancoId : null,
                    'q' => $q,
                ],
            ],
        ], 200);
    }

    /**
     * Endpoint JSON:
     * Gera remessa simples ou multiempresas.
     *
     * Payload aceito:
     * - titulo_ids: array<int>
     * - empresas: array<int> (opcional, informativo)
     * - multiempresa: bool
     * - banco_id: int (opcional)
     * - observacoes: string (opcional)
     *
     * Regras:
     * - agrupa por banco
     * - em modo multiempresa permite empresas diferentes no mesmo arquivo
     *   quando o convênio/carteira/layout coincidirem
     * - gera `nosso_numero` se estiver vazio
     *
     * @return \Cake\Http\Response
     */
    public function gerarRemessa()
    {
        $this->request->allowMethod(['post']);
        $this->autoRender = false;

        $empresaAtual = (int)$this->Auth->user('idempresa');
        $usuarioId = (int)$this->Auth->user('id');

        $tituloIds = array_values(array_filter(array_map(
            'intval',
            (array)$this->request->getData('titulo_ids', []),
        )));

        if (empty($tituloIds)) {
            return $this->jsonResponse([
                'ok' => false,
                'error' => 'Nenhum título informado para remessa.',
            ], 400);
        }

        $multiempresa = (bool)$this->request->getData('multiempresa', false);
        $bancoIdPayload = (int)$this->request->getData('banco_id', 0);
        $observacoes = trim((string)$this->request->getData('observacoes', ''));

        $rows = $this->FinanceiroLancamentos
            ->find()
            ->where([
                'FinanceiroLancamentos.id IN' => $tituloIds,
                'FinanceiroLancamentos.tipo' => 'receita',
            ])
            ->contain([
                'Clientes',
                'FinanceiroBancos',
            ])
            ->order([
                'FinanceiroLancamentos.idempresa' => 'ASC',
                'FinanceiroLancamentos.financeiro_banco_id' => 'ASC',
                'FinanceiroLancamentos.data_vencimento' => 'ASC',
                'FinanceiroLancamentos.id' => 'ASC',
            ])
            ->toArray();

        if (empty($rows)) {
            return $this->jsonResponse([
                'ok' => false,
                'error' => 'Nenhum título elegível foi encontrado.',
            ], 404);
        }

        $empresasTitulos = array_values(array_unique(array_map(function ($row) {
            return (int)$row->idempresa;
        }, $rows)));

        if (!$multiempresa && count($empresasTitulos) > 1) {
            return $this->jsonResponse([
                'ok' => false,
                'error' => 'A remessa simples aceita títulos de apenas uma empresa.',
            ], 400);
        }

        foreach ($rows as $row) {
            if (!$multiempresa && (int)$row->idempresa !== $empresaAtual) {
                return $this->jsonResponse([
                    'ok' => false,
                    'error' => 'A remessa simples deve usar apenas títulos da empresa atual.',
                ], 403);
            }
        }

        $invalidos = [];
        foreach ($rows as $row) {
            if ($bancoIdPayload > 0 && (int)($row->financeiro_banco_id ?? 0) !== $bancoIdPayload) {
                $invalidos[] = [
                    'id' => (int)$row->id,
                    'motivo' => 'Título não pertence ao banco informado.',
                ];
                continue;
            }

            if (!$this->_tituloElegivelRemessa($row)) {
                $invalidos[] = [
                    'id' => (int)$row->id,
                    'motivo' => $this->_motivoBloqueioTitulo($row),
                ];
            }
        }

        if (!empty($invalidos)) {
            return $this->jsonResponse([
                'ok' => false,
                'error' => 'Existem títulos inválidos para remessa.',
                'items' => $invalidos,
            ], 422);
        }

        $grupos = $this->_agruparTitulosParaRemessa($rows, $multiempresa);
        if (empty($grupos)) {
            return $this->jsonResponse([
                'ok' => false,
                'error' => 'Não foi possível montar os grupos de remessa.',
            ], 422);
        }

        $resultados = [];

        foreach ($grupos as $grupo) {
            $banco = $grupo['banco'];
            $titulos = $grupo['titulos'];
            $empresaPrincipal = (int)$grupo['empresa_principal'];
            $empresasGrupo = $grupo['empresas'];

            $sequencial = $this->_proximoSequencialRemessa($banco);
            $layout = (string)($banco->cnab_tipo ?? '240');

            $titulosPayload = [];
            foreach ($titulos as $titulo) {
                $nossoNumero = trim((string)($titulo->nosso_numero ?? ''));
                if ($nossoNumero === '') {
                    $nossoNumero = $this->CnabService->gerarNossoNumero([
                        'banco' => $banco,
                        'titulo' => $titulo,
                        'sequencial' => $sequencial,
                    ]);

                    $titulo->nosso_numero = $nossoNumero;
                    $this->FinanceiroLancamentos->save($titulo);
                }

                $titulosPayload[] = [
                    'id' => (int)$titulo->id,
                    'empresa_id' => (int)$titulo->idempresa,
                    'descricao' => (string)($titulo->descricao ?? ''),
                    'nosso_numero' => $nossoNumero,
                    'valor' => (float)$titulo->valor,
                    'data_vencimento' => !empty($titulo->data_vencimento)
                        ? $titulo->data_vencimento->format('Ymd')
                        : date('Ymd'),
                    'numero_documento' => (string)$titulo->id,
                    'cliente' => $this->_clientePayload($titulo),
                ];
            }

            $arquivoConteudo = $this->CnabService->gerarArquivoRemessa([
                'layout' => $layout,
                'banco' => [
                    'codigo_banco' => (string)($banco->codigo_banco ?? ''),
                    'nome' => (string)($banco->nome ?? ''),
                    'agencia' => (string)($banco->numero_agencia ?? ''),
                    'agencia_digito' => (string)($banco->digito_agencia ?? ''),
                    'conta' => (string)($banco->numero_conta ?? ''),
                    'conta_digito' => (string)($banco->digito_conta ?? ''),
                    'convenio' => (string)($banco->convenio ?? ''),
                    'carteira' => (string)($banco->carteira ?? ''),
                ],
                'empresa' => [
                    'id' => $empresaPrincipal,
                    'nome' => $this->_nomeEmpresa($empresaPrincipal),
                ],
                'sequencial_arquivo' => $sequencial,
                'data_geracao' => date('Ymd'),
                'titulos' => $titulosPayload,
            ]);

            $nomeArquivo = $this->_nomeArquivoRemessa($banco, $sequencial, $multiempresa);
            $caminhoRelativo = 'files/remessas/' . $nomeArquivo;
            $caminhoFisico = WWW_ROOT . $caminhoRelativo;

            $folder = new Folder(dirname($caminhoFisico), true, 0755);
            unset($folder);

            file_put_contents($caminhoFisico, $arquivoConteudo);

            $remessa = $this->FinanceiroRemessas->newEntity([
                'idempresa' => $empresaPrincipal,
                'financeiro_banco_id' => (int)$banco->id,
                'usuario_id' => $usuarioId > 0 ? $usuarioId : null,
                'cnab_layout' => $layout,
                'sequencial_arquivo' => $sequencial,
                'numero_remessa' => str_pad((string)$sequencial, 6, '0', STR_PAD_LEFT),
                'data_geracao' => date('Y-m-d'),
                'status' => 'gerada',
                'nome_arquivo' => $nomeArquivo,
                'caminho_arquivo' => $caminhoRelativo,
                'quantidade_titulos' => count($titulos),
                'valor_total' => array_reduce($titulos, function ($carry, $item) {
                    return $carry + (float)$item->valor;
                }, 0.0),
                'observacoes' => $observacoes,
            ]);

            if (!$this->FinanceiroRemessas->save($remessa)) {
                return $this->jsonResponse([
                    'ok' => false,
                    'error' => 'Falha ao registrar a remessa gerada.',
                    'fields' => $remessa->getErrors(),
                ], 422);
            }

            foreach ($titulos as $titulo) {
                $itemRemessa = $this->FinanceiroRemessaTitulos->newEntity([
                    'financeiro_remessa_id' => (int)$remessa->id,
                    'financeiro_lancamento_id' => (int)$titulo->id,
                    'nosso_numero_remessa' => (string)$titulo->nosso_numero,
                    'numero_documento' => (string)$titulo->id,
                    'valor_titulo' => (float)$titulo->valor,
                    'data_vencimento' => !empty($titulo->data_vencimento)
                        ? $titulo->data_vencimento->format('Y-m-d')
                        : null,
                    'status_item' => 'incluido',
                ]);
                $this->FinanceiroRemessaTitulos->save($itemRemessa);

                $titulo->status_cobranca = 'remetido';
                $titulo->codigo_rejeicao = null;
                $titulo->mensagem_rejeicao = null;
                $this->FinanceiroLancamentos->save($titulo);
            }

            $banco->proxima_remessa = $sequencial + 1;
            $this->FinanceiroBancos->save($banco);

            $resultados[] = [
                'remessa_id' => (int)$remessa->id,
                'banco_id' => (int)$banco->id,
                'banco' => (string)$banco->nome,
                'empresas' => $empresasGrupo,
                'multiempresa' => $multiempresa,
                'layout' => $layout,
                'arquivo' => [
                    'nome' => $nomeArquivo,
                    'caminho' => '/' . str_replace('\\', '/', $caminhoRelativo),
                ],
                'quantidade_titulos' => (int)$remessa->quantidade_titulos,
                'valor_total' => (float)$remessa->valor_total,
            ];
        }

        return $this->jsonResponse([
            'ok' => true,
            'data' => [
                'items' => $resultados,
                'multiempresa' => $multiempresa,
            ],
        ], 200);
    }

    /**
     * Converte entrada de empresas em array de IDs válidos.
     *
     * @param mixed $raw
     * @param int $empresaAtual
     * @return array
     */
    protected function _parseEmpresaIds($raw, $empresaAtual)
    {
        $ids = [];

        if (is_array($raw)) {
            $ids = $raw;
        } elseif ($raw !== null && $raw !== '') {
            $ids = explode(',', (string)$raw);
        }

        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            $ids = [$empresaAtual];
        }

        return array_values(array_unique($ids));
    }

    /**
     * Verifica se o lançamento pode ir para remessa.
     *
     * @param object $titulo
     * @return bool
     */
    protected function _tituloElegivelRemessa($titulo)
    {
        if ((string)($titulo->tipo ?? '') !== 'receita') {
            return false;
        }

        if ((string)($titulo->status ?? '') !== 'aberto') {
            return false;
        }

        if (empty($titulo->financeiro_banco_id) || empty($titulo->financeiro_banco)) {
            return false;
        }

        $statusCobranca = (string)($titulo->status_cobranca ?? 'sem_cobranca');
        if (in_array($statusCobranca, ['liquidado', 'baixado', 'cancelado'], true)) {
            return false;
        }

        return true;
    }

    /**
     * Explica por que um título está bloqueado.
     *
     * @param object $titulo
     * @return string
     */
    protected function _motivoBloqueioTitulo($titulo)
    {
        if ((string)($titulo->tipo ?? '') !== 'receita') {
            return 'Somente receitas podem ser enviadas em remessa.';
        }

        if ((string)($titulo->status ?? '') !== 'aberto') {
            return 'Somente títulos financeiros com status aberto podem ser enviados.';
        }

        if (empty($titulo->financeiro_banco_id) || empty($titulo->financeiro_banco)) {
            return 'Título sem banco vinculado.';
        }

        $statusCobranca = (string)($titulo->status_cobranca ?? 'sem_cobranca');
        if (in_array($statusCobranca, ['liquidado', 'baixado'], true)) {
            return 'Título já liquidado/baixado na cobrança.';
        }

        if ($statusCobranca === 'cancelado') {
            return 'Título cancelado na cobrança.';
        }

        return 'Título não elegível para remessa.';
    }

    /**
     * Agrupa títulos por banco, com suporte a multiempresa.
     *
     * @param array $rows
     * @param bool $multiempresa
     * @return array
     */
    protected function _agruparTitulosParaRemessa(array $rows, $multiempresa)
    {
        $grupos = [];

        foreach ($rows as $row) {
            $banco = $row->financeiro_banco;
            if (empty($banco)) {
                continue;
            }

            $chave = [
                (int)$banco->id,
                (string)($banco->convenio ?? ''),
                (string)($banco->carteira ?? ''),
                (string)($banco->cnab_tipo ?? '240'),
            ];

            if (!$multiempresa) {
                $chave[] = (int)$row->idempresa;
            }

            $key = implode('|', $chave);

            if (!isset($grupos[$key])) {
                $grupos[$key] = [
                    'banco' => $banco,
                    'titulos' => [],
                    'empresas' => [],
                    'empresa_principal' => (int)$row->idempresa,
                ];
            }

            $grupos[$key]['titulos'][] = $row;
            $grupos[$key]['empresas'][(int)$row->idempresa] = (int)$row->idempresa;
        }

        foreach ($grupos as &$grupo) {
            $grupo['empresas'] = array_values($grupo['empresas']);
            sort($grupo['empresas']);
        }

        return array_values($grupos);
    }

    /**
     * Obtém o próximo sequencial de remessa do banco.
     *
     * @param object $banco
     * @return int
     */
    protected function _proximoSequencialRemessa($banco)
    {
        $sequencial = (int)($banco->proxima_remessa ?? 1);
        return $sequencial > 0 ? $sequencial : 1;
    }

    /**
     * Payload resumido do cliente para geração CNAB.
     *
     * @param object $titulo
     * @return array
     */
    protected function _clientePayload($titulo)
    {
        $cliente = $titulo->cliente ?? null;

        return [
            'id' => (int)($cliente->id ?? 0),
            'nome' => (string)(
                ((int)($cliente->tipo ?? 0) === 1)
                    ? ($cliente->nome ?? '')
                    : ($cliente->razaosocial ?? '')
            ),
        ];
    }

    /**
     * Nome da empresa para header.
     *
     * @param int $empresaId
     * @return string
     */
    protected function _nomeEmpresa($empresaId)
    {
        if ((int)$empresaId <= 0) {
            return 'EMPRESA';
        }

        try {
            $empresa = $this->Empresas->get((int)$empresaId);
            return (string)($empresa->nomefantasia ?? $empresa->razaosocial ?? 'EMPRESA');
        } catch (\Throwable $e) {
            return 'EMPRESA';
        }
    }

    /**
     * Monta nome do arquivo remessa.
     *
     * @param object $banco
     * @param int $sequencial
     * @param bool $multiempresa
     * @return string
     */
    protected function _nomeArquivoRemessa($banco, $sequencial, $multiempresa)
    {
        $codigoBanco = preg_replace('/\D+/', '', (string)($banco->codigo_banco ?? '000')) ?: '000';
        $prefixo = $multiempresa ? 'REM_MULTI' : 'REM';
        $data = date('Ymd_His');

        return sprintf(
            '%s_%s_%06d_%s.REM',
            $prefixo,
            str_pad($codigoBanco, 3, '0', STR_PAD_LEFT),
            (int)$sequencial,
            $data,
        );
    }

    /**
     * Formata conta bancária para retorno JSON.
     *
     * @param object|null $banco
     * @return string
     */
    protected function _formatarContaBanco($banco)
    {
        if (empty($banco)) {
            return '';
        }

        $ag = trim((string)($banco->numero_agencia ?? ''));
        $agdv = trim((string)($banco->digito_agencia ?? ''));
        $cc = trim((string)($banco->numero_conta ?? ''));
        $ccdv = trim((string)($banco->digito_conta ?? ''));

        $agFmt = $ag !== '' ? $ag . ($agdv !== '' ? '-' . $agdv : '') : '';
        $ccFmt = $cc !== '' ? $cc . ($ccdv !== '' ? '-' . $ccdv : '') : '';

        if ($agFmt !== '' && $ccFmt !== '') {
            return $agFmt . ' / ' . $ccFmt;
        }

        return trim($agFmt . ' ' . $ccFmt);
    }
}
