<?php
/**
 * Funções usadas em templates (.ctp) quando vendor/PGMPackages/Utilities.php
 * não está no deploy. Com PGMPackages presente, não redeclara (function_exists).
 */
if (!function_exists('OrdensPagamento')) {
	/**
	 * Rótulo da forma de pagamento (OS / faturas). Usa C_OrdensPagamento.
	 *
	 * @param mixed $pagamento código gravado na entidade
	 */
	function OrdensPagamento($pagamento) {
		if (defined('C_OrdensPagamento') && is_array($opts = constant('C_OrdensPagamento'))) {
			if (array_key_exists($pagamento, $opts)) {
				return (string) $opts[$pagamento];
			}
			if (is_numeric($pagamento) && array_key_exists((int) $pagamento, $opts)) {
				return (string) $opts[(int) $pagamento];
			}
			$sk = (string) $pagamento;
			if (array_key_exists($sk, $opts)) {
				return (string) $opts[$sk];
			}
		}

		return htmlspecialchars((string) $pagamento, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}
}

if (!function_exists('OrdensAtendimento')) {
	/**
	 * Rótulo do tipo de atendimento (histórico em Ordensservico/edit). Usa C_OrdensAtendimento.
	 *
	 * @param mixed $atendimento código gravado na entidade
	 */
	function OrdensAtendimento($atendimento) {
		if (defined('C_OrdensAtendimento') && is_array($opts = constant('C_OrdensAtendimento'))) {
			if (array_key_exists($atendimento, $opts)) {
				return (string) $opts[$atendimento];
			}
			if (is_numeric($atendimento) && array_key_exists((int) $atendimento, $opts)) {
				return (string) $opts[(int) $atendimento];
			}
			$sk = (string) $atendimento;
			if (array_key_exists($sk, $opts)) {
				return (string) $opts[$sk];
			}
		}

		return htmlspecialchars((string) $atendimento, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}
}

if (!function_exists('ProdutosTipo')) {
	/**
	 * Rótulo do tipo de item (produto/serviço/licença) em impressão de OS. Usa C_ProdutosTipo.
	 *
	 * @param mixed $tipo código gravado em itensordem / carrinho
	 */
	function ProdutosTipo($tipo) {
		if (defined('C_ProdutosTipo') && is_array($opts = constant('C_ProdutosTipo'))) {
			if (array_key_exists($tipo, $opts)) {
				return (string) $opts[$tipo];
			}
			if (is_numeric($tipo) && array_key_exists((int) $tipo, $opts)) {
				return (string) $opts[(int) $tipo];
			}
			$sk = (string) $tipo;
			if (array_key_exists($sk, $opts)) {
				return (string) $opts[$sk];
			}
		}

		return htmlspecialchars((string) $tipo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}
}

/**
 * Função usada em templates (.ctp) de locação/faturas (ex.: Faturas/edit.ctp).
 * Evita "Call to undefined function LocacaoStatus" quando vendor/PGMPackages
 * não expõe esse helper.
 */
if (!function_exists('LocacaoStatus')) {
	function LocacaoStatus($status) {
		$pend = defined('C_LocacaoStatusPendente') ? (int) C_LocacaoStatusPendente : 1;
		$apr = defined('C_LocacaoStatusAprovado') ? (int) C_LocacaoStatusAprovado : 2;
		$rej = defined('C_LocacaoStatusRejeitado') ? (int) C_LocacaoStatusRejeitado : 3;
		$fin = defined('C_LocacaoStatusFinalizado') ? (int) C_LocacaoStatusFinalizado : 4;
		$map = [
			$pend => 'Pendente',
			$apr => 'Aprovado',
			$rej => 'Rejeitado',
			$fin => 'Finalizado',
		];
		$s = (int) $status;
		$out = $map[$s] ?? (string) $status;

		return htmlspecialchars($out, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}
}

/**
 * Detecção mobile em templates (.ctp). Ordensservico/add, edit, addFromTicket e
 * Orcamentos/novaordem chamam isMobile(); sem esta função o PHP encerra com
 * "Call to undefined function isMobile()" após renderizar o topo do formulário.
 */
if (!function_exists('isMobile')) {
	function isMobile(): bool {
		if (class_exists(\Cake\Routing\Router::class)) {
			$req = \Cake\Routing\Router::getRequest();
			if ($req !== null) {
				return (bool) $req->is('mobile');
			}
		}
		if (class_exists(\Detection\MobileDetect::class)) {
			return (new \Detection\MobileDetect())->isMobile();
		}

		return false;
	}
}

/**
 * Polyfills usados em .ctp e em OrdensservicoTable::historicoOrdens quando
 * vendor/PGMPackages/Utilities.php não está presente no deploy.
 * Não redeclara se o legado já definiu a função.
 */
if (!function_exists('formatCnpjCpf')) {
	/**
	 * @param mixed $value CPF/CNPJ com ou sem máscara
	 */
	function formatCnpjCpf($value): string {
		if ($value === null || $value === '') {
			return '';
		}
		$digits = preg_replace('/\D/', '', (string) $value);
		if (strlen($digits) === 11) {
			return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits);
		}
		if (strlen($digits) === 14) {
			return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $digits);
		}

		return (string) $value;
	}
}

if (!function_exists('pgm_format_date_br')) {
	/**
	 * Normaliza para d/m/Y (entrada: FrozenDate, DateTime, Y-m-d, d/m/Y, etc.).
	 */
	function pgm_format_date_br($value): string {
		if ($value instanceof \DateTimeInterface) {
			return $value->format('d/m/Y');
		}
		if ($value === null || $value === '') {
			return '';
		}
		$s = trim((string) $value);
		if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}/', $s)) {
			$d = \DateTime::createFromFormat('d/m/Y', substr($s, 0, 10));
			if ($d instanceof \DateTime) {
				return $d->format('d/m/Y');
			}
		}
		$d = date_create($s);
		if ($d instanceof \DateTime) {
			return $d->format('d/m/Y');
		}

		return '';
	}
}

