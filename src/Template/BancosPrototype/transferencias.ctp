<?php
/**
 * Transferências & PIX — mockup pg-transferencias.
 *
 * @var \App\View\AppView $this
 * @var array{empresa_nome:string,empresa_cnpj:string,data_hoje:string} $tfMeta
 * @var array<int,array<string,mixed>> $tfContas
 * @var array<int,array<string,mixed>> $tfCentrosCusto
 * @var array<string,string> $tfCategorias
 * @var array<int,array<string,mixed>> $tfDocumentos
 * @var array<int,array<string,mixed>> $tfPixChaves
 * @var array{banco:string,chave:string} $tfQrCode
 * @var array<string,mixed>|null $tfDestinatario
 * @var array<int,array<string,mixed>> $tfLotePagamentos
 * @var array<int,array<string,mixed>> $tfHistorico
 * @var array<int,array<string,mixed>> $tfRemessas
 * @var array<int,array<string,string>> $tfBancosCatalogo
 */
$H = $this->ErpPrototype;
$urlPix = $this->Url->build(['controller' => 'BancosPrototype', 'action' => 'enviarPix']);
$urlLista = ['controller' => 'BancosPrototype', 'action' => 'lista'];
$tfMeta = $tfMeta ?? ['empresa_nome' => '', 'empresa_cnpj' => '', 'data_hoje' => date('Y-m-d')];
$tfContas = $tfContas ?? [];
$tfCentrosCusto = $tfCentrosCusto ?? [];
$tfCategorias = $tfCategorias ?? [];
$tfDocumentos = $tfDocumentos ?? [];
$tfPixChaves = $tfPixChaves ?? [];
$tfQrCode = $tfQrCode ?? ['banco' => '—', 'chave' => ''];
$tfDestinatario = $tfDestinatario ?? null;
$tfLotePagamentos = $tfLotePagamentos ?? [];
$tfHistorico = $tfHistorico ?? [];
$tfRemessas = $tfRemessas ?? [];
$tfBancosCatalogo = $tfBancosCatalogo ?? [];
$loteTotal = 0.0;
foreach ($tfLotePagamentos as $lp) {
	$loteTotal += (float)$lp['valor'];
}
$seqRemessa = $tfRemessas !== [] ? (string)$tfRemessas[0]['sequencial'] : '000001';
$empresaTitulo = $tfMeta['empresa_nome'] !== '' ? $tfMeta['empresa_nome'] : __('Empresa');
?>
<div id="pg-transferencias">
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			<?= $this->Html->link('← ' . __('Bancos'), $urlLista, ['style' => 'color:var(--teal);text-decoration:none;']) ?>
			<span> › </span><span style="color:var(--teal);"><?= h(__('Transferências')) ?></span>
		</div>
		<h1 style="font-size:20px;font-weight:600;margin:0;"><?= h(__('Transferências & PIX')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);margin-top:2px;"><?= h(__('PIX · TED · DOC · Transferência interna entre contas próprias')) ?></div>
	</div>
</div>

<div class="card" style="margin-bottom:14px;padding:8px;">
	<div style="display:flex;gap:4px;flex-wrap:wrap;">
		<div class="bank-tab active" role="button" tabindex="0" data-transf-tab="pix" onclick="pgmTrocarTipoTransf(this,'pix')">⚡ <?= h(__('PIX')) ?></div>
		<div class="bank-tab" role="button" tabindex="0" data-transf-tab="ted" onclick="pgmTrocarTipoTransf(this,'ted')">🏦 <?= h(__('TED / DOC')) ?></div>
		<div class="bank-tab" role="button" tabindex="0" data-transf-tab="interna" onclick="pgmTrocarTipoTransf(this,'interna')">⇄ <?= h(__('Interna (própria)')) ?></div>
		<div class="bank-tab" role="button" tabindex="0" data-transf-tab="lote" onclick="pgmTrocarTipoTransf(this,'lote')">📋 <?= h(__('Lote (CNAB)')) ?></div>
	</div>
