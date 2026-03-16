<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Service\Cadastro\ConsultaEmpresaService;
use App\Utility\TextoUtil;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

/**
 * API de cadastro consolidado: dados da empresa por CNPJ (Receita + IE + IM).
 * Endpoints: GET /api/cadastro/empresa/:cnpj e POST /api/cadastro/empresa/consultar
 */
class CadastroController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Cidades');
        $this->loadModel('Estados');
    }

    /**
     * GET /api/cadastro/empresa/:cnpj
     * Query: consultar_ie, consultar_im, usar_cache (opcionais, 1/0)
     */
    public function empresa($cnpj = null)
    {
        $this->request->allowMethod(['get']);
        $this->autoRender = false;

        $consultarIe = $this->request->getQuery('consultar_ie', '1') !== '0';
        $consultarIm = $this->request->getQuery('consultar_im', '1') !== '0';
        $usarCache = $this->request->getQuery('usar_cache', '1') !== '0';

        try {
            $service = $this->buildService();
            $resposta = $service->consultar((string)$cnpj, [
                'consultar_ie' => $consultarIe,
                'consultar_im' => $consultarIm,
                'usar_cache' => $usarCache,
            ]);
        } catch (\Throwable $e) {
            Log::write('error', '[CadastroController::empresa] ' . $e->getMessage(), ['scope' => ['cadastro_empresa']]);
            return $this->jsonResponse([
                'sucesso' => false,
                'mensagem' => 'Falha ao consultar serviço cadastral.',
                'codigo_erro' => 'ERRO_SERVICO_EXTERNO',
                'dados' => null,
                'origem' => null,
                'status_consultas' => null,
                'avisos' => ['Tente novamente mais tarde ou faça o preenchimento manual.'],
            ], 200);
        }

        $status = isset($resposta['codigo_erro']) && $resposta['codigo_erro'] === 'CNPJ_INVALIDO' ? 400 : 200;
        return $this->jsonResponse($resposta, $status);
    }

    /**
     * POST /api/cadastro/empresa/consultar
     * Body: { "cnpj": "...", "consultar_ie": true, "consultar_im": true, "usar_cache": true }
     */
    public function consultar()
    {
        $this->request->allowMethod(['post']);
        $this->autoRender = false;

        $data = $this->request->getData();
        $cnpj = $data['cnpj'] ?? $this->request->getQuery('cnpj');
        if ($cnpj === null || $cnpj === '') {
            return $this->jsonResponse([
                'sucesso' => false,
                'mensagem' => 'CNPJ não informado',
                'codigo_erro' => 'CNPJ_OBRIGATORIO',
                'dados' => null,
                'origem' => null,
                'status_consultas' => null,
                'avisos' => [],
            ], 400);
        }

        $consultarIe = isset($data['consultar_ie']) ? (bool)$data['consultar_ie'] : true;
        $consultarIm = isset($data['consultar_im']) ? (bool)$data['consultar_im'] : true;
        $usarCache = isset($data['usar_cache']) ? (bool)$data['usar_cache'] : true;

        try {
            $service = $this->buildService();
            $resposta = $service->consultar((string)$cnpj, [
                'consultar_ie' => $consultarIe,
                'consultar_im' => $consultarIm,
                'usar_cache' => $usarCache,
            ]);
        } catch (\Throwable $e) {
            Log::write('error', '[CadastroController::consultar] ' . $e->getMessage(), ['scope' => ['cadastro_empresa']]);
            return $this->jsonResponse([
                'sucesso' => false,
                'mensagem' => 'Falha ao consultar serviço cadastral.',
                'codigo_erro' => 'ERRO_SERVICO_EXTERNO',
                'dados' => null,
                'origem' => null,
                'status_consultas' => null,
                'avisos' => ['Tente novamente mais tarde ou faça o preenchimento manual.'],
            ], 200);
        }

        $status = isset($resposta['codigo_erro']) && $resposta['codigo_erro'] === 'CNPJ_INVALIDO' ? 400 : 200;
        return $this->jsonResponse($resposta, $status);
    }

    private function buildService(): ConsultaEmpresaService
    {
        $cidades = $this->Cidades;
        $estados = $this->Estados;
        $resolveIdCidade = function (string $municipio, string $uf) use ($cidades, $estados) {
            $estado = $estados->find()->where(['sigla' => strtoupper($uf)])->first();
            if (!$estado) {
                return null;
            }
            $lista = $cidades->find()->where(['idestado' => $estado->id])->all();
            $norm = TextoUtil::normalizaParaBusca($municipio);
            foreach ($lista as $c) {
                if (TextoUtil::normalizaParaBusca($c->nome) === $norm) {
                    return $c->id;
                }
            }
            return null;
        };
        return new ConsultaEmpresaService(null, null, null, $resolveIdCidade);
    }
}
