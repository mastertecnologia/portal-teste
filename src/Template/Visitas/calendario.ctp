<?php
	use Cake\Routing\Router;
	$this->Breadcrumbs->add('Agenda', [], ['class' => 'breadcrumb-item active']);
	$labelsTipo = [0 => 'Visita', 1 => 'Reunião', 2 => 'Tarefa', 3 => 'Lembrete'];
	$defaultEventsArray = [];
	foreach ($visitas as $reg) {
		switch ($reg->situacao) {
			case 0: $classname = 'bg-info'; break;
			case 1: $classname = 'bg-success'; break;
			case 2: $classname = 'bg-warning'; break;
			case 3: $classname = 'bg-danger'; break;
			default: $classname = 'bg-info';
		}
		$tipo = isset($reg->agenda_tipo) ? (int)$reg->agenda_tipo : 0;
		$tituloCustom = isset($reg->agenda_titulo) ? trim((string)$reg->agenda_titulo) : '';
		if ($tituloCustom !== '') {
			$titulo = $tituloCustom;
		} else {
			$tipoName = $labelsTipo[$tipo] ?? $labelsTipo[0];
			if (!empty($reg->cliente)) {
				$c = $reg->cliente;
				$cn = !empty($c->razaosocial) ? $c->razaosocial : ($c->nome ?? '');
				$titulo = $tipoName . ': ' . $cn;
			} else {
				$titulo = $tipoName;
			}
		}
		$defaultEventsArray[] = [
			'title' => $titulo,
			'start' => $reg->data->format('Y-m-d') . 'T' . $reg->horaini->format('H:i:00'),
			'end' => $reg->data->format('Y-m-d') . 'T' . $reg->horafim->format('H:i:00'),
			'className' => $classname,
			'id' => (int)$reg->id,
		];
	}
	$feriadosUrl = Router::url(['controller' => 'Visitas', 'action' => 'feriados']);
