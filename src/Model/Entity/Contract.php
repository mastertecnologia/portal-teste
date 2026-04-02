<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Contrato do módulo avançado (tabela contracts).
 */
class Contract extends Entity {

	protected $_accessible = [
		'id' => false,
		'idempresa' => true,
		'idcliente' => true,
		'unit_id' => true,
		'code' => true,
		'name' => true,
		'type' => true,
		'status' => true,
		'start_date' => true,
		'end_date' => true,
		'renewal_date' => true,
		'billing_cycle' => true,
		'monthly_value' => true,
		'sla_hours' => true,
		'included_hours' => true,
		'overage_hour_value' => true,
		'readjustment_index' => true,
		'readjustment_date' => true,
		'auto_renew' => true,
		'notes' => true,
		'template_id' => true,
		'valor_total' => true,
		'nivel_sla' => true,
		'observacoes_cli' => true,
		'clausulas' => true,
		'modulos_cobertos' => true,
		'cobre_remoto' => true,
		'cobre_presencial' => true,
		'cobre_manutencao' => true,
		'cobre_backup' => true,
		'cobre_monitoramento' => true,
		'limite_chamados' => true,
		'dias_aviso_vencimento' => true,
		'autentique_doc_id' => true,
		'autentique_status' => true,
		'autentique_url' => true,
		'signature_provider' => true,
		'signed_file_url' => true,
		'sent_for_signature_at' => true,
		'fully_signed_at' => true,
		'pdf_path' => true,
		'signed_pdf_path' => true,
		'aprovado_por' => true,
		'aprovado_em' => true,
		'assinado_em' => true,
		'cancelado_em' => true,
		'motivo_cancelamento' => true,
		'versao' => true,
		'contrato_pai_id' => true,
		'created' => true,
		'modified' => true,
	];

	protected $_virtual = ['status_label', 'dias_para_vencer'];

	protected $_casts = [
		'clausulas' => 'array',
		'modulos_cobertos' => 'array',
		'auto_renew' => 'boolean',
		'cobre_remoto' => 'boolean',
		'cobre_presencial' => 'boolean',
		'cobre_manutencao' => 'boolean',
		'cobre_backup' => 'boolean',
		'cobre_monitoramento' => 'boolean',
		'start_date' => 'date',
		'end_date' => 'date',
		'renewal_date' => 'date',
		'readjustment_date' => 'date',
		'aprovado_em' => 'datetime',
		'assinado_em' => 'datetime',
		'cancelado_em' => 'datetime',
		'sent_for_signature_at' => 'datetime',
		'fully_signed_at' => 'datetime',
	];

	/**
	 * Rótulos PT para UI (lista, filtros, portal).
	 *
	 * @return array<string,string>
	 */
	public static function statusLabelMap() {
		return [
			'rascunho' => 'Rascunho',
			'revisao' => 'Em revisão',
			'aguardando_assinatura' => 'Aguard. assinatura',
			'awaiting_signature' => 'Aguard. assinatura',
			'ativo' => 'Ativo',
			'active' => 'Ativo',
			'a_vencer' => 'A vencer',
			'em_renovacao' => 'Em renovação',
			'suspenso' => 'Suspenso',
			'encerrado' => 'Encerrado',
			'cancelado' => 'Cancelado',
			'recusado' => 'Recusado',
			'assinatura_expirada' => 'Assin. expirada',
		];
	}

	protected function _getStatusLabel() {
		$s = (string)$this->status;
		$labels = static::statusLabelMap();

		return $labels[$s] ?? $s;
	}

	protected function _getDiasParaVencer() {
		$vf = $this->end_date;
		if ($vf === null || $vf === '') {
			return null;
		}
		$vfStr = $vf instanceof \DateTimeInterface ? $vf->format('Y-m-d') : (string)$vf;
		$today = strtotime('today UTC');
		$end = strtotime($vfStr . ' UTC');

		return (int)ceil(($end - $today) / 86400);
	}
}
