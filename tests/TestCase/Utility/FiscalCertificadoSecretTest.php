<?php
declare(strict_types=1);
namespace App\Test\TestCase\Utility;

use App\Utility\Fiscal\FiscalCertificadoSecret;
use PHPUnit\Framework\TestCase;

class FiscalCertificadoSecretTest extends TestCase {

	public function testPfxBytesFromString(): void {
		$c = new \stdClass();
		$c->arquivo_pfx = 'pfx-bytes';
		$this->assertSame('pfx-bytes', FiscalCertificadoSecret::pfxBytes($c));
	}

	public function testPfxBytesFromResource(): void {
		$h = fopen('php://memory', 'r+b');
		$this->assertNotFalse($h);
		fwrite($h, 'abc');
		rewind($h);
		$c = new \stdClass();
		$c->arquivo_pfx = $h;
		$this->assertSame('abc', FiscalCertificadoSecret::pfxBytes($c));
		fclose($h);
	}

	public function testPfxBytesMissingProperty(): void {
		$c = new \stdClass();
		$this->assertSame('', FiscalCertificadoSecret::pfxBytes($c));
	}
}
