<?php
/**
 * Constantes de negócio compartilhadas (legado PGM).
 * Ajuste C_ChaveAcesso e C_Filial conforme o WebGrid/ERP (SOAP).
 *
 * @see LIGACAO_ERP_WINDOWS.md
 */
if (!defined('C_RoleCliente')) {
	define('C_RoleCliente', 1);
}
if (!defined('C_RoleFuncionario')) {
	define('C_RoleFuncionario', 0);
}
if (!defined('C_EmpresaMaster')) {
	define('C_EmpresaMaster', 1);
}
if (!defined('C_EmpresaPGM')) {
	define('C_EmpresaPGM', 2);
}
if (!defined('C_Filial')) {
	define('C_Filial', 1);
}
if (!defined('C_ChaveAcesso')) {
	define('C_ChaveAcesso', 'gridweb');
}
if (!defined('C_ClientesTipoFisica')) {
	define('C_ClientesTipoFisica', 1);
}
if (!defined('C_ClientesTipoJuridica')) {
	define('C_ClientesTipoJuridica', 2);
}
