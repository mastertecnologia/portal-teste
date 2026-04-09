<?php
namespace App\Test\TestCase\Utility;

use App\Utility\AbacQuery;
use Cake\Core\Configure;
use Cake\ORM\Query;
use PHPUnit\Framework\TestCase;

class AbacQueryTest extends TestCase {

	protected function tearDown(): void {
		Configure::delete('Abac');
		parent::tearDown();
	}

	public function testResolveScopePortalClienteColumn() {
		$user = ['role' => 1];
		$map = ['alias' => 'T', 'cliente_column' => 'idcliente', 'empresa_column' => 'idempresa'];
		$this->assertSame('cliente', AbacQuery::resolveScope($user, new \stdClass(), $map));
	}

	public function testResolveScopeStringRoleOneTreatedAsPortal() {
		$user = ['role' => '1'];
		$map = ['alias' => 'T', 'cliente_column' => 'idcliente', 'empresa_column' => 'idempresa'];
		$this->assertSame('cliente', AbacQuery::resolveScope($user, new \stdClass(), $map));
	}

	public function testResolveScopePortalClienteRowId() {
		$user = ['role' => 1];
		$map = ['cliente_row_id' => true, 'empresa_column' => 'idempresa'];
		$this->assertSame('cliente', AbacQuery::resolveScope($user, new \stdClass(), $map));
	}

	public function testResolveScopePortalEmpresaOnly() {
		$user = ['role' => 1];
		$map = ['empresa_column' => 'idempresa'];
		$this->assertSame('empresa', AbacQuery::resolveScope($user, new \stdClass(), $map));
	}

	public function testResolveScopeEquipeFromRbacOnController() {
		$user = ['role' => 0];
		$map = ['empresa_column' => 'idempresa'];
		$c = new \stdClass();
		$c->rbacAbacScope = 'cliente';
		$this->assertSame('cliente', AbacQuery::resolveScope($user, $c, $map));
	}

	public function testResolveScopeEquipeDefaultsEmpresa() {
		$user = ['role' => 0];
		$map = ['empresa_column' => 'idempresa'];
		$this->assertSame('empresa', AbacQuery::resolveScope($user, new \stdClass(), $map));
	}

	public function testResolveScopePortalNoIsolationColumnsReturnsNull() {
		$user = ['role' => 1];
		$map = ['alias' => 'X'];
		$this->assertNull(AbacQuery::resolveScope($user, new \stdClass(), $map));
	}

	public function testResolveScopeEquipeUnknownRbacAbacScopeFallsBackToEmpresaColumn() {
		$user = ['role' => 0];
		$map = ['empresa_column' => 'idempresa'];
		$c = new \stdClass();
		$c->rbacAbacScope = 'invalid';
		$this->assertSame('empresa', AbacQuery::resolveScope($user, $c, $map));
	}

	public function testResolveScopeEquipeWhitespaceRbacAbacScopeIgnored() {
		$user = ['role' => 0];
		$map = ['empresa_column' => 'idempresa'];
		$c = new \stdClass();
		$c->rbacAbacScope = '   ';
		$this->assertSame('empresa', AbacQuery::resolveScope($user, $c, $map));
	}

	public function testResolveScopeEquipeWithoutEmpresaColumnAndNoRbacScopeReturnsNull() {
		$user = ['role' => 0];
		$map = ['alias' => 'OnlyAlias'];
		$this->assertNull(AbacQuery::resolveScope($user, new \stdClass(), $map));
	}

	public function testResolveScopeEquipeOwnFromControllerWithoutEmpresaColumn() {
		$user = ['role' => 0];
		$map = ['alias' => 'Profile'];
		$c = new \stdClass();
		$c->rbacAbacScope = 'own';
		$this->assertSame('own', AbacQuery::resolveScope($user, $c, $map));
	}

	public function testApplyAbacDisabledDoesNotCallWhere() {
		Configure::write('Abac', ['enabled' => false, 'tables' => []]);
		$query = $this->createMock(Query::class);
		$query->expects($this->never())->method('where');
		$r = AbacQuery::apply($query, ['id' => 1], new \stdClass(), 'Tickets');
		$this->assertSame($query, $r);
	}

	public function testApplyEnabledIntegerZeroTreatedAsDisabled() {
		Configure::write('Abac', [
			'enabled' => 0,
			'tables' => [
				'Tickets' => ['alias' => 'Tickets', 'empresa_column' => 'idempresa'],
			],
		]);
		$query = $this->createMock(Query::class);
		$query->expects($this->never())->method('where');
		AbacQuery::apply($query, ['role' => 0, 'idempresa' => 1], new \stdClass(), 'Tickets');
	}

