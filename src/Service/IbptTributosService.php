<?php
namespace App\Service;

use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

/**
 * Consulta IBPT (CDN e/ou API) e calcula tributos aproximados por linha da locação.
 */
class IbptTributosService {

	/**
	 * Monta o detalhamento por item do carrinho e totais (nacional / estadual / municipal).
	 *
	 * @param object $empresa Entidade Empresas com Cidades.Estados carregado.
	 * @param array $carrinho Lista de Faturasitens.
	 * @param int $idempresa
	 * @return array
	 */
	public function breakdownForCarrinho($empresa, array $carrinho, $idempresa) {
		$cfg = Configure::read('Ibpt') ?: [];
		if (empty(trim((string) ($cfg['cnpj'] ?? ''))) && !empty($empresa->cnpj)) {
			$cfg['cnpj'] = $empresa->cnpj;
		}
		$enabled = !empty($cfg['enabled']);
		$uf = strtoupper((string) ($empresa->cidade->estado->sigla ?? $cfg['ufDefault'] ?? 'RS'));
		$defaultNcm = $this->normalizeNcm(isset($cfg['defaultNcm']) ? $cfg['defaultNcm'] : '00000000');
		$fallbackRate = isset($cfg['fallbackTotalRate']) ? (float) $cfg['fallbackTotalRate'] : 0.3145;

		$codes = [];
		foreach ($carrinho as $it) {
			$c = trim((string) $it->codigo);
			if ($c !== '') {
				$codes[$c] = true;
			}
		}

		$prodByCodigo = [];
		if ($codes !== []) {
			$Produtos = TableRegistry::get('Produtos');
			$q = $Produtos->find()->where(['idempresa' => $idempresa, 'codigo IN' => array_keys($codes)]);
			foreach ($q as $pr) {
				$prodByCodigo[trim((string) $pr->codigo)] = $pr;
			}
		}

		$lines = [];
		$sumFed = 0.0;
		$sumEst = 0.0;
		$sumMun = 0.0;
		$versao = '';
		$fonte = '';
		$anyFallback = false;

		foreach ($carrinho as $it) {
			$valor = (float) $it->valortotal;
			$cod = trim((string) $it->codigo);
			$pr = isset($prodByCodigo[$cod]) ? $prodByCodigo[$cod] : null;
			$rawNcm = ($pr && !empty($pr->ncm)) ? (string) $pr->ncm : '';
			$ncm = $rawNcm !== '' ? $this->normalizeNcm($rawNcm) : $defaultNcm;

			if (!$enabled) {
				$rates = null;
			} else {
				$rates = $this->resolveRates($uf, $ncm, $cfg, (string) $it->descricao, $valor);
			}

			if ($rates === null) {
				$anyFallback = true;
				$aprox = $valor * $fallbackRate;
				$lines[] = [
					'codigo' => $cod,
					'descricao' => (string) $it->descricao,
					'valor' => $valor,
					'ncm' => $ncm,
					'pct_nacional' => null,
					'pct_estadual' => null,
					'pct_municipal' => null,
					'pct_total' => $fallbackRate * 100,
					'aprox_nacional' => $aprox / 3,
					'aprox_estadual' => $aprox / 3,
					'aprox_municipal' => $aprox / 3,
					'aprox' => $aprox,
					'fonte' => 'Estimativa (fallback configurado)',
				];
				$sumFed += $aprox / 3;
				$sumEst += $aprox / 3;
				$sumMun += $aprox / 3;
				continue;
			}

			list($pctNat, $pctEst, $pctMun, $ver, $src) = $rates;
			$vFed = $valor * ($pctNat / 100.0);
			$vEst = $valor * ($pctEst / 100.0);
			$vMun = $valor * ($pctMun / 100.0);
			$aprox = $vFed + $vEst + $vMun;
			if ($versao === '' && $ver !== '') {
				$versao = $ver;
			}
			if ($fonte === '' && $src !== '') {
				$fonte = $src;
			}

			$lines[] = [
				'codigo' => $cod,
				'descricao' => (string) $it->descricao,
				'valor' => $valor,
				'ncm' => $ncm,
				'pct_nacional' => $pctNat,
				'pct_estadual' => $pctEst,
				'pct_municipal' => $pctMun,
				'pct_total' => $pctNat + $pctEst + $pctMun,
				'aprox_nacional' => $vFed,
				'aprox_estadual' => $vEst,
				'aprox_municipal' => $vMun,
				'aprox' => $aprox,
				'fonte' => $src,
			];
			$sumFed += $vFed;
			$sumEst += $vEst;
			$sumMun += $vMun;
		}

		return [
			'lines' => $lines,
			'totais' => [
				'nacional_valor' => $sumFed,
				'estadual_valor' => $sumEst,
				'municipal_valor' => $sumMun,
				'geral_valor' => $sumFed + $sumEst + $sumMun,
			],
			'versao' => $versao,
			'fonte' => $fonte !== '' ? $fonte : 'IBPT',
			'uf' => $uf,
			'fallback' => $anyFallback,
		];
	}

