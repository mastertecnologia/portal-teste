<?php
use Cake\Routing\Router;

$this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']));

$defaultEmpresaDominanteId = null;
if (!empty($empresasOptSidebar)) {
	foreach ($empresasOptSidebar as $idEmpresa => $nomeEmpresa) {
		if (stripos($nomeEmpresa, 'PGM') !== false) {
			$defaultEmpresaDominanteId = $idEmpresa;
			break;
		}
	}
	if ($defaultEmpresaDominanteId === null) {
		$keys = array_keys($empresasOptSidebar);
		$defaultEmpresaDominanteId = reset($keys);
	}
}
$tipoOpts = [
	(int)C_ClientesTipoJuridica => __('Pessoa Jurídica (CNPJ)'),
	(int)C_ClientesTipoFisica => __('Pessoa Física (CPF)'),
];
?>
<?= $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']) ?>

<div class="pg cli-form-root" id="pg-cliente-novo">

<?= $this->Form->create($cliente, ['class' => 'cli-add-form', 'id' => 'cli-add-form', 'data-turbo' => 'false']) ?>

	<?= $this->element('Cli/cadastro_page_header', [
		'pageTitle' => __('Novo cadastro de cliente'),
		'showHeaderSave' => true,
		'showHeaderDraft' => true,
		'cancelUrl' => ['action' => 'index'],
	]) ?>

	<?= $this->element('Cli/cadastro_stepper_decorativo') ?>

	<div id="cadastro-empresa-avisos" class="alert d-none" role="alert" style="margin-bottom:14px;">
		<strong><?= h(__('Origem dos dados:')) ?></strong>
		<ul id="cadastro-empresa-avisos-lista" class="mb-0 mt-1"></ul>
	</div>

	<div class="g2" style="gap:14px;align-items:start;">
		<div style="display:flex;flex-direction:column;gap:14px;">
			<div class="card" style="margin-bottom:0;">
				<div class="sec-title"><?= h(__('Identificação')) ?></div>
				<div class="g2" style="margin-bottom:10px;">
					<div class="field">
						<label><?= h(__('Tipo de pessoa')) ?> *</label>
						<?= $this->Form->control('tipo', [
							'id' => 'tipo',
							'options' => $tipoOpts,
							'required' => false,
							'label' => false,
							'class' => 'form-control',
						]) ?>
					</div>
					<div class="field">
						<label><?= h(__('Código do cliente')) ?></label>
						<input type="text" readonly value="" placeholder="<?= h(__('Gerado ao salvar')) ?>" style="background:var(--gray-100,#f1f5f9);font-family:monospace;color:var(--text-muted);"/>
					</div>
				</div>

				<?= $this->element('Cli/papel_cadastro_checkboxes', [
					'cliente' => $cliente,
					'cliPapelColumns' => !empty($cliPapelColumns),
					'showFornecedorExtras' => !empty($cliPrefFornecedor),
					'embedInIdentificacao' => true,
				]) ?>

				<div class="cli-bloco-pj pessoaJuridica">
					<div class="g2" style="margin-bottom:10px;">
						<div class="field">
							<label><?= h(__('CNPJ')) ?> *</label>
							<div style="display:flex;gap:6px;">
								<?= $this->Form->control('cnpj', ['class' => 'form-control', 'id' => 'cnpj', 'label' => false, 'placeholder' => '00.000.000/0000-00', 'style' => 'flex:1;']) ?>
								<button type="button" class="btn btn-blue btn-sm" id="btn-buscar-cnpj" title="<?= h(__('Consultar Receita Federal')) ?>">🔍</button>
							</div>
							<input type="hidden" id="uf_contribuinte" value="" />
							<div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= h(__('Clique em Buscar para preencher automaticamente via Receita Federal')) ?></div>
						</div>
						<div class="field">
							<label><?= h(__('Inscrição estadual')) ?></label>
							<div style="display:flex;gap:6px;">
								<?= $this->Form->control('inscricaoestadual', ['id' => 'inscricaoestadual', 'onkeypress' => 'return SomenteNumero(event)', 'class' => 'form-control', 'label' => false, 'placeholder' => __('000/0000000 ou ISENTO'), 'style' => 'flex:1;']) ?>
								<button type="button" class="btn btn-blue btn-sm" id="btn-buscar-ie" title="<?= h(__('Consultar IE')) ?>">🔍</button>
							</div>
						</div>
					</div>
					<div class="field" style="margin-bottom:10px;">
						<label><?= h(__('Razão social')) ?> *</label>
						<?= $this->Form->control('razaosocial', ['class' => 'form-control', 'label' => false, 'placeholder' => __('Nome legal completo conforme CNPJ')]) ?>
					</div>
					<div class="field" style="margin-bottom:10px;">
						<label><?= h(__('Nome fantasia')) ?></label>
						<?= $this->Form->control('nomefantasia', ['class' => 'form-control', 'label' => false, 'placeholder' => __('Nome comercial / como o cliente é conhecido')]) ?>
					</div>
					<div class="g2" style="margin-bottom:10px;">
						<div class="field">
							<label><?= h(__('Inscrição municipal')) ?></label>
							<?= $this->Form->control('inscricaomunicipal', ['onkeypress' => 'return SomenteNumero(event)', 'class' => 'form-control', 'label' => false, 'placeholder' => __('Para emissão de NFS-e')]) ?>
						</div>
						<div class="field">
							<label><?= h(__('CNAE principal')) ?></label>
							<input type="text" disabled class="form-control" placeholder="0000-0/00" title="<?= h(__('Campo previsto no ERP — em breve')) ?>"/>
						</div>
					</div>
					<div class="g2" style="margin-bottom:10px;">
						<div class="field">
							<label><?= h(__('Regime tributário')) ?> *</label>
							<select disabled class="form-control" title="<?= h(__('Campo previsto no ERP — em breve')) ?>">
								<option><?= h(__('Simples Nacional')) ?></option>
							</select>
						</div>
						<div class="field">
							<label><?= h(__('Data de abertura')) ?></label>
							<input type="date" disabled class="form-control" title="<?= h(__('Campo previsto no ERP — em breve')) ?>"/>
						</div>
					</div>
					<div class="g3" style="margin-bottom:0;">
						<div class="field">
							<label><?= h(__('Nome do responsável')) ?></label>
							<?= $this->Form->control('nomeresponsavel', ['class' => 'form-control', 'label' => false, 'placeholder' => __('Sócio / administrador')]) ?>
						</div>
						<div class="field">
							<label><?= h(__('CPF do responsável')) ?></label>
							<?= $this->Form->control('cpf', ['id' => 'cpfresponsavel', 'class' => 'form-control', 'label' => false, 'placeholder' => '000.000.000-00']) ?>
						</div>
						<div class="field">
							<label><?= h(__('RG do responsável')) ?></label>
							<?= $this->Form->control('rg', ['class' => 'form-control', 'label' => false, 'placeholder' => __('Documento de identidade')]) ?>
						</div>
					</div>
				</div>

				<div class="cli-bloco-pf pessoaFisica" style="display:none;">
					<div class="g2" style="margin-bottom:0;">
						<div class="field">
							<label><?= h(__('Nome completo')) ?> *</label>
							<?= $this->Form->control('nome', ['class' => 'form-control', 'label' => false, 'placeholder' => __('Nome completo')]) ?>
						</div>
						<div class="field">
							<label><?= h(__('CPF')) ?></label>
							<?= $this->Form->control('cpf', ['id' => 'cpffisica', 'class' => 'form-control', 'label' => false, 'placeholder' => '000.000.000-00']) ?>
						</div>
					</div>
				</div>
			</div>

			<div class="card" style="margin-bottom:0;">
				<div class="sec-title"><?= h(__('Endereço principal')) ?></div>
				<div class="g2" style="margin-bottom:10px;">
					<div class="field">
						<label><?= h(__('CEP')) ?> *</label>
						<div style="display:flex;gap:6px;">
							<?= $this->Form->control('cep', ['class' => 'form-control', 'id' => 'cep', 'label' => false, 'placeholder' => '00000-000', 'required' => true, 'style' => 'flex:1;']) ?>
							<button type="button" class="btn btn-blue btn-sm" id="btn-buscar-cep" title="<?= h(__('Buscar CEP')) ?>">🔍</button>
						</div>
					</div>
					<div class="field">
						<label><?= h(__('Tipo de endereço')) ?></label>
						<select disabled class="form-control" title="<?= h(__('Campo previsto no ERP — em breve')) ?>">
							<option><?= h(__('Comercial · Sede')) ?></option>
						</select>
					</div>
				</div>
				<div class="field" style="margin-bottom:10px;">
					<label><?= h(__('Logradouro')) ?> *</label>
					<?= $this->Form->control('endereco', ['class' => 'form-control', 'label' => false, 'placeholder' => __('Rua / Avenida / Travessa…'), 'required' => true]) ?>
				</div>
				<div style="display:grid;grid-template-columns:120px 1fr;gap:10px;margin-bottom:10px;">
					<div class="field"><label><?= h(__('Número')) ?> *</label><?= $this->Form->control('nroendereco', ['class' => 'form-control', 'label' => false, 'placeholder' => '123', 'required' => true]) ?></div>
					<div class="field"><label><?= h(__('Complemento')) ?></label><?= $this->Form->control('complemento', ['class' => 'form-control', 'label' => false, 'placeholder' => __('Sala, andar, bloco…')]) ?></div>
				</div>
				<div class="field" style="margin-bottom:10px;">
					<label><?= h(__('Bairro')) ?> *</label>
					<?= $this->Form->control('bairro', ['class' => 'form-control', 'label' => false, 'placeholder' => __('Bairro'), 'required' => true]) ?>
				</div>
				<div class="field" style="margin-bottom:0;">
					<label><?= h(__('Cidade')) ?> *</label>
					<?= $this->Form->control('idcidade', ['data-live-search' => 'true', 'class' => 'selectpicker form-control', 'options' => $cidades, 'label' => false]) ?>
				</div>
			</div>

			<div class="card" style="margin-bottom:0;">
				<div class="sec-title"><?= h(__('Contatos')) ?></div>
				<div class="g2" style="margin-bottom:10px;">
					<div class="field">
						<label><?= h(__('Telefone principal')) ?> *</label>
						<?= $this->Form->control('fone', ['class' => 'form-control', 'id' => 'fone', 'label' => false, 'placeholder' => '(00) 00000-0000']) ?>
					</div>
					<div class="field">
						<label><?= h(__('WhatsApp')) ?></label>
						<?= $this->Form->control('fone2', ['class' => 'form-control', 'id' => 'fone2', 'label' => false, 'placeholder' => '(00) 00000-0000']) ?>
					</div>
				</div>
				<div class="field" style="margin-bottom:10px;">
					<label><?= h(__('E-mail principal')) ?> *</label>
					<?= $this->Form->email('email', ['id' => 'email', 'class' => 'form-control', 'label' => false, 'placeholder' => 'contato@empresa.com.br']) ?>
				</div>
				<div class="field" style="margin-bottom:10px;">
					<label><?= h(__('Site / domínio')) ?></label>
					<?= $this->Form->control('site', ['class' => 'form-control', 'id' => 'site', 'label' => false, 'placeholder' => 'https://www.empresa.com.br', 'autocomplete' => 'url']) ?>
				</div>
				<p style="font-size:11px;color:var(--text-muted);margin:0;"><?= h(__('Pessoas de contato adicionais: cadastre após salvar, na ficha do cliente.')) ?></p>
			</div>
		</div>

		<div style="display:flex;flex-direction:column;gap:14px;">
			<div class="card" style="margin-bottom:0;">
				<div class="sec-title"><?= h(__('Configuração financeira')) ?></div>
				<div class="g2" style="margin-bottom:10px;">
					<div class="field">
						<label><?= h(__('Limite de crédito')) ?></label>
						<?= $this->Form->control('limite_credito', ['class' => 'form-control', 'label' => false, 'placeholder' => 'R$ 0,00', 'style' => 'font-weight:600;']) ?>
					</div>
					<div class="field">
						<label><?= h(__('Status')) ?></label>
						<select disabled class="form-control" title="<?= h(__('Campo previsto no ERP — em breve')) ?>"><option><?= h(__('Ativo')) ?></option></select>
					</div>
				</div>
				<div class="g2" style="margin-bottom:10px;">
					<div class="field">
						<label><?= h(__('Condição padrão')) ?></label>
						<select disabled class="form-control"><option><?= h(__('30/60 dias')) ?></option></select>
					</div>
					<div class="field">
						<label><?= h(__('Forma preferida')) ?></label>
						<select disabled class="form-control"><option><?= h(__('Boleto bancário')) ?></option></select>
					</div>
				</div>
				<div class="field" style="margin-bottom:10px;">
					<label><?= h(__('Score interno (0–10)')) ?></label>
					<?= $this->Form->control('score_interno', ['class' => 'form-control', 'label' => false, 'placeholder' => '9,2']) ?>
				</div>
				<div class="field" style="margin-bottom:0;">
					<label><?= h(__('Observações financeiras')) ?></label>
					<?= $this->Form->control('observacoes_financeiras', ['type' => 'textarea', 'rows' => 2, 'class' => 'form-control', 'label' => false, 'placeholder' => __('Ex: cliente paga sempre no dia 25, prefere boleto…')]) ?>
				</div>
			</div>

			<div class="card pessoaJuridica" style="margin-bottom:0;">
				<div class="sec-title"><?= h(__('Configuração fiscal')) ?></div>
				<div class="field" style="margin-bottom:10px;">
					<label><?= h(__('Tipo de operação fiscal')) ?></label>
					<select disabled class="form-control"><option><?= h(__('Prestação de serviço (NFS-e)')) ?></option></select>
				</div>
				<div class="g2" style="margin-bottom:10px;">
					<div class="field">
						<label><?= h(__('Indicador de IE')) ?></label>
						<select disabled class="form-control"><option><?= h(__('9 · Não contribuinte')) ?></option></select>
					</div>
					<div class="field">
						<label><?= h(__('Suframa')) ?></label>
						<input type="text" disabled class="form-control" placeholder="<?= h(__('Apenas se aplicável')) ?>"/>
					</div>
				</div>
				<div class="field" style="margin-bottom:0;">
					<label><?= h(__('CFOP padrão')) ?></label>
					<select disabled class="form-control"><option><?= h(__('5933 · Prestação de serviço de comunicação')) ?></option></select>
				</div>
			</div>

			<div class="card" style="margin-bottom:0;">
				<div class="sec-title"><?= h(__('Comercial & CRM')) ?></div>
				<div class="g2" style="margin-bottom:10px;">
					<div class="field">
						<label><?= h(__('Segmento')) ?> *</label>
						<select disabled class="form-control"><option><?= h(__('Serviços')) ?></option></select>
					</div>
					<div class="field">
						<label><?= h(__('Origem do contato')) ?></label>
						<select disabled class="form-control"><option><?= h(__('Indicação')) ?></option></select>
					</div>
				</div>
				<div class="field" style="margin-bottom:10px;">
					<label><?= h(__('Empresa dominante')) ?></label>
					<?= $this->Form->control('empresadominante', ['class' => 'form-control', 'label' => false, 'options' => $empresasOptSidebar, 'default' => $defaultEmpresaDominanteId]) ?>
				</div>
				<label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;margin-bottom:10px;text-transform:none;letter-spacing:0;">
					<?= $this->Form->checkbox('contrato', ['id' => 'contrato']) ?>
					<?= h(__('Possui contrato de serviço')) ?>
				</label>
				<div class="field" style="margin-bottom:0;">
					<label><?= h(__('Classificação ABC')) ?></label>
					<select disabled class="form-control"><option><?= h(__('B · cliente recorrente')) ?></option></select>
				</div>
			</div>
		</div>
	</div>

	<div style="display:flex;justify-content:flex-end;gap:8px;margin-top:18px;flex-wrap:wrap;">
		<?= $this->Html->link(__('Cancelar'), ['action' => 'index'], ['class' => 'btn btn-ghost', 'data-turbo' => 'false']) ?>
		<button type="button" class="btn btn-ghost" disabled title="<?= h(__('Em breve')) ?>">💾 <?= h(__('Salvar como rascunho')) ?></button>
		<?= $this->Form->button('✓ ' . __('Salvar cliente'), ['class' => 'btn btn-primary', 'escape' => false]) ?>
	</div>