	public function testApplyUnknownTableKeyDoesNotCallWhere() {
		Configure::write('Abac', [
			'enabled' => true,
			'tables' => [
				'Tickets' => ['alias' => 'Tickets', 'empresa_column' => 'idempresa'],
			],
		]);
		$query = $this->createMock(Query::class);
		$query->expects($this->never())->method('where');
		$r = AbacQuery::apply($query, ['role' => 0, 'idempresa' => 5], new \stdClass(), 'MissingTable');
		$this->assertSame($query, $r);
	}

	public function testApplyTableEntryNotArrayDoesNotCallWhere() {
		Configure::write('Abac', [
			'enabled' => true,
			'tables' => [
				'Broken' => 'not-an-array',
			],
		]);
		$query = $this->createMock(Query::class);
		$query->expects($this->never())->method('where');
		$r = AbacQuery::apply($query, ['role' => 0, 'idempresa' => 1], new \stdClass(), 'Broken');
		$this->assertSame($query, $r);
	}

	public function testApplyEmpresaScopeAddsWhere() {
		Configure::write('Abac', [
			'enabled' => true,
			'tables' => [
				'Tickets' => ['alias' => 'Tickets', 'empresa_column' => 'idempresa'],
			],
		]);
		$query = $this->createMock(Query::class);
		$query->expects($this->once())->method('where')->with(['Tickets.idempresa' => 7])->willReturnSelf();
		AbacQuery::apply($query, ['role' => 0, 'idempresa' => 7], new \stdClass(), 'Tickets');
	}

	public function testApplyUsesExplicitAliasParameter() {
		Configure::write('Abac', [
			'enabled' => true,
			'tables' => [
				'Tickets' => ['alias' => 'Tickets', 'empresa_column' => 'idempresa'],
			],
		]);
		$query = $this->createMock(Query::class);
		$query->expects($this->once())->method('where')->with(['T.idempresa' => 7])->willReturnSelf();
		AbacQuery::apply($query, ['role' => 0, 'idempresa' => 7], new \stdClass(), 'Tickets', 'T');
	}

	public function testApplyEmptyStringAliasParameterUsesMapAlias() {
		Configure::write('Abac', [
			'enabled' => true,
			'tables' => [
				'Tickets' => ['alias' => 'Tk', 'empresa_column' => 'idempresa'],
			],
		]);
		$query = $this->createMock(Query::class);
		$query->expects($this->once())->method('where')->with(['Tk.idempresa' => 3])->willReturnSelf();
		AbacQuery::apply($query, ['role' => 0, 'idempresa' => 3], new \stdClass(), 'Tickets', '');
	}

	public function testApplyAliasDefaultsToTableKeyWhenOmittedInMap() {
		Configure::write('Abac', [
			'enabled' => true,
			'tables' => [
				'NoAliasKey' => ['empresa_column' => 'idempresa'],
			],
		]);
		$query = $this->createMock(Query::class);
		$query->expects($this->once())->method('where')->with(['NoAliasKey.idempresa' => 4])->willReturnSelf();
		AbacQuery::apply($query, ['role' => 0, 'idempresa' => 4], new \stdClass(), 'NoAliasKey');
	}

	public function testApplyEmpresaScopeWithoutColumnDoesNotAddWhere() {
		Configure::write('Abac', [
			'enabled' => true,
			'tables' => [
				'Loose' => ['alias' => 'Loose'],
			],
		]);
		$c = new \stdClass();
		$c->rbacAbacScope = 'empresa';
		$query = $this->createMock(Query::class);
		$query->expects($this->never())->method('where');
		AbacQuery::apply($query, ['role' => 0, 'idempresa' => 9], $c, 'Loose');
	}

	public function testApplyClienteScopeWithoutColumnDoesNotAddWhere() {
		Configure::write('Abac', [
			'enabled' => true,
			'tables' => [
				'LooseCliente' => ['alias' => 'LC', 'empresa_column' => 'idempresa'],
			],
		]);
		$c = new \stdClass();
		$c->rbacAbacScope = 'cliente';
		$query = $this->createMock(Query::class);
		$query->expects($this->never())->method('where');
		AbacQuery::apply($query, ['role' => 0, 'idcliente' => 1], $c, 'LooseCliente');
	}

	public function testApplyResolveScopeNullDoesNotCallWhere() {
		Configure::write('Abac', [
			'enabled' => true,
			'tables' => [
				'Bare' => ['alias' => 'Bare'],
			],
		]);
		$query = $this->createMock(Query::class);
		$query->expects($this->never())->method('where');
		AbacQuery::apply($query, ['role' => 1], new \stdClass(), 'Bare');
	}

	public function testApplyEmpresaMissingIdEmpresaUsesImpossibleWhere() {
		Configure::write('Abac', [
			'enabled' => true,
			'tables' => [
				'Tickets' => ['alias' => 'Tickets', 'empresa_column' => 'idempresa'],
			],
		]);
		$query = $this->createMock(Query::class);
		$query->expects($this->once())->method('where')->with('1=0')->willReturnSelf();
		AbacQuery::apply($query, ['role' => 0, 'idempresa' => null], new \stdClass(), 'Tickets');
	}

