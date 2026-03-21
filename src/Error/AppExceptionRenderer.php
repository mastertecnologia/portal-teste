<?php
namespace App\Error;

use Cake\Core\Configure;
use Cake\Error\ExceptionRenderer;

/**
 * Garante JSON com mensagem/retorno nas rotas de integração ERP quando o erro
 * ocorre antes do controller (ex.: BodyParser). O Integrador Grid lê "retorno";
 * o ExceptionRenderer padrão do Cake só envia "message", deixando o campo vazio.
 */
class AppExceptionRenderer extends ExceptionRenderer
{

    /**
     * @return \Cake\Http\Response
     */
    public function render()
    {
        $request = $this->controller->getRequest();
        if ($this->_isIntegracaoErpApiPath($request)) {
            return $this->_renderIntegracaoErpApiJson();
        }

        return parent::render();
    }

    /**
     * @param \Cake\Http\ServerRequest $request Request.
     * @return bool
     */
    protected function _isIntegracaoErpApiPath($request)
    {
        $path = $request->getUri()->getPath();

        return (bool) preg_match(
            '#/(clientes|produtos|ordensservico)/(add-api|addAPI|list-api|listAPI|refresh-api|refreshAPI)(?:/|$|\?)#i',
            $path
        );
    }

    /**
     * @return \Cake\Http\Response
     */
    protected function _renderIntegracaoErpApiJson()
    {
        $exception = $this->error;
        $code = $this->_code($exception);
        if ($code < 400 || $code > 599) {
            $code = 500;
        }

        $unwrapped = $this->_unwrap($exception);
        $message = $this->_message($exception, $code);

        $prev = $unwrapped->getPrevious();
        if ($prev !== null && $prev->getMessage() !== '' && $code === 400) {
            $message = $prev->getMessage();
        }

        if ($code === 400 && ($message === 'Bad Request' || $message === '')) {
            $message = 'Requisição inválida: use POST com Content-Type application/json e corpo JSON válido (UTF-8). Em clientes, inclua codibge.';
        }

        $request = $this->controller->getRequest();
        $payload = [
            'mensagem' => $message,
            'retorno' => $message,
            'code' => $code,
        ];
        if (Configure::read('debug')) {
            $payload['url'] = $request->getRequestTarget();
            $payload['file'] = $exception->getFile();
            $payload['line'] = $exception->getLine();
        }

        $response = $this->controller->getResponse()
            ->withType('application/json')
            ->withStatus($code)
            ->withStringBody(json_encode($payload, JSON_UNESCAPED_UNICODE));

        $this->controller->response = $response;

        return $this->_shutdown();
    }
}
