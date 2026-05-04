<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

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

    public function _getPodeEditar(): bool
    {
        return in_array($this->status, ['rascunho', 'em_analise'], true);
    }
}
