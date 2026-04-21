<?php
/**
 * @var \App\Model\Entity\Cliente $cliente
 * @var \App\Model\Entity\Cidade|null $cidade
 */
$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-premium']));
$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']));
$this->Breadcrumbs->add('Clientes', ['controller' => 'Clientes', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Visualizar', [], ['class' => 'breadcrumb-item active']);
$nome = $cliente->tipo == C_ClientesTipoFisica ? h($cliente->nome) : h($cliente->razaosocial);
?>
<div class="col-md-12 p-0 cli-page--module">
	<div class="cli-root cli-layout-unificado">
		<div class="cli-section">
			<div class="cli-section-head">
				<div class="cli-section-icon"><i class="fas fa-eye" aria-hidden="true"></i></div>
				<div class="cli-section-title">Resumo do cliente</div>
			</div>
			<div class="cli-section-body">
				<div class="cli-page-head cli-page-head--embedded">
					<div class="cli-page-head-left">
						<div class="cli-eyebrow">Cadastro</div>
						<h1><?= $nome ?></h1>
					</div>
					<?= $this->Html->link('Editar cadastro', ['action' => 'edit', $cliente->id], ['class' => 'btn btn-sm btn-success', 'data-turbo' => 'false']) ?>
				</div>
				<dl class="row mb-0 cli-dl-view">
					<dt class="col-sm-3">Tipo</dt>
					<dd class="col-sm-9"><?= (int)$cliente->tipo === (int)C_ClientesTipoFisica ? 'Pessoa física' : 'Pessoa jurídica' ?></dd>
					<?php if (!empty($cliente->cnpj)): ?>
						<dt class="col-sm-3">CNPJ</dt>
						<dd class="col-sm-9"><?= h(formatCnpjCpf($cliente->cnpj)) ?></dd>
					<?php endif; ?>
					<?php if (!empty($cliente->cpf)): ?>
						<dt class="col-sm-3">CPF</dt>
						<dd class="col-sm-9"><?= h(formatCnpjCpf($cliente->cpf)) ?></dd>
					<?php endif; ?>
					<dt class="col-sm-3">E-mail</dt>
					<dd class="col-sm-9"><?= h((string)($cliente->email ?? '')) ?></dd>
					<dt class="col-sm-3">Cidade</dt>
					<dd class="col-sm-9"><?= $cidade ? h($cidade->nome) : '—' ?></dd>
					<dt class="col-sm-3">Situação</dt>
					<dd class="col-sm-9"><?= (int)$cliente->inativo === 0 ? 'Ativo' : 'Inativo' ?></dd>
				</dl>
			</div>
		</div>
	</div>
</div>
