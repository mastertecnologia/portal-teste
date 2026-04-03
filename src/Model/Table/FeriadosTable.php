<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class FeriadosTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function validationDefault(Validator $validator)
    {
        $validator
            ->requirePresence('data', 'create')
            ->notEmptyDate('data');
        return $validator;
    }

    /**
     * Retorna array de datas (Y-m-d) que são feriados no intervalo dado (para idempresa ou globais).
     * @param string $dataInicio Y-m-d
     * @param string $dataFim Y-m-d
     * @param int|null $idempresa
     * @return array lista de 'data' => Y-m-d
     */
    public function listarFeriadosNoPeriodo($dataInicio, $dataFim, $idempresa = null)
    {
        $query = $this->find()
            ->where([
                'data >=' => $dataInicio,
                'data <=' => $dataFim,
            ]);
        if ($idempresa !== null) {
            $query->andWhere([
                'OR' => [
                    ['idempresa IS' => null],
                    ['idempresa' => $idempresa],
                ],
            ]);
        }
        $lista = $query->all();
        $result = [];
        foreach ($lista as $entity) {
            $d = $entity->data;
            if ($d instanceof \DateTimeInterface) {
                $result[$d->format('Y-m-d')] = true;
            }
        }
        return $result;
    }

    /**
     * Feriados cadastrados (empresa + globais) com descrição, para o calendário.
     *
     * @param string $dataInicio Y-m-d
     * @param string $dataFim    Y-m-d
     * @param int|null $idempresa
     * @return \Cake\ORM\Query|\Cake\Database\Query
     */
    public function findParaCalendario($dataInicio, $dataFim, $idempresa = null)
    {
        $q = $this->find()
            ->select(['data', 'descricao'])
            ->where([
                'data >=' => $dataInicio,
                'data <=' => $dataFim,
            ])
            ->order(['data' => 'ASC']);
        if ($idempresa !== null) {
            $q->andWhere([
                'OR' => [
                    ['idempresa IS' => null],
                    ['idempresa' => $idempresa],
                ],
            ]);
        }

        return $q;
    }
}
