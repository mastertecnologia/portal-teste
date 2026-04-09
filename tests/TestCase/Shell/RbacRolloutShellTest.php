<?php
namespace App\Test\TestCase\Shell;

use App\Shell\RbacRolloutShell;
use PHPUnit\Framework\TestCase;

class RbacRolloutShellTest extends TestCase {

	public function testPlaybookChecklistLinesNonEmpty() {
		$lines = RbacRolloutShell::playbookChecklistLines();
		$this->assertNotEmpty($lines);
		$this->assertIsArray($lines);
	}

	public function testPlaybookChecklistLinesContainsKeyCommands() {
		$blob = implode("\n", RbacRolloutShell::playbookChecklistLines());
		$this->assertStringContainsString('menu_gates_check --strict', $blob);
		$this->assertStringContainsString('unassigned_equipe --csv', $blob);
		$this->assertStringContainsString('unassigned_portal --csv', $blob);
		$this->assertStringContainsString('user_effective --user_id=N', $blob);
		$this->assertStringContainsString('enforce_readiness [--strict] [--csv]', $blob);
		$this->assertStringContainsString('pre_deploy', $blob);
		$this->assertStringContainsString('TEST_CHECKLIST_RBAC.md', $blob);
		$this->assertStringContainsString('bin/cake rbac_rollout report', $blob);
		$this->assertStringContainsString('menu_sidebar_gates', $blob);
		$this->assertStringContainsString('assign_equipe --role_slug=operacao', $blob);
		$this->assertStringContainsString('--limit=5000', $blob);
		$this->assertStringContainsString('Matriz', $blob);
		$this->assertStringContainsString('composer test-rbac', $blob);
		$this->assertStringContainsString('rbac-verify-noninteractive', $blob);
		$this->assertStringContainsString('rbac_verify_noninteractive', $blob);
		$this->assertStringContainsString('rbac-http', $blob);
	}
}
