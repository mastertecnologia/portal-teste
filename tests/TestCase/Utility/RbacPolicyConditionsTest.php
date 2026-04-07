<?php
namespace App\Test\TestCase\Utility;

use App\Utility\RbacPolicyConditions;
use PHPUnit\Framework\TestCase;

class RbacPolicyConditionsTest extends TestCase {

	public function testMatchesOrEmptyNullAndBlank() {
		$this->assertTrue(RbacPolicyConditions::matchesOrEmpty(null, []));
		$this->assertTrue(RbacPolicyConditions::matchesOrEmpty('   ', []));
	}

	public function testMatchesEq() {
		$json = '{"all":[{"path":"user.role","eq":0}]}';
		$this->assertTrue(RbacPolicyConditions::matches($json, ['user.role' => 0]));
		$this->assertFalse(RbacPolicyConditions::matches($json, ['user.role' => 1]));
	}

	public function testMatchesIn() {
		$json = '{"all":[{"path":"request.prefix","in":["admin",""]}]}';
		$this->assertTrue(RbacPolicyConditions::matches($json, ['request.prefix' => 'admin']));
		$this->assertTrue(RbacPolicyConditions::matches($json, ['request.prefix' => '']));
		$this->assertFalse(RbacPolicyConditions::matches($json, ['request.prefix' => 'api']));
	}

	public function testMatchesAllConjunction() {
		$json = '{"all":[{"path":"user.role","eq":0},{"path":"user.admin","eq":true}]}';
		$this->assertTrue(RbacPolicyConditions::matches($json, [
			'user.role' => 0,
			'user.admin' => true,
		]));
		$this->assertFalse(RbacPolicyConditions::matches($json, [
			'user.role' => 0,
			'user.admin' => false,
		]));
	}
}
