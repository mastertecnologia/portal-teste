# Como configurar o Integrador no Windows (Grid)

Onde encontrar e onde preencher as informações para o Integrador chamar o Portal (10.0.2.25).

---

## 1. Valores prontos para usar

Use estes valores ao achar a tela ou arquivo de configuração:

| O que configurar | Valor para copiar |
|------------------|-------------------|
| **URL do Portal** | `https://portal.pgm.inf.br/portal` |
| **URL de envio de produtos** | `https://portal.pgm.inf.br/portal/produtos/add-api` |
| **Método (envio de produtos)** | `POST` |
| **Header empresa** | `1` (Master Tecnologia) ou `2` (PGM) – conforme a empresa que o Integrador representa) |
| **Header token** | Token completo da empresa no Portal (veja item 2 abaixo como obter) |

Se não usar HTTPS/domínio, use em vez disso:
- URL do Portal: `http://10.0.2.25/portal`
- URL produtos: `http://10.0.2.25/portal/produtos/add-api`

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

### 3.1 Pela tela do Integrador

1. Abra o **Integrador GridERP + Web** no Windows (10.0.2.7).
2. Na janela principal costuma haver um botão ou ícone **Configurações** (canto superior direito na imagem que você enviou). Clique nele.
3. Procure abas ou opções como:
   - **Web / Portal / API / Integração**
   - **URL do Portal** ou **URL do sistema web**
   - **Envio de produtos** ou **Produtos**
   - Campos para **Empresa**, **Token**, **Chave** ou **API Key**
4. Onde houver:
   - **URL / Endereço / Base URL:** cole `https://portal.pgm.inf.br/portal` (ou `http://10.0.2.25/portal`).
   - **URL de produtos (se for separada):** cole `https://portal.pgm.inf.br/portal/produtos/add-api`.
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
   - `https://portal.pgm.inf.br/portal` ou `http://10.0.2.25/portal`
   - E, se houver um campo de URL de produtos: `https://portal.pgm.inf.br/portal/produtos/add-api`
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
| **Arquivo de config no Windows** | Procurar .ini/.config/.xml/.json na pasta do Integrador ou em AppData; substituir URL por `https://portal.pgm.inf.br/portal` (ou `http://10.0.2.25/portal`) e preencher empresa e token. |
| **Token** | Obter com `psql ... SELECT id, nomefantasia, token FROM empresas` ou no Portal em Empresas → Exibir (token). |

Se você disser o nome exato da tela de configuração do Integrador (ou enviar um print), dá para indicar campo a campo onde colar cada valor.
