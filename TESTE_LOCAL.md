# Como testar as alterações localmente (sem produção)

Siga estes passos para rodar o portal na sua máquina e testar as mudanças **sem usar o banco nem o servidor de produção**.

## 1. Configuração local (só uma vez)

### Opção A: Usar `app_local.php` (recomendado)

1. Na pasta do projeto, copie o exemplo de config local:
   ```bash
   cd c:\Portal_New\htdocs\portal
   copy config\app_local.php.example config\app_local.php
   ```

2. Edite `config/app_local.php` e ajuste:
   - **Banco**: use um PostgreSQL/MySQL **local** (ou uma cópia de teste).
   - **Security.salt**: qualquer string longa para teste (ex.: `meu-salt-de-teste-local-123`).
   - **debug**: deixe `true` para ver erros na tela.

O `app_local.php` **sobrescreve** apenas o que você definir; o resto continua vindo do `app.php`. Esse arquivo já está no `.gitignore`, então não será commitado.

### Opção B: Variáveis de ambiente (se o servidor já usa .env)

Se você usar um carregador de `.env` (por exemplo, no Apache/Nginx ou em algum script), copie `.env.example` para `.env` e preencha com valores **locais**. O `.env` também está no `.gitignore`.

---

## 2. Banco de dados local

- **PostgreSQL**: crie um banco de teste, por exemplo `pgm_local`, e use no `app_local.php`:
  ```sql
  CREATE DATABASE pgm_local;
  ```
  Depois rode as migrations ou importe um dump de estrutura (sem dados sensíveis de produção).

- Se não quiser instalar PostgreSQL: use o mesmo usuário/senha do seu ambiente de desenvolvimento ou um MySQL local, e no `app_local.php` altere `driver` e os dados de conexão conforme o seu `app.php` (driver Postgres/MySQL).

---

## 3. Subir o servidor PHP embutido

No diretório do projeto:

```bash
cd c:\Portal_New\htdocs\portal
php -S localhost:8765 -t webroot
```

Acesse no navegador: **http://localhost:8765**

(Se a porta 8765 estiver em uso, use outra, por exemplo `php -S localhost:9080 -t webroot`.)

---

## 4. O que testar

- **Login**: tela centralizada, fluxo de login e redirecionamento.
- **Tickets**: abrir um ticket, comentários, movimentações, modais (homologação, faturas, cancelamento).
- **Ordens de serviço**: adicionar ordem, carrinho, editar, imprimir, ações em lote.
- **Notificações**: ver se a listagem carrega sem erro.
- **Segurança**: tentar acessar uma URL de ticket de outra empresa (deve retornar 404/erro).

---

## 5. Parar o servidor

No terminal onde rodou `php -S ...`, use `Ctrl+C`.

---

## Resumo

| Objetivo              | Ação                                                                 |
|-----------------------|----------------------------------------------------------------------|
| Não usar produção     | Usar só `app_local.php` (e/ou `.env`) com banco e config **local**. |
| Rodar na sua máquina  | `php -S localhost:8765 -t webroot` e abrir http://localhost:8765.    |
| Não subir config real | `app_local.php` e `.env` estão no `.gitignore`.                     |

Assim você testa tudo (incluindo as correções de segurança, performance e UI) sem importar ou alterar o sistema em produção.
