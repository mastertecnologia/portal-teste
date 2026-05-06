# Permissões e Controle de Acesso

## Papéis (roles)

O módulo assume três papéis principais. Adapte aos papéis que já existem no seu sistema.

| Papel        | Slug         | Descrição                                          |
|--------------|--------------|----------------------------------------------------|
| Técnico      | `tecnico`    | Cria, edita e gerencia pareceres                   |
| Supervisor   | `supervisor` | Revisa, aprova e rejeita pareceres                 |
| Admin        | `admin`      | Tudo + gerencia catálogos, templates e configurações |

> Se o seu sistema usa um campo diferente para identificar o papel (ex: `users.role`, `users.is_admin`, `roles_users.role_id`), adapte a verificação nos controllers.

## Matriz de permissões

### Pareceres

| Ação                              | Técnico | Supervisor | Admin |
|-----------------------------------|---------|------------|-------|
| Listar pareceres da empresa       | ✓       | ✓          | ✓     |
| Criar parecer                     | ✓       | ✓          | ✓     |
| Editar parecer próprio (rascunho) | ✓       | ✓          | ✓     |
| Editar parecer de outro técnico   | ✗       | ✓          | ✓     |
| Excluir parecer próprio (rascunho)| ✓       | ✓          | ✓     |
| Excluir parecer concluído/enviado | ✗       | ✗          | ✓     |
| Mudar status: rascunho → análise  | ✓       | ✓          | ✓     |
| Mudar status: análise → aprovado  | ✗       | ✓          | ✓     |
| Mudar status: análise → rascunho (rejeitar) | ✗ | ✓     | ✓     |
| Mudar status: aprovado → concluído| ✓       | ✓          | ✓     |
| Mudar status: concluído → enviado | ✓       | ✓          | ✓     |
| Gerar PDF                         | ✓       | ✓          | ✓     |
| Enviar por e-mail                 | ✓       | ✓          | ✓     |
| Duplicar parecer                  | ✓       | ✓          | ✓     |

### Catálogos e templates

| Ação                              | Técnico | Supervisor | Admin |
|-----------------------------------|---------|------------|-------|
| Listar peças e serviços           | ✓       | ✓          | ✓     |
| Adicionar peça personalizada (no parecer) | ✓ | ✓        | ✓     |
| Adicionar peça ao catálogo global | ✗       | ✓          | ✓     |
| Editar/excluir item de catálogo   | ✗       | ✗          | ✓     |
| Listar templates                  | ✓       | ✓          | ✓     |
| Criar/editar templates            | ✗       | ✗          | ✓     |

### Configurações da empresa

| Ação                              | Técnico | Supervisor | Admin |
|-----------------------------------|---------|------------|-------|
| Editar dados da empresa, logo     | ✗       | ✗          | ✓     |
| Editar carimbo padrão             | ✗       | ✗          | ✓     |
| Editar formato de numeração       | ✗       | ✗          | ✓     |
| Editar threshold de reparo        | ✗       | ✗          | ✓     |

## Implementação no CakePHP

### Opção A — Authorization Plugin (recomendado)

```bash
composer require cakephp/authorization
bin/cake plugin load Authorization
```

Crie uma policy:

