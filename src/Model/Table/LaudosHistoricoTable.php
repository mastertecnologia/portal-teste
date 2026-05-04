<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Datasource\EntityInterface;

class LaudosHistoricoTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('laudos_historico');
        $this->setDisplayField('action');

        $this->belongsTo('LaudosPareceres', ['foreignKey' => 'parecer_id']);
        $this->belongsTo('Users', ['foreignKey' => 'user_id']);
    }

    /**
     * Registra um evento no histórico (append-only).
     */
    public function logEvent(int $parecerId, ?int $userId, ?string $userName, string $action, array $details = []): EntityInterface
    {
        $entity = $this->newEntity([
            'parecer_id' => $parecerId,
            'user_id' => $userId,
            'user_name_snapshot' => $userName,
            'action' => $action,
            'details' => !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
        ]);
        $this->saveOrFail($entity);
        return $entity;
    }
}
