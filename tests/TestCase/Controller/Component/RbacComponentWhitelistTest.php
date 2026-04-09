<?php
namespace App\Test\TestCase\Controller\Component;

use App\Controller\Component\RbacComponent;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Cobre `_isWhitelisted` (lista em config) sem I/O nem BD — reflexão + mock sem construtor.
 */
class RbacComponentWhitelistTest extends TestCase {

	private function invokeIsWhitelisted($controllerLower, $actionLower, array $cfg): bool {
		$stub = $this->getMockBuilder(RbacComponent::class)
			->disableOriginalConstructor()
			->getMock();
		$m = new ReflectionMethod(RbacComponent::class, '_isWhitelisted');
		$m->setAccessible(true);

		return $m->invoke($stub, $controllerLower, $actionLower, $cfg);
	}

	public function testWhitelistExactActionMatch() {
		$cfg = ['whitelist' => ['users#accessdenied', 'pages#display']];
		$this->assertTrue($this->invokeIsWhitelisted('users', 'accessdenied', $cfg));
		$this->assertFalse($this->invokeIsWhitelisted('users', 'login', $cfg));
	}

	public function testWhitelistWildcardAction() {
		$cfg = ['whitelist' => ['error#*']];
		$this->assertTrue($this->invokeIsWhitelisted('error', 'notfound', $cfg));
	}

	public function testWhitelistSkipsEntriesWithoutHash() {
		$cfg = ['whitelist' => ['no-hash-at-all', 'users#index']];
		$this->assertTrue($this->invokeIsWhitelisted('users', 'index', $cfg));
		$this->assertFalse($this->invokeIsWhitelisted('no-hash-at-all', 'x', $cfg));
	}

	public function testWhitelistEntryIsCaseInsensitive() {
		$cfg = ['whitelist' => ['Users#AccessDenied']];
		$this->assertTrue($this->invokeIsWhitelisted('users', 'accessdenied', $cfg));
	}

	public function testWhitelistMissingListReturnsFalse() {
		$this->assertFalse($this->invokeIsWhitelisted('users', 'index', []));
	}

	public function testWhitelistSplitsOnlyOnFirstHash() {
		$cfg = ['whitelist' => ['api#v1#status']];
		$this->assertTrue($this->invokeIsWhitelisted('api', 'v1#status', $cfg));
		$this->assertFalse($this->invokeIsWhitelisted('api', 'v1', $cfg));
	}

	public function testWhitelistSkipsNonMatchingControllerBeforeMatch() {
		$cfg = ['whitelist' => ['other#index', 'users#index', 'users#extra']];
		$this->assertTrue($this->invokeIsWhitelisted('users', 'index', $cfg));
	}
}
