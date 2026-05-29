<?php $this->start('script'); ?>
<script>
window.abrirCadastroConta = window.abrirCadastroConta || function () {
	var m = document.getElementById('modal-conta');
	if (!m) {
		return false;
	}
	if (m.parentNode !== document.body) {
		document.body.appendChild(m);
	}
	m.classList.add('open');
	m.setAttribute('aria-hidden', 'false');
	document.body.classList.add('pgm-bancos-modal-open');
	return false;
};
window.closeModalOrc = window.closeModalOrc || function (id) {
	var m = document.getElementById(id || 'modal-conta');
	if (!m) {
		return false;
	}
	m.classList.remove('open');
	m.setAttribute('aria-hidden', 'true');
	document.body.classList.remove('pgm-bancos-modal-open');
	return false;
};
</script>
<?php $this->end(); ?>
