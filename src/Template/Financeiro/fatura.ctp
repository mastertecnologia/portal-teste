<?php
/**
 * Financeiro — Detalhe da fatura (lançamento)
 */
use Cake\Routing\Router;

$this->Breadcrumbs->add('Início', ['controller' => 'Users', 'action' => 'dashboard']);
$this->Breadcrumbs->add('Financeiro', ['controller' => 'Financeiro', 'action' => 'index']);
$this->Breadcrumbs->add('Contas a Receber', ['controller' => 'Financeiro', 'action' => 'contasReceber']);
$this->Breadcrumbs->add('Detalhe');

$statusMap = [
	'aberto'    => ['label' => 'Aberto',    'class' => 'badge-warning'],
	'recebido'  => ['label' => 'Recebido',  'class' => 'badge-success'],
	'pago'      => ['label' => 'Pago',      'class' => 'badge-success'],
	'vencido'   => ['label' => 'Vencido',   'class' => 'badge-danger'],
	'cancelado' => ['label' => 'Cancelado', 'class' => 'badge-secondary'],
];

$nomeCli = '—';
if (!empty($lancamento->cliente)) {
	$nomeCli = ($lancamento->cliente->tipo == 1)
		? ($lancamento->cliente->nome ?? '—')
		: ($lancamento->cliente->razaosocial ?? '—');
}
$st = $statusMap[$lancamento->status] ?? ['label' => $lancamento->status, 'class' => 'badge-secondary'];
$hoje = date('Y-m-d');
$vencido = $lancamento->status === 'aberto' && $lancamento->data_vencimento
	&& $lancamento->data_vencimento->format('Y-m-d') < $hoje;
$autor = $lancamento->user ?? $lancamento->users ?? null;
$nomeAutor = $autor ? trim((string)($autor->name ?? '') ?: (string)($autor->username ?? '')) : '—';

$historicoFatura = $historicoFatura ?? [];
$auditoriaFatura = $auditoriaFatura ?? [];
$anexosLista = !empty($lancamento->financeiro_lancamento_anexos)
	? $lancamento->financeiro_lancamento_anexos
	: [];
