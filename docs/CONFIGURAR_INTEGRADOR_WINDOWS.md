# Como configurar o Integrador no Windows (Grid)

Onde encontrar e onde preencher as informações para o Integrador chamar o Portal (10.0.2.25).

---

## 0. Use HTTPS na URL do Portal (evita 301 e “Retorno vazio”)

**Recomendado (Grid → Portal):** configure sempre **`https://portal.pgm.inf.br/portal`** como base do Portal.

Se o Integrador usar **`http://10.0.2.25/portal`** ou **`http://...` na porta 80**, o Apache do Portal costuma responder **HTTP 301** redirecionando para HTTPS. A resposta vem em **HTML**, não em JSON.

- Programas antigos (ex.: **PGMModeloEnviaClientes**, Integrador Grid) **muitas vezes não seguem redirect em POST** (ou tratam 301 como erro).
- Na tela aparece erro ao cadastrar e **“Retorno:” vazio**; no Fiddler o **JSON fica vazio** porque o corpo é a página “Moved Permanently”.

**Correção:** altere a URL no Grid para **`https://portal.pgm.inf.br/portal`** (tela de configuração ou arquivos `.ini` / `.config` / `.xml` / `.json` — ver secções 3.1 e 3.2).

**Alternativa no servidor:** se for obrigatório manter HTTP na porta 80, veja `docs/EVITAR_301_INTEGRADOR.md` (Apache sem redirect para `/portal`).

---

## 1. Valores prontos para usar

Use estes valores ao achar a tela ou arquivo de configuração:

| O que configurar | Valor para copiar |
|------------------|-------------------|
| **URL do Portal (base)** | `https://portal.pgm.inf.br/portal` |
| **URL de envio de produtos** (se pedir URL completa) | `https://portal.pgm.inf.br/portal/produtos/add-api` |
| **URL de envio de clientes** (se pedir URL completa) | `https://portal.pgm.inf.br/portal/clientes/addAPI` |
| **Método (envio de produtos / clientes)** | `POST` |
| **Header empresa** | `1` (Master Tecnologia) ou `2` (PGM) – conforme a empresa que o Integrador representa) |
| **Header token** | Token completo da empresa no Portal (veja item 2 abaixo como obter) |

**Evite** `http://10.0.2.25/portal` para chamadas da API **a menos** que o Apache esteja configurado para **não** redirecionar `/portal` (ver `EVITAR_301_INTEGRADOR.md`). Caso contrário ocorre **301** e a integração quebra.

---

## 2. Como obter o token completo (no PostgreSQL ou no Portal)

No servidor do banco (ou de um PC com acesso ao PostgreSQL):

```bash
psql -h 10.0.2.23 -U postgres -d pgm -t -A -c "SELECT id, nomefantasia, token FROM empresas;"
```

Saída esperada (exemplo): duas colunas separadas por `|`. A terceira coluna é o token completo. Copie o token da empresa que você vai usar no Integrador (id 1 ou 2).

**Ou** no Portal (navegador): **Empresas** → na lista, na coluna Token clique em **Exibir** → copie o valor que aparecer.

---

## 3. Onde fica o Integrador e qual arquivo modificar

### 3.0 Como achar a pasta do Integrador no Windows

1. **Pelo atalho:** No menu Iniciar ou na área de trabalho, localize o atalho do **Integrador GridERP + Web** → botão direito → **Abrir local do arquivo** (ou **Propriedades** e veja **Destino** / **Iniciar em**). A pasta que abrir (ou a de “Iniciar em”) é a pasta do programa.
2. **Pastas comuns:** O Integrador costuma estar em algo como:
   - `C:\Program Files (x86)\Grid\` ou `C:\Program Files\Grid\`
   - `C:\Grid\` ou na pasta de instalação do ERP (ex.: `C:\...\WebOrder_17_1` ou similar)
3. **Procurar no disco:** No Explorador de Arquivos, em `C:\`, use a busca por **Integrador** ou **Grid*Integrador*.exe** e abra a pasta do executável encontrado.
4. **Arquivos para procurar:** Dentro dessa pasta (e em `C:\Users\<seu_usuario>\AppData\Local\` ou `AppData\Roaming\` em pastas com nome “Grid” ou “Integrador”), procure por:
   - `*.ini`, `*.config`, `*.xml`, `*.json`, `*.cfg`
   - Nomes como: `Integrador.config`, `app.config`, `config.ini`, `settings.json`, `Web.config`
5. Abra esses arquivos com o **Bloco de Notas** e procure por: `localhost`, `portal`, `url`, `Url`, `token`, `empresa`, `api`. Onde estiver a URL do portal ou do envio de produtos, troque para os valores indicados na seção 1 (e empresa/token conforme o banco). Salve e **reinicie o Integrador**.

Se não achar nenhum arquivo de texto com isso, a configuração pode estar **só na tela** do programa (ícone Configurações) ou no **banco de dados do próprio Grid** no Windows; nesse caso é preciso usar a tela ou a documentação/suporte da Grid.

### 3.0.1 PGMModeloEnviaClientes (envio de clientes)

O executável **PGMModeloEnviaClientes** pode ter configuração **separada** do Integrador GridERP + Web.

1. Localize a pasta do `.exe` (atalho → **Abrir local do arquivo**).
2. Em **`AppData\Local`** e **`AppData\Roaming`** do utilizador que roda o programa, procure pastas **Grid** / **PGM** e ficheiros **`.ini`**, **`.config`**, **`.xml`**, **`.json`**.
3. Substitua qualquer URL **`http://10.0.2.25/...`** ou **`http://portal...`** por **`https://portal.pgm.inf.br/portal`** (ou URL completa **`https://portal.pgm.inf.br/portal/clientes/addAPI`** se o campo for o endpoint completo).
4. Reinicie o programa e confirme no Fiddler: resposta **201** com JSON, não **301** com HTML.

