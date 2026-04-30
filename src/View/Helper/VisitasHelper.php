<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;

/**
 * Rótulos de domínio para visitas/agenda (constantes legadas em PGMPackages\UserConstants).
 */
class VisitasHelper extends Helper
{
    /**
     * Descrição legível da situação da visita (substitui função global removida).
     *
     * @param int|string|null $situacao Valor persistido em visitas.situacao
     */
    public function descricaoSituacaoVisitas($situacao): string
    {
        if ($situacao === null || $situacao === '') {
            return '';
        }
        $code = (int)$situacao;

        if (defined('C_VisitasSituacaoQuery')) {
            $q = constant('C_VisitasSituacaoQuery');
            if (is_array($q)) {
                foreach ([$code, (string)$code] as $k) {
                    if (array_key_exists($k, $q)) {
                        return (string)$q[$k];
                    }
                }
            }
        }

        $map = [];
        if (defined('C_UserSituacaoAgendada')) {
            $map[(int)constant('C_UserSituacaoAgendada')] = 'Agendada';
        }
        if (defined('C_UserSituacaoPendende')) {
            $map[(int)constant('C_UserSituacaoPendende')] = 'Pendente';
        }
        if (defined('C_UserSituacaoFinalizada')) {
            $map[(int)constant('C_UserSituacaoFinalizada')] = 'Finalizada';
        }
        if (defined('C_UserSituacaoCancelada')) {
            $map[(int)constant('C_UserSituacaoCancelada')] = 'Cancelada';
        }
        if (isset($map[$code])) {
            return $map[$code];
        }

        return (string)$situacao;
    }
}
