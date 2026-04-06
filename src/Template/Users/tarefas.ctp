<?php use Cake\Routing\Router; ?>

<?php foreach($tarefas as $reg){ 
    if($reg->status == 1) $checkado = 'checked';
    else $checkado = '';
    ?>
    <li class="tarefa<?= $reg->id ?>">
        <div class="custom-control custom-checkbox">
            <input <?= $checkado ?> type="checkbox" class="custom-control-input inputTarefas " data-id='<?= $reg->id ?>' id="customCheck<?= $reg->id ?>">
            <label class="custom-control-label task-done customCheck<?= $reg->id ?>" id="customCheck<?= $reg->id; ?>" for="customCheck<?= $reg->id ?>"> 
                <p class="customCheck<?= $reg->id ?><?= $reg->status == 1 ? ' users-tarefa-line-done' : '' ?>"> <?= $reg->tarefa ?> </p>
                </label>
        </div>
    </li>
<?php }?>

<script>


$(document).ready(function() {
    $('.inputTarefas').click(function(e) {
		id =  $(this).attr('data-id') ;
		var idclasse = '#' + $(this).attr('id');
		var classeclasse = '.' + $(this).attr('id');
		$(this).removeAttr('checked');

		if ($( this ).is(':checked')) {
			$(idclasse).prop( "checked", true );
			$(classeclasse).addClass('users-tarefa-line-done');
			var url = "<?= Router::url(array('controller'=>'users','action'=>'alterasituacaotarefa'));?>";
			url = url + '/' + id + '/' + 1;
			$.ajax({
				url: url,
			})
		}else{
			$(idclasse).prop( "checked", false );
			$(classeclasse).removeClass('users-tarefa-line-done');
			var url = "<?= Router::url(array('controller'=>'users','action'=>'alterasituacaotarefa'));?>";
			url = url + '/' + id + '/' + 0;
			$.ajax({
				url: url,
			})
		}	
	});
});

</script>