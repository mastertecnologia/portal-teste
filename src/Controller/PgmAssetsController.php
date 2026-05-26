<?php
namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

/**
 * Serve folhas CSS premium a partir de WWW_ROOT/css e JS do módulo clientes/edit
 * (evita 404 estático com APP_BASE=/portal e Alias Apache, igual ao padrão /pgm-assets/css).
 */
class PgmAssetsController extends AppController
{
	/** @var string[] */
	protected static $allowedClientesModuleJs = [
		'cliente-edit.js',
		'cliente-edit-ficha.js',
		'cliente-edit-ficha-acessos.js',
		'cliente-visao360-anexo.js',
	];

	protected static $allowedCss = [
		'produtos-premium' => 'produtos-premium.css',
		'clientes-premium' => 'clientes-premium.css',
		'clientes-layout-unificado' => 'clientes-layout-unificado.css',
		'orcamentos-premium' => 'orcamentos-premium.css',
		'pgm-action-buttons' => 'pgm-action-buttons.css',
		'pgm-estoque' => 'pgm-estoque.css',
		'ativos-premium' => 'ativos-premium.css',
	];

	/** @var string[] slug => path relativo a WWW_ROOT ou webroot/ */
	protected static $allowedDistCss = [
		'style-min' => 'dist/css/style.min.css',
		'pgm-erp-prototype' => 'dist/css/pgm-erp-prototype.css',
		'pgm-servicedesk-prototype' => 'dist/css/pages/pgm-servicedesk-prototype.css',
	];

	public function initialize()
	{
		parent::initialize();
		if ($this->components()->has('Security')) {
			$this->Security->setConfig('unlockedActions', ['css', 'legacyCss', 'clientesModuleJs', 'distCss', 'manifestErp']);
		}
	}

	public function beforeFilter(Event $event)
	{
		$this->Auth->allow(['css', 'legacyCss', 'clientesModuleJs', 'distCss', 'manifestErp']);
		parent::beforeFilter($event);
	}

	/**
	 * Atende URLs antigas /css/produtos-premium.css etc. (cache de página, bookmarks).
	 *
	 * @param string|null $file Nome do ficheiro com extensão .css
	 * @return \Cake\Http\Response
	 */
	public function legacyCss($file = null)
	{
		$map = [
			'produtos-premium.css' => 'produtos-premium',
			'clientes-premium.css' => 'clientes-premium',
			'clientes-layout-unificado.css' => 'clientes-layout-unificado',
			'orcamentos-premium.css' => 'orcamentos-premium',
			'pgm-action-buttons.css' => 'pgm-action-buttons',
			'pgm-estoque.css' => 'pgm-estoque',
			'ativos-premium.css' => 'ativos-premium',
		];
		if ($file === null || !isset($map[$file])) {
			throw new NotFoundException();
		}

		return $this->css($map[$file]);
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

		return $this->respondStaticFile($file, 'text/css');
	}

	/**
	 * GET /pgm-assets/dist/:name — CSS em public/dist (protótipo ERP/SD com APP_BASE=/portal).
	 *
	 * @param string|null $name
	 * @return \Cake\Http\Response
	 */
	public function distCss($name = null)
	{
		if ($name === null || !isset(static::$allowedDistCss[$name])) {
			throw new NotFoundException();
		}
		$file = static::distAssetPath(static::$allowedDistCss[$name]);
		if ($file === null) {
			throw new NotFoundException();
		}

		return $this->respondStaticFile($file, 'text/css');
	}

	/**
	 * GET /manifest-erp.json — PWA manifest do shell erp_prototype.
	 *
	 * @return \Cake\Http\Response
	 */
	public function manifestErp()
	{
		$file = static::distAssetPath('manifest-erp.json');
		if ($file === null) {
			throw new NotFoundException();
		}

		return $this->respondStaticFile($file, 'application/manifest+json');
	}

	/**
	 * @param string $relative ex.: dist/css/style.min.css
	 * @return string|null
	 */
	protected static function distAssetPath($relative)
	{
		$relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
		$inWww = WWW_ROOT . $relative;
		if (is_readable($inWww)) {
			return $inWww;
		}
		$inWebroot = ROOT . DIRECTORY_SEPARATOR . 'webroot' . DIRECTORY_SEPARATOR . $relative;
		if (is_readable($inWebroot)) {
			return $inWebroot;
		}

		return null;
	}

	/**
	 * @param string $file Caminho absoluto
	 * @param string $mime
	 * @return \Cake\Http\Response
	 */
	protected function respondStaticFile($file, $mime)
	{
		$body = file_get_contents($file);
		if ($body === false) {
			throw new NotFoundException();
		}
		$mtime = filemtime($file);

		$this->autoRender = false;
		$this->response = $this->response
			->withType($mime)
			->withHeader('Cache-Control', 'public, max-age=86400')
			->withHeader('Last-Modified', gmdate('D, d M Y H:i:s', $mtime) . ' GMT')
			->withStringBody($body);

		return $this->response;
	}

	/**
	 * Caminho físico do JS do módulo clientes (WWW_ROOT ou webroot/ na raiz do projeto).
	 *
	 * Em produção com WEBROOT_DIR=public, os ficheiros podem estar só em webroot/js/... no repositório.
	 *
	 * @param string $file ex.: cliente-edit-ficha.js
	 * @return string|null
	 */
	protected static function clientesModuleJsPath($file)
	{
		$relative = 'js' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'clientes' . DIRECTORY_SEPARATOR . $file;
		$inWww = WWW_ROOT . $relative;
		if (is_readable($inWww)) {
			return $inWww;
		}
		$inWebroot = ROOT . DIRECTORY_SEPARATOR . 'webroot' . DIRECTORY_SEPARATOR . $relative;
		if (is_readable($inWebroot)) {
			return $inWebroot;
		}

		return null;
	}

	/**
	 * GET /pgm-assets/js/modules/clientes/*.js (ou /js/modules/clientes/*.js) — ficheiros em WWW_ROOT ou webroot/.
	 *
	 * @param string|null $file Nome do ficheiro (ex.: cliente-edit-ficha.js)
	 * @return \Cake\Http\Response
	 */
	public function clientesModuleJs($file = null)
	{
		if ($file === null || !in_array($file, static::$allowedClientesModuleJs, true)) {
			throw new NotFoundException();
		}
		$full = static::clientesModuleJsPath($file);
		if ($full === null) {
			throw new NotFoundException();
		}
		$body = file_get_contents($full);
		if ($body === false) {
			throw new NotFoundException();
		}
		$mtime = filemtime($full);

		$this->autoRender = false;
		$this->response = $this->response
			->withType('application/javascript; charset=utf-8')
			->withHeader('Cache-Control', 'public, max-age=86400')
			->withHeader('Last-Modified', gmdate('D, d M Y H:i:s', $mtime) . ' GMT')
			->withStringBody($body);

		return $this->response;
	}
}
