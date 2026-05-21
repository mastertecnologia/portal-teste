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
    $receitaPorCliente = $crm['receita_por_cliente'] ?? [];
    $aReceberPorCliente = $crm['a_receber_por_cliente'] ?? [];
    $barTones = ['teal', 'blue', 'navy', 'orange', 'wine'];
?>
<?= $this->element('pgm_premium_css', ['name' => 'clientes-premium']) ?>
<?= $this->element('pgm_premium_css', ['name' => 'clientes-layout-unificado']) ?>

<div class="col-md-12 p-0">
<div class="cli-root cli-layout-unificado cli-crm-lista">

    <header class="cli-crm-page-head">
        <div class="cli-crm-page-head-text">
            <p class="cli-crm-eyebrow"><?= h(__('Módulo comercial')) ?></p>
            <h1 class="cli-crm-h1"><?= h(__('Clientes')) ?></h1>
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
            <div class="cli-kpi-sub"><?= h(__('na carteira')) ?></div>
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
        <div class="cli-kpi cli-kpi--rose" data-kpi="bloqueados">
            <div class="cli-kpi-label"><?= h(__('Bloqueados')) ?></div>
            <div class="cli-kpi-val"><?= (int)($crm['bloqueados'] ?? $cntInativos) ?></div>
            <div class="cli-kpi-sub"><?= h(__('restrição interna')) ?></div>
        </div>
        <div class="cli-kpi cli-kpi--purple" data-kpi="aniversariantes">
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
            <div class="cli-crm-seg-grid">
                <?php
                $segTones = ['teal', 'blue', 'rose', 'orange', 'purple'];
                foreach ($segmentos as $si => $seg) :
                    $tone = $segTones[$si] ?? 'teal';
                ?>
                <div class="cli-crm-seg-tile cli-crm-seg-tile--<?= h($tone) ?>">
                    <span class="cli-crm-seg-n"><?= (int)$seg['count'] ?></span>
                    <span class="cli-crm-seg-l"><?= h((string)$seg['label']) ?></span>
                    <span class="cli-crm-seg-p"><?= (int)$seg['pct'] ?>% <?= h(__('da carteira')) ?></span>
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
        <div class="cli-crm-filters">
            <div class="cli-crm-search-wide">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                    <circle cx="6.5" cy="6.5" r="4.5" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M10.5 10.5L14 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <input type="text" id="cli-search" placeholder="<?= h(__('Buscar por nome, CNPJ/CPF, e-mail, telefone...')) ?>" autocomplete="off" inputmode="text" aria-describedby="cli-search-mode" />
                <span class="cli-search-mode" id="cli-search-mode"></span>
            </div>
            <select class="cli-crm-select" id="cli-filter-status" aria-label="<?= h(__('Status')) ?>">
                <option value=""><?= h(__('Todos os status')) ?></option>
                <option value="ativos"><?= h(__('Ativos')) ?></option>
                <option value="inativos"><?= h(__('Inativos')) ?></option>
            </select>
            <select class="cli-crm-select" id="cli-filter-tipo" aria-label="<?= h(__('Tipo')) ?>">
                <option value=""><?= h(__('PJ + PF')) ?></option>
                <option value="pj"><?= h(__('Pessoa Jurídica')) ?></option>
                <option value="pf"><?= h(__('Pessoa Física')) ?></option>
            </select>
        </div>

        <div class="cli-crm-chips" role="group" aria-label="<?= h(__('Filtros rápidos')) ?>">
            <button type="button" class="cli-crm-chip" data-chip="top-receita"><?= h(__('Top 10 receita')) ?></button>
            <button type="button" class="cli-crm-chip" data-chip="novos"><?= h(__('Novos clientes')) ?></button>
            <button type="button" class="cli-crm-chip" data-chip="atraso"><?= h(__('Em atraso')) ?></button>
            <button type="button" class="cli-crm-chip" data-chip="sem-contato"><?= h(__('Sem contato 30d')) ?></button>
            <button type="button" class="cli-crm-chip" data-chip="vip"><?= h(__('Clientes VIP')) ?></button>
            <button type="button" class="cli-crm-chip" data-chip="aniversariantes"><?= h(__('Aniversariantes')) ?></button>
        </div>

        <div class="cli-filter-bar cli-filter-bar--crm" id="cli-filter-bar">
            <div class="cli-pill-group" id="cli-status-pills">
                <button type="button" class="cli-pill active" data-status="ativos">
                    <i class="fas fa-circle" style="font-size:6px;" aria-hidden="true"></i> <?= h(__('Ativos')) ?>
                    <span class="cnt"><?= $cntAtivos ?></span>
                </button>
                <button type="button" class="cli-pill" data-status="inativos">
                    <i class="fas fa-circle cli-pill-dot--danger" style="font-size:6px;" aria-hidden="true"></i> <?= h(__('Inativos')) ?>
                    <span class="cnt"><?= $cntInativos ?></span>
                </button>
            </div>
            <div class="cli-filter-divider"></div>
            <div class="cli-pill-group" id="cli-type-pills">
                <button type="button" class="cli-pill active" data-type="pj">
                    <i class="fas fa-building" style="font-size:10px;" aria-hidden="true"></i> <?= h(__('Pessoa Jurídica')) ?>
                    <span class="cnt" id="cnt-pj"><?= $cntAPJ ?></span>
                </button>
                <button type="button" class="cli-pill" data-type="pf">
                    <i class="fas fa-user" style="font-size:10px;" aria-hidden="true"></i> <?= h(__('Pessoa Física')) ?>
                    <span class="cnt" id="cnt-pf"><?= $cntAPF ?></span>
                </button>
            </div>
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
                        <?php $idxRow = 0; foreach (($clientesLista ?? []) as $reg) :
                            $isPj = (int)$reg->tipo === (int)C_ClientesTipoJuridica;
                            $statusKey = (int)$reg->inativo === 1 ? 'inativos' : 'ativos';
                            $tipoKey = $isPj ? 'pj' : 'pf';
                            $nome = $isPj ? ($reg->razaosocial ?? '') : ($reg->nome ?? '');
                            $doc = $isPj ? ($reg->cnpj ?? '') : ($reg->cpf ?? '');
                            $cidadeNome = '';
                            if (!empty($reg->cidade) && !empty($reg->cidade->nome)) {
                                $cidadeNome = (string)$reg->cidade->nome;
                            }
                            $cid = (int)$reg->id;
                            $rec12 = isset($receitaPorCliente[$cid]) ? (float)$receitaPorCliente[$cid] : null;
                            $aRec = isset($aReceberPorCliente[$cid]) ? (float)$aReceberPorCliente[$cid] : null;
                            $url = $this->Url->build(['controller' => 'Clientes', 'action' => 'edit', $reg->id]);
                            $codigo = trim((string)($reg->public_code ?? ''));
                            if ($codigo === '') {
                                $codigo = 'P' . str_pad((string)$reg->id, 8, '0', STR_PAD_LEFT);
                            }
                            $ultima = '—';
                            if (!empty($reg->membrodesde) && $reg->membrodesde instanceof \DateTimeInterface) {
                                $ultima = $reg->membrodesde->format('d/m/Y');
                            }
                        ?>
                        <tr<?= cliRowDataAttrs($reg) ?>
                            data-cli-status="<?= h($statusKey) ?>"
                            data-cli-tipo="<?= h($tipoKey) ?>"
                            data-cli-receita="<?= $rec12 !== null ? (float)$rec12 : 0 ?>"
                            data-cli-atraso="<?= ($aRec !== null && $aRec > 0) ? '1' : '0' ?>"
                            data-cli-edit-url="<?= h($url) ?>"
                            data-cli-ord="<?= (int)$idxRow ?>"
                            role="button"
                            tabindex="0">
                            <td class="cli-td-code"><span translate="no"><?= h($codigo) ?></span></td>
                            <td>
                                <div class="cli-td-name">
                                    <div class="cli-av"><?= cliInitials($nome) ?></div>
                                    <span class="cli-name-main"><?= h($nome) ?></span>
                                </div>
                            </td>
                            <td class="cli-td-doc"><?= h(formatCnpjCpf($doc)) ?></td>
                            <td class="cli-td-seg"><?= $isPj ? h(__('Pessoa Jurídica')) : h(__('Pessoa Física')) ?></td>
                            <td class="cli-td-city"><?= $cidadeNome !== '' ? h($cidadeNome) : '—' ?></td>
                            <td class="cli-td-num"><?= $rec12 !== null && $rec12 > 0 ? h($this->Number->currency($rec12, 'BRL')) : '—' ?></td>
                            <td class="cli-td-num"><?= $aRec !== null && $aRec > 0 ? h($this->Number->currency($aRec, 'BRL')) : '—' ?></td>
                            <td>
                                <?php if ((int)$reg->inativo === 1) : ?>
                                    <span class="cli-status-badge cli-status-badge--off"><?= h(__('Inativo')) ?></span>
                                <?php else : ?>
                                    <span class="cli-status-badge cli-status-badge--on"><?= h(__('Ativo')) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="cli-td-muted"><?= h($ultima) ?></td>
                            <td class="cli-td-arrow" onclick="event.stopPropagation()">
                                <?php if (isset($role) && (int)$role === 0 && (int)$reg->inativo === 0) : ?>
                                <?= $this->Html->link(
                                    '<i class="fas fa-user-slash" title="' . h(__('Inativar cliente')) . '"></i>',
                                    ['controller' => 'Clientes', 'action' => 'inativar', $reg->id],
                                    ['class' => 'cli-btn-inativar', 'escape' => false, 'confirm' => __('Confirma inativar este cliente no portal e no ERP?'), 'data-turbo' => 'false']
                                ) ?>
                                <?php endif; ?>
                                <i class="fas fa-chevron-right" aria-hidden="true"></i>
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
        var status = 'ativos';
        var type = 'pj';
        var typeAll = false;
        var activeChip = '';

        var counts = {
            ativos:   { pj: <?= $cntAPJ ?>, pf: <?= $cntAPF ?> },
            inativos: { pj: <?= $cntIPJ ?>, pf: <?= $cntIPF ?> }
        };

        function rowVisible($row) {
            if ($row.attr('data-cli-status') !== status) {
                return false;
            }
            if (!typeAll && $row.attr('data-cli-tipo') !== type) {
                return false;
            }
            if (activeChip === 'atraso' && $row.attr('data-cli-atraso') !== '1') {
                return false;
            }
            if (activeChip === 'top-receita') {
                var r = parseFloat($row.attr('data-cli-receita') || '0');
                if (!(r > 0)) {
                    return false;
                }
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

        function syncSelectsFromPills() {
            $('#cli-filter-status').val(status === 'ativos' ? 'ativos' : (status === 'inativos' ? 'inativos' : ''));
            $('#cli-filter-tipo').val(type === 'pj' ? 'pj' : (type === 'pf' ? 'pf' : ''));
        }

        function updateTypePills() {
            $('#cli-type-pills .cli-pill').removeClass('active');
            $('#cli-type-pills .cli-pill[data-type="' + type + '"]').addClass('active');
            var c = counts[status] || { pj: 0, pf: 0 };
            $('#cnt-pj').text(c.pj);
            $('#cnt-pf').text(c.pf);
        }

        function updateStatusPills() {
            $('#cli-status-pills .cli-pill').removeClass('active');
            $('#cli-status-pills .cli-pill[data-status="' + status + '"]').addClass('active');
            updateTypePills();
            syncSelectsFromPills();
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
            status = k === 'bloqueados' ? 'inativos' : 'ativos';
            type = 'pj';
            $('.cli-kpi').removeClass('active');
            $(this).addClass('active');
            updateStatusPills();
            redrawTable();
        });

        $('#cli-status-pills .cli-pill').on('click', function () {
            status = $(this).data('status');
            type = 'pj';
            typeAll = false;
            updateStatusPills();
            redrawTable();
        });

        $('#cli-type-pills .cli-pill').on('click', function () {
            type = $(this).data('type');
            typeAll = false;
            $('#cli-filter-tipo').val(type);
            updateTypePills();
            redrawTable();
        });

        $('#cli-filter-status').on('change', function () {
            var v = $(this).val();
            status = v === 'inativos' ? 'inativos' : 'ativos';
            type = 'pj';
            updateStatusPills();
            redrawTable();
        });

        $('#cli-filter-tipo').on('change', function () {
            var v = $(this).val();
            if (v === '') {
                typeAll = true;
            } else {
                typeAll = false;
                type = v === 'pf' ? 'pf' : 'pj';
                updateTypePills();
            }
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
        updateStatusPills();

        if (typeof filters !== 'undefined') {
            $('#cli-search').val(filters);
            updateSearchModeUi();
            redrawTable();
        }
    });
})();
</script>
