<?php
/**
 * Remessa CNAB 240 — alinhado ao modal-remessa (pgm_erp_completo.html).
 *
 * @var \App\View\AppView $this
 * @var array<int,array<string,mixed>> $rmBancos
 * @var array<int,array<string,mixed>> $rmTitulos
 * @var array{banco_label:string,qtd_titulos:int,total:float,seq_arquivo:string} $rmKpi
 * @var array{banco_id:int,busca:string} $rmFiltros
 * @var array{data_hoje:string,nome_arquivo:string} $rmMeta
 */
$H = $this->ErpPrototype;
$urlLista = ['controller' => 'BancosPrototype', 'action' => 'lista'];
$urlRemessa = ['controller' => 'BancosPrototype', 'action' => 'view', 'remessa'];
$urlHistorico = ['controller' => 'FinanceiroBancos', 'action' => 'relacaoRemessas'];
$urlGerar = $this->Url->build(['controller' => 'Remessas', 'action' => 'gerarRemessa']);
$bancoId = (int)$rmFiltros['banco_id'];
$busca = (string)$rmFiltros['busca'];
?>
<div id="pg-remessa">
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			← <?= $this->Html->link(__('Bancos'), $urlLista, ['style' => 'color:var(--teal);text-decoration:none;']) ?>
			<span> › </span><span style="color:var(--teal);"><?= h(__('Remessa CNAB')) ?></span>
		</div>
		<h1 style="font-size:20px;font-weight:600;margin:0;">📤 <?= h(__('Gerar remessa CNAB 240')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);margin-top:2px;"><?= h(__('Selecione os títulos e gere o arquivo de remessa para o banco')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('📋 ' . __('Histórico'), $urlHistorico, ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('← ' . __('Voltar'), $urlLista, ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:14px;">
	<div class="stat" style="--sc:var(--blue);"><div class="stat-l"><?= h(__('Banco selecionado')) ?></div><div class="stat-n" style="font-size:14px;line-height:1.3;"><?= h($rmKpi['banco_label']) ?></div></div>
	<div class="stat" style="--sc:var(--teal);"><div class="stat-l"><?= h(__('Qtd. títulos')) ?></div><div class="stat-n"><?= (int)$rmKpi['qtd_titulos'] ?></div></div>
	<div class="stat" style="--sc:var(--teal-dark);"><div class="stat-l"><?= h(__('Total da remessa')) ?></div><div class="stat-n" style="font-size:16px;"><?= h($H->brl((float)$rmKpi['total'])) ?></div></div>
</div>

<?= $this->Form->create(null, ['type' => 'get', 'url' => $urlRemessa, 'style' => 'margin-bottom:14px;']) ?>
<div class="card" style="padding:14px 18px;">
	<div class="g2" style="align-items:end;">
		<div class="field" style="margin:0;">
			<label class="field-lbl"><?= h(__('Banco')) ?> *</label>
			<select name="banco_id" required onchange="this.form.submit()">
				<option value=""><?= h(__('-- Selecione um banco --')) ?></option>
				<?php foreach ($rmBancos as $b) : ?>
					<option value="<?= (int)$b['id'] ?>"<?= $bancoId === (int)$b['id'] ? ' selected' : '' ?>><?= h((string)$b['label']) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="field" style="margin:0;">
			<label class="field-lbl"><?= h(__('Buscar título')) ?></label>
			<input type="text" name="busca" value="<?= h($busca) ?>" placeholder="<?= h(__('Cliente, descrição...')) ?>"/>
		</div>
		<div style="display:flex;gap:8px;">
			<button type="submit" class="btn btn-primary btn-sm">🔍 <?= h(__('Consultar títulos')) ?></button>
			<?= $this->Html->link(__('Limpar'), $urlRemessa, ['class' => 'btn btn-ghost btn-sm']) ?>
		</div>
	</div>
</div>
<?= $this->Form->end() ?>

<?php if ($bancoId <= 0) : ?>
<div class="card" style="text-align:center;padding:40px 24px;color:var(--text-muted);">
	<div style="font-size:40px;margin-bottom:10px;opacity:.5;">📁</div>
	<?= h(__('Selecione um banco para visualizar os títulos da remessa.')) ?>