</div>

<div class="g2 transf-main-grid">
	<!-- PIX -->
	<div class="card" id="form-transf-pix">
		<div class="sec-title"><?= h(__('Nova transferência PIX')) ?></div>
		<div class="alert-box alert-blue" style="margin-bottom:12px;">
			⚡ <strong><?= h(__('PIX:')) ?></strong> <?= h(__('Transferência instantânea 24/7 · Limite diário R$ 50.000 · Sem custo')) ?>
		</div>
		<?= $this->Form->create(null, ['url' => $urlPix, 'id' => 'form-pix-envio']) ?>
		<div class="field" style="margin-bottom:10px;">
			<label><?= h(__('Conta de origem')) ?> *</label>
			<select name="financeiro_banco_id" required>
				<?php if ($tfContas === []) : ?>
					<option value=""><?= h(__('Nenhuma conta cadastrada')) ?></option>
				<?php else : foreach ($tfContas as $c) : ?>
					<option value="<?= (int)$c['id'] ?>"><?= h((string)$c['label']) ?></option>
				<?php endforeach; endif; ?>
			</select>
		</div>
		<div class="field" style="margin-bottom:10px;">
			<label><?= h(__('Tipo de chave PIX')) ?> *</label>
			<select id="pix-tipo-chave">
				<option value="cnpj"><?= h(__('CPF / CNPJ')) ?></option>
				<option value="email"><?= h(__('E-mail')) ?></option>
				<option value="telefone"><?= h(__('Telefone')) ?></option>
				<option value="aleatoria"><?= h(__('Chave aleatória')) ?></option>
				<option value="qrcode"><?= h(__('QR Code')) ?></option>
			</select>
		</div>
		<div class="field" style="margin-bottom:10px;">
			<label><?= h(__('Chave PIX do destinatário')) ?> *</label>
			<input type="text" name="chave_pix" id="pix-chave-input" required placeholder="<?= h(__('00.000.000/0000-00 ou e-mail / telefone / chave')) ?>"/>
		</div>
		<?php if ($tfDestinatario !== null) : ?>
		<div id="pix-destinatario-box" style="background:var(--bg-surface);padding:10px 12px;border-radius:var(--radius);margin-bottom:10px;">
			<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;margin-bottom:4px;"><?= h(__('Destinatário consultado')) ?></div>
			<div style="font-size:13px;font-weight:600;"><?= h((string)$tfDestinatario['nome']) ?></div>
			<div style="font-size:11px;color:var(--text-muted);"><?= h((string)$tfDestinatario['detalhe']) ?></div>
		</div>
		<?php endif; ?>
		<div class="g2" style="margin-bottom:10px;">
			<div class="field"><label><?= h(__('Valor')) ?> *</label><input type="text" name="valor" placeholder="R$ 0,00" style="font-weight:700;font-size:16px;" required/></div>
			<div class="field"><label><?= h(__('Data')) ?></label><input type="date" name="data" value="<?= h($tfMeta['data_hoje']) ?>"/></div>
		</div>
		<div class="field" style="margin-bottom:10px;">
			<label><?= h(__('Categoria / classificação')) ?></label>
			<select name="categoria">
				<?php foreach ($tfCategorias as $k => $lbl) : ?>
					<option value="<?= h($k) ?>"<?= $k === 'fornecedor' ? ' selected' : '' ?>><?= h($lbl) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="field" style="margin-bottom:10px;">
			<label><?= h(__('Centro de custo')) ?></label>
			<select name="centro_custo_id">
				<?php if ($tfCentrosCusto === []) : ?>
					<option value=""><?= h(__('Sem centro de custo cadastrado')) ?></option>
				<?php else : foreach ($tfCentrosCusto as $i => $cc) : ?>
					<option value="<?= (int)$cc['id'] ?>"<?= $i === 1 || stripos((string)$cc['codigo'], '002') !== false ? ' selected' : '' ?>><?= h((string)$cc['label']) ?></option>
				<?php endforeach; endif; ?>
			</select>
		</div>
		<div class="field" style="margin-bottom:10px;">
			<label><?= h(__('Descrição / histórico')) ?></label>
			<input type="text" name="descricao" placeholder="<?= h(__('Ex: Pagamento NF 89432 · servidor PowerEdge')) ?>"/>
		</div>
		<div class="field" style="margin-bottom:14px;">
			<label><?= h(__('Documento vinculado (opcional)')) ?></label>
			<select name="lancamento_id">
				<option value=""><?= h(__('Sem vínculo')) ?></option>
				<?php foreach ($tfDocumentos as $doc) : ?>
					<option value="<?= (int)$doc['id'] ?>"><?= h((string)$doc['label']) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="alert-box alert-amber" style="margin-bottom:12px;font-size:11px;">
			⚠ <?= h(__('Após confirmar, a transferência será processada imediatamente e')) ?> <strong><?= h(__('não pode ser cancelada')) ?></strong>. <?= h(__('Verifique todos os dados.')) ?>
		</div>
		<div style="display:flex;gap:8px;">
			<?= $this->Html->link(__('Cancelar'), $urlLista, ['class' => 'btn btn-ghost', 'style' => 'flex:1;justify-content:center;']) ?>
			<button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;">⚡ <?= h(__('Enviar PIX')) ?></button>
		</div>
		<?= $this->Form->end() ?>
	</div>

	<!-- TED / DOC -->
	<div class="card" id="form-transf-ted" style="display:none;">
		<div class="sec-title"><?= h(__('Nova transferência TED / DOC')) ?></div>
		<div class="alert-box alert-amber" style="margin-bottom:12px;">
			🏦 <strong><?= h(__('TED:')) ?></strong> <?= h(__('Compensação no mesmo dia (até 17h em dias úteis) · Tarifa R$ 9,90 · Limite R$ 1.000.000')) ?><br>
			<strong><?= h(__('DOC:')) ?></strong> <?= h(__('Compensação em D+1 · Tarifa R$ 6,90 · Limite R$ 4.999,99 (descontinuado em alguns bancos)')) ?>
		</div>
		<div class="g2" style="margin-bottom:10px;">
			<div class="field"><label><?= h(__('Tipo')) ?> *</label>
				<select><option selected>TED · <?= h(__('Transferência Eletrônica Disponível')) ?></option><option>DOC · <?= h(__('Documento de Crédito')) ?></option></select>
			</div>
			<div class="field"><label><?= h(__('Conta de origem')) ?> *</label>
				<select>
					<?php foreach ($tfContas as $c) : ?><option value="<?= (int)$c['id'] ?>"><?= h((string)$c['label']) ?></option><?php endforeach; ?>
				</select>
			</div>
		</div>
		<div class="sec-title" style="margin-top:14px;"><?= h(__('Dados do destinatário')) ?></div>
		<div class="g2" style="margin-bottom:10px;">
			<div class="field"><label><?= h(__('Tipo de pessoa')) ?> *</label>
				<select><option><?= h(__('Pessoa Jurídica (CNPJ)')) ?></option><option><?= h(__('Pessoa Física (CPF)')) ?></option></select>
			</div>
			<div class="field"><label><?= h(__('CNPJ / CPF')) ?> *</label><input type="text" placeholder="00.000.000/0000-00"/></div>
		</div>
		<div class="field" style="margin-bottom:10px;">
			<label><?= h(__('Nome / Razão social')) ?> *</label>
			<input type="text" placeholder="<?= h(__('Nome completo do destinatário')) ?>"/>
		</div>
		<div class="g2" style="margin-bottom:10px;">
			<div class="field"><label><?= h(__('Banco')) ?> *</label>
				<select><option><?= h(__('Selecione...')) ?></option>
					<?php foreach ($tfBancosCatalogo as $b) : ?>
						<option><?= h((string)$b['codigo'] . ' · ' . $b['nome']) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="field"><label><?= h(__('Tipo de conta')) ?> *</label>
				<select><option><?= h(__('Conta Corrente')) ?></option><option><?= h(__('Conta Poupança')) ?></option><option><?= h(__('Conta Pagamento')) ?></option></select>
			</div>
		</div>
		<div class="g2" style="margin-bottom:10px;">
			<div class="field"><label><?= h(__('Agência')) ?> *</label><input type="text" placeholder="0000-0"/></div>
			<div class="field"><label><?= h(__('Conta')) ?> *</label><input type="text" placeholder="00000-0"/></div>
		</div>
		<div class="sec-title" style="margin-top:14px;"><?= h(__('Valores e configurações')) ?></div>
		<div class="g2" style="margin-bottom:10px;">
			<div class="field"><label><?= h(__('Valor')) ?> *</label><input type="text" placeholder="R$ 0,00" style="font-weight:700;font-size:16px;"/></div>
			<div class="field"><label><?= h(__('Data')) ?> *</label><input type="date" value="<?= h($tfMeta['data_hoje']) ?>"/></div>
		</div>
		<div class="g2" style="margin-bottom:10px;">
			<div class="field"><label><?= h(__('Finalidade')) ?> *</label>
				<select>
					<option>1 · <?= h(__('Crédito em conta')) ?></option>
					<option>5 · <?= h(__('Pagamento de salários')) ?></option>
					<option>10 · <?= h(__('Transferência entre contas próprias')) ?></option>
				</select>
			</div>
			<div class="field"><label><?= h(__('Centro de custo')) ?></label>
				<select>
					<?php foreach ($tfCentrosCusto as $i => $cc) : ?>
						<option value="<?= (int)$cc['id'] ?>"<?= $i === 1 ? ' selected' : '' ?>><?= h((string)$cc['label']) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<div class="field" style="margin-bottom:14px;">
			<label><?= h(__('Histórico / descrição')) ?></label>
			<input type="text" placeholder="<?= h(__('Ex: Pagto. fornecedor NF 89432')) ?>"/>
		</div>
		<div class="alert-box alert-amber" style="margin-bottom:12px;font-size:11px;">
			⚠ <?= h(__('TED só será efetivada em dia útil até 17h. Após esse horário, o agendamento será para o próximo dia útil. Tarifa de R$ 9,90 será debitada da conta de origem.')) ?>
		</div>
		<div style="display:flex;gap:8px;">
			<?= $this->Html->link(__('Cancelar'), $urlLista, ['class' => 'btn btn-ghost', 'style' => 'flex:1;justify-content:center;']) ?>
			<button type="button" class="btn btn-primary" style="flex:1;justify-content:center;" onclick="alert(<?= json_encode(__('Envio TED/DOC via API bancária — em roadmap.')) ?>)">🏦 <?= h(__('Enviar TED')) ?></button>
		</div>
	</div>

	<!-- Interna -->
	<div class="card" id="form-transf-interna" style="display:none;">
		<div class="sec-title"><?= h(__('Transferência interna entre contas próprias')) ?></div>
		<div class="alert-box alert-blue" style="margin-bottom:12px;">
			⇄ <strong><?= h(__('Transferência interna:')) ?></strong>
			<?= sprintf(
				h(__('Movimentação entre contas da mesma empresa (CNPJ %s) · Sem tarifa · Compensação imediata')),
				h($tfMeta['empresa_cnpj'] !== '' ? $tfMeta['empresa_cnpj'] : '—')
			) ?>
		</div>
		<div class="field" style="margin-bottom:10px;">
			<label><?= h(__('De (conta de origem)')) ?> *</label>
			<select>
				<?php foreach ($tfContas as $c) : ?><option value="<?= (int)$c['id'] ?>"><?= h((string)$c['label']) ?></option><?php endforeach; ?>
			</select>
		</div>
		<div style="text-align:center;font-size:24px;color:var(--text-muted);margin:8px 0;">⬇</div>
		<div class="field" style="margin-bottom:10px;">
			<label><?= h(__('Para (conta de destino)')) ?> *</label>
			<select>
				<option><?= h(__('Selecione...')) ?></option>
				<?php foreach ($tfContas as $i => $c) : ?>
					<option value="<?= (int)$c['id'] ?>"<?= $i === 1 ? ' selected' : '' ?>><?= h($c['sigla'] . ' · Ag.' . $c['agencia'] . ' · CC.' . $c['conta']) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="g2" style="margin-bottom:10px;">
			<div class="field"><label><?= h(__('Valor')) ?> *</label><input type="text" placeholder="R$ 0,00" style="font-weight:700;font-size:16px;"/></div>
			<div class="field"><label><?= h(__('Data')) ?> *</label><input type="date" value="<?= h($tfMeta['data_hoje']) ?>"/></div>
		</div>
		<div class="field" style="margin-bottom:10px;">
			<label><?= h(__('Motivo da transferência')) ?></label>
			<select>
				<option selected><?= h(__('Reforço de saldo')) ?></option>
				<option><?= h(__('Concentração de caixa')) ?></option>
				<option><?= h(__('Folha de pagamento')) ?></option>
				<option><?= h(__('Pagamento de fornecedores')) ?></option>
			</select>
		</div>
		<div class="field" style="margin-bottom:14px;">
			<label><?= h(__('Observações')) ?></label>
			<input type="text" placeholder="<?= h(__('Ex: Provisão para folha de pagamento')) ?>"/>
		</div>
		<div style="display:flex;gap:8px;">
			<?= $this->Html->link(__('Cancelar'), $urlLista, ['class' => 'btn btn-ghost', 'style' => 'flex:1;justify-content:center;']) ?>
			<button type="button" class="btn btn-primary" style="flex:1;justify-content:center;" onclick="alert(<?= json_encode(__('Transferência interna registrada (protótipo).')) ?>)">⇄ <?= h(__('Confirmar transferência')) ?></button>
		</div>
	</div>

	<!-- Lote CNAB -->
	<div class="card" id="form-transf-lote" style="display:none;">
		<div class="sec-title"><?= h(__('Pagamento em lote (CNAB 240)')) ?></div>
		<div class="alert-box alert-blue" style="margin-bottom:12px;">
			📋 <strong><?= h(__('Lote CNAB:')) ?></strong> <?= h(__('Processe múltiplos pagamentos de uma vez através de arquivo bancário · Ideal para folha, fornecedores e tributos')) ?>
		</div>
		<div class="field" style="margin-bottom:12px;">
			<label><?= h(__('Banco emissor')) ?> *</label>
			<select>
				<?php foreach ($tfContas as $c) : ?><option><?= h((string)$c['label']) ?></option><?php endforeach; ?>
			</select>
		</div>
		<div class="field" style="margin-bottom:12px;">
			<label><?= h(__('Tipo de pagamento')) ?> *</label>
			<select>
				<option><?= h(__('Folha de pagamento (salários)')) ?></option>
				<option><?= h(__('Fornecedores · TED')) ?></option>
				<option><?= h(__('Boletos')) ?></option>
				<option><?= h(__('Tributos · DARF / GPS / GRU')) ?></option>
			</select>
		</div>
		<div class="sec-title" style="margin-top:14px;"><?= h(__('Origem dos pagamentos')) ?></div>
		<div style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px;">
			<label class="transf-lote-opt transf-lote-opt--active">
				<input type="radio" name="lote-origem" checked/>
				<div>
					<div style="font-size:13px;font-weight:600;"><?= h(__('Selecionar contas a pagar do sistema')) ?></div>
					<div style="font-size:11px;color:var(--text-muted);"><?= h(__('Use os títulos já cadastrados em Contas a Pagar')) ?></div>
				</div>
			</label>
		</div>
		<div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:14px;">
			<div style="padding:8px 12px;background:var(--bg-surface);font-size:11px;text-transform:uppercase;font-weight:600;color:var(--text-muted);display:flex;justify-content:space-between;">
				<span><?= sprintf(h(__('%d pagamentos selecionados')), count($tfLotePagamentos)) ?></span>
				<span><?= h($H->brl($loteTotal)) ?></span>
			</div>
			<?php if ($tfLotePagamentos === []) : ?>
				<div style="padding:16px;text-align:center;color:var(--text-muted);font-size:12px;"><?= h(__('Nenhum título em aberto.')) ?></div>
			<?php else : foreach ($tfLotePagamentos as $lp) : ?>
				<div class="titulo-row" style="grid-template-columns:30px 1fr 110px 100px;">
					<div><input type="checkbox" checked/></div>
					<div>
						<div style="font-size:13px;font-weight:500;"><?= h((string)$lp['titulo']) ?></div>
						<?php if ((string)$lp['sub'] !== '') : ?><div style="font-size:11px;color:var(--text-muted);"><?= h((string)$lp['sub']) ?></div><?php endif; ?>
					</div>
					<div style="font-size:11px;color:var(--text-muted);"><?= (string)$lp['vencimento'] !== '' ? h(__('Vence {0}', (string)$lp['vencimento'])) : '—' ?></div>
					<div style="text-align:right;font-weight:600;"><?= h($H->brl((float)$lp['valor'])) ?></div>
				</div>
			<?php endforeach; endif; ?>
		</div>
		<div class="g2" style="margin-bottom:14px;">
			<div class="field"><label><?= h(__('Data de processamento')) ?> *</label><input type="date" value="<?= h($tfMeta['data_hoje']) ?>"/></div>
			<div class="field"><label><?= h(__('Sequencial do arquivo')) ?></label><input type="text" value="<?= h($seqRemessa) ?>" readonly style="background:var(--gray-100);"/></div>
		</div>
		<div style="display:flex;gap:8px;">
			<?= $this->Html->link(__('Cancelar'), $urlLista, ['class' => 'btn btn-ghost', 'style' => 'flex:1;justify-content:center;']) ?>
			<?= $this->Html->link('📋 ' . __('Gerar remessa (clássico)'), ['controller' => 'Remessas', 'action' => 'index'], ['class' => 'btn btn-primary', 'style' => 'flex:1;justify-content:center;']) ?>
		</div>
	</div>

	<!-- Sidebar -->
	<div class="transf-sidebar" style="display:flex;flex-direction:column;gap:14px;">
		<div class="card">
			<div class="sec-title"><?= sprintf(h(__('Chaves PIX cadastradas (%s)')), h($empresaTitulo)) ?></div>
			<div style="display:flex;flex-direction:column;gap:8px;">
				<?php if ($tfPixChaves === []) : ?>
					<div style="padding:12px;text-align:center;color:var(--text-muted);font-size:12px;"><?= h(__('Cadastre contas com integração PIX para exibir chaves.')) ?></div>
				<?php else : foreach ($tfPixChaves as $pk) :
					$tipoLabel = (string)($pk['tipo_label'] ?? strtoupper((string)$pk['tipo']));
					$badge = (string)($pk['badge'] ?? 'Ativa');
					$badgeKind = (string)($pk['badge_kind'] ?? 'aprov');
				?>
				<div class="pix-key-row">
					<div>
						<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;"><?= h($tipoLabel) ?></div>
						<div class="pix-key"<?= !empty($pk['font_small']) ? ' style="font-size:10px;"' : '' ?>><?= h((string)$pk['valor']) ?></div>
					</div>
					<div style="text-align:right;">
						<div style="font-size:11px;color:var(--text-muted);"><?= h((string)$pk['conta_label']) ?></div>
						<?= $H->badge($badge, $badgeKind) ?>
					</div>
				</div>
				<?php endforeach; endif; ?>
			</div>
			<button type="button" class="btn btn-ghost btn-xs" style="margin-top:10px;" data-pgm-open-pix-modal onclick="return abrirCadastroPIX();">+ <?= h(__('Cadastrar nova chave PIX')) ?></button>
		</div>

		<div class="card" style="text-align:center;">
			<div class="sec-title" style="text-align:left;"><?= h(__('QR Code para recebimento')) ?></div>
			<div style="background:var(--bg-surface);padding:16px;border-radius:var(--radius);display:inline-block;margin:8px 0;">
				<svg width="120" height="120" viewBox="0 0 21 21" style="image-rendering:pixelated;" aria-hidden="true">
					<rect width="21" height="21" fill="#fff"/>
					<g fill="#1a1a18">
						<rect x="0" y="0" width="7" height="7"/><rect x="1" y="1" width="5" height="5" fill="#fff"/><rect x="2" y="2" width="3" height="3"/>
						<rect x="14" y="0" width="7" height="7"/><rect x="15" y="1" width="5" height="5" fill="#fff"/><rect x="16" y="2" width="3" height="3"/>
						<rect x="0" y="14" width="7" height="7"/><rect x="1" y="15" width="5" height="5" fill="#fff"/><rect x="2" y="16" width="3" height="3"/>
						<rect x="8" y="0" width="1" height="1"/><rect x="10" y="0" width="2" height="1"/>
						<rect x="9" y="2" width="1" height="2"/><rect x="11" y="2" width="2" height="1"/>
						<rect x="8" y="4" width="2" height="1"/><rect x="11" y="4" width="1" height="2"/>
						<rect x="8" y="8" width="1" height="2"/><rect x="10" y="8" width="2" height="1"/><rect x="13" y="8" width="1" height="1"/>
						<rect x="9" y="10" width="2" height="1"/><rect x="12" y="10" width="1" height="2"/>
						<rect x="14" y="9" width="2" height="1"/><rect x="17" y="9" width="1" height="2"/><rect x="19" y="10" width="2" height="1"/>
						<rect x="8" y="12" width="2" height="1"/><rect x="11" y="13" width="1" height="2"/><rect x="13" y="12" width="2" height="2"/>
						<rect x="15" y="14" width="1" height="2"/><rect x="17" y="13" width="2" height="1"/><rect x="20" y="13" width="1" height="2"/>
						<rect x="8" y="15" width="1" height="2"/><rect x="10" y="16" width="2" height="1"/><rect x="13" y="16" width="1" height="2"/>
						<rect x="14" y="18" width="2" height="1"/><rect x="17" y="17" width="1" height="2"/><rect x="19" y="18" width="2" height="1"/>
					</g>
				</svg>
			</div>
			<div style="font-size:12px;color:var(--text-muted);"><?= sprintf(h(__('QR Code estático · %s')), h($tfQrCode['banco'])) ?></div>
			<button type="button" class="btn btn-ghost btn-xs" style="margin-top:8px;" onclick="alert(<?= json_encode(__('Download PNG disponível após integração PIX com o banco.')) ?>)">📥 <?= h(__('Baixar PNG')) ?></button>
		</div>
	</div>
