<?php
    /**
     * Clientes — lista CRM (layout alinhado ao mock pg-clientes / shell premium).
     */
    use Cake\Routing\Router;

    $this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-premium']));
    $this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']));

    function cliMask($mask, $str) {
        if ($str === null || $str === '') {
            return '';
        }
        $mask = (string)$mask;
        $str = str_replace(' ', '', (string)$str);
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $pos = strpos($mask, '#');
            if ($pos === false) {
                break;
            }
            $mask[$pos] = $str[$i];
        }

        return $mask;
    }

    function cliInitials($str) {
        $parts = preg_split('/\s+/', trim($str), -1, PREG_SPLIT_NO_EMPTY);
        $a = strtoupper(substr($parts[0] ?? 'C', 0, 1));
        $b = strtoupper(substr($parts[1] ?? '', 0, 1));

        return $a . $b;
    }

    function cliRowDataAttrs($reg) {
        $isPj = (int)$reg->tipo === (int)C_ClientesTipoJuridica;
        $docDigits = preg_replace('/\D/', '', (string)($isPj ? ($reg->cnpj ?? '') : ($reg->cpf ?? '')));
        $emailLower = mb_strtolower(trim((string)($reg->email ?? '')), 'UTF-8');
        $pub = mb_strtolower(trim((string)($reg->public_code ?? '')), 'UTF-8');
        $parts = $isPj ? [trim((string)($reg->razaosocial ?? '')), trim((string)($reg->nomefantasia ?? ''))] : [trim((string)($reg->nome ?? ''))];
        $textBlob = mb_strtolower(trim(preg_replace('/\s+/', ' ', implode(' ', array_filter($parts)))), 'UTF-8');
        if ($emailLower !== '') {
            $textBlob = trim($textBlob . ' ' . $emailLower);
        }
        if ($pub !== '') {
            $textBlob = trim($textBlob . ' ' . $pub);
        }
        $primaryLower = mb_strtolower(trim($isPj ? (string)($reg->razaosocial ?? '') : (string)($reg->nome ?? '')), 'UTF-8');
        $primaryLower = trim(preg_replace('/\s+/', ' ', $primaryLower));

        return ' data-cli-doc="' . h($docDigits) . '" data-cli-email="' . h($emailLower) . '" data-cli-text="' . h($textBlob) . '" data-cli-primary="' . h($primaryLower) . '"';
    }

    $cntAPJ = count($clientesAtivosPJ);
    $cntAPF = count($clientesAtivosPF);
    $cntIPJ = count($clientesInativosPJ);
    $cntIPF = count($clientesInativosPF);
    $cntAtivos = $cntAPJ + $cntAPF;
    $cntInativos = $cntIPJ + $cntIPF;

    $crm = isset($cliCrm) && is_array($cliCrm) ? $cliCrm : [];
    $top5 = $crm['top5'] ?? [];
    $segmentos = $crm['segmentos'] ?? [];
    $cliRows = isset($cliRows) && is_array($cliRows) ? $cliRows : [];
    $cliVendedores = isset($cliVendedores) && is_array($cliVendedores) ? $cliVendedores : [];
    $barTones = ['teal', 'blue', 'navy', 'orange', 'wine'];
?>
<?= $this->element('pgm_premium_css', ['name' => 'clientes-premium']) ?>
<?= $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']) ?>

