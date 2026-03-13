<?php
function Mask($mask,$str){

    $str = str_replace(" ","",$str);

    for($i=0;$i<strlen($str);$i++){
        $mask[strpos($mask,"#")] = $str[$i];
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
						<label class="control-label text-muted">Informe a razão social ou o nome fantasia do cliente que deseja buscar no campo abaixo:</label>
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
								<th width="25%">Razão Social</th>
								<th width="25%">Nome Fantasia</th>
								<th width="20%">E-mail</th>
								<th width="20%">Telefone</th>
								<th width="10%">Situação</th>
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
