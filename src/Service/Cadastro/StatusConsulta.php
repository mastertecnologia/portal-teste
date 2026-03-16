<?php
namespace App\Service\Cadastro;

/**
 * Status padronizados das consultas (dados cadastrais, IE, IM).
 */
final class StatusConsulta
{
    const SUCESSO = 'SUCESSO';
    const SEM_RESULTADO = 'SEM_RESULTADO';
    const NAO_EXECUTADO = 'NAO_EXECUTADO';
    const NAO_IMPLEMENTADO = 'NAO_IMPLEMENTADO';
    const ERRO = 'ERRO';
    const ERRO_TIMEOUT = 'ERRO_TIMEOUT';
    const ERRO_AUTENTICACAO = 'ERRO_AUTENTICACAO';
}
