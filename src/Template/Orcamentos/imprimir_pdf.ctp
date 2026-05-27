<?php
$cssFile = WWW_ROOT . 'css' . DS . 'orcamentos-imprimir-pdf.css';
$pdfCss = is_file($cssFile) ? file_get_contents($cssFile) : '';
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<style><?= $pdfCss ?></style>
</head>
<body class="orc-pdf-body">
	<div class="orc-pdf-doc">
		<?= $this->element('orcamentos_imprimir_paper', ['pdf' => true]) ?>
	</div>
</body>
</html>
