<?php
namespace App\Shell;

use App\Service\Common\HttpClientService;
use Cake\Console\Shell;
use Cake\Log\Log;

/**
 * Importa a tabela NCM completa da BrasilAPI (fonte: Receita Federal / MDIC).
 *
 * Uso:
 *   bin/cake fiscal_ncm_import sync            # upsert completo (~13 000 registros)
 *   bin/cake fiscal_ncm_import sync --dry-run   # apenas exibe contagens, sem gravar
 *
 * Crontab sugerido (1x por mês, madrugada):
 *   0 3 1 * * cd /var/www/portal && bin/cake fiscal_ncm_import sync >> logs/fiscal_ncm_import.log 2>&1
 */
class FiscalNcmImportShell extends Shell {

    private $apiUrl = 'https://brasilapi.com.br/api/ncm/v1';

    public function initialize() {
        parent::initialize();
        $this->loadModel('FiscalNcm');
    }

    public function getOptionParser() {
        $parser = parent::getOptionParser();
        $parser->setDescription('Importa tabela NCM completa via BrasilAPI.');
        $parser->addSubcommand('sync', ['help' => 'Baixa e sincroniza NCMs (upsert).']);
        $parser->addOption('dry-run', [
            'boolean' => true,
            'default' => false,
            'help' => 'Apenas simula, sem gravar no banco.',
        ]);
        return $parser;
    }

    public function sync() {
        $dryRun = (bool)$this->param('dry-run');
        $this->out('Buscando NCMs na BrasilAPI...');

        $items = $this->_fetchApi();
        if ($items === false) {
            $this->err('Falha ao consultar a BrasilAPI. Verifique conectividade.');
            return Shell::CODE_ERROR;
        }
        if (!is_array($items) || empty($items)) {
            $this->err('Resposta vazia ou inválida da API.');
            return Shell::CODE_ERROR;
        }

        $this->out(sprintf('Recebidos %d registros.', count($items)));

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        $conn = $this->FiscalNcm->getConnection();

        foreach ($items as $item) {
            $codigo = trim($item['codigo'] ?? '');
            $descricao = trim($item['descricao'] ?? '');

            if ($codigo === '' || $descricao === '') {
                $skipped++;
                continue;
            }

            // Normalizar: remover pontos do código (API retorna "8471.30.12")
            $codigoLimpo = preg_replace('/[^0-9]/', '', $codigo);

            if (strlen($codigoLimpo) < 2 || strlen($codigoLimpo) > 10) {
                $skipped++;
                continue;
            }

            // Truncar descrição a 500 chars (limite da coluna)
            if (mb_strlen($descricao) > 500) {
                $descricao = mb_substr($descricao, 0, 497) . '...';
            }

            if ($dryRun) {
                $inserted++;
                continue;
            }

            $existing = $this->FiscalNcm->find()
                ->where(['codigo' => $codigoLimpo])
                ->first();

            if ($existing) {
                if ($existing->descricao !== $descricao) {
                    $existing->descricao = $descricao;
                    $this->FiscalNcm->save($existing);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                $entity = $this->FiscalNcm->newEntity([
                    'codigo' => $codigoLimpo,
                    'descricao' => $descricao,
                ]);
                if ($this->FiscalNcm->save($entity)) {
                    $inserted++;
                } else {
                    $skipped++;
                }
            }
        }

        $prefix = $dryRun ? '[DRY-RUN] ' : '';
        $this->out($prefix . sprintf(
            'Concluído: %d inseridos, %d atualizados, %d ignorados.',
            $inserted, $updated, $skipped
        ));

        Log::write('info', sprintf(
            'FiscalNcmImport::sync — %s%d inseridos, %d atualizados, %d ignorados (total API: %d)',
            $prefix, $inserted, $updated, $skipped, count($items)
        ));

        return Shell::CODE_SUCCESS;
    }

    /**
     * Fetch da API via HttpClientService. Retorna array decodificado ou false em erro.
     */
    protected function _fetchApi() {
        $result = HttpClientService::get($this->apiUrl, [], [
            'timeout' => 120,
            'headers' => ['Accept' => 'application/json'],
        ]);

        if (!$result['success']) {
            $this->err(sprintf(
                'HTTP erro: status %d — %s',
                $result['status'],
                $result['error'] ?? 'sem detalhe'
            ));
            return false;
        }

        return is_array($result['data']) ? $result['data'] : false;
    }

    public function main() {
        $this->out($this->getOptionParser()->help());
    }
}
