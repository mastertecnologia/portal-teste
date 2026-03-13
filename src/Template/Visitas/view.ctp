<?php 
	$this->Breadcrumbs->add('Agenda', ['controller' => 'Agenda', 'action' => 'index'], ['class' => 'breadcrumb-item']);
	$this->Breadcrumbs->add('Detalhes', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-body">
			<h3 class="title"><strong>Detalhes da Visita </strong></h3>
			<div class="row">
				<div class="col-lg-12">
					<h4><strong>Motivo</strong></h4><p> <?= nl2br($visita->motivo) ?></p>
				</div>
				<div class="col-lg-3">
					<h4><strong>Situação</strong></h4><p> <?= DescricaoSituacaoVisitas($visita->situacao) ?></p>
				</div>
				<div class="col-lg-4">
					<h4><strong>Membro(s) da visita</strong></h4>
					<?php 
					foreach ($visita->users as $atendente){
						echo $atendente->name . '<br>'; 
					} ?>
				</div>
				<div class="col-lg-5">
				 	<h4><strong>Data e Hora</strong></h4> <?= date_format($visita->data, 'd/m/Y') . ' entre ' .  date_format($visita->horaini, 'H:i') . " e " . date_format($visita->horafim, 'H:i') ?></p>
				</div>
				<div id='viewVisitaBtn'>
				 	
				</div>
			</div>
		</div>
		<div class="col-lg-12">
			<h4><strong>Valor</strong></h4><p> R$: <?= $visita->valor ?></p>
		</div>
	</div>
</div>
