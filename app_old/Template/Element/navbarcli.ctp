<nav class="navbar navbar-transparent navbar-absolute">
	<div class="container-fluid">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle" data-toggle="collapse">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<a class="navbar-brand" href="#"><?= $this->fetch('title')?> </a>
		</div>
		<div class="collapse navbar-collapse">
			<ul class="nav navbar-nav navbar-right">
				<li class="dropdown">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown">
						<i class="ti ti-user"> </i>					
						<p class="hidden-lg hidden-md"><?= $name ?></p>
						<label class="hidden-xs hidden-sm"><?= $name ?></label>
					</a>
					<ul class="dropdown-menu">
						<li><?= $this->Html->link('<i class="material-icons md-18">lock_outline</i> Alterar senha', ['controller' => 'Users', 'action' => 'changePassword'], ['escape' => false]); ?></li>
						<li><?= $this->Html->link('<i class="material-icons md-18">power_settings_new</i> Sair', ['controller' => 'Users', 'action' => 'logout'], ['escape' => false]); ?></li>
					</ul>
				</li>
			</ul>
		</div>
	</div>
</nav>
