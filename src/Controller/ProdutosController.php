<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use CakeSoap\Network\CakeSoap;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'Utilities.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');
require_once (ROOT . DS . 'vendor' . DS  . '/queencitycodefactory/cakesoap/src/Network/CakeSoap.php');

//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/Utilities.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/queencitycodefactory/cakesoap/src/Network/CakeSoap.php';


class ProdutosController extends AppController {
	public function initialize() {
        parent::initialize();
		$this->loadModel('Empresas');
		$this->loadModel('Config');
    }
    
    public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->set('title', 'Produtos');
		$this->Auth->allow(['addApi', 'listApi']);

		if ($this->Auth->user('role') == C_RoleCliente) {
            $this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
	}

	public function index() {
		$this->set('title', 'Produtos e Serviços');
        $produtos = $this->Produtos->findByTipo(1)->where(['idempresa' => $this->Auth->user('idempresa')])->toArray();

		$produtosWs = [];
		$soapprodutos = $this->Empresas->get($this->Auth->user('idempresa'))->urlerp . 'WsProdutos.wso?wsdl';

		try {
			$this->runSoapBuffered(function () use ($soapprodutos, &$produtosWs) {
				$soap = new CakeSoap(['wsdl' => $soapprodutos]);
				if ($soap === null) {
					throw new \Exception('Erro ao instanciar SOAP para produtos.');
				}

				$response = $soap->sendRequest('GetEstoqueProdutos', [
					'Data' => [
						'iFilial' => C_Filial,
						'sChave' => C_ChaveAcesso,
						'bApenasComSaldo' => false,
						'sCodProduto' => null,
						'sDescricao' => null,
					]
				]);

				if (!empty($response->GetEstoqueProdutosResult->tWsProdutosEstoque)) {
					if (!is_array($response->GetEstoqueProdutosResult->tWsProdutosEstoque)) {
						$response->GetEstoqueProdutosResult->tWsProdutosEstoque = [$response->GetEstoqueProdutosResult->tWsProdutosEstoque];
					}
					foreach ($response->GetEstoqueProdutosResult->tWsProdutosEstoque as $produtoWs) {
						$produtosWs[$produtoWs->sCodProduto] = $produtoWs;
					}
				}
			});
		} catch (\Throwable $e) {
			// Se o ERP estiver indisponível, apenas não sincroniza valores
			$this->log('Produtos::index WS indisponível: ' . $e->getMessage(), 'error');
		}

		foreach($produtos as $produto) {
			if(isset($produtosWs[$produto->codigo])) {
				$produto->nPrecoVenda = $produtosWs[$produto->codigo]->nPrecoVenda;
				$produto->nPrecoCusto = $produtosWs[$produto->codigo]->nPrecoCusto;
				$produto->nQtdeAtual = $produtosWs[$produto->codigo]->nQtdeAtual;
				// Sincronizar Valor Unitário com Preço de Venda do estoque/ERP (valor vigente)
				if (isset($produtosWs[$produto->codigo]->nPrecoVenda)) {
					$precoVenda = (float) $produtosWs[$produto->codigo]->nPrecoVenda;
					if ($precoVenda != (float) $produto->vlunitario) {
						$produto->vlunitario = $precoVenda;
						$this->Produtos->save($produto);
					}
				}
			}
		}

        $servicos = $this->Produtos->findByTipo(2)->where(['idempresa' => $this->Auth->user('idempresa')])->toArray();
        $contratos = $this->Produtos->findByTipo(3)->where(['idempresa' => $this->Auth->user('idempresa')])->toArray();
        
		$this->set('contratos', $contratos);
		$this->set('produtos', $produtos);
		$this->set('servicos', $servicos);
	}

	public function add() {
        $produto = $this->Produtos->newEntity();
        
		if ($this->request->is('post')) {
            $data = $this->request->getData();
            $produto = $this->Produtos->patchEntity($produto, $data);
            $produto->idempresa = $this->Auth->user('idempresa');
            $produto->vlunitario = str_replace('.', '', $data['vlunitario']);
            $produto->vlunitario = str_replace(',', '.', $produto->vlunitario);
            if ($this->Produtos->save($produto)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $produto->id);

                if($produto->tipo == 1)  $this->Flash->success(__('O produto foi cadastrado com sucesso!.'));
                else  $this->Flash->success(__('O serviço foi cadastrado com sucesso!.'));
                return $this->redirect(['action' => 'edit', $produto->id]);
            }
            $this->Flash->error(__('Não foi possível cadastrar o produto/serviço.'));
        }

