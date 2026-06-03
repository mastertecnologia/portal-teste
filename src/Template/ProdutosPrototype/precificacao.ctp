<?php
/**
 * Centro de Cálculo de Precificação — pg-precificacao (paridade mock).
 *
 * @var array<int,string> $precificOpcoes
 * @var string $precificProdutosJson
 * @var string $precificEmpresaJson
 * @var int $precificProdutoId
 * @var array<string,string> $precificInicial
 * @var int $precificTabelaAtivaId
 */
$ini = $precificInicial;
$nav = $this->ErpPrototype->navLinkOpts();
$urlPrecos = ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos'];
$urlHist = ['controller' => 'ProdutosPrototype', 'action' => 'view', 'historico-precos'];
$jsSim = $this->Url->build('/js/pgm-precificacao-simulador.js') . '?v=20260604';
$urlAplicar = ['controller' => 'ProdutosPrototype', 'action' => 'precificacaoAplicar'];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;"><?= h(__('Produtos ›')) ?> <?= $this->Html->link(__('Tabela de preços'), $urlPrecos, array_merge($nav, ['style' => 'color:var(--teal);'])) ?> › <?= h(__('Centro de Cálculo')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🧮 <?= h(__('Centro de Cálculo de Precificação')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);margin-top:2px;"><?= h(__('Simulador profissional · Tributação 2026 · Atualizado conforme LC 224/2025')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('📜 ' . __('Histórico'), $urlHist, array_merge($nav, ['class' => 'btn btn-ghost btn-sm'])) ?>
		<button type="button" class="btn btn-ghost btn-sm" onclick="window.print()">📥 <?= h(__('Exportar PDF')) ?></button>
		<button type="button" class="btn btn-ghost btn-sm" onclick="alert('<?= h(__('Simulação arquivada no protótipo (histórico de precificação).')) ?>')">💾 <?= h(__('Salvar simulação')) ?></button>
		<button type="button" class="btn btn-primary btn-sm" onclick="PgmPrecificacao.aplicarAoProduto('precificacao-aplicar-form')">✓ <?= h(__('Aplicar ao produto')) ?></button>
	</div>
</div>

<div class="alert-box alert-blue" style="margin-bottom:14px;">
	📚 <strong><?= h(__('Tributação 2026:')) ?></strong> <?= h(__('alíquotas atualizadas conforme LC 123/2006 (Simples), Lei 9.249/95 (Presumido/Real), LC 224/2025 (presunção majorada acima R$ 5M/ano) e LC 214/2025 (Reforma Tributária · IBS/CBS em fase de teste). Consulte sempre seu contador.')) ?>
</div>

<?php if (!empty($precificOpcoes)) : ?>
<div class="card" style="margin-bottom:14px;padding:12px 14px;">
	<div class="field" style="margin:0;">
		<label><?= h(__('Carregar custo de um produto do catálogo (opcional)')) ?></label>
		<select id="prec-produto-base">
			<option value="0"><?= h(__('— Simulação manual —')) ?></option>
			<?php foreach ($precificOpcoes as $id => $lbl) :
				$sel = (int)$id === (int)$precificProdutoId ? ' selected' : '';
			?>
				<option value="<?= (int)$id ?>"<?= $sel ?>><?= h((string)$lbl) ?></option>
			<?php endforeach; ?>
		</select>
		<div id="prec-produto-info" style="display:none;margin-top:8px;padding:10px;background:var(--teal-light);border-radius:6px;font-size:12px;color:var(--teal-dark);"></div>
		<?php if ((int)($precificTabelaAtivaId ?? 0) > 0) : ?>
			<div style="font-size:11px;color:var(--text-muted);margin-top:6px;"><?= h(__('Preços de venda da tabela ativa de preços aplicados aos serviços/produtos vinculados.')) ?></div>
		<?php endif; ?>
	</div>
	<?= $this->Form->create(null, ['url' => $urlAplicar, 'id' => 'precificacao-aplicar-form', 'style' => 'display:none;']) ?>
	<input type="hidden" name="produto_id" id="precificacao-produto-id" value="<?= (int)($ini['produto_id'] ?? $precificProdutoId) ?>">
	<input type="hidden" name="vlunitario" id="precificacao-vl-hidden" value="0">
	<?= $this->Form->end() ?>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:14px;">
	<div class="sec-title">1️⃣ <?= h(__('Regime tributário e atividade')) ?></div>
	<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px;">
		<label style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:14px 10px;background:var(--teal-light);border:2px solid var(--teal);border-radius:var(--radius);cursor:pointer;text-align:center;" id="reg-card-simples">
			<input type="radio" name="regime" value="simples" checked onchange="PgmPrecificacao.trocarRegime('simples')"/>
			<div style="font-size:24px;">📊</div>
			<div style="font-size:13px;font-weight:600;"><?= h(__('Simples Nacional')) ?></div>
			<div style="font-size:10px;color:var(--text-muted);"><?= h(__('Faturamento até R$ 4,8M/ano · DAS unificado')) ?></div>
		</label>
		<label style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:14px 10px;background:var(--bg-surface);border:2px solid var(--border-light);border-radius:var(--radius);cursor:pointer;text-align:center;" id="reg-card-presumido">
			<input type="radio" name="regime" value="presumido" onchange="PgmPrecificacao.trocarRegime('presumido')"/>
			<div style="font-size:24px;">📈</div>
			<div style="font-size:13px;font-weight:600;"><?= h(__('Lucro Presumido')) ?></div>
			<div style="font-size:10px;color:var(--text-muted);"><?= h(__('Até R$ 78M/ano · presunção 8%/32%')) ?></div>
		</label>
		<label style="display:flex;flex-direction:column;align-items:center;gap:6px;padding:14px 10px;background:var(--bg-surface);border:2px solid var(--border-light);border-radius:var(--radius);cursor:pointer;text-align:center;" id="reg-card-real">
			<input type="radio" name="regime" value="real" onchange="PgmPrecificacao.trocarRegime('real')"/>
			<div style="font-size:24px;">🎯</div>
			<div style="font-size:13px;font-weight:600;"><?= h(__('Lucro Real')) ?></div>
			<div style="font-size:10px;color:var(--text-muted);"><?= h(__('Sem limite · não-cumulativo · com créditos')) ?></div>
		</label>
	</div>
	<div class="g2" style="margin-bottom:12px;">
		<div class="field">
			<label><?= h(__('Tipo de operação')) ?> *</label>
			<select id="prec-operacao">
				<option value="comercio"><?= h(__('Comércio · revenda de produtos')) ?></option>
				<option value="industria"><?= h(__('Indústria · transformação')) ?></option>
				<option value="servico" selected><?= h(__('Prestação de serviços')) ?></option>
				<option value="locacao"><?= h(__('Locação de bens')) ?></option>
				<option value="misto"><?= h(__('Comércio + serviços (atividade mista)')) ?></option>
			</select>
		</div>
		<div class="field">
			<label><?= h(__('Receita bruta · 12 meses (RBT12)')) ?></label>
			<input type="text" id="prec-rbt12" value="<?= h((string)($ini['rbt12'] ?? 'R$ 1.420.000,00')) ?>"/>
			<div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= h(__('Base para definição da faixa do Simples ou aplicação do limite de R$ 5M no Presumido')) ?></div>
		</div>
	</div>
	<div id="prec-simples-config">
		<div class="g2" style="margin-bottom:0;">
			<div class="field">
				<label><?= h(__('Anexo do Simples Nacional')) ?></label>
				<select id="prec-anexo">
					<option value="I"><?= h(__('Anexo I · Comércio (4% a 19%)')) ?></option>
					<option value="II"><?= h(__('Anexo II · Indústria (4,5% a 30%)')) ?></option>
					<option value="III" selected><?= h(__('Anexo III · Serviços com Fator R ≥ 28% (6% a 33%)')) ?></option>
					<option value="IV"><?= h(__('Anexo IV · Serviços com INSS patronal (4,5% a 33%)')) ?></option>
					<option value="V"><?= h(__('Anexo V · Serviços com Fator R < 28% (15,5% a 30,5%)')) ?></option>
				</select>
			</div>
			<div class="field">
				<label><?= h(__('Fator R (folha ÷ receita)')) ?> <span style="font-size:10px;color:var(--text-muted);"><?= h(__('opcional')) ?></span></label>
				<div style="display:flex;gap:6px;align-items:center;">
					<input type="text" id="prec-fator-r" value="32" style="flex:1;"/>
					<span style="font-size:13px;color:var(--text-muted);">%</span>
					<span class="badge b-paga" id="prec-fator-status" style="font-size:10px;">✓ <?= h(__('Anexo III')) ?></span>
				</div>
			</div>
		</div>
		<div class="alert-box alert-teal" style="margin-top:10px;margin-bottom:0;font-size:11px;">
			💡 <strong><?= h(__('Faixa atual detectada:')) ?></strong> <span id="prec-faixa-info">—</span>
		</div>
	</div>
	<div id="prec-presumido-config" style="display:none;">
		<div class="g2" style="margin-bottom:0;">
			<div class="field">
				<label><?= h(__('Atividade · presunção IRPJ')) ?></label>
				<select id="prec-pres-irpj">
					<option value="1.6">1,6% · <?= h(__('revenda de combustíveis')) ?></option>
					<option value="8" selected>8% · <?= h(__('comércio, indústria, transporte de cargas')) ?></option>
					<option value="16">16% · <?= h(__('serviços de transporte (exceto carga)')) ?></option>
					<option value="32">32% · <?= h(__('prestação de serviços em geral')) ?></option>
				</select>
			</div>
			<div class="field">
				<label><?= h(__('Atividade · presunção CSLL')) ?></label>
				<select id="prec-pres-csll">
					<option value="12" selected>12% · <?= h(__('comércio, indústria, transporte de cargas')) ?></option>
					<option value="32">32% · <?= h(__('serviços em geral')) ?></option>
				</select>
			</div>
		</div>
		<div class="alert-box alert-amber" style="margin-top:10px;margin-bottom:0;font-size:11px;">
			⚠ <strong>LC 224/2025:</strong> <?= h(__('empresas com receita acima de R$ 5M/ano têm presunção majorada em 10% sobre o excedente.')) ?>
			<span id="prec-aviso-lc224"></span>
		</div>
	</div>
	<div id="prec-real-config" style="display:none;">
		<div class="g2" style="margin-bottom:0;">
			<div class="field">
				<label><?= h(__('Margem de lucro real estimada (%)')) ?></label>
				<input type="text" id="prec-margem-real" value="18,00"/>
			</div>
			<div class="field">
				<label><?= h(__('% de créditos PIS/COFINS aproveitáveis')) ?></label>
				<input type="text" id="prec-creditos" value="35,00"/>
			</div>
		</div>
		<div class="alert-box alert-teal" style="margin-top:10px;margin-bottom:0;font-size:11px;">
			💡 <?= h(__('Lucro Real é vantajoso quando a margem real é menor que a presunção ou há muitos créditos de PIS/COFINS.')) ?>
		</div>
	</div>
</div>

<div class="g2" style="margin-bottom:14px;">
	<div class="card">
		<div class="sec-title">2️⃣ <?= h(__('Custos diretos do produto')) ?></div>
		<div class="field" style="margin-bottom:10px;">
			<label><?= h(__('Preço de venda atual (referência)')) ?></label>
			<div style="display:flex;gap:6px;align-items:center;">
				<span style="font-size:13px;color:var(--text-muted);">R$</span>
				<input type="text" id="prec-venda-atual" value="<?= h((string)($ini['venda'] ?? '0,00')) ?>" readonly style="flex:1;font-weight:600;font-size:15px;text-align:right;background:var(--bg-surface);"/>
			</div>
			<div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= h(__('Tabela vigente, cadastro do produto ou ERP (GetEstoqueProdutos).')) ?></div>
		</div>
		<div class="field" style="margin-bottom:10px;">
			<label><?= h(__('Custo de aquisição / produção')) ?> *</label>
			<div style="display:flex;gap:6px;align-items:center;">
				<span style="font-size:13px;color:var(--text-muted);">R$</span>
				<input type="text" id="prec-custo" value="<?= h((string)$ini['custo']) ?>" style="flex:1;font-weight:700;font-size:16px;text-align:right;"/>
			</div>
			<div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= h(__('Para produtos: preço de compra. Para serviços: custo da hora técnica × tempo previsto.')) ?></div>
		</div>
		<div class="g2" style="margin-bottom:10px;">
			<div class="field"><label><?= h(__('Frete e logística (R$)')) ?></label><input type="text" id="prec-frete" value="<?= h((string)$ini['frete']) ?>"/></div>
			<div class="field"><label><?= h(__('Embalagem (R$)')) ?></label><input type="text" id="prec-embal" value="0,00"/></div>
		</div>
		<div class="g2" style="margin-bottom:10px;">
			<div class="field"><label><?= h(__('Outros custos diretos (R$)')) ?></label><input type="text" id="prec-outros-custos" value="0,00"/></div>
			<div class="field"><label><?= h(__('ICMS-ST / IPI substituição (R$)')) ?></label><input type="text" id="prec-icmsst" value="0,00"/></div>
		</div>
		<div style="background:var(--bg-surface);padding:12px 14px;border-radius:var(--radius);display:flex;justify-content:space-between;align-items:center;">
			<span style="font-size:12px;color:var(--text-muted);font-weight:600;"><?= h(__('CUSTO TOTAL DO PRODUTO')) ?></span>
			<strong id="prec-custo-total" style="font-size:18px;font-variant-numeric:tabular-nums;">R$ 1.050,00</strong>
		</div>
	</div>
	<div class="card">
		<div class="sec-title">3️⃣ <?= h(__('Despesas operacionais (% sobre venda)')) ?></div>
		<div class="g2" style="margin-bottom:10px;">
			<div class="field"><label><?= h(__('Despesas administrativas (%)')) ?></label><input type="text" id="prec-desp-adm" value="8,00"/></div>
			<div class="field"><label><?= h(__('Despesas comerciais (%)')) ?></label><input type="text" id="prec-desp-com" value="5,00"/></div>
		</div>
		<div class="g2" style="margin-bottom:10px;">
			<div class="field"><label><?= h(__('Comissão do vendedor (%)')) ?></label><input type="text" id="prec-comissao" value="3,00"/></div>
			<div class="field"><label><?= h(__('Taxas (cartão / boleto / gateway) (%)')) ?></label><input type="text" id="prec-taxa-pagto" value="2,50"/></div>
		</div>
		<div class="g2" style="margin-bottom:10px;">
			<div class="field"><label><?= h(__('Inadimplência prevista (%)')) ?></label><input type="text" id="prec-inadim" value="1,50"/></div>
			<div class="field"><label><?= h(__('Frete saída ao cliente (%)')) ?></label><input type="text" id="prec-frete-saida" value="0,00"/></div>
		</div>
		<div class="field" style="margin-bottom:10px;">
			<label><?= h(__('Margem de lucro líquida desejada (%)')) ?> *</label>
			<input type="text" id="prec-margem" value="20,00" style="font-weight:700;font-size:15px;"/>
		</div>
		<div style="background:#FAEEDA;padding:12px 14px;border-radius:var(--radius);display:flex;justify-content:space-between;align-items:center;">
			<span style="font-size:12px;color:#8A4D02;font-weight:600;"><?= h(__('DESPESAS + MARGEM (% sobre venda)')) ?></span>
			<strong id="prec-desp-total-pct" style="font-size:18px;color:#8A4D02;font-variant-numeric:tabular-nums;">40,00%</strong>
		</div>
	</div>
</div>

<div class="card" style="margin-bottom:14px;">
	<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:10px;">
		<div class="sec-title" style="margin:0;border:none;">4️⃣ <?= h(__('Tributos aplicáveis ·')) ?> <span id="prec-regime-label" style="color:var(--teal-dark);"><?= h(__('Simples Nacional · Anexo III')) ?></span></div>
		<div style="display:flex;gap:6px;">
			<button type="button" class="btn btn-ghost btn-xs" onclick="PgmPrecificacao.alternarTributos()" id="btn-toggle-trib">▼ <?= h(__('Detalhar')) ?></button>
			<button type="button" class="btn btn-ghost btn-xs" onclick="alert('<?= h(__('Simples: alíquota efetiva = (RBT12 × Nominal − Dedução) ÷ RBT12. Presumido: IRPJ 15% + CSLL 9% + PIS/COFINS cumulativo. Real: sobre lucro com créditos.')) ?>')">❔ <?= h(__('Ajuda')) ?></button>
		</div>
	</div>
	<div id="prec-trib-resumo" style="display:flex;gap:10px;flex-wrap:wrap;">
		<div style="flex:1;min-width:160px;padding:14px;background:var(--teal-light);border-radius:var(--radius);text-align:center;border-left:3px solid var(--teal);">
			<div style="font-size:11px;color:var(--teal-dark);text-transform:uppercase;font-weight:600;"><?= h(__('Carga federal')) ?></div>
			<div style="font-size:22px;font-weight:700;color:var(--teal-dark);" id="prec-carga-fed">—</div>
			<div style="font-size:11px;color:var(--text-muted);" id="prec-carga-fed-rs">—</div>
		</div>
		<div style="flex:1;min-width:160px;padding:14px;background:var(--blue-light);border-radius:var(--radius);text-align:center;border-left:3px solid var(--blue);">
			<div style="font-size:11px;color:#0C447C;text-transform:uppercase;font-weight:600;"><?= h(__('Carga estadual/municipal')) ?></div>
			<div style="font-size:22px;font-weight:700;color:#0C447C;" id="prec-carga-est">—</div>
			<div style="font-size:11px;color:var(--text-muted);" id="prec-carga-est-rs">—</div>
		</div>
		<div style="flex:1;min-width:160px;padding:14px;background:#FCE0EC;border-radius:var(--radius);text-align:center;border-left:3px solid #D946A0;">
			<div style="font-size:11px;color:#7A1B5C;text-transform:uppercase;font-weight:600;"><?= h(__('Reforma Trib. (IBS+CBS)')) ?></div>
			<div style="font-size:22px;font-weight:700;color:#7A1B5C;" id="prec-carga-ibs">—</div>
			<div style="font-size:11px;color:var(--text-muted);" id="prec-carga-ibs-rs"><?= h(__('teste · compensável')) ?></div>
		</div>
		<div style="flex:1;min-width:160px;padding:14px;background:#FAEEDA;border-radius:var(--radius);text-align:center;border-left:3px solid var(--amber);">
			<div style="font-size:11px;color:#8A4D02;text-transform:uppercase;font-weight:600;"><?= h(__('Carga total')) ?></div>
			<div style="font-size:22px;font-weight:700;color:#8A4D02;" id="prec-carga-total">—</div>
			<div style="font-size:11px;color:var(--text-muted);" id="prec-carga-total-rs">—</div>
		</div>
	</div>
	<div id="prec-trib-detalhe" style="display:none;margin-top:14px;">
		<div style="overflow-x:auto;">
			<table style="width:100%;border-collapse:collapse;font-size:12px;">
				<thead>
					<tr style="background:var(--bg-surface);border-bottom:1px solid var(--border);">
						<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Tributo')) ?></th>
						<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Esfera')) ?></th>
						<th style="padding:10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Base de cálculo')) ?></th>
						<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Alíquota')) ?></th>
						<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);"><?= h(__('Valor (R$)')) ?></th>
						<th style="padding:10px;text-align:right;font-size:11px;text-transform:uppercase;color:var(--text-muted);">% <?= h(__('sobre venda')) ?></th>
					</tr>
				</thead>
				<tbody id="prec-trib-tabela"></tbody>
				<tfoot id="prec-trib-foot"></tfoot>
			</table>
		</div>
		<div class="alert-box alert-blue" style="margin-top:14px;margin-bottom:0;font-size:11px;">
			ℹ <strong>IBS + CBS:</strong> <?= h(__('em 2026 a alíquota de teste é de 1% destacada na NF-e mas integralmente compensada nas guias de PIS/COFINS.')) ?>
		</div>
	</div>
