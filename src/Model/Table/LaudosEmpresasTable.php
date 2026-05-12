<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Database\Driver\Postgres;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class LaudosEmpresasTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_empresas');
        $this->setDisplayField('razao_social');
        $this->addBehavior('Timestamp');

        $this->hasMany('LaudosCatalogoPecas', ['foreignKey' => 'empresa_id']);
        $this->hasMany('LaudosCatalogoServicos', ['foreignKey' => 'empresa_id']);
        $this->hasMany('LaudosTemplates', ['foreignKey' => 'empresa_id']);
        $this->hasMany('LaudosPareceres', ['foreignKey' => 'empresa_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('razao_social')->maxLength('razao_social', 200)->notEmptyString('razao_social')
            ->scalar('cnpj')->maxLength('cnpj', 18)->notEmptyString('cnpj')
            ->email('email', false)->allowEmptyString('email');
        return $validator;
    }

    /**
     * Garante que laudos_empresas tenha uma linha com id = empresas.id (idempresa da sessão).
     * O módulo Laudos usa o mesmo identificador que o portal; o seed só criava id=1.
     */
    public function ensureForPortalEmpresa(int $portalEmpresaId): EntityInterface
    {
        $existing = $this->find()->where(['id' => $portalEmpresaId])->first();
        if ($existing !== null) {
            return $existing;
        }

        /** @var \App\Model\Table\EmpresasTable $empresas */
        $empresas = TableRegistry::getTableLocator()->get('Empresas');
        $emp = $empresas->get($portalEmpresaId);

        $razao = trim((string)($emp->razaosocial ?? ''));
        if ($razao === '') {
            $razao = trim((string)($emp->nomefantasia ?? ''));
        }
        if ($razao === '') {
            $razao = 'Empresa #' . $portalEmpresaId;
        }

        $cnpj = trim((string)($emp->cnpj ?? ''));
        if ($cnpj === '') {
            $cnpj = '00000000000000';
        }
        if (strlen($cnpj) > 18) {
            $cnpj = substr($cnpj, 0, 18);
        }

        $endereco = trim((string)($emp->endereco ?? ''));
        $nro = trim((string)($emp->nroendereco ?? ''));
        if ($endereco !== '' && $nro !== '') {
            $endereco = $endereco . ', ' . $nro;
        } elseif ($nro !== '') {
            $endereco = $nro;
        }

        $row = $this->newEntity([
            'razao_social' => $razao,
            'cnpj' => $cnpj,
            'email' => $this->truncateNullable($emp->email ?? null, 150),
            'telefone' => $this->truncateNullable($emp->fone ?? null, 30),
            'telefone2' => $this->truncateNullable($emp->fone2 ?? null, 30),
            'cep' => $this->truncateNullable($emp->cep ?? null, 10),
            'endereco' => $endereco !== '' ? $endereco : null,
            'site' => $this->truncateNullable($emp->site ?? null, 150),
        ], ['validate' => false]);

        $row->set('id', $portalEmpresaId, ['guard' => false]);
        $row->isNew(true);

        try {
            if (!$this->save($row)) {
                throw new \RuntimeException(
                    'Laudos: não foi possível criar empresa emissora: ' . json_encode($row->getErrors())
                );
            }
        } catch (\Exception $e) {
            $again = $this->find()->where(['id' => $portalEmpresaId])->first();
            if ($again !== null) {
                return $again;
            }
            throw $e;
        }

        $this->syncIdSequenceAfterManualInsert();

        return $row;
    }

    protected function truncateNullable(?string $value, int $maxLen): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (strlen($value) <= $maxLen) {
            return $value;
        }

        return substr($value, 0, $maxLen);
    }

    /**
     * Após INSERT com id explícito, alinha a sequência em PostgreSQL.
     */
    protected function syncIdSequenceAfterManualInsert(): void
    {
        $driver = $this->getConnection()->getDriver();
        if (!($driver instanceof Postgres)) {
            return;
        }
        $this->getConnection()->execute(
            "SELECT setval(pg_get_serial_sequence('laudos_empresas', 'id'), GREATEST((SELECT COALESCE(MAX(id), 1) FROM laudos_empresas), 1))"
        );
    }
}

