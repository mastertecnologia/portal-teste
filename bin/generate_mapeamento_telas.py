# -*- coding: utf-8 -*-
"""Gera webroot/mapeamento-telas-projeto.html. Uso: python bin/generate_mapeamento_telas.py"""
from __future__ import annotations

import html
import re
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / "webroot" / "mapeamento-telas-projeto.html"

SKIP_METHODS = frozenset(
    {"initialize", "beforeFilter", "afterFilter", "beforeRender", "isAuthorized"}
)

CTRL_PURPOSE: dict[str, str] = {
    "Users": "Autenticação, perfis, dashboard, cadastro de usuários e preferências de UI.",
    "App": "Controller base: carregamento comum, autorização genérica, resposta JSON.",
    "Base": "Base para controllers que seguem o fluxo “base” do projeto.",
    "Error": "Páginas de erro HTTP (404, 500, etc.).",
    "Pages": "Páginas estáticas genéricas via roteamento display().",
    "Clientes": "CRUD e operações de clientes, solicitantes, integrações e APIs de cliente.",
    "Tickets": "Service desk: tickets, painéis, fluxos de atendimento e chamados.",
    "Orcamentos": "Propostas comerciais: edição, carrinho, aprovação, PDF e envio.",
    "Ordensservico": "Ordens de serviço: cadastro, horas, impressão, integração com ERP.",
    "Faturamento": "Fechamento/faturamento de OS e gestão de lotes de faturamento.",
    "Faturas": "Locação/faturas: carrinho, aprovação, recibo e ciclo de cobrança.",
    "Financeiro": "Contas a pagar/receber, conciliação, DRE e operações financeiras.",
    "FinanceiroBancos": "Contas bancárias, remessa/retorno CNAB e relatórios bancários.",
    "FinanceiroConfig": "Plano de contas e centros de custo (configuração financeira).",
    "FinanceiroRelatorios": "Relatórios financeiros: aging, inadimplência, centro de custo.",
    "Fiscal": "Módulo fiscal: visão geral e operações de notas/documentos fiscais.",
    "FiscalNotas": "NF-e saída: emissão, consulta, cancelamento e fluxo de notas.",
    "FiscalNotasEntrada": "Notas fiscais de entrada (compras/importação XML).",
    "FiscalConfig": "Tabelas fiscais: CFOP, NCM, alíquotas, naturezas de operação.",
    "FiscalRelatorios": "Livros fiscais, SPED, resumos e exportações fiscais.",
    "FiscalCertificados": "Certificados digitais A1 para emissão fiscal.",
    "Produtos": "Cadastro de produtos/serviços, estoque, precificação e APIs de produto.",
    "Empresas": "Multi-empresa: cadastro, logotipo, migração e senha da empresa.",
    "Empresasusers": "Vínculo usuário–empresa e permissões por empresa.",
    "Config": "Configurações gerais (pastas, acessos, e-mail suporte).",
    "Permissoes": "Administração ABAC/RBAC: papéis, políticas, matriz e auditoria.",
    "Visitas": "Agenda de visitas/compromissos ligados a tickets e calendário.",
    "Prefaturamento": "Conferência e pré-faturamento antes do fechamento.",
    "Relatorios": "Relatórios operacionais internos.",
    "PortalRelatorios": "Relatórios para visão portal / exportação temporal.",
    "AdvancedReports": "Relatórios avançados agregados e exportação.",
    "ContractManagement": "Gestão de contratos com assinatura eletrônica.",
    "ContractTemplates": "Modelos de contrato reutilizáveis e preview.",
    "PortalContratos": "Área do cliente: contratos, PDFs, renovação e franquia.",
    "PortalAdvancedContracts": "Contratos avançados no portal (franquia, índice).",
    "AdvancedContracts": "Contratos avançados (visão interna).",
    "PortalAdvancedInvoices": "Faturas avançadas no portal do cliente.",
    "AdvancedInvoices": "Faturas avançadas (visão interna).",
    "PortalAdvancedAttendance": "Atendimento avançado no portal.",
    "PortalNotifications": "Preferências e centro de notificações do portal.",
    "Clicontratos": "Contratos do cliente (visão interna vinculada a cliente).",
    "Cliacessos": "Acessos de cliente ao portal e troca de senha.",
    "ContratosHoras": "Contratos por banco de horas.",
    "Cadastro": "Cadastro público ou fluxo inicial de empresa (CNPJ).",
    "Pesquisa": "Busca global e resolução de links no portal.",
    "Servicedesk": "Login/branding do módulo service desk.",
    "Queues": "Administração de filas (jobs).",
    "Remessas": "Remessas bancárias (fluxo complementar).",
    "Retornos": "Processamento de arquivo de retorno bancário.",
    "PgmAssets": "Servir CSS legado/premium compilado (assets PGM).",
    "Notificacoes": "Listagem/configuração de notificações in-app.",
    "Normasempresa": "Normas e documentos da empresa (ex.: acesso remoto).",
    "Areas": "Cadastro de áreas/departamentos.",
    "Problemas": "Catálogo de tipos de problema (service desk).",
    "Feriados": "Cadastro de feriados para agenda.",
    "Atividades": "Atividades vinculadas ao fluxo de tickets/OS.",
    "Bancosenhas": "Senhas de certificados/bancos para usuários autorizados.",
    "Ordemhoras": "Lançamento de horas em ordem de serviço.",
    "Ordemparcelas": "Parcelas financeiras ligadas à ordem de serviço.",
    "Ticketshoras": "Horas lançadas no ticket.",
    "Ticketsusers": "Participantes/resolução de ticket.",
    "Ticketsanexos": "Upload e gestão de anexos de ticket.",
    "Ticketcomentarios": "Comentários internos/externos em ticket.",
    "Api": "API REST (namespace Api/V1): endpoints JSON para integrações.",
}


