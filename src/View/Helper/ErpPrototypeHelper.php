<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;

/**
 * Helper compartilhado para todas as telas premium (mockup pgm_erp_completo.html).
 *
 * Fornece atalhos para badges, stepper, breadcrumb e formatações comuns. Quando
 * a funcionalidade for específica de um módulo, prefira um helper dedicado
 * (ex.: ServicedeskPrototypeHelper).
 */
class ErpPrototypeHelper extends Helper {

	/** @var array<int,string> */
	public $helpers = ['Html', 'Url'];

	/**
	 * Renderiza badge premium (classes .badge + .b-*).
	 *
	 * @param string $label texto exibido
	 * @param string $kind  pend|env|aprov|recus|arq|prod|serv|lic|loc|v|paga|vencida|vencendo|faturada|aguardando|pendente
	 * @param array<string,mixed> $options atributos HTML extras
	 */
	public function badge(string $label, string $kind = 'arq', array $options = []): string {
		$class = trim('badge b-' . $kind . ' ' . (string)($options['class'] ?? ''));
		unset($options['class']);
		$attrs = $this->_attrs(['class' => $class] + $options);

		return '<span ' . $attrs . '>' . h($label) . '</span>';
	}

	/**
	 * Stepper horizontal. Cada passo: ['label', 'state'?: 'done'|'active'|'pending'].
	 *
	 * @param array<int,array{label:string,state?:string}> $steps
	 */
	public function stepper(array $steps): string {
		if ($steps === []) {
			return '';
		}
		$html = '<div class="stepper">';
		$total = count($steps);
		foreach ($steps as $i => $step) {
			$state = (string)($step['state'] ?? 'pending');
			$stpCls = 'stp';
			if ($state === 'done') {
				$stpCls .= ' done';
			} elseif ($state === 'active') {
				$stpCls .= ' active';
			}
			$num = (string)($i + 1);
			$icon = $state === 'done' ? '✓' : $num;
			$html .= '<div class="' . $stpCls . '">';
			$html .= '<div class="stp-c">' . h($icon) . '</div>';
			$html .= '<div class="stp-l">' . h((string)($step['label'] ?? '')) . '</div>';
			if ($i < $total - 1) {
				$html .= '<div class="stp-line"></div>';
			}
			$html .= '</div>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Iniciais de até 2 palavras (para avatar `.av` ou `.user-av`).
	 */
	public function initials(string $name): string {
		$name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
		if ($name === '') {
			return '?';
		}
		$parts = explode(' ', $name);
		$ini = mb_substr($parts[0], 0, 1);
		if (count($parts) > 1) {
			$ini .= mb_substr($parts[count($parts) - 1], 0, 1);
		}

		return mb_strtoupper($ini);
	}

	/**
	 * Links do shell protótipo — navegação completa (sem turbo-frame).
	 *
	 * @param array<string,mixed> $extra
	 * @return array<string,mixed>
	 */
	public function navLinkOpts(array $extra = []): array {
		return array_merge(['data-turbo' => 'false'], $extra);
	}

	public function brl($v): string {
		$n = is_numeric($v) ? (float)$v : 0.0;

		return 'R$ ' . number_format($n, 2, ',', '.');
	}

	/**
	 * @param mixed $dt
	 */
	public function dt($dt, string $fmt = 'd/m/Y'): string {
		if ($dt instanceof \DateTimeInterface) {
			return $dt->format($fmt);
		}
		if (is_string($dt) && $dt !== '') {
			$ts = strtotime($dt);
			if ($ts !== false) {
				return date($fmt, $ts);
			}
		}

		return '';
	}

	/**
	 * Alert-box premium.
	 *
	 * @param string $kind blue|amber|teal|red
	 */
	public function alert(string $body, string $kind = 'blue'): string {
		return '<div class="alert-box alert-' . h($kind) . '">' . $body . '</div>';
	}

	/**
	 * @param array<string,mixed> $attrs
	 */
	protected function _attrs(array $attrs): string {
		$out = [];
		foreach ($attrs as $k => $v) {
			if ($v === null || $v === false) {
				continue;
			}
			if ($v === true) {
				$out[] = h($k);
				continue;
			}
			$out[] = h($k) . '="' . h((string)$v) . '"';
		}

		return implode(' ', $out);
	}
}
