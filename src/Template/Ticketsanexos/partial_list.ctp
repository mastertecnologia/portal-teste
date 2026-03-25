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
		<?= $this->Html->link(
			'<i class="fa fa-eye"></i> Visualizar',
			['controller' => 'Tickets', 'action' => 'downloadAnexo', $reg->id, '?' => ['inline' => '1']],
			['target' => '_blank', 'rel' => 'noopener noreferrer', 'class' => 'btn btn-pgm btn-pgm-situacao btn-info btn-simple btn-xs m-r-5', 'escape' => false, 'title' => 'Abrir no navegador']
		) ?>
		<?= $this->Html->link(
			'<i class="fa fa-download"></i> Baixar',
			['controller' => 'Tickets', 'action' => 'downloadAnexo', $reg->id],
			['class' => 'btn btn-secondary btn-simple btn-xs m-r-5', 'escape' => false, 'title' => 'Download']
		) ?>
		<?php if (!empty($admin)) echo $this->Html->link('<i class="fa fa-times"></i>', ["controller" => "Tickets", "action" => "deleteAnexo", $reg->id], ['rel' => 'tooltip', 'title' => 'Excluir', 'class' => 'btn btn-danger btn-simple btn-xs', 'escape' => false]) ?>
	</td>
</tr>
<?php
	endforeach;
else:
?>
<tr><td colspan="2" class="text-muted">Nenhum anexo.</td></tr>
<?php endif; ?>
