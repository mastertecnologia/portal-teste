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

    <?= $this->element('Fiscal/regime_context') ?>

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
                <small class="fpm-muted d-block mt-1">Define alíquotas PIS/COFINS de referência quando não há linha em Alíquotas. CST e bases continuam por operação.</small>
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

    <p class="fpm-muted small mb-3" style="padding:10px 14px;border-radius:8px;background:rgba(88,166,255,.08);border:1px solid rgba(88,166,255,.22);">
        <i class="fas fa-file-download" style="color:#58a6ff;margin-right:6px;"></i>
        <strong>Exportação SPED</strong> — gerar o arquivo .txt em
        <?= $this->Html->link('Relatórios fiscais', ['controller' => 'FiscalRelatorios', 'action' => 'index'], ['style' => 'color:#58a6ff;font-weight:600;']) ?>
        (mês de referência → Baixar .txt). Os blocos abaixo alimentam registros opcionais do mesmo leiaute.
    </p>

    <div class="fpm-card">
        <div class="fpm-card-title">SPED Fiscal — contabilista (registro 0100)</div>
        <p class="fpm-muted small mb-2">Usado na exportação EFD-ICMS/IPI. Com todos os campos obrigatórios preenchidos, o arquivo inclui a linha 0100; caso contrário, a linha é omitida (exceto se <code>FISCAL_SPED_0100_MODO=sempre_stub</code> no ambiente).</p>
        <div class="fpm-row">
            <div class="fpm-field"><?= $this->Form->control('sped_contabilista_nome', ['label' => 'Nome do contabilista', 'class' => 'form-control']) ?></div>
            <div class="fpm-field" style="max-width:160px;"><?= $this->Form->control('sped_contabilista_cpf', ['label' => 'CPF (11 dígitos)', 'class' => 'form-control']) ?></div>
            <div class="fpm-field" style="max-width:200px;"><?= $this->Form->control('sped_contabilista_crc', ['label' => 'CRC', 'class' => 'form-control']) ?></div>
            <div class="fpm-field" style="max-width:180px;"><?= $this->Form->control('sped_contabilista_cnpj', ['label' => 'CNPJ escritório (opc.)', 'class' => 'form-control']) ?></div>
        </div>
        <div class="fpm-row">
            <div class="fpm-field" style="max-width:140px;"><?= $this->Form->control('sped_contabilista_cep', ['label' => 'CEP (opc.)', 'class' => 'form-control']) ?></div>
            <div class="fpm-field"><?= $this->Form->control('sped_contabilista_logradouro', ['label' => 'Logradouro (opc.)', 'class' => 'form-control']) ?></div>
            <div class="fpm-field" style="max-width:120px;"><?= $this->Form->control('sped_contabilista_numero', ['label' => 'Nº', 'class' => 'form-control']) ?></div>
            <div class="fpm-field"><?= $this->Form->control('sped_contabilista_complemento', ['label' => 'Complemento', 'class' => 'form-control']) ?></div>
        </div>
        <div class="fpm-row">
            <div class="fpm-field"><?= $this->Form->control('sped_contabilista_bairro', ['label' => 'Bairro (opc.)', 'class' => 'form-control']) ?></div>
            <div class="fpm-field" style="max-width:160px;"><?= $this->Form->control('sped_contabilista_fone', ['label' => 'Telefone (opc.)', 'class' => 'form-control']) ?></div>
            <div class="fpm-field" style="max-width:160px;"><?= $this->Form->control('sped_contabilista_fax', ['label' => 'Fax (opc.)', 'class' => 'form-control']) ?></div>
            <div class="fpm-field"><?= $this->Form->control('sped_contabilista_email', ['label' => 'E-mail', 'class' => 'form-control']) ?></div>
            <div class="fpm-field" style="max-width:140px;"><?= $this->Form->control('sped_contabilista_cod_municipio', ['label' => 'Município IBGE (7)', 'class' => 'form-control']) ?></div>
        </div>
    </div>

    <div class="fpm-card">
        <div class="fpm-card-title">SPED Fiscal — inventário (bloco H)</div>
        <p class="fpm-muted small mb-2">Se ativado, o arquivo inclui <strong>H001=0</strong>, <strong>H005</strong> e linhas <strong>H010</strong> (conforme JSON). Caso contrário permanece <strong>H001=1</strong> (sem movimento). Valide no PVA e alinhe <code>COD_ITEM</code> ao registro 0200 quando aplicável.</p>
        <div class="fpm-row">
            <div class="fpm-field" style="max-width:220px;">
                <?= $this->Form->control('sped_inventario_declarar', [
                    'type' => 'checkbox',
                    'label' => 'Declarar inventário neste leiaute',
                ]) ?>
            </div>
            <div class="fpm-field" style="max-width:200px;"><?= $this->Form->control('sped_inventario_dt_inv', ['type' => 'date', 'label' => 'Data do inventário', 'class' => 'form-control']) ?></div>
            <div class="fpm-field" style="max-width:320px;">
                <?= $this->Form->control('sped_inventario_mot_inv', [
                    'type' => 'select',
                    'options' => [
                        '' => '—',
                        '01' => '01 — Final do período',
                        '02' => '02 — Mudança de tributação',
                        '03' => '03',
                        '04' => '04',
                        '05' => '05',
                        '06' => '06 — ST / restituição (legislação)',
                    ],
                    'label' => 'MOT_INV (H005)',
                    'class' => 'form-control',
                ]) ?>
            </div>
        </div>
        <div class="fpm-row">
            <div class="fpm-field" style="flex:1;min-width:280px;">
                <?= $this->Form->control('sped_inventario_itens_json', [
                    'type' => 'textarea',
                    'label' => 'Itens (JSON)',
                    'class' => 'form-control font-monospace',
                    'rows' => 6,
                    'placeholder' => '[{"cod_item":"SKU1","unid":"UN","qtd":10,"vl_unit":5.5,"ind_prop":"0"}]',
                ]) ?>
            </div>
        </div>
    </div>

    <div class="fpm-card">
        <div class="fpm-card-title">SPED Fiscal — apuração ICMS (E111)</div>
        <p class="fpm-muted small mb-2">Opcional: discriminar ajustes da apuração (registro <strong>E111</strong>) e lançar valores nos campos correspondentes do <strong>E110</strong>. Use códigos válidos na tabela de ajustes da UF (3.º caractere = 0 para ICMS próprio). Valide no PVA.</p>
        <div class="fpm-row">
            <div class="fpm-field" style="flex:1;min-width:280px;">
                <?= $this->Form->control('sped_e111_ajustes_json', [
                    'type' => 'textarea',
                    'label' => 'Ajustes (JSON)',
                    'class' => 'form-control font-monospace',
                    'rows' => 5,
                    'placeholder' => '[{"cod_aj_apur":"SP000001","descr_compl_aj":"","vl_aj_apur":100.5,"e110_campo":"VL_TOT_AJ_DEBITOS"}]',
                ]) ?>
            </div>
        </div>
        <p class="fpm-muted small mb-0"><code>e110_campo</code>: VL_AJ_DEBITOS, VL_TOT_AJ_DEBITOS, VL_ESTORNOS_CRED, VL_AJ_CREDITOS, VL_TOT_AJ_CREDITOS, VL_ESTORNOS_DEB, VL_TOT_DED, DEB_ESP</p>
    </div>

    <div class="fpm-card">
        <div class="fpm-card-title">SPED Fiscal — observações C190 (0460)</div>
        <p class="fpm-muted small mb-2">Opcional: quando a UF exige <strong>COD_OBS</strong> no registro <strong>C190</strong>, cadastre a tabela <strong>0460</strong> e vínculos por CST + CFOP + alíquota ICMS (mesma chave usada no agrupamento do arquivo). Só use códigos previstos na legislação da UF.</p>
        <div class="fpm-row">
            <div class="fpm-field" style="flex:1;min-width:280px;">
                <?= $this->Form->control('sped_0460_c190_json', [
                    'type' => 'textarea',
                    'label' => '0460 + vínculos C190 (JSON)',
                    'class' => 'form-control font-monospace',
                    'rows' => 6,
                    'placeholder' => '{"observacoes":[{"cod_obs":"UF0001","txt":"Fundamento legal…"}],"c190":[{"cst":"000","cfop":"5102","aliq_icms":18,"cod_obs":"UF0001"}]}',
                ]) ?>
            </div>
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
