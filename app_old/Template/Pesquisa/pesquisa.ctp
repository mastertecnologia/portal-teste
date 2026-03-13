<?php 
    use Cake\Routing\Router; 
    foreach($resultados as $resultado) { ?>
    <li><a class='link link-btn' href="<?= Router::url(['controller' => $resultado['Controller'], 'action' => $resultado['Action']]);?> "><?= $resultado['ControllerQueAparece'] . ' > ' . $resultado['ActionQueAparece'] ?> </a></li>
<?php } ?>