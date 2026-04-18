<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Histórico de Retorno', ['controller' => 'FinanceiroBancos', 'action' => 'historicoRetorno']);
$this->Breadcrumbs->add('Detalhe do Arquivo de Retorno');

$retornoArquivo = $retornoArquivo ?? null;
$itens = $itens ?? [];
$resumoDetalhe = $resumoDetalhe ?? [
    'processados' => 0,
    'baixados' => 0,
    'rejeitados' => 0,
    'ignorados' => 0,
    'erros' => 0,
    'download_disponivel' => false,
];

$banco = $retornoArquivo->financeiro_banco ?? null;
$usuario = $retornoArquivo->user ?? null;
$remessa = $retornoArquivo->financeiro_remessa ?? null;

$nomeArquivoOriginal = trim((string)($retornoArquivo->nome_arquivo_original ?? ''));
$nomeArquivoSalvo = trim((string)($retornoArquivo->nome_arquivo_salvo ?? ''));
$layoutCnab = trim((string)($retornoArquivo->layout_cnab ?? '240'));
$statusProcessamento = trim((string)($retornoArquivo->status_processamento ?? 'processado'));
$observacoes = trim((string)($retornoArquivo->observacoes ?? ''));

$statusClass = 'fb-badge fb-badge--neutral';
$statusLabel = $statusProcessamento !== '' ? $statusProcessamento : 'processado';

if (in_array(mb_strtolower($statusProcessamento), ['processado', 'sucesso'], true)) {
    $statusClass = 'fb-badge fb-badge--ok';
    $statusLabel = 'Processado';
} elseif (in_array(mb_strtolower($statusProcessamento), ['processado_parcial', 'pendente'], true)) {
    $statusClass = 'fb-badge fb-badge--warn';
    $statusLabel = 'Processado parcialmente';
} elseif (in_array(mb_strtolower($statusProcessamento), ['erro', 'rejeitado'], true)) {
    $statusClass = 'fb-badge fb-badge--danger';
    $statusLabel = 'Com erro';
}

function fbDetalheRetornoFmtDate($value, $withTime = false)
{
    if (empty($value) || !is_object($value) || !method_exists($value, 'format')) {
        return '—';
    }

    return $value->format($withTime ? 'd/m/Y H:i' : 'd/m/Y');
}

