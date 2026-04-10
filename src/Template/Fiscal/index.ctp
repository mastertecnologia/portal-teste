<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Módulo Fiscal', ['controller' => 'Fiscal', 'action' => 'index']);
echo $this->element('Fiscal/styles');

$mapStatus = function ($rows) {
    $out = [];
    foreach ($rows as $r) {
        $out[$r['status']] = ['total' => (int)$r['total'], 'valor' => (float)($r['valor'] ?? 0)];
    }
    return $out;
};
$byStatus = $mapStatus($totaisPorStatus);
$fpmDfeUltNsuLabel = isset($fpmDfeUltNsuLabel) ? (string)$fpmDfeUltNsuLabel : '';
$dfeFc = $dfeFilaContagens ?? ['pendente' => 0, 'ignorado' => 0, 'vinculado' => 0];
$dfeRecebidosLabel = '<i class="fas fa-inbox"></i> DF-e recebidos';
if ((int)($dfeFc['pendente'] ?? 0) > 0) {
    $dfeRecebidosLabel .= ' <span class="fpm-badge warn">' . (int)$dfeFc['pendente'] . ' pend.</span>';
}
if ((int)($dfeFc['vinculado'] ?? 0) > 0) {
    $dfeRecebidosLabel .= ' <span class="fpm-badge muted">' . (int)$dfeFc['vinculado'] . ' vinc.</span>';
}
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1"><i class="fas fa-file-invoice"></i>Módulo Fiscal</h1>
        <div class="fpm-actions">
            <button type="button" class="btn btn-pgm btn-pgm-situacao btn-sm" id="fpmStatusSefaz" title="Testar SEFAZ">
                <i class="fas fa-plug"></i> Status SEFAZ
            </button>
            <button type="button" class="btn btn-default btn-sm" id="fpmDistDfe" title="Consultar Distribuição DF-e (nacional)">
                <i class="fas fa-cloud-download-alt"></i> DF-e (AN)
            </button>
            <button type="button" class="btn btn-link btn-sm p-0 ml-1" id="fpmDistDfeReset" title="Zerar NSU guardado e consultar desde o início" style="vertical-align:middle;">
                Zerar NSU DF-e
            </button>
        </div>
    </div>

    <?php if (!empty($fiscalHomologacaoChecklist)) : ?>
    <div class="fpm-homolog-wrap">
        <?php foreach ($fiscalHomologacaoChecklist as $item) :
            $lvl = $item['level'] ?? 'info';
            ?>
        <div class="fpm-alert fpm-alert-<?= h($lvl) ?>">
            <?= h($item['message'] ?? '') ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="fpm-kpi-grid">
        <?php
        $statusOrder = ['rascunho', 'pendente', 'processando', 'autorizada', 'rejeitada', 'cancelada', 'denegada', 'inutilizada'];
        foreach ($statusOrder as $st) :
            $row = $byStatus[$st] ?? ['total' => 0, 'valor' => 0];
        ?>
        <div class="fpm-kpi">
            <div class="fpm-kpi-l"><?= h(\Cake\Core\Configure::read('Fiscal.status_nota.' . $st) ?: $st) ?></div>
            <div class="fpm-kpi-v"><?= (int)$row['total'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="fpm-nav-cards">
        <?= $this->Html->link('<i class="fas fa-arrow-up"></i>Notas de saída', ['controller' => 'FiscalNotas', 'action' => 'index'], ['class' => 'fpm-nav-card', 'escape' => false]) ?>
        <?= $this->Html->link('<i class="fas fa-arrow-down"></i>Notas de entrada', ['controller' => 'FiscalNotasEntrada', 'action' => 'index'], ['class' => 'fpm-nav-card', 'escape' => false]) ?>
        <?= $this->Html->link('<i class="fas fa-ban"></i>Inutilizar numeração', ['controller' => 'FiscalNotas', 'action' => 'inutilizarNumeracao'], ['class' => 'fpm-nav-card', 'escape' => false]) ?>
        <?= $this->Html->link('<i class="fas fa-barcode"></i>Séries (saída)', ['controller' => 'FiscalNotas', 'action' => 'controleSeries'], ['class' => 'fpm-nav-card', 'escape' => false]) ?>
        <?= $this->Html->link('<i class="fas fa-barcode"></i>Séries (entrada)', ['controller' => 'FiscalNotasEntrada', 'action' => 'controleSeries'], ['class' => 'fpm-nav-card', 'escape' => false]) ?>
        <?= $this->Html->link('<i class="fas fa-certificate"></i>Certificados digitais', ['controller' => 'FiscalCertificados', 'action' => 'index'], ['class' => 'fpm-nav-card', 'escape' => false]) ?>
        <?= $this->Html->link('<i class="fas fa-sliders-h"></i>Configuração fiscal', ['controller' => 'FiscalConfig', 'action' => 'index'], ['class' => 'fpm-nav-card', 'escape' => false]) ?>
        <?= $this->Html->link('<i class="fas fa-chart-bar"></i>Relatórios fiscais', ['controller' => 'FiscalRelatorios', 'action' => 'index'], ['class' => 'fpm-nav-card', 'escape' => false]) ?>
        <?= $this->Html->link($dfeRecebidosLabel, ['controller' => 'Fiscal', 'action' => 'dfeRecebidos'], ['class' => 'fpm-nav-card', 'escape' => false]) ?>
    </div>

    <div class="fpm-card" style="margin:16px 20px;">
        <div class="fpm-card-title">Certificado digital</div>
        <?php if (empty($certificado)): ?>
            <p class="fpm-muted mb-0">Nenhum certificado cadastrado. <?= $this->Html->link('Cadastrar', ['controller' => 'FiscalCertificados', 'action' => 'add']) ?>.</p>
        <?php else: ?>
            <p class="mb-1"><strong><?= h($certificado->nome) ?></strong> — <?= h($certificado->tipo) ?></p>
            <p class="fpm-muted mb-0">
                Validade: <?= $certificado->validade_fim ? h($certificado->validade_fim->format('d/m/Y H:i')) : '—' ?>
                <?php if ($certValido): ?>
                    <span class="fpm-badge ok"><?= (int)$certDiasRestantes ?> dias</span>
                <?php else: ?>
                    <span class="fpm-badge warn">Vencido ou inválido</span>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="fpm-table-wrap">
        <div class="fpm-card-title px-0 pt-0">Últimas notas</div>
        <?php if (empty($ultimasNotas)): ?>
            <div class="fpm-empty">Nenhuma nota registrada.</div>
        <?php else: ?>
        <table class="fpm-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tipo</th>
                    <th>Modelo</th>
                    <th>Cliente</th>
                    <th>Emissão</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ultimasNotas as $n): ?>
                <tr>
                    <td><?= (int)$n->id ?></td>
                    <td><?= h((int)$n->tipo_operacao === 0 ? 'Entrada' : 'Saída') ?></td>
                    <td><?= h($n->modelo) ?></td>
                    <td><?= h($n->cliente ? ($n->cliente->razaosocial ?: $n->cliente->nome) : '—') ?></td>
                    <td><?= $n->data_emissao ? h($n->data_emissao->format('d/m/Y')) : '—' ?></td>
                    <td>R$ <?= number_format((float)$n->valor_total, 2, ',', '.') ?></td>
                    <td><span class="fpm-badge muted"><?= h(\Cake\Core\Configure::read('Fiscal.status_nota.' . $n->status) ?: $n->status) ?></span></td>
                    <td><?= $this->Html->link('Abrir', [
                        'controller' => ((int)$n->tipo_operacao === 0) ? 'FiscalNotasEntrada' : 'FiscalNotas',
                        'action' => 'view',
                        $n->id,
                    ], ['class' => 'btn btn-xs btn-pgm btn-pgm-situacao']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<script>
(function() {
    var btn = document.getElementById('fpmStatusSefaz');
    if (!btn) return;
    btn.addEventListener('click', function() {
        btn.disabled = true;
        fetch('<?= $this->Url->build(['controller' => 'Fiscal', 'action' => 'statusSefaz']) ?>', { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                alert((d.mensagem || d.message || (d.success ? 'Serviço respondeu.' : 'Falha')));
            })
            .catch(function() { alert('Erro de rede.'); })
            .finally(function() { btn.disabled = false; });
    });
    var baseDfe = '<?= $this->Url->build(['controller' => 'Fiscal', 'action' => 'distribuicaoDfe']) ?>';
    var savedNsu = <?= json_encode($fpmDfeUltNsuLabel, JSON_UNESCAPED_UNICODE) ?>;

    function fpmShowDfeResult(d) {
        var msg = d.mensagem || d.message || '';
        if (d.doc_zip_count !== undefined) {
            msg += (msg ? '\n' : '') + 'Documentos (docZip): ' + d.doc_zip_count;
        }
        if (d.doc_zip_preview && d.doc_zip_preview.items && d.doc_zip_preview.items.length) {
            var lines = [];
            var maxL = 8;
            var arr = d.doc_zip_preview.items.slice(0, maxL);
            arr.forEach(function(it, i) {
                lines.push((i + 1) + ') ' + (it.schema || '') + ' ' + (it.tipo || '') + (it.chave ? ' chave:' + it.chave : ''));
            });
            if (d.doc_zip_preview.items.length > maxL) {
                lines.push('… +' + (d.doc_zip_preview.items.length - maxL) + ' documento(s)');
            }
            msg += (msg ? '\n\n' : '') + 'Pré-visualização:\n' + lines.join('\n');
        }
        if (d.ult_nsu_solicitado) { msg += (msg ? '\n' : '') + 'NSU enviado: ' + d.ult_nsu_solicitado; }
        if (d.ult_nsu) { msg += (msg ? '\n' : '') + 'ultNSU (retorno): ' + d.ult_nsu; }
        if (d.max_nsu) { msg += '\nmaxNSU: ' + d.max_nsu; }
        if (d.dfe_ult_nsu_guardado) { msg += '\nGuardado para próxima: ' + d.dfe_ult_nsu_guardado; }
        if (d.dfe_ingest_count !== undefined && d.dfe_ingest_count > 0) {
            msg += (msg ? '\n' : '') + 'Novos na fila DF-e: ' + d.dfe_ingest_count;
        }
        if (d.dfe_ingest_aviso) { msg += (msg ? '\n' : '') + d.dfe_ingest_aviso; }
        alert(msg || (d.success ? 'Consulta concluída.' : 'Falha'));
    }

    var btnD = document.getElementById('fpmDistDfe');
    if (btnD) {
        btnD.addEventListener('click', function() {
            var hint = savedNsu ? ('Último NSU guardado: ' + savedNsu) : 'Nenhum NSU guardado (primeira consulta usará 0).';
            var useSaved = confirm('Consultar Distribuição DF-e na Receita?\n\n' + hint + '\n\nOK = continuar a partir do último NSU guardado no portal.\nCancelar = informar um NSU manualmente.');
            var u;
            if (useSaved) {
                u = baseDfe;
            } else {
                var ult = prompt('Último NSU (15 dígitos)', savedNsu || '0');
                if (ult === null) return;
                u = baseDfe + '?ult_nsu=' + encodeURIComponent((ult || '0').replace(/\D/g, ''));
            }
            btnD.disabled = true;
            fetch(u, { credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    fpmShowDfeResult(d);
                    if (d.success && d.dfe_ult_nsu_guardado) { savedNsu = d.dfe_ult_nsu_guardado; }
                })
                .catch(function() { alert('Erro de rede.'); })
                .finally(function() { btnD.disabled = false; });
        });
    }
    var btnDr = document.getElementById('fpmDistDfeReset');
    if (btnDr) {
        btnDr.addEventListener('click', function() {
            if (!confirm('Zerar o NSU DF-e guardado para esta empresa e consultar desde o início (0)?')) return;
            btnDr.disabled = true;
            fetch(baseDfe + '?reset_nsu=1', { credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    fpmShowDfeResult(d);
                    if (d.success && d.dfe_ult_nsu_guardado) { savedNsu = d.dfe_ult_nsu_guardado; }
                    else if (d.success) { savedNsu = ''; }
                })
                .catch(function() { alert('Erro de rede.'); })
                .finally(function() { btnDr.disabled = false; });
        });
    }
})();
</script>
