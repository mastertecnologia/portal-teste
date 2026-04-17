<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Cadastrar bancos');

$bancos = $bancos ?? [];
$catalogo = $catalogo ?? [];
$codigo = $codigo ?? '';
$nome = $nome ?? '';
$ativo = isset($ativo) ? (string)$ativo : '';

$fmtConta = function ($banco) {
    $ag = trim((string)($banco->numero_agencia ?? ''));
    $dag = trim((string)($banco->digito_agencia ?? ''));
    $cc = trim((string)($banco->numero_conta ?? ''));
    $dcc = trim((string)($banco->digito_conta ?? ''));

    $agFmt = $ag !== '' ? $ag . ($dag !== '' ? '-' . $dag : '') : '—';
    $ccFmt = $cc !== '' ? $cc . ($dcc !== '' ? '-' . $dcc : '') : '—';

    return 'Ag. ' . $agFmt . ' / Cc. ' . $ccFmt;
};
?>
<style>
.fb-root { font-family:'DM Sans',sans-serif; }
.fb-topbar {
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:18px 24px 14px;
    border-bottom:1px solid rgba(255,255,255,.07);
    gap:12px;
    flex-wrap:wrap;
}
.fb-title {
    font-size:20px;
    font-weight:600;
    color:#e6edf3;
}
.fb-title i {
    color:#5cdbc0;
    margin-right:8px;
}
.fb-actions { display:flex; gap:8px; flex-wrap:wrap; }

.fb-filters {
    display:flex;
    gap:10px;
    padding:14px 24px;
    border-bottom:1px solid rgba(255,255,255,.07);
    flex-wrap:wrap;
    align-items:end;
}
.fb-filter-group { min-width:180px; flex:1; }
.fb-filter-group label {
    display:block;
    font-size:11px;
    color:#7d8590;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:600;
    margin-bottom:4px;
}
.fb-filter-actions {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    align-items:center;
}

.fb-grid {
    display:grid;
    grid-template-columns:2fr 1fr;
    gap:16px;
    padding:16px 24px 24px;
}
.fb-card {
    background:#161b22;
    border:1px solid rgba(255,255,255,.07);
    border-radius:10px;
    padding:18px 20px;
}
.fb-card-title {
    font-size:15px;
    font-weight:600;
    color:#e6edf3;
    margin-bottom:12px;
}
.fb-card-sub {
    font-size:12px;
    color:#7d8590;
    margin-bottom:14px;
}
.fb-table-wrap { overflow:auto; }
.fb-table {
    width:100%;
    border-collapse:collapse;
    font-size:12.5px;
}
.fb-table th {
    color:#7d8590;
    font-size:10.5px;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:600;
    padding:7px 8px;
    border-bottom:1px solid rgba(255,255,255,.07);
    text-align:left;
    white-space:nowrap;
}
.fb-table td {
    padding:9px 8px;
    border-bottom:1px solid rgba(255,255,255,.04);
    color:#c9d1d9;
    vertical-align:top;
}
.fb-table tr:hover td {
    background:rgba(255,255,255,.02);
}
.fb-status {
    display:inline-block;
    padding:2px 8px;
    border-radius:999px;
    font-size:11px;
    font-weight:700;
}
.fb-status--on {
    color:#3fb950;
    background:rgba(63,185,80,.12);
}
.fb-status--off {
    color:#f85149;
    background:rgba(248,81,73,.12);
}
.fb-status--warn {
    color:#ffd166;
    background:rgba(255,209,102,.14);
}
.fb-code {
    color:#5cdbc0;
    font-weight:700;
}
.fb-muted {
    color:#7d8590;
    font-size:11px;
}
.fb-empty {
    text-align:center;
    padding:26px 16px;
    color:#7d8590;
}
.fb-catalogo-list {
    display:flex;
    flex-direction:column;
    gap:10px;
    max-height:620px;
    overflow:auto;
}
.fb-catalogo-item {
    border:1px solid rgba(255,255,255,.07);
    border-radius:8px;
    padding:12px 12px;
    background:rgba(255,255,255,.015);
}
.fb-catalogo-top {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-bottom:4px;
}
.fb-catalogo-cod {
    color:#5cdbc0;
    font-weight:700;
    font-size:13px;
}
.fb-catalogo-nome {
    color:#e6edf3;
    font-size:13px;
    font-weight:600;
}
.fb-catalogo-meta {
    color:#7d8590;
    font-size:11px;
}
.fb-toolbar-note {
    padding:0 24px;
    margin-top:10px;
    color:#7d8590;
    font-size:12px;
}
@media (max-width: 1100px) {
    .fb-grid { grid-template-columns:1fr; }
}
</style>

