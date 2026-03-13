<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class TicketshorasTable extends Table {
    public function initialize(array $config) {
        $this->belongsTo('Tickets')->setForeignKey('idticket');
        $this->belongsTo('Users')->setForeignKey('iduser');
    }

    public function horasTicket($idticket) {
          $horas = $this->find('all', [
                'contain' => ['Users']
          ])->where(['idticket' => $idticket])->order(['Ticketshoras.id'])->toArray();

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
        $query = $this->find()->contain(['Tickets'])->where(
            ['tickets.idcliente' => $idcliente, 'ticketshoras.data >=' => $dataini, 'ticketshoras.data <=' => $datafin])
        ->toArray();
        
        $minutos = 0;

        foreach ($query as $value) {
            $minutos = $minutos + $this->getMinutos($value['horaini'], $value['horafin']);
        }

        return $minutos;
    }    
    
    public function minutosTicket($idticket, $dataini, $datafin) {
        $query = $this->find()->contain(['Tickets'])->where(
            ['ticketshoras.idticket' => $idticket, 'ticketshoras.data >=' => $dataini, 'ticketshoras.data <=' => $datafin])
        ->toArray();
        
        $minutos = 0;

        foreach ($query as $value) {
            $minutos = $minutos + $this->getMinutos($value['horaini'], $value['horafin']);
        }

        return $minutos;
    }

}
