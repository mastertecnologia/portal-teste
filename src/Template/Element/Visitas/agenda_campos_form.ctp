<?php
$opLembrete = $opLembrete ?? ['' => 'Sem lembrete'];
$opAgendaTipo = $opAgendaTipo ?? [0 => 'Visita'];
?>
<div class="row">
	<div class="col-lg-4 col-md-6">
		<div class="form-group">
			<label class="control-label text-muted">Tipo na agenda</label>
			<?= $this->Form->control('agenda_tipo', [
				'type' => 'select',
				'options' => $opAgendaTipo,
				'label' => false,
				'class' => 'form-control',
				'id' => 'agenda-tipo',
			]) ?>
		</div>
	</div>
	<div class="col-lg-8 col-md-6">
		<div class="form-group">
			<label class="control-label text-muted">Título (opcional)</label>
			<?= $this->Form->control('agenda_titulo', [
				'type' => 'text',
				'label' => false,
				'class' => 'form-control',
				'id' => 'agenda-titulo',
				'placeholder' => 'Ex.: Alinhamento comercial, retorno ao cliente…',
			]) ?>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-lg-6 col-md-12">
		<div class="form-group">
			<label class="control-label text-muted">Lembrete automático (notificação no portal)</label>
			<?= $this->Form->control('lembrete_minutos', [
				'type' => 'select',
				'options' => $opLembrete,
				'label' => false,
				'class' => 'form-control',
				'id' => 'lembrete-minutos',
			]) ?>
		</div>
	</div>
</div>
