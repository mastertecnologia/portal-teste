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

	// Equipe = não é portal cliente (C_RoleFuncionario pode ≠ 0 numérico; evita sumir abas Contratos/Token).
	$isClientePortal = isset($role) && (int)$role === (int)C_RoleCliente;
	$isEquipe = !$isClientePortal;
	// Fase 1 UX: sem disabled no HTML do cadastro; modo leitura via readonly + barra inferior (JS).

?>
<style>
.table td, .table th { padding: 0.4rem; }
/* ── Empresa edit — Premium tabs ─────────────────────────────── */
.cli-page-head{display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:18px;}
.cli-page-head-left .cli-eyebrow{font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;color:#00c08b;font-weight:700;margin-bottom:3px;}
.cli-page-head-left h1{font-size:1.2rem;font-weight:800;color:#e6edf3;margin:0;}
.cli-page-head-left p{font-size:.78rem;color:#6e7681;margin:3px 0 0;}
/* Tab nav override */
.cli-tabs-nav{display:flex;gap:2px;background:#0d1117;border:1px solid #21262d;border-radius:10px;padding:4px;margin-bottom:20px;flex-wrap:wrap;}
.cli-tabs-nav .nav-item{flex:none;}
.cli-tabs-nav .nav-link{display:flex;align-items:center;gap:6px;padding:7px 16px;border-radius:7px;font-size:.78rem;font-weight:600;color:#8b949e;border:none!important;background:transparent;transition:all .18s;cursor:pointer;}
.cli-tabs-nav .nav-link:hover{color:#c9d1d9;background:#161b22;}
.cli-tabs-nav .nav-link.active{background:#00c08b!important;color:#fff!important;}
.cli-tabs-nav .nav-link i{font-size:.8rem;}
/* Card body */
.cli-card{background:#161b22;border:1px solid #21262d;border-radius:12px;padding:24px;}
/* Form labels */
.cli-label{font-size:.72rem;font-weight:600;color:#8b949e;margin-bottom:4px;display:block;letter-spacing:.04em;text-transform:uppercase;}
/* Readonly display for clients */
.cli-field-ro{background:#0d1117;border:1px solid #30363d;border-radius:8px;padding:9px 12px;color:#c9d1d9;font-size:.85rem;min-height:38px;}
/* Section divider */
.cli-section{margin-top:20px;padding-top:16px;border-top:1px solid #21262d;}
.cli-section-title{font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#6e7681;margin-bottom:14px;}
/* Acessos table */
.cli-acessos-table{width:100%;border-collapse:collapse;font-size:.78rem;}
.cli-acessos-table thead th{padding:8px 10px;font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#6e7681;border-bottom:1px solid #21262d;}
.cli-acessos-table tbody tr{border-bottom:1px solid #21262d;}
.cli-acessos-table tbody tr:hover{background:#1c2230;}
.cli-acessos-table td{padding:9px 10px;color:#c9d1d9;vertical-align:middle;}
.cli-senha-mask{font-family:'DM Mono',monospace;letter-spacing:.1em;cursor:pointer;color:#8b949e;}
.cli-senha-mask:hover{color:#5cdbc0;}
/* Token box */
.cli-token-box{background:#0d1117;border:1px solid #30363d;border-radius:8px;padding:12px 14px;font-family:'DM Mono',monospace;font-size:.8rem;color:#5cdbc0;word-break:break-all;letter-spacing:.04em;}
.cli-token-note{font-size:.72rem;color:#6e7681;margin-top:8px;}
/* Rodapé operacional (resumo + atalhos) */
.cli-smart-footer{margin-top:20px;padding:14px 18px;border-radius:10px;border:1px solid #21262d;background:#0d1117;font-size:.78rem;color:#8b949e;display:flex;flex-wrap:wrap;gap:12px 20px;align-items:center;justify-content:space-between;}
.cli-smart-footer strong{color:#c9d1d9;font-weight:600;}
.cli-smart-footer .cli-sf-badges .badge{font-size:.65rem;font-weight:600;margin-left:4px;}
.cli-smart-footer a{color:#5cdbc0!important;text-decoration:none!important;}
.cli-smart-footer a:hover{text-decoration:underline!important;}
.cli-smart-footer--alert{border-color:rgba(248,81,73,.45);background:rgba(248,81,73,.06);}
.cli-smart-footer .cli-sf-token{flex:1 1 220px;max-width:100%;}
/* Fase 1 — cards + readonly claro (dark UI) */
.cli-subcard{border:1px solid #21262d;border-radius:10px;background:#0d1117;margin-bottom:16px;overflow:hidden;}
.cli-subcard-head{font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#8b949e;padding:10px 16px;border-bottom:1px solid #21262d;background:#161b22;}
.cli-subcard-body{padding:16px 16px 6px;}
#form-edit-cliente input.form-control[readonly],#form-edit-cliente textarea.form-control[readonly]{
	background:#21262d!important;border-color:#30363d!important;color:#e6edf3!important;opacity:1!important;cursor:default;
}
#form-edit-cliente input.form-control:focus,#form-edit-cliente textarea.form-control:focus{box-shadow:0 0 0 2px rgba(0,192,139,.25);}
/* Modo leitura: não alterar checkboxes do cadastro pelo clique (footer switch entra em edição) */
#form-edit-cliente.cli-ficha--readonly #inativo,#form-edit-cliente.cli-ficha--readonly #contrato,#form-edit-cliente.cli-ficha--readonly #exibirsenhacliente{pointer-events:none;}
#form-edit-cliente.cli-ficha--readonly label[for="inativo"],#form-edit-cliente.cli-ficha--readonly label[for="contrato"],#form-edit-cliente.cli-ficha--readonly label[for="exibirsenhacliente"]{pointer-events:none;cursor:default;}
.cli-ficha-toolbar{border:1px solid #21262d;border-radius:10px;padding:10px 14px;background:#161b22;margin-bottom:16px;display:flex;flex-wrap:wrap;gap:10px;justify-content:space-between;align-items:center;}
.cli-ficha-toolbar .cli-ficha-hint{font-size:.75rem;color:#6e7681;margin:0;}
.cli-ficha-footer-fixed{position:fixed;left:0;right:0;bottom:0;z-index:1025;background:#161b22;border-top:1px solid #21262d;box-shadow:0 -4px 24px rgba(0,0,0,.35);padding:10px 16px;font-size:.78rem;}
.cli-ficha-footer-fixed .cli-ff-inner{max-width:1400px;margin:0 auto;display:flex;flex-wrap:wrap;gap:10px 16px;align-items:center;justify-content:space-between;}
.cli-ff-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center;}
.cli-ff-status .custom-switch{padding-left:2.5rem;}
.cli-ff-status .custom-control-label{color:#c9d1d9;font-size:.78rem;}
.cli-ficha-page-pad{padding-bottom:100px;}
</style>
<?= $this->element('Cli/ui_css') ?>
<div class="col-md-12 cli-ficha-page-pad">
	<div class="cli-card">
		<div id="cli-ficha-loading" class="cli-ficha-loading d-none" aria-hidden="true" role="status">
			<div class="cli-ficha-loading__box"><div class="cli-ficha-loading__spin" aria-hidden="true"></div><span>Salvando alterações…</span></div>
		</div>

		<!-- Page head -->
		<div class="cli-page-head">
			<div class="cli-page-head-left">
				<div class="cli-eyebrow">Minha Empresa</div>
				<h1><?= h($cliente->tipo == C_ClientesTipoFisica ? $cliente->nome : ($cliente->razaosocial ?: $cliente->nomefantasia)) ?></h1>
				<p><?= h($cliente->tipo == C_ClientesTipoFisica ? 'Pessoa Física' : 'Pessoa Jurídica') ?> · CNPJ/CPF: <?= h($cliente->tipo == C_ClientesTipoFisica ? Mask('###.###.###-##', $cliente->cpf ?? '') : Mask('##.###.###/####-##', $cliente->cnpj ?? '')) ?></p>
			</div>
			<?php if ($isEquipe): ?>
				<div class="d-flex align-items-center flex-wrap" style="gap:8px;">
					<?= $this->Html->link('<i class="fas fa-history"></i> Histórico', ['action' => 'eventos', $cliente->id], ['class' => 'btn btn-sm btn-outline-info', 'escape' => false, 'title' => 'Eventos e auditoria do cliente']) ?>
					<?= $this->Html->link('<i class="fas fa-arrow-left"></i> Voltar', ['action' => 'index'], ['class' => 'btn btn-sm btn-outline-secondary', 'escape' => false]) ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Tab nav -->
		<ul class="nav cli-tabs-nav" role="tablist">
			<li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#cliente" role="tab"><i class="fas fa-user"></i> Cliente</a></li>
			<?php if($isEquipe || !empty($permissaoacesso)){ ?><li class="nav-item"><a class="nav-link" data-toggle="tab" href="#<?= $isClientePortal ? 'acessosCliente' : 'acessos' ?>" role="tab"><i class="fas fa-desktop"></i> Acessos</a></li><?php } ?>
			<?php if($isEquipe){ ?><li class="nav-item"><a class="nav-link" data-toggle="tab" href="#usuarios" role="tab"><i class="fas fa-users"></i> Usuários</a></li><?php } ?>
			<?php if($isEquipe || !empty($permissaoacesso)){ ?><li class="nav-item"><a class="nav-link" data-toggle="tab" href="#contratos" role="tab"><i class="fas fa-file-contract"></i> Contratos</a></li><?php } ?>
			<?php if($isEquipe || !empty($permissaoacesso)){ ?><li class="nav-item"><a class="nav-link" data-toggle="tab" href="#token" role="tab"><i class="fas fa-key"></i> Token</a></li><?php } ?>
		</ul>
			<div class="tab-content">
				<div class="tab-pane active" id="cliente">
					<?=  $this->Form->create($cliente, ['class' => 'form-material', 'id' => 'form-edit-cliente']) ?>
						<div class="cli-ficha-toolbar">
							<p class="cli-ficha-hint mb-0"><i class="fas fa-eye"></i> <span id="cli-ficha-mode-label">Modo leitura</span> — use a barra inferior para <strong>Editar</strong>, <strong>Salvar</strong> ou <strong>Cancelar</strong>.</p>
						</div>
						<?= $this->element('Cli/card', ['title' => 'Dados da empresa']) ?>
						<div class="row">
							<?= $this->element('Cli/select', ['label' => 'Tipo', 'field' => 'tipo', 'colClass' => 'col-lg-3 col-md-3 col-sm-12 col-xs-12', 'selectOptions' => C_ClientesTipo, 'options' => ['title' => 'Tipo do cliente', 'required' => true, 'class' => 'selectpicker form-control']]) ?>
						</div>
						<br>
						<div class="row pessoaJuridica <?= $pessoaJuridica ?>">
							<?= $this->element('Cli/input', ['label' => 'Razão Social', 'field' => 'razaosocial', 'colClass' => 'col-lg-5 col-md-4 col-sm-12 col-xs-12', 'options' => ['placeholder' => 'Insira a razão social']]) ?>
							<?= $this->element('Cli/input', ['label' => 'Nome Fantasia', 'field' => 'nomefantasia', 'colClass' => 'col-lg-5 col-md-4 col-sm-12 col-xs-12', 'options' => ['placeholder' => 'Insira o nome fantasia']]) ?>
							<?= $this->element('Cli/input', ['label' => 'CNPJ', 'field' => 'cnpj', 'colClass' => 'col-lg-2 col-md-4 col-sm-12 col-xs-12', 'options' => ['id' => 'cnpj', 'placeholder' => 'Insira o CNPJ']]) ?>
						</div>
						<div class="row pessoaFisica <?= $pessoaFisica ?>">
							<?= $this->element('Cli/input', ['label' => 'Nome', 'field' => 'nome', 'colClass' => 'col-lg-8 col-xs-12', 'options' => ['placeholder' => 'Insira o nome']]) ?>
							<?= $this->element('Cli/input', ['label' => 'CPF', 'field' => 'cpf', 'colClass' => 'col-lg-4 col-xs-12', 'options' => ['id' => 'cpffisica', 'placeholder' => 'Insira o CPF']]) ?>
						</div>
						<?= $this->element('Cli/card_end') ?>
						<?php if($isEquipe){ ?>
						<?= $this->element('Cli/card', ['title' => 'Responsável (representante legal)']) ?>
						<div class="row pessoaJuridica <?= $pessoaJuridica ?>">
							<?= $this->element('Cli/input', ['label' => 'Nome do Responsável', 'field' => 'nomeresponsavel', 'colClass' => 'col-lg-6 col-md-6 col-sm-12 col-xs-12', 'options' => ['placeholder' => 'Insira o nome']]) ?>
							<?= $this->element('Cli/input', ['label' => 'CPF', 'field' => 'cpf', 'colClass' => 'col-md-3 col-xs-12', 'options' => ['id' => 'cpfresponsavel', 'placeholder' => 'Insira o CPF']]) ?>
							<?= $this->element('Cli/input', ['label' => 'RG', 'field' => 'rg', 'colClass' => 'col-md-3 col-xs-12', 'options' => ['placeholder' => 'Insira o RG']]) ?>
						</div>
						<?= $this->element('Cli/card_end') ?>
						<?php } ?>
						<?= $this->element('Cli/card', ['title' => 'Endereço']) ?>
						<div class="row">
							<?= $this->element('Cli/input', ['label' => 'Endereço', 'field' => 'endereco', 'colClass' => 'col-lg-6 col-md-6 col-sm-12', 'options' => ['placeholder' => 'Insira o endereço', 'required' => true]]) ?>
							<?= $this->element('Cli/input', ['label' => 'Nro.', 'field' => 'nroendereco', 'colClass' => 'col-lg-2 col-md-6 col-sm-12', 'options' => ['placeholder' => 'Insira o nro.', 'required' => true]]) ?>
							<?= $this->element('Cli/input', ['label' => 'Bairro', 'field' => 'bairro', 'colClass' => 'col-lg-2 col-md-6 col-sm-12', 'options' => ['placeholder' => 'Insira o bairro', 'required' => true]]) ?>
							<?= $this->element('Cli/input', ['label' => 'Complemento', 'field' => 'complemento', 'colClass' => 'col-lg-2 col-md-6 col-sm-12', 'options' => ['placeholder' => 'Insira o complemento']]) ?>
							<?= $this->element('Cli/input', ['label' => 'CEP', 'field' => 'cep', 'colClass' => 'col-lg-2 col-md-12 col-sm-12', 'options' => ['id' => 'cep', 'placeholder' => 'Insira o CEP', 'required' => true]]) ?>
						</div>
						<div class="row">
							<?= $this->element('Cli/select', ['label' => 'Cidade', 'field' => 'idcidade', 'colClass' => 'col-lg-3 col-md-3 col-sm-12 col-xs-12', 'selectOptions' => $cidades, 'options' => ['data-live-search' => 'true', 'class' => 'selectpicker form-control']]) ?>
						</div>
						<?= $this->element('Cli/card_end') ?>
						<?= $this->element('Cli/card', ['title' => 'Contato']) ?>
						<div class="row">
							<?= $this->element('Cli/input', ['label' => 'Telefone', 'field' => 'fone', 'colClass' => 'col-lg-3 col-md-3 col-sm-6 col-xs-6', 'options' => ['id' => 'fone', 'placeholder' => 'Insira o telefone']]) ?>
							<?= $this->element('Cli/input', ['label' => 'Celular', 'field' => 'fone2', 'colClass' => 'col-lg-3 col-md-3 col-sm-6 col-xs-6', 'options' => ['id' => 'fone2', 'placeholder' => 'Insira o celular']]) ?>
						</div>
						<div class="row">
							<?= $this->element('Cli/email_readonly_block', [
								'labelTitle' => 'E-mail de faturamento',
								'modalTarget' => '#modal-emails-faturamento',
								'fieldName' => 'email',
								'hiddenId' => 'email',
								'textareaId' => 'email_faturamento_display',
								'placeholder' => 'Nenhum e-mail de faturamento cadastrado',
								'helpText' => 'Você pode informar um ou mais e-mails separados por ponto e vírgula. Serão usados para envio de notas, boletos e comunicações financeiras.',
								'gerenciarClass' => 'btn btn-sm btn-outline-info btn-gerenciar-emails-faturamento',
							]) ?>
							<?= $this->element('Cli/email_readonly_block', [
								'labelTitle' => 'E-mails de contato / responsáveis',
								'modalTarget' => '#modal-emails-contato',
								'fieldName' => 'emailresponsavel',
								'hiddenId' => 'emailresponsavel',
								'textareaId' => 'emailresponsavel_display',
								'placeholder' => 'Nenhum e-mail de contato cadastrado',
								'helpText' => 'E-mails usados para avisos gerais, suporte e comunicações operacionais.',
								'gerenciarClass' => 'btn btn-sm btn-outline-info btn-gerenciar-emails',
							]) ?>
						</div>
						<?php
						$cliLabelHtmlIe = '<label class="cli-cmp-label d-flex justify-content-between align-items-center flex-wrap" style="gap:6px;"><span>Inscrição Estadual <small class="text-muted">(somente números)</small></span>';
						if (!empty($isEquipe)) {
							$cliLabelHtmlIe .= '<button type="button" class="btn btn-sm btn-outline-info d-none" id="btn-buscar-ie-edit" title="Consultar IE na SEFAZ/SINTEGRA">Buscar IE</button>';
						}
						$cliLabelHtmlIe .= '</label>';
						$cliBeforeIe = '<input type="hidden" id="uf_contribuinte_edit" value="' . h($ufContribuinte ?? '') . '" />';
						?>
						<div class="row pessoaJuridica <?= $pessoaJuridica ?> mt-2">
							<?= $this->element('Cli/input', ['label' => 'Inscrição Municipal (somente números)', 'field' => 'inscricaomunicipal', 'colClass' => 'col-lg-6 col-md-6 col-sm-12 col-xs-12', 'options' => ['onkeypress' => 'return SomenteNumero(event)', 'placeholder' => 'Insira a inscrição municipal']]) ?>
							<?= $this->element('Cli/input', ['label' => '', 'labelHtml' => $cliLabelHtmlIe, 'beforeControlHtml' => $cliBeforeIe, 'field' => 'inscricaoestadual', 'colClass' => 'col-lg-6 col-md-6 col-sm-12 col-xs-12', 'options' => ['id' => 'inscricaoestadual', 'onkeypress' => 'return SomenteNumero(event)', 'placeholder' => 'Insira a inscrição estadual']]) ?>
						</div>
						<?= $this->element('Cli/card_end') ?>
						<?php if($isEquipe){ ?>
						<?= $this->element('Cli/card', ['title' => 'Área administrativa (equipe)']) ?>
							<div class="row">
							<?= $this->element('Cli/input', ['label' => 'Senha para o cliente visualizar os acessos', 'field' => 'senha', 'colClass' => 'col-lg-2 col-md-3 col-sm-3 col-xs-12', 'options' => ['placeholder' => 'Insira a senha']]) ?>
								<div class="custom-control custom-checkbox mr-sm-2 m-r-10 m-t-30">
									<?= $this->Form->checkbox('exibirsenhacliente', ['checked' => true, 'class' => 'custom-control-input', 'id' => 'exibirsenhacliente']); ?>
									<label class="custom-control-label text-muted" for="exibirsenhacliente">Exibir Senha </label>
								</div>
							<?= $this->element('Cli/select', ['label' => 'Empresa dominante', 'field' => 'empresadominante', 'colClass' => 'col-lg-3 col-md-3 col-sm-3 col-xs-12', 'selectOptions' => $empresasOptSidebar, 'options' => ['class' => 'form-control']]) ?>
							</div>
							<div class="row align-items-center">
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
								<div class="col-lg-7 col-md-6 col-sm-6 col-xs-12 m-t-5">
									<p class="text-muted small mb-0">Salvar, inativar e alternar status usam a <strong>barra fixa inferior</strong>.</p>
								</div>
							</div>
						<?= $this->element('Cli/card_end') ?>
						<?php } ?>
						<?= $this->Form->button('Salvar cliente', ['type' => 'submit', 'class' => 'btn-enviar btn btn-pgm btn-pgm-salvar btn-success salvarcliente d-none', 'id' => 'cli-ficha-submit-fallback']) ?>
					<?= $this->Form->end(); ?>
				</div>
				<?php if($isEquipe || !empty($permissaoacesso)){ ?>
				<div class="tab-pane" id="acessos">
					<?= $this->element('Cli/card', ['headHtml' => '<i class="fas fa-desktop"></i> Acessos', 'extraClass' => 'mb-3']) ?>
					<?php if ($isEquipe) { ?>
						<?= $this->Form->create(null, ['class' => 'form-material m-t-10', 'url' => ['controller' => 'Users', 'action' => 'permissaoacesso']]); ?>
							<div class="row">
								<div class="col-md-4 col-xs-12">
									<label class="control-label text-muted"> Usuários com permissão para acessar senhas, contratos e token: </label>
									<?= $this->Form->control('users._ids', ['value' => $usuariosValue, 'title' => 'Usuários', 'class' => 'form-control selectpicker', 'options' => $usuarios ,'label' => false]) ?>
								</div>
								<?= $this->Form->hidden('idcliente', ['value' => $cliente->id]); ?>
								<div class="col-md-4 col-xs-12">
									<?= $this->Form->button('Salvar', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success m-t-25']) ?>
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
									<?= $this->Form->button('Adicionar acesso', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success float-right'], $cliente->id) ?>
									<a role="button" class='btn btn-danger btn-inativoAcessos text-white'>Exibir Inativos </a>
								</div>
							</div>
						<?= $this->Form->end(); ?>
					<?php } ?>
					<div class="cli-section-title" style="margin-top:12px;">Acessos cadastrados</div>
					<div class="table-responsive">
						<table class="cli-acessos-table" id="tableAtivos">
							<thead>
								<tr>
								<th>Serviço</th>
								<th>Provedor</th>
								<th>IP</th>
								<th>Porta</th>
								<th>Usuário</th>
								<th>URL</th>
								<th>Protocolo</th>
								<th>Senha</th>
								<th>Ativo</th>
								<?php if ($isEquipe) { ?> <th style="width:10%">Ações</th> <?php } ?>
								</tr>
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
										<?php if ($isEquipe) { ?>
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
					<?= $this->element('Cli/card_end') ?>
				</div>
				<?php } if($isEquipe){ ?>
				<div class="tab-pane" id="usuarios">
					<?= $this->element('Cli/card', ['headHtml' => '<i class="fas fa-users"></i> Usuários do cliente', 'extraClass' => 'mb-3']) ?>
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
					<?= $this->element('Cli/card_end') ?>
				</div>
				<?php } if($isEquipe || !empty($permissaoacesso)){ ?>
				<div class="tab-pane" id="contratos">
					<?= $this->element('Cli/card', ['headHtml' => '<i class="fas fa-file-contract"></i> Contratos', 'extraClass' => 'mb-3']) ?>
					<?php if ($isEquipe) : ?>
					<?= $this->Html->link('Cadastrar item', ['controller' => 'Clicontratos', 'action' => 'add', $cliente->id], ['class' => 'btn btn-pgm btn-pgm-salvar btn-success  m-r-5 m-b-20']) ?>
					<?= $this->Html->link('Contratos de Horas Técnicas', ['controller' => 'ContratosHoras', 'action' => 'index', $cliente->id], ['class' => 'btn btn-pgm btn-pgm-situacao btn-info m-r-5 m-b-20']) ?>
					<?= $this->Html->link('Cadastrar Contrato de Horas', ['controller' => 'ContratosHoras', 'action' => 'add', $cliente->id], ['class' => 'btn btn-pgm btn-pgm-salvar text-white m-r-5 m-b-20']) ?>
					<?php endif; ?>
					<div class="table-responsive">
						<table class="cli-acessos-table" id="tableContratos">
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
										<td class="td-actions">
											<?= $this->Html->link('<i class="fa fa-eye"></i>', ['controller' => 'Clicontratos', 'action' => 'view', $reg->id], ['rel' => 'tooltip', 'title' => 'Detalhe', 'class' => 'btn btn-info btn-simple btn-xs', 'escape' => false, 'target' => '_blank']) ?>
											<?= $this->Html->link('<i class="fa fa-edit"></i>', ["controller" => "clicontratos", "action" => "edit", $reg->id], ['rel' => 'tooltip', 'title' => 'Editar', 'class' => 'btn btn-warning btn-simple btn-xs', 'escape' => false, 'target' => '_blank']) ?>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?= $this->element('Cli/card_end') ?>
				</div>
				<?php } if($isClientePortal ){ ?>
				<div class="tab-pane" id="acessosCliente">
					<?= $this->element('Cli/card', ['headHtml' => '<i class="fas fa-desktop"></i> Meus acessos', 'extraClass' => 'mb-3']) ?>
					<div class="table-responsive">
						<table class="table table-hover" id="tableAcessosClientes">
							<thead class="text-primary">
								<th>Provedor</th>
								<th>IP</th>
								<th>Usuário</th>
								<th>Senha</th>
								<th>Ativo</th>
								<?php if ($isEquipe) echo '<th>Ações</th>' ; ?>
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
										<td><a class="link senha cli-senha-mask" data-id="<?=$reg->id?>" href="#" title="Clique para revelar">••••••••</a></td>
										<td> <?= $reg->inativo == 1 ? 'Não' : 'Sim'; ?></td>
										<?php if ($isEquipe) { ?>
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
					<?= $this->element('Cli/card_end') ?>
				</div>
				<?php }if($isEquipe || !empty($permissaoacesso)){ ?>
				<div class="tab-pane" id="token">
					<?= $this->element('Cli/card', ['headHtml' => '<i class="fas fa-key"></i> Token de integração API', 'extraClass' => 'mb-3']) ?>
					<div class="cli-token-box" id="token-display"><?= h($cliente->token) ?></div>
					<p class="cli-token-note">Este token é utilizado para autenticar integrações externas com a API do portal. Mantenha-o em segurança.</p>
					<?php if($isEquipe) { ?>
					<div class="mt-3">
						<?= $this->Html->link('<i class="fas fa-sync-alt"></i> Atualizar Token', [], ['class' => 'btn-atualizaToken btn btn-sm btn-outline-warning salvarcliente', 'escape' => false]) ?>
					</div>
					<?php } ?>
					<?= $this->element('Cli/card_end') ?>
				</div>
				<?php }  ?>
		</div><!-- /tab-content -->

	</div><!-- /cli-card -->
</div><!-- /col-md-12 -->

<?php
	$sfAlert = !empty($cliFooter) && (!empty($cliFooter['contratos_vencidos']) || !empty($cliFooter['contratos_vencendo30']));
?>
<div class="cli-ficha-footer-fixed<?= $sfAlert ? ' cli-smart-footer--alert' : '' ?>" id="cli-ficha-footer-bar">
	<div class="cli-ff-inner">
		<div class="d-flex flex-wrap align-items-center" style="gap:10px;">
			<div>
				<strong class="text-muted">Status</strong><br>
				<?php if ($isEquipe): ?>
				<div class="custom-control custom-switch mt-1">
					<input type="checkbox" class="custom-control-input" id="cli-ff-switch-inativo" <?= !empty($cliente->inativo) ? 'checked' : '' ?>>
					<label class="custom-control-label" for="cli-ff-switch-inativo">Cliente inativo</label>
				</div>
				<?php else: ?>
				<span class="badge badge-<?= !empty($cliFooter['status_class']) ? h($cliFooter['status_class']) : 'secondary' ?>"><?= !empty($cliFooter['status_label']) ? h($cliFooter['status_label']) : '—' ?></span>
				<?php endif; ?>
			</div>
			<?php if (!empty($cliFooter)): ?>
			<div class="text-muted small" style="max-width:340px;">
				<strong>Contratos:</strong> <?= (int)$cliFooter['contratos_total'] ?>
				<?php if (!empty($cliFooter['contratos_vencidos'])): ?><span class="badge badge-danger ml-1"><?= (int)$cliFooter['contratos_vencidos'] ?> venc.</span><?php endif; ?>
				<?php if (!empty($cliFooter['contratos_vencendo30'])): ?><span class="badge badge-warning text-dark ml-1"><?= (int)$cliFooter['contratos_vencendo30'] ?> 30d</span><?php endif; ?>
				<br><span class="d-none d-md-inline"><?= h($cliFooter['token_note']) ?></span>
			</div>
			<?php endif; ?>
		</div>
		<div class="cli-ff-actions">
			<?= $this->element('Cli/button', ['text' => 'Editar cliente', 'iconHtml' => '<i class="fas fa-pen"></i>', 'class' => 'btn-sm btn-outline-primary', 'attrs' => ['id' => 'btn-cli-ficha-edit']]) ?>
			<?= $this->element('Cli/button', ['text' => 'Cancelar', 'iconHtml' => '<i class="fas fa-undo"></i>', 'class' => 'btn-sm btn-outline-secondary', 'attrs' => ['id' => 'btn-cli-ficha-cancel', 'style' => 'display:none']]) ?>
			<?= $this->element('Cli/button', ['text' => 'Salvar cliente', 'iconHtml' => '<i class="fas fa-save"></i>', 'class' => 'btn-sm btn-success', 'attrs' => ['id' => 'btn-cli-ficha-save', 'style' => 'display:none']]) ?>
			<?php if ($isEquipe && empty($cliente->inativo)): ?>
			<?= $this->element('Cli/button', ['text' => 'Inativar…', 'class' => 'btn-sm btn-outline-danger btn-inativar-cliente', 'attrs' => ['type' => 'button']]) ?>
			<?php endif; ?>
			<?php if ($isEquipe): ?>
			<?= $this->Html->link('<i class="fas fa-history"></i>', ['action' => 'eventos', $cliente->id], ['escape' => false, 'class' => 'btn btn-sm btn-outline-info', 'title' => 'Histórico']) ?>
			<?= $this->Html->link('<i class="fas fa-sliders-h"></i>', ['controller' => 'PortalNotifications', 'action' => 'preferences'], ['escape' => false, 'class' => 'btn btn-sm btn-outline-secondary', 'title' => 'Preferências de alertas']) ?>
			<?php endif; ?>
		</div>
	</div>
</div>
<!-- Modal gerir e-mails de faturamento -->
<div class="modal fade none-border" id="modal-emails-faturamento">
	<div class="modal-dialog">
		<div class="modal-content cli-modal-cmp">
			<div class="row m-20">
				<div class="col-12">
					<div class="form-group cli-cmp-field">
						<label class="cli-cmp-label" for="email_faturamento_editor">E-mails de faturamento</label>
						<textarea id="email_faturamento_editor" class="form-control cli-cmp-input" rows="4" placeholder="Informe um e-mail por linha ou separados por ponto e vírgula"></textarea>
						<small class="form-text text-muted cli-cmp-help">Você pode informar vários e-mails de cobrança e faturamento. Eles serão salvos no mesmo campo utilizado atualmente pelo sistema.</small>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-pgm btn-pgm-salvar btn-success btn-salvar-emails-faturamento">Salvar</button>
				<button type="button" class="btn btn-secondary waves-effect" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>
<!-- Modal gerir e-mails de contato -->
<div class="modal fade none-border" id="modal-emails-contato">
	<div class="modal-dialog">
		<div class="modal-content cli-modal-cmp">
			<div class="row m-20">
				<div class="col-12">
					<div class="form-group cli-cmp-field">
						<label class="cli-cmp-label" for="emailresponsavel_editor">E-mails de contato / responsáveis</label>
						<textarea id="emailresponsavel_editor" class="form-control cli-cmp-input" rows="4" placeholder="Informe um e-mail por linha ou separados por ponto e vírgula"></textarea>
						<small class="form-text text-muted cli-cmp-help">Você pode informar vários e-mails. Eles serão salvos no mesmo campo utilizado atualmente pelo sistema.</small>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-pgm btn-pgm-salvar btn-success btn-salvar-emails-contato">Salvar</button>
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
				<?= $this->Html->link('Atualizar', [], ['class' => 'btn btn-atualizaDentroDoModal btn-pgm btn-pgm-salvar btn-success text-white m-l-5']) ?>
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
				<?= $this->Html->link('Confirmar', ['#'], ['class' => 'btn btn-pgm btn-pgm-salvar btn-success text-white btn-verificasenha m-l-5']) ?>
				<button type="button" class="btn btn-danger waves-effect" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>
<script>
<?= $this->element('Cli/toast_js') ?>
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
	// Fase 1 — modo leitura / edição (readonly; sem disabled global nos inputs)
		window.cliFichaEditing = false;
		var isClientePortalJs = <?= $isClientePortal ? 'true' : 'false' ?>;

		function cliFichaPrepareSubmit() {
			$('#form-edit-cliente select').prop('disabled', false);
			try { $('.selectpicker').selectpicker('refresh'); } catch (e) {}
		}

		function cliFichaSetViewMode() {
			window.cliFichaEditing = false;
			$('#form-edit-cliente').addClass('cli-ficha--readonly');
			$('#cli-ficha-mode-label').text('Modo leitura');
			$('#btn-cli-ficha-edit').show();
			$('#btn-cli-ficha-cancel').hide();
			$('#btn-cli-ficha-save').hide();
			$('#form-edit-cliente select').prop('disabled', true);
			try { $('.selectpicker').selectpicker('refresh'); } catch (e) {}
			$('.btn-gerenciar-emails-faturamento, .btn-gerenciar-emails, #btn-buscar-ie-edit').prop('disabled', true).addClass('disabled');
			<?php if ($isEquipe): ?>$('#btn-buscar-ie-edit').addClass('d-none');<?php endif; ?>
			tipo($("#tipo").val());
			$('#form-edit-cliente').find('input.form-control, textarea.form-control').each(function() {
				var $el = $(this);
				if ($el.is('[type=hidden]')) return;
				if ($el.prop('disabled')) return;
				$el.prop('readonly', true);
			});
		}

		function cliFichaSetEditMode() {
			window.cliFichaEditing = true;
			$('#form-edit-cliente').removeClass('cli-ficha--readonly');
			$('#cli-ficha-mode-label').text('Modo edição');
			$('#btn-cli-ficha-edit').hide();
			$('#btn-cli-ficha-cancel').show();
			$('#btn-cli-ficha-save').show();
			$('#form-edit-cliente select').prop('disabled', false);
			try { $('.selectpicker').selectpicker('refresh'); } catch (e) {}
			$('.btn-gerenciar-emails-faturamento, .btn-gerenciar-emails').prop('disabled', false).removeClass('disabled');
			<?php if ($isEquipe): ?>
			$('#btn-buscar-ie-edit').prop('disabled', false).removeClass('disabled').removeClass('d-none');
			<?php endif; ?>
			tipo($("#tipo").val());
			$('#form-edit-cliente').find('input.form-control, textarea.form-control').each(function() {
				var $el = $(this);
				if ($el.is('[type=hidden]')) return;
				if ($el.prop('disabled')) return;
				$el.prop('readonly', false);
			});
		}

		$('#btn-cli-ficha-edit').on('click', function() { cliFichaSetEditMode(); });
		$('#btn-cli-ficha-cancel').on('click', function() {
			if (confirm('Descartar alterações não salvas nesta página?')) {
				document.getElementById('form-edit-cliente').reset();
				try { $('.selectpicker').selectpicker('refresh'); } catch (e) {}
				var emailsFaturamentoRaw = $('#email').val() || '';
				atualizaDisplayEmailsFaturamento(emailsFaturamentoRaw);
				var emailsRaw = $('#emailresponsavel').val() || '';
				atualizaDisplayEmails(emailsRaw);
				tipo($("#tipo").val());
				if ($('#cli-ff-switch-inativo').length && $('#inativo').length) {
					$('#cli-ff-switch-inativo').prop('checked', $('#inativo').is(':checked'));
				}
				cliFichaSetViewMode();
			}
		});
		$('#btn-cli-ficha-save').on('click', function() {
			cliFichaPrepareSubmit();
			$('#cli-ficha-submit-fallback').trigger('click');
		});
		$('#form-edit-cliente').on('submit', function() {
			cliFichaPrepareSubmit();
			$('#cli-ficha-loading').removeClass('d-none').attr('aria-hidden', 'false');
		});
		window.addEventListener('pageshow', function () {
			$('#cli-ficha-loading').addClass('d-none').attr('aria-hidden', 'true');
		});

		<?php if ($isEquipe): ?>
		$('#cli-ff-switch-inativo').on('change', function() {
			if ($('#inativo').length) {
				$('#inativo').prop('checked', $(this).is(':checked'));
			}
			if (!window.cliFichaEditing) {
				cliFichaSetEditMode();
			}
		});
		$(function() {
			if ($('#cli-ff-switch-inativo').length && $('#inativo').length) {
				$('#cli-ff-switch-inativo').prop('checked', $('#inativo').is(':checked'));
			}
		});
		<?php endif; ?>

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

			$("#senhaadministrativa").prop('disabled', false);
			$("#exibirsenha").prop('disabled', false);

			cliFichaSetViewMode();

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
					$('td').removeClass('dark-mode');
				},
			});
			if (typeof filters !== 'undefined') {
				table.search(filters).draw();
			}

			// Inicializa visualização dos e-mails de faturamento
			var emailsFaturamentoRaw = $('#email').val() || '';
			atualizaDisplayEmailsFaturamento(emailsFaturamentoRaw);

			$('#modal-emails-faturamento').on('show.bs.modal', function () {
				$('#email_faturamento_editor').val(formataEmailsParaEdicao($('#email').val() || ''));
			});

			$('.btn-salvar-emails-faturamento').click(function(e) {
				e.preventDefault();
				var texto = $('#email_faturamento_editor').val() || '';
				var normalizado = normalizaEmails(texto);
				$('#email').val(normalizado);
				atualizaDisplayEmailsFaturamento(normalizado);
				$('#modal-emails-faturamento').modal('hide');
				if (typeof window.cliUiToast === 'function') {
					window.cliUiToast('E-mails de faturamento atualizados na tela. Use Salvar cliente para gravar no servidor.', 'success');
				}
			});

			// Inicializa visualização dos e-mails de contato/responsáveis
			var emailsRaw = $('#emailresponsavel').val() || '';
			atualizaDisplayEmails(emailsRaw);

			$('#modal-emails-contato').on('show.bs.modal', function () {
				$('#emailresponsavel_editor').val(formataEmailsParaEdicao($('#emailresponsavel').val() || ''));
			});

			$('.btn-salvar-emails-contato').click(function(e) {
				e.preventDefault();
				var texto = $('#emailresponsavel_editor').val() || '';
				var normalizado = normalizaEmails(texto);
				$('#emailresponsavel').val(normalizado);
				atualizaDisplayEmails(normalizado);
				$('#modal-emails-contato').modal('hide');
				if (typeof window.cliUiToast === 'function') {
					window.cliUiToast('E-mails de contato atualizados na tela. Use Salvar cliente para gravar no servidor.', 'success');
				}
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

		// Buscar IE (SEFAZ/SINTEGRA) na edição
		$('#btn-buscar-ie-edit').on('click', function(e){
			e.preventDefault();
			var cnpj = ($('#cnpj').val() || '').replace(/\D/g, '');
			if (cnpj.length !== 14) {
				alert('Informe um CNPJ válido (14 dígitos).');
				return;
			}
			var uf = ($('#uf_contribuinte_edit').val() || '').trim().toUpperCase();
			var self = $(this);
			function doConsultaIe(ufValue) {
				if (!ufValue) {
					alert('Não foi possível obter a UF. Selecione a cidade do cliente.');
					return;
				}
				var url = "<?= Router::url(['controller' => 'Clientes', 'action' => 'consultaIe']); ?>/" + encodeURIComponent(cnpj) + "/" + encodeURIComponent(ufValue);
				self.prop('disabled', true).text('Buscando...');
				$.getJSON(url, function(data){
					self.prop('disabled', false).text('Buscar IE');
					if (data && data.success && data.ie) {
						$('#inscricaoestadual').val(data.ie);
					} else {
						alert(data && data.message ? data.message : 'IE não encontrada ou serviço indisponível.');
					}
				}).fail(function(){
					self.prop('disabled', false).text('Buscar IE');
					alert('Erro ao acessar o serviço de consulta de IE. Verifique se a chave da API está configurada.');
				});
			}
			if (uf) {
				doConsultaIe(uf);
			} else {
				var idcidade = $('#idcidade').val();
				if (!idcidade) {
					alert('Selecione a cidade do cliente para obter a UF.');
					return;
				}
				$.get("<?= Router::url(['controller' => 'Clientes', 'action' => 'cidadesestado']); ?>/" + idcidade, function(sigla){
					$('#uf_contribuinte_edit').val(sigla);
					doConsultaIe((sigla || '').trim().toUpperCase());
				}).fail(function(){ alert('Não foi possível obter a UF da cidade.'); });
			}
		});
	// Tipo (disabled só no ramo inativo do PF/PJ — necessário para não enviar ambos no POST)
		$("#tipo").change(function(){
			tipo($(this).val());
			if (!window.cliFichaEditing) {
				cliFichaSetViewMode();
			} else {
				cliFichaSetEditMode();
			}
		});

		function tipo(tipo){
			if(tipo == 2){
				$("#razaosocial, #nomefantasia, #cnpj").prop('disabled', false);
				$("#nome, #cpffisica").prop('disabled', true);
				$('.pessoaFisica').hide();
				$('.pessoaJuridica').fadeIn();
			} else {
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
		$('.btn-atualizaToken').click(function(e){
			e.preventDefault();
			var url = "<?= Router::url(['controller' => 'Clientes', 'action' => 'updateToken', $cliente->id]);?>";
			if (!isClientePortalJs) window.location = url;
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

		// Inativar cliente (atalho visual)
		$('.btn-inativar-cliente').click(function(e) {
			e.preventDefault();
			if (confirm('Você confirma a inativação deste cliente no portal e no ERP?')) {
				$('#inativo').prop('checked', true);
				$('#form-edit-cliente').submit();
			}
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
		function atualizaDisplayEmailsFaturamento(texto) {
			if (!texto) {
				$('#email_faturamento_display').val('');
				$('#email_faturamento_display').attr('placeholder', 'Nenhum e-mail de faturamento cadastrado');
				return;
			}
			$('#email_faturamento_display').val(texto.replace(/;/g, '; '));
		}
</script>