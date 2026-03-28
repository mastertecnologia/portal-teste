<?php
/**
 * Orcamentos/solicitar.ctp — Tela de solicitação de orçamento para clientes.
 */
$this->append('css', $this->element('pgm_premium_css', ['name' => 'orcamentos-premium']));
?>
<style>
/* ── Solicitar Orçamento — pgm_orcamentos_premium (claro) ───────── */
.sol-page-wrap{
    background:transparent;
    margin:-15px -15px 0;
    padding:32px 32px 48px;
    min-height:calc(100vh - 64px);
}
@media(max-width:768px){.sol-page-wrap{padding:20px 16px 40px;}}
.sol-root{max-width:960px;margin:0 auto;display:flex;flex-direction:column;gap:24px;}
.sol-head{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;padding-bottom:24px;border-bottom:1px solid #e5e4e0;}
.sol-eyebrow{font-size:.65rem;letter-spacing:.14em;text-transform:uppercase;color:#00c08b;font-weight:700;margin-bottom:6px;display:flex;align-items:center;gap:6px;}
.sol-eyebrow span{opacity:.6;color:#6b6a65;}
.sol-head h1{font-size:1.5rem;font-weight:800;color:#1a1a18;margin:0 0 5px;letter-spacing:-.01em;}
.sol-head p{font-size:.8rem;color:#6b6a65;margin:0;max-width:420px;}
.sol-back{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border:1px solid #e5e4e0;border-radius:8px;font-size:.78rem;color:#6b6a65;text-decoration:none;transition:all .15s;white-space:nowrap;background:#fff;}
.sol-back:hover{border-color:#00c08b;color:#008f68;text-decoration:none;}
.sol-card{background:#fff;border:1px solid #e5e4e0;border-radius:14px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06),0 1px 2px rgba(0,0,0,.04);}
.sol-card-head{padding:16px 22px;border-bottom:1px solid #e5e4e0;display:flex;align-items:center;gap:12px;background:#f9f9f8;}
.sol-card-icon{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;}
.sol-card-icon.teal{background:rgba(0,192,139,.12);color:#00c08b;}
.sol-card-icon.blue{background:rgba(51,204,255,.12);color:#006b88;}
.sol-card-title{font-size:.88rem;font-weight:700;color:#1a1a18;}
.sol-card-hint{font-size:.72rem;color:#9a9890;margin-left:auto;}
.sol-card-body{padding:22px;}
.sol-grid{display:grid;gap:16px;}
.sol-grid.two{grid-template-columns:1fr 1fr;}
.sol-grid.three{grid-template-columns:2fr 1fr 2fr;}
@media(max-width:640px){.sol-grid.two,.sol-grid.three{grid-template-columns:1fr;}}
.sol-field{display:flex;flex-direction:column;gap:6px;}
.sol-label{font-size:.68rem;font-weight:700;color:#9a9890;text-transform:uppercase;letter-spacing:.08em;}
.sol-req{color:#e24b4a;margin-left:2px;}
.sol-input,.sol-textarea{width:100%;padding:10px 14px;background:#f9f9f8;border:1px solid #e5e4e0;border-radius:9px;color:#1a1a18;font-size:.85rem;font-family:'DM Sans',sans-serif;transition:border-color .15s,box-shadow .15s;-webkit-appearance:none;}
.sol-input::placeholder,.sol-textarea::placeholder{color:#9a9890;}
.sol-input:focus,.sol-textarea:focus{outline:none;border-color:#00c08b;box-shadow:0 0 0 3px rgba(0,192,139,.12);}
.sol-textarea{resize:vertical;min-height:110px;line-height:1.55;}
.sol-urgency-row{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;}
@media(max-width:480px){.sol-urgency-row{grid-template-columns:1fr;}}
.sol-urgency-btn{padding:12px 10px;border-radius:10px;border:1px solid #e5e4e0;background:#fff;color:#6b6a65;font-size:.8rem;font-weight:600;cursor:pointer;text-align:center;transition:all .18s;display:flex;flex-direction:column;align-items:center;gap:6px;font-family:'DM Sans',sans-serif;}
.sol-urgency-btn .urg-icon{font-size:1.2rem;}
.sol-urgency-btn:hover{border-color:#9a9890;color:#1a1a18;background:#f9f9f8;}
.sol-urgency-btn.active-low{border-color:#00c08b;background:#e6faf4;color:#008f68;}
.sol-urgency-btn.active-med{border-color:#ffc107;background:#fff8e1;color:#8a6a00;}
.sol-urgency-btn.active-high{border-color:#e24b4a;background:#fcebeb;color:#791f1f;}
.sol-itens-list{display:flex;flex-direction:column;gap:8px;}
.sol-item-row{display:grid;grid-template-columns:1fr 90px 1fr 34px;gap:10px;align-items:end;background:#f9f9f8;border:1px solid #e5e4e0;border-radius:10px;padding:12px 14px;}
@media(max-width:600px){.sol-item-row{grid-template-columns:1fr;}}
.sol-btn-add{display:inline-flex;align-items:center;gap:7px;padding:9px 16px;margin-top:12px;background:transparent;border:1px dashed #e5e4e0;border-radius:9px;color:#6b6a65;font-size:.78rem;cursor:pointer;transition:all .18s;font-family:'DM Sans',sans-serif;}
.sol-btn-add:hover{border-color:#00c08b;color:#008f68;}
.sol-btn-rm{width:30px;height:30px;border-radius:7px;border:1px solid #e5e4e0;background:#fff;color:#6b6a65;font-size:.72rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s;flex-shrink:0;}
.sol-btn-rm:hover{background:#fcebeb;border-color:#e24b4a;color:#e24b4a;}
.sol-tip{background:#e6faf4;border:1px solid rgba(0,192,139,.22);border-radius:10px;padding:14px 16px;font-size:.78rem;color:#6b6a65;display:flex;gap:12px;align-items:flex-start;line-height:1.55;}
.sol-tip i{color:#00c08b;font-size:1rem;margin-top:1px;flex-shrink:0;}
.sol-actions{display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:16px 22px;background:#fff;border:1px solid #e5e4e0;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,.06);}
.sol-btn-cancel{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;background:transparent;color:#6b6a65;border:1px solid #e5e4e0;border-radius:9px;font-size:.82rem;cursor:pointer;text-decoration:none;transition:all .15s;}
.sol-btn-cancel:hover{color:#1a1a18;border-color:#9a9890;text-decoration:none;}
.sol-btn-submit{display:inline-flex;align-items:center;gap:8px;padding:11px 28px;background:linear-gradient(135deg,#00c08b,#008f68);color:#fff;border:none;border-radius:9px;font-size:.88rem;font-weight:700;cursor:pointer;transition:all .18s;font-family:'DM Sans',sans-serif;box-shadow:0 4px 14px rgba(0,192,139,.22);}
.sol-btn-submit:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(0,192,139,.3);}
.sol-btn-submit:active{transform:translateY(0);}
</style>

<div class="col-md-12">
<div class="sol-page-wrap">
<div class="sol-root">

    <!-- Head -->
    <div class="sol-head">
        <div>
            <div class="sol-eyebrow">
                Portal do Cliente <span>›</span> Orçamentos
            </div>
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
            <div class="sol-card-icon teal"><i class="fas fa-clipboard-list"></i></div>
            <span class="sol-card-title">Dados da Solicitação</span>
            <span class="sol-card-hint">* campos obrigatórios</span>
        </div>
        <div class="sol-card-body">
            <div class="sol-grid two">
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

            <div class="sol-field" style="margin-top:16px;">
                <label class="sol-label" for="sol-descricao">Descrição / Contexto <span class="sol-req">*</span></label>
                <textarea class="sol-textarea" id="sol-descricao" name="descricao" required
                    placeholder="Descreva o que precisa, contexto do projeto, volume, restrições técnicas ou qualquer informação relevante…" rows="5"></textarea>
            </div>

            <div class="sol-field" style="margin-top:16px;">
                <label class="sol-label">Urgência</label>
                <div class="sol-urgency-row" id="urgencyRow">
                    <button type="button" class="sol-urgency-btn active-low" data-value="Normal" onclick="setUrgency(this,'active-low')">
                        <span class="urg-icon">✅</span>Normal
                    </button>
                    <button type="button" class="sol-urgency-btn" data-value="Média" onclick="setUrgency(this,'active-med')">
                        <span class="urg-icon">⚠️</span>Média
                    </button>
                    <button type="button" class="sol-urgency-btn" data-value="Alta" onclick="setUrgency(this,'active-high')">
                        <span class="urg-icon">🔥</span>Alta
                    </button>
                </div>
                <input type="hidden" name="urgencia" id="inp-urgencia" value="Normal">
            </div>
        </div>
    </div>

    <!-- Seção 2: Itens -->
    <div class="sol-card">
        <div class="sol-card-head">
            <div class="sol-card-icon blue"><i class="fas fa-list-ul"></i></div>
            <span class="sol-card-title">Itens / Produtos / Serviços</span>
            <span class="sol-card-hint">Adicione os itens que precisam de orçamento (opcional)</span>
        </div>
        <div class="sol-card-body">
            <div class="sol-itens-list" id="itensList">
                <div class="sol-item-row" id="item-0">
                    <div class="sol-field" style="margin:0;">
                        <label class="sol-label" style="font-size:.63rem;">Descrição do item</label>
                        <input class="sol-input" name="itens[0][descricao]" type="text" placeholder="Ex.: Licença Microsoft 365, Switch 24 portas…">
                    </div>
                    <div class="sol-field" style="margin:0;">
                        <label class="sol-label" style="font-size:.63rem;">Qtde</label>
                        <input class="sol-input" name="itens[0][quantidade]" type="number" min="1" value="1" style="text-align:center;">
                    </div>
                    <div class="sol-field" style="margin:0;">
                        <label class="sol-label" style="font-size:.63rem;">Observação</label>
                        <input class="sol-input" name="itens[0][obs]" type="text" placeholder="Especificações, marca, modelo…">
                    </div>
                    <button type="button" class="sol-btn-rm" onclick="rmItem('item-0')" title="Remover item">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <button type="button" class="sol-btn-add" onclick="addItem()">
                <i class="fas fa-plus"></i> Adicionar item
            </button>
        </div>
    </div>

    <!-- Dica -->
    <div class="sol-tip">
        <i class="fas fa-lightbulb"></i>
        <div>
            <strong style="color:#c9d1d9;display:block;margin-bottom:3px;">Dica para uma proposta mais precisa</strong>
            Quanto mais detalhes você fornecer, mais precisa será a proposta. Informe marca/modelo preferencial,
            prazo, local de entrega/instalação e qualquer restrição de orçamento.
            Nossa equipe entrará em contato em até 2 dias úteis.
        </div>
    </div>

    <!-- Footer de ação -->
    <div class="sol-actions">
        <?= $this->Html->link(
            '<i class="fas fa-times"></i> Cancelar',
            ['action' => 'index'],
            ['class' => 'sol-btn-cancel', 'escape' => false]
        ) ?>
        <button type="submit" class="sol-btn-submit">
            <i class="fas fa-paper-plane"></i> Enviar Solicitação
        </button>
    </div>

    <?= $this->Form->end() ?>

</div><!-- /sol-root -->
</div><!-- /sol-page-wrap -->
</div><!-- /col-md-12 -->

<script>
(function() {
    var itemCount = 1;

    window.setUrgency = function(btn, cls) {
        document.querySelectorAll('.sol-urgency-btn').forEach(function(b) {
            b.classList.remove('active-low', 'active-med', 'active-high');
        });
        btn.classList.add(cls);
        document.getElementById('inp-urgencia').value = btn.getAttribute('data-value');
    };

    window.addItem = function() {
        var idx = itemCount++;
        var row = document.createElement('div');
        row.className = 'sol-item-row';
        row.id = 'item-' + idx;
        row.innerHTML =
            '<div class="sol-field" style="margin:0;">' +
                '<label class="sol-label" style="font-size:.63rem;">Descrição do item</label>' +
                '<input class="sol-input" name="itens[' + idx + '][descricao]" type="text" placeholder="Ex.: Licença, equipamento, serviço…">' +
            '</div>' +
            '<div class="sol-field" style="margin:0;">' +
                '<label class="sol-label" style="font-size:.63rem;">Qtde</label>' +
                '<input class="sol-input" name="itens[' + idx + '][quantidade]" type="number" min="1" value="1" style="text-align:center;">' +
            '</div>' +
            '<div class="sol-field" style="margin:0;">' +
                '<label class="sol-label" style="font-size:.63rem;">Observação</label>' +
                '<input class="sol-input" name="itens[' + idx + '][obs]" type="text" placeholder="Especificações, marca, modelo…">' +
            '</div>' +
            '<button type="button" class="sol-btn-rm" onclick="rmItem(\'item-' + idx + '\')" title="Remover"><i class="fas fa-times"></i></button>';
        document.getElementById('itensList').appendChild(row);
    };

    window.rmItem = function(id) {
        var el = document.getElementById(id);
        if (el && document.getElementById('itensList').children.length > 1) {
            el.remove();
        }
    };
})();
</script>
