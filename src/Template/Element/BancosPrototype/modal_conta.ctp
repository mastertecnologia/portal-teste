<?php
/**
 * Modal Nova conta bancária (mock modal-conta).
 *
 * @var \App\View\AppView $this
 * @var array<int,array<string,string>> $bancosCatalogo
 */
$bancosCatalogo = $bancosCatalogo ?? [];
?>
<div class="modal-bg" id="modal-conta" role="dialog" aria-labelledby="modal-conta-title" aria-modal="true">
	<div style="background:#fff;border-radius:var(--radius-lg);width:min(680px,95vw);max-height:92vh;overflow-y:auto;box-shadow:var(--shadow-lg);">
		<div style="padding:16px 20px;border-bottom:1px solid var(--border-light);display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,var(--teal-light),#fff);">
			<div>
				<div id="modal-conta-title" style="font-size:16px;font-weight:700;">🏦 <?= h(__('Nova conta bancária')) ?></div>
				<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Cadastre uma nova conta para o sistema gerenciar')) ?></div>
			</div>
			<button type="button" class="btn btn-ghost btn-sm" data-pgm-close-modal="modal-conta" aria-label="<?= h(__('Fechar')) ?>">✕</button>
		</div>

		<?= $this->Form->create(null, [
			'url' => ['controller' => 'BancosPrototype', 'action' => 'salvarConta'],
			'id' => 'form-nova-conta-banco',
		]) ?>
		<div style="padding:18px 20px;">
			<div class="g2">
				<div class="field">
					<label><?= h(__('Banco')) ?> *</label>
					<?= $this->Form->control('codigo_banco', [
						'type' => 'select',
						'label' => false,
						'empty' => __('Selecione...'),
						'options' => array_reduce($bancosCatalogo, static function ($acc, $b) {
							$cod = (string)($b['codigo'] ?? '');
							if ($cod !== '') {
								$acc[$cod] = str_pad($cod, 3, '0', STR_PAD_LEFT) . ' · ' . ($b['nome'] ?? '');
							}

							return $acc;
						}, []),
						'required' => true,
						'class' => '',
					]) ?>
				</div>
				<div class="field">
					<label><?= h(__('Tipo de conta')) ?> *</label>
					<?= $this->Form->control('tipo_conta', [
						'type' => 'select',
						'label' => false,
						'options' => [
							'Conta Corrente PJ' => __('Conta Corrente PJ'),
							'Conta Corrente PF' => __('Conta Corrente PF'),
							'Conta Poupança' => __('Conta Poupança'),
							'Conta Investimento' => __('Conta Investimento'),
							'Conta Garantida' => __('Conta Garantida'),
							'Cooperativa PJ' => __('Cooperativa PJ'),
						],
						'default' => 'Conta Corrente PJ',
						'required' => true,
					]) ?>
				</div>
			</div>

			<div class="g2" style="margin-top:10px;">
				<div class="field">
					<label><?= h(__('Agência')) ?> *</label>
					<?= $this->Form->control('agencia', [
						'type' => 'text',
						'label' => false,
						'placeholder' => '0000-0',
						'required' => true,
					]) ?>
				</div>
				<div class="field">
					<label><?= h(__('Conta')) ?> *</label>
					<?= $this->Form->control('conta', [
						'type' => 'text',
						'label' => false,
						'placeholder' => '00000-0',
						'required' => true,
					]) ?>
				</div>
			</div>

			<div class="g2" style="margin-top:10px;">
				<div class="field">
					<label><?= h(__('Apelido / identificação')) ?></label>
					<?= $this->Form->control('apelido', [
						'type' => 'text',
						'label' => false,
						'placeholder' => __('Ex: Conta principal · receitas'),
					]) ?>
				</div>
				<div class="field">
					<label><?= h(__('Carteira')) ?></label>
					<?= $this->Form->control('carteira', [
						'type' => 'text',
						'label' => false,
						'placeholder' => '17',
					]) ?>
				</div>
			</div>

			<div class="g2" style="margin-top:10px;">
				<div class="field">
					<label><?= h(__('Convênio')) ?></label>
					<?= $this->Form->control('convenio', [
						'type' => 'text',
						'label' => false,
					]) ?>
				</div>
				<div class="field">
					<label><?= h(__('Próx. nº remessa')) ?></label>
					<?= $this->Form->control('proxima_remessa', [
						'type' => 'number',
						'label' => false,
						'default' => 1,
						'min' => 1,
					]) ?>
				</div>
			</div>

			<div class="sec-title" style="margin-top:18px;"><?= h(__('Integrações')) ?></div>
			<div style="display:flex;flex-direction:column;gap:8px;">
				<label style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--bg-surface);border-radius:var(--radius);cursor:pointer;">
					<?= $this->Form->checkbox('integracao_cnab', ['checked' => true, 'hiddenField' => false]) ?>
					<div>
						<div style="font-size:13px;font-weight:600;"><?= h(__('CNAB 240 (remessa e retorno bancário)')) ?></div>
						<div style="font-size:11px;color:var(--text-muted);"><?= h(__('Geração de boletos e baixa automática via arquivo')) ?></div>
					</div>
				</label>
				<label style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--bg-surface);border-radius:var(--radius);cursor:pointer;">
					<?= $this->Form->checkbox('integracao_ofx', ['hiddenField' => false]) ?>
					<div>
						<div style="font-size:13px;font-weight:600;"><?= h(__('Open Banking · API extrato (OFX)')) ?></div>
						<div style="font-size:11px;color:var(--text-muted);"><?= h(__('Sincronização automática · requer certificado ICP-Brasil')) ?></div>
					</div>
				</label>
				<label style="display:flex;align-items:center;gap:10px;padding:10px;background:var(--bg-surface);border-radius:var(--radius);cursor:pointer;">
					<?= $this->Form->checkbox('integracao_pix', ['checked' => true, 'hiddenField' => false]) ?>
					<div>
						<div style="font-size:13px;font-weight:600;"><?= h(__('PIX (recebimento + pagamento)')) ?></div>
						<div style="font-size:11px;color:var(--text-muted);"><?= h(__('Cadastro de chaves e QR Code')) ?></div>
					</div>
				</label>
			</div>

			<div class="alert-box alert-blue" style="margin-top:14px;">
				💡 <strong><?= h(__('Dica')) ?>:</strong> <?= h(__('Após criar, configure as chaves PIX e importe o primeiro extrato OFX para começar a conciliação.')) ?>
			</div>
		</div>

		<div style="padding:14px 20px;border-top:1px solid var(--border-light);display:flex;justify-content:flex-end;gap:8px;background:var(--bg-surface);">
			<button type="button" class="btn btn-ghost btn-sm" data-pgm-close-modal="modal-conta"><?= h(__('Cancelar')) ?></button>
			<button type="submit" class="btn btn-primary btn-sm">✓ <?= h(__('Cadastrar conta')) ?></button>
		</div>
		<?= $this->Form->end() ?>
	</div>
</div>
