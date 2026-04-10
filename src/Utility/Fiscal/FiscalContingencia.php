<?php
namespace App\Utility\Fiscal;

use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

/**
 * Gestão de contingência fiscal — EPEC e SVC-RS/SVC-AN.
 *
 * Modos de contingência NF-e 4.00:
 * - **EPEC** (Evento Prévio de Emissão em Contingência): regista evento na SEFAZ Nacional (AN)
 *   com dados mínimos; a NF-e é transmitida posteriormente quando o serviço autorizador volta.
 * - **SVC-RS / SVC-AN** (SEFAZ Virtual de Contingência): autorizador substituto; a NF-e é
 *   transmitida normalmente mas para um endpoint diferente.
 *
 * O modo ativo é gravado em `fiscal_empresas_config.contingencia_modo` (null = normal).
 */
class FiscalContingencia {

    /** Modos de contingência disponíveis */
    const MODO_NORMAL = null;
    const MODO_SVC_RS = 'svc_rs';
    const MODO_SVC_AN = 'svc_an';
    const MODO_EPEC   = 'epec';

    /**
     * Lista de modos para UI.
     */
    public static function modosDisponiveis(): array {
        return [
            ''       => 'Normal (autorizador padrão)',
            'svc_rs' => 'SVC-RS (SEFAZ Virtual de Contingência RS)',
            'svc_an' => 'SVC-AN (SEFAZ Virtual de Contingência AN)',
            'epec'   => 'EPEC (Evento Prévio de Emissão em Contingência)',
        ];
    }

    /**
     * Modo de contingência ativo para a empresa.
     *
     * @param int $idempresa
     * @return string|null null = normal
     */
    public static function modoAtivo(int $idempresa): ?string {
        $FiscalEmpresasConfig = TableRegistry::getTableLocator()->get('FiscalEmpresasConfig');
        $cfg = $FiscalEmpresasConfig->getOrCreate($idempresa);
        $modo = $cfg->contingencia_modo ?? null;
        if ($modo !== null && !in_array($modo, ['svc_rs', 'svc_an', 'epec'], true)) {
            return null;
        }
        return $modo;
    }

    /**
     * Ativa/desativa modo de contingência.
     *
     * @param int $idempresa
     * @param string|null $modo null para desativar
     * @param string $justificativa Motivo da ativação
     * @param int $userId
     * @return bool
     */
    public static function setModo(int $idempresa, ?string $modo, string $justificativa, int $userId): bool {
        if ($modo !== null && !in_array($modo, ['svc_rs', 'svc_an', 'epec'], true)) {
            return false;
        }
        $FiscalEmpresasConfig = TableRegistry::getTableLocator()->get('FiscalEmpresasConfig');
        $cfg = $FiscalEmpresasConfig->getOrCreate($idempresa);
        $old = $cfg->contingencia_modo;
        $cfg->contingencia_modo = $modo;
        $cfg->contingencia_justificativa = $modo ? $justificativa : null;
        $cfg->contingencia_inicio = $modo ? new \DateTime() : null;

        if ($FiscalEmpresasConfig->save($cfg)) {
            FiscalAudit::write('info', 'contingencia_alterada', [
                'idempresa' => $idempresa,
                'user_id' => $userId,
                'modo_anterior' => $old,
                'modo_novo' => $modo,
                'justificativa' => $justificativa,
            ]);
            return true;
        }
        return false;
    }

    /**
     * Retorna URLs dos webservices SVC para o ambiente.
     *
     * @param string $modo 'svc_rs' ou 'svc_an'
     * @param int $ambiente 1 ou 2
     * @return array<string, string>
     */
    public static function getWebservicesSvc(string $modo, int $ambiente): array {
        $key = ($modo === 'svc_an') ? 'svc_an' : 'svc_rs';
        return (array)Configure::read("Fiscal.webservices.{$key}.{$ambiente}") ?: [];
    }

