<?php
/**
 * Tokens visuais da ficha cliente (campos, modais, loading).
 * Tema escuro: padrão. Com body.pgm-theme-light, campos seguem área clara (Sprint B).
 */
?>
<style>
/* Labels e campos (complementa #form-edit-cliente) — base escura */
.cli-cmp-label{font-size:.72rem;font-weight:600;color:#8b949e;margin-bottom:4px;display:block;letter-spacing:.04em;text-transform:uppercase;}
.cli-cmp-label--row{text-transform:none;letter-spacing:.02em;font-size:.78rem;color:#c9d1d9;}
.cli-cmp-help{font-size:.72rem;line-height:1.45;}
.cli-cmp-field{margin-bottom:12px;}
#form-edit-cliente input.form-control.cli-cmp-input,#form-edit-cliente textarea.form-control.cli-cmp-input,
#form-edit-cliente select.cli-cmp-select{background:#21262d;border-color:#30363d;color:#e6edf3;}
#form-edit-cliente input.form-control.cli-cmp-input:focus,#form-edit-cliente textarea.form-control.cli-cmp-input:focus,
#form-edit-cliente select.cli-cmp-select:focus{border-color:#00c08b;box-shadow:0 0 0 2px rgba(0,192,139,.22);}
#form-edit-cliente input.form-control.cli-cmp-input[readonly],#form-edit-cliente textarea.form-control.cli-cmp-input[readonly]{
	background:#21262d!important;border-color:#30363d!important;color:#e6edf3!important;opacity:1!important;cursor:default;
}
#form-edit-cliente textarea.form-control.cli-cmp-input[readonly]{min-height:52px;resize:none;}
.cli-modal-cmp .cli-cmp-label{margin-bottom:8px;}
.cli-modal-cmp textarea.form-control.cli-cmp-input,.cli-modal-cmp input.form-control.cli-cmp-input{
	background:#21262d;border-color:#30363d;color:#e6edf3;
}
.cli-modal-cmp textarea.form-control.cli-cmp-input:focus,.cli-modal-cmp input.form-control.cli-cmp-input:focus{
	border-color:#00c08b;box-shadow:0 0 0 2px rgba(0,192,139,.22);
}
.cli-cmp-card{transition:border-color .2s,box-shadow .2s;}
.cli-cmp-card:focus-within{border-color:#30363d;box-shadow:0 0 0 1px rgba(0,192,139,.08);}
.cli-cmp-btn:focus{box-shadow:0 0 0 2px rgba(0,192,139,.35);}
.cli-ficha-loading{position:fixed;inset:0;z-index:2000;background:rgba(13,17,23,.72);display:flex;align-items:center;justify-content:center;backdrop-filter:blur(2px);}
.cli-ficha-loading__box{display:flex;align-items:center;gap:14px;padding:18px 22px;border-radius:12px;background:#161b22;border:1px solid #30363d;color:#e6edf3;font-size:.88rem;font-weight:600;}
.cli-ficha-loading__spin{width:22px;height:22px;border:2px solid #30363d;border-top-color:#00c08b;border-radius:50%;animation:cliSpin .7s linear infinite;}
@keyframes cliSpin{to{transform:rotate(360deg);}}

/* Tema claro interno: alinhado a pgm-theme-light.css (inputs claros, texto escuro) */
body.pgm-theme-light #form-edit-cliente input.form-control,
body.pgm-theme-light #form-edit-cliente textarea.form-control,
body.pgm-theme-light #form-edit-cliente select.form-control{
	background:#ffffff!important;border-color:#d0d7de!important;color:#1a1f2e!important;border-radius:8px!important;
}
body.pgm-theme-light #form-edit-cliente input.form-control:focus,
body.pgm-theme-light #form-edit-cliente textarea.form-control:focus,
body.pgm-theme-light #form-edit-cliente select.form-control:focus{
	border-color:#00a876!important;box-shadow:0 0 0 3px rgba(0,168,118,.2)!important;
}
body.pgm-theme-light #form-edit-cliente input.form-control[readonly],
body.pgm-theme-light #form-edit-cliente textarea.form-control[readonly]{
	background:#f8f9fb!important;border-color:#d0d7de!important;color:#1a1f2e!important;cursor:default!important;
}
body.pgm-theme-light #form-edit-cliente .bootstrap-select > .dropdown-toggle{
	background:#ffffff!important;border-color:#d0d7de!important;color:#1a1f2e!important;
}
body.pgm-theme-light #form-edit-cliente .bootstrap-select > .dropdown-toggle:focus,
body.pgm-theme-light #form-edit-cliente .bootstrap-select.open > .dropdown-toggle{
	border-color:#00a876!important;box-shadow:0 0 0 3px rgba(0,168,118,.2)!important;
}
body.pgm-theme-light .cli-modal-cmp textarea.form-control,
body.pgm-theme-light .cli-modal-cmp input.form-control{
	background:#ffffff!important;border-color:#d0d7de!important;color:#1a1f2e!important;
}
body.pgm-theme-light .cli-modal-cmp textarea.form-control:focus,
body.pgm-theme-light .cli-modal-cmp input.form-control:focus{
	border-color:#00a876!important;box-shadow:0 0 0 3px rgba(0,168,118,.2)!important;
}
body.pgm-theme-light .cli-cmp-label,
body.pgm-theme-light .cli-cmp-label--row span{color:#6b7280!important;}
body.pgm-theme-light .cli-cmp-label--row{color:#374151!important;}

/*
 * Portal cliente (sem tema claro): reforço escuro — não combinar com .pgm-theme-light (regra acima).
 */
body.layout-no-topbar.pgm-portal-client:not(.pgm-theme-light) #form-edit-cliente input.form-control,
body.layout-no-topbar.pgm-portal-client:not(.pgm-theme-light) #form-edit-cliente textarea.form-control,
body.layout-no-topbar.pgm-portal-client:not(.pgm-theme-light) #form-edit-cliente select.form-control{
	background:#21262d!important;border-color:#30363d!important;color:#e6edf3!important;border-radius:8px!important;
}
body.layout-no-topbar.pgm-portal-client:not(.pgm-theme-light) #form-edit-cliente input.form-control:focus,
body.layout-no-topbar.pgm-portal-client:not(.pgm-theme-light) #form-edit-cliente textarea.form-control:focus,
body.layout-no-topbar.pgm-portal-client:not(.pgm-theme-light) #form-edit-cliente select.form-control:focus{
	border-color:#00c08b!important;box-shadow:0 0 0 2px rgba(0,192,139,.22)!important;
}
body.layout-no-topbar.pgm-portal-client:not(.pgm-theme-light) #form-edit-cliente input.form-control[readonly],
body.layout-no-topbar.pgm-portal-client:not(.pgm-theme-light) #form-edit-cliente textarea.form-control[readonly]{
	background:#21262d!important;border-color:#30363d!important;color:#e6edf3!important;cursor:default!important;
}
body.layout-no-topbar.pgm-portal-client:not(.pgm-theme-light) #form-edit-cliente .bootstrap-select > .dropdown-toggle{
	background:#21262d!important;border-color:#30363d!important;color:#e6edf3!important;
}
body.layout-no-topbar.pgm-portal-client:not(.pgm-theme-light) #form-edit-cliente .bootstrap-select > .dropdown-toggle:focus,
body.layout-no-topbar.pgm-portal-client:not(.pgm-theme-light) #form-edit-cliente .bootstrap-select.open > .dropdown-toggle{
	border-color:#00c08b!important;box-shadow:0 0 0 2px rgba(0,192,139,.22)!important;
}
body.layout-no-topbar.pgm-portal-client:not(.pgm-theme-light) .cli-modal-cmp textarea.form-control,
body.layout-no-topbar.pgm-portal-client:not(.pgm-theme-light) .cli-modal-cmp input.form-control{
	background:#21262d!important;border-color:#30363d!important;color:#e6edf3!important;
}
body.layout-no-topbar.pgm-portal-client:not(.pgm-theme-light) .cli-modal-cmp textarea.form-control:focus,
body.layout-no-topbar.pgm-portal-client:not(.pgm-theme-light) .cli-modal-cmp input.form-control:focus{
	border-color:#00c08b!important;box-shadow:0 0 0 2px rgba(0,192,139,.22)!important;
}
body.layout-no-topbar.pgm-portal-client:not(.pgm-theme-light) .cli-cmp-label,
body.layout-no-topbar.pgm-portal-client:not(.pgm-theme-light) .cli-cmp-label--row span{color:#8b949e!important;}
body.layout-no-topbar.pgm-portal-client:not(.pgm-theme-light) .cli-cmp-label--row{color:#c9d1d9!important;}
</style>
