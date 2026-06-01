<?php
/**
 * Hub pg-config — painel administrativo premium (somente admin).
 *
 * @var \App\View\AppView $this
 */
$card = static function ($label, $desc, $url, $icon) {
	return compact('label', 'desc', 'url', 'icon');
};
$cards = [
	$card(__('Dados da empresa'), __('CNPJ · logo · multi-empresa'), ['action' => 'view', 'empresa'], '🏢'),
	$card(__('Usuários ERP'), __('contas · senhas'), ['action' => 'usuarios'], '👥'),
	$card(__('Auditoria · LGPD'), __('log de ações'), ['action' => 'auditoria'], '📜'),
	$card(__('Integrações'), __('API Grid · ERP · webhooks'), ['action' => 'view', 'config-integracoes'], '🔌'),
	$card(__('E-mail'), __('SMTP · templates'), ['action' => 'view', 'config-email'], '✉️'),
	$card(__('Segurança'), __('RBAC · políticas'), ['action' => 'view', 'config-seguranca'], '🔒'),
	$card(__('Backup'), __('retenção · export'), ['action' => 'view', 'config-backup'], '💾'),
	$card(__('Numeração'), __('séries documentos'), ['action' => 'view', 'config-numeracao'], '🔢'),
	$card(__('Notificações'), __('alertas sistema'), ['action' => 'view', 'config-notificacoes'], '🔔'),
	$card(__('Localização'), __('idioma · fuso'), ['action' => 'view', 'config-localizacao'], '🌐'),
	$card(__('Controle de acesso'), __('papéis · matriz'), ['action' => 'acessoCentral'], '🛡️'),
	$card(__('Empresas'), __('multi-empresa'), ['controller' => 'EmpresasPrototype', 'action' => 'lista'], '🏙️'),
];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">PGM ERP › <?= h(__('Configurações')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">⚙ <?= h(__('Configurações do sistema')) ?></h1>
		<p style="font-size:12px;color:var(--text-muted);margin-top:4px;"><?= h(__('Somente administradores. Parâmetros globais e integrações.')) ?></p>
	</div>
	<?= $this->Html->link(__('Painel clássico'), ['controller' => 'Config', 'action' => 'index'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;">
	<?php foreach ($cards as $c) : ?>
	<a class="card" href="<?= h($this->Url->build($c['url'])) ?>" style="text-decoration:none;color:inherit;display:block;">
		<div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
			<span style="font-size:28px;"><?= h($c['icon']) ?></span>
			<strong style="font-size:14px;"><?= h($c['label']) ?></strong>
		</div>
		<div style="font-size:12px;color:var(--text-muted);"><?= h($c['desc']) ?></div>
	</a>
	<?php endforeach; ?>
</div>
