<?php
namespace App\Test\TestCase\Utility;

use App\Utility\Fiscal\FiscalSpedGenerator;
use Cake\TestSuite\TestCase;
use ReflectionClass;

/**
 * Garante que o bloco 9 do SPED (9900, 9990, 9999) reflete as linhas já geradas.
 */
class FiscalSpedGeneratorBloco9Test extends TestCase {

    public function testBloco9Contagens9990e9999() {
        $gen = new FiscalSpedGenerator(['id' => 1], [], '2024-01-01', '2024-01-31');
        $ref = new ReflectionClass(FiscalSpedGenerator::class);

        $propLinhas = $ref->getProperty('linhas');
        $propLinhas->setAccessible(true);
        $propLinhas->setValue($gen, [
            '|0000|015|0|01012024|31012024|EMPRESA|00000000000000||SP||3550308||||0|1|',
            '|0001|0|',
        ]);

        $m = $ref->getMethod('bloco9');
        $m->setAccessible(true);
        $m->invoke($gen);

        $linhas = $propLinhas->getValue($gen);
        $this->assertNotEmpty($linhas);

        $last = $linhas[count($linhas) - 1];
        $this->assertStringStartsWith('|9999|', $last);
        $parts9999 = explode('|', $last);
        $this->assertSame('11', $parts9999[2] ?? '', '2 linhas antes + 9001 + 6×9900 + 9990 + 9999');

        $penult = $linhas[count($linhas) - 2];
        $this->assertStringStartsWith('|9990|', $penult);
        $parts9990 = explode('|', $penult);
        $this->assertSame('8', $parts9990[2] ?? '', '9001 + 6 linhas 9900 + 9990');

        $n9900 = 0;
        foreach ($linhas as $ln) {
            if (strpos($ln, '|9900|') === 0) {
                $n9900++;
            }
        }
        $this->assertSame(6, $n9900, 'um 9900 por tipo: 0000,0001,9001,9900,9990,9999');
    }
}
