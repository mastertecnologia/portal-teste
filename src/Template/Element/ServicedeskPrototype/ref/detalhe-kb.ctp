<?php
/**
 * @var \App\View\AppView $this
 * @var array<string,mixed> $screen
 */
$art = (array)($screen['kb_article'] ?? []);
$code = (string)($art['code'] ?? 'KB-042');
$stats = (array)($art['stats'] ?? []);
$meta = (array)($art['meta'] ?? []);
$body = (array)($art['body'] ?? []);
$related = (array)($art['related'] ?? []);
$comments = (array)($art['comments'] ?? []);
$interno = (($art['visibilidade'] ?? '') === 'interno');
$H = $this->ServicedeskPrototype;
$uKb = $H->sdpPage('kb');
?>
<div id="pg-sd-detalhe-kb" class="pgm-sd-prototype">
	<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
		<div>
			<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">PGM › <?= $this->Html->link(__('Base de Conhecimento'), $uKb, ['style' => 'color:var(--teal);']) ?> › <?= h($code) ?></div>
			<h1 style="font-size:22px;font-weight:600;margin:0;">📄 <?= h((string)($art['titulo'] ?? '')) ?></h1>
			<div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
				<span class="badge <?= $interno ? 'sdp-kb-badge-internal' : 'b-paga' ?>" style="font-size:10px;"><?= $interno ? '🔒 ' . h(__('Interno')) : '🌐 ' . h(__('Público')) ?></span>
				<?= h($code) ?> · v<?= h((string)($art['version'] ?? '1.0')) ?> · <?= h(__('atualizado')) ?> <?= h((string)($art['updated_at'] ?? '')) ?> · <?= h((string)($art['autor'] ?? '')) ?> · <?= (int)($art['read_min'] ?? 5) ?> <?= h(__('min de leitura')) ?>
			</div>
		</div>
		<div style="display:flex;gap:8px;flex-wrap:wrap;">
			<a class="btn btn-ghost btn-sm" href="<?= h($uKb) ?>">← <?= h(__('KB')) ?></a>
			<button type="button" class="btn btn-ghost btn-sm" disabled>📋 <?= h(__('Histórico de versões')) ?></button>
			<button type="button" class="btn btn-ghost btn-sm" disabled>📤 <?= h(__('Compartilhar')) ?></button>
			<button type="button" class="btn btn-ghost btn-sm" disabled>✏ <?= h(__('Editar')) ?></button>
			<button type="button" class="btn btn-primary btn-sm" disabled>💾 <?= h(__('Salvar')) ?></button>
		</div>
	</div>

	<div style="display:grid;grid-template-columns:2.2fr 1fr;gap:14px;">
		<div>
			<div class="card" style="margin-bottom:14px;">
				<div style="font-size:13px;line-height:1.7;">
					<?php foreach ($body as $block) : ?>
						<?php
						$type = (string)($block['type'] ?? 'p');
						if ($type === 'p') :
						?>
							<p><?= (string)($block['html'] ?? '') ?></p>
						<?php elseif ($type === 'ul') : ?>
							<ul style="padding-left:24px;margin-bottom:14px;">
								<?php foreach ((array)($block['items'] ?? []) as $item) : ?>
									<li><?= h((string)$item) ?></li>
								<?php endforeach; ?>
							</ul>
						<?php elseif ($type === 'ol') : ?>
							<ol style="padding-left:24px;margin-bottom:14px;">
								<?php foreach ((array)($block['items'] ?? []) as $item) : ?>
									<li><?= h((string)$item) ?></li>
								<?php endforeach; ?>
							</ol>
						<?php elseif ($type === 'pre') : ?>
							<pre style="background:var(--bg-surface);padding:12px;border-radius:6px;font-size:12px;overflow-x:auto;"><?= h((string)($block['text'] ?? '')) ?></pre>
						<?php elseif ($type === 'alert') : ?>
							<div style="background:#FAEEDA;border-left:3px solid var(--amber);padding:12px;border-radius:6px;margin-bottom:14px;"><?= (string)($block['html'] ?? '') ?></div>
						<?php endif; ?>
					<?php endforeach; ?>
					<?php if ($body === []) : ?>
						<p class="text-muted"><?= h(__('Conteúdo do artigo em elaboração.')) ?></p>
					<?php endif; ?>
				</div>
			</div>

			<div class="card" style="background:linear-gradient(135deg,var(--teal-light),#fff);">
				<div style="text-align:center;">
					<strong style="font-size:14px;"><?= h(__('Este artigo foi útil?')) ?></strong>
					<div style="margin-top:10px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
						<button type="button" class="btn btn-primary btn-sm" disabled>👍 <?= h(__('Sim, resolveu')) ?></button>
						<button type="button" class="btn btn-ghost btn-sm" disabled>👎 <?= h(__('Não ajudou')) ?></button>
						<button type="button" class="btn btn-ghost btn-sm" disabled>📝 <?= h(__('Sugerir melhoria')) ?></button>
					</div>
					<div style="font-size:11px;color:var(--text-muted);margin-top:8px;"><?= (int)($art['votos'] ?? 0) ?> <?= h(__('pessoas marcaram como útil')) ?> · ⭐ <?= h((string)($art['rating'] ?? '—')) ?>/5</div>
				</div>
			</div>
		</div>

		<div>
			<div class="card" style="margin-bottom:14px;">
				<div class="sec-title">📊 <?= h(__('Estatísticas')) ?></div>
				<div style="display:flex;flex-direction:column;gap:6px;font-size:12px;">
					<div style="display:flex;justify-content:space-between;"><span style="color:var(--text-muted);"><?= h(__('Visualizações')) ?></span><strong><?= (int)($stats['views'] ?? 0) ?></strong></div>
					<div style="display:flex;justify-content:space-between;"><span style="color:var(--text-muted);"><?= h(__('Usado em tickets')) ?></span><strong><?= (int)($stats['tickets'] ?? 0) ?></strong></div>
					<div style="display:flex;justify-content:space-between;"><span style="color:var(--text-muted);"><?= h(__('Avaliação')) ?></span><strong>⭐ <?= h((string)($art['rating'] ?? '—')) ?> (<?= (int)($art['votos'] ?? 0) ?>)</strong></div>
					<div style="display:flex;justify-content:space-between;"><span style="color:var(--text-muted);"><?= h(__('% auto-resolução')) ?></span><strong style="color:var(--teal-dark);"><?= h((string)($stats['auto_resolucao_pct'] ?? '—')) ?></strong></div>
				</div>
			</div>

			<div class="card" style="margin-bottom:14px;">
				<div class="sec-title">🏷 <?= h(__('Metadados')) ?></div>
				<div style="font-size:12px;line-height:1.7;">
					<div><strong><?= h(__('Categoria')) ?>:</strong> <?= h((string)($meta['categoria'] ?? '')) ?></div>
					<div><strong><?= h(__('Tags')) ?>:</strong>
						<div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:4px;">
							<?php foreach ((array)($meta['tags'] ?? []) as $tag) : ?>
								<span style="padding:2px 6px;background:var(--teal-light);color:var(--teal-dark);border-radius:4px;font-size:10px;"><?= h((string)$tag) ?></span>
							<?php endforeach; ?>
						</div>
					</div>
					<div><strong><?= h(__('Autor')) ?>:</strong> <?= h((string)($art['autor'] ?? '')) ?></div>
					<div><strong><?= h(__('Última revisão')) ?>:</strong> <?= h((string)($art['updated_at'] ?? '')) ?></div>
					<div><strong><?= h(__('Próxima revisão')) ?>:</strong> <?= h((string)($meta['proxima_revisao'] ?? '')) ?></div>
				</div>
			</div>

			<?php if ($related !== []) : ?>
				<div class="card" style="margin-bottom:14px;">
					<div class="sec-title">🔗 <?= h(__('Artigos relacionados')) ?></div>
					<div style="display:flex;flex-direction:column;gap:6px;font-size:12px;">
						<?php foreach ($related as $rel) : ?>
							<?= $this->Html->link(
								'<strong>' . h((string)($rel['code'] ?? '')) . '</strong><div style="font-size:11px;color:var(--text-muted);">' . h((string)($rel['titulo'] ?? '')) . '</div>',
								$H->sdpPage('detalhe-kb', ['code' => (string)($rel['code'] ?? '')]),
								['escape' => false, 'style' => 'padding:8px;background:var(--bg-surface);border-radius:6px;display:block;text-decoration:none;color:inherit;']
							) ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ($comments !== []) : ?>
				<div class="card">
					<div class="sec-title">💬 <?= h(__('Comentários internos')) ?></div>
					<div style="display:flex;flex-direction:column;gap:8px;font-size:12px;">
						<?php foreach ($comments as $c) : ?>
							<div style="padding:8px;background:var(--bg-surface);border-radius:6px;">
								<strong style="font-size:11px;"><?= h((string)($c['autor'] ?? '')) ?> · <?= h((string)($c['data'] ?? '')) ?></strong>
								<div style="font-size:11px;color:var(--text-muted);"><?= h((string)($c['texto'] ?? '')) ?></div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
