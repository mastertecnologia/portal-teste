<?php
    use Cake\Routing\Router;
    $this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']));

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
<?= $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']) ?>

<div class="pg cli-form-root cli-layout-unificado" id="pg-cliente-novo">

<?= $this->Form->create($cliente, ['class' => 'cli-add-form', 'id' => 'cli-add-form', 'data-turbo' => 'false']) ?>

    <?= $this->element('Cli/cadastro_page_header', [
        'pageTitle' => __('Novo cadastro de cliente'),
        'showHeaderSave' => true,
        'cancelUrl' => ['action' => 'index'],
    ]) ?>

    <div class="cli-form-body cli-form-body--cadastro-lead cli-wizard-root" data-cli-wizard-root>
    <?= $this->element('Cli/cadastro_wizard_stepper', ['wizardStep' => 1]) ?>

        <div id="cadastro-empresa-avisos" class="alert d-none" role="alert" style="margin-bottom:14px;">
            <strong><?= h(__('Origem dos dados:')) ?></strong>
            <ul id="cadastro-empresa-avisos-lista" class="mb-0 mt-1"></ul>
        </div>

        <div class="cli-wizard-pane cli-wizard-pane--active" data-cli-wizard-step="1" data-cli-wizard-title="<?= h(__('Identificação')) ?>">
        <div class="g2" style="gap:14px;align-items:stretch;margin-bottom:14px;">
            <div class="card" style="margin-bottom:0;">
                <div class="sec-title">💳 <?= h(__('Tipo de cliente')) ?></div>
                <?= $this->Form->control('tipo', ['id' => 'tipo', 'options' => C_ClientesTipo, 'required' => false, 'label' => false, 'class' => 'form-control d-none', 'templates' => ['inputContainer' => '{{content}}']]) ?>
                <div class="cli-tipo-group">
                    <button type="button" class="cli-tipo-btn active" id="btn-tipo-pj" onclick="cliSetTipo(2)">
                        <i class="fas fa-building" aria-hidden="true"></i> <?= h(__('Pessoa Jurídica')) ?>
                    </button>
                    <button type="button" class="cli-tipo-btn" id="btn-tipo-pf" onclick="cliSetTipo(1)">
                        <i class="fas fa-user" aria-hidden="true"></i> <?= h(__('Pessoa Física')) ?>
                    </button>
                </div>
            </div>
            <div class="card" style="margin-bottom:0;">
                <div class="sec-title">📊 <?= h(__('Código do cliente')) ?></div>
                <div style="border:1px dashed var(--border);border-radius:var(--radius);padding:14px 16px;font-size:12px;color:var(--text-muted);line-height:1.5;background:var(--bg-surface);min-height:72px;display:flex;align-items:center;">
                    <?= h(__('O código portal (P########) é gerado automaticamente ao salvar.')) ?>
                </div>
            </div>
        </div>

        <?= $this->element('Cli/papel_cadastro_checkboxes', [
            'cliente' => $cliente,
            'cliPapelColumns' => !empty($cliPapelColumns),
            'showFornecedorExtras' => !empty($cliPrefFornecedor),
        ]) ?>

        <div class="card pessoaJuridica" style="margin-bottom:14px;">
            <div class="sec-title">🏢 <?= h(__('Dados da empresa')) ?></div>
            <div class="g2" style="margin-bottom:10px;">
                <div class="field">
                    <label><?= h(__('Razão social')) ?> *</label>
                    <?= $this->Form->control('razaosocial', ['class' => 'form-control', 'label' => false, 'placeholder' => __('Nome empresarial completo')]) ?>
                </div>
                <div class="field">
                    <label><?= h(__('Nome fantasia')) ?></label>
                    <?= $this->Form->control('nomefantasia', ['class' => 'form-control', 'label' => false, 'placeholder' => __('Nome comercial')]) ?>
                </div>
            </div>
            <div class="field" style="margin-bottom:10px;">
                <label><?= h(__('CNPJ')) ?> *</label>
                <div style="display:flex;gap:6px;">
                    <?= $this->Form->control('cnpj', ['class' => 'form-control', 'id' => 'cnpj', 'label' => false, 'placeholder' => '00.000.000/0000-00', 'style' => 'flex:1;']) ?>
                    <button type="button" class="btn btn-blue btn-sm" id="btn-buscar-cnpj" title="<?= h(__('Consultar Receita Federal')) ?>">🔍</button>
                </div>
                <input type="hidden" id="uf_contribuinte" value="" />
                <div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= h(__('Clique em Buscar para preencher automaticamente via Receita Federal')) ?></div>
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

        <div class="card pessoaFisica" style="margin-bottom:14px;">
            <div class="sec-title">👤 <?= h(__('Dados pessoais')) ?></div>
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

        <div class="cli-wizard-pane" data-cli-wizard-step="2" data-cli-wizard-title="<?= h(__('Endereço & Contato')) ?>" hidden>
        <div class="g2" style="gap:14px;align-items:start;">
        <div style="display:flex;flex-direction:column;gap:14px;">
        <div class="card" style="margin-bottom:0;">
            <div class="sec-title">📍 <?= h(__('Endereço principal')) ?></div>
            <div class="g2" style="margin-bottom:10px;">
                <div class="field">
                    <label><?= h(__('CEP')) ?> *</label>
                    <div style="display:flex;gap:6px;">
                        <?= $this->Form->control('cep', ['class' => 'form-control', 'id' => 'cep', 'label' => false, 'placeholder' => '00000-000', 'required' => true, 'style' => 'flex:1;']) ?>
                        <button type="button" class="btn btn-blue btn-sm" id="btn-buscar-cep" title="<?= h(__('Buscar CEP')) ?>">🔍</button>
                    </div>
                </div>
                <div class="field">
                    <label><?= h(__('Cidade')) ?> *</label>
                    <?= $this->Form->control('idcidade', ['data-live-search' => 'true', 'class' => 'selectpicker form-control', 'options' => $cidades, 'label' => false]) ?>
                </div>
            </div>
            <div class="field" style="margin-bottom:10px;">
                <label><?= h(__('Logradouro')) ?> *</label>
                <?= $this->Form->control('endereco', ['class' => 'form-control', 'label' => false, 'placeholder' => __('Rua, Av., Trav…'), 'required' => true]) ?>
            </div>
            <div style="display:grid;grid-template-columns:120px 1fr;gap:10px;margin-bottom:10px;">
                <div class="field"><label><?= h(__('Número')) ?> *</label><?= $this->Form->control('nroendereco', ['class' => 'form-control', 'label' => false, 'placeholder' => __('Nro.'), 'required' => true]) ?></div>
                <div class="field"><label><?= h(__('Complemento')) ?></label><?= $this->Form->control('complemento', ['class' => 'form-control', 'label' => false, 'placeholder' => __('Sala, Bloco…')]) ?></div>
            </div>
            <div class="field" style="margin-bottom:0;">
                <label><?= h(__('Bairro')) ?> *</label>
                <?= $this->Form->control('bairro', ['class' => 'form-control', 'label' => false, 'placeholder' => __('Bairro'), 'required' => true]) ?>
            </div>
        </div>

        <div class="card" style="margin-bottom:0;">
            <div class="sec-title">📞 <?= h(__('Contatos')) ?></div>
            <div class="g2" style="margin-bottom:10px;">
                <div class="field">
                    <label><?= h(__('Telefone principal')) ?> *</label>
                    <?= $this->Form->control('fone', ['class' => 'form-control', 'id' => 'fone', 'label' => false, 'placeholder' => '(00) 0000-0000']) ?>
                </div>
                <div class="field">
                    <label><?= h(__('WhatsApp')) ?></label>
                    <?= $this->Form->control('fone2', ['class' => 'form-control', 'id' => 'fone2', 'label' => false, 'placeholder' => '(00) 00000-0000']) ?>
                </div>
            </div>
            <div class="field" style="margin-bottom:10px;">
                <label><?= h(__('E-mail principal')) ?></label>
                <?= $this->Form->email('email', ['id' => 'email', 'class' => 'form-control', 'label' => false, 'placeholder' => 'financeiro@empresa.com.br']) ?>
            </div>
            <div class="field" style="margin-bottom:0;">
                <label><?= h(__('Site / domínio')) ?></label>
                <?= $this->Form->control('site', ['class' => 'form-control', 'id' => 'site', 'label' => false, 'placeholder' => __('empresa.com.br'), 'autocomplete' => 'url']) ?>
            </div>
            <p style="font-size:11px;color:var(--text-muted);margin:12px 0 0;"><?= h(__('Pessoas de contato e acessos são cadastrados após salvar, na ficha do cliente.')) ?></p>
        </div>
        </div>
        <div class="card" style="margin-bottom:0;">
            <div class="sec-title">📋 <?= h(__('Próximos passos')) ?></div>
            <p style="font-size:12px;color:var(--text-muted);margin:0;line-height:1.6;"><?= h(__('Inscrições fiscais, limite de crédito e configuração comercial nos passos 3 e 4 do assistente.')) ?></p>
        </div>
        </div>
        </div>

        <div class="cli-wizard-pane" data-cli-wizard-step="3" data-cli-wizard-title="<?= h(__('Fiscal & Financeiro')) ?>" hidden>
        <div class="g2" style="gap:14px;align-items:start;">
        <div class="card pessoaJuridica" style="margin-bottom:0;">
            <div class="sec-title">📄 <?= h(__('Registros fiscais')) ?></div>
            <div class="g2" style="margin-bottom:0;">
                <div class="field">
                    <label><?= h(__('Inscrição municipal')) ?></label>
                    <?= $this->Form->control('inscricaomunicipal', ['onkeypress' => 'return SomenteNumero(event)', 'class' => 'form-control', 'label' => false, 'placeholder' => __('Somente números')]) ?>
                </div>
                <div class="field">
                    <label><?= h(__('Inscrição estadual')) ?></label>
                    <div style="display:flex;gap:6px;">
                        <?= $this->Form->control('inscricaoestadual', ['id' => 'inscricaoestadual', 'onkeypress' => 'return SomenteNumero(event)', 'class' => 'form-control', 'label' => false, 'placeholder' => __('Somente números'), 'style' => 'flex:1;']) ?>
                        <button type="button" class="btn btn-blue btn-sm" id="btn-buscar-ie" title="<?= h(__('Consultar IE na SEFAZ/SINTEGRA')) ?>">🔍</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card" style="margin-bottom:0;">
            <div class="sec-title">💰 <?= h(__('Configuração financeira')) ?></div>
            <div class="g2" style="margin-bottom:10px;">
                <div class="field">
                    <label><?= h(__('Limite de crédito (R$)')) ?></label>
                    <?= $this->Form->control('limite_credito', ['class' => 'form-control', 'label' => false, 'placeholder' => '0,00']) ?>
                </div>
                <div class="field">
                    <label><?= h(__('Score interno (0–10)')) ?></label>
                    <?= $this->Form->control('score_interno', ['class' => 'form-control', 'label' => false, 'placeholder' => '9,2']) ?>
                </div>
            </div>
            <div class="field" style="margin-bottom:0;">
                <label><?= h(__('Observações financeiras')) ?></label>
                <?= $this->Form->control('observacoes_financeiras', ['type' => 'textarea', 'rows' => 2, 'class' => 'form-control', 'label' => false]) ?>
            </div>
        </div>
        </div>
        </div>

        <div class="cli-wizard-pane" data-cli-wizard-step="4" data-cli-wizard-title="<?= h(__('Comercial & CRM')) ?>" hidden>
        <div class="g2" style="gap:14px;align-items:start;">
        <div class="card" style="margin-bottom:0;">
            <div class="sec-title">📈 <?= h(__('Comercial & CRM')) ?></div>
            <div class="field" style="margin-bottom:10px;">
                <label><?= h(__('Empresa dominante')) ?></label>
                <?= $this->Form->control('empresadominante', ['class' => 'form-control', 'label' => false, 'options' => $empresasOptSidebar, 'default' => $defaultEmpresaDominanteId]) ?>
            </div>
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer;text-transform:none;letter-spacing:0;">
                <?= $this->Form->checkbox('contrato', ['id' => 'contrato']) ?>
                <?= h(__('Possui contrato de serviço')) ?>
            </label>
        </div>
        <div class="card" style="margin-bottom:0;">
            <div class="sec-title">ℹ️ <?= h(__('Evolução do cadastro')) ?></div>
            <p style="font-size:12px;color:var(--text-muted);margin:0;line-height:1.6;"><?= h(__('Segmentação ABC, vendedor e tags avançados podem ser evoluídos em fases futuras; os dados operacionais acima já sincronizam com o ERP.')) ?></p>
        </div>
        </div>
        </div>

    <?= $this->element('Cli/cadastro_wizard_nav', ['wizardShowSave' => true]) ?>
    </div><!-- /cli-form-body -->

    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:18px;flex-wrap:wrap;">
        <?= $this->Html->link(__('Cancelar'), ['action' => 'index'], ['class' => 'btn btn-ghost', 'data-turbo' => 'false']) ?>
        <?= $this->Form->button('✓ ' . __('Salvar cliente'), ['class' => 'btn btn-primary cli-wizard-save-footer', 'escape' => false]) ?>
    </div>

