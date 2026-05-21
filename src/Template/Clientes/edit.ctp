<?php
	use Cake\Routing\Router;
	$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-premium']));
	$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']));
	// Breadcumbs
	$this->Breadcrumbs->add('Clientes', ['controller' => 'Clientes', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Editar', [], ['class' => 'breadcrumb-item active']);

	function Mask($mask, $str) {
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

	$pessoaFisica = $cliente->tipo == C_ClientesTipoFisica ? '' : 'hide';
	$pessoaJuridica = $cliente->tipo == C_ClientesTipoJuridica ? '' : 'hide';

	// Equipe = não é portal cliente (C_RoleFuncionario pode ≠ 0 numérico; evita sumir abas Contratos/Token).
	$isClientePortal = isset($role) && (int)$role === (int)C_RoleCliente;
	$isEquipe = !$isClientePortal;
	// Fase 1 UX: sem disabled no HTML do cadastro; modo leitura via readonly + barra inferior (JS).
	$_uidRbacField = isset($iduser) ? (int)$iduser : 0;
	$_rbacClienteApiToken = \App\Utility\RbacChecker::resourceFieldAccess($_uidRbacField, 'Clientes.field.api_token');
	$showClienteApiTokenTab = ($isEquipe || !empty($permissaoacesso));
	if ($showClienteApiTokenTab && $_rbacClienteApiToken !== null && empty($_rbacClienteApiToken['visible'])) {
		$showClienteApiTokenTab = false;
	}
	$cliAllowTokenRenewal = $isEquipe && ($_rbacClienteApiToken === null || (!empty($_rbacClienteApiToken['visible']) && !empty($_rbacClienteApiToken['editable'])));
	$_rbacClienteInativo = $isEquipe ? \App\Utility\RbacChecker::resourceFieldAccess($_uidRbacField, 'Clientes.field.inativo') : null;
	$cliInativoRbacHidden = $isEquipe && $_rbacClienteInativo !== null && empty($_rbacClienteInativo['visible']);
	$cliInativoRbacReadonly = $isEquipe && $_rbacClienteInativo !== null && !empty($_rbacClienteInativo['visible']) && empty($_rbacClienteInativo['editable']);
	$cliPublicCode = trim((string)($cliente->public_code ?? ''));

?>
<style>.table td, .table th { padding: 0.4rem; }</style>
<div class="col-md-12 p-0 cli-ficha-layout-unificado">
	<div class="cli-form-root cli-layout-unificado cli-ficha-page-pad">
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
				<div class="cli-page-head-code-row" title="<?= h(__('Identificador único na empresa; gerado pelo sistema ou informado pela integração.')) ?>">
					<span class="cli-page-head-code-label"><?= __('Código do cliente') ?></span>
					<span class="cli-page-head-code" id="cli-public-code-ro" translate="no"><?= $cliPublicCode !== '' ? h($cliPublicCode) : '—' ?></span>
				</div>
			</div>
			<?php if ($isEquipe): ?>
				<div class="cli-crm-page-actions">
					<?= $this->Html->link('<i class="fas fa-history" aria-hidden="true"></i> ' . __('Histórico'), ['action' => 'eventos', $cliente->id], ['class' => 'btn-cli-secondary', 'escape' => false, 'title' => __('Eventos e auditoria do cliente'), 'data-turbo' => 'false']) ?>
					<?= $this->Html->link('<i class="fas fa-arrow-left" aria-hidden="true"></i> ' . __('Voltar'), ['action' => 'index'], ['class' => 'btn-cli-secondary', 'escape' => false, 'data-turbo' => 'false']) ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Tab nav (element reutilizável + deep-link #hash) -->
		<?= $this->element('Cli/edit_tabs_nav', array_merge(compact('isEquipe', 'isClientePortal', 'permissaoacesso'), ['showTokenTab' => $showClienteApiTokenTab, 'ativosCount' => is_countable($ativosCliente ?? null) ? count($ativosCliente) : 0])) ?>
			<div class="tab-content">
				<div class="tab-pane active" id="cliente" role="tabpanel" aria-labelledby="cli-tab-cliente">
					<?=  $this->Form->create($cliente, ['class' => 'form-material', 'id' => 'form-edit-cliente']) ?>
						<div class="cli-ficha-toolbar">
							<p class="cli-ficha-hint mb-0"><i class="fas fa-eye"></i> <span id="cli-ficha-mode-label">Modo leitura</span> — use a barra inferior para <strong>Editar</strong>, <strong>Salvar</strong> ou <strong>Cancelar</strong>.</p>
						</div>
						<?= $this->element('Cli/card', ['title' => 'Dados da empresa']) ?>
						<div class="row">
							<?= $this->element('Cli/select', ['label' => 'Tipo', 'field' => 'tipo', 'colClass' => 'col-lg-3 col-md-3 col-sm-12 col-xs-12', 'selectOptions' => C_ClientesTipo, 'options' => ['title' => 'Tipo do cliente', 'required' => true, 'class' => 'form-control']]) ?>
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
						$cliLabelHtmlIe = '<label class="cli-cmp-label d-flex justify-content-between align-items-center flex-wrap pgm-gap-6"><span>Inscrição Estadual <small class="text-muted">(somente números)</small></span>';
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
						<?= $this->element('Cli/card', ['title' => 'Dados operacionais']) ?>
							<div class="row">
							<?= $this->element('Cli/input', ['label' => 'Senha para o cliente visualizar os acessos', 'field' => 'senha', 'colClass' => 'col-lg-2 col-md-3 col-sm-3 col-xs-12', 'options' => ['placeholder' => 'Insira a senha']]) ?>
								<div class="custom-control custom-checkbox mr-sm-2 m-r-10 m-t-30">
									<?= $this->Form->checkbox('exibirsenhacliente', ['checked' => true, 'class' => 'custom-control-input', 'id' => 'exibirsenhacliente']); ?>
									<label class="custom-control-label text-muted" for="exibirsenhacliente">Exibir Senha </label>
								</div>
							<?= $this->element('Cli/select', ['label' => 'Empresa dominante', 'field' => 'empresadominante', 'colClass' => 'col-lg-3 col-md-3 col-sm-3 col-xs-12', 'selectOptions' => $empresasOptSidebar, 'options' => ['class' => 'form-control']]) ?>
							</div>
							<div class="row align-items-center">
								<?php if (!$cliInativoRbacHidden) : ?>
								<div class="col-lg-2 col-md-3 col-sm-3 col-xs-12">
									<div class="custom-control custom-checkbox mr-sm-2 m-r-10 m-l-10 m-t-5">
										<?= $this->Form->checkbox('inativo', [
											'class' => 'custom-control-input',
											'id' => 'inativo',
											'disabled' => $cliInativoRbacReadonly,
										]); ?>
										<label class="custom-control-label text-muted" for="inativo">Inativo </label>
									</div>
									<?php if ($cliInativoRbacReadonly) : ?>
										<p class="text-muted small mb-0 mt-1">Status inativo bloqueado por regra RBAC (<code class="ap-code-violet">Clientes.field.inativo</code>).</p>
									<?php endif; ?>
								</div>
								<?php endif; ?>
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
				<div class="tab-pane" id="acessos" role="tabpanel" aria-labelledby="cli-tab-acessos">
					<?= $this->element('Cli/card', ['headHtml' => '<i class="fas fa-desktop"></i> Acessos', 'extraClass' => 'mb-3']) ?>
					<?php if ($isEquipe) { ?>
						<div class="cli-subcard mb-3">
							<div class="cli-subcard-head">Quem pode ver senhas, contratos e token</div>
							<div class="cli-subcard-body">
								<p class="text-muted small mb-2">Usuários do cliente marcados abaixo passam a enxergar as abas sensíveis no portal. Alterações são salvas ao clicar em <strong>Salvar</strong> neste bloco (Flash na próxima tela).</p>
								<?= $this->Form->create(null, ['class' => 'form-material m-t-5', 'url' => ['controller' => 'Users', 'action' => 'permissaoacesso']]); ?>
									<div class="row align-items-end">
										<div class="col-md-8 col-xs-12">
											<label class="cli-label" for="users-ids">Usuários</label>
											<?= $this->Form->control('users._ids', ['value' => $usuariosValue, 'title' => 'Usuários', 'class' => 'form-control selectpicker', 'options' => $usuariosOptions, 'label' => false, 'id' => 'users-ids']) ?>
										</div>
										<?= $this->Form->hidden('idcliente', ['value' => $cliente->id]); ?>
										<div class="col-md-4 col-xs-12 m-t-15">
											<?= $this->Form->button('Salvar permissões', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success']) ?>
										</div>
									</div>
								<?= $this->Form->end(); ?>
							</div>
						</div>
						<div class="cli-subcard mb-3">
							<div class="cli-subcard-head">Incluir novo acesso</div>
							<div class="cli-subcard-body">
								<?= $this->Form->create(null, ['class' => 'form-material m-t-5', 'url' => ['controller' => 'Cliacessos', 'action' => 'add']]); ?>
									<div class="row">
										<div class="col-md-3 col-xs-12">
											<label class="cli-label" for="nomeservico">Nome do serviço</label>
											<?= $this->Form->control('nomeservico', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe o nome do serviço']);?>
										</div>
										<div class="col-md-3 col-xs-12">
											<label class="cli-label" for="usuarioaa">Usuário</label>
											<?= $this->Form->control('usuarioaa', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe o usuario', 'required' => true,]);?>
										</div>
										<div class="col-md-3 col-xs-12">
											<label class="cli-label" for="ip">IP</label>
											<?= $this->Form->control('ip', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe o ip']);?>
										</div>
										<div class="col-md-3 col-xs-12">
											<label class="cli-label" for="protocolo">Protocolo</label>
											<?= $this->Form->control('protocolo', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe o protocolo', 'list' => 'protocolos']);?>
											<datalist id="protocolos">
												<?php foreach(C_ProtocolosArray as $reg) echo '<option value="'.$reg.'">'; ?>
											</datalist>
										</div>
									</div>
									<div class="row m-t-5">
										<div class="col-md-2 col-xs-12">
											<label class="cli-label" for="nome-provedor-acesso">Provedor</label>
											<?= $this->Form->text('nome', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe o provedor', 'id' => 'nome-provedor-acesso']);?>
										</div>
										<div class="col-md-3 col-xs-12">
											<label class="cli-label" for="url">URL</label>
											<?= $this->Form->control('url', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe a url']);?>
										</div>
										<div class="col-md-2 col-xs-12">
											<label class="cli-label" for="porta">Porta</label>
											<?= $this->Form->control('porta', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Informe a porta']);?>
										</div>
										<div class="col-md-2 col-xs-12">
											<label class="cli-label" for="senha-acesso">Senha</label>
											<?= $this->Form->control('senha', ['type' => 'password', 'class' => 'form-control', 'label' => false, 'placeholder' => 'Informe a senha', 'required' => true, 'id' => 'senha-acesso']);?>
										</div>
										<div class="col-md-2 col-xs-12">
											<label class="cli-label" for="confirmasenha">Confirme a senha</label>
											<?= $this->Form->control('confirmasenha', ['type' => 'password', 'class' => 'form-control', 'label' => false, 'placeholder' => 'Informe novamente a senha', 'required' => true,]);?>
										</div>
									</div>
									<?= $this->Form->hidden('idcliente', ['value' => $cliente->id]); ?>
									<div class="row m-t-10">
										<div class="col-12 d-flex flex-wrap align-items-center pgm-gap-8">
											<a role="button" class="btn btn-danger btn-inativoAcessos text-white">Exibir inativos</a>
											<?= $this->Form->button('Adicionar acesso', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success ml-auto'], $cliente->id) ?>
										</div>
									</div>
								<?= $this->Form->end(); ?>
							</div>
						</div>
					<?php } ?>
					<div class="cli-section-title cli-section-title--mt12">Acessos cadastrados</div>
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
								<?php if ($isEquipe) { ?> <th class="cli-col-actions">Ações</th> <?php } ?>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($acessos as $reg):
									$inativo = '';
									if ($reg->inativo) {
										$inativo = 'inativoAcessos';
									}
									$rowAcessoClass = 'vesetainativoAcessos ' . $inativo . ($reg->inativo ? ' cli-row-acesso-inativo' : '');
								?>
									<tr class="<?= h($rowAcessoClass) ?>">
										<td><?= h($reg->nomeservico) ?></td>
										<td><?= h($reg->nome) ?></td>
										<td><?= h($reg->ip) ?></td>
										<td><?= h($reg->porta) ?></td>
										<td><?= h($reg->usuario) ?></td>
										<td><?= h($reg->url) ?></td>
										<td><?= h($reg->protocolo) ?></td>
										<td><a class="link senha" data-id="<?= (int)$reg->id ?>" href="#"> ********** </a></td>
										<td><?= $reg->inativo ? 'Não' : 'Sim' ?></td>
										<?php if ($isEquipe) { ?>
											<td class="td-actions">
												<?= $this->Html->link('<i class="fa fa-edit"></i>', ["controller" => "cliacessos", "action" => "edit", $reg->id], ['rel' => 'tooltip', 'title' => 'Editar', 'class' => 'btn btn-warning btn-simple btn-xs', 'escape' => false, 'data-turbo' => 'false']) ?>
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
				<div class="tab-pane" id="usuarios" role="tabpanel" aria-labelledby="cli-tab-usuarios">
					<?= $this->element('Cli/card', ['headHtml' => '<i class="fas fa-users"></i> Usuários do cliente', 'extraClass' => 'mb-3']) ?>
					<div class="d-flex flex-wrap align-items-center justify-content-between mb-2 pgm-gap-8">
						<p class="text-muted small mb-0">Edição abre em nova aba. Novo usuário escolhe o cliente no formulário de cadastro.</p>
						<?= $this->Html->link('<i class="fas fa-user-plus"></i> Novo usuário', ['controller' => 'Users', 'action' => 'addcliente'], ['class' => 'btn btn-sm btn-success', 'escape' => false, 'data-turbo' => 'false']) ?>
					</div>
					<div class="table-responsive">
						<table class="cli-acessos-table" id="tableUsers">
							<thead>
								<tr>
									<th>Usuário</th>
									<th>E-mail</th>
									<th>Nome</th>
									<th>Status</th>
									<th class="cli-col-actions">Ações</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($usuarios as $usr): ?>
									<?php
										$badgeClass = $usr->inativo ? 'badge-danger' : 'badge-success';
										$sit   = $usr->inativo ? 'Inativo' : 'Ativo';
									?>
									<tr>
										<td><?= h($usr->username) ?></td>
										<td><?= h($usr->email) ?></td>
										<td><?= h($usr->name) ?></td>
										<td><span class="badge <?= h($badgeClass) ?>"><?= h($sit) ?></span></td>
										<td class="td-actions">
											<?= $this->Html->link('<i class="fa fa-edit"></i>', ["controller" => "Users", "action" => "editcliente", $usr->id], ['rel' => 'tooltip', 'title' => 'Editar usuário do cliente', 'class' => 'btn btn-warning btn-simple btn-xs', 'escape' => false, 'data-turbo' => 'false']) ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?= $this->element('Cli/card_end') ?>
				</div>
				<?php } if($isEquipe || !empty($permissaoacesso)){ ?>
				<div class="tab-pane" id="contratos" role="tabpanel" aria-labelledby="cli-tab-contratos">
					<?= $this->element('Cli/card', ['headHtml' => '<i class="fas fa-file-contract"></i> Contratos', 'extraClass' => 'mb-3']) ?>
					<?php if ($isEquipe) : ?>
					<?= $this->Html->link('Cadastrar item', ['controller' => 'Clicontratos', 'action' => 'add', $cliente->id], ['class' => 'btn btn-pgm btn-pgm-salvar btn-success  m-r-5 m-b-20']) ?>
					<?= $this->Html->link('Contratos de Horas Técnicas', ['controller' => 'ContratosHoras', 'action' => 'index', $cliente->id], ['class' => 'btn btn-pgm btn-pgm-situacao btn-info m-r-5 m-b-20']) ?>
					<?= $this->Html->link('Cadastrar Contrato de Horas', ['controller' => 'ContratosHoras', 'action' => 'add', $cliente->id], ['class' => 'btn btn-pgm btn-pgm-salvar text-white m-r-5 m-b-20']) ?>
					<?php endif; ?>
					<?php $contratosRowUi = isset($contratosRowUi) && is_array($contratosRowUi) ? $contratosRowUi : []; ?>
					<p class="cli-ctr-legend mb-2">Situação do item (mesma regra do resumo no rodapé): <span class="badge badge-success">Ativo</span> <span class="badge badge-warning text-dark">Vence em 30 dias</span> <span class="badge badge-danger">Vencido</span> <span class="badge badge-secondary">Cancelado / sem validade</span></p>
					<div class="table-responsive">
						<table class="cli-acessos-table" id="tableContratos">
							<thead>
								<tr>
									<th>Situação</th>
									<th>Cód. Produto</th>
									<th>Descrição</th>
									<th>Inf. Adicional</th>
									<th>Vl. Unit.</th>
									<th>Qtde</th>
									<th>Vl. Total</th>
									<th>Dt. Contratação</th>
									<th>Dt. Validade</th>
									<th>Dt. Cancelamento</th>
									<th>Ações</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($contratos as $reg):
									$rid = (int)$reg->id;
									$cui = $contratosRowUi[$rid] ?? ['label' => '—', 'row_class' => '', 'badge_class' => 'badge-secondary'];
									$trClass = trim('cli-ctr-contract-row ' . ($cui['row_class'] ?? ''));
								?>
									<tr class="<?= h($trClass) ?>">
										<td><span class="badge <?= h($cui['badge_class'] ?? 'badge-secondary') ?>"><?= h($cui['label'] ?? '—') ?></span></td>
										<td><?= h($reg->codproduto) ?></td>
										<td><?= h($reg->descricao) ?></td>
										<td><?= h($reg->infadicional) ?></td>
										<td><?= number_format($reg->vlunit, 2, ',', '.') ?></td>
										<td><?= h($reg->qtde) ?></td>
										<td><?= number_format($reg->vltotal, 2, ',', '.') ?></td>
										<td><?php if(!empty($reg->dtcontratacao)) { echo h(date_format($reg->dtcontratacao, 'd/m/Y')); } ?></td>
										<td><?php if(!empty($reg->dtvalidade)) { echo h(date_format($reg->dtvalidade, 'd/m/Y')); } ?></td>
										<td><?php if(!empty($reg->dtcancelamento)) { echo h(date_format($reg->dtcancelamento, 'd/m/Y')); } ?></td>
										<td class="td-actions">
											<?= $this->Html->link('<i class="fa fa-eye"></i>', ['controller' => 'Clicontratos', 'action' => 'view', $reg->id], ['rel' => 'tooltip', 'title' => 'Detalhe', 'class' => 'btn btn-info btn-simple btn-xs', 'escape' => false, 'data-turbo' => 'false']) ?>
											<?= $this->Html->link('<i class="fa fa-edit"></i>', ["controller" => "clicontratos", "action" => "edit", $reg->id], ['rel' => 'tooltip', 'title' => 'Editar', 'class' => 'btn btn-warning btn-simple btn-xs', 'escape' => false, 'data-turbo' => 'false']) ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?= $this->element('Cli/card_end') ?>
				</div>
				<?php }
				if (!empty($cliente->id)) :
					$ativosCli = $ativosCliente ?? [];
					$ativosCount = is_countable($ativosCli) ? count($ativosCli) : 0;
				?>
				<div class="tab-pane" id="ativos" role="tabpanel" aria-labelledby="cli-tab-ativos">
					<?= $this->element('Cli/card', ['headHtml' => '<i class="fas fa-server"></i> Ativos de TI deste cliente <span class="badge badge-secondary" style="margin-left:6px">' . (int)$ativosCount . '</span>', 'extraClass' => 'mb-3']) ?>
					<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap" style="gap:8px">
						<input type="search" id="cli-ativos-filter" class="form-control form-control-sm" placeholder="Filtrar por descrição, série, hostname…" style="max-width:320px"/>
						<div>
							<?= $this->Html->link('<i class="fas fa-list"></i> Ver todos', ['controller' => 'Ativos', 'action' => 'index', '?' => ['idcliente' => $cliente->id]], ['class' => 'btn btn-sm btn-outline-secondary mr-2', 'escape' => false, 'data-turbo' => 'false']) ?>
							<?= $this->Html->link('<i class="fas fa-plus"></i> Cadastrar ativo', ['controller' => 'Ativos', 'action' => 'add', '?' => ['idcliente' => $cliente->id]], ['class' => 'btn btn-sm btn-success', 'escape' => false, 'data-turbo' => 'false']) ?>
						</div>
					</div>
					<?php if ($ativosCount === 0) : ?>
						<p class="text-muted small mb-0">Nenhum ativo cadastrado para este cliente.</p>
					<?php else : ?>
					<div class="table-responsive">
						<table class="table table-sm table-striped" id="cli-ativos-table">
							<thead>
								<tr>
									<th>Identificador</th>
									<th>Descrição</th>
									<th>Tipo</th>
									<th>Marca/Modelo</th>
									<th>Nº Série</th>
									<th>Hostname</th>
									<th>Status</th>
									<th class="td-actions">Ações</th>
								</tr>
							</thead>
							<tbody>
							<?php foreach ($ativosCli as $a) :
								$idTag = $a->identificador ?: ('ATV-' . str_pad((string)$a->id, 6, '0', STR_PAD_LEFT));
								$status = (string)($a->status_operacional ?? '');
								$statusLabels = [
									'em_uso' => 'Em uso', 'estoque' => 'Em estoque', 'manutencao' => 'Manutenção',
									'reservado' => 'Reservado', 'descartado' => 'Descartado', 'perdido' => 'Perdido',
								];
							?>
								<tr>
									<td><code><?= h($idTag) ?></code></td>
									<td><?= h($a->descricao ?: '—') ?></td>
									<td><?= h($a->tipo ?: '—') ?></td>
									<td><?= h(trim((string)($a->marca ?? '') . ' ' . (string)($a->modelo ?? ''))) ?: '—' ?></td>
									<td><code><?= h($a->numero_serie ?: '—') ?></code></td>
									<td><code><?= h($a->hostname ?: '—') ?></code></td>
									<td><?= h($statusLabels[$status] ?? '—') ?></td>
									<td class="td-actions">
										<?= $this->Html->link('<i class="fa fa-eye"></i>', ['controller' => 'Ativos', 'action' => 'view', $a->id], ['rel' => 'tooltip', 'title' => 'Ver', 'class' => 'btn btn-info btn-simple btn-xs', 'escape' => false, 'data-turbo' => 'false']) ?>
										<?= $this->Html->link('<i class="fa fa-edit"></i>', ['controller' => 'Ativos', 'action' => 'edit', $a->id], ['rel' => 'tooltip', 'title' => 'Editar', 'class' => 'btn btn-warning btn-simple btn-xs', 'escape' => false, 'data-turbo' => 'false']) ?>
									</td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<script>
					(function () {
						var input = document.getElementById('cli-ativos-filter');
						var rows = document.querySelectorAll('#cli-ativos-table tbody tr');
						if (!input || !rows.length) return;
						input.addEventListener('input', function () {
							var q = (input.value || '').toLowerCase();
							rows.forEach(function (tr) {
								tr.style.display = tr.textContent.toLowerCase().indexOf(q) >= 0 ? '' : 'none';
							});
						});
					})();
					</script>
					<?php endif; ?>
					<?= $this->element('Cli/card_end') ?>
				</div>
				<?php endif; ?>
				<?php if($isClientePortal ){ ?>
				<div class="tab-pane" id="acessosCliente" role="tabpanel" aria-labelledby="cli-tab-acessosCliente">
					<?= $this->element('Cli/card', ['headHtml' => '<i class="fas fa-desktop"></i> Meus acessos', 'extraClass' => 'mb-3']) ?>
					<p class="text-muted small mb-2">Senhas podem ser exibidas mediante clique; não compartilhe em canais inseguros.</p>
					<div class="table-responsive">
						<table class="cli-acessos-table" id="tableAcessosClientes">
							<thead>
								<tr>
									<th>Provedor</th>
									<th>IP</th>
									<th>Usuário</th>
									<th>Senha</th>
									<th>Ativo</th>
									<?php if ($isEquipe) { ?><th>Ações</th><?php } ?>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($acessos as $reg):
								$inativo = '';
								if ($reg->inativo) {
									$inativo = 'inativoAcessos';
								}
								$rowPortalClass = 'vesetainativoAcessos ' . $inativo . ($reg->inativo ? ' cli-row-acesso-inativo' : '');
								?>
									<tr class="<?= h($rowPortalClass) ?>">
										<td><?= h($reg->nome) ?></td>
										<td><?= h($reg->ip) ?></td>
										<td><?= h($reg->usuario) ?></td>
										<td><a class="link senha cli-senha-mask" data-id="<?= (int)$reg->id ?>" href="#" title="Clique para revelar">••••••••</a></td>
										<td><?= $reg->inativo == 1 ? 'Não' : 'Sim' ?></td>
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
				<?php } ?>
				<?php if ($showClienteApiTokenTab) { ?>
				<div class="tab-pane" id="token" role="tabpanel" aria-labelledby="cli-tab-token">
					<?= $this->element('Cli/card', ['headHtml' => '<i class="fas fa-key"></i> Token de integração API', 'extraClass' => 'mb-3']) ?>
					<div class="cli-token-panel cli-token-panel--split">
						<div class="cli-token-panel__col">
							<div class="cli-sf-kicker">Valor atual (somente leitura)</div>
							<div class="cli-token-box" id="token-display" aria-readonly="true"><?= h($cliente->token) ?></div>
							<p class="cli-token-note mb-0">Usado para autenticar integrações externas com a API do portal. <strong>Não há data de expiração cadastrada</strong> — a renovação é manual.</p>
						</div>
						<div class="cli-token-panel__col">
							<?php if ($cliAllowTokenRenewal) { ?>
								<div class="cli-sf-kicker">Renovação (equipe)</div>
								<p class="cli-token-note">Gerar um novo token <strong>invalida o valor anterior</strong> nas integrações que ainda o utilizam.</p>
								<div class="mt-2">
									<?= $this->Html->link('<i class="fas fa-sync-alt"></i> Atualizar token', [], ['class' => 'btn-atualizaToken btn btn-sm btn-outline-warning salvarcliente', 'escape' => false]) ?>
								</div>
							<?php } elseif ($isEquipe) { ?>
								<div class="cli-token-callout">
									<strong>Só leitura.</strong> A renovação do token exige permissão adicional no catálogo RBAC (regra <code class="ap-code-violet">Clientes.field.api_token</code>).
								</div>
							<?php } else { ?>
								<div class="cli-token-callout">
									<strong>Portal do cliente.</strong> A renovação do token é feita pela equipe PGM. Em caso de vazamento ou troca de sistema, solicite suporte.
								</div>
							<?php } ?>
						</div>
					</div>
					<?= $this->element('Cli/card_end') ?>
				</div>
				<?php }  ?>
		</div><!-- /tab-content -->

	</div><!-- /cli-card -->
	</div><!-- /cli-form-root -->

<?php
	$sfAlert = !empty($cliFooter) && (!empty($cliFooter['contratos_vencidos']) || !empty($cliFooter['contratos_vencendo30']));
?>
<div class="cli-ficha-footer-fixed<?= $sfAlert ? ' cli-smart-footer--alert' : '' ?>" id="cli-ficha-footer-bar">
	<div class="cli-ff-inner cli-sf-inner">
		<div class="cli-sf-main">
			<div class="cli-sf-block">
				<div class="cli-sf-kicker">Status do cliente</div>
				<?php if ($isEquipe): ?>
					<?php if ($cliInativoRbacHidden): ?>
				<span class="badge badge-<?= !empty($cliFooter['status_class']) ? h($cliFooter['status_class']) : 'secondary' ?>"><?= !empty($cliFooter['status_label']) ? h($cliFooter['status_label']) : '—' ?></span>
				<p class="cli-sf-token-note mb-0 mt-1">Alteração de inativo não disponível (RBAC <code class="ap-code-violet">Clientes.field.inativo</code>).</p>
					<?php elseif ($cliInativoRbacReadonly): ?>
				<span class="badge badge-<?= !empty($cliFooter['status_class']) ? h($cliFooter['status_class']) : 'secondary' ?>"><?= !empty($cliFooter['status_label']) ? h($cliFooter['status_label']) : '—' ?></span>
				<p class="cli-sf-token-note mb-0 mt-1">Somente leitura — regra <code class="ap-code-violet">Clientes.field.inativo</code>.</p>
					<?php else: ?>
				<div class="custom-control custom-switch mt-1">
					<input type="checkbox" class="custom-control-input" id="cli-ff-switch-inativo" <?= !empty($cliente->inativo) ? 'checked' : '' ?> aria-describedby="cli-ff-status-hint">
					<label class="custom-control-label" for="cli-ff-switch-inativo">Cliente inativo</label>
				</div>
				<p class="cli-sf-token-note mb-0 mt-1 d-md-none" id="cli-ff-status-hint">Altere o switch e use <strong>Salvar</strong> na barra para persistir.</p>
					<?php endif; ?>
				<?php else: ?>
				<span class="badge badge-<?= !empty($cliFooter['status_class']) ? h($cliFooter['status_class']) : 'secondary' ?>"><?= !empty($cliFooter['status_label']) ? h($cliFooter['status_label']) : '—' ?></span>
				<?php endif; ?>
			</div>
			<?php if (!empty($cliFooter)): ?>
			<div class="cli-sf-block cli-sf-contracts">
				<div class="cli-sf-kicker">Contratos (resumo)</div>
				<div class="small cli-sf-contracts-total-line">
					<strong>Total:</strong> <?= (int)$cliFooter['contratos_total'] ?>
					<?php if (!empty($cliFooter['contratos_vencidos'])): ?>
						<span class="badge cli-sf-badge-danger ml-1"><?= (int)$cliFooter['contratos_vencidos'] ?> vencido(s)</span>
					<?php endif; ?>
					<?php if (!empty($cliFooter['contratos_vencendo30'])): ?>
						<span class="badge cli-sf-badge-warn ml-1"><?= (int)$cliFooter['contratos_vencendo30'] ?> vence em 30 dias</span>
					<?php endif; ?>
				</div>
			</div>
			<?php if (!empty($showClienteApiTokenTab)) : ?>
			<div class="cli-sf-block cli-sf-token d-none d-md-block">
				<div class="cli-sf-kicker">Token / integração</div>
				<p class="cli-sf-token-note mb-0"><?= h($cliFooter['token_note']) ?></p>
			</div>
			<?php endif; ?>
			<?php endif; ?>
		</div>
		<div class="cli-ff-actions">
			<button type="button" class="btn-cli-secondary" id="btn-cli-ficha-edit"><i class="fas fa-pen" aria-hidden="true"></i> <?= h(__('Editar cliente')) ?></button>
			<button type="button" class="btn-cli-secondary d-none" id="btn-cli-ficha-cancel"><i class="fas fa-undo" aria-hidden="true"></i> <?= h(__('Cancelar')) ?></button>
			<button type="button" class="btn-cli-primary btn-cli-ficha-save d-none" id="btn-cli-ficha-save"><i class="fas fa-check" aria-hidden="true"></i> <?= h(__('Salvar cliente')) ?></button>
			<?php if ($isEquipe && empty($cliente->inativo) && !$cliInativoRbacHidden && !$cliInativoRbacReadonly): ?>
			<button type="button" class="btn-cli-secondary btn-inativar-cliente" style="border-color:rgba(226,75,74,.4);color:var(--orc-red,#e24b4a);"><i class="fas fa-user-slash" aria-hidden="true"></i> <?= h(__('Inativar')) ?></button>
			<?php endif; ?>
		</div>
	</div>
</div>
</div><!-- /col-md-12 cli-ficha-layout-unificado -->

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
<?php
	$pgmClienteEditCfg = [
		'clienteId' => (int)$cliente->id,
		'isEquipe' => !empty($isEquipe),
		'isClientePortal' => !empty($isClientePortal),
		'urls' => [
			'cidadesestado' => Router::url(['controller' => 'Clientes', 'action' => 'cidadesestado']),
			'consultaIe' => Router::url(['controller' => 'Clientes', 'action' => 'consultaIe']),
			'updateToken' => Router::url(['controller' => 'Clientes', 'action' => 'updateToken', $cliente->id]),
			'verificadadoscliente' => Router::url(['controller' => 'Users', 'action' => 'verificadadoscliente']),
			'verificasenha' => Router::url(['controller' => 'Cliacessos', 'action' => 'verificasenha']),
		],
	];
?>
<?= $this->Html->script('/pgm-assets/js/modules/clientes/cliente-edit') ?>
<script>
window.PgmClienteEditConfig = <?= json_encode($pgmClienteEditCfg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
<?= $this->Html->script('/pgm-assets/js/modules/clientes/cliente-edit-ficha') ?>
<?= $this->Html->script('/pgm-assets/js/modules/clientes/cliente-edit-ficha-acessos') ?>
<script>
<?= $this->element('Cli/toast_js') ?>
<?= $this->element('Cli/edit_tabs_js') ?>
</script>