	/**
	 * Escala valores (ex.: recibo parcial em relação ao total da fatura).
	 *
	 * @param array $breakdown Retorno de breakdownForCarrinho
	 * @param float $factor 0..1
	 * @return array
	 */
	public function scaleBreakdown(array $breakdown, $factor) {
		$factor = max(0.0, min(1.0, (float) $factor));
		if ($factor >= 0.999999) {
			return $breakdown;
		}
		$out = $breakdown;
		$out['lines'] = [];
		foreach ($breakdown['lines'] as $ln) {
			$row = $ln;
			$row['valor'] = round($ln['valor'] * $factor, 2);
			$row['aprox_nacional'] = round($ln['aprox_nacional'] * $factor, 2);
			$row['aprox_estadual'] = round($ln['aprox_estadual'] * $factor, 2);
			$row['aprox_municipal'] = round($ln['aprox_municipal'] * $factor, 2);
			$row['aprox'] = round($ln['aprox'] * $factor, 2);
			$out['lines'][] = $row;
		}
		$t = $breakdown['totais'];
		$out['totais'] = [
			'nacional_valor' => round($t['nacional_valor'] * $factor, 2),
			'estadual_valor' => round($t['estadual_valor'] * $factor, 2),
			'municipal_valor' => round($t['municipal_valor'] * $factor, 2),
			'geral_valor' => round($t['geral_valor'] * $factor, 2),
		];
		$out['scaled_by'] = $factor;

		return $out;
	}

	/**
	 * @return array|null [nacional%, estadual%, municipal%, versao, fonte]
	 */
	protected function resolveRates($uf, $ncm8, array $cfg, $descricao, $valor) {
		$token = isset($cfg['token']) ? trim((string) $cfg['token']) : '';
		$cnpj = isset($cfg['cnpj']) ? preg_replace('/\D/', '', (string) $cfg['cnpj']) : '';
		if ($token !== '' && strlen($cnpj) >= 14) {
			$r = $this->fetchFromOfficialApi($uf, $ncm8, $cfg, $token, $cnpj, $descricao, $valor);
			if ($r !== null) {
				return $r;
			}
		}

		return $this->fetchFromCdn($uf, $ncm8, $cfg);
	}

	/**
	 * @return array|null
	 */
	protected function fetchFromCdn($uf, $ncm8, array $cfg) {
		$base = isset($cfg['cdnBaseUrl']) ? rtrim((string) $cfg['cdnBaseUrl'], '/') : 'https://ibpt.nfe.io';
		$url = $base . '/ncm/' . strtolower($uf) . '/' . $ncm8 . '.json';
		$timeout = isset($cfg['timeout']) ? (int) $cfg['timeout'] : 10;

		try {
			$client = new Client(['timeout' => $timeout]);
			$resp = $client->get($url);
			$code = (int) $resp->getStatusCode();
			if ($code < 200 || $code >= 300) {
				Log::warning('IBPT CDN ' . $code . ' ' . $url);

				return null;
			}
			$data = json_decode((string) $resp->getBody(), true);
			if (!is_array($data)) {
				return null;
			}
			$row = isset($data['ncm']) ? $data['ncm'] : $data;
			if (isset($row[0]) && is_array($row[0])) {
				$row = $row[0];
			}
			if (!is_array($row)) {
				return null;
			}

			$nacional = $this->pickPercent($row, ['nacionalfederal', 'NacionalFederal', 'alNacionalFederal']);
			$estadual = $this->pickPercent($row, ['estadual', 'Estadual', 'alEstadual']);
			$municipal = $this->pickPercent($row, ['municipal', 'Municipal', 'alMunicipal']);
			$versao = (string) (isset($data['versao']) ? $data['versao'] : (isset($row['versao']) ? $row['versao'] : ''));

			if ($nacional <= 0 && $estadual <= 0 && $municipal <= 0) {
				return null;
			}

			return [$nacional, $estadual, $municipal, $versao, 'IBPT (CDN nfe.io)'];
		} catch (\Throwable $e) {
			Log::warning('IBPT CDN: ' . $e->getMessage());

			return null;
		}
	}

