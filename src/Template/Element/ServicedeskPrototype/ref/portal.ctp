<?php
/**
 * Portal do cliente (mockup pg-sd-portal) — dados reais da empresa.
 *
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$portal = (array)($screen['portal'] ?? []);
$bannerCliente = (string)($portal['banner_cliente'] ?? $portal['cliente_nome'] ?? __('Cliente'));
$firstName = (string)($portal['user_first_name'] ?? __('visitante'));
$abertos = (int)($portal['abertos_count'] ?? 0);
$res30 = (int)($portal['resolvidos_30d'] ?? 0);
$res30Hint = (string)($portal['resolvidos_30d_hint'] ?? '');
$aguarda = (int)($portal['aguarda_cliente'] ?? 0);
$tempoMedio = (string)($portal['tempo_medio_resolucao'] ?? '—');
$contrato = (string)($portal['contrato_label'] ?? __('Premium · suporte 24/7'));
$satisfacaoFmt = (string)($portal['satisfacao_fmt'] ?? '—');
$tickets = (array)($portal['tickets_abertos'] ?? []);
$cats = (array)($portal['categorias'] ?? []);
$kb = (array)($portal['kb_popular'] ?? []);
$H = $this->ServicedeskPrototype;
$uNovo = $H->sdpPage('portal-novo');
$uKb = $H->sdpPage('kb');
?>
<div id="pg-sd-portal" class="sdp-portal-page">
	<div class="sdp-portal-hero">
		<div>
			<div class="sdp-portal-hero-eyebrow"><?= h(__('PORTAL DO CLIENTE')) ?> · <?= h($bannerCliente) ?></div>
			<h1 class="sdp-portal-hero-title"><?= h(sprintf(__('Olá, %s! 👋'), $firstName)) ?></h1>
			<p class="sdp-portal-hero-sub">
				<?= h(sprintf(
					__('Você tem %d tickets em andamento · contrato %s'),
					$abertos,
					$contrato
				)) ?>
			</p>
		</div>
		<div class="sdp-portal-hero-actions">
			<?= $this->Html->link('📚 ' . __('Base de conhecimento'), $uKb, [
				'class' => 'btn btn-sm sdp-portal-btn-ghost',
			]) ?>
			<?= $this->Html->link('+ ' . __('Novo chamado'), $uNovo, [
				'class' => 'btn btn-sm sdp-portal-btn-primary',
			]) ?>
		</div>
	</div>

	<div class="summary-grid sdp-portal-kpis">
		<div class="summary-card" style="border-left:3px solid var(--teal);">
			<div class="lbl"><?= h(__('Tickets abertos')) ?></div>
			<div class="val" style="color:var(--teal-dark);"><?= (int)$abertos ?></div>
			<div style="font-size:11px;color:var(--text-muted);"><?= h(__('em andamento')) ?></div>
		</div>
		<div class="summary-card" style="border-left:3px solid var(--blue);">
			<div class="lbl"><?= h(__('Resolvidos · 30d')) ?></div>
			<div class="val" style="color:#0C447C;"><?= (int)$res30 ?></div>
			<?php if ($res30Hint !== '') : ?>
				<div style="font-size:11px;color:var(--teal-dark);"><?= h($res30Hint) ?></div>
			<?php endif; ?>
		</div>
		<div class="summary-card" style="border-left:3px solid #6B5B95;">
			<div class="lbl"><?= h(__('Aguardando você')) ?></div>
			<div class="val" style="color:#3D2D63;"><?= (int)$aguarda ?></div>
			<?php if ($aguarda > 0) : ?>
				<div style="font-size:11px;color:#3D2D63;"><?= h(__('ação necessária')) ?></div>
			<?php endif; ?>
		</div>
		<div class="summary-card" style="border-left:3px solid var(--amber);">
			<div class="lbl"><?= h(__('Tempo médio')) ?></div>
			<div class="val" style="color:#8A4D02;"><?= h($tempoMedio) ?></div>
			<div style="font-size:11px;color:var(--text-muted);"><?= h(__('resolução')) ?></div>
		</div>
		<div class="summary-card" style="border-left:3px solid #D946A0;">
			<div class="lbl"><?= h(__('Satisfação')) ?></div>
			<div class="val" style="color:#7A1B5C;"><?= h($satisfacaoFmt) ?></div>
			<div style="font-size:11px;color:var(--text-muted);"><?= h(__('de 5,0')) ?></div>
		</div>
	</div>

	<div class="g2 sdp-portal-main">
		<div class="card">
			<div class="sec-title">📋 <?= h(__('Meus tickets abertos')) ?></div>
			<?php if ($tickets === []) : ?>
				<p style="margin:0;font-size:12px;color:var(--text-muted);"><?= h(__('Nenhum ticket em aberto no seu escopo.')) ?></p>
			<?php else : ?>
				<div class="sdp-portal-ticket-list">
					<?php foreach ($tickets as $tk) : ?>
						<?php
						$id = (int)($tk['id'] ?? 0);
						$url = $H->sdpTicketUrl($id);
						$cardStyle = (string)($tk['portal_card_style'] ?? 'background:var(--bg-surface);border-left:3px solid var(--teal);');
						$badgeStyle = (string)($tk['portal_badge_style'] ?? '');
						$badge = (string)($tk['portal_badge'] ?? '');
						$title = \Cake\Utility\Text::truncate((string)($tk['assunto_titulo'] ?? $tk['assunto'] ?? ''), 48, ['ellipsis' => '…']);
						?>
						<div class="sdp-portal-ticket-card" style="<?= h($cardStyle) ?>" role="link" tabindex="0" onclick="location.href='<?= h($url) ?>'">
							<div class="sdp-portal-ticket-head">
								<strong>#<?= $id ?> · <?= h($title) ?></strong>
								<?php if ($badge !== '') : ?>
									<span class="badge" style="<?= h($badgeStyle) ?>;font-size:10px;"><?= h($badge) ?></span>
								<?php endif; ?>
							</div>
							<div class="sdp-portal-ticket-meta"><?= h((string)($tk['portal_meta'] ?? '')) ?></div>
							<?php if (!empty($tk['portal_action'])) : ?>
								<div class="sdp-portal-ticket-action">⏰ <?= h((string)$tk['portal_action']) ?></div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="card">
			<div class="sec-title">⚡ <?= h(__('Categorias mais usadas')) ?></div>
			<div class="sdp-portal-cat-grid">
				<?php foreach ($cats as $cat) :
					$catKey = (string)($cat['cat'] ?? '');
					$catUrl = $catKey !== '' ? $H->sdpPage('portal-novo', ['cat' => $catKey]) : $uNovo;
				?>
					<?= $this->Html->link(
						'<div class="sdp-portal-cat-icon">' . h((string)($cat['icon'] ?? '')) . '</div>'
						. '<div class="sdp-portal-cat-name">' . h((string)($cat['nome'] ?? '')) . '</div>'
						. '<div class="sdp-portal-cat-sla">SLA ' . h((string)($cat['sla'] ?? '')) . '</div>',
						$catUrl,
						['class' => 'sdp-portal-cat', 'escape' => false]
					) ?>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<div class="card sdp-portal-kb-card">
		<div class="sec-title">📚 <?= h(__('Artigos populares · resolva sozinho')) ?></div>
		<div class="sdp-portal-kb-grid">
			<?php foreach ($kb as $art) :
				$code = (string)($art['code'] ?? '');
				$kbUrl = $code !== '' ? $H->sdpPage('detalhe-kb', ['code' => $code]) : $uKb;
			?>
				<?= $this->Html->link(
					'<div class="sdp-portal-kb-title">📄 ' . h((string)($art['titulo'] ?? '')) . '</div>'
					. '<div class="sdp-portal-kb-meta">' . h((string)($art['meta'] ?? '')) . '</div>',
					$kbUrl,
					['class' => 'sdp-portal-kb-item', 'escape' => false]
				) ?>
			<?php endforeach; ?>
		</div>
	</div>
</div>