<?= $this->Form->end() ?>
</div><!-- /pg-cliente-novo -->

<?= $this->Html->script('/pgm-assets/js/modules/clientes/cliente-cadastro-wizard') ?>
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

        $('#btn-buscar-cep').on('click', function (e) {
            e.preventDefault();
            var cep = ($('#cep').val() || '').replace(/\D/g, '');
            if (cep.length !== 8) {
                alert('<?= h(__('Informe um CEP válido.')) ?>');
                return;
            }
            var url = "<?= rtrim(Router::url('/', true), '/'); ?>/api/util/cep/" + encodeURIComponent(cep);
            $.getJSON(url, function (res) {
                if (!res || !res.success || !res.data) {
                    alert('<?= h(__('CEP não encontrado.')) ?>');
                    return;
                }
                var d = res.data;
                if (d.street) $('#endereco').val(String(d.street).toUpperCase());
                if (d.neighborhood) $('#bairro').val(String(d.neighborhood).toUpperCase());
                if (d.city && d.state) {
                    var alvo = String(d.city).toUpperCase() + ' - ' + String(d.state).toUpperCase();
                    $('#idcidade option').each(function () {
                        if ($(this).text().toUpperCase().indexOf(alvo) >= 0) {
                            $('#idcidade').val($(this).val());
                            if (typeof $().selectpicker === 'function') {
                                $('#idcidade').selectpicker('refresh');
                            }
                            return false;
                        }
                    });
                }
            }).fail(function () {
                alert('<?= h(__('Erro ao consultar CEP.')) ?>');
            });
        });

        $('.cli-wizard-save-header').on('click', function () {
            $('.cli-wizard-save-footer').trigger('click');
        });

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

        $("#tipo").on('change', function() {
            toggleTipo($(this).val());
        });

        var TIPO_PF = <?= (int)C_ClientesTipoFisica ?>;
        var TIPO_PJ = <?= (int)C_ClientesTipoJuridica ?>;
        var rawTipo = $('#tipo').val();
        var tipoNum = parseInt(rawTipo, 10);
        if (tipoNum !== TIPO_PF) {
            tipoNum = TIPO_PJ;
        }
        cliSetTipo(tipoNum);

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
