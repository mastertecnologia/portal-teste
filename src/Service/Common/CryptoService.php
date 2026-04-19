<?php
namespace App\Service\Common;

use App\Utility\VaultCrypto;
use Cake\Core\Configure;
use Cake\Log\Log;

/**
 * Crypto Service - Standardized encryption/decryption interface
 * 
 * Provides a unified interface for encryption operations using VaultCrypto.
 * Supports both modern AES-256 encryption and legacy encryption methods
 * for backward compatibility.
 */
class CryptoService
{
    /**
     * Encrypt data using the best available method
     * 
     * @param string $plainText The data to encrypt
     * @param int|string $idEmpresa Company ID for key derivation
     * @return string Encrypted data
     * @throws \RuntimeException If encryption fails
     */
    public static function encrypt($plainText, $idEmpresa)
    {
        if (empty($plainText)) {
            return '';
        }

        try {
            return VaultCrypto::encrypt($plainText, $idEmpresa);
        } catch (\Exception $e) {
            Log::error('CryptoService: Encryption failed', [
                'error' => $e->getMessage(),
                'id_empresa' => $idEmpresa
            ]);
            
            throw new \RuntimeException('Failed to encrypt data: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Decrypt data using the appropriate method
     * 
     * @param string $encryptedData The encrypted data
     * @param int|string $idEmpresa Company ID for key derivation
     * @return string Decrypted data
     * @throws \RuntimeException If decryption fails
     */
    public static function decrypt($encryptedData, $idEmpresa)
    {
        if (empty($encryptedData)) {
            return '';
        }

        try {
            return VaultCrypto::decrypt($encryptedData, $idEmpresa);
        } catch (\Exception $e) {
            Log::error('CryptoService: Decryption failed', [
                'error' => $e->getMessage(),
                'id_empresa' => $idEmpresa
            ]);
            
            throw new \RuntimeException('Failed to decrypt data: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Check if modern AES-256 encryption is enabled
     * 
     * @return bool True if VAULT_ENCRYPTION_KEY is configured
     */
    public static function isModernEncryptionEnabled()
    {
        return VaultCrypto::isDedicatedKeyEnabled();
    }

    /**
     * Encrypt password with additional validation
     * 
     * @param string $password The password to encrypt
     * @param int|string $idEmpresa Company ID for key derivation
     * @return string Encrypted password
     * @throws \InvalidArgumentException If password is empty
     */
    public static function encryptPassword($password, $idEmpresa)
    {
        if (empty($password)) {
            throw new \InvalidArgumentException('Password cannot be empty');
        }

        return self::encrypt($password, $idEmpresa);
    }

    /**
     * Decrypt password with additional validation
     * 
     * @param string $encryptedPassword The encrypted password
     * @param int|string $idEmpresa Company ID for key derivation
     * @return string Decrypted password
     * @throws \InvalidArgumentException If encrypted password is empty
     */
    public static function decryptPassword($encryptedPassword, $idEmpresa)
    {
        if (empty($encryptedPassword)) {
            throw new \InvalidArgumentException('Encrypted password cannot be empty');
        }

        return self::decrypt($encryptedPassword, $idEmpresa);
    }

    /**
     * Encrypt sensitive data (API keys, tokens, etc.)
     * 
     * @param string $sensitiveData The sensitive data to encrypt
     * @param int|string $idEmpresa Company ID for key derivation
     * @return string Encrypted data
     */
    public static function encryptSensitiveData($sensitiveData, $idEmpresa)
    {
        if (empty($sensitiveData)) {
            return '';
        }

        // Ensure we're using the strongest encryption available
        if (!self::isModernEncryptionEnabled()) {
            Log::warning('CryptoService: Modern encryption not available for sensitive data', [
                'id_empresa' => $idEmpresa
            ]);
        }

        return self::encrypt($sensitiveData, $idEmpresa);
    }

    /**
     * Decrypt sensitive data (API keys, tokens, etc.)
     * 
     * @param string $encryptedData The encrypted sensitive data
     * @param int|string $idEmpresa Company ID for key derivation
     * @return string Decrypted data
     */
    public static function decryptSensitiveData($encryptedData, $idEmpresa)
    {
        if (empty($encryptedData)) {
            return '';
        }

        return self::decrypt($encryptedData, $idEmpresa);
    }

    /**
     * Generate a secure random token
     * 
     * @param int $length Token length in bytes
     * @return string Base64 encoded token
     */
    public static function generateSecureToken($length = 32)
    {
        if ($length < 16) {
            throw new \InvalidArgumentException('Token length must be at least 16 bytes');
        }

        $bytes = random_bytes($length);
        return base64_encode($bytes);
    }

    /**
     * Generate a secure random hexadecimal token
     * 
     * @param int $length Token length in characters
     * @return string Hexadecimal token
     */
    public static function generateHexToken($length = 64)
    {
        if ($length < 32) {
            throw new \InvalidArgumentException('Hex token length must be at least 32 characters');
        }

        $byteLength = ceil($length / 2);
        $bytes = random_bytes($byteLength);
        return substr(bin2hex($bytes), 0, $length);
    }

    /**
     * Hash a password using the best available algorithm
     * 
     * @param string $password The password to hash
     * @return string Hashed password
     * @throws \RuntimeException If hashing fails
     */
    public static function hashPassword($password)
    {
        if (empty($password)) {
            throw new \InvalidArgumentException('Password cannot be empty');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        if ($hash === false) {
            throw new \RuntimeException('Failed to hash password');
        }

        return $hash;
    }

    /**
     * Verify a password against its hash
     * 
     * @param string $password The password to verify
     * @param string $hash The hash to verify against
     * @return bool True if password matches hash
     */
    public static function verifyPassword($password, $hash)
    {
        if (empty($password) || empty($hash)) {
            return false;
        }

        return password_verify($password, $hash);
    }

    /**
     * Check if a password needs rehashing
     * 
     * @param string $hash The hash to check
     * @return bool True if password needs rehashing
     */
    public static function passwordNeedsRehash($hash)
    {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    /**
     * Encrypt data for storage in database with metadata
     * 
     * @param string $data The data to encrypt
     * @param int|string $idEmpresa Company ID for key derivation
     * @param array $metadata Optional metadata to store with the encrypted data
     * @return array{encrypted: string, metadata: array}
     */
    public static function encryptWithMetadata($data, $idEmpresa, array $metadata = [])
    {
        $encrypted = self::encrypt($data, $idEmpresa);
        
        $defaultMetadata = [
            'encrypted_at' => date('Y-m-d H:i:s'),
            'encryption_method' => self::isModernEncryptionEnabled() ? 'AES-256-CBC' : 'Legacy',
            'id_empresa' => $idEmpresa
        ];

        $finalMetadata = array_merge($defaultMetadata, $metadata);

        return [
            'encrypted' => $encrypted,
            'metadata' => $finalMetadata
        ];
    }

    /**
     * Decrypt data with metadata validation
     * 
     * @param array $encryptedData Array with 'encrypted' and 'metadata' keys
     * @param int|string $idEmpresa Company ID for key derivation
     * @return string Decrypted data
     * @throws \InvalidArgumentException If data format is invalid
     */
    public static function decryptWithMetadata($encryptedData, $idEmpresa)
    {
        if (!is_array($encryptedData) || !isset($encryptedData['encrypted'])) {
            throw new \InvalidArgumentException('Invalid encrypted data format');
        }

        $decrypted = self::decrypt($encryptedData['encrypted'], $idEmpresa);

        // Optional: Validate metadata if present
        if (isset($encryptedData['metadata']['id_empresa'])) {
            if ((int)$encryptedData['metadata']['id_empresa'] !== (int)$idEmpresa) {
                Log::warning('CryptoService: Company ID mismatch in metadata', [
                    'expected' => $idEmpresa,
                    'found' => $encryptedData['metadata']['id_empresa']
                ]);
            }
        }

        return $decrypted;
    }

    /**
     * Get encryption configuration status
     * 
     * @return array{modern_enabled: bool, key_configured: bool, recommendations: array}
     */
    public static function getEncryptionStatus()
    {
        $modernEnabled = self::isModernEncryptionEnabled();
        $recommendations = [];

        if (!$modernEnabled) {
            $recommendations[] = 'Configure VAULT_ENCRYPTION_KEY in .env for AES-256 encryption';
            $recommendations[] = 'Consider migrating existing encrypted data to modern format';
        }

        return [
            'modern_enabled' => $modernEnabled,
            'key_configured' => $modernEnabled,
            'recommendations' => $recommendations
        ];
    }

    /**
     * Legacy function compatibility wrapper
     * 
     * @param string $data Data to encrypt
     * @return string Encrypted data using legacy method
     */
    public static function criptografaSenha($data)
    {
        if (function_exists('criptografaSenha')) {
            return criptografaSenha($data);
        }

        // Fallback to modern encryption if legacy function not available
        Log::warning('CryptoService: Legacy criptografaSenha function not available, using modern encryption');
        return self::encrypt($data, 0);
    }

    /**
     * Legacy function compatibility wrapper
     * 
     * @param string $data Data to decrypt
     * @return string Decrypted data using legacy method
     */
    public static function descriptografaSenha($data)
    {
        if (function_exists('descriptografaSenha')) {
            return descriptografaSenha($data);
        }

        // Fallback to modern encryption if legacy function not available
        Log::warning('CryptoService: Legacy descriptografaSenha function not available, using modern encryption');
        return self::decrypt($data, 0);
    }
}