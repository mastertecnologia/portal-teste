<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;

/**
 * Manutenção do módulo Fiscal (NF-e).
 *
 * Purge de cópias em BD (fiscal_notas_xmls): por defeito só conta linhas; com --execute apaga.
 * Ajuste legal de retenção é responsabilidade do contribuinte — use apenas com política interna definida.
 *
 * Uso:
 *   bin/cake fiscal_maintenance purge_xmls
 *   bin/cake fiscal_maintenance purge_xmls --days=730
 *   bin/cake fiscal_maintenance purge_xmls --days=730 --execute
 *   bin/cake fiscal_maintenance purge_inutilizacao
 *   bin/cake fiscal_maintenance purge_inutilizacao --days=730 --execute
 *   bin/cake fiscal_maintenance purge_distribuicao
 *   bin/cake fiscal_maintenance purge_distribuicao --days=730 --execute
 *   bin/cake fiscal_maintenance purge_dfe_recebidos
 *   bin/cake fiscal_maintenance purge_dfe_recebidos --days=365 --execute
 */
class FiscalMaintenanceShell extends Shell {

    public function getOptionParser() {
        $parser = parent::getOptionParser();
        $parser->setDescription('Manutenção do módulo Fiscal (purge de XMLs em BD, etc.).');
        $parser->addOption('days', [
            'short' => 'd',
            'default' => '',
            'help' => 'Apagar registos com created anterior a N dias (padrão: FISCAL_XML_RETENTION_DAYS / config fiscal).',
        ]);
        $parser->addOption('execute', [
            'boolean' => true,
            'default' => false,
            'help' => 'Efetivar DELETE; sem esta flag apenas mostra quantos registos seriam apagados.',
        ]);

        return $parser;
    }

    public function main() {
        $this->out('Subcomandos:');
        $this->out('  purge_xmls [--days=N] [--execute]  — fiscal_notas_xmls (BD)');
        $this->out('  purge_inutilizacao [--days=N] [--execute] — ficheiros em xml/inutilizacao');
        $this->out('  purge_distribuicao [--days=N] [--execute] — ficheiros em xml/distribuicao');
        $this->out('  purge_dfe_recebidos [--days=N] [--execute] — fila fiscal_dfe_recebidos (só pendente/ignorado; nunca vinculado)');
        $this->out('Ex.: bin/cake fiscal_maintenance purge_xmls --days=365');
    }

    public function purge_xmls() {
        Configure::load('fiscal');
        $daysOpt = isset($this->params['days']) ? trim((string)$this->params['days']) : '';
        $days = 0;
        if ($daysOpt !== '') {
            $days = (int)$daysOpt;
        } else {
            $hint = (int)Configure::read('Fiscal.xml_retention_days_hint', 365);
            $days = $hint > 0 ? $hint : 365;
        }
        if ($days < 30) {
            $this->err('purge_xmls: use --days>=30 (mínimo de segurança).');

            return;
        }

        $cutoff = FrozenTime::now()->subDays($days);
        $table = TableRegistry::get('FiscalNotasXmls');
        $count = $table->find()
            ->where(['created <' => $cutoff])
            ->count();

        $this->out(sprintf(
            '--- purge_xmls: fiscal_notas_xmls.created < %s (>%d dia(s)) — %d registo(s) ---',
            $cutoff->format('Y-m-d H:i:s'),
            $days,
            $count
        ));

        if ($count === 0) {
            return;
        }

        $execute = !empty($this->params['execute']);
        if (!$execute) {
            $this->out('Simulação: nada foi apagado. Repita com --execute para efetivar.');
            $this->out('(Ficheiros xml/inutilizacao: bin/cake fiscal_maintenance purge_inutilizacao.)');

            return;
        }

        $deleted = $table->deleteAll(['created <' => $cutoff]);
        $this->out(sprintf('Apagados %d registo(s) em fiscal_notas_xmls.', (int)$deleted));
    }

    /**
     * Remove ficheiros .xml antigos em storage/fiscal/xml/inutilizacao (por data de modificação).
     */
    public function purge_inutilizacao() {
        Configure::load('fiscal');
        $daysOpt = isset($this->params['days']) ? trim((string)$this->params['days']) : '';
        $days = 0;
        if ($daysOpt !== '') {
            $days = (int)$daysOpt;
        } else {
            $hint = (int)Configure::read('Fiscal.xml_retention_days_hint', 365);
            $days = $hint > 0 ? $hint : 365;
        }
        if ($days < 30) {
            $this->err('purge_inutilizacao: use --days>=30 (mínimo de segurança).');

            return;
        }

        $base = Configure::read('Fiscal.storage_path');
        $rel = Configure::read('Fiscal.paths.xml_inutilizacao');
        if (!is_string($base) || $base === '' || !is_string($rel) || $rel === '') {
            $this->err('purge_inutilizacao: Fiscal.storage_path ou paths.xml_inutilizacao não definidos.');

            return;
        }

        $dir = $base . DS . str_replace(['/', '\\'], DS, $rel);
        if (!is_dir($dir)) {
            $this->out('--- purge_inutilizacao: pasta inexistente — ' . $dir . ' ---');

            return;
        }

        $cutoffTs = FrozenTime::now()->subDays($days)->getTimestamp();
        $candidates = [];
        foreach (glob($dir . DS . '*.xml') ?: [] as $path) {
            if (!is_file($path)) {
                continue;
            }
            $mt = @filemtime($path);
            if ($mt !== false && $mt < $cutoffTs) {
                $candidates[] = $path;
            }
        }

        $n = count($candidates);
        $this->out(sprintf(
            '--- purge_inutilizacao: mtime < %s (>%d dia(s)) — %d ficheiro(s) ---',
            date('Y-m-d H:i:s', $cutoffTs),
            $days,
            $n
        ));

        if ($n === 0) {
            return;
        }

        $execute = !empty($this->params['execute']);
        if (!$execute) {
            $this->out('Simulação: nada foi apagado. Repita com --execute para efetivar.');

            return;
        }

        $ok = 0;
        foreach ($candidates as $path) {
            if (@unlink($path)) {
                $ok++;
            }
        }
        $this->out(sprintf('Removidos %d de %d ficheiro(s).', $ok, $n));
    }