```php
// src/Policy/LaudosParecerPolicy.php
<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\LaudosParecer;
use App\Model\Entity\User;
use Authorization\Policy\Result;

class LaudosParecerPolicy
{
    public function canView(User $user, LaudosParecer $parecer): bool
    {
        return $parecer->empresa_id === $user->empresa_id;
    }

    public function canEdit(User $user, LaudosParecer $parecer): Result
    {
        // empresa diferente
        if ($parecer->empresa_id !== $user->empresa_id) {
            return new Result(false, 'Outra empresa');
        }
        // já não pode editar pelo status
        if (!$parecer->pode_editar) {
            return new Result(false, 'Status não permite edição');
        }
        // Admin pode tudo
        if ($user->role === 'admin') {
            return new Result(true);
        }
        // Supervisor pode editar qualquer um da empresa
        if ($user->role === 'supervisor') {
            return new Result(true);
        }
        // Técnico só edita próprios
        if ($user->role === 'tecnico' && $parecer->tecnico_user_id === $user->id) {
            return new Result(true);
        }
        return new Result(false, 'Apenas o autor ou supervisor pode editar');
    }

    public function canDelete(User $user, LaudosParecer $parecer): bool
    {
        if ($user->role === 'admin') return true;
        // Outros papéis: só rascunho próprio
        return $parecer->status === 'rascunho'
            && $parecer->tecnico_user_id === $user->id;
    }

    public function canChangeStatus(User $user, LaudosParecer $parecer, string $newStatus): bool
    {
        $current = $parecer->status;

        // Admin pode tudo
        if ($user->role === 'admin') return true;

        // Aprovação/rejeição requer supervisor
        if (
            ($current === 'em_analise' && in_array($newStatus, ['aprovado', 'rascunho']))
        ) {
            return $user->role === 'supervisor';
        }

        // Outras transições liberadas para técnico
        return true;
    }
}
```

E nos controllers:

```php
// LaudosPareceresController::edit()
$parecer = $this->LaudosPareceres->get($id);
$this->Authorization->authorize($parecer, 'edit');
// ... resto da lógica
```

### Opção B — Verificação inline (mais simples)

Se o sistema atual não usa o Authorization plugin, faça verificação manual em cada action:

```php
protected function checkRole(string ...$allowed): void
{
    $user = $this->Authentication->getIdentity();
    if (!in_array($user->role, $allowed)) {
        throw new \Cake\Http\Exception\ForbiddenException(
            'Permissão insuficiente. Necessário: ' . implode(', ', $allowed)
        );
    }
}

public function changeStatus($id)
{
    $parecer = $this->LaudosPareceres->get($id);
    $newStatus = $this->request->getData('status');

    if ($parecer->status === 'em_analise' && $newStatus === 'aprovado') {
        $this->checkRole('supervisor', 'admin');
    }
    // ... resto
}
```

## Implementação no React

Apenas oculte botões/ações que o usuário não pode executar — o backend é a fonte da verdade.

```jsx
// hooks/usePermissions.js
export function usePermissions(currentUser, parecer) {
  const isAuthor = parecer?.tecnico_user_id === currentUser?.id;
  const role = currentUser?.role;

  return {
    canEdit: parecer?.pode_editar && (
      role === 'admin' || role === 'supervisor' || isAuthor
    ),
    canDelete: parecer?.status === 'rascunho' && (
      role === 'admin' || isAuthor
    ),
    canApprove: role === 'supervisor' || role === 'admin',
    canManageCatalog: role === 'admin',
  };
}
```

E nos componentes:

```jsx
const perms = usePermissions(currentUser, parecer);
{perms.canApprove && (
  <button onClick={handleApprove}>Aprovar</button>
)}
```

## Auditoria

Toda ação que modifica o parecer é registrada na tabela `laudos_historico` automaticamente pelos controllers, incluindo:

- Identificação do usuário (`user_id`)
- Snapshot do nome do usuário (caso seja deletado depois — `user_name_snapshot`)
- Tipo de ação (`action`)
- Detalhes em JSONB (`details`)
- Timestamp (`created`)

A tabela é **append-only** — não permite UPDATE nem DELETE. Para reforçar isso no PostgreSQL, adicione uma trigger:

```sql
CREATE OR REPLACE FUNCTION laudos_historico_imutavel()
RETURNS TRIGGER AS $$
BEGIN
    RAISE EXCEPTION 'Histórico é imutável';
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_laudos_historico_no_update
    BEFORE UPDATE OR DELETE ON laudos_historico
    FOR EACH ROW EXECUTE FUNCTION laudos_historico_imutavel();
```

## Acesso multi-empresa

Se o sistema tiver múltiplas empresas (multi-tenant), o filtro por `empresa_id` deve estar em **todas** as queries — já está implementado nos métodos `findFiltered` e nas verificações de `checkEmpresaAccess`.

Se for single-tenant, mantenha `empresa_id = 1` fixo e o controle ainda funciona corretamente.
