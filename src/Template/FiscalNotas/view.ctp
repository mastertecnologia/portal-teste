<?php
$fpmCtrl = $this->request->getParam('controller');
$isEntrada = ($fpmCtrl === 'FiscalNotasEntrada');
$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Módulo Fiscal', ['controller' => 'Fiscal', 'action' => 'index']);
$this->Breadcrumbs->add($isEntrada ? 'Notas de entrada' : 'Notas de saída', ['controller' => $fpmCtrl, 'action' => 'index']);
$this->Breadcrumbs->add('Nota #' . (int)$nota->id);
echo $this->element('Fiscal/styles');

$editavel = in_array($nota->status, ['rascunho', 'rejeitada'], true);
$cancelavel = ($nota->status === 'autorizada');
$fiscalAmbienteProducao = $fiscalAmbienteProducao ?? false;
$podeManifestar = !empty($podeManifestar);
$tiposManifestacao = $tiposManifestacao ?? [];
$fiscalDfeRecebidoOrigem = $fiscalDfeRecebidoOrigem ?? null;
?>
<div class="fpm-wrap">
    <?php if ($fiscalAmbienteProducao) : ?>
    <div class="fpm-homolog-wrap" style="padding-top:12px;">
        <div class="fpm-alert fpm-alert-danger">
            <strong>Ambiente de produção SEFAZ.</strong> Esta nota será transmitida para ambiente real se emitida ou eventada.
        </div>
    </div>
    <?php endif; ?>
    <div class="fpm-topbar">
        <h1 class="fpm-h1"><i class="fas fa-file-invoice"></i>Nota #<?= (int)$nota->id ?></h1>
        <div class="fpm-actions">
            <?= $this->Html->link('Lista', ['controller' => $fpmCtrl, 'action' => 'index'], ['class' => 'btn btn-default btn-sm']) ?>
            <?php if ($editavel): ?>
                <?= $this->Html->link('Editar', ['controller' => $fpmCtrl, 'action' => 'edit', $nota->id], ['class' => 'btn btn-pgm btn-pgm-salvar btn-sm']) ?>
            <?php endif; ?>
            <?php if ($nota->status === 'autorizada'): ?>
                <?= $this->Html->link('DANFE', ['action' => 'danfe', $nota->id], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'target' => '_blank']) ?>
                <?= $this->Html->link('XML', ['action' => 'downloadXml', $nota->id], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm']) ?>
                <button type="button" class="btn btn-pgm btn-sm" onclick="document.getElementById('fpm-email-panel').style.display='block'" title="Enviar DANFE + XML por e-mail"><i class="fas fa-envelope"></i> E-mail</button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isEntrada && !empty($fiscalDfeRecebidoOrigem)) : ?>
    <div class="fpm-card mx-3 mb-2">
        <div class="fpm-card-title"><i class="fas fa-inbox"></i> Origem DF-e</div>
        <p class="fpm-muted mb-2" style="margin:0;">Esta nota foi gerada a partir da fila de documentos recebidos na Distribuição DF-e.</p>
        <div class="fpm-row">
            <?= $this->Html->link('XML guardado na fila', ['controller' => 'Fiscal', 'action' => 'dfeRecebidoXml', $fiscalDfeRecebidoOrigem->id], ['class' => 'btn btn-xs btn-pgm btn-pgm-situacao']) ?>
            <?= $this->Html->link('Ir para fila DF-e', ['controller' => 'Fiscal', 'action' => 'dfeRecebidos', '?' => ['status' => 'vinculado']], ['class' => 'btn btn-xs btn-default']) ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="fpm-card mx-3">
        <div class="fpm-row">
            <div class="fpm-field">
                <span class="fpm-muted">Status</span>
                <div><span class="fpm-badge ok"><?= h($statusList[$nota->status] ?? $nota->status) ?></span></div>
            </div>
            <div class="fpm-field">
                <span class="fpm-muted">Tipo</span>
                <div><?= (int)$nota->tipo_operacao === 0 ? 'Entrada' : 'Saída' ?></div>
            </div>
            <div class="fpm-field">
                <span class="fpm-muted">Modelo / Série / Número</span>
                <div><?= h($nota->modelo) ?> / <?= h((string)$nota->serie) ?> / <?= $nota->numero !== null ? h((string)$nota->numero) : '—' ?></div>
            </div>
            <div class="fpm-field">
                <span class="fpm-muted">Chave</span>
                <div style="word-break:break-all;font-size:12px;"><?= h($nota->chave_acesso ?: '—') ?></div>
            </div>
        </div>
        <div class="fpm-row mt-2">
            <div class="fpm-field">
                <span class="fpm-muted">Cliente</span>
                <div><?= h($nota->cliente ? ($nota->cliente->razaosocial ?: $nota->cliente->nome) : '—') ?></div>
            </div>
            <div class="fpm-field">
                <span class="fpm-muted">Natureza</span>
                <div><?= h($nota->natureza_operacao ?: '—') ?></div>
            </div>
            <div class="fpm-field">
                <span class="fpm-muted">Emissão</span>
                <div><?= $nota->data_emissao ? h($nota->data_emissao->format('d/m/Y H:i')) : '—' ?></div>
            </div>
            <div class="fpm-field">
                <span class="fpm-muted">Total</span>
                <div><strong>R$ <?= number_format((float)$nota->valor_total, 2, ',', '.') ?></strong></div>
            </div>
            <div class="fpm-field">
                <span class="fpm-muted">Registrado por</span>
                <div><?php
                    $u = $nota->user ?? null;
                    echo $u ? h($u->name) : ($nota->user_id ? '#' . (int)$nota->user_id : '—');
                ?></div>
            </div>
        </div>
        <?php if ($nota->motivo_rejeicao): ?>
            <div class="alert alert-warning mt-3 mb-0"><?= h($nota->motivo_rejeicao) ?></div>
        <?php endif; ?>
    </div>

    <div class="fpm-table-wrap">
        <div class="fpm-card-title">Itens</div>
        <table class="fpm-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descrição</th>
                    <th>CFOP</th>
                    <th>NCM</th>
                    <th>Qtd</th>
                    <th>V. unit.</th>
                    <th>Total</th>
                    <th>Séries</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($nota->fiscal_notas_itens as $it): ?>
                <tr>
                    <td><?= (int)$it->numero_item ?></td>
                    <td><?= h($it->descricao) ?></td>
                    <td><?= h($it->cfop) ?></td>
                    <td><?= h($it->ncm ?: '—') ?></td>
                    <td><?= h($it->quantidade) ?></td>
                    <td>R$ <?= number_format((float)$it->valor_unitario, 4, ',', '.') ?></td>
                    <td>R$ <?= number_format((float)$it->valor_total, 2, ',', '.') ?></td>
                    <td>
                        <?php
                        $sns = [];
                        if (!empty($it->fiscal_notas_itens_series)) {
                            foreach ($it->fiscal_notas_itens_series as $s) {
                                $sns[] = h($s->numero_serie);
                            }
                        }
                        echo $sns ? '<small>' . implode('<br>', $sns) . '</small>' : '—';
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="fpm-card mx-3">
        <div class="fpm-card-title">Pagamentos</div>
        <?php if (empty($nota->fiscal_notas_pagamentos)): ?>
            <p class="fpm-muted mb-0">Nenhum.</p>
        <?php else: ?>
        <ul class="mb-0 pl-3">
            <?php foreach ($nota->fiscal_notas_pagamentos as $pg): ?>
            <li><?= h($formasPagamento[$pg->forma_pagamento] ?? $pg->forma_pagamento) ?> — R$ <?= number_format((float)$pg->valor, 2, ',', '.') ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <?php if (!empty($nota->fiscal_notas_eventos)) : ?>
    <?php
    $fpmEvList = is_array($nota->fiscal_notas_eventos)
        ? $nota->fiscal_notas_eventos
        : iterator_to_array($nota->fiscal_notas_eventos);
    usort($fpmEvList, function ($a, $b) {
        $ta = ($a->created instanceof \DateTimeInterface) ? $a->created->getTimestamp() : 0;
        $tb = ($b->created instanceof \DateTimeInterface) ? $b->created->getTimestamp() : 0;
        return $tb <=> $ta;
    });
    $fpmEvTipo = [
        'cancelamento' => 'Cancelamento',
        'carta_correcao' => 'Carta de correção',
        'inutilizacao' => 'Inutilização',
        'manifestacao' => 'Manifestação destinatário',
    ];
    $fpmEvCodManifest = [
        '210200' => 'Confirmação',
        '210210' => 'Ciência',
        '210220' => 'Desconhecimento',
        '210240' => 'Não realizada',
    ];
    ?>
    <div class="fpm-table-wrap">
        <div class="fpm-card-title">Eventos SEFAZ</div>
        <table class="fpm-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Seq</th>
                    <th>Cód. evento</th>
                    <th>Status</th>
                    <th>Protocolo</th>
                    <th>Retorno</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fpmEvList as $ev): ?>
                <tr>
                    <td><?= $ev->created ? h($ev->created->format('d/m/Y H:i')) : '—' ?></td>
                    <td><?php
                        $tl = $fpmEvTipo[$ev->tipo_evento] ?? (string)$ev->tipo_evento;
                        if ($ev->tipo_evento === 'manifestacao' && !empty($ev->codigo_evento)) {
                            $tl .= ' — ' . ($fpmEvCodManifest[$ev->codigo_evento] ?? (string)$ev->codigo_evento);
                        }
                        echo h($tl);
                    ?></td>
                    <td><?= (int)$ev->sequencia ?></td>
                    <td><?= h($ev->codigo_evento ?: '—') ?></td>
                    <td><span class="fpm-badge <?= $ev->status === 'autorizado' ? 'ok' : 'muted' ?>"><?= h($ev->status ?: '—') ?></span></td>
                    <td><?= h($ev->protocolo ?: '—') ?></td>
                    <td><small><?= h(($ev->codigo_retorno ? '[' . $ev->codigo_retorno . '] ' : '') . (string)($ev->mensagem_retorno ?? '')) ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="fpm-footer px-3">
        <?php if ($editavel && !empty($fiscalAmbienteProducao)) : ?>
            <?= $this->Form->create(null, ['url' => ['action' => 'emitir', $nota->id], 'class' => 'fpm-emitir-prod']) ?>
            <div class="fpm-field mb-2">
                <label class="fpm-check-prod mb-0">
                    <?= $this->Form->checkbox('confirmar_producao', ['value' => '1', 'required' => true, 'id' => 'fpmConfirmProd']) ?>
                    <span>Confirmo transmissão em <strong>produção</strong> SEFAZ (NF-e com efeito fiscal).</span>
                </label>
            </div>
            <?= $this->Form->button('<i class="fas fa-paper-plane"></i> Transmitir', [
                'class' => 'btn btn-pgm btn-pgm-salvar',
                'escape' => false,
                'type' => 'submit',
            ]) ?>
            <?= $this->Form->end() ?>
        <?php elseif ($editavel): ?>
            <?= $this->Form->postLink('<i class="fas fa-paper-plane"></i> Transmitir', ['action' => 'emitir', $nota->id], [
                'class' => 'btn btn-pgm btn-pgm-salvar', 'escape' => false, 'confirm' => 'Enviar esta nota à SEFAZ?',
            ]) ?>
        <?php endif; ?>
        <?php if ($cancelavel): ?>
            <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#fpmModalCancel">Cancelar NF-e</button>
            <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#fpmModalCce">Carta de correção</button>
        <?php endif; ?>
        <?php if ($podeManifestar): ?>
            <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#fpmModalManifest">Manifestar destinatário</button>
        <?php endif; ?>
        <?php if ($editavel && $nota->status === 'rascunho'): ?>
            <?= $this->Form->postLink('Excluir rascunho', ['action' => 'delete', $nota->id], [
                'class' => 'btn btn-outline-danger btn-sm', 'confirm' => 'Excluir definitivamente?',
            ]) ?>
        <?php endif; ?>
    </div>

    <?php if ($nota->status === 'autorizada'): ?>
    <div id="fpm-email-panel" class="fpm-card mx-3 mt-2" style="display:none;">
        <div class="fpm-card-title"><i class="fas fa-envelope"></i> Enviar DANFE + XML por e-mail</div>
        <?= $this->Form->create(null, ['url' => ['controller' => $fpmCtrl, 'action' => 'enviarEmail', $nota->id]]) ?>
        <div class="fpm-row">
            <div class="fpm-field" style="flex:1;">
                <label>E-mail do destinatário</label>
                <input type="email" name="email_destinatario" class="form-control"
                       value="<?= h($nota->cliente->email ?? '') ?>"
                       placeholder="Deixe vazio para usar o e-mail do cliente" />
                <small class="fpm-muted">Se vazio, usa o e-mail cadastrado do cliente.</small>
            </div>
        </div>
        <div class="mt-2">
            <?= $this->Form->button('<i class="fas fa-paper-plane"></i> Enviar e-mail', [
                'class' => 'btn btn-pgm btn-pgm-salvar btn-sm',
                'escape' => false,
                'type' => 'submit',
            ]) ?>
            <button type="button" class="btn btn-default btn-sm" onclick="document.getElementById('fpm-email-panel').style.display='none'">Cancelar</button>
        </div>
        <?= $this->Form->end() ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($cancelavel): ?>