$rotuloAudFin = function ($act) {
	$map = [
		'registrarrecebimento' => 'Recebimento registrado',
		'adicionaranexofatura' => 'Anexo enviado',
		'removeranexofatura' => 'Anexo removido',
		'gerarlancamento' => 'Lançamento gerado (faturamento)',
	];
	$key = strtolower((string)$act);

	return $map[$key] ?? h((string)$act);
};
$fat = $lancamento->faturamento ?? null;
$itensDoc = ($fat && !empty($fat->faturamento_itens)) ? $fat->faturamento_itens : [];
?>
<style>
.cr-root { font-family:'DM Sans',sans-serif; }
.cr-topbar { display:flex; align-items:center; justify-content:space-between; padding:16px 24px 12px; border-bottom:1px solid rgba(255,255,255,.07); flex-wrap:wrap; gap:10px; }
.cr-h1 { font-size:18px; font-weight:600; color:#e6edf3; }
.cr-body { padding:20px 24px; }
.cr-card { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; padding:18px 20px; margin-bottom:16px; }
.cr-card-title { font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:#7d8590; font-weight:600; margin-bottom:14px; }
.cr-tbl { width:100%; border-collapse:collapse; font-size:13px; }
.cr-tbl th { width:200px; color:#7d8590; font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:8px 12px 8px 0; border-bottom:1px solid rgba(255,255,255,.06); text-align:left; vertical-align:top; }
.cr-tbl td { padding:8px 0; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; }
.cr-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; }
.cr-badge.badge-warning  { background:rgba(255,193,7,.15); color:#ffc107; }
.cr-badge.badge-success  { background:rgba(63,185,80,.13); color:#3fb950; }
.cr-badge.badge-danger   { background:rgba(248,81,73,.13); color:#f85149; }
.cr-badge.badge-secondary{ background:rgba(255,255,255,.08); color:#9ca3af; }
.cr-principal { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px; margin-bottom:20px; }
.cr-principal-box { background:#161b22; border:1px solid rgba(255,255,255,.07); border-radius:10px; padding:14px 16px; }
.cr-principal-label { font-size:10px; text-transform:uppercase; letter-spacing:.08em; color:#7d8590; font-weight:600; margin-bottom:6px; }
.cr-principal-val { font-size:22px; font-weight:700; color:#e6edf3; line-height:1.15; }
.cr-principal-val.sm { font-size:15px; font-weight:600; }
.cr-items table { width:100%; border-collapse:collapse; font-size:13px; }
.cr-items th { color:#7d8590; font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:6px 10px; border-bottom:1px solid rgba(255,255,255,.07); text-align:left; }
.cr-items td { padding:9px 10px; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; }
.cr-empty { text-align:center; padding:28px; color:#7d8590; font-size:13px; }
.cr-items-total { text-align:right;font-size:14px;font-weight:700;color:#e6edf3;margin-top:6px;padding:0 10px 8px; }
.cr-hist-tbl { width:100%; border-collapse:collapse; font-size:13px; }
.cr-hist-tbl thead th { color:#7d8590; font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:8px 10px 8px 0; border-bottom:1px solid rgba(255,255,255,.08); text-align:left; }
.cr-hist-tbl tbody td { padding:8px 10px 8px 0; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; vertical-align:top; }
.cr-hist-dt { width:150px; white-space:nowrap; color:#7d8590; font-size:12px; }
.cr-layout-maincols { display:block; }
.cr-col-main { min-width:0; }
.cr-col-side { min-width:0; }
@media (min-width: 992px) {
	.cr-layout-maincols { display:grid; grid-template-columns:minmax(0,1fr) minmax(260px,32%); gap:16px; align-items:start; }
	.cr-col-side { position:sticky; top:12px; }
}
.cr-side-hint { font-size:12px; color:#7d8590; line-height:1.45; margin-bottom:12px; }
.cr-anexo-form { display:flex; flex-wrap:wrap; align-items:flex-end; gap:8px; margin-bottom:14px; }
.cr-anexo-form .form-group { margin-bottom:0; flex:1; min-width:160px; }
.cr-anexo-tbl { width:100%; border-collapse:collapse; font-size:12px; }
.cr-anexo-tbl th { color:#7d8590; font-size:10px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:6px 8px; border-bottom:1px solid rgba(255,255,255,.08); text-align:left; }
.cr-anexo-tbl td { padding:8px; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; vertical-align:middle; }
.cr-anexo-actions { white-space:nowrap; text-align:right; }
.cr-audit-tbl { width:100%; border-collapse:collapse; font-size:12px; }
.cr-audit-tbl thead th { color:#7d8590; font-size:10px; text-transform:uppercase; letter-spacing:.05em; font-weight:600; padding:6px 8px 6px 0; border-bottom:1px solid rgba(255,255,255,.08); text-align:left; }
.cr-audit-tbl tbody td { padding:6px 8px 6px 0; border-bottom:1px solid rgba(255,255,255,.04); color:#c9d1d9; vertical-align:top; }
.cr-audit-dt { white-space:nowrap; color:#7d8590; width:52px; }
.cr-audit-hr { white-space:nowrap; color:#7d8590; width:44px; }
.cr-obs { color:#c9d1d9; font-size:13px; line-height:1.5; }
.cr-h1-ico { color:#5cdbc0; margin-right:8px; }
.cr-h1-id { color:#7d8590; font-weight:400; }
.cr-principal-val--accent { color:#5cdbc0; }
.cr-status-wrap { margin-top:4px; }
.cr-empty--pb12 { padding-bottom:12px; }
.cr-items-val-unico { text-align:right; font-size:15px; font-weight:700; color:#5cdbc0; padding:0 10px 8px; }
.cr-items th.cr-num, .cr-items td.cr-num { text-align:right; }
.cr-discount-line { text-align:right; font-size:12px; color:#7d8590; margin-top:8px; padding-right:10px; }
.cr-empty--p18 { padding:18px; }
.cr-anexo-scroll { margin:0 -4px; }
.cr-anexo-user { font-size:11px; color:#9ca3af; max-width:120px; }
.cr-anexo-when { font-size:11px; color:#7d8590; white-space:nowrap; }
</style>

<div class="cr-root">
	<div class="cr-topbar">
		<div class="cr-h1"><i class="fas fa-file-invoice-dollar cr-h1-ico"></i><?= h($title) ?> <small class="cr-h1-id">#<?= (int)$lancamento->id ?></small></div>
		<div class="d-flex flex-wrap align-items-center pgm-gap-6">
			<?= $this->Html->link('<i class="fas fa-download"></i> CSV', ['action' => 'exportarFatura', $lancamento->id], ['class' => 'btn btn-default btn-sm', 'escape' => false, 'title' => 'Exportar resumo em CSV (UTF-8)']) ?>
			<?= $this->Html->link('<i class="fas fa-file-pdf"></i> PDF', ['action' => 'exportarFaturaPdf', $lancamento->id], ['class' => 'btn btn-default btn-sm', 'escape' => false, 'title' => 'Baixar detalhe em PDF']) ?>
			<?php if ($lancamento->status === 'aberto') : ?>
			<button type="button" class="btn btn-pgm btn-pgm-salvar btn-sm btn-receber-fatura" data-id="<?= (int)$lancamento->id ?>" title="Marcar como recebido">
				<i class="fas fa-check"></i> Marcar pagamento
			</button>
			<?php endif; ?>
			<?= $this->Html->link('<i class="fas fa-arrow-left"></i> Contas a Receber', ['action' => 'contasReceber'], ['class' => 'btn btn-default btn-sm', 'escape' => false]) ?>
			<?php if (!empty($lancamento->faturamento)) : ?>
			<?= $this->Html->link('<i class="fas fa-external-link-alt"></i> Documento faturamento', ['controller' => 'Faturamento', 'action' => 'view', $lancamento->faturamento->id], ['class' => 'btn btn-pgm btn-pgm-situacao btn-sm', 'escape' => false, 'target' => '_blank']) ?>
			<?php endif; ?>
		</div>
	</div>

	<div class="cr-body">
		<div class="cr-principal">
			<div class="cr-principal-box">
				<div class="cr-principal-label">Valor</div>
				<div class="cr-principal-val cr-principal-val--accent">R$ <?= number_format((float)$lancamento->valor, 2, ',', '.') ?></div>
			</div>
			<div class="cr-principal-box">
				<div class="cr-principal-label">Vencimento</div>
				<div class="cr-principal-val sm"><?= $lancamento->data_vencimento ? $lancamento->data_vencimento->format('d/m/Y') : '—' ?></div>
			</div>
			<div class="cr-principal-box">
				<div class="cr-principal-label">Status</div>
				<div class="cr-principal-val sm cr-status-wrap">
					<?php if ($vencido) : ?>
						<span class="cr-badge badge-danger">Vencido</span>
					<?php else : ?>
						<span class="cr-badge <?= h($st['class']) ?>"><?= h($st['label']) ?></span>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="cr-layout-maincols">
		<div class="cr-col-main">

		<div class="cr-card">
			<div class="cr-card-title">Dados complementares</div>
			<table class="cr-tbl">
				<tbody>
					<tr>
						<th>Descrição</th>
						<td><?= h($lancamento->descricao) ?></td>
					</tr>
					<tr>
						<th>Cliente</th>
						<td><?= h($nomeCli) ?></td>
					</tr>
					<tr>
						<th>Tipo</th>
						<td><?= h($lancamento->tipo) ?></td>
					</tr>
					<tr>
						<th>Data lançamento</th>
						<td><?= $lancamento->data_lancamento ? $lancamento->data_lancamento->format('d/m/Y') : '—' ?></td>
					</tr>
					<tr>
						<th>Recebimento</th>
						<td><?= $lancamento->data_recebimento ? $lancamento->data_recebimento->format('d/m/Y') : '—' ?></td>
					</tr>
					<tr>
						<th>Registrado por</th>
						<td><?= h($nomeAutor) ?></td>
					</tr>
					<?php if (!empty($lancamento->faturamento)) : ?>
					<tr>
						<th>Faturamento</th>
						<td>
							<?= $this->Html->link(
								h($lancamento->faturamento->numero ?? '#' . $lancamento->faturamento->id),
								['controller' => 'Faturamento', 'action' => 'view', $lancamento->faturamento->id],
								['target' => '_blank']
							) ?>
							<?php if (isset($lancamento->faturamento->valor_total)) : ?>
								<span class="text-muted"> — R$ <?= number_format((float)$lancamento->faturamento->valor_total, 2, ',', '.') ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<div class="cr-card cr-items">
			<div class="cr-card-title">Itens</div>
			<?php if (empty($fat)) : ?>
				<div class="cr-empty">Sem documento de faturamento vinculado. Os itens aparecem quando houver vínculo com um faturamento.</div>
			<?php elseif (empty($itensDoc)) : ?>
				<div class="cr-empty cr-empty--pb12">Documento sem linhas de item — valor único do faturamento.</div>
				<div class="cr-items-val-unico">
					R$ <?= number_format((float)($fat->valor_total ?? 0), 2, ',', '.') ?>
				</div>
			<?php else : ?>
				<table>
					<thead>
						<tr>
							<th>Descrição</th>
							<th class="cr-num">Qtd</th>
							<th class="cr-num">Vlr unit.</th>
							<th class="cr-num">Total</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($itensDoc as $item) : ?>
						<tr>
							<td><?= h($item->descricao) ?></td>
							<td class="cr-num"><?= number_format((float)$item->quantidade, 2, ',', '.') ?></td>
							<td class="cr-num">R$ <?= number_format((float)$item->valor_unitario, 2, ',', '.') ?></td>
							<td class="cr-num"><strong>R$ <?= number_format((float)$item->valor_total, 2, ',', '.') ?></strong></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php if (!empty($fat->valor_desconto) && (float)$fat->valor_desconto > 0) : ?>
				<div class="cr-discount-line">
					Desconto: R$ <?= number_format((float)$fat->valor_desconto, 2, ',', '.') ?>
				</div>
				<?php endif; ?>
				<div class="cr-items-total">Total: R$ <?= number_format((float)($fat->valor_total ?? 0), 2, ',', '.') ?></div>
			<?php endif; ?>
		</div>

		<div class="cr-card">
			<div class="cr-card-title">Histórico</div>
			<?php if (empty($historicoFatura)) : ?>
				<div class="cr-empty">Nenhum evento registrado.</div>
			<?php else : ?>
				<table class="cr-hist-tbl">
					<thead>
						<tr>
							<th>Data/Hora</th>
							<th>Ocorrência</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($historicoFatura as $ev) :
							$dtEv = $ev['dt'] ?? null;
							$dtStr = ($dtEv instanceof \DateTimeInterface)
								? $dtEv->format('d/m/Y H:i')
								: (string)($dtEv ?? '—');
						?>
						<tr>
							<td class="cr-hist-dt"><?= h($dtStr) ?></td>
							<td><?= h($ev['texto'] ?? '') ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<?php if (!empty($lancamento->observacoes)) : ?>
		<div class="cr-card">
			<div class="cr-card-title">Observações</div>
			<div class="cr-obs"><?= nl2br(h($lancamento->observacoes)) ?></div>
		</div>
		<?php endif; ?>

		</div>

		<div class="cr-col-side">
		<div class="cr-card">
			<div class="cr-card-title">Anexos</div>
			<p class="cr-side-hint mb-0">Comprovantes e arquivos vinculados a este lançamento (armazenados com segurança por empresa).</p>
			<?= $this->Form->create(null, [
				'url' => ['action' => 'adicionarAnexoFatura', $lancamento->id],
				'type' => 'file',
				'class' => 'cr-anexo-form',
			]) ?>
			<div class="form-group">
				<?= $this->Form->control('anexo', [
					'type' => 'file',
					'label' => false,
					'class' => 'form-control form-control-sm',
					'accept' => '*/*',
				]) ?>
			</div>
			<?= $this->Form->button('<i class="fas fa-upload"></i> Enviar', [
				'type' => 'submit',
				'class' => 'btn btn-pgm btn-pgm-salvar btn-sm',
				'escape' => false,
			]) ?>
			<?= $this->Form->end() ?>

			<?php if (empty($anexosLista)) : ?>
				<div class="cr-empty cr-empty--p18">Nenhum anexo neste lançamento.</div>
			<?php else : ?>
				<div class="table-responsive cr-anexo-scroll">
					<table class="cr-anexo-tbl">
						<thead>
							<tr>
								<th>Arquivo</th>
								<th>Por</th>
								<th>Quando</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($anexosLista as $ax) :
								$ux = $ax->user ?? $ax->users ?? null;
								$nomeUx = $ux ? trim((string)($ux->name ?? '') ?: (string)($ux->username ?? '')) : '—';
								$dtAx = !empty($ax->created) && $ax->created instanceof \DateTimeInterface
									? $ax->created->format('d/m/Y H:i')
									: '—';
							?>
							<tr>
								<td>
									<?= $this->Html->link(
										h($ax->nome_original ?? $ax->arquivo),
										['action' => 'baixarAnexoFatura', $ax->id],
										['escape' => false, 'title' => 'Baixar']
									) ?>
								</td>
								<td class="text-truncate cr-anexo-user" title="<?= h($nomeUx) ?>"><?= h($nomeUx) ?></td>
								<td class="cr-anexo-when"><?= h($dtAx) ?></td>
								<td class="cr-anexo-actions">
									<?= $this->Html->link('<i class="fas fa-download"></i>', ['action' => 'baixarAnexoFatura', $ax->id], [
										'class' => 'btn btn-default btn-xs m-r-5',
										'escape' => false,
										'title' => 'Baixar',
									]) ?>
									<?= $this->Form->postLink('<i class="fas fa-trash-alt"></i>', ['action' => 'removerAnexoFatura', $ax->id], [
										'class' => 'btn btn-default btn-xs',
										'escape' => false,
										'confirm' => __('Remover este anexo?'),
										'title' => 'Remover',
									]) ?>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>

		<div class="cr-card">
			<div class="cr-card-title">Auditoria</div>
			<p class="cr-side-hint mb-0">Ações registradas no módulo Financeiro para este lançamento (usuário e data).</p>
			<?php if (empty($auditoriaFatura)) : ?>
				<div class="cr-empty cr-empty--p18">Nenhum registro de auditoria para este item.</div>
			<?php else : ?>
				<table class="cr-audit-tbl">
					<thead>
						<tr>
							<th class="cr-audit-dt">Data</th>
							<th class="cr-audit-hr">Hora</th>
							<th>Usuário</th>
							<th>Ação</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($auditoriaFatura as $aud) :
							$u = $aud->user ?? $aud->users ?? null;
							$nomeUser = $u ? trim((string)($u->name ?? '') ?: (string)($u->username ?? '')) : '—';
						?>
						<tr>
							<td class="cr-audit-dt"><?= h((string)($aud->data ?? '—')) ?></td>
							<td class="cr-audit-hr"><?= h((string)($aud->hora ?? '—')) ?></td>
							<td><?= h($nomeUser) ?></td>
							<td><?= $rotuloAudFin($aud->action ?? '') ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		</div>

		</div>
	</div>
</div>

<script>
$(function() {
	$(document).on('click', '.btn-receber-fatura', function() {
		var id = $(this).data('id');
		bootbox.confirm('Confirmar recebimento deste lançamento?', function(r) {
			if (!r) return;
			$.ajax({
				type: 'POST',
				url: "<?= Router::url(['controller' => 'Financeiro', 'action' => 'registrarRecebimento']) ?>/" + id,
				data: { data_recebimento: new Date().toISOString().slice(0, 10) },
				success: function(res) {
					if (res.ok) location.reload();
					else bootbox.alert('Erro: ' + (res.msg || 'Tente novamente.'));
				},
				error: function() { bootbox.alert('Erro ao registrar recebimento.'); }
			});
		});
	});
});
</script>