if (!function_exists('descricaoMes')) {
	/**
	 * Nome do mês em português. $abbr: 0 = completo, 1 = abreviado (3 letras).
	 *
	 * @param mixed $date d/m/Y, Y-m-d, DateTime ou timestamp numérico
	 */
	function descricaoMes($date, $abbr = 0): string {
		$ts = null;
		if ($date instanceof \DateTimeInterface) {
			$ts = $date->getTimestamp();
		} elseif (is_int($date) || (is_float($date) && $date > 1000000000)) {
			$ts = (int) $date;
		}
		if ($ts === null && is_string($date) && trim($date) !== '') {
			$s = trim($date);
			$d = \DateTime::createFromFormat('d/m/Y', $s);
			if (!($d instanceof \DateTime)) {
				$d = date_create($s);
			}
			$ts = ($d instanceof \DateTime) ? $d->getTimestamp() : null;
		}
		if ($ts === null) {
			return '';
		}
		$n = (int) date('n', $ts);
		$full = ['', 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
		$short = ['', 'jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
		if ((int) $abbr === 1) {
			return $short[$n] ?? '';
		}

		return $full[$n] ?? '';
	}
}

if (!function_exists('dataAtual')) {
	function dataAtual(): string {
		return date('d/m/Y');
	}
}

if (!function_exists('primeiroDiaMes')) {
	function primeiroDiaMes(string $dataBr): string {
		$d = \DateTime::createFromFormat('d/m/Y', trim($dataBr));
		if (!($d instanceof \DateTime)) {
			return $dataBr;
		}
		$d->modify('first day of this month');

		return $d->format('d/m/Y');
	}
}

if (!function_exists('ultimoDiaMes')) {
	function ultimoDiaMes(string $dataBr): string {
		$d = \DateTime::createFromFormat('d/m/Y', trim($dataBr));
		if (!($d instanceof \DateTime)) {
			return $dataBr;
		}
		$d->modify('last day of this month');

		return $d->format('d/m/Y');
	}
}

if (!function_exists('increaseMonths')) {
	function increaseMonths(string $dataBr, int $meses = 1): string {
		$d = \DateTime::createFromFormat('d/m/Y', trim($dataBr));
		if (!($d instanceof \DateTime)) {
			return $dataBr;
		}
		$d->modify('+' . $meses . ' months');

		return $d->format('d/m/Y');
	}
}

if (!function_exists('decreaseMonths')) {
	function decreaseMonths(string $dataBr, int $meses = 1): string {
		$d = \DateTime::createFromFormat('d/m/Y', trim($dataBr));
		if (!($d instanceof \DateTime)) {
			return $dataBr;
		}
		$d->modify('-' . $meses . ' months');

		return $d->format('d/m/Y');
	}
}

/**
 * Helpers globais do legado PGM (Utilities.php). Sem eles, ClientesController::edit
 * e outros quebram com "Call to undefined function" se PGMPackages não estiver no deploy.
 * removeCaracteres: alinhado ao mutator em src/Model/Entity/Cliente.php.
 * Senha: se PGMPackages já definiu criptografaSenha/descriptografaSenha, não redeclara.
 * Caso contrário, tenta Cake\Utility\Security (mesmo salt do app) + base64 na coluna;
 * fallbacks evitam 500 quando o formato legado difere (exibe valor bruto / base64 simples).
 */
if (!function_exists('removeCaracteres')) {
	function removeCaracteres($string) {
		if ($string === null) {
			return '';
		}
		$chars = ['?', '+', '-', ',', '(', ')', '*', '&', ';', '=', '/', '.', ':', ' '];

		return str_replace($chars, '', (string) $string);
	}
}

if (!function_exists('criptografaSenha')) {
	function criptografaSenha($plain) {
		$plain = (string) ($plain ?? '');
		if ($plain === '') {
			return '';
		}
		$key = \Cake\Core\Configure::read('Security.salt');
		if (!is_string($key) || $key === '') {
			return base64_encode($plain);
		}
		$keyLen = function_exists('mb_strlen') ? mb_strlen($key, '8bit') : strlen($key);
		if ($keyLen < 32) {
			return base64_encode($plain);
		}
		try {
			$cipher = \Cake\Utility\Security::encrypt($plain, $key);
			if (!is_string($cipher) || $cipher === '') {
				return base64_encode($plain);
			}

			return base64_encode($cipher);
		} catch (\Throwable $e) {
			return base64_encode($plain);
		}
	}
}

if (!function_exists('descriptografaSenha')) {
	function descriptografaSenha($stored) {
		if ($stored === null || $stored === '') {
			return '';
		}
		$s = (string) $stored;
		$key = \Cake\Core\Configure::read('Security.salt');
		if (is_string($key) && $key !== '') {
			$keyLen = function_exists('mb_strlen') ? mb_strlen($key, '8bit') : strlen($key);
			if ($keyLen >= 32) {
				try {
					$bin = base64_decode($s, true);
					if ($bin !== false && $bin !== '') {
						$out = \Cake\Utility\Security::decrypt($bin, $key);
						if ($out !== false && $out !== null) {
							return (string) $out;
						}
					}
					if (strlen($s) > 64) {
						$out = \Cake\Utility\Security::decrypt($s, $key);
						if ($out !== false && $out !== null) {
							return (string) $out;
						}
					}
				} catch (\Throwable $e) {
					// formato legado diferente do Cake Security
				}
			}
		}
		$decoded = base64_decode($s, true);
		if ($decoded !== false && $decoded !== '') {
			return $decoded;
		}

		return $s;
	}
}

/**
 * Link para notificações de ticket (legado Utilities.php). TicketsController::criaNot e
 * TicketcomentariosController::criaNot usam idacao = id do ticket nos dois tipos.
 */
if (!function_exists('NotificacaoLink')) {
	/**
	 * @param int|string $tipo
	 * @param int|string $idacao id do ticket
	 * @return array{controller: string, action: string}
	 */
	function NotificacaoLink($tipo, $idacao) {
		unset($tipo, $idacao);

		return ['controller' => 'tickets', 'action' => 'view'];
	}
}
