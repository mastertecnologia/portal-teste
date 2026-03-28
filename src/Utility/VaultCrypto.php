<?php
namespace App\Utility;

/**
 * Cofre de senhas — criptografia em repouso.
 *
 * - Sem VAULT_ENCRYPTION_KEY: usa criptografaSenha / descriptografaSenha (legado PGM).
 * - Com VAULT_ENCRYPTION_KEY: novos/alterados usam prefixo v2: + AES-256-CBC (IV aleatório por registro).
 *   Registros antigos continuam legíveis via descriptografaSenha.
 *
 * A chave de dados é derivada: SHA-256(VAULT_ENCRYPTION_KEY | idempresa) — isolamento por empresa.
 */
class VaultCrypto {

	const PREFIX_V2 = 'v2:';

	const MIN_KEY_LEN = 16;

	/**
	 * @return bool
	 */
	public static function isDedicatedKeyEnabled() {
		$k = self::_envKey();

		return $k !== null && $k !== '';
	}

	/**
	 * @param string $plain
	 * @param int|string $idempresa
	 * @return string
	 */
	public static function encrypt($plain, $idempresa) {
		$envKey = self::_envKey();
		if ($envKey === null || $envKey === '') {
			if (!function_exists('criptografaSenha')) {
				throw new \RuntimeException('VaultCrypto: criptografaSenha indisponível.');
			}

			return criptografaSenha($plain);
		}
		if (strlen($envKey) < self::MIN_KEY_LEN) {
			throw new \RuntimeException('VAULT_ENCRYPTION_KEY deve ter pelo menos ' . self::MIN_KEY_LEN . ' caracteres.');
		}

		return self::PREFIX_V2 . self::_encryptAes256Cbc($plain, $envKey, $idempresa);
	}

	/**
	 * @param string       $stored
	 * @param int|string   $idempresa
	 * @return string
	 */
	public static function decrypt($stored, $idempresa) {
		if (strpos($stored, self::PREFIX_V2) === 0) {
			$envKey = self::_envKey();
			if ($envKey === null || $envKey === '') {
				throw new \RuntimeException('Registro criptografado com VAULT_ENCRYPTION_KEY; defina a chave no .env.');
			}
			if (strlen($envKey) < self::MIN_KEY_LEN) {
				throw new \RuntimeException('VAULT_ENCRYPTION_KEY inválida.');
			}

			return self::_decryptAes256Cbc(substr($stored, strlen(self::PREFIX_V2)), $envKey, $idempresa);
		}
		if (!function_exists('descriptografaSenha')) {
			throw new \RuntimeException('VaultCrypto: descriptografaSenha indisponível.');
		}

		return descriptografaSenha($stored);
	}

	/**
	 * @return string|null
	 */
	protected static function _envKey() {
		$k = '';
		if (function_exists('env')) {
			$v = env('VAULT_ENCRYPTION_KEY');
			if ($v !== null && $v !== false) {
				$k = trim((string)$v);
			}
		} else {
			$g = getenv('VAULT_ENCRYPTION_KEY');
			if ($g !== false) {
				$k = trim((string)$g);
			}
		}

		return $k === '' ? null : $k;
	}

	/**
	 * @param string       $envKey
	 * @param int|string   $idempresa
	 * @return string binary 32 bytes
	 */
	protected static function _deriveKey($envKey, $idempresa) {
		return hash('sha256', $envKey . '|pgm.vault|' . (int)$idempresa, true);
	}

	/**
	 * @param string       $plain
	 * @param string       $envKey
	 * @param int|string   $idempresa
	 * @return string base64
	 */
	protected static function _encryptAes256Cbc($plain, $envKey, $idempresa) {
		if (!function_exists('openssl_encrypt')) {
			throw new \RuntimeException('OpenSSL não disponível para o cofre.');
		}
		$key = self::_deriveKey($envKey, $idempresa);
		$ivlen = openssl_cipher_iv_length('aes-256-cbc');
		$iv = openssl_random_pseudo_bytes($ivlen);
		$cipher = openssl_encrypt($plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
		if ($cipher === false) {
			throw new \RuntimeException('Falha ao criptografar (AES-256-CBC).');
		}

		return base64_encode($iv . $cipher);
	}

	/**
	 * @param string       $b64
	 * @param string       $envKey
	 * @param int|string   $idempresa
	 * @return string
	 */
	protected static function _decryptAes256Cbc($b64, $envKey, $idempresa) {
		$raw = base64_decode($b64, true);
		if ($raw === false) {
			throw new \RuntimeException('Payload do cofre inválido (base64).');
		}
		$ivlen = openssl_cipher_iv_length('aes-256-cbc');
		if (strlen($raw) < $ivlen) {
			throw new \RuntimeException('Payload do cofre corrompido.');
		}
		$iv = substr($raw, 0, $ivlen);
		$cipher = substr($raw, $ivlen);
		$key = self::_deriveKey($envKey, $idempresa);
		$plain = openssl_decrypt($cipher, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
		if ($plain === false) {
			throw new \RuntimeException('Falha ao descriptografar — chave incorreta ou dado alterado.');
		}

		return $plain;
	}
}
