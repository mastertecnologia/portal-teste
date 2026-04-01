<?php
    /**
     * Clientes — lista (esta tela): action index.
     * Telas relacionadas no mesmo controller: add (novo), edit (cadastro), view (detalhe), search (pesquisa legada por query string).
     */
    use Cake\Routing\Router;
    $this->append('css', $this->element('pgm_premium_css', ['name' => 'clientes-premium']));

    function Mask($mask, $str) {
        $str = str_replace(" ", "", $str);
        for ($i = 0; $i < strlen($str); $i++) $mask[strpos($mask, "#")] = $str[$i];
        return $mask;
    }

    $cntAPJ = count($clientesAtivosPJ);
    $cntAPF = count($clientesAtivosPF);
    $cntIPJ = count($clientesInativosPJ);
    $cntIPF = count($clientesInativosPF);
    $cntTotal = $cntAPJ + $cntAPF + $cntIPJ + $cntIPF;

    // Initials helper
    function cliInitials($str) {
        $parts = preg_split('/\s+/', trim($str), -1, PREG_SPLIT_NO_EMPTY);
        $a = strtoupper(substr($parts[0] ?? 'C', 0, 1));
        $b = strtoupper(substr($parts[1] ?? '', 0, 1));
        return $a . $b;
    }

    /** Atributos data-* para busca client-side (nome, CNPJ/CPF sem máscara, e-mail). */
    function cliRowDataAttrs($reg) {
        $isPj = (int)$reg->tipo === (int)C_ClientesTipoJuridica;
        $docDigits = preg_replace('/\D/', '', (string)($isPj ? ($reg->cnpj ?? '') : ($reg->cpf ?? '')));
        $emailLower = mb_strtolower(trim((string)($reg->email ?? '')), 'UTF-8');
        $parts = $isPj ? [trim((string)($reg->razaosocial ?? '')), trim((string)($reg->nomefantasia ?? ''))] : [trim((string)($reg->nome ?? ''))];
        $textBlob = mb_strtolower(trim(preg_replace('/\s+/', ' ', implode(' ', array_filter($parts)))), 'UTF-8');
        if ($emailLower !== '') {
            $textBlob = trim($textBlob . ' ' . $emailLower);
        }
        return ' data-cli-doc="' . h($docDigits) . '" data-cli-email="' . h($emailLower) . '" data-cli-text="' . h($textBlob) . '"';
    }
?>

