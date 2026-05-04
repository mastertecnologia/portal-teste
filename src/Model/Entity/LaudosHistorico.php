<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class LaudosHistorico extends Entity
{
    protected $_accessible = ['*' => true, 'id' => false];

    public function _getDetailsArray(): array
    {
        if (empty($this->details)) {
            return [];
        }
        if (is_array($this->details)) {
            return $this->details;
        }
        return json_decode((string)$this->details, true) ?: [];
    }
}
