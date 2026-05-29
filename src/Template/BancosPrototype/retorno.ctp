<?php
/**
 * Retornos bancários — painel operacional + import CNAB (modal-retorno).
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $rtKpi
 * @var array<int,array<string,mixed>> $rtLinhas
 * @var array<int,array{id:int,label:string}> $rtBancosSelect
 */
$H = $this->ErpPrototype;
$urlLista = ['controller' => 'BancosPrototype', 'action' => 'lista'];
$urlHistorico = ['controller' => 'FinanceiroBancos', 'action' => 'historicoRetorno'];
$urlProcessar = $this->Url->build(['controller' => 'Retornos', 'action' => 'processar']);
$ultimoMov = !empty($rtKpi['ultimo_movimento']) && $rtKpi['ultimo_movimento'] instanceof \DateTimeInterface
	? $rtKpi['ultimo_movimento']->format('d/m/Y H:i')
	: '—';
?>
<div id="pg-retorno">
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">
			← <?= $this->Html->link(__('Bancos'), $urlLista, ['style' => 'color:var(--teal);text-decoration:none;']) ?>
			<span> › </span><span style="color:var(--teal);"><?= h(__('Retornos')) ?></span>
		</div>
		<h1 style="font-size:20px;font-weight:600;margin:0;">📥 <?= h(__('Retornos bancários')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);margin-top:2px;"><?= h(__('Painel operacional para acompanhar contas, extratos importados e pendências de conciliação')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('🕐 ' . __('Histórico de retorno'), $urlHistorico, ['class' => 'btn btn-blue btn-sm']) ?>
		<?= $this->Html->link('📋 ' . __('Cadastro de bancos'), ['controller' => 'BancosPrototype', 'action' => 'view', 'contas'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('← ' . __('Voltar'), $urlLista, ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));margin-bottom:14px;">
	<div class="stat" style="--sc:var(--blue);"><div class="stat-l"><?= h(__('Bancos monitorados')) ?></div><div class="stat-n"><?= (int)$rtKpi['bancos'] ?></div></div>
	<div class="stat" style="--sc:var(--teal);"><div class="stat-l"><?= h(__('Com conta configurada')) ?></div><div class="stat-n"><?= (int)$rtKpi['com_conta'] ?></div></div>
	<div class="stat" style="--sc:var(--teal-dark);"><div class="stat-l"><?= h(__('Bancos com extrato')) ?></div><div class="stat-n"><?= (int)$rtKpi['com_extrato'] ?></div></div>
	<div class="stat" style="--sc:var(--amber);"><div class="stat-l"><?= h(__('Pendências conciliação')) ?></div><div class="stat-n"><?= (int)$rtKpi['pendentes'] ?></div></div>
	<div class="stat" style="--sc:var(--teal);"><div class="stat-l"><?= h(__('Eventos conciliados')) ?></div><div class="stat-n"><?= (int)$rtKpi['conciliados'] ?></div></div>
	<div class="stat" style="--sc:var(--text-muted);"><div class="stat-l"><?= h(__('Último movimento')) ?></div><div class="stat-n" style="font-size:13px;"><?= h($ultimoMov) ?></div></div>
</div>

<div class="card" style="margin-bottom:14px;">
	<div class="sec-title"><?= h(__('Leitura operacional do retorno')) ?></div>
	<p style="font-size:12px;color:var(--text-muted);margin:0 0 12px;"><?= h(__('Priorize bancos com pendências, revise contas sem configuração completa e acompanhe o avanço da conciliação.')) ?></p>
	<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;">
		<div style="padding:12px;background:var(--bg-surface);border-radius:var(--radius);">
			<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;margin-bottom:6px;"><?= h(__('Prioridade imediata')) ?></div>
			<?= $H->badge((int)$rtKpi['pendentes'] . ' ' . __('pendência(s)'), 'vencendo') ?>
			<span style="margin-left:6px;"><?= $H->badge((int)$rtKpi['conciliados'] . ' ' . __('conciliado(s)'), 'paga') ?></span>
		</div>
		<div style="padding:12px;background:var(--bg-surface);border-radius:var(--radius);">
			<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;margin-bottom:6px;"><?= h(__('Qualidade do cadastro')) ?></div>
			<?= $H->badge((int)$rtKpi['com_conta'] . ' ' . __('com conta completa'), 'paga') ?>
			<span style="margin-left:6px;"><?= $H->badge(((int)$rtKpi['bancos'] - (int)$rtKpi['com_conta']) . ' ' . __('sem conta completa'), 'arq') ?></span>
		</div>
		<div style="padding:12px;background:var(--bg-surface);border-radius:var(--radius);">
			<div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;margin-bottom:6px;"><?= h(__('Cobertura de extrato')) ?></div>
			<?= $H->badge((int)$rtKpi['com_extrato'] . ' ' . __('com extrato'), 'aprov') ?>
			<span style="margin-left:6px;"><?= $H->badge(((int)$rtKpi['bancos'] - (int)$rtKpi['com_extrato']) . ' ' . __('sem extrato'), 'pendente') ?></span>
		</div>
	</div>
</div>

<div class="g2" style="align-items:start;">
	<div class="card">
		<div class="sec-title"><?= h(__('Importar arquivo de retorno CNAB')) ?></div>
		<div style="font-size:12px;color:var(--text-muted);margin-bottom:12px;"><?= h(__('Faça upload do arquivo CNAB de retorno (.RET, .CRE ou .TXT)')) ?></div>

		<form id="form-retorno-upload" enctype="multipart/form-data">
		<div class="field" style="margin-bottom:12px;">
			<label class="field-lbl"><?= h(__('Banco emissor')) ?> *</label>
			<select name="banco_id" id="rt-banco-id" required>
				<option value=""><?= h(__('-- Selecione --')) ?></option>
				<?php foreach ($rtBancosSelect as $bs) : ?>
					<option value="<?= (int)$bs['id'] ?>"><?= h((string)$bs['label']) ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div style="border:2px dashed var(--teal-mid);background:var(--teal-light);border-radius:var(--radius);padding:28px;text-align:center;margin-bottom:14px;">
			<div style="font-size:40px;margin-bottom:8px;">📁</div>
			<div style="font-size:14px;font-weight:600;color:var(--teal-dark);"><?= h(__('Selecione o arquivo de retorno')) ?></div>
			<div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= h(__('Formatos: .RET, .CRE, .TXT · até 50 MB')) ?></div>
			<div style="margin-top:12px;">
				<input type="file" name="arquivo" id="rt-arquivo" required accept=".ret,.cre,.txt,.RET,.CRE,.TXT" style="font-size:12px;"/>
			</div>
		</div>

		<div class="alert-box alert-teal" style="margin-bottom:14px;font-size:11px;">
			✓ <?= h(__('Baixas confirmadas atualizam o status dos títulos e registram movimentações no extrato bancário.')) ?>
		</div>

		<div id="rt-feedback" style="display:none;margin-bottom:10px;" class="alert-box"></div>

		<div style="display:flex;gap:8px;justify-content:flex-end;">
			<button type="button" class="btn btn-primary btn-sm" id="rt-btn-processar" onclick="pgmProcessarRetorno()">✓ <?= h(__('Processar retorno')) ?></button>
		</div>
		</form>
	</div>

	<div class="card" style="padding:0;overflow:hidden;">
		<div style="padding:14px 18px;border-bottom:1px solid var(--border);background:var(--bg-surface);">
			<div class="sec-title" style="margin:0;"><?= h(__('Painel por banco')) ?></div>
			<div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= h(__('Priorizado por pendências de conciliação')) ?></div>
		</div>
		<?php if ($rtLinhas === []) : ?>
			<div style="padding:32px;text-align:center;color:var(--text-muted);">
				<div style="font-size:36px;margin-bottom:8px;opacity:.5;">📁</div>
				<?= h(__('Nenhum banco cadastrado para a empresa.')) ?>
			</div>
		<?php else : ?>
			<div class="tbl-wrap">
				<table class="tbl">
					<thead><tr>
						<th><?= h(__('Banco')) ?></th>
						<th><?= h(__('Agência / Conta')) ?></th>
						<th class="r"><?= h(__('Movimentos')) ?></th>
						<th class="r"><?= h(__('Pendentes')) ?></th>
						<th><?= h(__('Status')) ?></th>
					</tr></thead>
					<tbody>
						<?php foreach ($rtLinhas as $ln) :
							$br = $ln['brand'];
							$ult = $ln['ultimo_evento'] instanceof \DateTimeInterface ? $ln['ultimo_evento']->format('d/m/Y') : '—';
						?>
						<tr>
							<td>
								<div style="display:flex;align-items:center;gap:8px;">
									<div style="width:28px;height:28px;border-radius:6px;background:<?= h($br['logo_bg']) ?>;color:<?= h($br['logo_fg']) ?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:10px;"><?= h($br['sigla']) ?></div>
									<div>
										<div style="font-weight:600;font-size:13px;"><?= h((string)$ln['nome']) ?></div>
										<div style="font-size:10px;color:var(--text-muted);"><?= h(__('Último: {0}', $ult)) ?></div>
									</div>
								</div>
							</td>
							<td style="font-family:monospace;font-size:11px;"><?= h((string)$ln['agencia']) ?> / <?= h((string)$ln['conta']) ?></td>
							<td class="r"><?= (int)$ln['quantidade'] ?></td>
							<td class="r"><strong style="color:<?= (int)$ln['pendentes'] > 0 ? '#8A4D02' : 'inherit' ?>;"><?= (int)$ln['pendentes'] ?></strong></td>
							<td><?= $H->badge((string)$ln['status_label'], (string)$ln['status_kind']) ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
</div>

<div class="alert-box alert-teal" style="margin-top:14px;font-size:11px;">
	💡 <strong><?= h(__('Dica operacional:')) ?></strong> <?= h(__('Mantenha agência, conta e CNAB corretamente preenchidos no cadastro bancário para melhorar o cruzamento com extratos e retornos.')) ?>
</div>
</div>
