<?php
namespace App\Model\Table;

use App\Utility\AbacQuery;
use Cake\Core\Configure;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use DateTime;

class OrdensservicoTable extends Table {
	// TODO: separar status operacional / aprovação / financeiro (hoje tudo em `situacao` + edição).

	public function initialize(array $config) {
		$this->belongsTo('Clientes')->setForeignKey('idcliente')->setDependent(false);
		$this->belongsTo('Users')->setForeignKey('iduser')->setDependent(false);
		$this->hasMany('Ordemservicositens')->setForeignKey('idordem')->setBindingKey('id')->setDependent(false);
		$this->hasMany('Ordemparcelas')->setForeignKey('id')->setDependent(false);
		$this->hasMany('Ordemhoras')->setForeignKey('id')->setDependent(false);
		$this->hasMany('Ordemmovs')->setForeignKey('id')->setDependent(false);
		$this->hasMany('Itensordem');
		$this->hasMany('Ordemmovs');
		$this->hasMany('FaturasOrdensServico')->setForeignKey('idordem')->setDependent(false);
		$this->hasMany('Faturamento')->setForeignKey('idordem')->setDependent(false);
	}

	public function limpacarrinho(){
		$this->autoRender = false;
		if(isset($_SESSION['PGM_Idcarrinhoadd'])){
			$carrinho = $this->Itensordem->find('all')->where(['idordempk' => $_SESSION['PGM_Idcarrinhoadd']])->toArray();
			foreach($carrinho as $item) $this->Itensordem->delete($item);
			unset($_SESSION['PGM_Idcarrinhoadd']);
		}
	}

	/**
	 * Intervalo d/m/Y (legado) → limites ISO para comparar dataabertura no PostgreSQL.
	 * A chave ORM `dataabertura::date` não gera SQL válido; isso evita 500 no dashboard.
	 *
	 * @return array|null com chaves ini, fin (datetime ISO) ou null se parse falhar
	 */
	protected function _boundsDataaberturaBrToIso($dIniBr, $dFinBr) {
		$d1 = DateTime::createFromFormat('d/m/Y', trim((string)$dIniBr));
		$d2 = DateTime::createFromFormat('d/m/Y', trim((string)$dFinBr));
		if ($d1 === false || $d2 === false) {
			return null;
		}
		return [
			'ini' => $d1->format('Y-m-d') . ' 00:00:00',
			'fin' => $d2->format('Y-m-d') . ' 23:59:59',
		];
	}
	
	/**
	 * Retorna string JSON-like para gráfico (4 meses).
	 *
	 * @param int|string|null $idUser
	 * @param int|string|null $idempresa usado se ABAC desligado ou sem controller
	 * @param \Cake\Controller\Controller|null $controller se informado, aplica escopo ABAC em Ordensservico
	 */
	public function historicoOrdens($idUser = null, $idempresa = null, $controller = null) {

		$dIni = \decreaseMonths(\primeiroDiaMes(\dataAtual()), 3);
		$dFin = \decreaseMonths(\ultimoDiaMes(\dataAtual()), 3);

		$historico = "";

		for ($i = 1; $i <= 4; $i++) {
			$bounds = $this->_boundsDataaberturaBrToIso($dIni, $dFin);
			$q = $this->find()->where(['iduser' => $idUser]);
			if ($bounds !== null) {
				$q->where([
					'dataabertura >=' => $bounds['ini'],
					'dataabertura <=' => $bounds['fin'],
				]);
			}
			if ($controller !== null) {
				$user = [];
				if (method_exists($controller, 'Auth')) {
					$u = $controller->Auth->user();
					if (is_array($u)) {
						$user = $u;
					}
				}
				AbacQuery::apply($q, $user, $controller, 'Ordensservico', 'Ordensservico');
				$abacCfg = Configure::read('Abac');
				if (empty($abacCfg['enabled']) && $idempresa !== null && $idempresa !== '') {
					$q->where(['idempresa' => $idempresa]);
				}
			} elseif ($idempresa !== null && $idempresa !== '') {
				$q->where(['idempresa' => $idempresa]);
			}
			$xKey1 = sizeof($q->toArray());

			$historico .= "{ mes: '" . \descricaoMes($dIni) . "', xKey1: " . $xKey1. " },";
			
			// Desconta 1 mês
			$dIni = \increaseMonths($dIni);
			$dFin = \ultimoDiaMes($dIni);
		}
		
		return (substr($historico, 0, -1));
	}

	public function criarMov($idordem = null, $sitantiga = null, $sitnova = null, $idempresa = null, $iduser = null, $obs = null) {
		$mov = $this->Ordemmovs->newEntity();
		$mov->idordem = $idordem;
		$mov->sitantiga = $sitantiga;
		$mov->sitnova = $sitnova;
		$mov->iduser =  $iduser;
		$mov->idempresa = $idempresa;
		$mov->data = date('d/m/Y H:i:s', time());
		$mov->obs = $obs;

		return $this->Ordemmovs->save($mov);
	}
}
