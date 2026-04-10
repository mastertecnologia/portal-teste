<?php
namespace App\Utility\Fiscal;

use Cake\ORM\TableRegistry;

/**
 * Cria rascunho de nota fiscal de entrada (tipo_operacao = 0) a partir de XML na fila fiscal_dfe_recebidos.
 */
class FiscalImportacaoDfeEntrada {

    /**
     * @return array{
     *   ok: bool,
     *   nota_id?: int|null,
     *   errors?: string[],
     *   warnings?: string[],
     *   redirect?: array<string, mixed>,
     *   flash?: string
     * }
     */
    public static function criarRascunhoDeDfe(int $idempresa, int $userId, int $dfeId): array {
        $Dfe = TableRegistry::get('FiscalDfeRecebidos');
        $row = $Dfe->find()->where(['id' => $dfeId, 'idempresa' => $idempresa])->first();
        if (!$row) {
            return ['ok' => false, 'errors' => ['Documento DF-e não encontrado.']];
        }
        if ($row->status !== 'pendente') {
            return ['ok' => false, 'errors' => ['Apenas documentos pendentes podem gerar rascunho de entrada.']];
        }

        $parsed = FiscalResNfeImportParser::parse((string)$row->xml_conteudo);
        if ($parsed['dados'] === null) {
            return ['ok' => false, 'errors' => $parsed['erros']];
        }
        $d = $parsed['dados'];
        $warnings = [];

        $Empresas = TableRegistry::get('Empresas');
        $empresa = $Empresas->get($idempresa);
        $empCnpj = preg_replace('/\D/', '', (string)$empresa->cnpj);
        if ($empCnpj !== '' && !empty($d['dest_cnpj']) && strlen((string)$d['dest_cnpj']) === 14) {
            if ($empCnpj !== preg_replace('/\D/', '', (string)$d['dest_cnpj'])) {
                return [
                    'ok' => false,
                    'errors' => ['O destinatário da NF-e (XML) não corresponde ao CNPJ da empresa logada.'],
                ];
            }
        } elseif ($empCnpj !== '' && empty($d['dest_cnpj'])) {
            $warnings[] = 'XML sem CNPJ de destinatário: não foi possível validar se a nota é para esta empresa.';
        }

        $Notas = TableRegistry::get('FiscalNotas');
        $existente = $Notas->find()
            ->select(['id', 'tipo_operacao', 'status'])
            ->where([
                'idempresa' => $idempresa,
                'chave_acesso' => $d['chave_acesso'],
            ])
            ->first();
        if ($existente) {
            $ctrl = (int)$existente->tipo_operacao === 0 ? 'FiscalNotasEntrada' : 'FiscalNotas';
            $act = in_array((string)$existente->status, ['rascunho', 'rejeitada'], true) ? 'edit' : 'view';

            return [
                'ok' => false,
                'redirect' => ['controller' => $ctrl, 'action' => $act, $existente->id],
                'flash' => 'Já existe nota com esta chave de acesso no portal.',
            ];
        }

        $emitCnpj = (string)$d['emit_cnpj'];
        $idcliente = self::localizarClientePorCnpj($idempresa, $emitCnpj);
        if ($idcliente === null) {
            $warnings[] = 'Fornecedor (emitente do XML) não encontrado como cliente cadastrado: vincule o cliente manualmente no rascunho.';
        }

        $Nat = TableRegistry::get('FiscalNaturezaOperacao');
        $listaNat = $Nat->listByEmpresa($idempresa, 'entrada');
        $natId = null;
        $natStr = (string)$d['natureza_operacao'];
        if ($listaNat !== []) {
            $natId = (int)array_key_first($listaNat);
            $natStr = (string)reset($listaNat);
        }

        $numero = $d['numero'];
        $serie = (int)$d['serie'];
        $modelo = (string)$d['modelo'];
        if ($numero !== null) {
            $dupNum = $Notas->find()
                ->select(['id'])
                ->where([
                    'idempresa' => $idempresa,
                    'modelo' => $modelo,
                    'serie' => $serie,
                    'numero' => $numero,
                ])
                ->first();
            if ($dupNum) {
                $numero = null;
                $warnings[] = 'Numeração do XML já existe no portal: o número da nota foi deixado em branco no rascunho (ajuste antes de emitir).';
            }
        }

        $itensRows = [];
        foreach ($d['itens'] as $it) {
            $itensRows[] = [
                'numero_item' => (int)$it['numero_item'],
                'codigo_produto' => $it['codigo_produto'],
                'descricao' => $it['descricao'],
                'ncm' => $it['ncm'],
                'cfop' => $it['cfop'],
                'unidade' => $it['unidade'],
                'quantidade' => $it['quantidade'],
                'valor_unitario' => $it['valor_unitario'],
                'valor_total' => $it['valor_total'],
            ];
        }

        $data = [
            'idempresa' => $idempresa,
            'user_id' => $userId,
            'tipo_operacao' => 0,
            'status' => 'rascunho',
            'modelo' => $modelo,
            'serie' => $serie,
            'numero' => $numero,
            'chave_acesso' => $d['chave_acesso'],
            'natureza_operacao' => $natStr,
            'natureza_operacao_id' => $natId,
            'finalidade' => 1,
            'presenca' => 9,
            'data_emissao' => $d['data_emissao'],
            'informacoes_complementares' => $d['informacoes_complementares'],
            'protocolo_autorizacao' => $d['protocolo_autorizacao'],
            'idcliente' => $idcliente,
            'fiscal_notas_itens' => $itensRows,
        ];

        $nota = $Notas->newEntity();
        $nota = $Notas->patchEntity($nota, $data, [
            'associated' => [
                'FiscalNotasItens' => ['associated' => ['FiscalNotasItensSeries']],
            ],
        ]);

        $Cfg = TableRegistry::get('FiscalEmpresasConfig');
        $Aliq = TableRegistry::get('FiscalAliquotas');
        $nota = FiscalNotaRecalculo::aplicar($nota, $idempresa, $Cfg, $Aliq);

        $Xmls = TableRegistry::get('FiscalNotasXmls');
        $notaId = null;
        $conn = $Notas->getConnection();

        try {
            $conn->transactional(function () use (
                $Notas,
                $nota,
                $Xmls,
                $Dfe,
                $row,
                &$notaId
            ) {
                if (!$Notas->save($nota, [
                    'associated' => [
                        'FiscalNotasItens' => ['associated' => ['FiscalNotasItensSeries']],
                    ],
                ])) {
                    throw new \RuntimeException(self::formatEntityErrors($nota));
                }
                $notaId = (int)$nota->id;

                $xmlEnt = $Xmls->newEntity([
                    'fiscal_nota_id' => $notaId,
                    'tipo' => 'dfe_import',
                    'xml_retorno' => (string)$row->xml_conteudo,
                ]);
                if (!$Xmls->save($xmlEnt)) {
                    throw new \RuntimeException('Não foi possível gravar o XML de referência na nota.');
                }

                $row->status = 'vinculado';
                $row->fiscal_nota_id = $notaId;
                if (!$Dfe->save($row)) {
                    throw new \RuntimeException('Não foi possível atualizar o registo na fila DF-e.');
                }
            });
        } catch (\Throwable $e) {
            return ['ok' => false, 'errors' => [$e->getMessage()]];
        }

        FiscalAudit::write('info', 'dfe_import_entrada', [
            'idempresa' => $idempresa,
            'user_id' => $userId,
            'dfe_id' => (int)$row->id,
            'fiscal_nota_id' => $notaId,
            'chave_acesso' => $d['chave_acesso'],
        ]);

        return [
            'ok' => true,
            'nota_id' => $notaId,
            'warnings' => $warnings,
        ];
    }

