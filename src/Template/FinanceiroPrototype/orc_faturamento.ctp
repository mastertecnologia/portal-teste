<?php
/**
 * Faturamento do orçamento — pg-orc-faturamento.
 *
 * @var \App\View\AppView $this
 */
$H = $this->ErpPrototype;
$cliente = (array)($cliente ?? []);
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			<?= $this->Html->link('← ' . __('Orçamentos'), ['controller' => 'OrcamentosPrototype', 'action' => 'lista'], ['style' => 'color:var(--teal);']) ?>
			› <span style="color:var(--teal);"><?= h(__('Faturamento')) ?></span>
		</div>
		<h1 style="font-size:20px;font-weight:600;margin:0;"><?= h(__('Faturamento do Orçamento #{0}', (int)($orcId ?? 0) > 0 ? (int)$orcId : (string)($fatNumero ?? ''))) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
			<?= h((string)($cliente['nome'] ?? '—')) ?>
			<?php if (!empty($data_emissao)) : ?> · <?= h($H->dt($data_emissao)) ?><?php endif; ?>
		</div>
	</div>
	<div style="display:flex;gap:8px;">
		<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'Faturamento', 'action' => 'index'], ['class' => 'btn btn-ghost']) ?>
		<?= $this->Html->link('🧾 ' . __('Emitir NF e gerar títulos'), (array)($emitir_url ?? ['controller' => 'Faturamento', 'action' => 'view', (int)($fatId ?? 0)]), ['class' => 'btn btn-primary', 'escape' => false]) ?>
	</div>
</div>

<?= $H->stepper((array)($steps ?? [])) ?>

<div class="alert-box alert-blue" style="margin:14px 0;">
	<strong>📋 <?= h(__('Geração de notas fiscais:')) ?></strong>
	<?= h(__('Os títulos serão criados conforme a condição de pagamento aprovada na proposta · integração fiscal via módulo NF-e/NFS-e.')) ?>
</div>

<div class="g2">
	<div class="card">
		<div class="sec-title"><?= h(__('Dados de faturamento')) ?></div>
		<div class="g2" style="margin-bottom:10px;">
			<div class="field"><label><?= h(__('Tomador / Cliente')) ?></label><input type="text" value="<?= h((string)($cliente['nome'] ?? '')) ?>" readonly style="background:var(--gray-100);"/></div>
			<div class="field"><label><?= h(__('CNPJ')) ?></label><input type="text" value="<?= h((string)($cliente['cnpj'] ?? '')) ?>" readonly style="background:var(--gray-100);"/></div>
		</div>
	</div>
	<div class="card">
		<div class="sec-title"><?= h(__('Configurações fiscais')) ?></div>
		<div style="background:var(--teal-light);padding:10px 12px;border-radius:var(--radius);font-size:12px;">
			<?= $this->Html->link(__('Abrir emissor fiscal →'), ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'nfe'], ['style' => 'color:var(--teal-dark);font-weight:600;']) ?>
		</div>
	</div>
</div>

<div class="card" style="margin-top:14px;">
	<div class="sec-title"><?= h(__('Itens a faturar — discriminação por documento fiscal')) ?></div>
	<div style="overflow-x:auto;">
		<table class="tbl">
			<thead><tr>
				<th style="width:60px;">NF</th>
				<th style="width:80px;"><?= h(__('Código')) ?></th>
				<th><?= h(__('Descrição')) ?></th>
				<th class="c" style="width:50px;"><?= h(__('Qtd')) ?></th>
				<th class="r" style="width:90px;"><?= h(__('V. unit')) ?></th>
				<th class="r" style="width:100px;"><?= h(__('Total')) ?></th>
			</tr></thead>
			<tbody>
				<?php if (($itens ?? []) === []) : ?>
					<tr><td colspan="6" style="padding:20px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum item cadastrado no faturamento.')) ?></td></tr>
				<?php else : foreach ($itens as $it) : ?>
					<tr>
						<td><?= $H->badge((string)$it['nf_tipo'], (string)$it['nf_badge']) ?></td>
						<td><code style="font-size:11px;"><?= h((string)$it['codigo']) ?></code></td>
						<td><?= h((string)$it['descricao']) ?></td>
						<td class="c"><?= h(number_format((float)$it['qtd'], 0, ',', '.')) ?></td>
						<td class="r"><?= h($H->brl((float)$it['unit'])) ?></td>
						<td class="r"><strong><?= h($H->brl((float)$it['total'])) ?></strong></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
	<div class="summary-grid" style="margin-top:14px;">
		<div class="summary-card"><div class="lbl">NF-e · <?= h(__('Produtos')) ?></div><div class="val" style="color:var(--blue);"><?= h($H->brl((float)($total_nfe ?? 0))) ?></div></div>
		<div class="summary-card"><div class="lbl">NFS-e · <?= h(__('Serviços')) ?></div><div class="val" style="color:var(--teal);"><?= h($H->brl((float)($total_nfse ?? 0))) ?></div></div>
		<div class="summary-card" style="background:linear-gradient(135deg,var(--teal-light),#fff);"><div class="lbl"><?= h(__('Total geral a faturar')) ?></div><div class="val" style="color:var(--teal-dark);"><?= h($H->brl((float)($valor_total ?? 0))) ?></div></div>
	</div>
</div>

<div style="margin-top:18px;display:flex;justify-content:flex-end;gap:8px;">
	<?= $this->Html->link(__('Cancelar'), ['controller' => 'Faturamento', 'action' => 'index'], ['class' => 'btn btn-ghost']) ?>
	<?= $this->Html->link('🧾 ' . __('Emitir NF-e + NFS-e e gerar títulos'), (array)($emitir_url ?? []), ['class' => 'btn btn-primary', 'escape' => false]) ?>
</div>