<div class="col-md-12 p-0">
<div class="cli-root cli-layout-unificado cli-crm-lista">

    <header class="cli-crm-page-head cli-crm-page-head--bar">
        <div class="cli-crm-page-head-text">
            <p class="cli-crm-subtitle"><?= h(__('Cadastro mestre · CRM básico · Histórico financeiro consolidado')) ?></p>
        </div>
        <div class="cli-crm-page-actions">
            <?= $this->Html->link(
                '<i class="fas fa-file-excel" aria-hidden="true"></i> ' . __('Exportar Excel'),
                ['controller' => 'ClientesPrototype', 'action' => 'exportCsv'],
                ['class' => 'btn-cli-secondary', 'escape' => false, 'title' => __('Exportação CSV (compatível com Excel)')]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-upload" aria-hidden="true"></i> ' . __('Importar'),
                ['controller' => 'ClientesPrototype', 'action' => 'view', 'import'],
                ['class' => 'btn-cli-secondary', 'escape' => false]
            ) ?>
            <?= $this->Html->link(
                '<i class="fas fa-plus" aria-hidden="true"></i> ' . __('Novo cliente'),
                ['action' => 'add'],
                ['class' => 'btn-cli-primary', 'escape' => false]
            ) ?>
        </div>
    </header>

    <div class="cli-kpi-strip cli-kpi-strip--crm">
        <div class="cli-kpi cli-kpi--blue active" data-kpi="ativos">
            <div class="cli-kpi-label"><?= h(__('Clientes Ativos')) ?></div>
            <div class="cli-kpi-val teal"><?= (int)($crm['ativos'] ?? $cntAtivos) ?></div>
            <div class="cli-kpi-sub">
                <?php if (!empty($crm['novos_mes'])) : ?>
                    ↑ <?= (int)$crm['novos_mes'] ?> <?= h(__('este mês')) ?>
                <?php else : ?>
                    <?= h(__('na carteira')) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="cli-kpi cli-kpi--blue" data-kpi="receita">
            <div class="cli-kpi-label"><?= h(__('Receita 12 Meses')) ?></div>
            <div class="cli-kpi-val"><?= h((string)($crm['receita12_fmt'] ?? '—')) ?></div>
            <div class="cli-kpi-sub">
                <?php if (!empty($crm['receita12_pct'])) : ?>
                    ↑ <?= (int)$crm['receita12_pct'] ?>% <?= h(__('vs período anterior')) ?>
                <?php else : ?>
                    <?= h(__('consolidado financeiro')) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="cli-kpi cli-kpi--rose" data-kpi="ticket">
            <div class="cli-kpi-label"><?= h(__('Ticket Médio')) ?></div>
            <div class="cli-kpi-val"><?= h((string)($crm['ticket_fmt'] ?? '—')) ?></div>
            <div class="cli-kpi-sub"><?= h(__('por cliente / ano')) ?></div>
        </div>
        <div class="cli-kpi cli-kpi--orange" data-kpi="inadimplentes">
            <div class="cli-kpi-label"><?= h(__('Inadimplentes')) ?></div>
            <div class="cli-kpi-val"><?= (int)($crm['inadimplentes'] ?? 0) ?></div>
            <div class="cli-kpi-sub"><?= h((string)($crm['inadimplentes_valor_fmt'] ?? '—')) ?> <?= h(__('em atraso')) ?></div>
        </div>
        <div class="cli-kpi cli-kpi--blocked" data-kpi="bloqueados">
            <div class="cli-kpi-label"><?= h(__('Bloqueados')) ?></div>
            <div class="cli-kpi-val"><?= (int)($crm['bloqueados'] ?? $cntInativos) ?></div>
            <div class="cli-kpi-sub"><?= h(__('restrição interna')) ?></div>
        </div>
        <div class="cli-kpi cli-kpi--birthday" data-kpi="aniversariantes">
            <div class="cli-kpi-label"><?= h(__('Aniversariantes do Mês')) ?></div>
            <div class="cli-kpi-val"><?= (int)($crm['aniversariantes'] ?? 0) ?></div>
            <div class="cli-kpi-sub"><?= h(__('enviar mensagem')) ?></div>
        </div>
    </div>

    <div class="cli-crm-insights">
        <section class="cli-crm-panel cli-crm-panel--top">
            <h2 class="cli-crm-panel-title"><?= h(__('TOP 5 CLIENTES · RECEITA 12 MESES')) ?></h2>
            <?php if ($top5 === []) : ?>
                <p class="cli-crm-panel-empty"><?= h(__('Sem receitas lançadas no período.')) ?></p>
            <?php else : ?>
                <ol class="cli-crm-top-list">
                    <?php foreach ($top5 as $i => $row) :
                        $tone = $barTones[$i] ?? 'teal';
                        $pct = max(4, min(100, (int)($row['pct'] ?? 0)));
                    ?>
                    <li class="cli-crm-top-item">
                        <span class="cli-crm-top-rank"><?= (int)($i + 1) ?></span>
                        <div class="cli-crm-top-body">
                            <div class="cli-crm-top-row">
                                <span class="cli-crm-top-name"><?= h((string)$row['nome']) ?></span>
                                <span class="cli-crm-top-val"><?= h($this->Number->currency((float)($row['valor'] ?? 0), 'BRL')) ?> · <?= (int)($row['pct'] ?? 0) ?>%</span>
                            </div>
                            <div class="cli-crm-top-bar" role="presentation">
                                <span class="cli-crm-top-bar-fill cli-crm-top-bar-fill--<?= h($tone) ?>" style="width:<?= (int)$pct ?>%"></span>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ol>
                <?php if (!empty($crm['alerta_concentracao'])) : ?>
                <div class="cli-crm-alert" role="status">
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                    <span><?= h(__('Concentração: {0} representa {1}% da carteira. Considere diversificar para reduzir risco.', $crm['alerta_concentracao']['nome'], $crm['alerta_concentracao']['pct'])) ?></span>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <section class="cli-crm-panel cli-crm-panel--seg">
            <h2 class="cli-crm-panel-title"><?= h(__('DISTRIBUIÇÃO POR SEGMENTO')) ?></h2>
            <div class="cli-crm-seg-grid cli-crm-seg-grid--5">
                <?php foreach ($segmentos as $seg) : ?>
                <div class="cli-crm-seg-tile cli-crm-seg-tile--<?= h((string)$seg['tone']) ?>">
                    <span class="cli-crm-seg-n"><?= (int)$seg['count'] ?></span>
                    <span class="cli-crm-seg-l"><?= h((string)$seg['label']) ?></span>
                    <span class="cli-crm-seg-p"><?= (int)$seg['pct'] ?>%<?= (int)$seg['pct'] > 0 ? ' ' . h(__('da carteira')) : '' ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="cli-crm-tipo-bars">
                <div class="cli-crm-tipo-row">
                    <span class="cli-crm-tipo-label"><?= h(__('Pessoa Jurídica')) ?></span>
                    <span class="cli-crm-tipo-meta"><?= (int)($crm['pj_bar']['count'] ?? $cntAPJ + $cntIPJ) ?> (<?= (int)($crm['pj_bar']['pct'] ?? 0) ?>%)</span>
                    <div class="cli-crm-tipo-track"><span class="cli-crm-tipo-fill cli-crm-tipo-fill--teal" style="width:<?= (int)($crm['pj_bar']['pct'] ?? 0) ?>%"></span></div>
                </div>
                <div class="cli-crm-tipo-row">
                    <span class="cli-crm-tipo-label"><?= h(__('Pessoa Física')) ?></span>
                    <span class="cli-crm-tipo-meta"><?= (int)($crm['pf_bar']['count'] ?? $cntAPF + $cntIPF) ?> (<?= (int)($crm['pf_bar']['pct'] ?? 0) ?>%)</span>
                    <div class="cli-crm-tipo-track"><span class="cli-crm-tipo-fill cli-crm-tipo-fill--blue" style="width:<?= (int)($crm['pf_bar']['pct'] ?? 0) ?>%"></span></div>
                </div>
            </div>
        </section>
    </div>

    <div class="cli-list-card">
        <div class="cli-crm-toolbar">
            <div class="cli-crm-search-wide">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                    <circle cx="6.5" cy="6.5" r="4.5" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M10.5 10.5L14 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <input type="text" id="cli-search" placeholder="<?= h(__('Buscar por nome, CNPJ/CPF, e-mail, telefone...')) ?>" autocomplete="off" inputmode="text" aria-describedby="cli-search-mode" />
                <span class="cli-search-mode" id="cli-search-mode"></span>
            </div>
            <div class="cli-crm-toolbar-filters">
                <label class="cli-crm-filter-card">
                    <span class="cli-crm-filter-card-lbl"><?= h(__('Status')) ?></span>
                    <span class="cli-crm-filter-card-box">
                        <select class="cli-crm-select" id="cli-filter-status" aria-label="<?= h(__('Status')) ?>">
                            <option value="" selected><?= h(__('Todos os status')) ?></option>
                            <option value="ativos"><?= h(__('Ativos')) ?></option>
                            <option value="inativos"><?= h(__('Inativos')) ?></option>
                        </select>
                        <i class="fas fa-chevron-down cli-crm-filter-card-chev" aria-hidden="true"></i>
                    </span>
                </label>
                <label class="cli-crm-filter-card">
                    <span class="cli-crm-filter-card-lbl"><?= h(__('Segmento')) ?></span>
                    <span class="cli-crm-filter-card-box">
                        <select class="cli-crm-select" id="cli-filter-segmento" aria-label="<?= h(__('Segmento')) ?>">
                            <option value="" selected><?= h(__('Todos os segmentos')) ?></option>
                            <?php foreach ($segmentos as $seg) : ?>
                            <option value="<?= h((string)$seg['slug']) ?>"><?= h((string)$seg['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down cli-crm-filter-card-chev" aria-hidden="true"></i>
                    </span>
                </label>
                <label class="cli-crm-filter-card">
                    <span class="cli-crm-filter-card-lbl"><?= h(__('Tipo')) ?></span>
                    <span class="cli-crm-filter-card-box">
                        <select class="cli-crm-select" id="cli-filter-tipo" aria-label="<?= h(__('Tipo')) ?>">
                            <option value="" selected><?= h(__('PJ + PF')) ?></option>
                            <option value="pj"><?= h(__('Pessoa Jurídica')) ?></option>
                            <option value="pf"><?= h(__('Pessoa Física')) ?></option>
                        </select>
                        <i class="fas fa-chevron-down cli-crm-filter-card-chev" aria-hidden="true"></i>
                    </span>
                </label>
                <label class="cli-crm-filter-card">
                    <span class="cli-crm-filter-card-lbl"><?= h(__('Vendedor')) ?></span>
                    <span class="cli-crm-filter-card-box">
                        <select class="cli-crm-select" id="cli-filter-vendedor" aria-label="<?= h(__('Vendedor')) ?>">
                            <option value="" selected><?= h(__('Todos os vendedores')) ?></option>
                            <?php foreach ($cliVendedores as $vid => $vname) : ?>
                            <option value="<?= (int)$vid ?>"><?= h((string)$vname) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down cli-crm-filter-card-chev" aria-hidden="true"></i>
                    </span>
                </label>
            </div>
        </div>

        <div class="cli-crm-chips" role="group" aria-label="<?= h(__('Filtros rápidos')) ?>">
            <button type="button" class="cli-crm-chip cli-crm-chip--teal" data-chip="top-receita"><span class="cli-crm-chip-ic" aria-hidden="true">★</span> <?= h(__('Top 10 receita')) ?></button>
            <button type="button" class="cli-crm-chip cli-crm-chip--blue" data-chip="novos"><span class="cli-crm-chip-ic" aria-hidden="true">🆕</span> <?= h(__('Novos clientes')) ?></button>
            <button type="button" class="cli-crm-chip cli-crm-chip--red" data-chip="atraso"><span class="cli-crm-chip-ic" aria-hidden="true">⏰</span> <?= h(__('Em atraso')) ?></button>
            <button type="button" class="cli-crm-chip cli-crm-chip--rose" data-chip="sem-contato"><span class="cli-crm-chip-ic" aria-hidden="true">📞</span> <?= h(__('Sem contato 30d')) ?></button>
            <button type="button" class="cli-crm-chip cli-crm-chip--indigo" data-chip="vip"><span class="cli-crm-chip-ic" aria-hidden="true">💎</span> <?= h(__('Clientes VIP')) ?></button>
            <button type="button" class="cli-crm-chip cli-crm-chip--orange" data-chip="aniversariantes"><span class="cli-crm-chip-ic" aria-hidden="true">🎂</span> <?= h(__('Aniversariantes')) ?></button>
        </div>

        <div class="cli-table-wrap">
            <div class="cli-table-card">
                <table class="cli-table cli-table--crm" id="tableClientes">
                    <thead>
                        <tr>
                            <th><?= h(__('Código')) ?></th>
                            <th><?= h(__('Cliente')) ?></th>
                            <th><?= h(__('CNPJ/CPF')) ?></th>
                            <th><?= h(__('Segmento')) ?></th>
                            <th><?= h(__('Cidade')) ?></th>
                            <th class="cli-col-num"><?= h(__('Receita 12M')) ?></th>
                            <th class="cli-col-num"><?= h(__('A receber')) ?></th>
                            <th><?= h(__('Status')) ?></th>
                            <th><?= h(__('Última compra')) ?></th>
                            <th class="cli-col-act"><?= h(__('Ações')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $idxRow = 0; foreach ($cliRows as $row) :
                            $reg = $row['entity'];
                            $seg = $row['segmento'];
                            $rec12 = (float)($row['receita12'] ?? 0);
                            $aRec = (float)($row['a_receber'] ?? 0);
                            $url = $this->Url->build(['controller' => 'Clientes', 'action' => 'edit', $reg->id]);
                            $stClass = (string)($row['status_class'] ?? 'on');
                            $avTone = (string)($seg['tone'] ?? 'teal');
                        ?>
                        <tr<?= cliRowDataAttrs($reg) ?>
                            data-cli-status="<?= h((string)$row['status_key']) ?>"
                            data-cli-tipo="<?= h((string)$row['tipo_key']) ?>"
                            data-cli-segmento="<?= h((string)$row['segmento_slug']) ?>"
                            data-cli-vendedor="<?= (int)($row['vendedor_id'] ?? 0) ?>"
                            data-cli-receita="<?= (float)$rec12 ?>"
                            data-cli-atraso="<?= (int)($row['atraso'] ?? 0) ?>"
                            data-cli-vip="<?= (int)($row['vip'] ?? 0) ?>"
                            data-cli-novo="<?= (int)($row['novo_mes'] ?? 0) ?>"
                            data-cli-aniv="<?= (int)($row['aniversariante'] ?? 0) ?>"
                            data-cli-top10="<?= (int)($row['top_receita'] ?? 0) ?>"
                            data-cli-sem-contato="<?= (int)($row['sem_contato'] ?? 0) ?>"
                            data-cli-edit-url="<?= h($url) ?>"
                            data-cli-ord="<?= (int)$idxRow ?>"
                            role="button"
                            tabindex="0">
                            <td class="cli-td-code"><span class="cli-code-badge" translate="no"><?= h((string)$row['codigo']) ?></span></td>
                            <td>
                                <div class="cli-td-name">
                                    <div class="cli-av cli-av--<?= h($avTone) ?>"><?= cliInitials((string)$row['nome']) ?></div>
                                    <div class="cli-td-name-text">
                                        <span class="cli-name-main"><?= h((string)$row['nome']) ?></span>
                                        <?php if (!empty($row['subline'])) : ?>
                                        <span class="cli-name-sub"><?= h((string)$row['subline']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="cli-td-doc"><?= h(formatCnpjCpf((string)$row['doc'])) ?></td>
                            <td class="cli-td-seg">
                                <span class="cli-seg-pill cli-seg-pill--<?= h((string)$seg['tone']) ?>"><?= h((string)$seg['short']) ?></span>
                            </td>
                            <td class="cli-td-city"><?= (string)$row['cidade'] !== '' ? h((string)$row['cidade']) : '—' ?></td>
                            <td class="cli-td-num"><?= $rec12 > 0 ? h($this->Number->currency($rec12, 'BRL')) : '—' ?></td>
                            <td class="cli-td-num"><?= $aRec > 0 ? h($this->Number->currency($aRec, 'BRL')) : '—' ?></td>
                            <td>
                                <span class="cli-status-badge cli-status-badge--<?= h($stClass) ?>">
                                    <?php if ($stClass === 'vip') : ?><i class="fas fa-star" aria-hidden="true"></i> <?php endif; ?>
                                    <?php if ($stClass === 'warn') : ?><i class="far fa-clock" aria-hidden="true"></i> <?php endif; ?>
                                    <?= h((string)$row['status_label']) ?>
                                </span>
                            </td>
                            <td class="cli-td-muted"><?= h((string)$row['ultima']) ?></td>
                            <td class="cli-td-act" onclick="event.stopPropagation()">
                                <?= $this->Html->link(
                                    '<i class="fas fa-pen" aria-hidden="true"></i>',
                                    ['controller' => 'Clientes', 'action' => 'edit', $reg->id],
                                    ['class' => 'cli-btn-edit', 'escape' => false, 'title' => __('Editar cliente'), 'data-turbo' => 'false']
                                ) ?>
                                <?php if (isset($role) && (int)$role === 0 && (int)$reg->inativo === 0) : ?>
                                <?= $this->Html->link(
                                    '<i class="fas fa-user-slash" aria-hidden="true"></i>',
                                    ['controller' => 'Clientes', 'action' => 'inativar', $reg->id],
                                    ['class' => 'cli-btn-inativar', 'escape' => false, 'title' => __('Inativar cliente'), 'confirm' => __('Confirma inativar este cliente no portal e no ERP?'), 'data-turbo' => 'false']
                                ) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php $idxRow++; endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<script>
(function () {
    if (typeof window.jQuery === 'undefined') {
        return;
    }
    var $ = window.jQuery;
    $(document).ready(function () {
        var statusFilter = '';
        var type = 'pj';
        var typeAll = true;
        var segmentoFilter = '';
        var vendedorFilter = '';
        var activeChip = '';

        function rowVisible($row) {
            if (statusFilter !== '' && $row.attr('data-cli-status') !== statusFilter) {
                return false;
            }
            if (!typeAll && $row.attr('data-cli-tipo') !== type) {
                return false;
            }
            if (segmentoFilter !== '' && $row.attr('data-cli-segmento') !== segmentoFilter) {
                return false;
            }
            if (vendedorFilter !== '' && String($row.attr('data-cli-vendedor') || '') !== String(vendedorFilter)) {
                return false;
            }
            if (activeChip === 'atraso' && $row.attr('data-cli-atraso') !== '1') {
                return false;
            }
            if (activeChip === 'vip' && $row.attr('data-cli-vip') !== '1') {
                return false;
            }
            if (activeChip === 'novos' && $row.attr('data-cli-novo') !== '1') {
                return false;
            }
            if (activeChip === 'aniversariantes' && $row.attr('data-cli-aniv') !== '1') {
                return false;
            }
            if (activeChip === 'sem-contato' && $row.attr('data-cli-sem-contato') !== '1') {
                return false;
            }
            if (activeChip === 'top-receita' && $row.attr('data-cli-top10') !== '1') {
                return false;
            }
            return true;
        }

        var dtStub = { draw: function () {}, search: function () {} };
        var table = dtStub;

        function redrawTable() {
            if (table && typeof table.draw === 'function') {
                table.draw();
            }
        }

        $('.cli-root').on('click', '#tableClientes tbody tr[data-cli-edit-url]', function (e) {
            if ($(e.target).closest('a, button, input, select, textarea').length) {
                return;
            }
            var u = this.getAttribute('data-cli-edit-url');
            if (u) {
                window.location.href = u;
            }
        });

        $('.cli-kpi[data-kpi="ativos"], .cli-kpi[data-kpi="bloqueados"]').on('click', function () {
            var k = $(this).data('kpi');
            statusFilter = k === 'bloqueados' ? 'inativos' : 'ativos';
            $('#cli-filter-status').val(statusFilter);
            $('.cli-kpi').removeClass('active');
            $(this).addClass('active');
            redrawTable();
        });

        $('.cli-kpi[data-kpi="aniversariantes"]').on('click', function () {
            activeChip = activeChip === 'aniversariantes' ? '' : 'aniversariantes';
            $('.cli-crm-chip').removeClass('active');
            if (activeChip) {
                $('.cli-crm-chip[data-chip="aniversariantes"]').addClass('active');
            }
            redrawTable();
        });

        $('#cli-filter-status').on('change', function () {
            statusFilter = $(this).val();
            redrawTable();
        });

        $('#cli-filter-segmento').on('change', function () {
            segmentoFilter = $(this).val();
            redrawTable();
        });

        $('#cli-filter-tipo').on('change', function () {
            var v = $(this).val();
            if (v === '') {
                typeAll = true;
            } else {
                typeAll = false;
                type = v === 'pf' ? 'pf' : 'pj';
            }
            redrawTable();
        });

        $('#cli-filter-vendedor').on('change', function () {
            vendedorFilter = $(this).val();
            redrawTable();
        });

        $('.cli-crm-chip').on('click', function () {
            var chip = $(this).data('chip');
            if (activeChip === chip) {
                activeChip = '';
                $('.cli-crm-chip').removeClass('active');
            } else {
                activeChip = chip;
                $('.cli-crm-chip').removeClass('active');
                $(this).addClass('active');
            }
            redrawTable();
        });

        function normalizeAccent(s) {
            if (!s) return '';
            try {
                return s.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            } catch (e) {
                return s;
            }
        }

        function detectQueryType(raw) {
            var s = (raw || '').trim();
            if (!s) return { type: 'empty' };
            if (s.indexOf('@') >= 0) {
                return { type: 'email', value: s.toLowerCase().replace(/\s/g, '') };
            }
            var digits = s.replace(/\D/g, '');
            var onlyDocChars = /^[\d\s.\-\/\(\)]+$/u.test(s);
            if (digits.length >= 3 && onlyDocChars) {
                return { type: 'doc', digits: digits };
            }
            var lowered = normalizeAccent(s.toLowerCase());
            var words = lowered.split(/\s+/).filter(function (w) { return w.length > 0; });
            return { type: 'nome', words: words };
        }

        function rowMatches($row, q) {
            if (!rowVisible($row)) {
                return false;
            }
            var doc = $row.attr('data-cli-doc') || '';
            var email = $row.attr('data-cli-email') || '';
            var text = normalizeAccent(($row.attr('data-cli-text') || '').toLowerCase());
            if (q.type === 'empty') return true;
            if (q.type === 'email') {
                return email.indexOf(q.value) !== -1;
            }
            if (q.type === 'doc') {
                if (!doc) return false;
                return doc.indexOf(q.digits) !== -1;
            }
            if (q.type === 'nome') {
                if (q.words.length === 0) return true;
                for (var i = 0; i < q.words.length; i++) {
                    if (text.indexOf(q.words[i]) === -1) return false;
                }
                return true;
            }
            return true;
        }

        function rowRelevanceRank($row, q) {
            var ord = parseInt($row.attr('data-cli-ord') || '0', 10);
            var doc = $row.attr('data-cli-doc') || '';
            var email = $row.attr('data-cli-email') || '';
            var text = normalizeAccent(($row.attr('data-cli-text') || '').toLowerCase());
            var primary = normalizeAccent(($row.attr('data-cli-primary') || '').toLowerCase());
            var textLen = Math.min(text.length || 0, 9999);

            function pack(tier, lenKey) {
                var lk = Math.min(lenKey || 0, 9999);
                return tier * 10000000 + lk * 1000 + ord;
            }

            if (q.type === 'empty') {
                return ord;
            }
            if (q.type === 'email') {
                var v = q.value;
                if (email === v) return pack(1, textLen);
                if (email.indexOf(v) === 0) return pack(2, textLen);
                return pack(3, textLen);
            }
            if (q.type === 'doc') {
                var d = q.digits;
                if (!doc) return pack(99, 9999);
                if (doc === d) return pack(1, doc.length);
                if (doc.indexOf(d) === 0) return pack(2, doc.length);
                return pack(3, doc.length);
            }
            if (q.type === 'nome') {
                var words = q.words;
                if (words.length === 0) return ord;
                var phrase = words.join(' ');
                if (text === phrase || primary === phrase) return pack(1, textLen);
                if (primary.indexOf(phrase) === 0) return pack(2, textLen);
                if (primary.indexOf(words[0]) === 0) return pack(3, textLen);
                if (text.indexOf(phrase) === 0) return pack(4, textLen);
                return pack(5, textLen);
            }
            return pack(50, textLen);
        }

        var dtOpts = {
            pageLength: <?= $pagelength ?? 25 ?>,
            order: [[0, 'asc']],
            columnDefs: [
                { targets: '_all', orderable: true }
            ],
            language: {
                sLengthMenu: 'Mostrar _MENU_ registros',
                sZeroRecords: 'Nenhum registro encontrado',
                sEmptyTable: 'Nenhum dado disponível',
                sInfo: 'Mostrando _START_ a _END_ de _TOTAL_',
                sInfoEmpty: 'Nenhum registro',
                sInfoFiltered: '(filtrado de _MAX_)',
                oPaginate: { sFirst: '<<', sLast: '>>', sNext: '>', sPrevious: '<' }
            },
            dom: 'rt<"cli-table-footer cli-dt-bottom w-100 d-flex justify-content-between align-items-center flex-wrap"lip>'
        };

        if (typeof $.fn.dataTable !== 'undefined') {
            try {
                $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                    var api = new $.fn.dataTable.Api(settings);
                    var row = api.row(dataIndex).node();
                    if (!row) return true;
                    var q = detectQueryType($('#cli-search').val());
                    return rowMatches($(row), q);
                });
                table = $('#tableClientes').DataTable(dtOpts);
            } catch (err) {
                table = dtStub;
            }
        }

        function updateSearchModeUi() {
            var $inp = $('#cli-search');
            var $mode = $('#cli-search-mode');
            var q = detectQueryType($inp.val());
            if (q.type === 'empty') {
                $mode.text('');
                return;
            }
            if (q.type === 'email') {
                $mode.text('E-mail');
                return;
            }
            if (q.type === 'doc') {
                $mode.text(q.digits.length === 11 ? 'CPF' : 'CNPJ/CPF');
                return;
            }
            $mode.text('Nome');
        }

        $('#cli-search').on('keyup input', function () {
            updateSearchModeUi();
            redrawTable();
        });
        updateSearchModeUi();

        if (typeof filters !== 'undefined') {
            $('#cli-search').val(filters);
            updateSearchModeUi();
            redrawTable();
        }
    });
})();
</script>
