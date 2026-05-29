<?php
/**
 * Modal Nova conta bancária (mock modal-conta — pgm_erp_completo.html).
 *
 * @var \App\View\AppView $this
 * @var array<int,array<string,string>> $bancosCatalogo
 * @var bool $abrirModalConta
 */
$bancosCatalogo = $bancosCatalogo ?? [];
$abrirModalConta = !empty($abrirModalConta);
$inputTpl = ['inputContainer' => '{{content}}', 'selectContainer' => '{{content}}', 'checkboxContainer' => '{{content}}'];
$bancoOptions = array_reduce($bancosCatalogo, static function ($acc, $b) {
	$cod = (string)($b['codigo'] ?? '');
	if ($cod !== '') {
		$acc[$cod] = str_pad($cod, 3, '0', STR_PAD_LEFT) . ' · ' . ($b['nome'] ?? '');
	}

	return $acc;
}, []);
?>
<div class="modal-bg" id="modal-conta" role="dialog" aria-labelledby="modal-conta-title" aria-modal="true" aria-hidden="true">
	<div class="pgm-modal-conta-panel">
		<div style="padding:16px 20px;border-bottom:1px solid var(--border-light);display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,var(--teal-light),#fff);flex-shrink:0;">
			<div>
				<div id="modal-conta-title" style="font-size:16px;font-weight:700;">🏦 <?= h(__('Nova conta bancária')) ?></div>
				<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Cadastre uma nova conta para o sistema gerenciar')) ?></div>
			</div>
			<button type="button" class="btn btn-ghost btn-sm" data-pgm-close-modal="modal-conta" onclick="return closeModalOrc('modal-conta');" aria-label="<?= h(__('Fechar')) ?>">✕</button>
		</div>

		<?= $this->Form->create(null, [
			'url' => ['controller' => 'BancosPrototype', 'action' => 'salvarConta'],
			'id' => 'form-nova-conta-banco',
			'style' => 'display:flex;flex-direction:column;flex:1;min-height:0;',
		]) ?>

		<div class="pgm-modal-conta-body">
			<div class="g2">
				<div class="field">
					<label class="field-lbl" for="codigo-banco"><?= h(__('Banco')) ?> *</label>
					<?= $this->Form->control('codigo_banco', [
						'type' => 'select',
						'label' => false,
						'id' => 'codigo-banco',
						'empty' => __('Selecione...'),
						'options' => $bancoOptions,
						'required' => true,
						'templates' => $inputTpl,
					]) ?>
				</div>
				<div class="field">
					<label class="field-lbl" for="tipo-conta"><?= h(__('Tipo de conta')) ?> *</label>
					<?= $this->Form->control('tipo_conta', [
						'type' => 'select',
						'label' => false,
						'id' => 'tipo-conta',
						'options' => [
							'Conta Corrente PJ' => __('Conta Corrente PJ'),
							'Conta Corrente PF' => __('Conta Corrente PF'),
							'Conta Poupança' => __('Conta Poupança'),
							'Conta Investimento' => __('Conta Investimento'),
							'Conta Garantida' => __('Conta Garantida'),
						],
						'default' => 'Conta Corrente PJ',
						'required' => true,
						'templates' => $inputTpl,
					]) ?>
				</div>
			</div>

			<div class="g2" style="margin-top:10px;">
				<div class="field">
					<label class="field-lbl" for="agencia"><?= h(__('Agência')) ?> *</label>
					<?= $this->Form->control('agencia', [
						'type' => 'text',
						'label' => false,
						'id' => 'agencia',
						'placeholder' => '0000-0',
						'required' => true,
						'templates' => $inputTpl,
					]) ?>
				</div>
				<div class="field">
					<label class="field-lbl" for="conta"><?= h(__('Conta')) ?> *</label>
					<?= $this->Form->control('conta', [
						'type' => 'text',
						'label' => false,
						'id' => 'conta',
						'placeholder' => '00000-0',
						'required' => true,
						'templates' => $inputTpl,
					]) ?>
				</div>
			</div>

			<div class="g2" style="margin-top:10px;">
				<div class="field">
					<label class="field-lbl" for="apelido"><?= h(__('Apelido / identificação')) ?></label>
					<?= $this->Form->control('apelido', [
						'type' => 'text',
						'label' => false,
						'id' => 'apelido',
						'placeholder' => __('Ex: Conta principal · receitas'),
						'templates' => $inputTpl,
					]) ?>
				</div>
				<div class="field">
					<label class="field-lbl" for="saldo-inicial"><?= h(__('Saldo inicial')) ?></label>
					<?= $this->Form->control('saldo_inicial', [
						'type' => 'text',
						'label' => false,
						'id' => 'saldo-inicial',
						'placeholder' => 'R$ 0,00',
						'templates' => $inputTpl,
					]) ?>
				</div>
			</div>

			<div class="g2" style="margin-top:10px;">
				<div class="field">
					<label class="field-lbl" for="limite-cheque"><?= h(__('Limite cheque especial')) ?></label>
					<?= $this->Form->control('limite_cheque_especial', [
						'type' => 'text',
						'label' => false,
						'id' => 'limite-cheque',
						'placeholder' => 'R$ 0,00',
						'templates' => $inputTpl,
					]) ?>
				</div>
				<div class="field">
					<label class="field-lbl" for="conta-contabil"><?= h(__('Conta contábil vinculada')) ?></label>
					<?= $this->Form->control('conta_contabil', [
						'type' => 'select',
						'label' => false,
						'id' => 'conta-contabil',
						'options' => [
							'1.1.02.001' => '1.1.02.001 · ' . __('Bancos conta movimento'),
							'1.1.02.002' => '1.1.02.002 · ' . __('Bancos aplicação'),
							'1.1.03.001' => '1.1.03.001 · ' . __('Investimentos curto prazo'),
						],
						'default' => '1.1.02.001',
						'templates' => $inputTpl,
					]) ?>
				</div>
			</div>

			<div class="sec-title" style="margin-top:18px;"><?= h(__('Configurações de cobrança (boletos)')) ?></div>
			<div class="g2">
				<div class="field">
					<label class="field-lbl" for="carteira"><?= h(__('Carteira')) ?></label>
					<?= $this->Form->control('carteira', [
						'type' => 'text',
						'label' => false,
						'id' => 'carteira',
						'placeholder' => '17',
						'templates' => $inputTpl,
					]) ?>
				</div>
				<div class="field">
					<label class="field-lbl" for="convenio"><?= h(__('Convênio')) ?></label>
					<?= $this->Form->control('convenio', [
						'type' => 'text',
						'label' => false,
						'id' => 'convenio',
						'placeholder' => '1078541',
						'templates' => $inputTpl,
					]) ?>
				</div>
			</div>
			<div class="g2" style="margin-top:10px;">
				<div class="field">
					<label class="field-lbl" for="proximo-titulo"><?= h(__('Próx. nº título')) ?></label>
					<?= $this->Form->control('proximo_titulo', [
						'type' => 'text',
						'label' => false,
						'id' => 'proximo-titulo',
						'placeholder' => '000001',
						'templates' => $inputTpl,
					]) ?>
				</div>
				<div class="field">
					<label class="field-lbl" for="especie-titulo"><?= h(__('Espécie título')) ?></label>
					<?= $this->Form->control('especie_titulo', [
						'type' => 'select',
						'label' => false,
						'id' => 'especie-titulo',
						'options' => [
							'DM' => 'DM · ' . __('Duplicata Mercantil'),
							'NP' => 'NP · ' . __('Nota Promissória'),
							'RC' => 'RC · ' . __('Recibo'),
						],
						'default' => 'DM',
						'templates' => $inputTpl,
					]) ?>
				</div>
			</div>

			<div class="sec-title" style="margin-top:18px;"><?= h(__('Integrações')) ?></div>
			<div style="display:flex;flex-direction:column;gap:8px;">
				<label class="integracao-item">
					<?= $this->Form->checkbox('integracao_cnab', ['checked' => true, 'hiddenField' => true]) ?>
					<div>
						<div style="font-size:13px;font-weight:600;"><?= h(__('CNAB 240 (remessa e retorno bancário)')) ?></div>
						<div style="font-size:11px;color:var(--text-muted);"><?= h(__('Geração de boletos e baixa automática via arquivo')) ?></div>
					</div>
				</label>
				<label class="integracao-item">
					<?= $this->Form->checkbox('integracao_ofx', ['hiddenField' => true]) ?>
					<div>
						<div style="font-size:13px;font-weight:600;"><?= h(__('Open Banking · API extrato (OFX)')) ?></div>
						<div style="font-size:11px;color:var(--text-muted);"><?= h(__('Sincronização automática a cada 1h · requer certificado ICP-Brasil')) ?></div>
					</div>
				</label>
				<label class="integracao-item">
					<?= $this->Form->checkbox('integracao_pix', ['checked' => true, 'hiddenField' => true]) ?>
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

		<div class="pgm-modal-conta-footer">
			<button type="button" class="btn btn-ghost btn-sm pgm-modal-conta-btn-cancel" data-pgm-close-modal="modal-conta" onclick="return closeModalOrc('modal-conta');"><?= h(__('Cancelar')) ?></button>
			<button type="submit" class="btn btn-primary btn-sm pgm-modal-conta-btn-submit"><span aria-hidden="true">✓</span> <?= h(__('Cadastrar conta')) ?></button>
		</div>
		<?= $this->Form->end() ?>
	</div>
