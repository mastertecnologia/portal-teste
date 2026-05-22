<?php
/**
 * Painel de contatos CRM (edição do cliente).
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Cliente $cliente
 * @var bool $cliContatosReady
 * @var \App\Model\Entity\ClientesContato[] $cliContatos
 */
use Cake\Routing\Router;

$cliContatosReady = !empty($cliContatosReady);
$cliContatos = $cliContatos ?? [];
$cfg = [
	'clienteId' => (int)$cliente->id,
	'ready' => $cliContatosReady,
	'urls' => [
		'list' => Router::url(['controller' => 'Clientes', 'action' => 'apiContatos', $cliente->id]),
		'save' => Router::url(['controller' => 'Clientes', 'action' => 'apiContatoSalvar', $cliente->id]),
		'delete' => Router::url(['controller' => 'Clientes', 'action' => 'apiContatoExcluir', $cliente->id]),
	],
];
?>
<div class="cli-contatos-crm" id="cli-contatos-crm" data-cli-contatos-root>
	<div class="cli-contatos-crm-head">
		<span class="cli-contatos-crm-title"><i class="fas fa-users" aria-hidden="true"></i> <?= h(__('Contatos do cliente')) ?></span>
		<?php if ($cliContatosReady) : ?>
		<button type="button" class="btn-cli-outline btn-cli-sm" data-cli-contato-add>
			<i class="fas fa-plus" aria-hidden="true"></i> <?= h(__('Adicionar')) ?>
		</button>
		<?php endif; ?>
	</div>
	<?php if (!$cliContatosReady) : ?>
	<p class="cli-wizard-info-text mb-0"><?= h(__('Execute a migration clientes_contatos para habilitar contatos múltiplos.')) ?></p>
	<?php else : ?>
	<ul class="cli-contatos-crm-list" id="cli-contatos-crm-list">
		<?php foreach ($cliContatos as $ct) :
			$nome = trim((string)$ct->nome);
			$parts = preg_split('/\s+/', $nome, -1, PREG_SPLIT_NO_EMPTY);
			$ini = strtoupper(substr($parts[0] ?? 'C', 0, 1)) . strtoupper(substr($parts[1] ?? '', 0, 1));
			$tone = ['teal', 'blue', 'rose', 'orange', 'purple'][(int)$ct->id % 5];
		?>
		<li class="cli-contatos-crm-item" data-contato-id="<?= (int)$ct->id ?>">
			<div class="cli-av cli-av--<?= h($tone) ?>"><?= h($ini) ?></div>
			<div class="cli-contatos-crm-body">
				<strong><?= h($nome) ?></strong>
				<?php if (!empty($ct->principal)) : ?><span class="cli-contatos-crm-badge"><?= h(__('Principal')) ?></span><?php endif; ?>
				<?php if (!empty($ct->cargo)) : ?><div class="cli-contatos-crm-meta"><?= h((string)$ct->cargo) ?></div><?php endif; ?>
				<?php if (!empty($ct->email)) : ?><div class="cli-contatos-crm-meta"><?= h((string)$ct->email) ?></div><?php endif; ?>
				<?php if (!empty($ct->fone)) : ?><div class="cli-contatos-crm-meta"><?= h((string)$ct->fone) ?></div><?php endif; ?>
			</div>
			<div class="cli-contatos-crm-act">
				<button type="button" class="btn-cli-ghost btn-cli-sm" data-cli-contato-edit="<?= (int)$ct->id ?>" title="<?= h(__('Editar')) ?>"><i class="fas fa-pen" aria-hidden="true"></i></button>
				<button type="button" class="btn-cli-ghost btn-cli-sm text-danger" data-cli-contato-del="<?= (int)$ct->id ?>" title="<?= h(__('Excluir')) ?>"><i class="fas fa-trash" aria-hidden="true"></i></button>
			</div>
		</li>
		<?php endforeach; ?>
	</ul>
	<p class="cli-contatos-crm-empty<?= $cliContatos !== [] ? ' d-none' : '' ?>" id="cli-contatos-crm-empty"><?= h(__('Nenhum contato cadastrado.')) ?></p>
	<?php endif; ?>
</div>
<script>window.PgmClienteContatosConfig = <?= json_encode($cfg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<?= $this->Html->script('/pgm-assets/js/modules/clientes/cliente-contatos') ?>
