<?php
namespace App\Utility\Fiscal;

use Cake\Database\Connection;
use Cake\Database\Driver\Postgres;

/**
 * Condições ORM compatíveis com PostgreSQL (produção) e SQLite (testes RBAC HTTP).
 */
class FiscalSqlConditions {

    /**
     * Fragmento único para where(): ILIKE no Postgres; LOWER(col) LIKE no resto.
     *
     * @param Connection $connection
     * @param string       $qualifiedColumn ex.: descricao, FiscalNcm.descricao
     * @param string       $likePattern       ex.: %termo% (já com curingas)
     * @return array
     */
    public static function caseInsensitiveLike(Connection $connection, $qualifiedColumn, $likePattern) {
        $driver = $connection->getDriver();
        if ($driver instanceof Postgres) {
            return ["{$qualifiedColumn} ILIKE" => $likePattern];
        }

        return ['LOWER(' . $qualifiedColumn . ') LIKE' => mb_strtolower((string)$likePattern, 'UTF-8')];
    }
}
