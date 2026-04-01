<?php
/**
 * Fase 2 — tokens visuais do módulo cliente (dark, foco, loading).
 * Incluir após estilos base da ficha (ex.: edit.ctp).
 */
?>
<style>
/* Labels e campos (complementa #form-edit-cliente) */
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
/* Textareas de e-mail na ficha (readonly) — mesmo contraste dos inputs */
#form-edit-cliente textarea.form-control.cli-cmp-input[readonly]{min-height:52px;resize:none;}
/* Modais de edição de e-mail (fora do form) */
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
/* Overlay salvando */
.cli-ficha-loading{position:fixed;inset:0;z-index:2000;background:rgba(13,17,23,.72);display:flex;align-items:center;justify-content:center;backdrop-filter:blur(2px);}
.cli-ficha-loading__box{display:flex;align-items:center;gap:14px;padding:18px 22px;border-radius:12px;background:#161b22;border:1px solid #30363d;color:#e6edf3;font-size:.88rem;font-weight:600;}
.cli-ficha-loading__spin{width:22px;height:22px;border:2px solid #30363d;border-top-color:#00c08b;border-radius:50%;animation:cliSpin .7s linear infinite;}
@keyframes cliSpin{to{transform:rotate(360deg);}}
</style>
