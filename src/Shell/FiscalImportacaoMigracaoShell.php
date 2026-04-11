<?php
namespace App\Shell;

use App\Utility\Fiscal\FiscalImportacaoDfeEntrada;
use App\Utility\Fiscal\FiscalResNfeImportParser;
use Cake\Console\Shell;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

/**
 * Importação em massa de XMLs de NF-e de outro ERP.
 *
 * Processa uma pasta com XMLs ou um arquivo .zip, cria notas de entrada
 * com status "autorizada" (já autorizadas na SEFAZ pelo ERP anterior).
 *
 * Uso:
 *   bin/cake fiscal_importacao_migracao importar /caminho/pasta_xmls --empresa=5
 *   bin/cake fiscal_importacao_migracao importar /caminho/notas.zip --empresa=5
 *   bin/cake fiscal_importacao_migracao importar /caminho/ --empresa=5 --dry-run
 *   bin/cake fiscal_importacao_migracao importar /caminho/ --empresa=5 --rascunho  (cria como rascunho em vez de autorizada)
 *
 * Para volumes grandes (ex: 5 000+ notas), rode diretamente no servidor.
 */
class FiscalImportacaoMigracaoShell extends Shell {

    public function initialize() {
        parent::initialize();
        $this->loadModel('FiscalNotas');
        $this->loadModel('FiscalNotasXmls');
    }

    public function getOptionParser() {
        $parser = parent::getOptionParser();
        $parser->setDescription('Importação em massa de XMLs de NF-e (migração de outro ERP).');
        $parser->addSubcommand('importar', [
            'help' => 'Importa XMLs de uma pasta ou arquivo .zip.',
        ]);
        $parser->addArgument('caminho', [
            'help' => 'Caminho da pasta com XMLs ou arquivo .zip.',
            'required' => true,
        ]);
        $parser->addOption('empresa', [
            'help' => 'ID da empresa (obrigatório).',
            'short' => 'e',
        ]);
        $parser->addOption('dry-run', [
            'boolean' => true,
            'default' => false,
            'help' => 'Apenas simula, sem gravar no banco.',
        ]);
        $parser->addOption('rascunho', [
            'boolean' => true,
            'default' => false,
            'help' => 'Cria como rascunho em vez de autorizada.',
        ]);
        $parser->addOption('user', [
            'help' => 'ID do usuário para auditoria (default: 1).',
            'default' => '1',
        ]);
        return $parser;
    }

    public function importar() {
        $caminho = $this->args[0] ?? null;
        $idempresa = (int)$this->param('empresa');
        $dryRun = (bool)$this->param('dry-run');
        $comoRascunho = (bool)$this->param('rascunho');
        $userId = (int)$this->param('user');

        if (!$caminho) {
            $this->err('Informe o caminho da pasta ou arquivo .zip.');
            return Shell::CODE_ERROR;
        }
        if ($idempresa < 1) {
            $this->err('Informe --empresa=ID (obrigatório).');
            return Shell::CODE_ERROR;
        }

        $Empresas = TableRegistry::get('Empresas');
        $empresa = $Empresas->find()->where(['id' => $idempresa])->first();
        if (!$empresa) {
            $this->err("Empresa ID {$idempresa} não encontrada.");
            return Shell::CODE_ERROR;
        }

        $this->out(sprintf(
            'Empresa: %s (ID %d) | Modo: %s | %s',
            $empresa->razaosocial ?? $empresa->nome ?? $idempresa,
            $idempresa,
            $comoRascunho ? 'RASCUNHO' : 'AUTORIZADA',
            $dryRun ? '[DRY-RUN]' : 'GRAVAÇÃO REAL'
        ));

        $xmlFiles = $this->_coletarXmls($caminho);
        if ($xmlFiles === false) {
            return Shell::CODE_ERROR;
        }

        $this->out(sprintf('Encontrados %d arquivo(s) XML.', count($xmlFiles)));
        $this->out('');

        $ok = 0;
        $erros = 0;
        $duplicados = 0;
        $total = count($xmlFiles);

        foreach ($xmlFiles as $i => $xmlPath) {
            $filename = basename($xmlPath);
            $num = $i + 1;

            $xmlContent = @file_get_contents($xmlPath);
            if (empty($xmlContent)) {
                $this->err("[{$num}/{$total}] {$filename} — arquivo vazio ou ilegível.");
                $erros++;
                continue;
            }

            // Parse para verificar dados antes de gravar
            $parsed = FiscalResNfeImportParser::parse($xmlContent);
            if ($parsed['dados'] === null) {
                $msg = implode(' | ', $parsed['erros']);
                $this->err("[{$num}/{$total}] {$filename} — {$msg}");
                $erros++;
                continue;
            }

            $d = $parsed['dados'];

            // Verificar duplicata por chave de acesso
            if (!empty($d['chave_acesso'])) {
                $existente = $this->FiscalNotas->find()
                    ->select(['id'])
                    ->where([
                        'idempresa' => $idempresa,
                        'chave_acesso' => $d['chave_acesso'],
                    ])
                    ->first();
                if ($existente) {
                    $this->out("[{$num}/{$total}] {$filename} — já existe (nota #{$existente->id}), pulando.");
                    $duplicados++;
                    continue;
                }
            }

            if ($dryRun) {
                $this->out("[{$num}/{$total}] {$filename} — OK (chave: {$d['chave_acesso']}, valor: R$ " . number_format($d['valor_total'], 2, ',', '.') . ")");
                $ok++;
                continue;
            }

            // Importar via método existente (cria como rascunho)
            $result = FiscalImportacaoDfeEntrada::criarRascunhoDeXmlString($idempresa, $userId, $xmlContent);

            if (empty($result['ok'])) {
                $msg = implode(' | ', $result['errors'] ?? ['Falha']);
                $this->err("[{$num}/{$total}] {$filename} — {$msg}");
                $erros++;
                continue;
            }

            $notaId = (int)$result['nota_id'];

            // Se não é rascunho, promover para autorizada
            if (!$comoRascunho && $notaId > 0) {
                $this->_promoverAutorizada($notaId, $d);
            }

            $status = $comoRascunho ? 'rascunho' : 'autorizada';
            $this->out("[{$num}/{$total}] {$filename} — nota #{$notaId} ({$status})");
            $ok++;
        }

        $this->out('');
        $prefix = $dryRun ? '[DRY-RUN] ' : '';
        $this->out($prefix . sprintf(
            'Concluído: %d importados, %d duplicados (ignorados), %d erros — total: %d',
            $ok, $duplicados, $erros, $total
        ));

        Log::write('info', sprintf(
            'FiscalImportacaoMigracao::importar — %s%d OK, %d dup, %d erros (total %d), empresa %d',
            $prefix, $ok, $duplicados, $erros, $total, $idempresa
        ));

        // Limpar pasta temporária do ZIP se existir
        if (isset($this->_tmpZipDir) && is_dir($this->_tmpZipDir)) {
            $this->_rmdir($this->_tmpZipDir);
        }

        return Shell::CODE_SUCCESS;
    }