<div class="col-md-12 p-0">
<div class="cli-root">

    <!-- ── Topbar ─────────────────────────────────────────── -->
    <div class="cli-topbar">
        <div class="cli-topbar-left">
            <div class="cli-eyebrow">Módulo comercial</div>
            <h1 class="cli-h1">Clientes</h1>
            <div class="cli-subtitle">Empresas e pessoas vinculadas ao ERP</div>
        </div>
        <div class="cli-topbar-right">
            <?= $this->Html->link(
                '<i class="fas fa-plus"></i> Novo cliente',
                ['action' => 'add'],
                ['class' => 'btn-cli-primary', 'escape' => false]
            ) ?>
        </div>
    </div>

    <!-- ── KPI Strip ──────────────────────────────────────── -->
    <div class="cli-kpi-strip">
        <div class="cli-kpi active" data-kpi="ativos-pj">
            <div class="cli-kpi-label">Ativos · PJ</div>
            <div class="cli-kpi-val teal"><?= $cntAPJ ?></div>
            <div class="cli-kpi-sub">Pessoa Jurídica</div>
        </div>
        <div class="cli-kpi" data-kpi="ativos-pf">
            <div class="cli-kpi-label">Ativos · PF</div>
            <div class="cli-kpi-val teal"><?= $cntAPF ?></div>
            <div class="cli-kpi-sub">Pessoa Física</div>
        </div>
        <div class="cli-kpi" data-kpi="inativos">
            <div class="cli-kpi-label">Inativos</div>
            <div class="cli-kpi-val muted"><?= $cntIPJ + $cntIPF ?></div>
            <div class="cli-kpi-sub">PJ + PF</div>
        </div>
        <div class="cli-kpi" data-kpi="total">
            <div class="cli-kpi-label">Total cadastros</div>
            <div class="cli-kpi-val"><?= $cntTotal ?></div>
            <div class="cli-kpi-sub">Todos</div>
        </div>
    </div>

    <!-- ── Table area ─────────────────────────────────────── -->
    <div class="cli-table-wrap">

        <!-- Status toggle (Ativos / Inativos) -->
        <div class="cli-filter-bar" id="cli-filter-bar">
            <div class="cli-pill-group" id="cli-status-pills">
                <button class="cli-pill active" data-status="ativos">
                    <i class="fas fa-circle" style="font-size:6px;"></i> Ativos
                    <span class="cnt"><?= $cntAPJ + $cntAPF ?></span>
                </button>
                <button class="cli-pill" data-status="inativos">
                    <i class="fas fa-circle" style="font-size:6px;color:#f85149;"></i> Inativos
                    <span class="cnt"><?= $cntIPJ + $cntIPF ?></span>
                </button>
            </div>
            <div class="cli-filter-divider"></div>
            <div class="cli-pill-group" id="cli-type-pills">
                <button class="cli-pill active" data-type="pj">
                    <i class="fas fa-building" style="font-size:10px;"></i> Pessoa Jurídica
                    <span class="cnt" id="cnt-pj"><?= $cntAPJ ?></span>
                </button>
                <button class="cli-pill" data-type="pf">
                    <i class="fas fa-user" style="font-size:10px;"></i> Pessoa Física
                    <span class="cnt" id="cnt-pf"><?= $cntAPF ?></span>
                </button>
            </div>
            <div class="cli-filter-divider"></div>
            <div class="cli-filter-search" id="cli-filter-search">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                    <circle cx="6.5" cy="6.5" r="4.5" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M10.5 10.5L14 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <input type="text" id="cli-search" placeholder="Nome, CNPJ, CPF ou e-mail" autocomplete="off" inputmode="text" aria-describedby="cli-search-mode" />
                <span class="cli-search-mode" id="cli-search-mode" title="Formato detectado para a busca"></span>
            </div>
        </div>

        <!-- ── Tables ─── -->

        <!-- ATIVOS PJ -->
        <div class="cli-table-panel active" id="panel-ativos-pj">
            <div class="cli-table-card">
                <table class="cli-table" id="tableAtivosPJ">
                    <thead>
                        <tr>
                            <th style="width:42%">Razão Social</th>
                            <th style="width:18%">CNPJ</th>
                            <th style="width:24%">E-mail</th>
                            <th style="width:14%">Telefone</th>
                            <th style="width:2%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientesAtivosPJ as $reg): ?>
                        <?php $url = $this->Url->build(['controller' => 'Clientes', 'action' => 'edit', $reg->id]); ?>
                        <tr<?= cliRowDataAttrs($reg) ?> data-cli-edit-url="<?= h($url) ?>" style="cursor:pointer" role="button" tabindex="0">
                            <td>
                                <div class="cli-td-name">
                                    <div class="cli-av"><?= cliInitials($reg->razaosocial ?? '') ?></div>
                                    <span class="cli-name-main"><?= h($reg->razaosocial) ?></span>
                                </div>
                            </td>
                            <td class="cli-td-doc"><?= h(formatCnpjCpf($reg->cnpj)) ?></td>
                            <td class="cli-td-email"><?= h($reg->email) ?></td>
                            <td class="cli-td-phone">
                                <?php if (!empty($reg->fone)) echo h(Mask("(###) ####-####", $reg->fone)); ?>
                                <?php if (!empty($reg->fone2)) echo '<br>' . h(Mask("(###) #####-####", $reg->fone2)); ?>
                            </td>
                            <td class="cli-td-arrow">
                                <?php if (isset($role) && (int)$role === 0): ?>
                                <?= $this->Html->link(
                                    '<i class="fas fa-user-slash" title="Inativar cliente"></i>',
                                    ['controller' => 'Clientes', 'action' => 'inativar', $reg->id],
                                    ['class' => 'cli-btn-inativar', 'escape' => false, 'confirm' => 'Confirma inativar este cliente no portal e no ERP?', 'onclick' => 'event.stopPropagation();']
                                ) ?>
                                <?php endif; ?>
                                <span class="cli-td-arrow-chev" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ATIVOS PF -->
        <div class="cli-table-panel" id="panel-ativos-pf">
            <div class="cli-table-card">
                <table class="cli-table" id="tableAtivosPF">
                    <thead>
                        <tr>
                            <th style="width:42%">Nome</th>
                            <th style="width:18%">CPF</th>
                            <th style="width:24%">E-mail</th>
                            <th style="width:14%">Telefone</th>
                            <th style="width:2%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientesAtivosPF as $reg): ?>
                        <?php $url = $this->Url->build(['controller' => 'Clientes', 'action' => 'edit', $reg->id]); ?>
                        <tr<?= cliRowDataAttrs($reg) ?> data-cli-edit-url="<?= h($url) ?>" style="cursor:pointer" role="button" tabindex="0">
                            <td>
                                <div class="cli-td-name">
                                    <div class="cli-av"><?= cliInitials($reg->nome ?? '') ?></div>
                                    <span class="cli-name-main"><?= h($reg->nome) ?></span>
                                </div>
                            </td>
                            <td class="cli-td-doc"><?= h(formatCnpjCpf($reg->cpf)) ?></td>
                            <td class="cli-td-email"><?= h($reg->email) ?></td>
                            <td class="cli-td-phone">
                                <?php if (!empty($reg->fone)) echo h(Mask("(###) ####-####", $reg->fone)); ?>
                                <?php if (!empty($reg->fone2)) echo '<br>' . h(Mask("(###) #####-####", $reg->fone2)); ?>
                            </td>
                            <td class="cli-td-arrow">
                                <?php if (isset($role) && (int)$role === 0): ?>
                                <?= $this->Html->link(
                                    '<i class="fas fa-user-slash" title="Inativar cliente"></i>',
                                    ['controller' => 'Clientes', 'action' => 'inativar', $reg->id],
                                    ['class' => 'cli-btn-inativar', 'escape' => false, 'confirm' => 'Confirma inativar este cliente no portal e no ERP?', 'onclick' => 'event.stopPropagation();']
                                ) ?>
                                <?php endif; ?>
                                <span class="cli-td-arrow-chev" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- INATIVOS PJ -->
        <div class="cli-table-panel" id="panel-inativos-pj">
            <div class="cli-table-card">
                <table class="cli-table" id="tableInativosPJ">
                    <thead>
                        <tr>
                            <th style="width:38%">Razão Social</th>
                            <th style="width:16%">CNPJ</th>
                            <th style="width:22%">E-mail</th>
                            <th style="width:14%">Telefone</th>
                            <th style="width:10%">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientesInativosPJ as $reg): ?>
                        <?php $url = $this->Url->build(['controller' => 'Clientes', 'action' => 'edit', $reg->id]); ?>
                        <tr<?= cliRowDataAttrs($reg) ?> data-cli-edit-url="<?= h($url) ?>" style="cursor:pointer" role="button" tabindex="0">
                            <td>
                                <div class="cli-td-name">
                                    <div class="cli-av" style="background:rgba(248,81,73,.10);color:#f85149;"><?= cliInitials($reg->razaosocial ?? '') ?></div>
                                    <span class="cli-name-main"><?= h($reg->razaosocial) ?></span>
                                </div>
                            </td>
                            <td class="cli-td-doc"><?= h(formatCnpjCpf($reg->cnpj)) ?></td>
                            <td class="cli-td-email"><?= h($reg->email) ?></td>
                            <td class="cli-td-phone">
                                <?php if (!empty($reg->fone)) echo h(Mask("(###) ####-####", $reg->fone)); ?>
                                <?php if (!empty($reg->fone2)) echo '<br>' . h(Mask("(###) #####-####", $reg->fone2)); ?>
                            </td>
                            <td onclick="event.stopPropagation()">
                                <?= $this->Html->link(
                                    '<i class="fas fa-redo-alt"></i> Reativar',
                                    ['controller' => 'Clientes', 'action' => 'reativar', $reg->id],
                                    ['class' => 'cli-btn-reativar', 'escape' => false, 'confirm' => 'Confirmar reativação deste cliente?']
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- INATIVOS PF -->
        <div class="cli-table-panel" id="panel-inativos-pf">
            <div class="cli-table-card">
                <table class="cli-table" id="tableInativosPF">
                    <thead>
                        <tr>
                            <th style="width:38%">Nome</th>
                            <th style="width:16%">CPF</th>
                            <th style="width:22%">E-mail</th>
                            <th style="width:14%">Telefone</th>
                            <th style="width:10%">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientesInativosPF as $reg): ?>
                        <?php $url = $this->Url->build(['controller' => 'Clientes', 'action' => 'edit', $reg->id]); ?>
                        <tr<?= cliRowDataAttrs($reg) ?> data-cli-edit-url="<?= h($url) ?>" style="cursor:pointer" role="button" tabindex="0">
                            <td>
                                <div class="cli-td-name">
                                    <div class="cli-av" style="background:rgba(248,81,73,.10);color:#f85149;"><?= cliInitials($reg->nome ?? '') ?></div>
                                    <span class="cli-name-main"><?= h($reg->nome) ?></span>
                                </div>
                            </td>
                            <td class="cli-td-doc"><?= h(formatCnpjCpf($reg->cpf)) ?></td>
                            <td class="cli-td-email"><?= h($reg->email) ?></td>
                            <td class="cli-td-phone">
                                <?php if (!empty($reg->fone)) echo h(Mask("(###) ####-####", $reg->fone)); ?>
                                <?php if (!empty($reg->fone2)) echo '<br>' . h(Mask("(###) #####-####", $reg->fone2)); ?>
                            </td>
                            <td onclick="event.stopPropagation()">
                                <?= $this->Html->link(
                                    '<i class="fas fa-redo-alt"></i> Reativar',
                                    ['controller' => 'Clientes', 'action' => 'reativar', $reg->id],
                                    ['class' => 'cli-btn-reativar', 'escape' => false, 'confirm' => 'Confirmar reativação deste cliente?']
                                ) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /cli-table-wrap -->
