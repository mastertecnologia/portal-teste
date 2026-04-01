<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

require_once ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';

/**
 * Módulo Financeiro
 * Dashboard, Contas a Receber, Contas a Pagar e Fluxo de Caixa.
 */
class FinanceiroController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('FinanceiroLancamentos');
		$this->loadModel('Clientes');
		$this->loadModel('Faturamento');
		$this->loadModel('FinanceiroLancamentoAnexos');
		$this->loadModel('Atividades');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->set('title', 'Financeiro');
	}

	public function isAuthorized($user) {
		if ((int)($user['role'] ?? 1) === C_RoleCliente) {
			return false;
		}
		return parent::isAuthorized($user);
	}

	/* ── Dashboard financeiro ──────────────────────────────────────────── */
	public function index() {
		$idempresa = $this->Auth->user('idempresa');

		$lancamentos = $this->FinanceiroLancamentos->find('all')
			->where(['FinanceiroLancamentos.idempresa' => $idempresa])
			->contain(['Clientes' => ['fields' => ['id', 'razaosocial', 'tipo', 'nome']]])
			->order(['FinanceiroLancamentos.data_vencimento' => 'ASC'])
			->toArray();

		// KPIs
		$kpi = [
			'total_receitas'  => 0,
			'total_despesas'  => 0,
			'a_receber'       => 0,
			'a_pagar'         => 0,
			'vencidos'        => 0,
			'recebido_mes'    => 0,
		];
		$hoje = date('Y-m-d');
		$mesAtual = date('Y-m');

		foreach ($lancamentos as $l) {
			if ($l->tipo === 'receita') {
				$kpi['total_receitas'] += $l->valor;
				if ($l->status === 'aberto') {
					$kpi['a_receber'] += $l->valor;
					if ($l->data_vencimento && $l->data_vencimento->format('Y-m-d') < $hoje) {
						$kpi['vencidos'] += $l->valor;
					}
				}
				if ($l->status === 'recebido' && $l->data_recebimento
					&& $l->data_recebimento->format('Y-m') === $mesAtual) {
					$kpi['recebido_mes'] += $l->valor;
				}
			} else {
				$kpi['total_despesas'] += $l->valor;
				if ($l->status === 'aberto') $kpi['a_pagar'] += $l->valor;
			}
		}

		// Últimos 6 meses — receitas x despesas por mês
		$grafico = [];
		for ($i = 5; $i >= 0; $i--) {
			$mes = date('Y-m', strtotime("-$i months"));
			$grafico[$mes] = ['receita' => 0, 'despesa' => 0];
		}
		foreach ($lancamentos as $l) {
			$mes = $l->data_lancamento ? $l->data_lancamento->format('Y-m') : null;
			if ($mes && isset($grafico[$mes])) {
				$grafico[$mes][$l->tipo === 'receita' ? 'receita' : 'despesa'] += $l->valor;
			}
		}

		// Próximos vencimentos (30 dias)
		$vencimentos = array_filter($lancamentos, function($l) use ($hoje) {
			if ($l->status !== 'aberto' || !$l->data_vencimento) return false;
			$dv = $l->data_vencimento->format('Y-m-d');
			return $dv >= $hoje && $dv <= date('Y-m-d', strtotime('+30 days'));
		});

		$this->set(compact('kpi', 'grafico', 'vencimentos', 'lancamentos'));
		$this->set('hideLayoutPageTitle', true);
	}

	/* ── Contas a receber ──────────────────────────────────────────────── */
	public function contasReceber() {
		$idempresa = $this->Auth->user('idempresa');
		$cliente   = $this->request->getQuery('cliente') ?? '';
		$status    = $this->request->getQuery('status') ?? 'aberto';

		$q = $this->FinanceiroLancamentos->find('all')
			->where([
				'FinanceiroLancamentos.idempresa' => $idempresa,
				'FinanceiroLancamentos.tipo'      => 'receita',
			])
			->contain([
				'Clientes'    => ['fields' => ['id', 'razaosocial', 'tipo', 'nome']],
				'Faturamento' => ['fields' => ['id', 'numero']],
			])
			->order(['FinanceiroLancamentos.data_vencimento' => 'ASC']);

		if ($status !== '') $q->where(['FinanceiroLancamentos.status' => $status]);
		if ($cliente !== '' && $cliente !== '0') $q->where(['FinanceiroLancamentos.idcliente' => $cliente]);

		$lancamentos = $q->toArray();

		$clientes = $this->Clientes->find('list', [
			'keyField'   => 'id',
			'valueField' => 'razaosocial',
		])->where(['idempresa' => $idempresa, 'inativo' => 0])->order(['razaosocial'])->toArray();

		$this->set(compact('lancamentos', 'clientes', 'status', 'cliente'));
		$this->set('title', 'Contas a Receber');
	}

	/**
	 * Detalhe do lançamento (contas a receber / fatura financeira).
	 */
	public function fatura($id = null) {
		$idempresa = $this->Auth->user('idempresa');
		$lancamento = $this->FinanceiroLancamentos->find('all')
			->where([
				'FinanceiroLancamentos.id' => $id,
				'FinanceiroLancamentos.idempresa' => $idempresa,
			])
			->contain([
				'Clientes' => ['fields' => ['id', 'razaosocial', 'tipo', 'nome', 'cnpj', 'cpf']],
				'Faturamento' => [
					'fields' => ['id', 'numero', 'status', 'valor_total', 'valor_subtotal', 'valor_desconto'],
					'FaturamentoItens',
				],
				'Users' => ['fields' => ['id', 'name', 'username']],
				'FinanceiroLancamentoAnexos' => [
					'sort' => ['FinanceiroLancamentoAnexos.id' => 'DESC'],
					'Users' => ['fields' => ['id', 'name', 'username']],
				],
			])
			->first();

		if (empty($lancamento)) {
			throw new \Cake\Http\Exception\NotFoundException(__('Lançamento não encontrado.'));
		}

		$historicoFatura = $this->_buildHistoricoFatura($lancamento);

		$auditoriaFatura = $this->Atividades->find('all')
			->contain(['Users' => ['fields' => ['Users.id', 'Users.name', 'Users.username']]])
			->where([
				'Atividades.controller' => 'Financeiro',
				'Atividades.idtable' => (int)$lancamento->id,
			])
			->order(['Atividades.id' => 'DESC'])
			->limit(80)
			->toArray();

		$this->set(compact('lancamento', 'historicoFatura', 'auditoriaFatura'));
		$this->set('title', 'Detalhe da fatura');
		$this->set('hideLayoutPageTitle', true);
	}

	/**
	 * Exporta resumo do lançamento em CSV (UTF-8).
	 */
	public function exportarFatura($id = null) {
		$this->autoRender = false;
		$idempresa = $this->Auth->user('idempresa');
		$l = $this->FinanceiroLancamentos->find('all')
			->where(['FinanceiroLancamentos.id' => $id, 'FinanceiroLancamentos.idempresa' => $idempresa])
			->contain(['Clientes' => ['fields' => ['razaosocial', 'tipo', 'nome']]])
			->first();

		if (empty($l)) {
			throw new \Cake\Http\Exception\NotFoundException(__('Lançamento não encontrado.'));
		}

		$nomeCli = '—';
		if (!empty($l->cliente)) {
			$nomeCli = ($l->cliente->tipo == 1) ? (string)($l->cliente->nome ?? '') : (string)($l->cliente->razaosocial ?? '');
		}

		$fh = fopen('php://temp', 'r+');
		fwrite($fh, "\xEF\xBB\xBF");
		fputcsv($fh, ['Campo', 'Valor']);
		fputcsv($fh, ['ID lançamento', (string)$l->id]);
		fputcsv($fh, ['Cliente', $nomeCli]);
		fputcsv($fh, ['Descrição', (string)$l->descricao]);
		fputcsv($fh, ['Valor', number_format((float)$l->valor, 2, ',', '.')]);
		fputcsv($fh, ['Vencimento', $l->data_vencimento ? $l->data_vencimento->format('d/m/Y') : '']);
		fputcsv($fh, ['Status', (string)$l->status]);
		fputcsv($fh, ['Data recebimento', $l->data_recebimento ? $l->data_recebimento->format('d/m/Y') : '']);
		rewind($fh);
		$csv = stream_get_contents($fh);
		fclose($fh);
		$fn = 'fatura-financeiro-' . (int)$l->id . '-' . date('Ymd-His') . '.csv';

		return $this->response
			->withType('text/csv; charset=UTF-8')
			->withDownload($fn)
			->withStringBody($csv);
	}

	/**
	 * Exporta detalhe do lançamento em PDF (mPDF), alinhado a Relatórios/Orcamentos.
	 */
	public function exportarFaturaPdf($id = null) {
		if (!class_exists(\Mpdf\Mpdf::class)) {
			$this->Flash->error(__('Biblioteca mPDF não disponível. Use a exportação em CSV.'));
			return $this->redirect(['action' => 'fatura', $id]);
		}

		$idempresa = $this->Auth->user('idempresa');
		$lancamento = $this->FinanceiroLancamentos->find('all')
			->where([
				'FinanceiroLancamentos.id' => $id,
				'FinanceiroLancamentos.idempresa' => $idempresa,
			])
			->contain([
				'Clientes' => ['fields' => ['id', 'razaosocial', 'tipo', 'nome', 'cnpj', 'cpf']],
				'Faturamento' => [
					'fields' => ['id', 'numero', 'status', 'valor_total', 'valor_subtotal', 'valor_desconto'],
					'FaturamentoItens',
				],
				'Users' => ['fields' => ['id', 'name', 'username']],
			])
			->first();

		if (empty($lancamento)) {
			throw new \Cake\Http\Exception\NotFoundException(__('Lançamento não encontrado.'));
		}

		$historicoFatura = $this->_buildHistoricoFatura($lancamento);

		$this->autoRender = false;
		$this->viewBuilder()->setLayout(false);
		$this->set(compact('lancamento', 'historicoFatura'));
		$view = $this->createView();
		$html = $view->render('pdf_fatura_financeiro');

		$tmpDir = TMP . 'mpdf' . DS;
		if (!is_dir($tmpDir)) {
			mkdir($tmpDir, 0775, true);
		}
		$mpdf = new \Mpdf\Mpdf([
			'mode' => 'utf-8',
			'format' => 'A4',
			'tempDir' => $tmpDir,
		]);
		$mpdf->WriteHTML($html);
		$pdf = $mpdf->Output('', 'S');
		$fn = 'fatura-financeiro-' . (int)$lancamento->id . '-' . date('Ymd-His') . '.pdf';

		return $this->response
			->withType('application/pdf')
			->withDownload($fn)
			->withStringBody($pdf);
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $l
	 * @return array<int,array{dt:\DateTimeInterface|string,texto:string}>
	 */
	protected function _buildHistoricoFatura($l) {
		$rows = [];
		if (!empty($l->created)) {
			$rows[] = ['dt' => $l->created, 'texto' => 'Lançamento criado'];
		}
		if (!empty($l->modified) && (string)$l->modified !== (string)$l->created) {
			$rows[] = ['dt' => $l->modified, 'texto' => 'Registro alterado'];
		}
		if (in_array($l->status, ['recebido', 'pago'], true) && !empty($l->data_recebimento)) {
			$rows[] = ['dt' => $l->data_recebimento, 'texto' => 'Recebimento registrado'];
		}
		usort($rows, function ($a, $b) {
			$ta = $a['dt'] instanceof \DateTimeInterface ? $a['dt']->getTimestamp() : 0;
			$tb = $b['dt'] instanceof \DateTimeInterface ? $b['dt']->getTimestamp() : 0;
			return $tb <=> $ta;
		});

		return $rows;
	}

	/**
	 * Diretório físico dos anexos do lançamento (por empresa).
	 */
	protected function _dirFinanceiroAnexos($idempresa, $idlancamento) {
		return WWW_ROOT . 'arquivos' . DS . 'financeiro_lancamentos' . DS . (int)$idempresa . DS . (int)$idlancamento;
	}

	/**
	 * POST — upload de anexo na fatura (lançamento).
	 */
	public function adicionarAnexoFatura($id = null) {
		$this->request->allowMethod(['post']);
		$idempresa = (int)$this->Auth->user('idempresa');
		$lancamento = $this->FinanceiroLancamentos->find('all')
			->where(['FinanceiroLancamentos.id' => $id, 'FinanceiroLancamentos.idempresa' => $idempresa])
			->first();

		if (empty($lancamento)) {
			$this->Flash->error(__('Lançamento não encontrado.'));
			return $this->redirect(['action' => 'contasReceber']);
		}

		$file = $this->request->getData('anexo');
		if (empty($file) || !is_array($file) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
			$this->Flash->error(__('Selecione um arquivo para enviar.'));
			return $this->redirect(['action' => 'fatura', $id]);
		}

		$orig = (string)($file['name'] ?? '');
		if ($orig === '' || strpos($orig, '..') !== false || strpos($orig, '/') !== false || strpos($orig, '\\') !== false) {
			$this->Flash->error(__('Nome de arquivo inválido.'));
			return $this->redirect(['action' => 'fatura', $id]);
		}

		$ext = pathinfo($orig, PATHINFO_EXTENSION);
		$base = pathinfo($orig, PATHINFO_FILENAME);
		$safeBase = trim(preg_replace('/[^a-zA-Z0-9._-]+/', '_', (string)$base), '._-') ?: 'arquivo';
		$safeExt = $ext !== '' ? '.' . preg_replace('/[^a-zA-Z0-9]/', '', (string)$ext) : '';
		$stored = uniqid('fl_', true) . '_' . $safeBase . $safeExt;

		$dir = $this->_dirFinanceiroAnexos($idempresa, (int)$id);
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
		$full = $dir . DS . $stored;
		if (!move_uploaded_file($file['tmp_name'], $full)) {
			$this->Flash->error(__('Não foi possível salvar o arquivo.'));
			return $this->redirect(['action' => 'fatura', $id]);
		}

		$anexo = $this->FinanceiroLancamentoAnexos->newEntity([
			'idlancamento' => (int)$id,
			'idempresa' => $idempresa,
			'arquivo' => $stored,
			'nome_original' => $orig,
			'iduser' => (int)$this->Auth->user('id'),
		]);
		if (!$this->FinanceiroLancamentoAnexos->save($anexo)) {
			@unlink($full);
			$this->Flash->error(__('Erro ao registrar o anexo.'));
			return $this->redirect(['action' => 'fatura', $id]);
		}

		$uid = $this->Auth->user('id');
		if ($uid) {
			$this->Atividades->registrar($uid, 'Financeiro', 'adicionarAnexoFatura', (int)$id);
		}
		$this->Flash->success(__('Anexo enviado.'));
		return $this->redirect(['action' => 'fatura', $id]);
	}

	/**
	 * GET — download de anexo.
	 */
	public function baixarAnexoFatura($idanexo = null) {
		$this->autoRender = false;
		$idempresa = (int)$this->Auth->user('idempresa');
		$anexo = $this->FinanceiroLancamentoAnexos->find('all')
			->where([
				'FinanceiroLancamentoAnexos.id' => $idanexo,
				'FinanceiroLancamentoAnexos.idempresa' => $idempresa,
			])
			->first();

		if (empty($anexo)) {
			throw new \Cake\Http\Exception\NotFoundException(__('Anexo não encontrado.'));
		}

		$full = $this->_dirFinanceiroAnexos($anexo->idempresa, $anexo->idlancamento) . DS . $anexo->arquivo;
		if (!is_readable($full)) {
			$this->Flash->error(__('Arquivo não localizado no servidor.'));
			return $this->redirect(['action' => 'fatura', $anexo->idlancamento]);
		}

		$downloadName = (string)($anexo->nome_original ?: basename((string)$anexo->arquivo));
		$downloadName = str_replace(['"', "\r", "\n"], '', basename($downloadName));

		return $this->response->withFile($full, ['download' => true, 'name' => $downloadName]);
	}

	/**
	 * POST — remove anexo.
	 */
	public function removerAnexoFatura($idanexo = null) {
		$this->request->allowMethod(['post']);
		$idempresa = (int)$this->Auth->user('idempresa');
		$anexo = $this->FinanceiroLancamentoAnexos->find('all')
			->where([
				'FinanceiroLancamentoAnexos.id' => $idanexo,
				'FinanceiroLancamentoAnexos.idempresa' => $idempresa,
			])
			->first();

		if (empty($anexo)) {
			$this->Flash->error(__('Anexo não encontrado.'));
			return $this->redirect($this->referer(['action' => 'contasReceber']));
		}

		$idLanc = (int)$anexo->idlancamento;
		$full = $this->_dirFinanceiroAnexos($anexo->idempresa, $anexo->idlancamento) . DS . $anexo->arquivo;
		if (is_file($full)) {
			@unlink($full);
		}
		$this->FinanceiroLancamentoAnexos->delete($anexo);
		$uid = $this->Auth->user('id');
		if ($uid) {
			$this->Atividades->registrar($uid, 'Financeiro', 'removerAnexoFatura', $idLanc);
		}
		$this->Flash->success(__('Anexo removido.'));
		return $this->redirect(['action' => 'fatura', $idLanc]);
	}

	/* ── Registrar recebimento ─────────────────────────────────────────── */
	public function registrarRecebimento($id = null) {
		$this->request->allowMethod(['post']);
		$idempresa = $this->Auth->user('idempresa');

		$lancamento = $this->FinanceiroLancamentos->find('all')
			->where(['id' => $id, 'idempresa' => $idempresa])->first();

		if (empty($lancamento)) {
			return $this->jsonResponse(['ok' => false, 'msg' => 'Não encontrado.'], 404);
		}

		$lancamento->status = 'recebido';
		$lancamento->data_recebimento = $this->request->getData('data_recebimento') ?? date('Y-m-d');
		if (!$this->FinanceiroLancamentos->save($lancamento)) {
			return $this->jsonResponse(['ok' => false, 'msg' => 'Não foi possível salvar.'], 500);
		}

		$uid = $this->Auth->user('id');
		if ($uid) {
			$this->Atividades->registrar($uid, 'Financeiro', 'registrarRecebimento', (int)$lancamento->id);
		}

		// Atualiza status do faturamento vinculado
		if (!empty($lancamento->idfaturamento)) {
			$fat = $this->Faturamento->find('all')
				->where(['id' => $lancamento->idfaturamento])->first();
			if (!empty($fat)) {
				$fat->status = 'pago';
				$this->Faturamento->save($fat);
			}
		}

		return $this->jsonResponse(['ok' => true]);
	}
}
