<?php
function Mask($mask, $str)
{
    if ($str === null || $str === '') {
        return '';
    }
    $mask = (string)$mask;
    $str = str_replace(' ', '', (string)$str);
    $len = strlen($str);
    for ($i = 0; $i < $len; $i++) {
        $pos = strpos($mask, '#');
        if ($pos === false) {
            break;
        }
        $mask[$pos] = $str[$i];
    }

    return $mask;
}

$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-premium']));
$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']));
$this->Breadcrumbs->add('Clientes', ['controller' => 'Clientes', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Pesquisa', [], ['class' => 'breadcrumb-item active']);

?>
<div class="col-md-12 p-0 cli-page--module">
	<div class="cli-root cli-layout-unificado">
		<div class="cli-section">
			<div class="cli-section-head">
				<div class="cli-section-icon"><i class="fas fa-search" aria-hidden="true"></i></div>
				<div class="cli-section-title">Pesquisa de clientes</div>
			</div>
			<div class="cli-section-body">
				<?= $this->Form->create('Clientes', ['type' => 'get', 'class' => 'form-material']); ?>
				<div class="row">
					<div class="col-lg-12 col-md-12">
						<div class="form-group">
							<label class="control-label text-muted">Busque por nome, razão social, nome fantasia, e-mail ou CNPJ/CPF (com ou sem máscara):</label>
							<?= $this->Form->control('keywords', ['class' => 'form-control', 'label' => false, 'required' => true]) ?>
						</div>
					</div>
				</div>
				<?= $this->Form->end(); ?>
				<div class="row">
					<div class="col-md-12">
						<div class="table-responsive">
							<table class="table table-hover table-row-clickable" id="tableClientes">
								<thead>
									<tr>
										<th width="25%">Razão Social</th>
										<th width="25%">Nome Fantasia</th>
										<th width="20%">E-mail</th>
										<th width="20%">Telefone</th>
										<th width="10%">Situação</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($clientes as $reg): ?>
									<tr>
										<?php
										$editUrl = $this->Url->build(['controller' => $reg->controller, 'action' => 'edit', $reg->id]);
										?>
										<td width="25%"><a class="link" href="<?= h($editUrl) ?>" data-turbo="false"><?= $reg->tipo == C_ClientesTipoFisica ? h($reg->nome) : h($reg->razaosocial) ?></a></td>
										<td width="25%"><a class="link" href="<?= h($editUrl) ?>" data-turbo="false"><?= h($reg->nomefantasia) ?></a></td>
										<td width="20%"><a class="link" href="<?= h($editUrl) ?>" data-turbo="false"><?= h($reg->email) ?></a></td>
										<td width="20%"><a class="link" href="<?= h($editUrl) ?>" data-turbo="false"><?php
											if (!empty($reg->fone)) {
												echo h(Mask('(###) ####-####', $reg->fone)) . '<br>';
											}
											if (!empty($reg->fone2)) {
												echo h(Mask('(###) #####-####', $reg->fone2));
											}
										?></a></td>
										<td width="10%"><a class="link" href="<?= h($editUrl) ?>" data-turbo="false"><?= h($reg->search) ?></a></td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<div class="clearfix"></div>
			</div>
		</div>
	</div>
</div>
<script>
window.onload = function() {
	$('#tableClientes [type="search"]').focus();
}
</script>
