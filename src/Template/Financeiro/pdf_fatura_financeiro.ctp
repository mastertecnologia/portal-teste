<?php
/** @var \App\Model\Entity\FinanceiroLancamento $lancamento */
/** @var array<int,array{dt:\DateTimeInterface|string,texto:string}> $historicoFatura */
$historicoFatura = $historicoFatura ?? [];
$statusMap = [
	'aberto' => 'Aberto',
	'recebido' => 'Recebido',
	'pago' => 'Pago',
	'vencido' => 'Vencido',
	'cancelado' => 'Cancelado',
];
$nomeCli = '—';
if (!empty($lancamento->cliente)) {
	$nomeCli = ($lancamento->cliente->tipo == 1)
		? (string)($lancamento->cliente->nome ?? '—')
		: (string)($lancamento->cliente->razaosocial ?? '—');
}
$hoje = date('Y-m-d');
$vencido = $lancamento->status === 'aberto' && $lancamento->data_vencimento
	&& $lancamento->data_vencimento->format('Y-m-d') < $hoje;
$statusTxt = $vencido ? 'Vencido' : ($statusMap[$lancamento->status] ?? (string)$lancamento->status);
$fat = $lancamento->faturamento ?? null;
$itensDoc = ($fat && !empty($fat->faturamento_itens)) ? $fat->faturamento_itens : [];
$autor = $lancamento->user ?? $lancamento->users ?? null;
$nomeAutor = $autor ? trim((string)($autor->name ?? '') ?: (string)($autor->username ?? '')) : '—';
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<style>
		body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
		h1 { font-size: 16px; margin: 0 0 6px; }
		h2 { font-size: 12px; margin: 16px 0 6px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
		.meta { margin: 0 0 12px; color: #444; }
		table { width: 100%; border-collapse: collapse; margin-top: 6px; }
		th, td { border: 1px solid #ccc; padding: 5px 7px; text-align: left; vertical-align: top; }
		th { background: #f0f0f0; font-weight: bold; }
		.num { text-align: right; }
		.total { font-weight: bold; text-align: right; margin-top: 6px; }
	</style>
</head>
<body>
	<h1>Detalhe da fatura — #<?= (int)$lancamento->id ?></h1>
	<div class="meta">Gerado em <?= h(date('d/m/Y H:i')) ?></div>

	<h2>Dados principais</h2>
	<table>
		<tbody>
			<tr><th style="width:32%">Valor</th><td>R$ <?= number_format((float)$lancamento->valor, 2, ',', '.') ?></td></tr>
			<tr><th>Vencimento</th><td><?= $lancamento->data_vencimento ? h($lancamento->data_vencimento->format('d/m/Y')) : '—' ?></td></tr>
			<tr><th>Status</th><td><?= h($statusTxt) ?></td></tr>
		</tbody>
	</table>

	<h2>Complementares</h2>
	<table>
		<tbody>
			<tr><th style="width:32%">Cliente</th><td><?= h($nomeCli) ?></td></tr>
			<tr><th>Descrição</th><td><?= h((string)$lancamento->descricao) ?></td></tr>
			<tr><th>Tipo</th><td><?= h((string)$lancamento->tipo) ?></td></tr>
			<tr><th>Data lançamento</th><td><?= $lancamento->data_lancamento ? h($lancamento->data_lancamento->format('d/m/Y')) : '—' ?></td></tr>
			<tr><th>Recebimento</th><td><?= $lancamento->data_recebimento ? h($lancamento->data_recebimento->format('d/m/Y')) : '—' ?></td></tr>
			<tr><th>Registrado por</th><td><?= h($nomeAutor) ?></td></tr>
			<?php if (!empty($fat)) : ?>
			<tr>
				<th>Faturamento</th>
				<td><?= h((string)($fat->numero ?? '#' . $fat->id)) ?><?php if (isset($fat->valor_total)) : ?> — R$ <?= number_format((float)$fat->valor_total, 2, ',', '.') ?><?php endif; ?></td>
			</tr>
			<?php endif; ?>
		</tbody>
	</table>

	<?php if (!empty($lancamento->observacoes)) : ?>
	<h2>Observações</h2>
	<p style="margin:4px 0;line-height:1.35"><?= nl2br(h((string)$lancamento->observacoes)) ?></p>
	<?php endif; ?>

	<h2>Itens</h2>
	<?php if (empty($fat)) : ?>
		<p style="color:#555">Sem documento de faturamento vinculado.</p>
	<?php elseif (empty($itensDoc)) : ?>
		<p style="color:#555">Documento sem linhas de item.</p>
		<div class="total">R$ <?= number_format((float)($fat->valor_total ?? 0), 2, ',', '.') ?></div>
	<?php else : ?>
		<table>
			<thead>
				<tr>
					<th>Descrição</th>
					<th class="num">Qtd</th>
					<th class="num">Vlr unit.</th>
					<th class="num">Total</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($itensDoc as $item) : ?>
				<tr>
					<td><?= h($item->descricao) ?></td>
					<td class="num"><?= number_format((float)$item->quantidade, 2, ',', '.') ?></td>
					<td class="num">R$ <?= number_format((float)$item->valor_unitario, 2, ',', '.') ?></td>
					<td class="num">R$ <?= number_format((float)$item->valor_total, 2, ',', '.') ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php if (!empty($fat->valor_desconto) && (float)$fat->valor_desconto > 0) : ?>
		<div style="text-align:right;font-size:10px;margin-top:4px">Desconto: R$ <?= number_format((float)$fat->valor_desconto, 2, ',', '.') ?></div>
		<?php endif; ?>
		<div class="total">Total: R$ <?= number_format((float)($fat->valor_total ?? 0), 2, ',', '.') ?></div>
	<?php endif; ?>

	<h2>Histórico</h2>
	<?php if (empty($historicoFatura)) : ?>
		<p style="color:#555">Nenhum evento registrado.</p>
	<?php else : ?>
		<table>
			<thead>
				<tr><th style="width:28%">Data/Hora</th><th>Ocorrência</th></tr>
			</thead>
			<tbody>
				<?php foreach ($historicoFatura as $ev) :
					$dtEv = $ev['dt'] ?? null;
					$dtStr = ($dtEv instanceof \DateTimeInterface)
						? $dtEv->format('d/m/Y H:i')
						: (string)($dtEv ?? '—');
				?>
				<tr>
					<td><?= h($dtStr) ?></td>
					<td><?= h($ev['texto'] ?? '') ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</body>
</html>
