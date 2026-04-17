<?php
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index']);
$this->Breadcrumbs->add('Retornos bancários');

$bancos = $bancos ?? [];
$resumoRetorno = $resumoRetorno ?? [];
?>
<style>
.fb-ret-root { font-family:'DM Sans',sans-serif; }
.fb-ret-topbar { display:flex; align-items:center; justify-content:space-between; padding:18px 24px 14px; border-bottom:1px solid rgba(255,255,255,.07); gap:12px; flex-wrap:wrap; }
.fb-ret-title { font-size:20px; font-weight:600; color:#e6edf3; margin:0; }
.fb-ret-title i { color:#5cdbc0; margin-right:8px; }
.fb-ret-actions { display:flex; gap:8px; flex-wrap:wrap; }

.fb-ret-card {
    background:#161b22;
    border:1px solid rgba(255,255,255,.07);
    border-radius:10px;
    padding:18px 20px;
    margin:16px 24px;
}

.fb-ret-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:16px;
}

.fb-ret-mini {
    background:rgba(255,255,255,.02);
    border:1px solid rgba(255,255,255,.06);
    border-radius:10px;
    padding:16px;
}

.fb-ret-mini h3 {
    margin:0 0 8px;
    font-size:14px;
    font-weight:600;
    color:#e6edf3;
}

.fb-ret-mini p {
    margin:0;
    color:#8b949e;
    font-size:12.5px;
    line-height:1.55;
}

.fb-ret-table {
    width:100%;
    border-collapse:collapse;
    font-size:12.5px;
}

.fb-ret-table th {
    text-align:left;
    color:#7d8590;
    font-size:10.5px;
    text-transform:uppercase;
    letter-spacing:.05em;
    font-weight:600;
    padding:8px 10px;
    border-bottom:1px solid rgba(255,255,255,.07);
}

.fb-ret-table td {
    padding:10px;
    color:#c9d1d9;
    border-bottom:1px solid rgba(255,255,255,.04);
    vertical-align:top;
}

.fb-ret-table tr:hover td {
    background:rgba(255,255,255,.02);
}

.fb-ret-badge {
    display:inline-block;
    padding:3px 8px;
    border-radius:999px;
    font-size:11px;
    font-weight:600;
}
.fb-ret-badge--implantacao {
    background:rgba(255,193,7,.12);
    color:#ffc107;
}
.fb-ret-badge--ok {
    background:rgba(63,185,80,.14);
    color:#3fb950;
}
.fb-ret-badge--pendente {
    background:rgba(249,196,74,.16);
    color:#ffd166;
}
.fb-ret-badge--semconta {
    background:rgba(255,255,255,.08);
    color:#9ca3af;
}
.fb-ret-sub {
    display:block;
    margin-top:4px;
    color:#8b949e;
    font-size:11px;
}

.fb-ret-empty {
    text-align:center;
    padding:34px 18px;
    color:#8b949e;
}

.fb-ret-empty i {
    display:block;
    font-size:28px;
    opacity:.45;
    margin-bottom:10px;
    color:#5cdbc0;
}
</style>

<div class="fb-ret-root">
    <div class="fb-ret-topbar">
        <h1 class="fb-ret-title"><i class="fas fa-university"></i>Retornos bancários</h1>
        <div class="fb-ret-actions">
            <?= $this->Html->link('Voltar para Bancos', ['controller' => 'FinanceiroBancos', 'action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
            <?= $this->Html->link('Cadastro de bancos', ['controller' => 'FinanceiroBancos', 'action' => 'cadastrar'], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm']) ?>
        </div>
    </div>

    <div class="fb-ret-card">
        <div class="fb-ret-grid">
            <div class="fb-ret-mini">
                <h3>Estrutura do módulo</h3>
                <p>Esta tela centraliza o acompanhamento de retornos bancários e já está alinhada ao submenu do financeiro para futuras integrações com arquivos CNAB e baixas automáticas.</p>
            </div>
            <div class="fb-ret-mini">
                <h3>Status atual</h3>
                <p>O módulo está preparado para receber a evolução de importação e processamento de arquivos de retorno. Enquanto isso, você já consegue visualizar os bancos configurados no financeiro.</p>
            </div>
            <div class="fb-ret-mini">
                <h3>Próximos passos</h3>
                <p>Na próxima etapa, esta área poderá consolidar histórico de importações, ocorrências, títulos liquidados e rejeições por banco.</p>
            </div>
        </div>
    </div>

    <div class="fb-ret-card">
        <h2 style="margin:0 0 14px; font-size:16px; color:#e6edf3;">Bancos disponíveis para retorno</h2>

        <?php if (empty($bancos)): ?>
            <div class="fb-ret-empty">
                <i class="fas fa-folder-open"></i>
                Nenhum banco cadastrado para a empresa.
            </div>
        <?php else: ?>
            <table class="fb-ret-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Banco</th>
                        <th>CNAB</th>
                        <th>Agência</th>
                        <th>Conta</th>
                        <th>Status do retorno</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bancos as $banco): ?>
                    <?php
                        $resumo = $resumoRetorno[(int)$banco->id] ?? [
                            'quantidade' => 0,
                            'conciliados' => 0,
                            'pendentes' => 0,
                            'ultimo_evento' => null,
                        ];

                        $temConta = !empty($banco->numero_agencia) || !empty($banco->numero_conta);
                        $statusLabel = 'Sem conta bancária';
                        $statusClass = 'fb-ret-badge--semconta';

                        if ($temConta) {
                            if ((int)$resumo['quantidade'] <= 0) {
                                $statusLabel = 'Sem extrato importado';
                                $statusClass = 'fb-ret-badge--implantacao';
                            } elseif ((int)$resumo['pendentes'] > 0) {
                                $statusLabel = 'Com pendências';
                                $statusClass = 'fb-ret-badge--pendente';
                            } else {
                                $statusLabel = 'Conciliado';
                                $statusClass = 'fb-ret-badge--ok';
                            }
                        }
                    ?>
                    <tr>
                        <td><strong><?= h($banco->codigo_banco ?: '—') ?></strong></td>
                        <td><?= h($banco->nome ?: '—') ?></td>
                        <td><?= h($banco->cnab ?: '—') ?></td>
                        <td>
                            <?= h($banco->numero_agencia ?: '—') ?>
                            <?= !empty($banco->digito_agencia) ? ' - ' . h($banco->digito_agencia) : '' ?>
                        </td>
                        <td>
                            <?= h($banco->numero_conta ?: '—') ?>
                            <?= !empty($banco->digito_conta) ? ' - ' . h($banco->digito_conta) : '' ?>
                        </td>
                        <td>
                            <span class="fb-ret-badge <?= h($statusClass) ?>"><?= h($statusLabel) ?></span>
                            <?php if ((int)$resumo['quantidade'] > 0): ?>
                                <span class="fb-ret-sub">
                                    <?= (int)$resumo['quantidade'] ?> extrato(s),
                                    <?= (int)$resumo['conciliados'] ?> conciliado(s),
                                    <?= (int)$resumo['pendentes'] ?> pendente(s)
                                    <?php if (!empty($resumo['ultimo_evento'])): ?>
                                        · último em <?= h($resumo['ultimo_evento']->format('d/m/Y')) ?>
                                    <?php endif; ?>
                                </span>
                            <?php elseif ($temConta): ?>
                                <span class="fb-ret-sub">Conta cadastrada, aguardando importação de extrato para conciliação.</span>
                            <?php else: ?>
                                <span class="fb-ret-sub">Cadastre agência e conta para relacionar extratos importados.</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
