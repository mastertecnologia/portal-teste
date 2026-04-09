<?php
namespace App\Test\TestCase\Controller\Component;

use App\Controller\Component\RbacComponent;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * `_isRbacEnforcedApiAction` — só config + strings; reflexão + mock sem construtor.
 */
class RbacComponentApiEnforcedTest extends TestCase {

	private function invokeIsRbacEnforcedApi(string $controllerLower, string $actionLower, array $cfg): bool {
		$stub = $this->getMockBuilder(RbacComponent::class)
			->disableOriginalConstructor()
			->getMock();
		$m = new ReflectionMethod(RbacComponent::class, '_isRbacEnforcedApiAction');
		$m->setAccessible(true);

		return $m->invoke($stub, $controllerLower, $actionLower, $cfg);
	}

	public function testReturnsFalseWhenListMissingOrNotArray() {
		$this->assertFalse($this->invokeIsRbacEnforcedApi('tickets', 'apiindex', []));
		$this->assertFalse($this->invokeIsRbacEnforcedApi('tickets', 'apiindex', [
			'rbac_api_enforced_actions' => 'not-array',
		]));
	}

	public function testReturnsTrueWhenControllerActionKeyMatches() {
		$cfg = ['rbac_api_enforced_actions' => ['tickets#apifoo', 'other#x']];
		$this->assertTrue($this->invokeIsRbacEnforcedApi('tickets', 'apifoo', $cfg));
		$this->assertFalse($this->invokeIsRbacEnforcedApi('tickets', 'apibar', $cfg));
	}

	public function testListEntriesAreTrimmedAndLowercased() {
		$cfg = ['rbac_api_enforced_actions' => [' Tickets#ApiFoo ']];
		$this->assertTrue($this->invokeIsRbacEnforcedApi('tickets', 'apifoo', $cfg));
	}

	public function testBlankListEntriesNeverMatch() {
		$cfg = ['rbac_api_enforced_actions' => ['', '  ', "\t"]];
		$this->assertFalse($this->invokeIsRbacEnforcedApi('tickets', 'apiindex', $cfg));
	}
}
