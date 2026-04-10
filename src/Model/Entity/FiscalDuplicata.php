<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FiscalDuplicata extends Entity {
    protected $_accessible = [
        '*' => true,
        'id' => false,
    ];
}
