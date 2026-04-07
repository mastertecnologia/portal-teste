<?php
use Cake\Routing\Router;

$this->assign('title', 'Redefinir senha');

$this->start('css');
?>
  <style>
    :root {
      --bg: #edf2f5;
      --bg-accent: #e4ecef;
      --card: #ffffff;
      --text: #173042;
      --muted: #64748b;
      --border: #d8e1e7;
      --focus: #0ea5a4;
      --primary: #07b889;
      --primary-hover: #059669;
      --secondary: #0ea5a4;
      --error: #dc2626;
      --success: #15803d;
      --weak: #ef4444;
      --medium: #f59e0b;
      --strong: #22c55e;
      --shadow: 0 22px 60px rgba(23, 48, 66, 0.14);
      --radius: 22px;
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      min-height: 100vh;
      font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background:
        radial-gradient(circle at top left, rgba(14, 165, 164, 0.10), transparent 28%),
        radial-gradient(circle at bottom right, rgba(7, 184, 137, 0.10), transparent 24%),
        linear-gradient(180deg, var(--bg) 0%, var(--bg-accent) 100%);
      color: var(--text);
      display: grid;
      place-items: center;
      padding: 24px;
    }

    .shell {
      width: 100%;
      max-width: 440px;
      position: relative;
    }

    .shell::before,
    .shell::after {
      content: "";
      position: absolute;
      border-radius: 999px;
      filter: blur(2px);
      z-index: 0;
    }

    .shell::before {
      width: 140px;
      height: 140px;
      background: rgba(14, 165, 164, 0.14);
      top: -28px;
      right: -18px;
    }

    .shell::after {
      width: 110px;
      height: 110px;
      background: rgba(7, 184, 137, 0.12);
      bottom: -18px;
      left: -10px;
    }

    .card {
      position: relative;
      z-index: 1;
      background: rgba(255, 255, 255, 0.94);
      backdrop-filter: blur(8px);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 34px;
      border: 1px solid rgba(255, 255, 255, 0.75);
    }

    .brand-row {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 22px;
    }

    .brand {
      width: 52px;
      height: 52px;
      border-radius: 16px;
      background: linear-gradient(135deg, var(--secondary), var(--primary));
      display: grid;
      place-items: center;
      color: white;
      font-weight: 800;
      letter-spacing: 0.6px;
      box-shadow: 0 12px 24px rgba(14, 165, 164, 0.24);
    }

    .brand-meta {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .brand-meta strong {
      font-size: 14px;
      letter-spacing: 0.08em;
      color: var(--secondary);
    }

    .brand-meta span {
      font-size: 13px;
      color: var(--muted);
    }

    h1 {
      font-size: 30px;
      line-height: 1.12;
      margin: 0 0 10px;
      letter-spacing: -0.02em;
    }

    .subtitle {
      font-size: 14px;
      line-height: 1.6;
      color: var(--muted);
      margin: 0 0 24px;
    }

    .security-note {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 12px;
      color: var(--secondary);
      background: rgba(14, 165, 164, 0.08);
      border: 1px solid rgba(14, 165, 164, 0.14);
      border-radius: 12px;
      padding: 10px 12px;
      margin-bottom: 22px;
    }

    .field {
      margin-bottom: 18px;
    }

    label {
      display: block;
      font-size: 14px;
      font-weight: 700;
      margin-bottom: 8px;
      color: #244154;
    }

    .input-wrap {
      position: relative;
    }

    input {
      width: 100%;
      height: 52px;
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 0 48px 0 14px;
      font-size: 15px;
      color: var(--text);
      outline: none;
      transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease;
      background: #fff;
    }

    input::placeholder {
      color: #94a3b8;
    }

    input:focus {
      border-color: var(--focus);
      box-shadow: 0 0 0 4px rgba(14, 165, 164, 0.12);
    }

    .toggle {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      border: 0;
      background: transparent;
      cursor: pointer;
      width: 32px;
      height: 32px;
      border-radius: 10px;
      color: #5b7183;
      font-size: 16px;
      transition: background-color .2s ease, color .2s ease;
    }

    .toggle:hover,
    .toggle:focus-visible {
      background: #f1f5f9;
      color: var(--secondary);
      outline: none;
    }

    .helper {
      font-size: 12px;
      color: var(--muted);
      margin-top: 8px;
    }

    .rules {
      list-style: none;
      padding: 14px;
      margin: 12px 0 18px;
      display: grid;
      gap: 8px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
    }

    .rules li {
      font-size: 13px;
      color: var(--muted);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .rules li::before {
      content: "•";
      font-size: 18px;
      line-height: 1;
      color: #cbd5e1;
    }

    .rules li.valid {
      color: var(--success);
      font-weight: 600;
    }

    .rules li.valid::before {
      content: "✓";
      color: var(--success);
      font-size: 14px;
    }

    .strength {
      margin: 12px 0 18px;
      padding: 14px;
      border-radius: 14px;
      background: linear-gradient(180deg, rgba(14, 165, 164, 0.04), rgba(7, 184, 137, 0.04));
      border: 1px solid rgba(14, 165, 164, 0.10);
    }

    .strength-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
      font-size: 13px;
    }

    .bar {
      height: 8px;
      background: #dbe4ea;
      border-radius: 999px;
      overflow: hidden;
    }

    .bar-fill {
      height: 100%;
      width: 20%;
      border-radius: 999px;
      transition: width .25s ease, background-color .25s ease;
      background: var(--weak);
    }

    .error {
      color: var(--error);
      font-size: 13px;
      margin: 8px 0 0;
      min-height: 18px;
      font-weight: 500;
    }

    .submit {
      width: 100%;
      height: 52px;
      border: 0;
      border-radius: 14px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      font-size: 15px;
      font-weight: 800;
      letter-spacing: 0.01em;
      cursor: pointer;
      transition: transform .08s ease, filter .2s ease, opacity .2s ease;
      margin-top: 6px;
      box-shadow: 0 14px 28px rgba(7, 184, 137, 0.24);
    }

    .submit:hover {
      filter: brightness(0.98);
    }

    .submit:active { transform: translateY(1px); }

    .submit:disabled {
      background: #9ca3af;
      box-shadow: none;
      cursor: not-allowed;
      transform: none;
      opacity: 0.85;
    }

    .success-box {
      display: none;
      margin-top: 16px;
      padding: 13px 14px;
      border-radius: 14px;
      background: #ecfdf5;
      color: #166534;
      font-size: 14px;
      border: 1px solid #bbf7d0;
    }

    .footer {
      display: flex;
      justify-content: center;
      gap: 8px;
      text-align: center;
      margin-top: 18px;
      font-size: 14px;
      color: var(--muted);
    }

    .footer a {
      color: #0f766e;
      text-decoration: none;
      font-weight: 700;
    }

    .input-error {
      border-color: var(--error);
      box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.08);
    }

    @media (max-width: 480px) {
      body {
        padding: 16px;
      }

      .card {
        padding: 24px;
        border-radius: 18px;
      }

      h1 {
        font-size: 26px;
      }

      .brand-row {
        margin-bottom: 18px;
      }
    }
  </style>
