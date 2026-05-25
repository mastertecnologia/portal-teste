<?php
declare(strict_types=1);

namespace App\Controller\Traits;

use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

/**
 * Contatos, arquivos e flags CRM usados pela Visão 360° (legado + protótipo).
 */
trait ClientesVisao360SupportTrait {

	protected function _clientesListarArquivosCliente(int $idcliente, int $idempresa): array {
		$items = [];
		try {
			$this->loadModel('Ticketsanexos');
			$rows = $this->Ticketsanexos->find()
				->contain(['Tickets'])
				->innerJoinWith('Tickets', function ($q) use ($idcliente, $idempresa) {
					return $q->where([
						'Tickets.idcliente' => $idcliente,
						'Tickets.idempresa' => $idempresa,
					]);
				})
				->order(['Ticketsanexos.id' => 'DESC'])
				->limit(150)
				->all();
			foreach ($rows as $reg) {
				$ticket = $reg->ticket ?? null;
				$nome = trim((string)($reg->arquivo ?? ''));
				if ($nome === '') {
					continue;
				}
				$meta = $this->_clientesArquivoMeta($nome);
				$tid = $ticket ? (int)$ticket->id : (int)($reg->idticket ?? 0);
				$items[] = [
					'label' => $nome,
					'sub' => $tid > 0 ? __('Ticket #{0}', $tid) : __('Ticket'),
					'origem' => 'ticket',
					'filtro' => $meta['filtro'],
					'icon' => $meta['icon'],
					'icon_tone' => $meta['tone'],
					'data_fmt' => '',
					'url' => $tid > 0 ? Router::url(['controller' => 'Tickets', 'action' => 'view', $tid]) : null,
					'sort_ts' => (int)$reg->id,
				];
			}
		} catch (\Throwable $e) {
		}
		try {
			$anexosTbl = TableRegistry::getTableLocator()->get('FinanceiroLancamentoAnexos');
			$rowsFin = $anexosTbl->find()
				->contain(['FinanceiroLancamentos'])
				->innerJoinWith('FinanceiroLancamentos', function ($q) use ($idcliente, $idempresa) {
					return $q->where([
						'FinanceiroLancamentos.idcliente' => $idcliente,
						'FinanceiroLancamentos.idempresa' => $idempresa,
					]);
				})
				->order(['FinanceiroLancamentoAnexos.id' => 'DESC'])
				->limit(150)
				->all();
			foreach ($rowsFin as $reg) {
				$nome = trim((string)($reg->nome_original ?? $reg->arquivo ?? ''));
				if ($nome === '') {
					$nome = trim((string)($reg->arquivo ?? ''));
				}
				if ($nome === '') {
					continue;
				}
				$meta = $this->_clientesArquivoMeta($nome);
				$lid = (int)($reg->idlancamento ?? 0);
				$created = $reg->created ?? null;
				$dataFmt = $created instanceof \DateTimeInterface
					? $created->i18nFormat('dd/MM/yyyy')
					: '';
				$items[] = [
					'label' => $nome,
					'sub' => __('Financeiro · lançamento #{0}', $lid > 0 ? $lid : '—'),
					'origem' => 'financeiro',
					'filtro' => $meta['filtro'],
					'icon' => $meta['icon'],
					'icon_tone' => $meta['tone'],
					'data_fmt' => $dataFmt,
					'url' => $lid > 0 ? Router::url(['controller' => 'Financeiro', 'action' => 'fatura', $lid]) : null,
					'sort_ts' => $created instanceof \DateTimeInterface ? $created->getTimestamp() : (int)$reg->id,
				];
			}
		} catch (\Throwable $e) {
		}

		usort($items, static function ($a, $b) {
			return ($b['sort_ts'] ?? 0) <=> ($a['sort_ts'] ?? 0);
		});

		return $items;
	}

	/**
	 * @param array<int,array<string,mixed>> $items
	 * @return array<string,int>
	 */
	protected function _clientesArquivosFiltros(array $items): array {
		$out = [
			'todos' => count($items),
			'tickets' => 0,
			'financeiro' => 0,
			'fotos' => 0,
			'pdf' => 0,
			'doc' => 0,
		];
		foreach ($items as $it) {
			$origem = (string)($it['origem'] ?? '');
			if ($origem === 'ticket') {
				$out['tickets']++;
			} elseif ($origem === 'financeiro') {
				$out['financeiro']++;
			}
			$f = (string)($it['filtro'] ?? '');
			if (isset($out[$f])) {
				$out[$f]++;
			}
		}

		return $out;
	}