</div>

<!-- Histórico -->
<div class="card" style="margin-top:14px;padding:0;overflow:hidden;">
	<div style="padding:14px 18px;border-bottom:1px solid var(--border);background:var(--bg-surface);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
		<div style="font-size:14px;font-weight:600;"><?= h(__('Histórico de transferências (últimas 30)')) ?></div>
		<select style="padding:6px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;background:#fff;" id="filtro-hist-tipo">
			<option value=""><?= h(__('Todos os tipos')) ?></option>
			<option value="pix">PIX</option>
			<option value="ted">TED</option>
			<option value="doc">DOC</option>
			<option value="interna"><?= h(__('Internas')) ?></option>
			<option value="lote"><?= h(__('Lote')) ?></option>
		</select>
	</div>
	<div class="tbl-wrap">
		<table class="tbl">
			<thead><tr>
				<th style="width:90px;"><?= h(__('Data/Hora')) ?></th>
				<th style="width:60px;"><?= h(__('Tipo')) ?></th>
				<th><?= h(__('Destinatário')) ?></th>
				<th style="width:140px;"><?= h(__('Conta origem')) ?></th>
				<th class="r" style="width:120px;"><?= h(__('Valor')) ?></th>
				<th style="width:90px;"><?= h(__('Status')) ?></th>
				<th style="width:90px;"><?= h(__('Ações')) ?></th>
			</tr></thead>
			<tbody id="hist-transf-body">
				<?php if ($tfHistorico === []) : ?>
					<tr><td colspan="7" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhuma transferência registrada no período.')) ?></td></tr>
				<?php else : foreach ($tfHistorico as $h) :
					$tipoIcon = $h['tipo'] === 'pix' ? '⚡' : ($h['tipo'] === 'interna' ? '⇄' : ($h['tipo'] === 'lote' ? '📋' : '🏦'));
					$valorColor = !empty($h['interna']) ? 'var(--text-muted)' : '#7A1822';
					$valorPrefix = !empty($h['interna']) ? '⇄ ' : '- ';
				?>
				<tr data-hist-tipo="<?= h((string)$h['tipo']) ?>">
					<td><strong><?= h($H->dt($h['data'], 'd/m')) ?></strong><br><span style="font-size:11px;color:var(--text-muted);"><?= h((string)$h['hora']) ?></span></td>
					<td><?= $H->badge($tipoIcon . ' ' . (string)$h['tipo_label'], (string)$h['tipo_badge']) ?></td>
					<td>
						<div style="font-weight:500;font-size:13px;"><?= h((string)$h['destinatario']) ?></div>
						<div style="font-size:11px;color:var(--text-muted);"><?= h((string)$h['destinatario_sub']) ?></div>
					</td>
					<td style="font-size:12px;"><?= h((string)$h['conta_origem']) ?></td>
					<td class="r"><strong style="color:<?= h($valorColor) ?>;"><?= h($valorPrefix . $H->brl((float)$h['valor'])) ?></strong></td>
					<td><?= $H->badge('✓ ' . (string)$h['status'], 'paga') ?></td>
					<td><button type="button" class="btn btn-ghost btn-xs" onclick="alert(<?= json_encode(__('Comprovante em desenvolvimento.')) ?>)">📄 <?= h(__('Comprovante')) ?></button></td>
				</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
