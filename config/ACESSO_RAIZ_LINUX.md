# Acessar portal em portal.pgm.inf.br (sem /portal na URL)

Para que o portal abra em **portal.pgm.inf.br** (e não em portal.pgm.inf.br/portal/users/login), o **DocumentRoot** do VirtualHost deve apontar para a **pasta pública** do projeto (**public**), não para a raiz do projeto.

**Estrutura no Linux:** tudo separado; a única pasta pública é **public**.

---

## Apache (Linux)

O VirtualHost do domínio **portal.pgm.inf.br** deve usar **DocumentRoot = /var/www/portal/public**:

```apache
<VirtualHost *:80>
    ServerName portal.pgm.inf.br
    DocumentRoot /var/www/portal/public

    <Directory /var/www/portal/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/portal-error.log
    CustomLog ${APACHE_LOG_DIR}/portal-access.log combined
</VirtualHost>
```

No **.env** da raiz do projeto (`/var/www/portal/.env`):

```
WEBROOT_DIR=public
```

**Importante:** não use `DocumentRoot /var/www/portal` (raiz do projeto). Use sempre `DocumentRoot /var/www/portal/public`. Assim as URLs ficam `/users/login`, `/assets/...`, etc., sem o prefixo `/portal`.

---

## Nginx (Linux)

```nginx
server {
    listen 80;
    server_name portal.pgm.inf.br;
    root /var/www/portal/public;

    index index.php;
    location / {
        try_files $uri $uri/ /index.php?$args;
    }
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;  # ajuste a versão do PHP
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

No `.env`: `WEBROOT_DIR=public`.

---

## Rota padrão no CakePHP

No projeto, a rota **/** já está configurada para o controller **Users** (dashboard). Usuários não logados são redirecionados para o login pelo Auth. Ou seja, ao acessar **portal.pgm.inf.br/** você cai no login quando não estiver autenticado.

---

## Resumo

| Objetivo | O que fazer |
|----------|-------------|
| URL sem /portal | DocumentRoot = `/var/www/portal/public` e no `.env`: `WEBROOT_DIR=public` |
| Raiz (/) abre o portal | Manter como está; / vai para dashboard e o Auth redireciona para login |
| Links e assets corretos | CakePHP gera `/users/login`, `/assets/...` automaticamente quando o app é servido na raiz do domínio |
