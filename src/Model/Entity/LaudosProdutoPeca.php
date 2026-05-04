<?php
declare(strict_types=1);

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
