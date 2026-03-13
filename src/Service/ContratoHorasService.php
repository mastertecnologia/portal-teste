<?php
namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * Aplica débito de horas no contrato e calcula valor (hora adicional quando saldo insuficiente).
 */
class ContratoHorasService
{
    /**
     * Debita minutos do contrato e atualiza saldo. Retorna resumo para relatório/e-mail.
     * @param int $idContrato
     * @param int $minutosComercial
     * @param int $minutosEspecial
     * @return array ['saldo_antes', 'saldo_depois', 'minutos_debitados_saldo', 'minutos_adicional_comercial', 'minutos_adicional_especial', 'valor_adicional_comercial', 'valor_adicional_especial', 'contrato' => entity]
     */
    public function debitarHoras($idContrato, $minutosComercial, $minutosEspecial)
    {
        $contratosTable = TableRegistry::getTableLocator()->get('ContratosHoras');
        $contrato = $contratosTable->get($idContrato);
        $saldoAntes = (float)$contrato->saldo_horas;
        $saldoMinutos = (int)round($saldoAntes * 60);

        $minutosDebitadosDoSaldo = 0;
        $minutosAdicionalComercial = 0;
        $minutosAdicionalEspecial = 0;

        $totalRequerido = $minutosComercial + $minutosEspecial;
        if ($totalRequerido <= 0) {
            return [
                'saldo_antes' => $saldoAntes,
                'saldo_depois' => $saldoAntes,
                'minutos_debitados_saldo' => 0,
                'minutos_adicional_comercial' => 0,
                'minutos_adicional_especial' => 0,
                'valor_adicional_comercial' => 0,
                'valor_adicional_especial' => 0,
                'contrato' => $contrato,
            ];
        }

        $disponivel = $saldoMinutos;
        $debitarComercial = min($minutosComercial, $disponivel);
        $minutosAdicionalComercial = $minutosComercial - $debitarComercial;
        $disponivel -= $debitarComercial;
        $debitarEspecial = min($minutosEspecial, $disponivel);
        $minutosAdicionalEspecial = $minutosEspecial - $debitarEspecial;
        $minutosDebitadosDoSaldo = $debitarComercial + $debitarEspecial;

        $novoSaldoMinutos = max(0, $saldoMinutos - $minutosDebitadosDoSaldo);
        $contrato->saldo_horas = round($novoSaldoMinutos / 60, 2);
        $contrato->horas_utilizadas = (float)$contrato->horas_utilizadas + round($totalRequerido / 60, 2);
        $contratosTable->save($contrato);

        $valorHoraAdicCom = $contrato->valor_hora_adicional_comercial ? (float)$contrato->valor_hora_adicional_comercial : 0;
        $valorHoraEsp = $contrato->valor_hora_especial ? (float)$contrato->valor_hora_especial : 0;
        $valorAdicionalComercial = ($minutosAdicionalComercial / 60) * $valorHoraAdicCom;
        $valorAdicionalEspecial = ($minutosAdicionalEspecial / 60) * $valorHoraEsp;

        return [
            'saldo_antes' => $saldoAntes,
            'saldo_depois' => $contrato->saldo_horas,
            'minutos_debitados_saldo' => $minutosDebitadosDoSaldo,
            'minutos_adicional_comercial' => $minutosAdicionalComercial,
            'minutos_adicional_especial' => $minutosAdicionalEspecial,
            'valor_adicional_comercial' => round($valorAdicionalComercial, 2),
            'valor_adicional_especial' => round($valorAdicionalEspecial, 2),
            'contrato' => $contrato,
        ];
    }
}
