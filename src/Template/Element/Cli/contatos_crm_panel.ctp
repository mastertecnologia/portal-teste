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
	'labels' => [
		'addTitle' => __('Novo contato'),
		'editTitle' => __('Editar contato'),
		'save' => __('Salvar'),
		'cancel' => __('Cancelar'),
		'deleteTitle' => __('Excluir contato'),
		'deleteConfirm' => __('Deseja excluir este contato? Esta ação não pode ser desfeita.'),
		'deleteBtn' => __('Excluir'),
		'errNome' => __('Informe o nome do contato.'),
		'errSave' => __('Não foi possível salvar.'),
	],
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
		<li class="cli-contatos-crm-item"
			data-contato-id="<?= (int)$ct->id ?>"
			data-contato-nome="<?= h($nome) ?>"
			data-contato-cargo="<?= h((string)$ct->cargo) ?>"
			data-contato-email="<?= h((string)$ct->email) ?>"
			data-contato-fone="<?= h((string)$ct->fone) ?>"
			data-contato-principal="<?= !empty($ct->principal) ? '1' : '0' ?>">
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

<?php if ($cliContatosReady) : ?>
<div class="modal fade none-border" id="modal-cli-contato" tabindex="-1" role="dialog" aria-labelledby="modal-cli-contato-title" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content cli-modal-cmp cli-contato-modal">
			<div class="modal-header cli-contato-modal-head">
				<h5 class="modal-title" id="modal-cli-contato-title"><?= h(__('Contato')) ?></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="<?= h(__('Fechar')) ?>">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<input type="hidden" id="cli-contato-id" value="" />
				<div class="form-group cli-cmp-field">
					<label class="cli-cmp-label" for="cli-contato-nome"><?= h(__('Nome')) ?> <span class="cli-req">*</span></label>
					<input type="text" class="form-control cli-cmp-input" id="cli-contato-nome" maxlength="120" autocomplete="name" />
				</div>
				<div class="form-group cli-cmp-field">
					<label class="cli-cmp-label" for="cli-contato-cargo"><?= h(__('Cargo / função')) ?></label>
					<input type="text" class="form-control cli-cmp-input" id="cli-contato-cargo" maxlength="80" placeholder="<?= h(__('Ex.: Diretor financeiro')) ?>" />
				</div>
				<div class="row">
					<div class="col-md-7 form-group cli-cmp-field">
						<label class="cli-cmp-label" for="cli-contato-email"><?= h(__('E-mail')) ?></label>
						<input type="email" class="form-control cli-cmp-input" id="cli-contato-email" maxlength="255" autocomplete="email" />
					</div>
					<div class="col-md-5 form-group cli-cmp-field">
						<label class="cli-cmp-label" for="cli-contato-fone"><?= h(__('Telefone')) ?></label>
						<input type="text" class="form-control cli-cmp-input" id="cli-contato-fone" maxlength="30" autocomplete="tel" />
					</div>
				</div>
				<div class="custom-control custom-checkbox">
					<input type="checkbox" class="custom-control-input" id="cli-contato-principal" />
					<label class="custom-control-label text-muted" for="cli-contato-principal"><?= h(__('Contato principal do cliente')) ?></label>
				</div>
				<p class="cli-contato-modal-err text-danger small d-none mb-0" id="cli-contato-err" role="alert"></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal"><?= h(__('Cancelar')) ?></button>
				<button type="button" class="btn btn-pgm btn-pgm-salvar btn-success" id="cli-contato-save">
					<i class="fas fa-check" aria-hidden="true"></i> <?= h(__('Salvar')) ?>
				</button>
			</div>
		</div>
	</div>
</div>
<div class="modal fade none-border" id="modal-cli-contato-del" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content cli-modal-cmp">
			<div class="modal-header">
				<h5 class="modal-title"><?= h(__('Excluir contato')) ?></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="<?= h(__('Fechar')) ?>"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<p class="mb-0" id="modal-cli-contato-del-text"><?= h(__('Deseja excluir este contato?')) ?></p>
				<input type="hidden" id="cli-contato-del-id" value="" />
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal"><?= h(__('Cancelar')) ?></button>
				<button type="button" class="btn btn-danger" id="cli-contato-del-confirm"><?= h(__('Excluir')) ?></button>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>

<script>window.PgmClienteContatosConfig = <?= json_encode($cfg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<?= $this->Html->script('/pgm-assets/js/modules/clientes/cliente-contatos') ?>