    /**
     * Monta XML do EPEC (Evento Prévio de Emissão em Contingência).
     *
     * @param array $nota Dados mínimos da nota (chave_acesso, modelo, serie, numero, valor_total, etc.)
     * @param array $empresa Dados da empresa
     * @param array $destinatario Dados do destinatário
     * @param int $ambiente 1 ou 2
     * @return string XML do evento EPEC
     */
    public static function buildEpecXml(array $nota, array $empresa, array $destinatario, int $ambiente): string {
        $chave = $nota['chave_acesso'] ?? '';
        $cnpjEmit = preg_replace('/\D/', '', (string)($empresa['cnpj'] ?? ''));
        $ieEmit = preg_replace('/\D/', '', (string)($empresa['ie'] ?? ''));
        $ufEmit = strtoupper((string)($empresa['uf'] ?? 'SP'));
        $cufEmit = Configure::read('Fiscal.ufs.' . $ufEmit) ?? '35';

        $cnpjDest = preg_replace('/\D/', '', (string)($destinatario['cnpj'] ?? $destinatario['cpf'] ?? ''));
        $ufDest = strtoupper((string)($destinatario['uf'] ?? $ufEmit));
        $cufDest = Configure::read('Fiscal.ufs.' . $ufDest) ?? $cufEmit;
        $ieDest = preg_replace('/\D/', '', (string)($destinatario['ie'] ?? ''));
        $valorTotal = number_format((float)($nota['valor_total'] ?? 0), 2, '.', '');
        $valorIcms = number_format((float)($nota['valor_icms'] ?? 0), 2, '.', '');
        $valorSt = number_format((float)($nota['valor_icms_st'] ?? 0), 2, '.', '');

        $dhEvento = (new \DateTime())->format('Y-m-d\TH:i:sP');
        $tpEvento = '110140';
        $nSeqEvento = '1';
        $idEvento = 'ID' . $tpEvento . $chave . '01';

        $xml = '<envEvento xmlns="http://www.portalfiscal.inf.br/nfe" versao="1.00">'
            . '<idLote>1</idLote>'
            . '<evento xmlns="http://www.portalfiscal.inf.br/nfe" versao="1.00">'
            . '<infEvento Id="' . $idEvento . '">'
            . '<cOrgao>91</cOrgao>'
            . '<tpAmb>' . $ambiente . '</tpAmb>'
            . '<CNPJ>' . $cnpjEmit . '</CNPJ>'
            . '<chNFe>' . $chave . '</chNFe>'
            . '<dhEvento>' . $dhEvento . '</dhEvento>'
            . '<tpEvento>' . $tpEvento . '</tpEvento>'
            . '<nSeqEvento>' . $nSeqEvento . '</nSeqEvento>'
            . '<verEvento>1.00</verEvento>'
            . '<detEvento versao="1.00">'
            . '<descEvento>EPEC</descEvento>'
            . '<cOrgaoAutor>' . $cufEmit . '</cOrgaoAutor>'
            . '<tpAutor>1</tpAutor>'
            . '<verAplic>PGM1.0</verAplic>'
            . '<dhEmi>' . $dhEvento . '</dhEmi>'
            . '<tpNF>' . ((int)($nota['tipo_operacao'] ?? 1) === 0 ? '0' : '1') . '</tpNF>'
            . '<IE>' . $ieEmit . '</IE>'
            . '<dest>';

        if (strlen($cnpjDest) === 14) {
            $xml .= '<CNPJ>' . $cnpjDest . '</CNPJ>';
        } elseif (strlen($cnpjDest) === 11) {
            $xml .= '<CPF>' . $cnpjDest . '</CPF>';
        }
        $xml .= '<UF>' . $ufDest . '</UF>';
        if ($ieDest !== '') {
            $xml .= '<IE>' . $ieDest . '</IE>';
        }
        $xml .= '<vNF>' . $valorTotal . '</vNF>'
            . '<vICMS>' . $valorIcms . '</vICMS>'
            . '<vST>' . $valorSt . '</vST>'
            . '</dest>'
            . '</detEvento>'
            . '</infEvento>'
            . '</evento>'
            . '</envEvento>';

        return $xml;
    }

    /**
     * Verifica se Status SEFAZ indica indisponibilidade (para sugerir contingência).
     *
     * @param array $statusResult Retorno de FiscalSefazClient::statusServico()
     * @return bool true se o serviço NÃO está operacional
     */
    public static function sefazIndisponivel(array $statusResult): bool {
        if (empty($statusResult['success'])) {
            return true;
        }
        $cod = (int)($statusResult['codigo'] ?? 0);
        // 107 = serviço em operação
        return $cod !== 107;
    }
}
