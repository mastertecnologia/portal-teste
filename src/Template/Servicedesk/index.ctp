<?php
/**
 * Apenas para visitante não autenticado (action index sem sessão).
 * Logados usam template react_app definido no controller.
 */
$this->assign('title', $title ?? 'Service Desk');
$w = $this->request->getAttribute('webroot');
$ret = $w . 'servicedesk';
?>
<div class="sd-gate card shadow-sm border-0 mx-auto" style="max-width: 420px; margin-top: 3rem;">
	<div class="card-body p-4 text-center">
		<h1 class="h4 text-dark mb-2">Central de Atendimento</h1>
		<p class="text-muted small mb-4">Escolha como deseja acessar. Você será redirecionado de volta para o Service Desk após o login.</p>
		<div class="d-grid gap-2">
			<a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'acessoEmpresa', '?' => ['redirect' => $ret]]) ?>" class="btn btn-primary btn-block">
				Acesso equipe PGM / Master
			</a>
			<a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login', '?' => ['redirect' => $ret]]) ?>" class="btn btn-outline-secondary btn-block">
				Acesso cliente
			</a>
		</div>
		<p class="small text-muted mt-4 mb-0">
			<a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'dashboard']) ?>">Voltar ao portal</a>
		</p>
	</div>
</div>