</div>
<script>
(function () {
	function mountModal() {
		var m = document.getElementById('modal-conta');
		if (!m || m.parentNode === document.body) {
			return m;
		}
		document.body.appendChild(m);
		return m;
	}
	function abrirCadastroConta() {
		var m = mountModal();
		if (!m) {
			return false;
		}
		m.classList.add('open');
		m.setAttribute('aria-hidden', 'false');
		document.body.classList.add('pgm-bancos-modal-open');
		var first = m.querySelector('#codigo-banco, select, input:not([type="hidden"])');
		if (first) {
			setTimeout(function () { first.focus(); }, 80);
		}
		return false;
	}
	function closeModalOrc(id) {
		var m = document.getElementById(id || 'modal-conta');
		if (!m) {
			return false;
		}
		m.classList.remove('open');
		m.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('pgm-bancos-modal-open');
		return false;
	}
	window.abrirCadastroConta = abrirCadastroConta;
	window.closeModalOrc = closeModalOrc;

	function bindModalUi() {
		mountModal();
		document.addEventListener('click', function (e) {
			var openBtn = e.target.closest('[data-pgm-open-conta-modal]');
			if (openBtn) {
				e.preventDefault();
				abrirCadastroConta();
				return;
			}
			var closeBtn = e.target.closest('[data-pgm-close-modal]');
			if (closeBtn) {
				e.preventDefault();
				closeModalOrc(closeBtn.getAttribute('data-pgm-close-modal'));
			}
		});
		var modal = document.getElementById('modal-conta');
		if (modal) {
			modal.addEventListener('click', function (e) {
				if (e.target === modal) {
					closeModalOrc('modal-conta');
				}
			});
		}
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				closeModalOrc('modal-conta');
			}
		});
		<?php if ($abrirModalConta) : ?>
		abrirCadastroConta();
		<?php endif; ?>
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bindModalUi);
	} else {
		bindModalUi();
	}
})();
</script>
