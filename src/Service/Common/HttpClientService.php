<?php
namespace App\Service\Common;

use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\Core\Configure;
use Cake\Log\Log;

/**
 * HTTP Client Service - Secure wrapper around CakePHP HttpClient
 * 
 * Replaces direct curl_exec usage with a secure, maintainable HTTP client.
 * Provides consistent error handling, timeout configuration, and SSL verification.
 */
class HttpClientService
{
    /**
     * Default timeout in seconds
     */
    const DEFAULT_TIMEOUT = 30;

    /**
     * Default connection timeout in seconds
     */
    const DEFAULT_CONNECT_TIMEOUT = 10;

    /**
     * HTTP Client instance
     * @var \Cake\Http\Client
     */
    private static $client = null;

    /**
     * Get or create HTTP client instance
     * 
     * @param array $config Optional configuration overrides
     * @return \Cake\Http\Client
     */
    private static function getClient(array $config = [])
    {
        if (self::$client === null) {
            $defaultConfig = [
                'timeout' => self::DEFAULT_TIMEOUT,
                'ssl_verify_peer' => true,
                'ssl_verify_peer_name' => true,
                'redirect' => true,
                'max_redirects' => 5,
            ];

            // Apply configuration from Configure if available
            $appConfig = Configure::read('HttpClient', []);
            $config = array_merge($defaultConfig, $appConfig, $config);

            self::$client = new Client($config);
        }

        return self::$client;
    }

    /**
     * Perform GET request
     * 
     * @param string $url The URL to request
     * @param array $query Query parameters
     * @param array $options Request options
     * @return array{success: bool, data: mixed, status: int, error: string|null}
     */
    public static function get($url, array $query = [], array $options = [])
    {
        try {
            $client = self::getClient($options);
            $response = $client->get($url, $query, $options);
            
            return self::formatResponse($response);
        } catch (\Exception $e) {
            return self::formatError($e);
        }
    }

    /**
     * Perform POST request
     * 
     * @param string $url The URL to request
     * @param array|string $data Request data
     * @param array $options Request options
     * @return array{success: bool, data: mixed, status: int, error: string|null}
     */
    public static function post($url, $data = [], array $options = [])
    {
        try {
            $client = self::getClient($options);
            
            // Auto-encode JSON for array data if content-type is JSON
            if (is_array($data) && (!isset($options['type']) || $options['type'] === 'json')) {
                $options['type'] = 'json';
            }
            
            $response = $client->post($url, $data, $options);
            
            return self::formatResponse($response);
        } catch (\Exception $e) {
            return self::formatError($e);
        }
    }

    /**
     * Perform PUT request
     * 
     * @param string $url The URL to request
     * @param array|string $data Request data
     * @param array $options Request options
     * @return array{success: bool, data: mixed, status: int, error: string|null}
     */
    public static function put($url, $data = [], array $options = [])
    {
        try {
            $client = self::getClient($options);
            
            if (is_array($data) && (!isset($options['type']) || $options['type'] === 'json')) {
                $options['type'] = 'json';
            }
            
            $response = $client->put($url, $data, $options);
            
            return self::formatResponse($response);
        } catch (\Exception $e) {
            return self::formatError($e);
        }
    }

    /**
     * Perform DELETE request
     * 
     * @param string $url The URL to request
     * @param array $options Request options
     * @return array{success: bool, data: mixed, status: int, error: string|null}
     */
    public static function delete($url, array $options = [])
    {
        try {
            $client = self::getClient($options);
            $response = $client->delete($url, [], $options);
            
            return self::formatResponse($response);
        } catch (\Exception $e) {
            return self::formatError($e);
        }
    }

    /**
     * Perform multipart/form-data request (for file uploads)
     * 
     * @param string $url The URL to request
     * @param array $data Form data including files
     * @param array $options Request options
     * @return array{success: bool, data: mixed, status: int, error: string|null}
     */
    public static function multipart($url, array $data = [], array $options = [])
    {
        try {
            $client = self::getClient($options);
            
            // Set headers for multipart form data
            $options['headers'] = $options['headers'] ?? [];
            $options['headers']['Content-Type'] = 'multipart/form-data';
            
            $response = $client->post($url, $data, $options);
            
            return self::formatResponse($response);
        } catch (\Exception $e) {
            return self::formatError($e);
        }
    }

