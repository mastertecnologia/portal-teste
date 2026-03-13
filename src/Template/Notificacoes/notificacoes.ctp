<script>
	<?php if(isset($scriptNot)) foreach($scriptNot as $not) { ?>
		not = new Notification('<?= $not['titulo'] ?>',{
			body: '<?= $not['texto'] ?>',
			icon: '<?= $not['img'] ?>'
		});

		not.addEventListener('click', function(){
			window.open('<?= $not['url'] ?>');
		});
	<?php } ?>
</script>