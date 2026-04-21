<?php
/**
 * @var \App\Model\Entity\Cliente $cliente
 * @var \App\Model\Entity\Cidade|null $cidade
 */
$this->Breadcrumbs->add('Clientes', ['controller' => 'Clientes', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Visualizar', [], ['class' => 'breadcrumb-item active']);
$nome = $cliente->tipo == C_ClientesTipoFisica ? h($cliente->nome) : h($cliente->razaosocial);
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<div class="d-flex justify-content-between align-items-center m-b-15">
				<h5 class="card-title m-b-0"><?= $nome ?></h5>
				<?= $this->Html->link('Editar', ['action' => 'edit', $cliente->id], ['class' => 'btn btn-sm btn-success']) ?>
			</div>
			<dl class="row m-b-0">
				<dt class="col-sm-3">Código do cliente</dt>
				<dd class="col-sm-9"><?= h((string)($cliente->public_code ?? '')) ?></dd>
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