?>
<style>
	.calendar-events { cursor: default; }
	.fc-event { cursor: pointer; }
	.fc-button-group { display: none; }
	.fc-event.pgm-fc-feriado { background-color: #6f6f6f !important; border-color: #555 !important; color: #fff !important; opacity: 0.92; }
	.fc-event.pgm-fc-feriado-estadual { box-shadow: inset 3px 0 0 #17a2b8; }
	.fc-event.pgm-fc-feriado-empresa { box-shadow: inset 3px 0 0 #ffc107; }
</style>
<div class="row">
	<div class="col-md-12">
		<div class="card">
			<div class="">
				<div class="row">
					<div class="col-lg-3">
						<div class="card-body">
							<h4 class="card-title m-t-10">
								<?php  if($role == 0) echo $this->Html->link('Lista completa de visitas', ['action' => 'index']);
 										else echo $this->Html->link('Lista completa de visitas', ['action' => 'indexcliente']); ?>
							</h4>
							<div class="row">
								<div class="col-md-12">
									<div id="calendar-events" class="">
									<div class="calendar-events" data-class="bg-info"><i class="fa fa-circle text-info"></i> Agendada </div>
										<div class="calendar-events" data-class="bg-success"><i class="fa fa-circle text-success"></i> Finalizada </div>
										<div class="calendar-events" data-class="bg-warning"><i class="fa fa-circle text-warning"></i> Pendente</div>
										<div class="calendar-events" data-class="bg-danger"><i class="fa fa-circle text-danger"></i> Cancelada </div>
										<div class="calendar-events m-t-10" style="cursor:default"><i class="fa fa-circle" style="color:#6f6f6f"></i> Feriados nacionais </div>
										<div class="calendar-events" style="cursor:default"><i class="fa fa-circle text-info"></i> Feriado estadual (UF da empresa) </div>
										<div class="calendar-events" style="cursor:default"><i class="fa fa-circle" style="color:#ffc107"></i> Feriado cadastrado (empresa/global) </div>
										<div class="small text-muted m-t-5">Municipais: inclua em Configurações → Feriados.</div>
									</div>
									<!-- checkbox -->
									<div class="checkbox m-t-20 hide">
										<input id="drop-remove" type="checkbox" checked>
										<label for="drop-remove">
											Remove after drop
										</label>
									</div>
								   <?php if($role == 0){ ?>
									<a href="javascript:void(0)" id="novavisita" data-toggle="modal" data-target="#add-new-event" class="btn btn-pgm btn-pgm-salvar m-t-10 btn-success btn-block waves-effect waves-light">
										<i class="ti-plus"></i> Cadastrar nova visita
									</a>
								   <?php } ?>
								</div>
							</div>
						</div>
					</div>
					<div class="col-lg-9">
						<div class="card-body b-l">
							<div id="calendar"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="modal none-border" id="my-event">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title"><strong>Cadastrar Visita</strong></h4>
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			</div>
			<div class="modal-body"></div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary waves-effect" data-dismiss="modal">Fechar</button>
				<button type="button" class="btn btn-pgm btn-pgm-salvar btn-success save-event waves-effect waves-light">Cadastrar visita</button>
				<button type="button" class="btn btn-danger delete-event waves-effect waves-light" data-dismiss="modal">Cancelar visita</button>
			</div>
		</div>
	</div>
</div>
<div class="modal fade none-border" id="add-new-event">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title"> Visita </h4>
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			</div>
			<div class="modal-body">
				<?= $this->Form->create($visitaAdd, ['class' => 'form-material']) ?>
					<div class="row">
						<div class="col-12">
							<label class="control-label text-muted">Cliente</label>
							<?= $this->Form->control('idcliente', ['title' => 'Selecione o cliente que irá visitar', 'title' => 'Selecione o cliente que irá visitar', 'data-live-search' => true, 'class' => 'form-control selectpicker', 'id' => 'idcliente', 'options' => $clientes, 'label' => false, 'required' => true]) ?>
						</div>
						<br>
						<br>
						<div class="col-12">
							<div class="form-group">
								<br>
								<label class="control-label text-muted">Situação</label>
								<?= $this->Form->control('situacao', ['class' => 'form-control', 'options' => C_VisitasSituacaoQuery, 'default' => 0, 'label' => false]) ?>
							</div>
						</div>
					</div>
					<?= $this->element('Visitas/agenda_campos_form') ?>
					<br/>
					<div class="row">
						<div class="col-lg-4 ">
							<div class="form-group">
								<label class="control-label text-muted">Data</label>
								<?= $this->Form->text('data', ['class' => 'form-control datepicker ', 'id' => 'data', 'placeholder' => 'Insira a data', 'required' => true, 'data-mask' => '99/99/9999']) ?>
							</div>
						</div>
						<div class="col-lg-4 ">
							<div class="form-group">
								<label class="control-label text-muted">Hora Inicial</label>
								<?= $this->Form->text('horaini', ['autocomplete' => "off", 'id' => 'horainicial', 'class' => 'form-control hora clockpicker', 'label' => false, 'required' => true])?>
							</div>
						</div>
						<div class="col-lg-4 ">
							<div class="form-group">
								<label class="control-label text-muted">Hora Final</label>
								<?= $this->Form->text('horafim', ['autocomplete' => "off", 'id' => 'horafinal', 'class' => 'form-control hora clockpicker', 'label' => false, 'required' => true])?>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-lg-12">
							<label class="control-label text-muted">Lista de membros</label>
							<?= $this->Form->control('users._ids', ['id' => 'listamembros', 'options' => $users, 'label' => false, 'multiple', 'required' => true, 'class' => 'selectpicker']) ?>
						</div>
					</div>
					<br/>
						<div class="row">
							<div class="col-3">
								<label class="control-label text-muted">Valor (R$)</label>
								<?= $this->Form->text('valor', ['class' => 'form-control mascaramonetaria', 'label' => false, 'placeholder' =>'Insira o valor'])?>
							</div>
						</div>
					<br>
					<div class="row">
						<div class="col-lg-12">
							<div class="form-group">
								<label class="control-label text-muted">Motivo</label>
								<?= $this->Form->textarea('motivo', ['id' => 'motivovisita', 'class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' =>'Insira o motivo']) ?>
							</div>
						</div>
					</div>

			</div>
			<div class="modal-footer">
				<?= $this->Form->button('Salvar Visita', ['class' => 'btn btn-pgm btn-pgm-salvar btn-success']) ?>
				<button type="button" class="btn btn-danger waves-effect" data-dismiss="modal">Fechar</button>
			</div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
<div class="modal fade none-border" id="edit-event">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title"> Visita </h4>
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			</div>
			<div  id='viewVisita'>
			</div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>
<script>
	$('.datepicker').bootstrapMaterialDatePicker({ format : 'DD/MM/YYYY', lang : 'pt-br', time : false, switchOnClick : true, nowButton : true, cancelText : 'Cancelar' , 'setDate' : 'currentDate', nowText : 'Hoje'});

	$('.clockpicker').clockpicker({ donetext: 'Confirmar' })

	$('.hora').mask('99:99');

	$('.role').hide();
	var role = '<?= $role; ?>';

	!function($) {

		"use strict";

		var CalendarApp = function() {
			this.$locale = 'pt-br',
			this.$body = $("body"),
			this.$calendar = $('#calendar'),
			this.$event = ('#calendar-events div.calendar-events'),
			this.$categoryForm = $('#add-new-event form'),
			this.$extEvents = $('#calendar-events'),
			this.$modal = $('#my-event'),
			this.$saveCategoryBtn = $('.save-category'),
			this.$calendarObj = null
		};


		/* on drop */
		CalendarApp.prototype.onDrop = function (eventObj, date) {
			var $this = this;
				// retrieve the dropped element's stored Event Object
				var originalEventObject = eventObj.data('eventObject');
				var $categoryClass = eventObj.attr('data-class');
				// we need to copy it, so that multiple events don't have a reference to the same object
				var copiedEventObject = $.extend({}, originalEventObject);
				// assign it the date that was reported
				copiedEventObject.start = date;
				if ($categoryClass)
					copiedEventObject['className'] = [$categoryClass];
				// render the event on the calendar
				$this.$calendar.fullCalendar('renderEvent', copiedEventObject, true);
				// is the "remove after drop" checkbox checked?
				if ($('#drop-remove').is(':checked')) {
					// if so, remove the element from the "Draggable Events" list
					eventObj.remove();
				}
		},
		/* on click on event */
		CalendarApp.prototype.onEventClick =  function (calEvent, jsEvent, view) {
			var cn = calEvent.className || '';
			var cns = (Array.isArray(cn) ? cn.join(' ') : String(cn));
			if (cns.indexOf('pgm-fc-feriado') !== -1 || !calEvent.id) {
				return false;
			}
			var url = "<?php if($role == 0) echo Router::url(array('controller'=>'Visitas','action'=>'edit'));
							else			echo Router::url(array('controller'=>'Visitas','action'=>'view')) ?>";
			url = url + '/' + calEvent.id;
			$.ajax({
				type: "GET",
				url: url,
				data: {'id' : calEvent.id},
				success:function(data){
					$('#viewVisita').html(data);
					$('.selectpicker').selectpicker();
					$('#edit-event').modal('toggle');
					$('#edit-event').modal('show');
					$('#edit-event').modal('hide');
				},
			});
		},

		/* on select */

		CalendarApp.prototype.onSelect = function (start, end, allDay) {
			if(role == 0){
				var date = new Date(start);
				var start = moment(date).utc().format('DD/MM/YYYY');
				$("#data").val(start);
				$('#add-new-event').modal('toggle');
			}
		},
		CalendarApp.prototype.enableDrag = function() {
			//init events
			$(this.$event).each(function () {
				// create an Event Object (http://arshaw.com/fullcalendar/docs/event_data/Event_Object/)
				// it doesn't need to have a start or end
				var eventObject = {
					title: $.trim($(this).text()) // use the element's text as the event title
				};
				// store the Event Object in the DOM element so we can get to it later
				$(this).data('eventObject', eventObject);
				// make the event draggable using jQuery UI
				// $(this).draggable({
				//     zIndex: 999,
				//     revert: true,      // will cause the event to go back to its
				//     revertDuration: 0  //  original position after the drag
				// });
			});
		}


		/* Initializing */
		CalendarApp.prototype.init = function() {
			this.enableDrag();
			/*  Initialize the calendar  */
			var date = new Date();
			var d = date.getDate();
			var m = date.getMonth();
			var y = date.getFullYear();
			var form = '';
			var today = new Date($.now());
			var defaultEvents = <?= json_encode($defaultEventsArray, JSON_UNESCAPED_UNICODE) ?>;
			var feriadosUrl = <?= json_encode($feriadosUrl, JSON_UNESCAPED_UNICODE) ?>;
			var $this = this;
			$this.$calendarObj = $this.$calendar.fullCalendar({
				slotDuration: '00:15:00', /* If we want to split day time each 15minutes */
				minTime: '08:00:00',
				maxTime: '19:00:00',
				defaultView: 'month',
				handleWindowResize: true,
				locale: 'pt-br',
				header: {
					left: 'prev,next today',
					center: 'title',
					right: 'month,basicWeek,basicDay,agendaWeek,agendaDay'
				},
				eventSources: [
					defaultEvents,
					{ url: feriadosUrl, editable: false }
				],
				editable: false,
				droppable: false, // this allows things to be dropped onto the calendar !!!
				eventLimit: true, // allow "more" link when too many events
				selectable: true,
				drop: function(date) { $this.onDrop($(this), date); },
				select: function (start, end, allDay) { $this.onSelect(start, end, allDay); },
				eventClick: function(calEvent, jsEvent, view) { $this.onEventClick(calEvent, jsEvent, view); }
			});
			//on new event
			this.$saveCategoryBtn.on('click', function(){
				var categoryName = $this.$categoryForm.find("input[name='category-name']").val();
				var categoryColor = $this.$categoryForm.find("select[name='category-color']").val();
				if (categoryName !== null && categoryName.length != 0) {
					$this.$extEvents.append('<div class="calendar-events" data-class="bg-' + categoryColor + '" style="position: relative;"><i class="fa fa-circle text-' + categoryColor + '"></i>' + categoryName + '</div>')
					$this.enableDrag();
				}
			});
		},
		//init CalendarApp
		$.CalendarApp = new CalendarApp, $.CalendarApp.Constructor = CalendarApp;
	}(window.jQuery),

	//initializing CalendarApp
	function($) {
		"use strict";
		$.CalendarApp.init();
	}(window.jQuery);

	// Reseta os campos do modal
	$("#novavisita").click(function() {
		$('#idcliente').val(0);
		$("#situacao").val(0);
		$("#listamembros").val('default').selectpicker("refresh");
		$("#data").val("");
		$('#horainicial').val("");
		$('#horafinal').val("");
		$("#motivovisita").val("");
		$('#agenda-tipo').val('0');
		$('#agenda-titulo').val('');
		$('#lembrete-minutos').val('');
	});
</script>
