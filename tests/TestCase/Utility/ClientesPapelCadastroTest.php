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

	public function testCodigoExibicaoFornecedor(): void {
		$entity = new \Cake\ORM\Entity([
			'id' => 572,
			'public_code' => 'P00000365',
			'eh_fornecedor' => true,
		]);
		$this->assertSame('FOR-0572', ClientesPapelCadastro::codigoExibicao($entity, true, true));
		$this->assertSame('P00000365', ClientesPapelCadastro::codigoExibicao($entity, true, false));
	}
}
