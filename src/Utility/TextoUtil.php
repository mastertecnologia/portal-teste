<?php
namespace App\Utility;

/**
 * Normalização de texto para busca (maiúsculas, sem acentos).
 */
class TextoUtil
{
    public static function normalizaParaBusca(?string $texto): string
    {
        $t = mb_strtoupper(trim((string)$texto), 'UTF-8');
        $map = [
            'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A', 'Ä' => 'A',
            'Ç' => 'C',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Õ' => 'O', 'Ô' => 'O', 'Ö' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
        ];
        return strtr($t, $map);
    }
}