<div class="fb-root">
    <div class="fb-topbar">
        <div class="fb-title">
            <i class="fas fa-university"></i>Cadastrar bancos
        </div>
        <div class="fb-actions">
            <?= $this->Html->link(
                '<i class="fas fa-plus"></i> Novo banco',
                ['action' => 'add'],
                ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-th-large"></i> Painel bancos',
                ['action' => 'index'],
                ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-arrow-left"></i> Financeiro',
                ['controller' => 'Financeiro', 'action' => 'index'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
        </div>
    </div>

    <?= $this->Form->create(null, ['type' => 'get', 'class' => 'fb-filters']) ?>
        <div class="fb-filter-group">
            <label>Código bancário / CNAB</label>
            <?= $this->Form->control('codigo', [
                'label' => false,
                'class' => 'form-control',
                'value' => $codigo,
                'placeholder' => 'Ex: 341, 756, 001',
            ]) ?>
        </div>
        <div class="fb-filter-group">
            <label>Nome do banco</label>
            <?= $this->Form->control('nome', [
                'label' => false,
                'class' => 'form-control',
                'value' => $nome,
                'placeholder' => 'Ex: Itaú, Bradesco, Sicredi',
            ]) ?>
        </div>
        <div class="fb-filter-group" style="max-width:180px;">
            <label>Status</label>
            <?= $this->Form->select('ativo', [
                '' => 'Todos',
                '1' => 'Ativos',
                '0' => 'Inativos',
            ], [
                'class' => 'form-control',
                'value' => $ativo,
            ]) ?>
        </div>
        <div class="fb-filter-actions">
            <?= $this->Form->button('<i class="fas fa-search"></i> Buscar', [
                'type' => 'submit',
                'class' => 'btn btn-pgm btn-pgm-salvar btn-sm',
                'escape' => false,
            ]) ?>
            <?= $this->Html->link(
                'Limpar',
                ['action' => 'cadastrar'],
                ['class' => 'btn btn-default btn-sm']
            ) ?>
        </div>
    <?= $this->Form->end() ?>

    <div class="fb-toolbar-note">
        Você pode buscar os bancos pelos códigos bancários informados no catálogo e alinhar o cadastro ao financeiro da empresa.
    </div>

    <div class="fb-grid">
        <div class="fb-card">
            <div class="fb-card-title">Bancos cadastrados no financeiro</div>
            <div class="fb-card-sub">Lista de bancos vinculados à empresa ativa, com busca por código bancário, CNAB e nome.</div>

            <div class="fb-table-wrap">
                <table class="fb-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Núm. banco</th>
                            <th>CNAB</th>
                            <th>Banco</th>
                            <th>Conta bancária</th>
                            <th>Status</th>
                            <th style="width:130px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bancos)): ?>
                            <tr>
                                <td colspan="7" class="fb-empty">
                                    Nenhum banco encontrado com os filtros informados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bancos as $banco): ?>
                                <tr>
                                    <td>
                                        <div class="fb-code"><?= h($banco->codigo_banco ?: '—') ?></div>
                                        <?php if (!empty($banco->codigo_banco_interno)): ?>
                                            <div class="fb-muted">Interno: <?= h($banco->codigo_banco_interno) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= h($banco->numero_banco ?: '—') ?></td>
                                    <td><?= h($banco->cnab ?: '—') ?></td>
                                    <td>
                                        <div><?= h($banco->nome) ?></div>
                                        <?php if (!empty($banco->verifica_receber)): ?>
                                            <div class="fb-muted">Verifica receber: <?= h($banco->verifica_receber) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($banco->utiliza_endosso)): ?>
                                            <div class="fb-muted">Utiliza endosso: <?= h($banco->utiliza_endosso) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= h($fmtConta($banco)) ?>
                                        <?php
                                            $temAgencia = trim((string)($banco->numero_agencia ?? '')) !== '';
                                            $temConta = trim((string)($banco->numero_conta ?? '')) !== '';
                                        ?>
                                        <?php if (!$temAgencia || !$temConta): ?>
                                            <div class="fb-muted" style="color:#ffd166;">Cadastro incompleto: preencha agência e conta para usar em conciliação e retorno.</div>
                                        <?php endif; ?>
                                        <?php if (!empty($banco->logotipo)): ?>
                                            <div class="fb-muted">Logo: <?= h($banco->logotipo) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($banco->ativo)): ?>
                                            <span class="fb-status fb-status--on">Ativo</span>
                                        <?php else: ?>
                                            <span class="fb-status fb-status--off">Inativo</span>
                                        <?php endif; ?>
                                        <?php if (!$temAgencia || !$temConta): ?>
                                            <div style="margin-top:6px;">
                                                <span class="fb-status fb-status--warn">Conta incompleta</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="white-space:nowrap;">
                                        <?= $this->Html->link(
                                            'Editar',
                                            ['action' => 'edit', $banco->id],
                                            ['class' => 'btn btn-xs btn-pgm btn-pgm-situacao']
                                        ) ?>
                                        <?= $this->Form->postLink(
                                            'Excluir',
                                            ['action' => 'delete', $banco->id],
                                            [
                                                'class' => 'btn btn-xs btn-outline-danger',
                                                'confirm' => 'Deseja excluir este banco do financeiro?',
                                            ]
                                        ) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="fb-card">
            <div class="fb-card-title">Catálogo bancário por código</div>
            <div class="fb-card-sub">Referência rápida para localizar bancos pelos códigos bancários e facilitar o cadastro.</div>

            <div class="fb-catalogo-list">
                <?php if (empty($catalogo)): ?>
                    <div class="fb-empty">
                        Nenhum banco localizado no catálogo para esta busca.
                    </div>
                <?php else: ?>
                    <?php foreach ($catalogo as $item): ?>
                        <div class="fb-catalogo-item">
                            <div class="fb-catalogo-top">
                                <div class="fb-catalogo-cod"><?= h($item['codigo'] ?? '—') ?></div>
                                <div class="fb-catalogo-meta">CNAB: <?= h($item['cnab'] ?? '—') ?></div>
                            </div>
                            <div class="fb-catalogo-nome"><?= h($item['nome'] ?? '—') ?></div>
                            <?php if (!empty($item['nome_completo'])): ?>
                                <div class="fb-catalogo-meta"><?= h($item['nome_completo']) ?></div>
                            <?php endif; ?>
                            <div style="margin-top:10px;">
                                <?= $this->Html->link(
                                    'Novo cadastro',
                                    ['action' => 'add', '?' => ['codigo' => $item['codigo'] ?? '']],
                                    ['class' => 'btn btn-xs btn-pgm btn-pgm-salvar']
                                ) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
