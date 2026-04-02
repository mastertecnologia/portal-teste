<?php
/** @var \App\View\AppView $this */
$editorId = isset($editorId) ? (string)$editorId : 'contract-template-conteudo-html';
?>
<script>
(function () {
	var editorId = <?= json_encode($editorId) ?>;
	function runInit() {
		if (typeof tinymce === 'undefined' || !document.getElementById(editorId)) {
			return;
		}
		if (tinymce.get(editorId)) {
			return;
		}
		tinymce.init({
			selector: '#' + editorId,
			plugins: 'advlist autolink link lists charmap preview autoresize hr textcolor fullscreen table paste code',
			height: 360,
			language: 'pt_BR',
			entity_encoding: 'raw',
			menubar: false,
			browser_spellcheck: true,
			setup: function (editor) {
				editor.addButton('pgm_ins_var', {
					text: '{{ }}',
					tooltip: 'Inserir variável',
					onclick: function () {
						var name = window.prompt('Nome da variável (sem chaves):', '');
						if (name && String(name).trim() !== '') {
							editor.insertContent('{{' + String(name).trim() + '}}');
						}
					}
				});
			},
			toolbar1: 'undo redo | bold italic underline | bullist numlist | alignleft aligncenter alignright | forecolor | table | link | pgm_ins_var | code | preview | fullscreen'
		});
	}
	if (typeof jQuery !== 'undefined') {
		jQuery(function () { runInit(); });
	} else {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', runInit);
		} else {
			runInit();
		}
	}
	window.pgmInsertContractTemplateVar = function (name) {
		if (!name || String(name).trim() === '') {
			return;
		}
		var ed = tinymce.get(editorId);
		if (ed) {
			ed.insertContent('{{' + String(name).trim() + '}}');
			ed.focus();
		}
	};
	if (typeof jQuery !== 'undefined') {
		jQuery(function () {
			jQuery('.contract-templates-form').on('submit', function () {
				if (typeof tinymce !== 'undefined') {
					tinymce.triggerSave();
				}
			});
		});
	}
})();
</script>
