<?php
declare(strict_types=1);

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
        if (empty($this->laudos_produto_pecas)) {
            return 0.0;
        }
        return array_reduce(
            $this->laudos_produto_pecas,
            function ($sum, $p) { return $sum + ((float)$p->quantidade * (float)$p->preco_unitario); },
            0.0
        );
    }

    public function _getTotalServicos(): float
    {
        if (empty($this->laudos_produto_servicos)) {
            return 0.0;
        }
        return array_reduce(
            $this->laudos_produto_servicos,
            function ($sum, $s) { return $sum + ((float)$s->horas * (float)$s->valor_hora); },
            0.0
        );
    }

    public function _getTotalGeral(): float
    {
        return $this->_getTotalPecas() + $this->_getTotalServicos();
    }
}