	/**
	 * @return array{icon:string,tone:string,filtro:string}
	 */
	protected function _clientesArquivoMeta(string $nomeArquivo): array {
		$ext = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));
		if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true)) {
			return ['icon' => 'fa-image', 'tone' => 'img', 'filtro' => 'fotos'];
		}
		if ($ext === 'pdf') {
			return ['icon' => 'fa-file-pdf', 'tone' => 'pdf', 'filtro' => 'pdf'];
		}

		return ['icon' => 'fa-file-alt', 'tone' => 'doc', 'filtro' => 'doc'];
	}

	protected function _clientesCrmFinanceReady(): bool {
		try {
			$schema = $this->Clientes->getSchema();

			return $schema->hasColumn('limite_credito');
		} catch (\Throwable $e) {
			return false;
		}
	}

	protected function _clientesContatosReady(): bool {
		try {
			$tables = array_map('strtolower', $this->Clientes->getConnection()->getSchemaCollection()->listTables());

			return in_array('clientes_contatos', $tables, true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * @return \App\Model\Entity\ClientesContato[]
	 */
	protected function _clientesContatosList(int $idcliente): array {
		if (!$this->_clientesContatosReady() || $idcliente <= 0) {
			return [];
		}
		try {
			$tbl = TableRegistry::getTableLocator()->get('ClientesContatos');

			return $tbl->find()
				->where(['ClientesContatos.idcliente' => $idcliente])
				->order(['ClientesContatos.principal' => 'DESC', 'ClientesContatos.nome' => 'ASC'])
				->all()
				->toArray();
		} catch (\Throwable $e) {
			return [];
		}
	}

	/**
	 * GET — lista contatos do cliente (JSON).
	 *
	 * @param int|string|null $id idcliente
	 */
	public function apiContatos($id = null) {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		if (!$this->_clientesContatosReady()) {
			return $this->jsonResponse(['ok' => false, 'error' => __('Módulo de contatos indisponível. Rode as migrations.')], 503);
		}
		$cliente = $this->_findClienteForCurrentUser($id);
		if (empty($cliente)) {
			return $this->jsonResponse(['ok' => false, 'error' => __('Cliente não encontrado.')], 404);
		}
		$items = [];
		foreach ($this->_clientesContatosList((int)$cliente->id) as $c) {
			$items[] = $this->_clientesContatoJsonRow($c);
		}

		return $this->jsonResponse(['ok' => true, 'contatos' => $items]);
	}

	/**
	 * POST — criar ou atualizar contato.
	 *
	 * @param int|string|null $id idcliente
	 */
	public function apiContatoSalvar($id = null) {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		if (!$this->_clientesContatosReady()) {
			return $this->jsonResponse(['ok' => false, 'error' => __('Módulo de contatos indisponível.')], 503);
		}
		$cliente = $this->_findClienteForCurrentUser($id);
		if (empty($cliente)) {
			return $this->jsonResponse(['ok' => false, 'error' => __('Cliente não encontrado.')], 404);
		}
		$tbl = TableRegistry::getTableLocator()->get('ClientesContatos');
		$data = $this->request->getData();
		$contatoId = (int)($data['id'] ?? 0);
		$entity = $contatoId > 0
			? $tbl->find()->where(['id' => $contatoId, 'idcliente' => (int)$cliente->id])->first()
			: $tbl->newEntity();
		if ($contatoId > 0 && $entity === null) {
			return $this->jsonResponse(['ok' => false, 'error' => __('Contato não encontrado.')], 404);
		}
		$patch = [
			'idcliente' => (int)$cliente->id,
			'idempresa' => (int)$cliente->idempresa,
			'nome' => trim((string)($data['nome'] ?? '')),
			'cargo' => trim((string)($data['cargo'] ?? '')),
			'email' => trim((string)($data['email'] ?? '')),
			'fone' => trim((string)($data['fone'] ?? '')),
			'principal' => !empty($data['principal']),
		];
		$entity = $tbl->patchEntity($entity, $patch);
		if (!empty($patch['principal'])) {
			$tbl->updateAll(['principal' => false], ['idcliente' => (int)$cliente->id]);
		}
		if (!$tbl->save($entity)) {
			$err = $entity->getErrors();
			$msg = __('Não foi possível salvar o contato.');
			if (!empty($err['nome'])) {
				$msg = (string)array_values($err['nome'])[0];
			}

			return $this->jsonResponse(['ok' => false, 'error' => $msg, 'errors' => $err], 422);
		}

		return $this->jsonResponse(['ok' => true, 'contato' => $this->_clientesContatoJsonRow($entity)]);
	}

	/**
	 * POST — excluir contato.
	 *
	 * @param int|string|null $id idcliente
	 */
	public function apiContatoExcluir($id = null) {
		$this->request->allowMethod(['post', 'delete']);
		$this->autoRender = false;
		if (!$this->_clientesContatosReady()) {
			return $this->jsonResponse(['ok' => false, 'error' => __('Módulo de contatos indisponível.')], 503);
		}
		$cliente = $this->_findClienteForCurrentUser($id);
		if (empty($cliente)) {
			return $this->jsonResponse(['ok' => false, 'error' => __('Cliente não encontrado.')], 404);
		}
		$contatoId = (int)$this->request->getData('id', $this->request->getQuery('id'));
		if ($contatoId <= 0) {
			return $this->jsonResponse(['ok' => false, 'error' => __('ID inválido.')], 400);
		}
		$tbl = TableRegistry::getTableLocator()->get('ClientesContatos');
		$row = $tbl->find()->where(['id' => $contatoId, 'idcliente' => (int)$cliente->id])->first();
		if ($row === null) {
			return $this->jsonResponse(['ok' => false, 'error' => __('Contato não encontrado.')], 404);
		}
		if (!$tbl->delete($row)) {
			return $this->jsonResponse(['ok' => false, 'error' => __('Falha ao excluir.')], 500);
		}

		return $this->jsonResponse(['ok' => true]);
	}

	/**
	 * @param \App\Model\Entity\ClientesContato $c
	 * @return array<string,mixed>
	 */
	protected function _clientesContatoJsonRow($c) {
		$nome = trim((string)$c->nome);
		$parts = preg_split('/\s+/', $nome, -1, PREG_SPLIT_NO_EMPTY);
		$ini = strtoupper(substr($parts[0] ?? 'C', 0, 1)) . strtoupper(substr($parts[1] ?? '', 0, 1));
		$tones = ['teal', 'blue', 'rose', 'orange', 'purple', 'navy'];

		return [
			'id' => (int)$c->id,
			'nome' => $nome,
			'cargo' => trim((string)($c->cargo ?? '')),
			'email' => trim((string)($c->email ?? '')),
			'fone' => trim((string)($c->fone ?? '')),
			'principal' => !empty($c->principal),
			'iniciais' => $ini !== '' ? $ini : 'C',
			'av_tone' => $tones[(int)$c->id % count($tones)],
		];
	}
}
