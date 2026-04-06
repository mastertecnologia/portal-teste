<?php
/**
 * Bloco: técnico responsável no registro do ticket (idtecnico_responsavel / owner_id).
 * Usado no painel do ticket e alinhado ao ranking PGM do dashboard.
 *
 * @var string|null $tecnicoResponsavelLabel
 */
$label = $tecnicoResponsavelLabel ?? null;
?>
<div class="ticket-resp-tecnicos ticket-resp-tecnicos--soft bg-white border rounded p-2 mb-2">
	<h5 class="ticket-resp-tecnicos-title text-muted mb-1">Técnico responsável</h5>
	<?php if ($label !== null && $label !== ''): ?>
		<p class="mb-1"><strong><?= h($label) ?></strong></p>
		<p class="ticket-resp-tecnicos-hint text-muted mb-0">Conta para o ranking do dashboard (fechamentos do mês).</p>
	<?php else: ?>
		<p class="mb-1 text-warning"><strong>Não definido</strong></p>
		<p class="ticket-resp-tecnicos-hint text-muted mb-0">Use <em>Em execução</em> ou transferência com técnico. Ao resolver/fechar, o sistema pode gravar o funcionário logado se ainda estiver vazio. O ranking também usa o último registro de movimentação que fechou o ticket.</p>
	<?php endif; ?>
</div>
