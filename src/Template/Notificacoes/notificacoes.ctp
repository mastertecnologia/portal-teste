<script>
	<?php if(isset($scriptNot)) foreach($scriptNot as $not) { ?>
	if (window.Notification && Notification.permission === 'granted') {
		try {
			var not = new Notification('<?= $not['titulo'] ?>',{
				body: '<?= $not['texto'] ?>',
				icon: '<?= $not['img'] ?>'
			});
			not.addEventListener('click', function(){
				window.open('<?= $not['url'] ?>');
			});
		} catch (e) {}
	}
	<?php } ?>
</script>