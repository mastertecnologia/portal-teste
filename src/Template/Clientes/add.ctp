<?php
    use Cake\Routing\Router;
    $this->append('css', $this->Html->css('/css/clientes-premium', ['timestamp' => true]));

    $this->Breadcrumbs->add('Clientes', ['controller' => 'Clientes', 'action' => 'index'], ['class' => 'breadcrumb-item']);
    $this->Breadcrumbs->add('Novo cliente', [], ['class' => 'breadcrumb-item active']);

    // Empresa dominante padrão (PGM)
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
?>

<div class="col-md-12 p-0">
<div class="cli-form-root">

<?= $this->Form->create($cliente, ['class' => 'cli-add-form']) ?>

    <!-- ── Topbar ─────────────────────────────────────────── -->
    <div class="cli-form-topbar">
        <div>
            <div class="cli-eyebrow">Módulo comercial</div>
            <div style="font-size:11px;color:#555e78;margin-bottom:3px;">
                <?= $this->Html->link('Clientes', ['action' => 'index'], ['escape' => false, 'style' => 'color:#7d8590;text-decoration:none;']) ?>
                › <span style="color:#5cdbc0;">Novo cliente</span>
            </div>
            <h1 class="cli-h1">Cadastrar cliente</h1>
        </div>
        <div class="cli-form-topbar-actions">
            <?= $this->Html->link('<i class="fa fa-arrow-left"></i> Voltar', ['action' => 'index'], ['class' => 'btn-cli-secondary', 'escape' => false]) ?>
        </div>
    </div>

    <!-- ── Form body ──────────────────────────────────────── -->
    <div class="cli-form-body">

        <!-- Avisos CNPJ -->
        <div id="cadastro-empresa-avisos" class="alert d-none" role="alert">
            <strong><i class="fas fa-info-circle"></i> Origem dos dados:</strong>
            <ul id="cadastro-empresa-avisos-lista" class="mb-0 mt-1"></ul>
        </div>

        <!-- ── Seção: Tipo ────────────────────────────────── -->
        <div class="cli-section">
            <div class="cli-section-head">
                <div class="cli-section-icon"><i class="fas fa-id-card"></i></div>
                <div class="cli-section-title">Tipo de cliente</div>
            </div>
            <div class="cli-section-body">
                <?= $this->Form->control('tipo', ['id' => 'tipo', 'options' => C_ClientesTipo, 'required' => true, 'label' => false, 'class' => 'form-control']) ?>
                <div class="cli-tipo-group" style="margin-top:12px;">
                    <button type="button" class="cli-tipo-btn active" id="btn-tipo-pj" onclick="cliSetTipo(2)">
                        <i class="fas fa-building"></i> Pessoa Jurídica
                    </button>
                    <button type="button" class="cli-tipo-btn" id="btn-tipo-pf" onclick="cliSetTipo(1)">
                        <i class="fas fa-user"></i> Pessoa Física
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Seção: Dados Pessoa Jurídica ──────────────── -->
        <div class="cli-section pessoaJuridica">
            <div class="cli-section-head">
                <div class="cli-section-icon"><i class="fas fa-building"></i></div>
                <div class="cli-section-title">Dados da empresa</div>
            </div>
            <div class="cli-section-body">
                <div class="cli-fg" style="grid-template-columns: 5fr 4fr 3fr; margin-bottom:14px;">
                    <div class="cli-fgroup">
                        <label>Razão Social <span class="req">*</span></label>
                        <?= $this->Form->control('razaosocial', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Nome empresarial completo']) ?>
                    </div>
                    <div class="cli-fgroup">
                        <label>Nome Fantasia</label>
                        <?= $this->Form->control('nomefantasia', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Nome comercial']) ?>
                    </div>
                    <div class="cli-fgroup">
                        <label style="display:flex;justify-content:space-between;align-items:center;">
                            <span>CNPJ <span class="req">*</span></span>
                            <button type="button" class="btn-cli-outline" id="btn-buscar-cnpj">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </label>
                        <?= $this->Form->control('cnpj', ['class' => 'form-control', 'id' => 'cnpj', 'label' => false, 'placeholder' => '00.000.000/0000-00']) ?>
                        <input type="hidden" id="uf_contribuinte" value="" />
                    </div>
                </div>
                <div class="cli-fg cli-fg-3">
                    <div class="cli-fgroup">
                        <label>Nome do Responsável</label>
                        <?= $this->Form->control('nomeresponsavel', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Sócio / administrador']) ?>
                    </div>
                    <div class="cli-fgroup">
                        <label>CPF do Responsável</label>
                        <?= $this->Form->control('cpf', ['id' => 'cpfresponsavel', 'class' => 'form-control', 'label' => false, 'placeholder' => '000.000.000-00']) ?>
                    </div>
                    <div class="cli-fgroup">
                        <label>RG do Responsável</label>
                        <?= $this->Form->control('rg', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Documento de identidade']) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Seção: Dados Pessoa Física ────────────────── -->
        <div class="cli-section pessoaFisica">
            <div class="cli-section-head">
                <div class="cli-section-icon"><i class="fas fa-user"></i></div>
                <div class="cli-section-title">Dados pessoais</div>
            </div>
            <div class="cli-section-body">
                <div class="cli-fg" style="grid-template-columns: 3fr 1fr;">
                    <div class="cli-fgroup">
                        <label>Nome completo <span class="req">*</span></label>
                        <?= $this->Form->control('nome', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Nome completo']) ?>
                    </div>
                    <div class="cli-fgroup">
                        <label>CPF</label>
                        <?= $this->Form->control('cpf', ['id' => 'cpffisica', 'class' => 'form-control', 'label' => false, 'placeholder' => '000.000.000-00']) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Seção: Endereço ────────────────────────────── -->
        <div class="cli-section">
            <div class="cli-section-head">
                <div class="cli-section-icon"><i class="fas fa-map-marker-alt"></i></div>
                <div class="cli-section-title">Endereço</div>
            </div>
            <div class="cli-section-body">
                <div class="cli-fg" style="grid-template-columns: 4fr 1fr 2fr 2fr 1.5fr; margin-bottom:14px;">
                    <div class="cli-fgroup">
                        <label>Logradouro <span class="req">*</span></label>
                        <?= $this->Form->control('endereco', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Rua, Av., Trav…', 'required' => true]) ?>
                    </div>
                    <div class="cli-fgroup">
                        <label>Número <span class="req">*</span></label>
                        <?= $this->Form->control('nroendereco', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Nro.', 'required' => true]) ?>
                    </div>
                    <div class="cli-fgroup">
                        <label>Bairro <span class="req">*</span></label>
                        <?= $this->Form->control('bairro', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Bairro', 'required' => true]) ?>
                    </div>
                    <div class="cli-fgroup">
                        <label>Complemento</label>
                        <?= $this->Form->control('complemento', ['class' => 'form-control', 'label' => false, 'placeholder' => 'Sala, Bloco…']) ?>
                    </div>
                    <div class="cli-fgroup">
                        <label>CEP <span class="req">*</span></label>
                        <?= $this->Form->control('cep', ['class' => 'form-control', 'id' => 'cep', 'label' => false, 'placeholder' => '00000-000', 'required' => true]) ?>
                    </div>
                </div>
                <div class="cli-fg cli-fg-2">
                    <div class="cli-fgroup">
                        <label>Cidade <span class="req">*</span></label>
                        <?= $this->Form->control('idcidade', ['data-live-search' => 'true', 'class' => 'selectpicker form-control', 'options' => $cidades, 'label' => false]) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Seção: Contato ─────────────────────────────── -->
        <div class="cli-section">
            <div class="cli-section-head">
                <div class="cli-section-icon"><i class="fas fa-phone-alt"></i></div>
                <div class="cli-section-title">Contato</div>
            </div>
            <div class="cli-section-body">
                <div class="cli-fg cli-fg-3">
                    <div class="cli-fgroup">
                        <label>Telefone <span class="req">*</span></label>
                        <?= $this->Form->control('fone', ['class' => 'form-control', 'id' => 'fone', 'label' => false, 'placeholder' => '(00) 0000-0000']) ?>
                    </div>
                    <div class="cli-fgroup">
                        <label>Celular</label>
                        <?= $this->Form->control('fone2', ['class' => 'form-control', 'id' => 'fone2', 'label' => false, 'placeholder' => '(00) 00000-0000']) ?>
                    </div>
                    <div class="cli-fgroup">
                        <label>E-mail de faturamento</label>
                        <?= $this->Form->email('email', ['id' => 'email', 'class' => 'form-control', 'label' => false, 'placeholder' => 'financeiro@empresa.com.br']) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Seção: Fiscal (somente PJ) ────────────────── -->
        <div class="cli-section pessoaJuridica">
            <div class="cli-section-head">
                <div class="cli-section-icon"><i class="fas fa-file-invoice"></i></div>
                <div class="cli-section-title">Registros fiscais</div>
            </div>
            <div class="cli-section-body">
                <div class="cli-fg cli-fg-2">
                    <div class="cli-fgroup">
                        <label>Inscrição Municipal <small style="text-transform:none;letter-spacing:0;">(somente números)</small></label>
                        <?= $this->Form->control('inscricaomunicipal', ['onkeypress' => 'return SomenteNumero(event)', 'class' => 'form-control', 'label' => false, 'placeholder' => 'Inscrição municipal']) ?>
                    </div>
                    <div class="cli-fgroup">
                        <label style="display:flex;justify-content:space-between;align-items:center;">
                            <span>Inscrição Estadual <small style="text-transform:none;letter-spacing:0;">(somente números)</small></span>
                            <button type="button" class="btn-cli-outline" id="btn-buscar-ie" title="Consultar IE na SEFAZ/SINTEGRA">
                                <i class="fas fa-search"></i> Buscar IE
                            </button>
                        </label>
                        <?= $this->Form->control('inscricaoestadual', ['id' => 'inscricaoestadual', 'onkeypress' => 'return SomenteNumero(event)', 'class' => 'form-control', 'label' => false, 'placeholder' => 'Inscrição estadual']) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Seção: Configurações ───────────────────────── -->
        <div class="cli-section">
            <div class="cli-section-head">
                <div class="cli-section-icon"><i class="fas fa-cog"></i></div>
                <div class="cli-section-title">Configurações</div>
            </div>
            <div class="cli-section-body">
                <div class="cli-fg" style="grid-template-columns: 1fr 1fr; align-items:center;">
                    <div class="cli-fgroup">
                        <label>Empresa dominante</label>
                        <?= $this->Form->control('empresadominante', ['class' => 'form-control', 'label' => false, 'options' => $empresasOptSidebar, 'default' => $defaultEmpresaDominanteId]) ?>
                    </div>
                    <div style="display:flex;align-items:center;padding-top:22px;">
                        <div class="cli-check-row">
                            <?= $this->Form->checkbox('contrato', ['class' => 'custom-control-input', 'id' => 'contrato']) ?>
                            <label for="contrato">Possui contrato de serviço</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /cli-form-body -->

    <!-- ── Footer com botão salvar ───────────────────────── -->
    <div class="cli-form-footer">
        <div class="cli-form-footer-left">
            <i class="fas fa-shield-alt" style="color:#1d9e75;margin-right:5px;"></i>
            Dados salvos com segurança no ERP
        </div>
        <div class="cli-form-footer-right">
            <?= $this->Html->link('<i class="fa fa-arrow-left"></i> Cancelar', ['action' => 'index'], ['class' => 'btn-cli-secondary', 'escape' => false]) ?>
            <?= $this->Form->button('<i class="fas fa-check"></i> Cadastrar cliente', ['class' => 'btn-cli-primary', 'escape' => false]) ?>
        </div>
    </div>

<?= $this->Form->end() ?>
</div><!-- /cli-form-root -->
</div>

<script>
    // ── Tipo toggle ──────────────────────────────────────────────
    function cliSetTipo(val) {
        $('#tipo').val(val).trigger('change');
        if (val == 2) {
            $('#btn-tipo-pj').addClass('active');
            $('#btn-tipo-pf').removeClass('active');
        } else {
            $('#btn-tipo-pf').addClass('active');
            $('#btn-tipo-pj').removeClass('active');
        }
    }

    jQuery(function($) {

        // Masks
        $("#cnpj").mask("99.999.999/9999-99");
        $("#cpffisica").mask("999.999.999-99");
        $("#cpfresponsavel").mask("999.999.999-99");
        $("#fone").mask("(999) 9999-9999");
        $("#fone2").mask("(999) 99999-9999");
        $("#cep").mask("99999-999");

        // Show/hide PJ/PF sections
        function toggleTipo(val) {
            if (val == 2) {
                $('.pessoaFisica').hide();
                $('.pessoaJuridica').fadeIn(200);
                $("#razaosocial, #nomefantasia, #cnpj").prop('disabled', false);
                $("#nome, #cpffisica").prop('disabled', true);
            } else {
                $('.pessoaJuridica').hide();
                $('.pessoaFisica').fadeIn(200);
                $("#nome, #cpffisica").prop('disabled', false);
                $("#razaosocial, #nomefantasia, #cnpj").prop('disabled', true);
            }
        }

        // Init
        $('.pessoaFisica').hide();
        $('.pessoaJuridica').hide();

        $("#tipo").on('change', function() {
            toggleTipo($(this).val());
        });

        // Default: PJ
        setTimeout(function() { cliSetTipo(2); }, 50);

        // Uppercase
        $('#razaosocial').on('change', function() { $(this).val($(this).val().toUpperCase()); });
        $('#nome').on('change', function() { $(this).val($(this).val().toUpperCase()); });

        // IE validation on change
        $('#inscricaoestadual').on('change', function() {
            var url = "<?= Router::url(array('controller'=>'Clientes','action'=>'cidadesestado'));?>" + '/' + $('#idcidade').val();
            $.ajax({ type:"get", url: url,
                success: function(data) { checkInscEstadual($('#inscricaoestadual').val(), data); },
                error: function() { alert('Inscrição Estadual Inválida'); }
            });
        });
    });

    function SomenteNumero(e) {
        var tecla = (window.event) ? event.keyCode : e.which;
        if ((tecla > 44 && tecla < 58)) return true;
        else { if (tecla == 8 || tecla == 0) return true; else return false; }
    }

    // ── Buscar CNPJ ─────────────────────────────────────────────
    $('#btn-buscar-cnpj').click(function(e) {
        e.preventDefault();
        var cnpj = ($('#cnpj').val() || '').replace(/\D/g, '');
        if (cnpj.length !== 14) { alert('Informe um CNPJ válido com 14 dígitos.'); return; }
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Buscando…');
        $('#cadastro-empresa-avisos').addClass('d-none');
        var baseUrl = "<?= rtrim(Router::url('/', true), '/'); ?>";
        var urlApi = baseUrl + '/api/cadastro/empresa/' + encodeURIComponent(cnpj) + '?consultar_ie=1&consultar_im=1&usar_cache=1';
        var urlFallback = "<?= Router::url(['controller' => 'Clientes', 'action' => 'consultacnpj']); ?>/" + cnpj;
        $.getJSON(urlApi, function(resposta) {
            $btn.prop('disabled', false).html('<i class="fas fa-search"></i> Buscar');
            if (!resposta.sucesso) { alert(resposta.mensagem || 'Não foi possível consultar o CNPJ.'); return; }
            var d = resposta.dados || {};
            var end = d.endereco || {};
            var contato = d.contato || {};
            if (d.razao_social) $('#razaosocial').val(d.razao_social.toUpperCase());
            if (d.nome_fantasia) $('#nomefantasia').val(d.nome_fantasia.toUpperCase());
            if (contato.email) { var em = String(contato.email).trim().toLowerCase(); $('#email').val(em); $('input[name="data[email]"]').val(em); }
            var cep = end.cep || '';
            if (typeof cep === 'string') cep = cep.replace(/\D/g, '');
            if (cep.length >= 8) $('#cep').val(cep.substring(0,5) + '-' + cep.substring(5,8));
            else if (end.cep) $('#cep').val(end.cep);
            if (end.bairro) $('#bairro').val(end.bairro.toUpperCase());
            if (end.logradouro) $('#endereco').val(end.logradouro.toUpperCase());
            if (end.numero) $('#nroendereco').val(end.numero);
            if (end.complemento) $('#complemento').val(end.complemento.toUpperCase());
            if (end.uf) $('#uf_contribuinte').val(String(end.uf).trim().toUpperCase());
            if (d.idcidade) { $('#idcidade').val(d.idcidade); if (typeof $().selectpicker === 'function') { $('#idcidade').selectpicker('refresh'); $('#idcidade').selectpicker('val', d.idcidade); } }
            if (d.inscricao_estadual && d.inscricao_estadual.numero) $('#inscricaoestadual').val(String(d.inscricao_estadual.numero).replace(/\D/g,''));
            if (d.inscricao_municipal && d.inscricao_municipal.numero) $('#inscricaomunicipal').val(String(d.inscricao_municipal.numero).replace(/\D/g,''));
            if (contato.telefone) $('#fone').val(contato.telefone);
            if (Array.isArray(d.qsa) && d.qsa.length > 0) { var s = d.qsa.find(function(x){ return String(x.qual||'').indexOf('Administrador')!==-1;})||d.qsa[0]; if(s&&s.nome)$('#nomeresponsavel').val(s.nome.toUpperCase()); }
            var lista = [];
            if (resposta.origem) { if (resposta.origem.dados_cadastrais) lista.push('Dados: ' + resposta.origem.dados_cadastrais); if (resposta.origem.inscricao_estadual) lista.push('IE: ' + resposta.origem.inscricao_estadual); if (resposta.origem.inscricao_municipal) lista.push('IM: ' + resposta.origem.inscricao_municipal); }
            if (Array.isArray(resposta.avisos) && resposta.avisos.length) resposta.avisos.forEach(function(a){ lista.push(a); });
            if (lista.length) { $('#cadastro-empresa-avisos-lista').empty(); lista.forEach(function(t){ $('#cadastro-empresa-avisos-lista').append('<li>'+t+'</li>'); }); $('#cadastro-empresa-avisos').removeClass('d-none'); }
        }).fail(function() {
            $.getJSON(urlFallback, function(data) {
                $btn.prop('disabled', false).html('<i class="fas fa-search"></i> Buscar');
                if (data && data.status === 'ERROR') { alert(data.message || 'Não foi possível consultar o CNPJ.'); return; }
                if (data.nome) $('#razaosocial').val(data.nome.toUpperCase());
                if (data.fantasia) $('#nomefantasia').val(data.fantasia.toUpperCase());
                if (data.email) { var em = String(data.email).trim().toLowerCase(); $('#email').val(em); }
                var cepNum = (data.cep || '').replace(/\D/g,'');
                if (cepNum.length >= 8) $('#cep').val(cepNum.substring(0,5)+'-'+cepNum.substring(5,8));
                if (data.bairro) $('#bairro').val(data.bairro.toUpperCase());
                if (data.logradouro) $('#endereco').val(data.logradouro.toUpperCase());
                if (data.numero) $('#nroendereco').val(data.numero);
                if (data.complemento) $('#complemento').val(data.complemento.toUpperCase());
                if (data.uf) $('#uf_contribuinte').val(String(data.uf).trim().toUpperCase());
                if (data.idcidade) { $('#idcidade').val(data.idcidade); if (typeof $().selectpicker === 'function') { $('#idcidade').selectpicker('refresh'); $('#idcidade').selectpicker('val', data.idcidade); } }
                if (data.telefone) $('#fone').val(data.telefone);
                if (Array.isArray(data.qsa) && data.qsa.length) { var s = data.qsa.find(function(x){ return String(x.qual||'').indexOf('Administrador')!==-1; })||data.qsa[0]; if(s&&s.nome)$('#nomeresponsavel').val(s.nome.toUpperCase()); }
                alert('Consulta consolidada indisponível. Dados da Receita preenchidos. Verifique manualmente.');
            }).fail(function() {
                $btn.prop('disabled', false).html('<i class="fas fa-search"></i> Buscar');
                alert('Erro ao acessar o serviço. Preencha manualmente.');
            });
        });
    });

    // ── Buscar IE ────────────────────────────────────────────────
    $('#btn-buscar-ie').click(function(e) {
        e.preventDefault();
        var cnpj = ($('#cnpj').val() || '').replace(/\D/g,'');
        if (cnpj.length !== 14) { alert('Informe um CNPJ válido. Use "Buscar CNPJ" primeiro.'); return; }
        var uf = ($('#uf_contribuinte').val() || '').trim().toUpperCase();
        if (!uf) { alert('A UF não foi definida. Use "Buscar CNPJ" primeiro.'); return; }
        var url = "<?= Router::url(['controller' => 'Clientes', 'action' => 'consultaIe']); ?>/" + encodeURIComponent(cnpj) + "/" + encodeURIComponent(uf);
        $('#btn-buscar-ie').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Buscando…');
        $.getJSON(url, function(data) {
            $('#btn-buscar-ie').prop('disabled', false).html('<i class="fas fa-search"></i> Buscar IE');
            if (data && data.success && data.ie) $('#inscricaoestadual').val(data.ie);
            else alert(data && data.message ? data.message : 'IE não encontrada.');
        }).fail(function() {
            $('#btn-buscar-ie').prop('disabled', false).html('<i class="fas fa-search"></i> Buscar IE');
            alert('Erro ao consultar IE (SEFAZ/SINTEGRA).');
        });
    });
</script>
