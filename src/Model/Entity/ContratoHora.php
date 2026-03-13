<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class ContratoHora extends Entity
{
    protected $_accessible = [
        '*' => true,
        'id' => false,
    ];
}
