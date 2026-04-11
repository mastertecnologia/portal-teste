<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FinanceiroRecorrente extends Entity {
    protected $_accessible = [
        '*' => true,
        'id' => false,
    ];
}
