<?php
namespace App\Model\Table;

use App\Utility\Fiscal\FiscalDfeDocZipSummary;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class FiscalDfeRecebidosTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('fiscal_dfe_recebidos');
        $this->setDisplayField('chave_acesso');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Empresas', ['foreignKey' => 'idempresa']);
        $this->belongsTo('FiscalNotas', ['foreignKey' => 'fiscal_nota_id']);
    }

    public function validationDefault(Validator $validator) {
        return $validator
            ->notEmpty('idempresa', 'Empresa é obrigatória')
            ->notEmpty('conteudo_hash', 'Hash do conteúdo é obrigatório')
            ->notEmpty('xml_conteudo', 'XML é obrigatório')
            ->inList('status', ['pendente', 'ignorado', 'vinculado'], 'Status inválido');
    }

    /**
     * Persiste documentos decodificados do XML retDistDFeInt (dedupe por idempresa + conteudo_hash).
     *
     * @return int Número de novos registos inseridos
     */
    public function ingestarDeRetornoDistribuicao(int $idempresa, string $xmlRetorno): int {
        $docs = FiscalDfeDocZipSummary::extractDocumentosParaIngest($xmlRetorno);
        $inseridos = 0;
        foreach ($docs as $d) {
            if (($d['tipo'] ?? '') === 'binario') {
                continue;
            }
            $plain = (string)($d['xml_plain'] ?? '');
            if ($plain === '') {
                continue;
            }
            $hash = md5($plain);
            if ($this->find()->where(['idempresa' => $idempresa, 'conteudo_hash' => $hash])->count() > 0) {
                continue;
            }
            $ent = $this->newEntity([
                'idempresa' => $idempresa,
                'nsu_doc' => $d['nsu'] !== '' ? $d['nsu'] : null,
                'schema' => (string)($d['schema'] ?? ''),
                'chave_acesso' => $d['chave'] !== null && $d['chave'] !== '' ? preg_replace('/\D/', '', (string)$d['chave']) : null,
                'tipo_documento' => (string)($d['tipo'] ?? ''),
                'conteudo_hash' => $hash,
                'xml_conteudo' => $plain,
                'status' => 'pendente',
            ]);
            if ($this->save($ent)) {
                $inseridos++;
            }
        }

        return $inseridos;
    }
}
