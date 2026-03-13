<?php
/**
 * Fragmento HTMX: lista de anexos do ticket (atualizada após adicionar anexo).
 */
$flash = $this->Flash->render();
if (!empty($flash)) echo '<tr><td colspan="2">' . $flash . '</td></tr>';
if (!empty($ticketanexos)):
	foreach ($ticketanexos as $reg):
?>
<tr>
	<td><?= h($reg->arquivo) ?></td>
	<td class="td-actions">
		<?= $this->Html->link("<i class='fa fa-eye'></i><div class='ripple-container'></div>", ["controller" => "Tickets", "action" => "downloadAnexo", $reg->id], ['rel' => 'tooltip', 'title' => 'Visualizar', 'class' => 'btn btn-info btn-simple btn-xs', 'escape' => false]) ?>
		<?php if (!empty($admin)) echo $this->Html->link('<i class="fa fa-times"></i>', ["controller" => "Tickets", "action" => "deleteAnexo", $reg->id], ['rel' => 'tooltip', 'title' => 'Excluir', 'class' => 'btn btn-danger btn-simple btn-xs', 'escape' => false]) ?>
	</td>
</tr>
<?php
	endforeach;
else:
?>
<tr><td colspan="2" class="text-muted">Nenhum anexo.</td></tr>
<?php endif; ?>