def extract_public_methods(php: str) -> list[str]:
    found = re.findall(r"public\s+function\s+(\w+)\s*\(", php)
    out: list[str] = []
    seen: set[str] = set()
    for name in found:
        if name in SKIP_METHODS or name in seen:
            continue
        seen.add(name)
        out.append(name)
    return out


def controller_base(filename: str) -> str:
    return filename.replace("Controller.php", "")


def human_controller_purpose(base: str) -> str:
    return CTRL_PURPOSE.get(
        base,
        "Camada HTTP: orquestra requisições, valida permissões e delega a modelos/serviços do domínio.",
    )


def template_purpose(rel: str) -> str:
    lower = rel.replace("\\", "/").lower()
    if lower.startswith("layout/"):
        return "Layout (estrutura HTML comum: cabeçalho, menus, scripts da página)."
    if lower.startswith("element/"):
        return "Elemento reutilizável incluído em outras telas (parcial)."
    if lower.startswith("email/"):
        return "Corpo de e-mail (HTML ou texto) enviado pelo sistema."
    if lower.startswith("error/"):
        return "Template de página de erro."
    m = re.search(r"/([^/]+)/([^/]+)\.ctp$", rel.replace("\\", "/"))
    if m:
        ctrl, action = m.group(1), m.group(2).lower()
        suffix = {
            "index": "Listagem principal do recurso.",
            "add": "Formulário de inclusão.",
            "edit": "Formulário de edição.",
            "view": "Detalhe/visualização somente leitura.",
            "login": "Tela de autenticação.",
            "imprimir": "Versão para impressão ou PDF da entidade.",
            "pdf": "Saída em PDF.",
            "email": "Composição ou pré-visualização de e-mail.",
        }
        if action in suffix:
            return f"{suffix[action]} ({ctrl})."
        return f"Tela da ação «{m.group(2)}» do módulo {ctrl}."
    return "Template de visualização CakePHP."


