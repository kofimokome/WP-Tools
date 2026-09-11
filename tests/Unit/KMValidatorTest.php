<?php

namespace KofiMokome\WordPressTools\Tests\Unit;

use Brain\Monkey\Functions;
use KMValidator;
use PHPUnit\Framework\TestCase;

class KMValidatorTest extends TestCase {

	protected function tearDown(): void {
		parent::tearDown();
		\Brain\Monkey\tearDown();
	}

	public function test_required_rule_passes_when_value_present(): void {
		Functions\expect( 'wp_send_json_error' )->never();

		$validator = KMValidator::make(
			[ 'name' => 'required' ],
			[ 'name' => 'John' ]
		);

		$result = $validator->validate();
		$this->assertIsArray( $result );
		$this->assertSame( 'John', $result['name'] );
	}

	public function test_required_rule_fails_when_value_missing(): void {
		Functions\expect( 'wp_send_json_error' )
			->once()
			->with( 'name is required', 400 );

		$validator = KMValidator::make(
			[ 'name' => 'required' ],
			[]
		);

		$result = $validator->validate();
		$this->assertFalse( $result );
	}

	public function test_required_rule_fails_when_value_empty(): void {
		Functions\expect( 'wp_send_json_error' )
			->once()
			->with( 'name is required', 400 );

		$validator = KMValidator::make(
			[ 'name' => 'required' ],
			[ 'name' => '' ]
		);

		$result = $validator->validate();
		$this->assertFalse( $result );
	}

	public function test_int_rule_passes_with_integer(): void {
		Functions\expect( 'wp_send_json_error' )->never();

		$validator = KMValidator::make(
			[ 'age' => 'int' ],
			[ 'age' => 25 ]
		);

		$result = $validator->validate();
		$this->assertIsArray( $result );
	}

	public function test_int_rule_fails_with_non_integer(): void {
		Functions\expect( 'wp_send_json_error' )
			->once()
			->with( 'age must be an integer', 400 );

		$validator = KMValidator::make(
			[ 'age' => 'int' ],
			[ 'age' => 'twenty' ]
		);

		$result = $validator->validate();
		$this->assertFalse( $result );
	}

	public function test_numeric_rule_passes_with_numeric_string(): void {
		Functions\expect( 'wp_send_json_error' )->never();

		$validator = KMValidator::make(
			[ 'price' => 'numeric' ],
			[ 'price' => '19.99' ]
		);

		$result = $validator->validate();
		$this->assertIsArray( $result );
	}

	public function test_numeric_rule_fails_with_non_numeric(): void {
		Functions\expect( 'wp_send_json_error' )
			->once()
			->with( 'price must be a numeric value', 400 );

		$validator = KMValidator::make(
			[ 'price' => 'numeric' ],
			[ 'price' => 'free' ]
		);

		$result = $validator->validate();
		$this->assertFalse( $result );
	}

	public function test_bool_rule_passes_with_boolean_string(): void {
		Functions\expect( 'wp_send_json_error' )->never();

		$validator = KMValidator::make(
			[ 'active' => 'bool' ],
			[ 'active' => 'true' ]
		);

		$result = $validator->validate();
		$this->assertIsArray( $result );
	}

	public function test_bool_rule_fails_with_invalid_value(): void {
		Functions\expect( 'wp_send_json_error' )
			->once()
			->with( 'active must be a boolean value', 400 );

		$validator = KMValidator::make(
			[ 'active' => 'bool' ],
			[ 'active' => 'yes' ]
		);

		$result = $validator->validate();
		$this->assertFalse( $result );
	}

	public function test_pdf_rule_fails_when_not_a_file(): void {
		Functions\expect( 'wp_send_json_error' )
			->once()
			->with( 'document must be a file', 400 );

		$validator = KMValidator::make(
			[ 'document' => 'pdf' ],
			[ 'document' => 'file.pdf' ]
		);

		$result = $validator->validate();
		$this->assertFalse( $result );
	}

	public function test_multiple_rules(): void {
		Functions\expect( 'wp_send_json_error' )->never();

		$validator = KMValidator::make(
			[ 'age' => 'required|int' ],
			[ 'age' => 30 ]
		);

		$result = $validator->validate();
		$this->assertIsArray( $result );
		$this->assertSame( 30, $result['age'] );
	}
}