</div>
<?php else : ?>

<div class="alert-box alert-blue" style="margin-bottom:14px;">
	⚡ <strong><?= h(__('CNAB 240:')) ?></strong> <?= h(__('Registro de novos boletos · Layout FEBRABAN v.10.7')) ?>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="padding:14px 18px;border-bottom:1px solid var(--border);background:var(--bg-surface);">
		<div class="sec-title" style="margin:0;"><?= h(__('Selecione os títulos a incluir na remessa')) ?></div>
	</div>

	<?php if ($rmTitulos === []) : ?>
		<div style="padding:32px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum título em aberto vinculado a este banco.')) ?></div>
	<?php else : ?>
		<div style="padding:10px 18px;border-bottom:1px solid var(--border-light);display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
			<label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer;">
				<input type="checkbox" id="rm-sel-todos" checked onchange="pgmRemessaToggleTodos(this)"/>
				<?= h(__('Selecionar todos')) ?>
			</label>
		</div>
		<div style="max-height:320px;overflow-y:auto;">
			<div class="titulo-row" style="grid-template-columns:30px 110px 1fr 90px 100px;background:var(--bg-surface);font-size:11px;text-transform:uppercase;font-weight:600;color:var(--text-muted);position:sticky;top:0;z-index:1;">
				<div></div><div><?= h(__('Código')) ?></div><div><?= h(__('Cliente · Descrição')) ?></div><div><?= h(__('Vencimento')) ?></div><div style="text-align:right;"><?= h(__('Valor')) ?></div>
			</div>
			<?php foreach ($rmTitulos as $t) : ?>
			<div class="titulo-row rm-titulo-row" style="grid-template-columns:30px 110px 1fr 90px 100px;" data-valor="<?= h((string)$t['valor']) ?>">
				<div><input type="checkbox" class="rm-titulo-cb" name="titulo_ids[]" value="<?= (int)$t['id'] ?>" checked onchange="pgmRemessaAtualizarResumo()"/></div>
				<div><span class="titulo-cod"><?= h((string)$t['codigo']) ?></span></div>
				<div>
					<div style="font-size:13px;font-weight:500;"><?= h((string)$t['cliente']) ?></div>
					<div style="font-size:11px;color:var(--text-muted);"><?= h(\Cake\Utility\Text::truncate((string)$t['descricao'], 60, ['ellipsis' => '…'])) ?></div>
				</div>
				<div style="font-size:12px;"><?= h((string)$t['vencimento']) ?></div>
				<div style="text-align:right;font-weight:600;"><?= h($H->brl((float)$t['valor'])) ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>

<?php if ($rmTitulos !== []) : ?>
<div style="background:var(--blue-light);border-radius:var(--radius);padding:12px 14px;margin:14px 0;display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;">
	<div><div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Títulos selecionados')) ?></div><div id="rm-res-qtd" style="font-size:18px;font-weight:700;color:#0C447C;"><?= count($rmTitulos) ?></div></div>
	<div><div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Valor total')) ?></div><div id="rm-res-total" style="font-size:18px;font-weight:700;color:#0C447C;"><?= h($H->brl((float)$rmKpi['total'])) ?></div></div>
	<div><div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h(__('Próx. seq. arquivo')) ?></div><div style="font-size:18px;font-weight:700;color:#0C447C;"><?= h((string)$rmKpi['seq_arquivo']) ?></div></div>
</div>

<div class="g2" style="margin-bottom:14px;">
	<div class="field"><label><?= h(__('Padrão / Layout')) ?></label><select disabled><option>FEBRABAN CNAB 240 · v.10.7</option></select></div>
	<div class="field"><label><?= h(__('Data prevista de envio')) ?></label><input type="date" value="<?= h($rmMeta['data_hoje']) ?>" disabled/></div>
</div>