	public function testApplyEmpresaBlankStringIdEmpresaUsesImpossibleWhere() {
		Configure::write('Abac', [
			'enabled' => true,
			'tables' => [
				'Tickets' => ['alias' => 'Tickets', 'empresa_column' => 'idempresa'],
			],
		]);
		$query = $this->createMock(Query::class);
		$query->expects($this->once())->method('where')->with('1=0')->willReturnSelf();
		AbacQuery::apply($query, ['role' => 0, 'idempresa' => ''], new \stdClass(), 'Tickets');
	}

	public function testApplyClienteColumnAddsWhere() {
		Configure::write('Abac', [
			'enabled' => true,
			'tables' => [
				'Tickets' => [
					'alias' => 'Tickets',
					'cliente_column' => 'idcliente',
					'empresa_column' => 'idempresa',
				],
			],
		]);
		$query = $this->createMock(Query::class);
		$query->expects($this->once())->method('where')->with(['Tickets.idcliente' => 55])->willReturnSelf();
		AbacQuery::apply($query, ['role' => 1, 'idcliente' => 55], new \stdClass(), 'Tickets');
	}

	public function testApplyClienteMissingIdClienteUsesImpossibleWhere() {
		Configure::write('Abac', [
			'enabled' => true,
			'tables' => [
				'Tickets' => ['alias' => 'Tickets', 'cliente_column' => 'idcliente', 'empresa_column' => 'idempresa'],
			],
		]);
		$query = $this->createMock(Query::class);
		$query->expects($this->once())->method('where')->with('1=0')->willReturnSelf();
		AbacQuery::apply($query, ['role' => 1, 'idcliente' => null], new \stdClass(), 'Tickets');
	}

	public function testApplyClienteBlankStringIdClienteUsesImpossibleWhere() {
		Configure::write('Abac', [
			'enabled' => true,
			'tables' => [
				'Tickets' => ['alias' => 'Tickets', 'cliente_column' => 'idcliente', 'empresa_column' => 'idempresa'],
			],
		]);
		$query = $this->createMock(Query::class);
		$query->expects($this->once())->method('where')->with('1=0')->willReturnSelf();
		AbacQuery::apply($query, ['role' => 1, 'idcliente' => ''], new \stdClass(), 'Tickets');
	}

	public function testApplyClienteRowIdUsesPrimaryKey() {
		Configure::write('Abac', [
			'enabled' => true,
			'tables' => [
				'Clientes' => [
					'alias' => 'Clientes',
					'cliente_row_id' => true,
					'empresa_column' => 'idempresa',
				],
			],
		]);
		$query = $this->createMock(Query::class);
		$query->expects($this->once())->method('where')->with(['Clientes.id' => 3])->willReturnSelf();
		AbacQuery::apply($query, ['role' => 1, 'idcliente' => 3], new \stdClass(), 'Clientes');
	}

	public function testApplyOwnScopeAddsWhere() {
		Configure::write('Abac', [
			'enabled' => true,
			'tables' => [
				'Users' => ['alias' => 'Users', 'empresa_column' => 'idempresa', 'user_id_column' => 'id'],
			],
		]);
		$c = new \stdClass();
		$c->rbacAbacScope = 'own';
		$query = $this->createMock(Query::class);
		$query->expects($this->once())->method('where')->with(['Users.id' => 42])->willReturnSelf();
		AbacQuery::apply($query, ['role' => 0, 'id' => 42], $c, 'Users');
	}

	public function testApplyOwnMissingUserIdUsesImpossibleWhere() {
		Configure::write('Abac', [
			'enabled' => true,
			'tables' => [
				'Users' => ['alias' => 'Users', 'empresa_column' => 'idempresa', 'user_id_column' => 'id'],
			],
		]);
		$c = new \stdClass();
		$c->rbacAbacScope = 'own';
		$query = $this->createMock(Query::class);
		$query->expects($this->once())->method('where')->with('1=0')->willReturnSelf();
		AbacQuery::apply($query, ['role' => 0, 'id' => 0], $c, 'Users');
	}

	public function testApplyOwnUsesDefaultUserIdColumnWhenOmitted() {
		Configure::write('Abac', [
			'enabled' => true,
			'tables' => [
				'Profile' => ['alias' => 'Profile', 'empresa_column' => 'idempresa'],
			],
		]);
		$c = new \stdClass();
		$c->rbacAbacScope = 'own';
		$query = $this->createMock(Query::class);
		$query->expects($this->once())->method('where')->with(['Profile.id' => 7])->willReturnSelf();
		AbacQuery::apply($query, ['role' => 0, 'id' => 7], $c, 'Profile');
	}
}
