<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake\Routing\Router;

class LaudosProdutoImagem extends Entity
{
    protected $_accessible = ['*' => true, 'id' => false];

    protected $_virtual = ['url'];

    public function _getUrl(): string
    {
        if (strpos((string)$this->file_path, 'uploads/') === 0) {
            return Router::url('/' . $this->file_path, true);
        }
        return (string)$this->file_path;
    }
}
