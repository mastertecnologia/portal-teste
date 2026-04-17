<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Cadastrar banco');

$catalogo = $catalogo ?? [];
?>
<style>
.fb-root { font-family:'DM Sans',sans-serif; }
.fb-topbar { display:flex; align-items:center; justify-content:space-between; padding:18px 24px 14px; border-bottom:1px solid rgba(255,255,255,.07); gap:12px; flex-wrap:wrap; }
.fb-h1 { font-size:20px; font-weight:600; color:#e6edf3; margin:0; }
.fb-h1-ico { color:#5cdbc0; margin-right:8px; }
.fb-layout { display:grid; grid-template-columns:minmax(0,1.7fr) minmax(320px,1fr); gap:18px; padding:16px 24px 24px; }
.fb-card { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; padding:18px 20px; }
.fb-card-title { font-size:14px; font-weight:600; color:#e6edf3; margin:0 0 14px; }
.fb-row { display:flex; gap:16px; flex-wrap:wrap; margin-bottom:12px; }
.fb-field { flex:1; min-width:200px; }
.fb-field--sm { max-width:180px; }
.fb-field--md { max-width:260px; }
.fb-field--full { min-width:100%; }
.fb-field label,
.fb-label { display:block; font-size:11px; color:#7d8590; text-transform:uppercase; letter-spacing:.05em; font-weight:600; margin-bottom:4px; }
.fb-help { display:block; font-size:11px; color:#7d8590; margin-top:4px; }
.fb-switch-row { display:flex; gap:18px; flex-wrap:wrap; margin-top:6px; }
.fb-check { display:flex; align-items:center; gap:8px; color:#c9d1d9; font-size:13px; }
.fb-check input { margin:0; }
.fb-actions { display:flex; align-items:center; gap:10px; margin-top:18px; flex-wrap:wrap; }
.fb-select-preview { border:1px dashed rgba(92,219,192,.25); border-radius:8px; padding:12px; background:rgba(92,219,192,.05); margin-bottom:16px; }
.fb-select-preview strong { color:#e6edf3; display:block; margin-bottom:4px; }
.fb-select-preview span { color:#9fb0bf; font-size:12px; display:block; line-height:1.45; }
.fb-tip-list { margin:0; padding-left:18px; color:#9fb0bf; font-size:12.5px; line-height:1.6; }
.fb-tip-list li + li { margin-top:4px; }
.fb-table-wrap { max-height:520px; overflow:auto; border:1px solid rgba(255,255,255,.06); border-radius:8px; }
.fb-table { width:100%; border-collapse:collapse; font-size:12px; }
.fb-table th { position:sticky; top:0; background:#0f141a; color:#7d8590; font-size:10.5px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:8px 10px; border-bottom:1px solid rgba(255,255,255,.07); text-align:left; }
.fb-table td { padding:8px 10px; border-bottom:1px solid rgba(255,255,255,.05); color:#c9d1d9; }
.fb-table tr:hover td { background:rgba(255,255,255,.02); }
.fb-table code { color:#5cdbc0; background:rgba(92,219,192,.08); padding:2px 5px; border-radius:4px; }
.fb-empty { text-align:center; color:#7d8590; padding:18px 8px; font-size:12.5px; }
.fb-badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:10px; font-weight:700; letter-spacing:.03em; }
.fb-badge--ok { background:rgba(63,185,80,.14); color:#3fb950; }
@media (max-width: 1100px) {
    .fb-layout { grid-template-columns:1fr; }
}
</style>

<div class="fb-root">
    <div class="fb-topbar">
        <h1 class="fb-h1"><i class="fas fa-university fb-h1-ico"></i>Cadastrar banco</h1>
        <div>
            <?= $this->Html->link('<i class="fas fa-list"></i> Lista de bancos', ['action' => 'cadastrar'], ['class' => 'btn btn-default btn-sm', 'escape' => false]) ?>
            <?= $this->Html->link('<i class="fas fa-arrow-left"></i> Voltar', ['action' => 'index'], ['class' => 'btn btn-default btn-sm', 'escape' => false]) ?>
        </div>
    </div>

    <div class="fb-layout">
        <div class="fb-card">
            <h2 class="fb-card-title">Dados do banco</h2>

            <?= $this->Form->create($banco) ?>

            <div class="fb-row">
                <div class="fb-field fb-field--md">
                    <label for="codigo-banco-select">Buscar banco pelo código bancário</label>
                    <select id="codigo-banco-select" class="form-control">
                        <option value="">— Selecione um banco do catálogo —</option>
                        <?php foreach ($catalogo as $item): ?>
                            <option
                                value="<?= h($item['codigo']) ?>"
                                data-codigo="<?= h($item['codigo']) ?>"
                                data-nome="<?= h($item['nome']) ?>"
                                data-cnab="<?= h($item['cnab']) ?>"
                            >
                                <?= h($item['codigo']) ?> - <?= h($item['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="fb-help">Ao selecionar um código bancário, os campos principais serão preenchidos automaticamente.</span>
                </div>

                <div class="fb-field">
                    <label>Código bancário</label>
                    <?= $this->Form->control('codigo_banco', [
                        'label' => false,
                        'class' => 'form-control',
                        'id' => 'codigo-banco',
                        'placeholder' => 'Ex: 341',
                        'maxlength' => 10
                    ]) ?>
                </div>

                <div class="fb-field">
                    <label>Núm. banco</label>
                    <?= $this->Form->control('numero_banco', [
                        'label' => false,
                        'class' => 'form-control',
                        'id' => 'numero-banco',
                        'placeholder' => 'Ex: 341',
                        'maxlength' => 10
                    ]) ?>
                </div>

                <div class="fb-field">
                    <label>CNAB</label>
                    <?= $this->Form->control('cnab', [
                        'label' => false,
                        'class' => 'form-control',
                        'id' => 'cnab-banco',
                        'placeholder' => 'Ex: 341',
                        'maxlength' => 10
                    ]) ?>
                </div>
            </div>

            <div class="fb-select-preview" id="catalogo-preview" style="display:none;">
                <strong>Banco selecionado</strong>
                <span id="catalogo-preview-text"></span>
            </div>

            <div class="fb-row">
                <div class="fb-field fb-field--full">
                    <label>Nome do banco</label>
                    <?= $this->Form->control('nome', [
                        'label' => false,
                        'class' => 'form-control',
                        'id' => 'nome-banco',
                        'placeholder' => 'Ex: ITAÚ'
                    ]) ?>
                </div>
            </div>

            <div class="fb-row">
                <div class="fb-field">
                    <label>Número agência</label>
                    <?= $this->Form->control('numero_agencia', [
                        'label' => false,
                        'class' => 'form-control',
                        'placeholder' => 'Ex: 1234'
                    ]) ?>
                </div>

                <div class="fb-field fb-field--sm">
                    <label>Dígito agência</label>
                    <?= $this->Form->control('digito_agencia', [
                        'label' => false,
                        'class' => 'form-control',
                        'placeholder' => 'Ex: 0'
                    ]) ?>
                </div>

                <div class="fb-field">
                    <label>Número conta</label>
                    <?= $this->Form->control('numero_conta', [
                        'label' => false,
                        'class' => 'form-control',
                        'placeholder' => 'Ex: 987654'
                    ]) ?>
                </div>

                <div class="fb-field fb-field--sm">
                    <label>Dígito conta</label>
                    <?= $this->Form->control('digito_conta', [
                        'label' => false,
                        'class' => 'form-control',
                        'placeholder' => 'Ex: 1'
                    ]) ?>
                </div>
            </div>

            <div class="fb-row">
                <div class="fb-field">
                    <label>Código banco interno</label>
                    <?= $this->Form->control('codigo_banco_interno', [
                        'label' => false,
                        'class' => 'form-control',
                        'placeholder' => 'Código interno do ERP (opcional)'
                    ]) ?>
                </div>

                <div class="fb-field">
                    <label>Verifica receber</label>
                    <?= $this->Form->control('verifica_receber', [
                        'label' => false,
                        'class' => 'form-control',
                        'placeholder' => 'Configuração opcional'
                    ]) ?>
                </div>

                <div class="fb-field fb-field--sm">
                    <label>Utiliza endosso</label>
                    <?= $this->Form->control('utiliza_endosso', [
                        'label' => false,
                        'class' => 'form-control',
                        'placeholder' => 'Ex: N'
                    ]) ?>
                </div>
            </div>

            <div class="fb-row">
                <div class="fb-field">
                    <label>Logotipo</label>
                    <?= $this->Form->control('logotipo', [
                        'label' => false,
                        'class' => 'form-control',
                        'placeholder' => 'URL ou caminho do logotipo (opcional)'
                    ]) ?>
                </div>
            </div>

            <div class="fb-row">
                <div class="fb-field fb-field--full">
                    <label>Observações</label>
                    <?= $this->Form->textarea('observacoes', [
                        'class' => 'form-control',
                        'rows' => 4,
                        'placeholder' => 'Observações internas sobre a conta bancária, convênio, cobrança, remessa ou retorno.'
                    ]) ?>
                </div>
            </div>

            <div class="fb-switch-row">
                <label class="fb-check">
                    <?= $this->Form->checkbox('ativo', ['hiddenField' => true, 'value' => 1, 'checked' => true]) ?>
                    <span>Banco ativo</span>
                </label>
            </div>

            <div class="fb-actions">
                <?= $this->Form->button('<i class="fas fa-save"></i> Salvar banco', ['class' => 'btn btn-pgm btn-pgm-salvar', 'escape' => false]) ?>
                <?= $this->Html->link('Cancelar', ['action' => 'cadastrar'], ['class' => 'btn btn-default']) ?>
            </div>

            <?= $this->Form->end() ?>
        </div>

        <div class="fb-card">
            <h2 class="fb-card-title">Catálogo bancário de apoio</h2>

            <ul class="fb-tip-list" style="margin-bottom:16px;">
                <li>Use o catálogo ao lado para localizar rapidamente o banco pelo código bancário.</li>
                <li>O cadastro fica vinculado ao seu financeiro e poderá ser usado em remessas, retornos e relatórios.</li>
                <li>Você pode manter mais de uma conta por banco, desde que estejam cadastradas com dados distintos.</li>
            </ul>

            <div class="fb-table-wrap">
                <?php if (empty($catalogo)): ?>
                    <div class="fb-empty">Nenhum banco disponível no catálogo.</div>
                <?php else: ?>
                    <table class="fb-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Banco</th>
                                <th>CNAB</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($catalogo as $item): ?>
                                <tr>
                                    <td><code><?= h($item['codigo']) ?></code></td>
                                    <td><?= h($item['nome']) ?></td>
                                    <td><span class="fb-badge fb-badge--ok"><?= h($item['cnab']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var select = document.getElementById('codigo-banco-select');
    var codigo = document.getElementById('codigo-banco');
    var numero = document.getElementById('numero-banco');
    var cnab = document.getElementById('cnab-banco');
    var nome = document.getElementById('nome-banco');
    var preview = document.getElementById('catalogo-preview');
    var previewText = document.getElementById('catalogo-preview-text');

    if (!select) {
        return;
    }

    select.addEventListener('change', function() {
        var option = this.options[this.selectedIndex];
        if (!option || !option.value) {
            if (preview) {
                preview.style.display = 'none';
            }
            return;
        }

        var bancoCodigo = option.getAttribute('data-codigo') || '';
        var bancoNome = option.getAttribute('data-nome') || '';
        var bancoCnab = option.getAttribute('data-cnab') || '';

        if (codigo) codigo.value = bancoCodigo;
        if (numero) numero.value = bancoCodigo;
        if (cnab) cnab.value = bancoCnab;
        if (nome && !nome.value) nome.value = bancoNome;

        if (preview && previewText) {
            previewText.textContent = bancoCodigo + ' - ' + bancoNome + ' | CNAB: ' + bancoCnab;
            preview.style.display = 'block';
        }
    });
})();
</script>
