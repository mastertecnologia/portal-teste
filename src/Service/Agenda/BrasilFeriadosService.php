<?php
namespace App\Service\Agenda;

/**
 * Feriados nacionais fixos e móveis (BR), feriados estaduais fixos por UF (sigla),
 * e feriados de cadastro (empresa/global) via endpoint + tabela Feriados.
 * Municipais: cadastrar manualmente em Feriados.
 */
class BrasilFeriadosService {

	/**
	 * @param string $dataInicio Y-m-d
	 * @param string $dataFim    Y-m-d
	 * @param string|null $uf    Sigla do estado (ex.: SP), 2 letras, maiúsculas
	 * @return array<int, array{data:string,titulo:string,escopo?:string}>
	 */
	public function listarNoPeriodo(string $dataInicio, string $dataFim, ?string $uf = null): array {
		$ini = new \DateTimeImmutable($dataInicio . ' 00:00:00');
		$fim = new \DateTimeImmutable($dataFim . ' 23:59:59');
		if ($ini > $fim) {
			return [];
		}
		$y0 = (int)$ini->format('Y');
		$y1 = (int)$fim->format('Y');
		$uf = $this->normalizarUf($uf);
		$out = [];
		for ($y = $y0; $y <= $y1; $y++) {
			foreach ($this->feriadosAno($y, $uf) as $row) {
				$d = $row['data'];
				if ($d >= $dataInicio && $d <= $dataFim) {
					$out[] = $row;
				}
			}
		}

		return $out;
	}

	/**
	 * Eventos no formato FullCalendar v6+.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function fullCalendarEvents(string $dataInicio, string $dataFim, ?string $uf = null): array {
		$events = [];
		foreach ($this->listarNoPeriodo($dataInicio, $dataFim, $uf) as $row) {
			$escopo = $row['escopo'] ?? 'nacional';
			$classNames = $escopo === 'estadual'
				? ['pgm-fc-feriado', 'pgm-fc-feriado-estadual']
				: ['pgm-fc-feriado'];
			$events[] = [
				'title' => $row['titulo'],
				'start' => $row['data'],
				'allDay' => true,
				'editable' => false,
				'classNames' => $classNames,
			];
		}

		return $events;
	}

	protected function normalizarUf(?string $uf): ?string {
		if ($uf === null || $uf === '') {
			return null;
		}
		$u = strtoupper(preg_replace('/[^A-Z]/i', '', $uf));

		return strlen($u) === 2 ? $u : null;
	}

	/**
	 * Feriados estaduais fixos (referência legislação comum; conferir município).
	 *
	 * @return array<int, array{m:int,d:int,t:string}>
	 */
	protected function feriadosFixosPorUf(string $uf): array {
		$map = [
			'AC' => [[6, 15, 'Aniversário do Acre (feriado estadual)']],
			'AL' => [[9, 24, 'Emancipação política de Alagoas (feriado estadual)']],
			'AM' => [[9, 5, 'Elevação do Amazonas à categoria de província (feriado estadual)']],
			'AP' => [[3, 19, 'Dia de São José, padroeiro do Amapá (feriado estadual)']],
			'BA' => [[7, 2, 'Independência da Bahia (feriado estadual)']],
			'CE' => [[3, 25, 'Data magna do Ceará (feriado estadual)']],
			'MA' => [[7, 28, 'Adesão do Maranhão à independência (feriado estadual)']],
			'PB' => [[7, 26, 'Homenagem à memória de João Pessoa (feriado estadual)']],
			'PE' => [[6, 24, 'São João (feriado estadual em Pernambuco)']],
			'PI' => [[10, 19, 'Dia do Piauí (feriado estadual)']],
			'PR' => [[12, 19, 'Emancipação política do Paraná (feriado estadual)']],
			'RJ' => [[4, 23, 'São Jorge (feriado estadual no RJ)']],
			'RN' => [[10, 3, 'Mártires de Cunhaú e Uruaçu (feriado estadual)']],
			'RO' => [[1, 4, 'Criação do estado de Rondônia (feriado estadual)']],
			'RR' => [[10, 10, 'Criação de Roraima (feriado estadual)']],
			'RS' => [[9, 20, 'Revolução Farroupilha (feriado estadual)']],
			'SC' => [[8, 11, 'Criação da capitania de Santa Catarina (feriado estadual)']],
			'SE' => [[7, 8, 'Autonomia política de Sergipe (feriado estadual)']],
			'SP' => [[7, 9, 'Revolução Constitucionalista de 1932 (feriado estadual em SP)']],
			'TO' => [[10, 5, 'Criação do Tocantins (feriado estadual)']],
		];

		return $map[$uf] ?? [];
	}

	/**
	 * @return array<int, array{data:string,titulo:string,escopo?:string}>
	 */
	protected function feriadosAno(int $year, ?string $uf): array {
		$rows = [];

		$add = function (string $ymd, string $titulo, string $escopo = 'nacional') use (&$rows) {
			$rows[] = ['data' => $ymd, 'titulo' => $titulo, 'escopo' => $escopo];
		};

		$add(sprintf('%04d-01-01', $year), 'Confraternização universal');
		$add(sprintf('%04d-04-21', $year), 'Tiradentes');
		$add(sprintf('%04d-05-01', $year), 'Dia do trabalhador');
		$add(sprintf('%04d-09-07', $year), 'Independência do Brasil');
		$add(sprintf('%04d-10-12', $year), 'Nossa Senhora Aparecida');
		$add(sprintf('%04d-11-02', $year), 'Finados');
		$add(sprintf('%04d-11-15', $year), 'Proclamação da República');
		$add(sprintf('%04d-11-20', $year), 'Consciência Negra (nacional)');
		$add(sprintf('%04d-12-25', $year), 'Natal');

		$pascoa = $this->easterSunday($year);
		if ($pascoa !== null) {
			$this->addMovable($add, $pascoa, -47, 'Carnaval (terça-feira)');
			$this->addMovable($add, $pascoa, -2, 'Sexta-feira Santa');
			$this->addMovable($add, $pascoa, 60, 'Corpus Christi');
		}

		if ($uf !== null) {
			foreach ($this->feriadosFixosPorUf($uf) as $fe) {
				$m = (int)$fe[0];
				$d = (int)$fe[1];
				$t = (string)$fe[2];
				$add(sprintf('%04d-%02d-%02d', $year, $m, $d), $t . ' — ' . $uf, 'estadual');
			}
		}

		return $rows;
	}

	protected function easterSunday(int $year): ?\DateTimeImmutable {
		if (!function_exists('easter_days')) {
			return null;
		}
		$base = new \DateTimeImmutable(sprintf('%04d-03-21', $year));

		return $base->modify('+' . easter_days($year) . ' days');
	}

	protected function addMovable(callable $add, \DateTimeImmutable $pascoa, int $offsetDays, string $titulo): void {
		$d = $pascoa->modify(($offsetDays >= 0 ? '+' : '') . $offsetDays . ' days');
		$add($d->format('Y-m-d'), $titulo, 'nacional');
	}
}
