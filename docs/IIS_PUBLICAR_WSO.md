# Como publicar os arquivos .wso no IIS (Windows)

Os serviços SOAP (`.wso`) que o **Portal** chama no **ERP** precisam estar publicados no IIS. Abaixo o passo a passo com base no seu ambiente: **Default Web Site**, HTTP na **porta 85**, e aplicação **WebGridPGM**.

---

## 1. Onde colocar os arquivos .wso

Os arquivos `.wso` (e qualquer DLL ou pasta que vier com eles) devem ficar na **pasta física** do site/aplicação que vai hospedar o serviço.

- **Default Web Site** → pasta padrão: `C:\inetpub\wwwroot`
- **WebGridPGM** (aplicação dentro do Default Web Site) → pasta: a que está configurada em **Basic Settings** do WebGridPGM (clique com o botão direito em **WebGridPGM** → **Manage Application** → **Advanced Settings** ou **Basic Settings** e veja **Physical path**).

**Recomendação:** usar a **mesma aplicação** onde já roda o Grid (por exemplo **WebGridPGM**), para manter configuração e pool já existentes.

1. No IIS Manager, clique com o botão direito em **WebGridPGM** (ou no site que você escolher).
2. Clique em **Explore** (ou **Abrir** no caminho físico) para abrir a pasta no Explorer.
3. Copie os arquivos `.wso` para dentro dessa pasta (ou para uma subpasta, ex.: `C:\...\WebGridPGM\Services\`).

Exemplo de estrutura após copiar:

```
...\WebGridPGM\
  WsProdutos.wso
  WSPGMPessoas.wso
  WSPGMContratos.wso
  (outros arquivos já existentes do WebGridPGM)
```

Se os `.wso` precisarem de subpastas ou DLLs (conforme a documentação do Grid), mantenha a mesma estrutura.

---

## 2. Configurar o IIS para atender .wso

O IIS precisa **entregar** os `.wso` ao programa que os executa (ASP.NET, WCF ou o runtime do Grid).

### 2.1 Se .wso for tratado como arquivo estático (raro)

- Não é necessário mapeamento especial; o IIS serve o arquivo.
- Nesse caso, o endereço seria só para baixar o arquivo, não para chamar SOAP. Para **serviços SOAP**, normalmente é necessário um **handler** (abaixo).

### 2.2 Se .wso for serviço (SOAP/WCF/ASMX) – caso mais comum

É preciso existir um **Handler Mapping** para a extensão `.wso` na aplicação (ou no site).

1. No IIS Manager, selecione a aplicação **WebGridPGM** (ou o site onde colocou os `.wso`).
2. Abra **Handler Mappings** (duplo clique).
3. Verifique se já existe alguma entrada para `.wso`:
   - Se **existir**, anote o “Handler” (ex.: um módulo do Grid ou ASP.NET). Não altere nada, a menos que esteja quebrado.
   - Se **não existir**, pode ser que o **WebGridPGM** já use um handler genérico (ex.: `*` ou um wildcard) que processa todas as requisições e encaminha para o framework do Grid. Nesse caso, os `.wso` podem ser atendidos por esse handler.
4. Se a documentação do **Grid Sistemas** ou do ERP disser para criar um mapeamento específico para `.wso`, use **Add Module Mapping** (ou “Add Script Map” em versões antigas):
   - **Request path:** `*.wso`
   - **Module:** o indicado pela documentação (ex.: `IsapiModule` ou o módulo do Grid).
   - **Executable:** só se for ISAPI; caso contrário deixe em branco e escolha o módulo correto.

Como o projeto usa **WebGridPGM** (Grid Sistemas), o ideal é seguir a documentação deles para “publicar serviços” ou “configurar .wso no IIS”. Se tiver um instalador ou pacote do Grid que já cria o site/aplicação, ele pode criar esses mapeamentos automaticamente.

---

## 3. URL que o Portal vai usar (URL ERP)

O Portal monta as chamadas assim: **`{urlerp}` + nome do serviço + `?wsdl`**.

Pelas suas imagens:

- Site: **Default Web Site**
- HTTP na **porta 85**
- Aplicação: **WebGridPGM**

Então a **URL base do ERP** a ser cadastrada no Portal (campo **URL ERP** da empresa) deve ser algo como:

- Se os `.wso` estão na **raiz do WebGridPGM**:  
  `http://10.0.2.7:85/WebGridPGM/` (Grid em 10.0.2.7 / ECS-MASTER)  
  (com barra no final)
- Se estiverem em uma subpasta, por exemplo `Services`:  
  `http://10.0.2.7:85/WebGridPGM/Services/`

No Portal, em **Empresas** → editar a empresa → **URL ERP**, coloque exatamente essa base. O Portal vai chamar, por exemplo:

- `http://10.0.2.7:85/WebGridPGM/WsProdutos.wso?wsdl`
- `http://10.0.2.7:85/WebGridPGM/WSPGMPessoas.wso?wsdl`
- `http://10.0.2.7:85/WebGridPGM/WSPGMContratos.wso?wsdl`

(ou com `/Services/` no meio, se você tiver colocado os `.wso` em uma subpasta **Services**.)

---

## 4. Testar se o serviço está acessível

No próprio servidor Windows (ou de um PC na mesma rede):

1. Abra o navegador e acesse:  
   `http://10.0.2.7:85/WebGridPGM/WsProdutos.wso?wsdl`
   (com Portal em outro servidor, use o IP do Grid: 10.0.2.7).
2. Se o serviço estiver publicado corretamente, deve aparecer o **XML do WSDL** (conteúdo em texto com tags `<wsdl:...>`).
3. Se aparecer 404, verifique:
   - Os arquivos estão na pasta física correta (passo 1).
   - O Handler Mapping para `.wso` (ou o handler genérico do WebGridPGM) está configurado (passo 2).
   - A aplicação **WebGridPGM** está iniciada (no IIS, clique com o botão direito em **WebGridPGM** → **Manage Application** → **Start**).
4. Se aparecer 500 ou erro do tipo “Handler not found”, a documentação do Grid/ERP para publicar serviços no IIS costuma explicar qual módulo ou extensão usar para `.wso`.

---

## 5. Resumo rápido

| Etapa | Ação |
|-------|------|
| 1 | Abrir a pasta física do **WebGridPGM** (ou do site escolhido) via IIS → **Explore**. |
| 2 | Copiar os arquivos **WsProdutos.wso**, **WSPGMPessoas.wso**, **WSPGMContratos.wso** (e pastas/DLLs necessárias) para essa pasta (ou subpasta). |
| 3 | Conferir em **Handler Mappings** se `.wso` já está mapeado ou se o handler genérico do WebGridPGM atende esses arquivos; seguir documentação do Grid se precisar criar mapeamento. |
| 4 | No Portal (10.0.2.25), em **Empresas** → **URL ERP**, usar: `http://10.0.2.7:85/WebGridPGM/` (com barra no final). |
| 5 | Testar: `http://10.0.2.7:85/WebGridPGM/WsProdutos.wso?wsdl` (do Portal ou de qualquer máquina que acesse o Grid). |

Se os `.wso` forem apenas **definições** (WSDL/descrição) e o serviço real for outro (ex.: `.asmx` ou `.svc`), a documentação do Grid/ERP deve indicar qual URL e qual extensão usar. Nesse caso, o “subir” seria: colocar os arquivos no caminho certo e configurar o handler que responde por essa URL (que pode ser .asmx/.svc e não .wso).
