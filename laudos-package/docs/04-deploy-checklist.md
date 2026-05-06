# Checklist de Deploy — Módulo Laudos / Parecer Técnico

Use este checklist para validar a implementação após o Cursor concluir.

## ☐ 1. Pré-requisitos do servidor

- [ ] PHP 8.1 ou superior
- [ ] Extensões PHP: `pdo`, `pdo_pgsql`, `gd` ou `imagick`, `mbstring`, `xml`, `curl`, `zip`
- [ ] PostgreSQL 13+
- [ ] Composer atualizado
- [ ] Node.js 18+ e npm/yarn (para o frontend)
- [ ] Pasta `webroot/uploads/` com permissão de escrita para o usuário do PHP

```bash
mkdir -p webroot/uploads/laudos
chmod -R 755 webroot/uploads
chown -R www-data:www-data webroot/uploads  # ajuste para seu usuário
```

## ☐ 2. Banco de dados

- [ ] Backup do banco antes de qualquer alteração
- [ ] Rodar `database/schema.sql` no PostgreSQL
- [ ] Verificar que todas as tabelas foram criadas:

```sql
SELECT table_name FROM information_schema.tables
WHERE table_schema = 'public' AND table_name LIKE 'laudos_%'
ORDER BY table_name;
-- Deve retornar 11 tabelas
```

- [ ] Rodar `database/seeds.sql` para popular catálogos e templates
- [ ] Verificar dados seed:

```sql
SELECT COUNT(*) FROM laudos_empresas;          -- 1
SELECT COUNT(*) FROM laudos_catalogo_pecas;     -- 26
SELECT COUNT(*) FROM laudos_catalogo_servicos;  -- 15
SELECT COUNT(*) FROM laudos_templates;          -- 13
```

- [ ] Testar função de numeração:

```sql
SELECT laudos_proximo_numero(1);
-- Deve retornar algo como: 0001/2026
```

- [ ] Ajustar FK `requester_client_id` para apontar para sua tabela de clientes (se diferente de `clientes`)

## ☐ 3. Composer e dependências PHP

```bash
composer require dompdf/dompdf
composer require cakephp/authorization  # opcional, se for usar policies
composer dump-autoload
```

- [ ] Verificar instalação:
  ```bash
  composer show dompdf/dompdf
  ```

## ☐ 4. Configuração CakePHP

### Cache (para BrasilAPI/ViaCEP)
Em `config/app.php`:

```php
'Cache' => [
    'default' => [
        'className' => FileEngine::class,
        'duration' => '+1 day',
        'path' => CACHE,
    ],
],
```

### E-mail (Mailer)
Em `config/app.php` ou `config/app_local.php`:

```php
'EmailTransport' => [
    'default' => [
        'className' => SmtpTransport::class,
        'host' => 'smtp.seudominio.com.br',
        'port' => 587,
        'timeout' => 30,
        'username' => 'pareceres@seudominio.com.br',
        'password' => '...',
        'tls' => true,
    ],
],
'Email' => [
    'default' => [
        'transport' => 'default',
        'from' => ['pareceres@seudominio.com.br' => 'Pareceres Técnicos'],
    ],
],
```

### Permissões
- [ ] `Authentication->allowUnauthenticated(['publica'])` configurado para a rota `/validar/{hash}` no AppController OU no ValidacaoController
- [ ] CSRF/Form middlewares configurados para aceitar JSON nos endpoints da API

## ☐ 5. Rotas

- [ ] Conteúdo de `backend/routes.php` adicionado ao `config/routes.php`
- [ ] Testar com curl (após login):

```bash
curl -b cookies.txt http://localhost:8765/api/laudos/pareceres
# Deve retornar: {"success":true,"data":[],"pagination":{...}}
```

- [ ] Testar rota pública:

```bash
curl http://localhost:8765/validar/QUALQUERHASH
# Deve retornar 404 (parecer não localizado), não 401/403
```

## ☐ 6. Frontend

- [ ] Arquivos copiados para os locais corretos no projeto React
- [ ] Rotas adicionadas ao React Router:
  - `/laudos/pareceres` → ParecerListPage
  - `/laudos/pareceres/:id` → ParecerEditPage
- [ ] Item de menu "Laudos > Parecer Técnico" visível na sidebar
- [ ] Build do frontend sem erros: `npm run build`
- [ ] axios configurado com baseURL e cookies/credentials

## ☐ 7. Testes funcionais

Login no sistema e teste cada item:

### Listagem
- [ ] Acessar `/laudos/pareceres` carrega sem erros
- [ ] Lista vazia mostra mensagem amigável
- [ ] Filtro por status funciona
- [ ] Busca por título/CNPJ filtra resultados

### Criação
- [ ] Botão "Novo Parecer" cria parecer com número `0001/2026` (ou próximo da sequência)
- [ ] Redireciona para tela de edição
- [ ] Status inicial é "Rascunho"
- [ ] Auto-save dispara após digitar (indicador "Salvando..." → "Tudo salvo")

### Cliente
- [ ] Buscar cliente existente por nome/CNPJ retorna resultados
- [ ] Selecionar cliente preenche os campos do requerente
- [ ] Consultar CNPJ via BrasilAPI preenche dados não preenchidos
- [ ] Consultar CEP via ViaCEP preenche endereço
- [ ] Validação de CNPJ inválido mostra erro

### Equipamento
- [ ] Adicionar equipamento cria card vazio
- [ ] Selecionar template de diagnóstico preenche campo
- [ ] Adicionar peça do catálogo preenche valores
- [ ] Adicionar peça personalizada funciona
- [ ] Adicionar serviço do catálogo preenche valores
- [ ] Subtotal e total geral atualizam em tempo real

