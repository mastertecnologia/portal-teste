<div>
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
    <div>
      <div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;">Service Desk · Visão executiva</div>
      <h1 style="font-size:22px;font-weight:600;">📊 Dashboard executivo</h1>
      <div style="font-size:12px;color:var(--text-muted);">Visão consolidada · atualização em tempo real · 11/05/2026 16:42</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <select style="padding:6px 10px;border:1px solid var(--border);border-radius:6px;font-size:12px;background:#fff;">
        <option>Hoje · tempo real</option><option>Ontem</option><option>Esta semana</option><option>Este mês</option><option>Últimos 30 dias</option><option>Trimestre</option>
      </select>
      <a class="btn btn-ghost btn-sm" href="<?= h($uFila) ?>">📋 Fila técnica</a>
      <button type="button" class="btn btn-primary btn-sm" onclick="alert('Protótipo: exportação não implementada.');">📥 Exportar relatório</button>
    </div>
  </div>

  <!-- Alertas críticos no topo -->
  <div class="alert-box alert-red" style="margin-bottom:14px;">
    🚨 <strong>3 tickets com SLA estourado</strong> requerem ação imediata · <a href="<?= h($uFila) ?>" style="color:var(--teal-dark);font-weight:700;text-decoration:underline;">ver agora →</a>
  </div>

  <!-- KPIs principais -->
  <div class="summary-grid" style="margin-bottom:14px;">
    <div class="summary-card" style="border-left:3px solid var(--teal);"><div class="lbl">Volume hoje</div><div class="val" style="color:var(--teal-dark);">18</div><div style="font-size:11px;color:var(--teal-dark);">↑ 12% vs ontem</div></div>
    <div class="summary-card" style="border-left:3px solid var(--blue);"><div class="lbl">Em aberto</div><div class="val" style="color:#0C447C;">462</div><div style="font-size:11px;color:var(--text-muted);">total empresa</div></div>
    <div class="summary-card" style="background:#F8D8DA;border-left:3px solid var(--red);"><div class="lbl">SLA estourado</div><div class="val" style="color:#7A1822;">3</div><div style="font-size:11px;color:#7A1822;">ação imediata</div></div>
    <div class="summary-card" style="background:#FAEEDA;border-left:3px solid var(--amber);"><div class="lbl">Próx. limite</div><div class="val" style="color:#8A4D02;">7</div><div style="font-size:11px;color:#8A4D02;">próximas 4h</div></div>
    <div class="summary-card" style="border-left:3px solid #6B5B95;"><div class="lbl">FCR (1ª resolução)</div><div class="val" style="color:#3D2D63;">73%</div><div style="font-size:11px;color:var(--teal-dark);">↑ 4pp</div></div>
    <div class="summary-card" style="border-left:3px solid #D946A0;"><div class="lbl">CSAT médio</div><div class="val" style="color:#7A1B5C;">⭐ 4.6</div><div style="font-size:11px;color:var(--text-muted);">218 respostas</div></div>
  </div>

  <!-- Linha 2: KPIs financeiros -->
  <div class="summary-grid" style="margin-bottom:14px;">
    <div class="summary-card" style="border-left:3px solid var(--teal-mid);"><div class="lbl">Receita mensal SD</div><div class="val" style="color:var(--teal-dark);">R$ 87.230</div><div style="font-size:11px;color:var(--teal-dark);">↑ 18% vs mês ant.</div></div>
    <div class="summary-card" style="border-left:3px solid var(--blue);"><div class="lbl">A faturar</div><div class="val" style="color:#0C447C;">R$ 28.480</div><div style="font-size:11px;color:var(--text-muted);">12 tickets fechados</div></div>
    <div class="summary-card" style="background:var(--teal-light);border-left:3px solid var(--teal);"><div class="lbl">Margem operacional</div><div class="val" style="color:var(--teal-dark);">62%</div><div style="font-size:11px;color:var(--text-muted);">após custo técnico</div></div>
    <div class="summary-card" style="border-left:3px solid #6B5B95;"><div class="lbl">Custo médio/ticket</div><div class="val" style="color:#3D2D63;">R$ 188</div><div style="font-size:11px;color:var(--teal-dark);">↓ 8%</div></div>
    <div class="summary-card" style="border-left:3px solid var(--amber);"><div class="lbl">Horas técnicas</div><div class="val" style="color:#8A4D02;">147,5h</div><div style="font-size:11px;color:var(--text-muted);">87h cobertas</div></div>
    <div class="summary-card" style="border-left:3px solid #D946A0;"><div class="lbl">NPS</div><div class="val" style="color:#7A1B5C;">+62</div><div style="font-size:11px;color:var(--teal-dark);">★ Excelente</div></div>
  </div>

  <!-- Grid de gráficos e tabelas -->
  <div class="g2" style="margin-bottom:14px;">
    <!-- Volume por dia -->
    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <div class="sec-title sdp-sec-no-line" style="margin:0;border:none;">📈 Volume diário · últimos 14 dias</div>
        <div style="font-size:11px;color:var(--text-muted);"><span style="color:var(--teal-dark);">●</span> Abertos · <span style="color:#D946A0;">●</span> Fechados</div>
      </div>
      <div style="height:200px;background:var(--bg-surface);border-radius:var(--radius);padding:14px;display:flex;align-items:end;gap:6px;">
        <div style="flex:1;display:flex;flex-direction:column;gap:1px;align-items:center;"><div style="width:100%;background:var(--teal);height:50%;border-radius:2px 2px 0 0;"></div><div style="width:100%;background:#D946A0;height:45%;border-radius:0;"></div><span style="font-size:9px;color:var(--text-muted);margin-top:4px;">28</span></div>
        <div style="flex:1;display:flex;flex-direction:column;gap:1px;align-items:center;"><div style="width:100%;background:var(--teal);height:65%;border-radius:2px 2px 0 0;"></div><div style="width:100%;background:#D946A0;height:55%;"></div><span style="font-size:9px;color:var(--text-muted);margin-top:4px;">29</span></div>
        <div style="flex:1;display:flex;flex-direction:column;gap:1px;align-items:center;"><div style="width:100%;background:var(--teal);height:40%;border-radius:2px 2px 0 0;"></div><div style="width:100%;background:#D946A0;height:38%;"></div><span style="font-size:9px;color:var(--text-muted);margin-top:4px;">30</span></div>
        <div style="flex:1;display:flex;flex-direction:column;gap:1px;align-items:center;"><div style="width:100%;background:var(--teal);height:75%;border-radius:2px 2px 0 0;"></div><div style="width:100%;background:#D946A0;height:68%;"></div><span style="font-size:9px;color:var(--text-muted);margin-top:4px;">01</span></div>
        <div style="flex:1;display:flex;flex-direction:column;gap:1px;align-items:center;"><div style="width:100%;background:var(--teal);height:62%;border-radius:2px 2px 0 0;"></div><div style="width:100%;background:#D946A0;height:60%;"></div><span style="font-size:9px;color:var(--text-muted);margin-top:4px;">02</span></div>
        <div style="flex:1;display:flex;flex-direction:column;gap:1px;align-items:center;"><div style="width:100%;background:var(--teal-mid);height:25%;border-radius:2px 2px 0 0;"></div><div style="width:100%;background:#D946A0;height:20%;"></div><span style="font-size:9px;color:var(--text-muted);margin-top:4px;">03</span></div>
        <div style="flex:1;display:flex;flex-direction:column;gap:1px;align-items:center;"><div style="width:100%;background:var(--teal-mid);height:22%;border-radius:2px 2px 0 0;"></div><div style="width:100%;background:#D946A0;height:18%;"></div><span style="font-size:9px;color:var(--text-muted);margin-top:4px;">04</span></div>
        <div style="flex:1;display:flex;flex-direction:column;gap:1px;align-items:center;"><div style="width:100%;background:var(--teal);height:80%;border-radius:2px 2px 0 0;"></div><div style="width:100%;background:#D946A0;height:72%;"></div><span style="font-size:9px;color:var(--text-muted);margin-top:4px;">05</span></div>
        <div style="flex:1;display:flex;flex-direction:column;gap:1px;align-items:center;"><div style="width:100%;background:var(--teal);height:70%;border-radius:2px 2px 0 0;"></div><div style="width:100%;background:#D946A0;height:65%;"></div><span style="font-size:9px;color:var(--text-muted);margin-top:4px;">06</span></div>
        <div style="flex:1;display:flex;flex-direction:column;gap:1px;align-items:center;"><div style="width:100%;background:var(--teal);height:58%;border-radius:2px 2px 0 0;"></div><div style="width:100%;background:#D946A0;height:55%;"></div><span style="font-size:9px;color:var(--text-muted);margin-top:4px;">07</span></div>
        <div style="flex:1;display:flex;flex-direction:column;gap:1px;align-items:center;"><div style="width:100%;background:var(--teal);height:85%;border-radius:2px 2px 0 0;"></div><div style="width:100%;background:#D946A0;height:78%;"></div><span style="font-size:9px;color:var(--text-muted);margin-top:4px;">08</span></div>
        <div style="flex:1;display:flex;flex-direction:column;gap:1px;align-items:center;"><div style="width:100%;background:var(--teal-mid);height:30%;border-radius:2px 2px 0 0;"></div><div style="width:100%;background:#D946A0;height:25%;"></div><span style="font-size:9px;color:var(--text-muted);margin-top:4px;">09</span></div>
        <div style="flex:1;display:flex;flex-direction:column;gap:1px;align-items:center;"><div style="width:100%;background:var(--teal-mid);height:28%;border-radius:2px 2px 0 0;"></div><div style="width:100%;background:#D946A0;height:22%;"></div><span style="font-size:9px;color:var(--text-muted);margin-top:4px;">10</span></div>
        <div style="flex:1;display:flex;flex-direction:column;gap:1px;align-items:center;"><div style="width:100%;background:var(--teal);height:90%;border-radius:2px 2px 0 0;border:1px solid var(--teal-dark);"></div><div style="width:100%;background:#D946A0;height:42%;"></div><span style="font-size:9px;color:var(--text-muted);margin-top:4px;font-weight:700;">11</span></div>
      </div>
    </div>

    <!-- Distribuição por status -->
    <div class="card">
      <div class="sec-title">📊 Distribuição por status (462 abertos)</div>
      <div style="display:flex;flex-direction:column;gap:10px;">
        <div><div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;"><span>🟢 Aberto · sem técnico</span><strong>32 (7%)</strong></div><div style="height:10px;background:var(--bg-surface);border-radius:5px;overflow:hidden;"><div style="height:100%;width:7%;background:#7DD3C0;"></div></div></div>
        <div><div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;"><span>🔵 Em execução</span><strong>187 (40%)</strong></div><div style="height:10px;background:var(--bg-surface);border-radius:5px;overflow:hidden;"><div style="height:100%;width:40%;background:#06B6D4;"></div></div></div>
        <div><div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;"><span>⏰ Pendente · aguarda cliente</span><strong>89 (19%)</strong></div><div style="height:10px;background:var(--bg-surface);border-radius:5px;overflow:hidden;"><div style="height:100%;width:19%;background:#F59E0B;"></div></div></div>
        <div><div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;"><span>✓ Resolvido · aguarda aprovação</span><strong>32 (7%)</strong></div><div style="height:10px;background:var(--bg-surface);border-radius:5px;overflow:hidden;"><div style="height:100%;width:7%;background:#10B981;"></div></div></div>
        <div><div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;"><span>⬛ Fechado (hoje)</span><strong>122 (26%)</strong></div><div style="height:10px;background:var(--bg-surface);border-radius:5px;overflow:hidden;"><div style="height:100%;width:26%;background:#6B7280;"></div></div></div>
      </div>
      <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border-light);">
        <div class="sec-title" style="margin-bottom:8px;">Por categoria</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:11px;">
          <div>🔑 Acesso · <strong>147</strong></div>
          <div>💿 Software · <strong>112</strong></div>
          <div>🖥 Hardware · <strong>87</strong></div>
          <div>📧 E-mail · <strong>68</strong></div>
          <div>🌐 Rede · <strong>43</strong></div>
          <div>📦 Outros · <strong>5</strong></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Linha 3: Top clientes + Top tipos + Equipe -->
  <div class="sdp-grid-3" style="margin-bottom:14px;">
    <div class="card">
      <div class="sec-title">🏆 Top 5 clientes · volume</div>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px;background:var(--bg-surface);border-radius:6px;cursor:pointer;" onclick="location.href=<?= json_encode($uClientes) ?>">
          <div><strong style="font-size:12px;">Mobles Fab. Móveis</strong><div style="font-size:10px;color:var(--text-muted);">Premium · CSAT ⭐ 4.8</div></div>
          <strong style="color:var(--teal-dark);">43</strong>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px;background:var(--bg-surface);border-radius:6px;cursor:pointer;" onclick="location.href=<?= json_encode($uClientes) ?>">
          <div><strong style="font-size:12px;">Cristofoli</strong><div style="font-size:10px;color:var(--text-muted);">Premium · CSAT ⭐ 4.6</div></div>
          <strong style="color:var(--teal-dark);">38</strong>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px;background:var(--bg-surface);border-radius:6px;cursor:pointer;" onclick="location.href=<?= json_encode($uClientes) ?>">
          <div><strong style="font-size:12px;">Engemed</strong><div style="font-size:10px;color:var(--text-muted);">Premium · CSAT ⭐ 4.7</div></div>
          <strong style="color:var(--teal-dark);">32</strong>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px;background:var(--bg-surface);border-radius:6px;cursor:pointer;" onclick="location.href=<?= json_encode($uClientes) ?>">
          <div><strong style="font-size:12px;">Vinícola Aurora</strong><div style="font-size:10px;color:var(--text-muted);">Standard · CSAT ⭐ 4.5</div></div>
          <strong style="color:var(--teal-dark);">28</strong>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px;background:var(--bg-surface);border-radius:6px;cursor:pointer;" onclick="location.href=<?= json_encode($uClientes) ?>">
          <div><strong style="font-size:12px;">Pinheiro Têxtil</strong><div style="font-size:10px;color:var(--text-muted);">Standard · CSAT ⭐ 4.4</div></div>
          <strong style="color:var(--teal-dark);">23</strong>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="sec-title">🔥 Categorias quentes (24h)</div>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <div style="padding:10px;background:#FEE2E2;border-radius:8px;border-left:3px solid var(--red);">
          <div style="font-size:12px;font-weight:600;color:#7A1822;">🚨 VPN não conecta</div>
          <div style="font-size:11px;color:var(--text-muted);">3 tickets idênticos · Aurora · provedor</div>
          <button type="button" class="btn btn-ghost btn-xs" style="margin-top:4px;" onclick="alert('Protótipo: ligação a Problemas não implementada.');">Criar Problema</button>
        </div>
        <div style="padding:10px;background:#FAEEDA;border-radius:8px;border-left:3px solid var(--amber);">
          <div style="font-size:12px;font-weight:600;color:#8A4D02;">⚠ Senha ERP expirada</div>
          <div style="font-size:11px;color:var(--text-muted);">7 tickets esta semana · pico mês</div>
          <button type="button" class="btn btn-ghost btn-xs" style="margin-top:4px;" onclick="alert('Protótipo: KB não implementado.');">Ver KB</button>
        </div>
        <div style="padding:10px;background:var(--blue-light);border-radius:8px;border-left:3px solid var(--blue);">
          <div style="font-size:12px;font-weight:600;color:#0C447C;">📊 Excel travando</div>
          <div style="font-size:11px;color:var(--text-muted);">5 tickets · investigar add-in</div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="sec-title">👥 Status da equipe agora</div>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <div style="display:flex;align-items:center;gap:8px;padding:8px;background:var(--bg-surface);border-radius:6px;">
          <div style="width:32px;height:32px;border-radius:50%;background:var(--teal);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;position:relative;">DG<span style="position:absolute;bottom:-2px;right:-2px;width:10px;height:10px;background:#10B981;border:2px solid #fff;border-radius:50%;"></span></div>
          <div style="flex:1;"><strong style="font-size:12px;">Darli</strong><div style="font-size:10px;color:var(--text-muted);">🟢 Online · 4 ativos</div></div>
          <div style="font-size:10px;color:var(--text-muted);">N3</div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;padding:8px;background:var(--bg-surface);border-radius:6px;">
          <div style="width:32px;height:32px;border-radius:50%;background:#06B6D4;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;position:relative;">LF<span style="position:absolute;bottom:-2px;right:-2px;width:10px;height:10px;background:#10B981;border:2px solid #fff;border-radius:50%;"></span></div>
          <div style="flex:1;"><strong style="font-size:12px;">Lucas</strong><div style="font-size:10px;color:var(--text-muted);">🟢 Online · 8 ativos</div></div>
          <div style="font-size:10px;color:var(--text-muted);">N1/N2</div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;padding:8px;background:var(--bg-surface);border-radius:6px;">
          <div style="width:32px;height:32px;border-radius:50%;background:#D946A0;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;position:relative;">FA<span style="position:absolute;bottom:-2px;right:-2px;width:10px;height:10px;background:#F59E0B;border:2px solid #fff;border-radius:50%;"></span></div>
          <div style="flex:1;"><strong style="font-size:12px;">Fernanda</strong><div style="font-size:10px;color:var(--text-muted);">🟡 Em chamada · 5 ativos</div></div>
          <div style="font-size:10px;color:var(--text-muted);">N2</div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;padding:8px;background:var(--bg-surface);border-radius:6px;opacity:.6;">
          <div style="width:32px;height:32px;border-radius:50%;background:#9CA3AF;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;position:relative;">PS<span style="position:absolute;bottom:-2px;right:-2px;width:10px;height:10px;background:#9CA3AF;border:2px solid #fff;border-radius:50%;"></span></div>
          <div style="flex:1;"><strong style="font-size:12px;">Pedro</strong><div style="font-size:10px;color:var(--text-muted);">⚫ Offline · até 8h</div></div>
          <div style="font-size:10px;color:var(--text-muted);">N1</div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;padding:8px;background:var(--bg-surface);border-radius:6px;">
          <div style="width:32px;height:32px;border-radius:50%;background:#6B5B95;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;position:relative;">MC<span style="position:absolute;bottom:-2px;right:-2px;width:10px;height:10px;background:#10B981;border:2px solid #fff;border-radius:50%;"></span></div>
          <div style="flex:1;"><strong style="font-size:12px;">Mariana</strong><div style="font-size:10px;color:var(--text-muted);">🟢 Online · 3 ativos</div></div>
          <div style="font-size:10px;color:var(--text-muted);">N1</div>
        </div>
        <button type="button" class="btn btn-ghost btn-xs" style="margin-top:4px;" onclick="alert('Protótipo: escala em calendar não ligada.');">📅 Ver escala completa</button>
      </div>
    </div>
  </div>

  <!-- Heatmap por hora x dia -->
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
      <div class="sec-title sdp-sec-no-line" style="margin:0;border:none;">🗺 Heatmap · volume por dia da semana × horário</div>
      <div style="font-size:11px;color:var(--text-muted);">média últimos 90 dias</div>
    </div>
    <div style="overflow-x:auto;">
      <table style="width:100%;border-collapse:separate;border-spacing:2px;font-size:11px;min-width:600px;">
        <thead>
          <tr>
            <th></th>
            <th style="padding:4px;color:var(--text-muted);font-weight:600;">8h</th><th style="padding:4px;color:var(--text-muted);font-weight:600;">9h</th><th style="padding:4px;color:var(--text-muted);font-weight:600;">10h</th><th style="padding:4px;color:var(--text-muted);font-weight:600;">11h</th><th style="padding:4px;color:var(--text-muted);font-weight:600;">12h</th><th style="padding:4px;color:var(--text-muted);font-weight:600;">13h</th><th style="padding:4px;color:var(--text-muted);font-weight:600;">14h</th><th style="padding:4px;color:var(--text-muted);font-weight:600;">15h</th><th style="padding:4px;color:var(--text-muted);font-weight:600;">16h</th><th style="padding:4px;color:var(--text-muted);font-weight:600;">17h</th><th style="padding:4px;color:var(--text-muted);font-weight:600;">18h</th>
          </tr>
        </thead>
        <tbody>
          <tr><td style="padding:4px;color:var(--text-muted);font-weight:600;">Seg</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">4</td><td style="background:#7DD3C0;padding:8px;text-align:center;color:#fff;border-radius:3px;">12</td><td style="background:#1D9E75;padding:8px;text-align:center;color:#fff;border-radius:3px;font-weight:700;">22</td><td style="background:#0a3d2c;padding:8px;text-align:center;color:#fff;border-radius:3px;font-weight:700;">28</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">3</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">2</td><td style="background:#7DD3C0;padding:8px;text-align:center;color:#fff;border-radius:3px;">14</td><td style="background:#1D9E75;padding:8px;text-align:center;color:#fff;border-radius:3px;font-weight:700;">20</td><td style="background:#1D9E75;padding:8px;text-align:center;color:#fff;border-radius:3px;font-weight:700;">19</td><td style="background:#7DD3C0;padding:8px;text-align:center;color:#fff;border-radius:3px;">11</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">5</td></tr>
          <tr><td style="padding:4px;color:var(--text-muted);font-weight:600;">Ter</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">5</td><td style="background:#7DD3C0;padding:8px;text-align:center;color:#fff;border-radius:3px;">15</td><td style="background:#1D9E75;padding:8px;text-align:center;color:#fff;border-radius:3px;font-weight:700;">24</td><td style="background:#0a3d2c;padding:8px;text-align:center;color:#fff;border-radius:3px;font-weight:700;">31</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">4</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">3</td><td style="background:#7DD3C0;padding:8px;text-align:center;color:#fff;border-radius:3px;">17</td><td style="background:#1D9E75;padding:8px;text-align:center;color:#fff;border-radius:3px;font-weight:700;">22</td><td style="background:#1D9E75;padding:8px;text-align:center;color:#fff;border-radius:3px;font-weight:700;">21</td><td style="background:#7DD3C0;padding:8px;text-align:center;color:#fff;border-radius:3px;">13</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">6</td></tr>
          <tr><td style="padding:4px;color:var(--text-muted);font-weight:600;">Qua</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">4</td><td style="background:#7DD3C0;padding:8px;text-align:center;color:#fff;border-radius:3px;">13</td><td style="background:#1D9E75;padding:8px;text-align:center;color:#fff;border-radius:3px;font-weight:700;">21</td><td style="background:#0a3d2c;padding:8px;text-align:center;color:#fff;border-radius:3px;font-weight:700;">29</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">3</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">2</td><td style="background:#7DD3C0;padding:8px;text-align:center;color:#fff;border-radius:3px;">15</td><td style="background:#1D9E75;padding:8px;text-align:center;color:#fff;border-radius:3px;font-weight:700;">19</td><td style="background:#1D9E75;padding:8px;text-align:center;color:#fff;border-radius:3px;font-weight:700;">18</td><td style="background:#7DD3C0;padding:8px;text-align:center;color:#fff;border-radius:3px;">10</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">4</td></tr>
          <tr><td style="padding:4px;color:var(--text-muted);font-weight:600;">Qui</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">5</td><td style="background:#7DD3C0;padding:8px;text-align:center;color:#fff;border-radius:3px;">14</td><td style="background:#1D9E75;padding:8px;text-align:center;color:#fff;border-radius:3px;font-weight:700;">23</td><td style="background:#0a3d2c;padding:8px;text-align:center;color:#fff;border-radius:3px;font-weight:700;">30</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">4</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">3</td><td style="background:#7DD3C0;padding:8px;text-align:center;color:#fff;border-radius:3px;">16</td><td style="background:#1D9E75;padding:8px;text-align:center;color:#fff;border-radius:3px;font-weight:700;">21</td><td style="background:#1D9E75;padding:8px;text-align:center;color:#fff;border-radius:3px;font-weight:700;">20</td><td style="background:#7DD3C0;padding:8px;text-align:center;color:#fff;border-radius:3px;">12</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">5</td></tr>
          <tr><td style="padding:4px;color:var(--text-muted);font-weight:600;">Sex</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">6</td><td style="background:#7DD3C0;padding:8px;text-align:center;color:#fff;border-radius:3px;">11</td><td style="background:#1D9E75;padding:8px;text-align:center;color:#fff;border-radius:3px;font-weight:700;">18</td><td style="background:#1D9E75;padding:8px;text-align:center;color:#fff;border-radius:3px;font-weight:700;">22</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">3</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">2</td><td style="background:#7DD3C0;padding:8px;text-align:center;color:#fff;border-radius:3px;">13</td><td style="background:#7DD3C0;padding:8px;text-align:center;color:#fff;border-radius:3px;">15</td><td style="background:#7DD3C0;padding:8px;text-align:center;color:#fff;border-radius:3px;">12</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">7</td><td style="background:#C5F1D8;padding:8px;text-align:center;border-radius:3px;">3</td></tr>
        </tbody>
      </table>
    </div>
    <div style="display:flex;align-items:center;gap:14px;margin-top:10px;font-size:11px;color:var(--text-muted);">
      <span>Intensidade:</span>
      <span style="display:flex;align-items:center;gap:4px;"><span style="width:14px;height:14px;background:#C5F1D8;border-radius:2px;"></span>0-9</span>
      <span style="display:flex;align-items:center;gap:4px;"><span style="width:14px;height:14px;background:#7DD3C0;border-radius:2px;"></span>10-15</span>
      <span style="display:flex;align-items:center;gap:4px;"><span style="width:14px;height:14px;background:#1D9E75;border-radius:2px;"></span>16-25</span>
      <span style="display:flex;align-items:center;gap:4px;"><span style="width:14px;height:14px;background:#0a3d2c;border-radius:2px;"></span>26+</span>
      <span style="margin-left:auto;">💡 Pico: <strong>terças 11h</strong> (31 tickets)</span>
    </div>
  </div>
</div>
