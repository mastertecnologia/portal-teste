<?php
/**
 * Fragmento HTMX: lista de comentários do ticket (atualizada após adicionar comentário).
 */
echo $this->Flash->render();
if (!empty($ticketcomentarios)):
	foreach (array_reverse($ticketcomentarios) as $comentario):
		$name = isset($comentario['Users']['name']) ? $comentario['Users']['name'] : (isset($comentario['user']['name']) ? $comentario['user']['name'] : '');
		$role = isset($comentario['Users']['role']) ? $comentario['Users']['role'] : (isset($comentario['user']['role']) ? $comentario['user']['role'] : 0);
		$created = isset($comentario['created']) ? $comentario['created'] : (is_object($comentario) && isset($comentario->created) ? $comentario->created : null);
		$texto = isset($comentario['comentario']) ? $comentario['comentario'] : (is_object($comentario) && isset($comentario->comentario) ? $comentario->comentario : '');
		$dataStr = '';
		if ($created) {
			if (is_object($created) && method_exists($created, 'format')) {
				$dataStr = $created->format('d/m/Y') . ', ' . $created->format('H:i');
			} elseif (is_string($created)) {
				$t = strtotime($created);
				$dataStr = $t ? date('d/m/Y', $t) . ', ' . date('H:i', $t) : $created;
			}
		}
		$imagem = ($role == 1) ? "cliente.png" : "tecnico.png";
?>
<div class="d-flex comment-row">
	<div class="p-2">
		<span class="round">
			<img src="<?= $this->request->getAttribute('webroot') . 'assets/images/' . $imagem ?>" alt="user" width="50">
		</span>
	</div>
	<div class="comment-text">
		<h5><?= h($name) ?></h5>
		<div class="comment-footer">
			<span class="date"><?= h($dataStr) ?></span>
		</div>
		<p class="m-b-5 m-t-10"><?= $texto ?></p>
	</div>
</div>
<?php
	endforeach;
else:
	echo '<p class="text-muted">Nenhum comentário ainda.</p>';
endif;
?>
