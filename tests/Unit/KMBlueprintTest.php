<?php

namespace KofiMokome\WordPressTools\Tests\Unit;

use KMBlueprint;
use PHPUnit\Framework\TestCase;

class KMBlueprintTest extends TestCase {

	public function test_empty_blueprint_has_no_columns(): void {
		$blueprint = new KMBlueprint();

		$this->assertEmpty( $blueprint->getColumns() );
		$this->assertFalse( $blueprint->isDropTable() );
	}

	public function test_string_column(): void {
		$blueprint = new KMBlueprint();
		$column    = $blueprint->string( 'username', 100 );

		$this->assertSame( 'username', $column->getName() );
		$this->assertCount( 1, $blueprint->getColumns() );
	}

	public function test_text_column(): void {
		$blueprint = new KMBlueprint();
		$column    = $blueprint->text( 'bio' );

		$this->assertSame( 'bio', $column->getName() );
	}

	public function test_integer_column(): void {
		$blueprint = new KMBlueprint();
		$column    = $blueprint->integer( 'count' );

		$this->assertSame( 'count', $column->getName() );
	}

	public function test_big_int_column(): void {
		$blueprint = new KMBlueprint();
		$column    = $blueprint->bigInt( 'views' );

		$this->assertSame( 'views', $column->getName() );
	}

	public function test_id_column(): void {
		$blueprint = new KMBlueprint();
		$column    = $blueprint->id();

		$this->assertSame( 'id', $column->getName() );
	}

	public function test_boolean_column(): void {
		$blueprint = new KMBlueprint();
		$column    = $blueprint->boolean( 'is_active' );

		$this->assertSame( 'is_active', $column->getName() );
	}

	public function test_date_column(): void {
		$blueprint = new KMBlueprint();
		$column    = $blueprint->date( 'birthday' );

		$this->assertSame( 'birthday', $column->getName() );
	}

	public function test_date_time_column(): void {
		$blueprint = new KMBlueprint();
		$column    = $blueprint->dateTime( 'published_at' );

		$this->assertSame( 'published_at', $column->getName() );
	}

	public function test_timestamps_adds_two_columns(): void {
		$blueprint = new KMBlueprint();
		$blueprint->timestamps();

		$columns = $blueprint->getColumns();
		$this->assertCount( 2, $columns );
		$this->assertSame( 'created_at', $columns[0]->getName() );
		$this->assertSame( 'updated_at', $columns[1]->getName() );
	}

	public function test_soft_delete_adds_deleted_column(): void {
		$blueprint = new KMBlueprint();
		$blueprint->softDelete();

		$columns = $blueprint->getColumns();
		$this->assertCount( 1, $columns );
		$this->assertSame( 'deleted', $columns[0]->getName() );
	}

	public function test_drop_column(): void {
		$blueprint = new KMBlueprint();
		$blueprint->dropColumn( 'old_field' );

		$columns = $blueprint->getColumns();
		$this->assertCount( 1, $columns );
		$this->assertStringContainsString( 'DROP COLUMN', $columns[0]->toString() );
	}

	public function test_rename_column(): void {
		$blueprint = new KMBlueprint();
		$blueprint->rename( 'old_name', 'new_name' );

		$columns = $blueprint->getColumns();
		$this->assertCount( 1, $columns );
		$this->assertStringContainsString( 'RENAME COLUMN', $columns[0]->toString() );
	}

	public function test_drop_table(): void {
		$blueprint = new KMBlueprint();
		$blueprint->drop();

		$this->assertTrue( $blueprint->isDropTable() );
	}

	public function test_has_column_returns_true_when_column_exists(): void {
		$blueprint = new KMBlueprint();
		$blueprint->string( 'username' );

		$this->assertTrue( $blueprint->hasColumn( 'username' ) );
		$this->assertFalse( $blueprint->hasColumn( 'password' ) );
	}

	public function test_to_string_combines_columns(): void {
		$blueprint = new KMBlueprint();
		$blueprint->string( 'name' );
		$blueprint->integer( 'age' );

		$string = $blueprint->toString();
		$this->assertStringContainsString( '`name`', $string );
		$this->assertStringContainsString( '`age`', $string );
	}
}
