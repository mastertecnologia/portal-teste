<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Utility\Fiscal\FiscalSpedGenerator;
use App\Utility\Fiscal\FiscalStorage;
use Cake\Core\Configure;
use Cake\Event\Event;

/**
 * Relatórios fiscais: livro de saídas/entradas, resumo mensal, impostos, SPED.
 */
class FiscalRelatoriosController extends AppController {

    use FiscalRegimeViewTrait;

    public function initialize() {
        parent::initialize();
        $this->loadModel('FiscalNotas');
        $this->loadModel('FiscalNotasItens');
        $this->loadModel('FiscalNotasImpostos');
        $this->loadModel('FiscalNotasItensSeries');
        $this->loadModel('FiscalEmpresasConfig');
        Configure::load('fiscal');
        FiscalStorage::ensureDirectories();
    }

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $this->set('title', 'Relatórios Fiscais');
        $this->set('pgmAdvancedModuleStylesheet', true);
    }

    public function isAuthorized($user) {
        if ((int)($user['role'] ?? 1) === 1) {
            return false;
        }
        return parent::isAuthorized($user);
    }

    /**
     * Index de relatórios.
     */
    public function index() {
        // Apenas exibe menu de relatórios disponíveis
    }

    /**
     * Livro fiscal de saídas.
     */
    public function livroSaidas() {
        $idempresa = $this->Auth->user('idempresa');
        $mesAno = $this->request->getQuery('mes_ano', date('Y-m'));
        $partes = explode('-', $mesAno);
        $ano = (int)($partes[0] ?? date('Y'));
        $mes = (int)($partes[1] ?? date('m'));

        $notas = $this->FiscalNotas->find()
            ->where([
                'FiscalNotas.idempresa' => $idempresa,
                'FiscalNotas.tipo_operacao' => 1,
                'FiscalNotas.status IN' => ['autorizada', 'cancelada'],
                'EXTRACT(YEAR FROM FiscalNotas.data_emissao)' => $ano,
                'EXTRACT(MONTH FROM FiscalNotas.data_emissao)' => $mes,
            ])
            ->contain([
                'Clientes' => ['fields' => ['id', 'razaosocial', 'cnpj']],
            ])
            ->order(['FiscalNotas.numero' => 'ASC'])
            ->toArray();

        // Totalizar
        $totais = ['valor_produtos' => 0, 'valor_icms' => 0, 'valor_ipi' => 0, 'valor_pis' => 0, 'valor_cofins' => 0, 'valor_iss' => 0, 'valor_total' => 0];
        foreach ($notas as $n) {
            if ($n->status === 'cancelada') continue;
            foreach (array_keys($totais) as $k) {
                $totais[$k] += (float)($n->{$k} ?? 0);
            }
        }

        $this->set(compact('notas', 'totais', 'mesAno', 'ano', 'mes'));
    }

    /**
     * Livro fiscal de entradas.
     */
    public function livroEntradas() {
        $idempresa = $this->Auth->user('idempresa');
        $mesAno = $this->request->getQuery('mes_ano', date('Y-m'));
        $partes = explode('-', $mesAno);
        $ano = (int)($partes[0] ?? date('Y'));
        $mes = (int)($partes[1] ?? date('m'));

        $notas = $this->FiscalNotas->find()
            ->where([
                'FiscalNotas.idempresa' => $idempresa,
                'FiscalNotas.tipo_operacao' => 0,
                'FiscalNotas.status IN' => ['autorizada', 'cancelada'],
                'EXTRACT(YEAR FROM FiscalNotas.data_emissao)' => $ano,
                'EXTRACT(MONTH FROM FiscalNotas.data_emissao)' => $mes,
            ])
            ->contain([
                'Clientes' => ['fields' => ['id', 'razaosocial', 'cnpj']],
            ])
            ->order(['FiscalNotas.numero' => 'ASC'])
            ->toArray();

        $totais = ['valor_produtos' => 0, 'valor_icms' => 0, 'valor_ipi' => 0, 'valor_total' => 0];
        foreach ($notas as $n) {
            if ($n->status === 'cancelada') continue;
            foreach (array_keys($totais) as $k) {
                $totais[$k] += (float)($n->{$k} ?? 0);
            }
        }

        $this->set(compact('notas', 'totais', 'mesAno', 'ano', 'mes'));
    }

    /**
     * Resumo mensal de impostos.
     */
    public function resumoMensal() {
        $idempresa = $this->Auth->user('idempresa');
        $mesAno = $this->request->getQuery('mes_ano', date('Y-m'));
        $partes = explode('-', $mesAno);
        $ano = (int)($partes[0] ?? date('Y'));
        $mes = (int)($partes[1] ?? date('m'));

        $resumo = $this->FiscalNotas->find()
            ->select([
                'modelo' => 'FiscalNotas.modelo',
                'qtd' => $this->FiscalNotas->find()->func()->count('*'),
                'valor_produtos' => $this->FiscalNotas->find()->func()->sum('valor_produtos'),
                'valor_icms' => $this->FiscalNotas->find()->func()->sum('valor_icms'),
                'valor_icms_st' => $this->FiscalNotas->find()->func()->sum('valor_icms_st'),
                'valor_ipi' => $this->FiscalNotas->find()->func()->sum('valor_ipi'),
                'valor_pis' => $this->FiscalNotas->find()->func()->sum('valor_pis'),
                'valor_cofins' => $this->FiscalNotas->find()->func()->sum('valor_cofins'),
                'valor_iss' => $this->FiscalNotas->find()->func()->sum('valor_iss'),
                'valor_total' => $this->FiscalNotas->find()->func()->sum('valor_total'),
            ])
            ->where([
                'FiscalNotas.idempresa' => $idempresa,
                'FiscalNotas.status' => 'autorizada',
                'EXTRACT(YEAR FROM FiscalNotas.data_emissao)' => $ano,
                'EXTRACT(MONTH FROM FiscalNotas.data_emissao)' => $mes,
            ])
            ->group('FiscalNotas.modelo')
            ->disableHydration()
            ->toArray();

        $configFiscal = $this->FiscalEmpresasConfig->getOrCreate($idempresa);
        $this->set(compact('resumo', 'mesAno', 'ano', 'mes', 'configFiscal'));
    }

    /**
     * Relatório de notas por cliente.
     */
    public function porCliente() {
        $idempresa = $this->Auth->user('idempresa');
        $mesAno = $this->request->getQuery('mes_ano', date('Y-m'));
        $partes = explode('-', $mesAno);
        $ano = (int)($partes[0] ?? date('Y'));
        $mes = (int)($partes[1] ?? date('m'));

        $dados = $this->FiscalNotas->find()
            ->select([
                'idcliente' => 'FiscalNotas.idcliente',
                'razaosocial' => 'Clientes.razaosocial',
                'qtd' => $this->FiscalNotas->find()->func()->count('*'),
                'valor_total' => $this->FiscalNotas->find()->func()->sum('FiscalNotas.valor_total'),
            ])
            ->contain(['Clientes'])
            ->where([
                'FiscalNotas.idempresa' => $idempresa,
                'FiscalNotas.status' => 'autorizada',
                'EXTRACT(YEAR FROM FiscalNotas.data_emissao)' => $ano,
                'EXTRACT(MONTH FROM FiscalNotas.data_emissao)' => $mes,
            ])
            ->group(['FiscalNotas.idcliente', 'Clientes.razaosocial'])
            ->order(['valor_total' => 'DESC'])
            ->disableHydration()
            ->toArray();

        $this->set(compact('dados', 'mesAno'));
    }

    /**
     * Relatório / extrato por número de série do produto (entradas e saídas).
     */
    public function porNumeroSerie() {
        $idempresa = $this->Auth->user('idempresa');
        $numeroSerie = trim((string)$this->request->getQuery('numero_serie', ''));
        $linhas = [];

        if ($numeroSerie !== '') {
            $filtros = array_filter([
                'numero_serie' => $numeroSerie,
                'tipo_operacao' => $this->request->getQuery('tipo_operacao'),
                'codigo_produto' => $this->request->getQuery('codigo_produto'),
                'data_inicio' => $this->request->getQuery('data_inicio'),
                'data_fim' => $this->request->getQuery('data_fim'),
            ], function ($v) {
                return $v !== null && $v !== '';
            });
            $linhas = $this->FiscalNotasItensSeries->findControlePorEmpresa($idempresa, $filtros)
                ->order(['FiscalNotas.data_emissao' => 'DESC', 'FiscalNotasItensSeries.id' => 'DESC'])
                ->toArray();
        }

        $statusList = Configure::read('Fiscal.status_nota');
        $this->set(compact('linhas', 'numeroSerie', 'statusList'));
    }

    /**
     * Exportação SPED Fiscal (EFD-ICMS/IPI).
     */
    public function exportarSped() {
        $idempresa = $this->Auth->user('idempresa');
        $mesAno = $this->request->getQuery('mes_ano', date('Y-m'));
        $partes = explode('-', $mesAno);
        $ano = (int)($partes[0] ?? date('Y'));
        $mes = (int)($partes[1] ?? date('m'));
        $dtIni = sprintf('%04d-%02d-01', $ano, $mes);
        $dtFim = date('Y-m-t', strtotime($dtIni));

        $this->loadModel('Empresas');
        $empresa = $this->Empresas->get($idempresa);
        $configFiscal = $this->FiscalEmpresasConfig->getOrCreate($idempresa);

        $gerador = new FiscalSpedGenerator(
            $empresa->toArray(),
            $configFiscal->toArray(),
            $dtIni,
            $dtFim
        );
        $conteudo = $gerador->gerar();

        $this->autoRender = false;
        $filename = 'SPED_FISCAL_' . $ano . str_pad($mes, 2, '0', STR_PAD_LEFT) . '.txt';
        $this->response = $this->response
            ->withType('text/plain')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withStringBody($conteudo);
        return $this->response;
    }

    /**
     * Exportar relatório (livro saídas/entradas/resumo) em Excel (.xlsx).
     */
    public function exportarExcel() {
        $idempresa = $this->Auth->user('idempresa');
        $tipo = (string)$this->request->getQuery('tipo', 'saidas');
        $mesAno = $this->request->getQuery('mes_ano', date('Y-m'));
        $partes = explode('-', $mesAno);
        $ano = (int)($partes[0] ?? date('Y'));
        $mes = (int)($partes[1] ?? date('m'));

        $tipoOp = ($tipo === 'entradas') ? 0 : 1;
        $notas = $this->FiscalNotas->find()
            ->where([
                'FiscalNotas.idempresa' => $idempresa,
                'FiscalNotas.tipo_operacao' => $tipoOp,
                'FiscalNotas.status IN' => ['autorizada', 'cancelada'],
                'EXTRACT(YEAR FROM FiscalNotas.data_emissao)' => $ano,
                'EXTRACT(MONTH FROM FiscalNotas.data_emissao)' => $mes,
            ])
            ->contain(['Clientes' => ['fields' => ['id', 'razaosocial', 'cnpj']]])
            ->order(['FiscalNotas.numero' => 'ASC'])
            ->toArray();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(($tipo === 'entradas' ? 'Entradas' : 'Saídas') . ' ' . $mesAno);

        // Header
        $headers = ['Número', 'Série', 'Modelo', 'Status', 'Data Emissão', 'Cliente', 'CNPJ/CPF', 'Vlr Produtos', 'ICMS', 'IPI', 'PIS', 'COFINS', 'ISS', 'Total'];
        foreach ($headers as $col => $h) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $h);
        }
        $sheet->getStyle('A1:N1')->getFont()->setBold(true);

        $row = 2;
        foreach ($notas as $n) {
            $cli = $n->cliente;
            $sheet->setCellValueByColumnAndRow(1, $row, $n->numero);
            $sheet->setCellValueByColumnAndRow(2, $row, $n->serie);
            $sheet->setCellValueByColumnAndRow(3, $row, $n->modelo);
            $sheet->setCellValueByColumnAndRow(4, $row, $n->status);
            $sheet->setCellValueByColumnAndRow(5, $row, $n->data_emissao ? $n->data_emissao->format('d/m/Y') : '');
            $sheet->setCellValueByColumnAndRow(6, $row, $cli ? ($cli->razaosocial ?: '') : '');
            $sheet->setCellValueByColumnAndRow(7, $row, $cli ? ($cli->cnpj ?: '') : '');
            $sheet->setCellValueByColumnAndRow(8, $row, (float)($n->valor_produtos ?? 0));
            $sheet->setCellValueByColumnAndRow(9, $row, (float)($n->valor_icms ?? 0));
            $sheet->setCellValueByColumnAndRow(10, $row, (float)($n->valor_ipi ?? 0));
            $sheet->setCellValueByColumnAndRow(11, $row, (float)($n->valor_pis ?? 0));
            $sheet->setCellValueByColumnAndRow(12, $row, (float)($n->valor_cofins ?? 0));
            $sheet->setCellValueByColumnAndRow(13, $row, (float)($n->valor_iss ?? 0));
            $sheet->setCellValueByColumnAndRow(14, $row, (float)($n->valor_total ?? 0));
            $row++;
        }

        // Format monetary columns
        for ($c = 8; $c <= 14; $c++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $sheet->getStyle($colLetter . '2:' . $colLetter . $row)
                ->getNumberFormat()->setFormatCode('#,##0.00');
        }

        $this->autoRender = false;
        $filename = 'Fiscal_' . ucfirst($tipo) . '_' . $mesAno . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        $this->response = $this->response
            ->withType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withStringBody($content);
        return $this->response;
    }
}
