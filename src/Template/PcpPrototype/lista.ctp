<?php
/**
 * Visão geral PCP — grid de cards das 13 telas.
 *
 * @var \App\View\AppView $this
 * @var array<string,array<string,mixed>> $pcpTiles
 */
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Indústria · PCP')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🏭 <?= h(__('Planejamento e Controle de Produção')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Módulo industrial completo — em planejamento. Cada card abaixo abre o roteiro de implementação.')) ?></div>
	</div>
</div>

<div class="alert-box alert-amber">
	<strong><?= h(__('Status: pré-implantação.')) ?></strong>
	<?= h(__('As 13 telas do mockup foram desenhadas mas dependem de modelagem nova no banco (BOM, roteiros, MRP, OPs). Roadmap estimado: 10-15 dias de desenvolvimento. Clique em cada tile para ver o detalhamento.')) ?>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;">
	<?php foreach ($pcpTiles as $key => $tile) : ?>
		<a href="<?= h($this->Url->build(['controller' => 'PcpPrototype', 'action' => 'view', $key])) ?>" class="card" style="margin:0;text-decoration:none;color:inherit;transition:transform .15s,box-shadow .15s;display:block;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 24px rgba(0,0,0,.12)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
			<div style="font-size:28px;margin-bottom:8px;line-height:1;"><?= $tile['icon'] ?></div>
			<strong style="display:block;font-size:14px;color:var(--text);"><?= h((string)$tile['title']) ?></strong>
			<div style="font-size:11px;color:var(--text-muted);margin-top:4px;line-height:1.6;"><?= h((string)$tile['subtitle']) ?></div>
		</a>
	<?php endforeach; ?>
</div>
