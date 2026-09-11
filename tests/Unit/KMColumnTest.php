<?php

namespace KofiMokome\WordPressTools\Tests\Unit;

use KMColumn;
use PHPUnit\Framework\TestCase;

class KMColumnTest extends TestCase {

	public function test_default_column_string(): void {
		$column = new KMColumn( 'name', [ 'VARCHAR(255)' ] );

		$this->assertSame( '`name` VARCHAR(255) NOT NULL', $column->toString() );
	}

	public function test_nullable_column(): void {
		$column = new KMColumn( 'email', [ 'VARCHAR(255)' ] );
		$column->nullable();

		$this->assertStringNotContainsString( 'NOT NULL', $column->toString() );
		$this->assertStringContainsString( 'NULL', $column->toString() );
	}

	public function test_unsigned_column(): void {
		$column = new KMColumn( 'age', [ 'INTEGER', 'SIGNED' ] );
		$column->unsigned();

		$this->assertStringContainsString( 'UNSIGNED', $column->toString() );
		$this->assertStringNotContainsString( ' SIGNED', $column->toString() );
	}

	public function test_primary_key_column(): void {
		$column = new KMColumn( 'id', [ 'BIGINT' ] );
		$column->primary();

		$this->assertStringContainsString( 'PRIMARY KEY', $column->toString() );
	}

	public function test_auto_increment_column(): void {
		$column = new KMColumn( 'id', [ 'BIGINT' ] );
		$column->autoIncrement();

		$this->assertStringContainsString( 'AUTO_INCREMENT', $column->toString() );
	}

	public function test_default_value_column(): void {
		$column = new KMColumn( 'status', [ 'VARCHAR(50)' ] );
		$column->default( 'active' );

		$this->assertStringContainsString( "DEFAULT 'active'", $column->toString() );
	}

	public function test_drop_column_string(): void {
		$column = new KMColumn( 'old_field', [], [ 'is_delete' => true ] );

		$this->assertSame( ' DROP COLUMN `old_field`', $column->toString() );
	}

	public function test_update_column_string(): void {
		$column = new KMColumn( 'new_field', [ 'VARCHAR(100)' ], [ 'is_update' => true ] );

		$this->assertSame( ' ADD `new_field` VARCHAR(100) NOT NULL', $column->toString() );
	}

	public function test_rename_column_string(): void {
		$column = new KMColumn( 'old_name', [], [ 'new_name' => 'new_name', 'is_rename' => true ] );

		$this->assertSame( ' RENAME COLUMN `old_name` TO `new_name`', $column->toString() );
	}

	public function test_get_name(): void {
		$column = new KMColumn( 'title', [ 'VARCHAR(255)' ] );

		$this->assertSame( 'title', $column->getName() );
	}
}
