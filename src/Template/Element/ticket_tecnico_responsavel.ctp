<?php
/**
 * Bloco: técnico responsável no registro do ticket (idtecnico_responsavel / owner_id).
 * Usado no painel do ticket e alinhado ao ranking PGM do dashboard.
 *
 * @var string|null $tecnicoResponsavelLabel
 */
$label = $tecnicoResponsavelLabel ?? null;
?>
<div class="ticket-resp-tecnicos bg-white border rounded p-2 mb-2" style="border-color: #e9ecef !important;">
	<h5 class="text-muted mb-1" style="font-size: 12px;">Técnico responsável</h5>
	<?php if ($label !== null && $label !== ''): ?>
		<p class="mb-1"><strong><?= h($label) ?></strong></p>
		<p class="text-muted mb-0" style="font-size: 11px; line-height: 1.35;">Conta para o ranking do dashboard (fechamentos do mês).</p>
	<?php else: ?>
		<p class="mb-1 text-warning"><strong>Não definido</strong></p>
		<p class="text-muted mb-0" style="font-size: 11px; line-height: 1.35;">Use <em>Em execução</em> ou transferência com técnico. Ao resolver/fechar, o sistema pode gravar o funcionário logado se ainda estiver vazio. O ranking também usa o último registro de movimentação que fechou o ticket.</p>
	<?php endif; ?>
</div>