</div>

<div class="card" style="margin-bottom:14px;background:linear-gradient(135deg,#0a3d2c 0%,#1D9E75 100%);color:#fff;border:none;">
	<div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
		<div style="font-size:24px;">🎯</div>
		<div style="font-size:16px;font-weight:700;">5️⃣ <?= h(__('Preço de venda sugerido')) ?></div>
	</div>
	<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
		<div style="background:rgba(255,255,255,.15);padding:18px;border-radius:var(--radius);">
			<div style="font-size:11px;opacity:.85;text-transform:uppercase;font-weight:600;"><?= h(__('Custo total')) ?></div>
			<div style="font-size:24px;font-weight:700;font-variant-numeric:tabular-nums;" id="prec-res-custo">—</div>
		</div>
		<div style="background:rgba(255,255,255,.15);padding:18px;border-radius:var(--radius);">
			<div style="font-size:11px;opacity:.85;text-transform:uppercase;font-weight:600;"><?= h(__('Mark-up divisor')) ?></div>
			<div style="font-size:24px;font-weight:700;font-variant-numeric:tabular-nums;" id="prec-res-divisor">—</div>
		</div>
	</div>
	<div style="background:rgba(255,255,255,.25);padding:24px;border-radius:var(--radius);text-align:center;margin-bottom:14px;">
		<div style="font-size:12px;opacity:.85;text-transform:uppercase;font-weight:600;letter-spacing:.6px;"><?= h(__('PREÇO DE VENDA SUGERIDO')) ?></div>
		<div style="font-size:42px;font-weight:700;font-variant-numeric:tabular-nums;line-height:1.1;margin-top:4px;" id="prec-res-preco">—</div>
		<div style="font-size:13px;opacity:.85;margin-top:4px;"><?= h(__('por unidade · líquido para o cliente')) ?></div>
	</div>
	<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;font-size:11px;">
		<div style="padding:10px;background:rgba(255,255,255,.15);border-radius:var(--radius);text-align:center;"><div style="opacity:.85;"><?= h(__('Markup')) ?></div><strong style="font-size:14px;" id="prec-res-markup">—</strong></div>
		<div style="padding:10px;background:rgba(255,255,255,.15);border-radius:var(--radius);text-align:center;"><div style="opacity:.85;"><?= h(__('Margem bruta')) ?></div><strong style="font-size:14px;" id="prec-res-margem-bruta">—</strong></div>
		<div style="padding:10px;background:rgba(255,255,255,.15);border-radius:var(--radius);text-align:center;"><div style="opacity:.85;"><?= h(__('Margem líquida')) ?></div><strong style="font-size:14px;" id="prec-res-margem">—</strong></div>
		<div style="padding:10px;background:rgba(255,255,255,.15);border-radius:var(--radius);text-align:center;"><div style="opacity:.85;"><?= h(__('Lucro líquido')) ?></div><strong style="font-size:14px;" id="prec-res-lucro">—</strong></div>
	</div>
