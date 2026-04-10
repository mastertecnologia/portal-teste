<?php
namespace App\Utility\Fiscal;

use App\Utility\Fiscal\FiscalRegimeHelper;
use App\Utility\Fiscal\Nfse\NfseEmissorResolver;
use App\Utility\Fiscal\Nfse\NfseEmissorStub;

/**
 * Validações fiscais: CNPJ, CPF, Inscrição Estadual, chave de acesso NFe.
 */
class FiscalValidator {

    /**
     * Valida CNPJ.
     */
    public static function validarCnpj($cnpj) {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        if (strlen($cnpj) !== 14) return false;
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) return false;

        $soma = 0;
        $pesos = [5,4,3,2,9,8,7,6,5,4,3,2];
        for ($i = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $pesos[$i];
        }
        $resto = $soma % 11;
        $d1 = ($resto < 2) ? 0 : 11 - $resto;
        if ((int)$cnpj[12] !== $d1) return false;

        $soma = 0;
        $pesos = [6,5,4,3,2,9,8,7,6,5,4,3,2];
        for ($i = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $pesos[$i];
        }
        $resto = $soma % 11;
        $d2 = ($resto < 2) ? 0 : 11 - $resto;
        return (int)$cnpj[13] === $d2;
    }

    /**
     * Valida CPF.
     */
    public static function validarCpf($cpf) {
        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) !== 11) return false;
        if (preg_match('/^(\d)\1{10}$/', $cpf)) return false;

        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += $cpf[$i] * (10 - $i);
        }
        $resto = $soma % 11;
        $d1 = ($resto < 2) ? 0 : 11 - $resto;
        if ((int)$cpf[9] !== $d1) return false;

        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += $cpf[$i] * (11 - $i);
        }
        $resto = $soma % 11;
        $d2 = ($resto < 2) ? 0 : 11 - $resto;
        return (int)$cpf[10] === $d2;
    }

    /**
     * Valida chave de acesso da NFe (44 dígitos).
     */
    public static function validarChaveAcesso($chave) {
        $chave = preg_replace('/\D/', '', $chave);
        if (strlen($chave) !== 44) return false;

        $chave43 = substr($chave, 0, 43);
        $dvInformado = (int)$chave[43];
        $dvCalculado = FiscalCalculator::modulo11($chave43);

        return $dvInformado === $dvCalculado;
    }

    /**
     * Valida dados mínimos de uma nota para emissão.
     *
     * @param array $empresaEmitente Dados da empresa (ex.: entity Empresas) — CNPJ obrigatório para XML/SEFAZ
     * @return array Erros encontrados (vazio = válido)
     */
    public static function validarNotaParaEmissao(array $nota, array $config, array $empresaEmitente = []) {
        $erros = [];

        if (isset($nota['modelo']) && strtoupper((string)$nota['modelo']) === 'NFSE') {
            if (NfseEmissorResolver::isStubOnly($config)) {
                $erros[] = NfseEmissorStub::mensagemBloqueioEmissaoPortal();

                return $erros;
            }
            // Emissor concreto: regras adicionais podem ir aqui; o provedor valida em emitir().
        }

        // Empresa
        if (empty($nota['idempresa'])) {
            $erros[] = 'Empresa não definida.';
        }

        if ($empresaEmitente !== []) {
            $cnpj = preg_replace('/\D/', '', (string)($empresaEmitente['cnpj'] ?? ''));
            if (strlen($cnpj) !== 14) {
                $erros[] = 'CNPJ do emitente (cadastro da empresa) deve ter 14 dígitos.';
            } elseif (!self::validarCnpj($cnpj)) {
                $erros[] = 'CNPJ do emitente (cadastro da empresa) inválido (dígitos verificadores).';
            }
        }

        // Destinatário
        if (empty($nota['idcliente'])) {
            $erros[] = 'Destinatário/cliente não selecionado.';
        }

        // Natureza da operação
        if (empty($nota['natureza_operacao'])) {
            $erros[] = 'Natureza da operação não informada.';
        }

        // Itens
        $itens = $nota['fiscal_notas_itens'] ?? [];
        if (empty($itens)) {
            $erros[] = 'Nota fiscal sem itens.';
        }
        foreach ($itens as $i => $item) {
            $nItem = $i + 1;
            if (empty($item['descricao'])) {
                $erros[] = "Item {$nItem}: descrição não informada.";
            }
            if (empty($item['cfop'])) {
                $erros[] = "Item {$nItem}: CFOP não informado.";
            }
            if ((float)($item['quantidade'] ?? 0) <= 0) {
                $erros[] = "Item {$nItem}: quantidade deve ser maior que zero.";
            }
            if ((float)($item['valor_unitario'] ?? 0) <= 0) {
                $erros[] = "Item {$nItem}: valor unitário deve ser maior que zero.";
            }
            if ($nota['modelo'] === '55' && empty($item['ncm'])) {
                $erros[] = "Item {$nItem}: NCM obrigatório para NFe.";
            }
        }

        // Configuração fiscal
        if (empty($config['inscricao_estadual']) && $nota['modelo'] !== 'NFSE') {
            $erros[] = 'Inscrição Estadual não configurada.';
        }
        if (empty($config['uf'])) {
            $erros[] = 'UF do emitente não configurada.';
        }
        if (empty($config['codigo_municipio_ibge'])) {
            $erros[] = 'Código município IBGE não configurado.';
        }

        $modeloNota = strtoupper((string)($nota['modelo'] ?? ''));
        if ($modeloNota !== 'NFSE' && !FiscalRegimeHelper::empresaRegimeNormalProntaParaNfe($config)) {
            $erros[] = FiscalRegimeHelper::mensagemBloqueioEmissaoRegimeNormalIncompleto();
        }

        // Certificado
        if (empty($config['certificado_id'])) {
            $erros[] = 'Certificado digital não configurado.';
        }

        // Pagamentos
        $pagamentos = $nota['fiscal_notas_pagamentos'] ?? [];
        if (!empty($pagamentos)) {
            $totalPag = 0;
            foreach ($pagamentos as $p) {
                $totalPag += (float)($p['valor'] ?? 0);
            }
            $valorNota = (float)($nota['valor_total'] ?? 0);
            if (abs($totalPag - $valorNota) > 0.01 && $valorNota > 0) {
                $erros[] = 'Soma dos pagamentos (' . number_format($totalPag, 2, ',', '.')
                    . ') difere do valor total da nota (' . number_format($valorNota, 2, ',', '.') . ').';
            }
        }

        return $erros;
    }

    /**
     * Formata CNPJ: 00.000.000/0000-00
     */
    public static function formatarCnpj($cnpj) {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        if (strlen($cnpj) !== 14) return $cnpj;
        return substr($cnpj,0,2) . '.' . substr($cnpj,2,3) . '.' . substr($cnpj,5,3) . '/'
            . substr($cnpj,8,4) . '-' . substr($cnpj,12,2);
    }

    /**
     * Formata CPF: 000.000.000-00
     */
    public static function formatarCpf($cpf) {
        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) !== 11) return $cpf;
        return substr($cpf,0,3) . '.' . substr($cpf,3,3) . '.' . substr($cpf,6,3) . '-' . substr($cpf,9,2);
    }

    /**
     * Formata chave de acesso: XXXX XXXX XXXX XXXX XXXX XXXX XXXX XXXX XXXX XXXX XXXX
     */
    public static function formatarChaveAcesso($chave) {
        $chave = preg_replace('/\D/', '', $chave);
        return implode(' ', str_split($chave, 4));
    }
}
