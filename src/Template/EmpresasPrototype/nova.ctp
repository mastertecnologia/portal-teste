<?php
/**
 * Wizard · Nova empresa (5 passos) — mockup pg-empresa-nova.
 *
 * @var \App\View\AppView $this
 */
$H = $this->ErpPrototype;
$steps = [
	['label' => __('Dados básicos'), 'state' => 'active'],
	['label' => __('Endereço'), 'state' => 'pending'],
	['label' => __('Fiscal'), 'state' => 'pending'],
	['label' => __('Usuários'), 'state' => 'pending'],
	['label' => __('Confirmação'), 'state' => 'pending'],
];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Sistema · Nova empresa')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🏢 <?= h(__('Adicionar nova empresa ao grupo')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Multi-empresa: cada empresa tem CNPJ, configuração fiscal e usuários próprios')) ?></div>
	</div>
	<?= $this->Html->link('← ' . __('Cancelar'), ['controller' => 'EmpresasPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<?= $H->stepper($steps) ?>

<div class="card">
	<div class="sec-title"><?= h(__('Identificação')) ?></div>
	<div class="g2">
		<div class="field"><label><?= h(__('Razão social')) ?></label><input type="text" placeholder="<?= h(__('Ex.: PGM Soluções Ltda')) ?>"></div>
		<div class="field"><label><?= h(__('Nome fantasia')) ?></label><input type="text"></div>
		<div class="field"><label><?= h(__('CNPJ')) ?></label><input type="text" placeholder="00.000.000/0000-00"></div>
		<div class="field"><label><?= h(__('Inscrição estadual')) ?></label><input type="text"></div>
	</div>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Contato')) ?></div>
	<div class="g3">
		<div class="field"><label><?= h(__('Telefone')) ?></label><input type="tel"></div>
		<div class="field"><label><?= h(__('E-mail')) ?></label><input type="email"></div>
		<div class="field"><label><?= h(__('Site')) ?></label><input type="url"></div>
	</div>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Integração ERP')) ?></div>
	<div class="field">
		<label><?= h(__('URL ERP (.wso)')) ?></label>
		<input type="url" placeholder="http://10.0.2.7:85/WebGridPGM/">
	</div>
	<div class="alert-box alert-amber" style="margin-top:14px;">
		<?= h(__('Esta URL é usada pelo portal para chamar SOAP do ERP (WsProdutos, WSPGMPessoas, WSPGMContratos).')) ?>
	</div>
</div>

<div class="footer-bar">
	<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'EmpresasPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<div style="display:flex;gap:8px;">
		<?= $this->Html->link(__('Salvar rascunho'), ['controller' => 'Empresas', 'action' => 'add'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link(__('Continuar →'), ['controller' => 'Empresas', 'action' => 'add'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>
