<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;


class VisitasTable extends Table
{
    public function initialize(array $config){
        parent::initialize($config);

        $this->setTable('visitas');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->belongsTo('Clientes')->setForeignKey('idcliente');
        $this->belongsTo('Listamembros')->setForeignKey('id');
        $this->belongsToMany('Users', [
            'foreignKey' => 'idvisita',
            'targetForeignKey' => 'iduser',
            'joinTable' => 'listamembros'
        ]);
        
    }

    public function visitasCliente($idcliente, $dataini, $datafin) {
        $query = $this->find()->where(['visitas.situacao' => 1,'visitas.idcliente' => $idcliente, 'visitas.data >=' => $dataini, 'visitas.data <=' => $datafin])->toArray();
        $visitas = 0;

        foreach ($query as $value) {
            $visitas++;
        }

        return $visitas;
    }

    public function relatoriosVisitas($visitas, $dIni, $dFin){
        $agendadasArray = $this->find()->where([
            'situacao' => C_UserSituacaoAgendada,
            'data::date >=' => $dIni,
            'data::date <=' => $dFin
            ])->order([
                'Clientes.razaosocial'
            ])->contain([
                'Clientes' => ['fields' => ['Clientes.razaosocial']],
        ])
        ->toArray();
        foreach($agendadasArray as $reg){

            if($reg->cliente != null) {
                if(!in_array($reg->idcliente, $visitas)){
                    @$visitas[$reg->idcliente]['id'] = $reg->idcliente;
                    @$visitas[$reg->idcliente]['nome'] = $reg->cliente->razaosocial;
                } 
                @$visitas[$reg->idcliente]['qtdagendadas']++;
                @$visitas[$reg->idcliente]['total']++;
            }
            else{
                if(!in_array($reg->nomecliente, $visitas)){
                    @$visitas[$reg->nomecliente]['id'] = $reg->nomecliente;
                    @$visitas[$reg->nomecliente]['nome'] = $reg->nomecliente;
                } 
                @$visitas[$reg->nomecliente]['qtdagendadas']++;
                @$visitas[$reg->nomecliente]['total']++;
            }  
        }

        $finalizadasArray = $this->find()->where([
            'situacao' => C_UserSituacaoFinalizada,
            'data::date >=' => $dIni,
            'data::date <=' => $dFin
            ])->order([
                'Clientes.razaosocial'
            ])->contain([
                'Clientes' => ['fields' => ['Clientes.razaosocial']],
        ])
        ->toArray();
        foreach($finalizadasArray as $reg){
            if($reg->cliente != null) {
                if(!in_array($reg->idcliente, $visitas)){
                    @$visitas[$reg->idcliente]['id'] = $reg->idcliente;
                    @$visitas[$reg->idcliente]['nome'] = $reg->cliente->razaosocial;
                } 
                @$visitas[$reg->idcliente]['qtdfinalizadas']++;
                @$visitas[$reg->idcliente]['total']++;
            }
            else{
                if(!in_array($reg->nomecliente, $visitas)){
                    @$visitas[$reg->nomecliente]['id'] = $reg->nomecliente;
                    @$visitas[$reg->nomecliente]['nome'] = $reg->nomecliente;
                } 
                @$visitas[$reg->nomecliente]['qtdfinalizadas']++;
                @$visitas[$reg->nomecliente]['total']++;
            }  
        }

        $pendentesArray = $this->find()->where([
            'situacao' => C_UserSituacaoPendende,
            'data::date >=' => $dIni,
            'data::date <=' => $dFin
            ])->order([
                'Clientes.razaosocial'
            ])->contain([
                'Clientes' => ['fields' => ['Clientes.razaosocial']],
        ])
        ->toArray();
        foreach($pendentesArray as $reg){
            if($reg->cliente != null) {
                if(!in_array($reg->idcliente, $visitas)){
                    @$visitas[$reg->idcliente]['id'] = $reg->idcliente;
                    @$visitas[$reg->idcliente]['nome'] = $reg->cliente->razaosocial;
                } 
                @$visitas[$reg->idcliente]['qtdpendentes']++;
                @$visitas[$reg->idcliente]['total']++;
            }
            else{
                if(!in_array($reg->nomecliente, $visitas)){
                    @$visitas[$reg->nomecliente]['id'] = $reg->nomecliente;
                    @$visitas[$reg->nomecliente]['nome'] = $reg->nomecliente;
                } 
                @$visitas[$reg->nomecliente]['qtdpendentes']++;
                @$visitas[$reg->nomecliente]['total']++;
            }  
        }

        $canceladasArray = $this->find()->where([
            'situacao' => C_UserSituacaoCancelada,
            'data::date >=' => $dIni,
            'data::date <=' => $dFin
            ])->order([
                'Clientes.razaosocial'
            ])->contain([
                'Clientes' => ['fields' => ['Clientes.razaosocial']],
        ])
        ->toArray();
        foreach($canceladasArray as $reg){
            if($reg->cliente != null) {
                if(!in_array($reg->idcliente, $visitas)){
                    @$visitas[$reg->idcliente]['id'] = $reg->idcliente;
                    @$visitas[$reg->idcliente]['nome'] = $reg->cliente->razaosocial;
                } 
                @$visitas[$reg->idcliente]['qtdcanceladas']++;
                @$visitas[$reg->idcliente]['total'] = $visitas[$reg->idcliente]['total'];
            }
            else{
                if(!in_array($reg->nomecliente, $visitas)){
                    @$visitas[$reg->nomecliente]['id'] = $reg->nomecliente;
                    @$visitas[$reg->nomecliente]['nome'] = $reg->nomecliente;
                } 
                @$visitas[$reg->nomecliente]['qtdcanceladas']++;
                @$visitas[$reg->nomecliente]['total'] = $visitas[$reg->nomecliente]['total'];
            }  
        }

        return $visitas;
    }

    public function valorVisitas($idcliente, $dIni, $dFin){
        $visitas = $this->find('all')->where(['idcliente' => $idcliente, 'data >=' => $dIni, 'data <=' => $dFin])->toArray();

        $valorvisitas = 0;
        foreach($visitas as $reg){
            if($reg->situacao != C_UserSituacaoCancelada) $valorvisitas += $reg->valor;
        }
     
        return $valorvisitas;
    }
}
