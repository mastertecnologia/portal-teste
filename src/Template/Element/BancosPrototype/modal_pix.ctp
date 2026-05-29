<?php
/**
 * Modal cadastro chave PIX (mock modal-pix-cad — pgm_erp_completo.html).
 *
 * @var \App\View\AppView $this
 * @var array<int,array<string,mixed>> $tfContas
 * @var bool $abrirModalPix
 */
$tfContas = $tfContas ?? [];
$abrirModalPix = !empty($abrirModalPix);
$inputTpl = ['inputContainer' => '{{content}}', 'selectContainer' => '{{content}}', 'checkboxContainer' => '{{content}}'];
$contaOptions = [];
foreach ($tfContas as $c) {
	$id = (int)($c['id'] ?? 0);
	if ($id <= 0) {
		continue;
	}
	$contaOptions[$id] = (string)($c['label'] ?? $c['label_curta'] ?? ('#' . $id));
}
?>
<div class="modal-bg" id="modal-pix-cad" role="dialog" aria-labelledby="modal-pix-cad-title" aria-modal="true" aria-hidden="true">
	<div class="pgm-modal-conta-panel" style="width:min(540px,95vw);max-height:min(92vh,720px);">
		<div style="padding:16px 20px;border-bottom:1px solid var(--border-light);display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,var(--teal-light),#fff);flex-shrink:0;">
			<div>
				<div id="modal-pix-cad-title" style="font-size:16px;font-weight:700;">⚡ <?= h(__('Cadastrar nova chave PIX')) ?></div>
				<div style="font-size:12px;color:var(--text-muted);"><?= h(__('A chave será vinculada à conta bancária e ficará disponível para recebimento')) ?></div>
			</div>
			<button type="button" class="btn btn-ghost btn-sm" data-pgm-close-modal="modal-pix-cad" onclick="return closeModalPix('modal-pix-cad');" aria-label="<?= h(__('Fechar')) ?>">✕</button>
		</div>

		<?php if ($contaOptions === []) : ?>
		<div class="pgm-modal-conta-body">
			<div class="alert-box alert-amber">
				<?= h(__('Cadastre uma conta bancária com integração PIX antes de registrar chaves.')) ?>
			</div>
		</div>
		<div class="pgm-modal-conta-footer">
			<button type="button" class="btn btn-ghost btn-sm" data-pgm-close-modal="modal-pix-cad" onclick="return closeModalPix('modal-pix-cad');"><?= h(__('Fechar')) ?></button>
			<button type="button" class="btn btn-primary btn-sm" onclick="closeModalPix('modal-pix-cad'); if (typeof abrirCadastroConta === 'function') { abrirCadastroConta(); }"><?= h(__('Cadastrar conta')) ?></button>
		</div>
		<?php else : ?>
		<?= $this->Form->create(null, [
			'url' => ['controller' => 'BancosPrototype', 'action' => 'salvarChavePix'],
			'id' => 'form-cadastro-pix',
			'style' => 'display:flex;flex-direction:column;flex:1;min-height:0;',
		]) ?>
		<div class="pgm-modal-conta-body">
			<div class="field" style="margin-bottom:12px;">
				<label class="field-lbl" for="pix-cad-conta"><?= h(__('Conta vinculada')) ?> *</label>
				<?= $this->Form->control('financeiro_banco_id', [
					'type' => 'select',
					'label' => false,
					'id' => 'pix-cad-conta',
					'options' => $contaOptions,
					'required' => true,
					'templates' => $inputTpl,
				]) ?>
			</div>

			<div class="field" style="margin-bottom:12px;">
				<label class="field-lbl" for="pix-cad-tipo"><?= h(__('Tipo de chave')) ?> *</label>
				<select name="tipo" id="pix-cad-tipo" required onchange="pgmTrocarPlaceholderPIX()">
					<option value="cnpj"><?= h(__('CNPJ')) ?></option>
					<option value="cpf"><?= h(__('CPF')) ?></option>
					<option value="email"><?= h(__('E-mail')) ?></option>
					<option value="telefone"><?= h(__('Telefone')) ?></option>
					<option value="aleatoria"><?= h(__('Chave aleatória (gerar)')) ?></option>
				</select>
			</div>

			<div class="field" style="margin-bottom:12px;">
				<label class="field-lbl" for="pix-cad-valor"><?= h(__('Chave')) ?> *</label>
				<input type="text" name="valor" id="pix-cad-valor" placeholder="00.000.000/0000-00"/>
				<div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= h(__('Para chave aleatória, deixe em branco — o sistema gerará automaticamente.')) ?></div>
			</div>

			<div class="field" style="margin-bottom:12px;">
				<label class="field-lbl" for="pix-cad-apelido"><?= h(__('Apelido (opcional)')) ?></label>
				<input type="text" name="apelido" id="pix-cad-apelido" placeholder="<?= h(__('Ex: Recebimentos comerciais')) ?>"/>
			</div>

			<label class="integracao-item" style="margin-bottom:14px;">
				<?= $this->Form->checkbox('principal', ['hiddenField' => true]) ?>
				<div>
					<div style="font-size:13px;font-weight:600;"><?= h(__('Definir como chave principal da empresa')) ?></div>
				</div>
			</label>

			<div class="alert-box alert-blue" style="margin-bottom:0;">
				🔐 <?= h(__('A chave ficará registrada na conta bancária selecionada. Integração DICT/API bancária é roadmap — o portal persiste a chave para uso operacional.')) ?>
			</div>
		</div>

		<div class="pgm-modal-conta-footer">
			<button type="button" class="btn btn-ghost btn-sm" data-pgm-close-modal="modal-pix-cad" onclick="return closeModalPix('modal-pix-cad');"><?= h(__('Cancelar')) ?></button>
			<button type="submit" class="btn btn-primary btn-sm"><span aria-hidden="true">✓</span> <?= h(__('Cadastrar chave')) ?></button>
		</div>
		<?= $this->Form->end() ?>
		<?php endif; ?>
	</div>
