<?php
/**
 * Solicitar orçamento — experiência comercial (distinta de tickets): catálogo, busca, formulário.
 * @var \App\View\AppView $this
 * @var array $catalogoDestaque
 * @var string $catalogoSugestoesUrl
 */
$this->append('css', $this->element('pgm_premium_css', ['name' => 'orcamentos-premium']));
$catalogoDestaque = $catalogoDestaque ?? [];
$catalogoSugestoesUrl = $catalogoSugestoesUrl ?? '';
$tipoLabel = static function ($t) {
	switch ((int)$t) {
		case 2:
			return 'Serviço';
		case 3:
			return 'Contrato';
		default:
			return 'Produto';
	}
};
?>
<style>
.pgm-solicitar-wrap{
	--sol-navy:#0c2340;
	--sol-cyan:#00b4d8;
	--sol-green:#00a878;
	--sol-border:#e2e8f0;
	--sol-muted:#64748b;
	margin:-15px -15px 0;
	padding:0 0 48px;
	min-height:calc(100vh - 64px);
	font-family:'DM Sans',system-ui,sans-serif;
}
.pgm-solicitar-wrap *{ box-sizing:border-box; }
.pgm-sol-hero{
	background:linear-gradient(135deg,var(--sol-navy) 0%,#153a5f 100%);
	color:#fff;
	padding:28px 32px 32px;
	border-radius:0 0 18px 18px;
	margin-bottom:28px;
	box-shadow:0 12px 40px rgba(12,35,64,.25);
}
.pgm-sol-hero-inner{ max-width:1100px;margin:0 auto;display:flex;flex-wrap:wrap;gap:16px;justify-content:space-between;align-items:flex-start; }
.pgm-sol-hero h1{ font-size:1.55rem;font-weight:800;margin:0 0 8px;letter-spacing:-.02em; }
.pgm-sol-hero p{ margin:0;font-size:.88rem;opacity:.88;max-width:520px;line-height:1.5; }
.pgm-sol-eyebrow{ font-size:.62rem;text-transform:uppercase;letter-spacing:.16em;font-weight:700;color:var(--sol-cyan);margin-bottom:8px; }
.pgm-sol-hero .sol-back{
	display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border-radius:10px;
	background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.25);color:#fff;font-size:.8rem;text-decoration:none;
}
.pgm-sol-hero .sol-back:hover{ background:rgba(255,255,255,.18);color:#fff;text-decoration:none; }
.pgm-sol-main{ max-width:1100px;margin:0 auto;padding:0 24px; display:flex;flex-direction:column;gap:22px; }
.pgm-sol-section-title{ font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--sol-muted);margin:0 0 12px; }
.pgm-sol-card{
	background:#fff;border:1px solid var(--sol-border);border-radius:14px;
	box-shadow:0 1px 3px rgba(15,23,42,.06); overflow:hidden;
}
.pgm-sol-card-h{
	padding:14px 20px;border-bottom:1px solid var(--sol-border);background:#f8fafc;
	font-weight:700;font-size:.9rem;color:var(--sol-navy);
}
.pgm-sol-card-b{ padding:20px; }
.pgm-sol-grid{ display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
@media(max-width:992px){ .pgm-sol-grid{ grid-template-columns:repeat(2,1fr);} }
@media(max-width:520px){ .pgm-sol-grid{ grid-template-columns:1fr;} }
.pgm-sol-pcard{
	border:1px solid var(--sol-border);border-radius:12px;padding:16px 14px;display:flex;flex-direction:column;gap:10px;
	min-height:200px;background:#fff;transition:box-shadow .15s,border-color .15s;
}
.pgm-sol-pcard:hover{ box-shadow:0 8px 24px rgba(12,35,64,.08); border-color:#cbd5e1; }
.pgm-sol-pcard h3{ font-size:.82rem;font-weight:800;color:var(--sol-navy);margin:0;line-height:1.35;min-height:2.6em; }
.pgm-sol-badge{
	display:inline-block;font-size:.58rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;
	padding:4px 10px;border-radius:999px;background:rgba(0,180,216,.15);color:#0077a8;
}
.pgm-sol-pcard .pgm-sol-sub{ font-size:.72rem;color:var(--sol-muted);line-height:1.45;flex:1; }
.pgm-sol-preco{ font-size:.8rem;color:var(--sol-muted); }
.pgm-sol-preco strong{ color:var(--sol-navy);font-weight:700; }
.pgm-sol-btn-outline{
	display:block;width:100%;text-align:center;padding:9px 12px;border-radius:9px;border:2px solid var(--sol-green);
	background:#fff;color:var(--sol-green);font-size:.76rem;font-weight:700;cursor:pointer;font-family:inherit;
}
.pgm-sol-btn-outline:hover{ background:rgba(0,168,120,.08); }
.pgm-sol-search-row{ display:flex;gap:10px;flex-wrap:wrap;align-items:flex-start; }
.pgm-sol-search-row input[type="search"]{
	flex:1;min-width:200px;padding:12px 14px;border:1px solid var(--sol-border);border-radius:10px;font-size:.88rem;
}
.pgm-sol-search-hint{ font-size:.75rem;color:var(--sol-muted);margin-top:8px; }
.pgm-sol-cat-table-wrap{
	margin-top:12px;max-height:240px;overflow:auto;border:1px solid var(--sol-border);border-radius:10px;display:none;
}
.pgm-sol-cat-table-wrap.open{ display:block; }
.pgm-sol-cat-table{ width:100%;border-collapse:collapse;font-size:.8rem; }
.pgm-sol-cat-table th{ text-align:left;padding:10px 12px;background:#e2e8f0;color:#334155;font-weight:700;position:sticky;top:0; }
.pgm-sol-cat-table td{ padding:10px 12px;border-top:1px solid var(--sol-border);cursor:pointer; }
.pgm-sol-cat-table tr:hover td{ background:#f1f5f9; }
.pgm-sol-cat-table .c-cod{ width:28%;font-weight:600;color:var(--sol-navy);white-space:nowrap; }
.pgm-sol-form-grid{ display:grid;grid-template-columns:1fr 1fr;gap:16px; }
@media(max-width:640px){ .pgm-sol-form-grid{ grid-template-columns:1fr;} }
.pgm-sol-field label{ display:block;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--sol-muted);margin-bottom:6px; }
.pgm-sol-field .req{ color:#dc2626; }
.pgm-sol-input,.pgm-sol-textarea{
	width:100%;padding:10px 12px;border:1px solid var(--sol-border);border-radius:9px;font-size:.85rem;font-family:inherit;
	background:#f8fafc;
}
.pgm-sol-input:focus,.pgm-sol-textarea:focus{ outline:none;border-color:var(--sol-cyan);box-shadow:0 0 0 3px rgba(0,180,216,.15);background:#fff; }
.pgm-sol-textarea{ min-height:100px;resize:vertical; }
.pgm-sol-urg{ display:grid;grid-template-columns:repeat(3,1fr);gap:8px; }
@media(max-width:480px){ .pgm-sol-urg{ grid-template-columns:1fr;} }
.pgm-sol-urg button{
	padding:11px;border-radius:10px;border:1px solid var(--sol-border);background:#fff;cursor:pointer;font-size:.78rem;font-weight:600;font-family:inherit;
}
.pgm-sol-urg button.active-n{ border-color:var(--sol-green);background:#ecfdf5;color:#047857; }
.pgm-sol-urg button.active-m{ border-color:#f59e0b;background:#fffbeb;color:#b45309; }
.pgm-sol-urg button.active-h{ border-color:#ef4444;background:#fef2f2;color:#b91c1c; }
.pgm-sol-item-row{
	display:grid;grid-template-columns:100px 1fr 72px 1fr 36px;gap:10px;align-items:end;
	background:#f8fafc;border:1px solid var(--sol-border);border-radius:10px;padding:12px;
}
@media(max-width:700px){ .pgm-sol-item-row{ grid-template-columns:1fr; } }
.pgm-sol-item-row .lbl{ font-size:.58rem;font-weight:700;text-transform:uppercase;color:var(--sol-muted);margin-bottom:4px; }
.pgm-sol-btn-add{
	margin-top:10px;padding:9px 16px;border:1px dashed var(--sol-border);border-radius:9px;background:transparent;
	cursor:pointer;font-size:.78rem;color:var(--sol-muted);font-family:inherit;
}
.pgm-sol-btn-add:hover{ border-color:var(--sol-green);color:var(--sol-green); }
.pgm-sol-btn-rm{
	width:34px;height:34px;border-radius:8px;border:1px solid var(--sol-border);background:#fff;cursor:pointer;color:var(--sol-muted);
}
.pgm-sol-actions{ display:flex;justify-content:flex-end;gap:12px;flex-wrap:wrap;padding-top:8px; }
.pgm-sol-btn-cancel{
	padding:11px 20px;border-radius:10px;border:1px solid var(--sol-border);background:#fff;color:var(--sol-muted);font-size:.85rem;text-decoration:none;
}
.pgm-sol-btn-submit{
	padding:12px 28px;border-radius:10px;border:none;background:linear-gradient(135deg,var(--sol-green),#008f68);color:#fff;font-size:.88rem;font-weight:800;
	cursor:pointer;font-family:inherit;box-shadow:0 4px 16px rgba(0,168,120,.25);
}
.pgm-sol-note{
	font-size:.78rem;color:var(--sol-muted);padding:14px 16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;line-height:1.5;
}
.pgm-sol-field--mt16{margin-top:16px;}
.pgm-sol-item-row input[type="number"].pgm-sol-input{text-align:center;}
</style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&display=swap" rel="stylesheet">

<div class="col-md-12 p-0">
<div class="pgm-solicitar-wrap">

	<div class="pgm-sol-hero">
		<div class="pgm-sol-hero-inner">
			<div>
				<div class="pgm-sol-eyebrow"><?= __('Portal comercial') ?> · <?= __('Orçamentos') ?></div>
				<h1><?= __('Solicitar proposta') ?></h1>
				<p><?= __('Monte seu pedido com itens do catálogo ou descreva o que precisa. Este fluxo é independente dos chamados de suporte (tickets).') ?></p>
			</div>
			<?= $this->Html->link(
				'<i class="fas fa-arrow-left"></i> ' . __('Meus orçamentos'),
				['action' => 'index'],
				['class' => 'sol-back', 'escape' => false]
			) ?>
		</div>
	</div>

	<div class="pgm-sol-main">
		<?= $this->Flash->render() ?>

		<?php if (!empty($catalogoDestaque)) : ?>
		<div>
			<p class="pgm-sol-section-title"><?= __('Catálogo em destaque') ?></p>
			<div class="pgm-sol-grid">
				<?php foreach ($catalogoDestaque as $p) :
					$preco = (float)($p->vlunitario ?? 0);
					$precoTxt = $preco > 0 ? ('R$ ' . number_format($preco, 2, ',', '.')) : __('Sob consulta');
					$dPlain = trim((string)$p->descricao);
					$cPlain = trim((string)$p->codigo);
					?>
				<div class="pgm-sol-pcard" data-codigo="<?= h($cPlain) ?>" data-descricao="<?= h($dPlain) ?>">
					<span class="pgm-sol-badge"><?= h($tipoLabel($p->tipo)) ?></span>
					<h3><?= h($dPlain) ?></h3>
					<div class="pgm-sol-sub"><?= h(__('Código')) ?>: <?= h($cPlain) ?></div>
					<div class="pgm-sol-preco"><?= __('A partir de') ?> <strong><?= h($precoTxt) ?></strong></div>
					<button type="button" class="pgm-sol-btn-outline pgm-sol-card-pick"><?= __('Incluir no pedido') ?></button>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<div class="pgm-sol-card">
			<div class="pgm-sol-card-h"><?= __('Buscar no catálogo (código ou descrição)') ?></div>
			<div class="pgm-sol-card-b">
				<div class="pgm-sol-search-row">
					<input type="search" id="solCatSearch" autocomplete="off" placeholder="<?= h(__('Ex.: CSPSOFT, Windows, Microsoft 365…')) ?>">
				</div>
				<p class="pgm-sol-search-hint"><?= __('Digite pelo menos 2 caracteres. Clique numa linha para preencher a linha de item ativa.') ?></p>
				<div class="pgm-sol-cat-table-wrap" id="solCatWrap">
					<table class="pgm-sol-cat-table" id="solCatTable">
						<thead><tr><th class="c-cod"><?= __('Código') ?></th><th><?= __('Descrição do produto') ?></th></tr></thead>
						<tbody id="solCatTbody"></tbody>
					</table>
				</div>
			</div>
		</div>

		<?= $this->Form->create(null, ['url' => ['action' => 'solicitar'], 'type' => 'post', 'id' => 'formSolicitar']) ?>

		<div class="pgm-sol-card">
			<div class="pgm-sol-card-h"><?= __('Dados da solicitação') ?></div>
			<div class="pgm-sol-card-b">
				<div class="pgm-sol-form-grid">
					<div class="pgm-sol-field">
						<label for="sol-assunto"><?= __('Assunto') ?> <span class="req">*</span></label>
						<input class="pgm-sol-input" id="sol-assunto" name="assunto" type="text" required maxlength="120"
							placeholder="<?= h(__('Licenças, infraestrutura, serviço gerenciado…')) ?>">
					</div>
					<div class="pgm-sol-field">
						<label for="sol-prazo"><?= __('Prazo desejado') ?></label>
						<input class="pgm-sol-input" id="sol-prazo" name="prazo" type="text" placeholder="<?= h(__('Data ou prazo em dias')) ?>">
					</div>
				</div>
				<div class="pgm-sol-field pgm-sol-field--mt16">
					<label for="sol-descricao"><?= __('Descrição / contexto') ?> <span class="req">*</span></label>
					<textarea class="pgm-sol-textarea" id="sol-descricao" name="descricao" required placeholder="<?= h(__('Objetivo, escopo, volume, restrições…')) ?>"></textarea>
				</div>
				<div class="pgm-sol-field pgm-sol-field--mt16">
					<label><?= __('Urgência') ?></label>
					<div class="pgm-sol-urg" id="urgencyRow">
						<button type="button" class="active-n" data-sev="n" data-value="<?= h(__('Normal')) ?>"><?= __('Normal') ?></button>
						<button type="button" data-sev="m" data-value="<?= h(__('Média')) ?>"><?= __('Média') ?></button>
						<button type="button" data-sev="h" data-value="<?= h(__('Alta')) ?>"><?= __('Alta') ?></button>
					</div>
					<input type="hidden" name="urgencia" id="inp-urgencia" value="<?= h(__('Normal')) ?>">
				</div>
			</div>
		</div>

		<div class="pgm-sol-card">
			<div class="pgm-sol-card-h"><?= __('Itens para cotar') ?></div>
			<div class="pgm-sol-card-b">
				<div id="itensList">
					<div class="pgm-sol-item-row" id="item-0" data-sol-focus="1">
						<div>
							<div class="lbl"><?= __('Código') ?></div>
							<input class="pgm-sol-input sol-inp-cod" name="itens[0][codigo]" type="text" placeholder="—">
						</div>
						<div>
							<div class="lbl"><?= __('Descrição') ?></div>
							<input class="pgm-sol-input sol-inp-desc" name="itens[0][descricao]" type="text" placeholder="<?= h(__('Descrição do item')) ?>">
						</div>
						<div>
							<div class="lbl"><?= __('Qtd') ?></div>
							<input class="pgm-sol-input" name="itens[0][quantidade]" type="number" min="1" value="1">
						</div>
						<div>
							<div class="lbl"><?= __('Obs.') ?></div>
							<input class="pgm-sol-input" name="itens[0][obs]" type="text" placeholder="<?= h(__('Opcional')) ?>">
						</div>
						<button type="button" class="pgm-sol-btn-rm" onclick="rmItem('item-0')" title="<?= h(__('Remover')) ?>"><i class="fas fa-times"></i></button>
					</div>
				</div>
				<button type="button" class="pgm-sol-btn-add" onclick="addItem()"><i class="fas fa-plus"></i> <?= __('Adicionar linha') ?></button>
			</div>
		</div>

		<p class="pgm-sol-note"><?= __('Após o envio, um chamado interno será aberto para a equipe comercial com estes dados. Acompanhe em «Meus orçamentos» ou nos chamados, conforme o processo da sua empresa.') ?></p>

		<div class="pgm-sol-actions">
			<?= $this->Html->link(__('Cancelar'), ['action' => 'index'], ['class' => 'pgm-sol-btn-cancel']) ?>
			<button type="submit" class="pgm-sol-btn-submit"><i class="fas fa-paper-plane"></i> <?= __('Enviar solicitação') ?></button>
		</div>

		<?= $this->Form->end() ?>
	</div>
</div>
</div>

<script>
(function() {
	var itemCount = 1;
	var catUrl = <?= json_encode($catalogoSugestoesUrl) ?>;
	var debounceT = null;

	function activeRow() {
		return document.querySelector('.pgm-sol-item-row[data-sol-focus="1"]') || document.querySelector('.pgm-sol-item-row');
	}
	function setFocusRow(row) {
		document.querySelectorAll('.pgm-sol-item-row').forEach(function(r) { r.removeAttribute('data-sol-focus'); r.style.outline = ''; });
		row.setAttribute('data-sol-focus', '1');
		row.style.outline = '2px solid #00b4d8';
	}
	document.getElementById('itensList').addEventListener('click', function(ev) {
		var row = ev.target.closest('.pgm-sol-item-row');
		if (row) setFocusRow(row);
	});

	document.querySelectorAll('.pgm-sol-urg button').forEach(function(btn) {
		btn.addEventListener('click', function() {
			document.querySelectorAll('.pgm-sol-urg button').forEach(function(b) {
				b.classList.remove('active-n', 'active-m', 'active-h');
			});
			var sev = btn.getAttribute('data-sev') || 'n';
			if (sev === 'm') btn.classList.add('active-m');
			else if (sev === 'h') btn.classList.add('active-h');
			else btn.classList.add('active-n');
			document.getElementById('inp-urgencia').value = btn.getAttribute('data-value') || '';
		});
	});

	function fillRow(codigo, descricao) {
		var row = activeRow();
		if (!row) return;
		var c = row.querySelector('.sol-inp-cod');
		var d = row.querySelector('.sol-inp-desc');
		if (c) { c.value = codigo || ''; }
		if (d) d.value = descricao || '';
	}

	document.querySelectorAll('.pgm-sol-card-pick').forEach(function(btn) {
		btn.addEventListener('click', function(ev) {
			ev.preventDefault();
			var card = btn.closest('.pgm-sol-pcard');
			if (!card) return;
			fillRow(card.getAttribute('data-codigo') || '', card.getAttribute('data-descricao') || '');
		});
	});

	function renderCatRows(itens) {
		var tb = document.getElementById('solCatTbody');
		var wrap = document.getElementById('solCatWrap');
		tb.innerHTML = '';
		if (!itens || !itens.length) { wrap.classList.remove('open'); return; }
		itens.forEach(function(it) {
			var tr = document.createElement('tr');
			tr.innerHTML = '<td class="c-cod"></td><td></td>';
			tr.cells[0].textContent = it.codigo || '';
			tr.cells[1].textContent = it.descricao || '';
			tr.addEventListener('click', function() {
				fillRow(it.codigo, it.descricao);
				wrap.classList.remove('open');
			});
			tb.appendChild(tr);
		});
		wrap.classList.add('open');
	}

	document.getElementById('solCatSearch').addEventListener('input', function() {
		var q = this.value.trim();
		clearTimeout(debounceT);
		if (q.length < 2) {
			document.getElementById('solCatWrap').classList.remove('open');
			return;
		}
		debounceT = setTimeout(function() {
			fetch(catUrl + '?q=' + encodeURIComponent(q), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
				.then(function(r) { return r.json(); })
				.then(function(data) { renderCatRows(data.itens || []); })
				.catch(function() { document.getElementById('solCatWrap').classList.remove('open'); });
		}, 320);
	});

	window.addItem = function() {
		var idx = itemCount++;
		var row = document.createElement('div');
		row.className = 'pgm-sol-item-row';
		row.id = 'item-' + idx;
		row.innerHTML =
			'<div><div class="lbl"><?= h(__('Código')) ?></div><input class="pgm-sol-input sol-inp-cod" name="itens[' + idx + '][codigo]" type="text" placeholder="—"></div>' +
			'<div><div class="lbl"><?= h(__('Descrição')) ?></div><input class="pgm-sol-input sol-inp-desc" name="itens[' + idx + '][descricao]" type="text" placeholder="<?= h(__('Descrição do item')) ?>"></div>' +
			'<div><div class="lbl"><?= h(__('Qtd')) ?></div><input class="pgm-sol-input" name="itens[' + idx + '][quantidade]" type="number" min="1" value="1"></div>' +
			'<div><div class="lbl"><?= h(__('Obs.')) ?></div><input class="pgm-sol-input" name="itens[' + idx + '][obs]" type="text" placeholder="<?= h(__('Opcional')) ?>"></div>' +
			'<button type="button" class="pgm-sol-btn-rm" onclick="rmItem(\'item-' + idx + '\')" title="<?= h(__('Remover')) ?>"><i class="fas fa-times"></i></button>';
		document.getElementById('itensList').appendChild(row);
		setFocusRow(row);
	};

	window.rmItem = function(id) {
		var el = document.getElementById(id);
		var list = document.getElementById('itensList');
		if (el && list.children.length > 1) el.remove();
	};

	setFocusRow(document.getElementById('item-0'));
})();
</script>