</div><!-- /cli-root -->
</div>

<script>
(function () {
    if (typeof window.jQuery === 'undefined') {
        console.error('[Clientes] jQuery não encontrado. Verifique no DevTools → Network se /assets/node_modules/jquery/jquery-3.2.1.min.js retorna 200 e se o layout carrega scripts antes desta view.');
        return;
    }
    var $ = window.jQuery;
    $(document).ready(function () {

    // Navegação para edição — registrar ANTES do DataTables: se $.fn.dataTable ou o init falhar, o clique na linha ainda funciona (evita “erro JS silencioso” que quebra só parte da tela).
    $('.cli-root').on('click', 'table.cli-table tbody tr[data-cli-edit-url]', function(e) {
        if ($(e.target).closest('a, button, input, select, textarea').length) {
            return;
        }
        var u = this.getAttribute('data-cli-edit-url');
        if (u) {
            window.location.href = u;
        }
    });
    $('.cli-root').on('keydown', 'table.cli-table tbody tr[data-cli-edit-url]', function(e) {
        if (e.key !== 'Enter' && e.key !== ' ') {
            return;
        }
        if ($(e.target).is('a, button')) {
            return;
        }
        e.preventDefault();
        var u = this.getAttribute('data-cli-edit-url');
        if (u) {
            window.location.href = u;
        }
    });

    // State
    var status = 'ativos';
    var type = 'pj';

    var counts = {
        'ativos':   { pj: <?= $cntAPJ ?>, pf: <?= $cntAPF ?> },
        'inativos': { pj: <?= $cntIPJ ?>, pf: <?= $cntIPF ?> }
    };

    /** APIs mínimas quando DataTables não existe ou falha no init (evita TypeError em pills/busca). */
    var dtStub = { draw: function () {}, search: function () {} };
    var tables = {
        ativosPJ: dtStub,
        ativosPF: dtStub,
        inativosPJ: dtStub,
        inativosPF: dtStub
    };

    function activeTableKey() {
        return status === 'ativos'
            ? (type === 'pj' ? 'ativosPJ' : 'ativosPF')
            : (type === 'pj' ? 'inativosPJ' : 'inativosPF');
    }

    function redrawActiveTable() {
        var t = tables[activeTableKey()];
        if (t && typeof t.draw === 'function') {
            t.draw();
        }
    }

    function showPanel() {
        $('.cli-table-panel').removeClass('active');
        $('#panel-' + status + '-' + type).addClass('active');
        redrawActiveTable();
    }

    // KPI clicks
    $('.cli-kpi').on('click', function() {
        var kpi = $(this).data('kpi');
        if (kpi === 'ativos-pj')  { status = 'ativos';   type = 'pj'; }
        else if (kpi === 'ativos-pf')  { status = 'ativos';   type = 'pf'; }
        else if (kpi === 'inativos')   { status = 'inativos'; type = 'pj'; }
        else if (kpi === 'total')      { status = 'ativos';   type = 'pj'; }
        $('.cli-kpi').removeClass('active');
        $(this).addClass('active');
        updatePills();
        showPanel();
    });

    // Status pills
    $('#cli-status-pills .cli-pill').on('click', function() {
        status = $(this).data('status');
        $('#cli-status-pills .cli-pill').removeClass('active');
        $(this).addClass('active');
        // reset type to pj
        type = 'pj';
        updateTypePills();
        updatePills();
        showPanel();
    });

    // Type pills
    $('#cli-type-pills .cli-pill').on('click', function() {
        type = $(this).data('type');
        $('#cli-type-pills .cli-pill').removeClass('active');
        $(this).addClass('active');
        showPanel();
    });

    function updateTypePills() {
        $('#cli-type-pills .cli-pill').removeClass('active');
        $('#cli-type-pills .cli-pill[data-type="pj"]').addClass('active');
        var c = counts[status];
        $('#cnt-pj').text(c.pj);
        $('#cnt-pf').text(c.pf);
    }

    function updatePills() {
        updateTypePills();
    }

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
        var words = lowered.split(/\s+/).filter(function(w) { return w.length > 0; });
        return { type: 'nome', words: words };
    }

    function rowMatches($row, q) {
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

    var dtOpts = {
        "pageLength": <?= $pagelength ?? 25 ?>,
        "language": {
            "sLengthMenu":    "Mostrar _MENU_ registros",
            "sZeroRecords":   "Nenhum registro encontrado",
            "sEmptyTable":    "Nenhum dado disponível",
            "sInfo":          "Mostrando _START_ a _END_ de _TOTAL_",
            "sInfoEmpty":     "Nenhum registro",
            "sInfoFiltered":  "(filtrado de _MAX_)",
            "sSearch":        "Filtrar:",
            "sLoadingRecords":"Carregando...",
            "oPaginate": { "sFirst":"<<","sLast":">>","sNext":">","sPrevious":"<" }
        },
        "dom": '<"d-flex justify-content-between align-items-center"lf>rt<"d-flex justify-content-between align-items-center"ip>',
    };

    if (typeof $.fn.dataTable === 'undefined') {
        console.error('[Clientes] DataTables não disponível ($.fn.dataTable). Verifique /assets/node_modules/datatables/datatables.min.js (Network → 404?) e ordem: jQuery antes do DataTables.');
    } else {
        try {
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                var api = new $.fn.dataTable.Api(settings);
                var row = api.row(dataIndex).node();
                if (!row) return true;
                var q = detectQueryType($('#cli-search').val());
                return rowMatches($(row), q);
            });
            tables = {
                'ativosPJ':   $('#tableAtivosPJ').DataTable(dtOpts),
                'ativosPF':   $('#tableAtivosPF').DataTable(dtOpts),
                'inativosPJ': $('#tableInativosPJ').DataTable(dtOpts),
                'inativosPF': $('#tableInativosPF').DataTable(dtOpts)
            };
            $.each(tables, function(_, dt) {
                if (dt && typeof dt.search === 'function') {
                    dt.search('');
                }
            });
        } catch (err) {
            console.error('[Clientes] Erro ao inicializar DataTables:', err && err.message ? err.message : err);
            if (err && err.stack) {
                console.debug(err.stack);
            }
            tables = {
                ativosPJ: dtStub,
                ativosPF: dtStub,
                inativosPJ: dtStub,
                inativosPF: dtStub
            };
        }
    }

    function updateSearchModeUi() {
        var $inp = $('#cli-search');
        var $wrap = $('#cli-filter-search');
        var $mode = $('#cli-search-mode');
        var q = detectQueryType($inp.val());
        $wrap.removeClass('cli-mode-nome cli-mode-doc cli-mode-email');
        if (q.type === 'empty') {
            $mode.text('');
            $inp.attr('inputmode', 'text');
            $inp.removeAttr('maxlength');
            return;
        }
        if (q.type === 'email') {
            $mode.text('E-mail');
            $wrap.addClass('cli-mode-email');
            $inp.attr('inputmode', 'email');
            $inp.removeAttr('maxlength');
            return;
        }
        if (q.type === 'doc') {
            if (q.digits.length < 11) {
                $mode.text('CNPJ/CPF');
            } else if (q.digits.length === 11) {
                $mode.text('CPF');
            } else {
                $mode.text('CNPJ');
            }
            $wrap.addClass('cli-mode-doc');
            $inp.attr('inputmode', 'numeric');
            $inp.attr('maxlength', '20');
            return;
        }
        $mode.text('Nome');
        $wrap.addClass('cli-mode-nome');
        $inp.attr('inputmode', 'text');
        $inp.removeAttr('maxlength');
    }

    // Persist pagelength
    $('#tableAtivosPJ, #tableAtivosPF, #tableInativosPJ, #tableInativosPF').on('length.dt', function(e, s, len) {
        if (typeof pagelength === 'function') {
            pagelength(len);
        }
    });

    $('#cli-search').on('keyup input', function() {
        updateSearchModeUi();
        redrawActiveTable();
    });

    updateSearchModeUi();

    // Auto-apply filter if passed from another page
    if (typeof filters !== 'undefined') {
        $('#cli-search').val(filters);
        updateSearchModeUi();
        redrawActiveTable();
    }
    }); // ready
})(); // IIFE Clientes index
</script>