<div class="card" style="padding:14px 18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
	<span style="font-size:11px;color:var(--text-muted);"><?= h(__('Arquivo:')) ?> <strong style="font-family:monospace;color:var(--text);"><?= h($rmMeta['nome_arquivo']) ?></strong></span>
	<div style="display:flex;gap:8px;">
		<button type="button" class="btn btn-ghost btn-sm" onclick="window.location.href='<?= h($this->Url->build($urlRemessa)) ?>'"><?= h(__('Cancelar')) ?></button>
		<button type="button" class="btn btn-blue btn-sm" id="rm-btn-gerar" onclick="pgmGerarRemessaCnab()">📤 <?= h(__('Gerar arquivo CNAB')) ?></button>
	</div>
</div>
<div id="rm-feedback" style="display:none;margin-top:10px;" class="alert-box"></div>
<?php endif; ?>

<?php endif; ?>

<div class="alert-box alert-amber" style="margin-top:14px;font-size:11px;">
	<?= h(__('Observação: os títulos selecionados terão status atualizado para "Em remessa" após a geração do arquivo CNAB.')) ?>
</div>
</div>

<script>
(function () {
	var bancoId = <?= (int)$bancoId ?>;
	var urlGerar = <?= json_encode($urlGerar) ?>;
	var csrf = document.querySelector('meta[name="csrfToken"]');
	var csrfVal = csrf ? csrf.getAttribute('content') : '';

	window.pgmRemessaToggleTodos = function (el) {
		document.querySelectorAll('.rm-titulo-cb').forEach(function (cb) { cb.checked = el.checked; });
		pgmRemessaAtualizarResumo();
	};

	window.pgmRemessaAtualizarResumo = function () {
		var qtd = 0, total = 0;
		document.querySelectorAll('.rm-titulo-cb:checked').forEach(function (cb) {
			qtd++;
			var row = cb.closest('.rm-titulo-row');
			if (row) { total += parseFloat(row.getAttribute('data-valor') || '0'); }
		});
		var qEl = document.getElementById('rm-res-qtd');
		var tEl = document.getElementById('rm-res-total');
		if (qEl) { qEl.textContent = String(qtd); }
		if (tEl) { tEl.textContent = total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }); }
	};

	window.pgmGerarRemessaCnab = function () {
		var ids = [];
		document.querySelectorAll('.rm-titulo-cb:checked').forEach(function (cb) { ids.push(parseInt(cb.value, 10)); });
		if (!ids.length) { alert(<?= json_encode(__('Selecione ao menos um título.')) ?>); return; }
		if (bancoId <= 0) { return; }
		var btn = document.getElementById('rm-btn-gerar');
		var fb = document.getElementById('rm-feedback');
		if (btn) { btn.disabled = true; btn.textContent = '…'; }
		fetch(urlGerar, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'Accept': 'application/json',
				'X-CSRF-Token': csrfVal
			},
			body: JSON.stringify({ titulo_ids: ids, banco_id: bancoId, multiempresa: false })
		}).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
		.then(function (res) {
			if (btn) { btn.disabled = false; btn.textContent = '📤 <?= h(__('Gerar arquivo CNAB')) ?>'; }
			if (!fb) { return; }
			fb.style.display = '';
			if (res.body && res.body.ok !== false) {
				fb.className = 'alert-box alert-teal';
				var msg = res.body.message || res.body.data?.message || <?= json_encode(__('Remessa gerada com sucesso.')) ?>;
				fb.innerHTML = '✓ <strong>' + msg + '</strong>';
				if (res.body.data && res.body.data.download_url) {
					fb.innerHTML += ' <a href="' + res.body.data.download_url + '" class="btn btn-primary btn-xs" style="margin-left:8px;"><?= h(__('Baixar arquivo')) ?></a>';
				}
			} else {
				fb.className = 'alert-box alert-amber';
				fb.textContent = (res.body && (res.body.error || res.body.message)) || <?= json_encode(__('Não foi possível gerar a remessa.')) ?>;
			}
		}).catch(function () {
			if (btn) { btn.disabled = false; btn.textContent = '📤 <?= h(__('Gerar arquivo CNAB')) ?>'; }
			if (fb) { fb.style.display = ''; fb.className = 'alert-box alert-amber'; fb.textContent = <?= json_encode(__('Erro de comunicação com o servidor.')) ?>; }
		});
	};
})();
</script>
