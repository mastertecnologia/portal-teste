<?php
namespace App\Test\TestCase\Utility;

use App\Utility\ProdutoTipoOrcamentoUtil;
use Cake\TestSuite\TestCase;

class ProdutoTipoOrcamentoUtilTest extends TestCase
{
	public function testLegacyFormProdutoIsProdBadge(): void
	{
		$r = ProdutoTipoOrcamentoUtil::labelAndBadge(1);
		$this->assertSame('Produto', $r['tipoLabel']);
		$this->assertSame('prod', $r['badge']);
	}

	public function testLegacyFormServicoIsSrvBadge(): void
	{
		$r = ProdutoTipoOrcamentoUtil::labelAndBadge(2);
		$this->assertSame('Serviço', $r['tipoLabel']);
		$this->assertSame('srv', $r['badge']);
	}

	public function testConstantZeroBasedProduto(): void
	{
		if (!defined('C_ProdutosTipoProduto')) {
			$this->markTestSkipped('TicketConstants not loaded');
		}
		$r = ProdutoTipoOrcamentoUtil::labelAndBadge((int)constant('C_ProdutosTipoProduto'));
		$this->assertSame('prod', $r['badge']);
	}
}
