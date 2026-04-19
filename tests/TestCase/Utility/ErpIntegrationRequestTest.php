<?php
namespace App\Test\TestCase\Utility;

use App\Utility\ErpIntegrationRequest;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

class ErpIntegrationRequestTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
        Configure::delete('ErpApi');
    }

    public function testReadEmpresaAndTokenAllowsQueryByDefault(): void
    {
        Configure::write('ErpApi.header_only_credentials', false);
        $req = new ServerRequest([
            'url' => '/x',
            'query' => ['empresa' => '5', 'token' => 'abc'],
        ]);

        list($e, $t, $err) = ErpIntegrationRequest::readEmpresaAndToken($req);
        $this->assertSame('5', $e);
        $this->assertSame('abc', $t);
        $this->assertNull($err);
    }

    public function testReadEmpresaAndTokenHeaderPreferredOverQuery(): void
    {
        Configure::write('ErpApi.header_only_credentials', false);
        $req = new ServerRequest([
            'url' => '/x',
            'query' => ['empresa' => '1', 'token' => 'q'],
        ]);
        $req = $req->withHeader('empresa', '99')->withHeader('token', 'h');

        list($e, $t, $err) = ErpIntegrationRequest::readEmpresaAndToken($req);
        $this->assertSame('99', $e);
        $this->assertSame('h', $t);
        $this->assertNull($err);
    }

    public function testReadEmpresaAndTokenHeaderOnlyRejectsQuery(): void
    {
        Configure::write('ErpApi.header_only_credentials', true);
        $req = new ServerRequest([
            'url' => '/x',
            'query' => ['token' => 'x'],
        ]);
        $req = $req->withHeader('empresa', '1')->withHeader('token', 'ok');

        list($e, $t, $err) = ErpIntegrationRequest::readEmpresaAndToken($req);
        $this->assertNotNull($err);
        $this->assertSame('', $e);
        $this->assertSame('', $t);
    }

    public function testAccessControlAllowOriginWildcardWhenListEmpty(): void
    {
        Configure::write('ErpApi.cors_allowed_origins', []);
        $req = new ServerRequest(['url' => '/x']);
        $req = $req->withHeader('Origin', 'https://evil.example');

        $this->assertSame(
            '*',
            ErpIntegrationRequest::accessControlAllowOriginValue($req),
        );
    }

    public function testAccessControlAllowOriginWhitelist(): void
    {
        Configure::write('ErpApi.cors_allowed_origins', [
            'https://erp.internal:85',
        ]);
        $req = new ServerRequest(['url' => '/x']);
        $req = $req->withHeader('Origin', 'https://erp.internal:85');

        $this->assertSame(
            'https://erp.internal:85',
            ErpIntegrationRequest::accessControlAllowOriginValue($req),
        );
    }

    public function testAccessControlAllowOriginUnknownBlocked(): void
    {
        Configure::write('ErpApi.cors_allowed_origins', [
            'https://erp.internal:85',
        ]);
        $req = new ServerRequest(['url' => '/x']);
        $req = $req->withHeader('Origin', 'https://other.example');

        $this->assertNull(
            ErpIntegrationRequest::accessControlAllowOriginValue($req),
        );
    }
}
