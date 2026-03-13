<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class OrdemparcelasTable extends Table {
    public function initialize(array $config) {
        $this->belongsTo('Ordensservico')->setForeignKey(['idordem', 'idempresa']);
        $this->belongsTo('Users')->setForeignKey('iduser');
    }

    public function parcelasOrdem($idordem) {
          $parcelas = $this->find('all', [
                'contain' => ['Users']
          ])->where(['idordem' => $idordem])->order(['Ordemparcelas.id'])->toArray();

          return $parcelas;
    }
}
