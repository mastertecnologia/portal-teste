<?php
/**
 * QueenCityCodeFactory CakeSoap — wrapper SoapClient para CakePHP 3.x.
 * Fonte: https://github.com/QueenCityCodeFactory/CakeSoap (MIT)
 *
 * Empacotado num único ficheiro para funcionar só com require_once (sem PSR-4 extra).
 * Correção: autenticação HTTP com SOAP_AUTHENTICATION_BASIC (upstream master estava corrompido).
 */

namespace CakeSoap\Network;

use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Core\InstanceConfigTrait;
use Cake\Log\LogTrait;
use Psr\Log\LogLevel;
use SoapClient as PhpSoapClient;
use SoapFault;

/**
 * SoapClient com log opcional em debug.
 */
class SoapClient extends PhpSoapClient
{
	use LogTrait;

	/**
	 * @param string $request
	 * @param string $location
	 * @param string $action
	 * @param int $version
	 * @param int $oneWay
	 * @return string
	 */
	public function __doRequest($request, $location, $action, $version, $oneWay = 0)
	{
		if (Configure::read('debug') === true) {
			$this->log($request, LogLevel::INFO);
			$this->log($location, LogLevel::INFO);
			$this->log($action, LogLevel::INFO);
			$this->log($version, LogLevel::INFO);
		}

		return parent::__doRequest($request, $location, $action, $version, $oneWay);
	}

	/**
	 * @param string $functionName
	 * @param array $arguments
	 * @param array|null $options
	 * @param mixed $inputHeaders
	 * @param mixed $outputHeaders
	 * @return mixed
	 */
	public function __soapCall($functionName, $arguments, $options = null, $inputHeaders = null, &$outputHeaders = null)
	{
		if (Configure::read('debug') === true) {
			$this->log($functionName, LogLevel::INFO);
			$this->log($arguments, LogLevel::INFO);
			$this->log($options, LogLevel::INFO);
			$this->log($inputHeaders, LogLevel::INFO);
			$this->log($outputHeaders, LogLevel::INFO);
		}

		return parent::__soapCall($functionName, $arguments, $options, $inputHeaders, $outputHeaders);
	}
}

/**
 * Wrapper de alto nível.
 */
class CakeSoap
{
	use InstanceConfigTrait;
	use LogTrait;

	/**
	 * @var bool
	 */
	public $logErrors = false;

	/**
	 * @var SoapClient|null
	 */
	public $client = null;

	/**
	 * @var bool
	 */
	public $connected = false;

	/**
	 * @var bool
	 */
	public $debug = false;

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'wsdl' => null,
		'userAgent' => 'SoapClient',
		'location' => '',
		'uri' => '',
		'login' => '',
		'password' => '',
		'authentication' => '',
		'trace' => false,
	];

	/**
	 * @param array $config
	 * @param array $options
	 */
	public function __construct(array $config = [], array $options = [])
	{
		$this->setConfig($config);

		if (!isset($options['debug'])) {
			$this->debug = (bool)Configure::read('debug');
		}
		if (isset($options['debug']) && $options['debug'] === true) {
			$this->debug = true;
		}
		if (isset($options['logErrors']) && $options['logErrors'] === true) {
			$this->logErrors = true;
		}

		if (!isset($options['options'])) {
			$options['options'] = [];
		}

		$this->connect($options['options']);
	}

	/**
	 * @param array $options
	 * @return array
	 */
	protected function _parseConfig(array $options = [])
	{
		if (!class_exists('SoapClient')) {
			$this->handleError('Class SoapClient not found, please enable Soap extensions');
		}

		$config = $this->getConfig();
		unset($config['wsdl']);

		if (!isset($config['trace'])) {
			$config['trace'] = $this->debug;
		}

		$opts = [];
		if (!empty($config['userAgent']) && empty($options['http'])) {
			$opts['http']['user_agent'] = $this->getConfig('userAgent');
		}
		unset($config['userAgent']);

		$opts += $options;

		if (!empty($opts)) {
			$config['stream_context'] = stream_context_create($opts);
		}

		if (!isset($config['cache_wsdl'])) {
			$config['cache_wsdl'] = WSDL_CACHE_NONE;
		}

		if (empty($config['location'])) {
			unset($config['location']);
		}

		if (empty($config['uri'])) {
			unset($config['uri']);
		}

		if (empty($config['login'])) {
			unset($config['login']);
		}

		if (empty($config['password'])) {
			unset($config['password']);
		}

		if (empty($config['authentication'])) {
			unset($config['authentication']);
		}

		if (empty($config['authentication']) && !empty($config['login'])) {
			$config['authentication'] = SOAP_AUTHENTICATION_BASIC;
		}

		return $config;
	}

	/**
	 * @param array $options
	 * @return bool
	 */
	public function connect(array $options = [])
	{
		$config = $this->_parseConfig($options);
		try {
			$this->client = new SoapClient($this->getConfig('wsdl'), $config);
		} catch (SoapFault $fault) {
			$this->handleError($fault->faultstring);
		}

		if ($this->client) {
			$this->connected = true;
		}

		return $this->connected;
	}

	/**
	 * @return bool
	 */
	public function close()
	{
		$this->client = null;
		$this->connected = false;

		return true;
	}

	/**
	 * @return array
	 */
	public function listSources()
	{
		return $this->client->__getFunctions();
	}

	/**
	 * @param string $action
	 * @param array $data
	 * @return mixed
	 */
	public function sendRequest($action, $data)
	{
		if (!$this->connected) {
			$this->connect();
		}
		try {
			$result = $this->client->__soapCall($action, $data);
		} catch (SoapFault $fault) {
			$this->handleError($fault->faultstring);
		}

		return $result;
	}

	/**
	 * @return string
	 */
	public function getResponse()
	{
		return $this->client->__getLastResponse();
	}

	/**
	 * @return string
	 */
	public function getRequest()
	{
		return $this->client->__getLastRequest();
	}

	/**
	 * @param string|null $error
	 * @return void
	 */
	public function handleError($error = null)
	{
		if ($this->logErrors === true) {
			$this->log($error);
			if ($this->client) {
				$this->log($this->client->__getLastRequest());
			}
		}
		throw new Exception($error);
	}
}
