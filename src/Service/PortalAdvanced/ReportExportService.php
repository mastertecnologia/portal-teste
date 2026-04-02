<?php
namespace App\Service\PortalAdvanced;

/**
 * Exportação CSV (relatórios / portal). Sem dependência de controller.
 */
class ReportExportService {

	/**
	 * Escreve cabeçalho + linhas num resource já aberto (ex.: php://output ou ficheiro).
	 *
	 * @param resource $handle
	 * @param string[] $headers
	 * @param iterable $rows Cada elemento: array de escalares (ordem igual a $headers)
	 * @return int Número de linhas de dados escritas
	 */
	public static function writeCsv($handle, array $headers, iterable $rows): int {
		if (!is_resource($handle)) {
			return 0;
		}
		fputcsv($handle, $headers);
		$n = 0;
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$flat = [];
			foreach ($headers as $i => $h) {
				$flat[] = isset($row[$i]) ? $row[$i] : ($row[$h] ?? '');
			}
			fputcsv($handle, $flat);
			$n++;
		}

		return $n;
	}

	/**
	 * Grava CSV em ficheiro temporário (TMP). Retorna caminho completo ou null.
	 *
	 * @param string[] $headers
	 * @param iterable $rows
	 */
	public static function writeCsvToTmp(string $basename, array $headers, iterable $rows): ?string {
		$safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
		if ($safe === '') {
			$safe = 'export';
		}
		$path = TMP . $safe . '_' . date('YmdHis') . '.csv';
		$fp = @fopen($path, 'wb');
		if ($fp === false) {
			return null;
		}
		self::writeCsv($fp, $headers, $rows);
		fclose($fp);

		return $path;
	}

	public static function sanitizeDownloadFilename(string $name): string {
		$s = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);

		return $s !== '' ? $s : 'export.csv';
	}
}
