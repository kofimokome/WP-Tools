<?php

namespace KofiMokome\WordPressTools\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Plural;

class PluralTest extends TestCase {

	public function test_pluralize_regular_nouns(): void {
		Plural::setLanguage( 'en' );

		$this->assertSame( 'cats', Plural::pluralize( 'cat' ) );
		$this->assertSame( 'dogs', Plural::pluralize( 'dog' ) );
		$this->assertSame( 'cars', Plural::pluralize( 'car' ) );
	}

	public function test_pluralize_irregular_nouns(): void {
		Plural::setLanguage( 'en' );

		$this->assertSame( 'people', Plural::pluralize( 'person' ) );
		$this->assertSame( 'children', Plural::pluralize( 'child' ) );
		$this->assertSame( 'men', Plural::pluralize( 'man' ) );
	}

	public function test_pluralize_unchanged_when_language_not_loaded(): void {
		Plural::setLanguage( 'xx' );

		$this->assertSame( 'unknown', Plural::pluralize( 'unknown' ) );
	}

	public function test_global_plural_helper(): void {
		Plural::setLanguage( 'en' );

		$this->assertSame( 'birds', plural( 'bird' ) );
	}
}
