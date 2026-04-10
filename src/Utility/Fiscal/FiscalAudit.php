<?php
namespace App\Utility\Fiscal;

use Cake\Core\Configure;
use Cake\Log\Log;

/**
 * Auditoria operacional do módulo fiscal (emissão, cancelamento, CC-e, inutilização, importação DF-e→entrada, consChNFe).
 * Desligar: FISCAL_AUDIT_LOG=0 no .env ou Fiscal.audit_log => false em config.
 */
class FiscalAudit {

    /**
     * @return bool
     */
    public static function enabled() {
        $v = Configure::read('Fiscal.audit_log');
        if ($v === false) {
            return false;
        }
        if ($v === true || $v === null) {
            return true;
        }

        return (string)$v !== '0';
    }

    /**
     * @param string               $level  debug|info|notice|warning|error|critical
     * @param string               $action ex.: emitir, cancelar, carta_correcao
     * @param array<string, mixed> $context sem dados sensíveis (sem XML, sem senhas)
     */
    public static function write($level, $action, array $context) {
        if (!self::enabled()) {
            return;
        }
        $payload = array_merge(['acao' => $action], $context);
        $line = '[Fiscal] ' . $action . ' ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        Log::write($level, $line);
    }
}
