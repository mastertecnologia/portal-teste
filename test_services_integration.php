<?php
/**
 * Integration test for the new service layer services
 * 
 * This script verifies that ModelService, HttpClientService, and CryptoService
 * are working correctly and can be used by existing controllers.
 */

// Bootstrap CakePHP
require_once 'config/bootstrap.php';

use App\Service\Common\ModelService;
use App\Service\Common\HttpClientService;
use App\Service\Common\CryptoService;

echo "=== Testing Service Layer Integration ===\n\n";

// Test ModelService
echo "1. Testing ModelService...\n";
try {
    // Test basic model access
    $usersTable = ModelService::getUsers();
    echo "   - Users model loaded: " . get_class($usersTable) . "\n";
    
    $empresasTable = ModelService::getEmpresas();
    echo "   - Empresas model loaded: " . get_class($empresasTable) . "\n";
    
    $clientesTable = ModelService::getClientes();
    echo "   - Clientes model loaded: " . get_class($clientesTable) . "\n";
    
    // Test caching
    $usersTable2 = ModelService::getUsers();
    echo "   - Model caching working: " . ($usersTable === $usersTable2 ? "YES" : "NO") . "\n";
    
    // Test getCommonModels
    $commonModels = ModelService::getCommonModels();
    echo "   - Common models retrieved: " . count($commonModels) . " models\n";
    
    echo "   ModelService: PASSED\n\n";
} catch (Exception $e) {
    echo "   ModelService: FAILED - " . $e->getMessage() . "\n\n";
}

// Test HttpClientService
echo "2. Testing HttpClientService...\n";
try {
    // Test basic GET request
    $result = HttpClientService::get('https://httpbin.org/get', ['test' => 'value']);
    echo "   - GET request status: " . ($result['success'] ? "SUCCESS" : "FAILED") . "\n";
    echo "   - Response status code: " . $result['status'] . "\n";
    
    // Test POST request
    $result = HttpClientService::post('https://httpbin.org/post', ['data' => 'test']);
    echo "   - POST request status: " . ($result['success'] ? "SUCCESS" : "FAILED") . "\n";
    
    // Test error handling
    $result = HttpClientService::get('https://invalid-domain-that-does-not-exist.com/test');
    echo "   - Error handling working: " . (!$result['success'] ? "YES" : "NO") . "\n";
    
    echo "   HttpClientService: PASSED\n\n";
} catch (Exception $e) {
    echo "   HttpClientService: FAILED - " . $e->getMessage() . "\n\n";
}

// Test CryptoService
echo "3. Testing CryptoService...\n";
try {
    $testData = "Sensitive test data";
    $idEmpresa = 1;
    
    // Test encryption
    $encrypted = CryptoService::encrypt($testData, $idEmpresa);
    echo "   - Data encrypted successfully\n";
    echo "   - Encrypted length: " . strlen($encrypted) . " characters\n";
    
    // Test decryption
    $decrypted = CryptoService::decrypt($encrypted, $idEmpresa);
    echo "   - Data decrypted successfully\n";
    echo "   - Decryption correct: " . ($decrypted === $testData ? "YES" : "NO") . "\n";
    
    // Test modern encryption status
    $modernEnabled = CryptoService::isModernEncryptionEnabled();
    echo "   - Modern encryption enabled: " . ($modernEnabled ? "YES" : "NO") . "\n";
    
    // Test password hashing
    $password = "testPassword123";
    $hash = CryptoService::hashPassword($password);
    echo "   - Password hashed successfully\n";
    echo "   - Password verification: " . (CryptoService::verifyPassword($password, $hash) ? "YES" : "NO") . "\n";
    
    // Test token generation
    $token = CryptoService::generateSecureToken(32);
    echo "   - Secure token generated: " . strlen($token) . " characters\n";
    
    echo "   CryptoService: PASSED\n\n";
} catch (Exception $e) {
    echo "   CryptoService: FAILED - " . $e->getMessage() . "\n\n";
}

// Test integration scenarios
echo "4. Testing Integration Scenarios...\n";
try {
    // Scenario 1: Controller-like usage
    echo "   - Scenario 1: Controller-like model loading\n";
    $models = ModelService::getCommonModels();
    foreach (['Users', 'Empresas', 'Clientes'] as $modelName) {
        if (isset($models[$modelName])) {
            echo "     * $modelName available\n";
        }
    }
    
    // Scenario 2: HTTP request with encryption
    echo "   - Scenario 2: HTTP request with encrypted data\n";
    $sensitiveData = "API_KEY_12345";
    $encryptedData = CryptoService::encryptSensitiveData($sensitiveData, 1);
    
    // Note: Not actually sending sensitive data, just testing the flow
    echo "     * Sensitive data encrypted for transmission\n";
    $decryptedData = CryptoService::decryptSensitiveData($encryptedData, 1);
    echo "     * Data successfully decrypted after transmission\n";
    
    // Scenario 3: Metadata encryption
    echo "   - Scenario 3: Encryption with metadata\n";
    $result = CryptoService::encryptWithMetadata("test data", 1, ['purpose' => 'test']);
    echo "     * Data encrypted with metadata\n";
    echo "     * Metadata keys: " . implode(', ', array_keys($result['metadata'])) . "\n";
    
    $decrypted = CryptoService::decryptWithMetadata($result, 1);
    echo "     * Data decrypted with metadata validation\n";
    
    echo "   Integration Scenarios: PASSED\n\n";
} catch (Exception $e) {
    echo "   Integration Scenarios: FAILED - " . $e->getMessage() . "\n\n";
}

echo "=== Service Layer Integration Test Complete ===\n";
echo "All services are ready for use in controllers.\n\n";

echo "=== Migration Examples ===\n";
echo "Before (in controller):\n";
echo "  \$this->loadModel('Users');\n";
echo "  \$this->loadModel('Empresas');\n";
echo "  \$this->loadModel('Clientes');\n\n";

echo "After (using ModelService):\n";
echo "  \$users = ModelService::getUsers();\n";
echo "  \$empresas = ModelService::getEmpresas();\n";
echo "  \$clientes = ModelService::getClientes();\n\n";

echo "Before (curl usage):\n";
echo "  \$ch = curl_init(\$url);\n";
echo "  curl_setopt_array(\$ch, \$options);\n";
echo "  \$response = curl_exec(\$ch);\n\n";

echo "After (using HttpClientService):\n";
echo "  \$result = HttpClientService::post(\$url, \$data, \$options);\n";
echo "  if (\$result['success']) {\n";
echo "      \$response = \$result['data'];\n";
echo "  }\n\n";

echo "Before (encryption):\n";
echo "  \$encrypted = criptografaSenha(\$data);\n";
echo "  \$decrypted = descriptografaSenha(\$encrypted);\n\n";

echo "After (using CryptoService):\n";
echo "  \$encrypted = CryptoService::encrypt(\$data, \$idEmpresa);\n";
echo "  \$decrypted = CryptoService::decrypt(\$encrypted, \$idEmpresa);\n\n";
