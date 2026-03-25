<?php

$this->append('css', $this->Html->css('/css/orcamentos-premium', ['timestamp' => true]));
?>

<?php
$versaoLbl = isset($orcVersaoLabel) && $orcVersaoLabel ? (string)$orcVersaoLabel : 'v1';
?>

<style>
	/* Escopo para garantir que o layout do passo 6 espelhe o mock */
	.orc-esign {
		--teal: #00C08B;
		--teal-dark: #008F68;
		--teal-light: #E6FAF4;
		--teal-mid: #5CDBC0;
		--amber: #FFC107;
		--amber-light: #FFF8E1;
		--red: #E24B4A;
		--red-light: #FCEBEB;
		--blue: #33CCFF;
		--blue-light: #E6F9FF;
		--blue-dark: #006B88;
		--purple: #A682E6;
		--purple-light: #F3EDFC;
		--purple-dark: #4A3488;
		--border: #e5e4e0;
		--border-light: #f0efec;
		--text: #1a1a18;
		--text-muted: #6b6a65;
		--bg: #ffffff;
		--bg-surface: #f9f9f8;
		--bg-card: #ffffff;
		--radius: 10px;
		--radius-lg: 14px;
		--radius-xl: 20px;
		--shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
		color: var(--text);
	}

	.orc-esign .btn-ghost {
		background: transparent;
		border: 1px solid var(--border);
		color: var(--text-muted);
	}
	.orc-esign .btn-ghost:hover { background: var(--bg-surface); }

	.orc-esign .g2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
	.orc-esign .card {
		background: var(--bg-card);
		border: 1px solid var(--border);
		border-radius: var(--radius-lg);
		padding: 18px 22px;
		margin-bottom: 14px;
		box-shadow: var(--shadow);
	}
	.orc-esign .sec-title {
		font-size: 10px;
		font-weight: 600;
		color: var(--text-muted);
		text-transform: uppercase;
		letter-spacing: .7px;
		margin-bottom: 14px;
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.orc-esign .sec-title::after {
		content: '';
		flex: 1;
		height: 1px;
		background: var(--border-light);
	}

	.orc-esign .badge {
		display: inline-flex;
		align-items: center;
		padding: 2px 8px;
		border-radius: 99px;
		font-size: 10px;
		font-weight: 600;
		white-space: nowrap;
	}
	.orc-esign .b-env { background: var(--teal-light); color: #085041; }
	.orc-esign .b-pend { background: #FAEEDA; color: #633806; }
	.orc-esign .b-aprov { background: #E6F1FB; color: #0C447C; }

	.orc-esign .sign-canvas {
		border: 1px solid var(--border);
		border-radius: var(--radius);
		width: 100%;
		height: 100px;
		cursor: crosshair;
		touch-action: none;
		background: #fff;
	}

	.orc-esign .footer-bar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 14px 0 0;
		border-top: 1px solid var(--border);
		margin-top: 6px;
	}
</style>

<div class="col-md-12 orc-premium-page-root">
<div class="orc-premium-wrap orc-premium-form orc-esign">
	<?php if (!empty($envioSucesso)) : ?>
		<div class="card" style="text-align:center;padding:32px;max-width:600px;margin:0 auto;">
			<div style="width:60px;height:60px;border-radius:50%;background:var(--teal-light);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
				<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#00C08B" stroke-width="2.5">
					<polyline points="20 6 9 17 4 12"></polyline>
				</svg>
			</div>
			<div style="font-size:26px;font-weight:600;color:var(--teal);margin-bottom:4px;">
				Orçamento #<?= h((string)$orcamento->id) ?> <?= h($versaoLbl) ?> gerado!
			</div>
			<div style="font-size:13px;color:var(--text-muted);margin-bottom:24px;">
				Proposta enviada. Aguardando assinaturas digitais.
			</div>
			<div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-bottom:28px;">
				<?= $this->Html->link('Ver lista', ['action' => 'index'], ['class' => 'btn btn-ghost', 'escape' => false]) ?>
				<?= $this->Html->link('Reenviar e-mail', ['action' => 'envioassinatura', $orcamento->id], ['class' => 'btn btn-pgm btn-pgm-email', 'escape' => false]) ?>
				<?= $this->Html->link(
					'Baixar PDF',
					['action' => 'imprimirPdf', $orcamento->id],
					['class' => 'btn btn-pgm btn-pgm-pdf', 'escape' => false]
				) ?>
				<?= $this->Html->link(
					'+ Novo orçamento',
					['action' => 'add'],
					['class' => 'btn btn-pgm btn-pgm-salvar', 'escape' => false]
				) ?>
			</div>
		</div>
	<?php else : ?>
		<?= $this->Form->create(null, [
			'url' => ['action' => 'email'],
			'type' => 'file',
			'id' => 'form-envioassinatura'
		]); ?>
		<input type="hidden" name="idorcamento" value="<?= h((string)$orcamento->id) ?>" />
		<input type="hidden" name="step6" value="1" />

		<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
			<div>
				<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
					← <?= $this->Html->link('Impressão', ['action' => 'imprimir', $orcamento->id], ['style' => 'cursor:pointer;color:var(--teal);', 'escape' => false]) ?>
					&nbsp;›&nbsp;
					<span style="color:var(--teal);">Envio &amp; Assinatura Digital</span>
				</div>
				<h1 style="font-size:20px;font-weight:600;color:var(--text);margin:0;letter-spacing:-0.02em;">
					Envio &amp; Assinatura Digital
				</h1>
			</div>
			<div style="display:flex;gap:8px;">
				<?= $this->Html->link(
					'← Voltar',
					['action' => 'imprimir', $orcamento->id],
					['class' => 'btn btn-ghost', 'escape' => false]
				) ?>
				<button type="submit" class="btn btn-pgm btn-pgm-salvar">
					Enviar e aguardar assinatura →
				</button>
			</div>
		</div>

		<?= $this->element('orcamentos_stepper') ?>

		<div class="g2">
			<div>
				<div class="card">
					<div class="sec-title">Envio por e-mail</div>
					<div style="display:flex;flex-direction:column;gap:0;background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:12px;">
						<div style="display:flex;align-items:center;gap:8px;padding:9px 14px;border-bottom:1px solid var(--border-light);font-size:12px;">
							<span style="font-size:11px;color:var(--text-muted);width:52px;flex-shrink:0;">Para:</span>
							<input
								type="email"
								id="em-para"
								name="emailemail"
								style="flex:1;border:none;background:transparent;outline:none;font-size:12px;font-family:inherit;color:var(--text);"
								value="<?= !empty($orcamento->cliente->email) ? h($orcamento->cliente->email) : '' ?>"
							/>
						</div>
						<div style="display:flex;align-items:center;gap:8px;padding:9px 14px;border-bottom:1px solid var(--border-light);font-size:12px;">
							<span style="font-size:11px;color:var(--text-muted);width:52px;flex-shrink:0;">CC:</span>
							<input
								type="email"
								placeholder="Cópia (opcional)"
								style="flex:1;border:none;background:transparent;outline:none;font-size:12px;font-family:inherit;color:var(--text);"
							/>
						</div>
						<div style="display:flex;align-items:center;gap:8px;padding:9px 14px;border-bottom:1px solid var(--border-light);font-size:12px;">
							<span style="font-size:11px;color:var(--text-muted);width:52px;flex-shrink:0;">Assunto:</span>
							<input
								type="text"
								id="em-assunto"
								style="flex:1;border:none;background:transparent;outline:none;font-size:12px;font-family:inherit;color:var(--text);"
							/>
						</div>
						<div style="padding:12px 14px;font-size:12px;color:var(--text);line-height:1.8;" contenteditable="true" id="em-corpo">
							Assinatura digital disponível no portal após envio do e-mail.
						</div>
					</div>
					<div style="display:flex;align-items:center;gap:8px;padding:10px 12px;background:var(--bg-surface);border-radius:var(--radius);border:1px solid var(--border-light);margin-bottom:10px;">
						<svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
							<rect x="2" y="2" width="12" height="14" rx="1"></rect>
							<line x1="5" y1="6" x2="11" y2="6"></line>
							<line x1="5" y1="9" x2="9" y2="9"></line>
						</svg>
						<span style="font-size:12px;color:var(--text-muted);flex:1;" id="em-anexo">Orcamento_<?= h((string)$orcamento->id) ?>.pdf</span>
						<span class="badge b-env">Anexado</span>
					</div>
					<div style="background:var(--teal-light);border-radius:var(--radius);padding:10px 13px;font-size:11px;color:var(--teal-dark);display:flex;align-items:flex-start;gap:8px;">
						<svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px;">
							<polyline points="13 2 6 12 2 8"></polyline>
						</svg>
						<span>Link de assinatura digital incluso. O cliente assina com validade jurídica diretamente no portal — compatível com <strong>D4Sign</strong> e <strong>ClickSign</strong>.</span>
					</div>
				</div>
			</div>

			<div>
				<div class="card">
					<div class="sec-title">Assinatura digital — fornecedor</div>
					<div style="margin-bottom:12px;">
						<div style="font-size:12px;font-weight:500;margin-bottom:6px;color:var(--text);">Assine o documento como fornecedor:</div>
						<canvas id="sign-canvas" class="sign-canvas" width="400" height="100"></canvas>
						<div style="display:flex;gap:6px;margin-top:8px;">
							<button type="button" class="btn btn-ghost btn-xs" onclick="clearCanvas()">Limpar assinatura</button>
							<button type="button" class="btn btn-pgm btn-pgm-salvar btn-xs" onclick="salvarAssinatura()">✓ Salvar assinatura</button>
						</div>
						<div id="sign-ok" style="display:none;background:var(--teal-light);border-radius:var(--radius);padding:10px 12px;align-items:center;gap:8px;">
							<svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
								<polyline points="13 2 6 12 2 8"></polyline>
							</svg>
							<span style="font-size:12px;color:var(--teal-dark);font-weight:500;">Assinatura salva · PGM Soluções em TI Ltda</span>
						</div>
					</div>
				</div>

				<div class="card">
					<div class="sec-title">Assinatura digital — cliente</div>
					<div style="background:var(--bg-surface);border-radius:var(--radius);padding:14px;text-align:center;border:1px solid var(--border-light);">
						<div style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">O cliente receberá um link exclusivo por e-mail para assinar digitalmente.</div>
						<div style="font-size:11px;background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:8px 12px;font-family:monospace;color:var(--blue-dark);margin-bottom:10px;word-break:break-all;">
							<?= !empty($assinarUrl) ? h($assinarUrl) : '—' ?>
						</div>
						<div style="display:flex;gap:6px;justify-content:center;margin-bottom:12px;">
							<span class="badge" style="background:var(--purple-light);color:var(--purple-dark);">D4Sign</span>
							<span class="badge" style="background:var(--blue-light);color:var(--blue-dark);">ClickSign</span>
							<span class="badge b-env">ICP-Brasil</span>
						</div>
						<button type="button" class="btn btn-pgm btn-pgm-email btn-sm" style="width:100%;justify-content:center;" onclick="simularAssinaturaCliente()">Simular assinatura do cliente</button>
					</div>
				</div>
			</div>
		</div>

		<div class="footer-bar">
			<?= $this->Html->link('← Voltar', ['action' => 'imprimir', $orcamento->id], ['class' => 'btn btn-ghost', 'escape' => false]) ?>
			<button type="submit" class="btn btn-pgm btn-pgm-salvar">Enviar e aguardar assinatura →</button>
		</div>

		<?= $this->Form->end(); ?>
	<?php endif; ?>
</div>
</div>

<script>
	// ===== ESIGN CANVAS (simulação) =====
	let painting = false;
	let ctx = null;

	function initCanvas(){
		const c = document.getElementById('sign-canvas');
		if(!c) return;
		ctx = c.getContext('2d');
		ctx.strokeStyle = '#1a1a18';
		ctx.lineWidth = 2;
		ctx.lineCap = 'round';
		ctx.lineJoin = 'round';

		c.addEventListener('mousedown', (e) => {
			painting = true;
			const r = c.getBoundingClientRect();
			ctx.beginPath();
			ctx.moveTo(e.clientX - r.left, e.clientY - r.top);
		});
		c.addEventListener('mousemove', (e) => {
			if(!painting) return;
			const r = c.getBoundingClientRect();
			ctx.lineTo(e.clientX - r.left, e.clientY - r.top);
			ctx.stroke();
		});
		c.addEventListener('mouseup', () => { painting = false; });
		c.addEventListener('mouseleave', () => { painting = false; });
	}

	function clearCanvas(){
		if(ctx){
			ctx.clearRect(0,0,400,100);
		}
	}

	function salvarAssinatura(){
		const ok = document.getElementById('sign-ok');
		if(!ok || !ctx) return;
		ok.style.display = 'flex';
	}

	function simularAssinaturaCliente(){
		// UI apenas: a tela final é acionada pelo botão de envio.
		alert('Simulação: cliente assinaria no portal.');
	}

	document.addEventListener('DOMContentLoaded', function(){
		initCanvas();
	});
</script>