	/**
	 * @return array|null
	 */
	protected function fetchFromOfficialApi($uf, $ncm8, array $cfg, $token, $cnpj, $descricao, $valor) {
		$urls = isset($cfg['apiProdutosUrls']) && is_array($cfg['apiProdutosUrls']) ? $cfg['apiProdutosUrls'] : [
			'https://apidadosabertos.ibpt.org.br/api/v1/produtos',
		];
		$timeout = isset($cfg['timeout']) ? (int) $cfg['timeout'] : 10;
		$query = [
			'token' => $token,
			'cnpj' => $cnpj,
			'codigo' => $ncm8,
			'uf' => strtoupper($uf),
			'ex' => 0,
			'descricao' => mb_substr((string) $descricao, 0, 250),
			'valor' => number_format((float) $valor, 2, '.', ''),
			'unidadeMedida' => 'UN',
			'gtin' => '',
		];

		try {
			$client = new Client(['timeout' => $timeout]);
			foreach ($urls as $base) {
				$base = trim((string) $base);
				if ($base === '') {
					continue;
				}
				$resp = $client->get($base, $query);
				$code = (int) $resp->getStatusCode();
				if ($code < 200 || $code >= 300) {
					continue;
				}
				$data = json_decode((string) $resp->getBody(), true);
				if (!is_array($data)) {
					continue;
				}
				$pctNat = $this->pickPercent($data, ['AlNacionalFederal', 'alNacionalFederal', 'NacionalFederal']);
				$pctImp = $this->pickPercent($data, ['AlImportados', 'alImportados', 'ImportadosFederal']);
				$pctEst = $this->pickPercent($data, ['AlEstadual', 'alEstadual', 'Estadual']);
				$pctMun = $this->pickPercent($data, ['AlMunicipal', 'alMunicipal', 'Municipal']);

				$vNat = $this->pickMoney($data, ['ValorTributoNacionalFederal', 'valorTributoNacionalFederal']);
				$vEst = $this->pickMoney($data, ['ValorTributoEstadual', 'valorTributoEstadual']);
				$vMun = $this->pickMoney($data, ['ValorTributoMunicipal', 'valorTributoMunicipal']);

				if (($pctNat <= 0 && $pctEst <= 0 && $pctMun <= 0) && $valor > 0 && ($vNat > 0 || $vEst > 0 || $vMun > 0)) {
					$pctNat = ($vNat / $valor) * 100.0;
					$pctEst = ($vEst / $valor) * 100.0;
					$pctMun = ($vMun / $valor) * 100.0;
				}

				if ($pctNat <= 0 && $pctImp > 0) {
					$pctNat = $pctImp;
				}

				if ($pctNat <= 0 && $pctEst <= 0 && $pctMun <= 0) {
					continue;
				}

				$versao = (string) (isset($data['Versao']) ? $data['Versao'] : (isset($data['versao']) ? $data['versao'] : ''));
				$fonte = 'IBPT (API oficial)';

				return [$pctNat, $pctEst, $pctMun, $versao, $fonte];
			}
		} catch (\Throwable $e) {
			Log::warning('IBPT API: ' . $e->getMessage());
		}

		return null;
	}

	protected function pickPercent(array $row, array $keys) {
		foreach ($keys as $k) {
			foreach ($row as $dk => $dv) {
				if (strcasecmp((string) $dk, $k) === 0 && $dv !== '' && $dv !== null) {
					return (float) str_replace(['%', ','], ['', '.'], (string) $dv);
				}
			}
		}

		return 0.0;
	}

	protected function pickMoney(array $row, array $keys) {
		foreach ($keys as $k) {
			foreach ($row as $dk => $dv) {
				if (strcasecmp((string) $dk, $k) === 0 && $dv !== '' && $dv !== null) {
					if (is_numeric($dv)) {
						return (float) $dv;
					}
					$s = preg_replace('/[^\d,.-]/', '', (string) $dv);
					$s = str_replace('.', '', $s);
					$s = str_replace(',', '.', $s);

					return (float) $s;
				}
			}
		}

		return 0.0;
	}

	protected function normalizeNcm($raw) {
		$d = preg_replace('/\D/', '', (string) $raw);
		if ($d === '') {
			return '00000000';
		}
		if (strlen($d) < 8) {
			return str_pad($d, 8, '0', STR_PAD_LEFT);
		}
		if (strlen($d) > 8) {
			return substr($d, 0, 8);
		}

		return $d;
	}
}