    /**
     * Remove ficheiros .xml antigos em storage/fiscal/xml/distribuicao (por mtime).
     */
    public function purge_distribuicao() {
        Configure::load('fiscal');
        $daysOpt = isset($this->params['days']) ? trim((string)$this->params['days']) : '';
        $days = 0;
        if ($daysOpt !== '') {
            $days = (int)$daysOpt;
        } else {
            $hint = (int)Configure::read('Fiscal.xml_retention_days_hint', 365);
            $days = $hint > 0 ? $hint : 365;
        }
        if ($days < 30) {
            $this->err('purge_distribuicao: use --days>=30 (mínimo de segurança).');

            return;
        }

        $base = Configure::read('Fiscal.storage_path');
        $rel = Configure::read('Fiscal.paths.xml_distribuicao');
        if (!is_string($base) || $base === '' || !is_string($rel) || $rel === '') {
            $this->err('purge_distribuicao: Fiscal.storage_path ou paths.xml_distribuicao não definidos.');

            return;
        }

        $dir = $base . DS . str_replace(['/', '\\'], DS, $rel);
        if (!is_dir($dir)) {
            $this->out('--- purge_distribuicao: pasta inexistente — ' . $dir . ' ---');

            return;
        }

        $cutoffTs = FrozenTime::now()->subDays($days)->getTimestamp();
        $candidates = [];
        foreach (glob($dir . DS . '*.xml') ?: [] as $path) {
            if (!is_file($path)) {
                continue;
            }
            $mt = @filemtime($path);
            if ($mt !== false && $mt < $cutoffTs) {
                $candidates[] = $path;
            }
        }

        $n = count($candidates);
        $this->out(sprintf(
            '--- purge_distribuicao: mtime < %s (>%d dia(s)) — %d ficheiro(s) ---',
            date('Y-m-d H:i:s', $cutoffTs),
            $days,
            $n
        ));

        if ($n === 0) {
            return;
        }

        $execute = !empty($this->params['execute']);
        if (!$execute) {
            $this->out('Simulação: nada foi apagado. Repita com --execute para efetivar.');

            return;
        }

        $ok = 0;
        foreach ($candidates as $path) {
            if (@unlink($path)) {
                $ok++;
            }
        }
        $this->out(sprintf('Removidos %d de %d ficheiro(s).', $ok, $n));
    }

    /**
     * Remove registos antigos da fila DF-e (BD): apenas status pendente ou ignorado.
     * Registos vinculados a nota (vinculado) nunca são apagados por este comando.
     */
    public function purge_dfe_recebidos() {
        Configure::load('fiscal');
        $daysOpt = isset($this->params['days']) ? trim((string)$this->params['days']) : '';
        $days = 0;
        if ($daysOpt !== '') {
            $days = (int)$daysOpt;
        } else {
            $hint = (int)Configure::read('Fiscal.xml_retention_days_hint', 365);
            $days = $hint > 0 ? $hint : 365;
        }
        if ($days < 30) {
            $this->err('purge_dfe_recebidos: use --days>=30 (mínimo de segurança).');

            return;
        }

        $cutoff = FrozenTime::now()->subDays($days);
        $table = TableRegistry::get('FiscalDfeRecebidos');
        $count = $table->find()
            ->where([
                'created <' => $cutoff,
                'status IN' => ['pendente', 'ignorado'],
            ])
            ->count();

        $this->out(sprintf(
            '--- purge_dfe_recebidos: fiscal_dfe_recebidos (pendente|ignorado) com created < %s (>%d dia(s)) — %d registo(s) ---',
            $cutoff->format('Y-m-d H:i:s'),
            $days,
            $count
        ));

        if ($count === 0) {
            return;
        }

        $execute = !empty($this->params['execute']);
        if (!$execute) {
            $this->out('Simulação: nada foi apagado. Repita com --execute para efetivar.');

            return;
        }

        $deleted = $table->deleteAll([
            'created <' => $cutoff,
            'status IN' => ['pendente', 'ignorado'],
        ]);
        $this->out(sprintf('Apagados %d registo(s) em fiscal_dfe_recebidos.', (int)$deleted));
    }
}