<?php
$this->end();
?>

  <main class="shell">
    <section class="card" aria-labelledby="titulo-reset">
      <div class="brand-row">
        <div class="brand" aria-hidden="true">PGM</div>
        <div class="brand-meta">
          <strong>PORTAL PGM</strong>
          <span>Acesso seguro do usuário</span>
        </div>
      </div>

      <h1 id="titulo-reset">Redefinir senha</h1>
      <p class="subtitle">Atualize sua senha para concluir o processo de redefinição com mais segurança e uma experiência mais clara.</p>
      <div class="security-note" aria-label="aviso de segurança">🔒 Sua senha será atualizada imediatamente após a confirmação.</div>

      <?= $this->Form->create($user, [
        'id' => 'resetForm',
        'novalidate' => true,
        'url' => ['controller' => 'Users', 'action' => 'resetPasswordNew', '?' => $this->request->getQueryParams()],
      ]) ?>
      <p class="error" id="serverError" aria-live="polite"></p>
        <div class="field">
          <label for="password">Nova senha</label>
          <div class="input-wrap">
            <input id="password" name="password" type="password" autocomplete="new-password" placeholder="Digite sua nova senha" />
            <button class="toggle" type="button" data-toggle="password" aria-label="Mostrar senha">👁</button>
          </div>
          <p class="helper">Use uma senha forte para proteger sua conta.</p>
        </div>

        <div class="strength" aria-live="polite">
          <div class="strength-top">
            <span>Força da senha</span>
            <strong id="strengthLabel">Fraca</strong>
          </div>
          <div class="bar"><div class="bar-fill" id="strengthBar"></div></div>
        </div>

        <ul class="rules" id="rulesList">
          <li data-rule="length">Mínimo de 8 caracteres</li>
          <li data-rule="upper">Pelo menos 1 letra maiúscula</li>
          <li data-rule="number">Pelo menos 1 número</li>
          <li data-rule="special">Pelo menos 1 caractere especial</li>
        </ul>

        <div class="field">
          <label for="confirmPassword">Confirmar senha</label>
          <div class="input-wrap">
            <input id="confirmPassword" name="confirmPassword" type="password" autocomplete="new-password" placeholder="Repita a nova senha" />
            <button class="toggle" type="button" data-toggle="confirmPassword" aria-label="Mostrar confirmação de senha">👁</button>
          </div>
          <p class="error" id="confirmError" aria-live="polite"></p>
        </div>

        <button class="submit" id="submitBtn" type="submit" disabled>Redefinir senha</button>
        <div class="success-box" id="successBox">Senha redefinida com sucesso. Redirecionando para o login...</div>
      <?= $this->Form->end() ?>

      <div class="footer">
        <a href="<?= h($voltarLoginUrl ?? Router::url(['controller' => 'Users', 'action' => 'login'])) ?>">Voltar para o login</a>
      </div>
    </section>
  </main>

