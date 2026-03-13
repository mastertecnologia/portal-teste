<?php

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Routing\Router; 

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';

class PesquisaController extends Controller {
	public function initialize() {
		parent::initialize();
		$this->loadComponent('Auth');
	}

	public function pesquisa($string = null) {
		$this->autoRender = false;
		if(!empty($string)){
			$arrResultados = [];
			$stringArr = explode(' ', $string);
			$i = 1;
			$j = 1;
			if($this->Auth->user('admin') == 1) $arrFuncoes = C_FuncoesSistemaAdmin;
			else if($this->Auth->user('role') == 0) $arrFuncoes = C_FuncoesSistemaTecnico;
			else $arrFuncoes = C_FuncoesSistemaCliente;
			foreach($arrFuncoes as $key=>$araragi) {
				$i++;
				if(sizeof($stringArr) > 1){
					foreach($stringArr as $reg) {
					if(strpos(strtolower($key), strtolower($reg)) !== false){
						$arrResultados[$i]['Controller'] = $araragi['Controller'];
						$arrResultados[$i]['ControllerQueAparece'] = $key;
						$controllercerto = $araragi['Controller'];
						$controllercertoqueaparece = $key;
							foreach($araragi['Actions'] as $keyAraragi=>$action) {
								foreach($stringArr as $regDeAntesComNomeDiferente) {
									$sinonimos = $this->sinonimos($regDeAntesComNomeDiferente);
									foreach($sinonimos as $sinonimo) {
										if(strpos(strtolower($action), strtolower($sinonimo)) !== false){
											$arrResultados[$i]['Controller'] = $araragi['Controller'];
											$arrResultados[$i]['ControllerQueAparece'] = $controllercertoqueaparece;
											$arrResultados[$i]['Action'] = $keyAraragi;
											$arrResultados[$i]['ActionQueAparece'] = $action;
											if(count($sinonimos) > 1)$i++;
											break;
										}        
									}
								}
							}
						}
					}
				} else {
					foreach($stringArr as $reg) {
						if(strpos(strtolower($key), strtolower($reg)) !== false){
							foreach($araragi['Actions'] as $keyAraragi=>$action) {
								foreach($stringArr as $regDeAntesComNomeDiferente) {
									$arrResultados[$j]['Controller'] = $araragi['Controller'];
									$arrResultados[$j]['ControllerQueAparece'] = $key;
									$arrResultados[$j]['Action'] = $keyAraragi;
									$arrResultados[$j]['ActionQueAparece'] = $action;
								}
								$j++;
							}
						}
					}
				}
			}
			//echo json_encode($arrResultados, JSON_PRETTY_PRINT);
			return $this->jsonResponse($arrResultados, 200);
		}else return $this->jsonResponse('', 200);
		//else echo json_encode([], JSON_PRETTY_PRINT);
		// debug($arrResultados);
	}

	public function link($controller, $action) {
		$this->autoRender = false;
		echo Router::url(['controller' => $controller, 'action' => $action]);
	}

	public function sinonimos($string) {
		$this->autoRender = false;
		$i = 1;
		$arraySinonimos = [];
		foreach(C_PesquisaSinonimos as $key=>$araragi) if(strpos(strtolower($key), strtolower($string)) !== false) $arraySinonimos[] = $araragi;
		return $arraySinonimos;
	}

}