function fbDetalheRetornoStatusClass($status)
{
    $status = mb_strtolower(trim((string)$status));

    if ($status === 'baixado') {
        return 'fb-badge fb-badge--ok';
    }
    if ($status === 'rejeitado') {
        return 'fb-badge fb-badge--danger';
    }
    if ($status === 'erro') {
        return 'fb-badge fb-badge--danger';
    }
    if ($status === 'aceito') {
        return 'fb-badge fb-badge--info';
    }

    return 'fb-badge fb-badge--warn';
}
?>
<style>
.fb-root { font-family:'DM Sans',sans-serif; }
.fb-topbar {
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    padding:18px 24px 14px;
    border-bottom:1px solid rgba(255,255,255,.07);
    gap:14px;
    flex-wrap:wrap;
}
.fb-h1 {
    font-size:20px;
    font-weight:600;
    color:#e6edf3;
    margin:0;
}
.fb-h1-ico {
    color:#5cdbc0;
    margin-right:8px;
}
.fb-sub {
    color:#8b949e;
    font-size:12.5px;
    margin-top:4px;
    line-height:1.6;
}
.fb-actions {
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.fb-kpi-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(210px,1fr));
    gap:14px;
    padding:20px 24px 0;
}
.fb-kpi {
    background:#161b22;
    border:1px solid rgba(255,255,255,.07);
    border-radius:10px;
    padding:16px 18px;
}
.fb-kpi-label {
    color:#7d8590;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:700;
    margin-bottom:6px;
}
.fb-kpi-value {
    color:#e6edf3;
    font-size:24px;
    font-weight:700;
    line-height:1.1;
}
.fb-kpi-help {
    margin-top:8px;
    color:#8b949e;
    font-size:11.5px;
    line-height:1.5;
}
.fb-card {
    background:#161b22;
    border:1px solid rgba(255,255,255,.07);
    border-radius:10px;
    padding:18px 20px;
    margin:16px 24px;
}
.fb-card-head {
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    margin-bottom:14px;
    flex-wrap:wrap;
}
.fb-card-title {
    color:#e6edf3;
    font-size:15px;
    font-weight:600;
    margin:0;
}
.fb-card-sub {
    color:#8b949e;
    font-size:12px;
    margin-top:4px;
    line-height:1.55;
}
.fb-summary-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:14px;
}
.fb-summary-box {
    background:rgba(255,255,255,.02);
    border:1px solid rgba(255,255,255,.06);
    border-radius:10px;
    padding:14px 16px;
}
.fb-summary-title {
    color:#e6edf3;
    font-size:13px;
    font-weight:700;
    margin-bottom:6px;
}
.fb-summary-text {
    color:#c9d1d9;
    font-size:12.5px;
    line-height:1.65;
}
.fb-summary-text strong {
    color:#e6edf3;
}
.fb-meta-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(210px,1fr));
    gap:12px;
}
.fb-meta-item {
    background:#0f141a;
    border:1px solid rgba(255,255,255,.06);
    border-radius:10px;
    padding:12px 14px;
}
.fb-meta-label {
    color:#7d8590;
    font-size:10.5px;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:700;
    margin-bottom:5px;
}
.fb-meta-value {
    color:#e6edf3;
    font-size:13px;
    line-height:1.55;
    word-break:break-word;
}
.fb-muted {
    color:#8b949e;
    font-size:11px;
    line-height:1.5;
}
.fb-badge {
    display:inline-block;
    padding:4px 10px;
    border-radius:999px;
    font-size:11px;
    font-weight:700;
}
.fb-badge--ok {
    background:rgba(63,185,80,.15);
    color:#3fb950;
}
.fb-badge--warn {
    background:rgba(255,193,7,.15);
    color:#ffd166;
}
.fb-badge--danger {
    background:rgba(248,81,73,.15);
    color:#f85149;
}
.fb-badge--info {
    background:rgba(56,139,253,.15);
    color:#79c0ff;
}
.fb-badge--neutral {
    background:rgba(255,255,255,.08);
    color:#c9d1d9;
}
.fb-table-wrap {
    overflow:auto;
}
.fb-table {
    width:100%;
    border-collapse:collapse;
    min-width:1320px;
    font-size:12.5px;
}
.fb-table th {
    color:#7d8590;
    font-size:10.5px;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:700;
    padding:8px 10px;
    border-bottom:1px solid rgba(255,255,255,.07);
    text-align:left;
    white-space:nowrap;
}
.fb-table td {
    padding:10px;
    border-bottom:1px solid rgba(255,255,255,.04);
    color:#c9d1d9;
    vertical-align:top;
}
.fb-table tr:hover td {
    background:rgba(255,255,255,.02);
}
.fb-code {
    display:inline-block;
    padding:2px 7px;
    border-radius:6px;
    background:rgba(92,219,192,.10);
    color:#5cdbc0;
    font-weight:700;
    font-size:12px;
}
.fb-money {
    white-space:nowrap;
    font-weight:700;
}
.fb-note-box {
    margin:16px 24px 0;
    background:rgba(29,158,117,.08);
    border:1px solid rgba(29,158,117,.16);
    border-radius:10px;
    padding:14px 16px;
    color:#c9d1d9;
    font-size:12.5px;
    line-height:1.6;
}
.fb-note-box strong {
    color:#5cdbc0;
}
.fb-empty {
    text-align:center;
    padding:36px 18px;
    color:#8b949e;
}
.fb-empty i {
    display:block;
    font-size:32px;
    margin-bottom:10px;
    opacity:.4;
    color:#5cdbc0;
}
.fb-footer-note {
    margin:0 24px 20px;
    color:#8b949e;
    font-size:12px;
    line-height:1.7;
}
</style>