### Imagens
- [ ] Upload de imagem grande (>2MB) é comprimida
- [ ] Indicador de redução aparece ("-72% (240KB)")
- [ ] Imagem aparece em thumbnail
- [ ] Botão de remover funciona

### Status / Workflow
- [ ] Mudar status para "Em análise" funciona
- [ ] Tentar editar parecer "Concluído" é bloqueado
- [ ] Histórico registra mudança de status

### PDF
- [ ] Botão "PDF" abre arquivo em nova aba
- [ ] PDF contém dados do requerente, equipamentos, peças, serviços
- [ ] Logo da empresa aparece no cabeçalho
- [ ] Assinatura e carimbo aparecem
- [ ] QR Code de validação aparece no rodapé
- [ ] Numeração de páginas funciona

### E-mail
- [ ] Botão "Enviar" abre modal
- [ ] Destinatário pré-preenchido com email do cliente
- [ ] Enviar dispara e-mail (verificar inbox)
- [ ] Status muda para "Enviado" após envio
- [ ] PDF anexado ao e-mail

### Validação pública
- [ ] Copiar URL de validação `https://seudominio.com.br/validar/{HASH}` e abrir em modo anônimo
- [ ] Página carrega sem login
- [ ] Mostra: número do parecer, data, emissor, cliente
- [ ] NÃO mostra: valores, diagnóstico técnico

### Histórico
- [ ] Botão "Histórico" abre modal
- [ ] Lista todos os eventos do parecer
- [ ] Cada evento tem usuário, ação e timestamp

### Anexos
- [ ] Upload de PDF/DOCX como anexo funciona
- [ ] Botão de download do anexo funciona
- [ ] Remover anexo funciona

### Duplicação
- [ ] Duplicar parecer cria cópia com novo número
- [ ] Status da cópia é "Rascunho"
- [ ] Equipamentos, peças e serviços são copiados (mas fotos não)

## ☐ 8. Performance

- [ ] Listagem com 100 pareceres carrega em < 2s
- [ ] Edição de parecer com 5 equipamentos carrega em < 3s
- [ ] Upload de imagem de 10MB processa em < 10s
- [ ] Geração de PDF de parecer com 5 equipamentos completa em < 8s

Se algum exceder o limite:
- Verifique índices do PostgreSQL (já incluídos no schema.sql)
- Verifique cache de CNPJ/CEP (deve estar funcionando)
- Verifique tamanho das imagens (compressão deve estar ativa)

## ☐ 9. Segurança

- [ ] HTTPS configurado em produção
- [ ] Cookies com `Secure` e `HttpOnly`
- [ ] CSRF protection ativa nas rotas mutáveis
- [ ] Pasta `uploads/` não permite execução de PHP (config Apache/Nginx)
- [ ] Validação de mime-type real nos uploads (não confiar só na extensão)
- [ ] Limite de tamanho de upload no PHP (`upload_max_filesize`, `post_max_size`)
- [ ] Rate limiting nos endpoints públicos (`/validar/*`, `/api/util/*`)

Configuração nginx para `uploads/`:

```nginx
location /uploads/ {
    location ~ \.php$ {
        deny all;
    }
}
```

## ☐ 10. Backup

- [ ] Backup automatizado das tabelas `laudos_*`
- [ ] Backup automatizado da pasta `webroot/uploads/laudos/`
- [ ] Política de retenção definida

```bash
# Exemplo de backup diário (cron)
pg_dump -t 'laudos_*' minhabase > /backups/laudos_$(date +%Y%m%d).sql
tar -czf /backups/laudos_uploads_$(date +%Y%m%d).tar.gz webroot/uploads/laudos/
```

## ☐ 11. Monitoramento

- [ ] Logs de erro do CakePHP visíveis (`logs/error.log`)
- [ ] Alertas para falhas em envio de e-mail
- [ ] Métrica de uso (qtde de pareceres por mês)

## ☐ 12. Documentação

- [ ] Manual do usuário publicado (interno)
- [ ] Treinamento dos técnicos realizado
- [ ] Contato de suporte definido

## ☐ 13. Pós-deploy

- [ ] Criar primeiro parecer real em produção (com dados verdadeiros)
- [ ] Validar PDF gerado em produção
- [ ] Confirmar e-mail funcionando em produção
- [ ] Monitorar logs por 7 dias
- [ ] Coletar feedback dos usuários e abrir backlog de melhorias

---

## Troubleshooting

### Auto-save não dispara
- Verifique no console do browser se há erros 401 (sessão expirada)
- Verifique se o axios está enviando cookies (`withCredentials: true`)

### Upload de imagem falha
- Verifique permissões de `webroot/uploads/laudos/`
- Verifique `upload_max_filesize` e `post_max_size` no PHP
- Verifique tamanho do arquivo após compressão (deve ser < limite do PHP)

### PDF gerado em branco
- Verifique se dompdf está instalado: `composer show dompdf/dompdf`
- Verifique se a view `templates/Laudos/Pdf/parecer_pdf.php` existe
- Habilite logs do dompdf: `$options->set('debugCss', true)`

### E-mail não envia
- Teste SMTP manualmente: `bin/cake mailer test`
- Verifique credenciais em `app_local.php`
- Verifique firewall/porta 587 aberta no servidor

### Numeração não incrementa
- Verifique se função `laudos_proximo_numero()` existe: `\df laudos_proximo_numero` no psql
- Verifique sequência `laudos_contadores` para o ano atual

### Validação pública retorna 401
- Verifique `Authentication->allowUnauthenticated(['publica'])` no controller
- Verifique se a rota está fora do escopo `/api`
