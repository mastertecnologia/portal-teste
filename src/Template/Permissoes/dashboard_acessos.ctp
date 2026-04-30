<?php

$this->assign('title', $title ?? 'IAM');


$flt = $filtros_dashboard ?? ['days' => $dash['period_days'] ?? 30];

$qy = [];

if (!empty($flt['days'])) {

	$qy['days'] = (int)$flt['days'];

}
if (!empty($flt['company_id'])) {


	$qy['company_id'] = (int)$flt['company_id'];


}




if (!empty($flt['status'])) {


	$qy['status'] = (string)$flt['status'];


}




?>

<div class="col-md-12 card">







	<div class="card-header">



		<strong><?= h($title ?? '') ?></strong>






		<span class="pull-right">





			<?= $this->Html->link('CSV resumo', ['action' => 'dashboardAcessosCsv', '?' => array_merge($qy, ['tipo' => 'resumo'])]) ?>





			&middot;



			<?= $this->Html->link('CSV pendentes', ['action' => 'dashboardAcessosCsv', '?' => array_merge($qy, ['tipo' => 'pendentes'])]) ?>





			&middot;



			<?= $this->Html->link('CSV grants', ['action' => 'dashboardAcessosCsv', '?' => array_merge($qy, ['tipo' => 'grants'])]) ?>





		</span>






	</div>















	<div class="card-body">







		<form method="get" action="<?= h($this->Url->build(['action' => 'dashboardAcessos'])) ?>" class="row" style="margin-bottom:14px">




			<div class="col-md-2">




				<label>Período (d)</label>



				<input class="form-control" type="number" name="days" min="7" max="365" value="<?= h((string)($dash['period_days'] ?? 30)) ?>">






			</div>





			<div class="col-md-4">





				<label>Empresa</label>








				<select class="form-control" name="company_id">







					<option value="0"><?= '— todas —' ?></option>








					<?php foreach ((array)$lista_empresas as $em) :

						if (empty($em->id)) {

							continue;
						}




						$sel = (string)(int)$em->id === (string)(int)($dash['company_id'] ?? 0) ? ' selected' : ''; ?>








						<option value="<?= (int)$em->id ?>"<?= $sel ?>><?= h((string)($em->nomefantasia ?? $em->nome ?? $em->id)) ?></option>






					<?php endforeach ?>








				</select>



			</div>








			<div class="col-md-4">








				<label>Status (CSV, opcional)</label>








				<input class="form-control" name="status" placeholder="pending_manager,pending_admin" value="<?= h((string)($dash['status_filter'] ?? '')) ?>">







			</div>














			<div class="col-md-2">







				<label>&nbsp;</label>








				<button type="submit" class="btn btn-primary btn-block">Filtrar</button>






			</div>

















		</form>

















		<?php if (!empty($dash['_error'])) : ?>

















			<p class="text-danger">Leitura parcial: <?= h((string)$dash['_error']) ?></p>

















		<?php endif; ?>


















		<div class="row">







			<?php foreach ([


				'pending_manager' => 'Pend. manager',





				'pending_admin' => 'Pend. admin',















				'admin_approved_waiting_grant' => 'Aguardando grant',





				'created_last_window' => 'Criados (janela)',








				'granted_last_window' => 'Granted',








				'rejected_last_window' => 'Rejeitados',








				'active_grants' => 'Grants ativos',








				'grants_expiring_7d' => 'Vencem <=7d',








				'critical_active_grants' => 'Críticos ativos',




				'expired_still_active_anomaly' => 'Anomalia expirado/act.',








			] as $k => $label) :

			 ?>








				<div class="col-md-3">







					<div class="well"><?= h($label) ?></div>



					<p class="lead"><?= h((string)($dash[$k] ?? 0)) ?></p>



				</div>



			<?php endforeach ?>



		</div>

















		<hr>

















		<h4>Tempos médios (h) — apenas pedidos <em>granted</em> na janela</h4>















		<div class="row">







			<div class="col-md-4">















				<div class="well">Criação → manager</div>



				<p><?= h($dash['avg_hours_created_to_manager'] !== null ? (string)$dash['avg_hours_created_to_manager'] : '—') ?></p>



			</div>








			<div class="col-md-4">








				<div class="well">Manager → admin</div>



				<p><?= h($dash['avg_hours_manager_to_admin'] !== null ? (string)$dash['avg_hours_manager_to_admin'] : '—') ?></p>



			</div>

















			<div class="col-md-4">















				<div class="well">Admin → grant</div>



				<p><?= h($dash['avg_hours_admin_to_grant'] !== null ? (string)$dash['avg_hours_admin_to_grant'] : '—') ?></p>



			</div>

















		</div>

















		<hr>

















		<h4>Top permissões</h4>



		<pre class="small"><?php


foreach ((array)( $dash['top_permissions'] ?? [] ) as $code => $c) {


	echo h((string)$code) . ": " . h((string)$c) . "\n";


}




?></pre>























		<h4>Top módulos</h4>















		<pre class="small"><?php


foreach ((array)( $dash['top_modules'] ?? [] ) as $code => $c) {


	echo h((string)$code) . ": " . h((string)$c) . "\n";


}


?></pre>


		<h4>Top usuários (id → contagem)</h4>


		<pre class="small"><?php


foreach ((array)( $dash['top_users'] ?? [] ) as $code => $c) {


	echo h((string)$code) . ": " . h((string)$c) . "\n";


}


?></pre>



		<h4>Top papéis sugeridos (id)</h4>



		<pre class="small"><?php


foreach ((array)( $dash['top_roles'] ?? [] ) as $code => $c) {


	echo h((string)$code) . ": " . h((string)$c) . "\n";


}


?></pre>


		<hr>




		<h4>Auditoria recente</h4>



		<ul class="small">





			<?php foreach ((array)($dash['recent_audit'] ?? []) as $lg) :

			 ?>





				<li>
					<?= h((string)($lg['created'] ?? '')) ?> —
					<?= h((string)($lg['action_type'] ?? '')) ?>

					<?php if (!empty($lg['request_id'])) :

					 ?>

						req #<?= h((string)$lg['request_id']) ?>

					<?php endif ?>





				</li>






			<?php endforeach ?>





		</ul>

















		<p class="small text-muted"><?= $this->Html->link('Matriz visual', ['action' => 'matrizVisual']) ?>





			|

			<?= $this->Html->link('Auditoria IAM', ['controller' => 'RbacAccessRequests', 'action' => 'auditLogs'])




			?> |






			<?= $this->Html->link('Grants', ['controller' => 'RbacAccessGrants', 'action' => 'index']) ?>




		</p>

















	</div>

















</div>
