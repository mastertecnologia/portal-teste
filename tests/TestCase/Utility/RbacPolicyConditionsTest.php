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

	public function testMatchesUsernameIn() {
		$json = '{"all":[{"path":"user.username","in":["alice","bob"]}]}';
		$this->assertTrue(RbacPolicyConditions::matches($json, ['user.username' => 'alice']));
		$this->assertFalse(RbacPolicyConditions::matches($json, ['user.username' => 'carol']));
	}

	public function testMatchesLooseEqNumericIntAndString() {
		$json = '{"all":[{"path":"user.id","eq":42}]}';
		$this->assertTrue(RbacPolicyConditions::matches($json, ['user.id' => 42]));
		$this->assertTrue(RbacPolicyConditions::matches($json, ['user.id' => '42']));
	}

	public function testMatchesLooseEqBool() {
		$json = '{"all":[{"path":"user.admin","eq":true}]}';
		$this->assertTrue(RbacPolicyConditions::matches($json, ['user.admin' => true]));
		$this->assertFalse(RbacPolicyConditions::matches($json, ['user.admin' => false]));
	}

	public function testMatchesRuleWithEqIgnoresInWhenBothPresent() {
		$json = '{"all":[{"path":"user.role","eq":0,"in":[9,8,7]}]}';
		$this->assertTrue(RbacPolicyConditions::matches($json, ['user.role' => 0]));
		$this->assertFalse(RbacPolicyConditions::matches($json, ['user.role' => 9]));
	}

	public function testMatchesIn() {
		$json = '{"all":[{"path":"request.prefix","in":["admin",""]}]}';
		$this->assertTrue(RbacPolicyConditions::matches($json, ['request.prefix' => 'admin']));
		$this->assertTrue(RbacPolicyConditions::matches($json, ['request.prefix' => '']));
		$this->assertFalse(RbacPolicyConditions::matches($json, ['request.prefix' => 'api']));
	}

	public function testMatchesRequestPluginEq() {
		$json = '{"all":[{"path":"request.plugin","eq":""}]}';
		$this->assertTrue(RbacPolicyConditions::matches($json, ['request.plugin' => '']));
		$this->assertFalse(RbacPolicyConditions::matches($json, ['request.plugin' => 'debug_kit']));
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

	public function testMatchesIdempresaIn() {
		$json = '{"all":[{"path":"user.idempresa","in":[10,20]}]}';
		$this->assertTrue(RbacPolicyConditions::matches($json, ['user.idempresa' => 10]));
		$this->assertFalse(RbacPolicyConditions::matches($json, ['user.idempresa' => 99]));
	}

	public function testMatchesInvalidJsonReturnsFalse() {
		$this->assertFalse(RbacPolicyConditions::matches('{not valid json', []));
	}

	public function testMatchesJsonScalarRootReturnsFalse() {
		$this->assertFalse(RbacPolicyConditions::matches('"just-a-string"', []));
	}

	public function testMatchesMissingAllKeyReturnsFalse() {
		$this->assertFalse(RbacPolicyConditions::matches('{"any":[]}', []));
	}

	public function testMatchesOrEmptyNonBlankInvalidJsonDelegatesToMatches() {
		$this->assertFalse(RbacPolicyConditions::matchesOrEmpty('{broken', []));
	}

	public function testMatchesEmptyAllArrayIsVacuouslyTrue() {
		$this->assertTrue(RbacPolicyConditions::matches('{"all":[]}', []));
	}

	public function testMatchesRuleWithoutEqOrInReturnsFalse() {
		$json = '{"all":[{"path":"user.role"}]}';
		$this->assertFalse(RbacPolicyConditions::matches($json, ['user.role' => 0]));
	}

	public function testMatchesRuleNotArrayReturnsFalse() {
		$this->assertFalse(RbacPolicyConditions::matches('{"all":[1]}', []));
	}

	public function testMatchesRuleEmptyPathReturnsFalse() {
		$json = '{"all":[{"path":"","eq":0}]}';
		$this->assertFalse(RbacPolicyConditions::matches($json, ['' => 0]));
	}

	public function testMatchesInValueNotArrayReturnsFalse() {
		$json = '{"all":[{"path":"request.prefix","in":"admin"}]}';
		$this->assertFalse(RbacPolicyConditions::matches($json, ['request.prefix' => 'admin']));
	}

	public function testMatchesEqWithMissingContextPathUsesNull() {
		$json = '{"all":[{"path":"user.missing","eq":0}]}';
		$this->assertFalse(RbacPolicyConditions::matches($json, []));
	}

	public function testMatchesInEmptyListNeverMatches() {
		$json = '{"all":[{"path":"request.prefix","in":[]}]}';
		$this->assertFalse(RbacPolicyConditions::matches($json, ['request.prefix' => 'admin']));
	}
}