</div>

<script>
function pgmTrocarTipoTransf(btn, tipo) {
	document.querySelectorAll('#pg-transferencias .bank-tab').forEach(function (t) { t.classList.remove('active'); });
	if (btn) { btn.classList.add('active'); }
	['pix', 'ted', 'interna', 'lote'].forEach(function (t) {
		var el = document.getElementById('form-transf-' + t);
		if (el) { el.style.display = 'none'; }
	});
	var tgt = document.getElementById('form-transf-' + tipo);
	if (tgt) { tgt.style.display = ''; }
}
(function () {
	var filtro = document.getElementById('filtro-hist-tipo');
	if (!filtro) { return; }
	filtro.addEventListener('change', function () {
		var v = filtro.value;
		document.querySelectorAll('#hist-transf-body tr[data-hist-tipo]').forEach(function (tr) {
			tr.style.display = (!v || tr.getAttribute('data-hist-tipo') === v) ? '' : 'none';
		});
	});
})();
</script>

<?= $this->element('BancosPrototype/modal_conta', [
	'bancosCatalogo' => $tfBancosCatalogo,
	'abrirModalConta' => false,
]) ?>

<?= $this->element('BancosPrototype/modal_pix', [
	'tfContas' => $tfContas,
	'abrirModalPix' => !empty($abrirModalPix),
]) ?>
