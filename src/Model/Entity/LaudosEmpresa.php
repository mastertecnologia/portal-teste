<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class LaudosEmpresa extends Entity
{
    protected $_accessible = [
        '*' => true,
        'id' => false,
    ];
}
