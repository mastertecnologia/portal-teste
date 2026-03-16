<?php
namespace App\Utility;

/**
 * Validação e normalização de CNPJ (RN01).
 */
class CnpjUtil
{
    /**
     * Remove máscara e retorna apenas dígitos.
     */
    public static function limpar(string $cnpj): string
    {
        return preg_replace('/\D+/', '', $cnpj);
    }

    /**
     * Valida tamanho (14) e dígitos verificadores.
     * Rejeita sequências repetidas (00...0, 11...1, etc.).
     */
    public static function validar(string $cnpj): bool
    {
        $cnpj = self::limpar($cnpj);
        if (strlen($cnpj) !== 14) {
            return false;
        }
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }
        $peso1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $peso2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        for ($i = 0; $i < 12; $i++) {
            $soma += (int)$cnpj[$i] * $peso1[$i];
        }
        $resto = $soma % 11;
        $dv1 = $resto < 2 ? 0 : 11 - $resto;
        if ((int)$cnpj[12] !== $dv1) {
            return false;
        }
        $soma = 0;
        for ($i = 0; $i < 13; $i++) {
            $soma += (int)$cnpj[$i] * $peso2[$i];
        }
        $resto = $soma % 11;
        $dv2 = $resto < 2 ? 0 : 11 - $resto;
        return (int)$cnpj[13] === $dv2;
    }
}
