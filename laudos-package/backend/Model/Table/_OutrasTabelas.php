<?php
// ===========================================================================
// ARQUIVO DE REFERÊNCIA — divida em arquivos individuais ao implementar
// ===========================================================================
// Cada classe deve estar em: src/Model/Table/{NomeDaClasse}.php
// ===========================================================================

declare(strict_types=1);

// ===========================================================================
// FILE: src/Model/Table/LaudosEmpresasTable.php
// ===========================================================================
namespace App\Model\Table;

use Cake\ORM\Table;
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
}

// ===========================================================================
// FILE: src/Model/Table/LaudosProdutosTable.php
// ===========================================================================
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LaudosProdutosTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_produtos');
        $this->setDisplayField('nome');
        $this->addBehavior('Timestamp');

        $this->belongsTo('LaudosPareceres', ['foreignKey' => 'parecer_id']);
        $this->hasMany('LaudosProdutoImagens', [
            'foreignKey' => 'produto_id',
            'sort' => ['LaudosProdutoImagens.ordem' => 'ASC'],
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
        $this->hasMany('LaudosProdutoPecas', [
            'foreignKey' => 'produto_id',
            'sort' => ['LaudosProdutoPecas.ordem' => 'ASC'],
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
        $this->hasMany('LaudosProdutoServicos', [
            'foreignKey' => 'produto_id',
            'sort' => ['LaudosProdutoServicos.ordem' => 'ASC'],
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('parecer_id')->notEmptyString('parecer_id')
            ->scalar('nome')->maxLength('nome', 200)->allowEmptyString('nome')
            ->scalar('tipo')->maxLength('tipo', 50)->allowEmptyString('tipo')
            ->inList('recomendacao', ['repair', 'replace', 'partial']);
        return $validator;
    }
}

// ===========================================================================
// FILE: src/Model/Table/LaudosProdutoImagensTable.php
// ===========================================================================
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LaudosProdutoImagensTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_produto_imagens');
        $this->setDisplayField('nome_original');

        $this->belongsTo('LaudosProdutos', ['foreignKey' => 'produto_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('produto_id')->notEmptyString('produto_id')
            ->scalar('file_path')->maxLength('file_path', 500)->notEmptyString('file_path');
        return $validator;
    }
}

// ===========================================================================
// FILE: src/Model/Table/LaudosProdutoPecasTable.php
// ===========================================================================
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LaudosProdutoPecasTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_produto_pecas');
        $this->setDisplayField('nome');

        $this->belongsTo('LaudosProdutos', ['foreignKey' => 'produto_id']);
        $this->belongsTo('LaudosCatalogoPecas', ['foreignKey' => 'catalogo_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('produto_id')->notEmptyString('produto_id')
            ->scalar('nome')->maxLength('nome', 200)->notEmptyString('nome')
            ->numeric('quantidade')
            ->numeric('preco_unitario');
        return $validator;
    }
}

// ===========================================================================
// FILE: src/Model/Table/LaudosProdutoServicosTable.php
// ===========================================================================
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LaudosProdutoServicosTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_produto_servicos');
        $this->setDisplayField('descricao');

        $this->belongsTo('LaudosProdutos', ['foreignKey' => 'produto_id']);
        $this->belongsTo('LaudosCatalogoServicos', ['foreignKey' => 'catalogo_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('produto_id')->notEmptyString('produto_id')
            ->scalar('descricao')->maxLength('descricao', 300)->notEmptyString('descricao')
            ->numeric('horas')
            ->numeric('valor_hora');
        return $validator;
    }
}

// ===========================================================================
// FILE: src/Model/Table/LaudosAnexosTable.php
// ===========================================================================
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LaudosAnexosTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_anexos');
        $this->setDisplayField('nome_original');

        $this->belongsTo('LaudosPareceres', ['foreignKey' => 'parecer_id']);
        $this->belongsTo('CreatedBy', ['className' => 'Users', 'foreignKey' => 'created_by']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('parecer_id')->notEmptyString('parecer_id')
            ->scalar('nome_original')->maxLength('nome_original', 255)->notEmptyString('nome_original')
            ->scalar('file_path')->maxLength('file_path', 500)->notEmptyString('file_path');
        return $validator;
    }
}

// ===========================================================================
// FILE: src/Model/Table/LaudosHistoricoTable.php
// ===========================================================================
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LaudosHistoricoTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_historico');
        $this->setDisplayField('action');

        $this->belongsTo('LaudosPareceres', ['foreignKey' => 'parecer_id']);
        $this->belongsTo('Users', ['foreignKey' => 'user_id']);
    }

    /**
     * Registra um evento no histórico.
     */
    public function logEvent(int $parecerId, ?int $userId, ?string $userName, string $action, array $details = []): \Cake\Datasource\EntityInterface
    {
        $entity = $this->newEntity([
            'parecer_id' => $parecerId,
            'user_id' => $userId,
            'user_name_snapshot' => $userName,
            'action' => $action,
            'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
        ]);
        $this->saveOrFail($entity);
        return $entity;
    }
}

// ===========================================================================
// FILE: src/Model/Table/LaudosCatalogoPecasTable.php
// ===========================================================================
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LaudosCatalogoPecasTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_catalogo_pecas');
        $this->setDisplayField('nome');
        $this->addBehavior('Timestamp');

        $this->belongsTo('LaudosEmpresas', ['foreignKey' => 'empresa_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('nome')->maxLength('nome', 200)->notEmptyString('nome')
            ->numeric('preco_default');
        return $validator;
    }

    /**
     * Busca peças do catálogo (com filtro de texto).
     */
    public function buscar(int $empresaId, ?string $q = null, int $limit = 50): array
    {
        $query = $this->find()
            ->where(['empresa_id' => $empresaId, 'ativo' => true])
            ->order(['categoria' => 'ASC', 'nome' => 'ASC'])
            ->limit($limit);

        if ($q) {
            $like = '%' . str_replace(' ', '%', $q) . '%';
            $query->where([
                'OR' => [
                    'nome ILIKE' => $like,
                    'codigo ILIKE' => $like,
                ],
            ]);
        }

        return $query->all()->toArray();
    }
}

// ===========================================================================
// FILE: src/Model/Table/LaudosCatalogoServicosTable.php
// ===========================================================================
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LaudosCatalogoServicosTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_catalogo_servicos');
        $this->setDisplayField('descricao');
        $this->addBehavior('Timestamp');

        $this->belongsTo('LaudosEmpresas', ['foreignKey' => 'empresa_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('descricao')->maxLength('descricao', 300)->notEmptyString('descricao')
            ->numeric('valor_hora_default');
        return $validator;
    }

    public function buscar(int $empresaId, ?string $q = null, int $limit = 50): array
    {
        $query = $this->find()
            ->where(['empresa_id' => $empresaId, 'ativo' => true])
            ->order(['categoria' => 'ASC', 'descricao' => 'ASC'])
            ->limit($limit);

        if ($q) {
            $like = '%' . str_replace(' ', '%', $q) . '%';
            $query->where(['descricao ILIKE' => $like]);
        }

        return $query->all()->toArray();
    }
}

// ===========================================================================
// FILE: src/Model/Table/LaudosTemplatesTable.php
// ===========================================================================
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LaudosTemplatesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_templates');
        $this->setDisplayField('nome');
        $this->addBehavior('Timestamp');

        $this->belongsTo('LaudosEmpresas', ['foreignKey' => 'empresa_id']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->inList('tipo', ['diagnostico', 'conclusao', 'objetivo', 'documentacao'])
            ->scalar('nome')->maxLength('nome', 150)->notEmptyString('nome')
            ->scalar('conteudo')->notEmptyString('conteudo');
        return $validator;
    }

    public function porTipo(int $empresaId, string $tipo): array
    {
        return $this->find()
            ->where(['empresa_id' => $empresaId, 'tipo' => $tipo, 'ativo' => true])
            ->order(['ordem' => 'ASC', 'nome' => 'ASC'])
            ->all()
            ->toArray();
    }
}
