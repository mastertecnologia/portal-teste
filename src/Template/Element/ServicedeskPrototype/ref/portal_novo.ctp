<?php
/**
 * Portal do cliente — novo chamado (mockup pg-sd-portal-novo).
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$H = $this->ServicedeskPrototype;
$uPortal = $H->sdpPage('portal');
$subtitle = trim((string)($screen['subtitle'] ?? ''));
if ($subtitle === '') {
	$subtitle = __('Descreva sua necessidade · resposta em até 2h conforme SLA do seu contrato');
}
$addUrl = ['controller' => 'Servicedesk', 'action' => 'add'];
foreach ((array)($screen['links'] ?? []) as $lnk) {
	if (!empty($lnk['url'])) {
		$addUrl = $lnk['url'];
		break;
	}
}
?>
<div id="pg-sd-portal-novo" class="sdp-pn-page">
	<div class="sdp-pn-page-head">
		<div>
			<div class="sdp-pn-crumb">
				<?= $this->Html->link(__('Portal do cliente'), $uPortal) ?> › <?= h(__('Novo chamado')) ?>
			</div>
			<h1 class="sdp-pn-title"><?= h(__('+ Abrir novo chamado')) ?></h1>
			<p class="sdp-pn-subtitle"><?= h($subtitle) ?></p>
		</div>
		<?= $this->Html->link('← ' . __('Voltar ao portal'), $uPortal, ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>

	<div class="sdp-pn-layout">
		<div class="card sdp-pn-form-card">
			<div class="sec-title">📋 <?= h(__('Detalhes do chamado')) ?></div>

			<div class="sdp-pn-row2">
				<div class="sdp-pn-field">
					<label><?= h(__('Tipo')) ?> *</label>
					<select>
						<option><?= h(__('Incidente · algo parou de funcionar')) ?></option>
						<option selected><?= h(__('Requisição · solicitar acesso/serviço')) ?></option>
						<option><?= h(__('Bug · erro no sistema')) ?></option>
						<option><?= h(__('Dúvida · informação')) ?></option>
						<option><?= h(__('Sugestão / Melhoria')) ?></option>
					</select>
				</div>
				<div class="sdp-pn-field">
					<label><?= h(__('Prioridade percebida')) ?></label>
					<select>
						<option selected><?= h(__('Baixa · sem urgência')) ?></option>
						<option><?= h(__('Média · afeta minha rotina')) ?></option>
						<option><?= h(__('Alta · afeta minha equipe')) ?></option>
						<option><?= h(__('Crítica · parou tudo')) ?></option>
					</select>
				</div>
			</div>

			<div class="sdp-pn-row2">
				<div class="sdp-pn-field">
					<label><?= h(__('Categoria')) ?> *</label>
					<select>
						<option><?= h(__('Hardware')) ?></option>
						<option selected><?= h(__('Acesso & Permissões')) ?></option>
						<option><?= h(__('Software / ERP')) ?></option>
						<option><?= h(__('E-mail')) ?></option>
						<option><?= h(__('Rede / Internet')) ?></option>
						<option><?= h(__('Telefonia')) ?></option>
						<option><?= h(__('Outros')) ?></option>
					</select>
				</div>
				<div class="sdp-pn-field">
					<label><?= h(__('Subcategoria')) ?></label>
					<select>
						<option><?= h(__('Senha')) ?></option>
						<option selected><?= h(__('Novo acesso')) ?></option>
						<option><?= h(__('Bloqueio de conta')) ?></option>
						<option><?= h(__('Permissão específica')) ?></option>
					</select>
				</div>
			</div>

			<div class="sdp-pn-field">
				<label><?= h(__('Título do chamado')) ?> *</label>
				<input type="text" placeholder="<?= h(__('Resuma em uma frase · ex: Ana Paula precisa de acesso ao módulo financeiro')) ?>" />
			</div>

			<div class="sdp-pn-field">
				<label><?= h(__('Descrição detalhada')) ?> *</label>
				<textarea rows="6" placeholder="<?= h(__('Descreva com o máximo de detalhes possível: o que você precisa, quem é afetado, há quanto tempo, o que já tentou...')) ?>"></textarea>
				<p class="sdp-pn-hint">💡 <?= h(__('Dica: print da tela, mensagens de erro e horários ajudam muito o técnico')) ?></p>
			</div>

			<div class="sdp-pn-field">
				<label><?= h(__('Anexar arquivos')) ?></label>
				<div class="sdp-pn-upload">📎 <?= h(__('Arraste arquivos ou clique · PDF, imagens, planilhas até 10MB')) ?></div>
			</div>

			<div class="sdp-pn-field sdp-pn-field-last">
				<label>
					<?= h(__('Observadores (CC)')) ?>
					<span class="sdp-pn-label-opt"><?= h(__('opcional')) ?></span>
				</label>
				<input type="text" placeholder="<?= h(__('email@empresa.com.br · receberão cópias das respostas')) ?>" />
			</div>
		</div>

		<aside class="sdp-pn-aside">
			<div class="card sdp-pn-kb-card">
				<div class="sec-title">💡 <?= h(__('Talvez você não precise abrir um chamado!')) ?></div>
				<p class="sdp-pn-kb-intro"><?= h(__('Encontramos artigos que podem resolver sua dúvida:')) ?></p>
				<div class="sdp-pn-kb-list">
					<div class="sdp-pn-kb-item">
						<div class="sdp-pn-kb-item-title">📄 <?= h(__('Como solicitar acesso ao módulo financeiro')) ?></div>
						<div class="sdp-pn-kb-item-meta">⭐ 4.8 · <?= h(__('5 min de leitura')) ?></div>
					</div>
					<div class="sdp-pn-kb-item">
						<div class="sdp-pn-kb-item-title">📄 <?= h(__('Perfis padrão por departamento')) ?></div>
						<div class="sdp-pn-kb-item-meta">⭐ 4.5 · <?= h(__('3 min')) ?></div>
					</div>
				</div>
			</div>

			<div class="card sdp-pn-contract-card">
				<div class="sec-title">📄 <?= h(__('Seu contrato')) ?></div>
				<dl class="sdp-pn-contract-list">
					<div class="sdp-pn-contract-row">
						<dt><?= h(__('Plano')) ?></dt>
						<dd><?= h(__('Premium 24/7')) ?></dd>
					</div>
					<div class="sdp-pn-contract-row">
						<dt><?= h(__('SLA esta categoria')) ?></dt>
						<dd class="sdp-pn-sla"><?= h(__('Resposta 2h · Resolução 1d')) ?></dd>
					</div>
					<div class="sdp-pn-contract-row">
						<dt><?= h(__('Horas restantes mês')) ?></dt>
						<dd><?= h(__('18h de 20h')) ?></dd>
					</div>
				</dl>
			</div>

			<?= $this->Html->link(
				'📤 ' . __('Abrir chamado'),
				$addUrl,
				['class' => 'btn btn-primary sdp-pn-submit']
			) ?>
			<p class="sdp-pn-footer-note"><?= h(__('Você receberá número de protocolo e atualizações por e-mail')) ?></p>
		</aside>
	</div>
</div>
