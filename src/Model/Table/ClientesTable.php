<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Security;
use Cake\Validation\Validator;

class ClientesTable extends Table {
    public function initialize(array $config) {
        $this->hasMany('Cliacessos')->setForeignKey('idcliente')->setDependent(true);
        $this->hasMany('Cliservicos')->setForeignKey('idcliente')->setDependent(true);
        $this->hasMany('Clicontint')->setForeignKey('idcliente')->setDependent(true);
        $this->hasMany('Clicontratos')->setForeignKey('idcliente')->setDependent(true);
        $this->hasMany('ContratosHoras', ['foreignKey' => 'idcliente', 'dependent' => true]);
        $this->belongsTo('Cidades')->setForeignKey('idcidade');
    }

    public function buildRules(RulesChecker $rules) {
        $rules->add($rules->isUnique(['idempresa', 'public_code'], __('Este código de cliente já existe nesta empresa.')));

        return $rules;
    }

    /**
     * Gera public_code antes das regras (evita isUnique com string vazia em concorrência).
     */
    public function beforeRules(Event $event, EntityInterface $entity, ArrayObject $options, $operation) {
        $raw = $entity->get('public_code');
        if ($raw !== null && trim((string)$raw) !== '') {
            return;
        }
        $eid = (int)$entity->get('idempresa');
        if ($eid <= 0) {
            return;
        }
        try {
            $entity->set('public_code', $this->allocateNextPortalPublicCode($eid));
        } catch (\Throwable $e) {
            \Cake\Log\Log::error('ClientesTable::beforeRules public_code: ' . $e->getMessage());
            $entity->errors('public_code', ['_allocate' => $e->getMessage()]);
            $event->stopPropagation();
        }
    }

    /**
     * Código vindo da integração (addAPI): trim, tamanho e charset seguro.
     * Retorna string normalizada, null se ausente, false se inválido.
     *
     * @param mixed $raw
     * @return string|false|null
     */
    public static function normalizeIntegrationPublicCode($raw) {
        if ($raw === null) {
            return null;
        }
        $s = trim((string)$raw);
        if ($s === '') {
            return null;
        }
        if (strlen($s) > 32) {
            return false;
        }
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $s)) {
            return false;
        }

        return $s;
    }

    /**
     * Próximo código portal P######## por empresa (serializado na linha de clientes_public_code_seq).
     */
    public function allocateNextPortalPublicCode(int $idempresa): string {
        $conn = $this->getConnection();
        $tables = array_map('strtolower', $conn->getSchemaCollection()->listTables());
        if (!in_array('clientes_public_code_seq', $tables, true)) {
            throw new \RuntimeException('Tabela clientes_public_code_seq inexistente. Rode as migrations.');
        }
        $conn->execute(
            'INSERT INTO clientes_public_code_seq (idempresa, next_val) VALUES (?, 0) ON CONFLICT (idempresa) DO NOTHING',
            [$idempresa]
        );
        $conn->execute(
            'UPDATE clientes_public_code_seq SET next_val = next_val + 1 WHERE idempresa = ?',
            [$idempresa]
        );
        $stmt = $conn->execute(
            'SELECT next_val FROM clientes_public_code_seq WHERE idempresa = ?',
            [$idempresa]
        );
        $row = $stmt->fetch('assoc');
        if ($row === false || !isset($row['next_val'])) {
            throw new \RuntimeException('Falha ao ler sequência de public_code.');
        }
        $n = (int)$row['next_val'];

        return 'P' . str_pad((string)$n, 8, '0', STR_PAD_LEFT);
    }

    public function validationDefault(Validator $validator) {
        return $validator
            // Telefone obrigatório apenas na criação do cliente; na edição,
            // permitimos salvar mesmo que o campo esteja vazio.
            ->notEmpty('fone', 'Telefone obrigatório!', 'create');
    }

    public function generateToken($string) {
      return Security::hash($string, 'sha1', true);
    }

    public function validToken($cnpj, $token) {
		$cliente = $this->find('all', ['fields' => ['id', 'cnpj', 'token']])->where(['cnpj' => $cnpj])->toArray();
		if (count($cliente) > 0 and $cliente[0]['token'] == $token) return true;

		return false;
    }
    	
    public function clientesArr($cliente){
		if($cliente->tipo == C_ClientesTipoFisica) $cliente->cnpj = $cliente->cpf;
		else $cliente->nome = $cliente->razaosocial;
		$cliente->inscest = removeCaracteres($cliente->inscricaoestadual);
		if (!empty($cliente->idcidade)) {
			$cliente->codibge = $this->Cidades->get($cliente->idcidade)->codibge;
		} else {
			$cliente->codibge = null;
		}
		$cliente->telefone = $cliente->fone;
		$cliente->celular = $cliente->fone2;
		$cliente->fantasia = $cliente->nomefantasia;
		unset($cliente->inscricaoestadual);
		unset($cliente->inscricaomunicipal);
		unset($cliente->cpf);
		unset($cliente->tipo);
		unset($cliente->razaosocial);
		unset($cliente->idcidade);
		unset($cliente->fone);
		unset($cliente->estado);
		unset($cliente->id);
		unset($cliente->inativo);
		unset($cliente->token);
		unset($cliente->membrodesde);
		unset($cliente->nomefantasia);
		unset($cliente->nomeresponsavel);
		unset($cliente->rg);
		unset($cliente->idempresa);
		unset($cliente->fone2);
		unset($cliente->public_code);
		return $cliente;
	}
	
	public function clicontratosArr($cliente){
		$clicontratos = $this->Clicontratos->find('all')->where(['idcliente' => $cliente->id, 'idempresa' => $cliente->idempresa])->toArray();
		foreach($clicontratos as $reg){
			unset($reg->id);
			unset($reg->idempresa);
			unset($reg->idcliente);
		}
		$cliente->Servicos = $clicontratos;

		return $cliente;
	}
}

