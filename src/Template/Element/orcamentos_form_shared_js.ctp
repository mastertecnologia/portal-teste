<?php
/**
 * JS compartilhado entre Novo orçamento (add) e Editar orçamento (edit):
 * totais, catálogo, itens, carrinho AJAX.
 *
 * @var string $mode 'add'|'edit'
 * @var mixed $idcarrinho id da sessão do carrinho (add)
 * @var int|null $orcamentoId id do orçamento (edit)
 * @var string $clientesMetaJson JSON de metadados de clientes (add)
 * @var string $produtosCatalogoJson JSON do catálogo
 */
use Cake\Routing\Router;

$mode = $mode ?? 'add';
$tipoServico = defined('C_ProdutosTipoServico') ? (int)C_ProdutosTipoServico : 1;
$tipoProduto = defined('C_ProdutosTipoProduto') ? (int)C_ProdutosTipoProduto : 0;
$juridicaTipo = (int)(defined('C_ClientesTipoJuridica') ? C_ClientesTipoJuridica : 1);

$clientesMetaJson = $clientesMetaJson ?? '{}';
$produtosCatalogoJson = $produtosCatalogoJson ?? '[]';

if ($mode === 'edit') {
	$carrinhoUrl = Router::url(['controller' => 'Orcamentos', 'action' => 'carrinhoedit']) . '/' . (int)($orcamentoId ?? 0);
	$carrinhoMethod = 'GET';
	$addservicoUrl = Router::url(['controller' => 'Orcamentos', 'action' => 'addservico']) . '/edit';
} else {
	$carrinhoUrl = Router::url(['controller' => 'Orcamentos', 'action' => 'carrinho', $idcarrinho ?? null]);
	$carrinhoMethod = 'POST';
	$addservicoUrl = Router::url(['controller' => 'Orcamentos', 'action' => 'addservico']);
}

$tipoLicenca = defined('C_ProdutosTipoLicenca') ? (int)C_ProdutosTipoLicenca : 2;
$tipoLocacao = defined('C_ProdutosTipoLocacao') ? (int)C_ProdutosTipoLocacao : 3;

$config = [
	'mode' => $mode,
	'orcamentoId' => $mode === 'edit' ? (int)($orcamentoId ?? 0) : 0,
	'tipoServico' => $tipoServico,
	'tipoProduto' => $tipoProduto,
	'tipoLicenca' => $tipoLicenca,
	'tipoLocacao' => $tipoLocacao,
	'tipoProdutoLegacy' => 1,
	'tipoServicoLegacy' => 2,
	'tipoLicencaLegacy' => 3,
	'tipoLocacaoLegacy' => 4,
	'juridicaTipo' => $juridicaTipo,
	'toggleTitleNew' => 'Proposta de Orçamento',
	'toggleTitleEdit' => 'Editando item do orçamento',
	'carrinhoUrl' => $carrinhoUrl,
	'carrinhoMethod' => $carrinhoMethod,
	'addservicoUrl' => $addservicoUrl,
	'limpacarrinhoUrl' => Router::url(['controller' => 'Orcamentos', 'action' => 'limpacarrinho']),
	'editaitemcarrinhoUrl' => Router::url(['controller' => 'Orcamentos', 'action' => 'editaitemcarrinho']),
	'produtoUrlBase' => Router::url(['controller' => 'Produtos', 'action' => 'produto']),
	'qtdestoqueUrlBase' => Router::url(['controller' => 'Produtos', 'action' => 'qtdestoque']),
];
$configJson = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$estoquesLoteJson = json_encode(Router::url(['controller' => 'Produtos', 'action' => 'estoquesLote']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<script>
window.orcClientesMeta = <?= $clientesMetaJson ?>;
window.orcProdutosCatalogo = <?= $produtosCatalogoJson ?>;
window.orcEstoquesLoteUrl = <?= $estoquesLoteJson ?>;
window.orcOrcamentoFormConfig = <?= $configJson ?>;
</script>
<?= $this->Html->script('/js/orcamentos_novo_edit_shared', ['block' => false]) ?>
