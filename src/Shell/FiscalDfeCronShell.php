<?php
namespace App\Shell;

use App\Utility\Fiscal\FiscalAudit;
use App\Utility\Fiscal\FiscalCertificadoSecret;
use App\Utility\Fiscal\FiscalDfeDocZipSummary;
use App\Utility\Fiscal\FiscalImportacaoDfeEntrada;
use App\Utility\Fiscal\FiscalNotificacaoService;
use App\Utility\Fiscal\FiscalResNfeImportParser;
use App\Utility\Fiscal\FiscalSefazClient;
use App\Utility\Fiscal\FiscalSigner;
use App\Utility\Fiscal\FiscalStorage;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

/**
 * Cron para consulta automática de Distribuição DF-e (NFeDistribuicaoDFe — AN).
 *
 * Executa para cada empresa com certificado ativo e CNPJ válido.
 * Persiste `dfe_ult_nsu` e ingesta documentos na fila `fiscal_dfe_recebidos`.
 *
 * Uso:
 *   bin/cake fiscal_dfe_cron poll           # consulta todas as empresas
 *   bin/cake fiscal_dfe_cron poll --empresa=5  # apenas empresa id=5
 *   bin/cake fiscal_dfe_cron poll --max-pages=3  # máx. 3 páginas de NSU por empresa
 *
 * Crontab sugerido (a cada 4 horas):
 *   0 *\/4 * * * cd /var/www/portal && bin/cake fiscal_dfe_cron poll >> logs/fiscal_dfe_cron.log 2>&1
 */
class FiscalDfeCronShell extends Shell {

    public function initialize() {
        parent::initialize();
        $this->loadModel('FiscalEmpresasConfig');
        $this->loadModel('FiscalCertificados');
        $this->loadModel('FiscalDfeRecebidos');
        $this->loadModel('Empresas');
        Configure::load('fiscal');
        FiscalStorage::ensureDirectories();
    }

    public function getOptionParser() {
        $parser = parent::getOptionParser();
        $parser->setDescription('Cron automático de Distribuição DF-e (consulta nacional).');
        $parser->addSubcommand('poll', [
            'help' => 'Consulta DF-e para empresas com certificado ativo.',
        ]);
        $parser->addOption('empresa', [
            'help' => 'ID da empresa específica (opcional).',
            'short' => 'e',
        ]);
        $parser->addOption('max-pages', [
            'help' => 'Máximo de páginas de NSU por empresa (default: 5).',
            'default' => '5',
        ]);
        return $parser;
    }

    public function poll() {
        $maxPages = max(1, min(20, (int)($this->params['max-pages'] ?? 5)));
        $empresaId = $this->params['empresa'] ?? null;

        $query = $this->FiscalCertificados->find()
            ->where(['ativo' => true])
            ->group(['idempresa']);
        if ($empresaId) {
            $query->where(['idempresa' => (int)$empresaId]);
        }
        $certs = $query->toArray();

        if (empty($certs)) {
            $this->out('Nenhuma empresa com certificado ativo encontrada.');
            return;
        }

        $total = count($certs);
        $this->out("DF-e Cron: processando {$total} empresa(s)...");

        foreach ($certs as $cert) {
            $idempresa = (int)$cert->idempresa;
            $this->out("--- Empresa #{$idempresa} ---");

            try {
                $empresa = $this->Empresas->get($idempresa, ['fields' => ['id', 'cnpj']]);
                $cnpj = preg_replace('/\D/', '', (string)($empresa->cnpj ?? ''));
                if (strlen($cnpj) !== 14) {
                    $this->out("  CNPJ inválido, pulando.");
                    continue;
                }

                $certificado = $this->FiscalCertificados->getAtivo($idempresa);
                if (!$certificado) {
                    $this->out("  Sem certificado ativo, pulando.");
                    continue;
                }

                $configFiscal = $this->FiscalEmpresasConfig->getOrCreate($idempresa);
                $signer = new FiscalSigner(
                    FiscalCertificadoSecret::pfxBytes($certificado),
                    FiscalCertificadoSecret::decryptForUse($certificado->senha_hash, $idempresa)
                );
                $client = new FiscalSefazClient($signer, $configFiscal->toArray());

                $saved = $configFiscal->dfe_ult_nsu;
                $ultNsu = ($saved !== null && $saved !== '')
                    ? str_pad(preg_replace('/\D/', '', (string)$saved), 15, '0', STR_PAD_LEFT)
                    : str_pad('0', 15, '0', STR_PAD_LEFT);

                $totalDocs = 0;
                for ($page = 1; $page <= $maxPages; $page++) {
                    $this->out("  Página {$page}, ultNSU={$ultNsu}");
                    $result = $client->distribuicaoDfeInteresse($cnpj, $ultNsu);

                    if (empty($result['success'])) {
                        $this->out("  SEFAZ: [{$result['codigo']}] {$result['mensagem']}");
                        break;
                    }

                    $docCount = (int)($result['doc_zip_count'] ?? 0);
                    $totalDocs += $docCount;
                    $this->out("  Documentos nesta página: {$docCount}");

                    // Persist NSU
                    $retUlt = $result['ult_nsu'] ?? null;
                    if ($retUlt !== null && $retUlt !== '') {
                        $ultNsu = str_pad(preg_replace('/\D/', '', (string)$retUlt), 15, '0', STR_PAD_LEFT);
                        $configFiscal->dfe_ult_nsu = $ultNsu;
                        $this->FiscalEmpresasConfig->save($configFiscal);
                    }

                    // Ingest to queue
                    $xmlRet = (string)($result['xml_retorno'] ?? '');
                    if ($xmlRet !== '') {
                        FiscalStorage::writeDistribuicaoArtifact($idempresa, 'retorno', $xmlRet);
                        try {
                            $ingested = $this->FiscalDfeRecebidos->ingestarDeRetornoDistribuicao($idempresa, $xmlRet);
                            $this->out("  Ingestados na fila: {$ingested}");
                        } catch (\Throwable $e) {
                            $this->out("  Erro na ingestão: " . $e->getMessage());
                        }
                    }

                    // Check if there are more pages
                    $maxNsu = $result['max_nsu'] ?? null;
                    if ($maxNsu !== null && $ultNsu >= str_pad(preg_replace('/\D/', '', (string)$maxNsu), 15, '0', STR_PAD_LEFT)) {
                        $this->out("  Alcançou maxNSU, parando.");
                        break;
                    }
                    if ($docCount === 0) {
                        break;
                    }
                }

                $this->out("  Total documentos: {$totalDocs}, ultNSU final: {$ultNsu}");
                FiscalAudit::write('info', 'dfe_cron_poll', [
                    'idempresa' => $idempresa,
                    'total_docs' => $totalDocs,
                    'ult_nsu' => $ultNsu,
                ]);

                // Auto-importação: criar notas de entrada a partir da fila pendente
                if ($totalDocs > 0 && !empty($configFiscal->dfe_auto_import)) {
                    $this->_autoImportarPendentes($idempresa, $configFiscal);
                }

            } catch (\Exception $e) {
                $this->err("  Erro: " . $e->getMessage());
                Log::error("FiscalDfeCron empresa={$idempresa}: " . $e->getMessage());
            }
        }

        $this->out("DF-e Cron concluído.");
    }

