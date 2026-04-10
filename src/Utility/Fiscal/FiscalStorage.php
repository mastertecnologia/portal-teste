<?php
namespace App\Utility\Fiscal;

use Cake\Core\Configure;

/**
 * Garante diretório base e subpastas do módulo fiscal (config/fiscal.php).
 */
class FiscalStorage {

    /**
     * Cria storage_path e entradas de Fiscal.paths quando possível.
     */
    public static function ensureDirectories() {
        $base = Configure::read('Fiscal.storage_path');
        if ($base === null || $base === '') {
            return;
        }
        $base = (string)$base;
        $paths = Configure::read('Fiscal.paths');
        if (!is_dir($base)) {
            @mkdir($base, 0770, true);
        }
        if (!is_array($paths)) {
            return;
        }
        foreach ($paths as $sub) {
            if ($sub === null || $sub === '') {
                continue;
            }
            $full = $base . DS . str_replace(['/', '\\'], DS, (string)$sub);
            if (!is_dir($full)) {
                @mkdir($full, 0770, true);
            }
        }
    }

    /**
     * Grava XML de pedido/resposta de inutilização (sem vínculo a fiscal_notas).
     *
     * @param string $suffix rótulo curto (ex.: envio, retorno)
     * @return string|null caminho do ficheiro ou null se storage indisponível
     */
    public static function writeInutilizacaoArtifact(int $idempresa, string $suffix, string $body): ?string {
        if ($body === '') {
            return null;
        }
        $base = Configure::read('Fiscal.storage_path');
        if (!is_string($base) || $base === '') {
            return null;
        }
        $rel = Configure::read('Fiscal.paths.xml_inutilizacao');
        if (!is_string($rel) || $rel === '') {
            return null;
        }
        $dir = $base . DS . str_replace(['/', '\\'], DS, $rel);
        if (!is_dir($dir)) {
            @mkdir($dir, 0770, true);
        }
        $safe = preg_replace('/[^a-z0-9_-]+/i', '_', $suffix);
        $fn = $dir . DS . 'emp' . $idempresa . '_' . date('YmdHis') . '_' . $safe . '.xml';
        if (@file_put_contents($fn, $body) === false) {
            return null;
        }

        return is_file($fn) ? $fn : null;
    }

    /**
     * Grava XML de pedido/resposta da Distribuição DF-e (nacional).
     *
     * @param string $suffix ex.: pedido, retorno
     * @return string|null caminho do ficheiro ou null
     */
    public static function writeDistribuicaoArtifact(int $idempresa, string $suffix, string $body): ?string {
        if ($body === '') {
            return null;
        }
        $base = Configure::read('Fiscal.storage_path');
        if (!is_string($base) || $base === '') {
            return null;
        }
        $rel = Configure::read('Fiscal.paths.xml_distribuicao');
        if (!is_string($rel) || $rel === '') {
            return null;
        }
        $dir = $base . DS . str_replace(['/', '\\'], DS, $rel);
        if (!is_dir($dir)) {
            @mkdir($dir, 0770, true);
        }
        $safe = preg_replace('/[^a-z0-9_-]+/i', '_', $suffix);
        $fn = $dir . DS . 'emp' . $idempresa . '_' . date('YmdHis') . '_' . $safe . '.xml';
        if (@file_put_contents($fn, $body) === false) {
            return null;
        }

        return is_file($fn) ? $fn : null;
    }
}