    /**
     * Promove nota de rascunho para autorizada com dados do XML original.
     */
    private function _promoverAutorizada(int $notaId, array $dados) {
        $nota = $this->FiscalNotas->get($notaId);
        $nota->status = 'autorizada';
        if (!empty($dados['protocolo_autorizacao'])) {
            $nota->protocolo_autorizacao = $dados['protocolo_autorizacao'];
        }
        if (!empty($dados['valor_total'])) {
            $nota->valor_total = $dados['valor_total'];
        }
        $this->FiscalNotas->save($nota);
    }

    /**
     * Coleta arquivos XML de uma pasta ou de dentro de um ZIP.
     *
     * @return string[]|false Lista de caminhos absolutos de XMLs ou false em caso de erro.
     */
    private function _coletarXmls(string $caminho) {
        // Se é um arquivo .zip
        if (is_file($caminho) && strtolower(pathinfo($caminho, PATHINFO_EXTENSION)) === 'zip') {
            return $this->_extrairZip($caminho);
        }

        // Se é uma pasta
        if (is_dir($caminho)) {
            return $this->_listarXmlsPasta($caminho);
        }

        $this->err("Caminho não encontrado ou não é pasta/ZIP: {$caminho}");
        return false;
    }

    /**
     * Lista todos os .xml em uma pasta (recursivo).
     */
    private function _listarXmlsPasta(string $dir): array {
        $xmls = [];
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'xml') {
                $xmls[] = $file->getPathname();
            }
        }
        sort($xmls);
        return $xmls;
    }

    /**
     * Extrai ZIP para pasta temporária e retorna lista de XMLs.
     */
    private function _extrairZip(string $zipPath): array {
        if (!class_exists('ZipArchive')) {
            $this->err('Extensão ZipArchive não disponível no PHP.');
            return [];
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $this->err("Não foi possível abrir o ZIP: {$zipPath}");
            return [];
        }

        $tmpDir = sys_get_temp_dir() . '/fiscal_migracao_' . uniqid();
        @mkdir($tmpDir, 0750, true);
        $zip->extractTo($tmpDir);
        $zip->close();

        $this->_tmpZipDir = $tmpDir;
        $this->out("ZIP extraído para: {$tmpDir}");

        return $this->_listarXmlsPasta($tmpDir);
    }

    private $_tmpZipDir = null;

    /**
     * Remove pasta temporária recursivamente.
     */
    private function _rmdir(string $dir) {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
        @rmdir($dir);
    }

    public function main() {
        $this->out($this->getOptionParser()->help());
    }
}
