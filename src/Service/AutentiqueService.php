<?php
namespace App\Service;

use Cake\Core\Configure;

/**
 * Integração Autentique (sandbox/produção). Quando desligado em config, métodos são no-op seguros.
 * Fase 7 pode preencher chamadas HTTP reais (GraphQL/API conforme doc do fornecedor).
 */
class AutentiqueService {

	/** @return bool */
	public function isEnabled() {
		return (bool)Configure::read('Contract.autentique.enabled');
	}

	/**
	 * @param string $documentId
	 * @return array{status:string, signed_url?:string}
	 */
	public function statusDocumento($documentId) {
		if (!$this->isEnabled() || $documentId === '') {
			return ['status' => 'unknown'];
		}

		return ['status' => 'unknown'];
	}

	/**
	 * @param string $documentId
	 * @param string $targetPath Caminho absoluto do PDF assinado.
	 * @return bool
	 */
	public function downloadSignedPdf($documentId, $targetPath) {
		if (!$this->isEnabled() || $documentId === '') {
			return false;
		}

		return false;
	}
}