<div class="modal fade" id="fpmModalCancel" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <?= $this->Form->create(null, ['url' => ['action' => 'cancelar', $nota->id]]) ?>
            <div class="modal-header"><h5 class="modal-title">Cancelar NF-e</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <?php if (!empty($fiscalAmbienteProducao)) : ?>
                <div class="fpm-field mb-3">
                    <label class="fpm-check-prod mb-0">
                        <?= $this->Form->checkbox('confirmar_producao', ['value' => '1', 'required' => true, 'id' => 'fpmConfirmCancel']) ?>
                        <span>Confirmo o <strong>cancelamento</strong> em produção SEFAZ.</span>
                    </label>
                </div>
                <?php endif; ?>
                <label>Justificativa (mín. 15 caracteres)</label>
                <textarea name="justificativa" class="form-control" rows="3" required minlength="15"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
                <button type="submit" class="btn btn-danger">Confirmar cancelamento</button>
            </div>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
<div class="modal fade" id="fpmModalCce" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <?= $this->Form->create(null, ['url' => ['action' => 'cartaCorrecao', $nota->id]]) ?>
            <div class="modal-header"><h5 class="modal-title">Carta de correção</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <?php if (!empty($fiscalAmbienteProducao)) : ?>
                <div class="fpm-field mb-3">
                    <label class="fpm-check-prod mb-0">
                        <?= $this->Form->checkbox('confirmar_producao', ['value' => '1', 'required' => true, 'id' => 'fpmConfirmCce']) ?>
                        <span>Confirmo o envio da <strong>carta de correção</strong> em produção SEFAZ.</span>
                    </label>
                </div>
                <?php endif; ?>
                <label>Texto da correção (mín. 15 caracteres)</label>
                <textarea name="correcao" class="form-control" rows="3" required minlength="15"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
                <button type="submit" class="btn btn-pgm btn-pgm-salvar">Enviar CC-e</button>
            </div>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($podeManifestar): ?>
