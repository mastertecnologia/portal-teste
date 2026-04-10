<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class FiscalEmpresasConfigTable extends Table {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->setTable('fiscal_empresas_config');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->belongsTo('Empresas')->setForeignKey('idempresa');
        $this->belongsTo('FiscalCertificados', [
            'foreignKey' => 'certificado_id',
        ]);
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options) {
        if (array_key_exists('regime_tributario', $data)) {
            $rt = (int)$data['regime_tributario'];
            if (in_array($rt, [1, 2], true)) {
                $data['regime_normal_enquadramento'] = null;
            }
        }
    }

    public function validationDefault(Validator $validator) {
        return $validator
            ->notEmpty('idempresa', 'Empresa é obrigatória')
            ->inList('regime_tributario', [1, 2, 3], 'Regime tributário inválido')
            ->allowEmpty('regime_normal_enquadramento', function ($context) {
                return (int)($context['data']['regime_tributario'] ?? 0) !== 3;
            })
            ->add('regime_normal_enquadramento', 'enquadramentoRegimeNormal', [
                'rule' => function ($value, $context) {
                    $rt = (int)($context['data']['regime_tributario'] ?? 0);
                    if ($rt !== 3) {
                        return true;
                    }

                    return in_array((int)$value, [1, 2], true);
                },
                'message' => 'Com Regime Normal, escolha Lucro presumido (1) ou Lucro real (2).',
            ])
            ->inList('ambiente', [1, 2], 'Ambiente deve ser 1 (Produção) ou 2 (Homologação)')
            ->allowEmpty('inscricao_estadual')
            ->allowEmpty('inscricao_municipal')
            ->allowEmpty('cnae_fiscal')
            ->maxLength('uf', 2)
            ->allowEmpty('dfe_ult_nsu')
            ->maxLength('dfe_ult_nsu', 15);
    }

    /**
     * Retorna ou cria a configuração fiscal da empresa.
     */
    public function getOrCreate($idempresa) {
        $config = $this->find()->where(['idempresa' => $idempresa])->first();
        if (!$config) {
            $config = $this->newEntity(['idempresa' => $idempresa]);
            $this->save($config);
        }
        return $config;
    }

    /**
     * Obtém e incrementa o próximo número de nota.
     */
    public function proximoNumero($idempresa, $modelo = '55') {
        $config = $this->find()->where(['idempresa' => $idempresa])->first();
        if (!$config) {
            return null;
        }
        $campo = 'prox_numero_nfe';
        if ($modelo === '65') {
            $campo = 'prox_numero_nfce';
        } elseif ($modelo === 'NFSE') {
            $campo = 'prox_numero_nfse';
        }
        $numero = $config->{$campo};
        $config->{$campo} = $numero + 1;
        $this->save($config);
        return $numero;
    }
}
