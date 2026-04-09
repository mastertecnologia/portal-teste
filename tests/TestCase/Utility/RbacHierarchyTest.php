<?php
namespace App\Test\TestCase\Utility;

use App\Utility\RbacHierarchy;
use PHPUnit\Framework\TestCase;

class RbacHierarchyTest extends TestCase {

	public function testFinalizeAdminUnlimited() {
		list($final, $stripped) = RbacHierarchy::finalizeRoleIdsForSave(null, [1, 2], [10, 20], []);
		$this->assertSame([10, 20], $final);
		$this->assertSame([], $stripped);
	}

	public function testFinalizePreservesHigherExisting() {
		$map = [99 => 100, 1 => 0];
		list($final, $stripped) = RbacHierarchy::finalizeRoleIdsForSave(5, [99], [1], $map);
		$this->assertEqualsCanonicalizing([99, 1], $final);
		$this->assertSame([], $stripped);
	}

	public function testFinalizeStripsRequestAboveCap() {
		$map = [1 => 0, 2 => 100];
		list($final, $stripped) = RbacHierarchy::finalizeRoleIdsForSave(5, [], [1, 2], $map);
		$this->assertSame([1], $final);
		$this->assertSame([2], $stripped);
	}

	public function testRolesVisibleForAssignEmptyRolesReturnsEmpty() {
		$this->assertSame([], RbacHierarchy::rolesVisibleForAssign(100, [], []));
	}

	public function testRolesVisibleForAssign() {
		$a = (object)['id' => 1, 'hierarchy_level' => 0];
		$b = (object)['id' => 2, 'hierarchy_level' => 100];
		$cap = 5;
		$visible = RbacHierarchy::rolesVisibleForAssign($cap, [2], [$a, $b]);
		$this->assertCount(2, $visible);
		$capNull = RbacHierarchy::rolesVisibleForAssign(null, [], [$a, $b]);
		$this->assertCount(2, $capNull);
	}

	public function testRolesVisibleForAssignCapExcludesHighUnlessAlreadyOnTarget() {
		$low = (object)['id' => 1, 'hierarchy_level' => 10];
		$high = (object)['id' => 2, 'hierarchy_level' => 9000];
		$visible = RbacHierarchy::rolesVisibleForAssign(100, [], [$low, $high]);
		$this->assertCount(1, $visible);
		$this->assertSame(1, (int)$visible[0]->id);
	}

	public function testFinalizePreservesHighExistingWhenRequestedEmpty() {
		$map = [99 => 10000, 1 => 100];
		list($final, $stripped) = RbacHierarchy::finalizeRoleIdsForSave(5000, [99], [], $map);
		$this->assertSame([99], $final);
		$this->assertSame([], $stripped);
	}

	public function testFinalizeIgnoresZeroAndNegativeInInputs() {
		$map = [1 => 0, 2 => 0];
		list($final, $stripped) = RbacHierarchy::finalizeRoleIdsForSave(10, [-1, 0, 0], [-5, 0, 1, 2], $map);
		$this->assertEqualsCanonicalizing([1, 2], $final);
		$this->assertSame([], $stripped);
	}

	public function testFinalizeDedupesMergedRoleIds() {
		$map = [1 => 0, 2 => 0, 3 => 0];
		list($final, $stripped) = RbacHierarchy::finalizeRoleIdsForSave(10, [1, 1], [2, 2, 3], $map);
		$this->assertEqualsCanonicalizing([1, 2, 3], $final);
		$this->assertSame([], $stripped);
	}

	public function testRolesVisibleForAssignCapZeroShowsOnlyLevelZeroUnlessExisting() {
		$z = (object)['id' => 1, 'hierarchy_level' => 0];
		$mid = (object)['id' => 2, 'hierarchy_level' => 100];
		$visible = RbacHierarchy::rolesVisibleForAssign(0, [], [$z, $mid]);
		$this->assertCount(1, $visible);
		$this->assertSame(1, (int)$visible[0]->id);
		$withExisting = RbacHierarchy::rolesVisibleForAssign(0, [2], [$z, $mid]);
		$this->assertCount(2, $withExisting);
	}

	public function testFinalizeCapZeroStripsPositiveLevelsFromRequest() {
		$map = [1 => 0, 2 => 500];
		list($final, $stripped) = RbacHierarchy::finalizeRoleIdsForSave(0, [], [1, 2], $map);
		$this->assertSame([1], $final);
		$this->assertSame([2], $stripped);
	}

	public function testFinalizeCapZeroPreservesExistingAboveCap() {
		$map = [9 => 100, 1 => 0];
		list($final, $stripped) = RbacHierarchy::finalizeRoleIdsForSave(0, [9], [1], $map);
		$this->assertEqualsCanonicalizing([9, 1], $final);
		$this->assertSame([], $stripped);
	}
}
