<!-- Delete modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModal" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
					<i class="ti ti-close"></i>
				</button>
				<h4 class="modal-title" id="deleteModalTitle"></h4>
			</div>
			<div class="modal-body">
				<p id="deleteModalBody"></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default btn-simple"data-dismiss="modal">Cancelar</button>
				<?= $this->Html->link('Confirmar', ['action' => 'delete'], ['type' => 'button', 'class' => 'btn btn-danger btn-simple', 'id' => 'confirmDelete'], false); ?>
			</div>
		</div>
	</div>
</div>
