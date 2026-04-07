<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <?= $this->Html->charset() ?>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= h($this->fetch('title')) ?></title>
  <?= $this->fetch('css') ?>
</head>
<body>
  <?= $this->fetch('content') ?>
  <?= $this->fetch('script') ?>
</body>
</html>