</div>
<script>
(function () {
	function mountModalPix() {
		var m = document.getElementById('modal-pix-cad');
		if (!m || m.parentNode === document.body) {
			return m;
		}
		document.body.appendChild(m);
		return m;
	}
	function abrirCadastroPIX() {
		var m = mountModalPix();
		if (!m) {
			return false;
		}
		m.classList.add('open');
		m.setAttribute('aria-hidden', 'false');
		document.body.classList.add('pgm-bancos-modal-open');
		if (typeof pgmTrocarPlaceholderPIX === 'function') {
			pgmTrocarPlaceholderPIX();
		}
		var first = m.querySelector('#pix-cad-conta, #pix-cad-tipo, select, input:not([type="hidden"])');
		if (first) {
			setTimeout(function () { first.focus(); }, 80);
		}
		return false;
	}
	function closeModalPix(id) {
		var m = document.getElementById(id || 'modal-pix-cad');
		if (!m) {
			return false;
		}
		m.classList.remove('open');
		m.setAttribute('aria-hidden', 'true');
		if (!document.querySelector('.modal-bg.open')) {
			document.body.classList.remove('pgm-bancos-modal-open');
		}
		return false;
	}
	window.abrirCadastroPIX = abrirCadastroPIX;
	window.closeModalPix = closeModalPix;
	window.closeModalById = window.closeModalById || closeModalPix;

	window.pgmTrocarPlaceholderPIX = function () {
		var tipo = document.getElementById('pix-cad-tipo');
		var inp = document.getElementById('pix-cad-valor');
		if (!tipo || !inp) {
			return;
		}
		var ph = {
			cnpj: '00.000.000/0000-00',
			cpf: '000.000.000-00',
			email: 'seuemail@dominio.com.br',
			telefone: '(00) 00000-0000',
			aleatoria: '<?= h(__('Será gerada automaticamente')) ?>'
		};
		inp.placeholder = ph[tipo.value] || '';
		if (tipo.value === 'aleatoria') {
			inp.disabled = true;
			inp.removeAttribute('required');
			inp.value = '';
		} else {
			inp.disabled = false;
			inp.setAttribute('required', 'required');
		}
	};

	function bindModalPixUi() {
		mountModalPix();
		document.addEventListener('click', function (e) {
			var openBtn = e.target.closest('[data-pgm-open-pix-modal]');
			if (openBtn) {
				e.preventDefault();
				abrirCadastroPIX();
			}
		});
		var modal = document.getElementById('modal-pix-cad');
		if (modal) {
			modal.addEventListener('click', function (e) {
				if (e.target === modal) {
					closeModalPix('modal-pix-cad');
				}
			});
		}
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				closeModalPix('modal-pix-cad');
			}
		});
		<?php if ($abrirModalPix) : ?>
		abrirCadastroPIX();
		<?php endif; ?>
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bindModalPixUi);
	} else {
		bindModalPixUi();
	}
})();
</script>
