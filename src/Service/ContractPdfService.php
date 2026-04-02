<?php
namespace App\Service;

use Cake\Core\Configure;

/**
 * Gera PDF de contrato (mPDF). Substitui placeholders {{var}} no HTML do modelo.
 */
class ContractPdfService {

	/**
	 * @param \Cake\Datasource\EntityInterface $contract Com Clientes / Empresas carregados se possível.
	 * @param \Cake\Datasource\EntityInterface|null $template Modelo (contract_templates); se null, usa HTML mínimo.
	 * @param \Cake\Datasource\EntityInterface[]|array $servicos Linhas de contract_services.
	 * @return string Caminho absoluto do ficheiro gravado.
	 */
	public function gerar($contract, $template, array $servicos = []) {
		if (!class_exists(\Mpdf\Mpdf::class)) {
			throw new \RuntimeException('mPDF não está disponível.');
		}

		$storagePath = (string)Configure::read('Contract.pdf.storage_path');
		if ($storagePath === '') {
			$storagePath = TMP . 'contracts' . DS;
		}
		$pdfDir = $storagePath . 'pdfs';
		if (!is_dir($pdfDir)) {
			mkdir($pdfDir, 0775, true);
		}

		$htmlBody = $template && $template->get('conteudo_html') !== null
			? (string)$template->get('conteudo_html')
			: $this->defaultHtmlShell();
		$html = $this->substituir($htmlBody, $contract, $servicos);
		$html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>';

		$tmpDir = TMP . 'mpdf' . DS;
		if (!is_dir($tmpDir)) {
			mkdir($tmpDir, 0775, true);
		}
		$mpdf = new \Mpdf\Mpdf([
			'mode' => 'utf-8',
			'format' => 'A4',
			'margin_top' => 20,
			'margin_bottom' => 20,
			'margin_left' => 25,
			'margin_right' => 25,
			'tempDir' => $tmpDir,
		]);
		$code = (string)($contract->get('code') ?: $contract->get('id'));
		$mpdf->SetTitle('Contrato ' . $code);
		$mpdf->SetAuthor('PGM Soluções em TI');
		$mpdf->WriteHTML($html);

		$versao = (int)($contract->get('versao') ?: 1);
		$path = $pdfDir . DS . sprintf('contrato_%d_v%d_%s.pdf', (int)$contract->get('id'), $versao, date('YmdHis'));
		$mpdf->Output($path, 'F');

		return $path;
	}

	/**
	 * @param mixed $x
	 * @return object
	 */
	protected function entityToObj($x) {
		if ($x instanceof \Cake\Datasource\EntityInterface) {
			return (object)$x->toArray();
		}
		if (is_array($x)) {
			return (object)$x;
		}
		if (is_object($x)) {
			return $x;
		}

		return (object)[];
	}

	protected function defaultHtmlShell() {
		return '<h1>Contrato {{numero_contrato}}</h1><p>Cliente: {{cliente_razaosocial}}</p><p>Vigência: {{vigencia_inicio}} a {{vigencia_fim}}</p><p>Valor mensal: {{valor_mensal}}</p>{{servicos_contratados}}';
	}

	protected function substituir($html, $contract, array $servicos) {
		$c = $contract->cliente ?? $contract->get('cliente');
		$e = $contract->empresa ?? $contract->get('empresa');
		$c = $this->entityToObj($c);
		$e = $this->entityToObj($e);

		$endParts = array_filter([
			$c->logradouro ?? '',
			$c->numero ?? '',
			$c->bairro ?? '',
			$c->cidade ?? '',
			$c->estado ?? '',
		]);

		$vars = [
			'{{cliente_razaosocial}}' => \h((string)($c->razaosocial ?? $c->nome ?? '')),
			'{{cliente_cnpj}}' => \h((string)($c->cnpj ?? $c->cpf ?? '')),
			'{{cliente_endereco}}' => \h(implode(', ', $endParts)),
			'{{empresa_razaosocial}}' => \h((string)($e->razaosocial ?? '')),
			'{{empresa_cnpj}}' => \h((string)($e->cnpj ?? '')),
			'{{numero_contrato}}' => \h((string)($contract->get('code') ?? '')),
			'{{vigencia_inicio}}' => $this->fmt($contract->get('start_date')),
			'{{vigencia_fim}}' => $this->fmt($contract->get('end_date'), '—'),
			'{{valor_mensal}}' => 'R$ ' . number_format((float)($contract->get('monthly_value') ?? 0), 2, ',', '.'),
			'{{valor_total}}' => 'R$ ' . number_format((float)($contract->get('valor_total') ?? 0), 2, ',', '.'),
			'{{horas_incluidas}}' => \h((string)($contract->get('included_hours') ?? '0')) . 'h/mês',
			'{{nivel_sla}}' => \h((string)($contract->get('nivel_sla') ?? '—')),
			'{{servicos_contratados}}' => $this->tabelaServicos($servicos),
			'{{clausulas}}' => $this->listaClausulas((array)($contract->get('clausulas') ?? [])),
			'{{data_hoje}}' => date('d/m/Y'),
		];

		return str_replace(array_keys($vars), array_values($vars), $html);
	}

	protected function fmt($date, $fallback = '') {
		if (empty($date)) {
			return $fallback;
		}
		if ($date instanceof \DateTimeInterface) {
			return $date->format('d/m/Y');
		}

		return date('d/m/Y', strtotime((string)$date));
	}

	protected function tabelaServicos(array $servicos) {
		if (empty($servicos)) {
			return '<p><em>Conforme proposta comercial.</em></p>';
		}
		$html = '<table border="1" cellpadding="5" style="width:100%;border-collapse:collapse;">';
		$html .= '<tr style="background:#f5f5f5"><th>Serviço</th><th>Qtde</th><th>Vl. unit.</th><th>Total</th></tr>';
		foreach ($servicos as $s) {
			$nome = is_object($s) ? (string)($s->get('service_name') ?? '') : (string)($s['service_name'] ?? '');
			$qty = is_object($s)
				? (string)($s->get('quantity') ?? $s->get('max_hours') ?? '1')
				: (string)($s['quantity'] ?? $s['max_hours'] ?? '1');
			$und = is_object($s) ? (string)($s->get('unidade') ?? 'unid') : (string)($s['unidade'] ?? 'unid');
			$vu = is_object($s) ? (float)($s->get('valor_unitario') ?? 0) : (float)($s['valor_unitario'] ?? 0);
			$vt = is_object($s) ? (float)($s->get('valor_total') ?? 0) : (float)($s['valor_total'] ?? 0);
			$html .= '<tr><td>' . \h($nome) . '</td><td>' . \h($qty) . ' ' . \h($und) . '</td><td>R$ '
				. number_format($vu, 2, ',', '.') . '</td><td>R$ ' . number_format($vt, 2, ',', '.') . '</td></tr>';
		}

		return $html . '</table>';
	}

	protected function listaClausulas(array $clausulas) {
		if (empty($clausulas)) {
			return '';
		}
		$html = '<ol>';
		foreach ($clausulas as $row) {
			$row = (array)$row;
			$html .= '<li><strong>' . \h((string)($row['titulo'] ?? '')) . '</strong><br>'
				. nl2br(\h((string)($row['texto'] ?? ''))) . '</li>';
		}

		return $html . '</ol>';
	}
}
