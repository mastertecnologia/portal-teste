<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;

/**
 * URLs e formatação do protótipo SD (mockup pgm_erp_completo.html).
 */
class ServicedeskPrototypeHelper extends Helper {

	/** @var array<string, mixed> */
	public $helpers = ['Html', 'Url'];

	/**
	 * @param array<string, mixed>|string $url
	 * @param array<string, mixed> $options
	 */
	public function sdpUrl($url, array $options = []): string {
		return $this->Url->build($url, $options);
	}

	public function sdpPage(string $page, array $query = []): string {
		if ($page === 'dashboard') {
			return $this->sdpUrl(['controller' => 'ServicedeskPrototype', 'action' => 'index']);
		}
		if ($page === 'fila') {
			return $this->sdpUrl(['controller' => 'ServicedeskPrototype', 'action' => 'fila'] + ($query ? ['?' => $query] : []));
		}

		return $this->sdpUrl(['controller' => 'ServicedeskPrototype', 'action' => 'view', $page] + ($query ? ['?' => $query] : []));
	}

	public function sdpTicketUrl(int $id): string {
		return $this->sdpUrl(['controller' => 'ServicedeskPrototype', 'action' => 'ticket', $id]);
	}

	public function initials(string $name): string {
		$name = trim(preg_replace('/\s+/', ' ', $name));
		if ($name === '') {
			return '?';
		}
		$parts = explode(' ', $name);

		return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[count($parts) - 1], 0, 1));
	}

	/**
	 * @param mixed $dt
	 */
	public function fmtDate($dt): string {
		if ($dt instanceof \DateTimeInterface) {
			return $dt->format('d/m/Y');
		}

		return '—';
	}

	/**
	 * @param mixed $dt
	 */
	public function fmtDateTime($dt): string {
		if ($dt instanceof \DateTimeInterface) {
			return $dt->format('d/m/Y H:i');
		}

		return '—';
	}

	public function heatmapCellStyle(int $count, int $max): string {
		if ($count <= 0) {
			return 'background:#F0EFEC;color:var(--text-muted);';
		}
		$ratio = $max > 0 ? $count / $max : 0;
		if ($ratio >= 0.85) {
			return 'background:#0a3d2c;color:#fff;font-weight:700;';
		}
		if ($ratio >= 0.55) {
			return 'background:#1D9E75;color:#fff;font-weight:700;';
		}
		if ($ratio >= 0.35) {
			return 'background:#7DD3C0;color:#fff;';
		}

		return 'background:#C5F1D8;';
	}

	public function formatSlaSeconds(int $seconds): string {
		if ($seconds <= 0) {
			return '0m';
		}
		$h = (int)floor($seconds / 3600);
		$m = (int)floor(($seconds % 3600) / 60);
		if ($h > 0) {
			return sprintf('%dh %dm', $h, $m);
		}

		return sprintf('%dm', $m);
	}

	public function situacaoPillClass(int $sit): string {
		if (defined('C_TicketSituacaoResolvido') && $sit === (int)C_TicketSituacaoResolvido) {
			return 'sdp-pill-resolvido';
		}
		if (defined('C_TicketSituacaoFechado') && $sit === (int)C_TicketSituacaoFechado) {
			return 'sdp-pill-fechado';
		}
		if (defined('C_TicketSituacaoEmandamento') && $sit === (int)C_TicketSituacaoEmandamento) {
			return 'sdp-pill-exec';
		}
		if (defined('C_TicketSituacaoRespondido') && $sit === (int)C_TicketSituacaoRespondido) {
			return 'sdp-pill-aguarda';
		}

		return 'sdp-pill-pendente';
	}
}
