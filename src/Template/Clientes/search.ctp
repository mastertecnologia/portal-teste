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

// Breadcumbs
$this->Breadcrumbs->add('Clientes', ['controller' => 'Clientes', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Pesquisa', [], ['class' => 'breadcrumb-item active']);

?>

<div class="col-md-12">
	<div class="card" >
		<div class="card-body">
			<?= $this->Form->create('Clientes', ['type' => 'get', 'class' => 'form-material']); ?>
			<div class="row">
				<div class="col-lg-12 col-md-12">
					<div class="form-group ">
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
							<thead class="text-primary">
								<tr>
									<th width="25%">Razão Social</th>
									<th width="25%">Nome Fantasia</th>
									<th width="20%">E-mail</th>
									<th width="20%">Telefone</th>
									<th width="10%">Situação</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($clientes as $reg): 
									?>
									<tr>
										<td width="25%"> <a class='link' href='<?= $this->Url->build(["controller" => $reg->controller, "action" => "edit", $reg->id]) ?>'><?= $reg->tipo == C_ClientesTipoFisica ? $reg->nome : $reg->razaosocial ?></td>
										<td width="25%"> <a class='link' href='<?= $this->Url->build(["controller" => $reg->controller, "action" => "edit", $reg->id]) ?>'><?= $reg->nomefantasia ?></td>
										<td width="20%"> <a class='link' href='<?= $this->Url->build(["controller" => $reg->controller, "action" => "edit", $reg->id]) ?>'><?= $reg->email ?></td>
										<td width="20%"> <a class='link' href='<?= $this->Url->build(["controller" => $reg->controller, "action" => "edit", $reg->id]) ?>'><?php if(!empty($reg->fone)) echo Mask("(###) ####-####",$reg->fone).'<br>'; if(!empty($reg->fone2))echo Mask("(###) #####-####",$reg->fone2) ?></td>
										<td width="10%"> <a class='link' href='<?= $this->Url->build(["controller" => $reg->controller, "action" => "edit", $reg->id]) ?>'><?= $reg->search ?></td>
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
<script>
window.onload = function() {
	$('#tableClientes [type="search"]').focus();
}
</script>
