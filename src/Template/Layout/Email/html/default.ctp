<?php
/**
 * E-mail HTML: clientes de e-mail têm suporte limitado a CSS.
 * Base clara PGM + realce opcional em prefers-color-scheme: dark.
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html lang="pt-BR">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="color-scheme" content="light dark">
	<meta name="supported-color-schemes" content="light dark">
	<title><?= $this->fetch('title') ?></title>
	<style type="text/css">
		body {
			margin: 0;
			padding: 20px;
			font-family: Arial, Helvetica, sans-serif;
			font-size: 14px;
			line-height: 1.5;
			color: #1a1f2e;
			background-color: #f4f6f8;
		}
		a {
			color: #00a876;
		}
		@media (prefers-color-scheme: dark) {
			body {
				color: #e8eaed !important;
				background-color: #12151c !important;
			}
			a {
				color: #45e5ed !important;
			}
		}
	</style>
</head>
<body>
	<?= $this->fetch('content') ?>
</body>
</html>
