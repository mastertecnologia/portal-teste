<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake\Routing\Router;

class LaudosAnexo extends Entity
{
    protected $_accessible = ['*' => true, 'id' => false];
    protected $_virtual = ['url', 'size_human'];

    public function _getUrl(): string
    {
        return Router::url([
            'controller' => 'LaudosUploads',
            'action' => 'downloadAnexo',
            $this->id,
            'prefix' => 'api/laudos',
        ], true);
    }

    public function _getSizeHuman(): string
    {
        $bytes = (int)$this->file_size;
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return round($bytes / 1048576, 2) . ' MB';
    }
}
