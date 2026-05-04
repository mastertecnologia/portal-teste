<?php
namespace App\Controller;

use App\Utility\ErpIntegrationRequest;
use App\Controller\AppController;
use App\Utility\ErpGridUrl;
use App\Utility\ErpSoapUrl;
use App\Utility\PortalUrlPath;
use Cake\Event\Event;
use Cake\View\View;
use CakeSoap\Network\CakeSoap;

$__pgmUtilities = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'Utilities.php';
if (is_file($__pgmUtilities)) {
	require_once $__pgmUtilities;
}
$__pgmUserConstants = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';
if (is_file($__pgmUserConstants)) {
	require_once $__pgmUserConstants;
}
$__cakeSoap = ROOT . DS . 'vendor' . DS . 'queencitycodefactory' . DS . 'cakesoap' . DS . 'src' . DS . 'Network' . DS . 'CakeSoap.php';
if (is_file($__cakeSoap)) {
	require_once $__cakeSoap;
}
if (!defined('C_RoleCliente')) {
	define('C_RoleCliente', 1);
}
if (!defined('C_Filial')) {
	define('C_Filial', 1);
}
if (!defined('C_ChaveAcesso')) {
	define('C_ChaveAcesso', '');
}
/** Opções do campo Situação (ativo) em add/edit — definidas no PGMPackages/UserConstants.php quando existir. */
if (!defined('C_ProdutosAtivo')) {
	define('C_ProdutosAtivo', [1 => 'Sim', 0 => 'Não']);
}
if (!defined('C_ProdutosAtivoSim')) {
	define('C_ProdutosAtivoSim', 1);
}

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
		$soapprodutos = ErpGridUrl::wsdl($this->Empresas->get($this->Auth->user('idempresa'))->urlerp);

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
		$this->set('tiposProdutoOptions', $this->produtoTipoOptionsForForms());
		$this->set('title', 'Cadastro de Produtos e Serviços');
	}

	public function edit($id = null) {
        $produto = $this->Produtos->get($id);
		$returnUrlEstoque = null;
		if ($this->request->is(['post', 'put'])) {
			$returnUrlEstoque = $this->sanitizeEstoqueReturnUrl($this->request->getData('return'));
		}
		if ($returnUrlEstoque === null) {
			$returnUrlEstoque = $this->sanitizeEstoqueReturnUrl($this->request->getQuery('return'));
		}
		$embedWanted = ($this->request->getQuery('embed_estoque') === '1');
		if ($this->request->is(['post', 'put'])) {
			$embedWanted = $embedWanted || ($this->request->getData('embed_estoque') === '1');
		}
		$embedEstoque = $embedWanted && $returnUrlEstoque !== null;
		if ($embedEstoque) {
			$this->viewBuilder()->setLayout('estoque_embed');
		}
        // Para itens de tipo produto, atualizar Valor Unitário com Preço de Venda do ERP ao abrir a edição.
        if ($this->produtoTipoEhProduto($produto->tipo) && !$this->request->is(['post', 'put'])) {
            try {
                $this->runSoapBuffered(function () use ($produto) {
                    $soapprodutos = ErpGridUrl::wsdl($this->Empresas->get($this->Auth->user('idempresa'))->urlerp);
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
		if (!$this->request->is(['post', 'put'])) {
			// Compatibilidade visual: quando constants divergem do legado (1/2/3), manter seleção correta do tipo.
			$produto->tipo = $this->produtoTipoUiValue($produto->tipo);
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

				if ($returnUrlEstoque !== null) {
					return $this->redirect($returnUrlEstoque);
				}
                return $this->redirect(['action' => 'edit', $id]);
            }
            if($produto->tipo == 1)  $this->Flash->error(__('Não foi possível salvar o produto.'));
            else  $this->Flash->error(__('Não foi possível salvar o serviço.'));
        }
        
		$this->set('produto', $produto);
		$this->set('tiposProdutoOptions', $this->produtoTipoOptionsForForms());
		$this->set('returnUrlEstoque', $returnUrlEstoque);
		$this->set('embedEstoque', $embedEstoque);
		$this->set('estoqueEmbedReturnUrl', $embedEstoque ? $returnUrlEstoque : null);
        $this->set('title', 'Editar Produto');
    }

	public function isAuthorized($user) {
		return true;
	}

	/**
	 * Tela de gestão de preços/precificação.
	 * Carrega todos os produtos/serviços/contratos da empresa e, para tipo 1 (produtos),
	 * busca custo e preço de venda no ERP via SOAP GetEstoqueProdutos.
	 * Envia $produtosJson (array JSON) para o template JS.
	 */
	public function precificacao() {
		$this->set('title', 'Gestão de Preços');
		$this->set('hideLayoutPageTitle', true);
		$this->set('bodyPageClass', 'prec-screen-active');
		$returnUrlEstoque = $this->sanitizeEstoqueReturnUrl($this->request->getQuery('return'));
		$embedEstoque = ($this->request->getQuery('embed_estoque') === '1') && $returnUrlEstoque !== null;
		if ($embedEstoque) {
			$this->viewBuilder()->setLayout('estoque_embed');
		}
		$this->set('returnUrlEstoque', $returnUrlEstoque);
		$this->set('embedEstoque', $embedEstoque);
		$this->set('estoqueEmbedReturnUrl', $embedEstoque ? $returnUrlEstoque : null);

		$idempresa = $this->Auth->user('idempresa');
		$todos = $this->Produtos->find('all')
			->where(['idempresa' => $idempresa])
			->order(['tipo', 'descricao'])
			->toArray();

		// Buscar dados do ERP (custo/venda/estoque) para cruzar com tipo 1
		$produtosWs = [];
		try {
			$soapprodutos = ErpGridUrl::wsdl($this->Empresas->get($idempresa)->urlerp);
			$this->runSoapBuffered(function () use ($soapprodutos, &$produtosWs) {
				$soap = new CakeSoap(['wsdl' => $soapprodutos]);
				$response = $soap->sendRequest('GetEstoqueProdutos', [
					'Data' => [
						'iFilial'        => C_Filial,
						'sChave'         => C_ChaveAcesso,
						'bApenasComSaldo' => false,
						'sCodProduto'    => null,
						'sDescricao'     => null,
					]
				]);
				if (!empty($response->GetEstoqueProdutosResult->tWsProdutosEstoque)) {
					$lista = $response->GetEstoqueProdutosResult->tWsProdutosEstoque;
					if (!is_array($lista)) $lista = [$lista];
					foreach ($lista as $item) {
						$produtosWs[trim((string)$item->sCodProduto)] = $item;
					}
				}
			});
		} catch (\Throwable $e) {
			$this->log('Precificacao::WS indisponível: ' . $e->getMessage(), 'error');
		}

		// Montar array para o JS
		$tipoLabels = [1 => 'Produto', 2 => 'Serviço', 3 => 'Contrato'];
		$lista = [];
		foreach ($todos as $p) {
			$custo     = 0.0;
			$qtde      = null;
			$temCusto  = false;
			$vendaErp  = (float) $p->vlunitario;

			if ((int)$p->tipo === 1 && isset($produtosWs[trim((string)$p->codigo)])) {
				$ws       = $produtosWs[trim((string)$p->codigo)];
				$custo    = (float)($ws->nPrecoCusto ?? 0);
				$qtde     = isset($ws->nQtdeAtual) ? (float)$ws->nQtdeAtual : null;
				$vendaErp = isset($ws->nPrecoVenda) ? (float)$ws->nPrecoVenda : $vendaErp;
				$temCusto = $custo > 0;
			}

			// Calcular markup e fator atual com base no custo ERP
			$markup    = ($temCusto && $custo > 0) ? round((($vendaErp / $custo) - 1) * 100, 4) : 0;
			$fatorMult = ($temCusto && $custo > 0) ? round($vendaErp / $custo, 6) : 0;
			$fatorDiv  = ($fatorMult > 0) ? round(1 / $fatorMult, 6) : 0;
			$margem    = ($vendaErp > 0 && $temCusto) ? round((1 - ($custo / $vendaErp)) * 100, 2) : 0;

			$lista[] = [
				'id'          => $p->id,
				'codigo'      => trim((string)$p->codigo),
				'descricao'   => trim((string)$p->descricao),
				'unidade'     => trim((string)($p->unidade ?? '')),
				'tipo'        => (int)$p->tipo,
				'tipoLabel'   => $tipoLabels[(int)$p->tipo] ?? 'Outro',
				'ativo'       => (int)$p->ativo,
				'custo'       => $custo,
				'vendaAtual'  => $vendaErp,
				'margem'      => $margem,
				'markup'      => $markup,
				'fatorMult'   => $fatorMult,
				'fatorDiv'    => $fatorDiv,
				'qtde'        => $qtde,
				'temCusto'    => $temCusto,
			];
		}

		$this->set('produtosJson', json_encode($lista, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	/**
	 * Salvar novos preços (vlunitario) para uma lista de produtos.
	 * POST JSON: { "precos": [{"id": 1, "vlunitario": 150.00}, ...] }
	 * Retorna JSON: { "salvos": N, "erros": [] }
	 */
	public function salvarPrecos() {
		$this->autoRender = false;
		$this->request->allowMethod(['post', 'put']);
		$idempresa = $this->Auth->user('idempresa');

		$precos = $this->request->getData('precos');
		if (empty($precos) || !is_array($precos)) {
			return $this->jsonResponse(['erro' => 'Nenhum preço enviado'], 400);
		}

		$salvos = 0;
		$erros  = [];

		foreach ($precos as $item) {
			$id  = isset($item['id'])  ? (int)$item['id']          : null;
			$vl  = isset($item['vlunitario']) ? (float)$item['vlunitario'] : null;

			if (!$id || $vl === null || $vl < 0) {
				$erros[] = ['id' => $id, 'erro' => 'Dados inválidos'];
				continue;
			}

			try {
				$produto = $this->Produtos->get($id);
				// Garantia: produto pertence à empresa autenticada
				if ((int)$produto->idempresa !== (int)$idempresa) {
					$erros[] = ['id' => $id, 'erro' => 'Acesso negado'];
					continue;
				}
				$produto->vlunitario = $vl;
				if ($this->Produtos->save($produto)) {
					$salvos++;
				} else {
					$erros[] = ['id' => $id, 'erro' => 'Falha ao salvar'];
				}
			} catch (\Throwable $e) {
				$erros[] = ['id' => $id, 'erro' => $e->getMessage()];
			}
		}

		if ($salvos > 0) {
			$this->Atividades->registrar(
				$this->Auth->user('id'),
				$this->request->getParam('controller'),
				$this->request->getParam('action'),
				$salvos
			);
		}

		return $this->jsonResponse(['salvos' => $salvos, 'erros' => $erros], 200);
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
		$produtosOpt = [];
		$produtosOpt1 = $this->Produtos->find('all')->where(['tipo' => $tipo, 'idempresa' => $this->Auth->user('idempresa'), 'ativo' => 1])->order(['descricao'])->toArray();
		foreach($produtosOpt1 as $reg) $produtosOpt[] = ['codigo' => trim($reg->codigo), 'descricao' => trim($reg->descricao).' ('.trim($reg->codigo).')'];
		//echo json_encode($produtosOpt, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		return $this->jsonResponse($produtosOpt, 200);
    }

    /**
     * Retorna dados do produto/serviço por código (usado no orçamento).
     * Opcional: filtrar por tipo (query ?tipo= ou segundo segmento da URL) para não devolver
     * outro cadastro com o mesmo código em tipo diferente.
     * Para produtos (C_ProdutosTipoProduto), busca preço de venda no estoque/ERP e atualiza vlunitario se disponível.
     */
    public function produto($codigo, $tipo = null){
		$this->autoRender = false;
		$codigo = trim((string) $codigo);
		if ($codigo === '') {
			return $this->jsonResponse(['mensagem' => 'Código não informado'], 400);
		}
		$idempresa = (int) $this->Auth->user('idempresa');
		$tipoFiltro = null;
		$tipoQuery = $this->request->getQuery('tipo');
		if ($tipoQuery !== null && $tipoQuery !== '' && is_numeric($tipoQuery)) {
			$tq = (int) $tipoQuery;
			if ($tq > 0) {
				$tipoFiltro = $tq;
			}
		} elseif ($tipo !== null && $tipo !== '' && is_numeric($tipo)) {
			$tr = (int) $tipo;
			if ($tr > 0) {
				$tipoFiltro = $tr;
			}
		}
		$where = ['codigo' => $codigo, 'idempresa' => $idempresa];
		if ($tipoFiltro !== null) {
			$where['tipo'] = $tipoFiltro;
		}
		$produto = $this->Produtos->find()->where($where)->first();
		if (!$produto) {
			if ($tipoFiltro !== null) {
				$anyTipo = $this->Produtos->findByCodigo($codigo)->where(['idempresa' => $idempresa])->first();
				if ($anyTipo) {
					return $this->jsonResponse(['mensagem' => 'Nenhum item encontrado para o tipo selecionado.'], 404);
				}
			}
			return $this->jsonResponse(['mensagem' => 'Produto/serviço não encontrado'], 404);
		}
		$tipoProdutoConst = defined('C_ProdutosTipoProduto') ? (int) C_ProdutosTipoProduto : 1;
		if ((int) $produto->tipo === $tipoProdutoConst) {
			try {
				$idempresa = $this->Auth->user('idempresa');
				$empresa = $this->Empresas->get($idempresa);
				$this->runSoapBuffered(function () use ($empresa, $codigo, $produto) {
						$soapprodutos = ErpGridUrl::wsdl($empresa->urlerp);
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
			} catch (\Throwable $e) {
				$this->log('Produtos::produto GetEstoqueProdutos: ' . $e->getMessage(), 'error');
			}
		}
		return $this->jsonResponse($produto, 200);
    }

    public function listAPI() {
        $this->autoRender = false;
        if ($this->request->is('get')) {
			list($empresa, $token, $erpCredErr) = ErpIntegrationRequest::readEmpresaAndToken(
				$this->request,
			);
            $codigo = $this->request->getHeaderLine('codigo') ?: $this->request->getQuery('codigo');

			if ($erpCredErr !== null) {
				return $this->jsonResponse(['mensagem' => $erpCredErr], 400);
			}
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

	/**
	 * Opções do select de tipo para formularios de produto.
	 * Garante chave legada 1/2/3 quando C_ProdutosTipo vier como lista indexada (0,1,2,...).
	 *
	 * @return array<int, string>
	 */
	private function produtoTipoOptionsForForms(): array {
		if (!defined('C_ProdutosTipo') || !is_array(constant('C_ProdutosTipo'))) {
			return [1 => 'Produto', 2 => 'Servico', 3 => 'Contrato'];
		}
		$src = constant('C_ProdutosTipo');
		$keys = array_keys($src);
		$isZeroIndexedList = $keys === range(0, count($src) - 1);
		$out = [];
		if (!$isZeroIndexedList) {
			foreach ($src as $k => $label) {
				$ik = (int)$k;
				if ($ik > 0) {
					$out[$ik] = (string)$label;
				}
			}

			return $out;
		}

		$labels = array_values($src);
		$constOrder = [];
		foreach (['C_ProdutosTipoProduto', 'C_ProdutosTipoServico', 'C_ProdutosTipoLicenca', 'C_ProdutosTipoLocacao'] as $cname) {
			if (defined($cname) && (int)constant($cname) > 0) {
				$constOrder[] = (int)constant($cname);
			}
		}
		$nextFallback = count($constOrder) > 0 ? (max($constOrder) + 1) : 1;
		for ($i = 0; $i < count($labels); $i++) {
			$val = isset($constOrder[$i]) ? (int)$constOrder[$i] : $nextFallback++;
			if ($val <= 0) {
				continue;
			}
			$out[$val] = (string)$labels[$i];
		}

		return $out;
	}

	/**
	 * Classifica um tipo numérico/textual no domínio legado do portal.
	 * Retorno: produto | servico | contrato | null
	 *
	 * Regras:
	 * - Legado BD: 1=produto, 2=serviço, 3=contrato
	 * - Constants/rótulos (quando existirem) podem divergir e são tratados por semântica.
	 */
	private function produtoTipoSemantic($tipo): ?string {
		$raw = is_scalar($tipo) ? trim((string)$tipo) : '';
		if ($raw === '') {
			return null;
		}
		if (preg_match('/^-?\d+$/', $raw)) {
			$i = (int)$raw;
			if ($i === 1) {
				return 'produto';
			}
			if ($i === 2) {
				return 'servico';
			}
			if ($i === 3) {
				return 'contrato';
			}
			$lbl = $this->produtoTipoLabelByValue($i);
			$fromLbl = $this->produtoTipoSemanticFromLabel($lbl);
			if ($fromLbl !== null) {
				return $fromLbl;
			}
		}

		return $this->produtoTipoSemanticFromLabel($raw);
	}

	private function produtoTipoSemanticFromLabel(string $label): ?string {
		$txt = mb_strtolower(trim($label), 'UTF-8');
		if ($txt === '') {
			return null;
		}
		$txt = strtr($txt, ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c']);
		if (strpos($txt, 'produt') !== false || strpos($txt, 'mercador') !== false) {
			return 'produto';
		}
		if (strpos($txt, 'servic') !== false) {
			return 'servico';
		}
		if (strpos($txt, 'contrat') !== false || strpos($txt, 'licenc') !== false || strpos($txt, 'loca') !== false) {
			return 'contrato';
		}

		return null;
	}

	private function produtoTipoLabelByValue(int $value): string {
		if (!defined('C_ProdutosTipo') || !is_array(constant('C_ProdutosTipo'))) {
			return '';
		}
		$map = constant('C_ProdutosTipo');
		if (array_key_exists($value, $map)) {
			return (string)$map[$value];
		}
		$sv = (string)$value;
		if (array_key_exists($sv, $map)) {
			return (string)$map[$sv];
		}

		return '';
	}

	private function produtoTipoUiValue($storedTipo): int {
		$semantic = $this->produtoTipoSemantic($storedTipo);
		if ($semantic === null || !defined('C_ProdutosTipo') || !is_array(constant('C_ProdutosTipo'))) {
			return (int)$storedTipo;
		}
		$map = constant('C_ProdutosTipo');
		foreach ($map as $k => $label) {
			$kk = (int)$k;
			if ($kk <= 0) {
				continue;
			}
			if ($this->produtoTipoSemanticFromLabel((string)$label) === $semantic) {
				return $kk;
			}
		}

		return (int)$storedTipo;
	}

	private function produtoTipoEhProduto($tipo): bool {
		return $this->produtoTipoSemantic($tipo) === 'produto';
	}

	private function produtoTipoLegacyBySemantic(?string $semantic): ?int {
		if ($semantic === 'produto') {
			return 1;
		}
		if ($semantic === 'servico') {
			return 2;
		}
		if ($semantic === 'contrato') {
			return 3;
		}

		return null;
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
            list($empresa, $token, $erpCredErr) = ErpIntegrationRequest::readEmpresaAndToken(
                $this->request,
            );
            if ($erpCredErr !== null) {
                return $responseApi($erpCredErr, 400);
            }
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
            if (isset($json->tipo) && $json->tipo !== '') {
				$sem = $this->produtoTipoSemantic($json->tipo);
				$legacy = $this->produtoTipoLegacyBySemantic($sem);
				$produto->tipo = $legacy !== null ? $legacy : (is_numeric($json->tipo) ? (int)$json->tipo : $produto->tipo);
			}
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

		$soapprodutos = ErpGridUrl::wsdl($this->Empresas->get($this->Auth->user('idempresa'))->urlerp);

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

	/**
	 * Estoque ERP em lote (GetProdutoEstoque por código) — catálogo de orçamento.
	 * POST: codigos = string CSV ou array de códigos (máx. 150).
	 *
	 * @return \Cake\Http\Response JSON objeto { "COD1": n, "COD2": -999, ... }
	 */
	public function estoquesLote() {
		error_reporting(0);
		$this->request->allowMethod(['post']);
		$this->autoRender = false;

		$codigos = $this->request->getData('codigos');
		if (is_string($codigos)) {
			$codigos = array_filter(array_map('trim', explode(',', $codigos)));
		}
		if (!is_array($codigos)) {
			return $this->jsonResponse(['erro' => 'codigos inválidos'], 400);
		}
		$codigos = array_values(array_unique(array_filter(array_map('strval', $codigos), function ($c) {
			return $c !== '';
		})));
		if (count($codigos) > 150) {
			$codigos = array_slice($codigos, 0, 150);
		}

		$empresa = $this->Empresas->get($this->Auth->user('idempresa'));
		$out = [];
		$soapprodutos = ErpGridUrl::wsdl($empresa->urlerp);
		foreach ($codigos as $produto) {
			try {
				$q = $this->runSoapBuffered(function () use ($soapprodutos, $produto) {
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

					return $response->GetProdutoEstoqueResult;
				});
				if (is_numeric($q)) {
					$out[$produto] = (float) $q;
				} elseif (is_scalar($q) && is_numeric((string) $q)) {
					$out[$produto] = (float) (string) $q;
				} else {
					$out[$produto] = -999;
				}
			} catch (\Throwable $e) {
				$out[$produto] = -999;
			}
		}

		return $this->jsonResponse($out, 200);
	}

	public function serialnumberproduto($produto) {
		error_reporting(0);
		$this->autoRender = false;

		$soapprodutos = ErpGridUrl::wsdl($this->Empresas->get($this->Auth->user('idempresa'))->urlerp);
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
		$query = (array)$this->request->getQueryParams();
		$data = $this->request->is(['post', 'put']) ? (array)$this->request->getData() : [];

		$sCodProduto = $data['sCodProduto'] ?? ($query['sCodProduto'] ?? null);
		$sDescricao = $data['sDescricao'] ?? ($query['sDescricao'] ?? null);

		if ($opt === 't') {
			$bApenasComSaldo = true;
		} elseif ($opt === 'f') {
			$bApenasComSaldo = false;
		} else {
			$bApenasComSaldo = in_array((string)($query['apenasComSaldo'] ?? '1'), ['1', 'true', 't'], true);
		}

		try {
			[$produtos, $produtosOpt, $sCodProduto] = $this->carregarDadosEstoque($bApenasComSaldo, $sCodProduto, $sDescricao);
		} catch (\Throwable $e) {
			$this->log('Produtos::estoque: ' . $e->getMessage(), 'error');
			$urlerp = '';
			try {
				$urlerp = (string)$this->Empresas->get((int)$this->Auth->user('idempresa'))->urlerp;
			} catch (\Throwable $ignore) {
			}
			$this->Flash->error(
				__('O estoque não pôde ser carregado. Erro: ') . $e->getMessage() . ErpSoapUrl::hintIfLocalhostUrlErp($urlerp)
			);
			return $this->redirect(['controller' => 'Produtos', 'action' => 'index']);
		}

		$this->set('sCodProduto', $sCodProduto);
		$this->set('sDescricao', $sDescricao);
		$this->set('produtosOpt', $produtosOpt);
		$this->set('bApenasComSaldo', $bApenasComSaldo);
		$this->set('produtos', $produtos);
		$this->set('mapCodigoId', $this->mapCodigoEstoqueParaIdPortal($produtos));
		$this->set('estoqueReturnUrl', $this->buildEstoqueListReturnUrl($bApenasComSaldo, $sCodProduto, $sDescricao));
		$this->set('title', 'Produtos em Estoque');
		$this->set('hideLayoutPageTitle', true);
		$this->set('bodyPageClass', 'estoque-screen-active');

		$isAjax = $this->request->is('ajax') || in_array((string)($query['ajax'] ?? '0'), ['1', 'true'], true);
		if ($isAjax) {
			$this->viewBuilder()->setLayout(false);
			$this->render('estoque_lista');
			return;
		}
	}

	public function estoquePdf($opt = null) {
		$query = (array)$this->request->getQueryParams();
		$sCodProduto = $query['sCodProduto'] ?? null;
		$sDescricao = $query['sDescricao'] ?? null;
		$codigosParam = (string)($query['codigos'] ?? '');
		$escopo = (string)($query['escopo'] ?? '');
		$codigosSelecionados = $this->extrairCodigosSelecionados($codigosParam);

		if ($opt === 't') {
			$bApenasComSaldo = true;
		} elseif ($opt === 'f') {
			$bApenasComSaldo = false;
		} else {
			$bApenasComSaldo = in_array((string)($query['apenasComSaldo'] ?? '1'), ['1', 'true', 't'], true);
		}

		try {
			[$produtos, , $sCodProduto] = $this->carregarDadosEstoque($bApenasComSaldo, $sCodProduto, $sDescricao);
		} catch (\Throwable $e) {
			$this->Flash->error(__('Não foi possível gerar o PDF. Erro: ') . $e->getMessage());
			return $this->redirect(['action' => 'estoque', $bApenasComSaldo ? 't' : 'f', '?' => ['sCodProduto' => $sCodProduto, 'sDescricao' => $sDescricao]]);
		}

		if (!empty($codigosSelecionados)) {
			$produtos = $this->filtrarProdutosPorCodigos($produtos, $codigosSelecionados);
		}

		if (!class_exists(\Mpdf\Mpdf::class)) {
			$this->Flash->error('Biblioteca mPDF não instalada. Execute: composer require mpdf/mpdf');
			return $this->redirect(['action' => 'estoque', $bApenasComSaldo ? 't' : 'f', '?' => ['sCodProduto' => $sCodProduto, 'sDescricao' => $sDescricao]]);
		}

		$tmpDir = TMP . 'mpdf' . DS;
		if (!is_dir($tmpDir)) {
			mkdir($tmpDir, 0775, true);
		}

		$view = new View($this->request, $this->response, $this->getEventManager(), ['layout' => false]);
		$view->setTemplatePath('Produtos');
		$view->set(compact('produtos', 'bApenasComSaldo', 'sCodProduto', 'sDescricao', 'escopo', 'codigosSelecionados'));
		$html = $view->render('estoque_pdf');

		$mpdf = new \Mpdf\Mpdf([
			'mode' => 'utf-8',
			'format' => 'A4-L',
			'tempDir' => $tmpDir,
		]);
		$mpdf->WriteHTML($html);

		$pdf = $mpdf->Output('', 'S');
		$filename = 'Estoque-' . date('Ymd-His') . '.pdf';

		return $this->response
			->withType('application/pdf')
			->withDownload($filename)
			->withStringBody($pdf);
	}

	/**
	 * URL canónica da listagem de estoque (filtros atuais) para retorno após editar/precificar.
	 */
	private function buildEstoqueListReturnUrl(bool $bApenasComSaldo, $sCodProduto, $sDescricao): string {
		$pass = $bApenasComSaldo ? 't' : 'f';
		$q = [];
		if ($sCodProduto !== null && $sCodProduto !== '' && (string)$sCodProduto !== '0') {
			$q['sCodProduto'] = $sCodProduto;
		}
		if ($sDescricao !== null && trim((string)$sDescricao) !== '') {
			$q['sDescricao'] = $sDescricao;
		}

		$path = \Cake\Routing\Router::url([
			'controller' => 'Produtos',
			'action' => 'estoque',
			$pass,
			'?' => $q,
		]);

		return PortalUrlPath::normalizeRelativeUrl($path);
	}

	/**
	 * Evita open redirect: só caminhos relativos para a action de estoque.
	 *
	 * @param mixed $url
	 */
	private function sanitizeEstoqueReturnUrl($url): ?string {
		if (!is_string($url)) {
			return null;
		}
		$url = trim(rawurldecode($url));
		if ($url === '') {
			return null;
		}
		if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
			return null;
		}
		if (strpos($url, '//') === 0) {
			return null;
		}
		$url = PortalUrlPath::normalizeRelativeUrl($url);
		if (stripos($url, 'produtos/estoque') === false) {
			return null;
		}

		return $url;
	}

	/**
	 * Cruza códigos ERP da listagem de estoque com o cadastro local (mesma empresa).
	 *
	 * @param array|\ArrayObject $produtosErp Lista de objetos SOAP (sCodProduto, …)
	 * @return array<string,int> codigo => id do produto no portal
	 */
	private function mapCodigoEstoqueParaIdPortal($produtosErp): array {
		$codigos = [];
		foreach ((array)$produtosErp as $p) {
			$c = trim((string)($p->sCodProduto ?? ''));
			if ($c !== '') {
				$codigos[$c] = true;
			}
		}
		$lista = array_keys($codigos);
		if ($lista === []) {
			return [];
		}
		$idempresa = (int)$this->Auth->user('idempresa');
		$q = $this->Produtos->find()
			->select(['id', 'codigo'])
			->where(['idempresa' => $idempresa, 'codigo IN' => $lista]);
		$out = [];
		foreach ($q as $row) {
			$ck = trim((string)$row->codigo);
			if ($ck !== '') {
				$out[$ck] = (int)$row->id;
			}
		}

		return $out;
	}

	private function carregarDadosEstoque($bApenasComSaldo, $sCodProduto, $sDescricao) {
		$produtos = [];
		$produtosOpt = [];
		$empresa = $this->Empresas->get($this->Auth->user('idempresa'));

		$soapprodutos = ErpGridUrl::wsdl($empresa->urlerp);

		$this->runSoapBuffered(function () use ($soapprodutos, $bApenasComSaldo, &$sCodProduto, $sDescricao, &$produtos) {
			$soap = new CakeSoap(['wsdl' => $soapprodutos]);
			if ($soap === null) {
				throw new \Exception('Cliente SOAP não inicializado.');
			}

			if ($sCodProduto == 0 || $sCodProduto === '0') {
				$sCodProduto = null;
			}

			$response = $soap->sendRequest('GetEstoqueProdutos', [
				'Data' => [
					'iFilial' => C_Filial,
					'sChave' => C_ChaveAcesso,
					'bApenasComSaldo' => (bool)$bApenasComSaldo,
					'sCodProduto' => $sCodProduto,
					'sDescricao' => $sDescricao,
				]
			]);

			$result = $response->GetEstoqueProdutosResult ?? null;
			$lista = ($result && isset($result->tWsProdutosEstoque)) ? $result->tWsProdutosEstoque : null;

			if ($lista === null) {
				$produtos = [];
				return;
			}

			if (!is_array($lista)) {
				$lista = [$lista];
			}

			$produtos = $lista;
			usort($produtos, function ($a, $b) {
				$descA = $a->sDescProduto ?? '';
				$descB = $b->sDescProduto ?? '';
				if ($descA === $descB) {
					return 0;
				}
				return ($descA < $descB) ? -1 : 1;
			});
			$produtos = $this->aplicarRegraBuscaDescricao($produtos, $sDescricao);
		});

		$produtosOpt1 = $this->Produtos->find('all')
			->where(['idempresa' => $this->Auth->user('idempresa'), 'ativo' => 1])
			->order(['descricao'])
			->toArray();

		foreach ($produtosOpt1 as $reg) {
			$produtosOpt[$reg->codigo] = $reg->descricao . ' (' . $reg->codigo . ')';
		}

		return [$produtos, $produtosOpt, $sCodProduto];
	}

	private function extrairCodigosSelecionados($codigosParam) {
		if ($codigosParam === '') {
			return [];
		}
		$itens = array_filter(array_map('trim', explode(',', $codigosParam)), function ($v) {
			return $v !== '';
		});
		return array_values(array_unique($itens));
	}

	private function filtrarProdutosPorCodigos($produtos, $codigos) {
		$map = array_fill_keys($codigos, true);
		return array_values(array_filter($produtos, function ($p) use ($map) {
			$codigo = trim((string)($p->sCodProduto ?? ''));
			return isset($map[$codigo]);
		}));
	}

	private function aplicarRegraBuscaDescricao($produtos, $sDescricao) {
		$busca = trim((string)$sDescricao);
		if ($busca === '') {
			return $produtos;
		}

		$buscaNorm = $this->normalizarTextoBusca($busca);
		if ($buscaNorm === '') {
			return $produtos;
		}

		$tokens = preg_split('/\s+/', $buscaNorm, -1, PREG_SPLIT_NO_EMPTY);
		$stop = ['de', 'da', 'do', 'das', 'dos', 'e', 'em', 'com', 'para', 'por', 'a', 'o', 'as', 'os'];
		$tokens = array_values(array_filter($tokens, function ($t) use ($stop) {
			return mb_strlen($t) >= 2 && !in_array($t, $stop, true);
		}));

		if (empty($tokens)) {
			return $produtos;
		}

		$filtrados = array_filter($produtos, function ($p) use ($tokens) {
			$descNorm = $this->normalizarTextoBusca((string)($p->sDescProduto ?? ''));
			foreach ($tokens as $tk) {
				if (mb_strpos($descNorm, $tk) === false) {
					return false;
				}
			}
			return true;
		});

		usort($filtrados, function ($a, $b) use ($buscaNorm, $tokens) {
			$da = $this->normalizarTextoBusca((string)($a->sDescProduto ?? ''));
			$db = $this->normalizarTextoBusca((string)($b->sDescProduto ?? ''));

			$score = function ($desc) use ($buscaNorm, $tokens) {
				$s = 0;
				if ($desc === $buscaNorm) {
					$s += 100;
				}
				if (mb_strpos($desc, $buscaNorm) === 0) {
					$s += 50;
				}
				foreach ($tokens as $tk) {
					$pos = mb_strpos($desc, $tk);
					if ($pos === 0) {
						$s += 12;
					} elseif ($pos !== false) {
						$s += 5;
					}
				}
				return $s;
			};

			$sa = $score($da);
			$sb = $score($db);
			if ($sa === $sb) {
				return strcmp($da, $db);
			}
			return ($sa > $sb) ? -1 : 1;
		});

		return array_values($filtrados);
	}

	private function normalizarTextoBusca($txt) {
		$txt = mb_strtolower(trim((string)$txt), 'UTF-8');
		$txt = strtr($txt, [
			'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
			'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
			'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
			'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
			'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
			'ç' => 'c',
		]);
		$txt = preg_replace('/[^a-z0-9\s\-]/u', ' ', $txt);
		$txt = preg_replace('/\s+/', ' ', $txt);
		return trim((string)$txt);
	}

	/**
	 * O tipo da OS pode vir de constants antigas/novas; o BD da tabela produtos permanece no legado (1/2/3).
	 * Esta função devolve aliases compatíveis por semântica para evitar lista vazia na pesquisa.
	 *
	 * @param int $tipoInt tipo enviado pela grid (inteiro > 0).
	 * @return int[]
	 */
	private function produtosTipoPesquisaAliases(int $tipoInt): array {
		$out = [];
		$sem = $this->produtoTipoSemantic($tipoInt);
		if ($sem !== null) {
			$legacy = $this->produtoTipoLegacyBySemantic($sem);
			if ($legacy !== null) {
				$out[] = $legacy;
			}
		}
		$out[] = $tipoInt;
		$pairs = [];
		if (defined('C_ProdutosTipoProduto')) {
			$pairs[] = [(int) constant('C_ProdutosTipoProduto'), 1];
		}
		if (defined('C_ProdutosTipoServico')) {
			$pairs[] = [(int) constant('C_ProdutosTipoServico'), 2];
		}
		foreach ($pairs as $pair) {
			list($constTipo, $legacyTipo) = $pair;
			if ($constTipo <= 0 || $legacyTipo <= 0 || $constTipo === $legacyTipo) {
				continue;
			}
			if ($tipoInt === $constTipo) {
				$out[] = $legacyTipo;
			}
			if ($tipoInt === $legacyTipo) {
				$out[] = $constTipo;
			}
		}
		$out = array_values(array_unique(array_filter($out, function ($t) {
			return (int) $t > 0;
		})));

		// Compatibilidade legado: algumas bases antigas gravaram "Produto" como 0.
		$isTipoProduto = $sem === 'produto' || in_array(1, $out, true);
		if ($isTipoProduto) {
			$out[] = 0;
		}
		$out = array_values(array_unique(array_map('intval', $out)));

		return $out !== [] ? $out : [$tipoInt];
	}

	public function pesquisar() {
        $this->autoRender = false;
		/* Mesma origem do cadastro (tabela produtos no portal), alinhada ao módulo Produtos/Estoque para tipo produto;
		 * não misturar tipos: o cliente da OS deve enviar sempre o tipo da linha (C_ProdutosTipo* / tiposOpt). */
		$tipoParam = $this->request->getQuery('tipo');
		if ($tipoParam === null || $tipoParam === '' || !is_numeric($tipoParam) || (int) $tipoParam <= 0) {
			return $this->jsonResponse([
				'mensagem' => 'Informe o tipo do item para pesquisar (produto, serviço, licença ou locação).',
				'code' => 'pesquisa_tipo_obrigatorio',
			], 422);
		}
		$tipoInt = (int) $tipoParam;
		$tiposDb = $this->produtosTipoPesquisaAliases($tipoInt);

        $termo = $this->request->getQuery('termo');
        $idEmpresa = $this->Auth->user('idempresa');
		/* Mesmo critério da listagem Produtos (aba Produtos/Serviços): index() não filtra por ativo;
		 * filtrar só ativo=1 aqui esvaziava a modal quando ativo era null/0 legado ainda visível no módulo. */
        $query = $this->Produtos->find()
            ->select(['codigo', 'descricao', 'vlunitario', 'unidade', 'tipo'])
            ->where([
				'idempresa' => $idEmpresa,
				'tipo IN' => $tiposDb,
			]);
    
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