    /**
     * Format successful response
     * 
     * @param \Cake\Http\Client\Response $response
     * @return array{success: bool, data: mixed, status: int, error: string|null}
     */
    private static function formatResponse(Response $response)
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getStringBody();
        $data = null;
        $error = null;

        // Try to parse JSON response
        if (strpos($response->getHeaderLine('Content-Type'), 'application/json') !== false) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $decoded;
            } else {
                $data = $body;
                $error = 'Invalid JSON response: ' . json_last_error_msg();
            }
        } else {
            $data = $body;
        }

        // Consider 2xx and 3xx status codes as success
        $success = $statusCode >= 200 && $statusCode < 400;

        // Log errors for non-successful responses
        if (!$success) {
            Log::error('HttpClientService: HTTP request failed', [
                'status' => $statusCode,
                'body' => substr($body, 0, 500), // Limit log size
                'url' => $response->getUri() ?? 'unknown'
            ]);
        }

        return [
            'success' => $success,
            'data' => $data,
            'status' => $statusCode,
            'error' => $error
        ];
    }

    /**
     * Format error response
     * 
     * @param \Exception $e
     * @return array{success: bool, data: null, status: int, error: string}
     */
    private static function formatError(\Exception $e)
    {
        $errorMessage = $e->getMessage();
        
        Log::error('HttpClientService: Exception occurred', [
            'message' => $errorMessage,
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);

        return [
            'success' => false,
            'data' => null,
            'status' => 0,
            'error' => $errorMessage
        ];
    }

    /**
     * Set custom headers for all subsequent requests
     * 
     * @param array $headers Associative array of headers
     * @return void
     */
    public static function setHeaders(array $headers)
    {
        $client = self::getClient();
        $client->config('headers', $headers);
    }

    /**
     * Set custom timeout for all subsequent requests
     * 
     * @param int $timeout Timeout in seconds
     * @return void
     */
    public static function setTimeout($timeout)
    {
        $client = self::getClient();
        $client->config('timeout', $timeout);
    }

    /**
     * Reset client instance (useful for testing)
     * 
     * @return void
     */
    public static function reset()
    {
        self::$client = null;
    }

    /**
     * Legacy curl_exec replacement method
     * 
     * Provides a drop-in replacement for existing curl_exec calls
     * 
     * @param string $url URL to request
     * @param array $curlOpts curl_setopt_array options (converted to HttpClient options)
     * @return array{success: bool, data: mixed, status: int, error: string|null}
     */
    public static function curlExec($url, array $curlOpts = [])
    {
        $options = [];
        $method = 'GET';
        $data = null;

        // Convert curl options to HttpClient options
        if (isset($curlOpts[CURLOPT_POST]) && $curlOpts[CURLOPT_POST]) {
            $method = 'POST';
        }

        if (isset($curlOpts[CURLOPT_POSTFIELDS])) {
            $data = $curlOpts[CURLOPT_POSTFIELDS];
        }

        if (isset($curlOpts[CURLOPT_TIMEOUT])) {
            $options['timeout'] = $curlOpts[CURLOPT_TIMEOUT];
        }

        if (isset($curlOpts[CURLOPT_HTTPHEADER]) && is_array($curlOpts[CURLOPT_HTTPHEADER])) {
            $headers = [];
            foreach ($curlOpts[CURLOPT_HTTPHEADER] as $header) {
                if (strpos($header, ':') !== false) {
                    list($name, $value) = explode(':', $header, 2);
                    $headers[trim($name)] = trim($value);
                }
            }
            if (!empty($headers)) {
                $options['headers'] = $headers;
            }
        }

        if (isset($curlOpts[CURLOPT_SSL_VERIFYPEER])) {
            $options['ssl_verify_peer'] = $curlOpts[CURLOPT_SSL_VERIFYPEER];
        }

        switch ($method) {
            case 'POST':
                return self::post($url, $data, $options);
            default:
                return self::get($url, [], $options);
        }
    }
}