<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class Feriado extends Entity
{
    protected $_accessible = [
        '*' => true,
        'id' => false,
    ];
}