		$this->set('produto', $produto);
		$this->set('title', 'Cadastro de Produtos e Serviços');
	}

	public function edit($id = null) {
        $produto = $this->Produtos->get($id);
        // Para produtos (tipo 1), atualizar Valor Unitário com Preço de Venda do ERP ao abrir a edição
        if ($produto->tipo == 1 && !$this->request->is(['post', 'put'])) {
            try {
                $this->runSoapBuffered(function () use ($produto) {
                    $soapprodutos = $this->Empresas->get($this->Auth->user('idempresa'))->urlerp . 'WsProdutos.wso?wsdl';
                    $soap = new CakeSoap(['wsdl' => $soapprodutos]);
                    $response = $soap->sendRequest('GetEstoqueProdutos', [
                        'Data' => [
                            'iFilial' => C_Filial,
                            'sChave' => C_ChaveAcesso,
                            'bApenasComSaldo' => false,
                            'sCodProduto' => null,
                            'sDescricao' => null,
                        ]
                    ]);
                    $lista = $response->GetEstoqueProdutosResult->tWsProdutosEstoque;
                    if (!is_array($lista)) {
                        $lista = [$lista];
                    }
                    foreach ($lista as $item) {
                        if (trim((string)$item->sCodProduto) === trim((string)$produto->codigo) && isset($item->nPrecoVenda)) {
                            $precoVenda = (float) $item->nPrecoVenda;
                            if ($precoVenda != (float) $produto->vlunitario) {
                                $produto->vlunitario = $precoVenda;
                                $this->Produtos->save($produto);
                            }
                            break;
                        }
                    }
                });
            } catch (\Throwable $e) {
                // Mantém vlunitario atual se o ERP não responder
            }
        }
        $produto->vlunitario = number_format($produto->vlunitario, 2, ",", ".");
        
		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
            $produto = $this->Produtos->patchEntity($produto, $data);
            $produto->vlunitario = str_replace('.', '', $data['vlunitario']);
			$produto->vlunitario = str_replace(',', '.', $produto->vlunitario);
            if ($this->Produtos->save($produto)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $produto->id);
                if($produto->tipo == 1)  $this->Flash->success(__('O produto foi salvo com sucesso!.'));
                else  $this->Flash->success(__('O serviço foi salvo com sucesso!.'));

                return $this->redirect(['action' => 'edit', $id]);
            }
            if($produto->tipo == 1)  $this->Flash->error(__('Não foi possível salvar o produto.'));
            else  $this->Flash->error(__('Não foi possível salvar o serviço.'));
        }
        
		$this->set('produto', $produto);
        $this->set('title', 'Editar Produto');
    }

	public function isAuthorized($user) {
		return true;
	}

	public function delete($id = null) {
		$produto = $this->Produtos->get($id);

		if ($this->Produtos->delete($produto)) {
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $produto->id);
			if($produto->tipo == 1)  $this->Flash->success(__('O produto foi deletado com sucesso!.'));
			else $this->Flash->success('O serviço foi deletado com sucesso!');
			return $this->redirect(['action' => 'index']);
		}
    }
	
	public function produtostipo($tipo){
		$this->autoRender = false;
		error_reporting(0);
		$produtosOpt1 = $this->Produtos->find('all')->where(['tipo' => $tipo, 'idempresa' => $this->Auth->user('idempresa'), 'ativo' => 1])->order(['descricao'])->toArray();
		foreach($produtosOpt1 as $reg) $produtosOpt[] = ['codigo' => trim($reg->codigo), 'descricao' => trim($reg->descricao).' ('.trim($reg->codigo).')'];
		//echo json_encode($produtosOpt, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		return $this->jsonResponse($produtosOpt, 200);
    }

    /**
     * Retorna dados do produto/serviço por código (usado no orçamento).
     * Para produtos (tipo 1), busca preço de venda no estoque/ERP e atualiza vlunitario se disponível.
     */
    public function produto($codigo, $tipo = null){
		$this->autoRender = false;
		$codigo = trim((string) $codigo);
		if ($codigo === '') {
			return $this->jsonResponse(['mensagem' => 'Código não informado'], 400);
		}
		$produto = $this->Produtos->findByCodigo($codigo)->where(['idempresa' => $this->Auth->user('idempresa')])->first();
		if (!$produto) {
			return $this->jsonResponse(['mensagem' => 'Produto/serviço não encontrado'], 404);
		}
		// Para produtos (tipo 1), buscar preço de venda no estoque/ERP e usar no orçamento
		if ((int) $produto->tipo === 1) {
			try {
				$idempresa = $this->Auth->user('idempresa');
				$empresa = $this->Empresas->get($idempresa);
				if (!empty($empresa->urlerp)) {
					$this->runSoapBuffered(function () use ($empresa, $codigo, $produto) {
						$soapprodutos = $empresa->urlerp . 'WsProdutos.wso?wsdl';
						$soap = new CakeSoap(['wsdl' => $soapprodutos]);
						if ($soap === null) {
							return;
						}
						$response = $soap->sendRequest('GetEstoqueProdutos', [
							'Data' => [
								'iFilial' => C_Filial,
								'sChave' => C_ChaveAcesso,
								'bApenasComSaldo' => false,
								'sCodProduto' => $codigo,
								'sDescricao' => null,
							]
						]);
						$lista = isset($response->GetEstoqueProdutosResult->tWsProdutosEstoque) ? $response->GetEstoqueProdutosResult->tWsProdutosEstoque : null;
						if ($lista !== null) {
							if (!is_array($lista)) {
								$lista = [$lista];
							}
							$vlAntigo = (float) $produto->vlunitario;
							foreach ($lista as $item) {
								if (trim((string)($item->sCodProduto ?? '')) === $codigo && isset($item->nPrecoVenda)) {
									$precoVenda = (float) $item->nPrecoVenda;
									if ($precoVenda > 0) {
										$produto->vlunitario = $precoVenda;
										$produto->nPrecoVenda = $precoVenda;
										$produto->nQtdeAtual = isset($item->nQtdeAtual) ? (float) $item->nQtdeAtual : null;
										if ($vlAntigo != $precoVenda) {
											$this->Produtos->save($produto);
										}
									}
									break;
								}
							}
						}
					});
				}
			} catch (\Throwable $e) {
				$this->log('Produtos::produto GetEstoqueProdutos: ' . $e->getMessage(), 'error');
			}
		}
		return $this->jsonResponse($produto, 200);
    }

    public function listAPI() {
        $this->autoRender = false;
        if ($this->request->is('get')) {
			$empresa = $this->request->getHeaderLine('empresa') ?: $this->request->getQuery('empresa');
            $token = $this->request->getHeaderLine('token') ?: $this->request->getQuery('token');
            $codigo = $this->request->getHeaderLine('codigo') ?: $this->request->getQuery('codigo');

			if(empty($token) || empty($empresa)) 
			return $this->jsonResponse(['mensagem' => 'Parâmetros da requisição inválidos'], 400);
			
			if(empty($this->Empresas->findById($empresa)->first())) return $this->jsonResponse(['mensagem' => 'Parâmetros da requisição inválidos'], 400);
			if($token == $this->Empresas->get($empresa)->token){
				if(!empty($codigo)){
					$produto = $this->Produtos->findByCodigo($codigo)->where(['idempresa' => $empresa])->toArray(); 
					if ($produto == null) return $this->jsonResponse(['mensagem' => 'Não foi encontrado um produto com o código '. $codigo], 404);
				}else $produto = $this->Produtos->find('all')->where(['idempresa' => $empresa])->toArray(); 
				foreach($produto as $reg) {
					unset($reg->id);
					unset($reg->idempresa);
				}
				return $this->jsonResponse($produto, 200);
			}else return $this->jsonResponse(['mensagem' => 'Autenticação Inválida'], 401);
        }
    }

    /**
     * Normaliza valor "ativo" enviado pelo ERP (Sim/Não, true/false, 1/0) para 0 ou 1.
     */
    private function _normalizarAtivoApi($valor) {
        if ($valor === null || $valor === '') {
            return 1;
        }
        if (is_numeric($valor)) {
            return (int) $valor ? 1 : 0;
        }
        $v = is_string($valor) ? strtolower(trim($valor)) : $valor;
        if (in_array($v, ['sim', 's', 'true', '1', 'yes'], true)) {
            return 1;
        }
        if (in_array($v, ['não', 'nao', 'n', 'false', '0', 'no'], true)) {
            return 0;
        }
        return 1;
    }

    /**
     * Normaliza valor monetário (ex.: "180,00" ou "180.00") para float.
     */
    private function _normalizarVlUnitarioApi($valor) {
        if ($valor === null || $valor === '') {
            return null;
        }
        if (is_numeric($valor)) {
            return (float) $valor;
        }
        $s = preg_replace('/[^\d,.-]/', '', trim((string) $valor));
        $s = str_replace('.', '', $s);
        $s = str_replace(',', '.', $s);
        return $s === '' ? null : (float) $s;
    }

    public function addAPI() {
        $this->autoRender = false;
        $responseApi = function ($mensagem, $status = 200) {
            return $this->jsonResponse(['mensagem' => $mensagem, 'retorno' => $mensagem], $status);
        };
        if (!$this->request->is('post')) {
            return $responseApi('Método não permitido. Use POST com headers empresa e token, e body JSON com pelo menos "codigo".', 405);
        }
        try {
            $empresa = $this->request->getHeaderLine('empresa') ?: $this->request->getQuery('empresa');
            $token = $this->request->getHeaderLine('token') ?: $this->request->getQuery('token');
            // Usar getData() quando o body já foi parseado (Content-Type: application/json)
            $json = $this->request->getData();
            if (empty($json) || !is_array($json)) {
                $raw = $this->request->input('json_decode');
                $json = is_string($raw) ? json_decode($raw, true) : (is_object($raw) ? (array) $raw : $raw);
            }
            // Suporte a lote: se for array de produtos, usar o primeiro (um por requisição)
            if (isset($json[0]) && is_array($json[0])) {
                $json = $json[0];
            }
            $json = (object) $json;

            if (empty($token) || empty($empresa) || empty($json) || !isset($json->codigo)) {
                return $responseApi('Objeto ou parâmetros inválidos', 400);
            }
            if (empty($this->Empresas->findById($empresa)->first())) {
                return $responseApi('Objeto ou parâmetros inválidos', 400);
            }
            if ($token !== $this->Empresas->get($empresa)->token) {
                return $responseApi('Autenticação Inválida', 401);
            }

            $produto = $this->Produtos->findByCodigo(trim($json->codigo))->where(['idempresa' => $empresa])->first();
            if (empty($produto)) {
                $produto = $this->Produtos->newEntity();
            }

            $produto->idempresa = $empresa;
            $produto->codigo = trim($json->codigo);
            $produto->descricao = isset($json->descricao) ? trim($json->descricao) : $produto->descricao;
            $produto->unidade = isset($json->unidade) ? trim((string) $json->unidade) : $produto->unidade;
            $vl = $this->_normalizarVlUnitarioApi(isset($json->vlunitario) ? $json->vlunitario : $produto->vlunitario);
            $produto->vlunitario = $vl !== null ? $vl : $produto->vlunitario;
            $produto->tipo = isset($json->tipo) ? $json->tipo : $produto->tipo;
            $produto->ativo = $this->_normalizarAtivoApi(isset($json->ativo) ? $json->ativo : (isset($produto->ativo) ? $produto->ativo : 1));

            // Campos de locação: se for entidade nova e o ERP não enviar, usar 0 para evitar falha em colunas NOT NULL
            if ($produto->isNew() && $produto->get('vllocdiario') === null) {
                $produto->vllocdiario = 0;
            }
            if ($produto->isNew() && $produto->get('vllocsemanal') === null) {
                $produto->vllocsemanal = 0;
            }
            if ($produto->isNew() && $produto->get('vllocquinzenal') === null) {
                $produto->vllocquinzenal = 0;
            }
            if ($produto->isNew() && $produto->get('vllocmensal') === null) {
                $produto->vllocmensal = 0;
            }

            if ($this->Produtos->save($produto)) {
                return $responseApi('Produto cadastrado com sucesso', 201);
            }
            $errors = $produto->getErrors();
            $msg = !empty($errors) ? json_encode($errors) : 'Objeto ou parâmetros inválidos';
            return $responseApi($msg, 400);
        } catch (\Exception $e) {
            $this->log('Produtos::addAPI exceção: ' . $e->getMessage(), 'error');
            return $responseApi('Erro ao processar requisição: ' . $e->getMessage(), 500);
        }
    }

	public function qtdestoque($produto) {
		error_reporting(0);
		$this->autoRender = false;

		$soapprodutos = $this->Empresas->get($this->Auth->user('idempresa'))->urlerp . 'WsProdutos.wso?wsdl';

		try {
			return $this->runSoapBuffered(function () use ($soapprodutos, $produto) {
				$soap = new CakeSoap(['wsdl' => $soapprodutos]);
				if ($soap === null) {
					throw new \Exception('Erro');
				}
				$response = $soap->sendRequest('GetProdutoEstoque', [
					'Data' => [
						'iFilial' => C_Filial,
						'sChave' => C_ChaveAcesso,
						'sProduto' => $produto,
					]
				]);

				return $this->jsonResponse($response->GetProdutoEstoqueResult, 200);
			});
		} catch (\Throwable $e) {
			return $this->jsonResponse(-999, 200);
		}
	}

	public function serialnumberproduto($produto) {
		error_reporting(0);
		$this->autoRender = false;

		$soapprodutos = $this->Empresas->get($this->Auth->user('idempresa'))->urlerp . 'WsProdutos.wso?wsdl';
		try {
			return $this->runSoapBuffered(function () use ($soapprodutos, $produto) {
				$soap = new CakeSoap(['wsdl' => $soapprodutos]);
				if ($soap === null) {
					throw new \Exception('Erro');
				}
				$response = $soap->sendRequest('GetSerialNumberProduto', [
					'Data' => [
						'iFilial' => C_Filial,
						'sChave' => C_ChaveAcesso,
						'sProduto' => $produto,
						'bApenasDisponiveis' => true,
					]
				]);
				if (!is_array($response->GetSerialNumberProdutoResult->tWsProdutoSerialNumber)) {
					$response->GetSerialNumberProdutoResult->tWsProdutoSerialNumber = [$response->GetSerialNumberProdutoResult->tWsProdutoSerialNumber];
				}
				return $this->jsonResponse($response->GetSerialNumberProdutoResult->tWsProdutoSerialNumber, 200);
			});
		} catch (\Throwable $e) {
			return $this->jsonResponse([], 200);
		}
	}

	public function estoque($opt = null) {
		error_reporting(0);
		if($opt == 't') $bApenasComSaldo = true;
		else $bApenasComSaldo = false;
		$sCodProduto = null;
		$sDescricao = null;

		if ($this->request->is(['post', 'put'])) {
            $data = $this->request->getData();
			$sCodProduto = isset($data['sCodProduto']) ? $data['sCodProduto'] : null;
			$sDescricao = isset($data['sDescricao']) ? $data['sDescricao'] : null;
			$opt = 't';
        }

		$produtos = [];
		$produtosOpt = [];
		$empresa = $this->Empresas->get($this->Auth->user('idempresa'));

		if (empty($empresa->urlerp)) {
			$this->Flash->error(__('URL do ERP não configurada para esta empresa. Configure em Empresas > Editar > URL ERP.'));
			return $this->redirect(['controller' => 'Produtos', 'action' => 'index']);
		}

		$soapprodutos = $empresa->urlerp . 'WsProdutos.wso?wsdl';
		try {
			$this->runSoapBuffered(function () use ($soapprodutos, $bApenasComSaldo, &$sCodProduto, $sDescricao, &$produtos) {
				$soap = new CakeSoap(['wsdl' => $soapprodutos]);
				if ($soap === null) {
					throw new \Exception('Cliente SOAP não inicializado.');
				}

				if ($sCodProduto == 0) {
					$sCodProduto = null;
				}

				$response = $soap->sendRequest('GetEstoqueProdutos', [
					'Data' => [
						'iFilial' => C_Filial,
						'sChave' => C_ChaveAcesso,
						'bApenasComSaldo' => $bApenasComSaldo,
						'sCodProduto' => $sCodProduto,
						'sDescricao' => $sDescricao,
					]
				]);

				$result = $response->GetEstoqueProdutosResult ?? null;
				$lista = ($result && isset($result->tWsProdutosEstoque)) ? $result->tWsProdutosEstoque : null;

				if ($lista === null) {
					$produtos = [];
				} else {
					if (!is_array($lista)) {
						$lista = [$lista];
					}
					$produtos = $lista;
					usort($produtos, function ($a, $b) {
						$descA = $a->sDescProduto ?? '';
						$descB = $b->sDescProduto ?? '';
						if ($descA == $descB) {
							return 0;
						}
						return ($descA < $descB) ? -1 : 1;
					});
				}
			});
		} catch (\SoapFault $e) {
			$this->log('Produtos::estoque SoapFault: ' . $e->getMessage(), 'error');
			$this->Flash->error(__('Erro ao comunicar com o ERP (Windows Server). Verifique: 1) Se o servidor ERP está ligado e acessível; 2) URL do ERP em Empresas; 3) Rede/firewall. Detalhe: ') . $e->getMessage());
			return $this->redirect(['controller' => 'Produtos', 'action' => 'index']);
		} catch (\Exception $e) {
			$this->log('Produtos::estoque: ' . $e->getMessage(), 'error');
			$this->Flash->error(__('O estoque não pôde ser carregado. Erro: ') . $e->getMessage());
			return $this->redirect(['controller' => 'Produtos', 'action' => 'index']);
		}

		$produtosOpt1 = $this->Produtos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'ativo' => 1])->order(['descricao'])->toArray();
		foreach ($produtosOpt1 as $reg) $produtosOpt[$reg->codigo] = $reg->descricao . ' (' . $reg->codigo . ')';

		$this->set('sCodProduto', $sCodProduto);
		$this->set('produtosOpt', $produtosOpt);
		$this->set('bApenasComSaldo', $bApenasComSaldo);
		$this->set('produtos', $produtos);
		$this->set('title', 'Produtos em Estoque');
	}

	public function pesquisar() {
        $this->autoRender = false;
        $termo = $this->request->getQuery('termo');
        $idEmpresa = $this->Auth->user('idempresa');
        $query = $this->Produtos->find()
            ->select(['codigo', 'descricao', 'vlunitario', 'unidade', 'tipo'])
            ->where(['idempresa' => $idEmpresa, 'ativo' => 1]);
    
        if (!empty($termo)) {
            $termo = trim($termo);
            $termoMin = mb_strtolower($termo);
            $query->where([
                'OR' => [
                    'LOWER(descricao) LIKE' => '%' . $termoMin . '%',
                    'codigo LIKE' => '%' . $termo . '%'
                ]
            ]);
        }
        $produtos = $query
            ->limit(25)
            ->order(['descricao' => 'ASC']) 
            ->toArray();
            
        return $this->jsonResponse($produtos, 200);
    }

	/**
	 * SoapClient emite warnings (ex.: connection refused) que iam para o output antes dos headers,
	 * quebrando CSS/sessão. Isto descarta essa saída e regista em log.
	 *
	 * @param callable $fn
	 * @return mixed
	 * @throws \Throwable
	 */
	private function runSoapBuffered(callable $fn) {
		ob_start();
		try {
			$result = $fn();
		} catch (\Throwable $e) {
			$this->discardSoapBuffer();
			throw $e;
		}
		$this->discardSoapBuffer();
		return $result;
	}

	private function discardSoapBuffer() {
		$buf = ob_get_clean();
		if ($buf !== false && trim($buf) !== '') {
			$this->log('Produtos::SOAP output suprimido: ' . trim($buf), 'warning');
		}
	}
}



