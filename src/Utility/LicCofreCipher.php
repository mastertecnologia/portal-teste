<?php
declare(strict_types=1);

namespace App\Utility;

/**
 * Cofre: AES-256-GCM quando LIC_COFRE_CIPHER_KEY está definida; senão prefixo b64: (protótipo).
 */
class LicCofreCipher {

	public static function encrypt(string $plain): string {
		$key = trim((string)env('LIC_COFRE_CIPHER_KEY', ''));
		if ($plain === '') {
			return '';
		}
		if ($key === '') {
			return 'b64:' . base64_encode($plain);
		}
		$keyBin = hash('sha256', $key, true);
		$iv = random_bytes(12);
		$tag = '';
		$cipher = openssl_encrypt($plain, 'aes-256-gcm', $keyBin, OPENSSL_RAW_DATA, $iv, $tag);
		if ($cipher === false) {
			return 'b64:' . base64_encode($plain);
		}

		return 'gcm:' . base64_encode($iv . $tag . $cipher);
	}

	public static function decrypt(?string $stored): ?string {
		if ($stored === null || $stored === '') {
			return null;
		}
		$stored = (string)$stored;
		if (strpos($stored, 'gcm:') === 0) {
			$raw = base64_decode(substr($stored, 4), true);
			if ($raw === false || strlen($raw) < 28) {
				return null;
			}
			$key = trim((string)env('LIC_COFRE_CIPHER_KEY', ''));
			if ($key === '') {
				return null;
			}
			$keyBin = hash('sha256', $key, true);
			$iv = substr($raw, 0, 12);
			$tag = substr($raw, 12, 16);
			$cipher = substr($raw, 28);
			$plain = openssl_decrypt($cipher, 'aes-256-gcm', $keyBin, OPENSSL_RAW_DATA, $iv, $tag);
			if ($plain === false) {
				return null;
			}

			return $plain;
		}
		if (strpos($stored, 'b64:') === 0) {
			$decoded = base64_decode(substr($stored, 4), true);

			return $decoded !== false && $decoded !== '' ? $decoded : null;
		}
		$legacy = base64_decode($stored, true);

		return $legacy !== false && $legacy !== '' ? $legacy : null;
	}
}
