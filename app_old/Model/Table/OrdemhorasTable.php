<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class OrdemhorasTable extends Table {
    public function initialize(array $config) {
        $this->belongsTo('Ordensservico')->setForeignKey(['idordem', 'idempresa']);
        $this->belongsTo('Users')->setForeignKey('iduser');
    }

    public function horasOrdem($idordem) {
          $horas = $this->find('all', [
                'contain' => ['Users']
          ])->where(['idordem' => $idordem])->order(['Ordemhoras.id'])->toArray();

          return $horas;
    }
    
    public function getMinutos($horaini, $horafim) {
        $horaini = $horaini->setDate(2017, 1, 1);
        $horafim = $horafim->setDate(2017, 1, 1);

        $interval = $horaini->diff($horafim);

        $horas = $interval->h;
        $minutos = $interval->i;

        $minutos = $minutos + ($horas * 60);

        return $minutos;
    }

    public function minutosCliente($idcliente, $dataini, $datafin) {
        $query = $this->find()->contain(['Ordensservico'])->where(
            ['ordensservico.idcliente' => $idcliente, 'ordemhoras.data >=' => $dataini, 'ordemhoras.data <=' => $datafin])
        ->toArray();
        
        $minutos = 0;
        foreach ($query as $value) $minutos = $minutos + $this->getMinutos($value['horaini'], $value['horafin']);

        return $minutos;
    }    
    
    public function minutosOrdem($idordem, $dataini, $datafin) {
        $query = $this->find()->contain(['Ordensservico'])->where(
            ['ordemhoras.idordem' => $idordem, 'ordemhoras.data >=' => $dataini, 'ordemhoras.data <=' => $datafin])
        ->toArray();
        
        $minutos = 0;

        foreach ($query as $value) $minutos = $minutos + $this->getMinutos($value['horaini'], $value['horafin']);

        return $minutos;
    }
}
