<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class FaturamentoTable extends Table {

	public function initialize(array $config) {
		$this->setTable('faturamento');
		$this->belongsTo('Clientes')->setForeignKey('idcliente')->setDependent(false);
		$this->belongsTo('Users')->setForeignKey('idautor')->setDependent(false);
		$this->belongsTo('Ordensservico')->setForeignKey('idordem')->setDependent(false);
		$this->hasMany('FaturamentoItens')->setForeignKey('idfaturamento')->setDependent(true);
		$this->hasMany('FinanceiroLancamentos')->setForeignKey('idfaturamento')->setDependent(false);
	}

	/** Gera hash único de 20 chars para compartilhamento público */
	public function gerarHash() {
		do {
			$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$hash = '';
			for ($i = 0; $i < 20; $i++) {
				$hash .= $chars[random_int(0, strlen($chars) - 1)];
			}
		} while ($this->findByHash($hash)->first());
		return $hash;
	}

	/** Próximo número sequencial por empresa: FT-0001, FT-0002, … */
	public function proximoNumero($idempresa) {
		$ultimo = $this->find('all')
			->where(['idempresa' => $idempresa, 'numero IS NOT' => null])
			->order(['id' => 'DESC'])
			->select(['numero'])
			->first();
		if (empty($ultimo)) {
			return 'FT-' . str_pad(1, 4, '0', STR_PAD_LEFT);
		}
		$seq = (int)preg_replace('/\D/', '', $ultimo->numero);
		return 'FT-' . str_pad($seq + 1, 4, '0', STR_PAD_LEFT);
	}
}