def main() -> None:
    ctrl_dir = ROOT / "src" / "Controller"
    controllers: list[dict] = []
    for path in sorted(ctrl_dir.rglob("*Controller.php")):
        if "Component" in path.parts:
            continue
        rel = path.relative_to(ROOT).as_posix()
        base = controller_base(path.name)
        php = path.read_text(encoding="utf-8", errors="replace")
        parts = path.parts
        is_api_v1 = "Api" in parts and "V1" in parts
        if is_api_v1 and base != "Api":
            purpose = f"Endpoint API REST v1 ({base}): JSON para integrações externas."
        elif is_api_v1 and base == "Api":
            purpose = "Controller base das rotas API v1 (comportamento comum aos endpoints)."
        else:
            purpose = human_controller_purpose(base)
        controllers.append(
            {
                "file": rel,
                "purpose": purpose,
                "methods": extract_public_methods(php),
            }
        )

    tpl_dir = ROOT / "src" / "Template"
    templates: list[dict] = []
    for path in sorted(tpl_dir.rglob("*.ctp")):
        rel = path.relative_to(ROOT).as_posix()
        templates.append({"file": rel, "purpose": template_purpose(rel)})

    gen_at = datetime.now(timezone.utc).astimezone().strftime("%Y-%m-%d %H:%M:%S %Z")
    nc, nt = len(controllers), len(templates)

    esc = html.escape
    rows_c = "\n".join(
        f"""          <tr>
            <td><code class="path">{esc(c["file"])}</code></td>
            <td>{esc(c["purpose"])}</td>
            <td class="actions">{esc(", ".join(c["methods"]))}</td>
          </tr>"""
        for c in controllers
    )
    rows_t = "\n".join(
        f"""          <tr>
            <td><code class="path">{esc(t["file"])}</code></td>
            <td>{esc(t["purpose"])}</td>
          </tr>"""
        for t in templates
    )

    doc = f"""<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mapeamento de telas e arquivos — Portal</title>
  <style>
    :root {{ --bg:#0f1419; --card:#1a2332; --text:#e7ecf3; --muted:#8b9bb4; --accent:#3d8bfd; --border:#2d3a4d; }}
    * {{ box-sizing: border-box; }}
    body {{ margin:0; font-family: system-ui, Segoe UI, Roboto, sans-serif; background:var(--bg); color:var(--text); line-height:1.5; }}
    header {{ padding:1.5rem 2rem; border-bottom:1px solid var(--border); background:var(--card); }}
    header h1 {{ margin:0 0 .35rem; font-size:1.35rem; font-weight:600; }}
    header p {{ margin:0; color:var(--muted); font-size:.9rem; }}
    main {{ padding:1.5rem 2rem 3rem; max-width:1200px; margin:0 auto; }}
    nav.toc {{ margin-bottom:2rem; padding:1rem 1.25rem; background:var(--card); border-radius:8px; border:1px solid var(--border); }}
    nav.toc a {{ color:var(--accent); text-decoration:none; margin-right:1.25rem; font-size:.9rem; }}
    nav.toc a:hover {{ text-decoration:underline; }}
    section {{ margin-bottom:2.5rem; }}
    section h2 {{ font-size:1.1rem; margin:0 0 1rem; padding-bottom:.5rem; border-bottom:1px solid var(--border); color:#c5d4e8; }}
    table {{ width:100%; border-collapse:collapse; font-size:.82rem; background:var(--card); border-radius:8px; overflow:hidden; border:1px solid var(--border); }}
    th, td {{ text-align:left; padding:.55rem .7rem; border-bottom:1px solid var(--border); vertical-align:top; }}
    th {{ background:#243044; color:#dbe7f7; font-weight:600; white-space:nowrap; }}
    tr:last-child td {{ border-bottom:none; }}
    code.path {{ font-family: ui-monospace, Consolas, monospace; font-size:.78rem; color:#9ed2ff; word-break:break-all; }}
    .muted {{ color:var(--muted); font-size:.8rem; }}
    .actions {{ color:#a8c5e8; font-size:.75rem; max-width:420px; word-break:break-word; }}
    tbody tr:hover td {{ background:#1e2838; }}
  </style>
</head>
<body>
  <header>
    <h1>Mapeamento de telas e responsabilidades por arquivo</h1>
    <p>Gerado em {esc(gen_at)} · {nc} controllers · {nt} templates (.ctp)</p>
  </header>
  <main>
    <nav class="toc">
      <a href="#controllers">Controllers</a>
      <a href="#templates">Templates (.ctp)</a>
    </nav>

    <section id="controllers">
      <h2>Controllers <span class="muted">(ação = possível tela ou endpoint; nem toda ação renderiza HTML)</span></h2>
      <table>
        <thead>
          <tr><th>Arquivo</th><th>Responsabilidade</th><th>Ações públicas</th></tr>
        </thead>
        <tbody>
{rows_c}
        </tbody>
      </table>
    </section>

    <section id="templates">
      <h2>Templates CakePHP <span class="muted">(cada .ctp é uma tela parcial, e-mail ou layout)</span></h2>
      <table>
        <thead>
          <tr><th>Arquivo</th><th>Responsabilidade</th></tr>
        </thead>
        <tbody>
{rows_t}
        </tbody>
      </table>
    </section>
  </main>
</body>
</html>
"""
    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(doc, encoding="utf-8")
    print(f"Escrito: {OUT}")


if __name__ == "__main__":
    main()
