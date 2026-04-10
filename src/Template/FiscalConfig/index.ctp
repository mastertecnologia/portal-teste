<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Módulo Fiscal', ['controller' => 'Fiscal', 'action' => 'index']);
$this->Breadcrumbs->add('Configuração fiscal');
echo $this->element('Fiscal/styles');
$ambOpts = [1 => 'Produção', 2 => 'Homologação'];
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1"><i class="fas fa-sliders-h"></i>Configuração fiscal da empresa</h1>
        <div class="fpm-actions">
            <?= $this->Html->link('Naturezas', ['action' => 'naturezas'], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm']) ?>
            <?= $this->Html->link('Alíquotas', ['action' => 'aliquotas'], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm']) ?>
            <?= $this->Html->link('CFOP', ['action' => 'cfop'], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm']) ?>
            <?= $this->Html->link('NCM', ['action' => 'ncm'], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm']) ?>
        </div>
    </div>

    <div class="px-3">
    <?= $this->Form->create($config) ?>
    <div class="fpm-card">
        <div class="fpm-card-title">Geral</div>
        <div class="fpm-row">
            <div class="fpm-field">
                <?= $this->Form->control('regime_tributario', ['type' => 'select', 'options' => $regimes, 'label' => 'Regime tributário', 'class' => 'form-control', 'id' => 'regime-tributario']) ?>
            </div>
            <div class="fpm-field" id="fpm-wrap-enquadramento-normal" style="display:none;">
                <?= $this->Form->control('regime_normal_enquadramento', [
                    'type' => 'select',
                    'options' => $regimeNormalEnquadramentoOptions,
                    'label' => 'Enquadramento (Regime normal)',
                    'class' => 'form-control',
                    'empty' => false,
                ]) ?>
                <small class="text-muted d-block mt-1">Define alíquotas PIS/COFINS de referência quando não há linha em Alíquotas. CST e bases continuam por operação.</small>
            </div>
            <div class="fpm-field">
                <?= $this->Form->control('ambiente', ['type' => 'select', 'options' => $ambOpts, 'label' => 'Ambiente SEFAZ', 'class' => 'form-control']) ?>
            </div>
            <div class="fpm-field">
                <?= $this->Form->control('uf', ['type' => 'select', 'options' => $ufsOptions, 'empty' => '—', 'label' => 'UF', 'class' => 'form-control']) ?>
            </div>
            <div class="fpm-field">
                <?= $this->Form->control('certificado_id', [
                    'type' => 'select', 'options' => $certificados, 'empty' => '— Nenhum —',
                    'label' => 'Certificado padrão', 'class' => 'form-control',
                ]) ?>
            </div>
        </div>
    </div>

    <div class="fpm-card">
        <div class="fpm-card-title">Numeração</div>
        <div class="fpm-row">
            <div class="fpm-field" style="max-width:140px;"><?= $this->Form->control('serie_nfe', ['label' => 'Série NFe', 'class' => 'form-control']) ?></div>
            <div class="fpm-field" style="max-width:160px;"><?= $this->Form->control('prox_numero_nfe', ['label' => 'Próx. NFe', 'class' => 'form-control']) ?></div>
            <div class="fpm-field" style="max-width:140px;"><?= $this->Form->control('serie_nfse', ['label' => 'Série NFSe', 'class' => 'form-control']) ?></div>
            <div class="fpm-field" style="max-width:160px;"><?= $this->Form->control('prox_numero_nfse', ['label' => 'Próx. NFSe', 'class' => 'form-control']) ?></div>
            <div class="fpm-field" style="max-width:140px;"><?= $this->Form->control('serie_nfce', ['label' => 'Série NFCe', 'class' => 'form-control']) ?></div>
            <div class="fpm-field" style="max-width:160px;"><?= $this->Form->control('prox_numero_nfce', ['label' => 'Próx. NFCe', 'class' => 'form-control']) ?></div>
        </div>
    </div>

    <div class="fpm-card">
        <div class="fpm-card-title">Cadastros auxiliares</div>
        <div class="fpm-row">
            <div class="fpm-field"><?= $this->Form->control('inscricao_estadual', ['label' => 'Inscrição estadual', 'class' => 'form-control']) ?></div>
            <div class="fpm-field"><?= $this->Form->control('inscricao_municipal', ['label' => 'Inscrição municipal', 'class' => 'form-control']) ?></div>
            <div class="fpm-field"><?= $this->Form->control('cnae_fiscal', ['label' => 'CNAE fiscal', 'class' => 'form-control']) ?></div>
            <div class="fpm-field"><?= $this->Form->control('codigo_municipio_ibge', ['label' => 'Município IBGE', 'class' => 'form-control']) ?></div>
        </div>
        <div class="fpm-row">
            <div class="fpm-field" style="max-width:200px;"><?= $this->Form->control('aliquota_simples', ['label' => 'Alíquota Simples (%)', 'class' => 'form-control']) ?></div>
        </div>
    </div>

    <div class="fpm-card">
        <div class="fpm-card-title">NFC-e (CSC)</div>
        <div class="fpm-row">
            <div class="fpm-field"><?= $this->Form->control('csc_id', ['label' => 'ID CSC', 'class' => 'form-control']) ?></div>
            <div class="fpm-field"><?= $this->Form->control('csc_token', ['type' => 'text', 'label' => 'Token CSC', 'class' => 'form-control']) ?></div>
        </div>
    </div>

    <div class="fpm-card">
        <div class="fpm-card-title">NFS-e</div>
        <div class="fpm-row">
            <div class="fpm-field"><?= $this->Form->control('nfse_provedor', ['label' => 'Provedor', 'class' => 'form-control', 'placeholder' => 'ginfes, betha…']) ?></div>
            <div class="fpm-field"><?= $this->Form->control('nfse_usuario', ['label' => 'Usuário', 'class' => 'form-control']) ?></div>
            <div class="fpm-field"><?= $this->Form->control('nfse_senha', ['type' => 'password', 'label' => 'Senha (deixe em branco para manter)', 'class' => 'form-control', 'value' => '']) ?></div>
        </div>
    </div>

    <div class="fpm-footer">
        <button type="submit" class="btn btn-pgm btn-pgm-salvar"><i class="fas fa-save"></i> Salvar</button>
    </div>
    <?= $this->Form->end() ?>
    </div>
</div>
<script>
(function () {
    var sel = document.getElementById('regime-tributario');
    var wrap = document.getElementById('fpm-wrap-enquadramento-normal');
    function togg() {
        if (!wrap || !sel) {
            return;
        }
        wrap.style.display = (sel.value === '3') ? '' : 'none';
    }
    if (sel) {
        sel.addEventListener('change', togg);
        togg();
    }
})();
</script>