<div class="fb-root">
    <div class="fb-topbar">
        <div>
            <h1 class="fb-h1"><i class="fas fa-file-invoice fb-h1-ico"></i>Detalhe do Arquivo de Retorno</h1>
            <div class="fb-sub">
                Auditoria completa do arquivo processado, com resumo operacional, vínculo bancário e grade detalhada das ocorrências retornadas pelo banco.
            </div>
        </div>

        <div class="fb-actions">
            <?= $this->Html->link(
                '<i class="fas fa-history"></i> Histórico de retorno',
                ['controller' => 'FinanceiroBancos', 'action' => 'historicoRetorno'],
                ['class' => 'btn btn-default btn-sm', 'escape' => false]
            ) ?>
            <?php if (!empty($resumoDetalhe['download_disponivel']) && !empty($retornoArquivo->id)): ?>
                <?= $this->Html->link(
                    '<i class="fas fa-download"></i> Download do arquivo',
                    ['controller' => 'FinanceiroBancos', 'action' => 'downloadRetorno', $retornoArquivo->id],
                    ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm', 'escape' => false]
                ) ?>
            <?php endif; ?>
            <?php if (!empty($banco->id)): ?>
                <?= $this->Html->link(
                    '<i class="fas fa-university"></i> Abrir banco',
                    ['controller' => 'FinanceiroBancos', 'action' => 'edit', $banco->id],
                    ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false]
                ) ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="fb-kpi-grid">
        <div class="fb-kpi">
            <div class="fb-kpi-label">Processados</div>
            <div class="fb-kpi-value"><?= number_format((int)$resumoDetalhe['processados'], 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Total de ocorrências lidas no arquivo de retorno.</div>
        </div>

        <div class="fb-kpi">
            <div class="fb-kpi-label">Baixados</div>
            <div class="fb-kpi-value"><?= number_format((int)$resumoDetalhe['baixados'], 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Itens liquidados/baixados automaticamente durante o processamento.</div>
        </div>

        <div class="fb-kpi">
            <div class="fb-kpi-label">Rejeitados</div>
            <div class="fb-kpi-value"><?= number_format((int)$resumoDetalhe['rejeitados'], 0, ',', '.') ?></div>
            <div class="fb-kpi-help">Ocorrências rejeitadas pelo banco e registradas para correção operacional.</div>
        </div>

        <div class="fb-kpi">
            <div class="fb-kpi-label">Ignorados + erros</div>
            <div class="fb-kpi-value">
                <?= number_format((int)$resumoDetalhe['ignorados'] + (int)$resumoDetalhe['erros'], 0, ',', '.') ?>
            </div>
            <div class="fb-kpi-help">Itens que exigem conferência manual, análise ou nova importação.</div>
        </div>
    </div>

    <div class="fb-note-box">
        <strong>Status do arquivo:</strong>
        <span class="<?= h($statusClass) ?>" style="margin-left:6px;"><?= h($statusLabel) ?></span>
        <?php if ($layoutCnab !== ''): ?>
            <span style="margin-left:10px;">Layout CNAB <strong><?= h($layoutCnab) ?></strong></span>
        <?php endif; ?>
        <?php if (!empty($retornoArquivo->data_processamento)): ?>
            <span style="margin-left:10px;">Processado em <strong><?= h(fbDetalheRetornoFmtDate($retornoArquivo->data_processamento, true)) ?></strong></span>
        <?php endif; ?>
    </div>

    <div class="fb-card">
        <div class="fb-card-head">
            <div>
                <h2 class="fb-card-title">Resumo do arquivo</h2>
                <div class="fb-card-sub">
                    Identificação do arquivo importado, banco vinculado, remessa associada e dados úteis para rastreabilidade.
                </div>
            </div>
        </div>

        <div class="fb-meta-grid">
            <div class="fb-meta-item">
                <div class="fb-meta-label">Arquivo original</div>
                <div class="fb-meta-value">
                    <?= h($nomeArquivoOriginal !== '' ? $nomeArquivoOriginal : '—') ?>
                </div>
            </div>

            <div class="fb-meta-item">
                <div class="fb-meta-label">Arquivo salvo</div>
                <div class="fb-meta-value">
                    <?= h($nomeArquivoSalvo !== '' ? $nomeArquivoSalvo : '—') ?>
                </div>
            </div>

            <div class="fb-meta-item">
                <div class="fb-meta-label">Banco vinculado</div>
                <div class="fb-meta-value">
                    <?php if (!empty($banco)): ?>
                        <?= h(trim((string)($banco->codigo_banco ?? '')) !== '' ? $banco->codigo_banco . ' — ' . $banco->nome : ($banco->nome ?? '—')) ?>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </div>
            </div>

            <div class="fb-meta-item">
                <div class="fb-meta-label">Usuário</div>
                <div class="fb-meta-value">
                    <?=
                        h(
                            trim((string)($usuario->name ?? $usuario->username ?? '')) !== ''
                                ? (string)($usuario->name ?? $usuario->username)
                                : 'Usuário não identificado'
                        )
                    ?>
                </div>
            </div>

            <div class="fb-meta-item">
                <div class="fb-meta-label">Remessa relacionada</div>
                <div class="fb-meta-value">
                    <?php if (!empty($remessa)): ?>
                        <?= h(trim((string)($remessa->numero_remessa ?? '')) !== '' ? $remessa->numero_remessa : str_pad((string)($remessa->sequencial_arquivo ?? 0), 6, '0', STR_PAD_LEFT)) ?>
                        <div class="fb-muted"><?= h((string)($remessa->nome_arquivo ?? 'Arquivo não informado')) ?></div>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </div>
            </div>

            <div class="fb-meta-item">
                <div class="fb-meta-label">Data de processamento</div>
                <div class="fb-meta-value">
                    <?= h(fbDetalheRetornoFmtDate($retornoArquivo->data_processamento ?? null, true)) ?>
                </div>
            </div>
        </div>

        <?php if ($observacoes !== ''): ?>
            <div style="height:14px;"></div>
            <div class="fb-summary-box">
                <div class="fb-summary-title">Observações operacionais</div>
                <div class="fb-summary-text">
                    <?= nl2br(h($observacoes)) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="fb-card">
        <div class="fb-card-head">
            <div>
                <h2 class="fb-card-title">Leitura operacional</h2>
                <div class="fb-card-sub">
                    Resumo executivo do resultado da importação, útil para cobrança, conciliação e auditoria interna.
                </div>
            </div>
        </div>

        <div class="fb-summary-grid">
            <div class="fb-summary-box">
                <div class="fb-summary-title">Conciliação do arquivo</div>
                <div class="fb-summary-text">
                    Foram processados <strong><?= number_format((int)$resumoDetalhe['processados'], 0, ',', '.') ?></strong> item(ns),
                    dos quais <strong><?= number_format((int)$resumoDetalhe['baixados'], 0, ',', '.') ?></strong> resultaram em baixa automática
                    e <strong><?= number_format((int)$resumoDetalhe['rejeitados'], 0, ',', '.') ?></strong> ficaram como rejeição bancária.
                </div>
            </div>

            <div class="fb-summary-box">
                <div class="fb-summary-title">Pendências de análise</div>
                <div class="fb-summary-text">
                    O arquivo possui <strong><?= number_format((int)$resumoDetalhe['ignorados'], 0, ',', '.') ?></strong> ocorrência(s) ignorada(s)
                    e <strong><?= number_format((int)$resumoDetalhe['erros'], 0, ',', '.') ?></strong> erro(s) de processamento,
                    o que indica a necessidade de conferência manual em parte do retorno.
                </div>
            </div>

            <div class="fb-summary-box">
                <div class="fb-summary-title">Contexto bancário</div>
                <div class="fb-summary-text">
                    <?php if (!empty($banco)): ?>
                        Este retorno está associado ao banco <strong><?= h((string)($banco->nome ?? '—')) ?></strong>
                        <?php if (!empty($banco->numero_agencia) || !empty($banco->numero_conta)): ?>
                            na conta
                            <strong>
                                <?= h(trim((string)($banco->numero_agencia ?? '')) !== '' ? $banco->numero_agencia : '—') ?>
                                <?php if (!empty($banco->digito_agencia)): ?>-<?= h($banco->digito_agencia) ?><?php endif; ?>
                                /
                                <?= h(trim((string)($banco->numero_conta ?? '')) !== '' ? $banco->numero_conta : '—') ?>
                                <?php if (!empty($banco->digito_conta)): ?>-<?= h($banco->digito_conta) ?><?php endif; ?>
                            </strong>.
                        <?php else: ?>
                            sem agência/conta completas no cadastro.
                        <?php endif; ?>
                    <?php else: ?>
                        O arquivo foi persistido sem banco claramente vinculado, então vale revisar a associação operacional.
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="fb-card">
        <div class="fb-card-head">
            <div>
                <h2 class="fb-card-title">Itens processados</h2>
                <div class="fb-card-sub">
                    Grade detalhada das ocorrências retornadas, com vínculo ao título financeiro, remessa e mensagem operacional.
                </div>
            </div>
        </div>

        <div class="fb-table-wrap">
            <table class="fb-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Status</th>
                        <th>Nosso número</th>
                        <th>Documento</th>
                        <th>Ocorrência</th>
                        <th>Valor título</th>
                        <th>Valor pago</th>
                        <th>Vencimento</th>
                        <th>Ocorrência em</th>
                        <th>Título / Remessa</th>
                        <th>Mensagem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($itens)): ?>
                        <tr>
                            <td colspan="11" class="fb-empty">
                                <i class="fas fa-folder-open"></i>
                                Nenhum item persistido para este arquivo de retorno.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($itens as $index => $item): ?>
                            <?php
                                $statusItem = trim((string)($item->status_item ?? 'ignorado'));
                                $lancamento = $item->financeiro_lancamento ?? null;
                                $remessaItem = $item->financeiro_remessa_titulo ?? null;
                                $remessaItemPai = $item->financeiro_remessa ?? null;
                            ?>
                            <tr>
                                <td>
                                    <span class="fb-code"><?= number_format((int)$index + 1, 0, ',', '.') ?></span>
                                </td>
                                <td>
                                    <span class="<?= h(fbDetalheRetornoStatusClass($statusItem)) ?>">
                                        <?= h($statusItem !== '' ? $statusItem : 'ignorado') ?>
                                    </span>
                                </td>
                                <td>
                                    <?= h(trim((string)($item->nosso_numero ?? '')) !== '' ? $item->nosso_numero : '—') ?>
                                </td>
                                <td>
                                    <?= h(trim((string)($item->numero_documento ?? '')) !== '' ? $item->numero_documento : '—') ?>
                                </td>
                                <td>
                                    <?php if (!empty($item->codigo_ocorrencia)): ?>
                                        <span class="fb-code"><?= h($item->codigo_ocorrencia) ?></span>
                                    <?php else: ?>
                                        <span class="fb-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fb-money">
                                    R$ <?= number_format((float)($item->valor_titulo ?? 0), 2, ',', '.') ?>
                                </td>
                                <td class="fb-money">
                                    <?php if ($item->valor_pago !== null && $item->valor_pago !== ''): ?>
                                        R$ <?= number_format((float)$item->valor_pago, 2, ',', '.') ?>
                                    <?php else: ?>
                                        <span class="fb-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= h(fbDetalheRetornoFmtDate($item->data_vencimento ?? null, false)) ?>
                                </td>
                                <td>
                                    <?= h(fbDetalheRetornoFmtDate($item->data_ocorrencia ?? null, true)) ?>
                                </td>
                                <td>
                                    <?php if (!empty($lancamento->id)): ?>
                                        <div><strong>Título #<?= h($lancamento->id) ?></strong></div>
                                        <div class="fb-muted"><?= h((string)($lancamento->descricao ?? 'Sem descrição no lançamento')) ?></div>
                                    <?php else: ?>
                                        <div class="fb-muted">Sem título vinculado</div>
                                    <?php endif; ?>

                                    <?php if (!empty($remessaItemPai->id)): ?>
                                        <div class="fb-muted" style="margin-top:5px;">Remessa #<?= h($remessaItemPai->id) ?></div>
                                    <?php elseif (!empty($remessaItem->financeiro_remessa_id)): ?>
                                        <div class="fb-muted" style="margin-top:5px;">Remessa #<?= h($remessaItem->financeiro_remessa_id) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= h(trim((string)($item->mensagem_ocorrencia ?? '')) !== '' ? $item->mensagem_ocorrencia : 'Sem mensagem detalhada.') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="fb-footer-note">
        Esta tela foi preparada para auditoria do retorno bancário: use o resumo superior para leitura executiva,
        confira a grade para investigar cada ocorrência individual e utilize o download do arquivo sempre que precisar
        revisar o conteúdo original processado pelo módulo.
    </p>
</div>
