<?php
/**
 * Portal do cliente — novo chamado (mockup pg-sd-portal-novo).
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$H = $this->ServicedeskPrototype;
$uPortal = $H->sdpPage('portal');
$novo = (array)($screen['portal_novo'] ?? []);
$subtitle = trim((string)($screen['subtitle'] ?? ''));
if ($subtitle === '') {
	$subtitle = __('Descreva sua necessidade · resposta em até 2h conforme SLA do seu contrato');
}
$selectedCat = (string)($novo['selected_categoria'] ?? 'acesso');
$tipos = (array)($novo['tipos'] ?? []);
$prioridades = (array)($novo['prioridades'] ?? []);
$categorias = (array)($novo['categorias'] ?? []);
$subcategorias = (array)($novo['subcategorias'] ?? []);
$kbSuggestions = (array)($novo['kb_suggestions'] ?? []);
$contract = (array)($novo['contract'] ?? []);
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
					<select disabled aria-disabled="true">
						<?php foreach ($tipos as $key => $label) : ?>
							<option value="<?= h((string)$key) ?>" <?= $key === 'requisicao' ? 'selected' : '' ?>><?= h((string)$label) ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="sdp-pn-field">
					<label><?= h(__('Prioridade percebida')) ?></label>
					<select disabled aria-disabled="true">
						<?php foreach ($prioridades as $key => $label) : ?>
							<option value="<?= h((string)$key) ?>" <?= $key === 'baixa' ? 'selected' : '' ?>><?= h((string)$label) ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="sdp-pn-row2">
				<div class="sdp-pn-field">
					<label><?= h(__('Categoria')) ?> *</label>
					<select disabled aria-disabled="true">
						<?php foreach ($categorias as $key => $cat) : ?>
							<option value="<?= h((string)$key) ?>" <?= $key === $selectedCat ? 'selected' : '' ?>><?= h((string)($cat['label'] ?? $key)) ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="sdp-pn-field">
					<label><?= h(__('Subcategoria')) ?></label>
					<select disabled aria-disabled="true">
						<?php foreach ($subcategorias as $key => $label) : ?>
							<option value="<?= h((string)$key) ?>" <?= $key === 'novo_acesso' ? 'selected' : '' ?>><?= h((string)$label) ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="sdp-pn-field">
				<label><?= h(__('Título do chamado')) ?> *</label>
				<input type="text" disabled aria-disabled="true" placeholder="<?= h(__('Resuma em uma frase · ex: Ana Paula precisa de acesso ao módulo financeiro')) ?>" />
			</div>

			<div class="sdp-pn-field">
				<label><?= h(__('Descrição detalhada')) ?> *</label>
				<textarea rows="6" disabled aria-disabled="true" placeholder="<?= h(__('Descreva com o máximo de detalhes possível: o que você precisa, quem é afetado, há quanto tempo, o que já tentou...')) ?>"></textarea>
				<p class="sdp-pn-hint">💡 <?= h(__('Dica: print da tela, mensagens de erro e horários ajudam muito o técnico')) ?></p>
			</div>

			<div class="sdp-pn-field">
				<label><?= h(__('Anexar arquivos')) ?></label>
				<div class="sdp-pn-upload" aria-disabled="true">📎 <?= h(__('Arraste arquivos ou clique · PDF, imagens, planilhas até 10MB')) ?></div>
			</div>

			<div class="sdp-pn-field sdp-pn-field-last">
				<label>
					<?= h(__('Observadores (CC)')) ?>
					<span class="sdp-pn-label-opt"><?= h(__('opcional')) ?></span>
				</label>
				<input type="text" disabled aria-disabled="true" placeholder="<?= h(__('email@empresa.com.br · receberão cópias das respostas')) ?>" />
			</div>
		</div>

		<aside class="sdp-pn-aside">
			<div class="card sdp-pn-kb-card">
				<div class="sec-title">💡 <?= h(__('Talvez você não precise abrir um chamado!')) ?></div>
				<p class="sdp-pn-kb-intro"><?= h(__('Encontramos artigos que podem resolver sua dúvida:')) ?></p>
				<div class="sdp-pn-kb-list">
					<?php if ($kbSuggestions === []) : ?>
						<p class="sdp-pn-kb-intro" style="margin:0;"><?= h(__('Nenhum artigo sugerido no momento.')) ?></p>
					<?php else : ?>
						<?php foreach ($kbSuggestions as $art) :
							$code = (string)($art['code'] ?? '');
							$kbUrl = $code !== '' ? $H->sdpPage('detalhe-kb', ['code' => $code]) : $H->sdpPage('kb');
						?>
							<?= $this->Html->link(
								'<div class="sdp-pn-kb-item-title">📄 ' . h((string)($art['titulo'] ?? '')) . '</div>'
								. '<div class="sdp-pn-kb-item-meta">' . h((string)($art['meta'] ?? '')) . '</div>',
								$kbUrl,
								['class' => 'sdp-pn-kb-item', 'escape' => false]
							) ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>

			<div class="card sdp-pn-contract-card">
				<div class="sec-title">📄 <?= h(__('Seu contrato')) ?></div>
				<dl class="sdp-pn-contract-list">
					<div class="sdp-pn-contract-row">
						<dt><?= h(__('Plano')) ?></dt>
						<dd><?= h((string)($contract['plano'] ?? __('Premium 24/7'))) ?></dd>
					</div>
					<div class="sdp-pn-contract-row">
						<dt><?= h(__('SLA esta categoria')) ?></dt>
						<dd class="sdp-pn-sla"><?= h((string)($contract['sla_categoria'] ?? $contract['sla'] ?? __('Resposta 2h · Resolução 1d'))) ?></dd>
					</div>
					<div class="sdp-pn-contract-row">
						<dt><?= h(__('Horas restantes mês')) ?></dt>
						<dd><?= h((string)($contract['horas_restantes'] ?? '—')) ?></dd>
					</div>
				</dl>
			</div>

			<button type="button" class="btn btn-primary sdp-pn-submit" disabled title="<?= h(__('Protótipo somente leitura')) ?>">
				📤 <?= h(__('Abrir chamado')) ?>
			</button>
			<p class="sdp-pn-footer-note">
				<?= h(__('Você receberá número de protocolo e atualizações por e-mail')) ?>
				· <?= $this->Html->link(__('Abrir na equipe'), $addUrl, ['style' => 'color:var(--teal);']) ?>
			</p>
		</aside>
	</div>
</div>
