<?php
  	use Cake\Routing\Router;
	// Breadcumbs
	$this->Breadcrumbs->add('Clientes', ['controller' => 'Clientes', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Editar', [], ['class' => 'breadcrumb-item active']);

	function Mask($mask,$str) {
		if (empty($str)) return "";
		$str = str_replace(" ","",$str);
		for($i=0;$i<strlen($str);$i++) $mask[strpos($mask,"#")] = $str[$i];
		return $mask;
	}

	$pessoaFisica = $cliente->tipo == C_ClientesTipoFisica ? '' : 'hide';
	$pessoaJuridica = $cliente->tipo == C_ClientesTipoJuridica ? '' : 'hide';

	if($role == 1) $disabled = "disabled";
	else $disabled = null;

?>
<style>
	.table td, .table th { padding: 0.4rem;	}
</style>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<ul class="nav nav-tabs customtab m-b-20" role="tablist">
				<li class="nav-item"> <a class="nav-link active " data-toggle="tab" href="#cliente" role="tab" aria-selected="true"><span class="hidden-sm-up"><i class="ti-user"></i></span> <span class="hidden-xs-down">Cliente</span></a> </li>
				<?php if($permissaoacesso || $role == 0){ ?><li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#<?= $role == 1 ? 'acessosCliente' : 'acessos' ?>" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti ti-desktop"></i></span> <span class="hidden-xs-down">Acessos</span></a> </li> </li><?php } ?>
				<?php if($role == 0){ ?><li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#usuarios" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti ti-user"></i></span> <span class="hidden-xs-down">Usuários</span></a> </li> </li><?php } ?>
				<?php if($permissaoacesso || $role == 0){ ?><li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#contratos" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti ti-write"></i></span> <span class="hidden-xs-down">Contratos</span></a> </li><?php } ?>
				<?php if($permissaoacesso || $role == 0){ ?><li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#token" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti ti-key"></i></span> <span class="hidden-xs-down">Token</span></a> </li><?php } ?>
			</ul>
			<div class="tab-content">
				<div class="tab-pane active" id="cliente">
					<?=  $this->Form->create($cliente, ['class' => 'form-material']) ?>
						<div class="row">
							<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
								<label class="control-label text-muted"> Tipo </label>
								<?= $this->Form->control('tipo', ['title' => 'Tipo do cliente', 'options' => C_ClientesTipo, 'required' => true, 'class' => 'selectpicker form-control', 'label' => false,  $disabled]) ?>
							</div>
						</div>
						<br>
						<div class="row pessoaJuridica <?= $pessoaJuridica ?>">
							<div class="col-lg-5 col-md-4 col-sm-12 col-xs-12">
								<div class="form-group">
									<label class="control-label text-muted"> Razão Social </label>
									<?= $this->Form->control('razaosocial', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira a razão social',  $disabled]) ?>
								</div>
							</div>
							<div class="col-lg-5 col-md-4 col-sm-12 col-xs-12">
								<div class="form-group">
									<label class="control-label text-muted"> Nome Fantasia </label>
									<?= $this->Form->control('nomefantasia', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o nome fantasia',  $disabled]) ?>
								</div>
							</div>
							<div class="col-lg-2 col-md-4 col-sm-12 col-xs-12">
								<div class="form-group">
									<label class="control-label text-muted"> CNPJ  </label>
									<?= $this->Form->control('cnpj', ['class' => 'form-control', 'id' => 'cnpj', 'label' => false, 'placeholder' => 'Insira o CNPJ',  $disabled]) ?>
								</div>
							</div>
						</div>
						<?php if($role == 0){ ?>
						<div class="row pessoaJuridica <?= $pessoaJuridica ?>">
							<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
								<div class="form-group">
									<label class="control-label text-muted"> Nome do Responsável </label>
									<?= $this->Form->control('nomeresponsavel', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o nome',  $disabled]) ?>
								</div>
							</div>
							<div class="col-md-3 col-xs-12">
								<div class="form-group">
									<label class="control-label text-muted"> CPF </label>
									<?= $this->Form->control('cpf', ['id' => 'cpfresponsavel', 'class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o CPF',  $disabled]) ?>
								</div>
							</div>
							<div class="col-md-3 col-xs-12">
								<div class="form-group">
									<label class="control-label text-muted"> RG </label>
									<?= $this->Form->control('rg', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o RG',  $disabled]) ?>
								</div>
							</div>
						</div>
						<?php } ?>
						<div class="row pessoaFisica <?= $pessoaFisica ?>">
							<div class="col-lg-8 col-xs-12">
								<div class="form-group">
									<label class="control-label text-muted"> Nome </label>
									<?= $this->Form->control('nome', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o nome',  $disabled]) ?>
								</div>
							</div>
							<div class="col-lg-4 col-xs-12">
								<div class="form-group">
									<label class="control-label text-muted"> CPF </label>
									<?= $this->Form->control('cpf', ['id' => 'cpffisica', 'class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o CPF',  $disabled]) ?>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-lg-4 col-md-6 col-sm-12">
								<div class="form-group">
									<label class="control-label text-muted"> Endereço </label>
									<?= $this->Form->control('endereco', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o endereço', 'required' => true,  $disabled]) ?>
								</div>
							</div>
							<div class="col-lg-2 col-md-6 col-sm-12">
								<div class="form-group">
									<label class="control-label text-muted"> Nro. </label>
									<?= $this->Form->control('nroendereco', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o nro.', 'required' => true,  $disabled]) ?>
								</div>
							</div>
							<div class="col-lg-2 col-md-6 col-sm-12">
								<div class="form-group">
									<label class="control-label text-muted"> Bairro </label>
									<?= $this->Form->control('bairro', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o bairro', 'required' => true,  $disabled]) ?>
								</div>
							</div>
							<div class="col-lg-2 col-md-6 col-sm-12">
								<div class="form-group">
									<label class="control-label text-muted"> Complemento </label>
									<?= $this->Form->control('complemento', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira o complemento',  $disabled]) ?>
								</div>
							</div>
							<div class="col-lg-2 col-md-12 col-sm-12">
								<div class="form-group">
									<label class="control-label text-muted"> CEP <small class="text-muted"> </small> </label>
									<?= $this->Form->control('cep', ['class' => 'form-control', 'id' => 'cep', 'label' => false, 'placeholder' => 'Insira o CEP', 'required' => true,  $disabled]) ?>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
								<label class="control-label text-muted"> Cidade </label>
								<?= $this->Form->control('idcidade', ['data-live-search' => 'true', 'class' => 'selectpicker form-control', 'options' => $cidades, 'label' => false,  $disabled]) ?>
							</div>
							<div class="col-lg-3 col-md-3 col-sm-6 col-xs-6">
								<div class="form-group">
									<label class="control-label text-muted"> Telefone </label>
									<?= $this->Form->control('fone', ['class' => 'form-control', 'id' => 'fone', 'label' => false, 'placeholder' => 'Insira o telefone',  $disabled]) ?>
								</div>
							</div>
							<div class="col-lg-3 col-md-3 col-sm-6 col-xs-6">
								<div class="form-group">
									<label class="control-label text-muted"> Celular </label>
									<?= $this->Form->control('fone2', ['class' => 'form-control', 'id' => 'fone2', 'label' => false, 'placeholder' => 'Insira o celular',  $disabled]) ?>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-lg-4 col-md-6 col-sm-12">
								<div class="form-group">
									<label class="control-label text-muted">E-mail de faturamento</label>
									<?= $this->Form->email('email', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Ex.: financeiro@cliente.com.br', $disabled]) ?>
									<small class="form-text text-muted">Utilizado para envio de notas, boletos e comunicações financeiras.</small>
								</div>
							</div>
							<div class="col-lg-8 col-md-6 col-sm-12">
								<div class="form-group">
									<label class="control-label text-muted d-flex justify-content-between align-items-center">
										<span>E-mails de contato / responsáveis</span>
										<button type="button" class="btn btn-sm btn-outline-info btn-gerenciar-emails" data-toggle="modal" data-target="#modal-emails-contato">
											Gerenciar e-mails
										</button>
									</label>
									<?= $this->Form->hidden('emailresponsavel', ['id' => 'emailresponsavel', $disabled]) ?>
									<textarea id="emailresponsavel_display" class="form-control" rows="2" readonly placeholder="Nenhum e-mail de contato cadastrado"></textarea>
									<small class="form-text text-muted">E-mails usados para avisos gerais, suporte e comunicações operacionais.</small>
								</div>
							</div>
						</div>
						<div class="row pessoaJuridica <?= $pessoaJuridica ?>">
							<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
								<div class="form-group">
									<label class="control-label text-muted"> Inscrição Municipal <small>(somente números)</small>  </label>
									<?= $this->Form->control('inscricaomunicipal', ['onkeypress' => 'return SomenteNumero(event)', 'class' => 'form-control', 'label' => false, 'placeholder' => 'Insira a inscrição municipal',  $disabled]) ?>
								</div>
							</div>
							<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
								<div class="form-group">
									<label class="control-label text-muted"> Inscrição Estadual <small>(somente números)</small> </label>
									<?= $this->Form->control('inscricaoestadual', ['onkeypress' => 'return SomenteNumero(event)', 'class' => 'form-control', 'label' => false, 'placeholder' => 'Insira a inscrição estadual',  $disabled]) ?>
								</div>
							</div>
						</div>
						<?php if($role == 0){ ?>
							<div class="row">
								<div class="col-lg-2 col-md-3 col-sm-3 col-xs-12">
									<div class="form-group">
										<label class="control-label text-muted"> Senha para o cliente visualizar os acessos </label>
										<?= $this->Form->control('senha', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Insira a senha']) ?>
									</div>
								</div>
								<div class="custom-control custom-checkbox mr-sm-2 m-r-10 m-t-30">
									<?= $this->Form->checkbox('exibirsenhacliente', ['checked' => true, 'class' => 'custom-control-input', 'id' => 'exibirsenhacliente']); ?>
									<label class="custom-control-label text-muted" for="exibirsenhacliente">Exibir Senha </label>
								</div>
								<div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
									<div class="form-group">
										<label class='control-label text-muted'> Empresa dominante:  </label>
										<?= $this->Form->control('empresadominante', ['class' => 'form-control', 'label' => false, 'options' => $empresasOptSidebar]) ?>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-lg-2 col-md-3 col-sm-3 col-xs-12">
									<div class="custom-control custom-checkbox mr-sm-2 m-r-10 m-l-10 m-t-5">
										<?= $this->Form->checkbox('inativo', ['class' => 'custom-control-input', 'id' => 'inativo']); ?>
										<label class="custom-control-label text-muted" for="inativo">Inativo </label>
									</div>
								</div>
								<div class="col-lg-3 col-md-3 col-sm-3 col-xs-12">
									<div class="custom-control custom-checkbox mr-sm-2 m-r-10 m-l-10 m-t-5">
										<?= $this->Form->checkbox('contrato', ['class' => 'custom-control-input', 'id' => 'contrato']); ?>
										<label class="custom-control-label text-muted" for="contrato">Contrato </label>
									</div>
								</div>
								<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
									<?= $this->Form->button('Salvar cliente', ['class' => 'btn-enviar btn btn-success float-right salvarcliente']) ?>
								</div>
							</div>
						<?php } ?>
					<?= $this->Form->end(); ?>
				</div>
				<?php if($permissaoacesso || $role == 0){ ?>
				<div class="tab-pane" id="acessos">
					<?php if ($role == 0) { ?>
						<?= $this->Form->create(null, ['class' => 'form-material m-t-10', 'url' => ['controller' => 'Users', 'action' => 'permissaoacesso']]); ?>
							<div class="row">
								<div class="col-md-4 col-xs-12">
									<label class="control-label text-muted"> Usuários com permissão para acessar senhas, contratos e token: </label>
									<?= $this->Form->control('users._ids', ['value' => $usuariosValue, 'title' => 'Usuários', 'class' => 'form-control selectpicker', 'options' => $usuarios ,'label' => false]) ?>
								</div>
								<?= $this->Form->hidden('idcliente', ['value' => $cliente->id]); ?>
								<div class="col-md-4 col-xs-12">
									<?= $this->Form->button('Salvar', ['class' => 'btn btn-success m-t-25']) ?>
								</div>
							</div>
						<?= $this->Form->end(); ?>
						<?= $this->Form->create(null, ['class' => 'form-material m-t-10', 'url' => ['controller' => 'Cliacessos', 'action' => 'add']]); ?>
							<div class="row">
								<div class="col-md-3 col-xs-12">
									<label class="control-label m-b-0">Nome do serviço </label>
									<?= $this->Form->control('nomeservico', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe o nome do serviço']);?>
								</div>
								<div class="col-md-3 col-xs-12">
									<label class="control-label m-b-0">Usuário </label>
									<?= $this->Form->control('usuarioaa', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe o usuario', 'required' => true,]);?>
								</div>
								<div class="col-md-3 col-xs-12">
									<label class="control-label m-b-0">IP </label>
									<?= $this->Form->control('ip', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe o ip']);?>
								</div>
								<div class="col-3">
									<label class="control-label m-b-0">Protocolo </label>
									<?= $this->Form->control('protocolo', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe o protocolo', 'list' => 'protocolos']);?>
									<datalist id="protocolos">
										<?php foreach(C_ProtocolosArray as $reg) echo '<option value="'.$reg.'">'; ?>
									</datalist>
								</div>
							</div>
							<div class="row m-t-5">
								<div class="col-md-2 col-xs-12">
									<label class="control-label m-b-0">Provedor </label>
									<?= $this->Form->text('nome', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe o provedor']);?>
								</div>
								<div class="col-md-3 col-xs-12">
									<label class="control-label m-b-0">URL </label>
									<?= $this->Form->control('url', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe a url']);?>
								</div>
								<div class="col-md-2 col-xs-12">
									<label class="control-label m-b-0">Porta </label>
									<?= $this->Form->control('porta', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe a porta']);?>
								</div>
								<div class="col-md-2 col-xs-12">
									<label class="control-label m-b-0">Senha </label>
									<?= $this->Form->control('senha', ['type' => 'password', 'class' => 'form-control', 'label' => false, 'placeholder' => 'Informe a senha', 'required' => true,]);?>
								</div>
								<div class="col-md-2 col-xs-12">
									<label class="control-label m-b-0">Confirme a senha </label>
									<?= $this->Form->control('confirmasenha', ['type' => 'password', 'class' => 'form-control', 'label' => false, 'placeholder' => 'Informe novamente a senha', 'required' => true,]);?>
								</div>
							</div>
							<?= $this->Form->hidden('idcliente', ['value' => $cliente->id]); ?>
							<div class="row m-t-10">
								<div class="col-12">
									<?= $this->Form->button('Adicionar acesso', ['class' => 'btn btn-success float-right'], $cliente->id) ?>
									<a role="button" class='btn btn-danger btn-inativoAcessos text-white'>Exibir Inativos </a>
								</div>
							</div>
						<?= $this->Form->end(); ?>
					<?php } ?>
					<hr>
					<div class="table-responsive">
						<table class="table table-hover" id="tableAtivos">
							<thead class="text-primary">
								<th>Serviço</th>
								<th>Provedor</th>
								<th>IP</th>
								<th>Porta</th>
								<th>Usuário</th>
								<th>URL</th>
								<th>Protocolo</th>
								<th>Senha</th>
								<th>Ativo</th>
								<?php if ($role == 0) { ?> <th width="10%">Ações</th> <?php } ?>
							</thead>
							<tbody>
								<?php foreach ($acessos as $reg):
									$inativo = '';
									if ($reg->inativo)	$inativo = 'inativoAcessos';
								?>
									<tr class='vesetainativoAcessos <?= $inativo ?>'>
										<td><?= $reg->nomeservico ?></td>
										<td><?= $reg->nome ?></td>
										<td><?= $reg->ip ?></td>
										<td><?= $reg->porta ?></td>
										<td><?= $reg->usuario ?></td>
										<td><?= $reg->url ?></td>
										<td><?= $reg->protocolo ?></td>
										<td><a class="link senha" data-id="<?=$reg->id?>" href="#"> ********** </a></td>
										<td> <?= $reg->inativo ? 'Não' : 'Sim'; ?></td>
										<?php if ($role == 0) { ?>
											<td class="td-actions">
												<?= $this->Html->link('<i class="fa fa-edit"></i>', ["controller" => "cliacessos", "action" => "edit", $reg->id], ['rel' => 'tooltip', 'title' => 'Editar', 'class' => 'btn btn-warning btn-simple btn-xs', 'escape' => false, 'target' => '_blank']) ?>
												<?php if($admin) echo $this->Html->link('<i class="fa fa-times"></i>', ["controller" => "cliacessos", "action" => "delete", $reg->id], ['confirm' => 'Você confirma a exclusão deste acesso?', 'rel' => 'tooltip', 'title' => 'Excluir', 'class' => 'btn btn-danger btn-simple btn-xs', 'escape' => false]) ?>
											</td>
										<?php }?>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php } if($role == 0){ ?>
				<div class="tab-pane" id="usuarios">
					<div class="table-responsive">
						<table class="table table-hover" id="tableUsers">
							<thead class="text-primary">
								<tr>
									<th>Usuário</th>
									<th>E-mail</th>
									<th>Nome</th>
									<th>Status</th>
									<th width="10%">Ações</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($usuarios as $usr): ?>
									<?php
										$label = $usr->inativo ? 'danger' : 'success';
										$sit   = $usr->inativo ? 'Inativo' : 'Ativo';
									?>
									<tr>
										<td><?= h($usr->username) ?></td>
										<td><?= h($usr->email) ?></td>
										<td><?= h($usr->name) ?></td>
										<td><span class="label label-<?= $label ?>"><?= $sit ?></span></td>
										<td class="td-actions">
											<?= $this->Html->link('<i class="fa fa-edit"></i>', ["controller" => "Users", "action" => "editcliente", $usr->id], ['rel' => 'tooltip', 'title' => 'Editar usuário do cliente', 'class' => 'btn btn-warning btn-simple btn-xs', 'escape' => false, 'target' => '_blank']) ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php } if($permissaoacesso || $role == 0){ ?>
				<div class="tab-pane" id="contratos">
					<?= $this->Html->link('Cadastrar item', ['controller' => 'Clicontratos', 'action' => 'add', $cliente->id], ['class' => 'btn btn-success  m-r-5 m-b-20']) ?>
					<?= $this->Html->link('Contratos de Horas Técnicas', ['controller' => 'ContratosHoras', 'action' => 'index', $cliente->id], ['class' => 'btn btn-info m-r-5 m-b-20']) ?>
					<?= $this->Html->link('Cadastrar Contrato de Horas', ['controller' => 'ContratosHoras', 'action' => 'add', $cliente->id], ['class' => 'btn btn-orange text-white m-r-5 m-b-20']) ?>
					<div class="table-responsive">
						<table class="table table-hover" id="tableContratos">
							<thead class="text-primary">
								<th>Cód. Produto</th>
								<th>Descrição </th>
								<th>Inf. Adicional</th>
								<th>Vl. Unit.</th>
								<th>Qtde</th>
								<th>Vl. Total</th>
								<th>Dt. Contratação</th>
								<th>Dt. Validade</th>
								<th>Dt. Cancelamento</th>
								<th>Ações</th>
							</thead>
							<tbody>
								<?php foreach ($contratos as $reg): ?>
									<tr>
										<td><?= $reg->codproduto ?></td>
										<td><?= $reg->descricao ?></td>
										<td><?= $reg->infadicional ?></td>
										<td><?= number_format($reg->vlunit, 2, ',', '.') ?></td>
										<td><?= $reg->qtde ?></td>
										<td><?= number_format($reg->vltotal, 2, ',', '.') ?></td>
										<td><?php if(!empty($reg->dtcontratacao)) 	echo date_format($reg->dtcontratacao, 'd/m/Y') ?></td>
										<td><?php if(!empty($reg->dtvalidade)) 		echo date_format($reg->dtvalidade, 'd/m/Y') ?></td>
										<td><?php if(!empty($reg->dtcancelamento)) 	echo date_format($reg->dtcancelamento, 'd/m/Y') ?></td>
										<td><?= $this->Html->link('<i class="fa fa-edit"></i>', ["controller" => "clicontratos", "action" => "edit", $reg->id], ['rel' => 'tooltip', 'title' => 'Editar', 'class' => 'btn btn-warning btn-simple btn-xs', 'escape' => false, 'target' => '_blank']) ?>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php } if($role == 1 ){ ?>
				<div class="tab-pane" id="acessosCliente">
					<div class="table-responsive">
						<table class="table table-hover" id="tableAcessosClientes">
							<thead class="text-primary">
								<th>Provedor</th>
								<th>IP</th>
								<th>Usuário</th>
								<th>Senha</th>
								<th>Ativo</th>
								<?php if ($role == 0) echo '<th>Ações</th>' ; ?>
							</thead>
							<tbody>
								<?php foreach ($acessos as $reg):
								$inativo = '';
								if ($reg->inativo)	$inativo = 'inativoAcessos';
								?>
									<tr class='vesetainativoAcessos <?= $inativo ?>'>
										<td><?= $reg->nome ?></td>
										<td><?= $reg->ip ?></td>
										<td><?= $reg->usuario ?></td>
										<td><a class="link senha" data-id="<?=$reg->id?>" href="#"><?=$reg->senha?></a></td>
										<td> <?= $reg->inativo == 1 ? 'Não' : 'Sim'; ?></td>
										<?php if ($role == 0) { ?>
										<td class="td-actions">
											<?= $this->Html->link('<i class="fa fa-edit"></i>', ["controller" => "cliacessos", "action" => "edit", $reg->id], ['rel' => 'tooltip', 'title' => 'Editar', 'class' => 'btn btn-warning btn-simple btn-xs', 'escape' => false]) ?>
											<?php if($admin){ ?>
											<?= $this->Html->link('<i class="fa fa-times"></i>', ["controller" => "cliacessos", "action" => "delete", $reg->id], ['confirm' => 'Você confirma a exclusão deste acesso?', 'rel' => 'tooltip', 'title' => 'Excluir', 'class' => 'btn btn-danger btn-simple btn-xs', 'escape' => false]) ?>
											<?php } ?>
										</td>
										<?php }?>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php }if($permissaoacesso || $role == 0){ ?>
				<div class="tab-pane" id="token">
					<div class="row">
						<div class="col-12">
							<label class="control-label text-muted"> Token: </label> <br>
							<?= $cliente->token ?>
						</div><?php if($role == 0) { ?>
						<div class="col-12">
							<?= $this->Html->link('Atualizar Token', [], ['class' => 'btn-atualizaToken btn btn-success float-right salvarcliente']) ?>
						</div><?php } ?>
					</div>
				</div>
				<?php }  ?>
			</div>
		</div>
	</div>
</div>
<!-- Modal gerir e-mails de contato -->
<div class="modal fade none-border" id="modal-emails-contato">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="row m-20">
				<div class="col-12">
					<div class="form-group">
						<label class="control-label">E-mails de contato / responsáveis</label>
						<textarea id="emailresponsavel_editor" class="form-control" rows="4" placeholder="Informe um e-mail por linha ou separados por ponto e vírgula"></textarea>
						<small class="form-text text-muted">Você pode informar vários e-mails. Eles serão salvos no mesmo campo utilizado atualmente pelo sistema.</small>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-success btn-salvar-emails-contato">Salvar</button>
				<button type="button" class="btn btn-secondary waves-effect" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>
<!-- Modal Confirma Token -->
<div class="modal fade none-border" id="modal-confirmaToken">
	<div class="modal-dialog">
		<div class="modal-content">
			<?=  $this->Form->create(null, ['class' => 'form-material']) ?>
				<div class="row m-20">
					<div class="col-12">
						<div class="form-group">
							<label class="control-label ">Nome do Responsável </label>
							<?= $this->Form->control('confirmaNomeResponsavel', ['id' => 'confirmaNomeResponsavel', 'type' => 'text', 'class' => 'form-control', 'label' => false]);?>
						</div>
					</div>
					<div class="col-12">
						<div class="form-group">
							<label class="control-label ">CPF do Responsável </label>
							<?= $this->Form->control('confirmaCpfResponsavel', ['id' => 'confirmaCpfResponsavel', 'type' => 'text', 'class' => 'form-control', 'label' => false]);?>
						</div>
					</div>
					<div class="col-12">
						<div class="form-group">
							<label class="control-label ">RG do Responsável </label>
							<?= $this->Form->control('confirmaRgResponsavel', ['id' => 'confirmaRgResponsavel', 'type' => 'text', 'class' => 'form-control', 'label' => false]);?>
						</div>
					</div>
				</div>
			<?=  $this->Form->end() ?>
			<div class="modal-footer">
				<?= $this->Html->link('Atualizar', [], ['class' => 'btn btn-atualizaDentroDoModal btn-success text-white m-l-5']) ?>
				<button type="button" class="btn btn-danger waves-effect" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>
<!-- Modal Senha -->
<div class="modal fade none-border" id="modal-senha">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="row m-20">
				<div class="col-12">
					<div class="form-material">
						<div class="form-group">
							<label class="control-label ">Senha Administrativa </label>
							<?= $this->Form->control('senhaadministrativa', ['type' => 'text', 'class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Insira a senha administrativa']);?>
						</div>
						<?= $this->Form->control('idsenha', ['class' => 'idsenha', 'value' => null, 'label' => false, 'type' => 'hidden']) ?>
						<div class="custom-control custom-checkbox mr-sm-2 m-r-10">
							<?= $this->Form->checkbox('exibirsenha', ['checked' => true, 'class' => 'custom-control-input', 'id' => 'exibirsenha']); ?>
							<label class="custom-control-label text-muted" for="exibirsenha">Exibir Senha </label>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<?= $this->Html->link('Confirmar', ['#'], ['class' => 'btn btn-success text-white btn-verificasenha m-l-5']) ?>
				<button type="button" class="btn btn-danger waves-effect" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>
<script>
	// Datepicker 
		$('.datepicker').bootstrapMaterialDatePicker({ format : 'DD/MM/YYYY', lang : 'pt-br', time : false, switchOnClick : true, nowButton : true, cancelText : 'Cancelar' , 'setDate' : 'currentDate', nowText : 'Hoje'});
	// Somente número 
		function SomenteNumero(e){
			var tecla=(window.event)?event.keyCode:e.which;
			if((tecla>47 && tecla<58)) return true;
			else{
				if (tecla==8 || tecla==0) return true;
				else  return false;
			}
		}
	// Disabled 
		<?php if($role == 1) { ?> 	disabled = true;
		<?php } else { ?>			disabled = false; <?php } ?>

	// Masks e Datatable 
		$(document).ready(function(){
			$("#cnpj").mask("99.999.999/9999-99");
			$("#fone").mask("(999) 9999-9999");
			$("#fone2").mask("(999) 99999-9999");
			$("#cep").mask("99999-999");
			$("#cpffisica").mask("999.999.999-99");
			$("#cpfresponsavel").mask("999.999.999-99");
			$("#confirmaCpfResponsavel").mask("999.999.999-99");
			$(".telefone").mask("(99) 9999-9999");
			$(".celular").mask("(99) 99999-9999");

			if(disabled) $("input").prop('disabled', true);

			$("#senhaadministrativa").prop('disabled', false);
			$("#exibirsenha").prop('disabled', false);

			table = $('#tableContatos, #tableContratos')
			table.on( 'length.dt', function ( e, settings, len ) {
				pagelength(len);
			} )
			table.DataTable({
				"pageLength": 20,
				"lengthChange": false,
				"language": {
					"sProcessing":    "Procesando...",
					"sLengthMenu":    "Mostrar _MENU_ registros",
					"sZeroRecords":   "Nenhum registro encontrado",
					"sEmptyTable":    "Nenhum dado disponível",
					"sInfo":          "Mostrando registros de _START_ até _END_ de um total de _TOTAL_ registros",
					"sInfoEmpty":     "Mostrando registros de 0 a 0 de um total de 0 registros",
					"sInfoFiltered":  "(filtrado de um total de _MAX_ registros)",
					"sInfoPostFix":   "",
					"sSearch":        "Buscar:",
					"sUrl":           "",
					"sInfoThousands":  ",",
					"sLoadingRecords": "Carregando...",
					"oPaginate": {
						"sFirst":    "<<",
						"sLast":    ">>",
						"sNext":    ">",
						"sPrevious": "<"
					},
					"oAria": {
						"sSortAscending":  ": Ordem Ascendente",
						"sSortDescending": ": Ordem descendente"
					}
				},
				"drawCallback": function( settings ) {
					if ($('body').hasClass('dark-mode') ) $('td').each(function(){$(this).addClass('dark-mode');});
					else $('td').each(function(){$(this).removeClass('dark-mode');});
				},
			});
			table.search(filters).draw();

			// Inicializa visualização dos e-mails de contato/responsáveis
			var emailsRaw = $('#emailresponsavel').val() || '';
			atualizaDisplayEmails(emailsRaw);

			$('#modal-emails-contato').on('show.bs.modal', function () {
				$('#emailresponsavel_editor').val(formataEmailsParaEdicao($('#emailresponsavel').val() || ''));
			});

			$('.btn-salvar-emails-contato').click(function() {
				var texto = $('#emailresponsavel_editor').val() || '';
				var normalizado = normalizaEmails(texto);
				$('#emailresponsavel').val(normalizado);
				atualizaDisplayEmails(normalizado);
				$('#modal-emails-contato').modal('hide');
			});
		});
	// Inativos 
		$('.inativo').hide();

		var ativo = 'nao';

		$('.btn-inativo').click(function(e) {
			e.preventDefault();
			if( $('.vesetainativo').hasClass('inativo') ){
				if(window.ativo == 'nao'){
					$('.inativo').show();
					window.ativo = "sim";
				}else{
					$('.inativo').hide();
					window.ativo = "nao";
				}
			}
		})

		$('.inativoAcessos').hide();

		var inativoAcessos = 'nao';

		$('.btn-inativoAcessos').click(function(e) {
			e.preventDefault();
			if( $('.vesetainativoAcessos').hasClass('inativoAcessos') ){
				if(window.inativoAcessos == 'nao'){
					$('.inativoAcessos').show();
					window.inativoAcessos = "sim";
				}else{
					$('.inativoAcessos').hide();
					window.inativoAcessos = "nao";
				}
			}
		})
	// Inscrição estadual 
		$('#inscricaoestadual').change(function(e) {
			$.ajax({
				url: "<?= Router::url(array('controller'=>'Clientes','action'=>'cidadesestado'));?>/" + $('#idcidade').val(),
				success: function(data){
					checkInscEstadual( $('#inscricaoestadual').val(), data );
				},
				error: function (tab) { alert('Inscrição estadual inválida'); }
			});
		});
	// Tipo 
		tipo($("#tipo").val());

		$("#tipo").change(function(){
			tipo($(this).val());
		})

		function tipo(tipo){
			if(tipo == 2){
				// Campos obrigatórios pessoa jurídica
				$("#razaosocial, #nomefantasia, #cnpj").prop('disabled', false);
				$("#nome, #cpffisica").prop('disabled', true);

				$('.pessoaFisica').hide();
				$('.pessoaJuridica').fadeIn();
			} else {
				// Campos obrigatórios pessoa física
				$("#nome, #cpffisica").prop('disabled', false);
				$("#razaosocial, #nomefantasia, #cnpj").prop('disabled', true);

				$('.pessoaJuridica').hide();
				$('.pessoaFisica').fadeIn();
			}
		}
	// Nome 
		$('#razaosocial').change(function(){
			issoemmaiusculo = $(this).val().toUpperCase();
			$(this).val(issoemmaiusculo);
		});

		$('#nome').change(function(){
			issoemmaiusculo = $(this).val().toUpperCase();
			$(this).val(issoemmaiusculo);
		});
	// Token 
		var role = <?= $role ?>;

		$('.btn-atualizaToken').click(function(e){
			e.preventDefault();
			var url = "<?= Router::url(['controller' => 'Clientes', 'action' => 'updateToken', $cliente->id]);?>";
			if(role == 0) window.location = url;
			else{
				$('#modal-confirmaToken').modal('toggle');
				$('#modal-confirmaToken').modal('show');
			}
		});

		$('.btn-atualizaDentroDoModal').click(function(e){
			e.preventDefault();
			$.ajax({
				url: "<?= Router::url(['controller' => 'Users', 'action' => 'verificadadoscliente', $cliente->id]);?>/" + $('#confirmaNomeResponsavel').val() + '/' + $('#confirmaCpfResponsavel').val() + '/' + $('#confirmaRgResponsavel').val(),
				success: function(data){
					if(data == 'tudocerto') window.location = "<?= Router::url(['controller' => 'Clientes', 'action' => 'updateToken', $cliente->id]);?>";
					else bootbox.alert('<p class="text-center" style="font-size: 1.2rem">Os dados inseridos não conferem com os cadastrados no bando de dados.</p>');
				},
			});
		});
	// Senhas 
		$('.senha').click(function(e) {
			var id = $(this).attr('data-id');
			$('#idsenha').val(id);
			$('#modal-senha').modal('toggle');
			$('#modal-senha').modal('show');;
		});

		$('.btn-verificasenha').click(function(e) {
			e.preventDefault()
			id = $('#idsenha').val();
			senha = $('#senhaadministrativa').val();
			idcliente = <?= $cliente->id ?>;
			$.ajax({
				type:"post",
				url: "<?= Router::url(['controller'=>'Cliacessos','action'=>'verificasenha']);?>/",
				data: {id: id, senhaadm: senha, idcliente : idcliente},
				success: function(data){
					$('#modal-senha').modal('toggle');
					bootbox.alert(data);
				},
				error: function (data) { alert('erro'); }
			});
		});

		$('#exibirsenha').change(function(){
			if ($(this).is(':checked')) $('#senhaadministrativa').attr('type', 'text');
			else $('#senhaadministrativa').attr('type', 'password');
		});

		$('#exibirsenhacliente').change(function(){
			if ($(this).is(':checked')) $('#senha').attr('type', 'text');
			else $('#senha').attr('type', 'password');
		});
	// Funções auxiliares para e-mails de contato
		function normalizaEmails(texto) {
			if (!texto) return '';
			var partes = texto
				.replace(/[\r\n]+/g, ';')
				.split(';')
				.map(function(p) { return p.trim(); })
				.filter(function(p) { return p.length > 0; });
			return partes.join('; ');
		}

		function formataEmailsParaEdicao(texto) {
			if (!texto) return '';
			return texto.split(';').map(function(p){ return p.trim(); }).filter(function(p){ return p.length > 0; }).join('\n');
		}

		function atualizaDisplayEmails(texto) {
			if (!texto) {
				$('#emailresponsavel_display').val('');
				$('#emailresponsavel_display').attr('placeholder', 'Nenhum e-mail de contato cadastrado');
				return;
			}
			$('#emailresponsavel_display').val(texto.replace(/;/g, '; '));
		}
</script>