### 3.1 Pela tela do Integrador

1. Abra o **Integrador GridERP + Web** no Windows (10.0.2.7).
2. Na janela principal costuma haver um botão ou ícone **Configurações** (canto superior direito na imagem que você enviou). Clique nele.
3. Procure abas ou opções como:
   - **Web / Portal / API / Integração**
   - **URL do Portal** ou **URL do sistema web**
   - **Envio de produtos** ou **Produtos**
   - Campos para **Empresa**, **Token**, **Chave** ou **API Key**
4. Onde houver:
   - **URL / Endereço / Base URL:** cole **`https://portal.pgm.inf.br/portal`** (preferencial; evita 301).
   - **URL de produtos (se for separada):** cole `https://portal.pgm.inf.br/portal/produtos/add-api`.
   - **URL de clientes / portal web (se for separada):** use a mesma base HTTPS ou `https://portal.pgm.inf.br/portal/clientes/addAPI` conforme o programa peça.
   - **Método:** selecione **POST** (não GET).
   - **Empresa / ID empresa:** coloque `1` ou `2`.
   - **Token / Chave / Senha API:** cole o token completo que você obteve no passo 2.
5. Salve e feche as configurações.

Se a tela tiver nomes diferentes (ex.: “Servidor Web”, “Conexão Portal”), use os mesmos valores: URL = Portal, empresa = id, token = token do banco.

### 3.2 Por arquivo de configuração (se não achar na tela)

O Integrador pode guardar a URL e o token em um arquivo .ini, .config, .xml ou .json na pasta do programa ou em AppData.

1. **Pasta de instalação** (ex.: `C:\Program Files (x86)\Grid\Integrador` ou similar). Procure por:
   - `*.ini`, `*.config`, `*.xml`, `*.json`
   - Arquivos com nome tipo: `Integrador*.config`, `appsettings*.json`, `config*.ini`
2. **AppData do usuário que roda o Integrador:**
   - `C:\Users\<usuário>\AppData\Local\` ou `AppData\Roaming\`
   - Procure pasta com nome do Integrador ou “Grid” e dentro arquivos de config.
3. Abra os arquivos com Bloco de Notas e procure por:
   - `localhost`, `127.0.0.1`, `portal`, `url`, `Url`, `token`, `Token`, `empresa`, `api`
4. Onde aparecer URL do portal/web, troque para:
   - **`https://portal.pgm.inf.br/portal`** (recomendado)
   - Se houver URL de produtos: `https://portal.pgm.inf.br/portal/produtos/add-api`
   - Procure também `http://10.0.2.25` e substitua pela URL **https** acima
5. Onde aparecer empresa/id: use `1` ou `2`. Onde aparecer token/chave: use o token completo do banco.
6. Salve o arquivo e reinicie o Integrador.

### 3.3 Pelo registro do Windows (só se for indicado pela Grid)

Alguns programas guardam config no Registro. Só altere se você souber usar o `regedit` ou se a documentação do Grid indicar.

- `Win + R` → `regedit` → Enter.
- Procure por chaves com nome do Integrador ou “Grid” (ex.: `HKEY_CURRENT_USER\Software\Grid` ou similar).
- Se achar valores como URL ou Token, anote o caminho e o nome dos valores; altere apenas se tiver certeza (e faça backup da chave antes).

---

## 4. Resumo rápido

| Onde | O que fazer |
|------|-------------|
| **Configurações do Integrador (tela)** | Abrir **Configurações** → preencher URL do Portal, URL de produtos (se houver), método POST, empresa (1 ou 2), token (copiado do banco/Portal). |
| **Arquivo de config no Windows** | Procurar .ini/.config/.xml/.json na pasta do Integrador, **PGMModeloEnviaClientes** ou em AppData; substituir `http://10.0.2.25` / HTTP por **`https://portal.pgm.inf.br/portal`**; preencher empresa e token. |
| **Token** | Obter com `psql ... SELECT id, nomefantasia, token FROM empresas` ou no Portal em Empresas → Exibir (token). |

Se você disser o nome exato da tela de configuração do Integrador (ou enviar um print), dá para indicar campo a campo onde colar cada valor.
