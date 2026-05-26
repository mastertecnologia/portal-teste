<?php
namespace App\Test\TestCase\Utility;

use App\Utility\OrcamentoDescontoUtil;
use Cake\TestSuite\TestCase;

class OrcamentoDescontoUtilTest extends TestCase
{
	public function testDescontoPct(): void
	{
		$this->assertSame(22.0, OrcamentoDescontoUtil::descontoAbsoluto(220.0, 10.0, 'pct'));
	}

	public function testDescontoFixCap(): void
	{
		$this->assertSame(50.0, OrcamentoDescontoUtil::descontoAbsoluto(40.0, 50.0, 'fix'));
	}

	public function testLinhaLiquido(): void
	{
		$row = (object)[
			'valordoservico' => 100.0,
			'valormensal' => 0,
			'desconto_valor' => 10.0,
			'desconto_tipo' => 'pct',
		];
		$this->assertSame(90.0, OrcamentoDescontoUtil::linhaLiquido($row));
	}
}
