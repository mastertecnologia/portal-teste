<?php
/**
 * Formulário público de CSAT (sem autenticação).
 *
 * @var \App\View\AppView $this
 * @var \Cake\Datasource\EntityInterface $ticket
 * @var string $csatToken
 */
$csrf = (string)$this->request->getAttribute('csrfToken');
?>
<div style="max-width:560px;margin:24px auto;">
	<div class="card" style="padding:32px;">
		<h1 style="font-size:22px;font-weight:600;margin:0 0 6px;">⭐ <?= h(__('Como foi seu atendimento?')) ?></h1>
		<div style="font-size:13px;color:var(--text-muted);margin-bottom:18px;">
			<?= h(__('Ticket #{0}', (int)$ticket->get('id'))) ?> ·
			<?= h(\Cake\Utility\Text::truncate((string)$ticket->get('solicitacao'), 80, ['ellipsis' => '…'])) ?>
		</div>

		<form method="post" id="csatForm">
			<input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">

			<div class="field">
				<label style="font-size:13px;font-weight:600;margin-bottom:8px;display:block;"><?= h(__('Sua nota (1 a 5)')) ?> *</label>
				<div id="csatStars" style="display:flex;gap:8px;font-size:36px;cursor:pointer;justify-content:center;margin:12px 0;">
					<?php for ($i = 1; $i <= 5; $i++) : ?>
						<span data-star="<?= $i ?>" style="color:#ddd;transition:color .15s;">★</span>
					<?php endfor; ?>
				</div>
				<input type="hidden" name="csat_score" id="csat_score" value="" required>
			</div>

			<div class="field" style="margin-top:14px;">
				<label><?= h(__('Em escala 0-10, indicaria nossos serviços a um colega? (NPS)')) ?></label>
				<input type="number" name="nps_score" min="0" max="10" placeholder="0 a 10 (opcional)">
			</div>

			<div class="field" style="margin-top:14px;">
				<label><?= h(__('Comentário (opcional)')) ?></label>
				<textarea name="comentario" rows="3" placeholder="<?= h(__('Conte como podemos melhorar...')) ?>"></textarea>
			</div>

			<div style="margin-top:18px;text-align:center;">
				<button type="submit" class="btn btn-primary" style="padding:10px 28px;font-size:14px;">📨 <?= h(__('Enviar resposta')) ?></button>
			</div>
		</form>
	</div>
	<p style="text-align:center;font-size:11px;color:var(--text-muted);margin-top:14px;">
		<?= h(__('Sua resposta é confidencial e usada apenas para melhorar nossos serviços.')) ?>
	</p>
</div>

<?php $this->start('script'); ?>
<script>
(function () {
	var stars = document.querySelectorAll('#csatStars span');
	var input = document.getElementById('csat_score');
	function paint(n) {
		stars.forEach(function (s, i) { s.style.color = (i < n) ? '#E9A025' : '#ddd'; });
	}
	stars.forEach(function (s) {
		s.addEventListener('mouseenter', function () { paint(parseInt(this.dataset.star, 10)); });
		s.addEventListener('click', function () {
			var v = parseInt(this.dataset.star, 10);
			input.value = v;
			paint(v);
		});
	});
	document.getElementById('csatStars').addEventListener('mouseleave', function () {
		paint(parseInt(input.value, 10) || 0);
	});
})();
</script>
<?php $this->end(); ?>