<?php $this->start('script'); ?>
  <script>
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');
    const submitBtn = document.getElementById('submitBtn');
    const confirmError = document.getElementById('confirmError');
    const strengthBar = document.getElementById('strengthBar');
    const strengthLabel = document.getElementById('strengthLabel');
    const successBox = document.getElementById('successBox');
    const form = document.getElementById('resetForm');
    const serverError = document.getElementById('serverError');
    form.setAttribute('novalidate', '');

    const checks = {
      length: value => value.length >= 8,
      upper: value => /[A-Z]/.test(value),
      number: value => /\d/.test(value),
      special: value => /[^A-Za-z0-9]/.test(value),
    };

    function evaluatePassword(value) {
      const results = Object.fromEntries(
        Object.entries(checks).map(([key, fn]) => [key, fn(value)])
      );

      const passed = Object.values(results).filter(Boolean).length;
      let label = 'Fraca';
      let width = '20%';
      let color = 'var(--weak)';

      if (passed >= 4 && value.length >= 10) {
        label = 'Forte';
        width = '100%';
        color = 'var(--strong)';
      } else if (passed >= 3) {
        label = 'Média';
        width = '66%';
        color = 'var(--medium)';
      } else if (passed >= 1) {
        label = 'Fraca';
        width = '33%';
        color = 'var(--weak)';
      }

      strengthLabel.textContent = value ? label : 'Fraca';
      strengthBar.style.width = value ? width : '20%';
      strengthBar.style.background = value ? color : 'var(--weak)';

      document.querySelectorAll('[data-rule]').forEach(item => {
        const rule = item.getAttribute('data-rule');
        item.classList.toggle('valid', results[rule]);
      });

      return Object.values(results).every(Boolean);
    }

    function validateForm() {
      const passwordValid = evaluatePassword(password.value);
      const passwordsMatch = password.value && confirmPassword.value && password.value === confirmPassword.value;

      if (confirmPassword.value && password.value !== confirmPassword.value) {
        confirmError.textContent = 'As senhas não coincidem.';
        confirmPassword.classList.add('input-error');
      } else {
        confirmError.textContent = '';
        confirmPassword.classList.remove('input-error');
      }

      submitBtn.disabled = !(passwordValid && passwordsMatch);
    }

    [password, confirmPassword].forEach(input => {
      input.addEventListener('input', validateForm);
    });

    document.querySelectorAll('.toggle').forEach(button => {
      button.addEventListener('click', () => {
        const target = document.getElementById(button.dataset.toggle);
        const isPassword = target.type === 'password';
        target.type = isPassword ? 'text' : 'password';
        button.textContent = isPassword ? '🙈' : '👁';
      });
    });

    form.addEventListener('submit', event => {
      event.preventDefault();
      if (serverError) {
        serverError.textContent = '';
      }
      submitBtn.disabled = true;
      submitBtn.textContent = 'Atualizando...';

      const fd = new FormData(form);
      const action = form.getAttribute('action') || window.location.href;

      fetch(action, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
      })
        .then(response => {
          const ct = response.headers.get('content-type') || '';
          if (ct.indexOf('application/json') !== -1) {
            return response.json().then(data => ({ ok: response.ok, status: response.status, data: data }));
          }
          return { ok: response.ok, status: response.status, data: null };
        })
        .then(result => {
          if (result.data && result.data.success && result.data.redirect) {
            successBox.style.display = 'block';
            submitBtn.textContent = 'Senha atualizada';
            setTimeout(function () {
              window.location.href = result.data.redirect;
            }, 900);
            return;
          }
          if (result.data && result.data.message && serverError) {
            serverError.textContent = result.data.message;
          } else if (serverError) {
            serverError.textContent = 'Não foi possível atualizar a senha.';
          }
          submitBtn.disabled = false;
          submitBtn.textContent = 'Redefinir senha';
          validateForm();
        })
        .catch(function () {
          if (serverError) {
            serverError.textContent = 'Erro de rede. Tente novamente.';
          }
          submitBtn.disabled = false;
          submitBtn.textContent = 'Redefinir senha';
          validateForm();
        });
    });
  </script>
<?php $this->end(); ?>