<div class="modal fade" id="fpmModalManifest" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <?= $this->Form->create(null, ['url' => ['controller' => $fpmCtrl, 'action' => 'manifestarDestinatario', $nota->id]]) ?>
            <div class="modal-header"><h5 class="modal-title">Manifestação do destinatário (SEFAZ)</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <?php if (!empty($fiscalAmbienteProducao)) : ?>
                <div class="fpm-field mb-3">
                    <label class="fpm-check-prod mb-0">
                        <?= $this->Form->checkbox('confirmar_producao', ['value' => '1', 'required' => true, 'id' => 'fpmConfirmManifest']) ?>
                        <span>Confirmo o envio da <strong>manifestação</strong> em produção SEFAZ.</span>
                    </label>
                </div>
                <?php endif; ?>
                <label>Tipo</label>
                <?= $this->Form->select('tipo_manifestacao', $tiposManifestacao, [
                    'class' => 'form-control',
                    'id' => 'fpmManifestTipo',
                    'required' => true,
                    'empty' => false,
                ]) ?>
                <label class="mt-2">Justificativa (obrigatória para desconhecimento / operação não realizada, mín. 15 caracteres)</label>
                <textarea name="justificativa_manifestacao" id="fpmManifestJust" class="form-control" rows="3" placeholder="Opcional para confirmação ou ciência"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
                <button type="submit" class="btn btn-pgm btn-pgm-salvar">Enviar manifestação</button>
            </div>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
<script>
(function() {
    var sel = document.getElementById('fpmManifestTipo');
    var j = document.getElementById('fpmManifestJust');
    if (!sel || !j) return;
    function sync() {
        var v = sel.value;
        var need = (v === 'desconhecimento' || v === 'nao_realizada');
        j.required = need;
    }
    sel.addEventListener('change', sync);
    sync();
})();
</script>
<?php endif; ?>
