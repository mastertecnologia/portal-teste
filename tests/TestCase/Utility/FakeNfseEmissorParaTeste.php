<?php
declare(strict_types=1);

namespace App\Test\TestCase\Utility;

use App\Utility\Fiscal\Nfse\NfseEmissorInterface;

/** Duplo de teste para NfseEmissorResolver (não usar em produção). */
class FakeNfseEmissorParaTeste implements NfseEmissorInterface {

    public function emitir(array $nota, array $config, array $empresa): array {
        return ['success' => true, 'protocolo' => 'TEST-1', 'mensagem' => 'OK'];
    }
}
