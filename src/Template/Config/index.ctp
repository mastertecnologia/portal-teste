<?php
  	use Cake\Routing\Router;
	// Breadcumbs
	$this->Breadcrumbs->add('Configurações', [], ['class' => 'breadcrumb-item active']);
?>

<div class="col-6">
    <div class="card card-stats">
        <div class="card-body">  
            <p class="category"><i class="fa fa-user"></i> Usuários</p> 
            <h3 class="title"><?= $nroUsuarios ?></h3>
            <a href="<?=$this->Url->build(['controller' => 'Users', 'action' => 'index'])?>">Visualizar usuários</a>
        </div>
    </div>
</div>
<div class="col-6">
    <div class="card card-stats">
        <div class="card-body">  
            <p class="category"><i class="fas fa-users"></i> Empresas</p> 
            <h3 class="title"><?= $nroEmpresas ?></h3>
            <a href="<?=$this->Url->build(['controller' => 'Empresas', 'action' => 'index'])?>">Visualizar empresas</a>
        </div>
    </div>
</div>
<div class="col-6">
    <div class="card card-stats">
        <div class="card-body">  
            <p class="category"><i class="fa fa-handshake"></i> Empresa / Usuário</p> 
            <h3 class="title"><?= $nroEmpresasusers ?></h3>
            <a href="<?=$this->Url->build(['controller' => 'Empresasusers', 'action' => 'index'])?>">Visualizar relações</a>
        </div>
    </div>
</div>
<div class="col-6">
    <div class="card card-stats">
        <div class="card-body">
            <p class="category"><i class="ti ti-folder"></i> Sistema</p>
            <h3 class="title">Login externo</h3>
            <a href="<?=$this->Url->build(['controller' => 'Config', 'action' => 'acessos'])?>">Configurações de acesso externo</a>
        </div>
    </div>
</div>
<div class="col-6">
    <div class="card card-stats">
        <div class="card-body">
            <p class="category"><i class="fas fa-exclamation-triangle"></i> Tipos</p>
            <h3 class="title">Tipos</h3>
            <a href="<?=$this->Url->build(['controller' => 'Problemas', 'action' => 'index'])?>">Tipo de OS</a>
        </div>
    </div>
</div>
<div class="col-6">
    <div class="card card-stats">
        <div class="card-body">
            <p class="category"><i class="fas fa-map-marker-alt"></i> Status</p>
            <h3 class="title">Status</h3>
            <a href="<?=$this->Url->build(['controller' => 'Areas', 'action' => 'index'])?>">Status de OS</a>
        </div>
    </div>
</div>
<div class="col-6">
    <div class="card card-stats">
        <div class="card-body">
            <p class="category"><i class="fa fa-envelope"></i> E-mail</p>
            <h3 class="title">Suporte</h3>
            <a href="<?=$this->Url->build(['controller' => 'Config', 'action' => 'emailsuporte'])?>">E-mail de destino</a>
        </div>
    </div>
</div>
<div class="col-6">
    <div class="card card-stats">
        <div class="card-body">
            <p class="category"><i class="fa fa-calendar"></i> Visitas</p>
            <h3 class="title">Visitas</h3>
            <a href="<?=$this->Url->build(['controller' => 'Visitas', 'action' => 'calendario'])?>">Visitas da empresa</a>
        </div>
    </div>
</div>
<div class="col-6">
    <div class="card card-stats">
        <div class="card-body">
            <p class="category"><i class="ti-time"></i> Contratos e Horas</p>
            <h3 class="title">Feriados</h3>
            <a href="<?=$this->Url->build(['controller' => 'Feriados', 'action' => 'index'])?>">Cadastrar feriados (horário especial)</a>
        </div>
    </div>
</div>


