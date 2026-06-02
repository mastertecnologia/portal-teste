<?php
declare(strict_types=1);

namespace App\Test\TestCase\Utility;

use App\Utility\ClientesPapelCadastro;
use Cake\TestSuite\TestCase;

class ClientesPapelCadastroTest extends TestCase {

	public function testWhereFornecedorExigeColunaEhFornecedor(): void {
		$where = ClientesPapelCadastro::whereFornecedor(1, true);
		$this->assertTrue($where['Clientes.eh_fornecedor']);
		$this->assertSame(1, $where['Clientes.idempresa']);
	}

	public function testWhereFornecedorSemColunasNaoListaTodosPj(): void {
		$where = ClientesPapelCadastro::whereFornecedor(1, false);
		$this->assertSame(0, $where['Clientes.id']);
		$this->assertArrayNotHasKey('Clientes.tipo', $where);
	}
}
