<?php
/**
 * Orcamentos/solicitar.ctp — Tela de solicitação de orçamento para clientes.
 * Coleta dados detalhados, urgência, itens e cria um ticket interno.
 */
$this->append('css', $this->element('pgm_premium_css', ['name' => 'orcamentos-premium']));
?>
<style>
/* ── Solicitar Orçamento — Premium ─────────────────────────────── */
.sol-root{max-width:860px;margin:0 auto;padding:4px 0 32px;display:flex;flex-direction:column;gap:20px;}
.sol-head{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;}
.sol-head-left .sol-eyebrow{font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;color:#1d9e75;font-weight:700;margin-bottom:4px;}
.sol-head-left h1{font-size:1.3rem;font-weight:800;color:#e6edf3;margin:0 0 4px;}
.sol-head-left p{font-size:.78rem;color:#6e7681;margin:0;}
.sol-back{display:inline-flex;align-items:center;gap:5px;font-size:.78rem;color:#6e7681;text-decoration:none;transition:color .15s;}
.sol-back:hover{color:#5cdbc0;text-decoration:none;}
/* Stepper */
.sol-stepper{display:flex;gap:0;align-items:center;}
.sol-step{display:flex;align-items:center;gap:8px;font-size:.72rem;color:#6e7681;font-weight:600;}
.sol-step-num{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:800;background:#21262d;color:#6e7681;transition:all .2s;}
.sol-step.active .sol-step-num{background:#1d9e75;color:#fff;}
.sol-step.active{color:#c9d1d9;}
.sol-step-sep{width:28px;height:1px;background:#21262d;margin:0 4px;}
/* Cards de seção */
.sol-card{background:#161b22;border:1px solid #21262d;border-radius:12px;overflow:hidden;}
.sol-card-head{padding:14px 20px;border-bottom:1px solid #21262d;display:flex;align-items:center;gap:10px;}
.sol-card-head-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.85rem;}
.sol-card-head-icon.teal{background:rgba(29,158,117,.15);color:#5cdbc0;}
.sol-card-head-icon.blue{background:rgba(56,139,253,.15);color:#79c0ff;}
.sol-card-head-icon.orange{background:rgba(210,153,34,.15);color:#e3b341;}
.sol-card-title{font-size:.82rem;font-weight:700;color:#c9d1d9;}
.sol-card-subtitle{font-size:.7rem;color:#6e7681;margin-left:auto;}
.sol-card-body{padding:20px;}
/* Form fields */
.sol-field{display:flex;flex-direction:column;gap:5px;margin-bottom:14px;}
.sol-label{font-size:.7rem;font-weight:700;color:#8b949e;text-transform:uppercase;letter-spacing:.07em;}
.sol-label .sol-req{color:#f87171;margin-left:2px;}
.sol-input,.sol-select,.sol-textarea{width:100%;padding:9px 12px;background:#0d1117;border:1px solid #30363d;border-radius:8px;color:#e6edf3;font-size:.85rem;font-family:'DM Sans',sans-serif;transition:border-color .15s;}
.sol-input:focus,.sol-select:focus,.sol-textarea:focus{outline:none;border-color:#1d9e75;box-shadow:0 0 0 2px rgba(29,158,117,.12);}
.sol-textarea{resize:vertical;min-height:90px;}
.sol-select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236e7681' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center;}
.sol-row{display:grid;gap:14px;}
.sol-row.two{grid-template-columns:1fr 1fr;}
.sol-row.three{grid-template-columns:1fr 1fr 1fr;}
@media(max-width:600px){.sol-row.two,.sol-row.three{grid-template-columns:1fr;}}
/* Urgência */
.sol-urgency-row{display:flex;gap:8px;flex-wrap:wrap;}
.sol-urgency-btn{flex:1;min-width:100px;padding:9px 12px;border-radius:8px;border:1px solid #30363d;background:#0d1117;color:#8b949e;font-size:.75rem;font-weight:600;cursor:pointer;text-align:center;transition:all .18s;}
.sol-urgency-btn:hover{border-color:#8b949e;color:#c9d1d9;}
.sol-urgency-btn.active-low{border-color:#1d9e75;background:rgba(29,158,117,.1);color:#5cdbc0;}
.sol-urgency-btn.active-med{border-color:#e3b341;background:rgba(210,153,34,.1);color:#e3b341;}
.sol-urgency-btn.active-high{border-color:#f87171;background:rgba(248,113,113,.1);color:#f87171;}
/* Itens */
.sol-itens-list{display:flex;flex-direction:column;gap:8px;}
.sol-item-row{display:grid;grid-template-columns:1fr 80px 1fr 28px;gap:8px;align-items:start;background:#0d1117;border:1px solid #21262d;border-radius:8px;padding:10px 12px;}
@media(max-width:600px){.sol-item-row{grid-template-columns:1fr;}}
.sol-item-num{font-size:.65rem;font-family:'DM Mono',monospace;color:#6e7681;padding-top:2px;}
.sol-btn-add-item{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:transparent;border:1px dashed #30363d;border-radius:8px;color:#6e7681;font-size:.75rem;cursor:pointer;transition:all .18s;font-family:'DM Sans',sans-serif;}
.sol-btn-add-item:hover{border-color:#1d9e75;color:#5cdbc0;}
.sol-btn-rm{width:26px;height:26px;border-radius:6px;border:none;background:#21262d;color:#6e7681;font-size:.7rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s;flex-shrink:0;margin-top:4px;}
.sol-btn-rm:hover{background:#f87171;color:#fff;}
/* Dicas */
.sol-tip{background:rgba(29,158,117,.07);border:1px solid rgba(29,158,117,.2);border-radius:8px;padding:12px 14px;font-size:.75rem;color:#6e7681;display:flex;gap:10px;align-items:flex-start;}
.sol-tip i{color:#5cdbc0;margin-top:1px;flex-shrink:0;}
/* Footer */
.sol-footer{display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:14px 20px;background:#0d1117;border-top:1px solid #21262d;}
.sol-btn-submit{display:inline-flex;align-items:center;gap:7px;padding:10px 24px;background:#1d9e75;color:#fff;border:none;border-radius:8px;font-size:.85rem;font-weight:700;cursor:pointer;transition:all .18s;font-family:'DM Sans',sans-serif;}
.sol-btn-submit:hover{background:#5cdbc0;color:#111;}
.sol-btn-cancel{display:inline-flex;align-items:center;gap:6px;padding:10px 16px;background:transparent;color:#6e7681;border:1px solid #30363d;border-radius:8px;font-size:.82rem;cursor:pointer;text-decoration:none;transition:all .15s;}
.sol-btn-cancel:hover{color:#c9d1d9;border-color:#8b949e;text-decoration:none;}
</style>

<div class="col-md-12 orc-premium-page-root">
<div class="sol-root">

	<!-- Head -->
	<div class="sol-head">
		<div class="sol-head-left">
			<div class="sol-eyebrow">Portal do Cliente &rsaquo; Orçamentos</div>
			<h1>Solicitar Orçamento</h1>
			<p>Preencha os dados abaixo e nossa equipe preparará uma proposta personalizada.</p>
		</div>
		<?= $this->Html->link(
			'<i class="fas fa-arrow-left"></i> Meus Orçamentos',
			['action' => 'index'],
			['class' => 'sol-back', 'escape' => false]
		) ?>
	</div>

	<!-- Flash messages -->
	<?= $this->Flash->render() ?>

	<?= $this->Form->create(null, ['url' => ['action' => 'solicitar'], 'type' => 'post', 'id' => 'formSolicitar']) ?>

	<!-- Seção 1: Dados gerais -->
	<div class="sol-card">
		<div class="sol-card-head">
			<div class="sol-card-head-icon teal"><i class="fas fa-clipboard-list"></i></div>
			<span class="sol-card-title">Dados da Solicitação</span>
			<span class="sol-card-subtitle">* campos obrigatórios</span>
		</div>
		<div class="sol-card-body">
			<div class="sol-row two">
				<div class="sol-field">
					<label class="sol-label" for="sol-assunto">Assunto <span class="sol-req">*</span></label>
					<input class="sol-input" id="sol-assunto" name="assunto" type="text" required
						placeholder="Ex.: Suporte, Implantação, Licença, Equipamento…" maxlength="120">
				</div>
				<div class="sol-field">
					<label class="sol-label" for="sol-prazo">Prazo desejado</label>
					<input class="sol-input" id="sol-prazo" name="prazo" type="text"
						placeholder="Ex.: 15/04/2026 ou 30 dias">
				</div>
			</div>

			<div class="sol-field">
				<label class="sol-label" for="sol-descricao">Descrição / Contexto <span class="sol-req">*</span></label>
				<textarea class="sol-textarea" id="sol-descricao" name="descricao" required
					placeholder="Descreva o que precisa, contexto do projeto, volume, restrições técnicas ou qualquer informação relevante para a elaboração do orçamento…" rows="5"></textarea>
			</div>

			<div class="sol-field">
				<label class="sol-label">Urgência</label>
				<div class="sol-urgency-row" id="urgencyRow">
					<button type="button" class="sol-urgency-btn active-low" data-value="Normal" onclick="setUrgency(this,'active-low')">
						<i class="fas fa-check-circle"></i> Normal
					</button>
					<button type="button" class="sol-urgency-btn" data-value="Média" onclick="setUrgency(this,'active-med')">
						<i class="fas fa-exclamation-circle"></i> Média
					</button>
					<button type="button" class="sol-urgency-btn" data-value="Alta" onclick="setUrgency(this,'active-high')">
						<i class="fas fa-fire"></i> Alta
					</button>
				</div>
				<input type="hidden" name="urgencia" id="inp-urgencia" value="Normal">
			</div>
		</div>
	</div>

	<!-- Seção 2: Itens -->
	<div class="sol-card">
		<div class="sol-card-head">
			<div class="sol-card-head-icon blue"><i class="fas fa-list-ul"></i></div>
			<span class="sol-card-title">Itens / Produtos / Serviços</span>
			<span class="sol-card-subtitle">Adicione os itens que precisam de orçamento (opcional)</span>
		</div>
		<div class="sol-card-body">
			<div class="sol-itens-list" id="itensList">
				<!-- Primeiro item padrão -->
				<div class="sol-item-row" id="item-0">
					<div class="sol-field" style="margin:0;">
						<label class="sol-label" style="font-size:.65rem;">Descrição do item</label>
						<input class="sol-input" name="itens[0][descricao]" type="text" placeholder="Ex.: Licença Microsoft 365, Switch 24 portas…">
					</div>
					<div class="sol-field" style="margin:0;">
						<label class="sol-label" style="font-size:.65rem;">Quantidade</label>
						<input class="sol-input" name="itens[0][quantidade]" type="number" min="1" value="1" style="text-align:center;">
					</div>
					<div class="sol-field" style="margin:0;">
						<label class="sol-label" style="font-size:.65rem;">Observação</label>
						<input class="sol-input" name="itens[0][obs]" type="text" placeholder="Especificações, marca, modelo…">
					</div>
					<button type="button" class="sol-btn-rm" onclick="rmItem('item-0')" title="Remover"><i class="fas fa-times"></i></button>
				</div>
			</div>
			<div style="margin-top:10px;">
				<button type="button" class="sol-btn-add-item" onclick="addItem()">
					<i class="fas fa-plus"></i> Adicionar item
				</button>
			</div>
		</div>
	</div>

	<!-- Dica -->
	<div class="sol-tip">
		<i class="fas fa-lightbulb"></i>
		<div>
			<strong style="color:#c9d1d9;">Dica:</strong> Quanto mais detalhes você fornecer, mais precisa será a proposta.
			Informe marca/modelo preferencial, prazo, local de entrega/instalação e qualquer restrição de orçamento.
			Nossa equipe entrará em contato em até 2 dias úteis.
		</div>
	</div>

	<!-- Footer -->
	<div class="sol-card" style="overflow:visible;">
		<div class="sol-footer">
			<?= $this->Html->link(
				'<i class="fas fa-arrow-left"></i> Cancelar',
				['action' => 'index'],
				['class' => 'sol-btn-cancel', 'escape' => false]
			) ?>
			<button type="submit" class="sol-btn-submit">
				<i class="fas fa-paper-plane"></i> Enviar Solicitação
			</button>
		</div>
	</div>

	<?= $this->Form->end() ?>

</div><!-- /sol-root -->
</div><!-- /orc-premium-page-root -->

<script>
(function() {
	var itemCount = 1;

	/* Urgência */
	window.setUrgency = function(btn, cls) {
		document.querySelectorAll('.sol-urgency-btn').forEach(function(b) {
			b.classList.remove('active-low', 'active-med', 'active-high');
		});
		btn.classList.add(cls);
		document.getElementById('inp-urgencia').value = btn.getAttribute('data-value');
	};

	/* Adicionar item */
	window.addItem = function() {
		var idx = itemCount++;
		var row = document.createElement('div');
		row.className = 'sol-item-row';
		row.id = 'item-' + idx;
		row.innerHTML =
			'<div class="sol-field" style="margin:0;">' +
				'<label class="sol-label" style="font-size:.65rem;">Descrição do item</label>' +
				'<input class="sol-input" name="itens[' + idx + '][descricao]" type="text" placeholder="Ex.: Licença, equipamento, serviço…">' +
			'</div>' +
			'<div class="sol-field" style="margin:0;">' +
				'<label class="sol-label" style="font-size:.65rem;">Quantidade</label>' +
				'<input class="sol-input" name="itens[' + idx + '][quantidade]" type="number" min="1" value="1" style="text-align:center;">' +
			'</div>' +
			'<div class="sol-field" style="margin:0;">' +
				'<label class="sol-label" style="font-size:.65rem;">Observação</label>' +
				'<input class="sol-input" name="itens[' + idx + '][obs]" type="text" placeholder="Especificações, marca, modelo…">' +
			'</div>' +
			'<button type="button" class="sol-btn-rm" onclick="rmItem(\'item-' + idx + '\')" title="Remover"><i class="fas fa-times"></i></button>';
		document.getElementById('itensList').appendChild(row);
	};

	/* Remover item */
	window.rmItem = function(id) {
		var el = document.getElementById(id);
		if (el && document.getElementById('itensList').children.length > 1) {
			el.remove();
		}
	};
})();
</script>
