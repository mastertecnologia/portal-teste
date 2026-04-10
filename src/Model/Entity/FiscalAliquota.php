<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class FiscalAliquota extends Entity {

    protected $_accessible = [
        'id' => false,
        'idempresa' => true,
        'uf_origem' => true,
        'uf_destino' => true,
        'ncm_codigo' => true,
        'icms_aliquota' => true,
        'icms_reducao' => true,
        'icms_st_mva' => true,
        'ipi_aliquota' => true,
        'pis_aliquota' => true,
        'cofins_aliquota' => true,
        'iss_aliquota' => true,
        'created' => true,
        'modified' => true,
        'empresa' => true,
    ];
}
