<?php
namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

/**
 * Serve folhas CSS premium a partir de WWW_ROOT/css (evita 404 com Alias /portal + rewrite).
 */
class PgmAssetsController extends AppController
{
	protected static $allowedCss = [
		'produtos-premium' => 'produtos-premium.css',
		'clientes-premium' => 'clientes-premium.css',
		'orcamentos-premium' => 'orcamentos-premium.css',
		'pgm-action-buttons' => 'pgm-action-buttons.css',
	];

	public function initialize()
	{
		parent::initialize();
		if ($this->components()->has('Security')) {
			$this->Security->setConfig('unlockedActions', ['css']);
		}
	}

	public function beforeFilter(Event $event)
	{
		$this->Auth->allow(['css']);
		parent::beforeFilter($event);
	}

	public function css($name = null)
	{
		if ($name === null || !isset(static::$allowedCss[$name])) {
			throw new NotFoundException();
		}
		$file = WWW_ROOT . 'css' . DIRECTORY_SEPARATOR . static::$allowedCss[$name];
		if (!is_readable($file)) {
			throw new NotFoundException();
		}
		$body = file_get_contents($file);
		if ($body === false) {
			throw new NotFoundException();
		}
		$mtime = filemtime($file);

		$this->autoRender = false;
		$this->response = $this->response
			->withType('text/css')
			->withHeader('Cache-Control', 'public, max-age=86400')
			->withHeader('Last-Modified', gmdate('D, d M Y H:i:s', $mtime) . ' GMT')
			->withStringBody($body);

		return $this->response;
	}
}
