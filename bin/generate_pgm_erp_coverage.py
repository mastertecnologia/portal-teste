#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Gera documentação de cobertura do mock pgm_erp_completo.html.

Uso:
  python3 bin/generate_pgm_erp_coverage.py
  python3 bin/generate_pgm_erp_coverage.py --check  # exit 1 se houver lacunas críticas
"""
from __future__ import annotations

import json
import re
import sys
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
REGISTRY = ROOT / "config" / "pgm_erp_screens.json"
HTML_CANDIDATES = [
    ROOT / "docs/referencias/pgm_erp_completo.html",
    ROOT / "docs/reference/pgm_erp_completo.html",
    ROOT / "docs/referencias/pgm_erp_completo_2.html",
    ROOT / "docs/reference/pgm_erp_completo_2.html",
]
OUT_COBERTURA = ROOT / "docs" / "PGM_ERP_COBERTURA_TELAS.md"
OUT_INTEGRACOES = ROOT / "docs" / "PGM_ERP_INTEGRACOES_GRID.md"
OUT_JSON_REPORT = ROOT / "docs" / "generated" / "pgm_erp_coverage_report.json"


def _pick_html_path() -> Path:
    """Escolhe o HTML com mais telas pg-* (referência atualizada)."""
    best: Path | None = None
    best_count = -1
    for path in HTML_CANDIDATES:
        if not path.is_file():
            continue
        text = path.read_text(encoding="utf-8", errors="replace")
        count = len(set(re.findall(r'\bid=["\']?(pg-[a-z0-9-]+)["\']?', text, re.I)))
        if count > best_count:
            best_count = count
            best = path
    if best is None:
        raise SystemExit("HTML de referência não encontrado em docs/reference ou docs/referencias")
    return best


def load_html_ids() -> tuple[list[str], Path]:
    path = _pick_html_path()
    text = path.read_text(encoding="utf-8", errors="replace")
    ids = sorted(set(re.findall(r'\bid=["\']?(pg-[a-z0-9-]+)["\']?', text, re.I)))

    return ids, path


def load_registry() -> dict:
    if not REGISTRY.is_file():
        raise SystemExit(f"Registry ausente: {REGISTRY}")
    return json.loads(REGISTRY.read_text(encoding="utf-8"))


def status_icon(status: str) -> str:
    return {
        "implemented": "OK",
        "bridge": "BRIDGE",
        "placeholder": "PLACEHOLDER",
        "planned": "PLANNED",
    }.get(status, status.upper())


def render_cobertura(html_path: Path, html_ids: list[str], reg: dict) -> str:
    screens = {s["id"]: s for s in reg.get("screens", [])}
    missing_in_reg = [i for i in html_ids if i not in screens]
    extra_in_reg = [i for i in screens if i not in html_ids]
    by_module: dict[str, list] = {}
    for sid, s in screens.items():
        mod = s.get("module", "outros")
        by_module.setdefault(mod, []).append(s)

    counts = {"implemented": 0, "bridge": 0, "placeholder": 0, "planned": 0}
    for s in screens.values():
        st = s.get("status", "planned")
        counts[st] = counts.get(st, 0) + 1

    lines = [
        "# Cobertura de telas — PGM ERP Completo",
        "",
        f"Gerado em: {datetime.now(timezone.utc).strftime('%Y-%m-%d %H:%M UTC')}",
        f"Referência HTML: `{html_path.relative_to(ROOT).as_posix()}`",
        f"Registry: `{REGISTRY.relative_to(ROOT).as_posix()}`",
        "",
        "## Resumo",
        "",
        f"| Métrica | Valor |",
        f"|---------|------:|",
        f"| Telas `pg-*` no HTML | {len(html_ids)} |",
        f"| Entradas no registry | {len(screens)} |",
        f"| Implementadas (premium) | {counts.get('implemented', 0)} |",
        f"| Bridge (legado/API intacta) | {counts.get('bridge', 0)} |",
        f"| Placeholder / roadmap | {counts.get('placeholder', 0)} |",
        f"| Planejadas | {counts.get('planned', 0)} |",
        "",
    ]
    if missing_in_reg:
        lines += ["## Telas no HTML sem registry", ""]
        for i in missing_in_reg:
            lines.append(f"- `{i}`")
        lines.append("")
    if extra_in_reg:
        lines += ["## Registry sem `pg-*` no HTML (revisar)", ""]
        for i in extra_in_reg:
            lines.append(f"- `{i}`")
        lines.append("")

    lines += [
        "## Matriz por módulo",
        "",
        "| Módulo | Tela | Status | Rota protótipo | Tabelas | Grid ERP |",
        "|--------|------|--------|----------------|---------|----------|",
    ]
    for mod in sorted(by_module.keys()):
        for s in sorted(by_module[mod], key=lambda x: x["id"]):
            route = s.get("route", "—")
            tables = ", ".join(s.get("tables", [])) or "—"
            grid = s.get("grid_erp", "—")
            lines.append(
                f"| {mod} | `{s['id']}` | {status_icon(s.get('status', ''))} | `{route}` | {tables} | {grid} |"
            )
    lines.append("")
    lines.append("## Comandos")
    lines.append("")
    lines.append("```bash")
    lines.append("python3 bin/generate_pgm_erp_coverage.py")
    lines.append("php bin/audit_pgm_erp_mock.php   # requer PHP no PATH")
    lines.append("bash bin/homologacao_pgm_erp.sh")
    lines.append("```")
    lines.append("")
    return "\n".join(lines)


def render_integracoes(reg: dict) -> str:
    endpoints = reg.get("grid_api_endpoints", [])
    screen_links = [s for s in reg.get("screens", []) if s.get("grid_erp") not in (None, "", "—", "none")]

    lines = [
        "# Integrações Grid ERP — mapa por tela e endpoint",
        "",
        f"Gerado em: {datetime.now(timezone.utc).strftime('%Y-%m-%d %H:%M UTC')}",
        "",
        "> **Regra:** contratos de API existentes (`listAPI`, `addAPI`, `refreshAPI`, SOAP `.wso`) **não são alterados**.",
        "> Telas premium consomem os mesmos dados via ORM ou delegam ao controller legado (bridge).",
        "",
        "## Endpoints HTTP ERP → Portal (token em header)",
        "",
        "| Endpoint | Controller | Direção | Tabelas principais |",
        "|----------|------------|---------|-------------------|",
    ]
    for ep in endpoints:
        lines.append(
            f"| `{ep.get('path', '')}` | `{ep.get('controller', '')}::{ep.get('action', '')}` "
            f"| {ep.get('direction', '')} | {ep.get('tables', '')} |"
        )
    lines.append("")
    lines.append("## SOAP / WebGrid (urlerp em `empresas`)")
    lines.append("")
    for note in reg.get("grid_soap_notes", []):
        lines.append(f"- {note}")
    lines.append("")
    lines.append("## Telas com vínculo Grid/ERP")
    lines.append("")
    lines.append("| Tela | Módulo | Grid | Observação |")
    lines.append("|------|--------|------|------------|")
    for s in sorted(screen_links, key=lambda x: x["id"]):
        lines.append(
            f"| `{s['id']}` | {s.get('module', '')} | {s.get('grid_erp', '')} | {s.get('grid_note', '')} |"
        )
    lines.append("")
    return "\n".join(lines)


def main() -> int:
    check = "--check" in sys.argv
    html_ids, html_path = load_html_ids()
    reg = load_registry()
    reg_ids = {s["id"] for s in reg.get("screens", [])}

    OUT_COBERTURA.parent.mkdir(parents=True, exist_ok=True)
    OUT_JSON_REPORT.parent.mkdir(parents=True, exist_ok=True)

    cobertura = render_cobertura(html_path, html_ids, reg)
    OUT_COBERTURA.write_text(cobertura, encoding="utf-8")

    integracoes = render_integracoes(reg)
    OUT_INTEGRACOES.write_text(integracoes, encoding="utf-8")

    gaps = [i for i in html_ids if i not in reg_ids]
    placeholders = [
        s["id"]
        for s in reg.get("screens", [])
        if s.get("status") == "placeholder" and s["id"] in html_ids
    ]
    report = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "html_path": str(html_path),
        "html_screen_count": len(html_ids),
        "registry_count": len(reg_ids),
        "missing_in_registry": gaps,
        "placeholder_screens": placeholders,
        "status_counts": {},
    }
    for s in reg.get("screens", []):
        st = s.get("status", "planned")
        report["status_counts"][st] = report["status_counts"].get(st, 0) + 1
    OUT_JSON_REPORT.write_text(json.dumps(report, indent=2, ensure_ascii=False), encoding="utf-8")

    print(f"Wrote {OUT_COBERTURA}")
    print(f"Wrote {OUT_INTEGRACOES}")
    print(f"Wrote {OUT_JSON_REPORT}")
    if gaps:
        print(f"AVISO: {len(gaps)} telas no HTML sem registry")
    print(f"Placeholders: {len(placeholders)}")

    if check and (gaps or len(placeholders) > 40):
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
