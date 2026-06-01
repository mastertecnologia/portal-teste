#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Inclui telas pg-* do HTML de referência que ainda não estão em config/pgm_erp_screens.json.

Uso:
  python3 bin/sync_pgm_erp_registry_from_html.py
  python3 bin/sync_pgm_erp_registry_from_html.py --dry-run
"""
from __future__ import annotations

import argparse
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
REGISTRY = ROOT / "config" / "pgm_erp_screens.json"
HTML_PATH = ROOT / "docs" / "referencias" / "pgm_erp_completo.html"

# Mapeamento pg-id → metadados (derivado do mock; sem inventar integrações Grid).
SCREEN_META: dict[str, dict] = {
    # Bancos (extensão)
    "pg-bancos-cadastro": {
        "module": "bancos",
        "route": "/bancos-prototype/bancos-cadastro",
        "tables": ["financeiro_bancos"],
        "grid_erp": "none",
    },
    "pg-banco-novo": {
        "module": "bancos",
        "route": "/bancos-prototype/banco-novo",
        "tables": ["financeiro_bancos"],
        "grid_erp": "none",
        "bridge": "FinanceiroBancos/add",
    },
    "pg-banco-openbanking": {
        "module": "bancos",
        "route": "/bancos-prototype/banco-openbanking",
        "tables": ["financeiro_bancos", "financeiro_extrato_bancario"],
        "grid_erp": "none",
    },
    # Produtos
    "pg-preco-tabela-nova": {
        "module": "produtos",
        "route": "/produtos-prototype/preco-tabela-nova",
        "tables": ["produtos"],
        "grid_erp": "both",
        "grid_note": "addAPI/listAPI + SOAP GetEstoqueProdutos",
    },
    # Painel administrativo (somente admin)
    "pg-config-integracoes": {
        "module": "sistema",
        "route": "/sistema-prototype/config-integracoes",
        "tables": ["empresas", "config"],
        "grid_erp": "both",
        "grid_note": "urlerp + listAPI/addAPI documentados em PGM_ERP_INTEGRACOES_GRID.md",
    },
    "pg-config-email": {
        "module": "sistema",
        "route": "/sistema-prototype/config-email",
        "tables": ["config"],
        "grid_erp": "none",
        "bridge": "Config/emailsuporte",
    },
    "pg-config-seguranca": {
        "module": "sistema",
        "route": "/sistema-prototype/config-seguranca",
        "tables": ["users", "rbac_roles"],
        "grid_erp": "none",
        "bridge": "Permissoes/adminIndex",
    },
    "pg-config-backup": {
        "module": "sistema",
        "route": "/sistema-prototype/config-backup",
        "tables": ["config"],
        "grid_erp": "none",
    },
    "pg-config-numeracao": {
        "module": "sistema",
        "route": "/sistema-prototype/config-numeracao",
        "tables": ["config"],
        "grid_erp": "none",
    },
    "pg-config-notificacoes": {
        "module": "sistema",
        "route": "/sistema-prototype/config-notificacoes",
        "tables": ["config"],
        "grid_erp": "none",
    },
    "pg-config-localizacao": {
        "module": "sistema",
        "route": "/sistema-prototype/config-localizacao",
        "tables": ["config", "empresas"],
        "grid_erp": "none",
    },
    "pg-email-template-editar": {
        "module": "sistema",
        "route": "/sistema-prototype/email-template-editar",
        "tables": ["config"],
        "grid_erp": "none",
        "bridge": "Config/emailsuporte",
    },
}

# Licenciamento — rotas /licencas-prototype/*
LIC_PAGES: list[tuple[str, str, list[str]]] = [
    ("dashboard", "Painel", ["lic_licencas", "lic_dispositivos", "clientes"]),
    ("empresas", "Empresas-cliente", ["clientes", "lic_licencas"]),
    ("empresa-detalhe", "Empresa-cliente", ["clientes", "lic_licencas"]),
    ("empresa-nova", "Nova empresa-cliente", ["clientes"]),
    ("licencas", "Licenças", ["lic_licencas", "lic_catalogo_produtos", "clientes"]),
    ("licenca-detalhe", "Detalhe licença", ["lic_licencas", "lic_assentos"]),
    ("licenca-versoes", "Versões licença", ["lic_licencas"]),
    ("nova", "Nova licença (passo 1)", ["lic_licencas"]),
    ("nova-2", "Nova licença (passo 2)", ["lic_licencas"]),
    ("nova-3", "Nova licença (passo 3)", ["lic_licencas", "lic_assentos"]),
    ("nova-4", "Nova licença (passo 4)", ["lic_licencas", "lic_cofre_itens"]),
    ("catalogo", "Catálogo", ["lic_catalogo_produtos", "lic_categorias"]),
    ("categorias", "Categorias", ["lic_categorias"]),
    ("categoria-editar", "Editar categoria", ["lic_categorias"]),
    ("produto-novo", "Novo produto catálogo", ["lic_catalogo_produtos"]),
    ("produto-editar", "Editar produto catálogo", ["lic_catalogo_produtos"]),
    ("produto-detalhe", "Detalhe produto catálogo", ["lic_catalogo_produtos"]),
    ("fornecedores", "Fornecedores software", ["clientes", "lic_catalogo_produtos"]),
    ("fornecedor-novo", "Novo fornecedor", ["clientes"]),
    ("renovacoes", "Renovações", ["lic_licencas"]),
    ("calendario", "Calendário vencimentos", ["lic_licencas"]),
    ("dispositivos", "Dispositivos", ["lic_dispositivos", "clientes"]),
    ("dispositivo-novo", "Novo dispositivo", ["lic_dispositivos"]),
    ("dispositivo-detalhe", "Detalhe dispositivo", ["lic_dispositivos"]),
    ("cofre", "Cofre credenciais", ["lic_cofre_itens"]),
    ("cofre-item", "Item cofre", ["lic_cofre_itens"]),
    ("cofre-novo", "Nova credencial", ["lic_cofre_itens"]),
    ("cofre-editar", "Editar credencial", ["lic_cofre_itens"]),
    ("auditoria", "Auditoria módulo", ["lic_auditoria_eventos", "audit_logs"]),
    ("inteligencia", "Inteligência", ["lic_licencas"]),
    ("relatorios", "Relatórios", ["lic_licencas"]),
    ("config", "Configurações módulo", ["lic_licencas"]),
    ("solicitacoes", "Solicitações clientes", ["lic_solicitacoes"]),
    ("portal-dash", "Portal cliente · painel", ["lic_licencas", "lic_solicitacoes"]),
    ("portal-licencas", "Portal · licenças", ["lic_licencas"]),
    ("portal-cofre", "Portal · cofre", ["lic_cofre_itens"]),
    ("portal-financeiro", "Portal · financeiro", ["lic_licencas", "faturas"]),
    ("portal-solicitar", "Portal · solicitar", ["lic_solicitacoes"]),
    ("portal-solicitacao-acompanhar", "Portal · acompanhar", ["lic_solicitacoes"]),
]

for slug, _title, tables in LIC_PAGES:
    pg_id = f"pg-lic-{slug}"
    SCREEN_META[pg_id] = {
        "module": "licencas",
        "route": f"/licencas-prototype/{slug}",
        "tables": tables,
        "grid_erp": "none",
        "grid_note": "Fornecedores vinculados a clientes PJ (cadastro ERP); sem API Grid dedicada no mock.",
    }


def load_html_ids() -> list[str]:
    text = HTML_PATH.read_text(encoding="utf-8", errors="replace")
    return sorted(set(re.findall(r'\bid=["\']?(pg-[a-z0-9-]+)["\']?', text, re.I)))


def default_meta(pg_id: str) -> dict:
    if pg_id in SCREEN_META:
        return SCREEN_META[pg_id]
    if pg_id.startswith("pg-lic-"):
        slug = pg_id[7:]
        return {
            "module": "licencas",
            "route": f"/licencas-prototype/{slug}",
            "tables": ["lic_licencas"],
            "grid_erp": "none",
        }
    if pg_id.startswith("pg-config-"):
        slug = pg_id[10:]
        return {
            "module": "sistema",
            "route": f"/sistema-prototype/config-{slug}",
            "tables": ["config", "empresas"],
            "grid_erp": "none",
        }
    return {
        "module": "outros",
        "route": f"/erp-home-prototype?pg={pg_id}",
        "tables": [],
        "grid_erp": "none",
    }


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--dry-run", action="store_true")
    args = parser.parse_args()

    if not HTML_PATH.is_file():
        raise SystemExit(f"HTML não encontrado: {HTML_PATH}")

    reg = json.loads(REGISTRY.read_text(encoding="utf-8"))
    screens = reg.get("screens", [])
    by_id = {s["id"]: s for s in screens if s.get("id")}
    html_ids = load_html_ids()
    added = []

    for pg_id in html_ids:
        if pg_id in by_id:
            continue
        meta = default_meta(pg_id)
        entry = {
            "id": pg_id,
            "module": meta["module"],
            "status": "planned",
            "route": meta["route"],
            "tables": meta.get("tables", []),
            "grid_erp": meta.get("grid_erp", "none"),
            "grid_note": meta.get("grid_note", ""),
        }
        if meta.get("bridge"):
            entry["legacy_bridge"] = meta["bridge"]
        screens.append(entry)
        added.append(pg_id)

    screens.sort(key=lambda x: x["id"])
    reg["screens"] = screens
    reg["reference_html"] = "docs/referencias/pgm_erp_completo.html"
    reg["version"] = int(reg.get("version", 1)) + (1 if added else 0)

    print(f"HTML: {len(html_ids)} telas | Registry antes: {len(by_id)} | Novas: {len(added)}")
    for pg_id in added:
        print(f"  + {pg_id}")

    if args.dry_run:
        return 0

    REGISTRY.write_text(json.dumps(reg, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"Atualizado {REGISTRY}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