    /**
     * Auto-importa documentos pendentes na fila DF-e como notas de entrada autorizadas.
     * Notifica a equipe no sino com a lista de notas criadas.
     */
    private function _autoImportarPendentes(int $idempresa, $configFiscal) {
        $pendentes = $this->FiscalDfeRecebidos->find()
            ->where([
                'idempresa' => $idempresa,
                'status' => 'pendente',
            ])
            ->order(['id' => 'ASC'])
            ->toArray();

        if (empty($pendentes)) {
            return;
        }

        $this->out("  Auto-importação: " . count($pendentes) . " documento(s) pendentes...");

        $notasImportadas = [];
        $erros = 0;

        foreach ($pendentes as $dfe) {
            // Só importar XMLs com infNFe (NF-e completas, não resumos)
            $xmlContent = (string)$dfe->xml_conteudo;
            $parsed = FiscalResNfeImportParser::parse($xmlContent);
            if ($parsed['dados'] === null) {
                // Resumo sem corpo (resNFe) — ignorar na auto-importação
                continue;
            }

            $result = FiscalImportacaoDfeEntrada::criarRascunhoDeDfe($idempresa, 1, (int)$dfe->id);

            if (!empty($result['ok']) && !empty($result['nota_id'])) {
                $notaId = (int)$result['nota_id'];

                // Promover para autorizada
                $Notas = TableRegistry::get('FiscalNotas');
                $nota = $Notas->get($notaId);
                $nota->status = 'autorizada';
                if (!empty($parsed['dados']['protocolo_autorizacao'])) {
                    $nota->protocolo_autorizacao = $parsed['dados']['protocolo_autorizacao'];
                }
                $Notas->save($nota);

                $notasImportadas[] = [
                    'nota_id' => $notaId,
                    'chave_acesso' => $parsed['dados']['chave_acesso'] ?? '',
                    'emit_nome' => $parsed['dados']['emit_xnome'] ?? 'Desconhecido',
                    'valor' => $parsed['dados']['valor_total'] ?? 0,
                ];

                $this->out("    Nota #{$notaId} criada (autorizada) — " . ($parsed['dados']['emit_xnome'] ?? ''));
            } else {
                $erros++;
            }
        }

        $this->out("  Auto-importação concluída: " . count($notasImportadas) . " notas, {$erros} erros/ignorados.");

        // Notificar equipe no sino
        if (!empty($notasImportadas) && !empty($configFiscal->dfe_auto_import_notificar)) {
            FiscalNotificacaoService::notificarNotasRecebidas($idempresa, $notasImportadas);
            $this->out("  Notificação enviada para equipe.");
        }
    }

    public function main() {
        $this->out($this->getOptionParser()->help());
    }
}
