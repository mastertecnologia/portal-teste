<?php
/**
 * Menu lateral — ordem alinhada ao mockup / imagem de referência.
 *
 * @var \App\View\AppView $this
 * @var string $active
 * @var array<string,int> $sdpNavBadges
 */
$u = function (array $url, array $options = []): string {
	return $this->Url->build($url, $options);
};
$is = static function (string $a, string $b): string {
	return $a === $b ? ' sdp-nav-active' : '';
};
$badges = (array)($sdpNavBadges ?? []);
$badge = static function (string $key) use ($badges): string {
	$n = (int)($badges[$key] ?? 0);
	$navKey = $key === 'aprovacoes' ? 'sd-aprovacoes' : $key;
	$vis = $n <= 0 ? ' style="display:none;"' : '';

	return ' <span class="sdp-nav-badge" data-nav-badge="' . h($navKey) . '"' . $vis . '>' . $n . '</span>';
};
?>
<a class="<?= h(trim($is($active, 'dashboard'))) ?>" href="<?= h($u(['controller' => 'ServicedeskPrototype', 'action' => 'index'])) ?>"><?= h(__('Dashboard')) ?></a>
<a class="<?= h(trim($is($active, 'fila'))) ?>" href="<?= h($u(['controller' => 'ServicedeskPrototype', 'action' => 'fila'])) ?>"><?= h(__('Fila técnica')) ?></a>
<a class="<?= h(trim($is($active, 'meus'))) ?>" href="<?= h($u(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'meus'])) ?>"><?= h(__('Meus tickets')) ?></a>
<a class="<?= h(trim($is($active, 'grupo'))) ?>" href="<?= h($u(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'grupo'])) ?>"><?= h(__('Meu grupo')) ?></a>
<a class="<?= h(trim($is($active, 'kanban'))) ?>" href="<?= h($u(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'kanban'])) ?>"><?= h(__('Kanban')) ?></a>
<a class="<?= h(trim($is($active, 'aprovacoes'))) ?>" href="<?= h($u(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'aprovacoes'])) ?>"><?= h(__('Aprovações')) ?><?= $badge('aprovacoes') ?></a>
<a class="<?= h(trim($is($active, 'cmdb'))) ?>" href="<?= h($u(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'cmdb'])) ?>"><?= h(__('CMDB · Ativos')) ?></a>
<a class="<?= h(trim($is($active, 'problemas'))) ?>" href="<?= h($u(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'problemas'])) ?>"><?= h(__('Problemas')) ?></a>
<a class="<?= h(trim($is($active, 'mudancas'))) ?>" href="<?= h($u(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'mudancas'])) ?>"><?= h(__('Mudanças')) ?></a>
<a class="<?= h(trim($is($active, 'contratos'))) ?>" href="<?= h($u(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'contratos'])) ?>"><?= h(__('Contratos SLA')) ?></a>
<a class="<?= h(trim($is($active, 'fat'))) ?>" href="<?= h($u(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'fat'])) ?>"><?= h(__('Faturamento')) ?></a>
<a class="<?= h(trim($is($active, 'kb'))) ?>" href="<?= h($u(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'kb'])) ?>"><?= h(__('Base conhecimento')) ?></a>
<a class="<?= h(trim($is($active, 'portal'))) ?>" href="<?= h($u(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'portal'])) ?>"><?= h(__('Portal cliente')) ?></a>
<a class="<?= h(trim($is($active, 'calendar'))) ?>" href="<?= h($u(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'calendar'])) ?>"><?= h(__('Plantões')) ?></a>
<a class="<?= h(trim($is($active, 'csat'))) ?>" href="<?= h($u(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'csat'])) ?>"><?= h(__('CSAT & NPS')) ?></a>
<a class="<?= h(trim($is($active, 'relatorios'))) ?>" href="<?= h($u(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'relatorios'])) ?>"><?= h(__('Relatórios')) ?></a>
<a class="<?= h(trim($is($active, 'config'))) ?>" href="<?= h($u(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'config'])) ?>"><?= h(__('SLA & Config')) ?></a>
<a class="<?= h(trim($is($active, 'perm'))) ?>" href="<?= h($u(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'perm'])) ?>"><?= h(__('Permissões')) ?></a>
<a class="<?= h(trim($is($active, 'integracoes'))) ?>" href="<?= h($u(['controller' => 'ServicedeskPrototype', 'action' => 'view', 'integracoes'])) ?>"><?= h(__('Integrações')) ?></a>

<div class="sdp-nav-h" style="margin-top:12px;"><?= h(__('Oficial')) ?></div>
<a href="<?= h($u(['controller' => 'Servicedesk', 'action' => 'index'])) ?>"><?= h(__('Service Desk clássico')) ?></a>
<a href="<?= h($u(['controller' => 'Servicedesk', 'action' => 'operacional'])) ?>"><?= h(__('Painel operacional')) ?></a>
