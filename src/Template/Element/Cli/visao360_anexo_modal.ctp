<?php
/**
 * Modal de upload de anexos (Visão 360°) — vinculado a ticket do cliente.
 *
 * @var \App\View\AppView $this
 * @var int $cli360ClienteId
 * @var array<int,array{id:int,label:string}> $cli360AnexoTickets
 * @var string $cli360AnexoUploadBase URL base (…/tickets/api-anexo-upload) sem ID do ticket
 * @var array $cli360TicketAddUrl
 * @var array $cli360Visao360ArquivosUrl
 */
$cli360AnexoTickets = $cli360AnexoTickets ?? [];
$hasTickets = $cli360AnexoTickets !== [];
?>
<div class="modal fade none-border" id="modal-cli360-anexo" tabindex="-1" role="dialog" aria-labelledby="modal-cli360-anexo-title" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content cli-modal-cmp cli-360-anexo-modal">
			<div class="modal-header cli-360-anexo-modal-head">
				<h5 class="modal-title" id="modal-cli360-anexo-title">
					<i class="fas fa-paperclip" aria-hidden="true"></i> <?= h(__('Anexar arquivo')) ?>
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="<?= h(__('Fechar')) ?>">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<?php if ($hasTickets) : ?>
				<div class="form-group cli-cmp-field">
					<label class="cli-cmp-label" for="cli360-anexo-ticket"><?= h(__('Ticket')) ?> <span class="cli-req">*</span></label>
					<select class="form-control cli-cmp-input" id="cli360-anexo-ticket" required>
						<?php foreach ($cli360AnexoTickets as $opt) : ?>
						<option value="<?= (int)$opt['id'] ?>"><?= h((string)$opt['label']) ?></option>
						<?php endforeach; ?>
					</select>
					<p class="cli-360-anexo-hint small text-muted mb-0"><?= h(__('O arquivo ficará vinculado ao ticket selecionado e aparecerá na aba Arquivos.')) ?></p>
				</div>

				<input type="file" id="cli360-anexo-file" class="cli-360-anexo-file-input" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.txt,.zip" tabindex="-1" aria-hidden="true" />
				<div class="cli-360-anexo-dropzone" id="cli360-anexo-dropzone" role="button" tabindex="0" aria-label="<?= h(__('Área para enviar arquivos')) ?>">
					<div class="cli-360-anexo-dropzone-icon" aria-hidden="true"><i class="fas fa-cloud-upload-alt"></i></div>
					<p class="cli-360-anexo-dropzone-title"><?= h(__('Clique ou arraste arquivos aqui')) ?></p>
					<p class="cli-360-anexo-dropzone-sub"><?= h(__('PDF, imagens, Office · até 25 MB por arquivo')) ?></p>
					<button type="button" class="btn-cli-secondary btn-cli-sm cli-360-anexo-pick" id="cli360-anexo-pick"><?= h(__('Selecionar arquivos…')) ?></button>
				</div>

				<ul class="cli-360-anexo-filelist" id="cli360-anexo-filelist" aria-live="polite"></ul>

				<div class="form-group cli-cmp-field mb-0">
					<label class="cli-cmp-label" for="cli360-anexo-categoria"><?= h(__('Categoria')) ?></label>
					<select class="form-control cli-cmp-input" id="cli360-anexo-categoria">
						<option value="comprovante"><?= h(__('Comprovante')) ?></option>
						<option value="foto"><?= h(__('Foto / evidência')) ?></option>
						<option value="nf"><?= h(__('Nota fiscal / documento')) ?></option>
						<option value="laudo"><?= h(__('Laudo técnico')) ?></option>
						<option value="email"><?= h(__('E-mail / comunicação')) ?></option>
						<option value="outros" selected><?= h(__('Outros')) ?></option>
					</select>
				</div>
				<div class="form-group cli-cmp-field mb-0 mt-2">
					<label class="cli-cmp-label" for="cli360-anexo-desc"><?= h(__('Descrição (opcional)')) ?></label>
					<textarea class="form-control cli-cmp-input" id="cli360-anexo-desc" rows="2" maxlength="500" placeholder="<?= h(__('Detalhe sobre o anexo…')) ?>"></textarea>
				</div>
				<?php else : ?>
				<p class="mb-2"><?= h(__('Não há tickets deste cliente para vincular o anexo.')) ?></p>
				<p class="mb-0">
					<?= $this->Html->link(
						'<i class="fas fa-plus" aria-hidden="true"></i> ' . __('Abrir novo ticket'),
						$cli360TicketAddUrl,
						['class' => 'btn-cli-primary btn-cli-sm', 'escape' => false, 'data-turbo' => 'false']
					) ?>
				</p>
				<p class="cli-360-anexo-hint small text-muted mt-3 mb-0"><?= h(__('Após criar o ticket, volte aqui e use Anexar novamente.')) ?></p>
				<?php endif; ?>
				<p class="cli-360-anexo-err text-danger small d-none mb-0 mt-2" id="cli360-anexo-err" role="alert"></p>
			</div>
			<?php if ($hasTickets) : ?>
			<div class="modal-footer cli-360-anexo-modal-foot">
				<button type="button" class="btn btn-secondary" data-dismiss="modal"><?= h(__('Cancelar')) ?></button>
				<button type="button" class="btn btn-pgm btn-pgm-salvar btn-success" id="cli360-anexo-submit" disabled>
					<i class="fas fa-paperclip" aria-hidden="true"></i> <?= h(__('Anexar')) ?>
				</button>
			</div>
			<?php endif; ?>
		</div>
	</div>
</div>