<?= $this->Form->end() ?>
</div>

<script>
function SomenteNumero(e) {
	var tecla = (window.event) ? event.keyCode : e.which;
	if ((tecla > 44 && tecla < 58)) return true;
	if (tecla == 8 || tecla == 0) return true;
	return false;
}

jQuery(function($) {
	$("#cnpj").mask("99.999.999/9999-99");
	$("#cpffisica").mask("999.999.999-99");
	$("#cpfresponsavel").mask("999.999.999-99");
	$("#fone").mask("(999) 9999-9999");
	$("#fone2").mask("(999) 99999-9999");
	$("#cep").mask("99999-999");

	var TIPO_PF = <?= (int)C_ClientesTipoFisica ?>;
	var TIPO_PJ = <?= (int)C_ClientesTipoJuridica ?>;

	function toggleTipo(val) {
		val = parseInt(val, 10);
		if (val === TIPO_PF) {
			$('.pessoaJuridica').hide();
			$('.pessoaFisica').show();
			$('.cli-bloco-pj').hide();
			$('.cli-bloco-pf').show();
			$("#nome, #cpffisica").prop('disabled', false);
			$("#razaosocial, #nomefantasia, #cnpj, #inscricaoestadual, #inscricaomunicipal").prop('disabled', true);
		} else {
			$('.pessoaFisica').hide();
			$('.pessoaJuridica').show();
			$('.cli-bloco-pf').hide();
			$('.cli-bloco-pj').show();
			$("#razaosocial, #nomefantasia, #cnpj, #inscricaoestadual, #inscricaomunicipal").prop('disabled', false);
			$("#nome, #cpffisica").prop('disabled', true);
		}
	}

	$('#tipo').on('change', function() { toggleTipo($(this).val()); });
	var rawTipo = parseInt($('#tipo').val(), 10);
	toggleTipo(rawTipo === TIPO_PF ? TIPO_PF : TIPO_PJ);

	$('#razaosocial').on('change', function() { $(this).val($(this).val().toUpperCase()); });
	$('#nome').on('change', function() { $(this).val($(this).val().toUpperCase()); });

	$('#btn-buscar-cep').on('click', function (e) {
		e.preventDefault();
		var cep = ($('#cep').val() || '').replace(/\D/g, '');
		if (cep.length !== 8) { alert('<?= h(__('Informe um CEP válido.')) ?>'); return; }
		var url = "<?= rtrim(Router::url('/', true), '/'); ?>/api/util/cep/" + encodeURIComponent(cep);
		$.getJSON(url, function (res) {
			if (!res || !res.success || !res.data) { alert('<?= h(__('CEP não encontrado.')) ?>'); return; }
			var d = res.data;
			if (d.street) $('#endereco').val(String(d.street).toUpperCase());
			if (d.neighborhood) $('#bairro').val(String(d.neighborhood).toUpperCase());
			if (d.city && d.state) {
				var alvo = String(d.city).toUpperCase() + ' - ' + String(d.state).toUpperCase();
				$('#idcidade option').each(function () {
					if ($(this).text().toUpperCase().indexOf(alvo) >= 0) {
						$('#idcidade').val($(this).val());
						if (typeof $().selectpicker === 'function') { $('#idcidade').selectpicker('refresh'); }
						return false;
					}
				});
			}
		}).fail(function () { alert('<?= h(__('Erro ao consultar CEP.')) ?>'); });
	});

	$('#inscricaoestadual').on('change', function() {
		var url = "<?= Router::url(['controller' => 'Clientes', 'action' => 'cidadesestado']); ?>/" + $('#idcidade').val();
		$.get(url, function(data) { if (typeof checkInscEstadual === 'function') checkInscEstadual($('#inscricaoestadual').val(), data); });
	});

	$('#btn-buscar-cnpj').on('click', function(e) {
		e.preventDefault();
		var cnpj = ($('#cnpj').val() || '').replace(/\D/g, '');
		if (cnpj.length !== 14) { alert('<?= h(__('Informe um CNPJ válido com 14 dígitos.')) ?>'); return; }
		var $btn = $(this);
		$btn.prop('disabled', true).text('…');
		$('#cadastro-empresa-avisos').addClass('d-none');
		var baseUrl = "<?= rtrim(Router::url('/', true), '/'); ?>";
		var urlApi = baseUrl + '/api/cadastro/empresa/' + encodeURIComponent(cnpj) + '?consultar_ie=1&consultar_im=1&usar_cache=1';
		var urlFallback = "<?= Router::url(['controller' => 'Clientes', 'action' => 'consultacnpj']); ?>/" + cnpj;
		$.getJSON(urlApi, function(resposta) {
			$btn.prop('disabled', false).text('🔍');
			if (!resposta.sucesso) { alert(resposta.mensagem || '<?= h(__('Não foi possível consultar o CNPJ.')) ?>'); return; }
			var d = resposta.dados || {}, end = d.endereco || {}, contato = d.contato || {};
			if (d.razao_social) $('#razaosocial').val(d.razao_social.toUpperCase());
			if (d.nome_fantasia) $('#nomefantasia').val(d.nome_fantasia.toUpperCase());
			if (contato.email) $('#email').val(String(contato.email).trim().toLowerCase());
			var cep = (end.cep || '').toString().replace(/\D/g, '');
			if (cep.length >= 8) $('#cep').val(cep.substring(0,5) + '-' + cep.substring(5,8));
			if (end.bairro) $('#bairro').val(end.bairro.toUpperCase());
			if (end.logradouro) $('#endereco').val(end.logradouro.toUpperCase());
			if (end.numero) $('#nroendereco').val(end.numero);
			if (end.complemento) $('#complemento').val(end.complemento.toUpperCase());
			if (end.uf) $('#uf_contribuinte').val(String(end.uf).trim().toUpperCase());
			if (d.idcidade) { $('#idcidade').val(d.idcidade); if (typeof $().selectpicker === 'function') { $('#idcidade').selectpicker('refresh'); } }
			if (d.inscricao_estadual && d.inscricao_estadual.numero) $('#inscricaoestadual').val(String(d.inscricao_estadual.numero).replace(/\D/g,''));
			if (d.inscricao_municipal && d.inscricao_municipal.numero) $('#inscricaomunicipal').val(String(d.inscricao_municipal.numero).replace(/\D/g,''));
			if (contato.telefone) $('#fone').val(contato.telefone);
			if (Array.isArray(d.qsa) && d.qsa.length) {
				var s = d.qsa.find(function(x){ return String(x.qual||'').indexOf('Administrador')!==-1; }) || d.qsa[0];
				if (s && s.nome) $('#nomeresponsavel').val(s.nome.toUpperCase());
			}
		}).fail(function() {
			$.getJSON(urlFallback, function(data) {
				$btn.prop('disabled', false).text('🔍');
				if (data && data.status === 'ERROR') { alert(data.message || '<?= h(__('Erro na consulta.')) ?>'); return; }
				if (data.nome) $('#razaosocial').val(data.nome.toUpperCase());
				if (data.fantasia) $('#nomefantasia').val(data.fantasia.toUpperCase());
				if (data.email) $('#email').val(String(data.email).trim().toLowerCase());
			}).fail(function() { $btn.prop('disabled', false).text('🔍'); alert('<?= h(__('Erro ao consultar CNPJ.')) ?>'); });
		});
	});

	$('#btn-buscar-ie').on('click', function(e) {
		e.preventDefault();
		var cnpj = ($('#cnpj').val() || '').replace(/\D/g,'');
		if (cnpj.length !== 14) { alert('<?= h(__('Informe um CNPJ válido.')) ?>'); return; }
		var uf = ($('#uf_contribuinte').val() || '').trim().toUpperCase();
		if (!uf) { alert('<?= h(__('Use Buscar CNPJ antes.')) ?>'); return; }
		var url = "<?= Router::url(['controller' => 'Clientes', 'action' => 'consultaIe']); ?>/" + encodeURIComponent(cnpj) + "/" + encodeURIComponent(uf);
		var $btn = $(this);
		$btn.prop('disabled', true).text('…');
		$.getJSON(url, function(data) {
			$btn.prop('disabled', false).text('🔍');
			if (data && data.success && data.ie) $('#inscricaoestadual').val(data.ie);
			else alert(data && data.message ? data.message : '<?= h(__('IE não encontrada.')) ?>');
		}).fail(function() { $btn.prop('disabled', false).text('🔍'); alert('<?= h(__('Erro ao consultar IE.')) ?>'); });
	});
});
</script>
