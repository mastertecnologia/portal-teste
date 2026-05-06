<?php
// ===========================================================================
// ENTITIES — divida em arquivos individuais ao implementar
// Cada classe deve estar em: src/Model/Entity/{NomeDaEntity}.php
// ===========================================================================

declare(strict_types=1);

// ===========================================================================
// FILE: src/Model/Entity/LaudosParecer.php
// ===========================================================================
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $empresa_id
 * @property string $numero
 * @property string $titulo
 * @property string $public_hash
 * @property string $status
 * @property int|null $tecnico_user_id
 * @property string|null $tecnico_nome
 * @property string|null $tecnico_registro
 * @property int|null $requester_client_id
 * @property string|null $requester_attention_to
 * @property string|null $requester_company_name
 * @property string|null $requester_cnpj
 * @property string|null $requester_phone
 * @property string|null $requester_email
 * @property string|null $requester_cep
 * @property string|null $requester_address
 * @property string|null $objetivo
 * @property string|null $documentacao
 * @property string|null $conclusao
 * @property string $estimated_new_equipment
 * @property bool $show_comparison
 * @property string|null $assinatura_path
 * @property string|null $cidade
 * @property \Cake\I18n\FrozenDate|null $data_emissao
 * @property \Cake\I18n\FrozenTime|null $deleted
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 */
class LaudosParecer extends Entity
{
    protected $_accessible = [
        '*' => true,
        'id' => false,
        'created' => false,
        'modified' => false,
    ];

    protected $_virtual = ['status_label', 'pode_editar'];

    public function _getStatusLabel(): string
    {
        $labels = [
            'rascunho' => 'Rascunho',
            'em_analise' => 'Em análise',
            'aprovado' => 'Aprovado',
            'concluido' => 'Concluído',
            'enviado' => 'Enviado',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Pode editar livremente apenas em rascunho ou em análise.
     */
    public function _getPodeEditar(): bool
    {
        return in_array($this->status, ['rascunho', 'em_analise']);
    }
}

// ===========================================================================
// FILE: src/Model/Entity/LaudosEmpresa.php
// ===========================================================================
namespace App\Model\Entity;

use Cake\ORM\Entity;

class LaudosEmpresa extends Entity
{
    protected $_accessible = [
        '*' => true,
        'id' => false,
    ];
}

// ===========================================================================
// FILE: src/Model/Entity/LaudosProduto.php
// ===========================================================================
namespace App\Model\Entity;

use Cake\ORM\Entity;

class LaudosProduto extends Entity
{
    protected $_accessible = [
        '*' => true,
        'id' => false,
    ];

    protected $_virtual = ['total_pecas', 'total_servicos', 'total_geral'];

    public function _getTotalPecas(): float
    {
        if (empty($this->laudos_produto_pecas)) return 0;
        return array_reduce($this->laudos_produto_pecas, fn($sum, $p) =>
            $sum + ((float)$p->quantidade * (float)$p->preco_unitario), 0);
    }

    public function _getTotalServicos(): float
    {
        if (empty($this->laudos_produto_servicos)) return 0;
        return array_reduce($this->laudos_produto_servicos, fn($sum, $s) =>
            $sum + ((float)$s->horas * (float)$s->valor_hora), 0);
    }

    public function _getTotalGeral(): float
    {
        return $this->_getTotalPecas() + $this->_getTotalServicos();
    }
}

// ===========================================================================
// FILE: src/Model/Entity/LaudosProdutoImagem.php
// ===========================================================================
namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake\Routing\Router;

class LaudosProdutoImagem extends Entity
{
    protected $_accessible = ['*' => true, 'id' => false];

    protected $_virtual = ['url'];

    /**
     * URL pública para acessar a imagem.
     */
    public function _getUrl(): string
    {
        // Se file_path começa com 'uploads/', monta URL completa
        if (str_starts_with($this->file_path, 'uploads/')) {
            return Router::url('/' . $this->file_path, true);
        }
        return $this->file_path;
    }
}

// ===========================================================================
// FILE: src/Model/Entity/LaudosProdutoPeca.php
// ===========================================================================
namespace App\Model\Entity;

use Cake\ORM\Entity;

class LaudosProdutoPeca extends Entity
{
    protected $_accessible = ['*' => true, 'id' => false];
    protected $_virtual = ['subtotal'];

    public function _getSubtotal(): float
    {
        return (float)$this->quantidade * (float)$this->preco_unitario;
    }
}

// ===========================================================================
// FILE: src/Model/Entity/LaudosProdutoServico.php
// ===========================================================================
namespace App\Model\Entity;

use Cake\ORM\Entity;

class LaudosProdutoServico extends Entity
{
    protected $_accessible = ['*' => true, 'id' => false];
    protected $_virtual = ['subtotal'];

    public function _getSubtotal(): float
    {
        return (float)$this->horas * (float)$this->valor_hora;
    }
}

// ===========================================================================
// FILE: src/Model/Entity/LaudosAnexo.php
// ===========================================================================
namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake\Routing\Router;

class LaudosAnexo extends Entity
{
    protected $_accessible = ['*' => true, 'id' => false];
    protected $_virtual = ['url', 'size_human'];

    public function _getUrl(): string
    {
        return Router::url(['controller' => 'LaudosAnexos', 'action' => 'download', $this->id], true);
    }

    public function _getSizeHuman(): string
    {
        $bytes = (int)$this->file_size;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 2) . ' MB';
    }
}

// ===========================================================================
// FILE: src/Model/Entity/LaudosHistorico.php
// ===========================================================================
namespace App\Model\Entity;

use Cake\ORM\Entity;

class LaudosHistorico extends Entity
{
    protected $_accessible = ['*' => true, 'id' => false];

    /**
     * Decodifica details JSON ao acessar.
     */
    protected function _getDetailsArray(): array
    {
        if (empty($this->details)) return [];
        if (is_array($this->details)) return $this->details;
        return json_decode($this->details, true) ?: [];
    }
}

// ===========================================================================
// FILE: src/Model/Entity/LaudosCatalogoPeca.php
// ===========================================================================
namespace App\Model\Entity;

use Cake\ORM\Entity;

class LaudosCatalogoPeca extends Entity
{
    protected $_accessible = ['*' => true, 'id' => false];
}

// ===========================================================================
// FILE: src/Model/Entity/LaudosCatalogoServico.php
// ===========================================================================
namespace App\Model\Entity;

use Cake\ORM\Entity;

class LaudosCatalogoServico extends Entity
{
    protected $_accessible = ['*' => true, 'id' => false];
}

// ===========================================================================
// FILE: src/Model/Entity/LaudosTemplate.php
// ===========================================================================
namespace App\Model\Entity;

use Cake\ORM\Entity;

class LaudosTemplate extends Entity
{
    protected $_accessible = ['*' => true, 'id' => false];
}