</div>

<div class="g2">
	<div class="card">
		<div class="sec-title">📊 <?= h(__('DRE Gerencial · por unidade')) ?></div>
		<div style="overflow-x:auto;">
			<table style="width:100%;border-collapse:collapse;font-size:12px;">
				<tbody id="prec-dre-body"></tbody>
			</table>
		</div>
	</div>
	<div class="card">
		<div class="sec-title">⚖ <?= h(__('Comparativo de regimes · este produto')) ?></div>
		<div style="display:flex;flex-direction:column;gap:8px;">
			<div style="padding:10px 12px;background:var(--bg-surface);border-radius:var(--radius);border-left:3px solid var(--teal);" id="comp-simples">
				<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;"><strong style="font-size:13px;"><?= h(__('Simples Nacional')) ?></strong><strong style="font-size:14px;color:var(--teal-dark);" id="comp-simples-preco">—</strong></div>
				<div style="font-size:11px;color:var(--text-muted);"><?= h(__('Carga:')) ?> <span id="comp-simples-carga">—</span> · <?= h(__('Lucro:')) ?> <span id="comp-simples-lucro">—</span></div>
			</div>
			<div style="padding:10px 12px;background:var(--bg-surface);border-radius:var(--radius);border-left:3px solid var(--blue);" id="comp-presumido">
				<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;"><strong style="font-size:13px;"><?= h(__('Lucro Presumido')) ?></strong><strong style="font-size:14px;" id="comp-presumido-preco">—</strong></div>
				<div style="font-size:11px;color:var(--text-muted);"><?= h(__('Carga:')) ?> <span id="comp-presumido-carga">—</span> · <?= h(__('Lucro:')) ?> <span id="comp-presumido-lucro">—</span></div>
			</div>
			<div style="padding:10px 12px;background:var(--bg-surface);border-radius:var(--radius);border-left:3px solid #D946A0;" id="comp-real">
				<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;"><strong style="font-size:13px;"><?= h(__('Lucro Real')) ?></strong><strong style="font-size:14px;" id="comp-real-preco">—</strong></div>
				<div style="font-size:11px;color:var(--text-muted);"><?= h(__('Carga:')) ?> <span id="comp-real-carga">—</span> · <?= h(__('Lucro:')) ?> <span id="comp-real-lucro">—</span></div>
			</div>
		</div>
		<div class="alert-box alert-teal" style="margin-top:12px;margin-bottom:0;font-size:11px;" id="prec-comp-recomenda">—</div>
	</div>
</div>

<script>
window.PGM_PREC_PRODUTOS = <?= $precificProdutosJson ?: '{}' ?>;
window.PGM_PREC_EMPRESA = <?= $precificEmpresaJson ?: '{}' ?>;
</script>
<script src="<?= h($jsSim) ?>"></script>
<script>
(function () {
	function runBoot() {
		if (typeof window.pgmPrecificacaoBoot === 'function') {
			window.pgmPrecificacaoBoot();
		}
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', runBoot);
	} else {
		runBoot();
	}
	function onFrameReady(e) {
		if (!e.target || e.target.id !== 'pgm-main-frame') {
			return;
		}
		setTimeout(runBoot, 0);
	}
	document.addEventListener('turbo:frame-load', onFrameReady);
	document.addEventListener('turbo:frame-render', onFrameReady);
})();
</script>