    /**
     * Cria rascunho de nota de entrada a partir de uma string XML bruta (upload direto).
     * Usa o mesmo parser do DF-e mas sem depender da fila fiscal_dfe_recebidos.
     *
     * @return array{ok: bool, nota_id?: int|null, errors?: string[], warnings?: string[]}
     */
    public static function criarRascunhoDeXmlString(int $idempresa, int $userId, string $xmlContent): array {
        $parsed = FiscalResNfeImportParser::parse($xmlContent);
        if ($parsed['dados'] === null) {
            return ['ok' => false, 'errors' => $parsed['erros']];
        }
        $d = $parsed['dados'];
        $warnings = [];

        $Empresas = TableRegistry::get('Empresas');
        $empresa = $Empresas->get($idempresa);
        $empCnpj = preg_replace('/\D/', '', (string)$empresa->cnpj);
        if ($empCnpj !== '' && !empty($d['dest_cnpj']) && strlen((string)$d['dest_cnpj']) === 14) {
            if ($empCnpj !== preg_replace('/\D/', '', (string)$d['dest_cnpj'])) {
                $warnings[] = 'Destinatário da NF-e não corresponde ao CNPJ da empresa: verifique se está correto.';
            }
        }

        $Notas = TableRegistry::get('FiscalNotas');
        if (!empty($d['chave_acesso'])) {
            $existente = $Notas->find()
                ->select(['id'])
                ->where(['idempresa' => $idempresa, 'chave_acesso' => $d['chave_acesso']])
                ->first();
            if ($existente) {
                return ['ok' => false, 'errors' => ['Nota com esta chave de acesso já existe (ID: ' . $existente->id . ').']];
            }
        }

        $idcliente = self::localizarClientePorCnpj($idempresa, (string)$d['emit_cnpj']);
        if ($idcliente === null) {
            $warnings[] = 'Fornecedor (emitente) não encontrado como cliente: vincule manualmente.';
        }

        $Nat = TableRegistry::get('FiscalNaturezaOperacao');
        $listaNat = $Nat->listByEmpresa($idempresa, 'entrada');
        $natId = $listaNat !== [] ? (int)array_key_first($listaNat) : null;
        $natStr = $listaNat !== [] ? (string)reset($listaNat) : (string)$d['natureza_operacao'];

        $itensRows = [];
        foreach ($d['itens'] as $it) {
            $itensRows[] = [
                'numero_item' => (int)$it['numero_item'],
                'codigo_produto' => $it['codigo_produto'],
                'descricao' => $it['descricao'],
                'ncm' => $it['ncm'],
                'cfop' => $it['cfop'],
                'unidade' => $it['unidade'],
                'quantidade' => $it['quantidade'],
                'valor_unitario' => $it['valor_unitario'],
                'valor_total' => $it['valor_total'],
            ];
        }

        $data = [
            'idempresa' => $idempresa,
            'user_id' => $userId,
            'tipo_operacao' => 0,
            'status' => 'rascunho',
            'modelo' => (string)$d['modelo'],
            'serie' => (int)$d['serie'],
            'numero' => $d['numero'],
            'chave_acesso' => $d['chave_acesso'],
            'natureza_operacao' => $natStr,
            'natureza_operacao_id' => $natId,
            'finalidade' => 1,
            'presenca' => 9,
            'data_emissao' => $d['data_emissao'],
            'informacoes_complementares' => $d['informacoes_complementares'],
            'protocolo_autorizacao' => $d['protocolo_autorizacao'],
            'idcliente' => $idcliente,
            'fiscal_notas_itens' => $itensRows,
        ];

        $nota = $Notas->newEntity();
        $nota = $Notas->patchEntity($nota, $data, [
            'associated' => ['FiscalNotasItens' => ['associated' => ['FiscalNotasItensSeries']]],
        ]);

        $Cfg = TableRegistry::get('FiscalEmpresasConfig');
        $Aliq = TableRegistry::get('FiscalAliquotas');
        $nota = FiscalNotaRecalculo::aplicar($nota, $idempresa, $Cfg, $Aliq);

        $Xmls = TableRegistry::get('FiscalNotasXmls');
        $notaId = null;
        try {
            $Notas->getConnection()->transactional(function () use ($Notas, $nota, $Xmls, $xmlContent, &$notaId) {
                if (!$Notas->save($nota, [
                    'associated' => ['FiscalNotasItens' => ['associated' => ['FiscalNotasItensSeries']]],
                ])) {
                    throw new \RuntimeException(self::formatEntityErrors($nota));
                }
                $notaId = (int)$nota->id;
                $Xmls->save($Xmls->newEntity([
                    'fiscal_nota_id' => $notaId,
                    'tipo' => 'xml_import',
                    'xml_retorno' => $xmlContent,
                ]));
            });
        } catch (\Throwable $e) {
            return ['ok' => false, 'errors' => [$e->getMessage()]];
        }

        FiscalAudit::write('info', 'xml_lote_import', [
            'idempresa' => $idempresa,
            'user_id' => $userId,
            'fiscal_nota_id' => $notaId,
            'chave_acesso' => $d['chave_acesso'] ?? '',
        ]);

        return ['ok' => true, 'nota_id' => $notaId, 'warnings' => $warnings];
    }

    private static function localizarClientePorCnpj(int $idempresa, string $cnpj14): ?int {
        $cnpj14 = preg_replace('/\D/', '', $cnpj14);
        if (strlen($cnpj14) !== 14) {
            return null;
        }
        $Clientes = TableRegistry::get('Clientes');
        $candidatos = $Clientes->find()
            ->select(['id', 'cnpj'])
            ->where(['Clientes.idempresa' => $idempresa])
            ->toArray();
        foreach ($candidatos as $c) {
            if (preg_replace('/\D/', '', (string)$c->cnpj) === $cnpj14) {
                return (int)$c->id;
            }
        }

        return null;
    }

    /**
     * @param \Cake\Datasource\EntityInterface $entity
     */
    private static function formatEntityErrors($entity): string {
        $errors = $entity->getErrors();
        if ($errors === []) {
            return 'Erro ao salvar a nota fiscal.';
        }

        return 'Erro ao salvar: ' . json_encode($errors, JSON_UNESCAPED_UNICODE);
    }
}
