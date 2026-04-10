<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Módulo Fiscal', ['controller' => 'Fiscal', 'action' => 'index']);
$this->Breadcrumbs->add('DF-e recebidos');
echo $this->element('Fiscal/styles');
$st = $statusFiltro ?? 'pendente';
$chaveFiltro = isset($chaveFiltro) ? (string)$chaveFiltro : '';
$dfeQueryBase = ['status' => $st];
if ($chaveFiltro !== '') {
    $dfeQueryBase['chave'] = $chaveFiltro;
}
?>
<div class="fpm-wrap">
    <div class="fpm-topbar">
        <h1 class="fpm-h1"><i class="fas fa-inbox"></i>DF-e recebidos (fila)</h1>
        <div class="fpm-actions">
            <?= $this->Html->link('Painel fiscal', ['action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
        </div>
    </div>

    <p class="fpm-muted mx-3 mb-2">
        Documentos extraídos automaticamente após consultas bem-sucedidas à <strong>Distribuição DF-e</strong> (AN).
        Em documentos pendentes do tipo <strong>resNFe</strong> ou <strong>nfeProc</strong>, pode gerar um <strong>rascunho de nota de entrada</strong> quando o XML incluir o corpo da NF-e (<code>infNFe</code> com itens).
        Documentos <strong>resNFe</strong> que trazem só a chave: use <strong>Baixar completo (SEFAZ)</strong> para consultar por chave na AN (<code>consChNFe</code>) quando o XML integral estiver disponível (ex.: após manifestação).
    </p>

    <div class="fpm-card mx-3 mb-3">
        <div class="fpm-row">
            <span class="fpm-badge ok mr-2">Pendentes: <?= (int)($contagens['pendente'] ?? 0) ?></span>
            <span class="fpm-badge muted mr-2">Ignorados: <?= (int)($contagens['ignorado'] ?? 0) ?></span>
            <span class="fpm-badge muted">Vinculados: <?= (int)($contagens['vinculado'] ?? 0) ?></span>
        </div>
        <div class="mt-2">
            <?php
            $filtros = ['pendente' => 'Pendentes', 'ignorado' => 'Ignorados', 'vinculado' => 'Vinculados', 'todos' => 'Todos'];
            foreach ($filtros as $k => $lab) :
                $active = ($st === $k) ? 'btn-pgm btn-pgm-situacao' : 'btn-default';
                $qF = $chaveFiltro !== '' ? ['status' => $k, 'chave' => $chaveFiltro] : ['status' => $k];
            ?>
                <?= $this->Html->link($lab, ['action' => 'dfeRecebidos', '?' => $qF], ['class' => 'btn btn-sm ' . $active]) ?>
            <?php endforeach; ?>
        </div>
        <div class="mt-3 fpm-row" style="align-items:flex-end;flex-wrap:wrap;gap:8px;">
            <?= $this->Form->create(null, ['type' => 'get', 'url' => ['action' => 'dfeRecebidos'], 'class' => 'form-inline']) ?>
            <?= $this->Form->hidden('status', ['value' => $st]) ?>
            <div class="fpm-field" style="min-width:220px;">
                <label class="fpm-muted" style="display:block;font-size:12px;">Chave de acesso (parcial)</label>
                <?= $this->Form->control('chave', [
                    'label' => false,
                    'class' => 'form-control input-sm',
                    'placeholder' => '44 dígitos ou trecho',
                    'value' => $chaveFiltro,
                    'templates' => ['inputContainer' => '{{content}}'],
                ]) ?>
            </div>
            <?= $this->Form->button('Filtrar', ['class' => 'btn btn-sm btn-pgm']) ?>
            <?php if ($chaveFiltro !== '') : ?>
                <?= $this->Html->link('Limpar chave', ['action' => 'dfeRecebidos', '?' => ['status' => $st]], ['class' => 'btn btn-sm btn-default']) ?>
            <?php endif; ?>
            <?= $this->Form->end() ?>
        </div>
    </div>

    <div class="fpm-table-wrap">
        <?php if (empty($dfeRecebidos)): ?>
            <div class="fpm-empty">
                <?php if ($chaveFiltro !== ''): ?>
                    Nenhum documento corresponde à chave indicada neste filtro de status.
                    <?= $this->Html->link('Limpar filtro de chave', ['action' => 'dfeRecebidos', '?' => ['status' => $st]], ['class' => 'btn btn-sm btn-default ml-2']) ?>
                <?php else: ?>
                    Nenhum documento neste filtro.
                <?php endif; ?>
            </div>
        <?php else: ?>
        <table class="fpm-table">
            <thead>
                <tr>
                    <th>Recebido</th>
                    <th>Status</th>
                    <th>Tipo</th>
                    <th>Schema</th>
                    <th>NSU</th>
                    <th>Chave</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dfeRecebidos as $r): ?>
                <tr>
                    <td><?= $r->created ? h($r->created->format('d/m/Y H:i')) : '—' ?></td>
                    <td><span class="fpm-badge muted"><?= h($r->status) ?></span></td>
                    <td><?= h($r->tipo_documento ?: '—') ?></td>
                    <td><small><?= h($r->schema ?: '—') ?></small></td>
                    <td><?= h($r->nsu_doc ?: '—') ?></td>
                    <td style="word-break:break-all;font-size:11px;"><?= h($r->chave_acesso ?: '—') ?></td>
                    <td>
                        <?= $this->Html->link('XML', ['action' => 'dfeRecebidoXml', $r->id], ['class' => 'btn btn-xs btn-pgm btn-pgm-situacao']) ?>
                        <?php if ($r->status === 'pendente'): ?>
                            <?php
                            $tipoDoc = strtolower((string)$r->tipo_documento);
                            $podeImportarEntrada = in_array($tipoDoc, ['resnfe', 'nfeproc'], true)
                                && ($podeImportarDfeParaEntrada ?? true);
                            $resumoSoChave = \App\Utility\Fiscal\FiscalResNfeImportParser::chaveSeResumoResNfe((string)$r->xml_conteudo) !== null;
                            ?>
                            <?php if ($resumoSoChave): ?>
                                <?= $this->Form->postLink('Baixar completo (SEFAZ)', ['action' => 'dfeRecebidoBaixarCompleto', $r->id, '?' => $dfeQueryBase], [
                                    'class' => 'btn btn-xs btn-default',
                                    'confirm' => 'Consultar a Receita por chave (consChNFe) e substituir este registo pelo XML completo, se disponível?',
                                ]) ?>
                            <?php endif; ?>
                            <?php if ($podeImportarEntrada): ?>
                                <?= $this->Form->postLink('Rascunho entrada', ['action' => 'dfeRecebidoCriarEntrada', $r->id, '?' => $dfeQueryBase], [
                                    'class' => 'btn btn-xs btn-pgm',
                                    'confirm' => 'Criar rascunho de nota fiscal de entrada a partir deste XML?',
                                ]) ?>
                            <?php endif; ?>
                            <?= $this->Form->postLink('Ignorar', ['action' => 'dfeRecebidoIgnorar', $r->id, '?' => $dfeQueryBase], [
                                'class' => 'btn btn-xs btn-default',
                                'confirm' => 'Marcar este documento como ignorado?',
                            ]) ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
