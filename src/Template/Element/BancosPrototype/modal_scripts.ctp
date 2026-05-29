<?php
/**
 * JS modal contas bancárias.
 *
 * @var \App\View\AppView $this
 * @var bool $abrirModalConta
 */
$abrirModalConta = !empty($abrirModalConta);
?>
<?php $this->start('script'); ?>
<script>
(function () {
	function abrirCadastroConta() {
		var m = document.getElementById('modal-conta');
		if (m) {
			m.classList.add('open');
		}
	}
	function fecharModalConta(id) {
		var m = document.getElementById(id || 'modal-conta');
		if (m) {
			m.classList.remove('open');
		}
	}
	window.abrirCadastroConta = abrirCadastroConta;
	window.closeModalOrc = fecharModalConta;

	document.querySelectorAll('[data-pgm-open-conta-modal]').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			abrirCadastroConta();
		});
	});
	document.querySelectorAll('[data-pgm-close-modal]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			fecharModalConta(btn.getAttribute('data-pgm-close-modal'));
		});
	});
	var modal = document.getElementById('modal-conta');
	if (modal) {
		modal.addEventListener('click', function (e) {
			if (e.target === modal) {
				fecharModalConta('modal-conta');
			}
		});
	}
	<?php if ($abrirModalConta) : ?>
	abrirCadastroConta();
	<?php endif; ?>
})();
</script>
<?php $this->end(); ?>
