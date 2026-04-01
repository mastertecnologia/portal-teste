<?php
/**
 * Shell visual escuro reutilizável (contratos, acessos, horas, etc.).
 * @var string $formId id do <form>
 */
$formId = isset($formId) ? (string)$formId : 'form-pgm-dark';
if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_\-]*$/', $formId)) {
	$formId = 'form-pgm-dark';
}
$fid = h($formId);
?>
<style>
.clictr-edit-page{padding-bottom:24px;}
.clictr-card{
	background:#161b22;border:1px solid #21262d;border-radius:12px;padding:22px 24px 20px;
	max-width:960px;margin:0 auto;box-shadow:0 4px 24px rgba(0,0,0,.18);
}
.clictr-card--wide{max-width:none;}
.clictr-section{margin-top:18px;padding-top:18px;border-top:1px solid #21262d;}
.clictr-section:first-of-type{margin-top:0;padding-top:0;border-top:none;}
.clictr-section-title{
	font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#6e7681;margin-bottom:14px;
}
.clictr-label{
	font-size:.72rem;font-weight:600;color:#8b949e;margin-bottom:6px;display:block;letter-spacing:.04em;text-transform:uppercase;
}
.clictr-edit-page .form-group{margin-bottom:14px;}
#<?= $fid ?>.form-material .form-control,
#<?= $fid ?>.form-material .form-control:focus,
#<?= $fid ?>.form-material .form-control.focus{
	background:#21262d!important;background-image:none!important;border:1px solid #30363d!important;
	border-radius:8px!important;box-shadow:none!important;color:#e6edf3!important;
	padding:9px 12px!important;min-height:40px;
}
#<?= $fid ?>.form-material textarea.form-control{min-height:88px!important;resize:vertical;}
#<?= $fid ?>.form-material .form-control:focus{
	border-color:#00c08b!important;box-shadow:0 0 0 2px rgba(0,192,139,.22)!important;
}
#<?= $fid ?>.form-material input.form-control[readonly]{
	opacity:1!important;cursor:default;background:#1c2128!important;color:#c9d1d9!important;
}
#<?= $fid ?>.form-material .form-control:disabled{
	opacity:.65!important;cursor:not-allowed!important;background:#1a1f26!important;color:#8b949e!important;
}
#<?= $fid ?> .form-group > label{color:#8b949e;font-size:.75rem;font-weight:600;margin-bottom:6px;}
#<?= $fid ?> input[type=checkbox]{width:auto!important;min-height:auto!important;margin-top:6px;}
#<?= $fid ?> .checkbox label,
#<?= $fid ?> .form-check label{text-transform:none;color:#c9d1d9;font-size:.85rem;}
#<?= $fid ?> .bootstrap-select.form-control{
	background:transparent!important;border:none!important;padding:0!important;box-shadow:none!important;
}
#<?= $fid ?> .bootstrap-select > .dropdown-toggle{
	background:#21262d!important;border:1px solid #30363d!important;color:#e6edf3!important;
	border-radius:8px!important;padding:9px 12px!important;min-height:40px;
}
#<?= $fid ?> .bootstrap-select.open > .dropdown-toggle,
#<?= $fid ?> .bootstrap-select > .dropdown-toggle:focus{
	border-color:#00c08b!important;box-shadow:0 0 0 2px rgba(0,192,139,.22)!important;
}
.clictr-actions{margin-top:20px;padding-top:16px;border-top:1px solid #21262d;}
.clictr-page-title{font-size:1.15rem;font-weight:700;color:#e6edf3;margin:0 0 6px;}
.clictr-page-lead{color:#8b949e;font-size:.85rem;margin-bottom:18px;line-height:1.45;}
.clictr-card .table{color:#c9d1d9;margin-bottom:16px;}
.clictr-card .table thead th{border-color:#30363d;color:#8b949e;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;border-bottom-width:1px;}
.clictr-card .table td{border-color:#21262d;vertical-align:middle;}
.clictr-card .table tbody tr:hover{background:rgba(110,118,129,.08);}
.clictr-card .table .text-muted{color:#6e7681;}
#<?= $fid ?> .custom-control-label{color:#c9d1d9;text-transform:none;font-size:.85rem;}
.clictr-edit-page .btn-success.btn-disabled{background:#30363d!important;color:#6e7681!important;cursor:not-allowed!important;}
.clictr-modal-cmp .form-group > label.control-label{color:#8b949e;font-size:.75rem;}
.clictr-modal-cmp input.form-control,.clictr-modal-cmp textarea.form-control{
	background:#21262d!important;border:1px solid #30363d!important;color:#e6edf3!important;border-radius:8px!important;padding:9px 12px!important;
}
.clictr-modal-cmp input.form-control:focus,.clictr-modal-cmp textarea.form-control:focus{
	border-color:#00c08b!important;box-shadow:0 0 0 2px rgba(0,192,139,.22)!important;
}
body.pgm-theme-light #<?= $fid ?>.form-material .form-control,
body.pgm-theme-light #<?= $fid ?>.form-material .form-control:focus,
body.layout-no-topbar.pgm-portal-client #<?= $fid ?>.form-material .form-control,
body.layout-no-topbar.pgm-portal-client #<?= $fid ?>.form-material .form-control:focus{
	background:#21262d!important;border-color:#30363d!important;color:#e6edf3!important;
}
body.pgm-theme-light #<?= $fid ?> .bootstrap-select > .dropdown-toggle,
body.layout-no-topbar.pgm-portal-client #<?= $fid ?> .bootstrap-select > .dropdown-toggle{
	background:#21262d!important;border-color:#30363d!important;color:#e6edf3!important;
}
</style>
