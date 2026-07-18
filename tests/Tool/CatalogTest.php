<?php

namespace Aimeos\Admin\Mcp\Tests\Tool;

use Aimeos\Admin\Mcp\Tool;
use Aimeos\Admin\Mcp\Tools;


class CatalogTest extends \PHPUnit\Framework\TestCase
{
	public function testClasses() : void
	{
		$classes = Tools::classes();

		$this->assertCount( 116, $classes );
		$this->assertCount( count( $classes ), array_unique( $classes ) );

		foreach( $classes as $class ) {
			$this->assertTrue( is_subclass_of( $class, Tool::class ), $class );
		}
	}


	public function testCreate() : void
	{
		$tools = Tools::create( \TestHelper::context() );

		$this->assertCount( 116, $tools );
		$this->assertArrayHasKey( 'product-search', $tools );
		$this->assertArrayHasKey( 'catalog-tree', $tools );
		$this->assertArrayHasKey( 'coupon-code-save', $tools );
		$this->assertArrayHasKey( 'locale-language-search', $tools );
		$this->assertArrayHasKey( 'locale-currency-save', $tools );
		$this->assertArrayHasKey( 'locale-site-move', $tools );
		$this->assertArrayNotHasKey( 'product-type-search', $tools );

		foreach( $tools as $name => $tool )
		{
			$this->assertSame( $name, $tool->name() );
			$this->assertMatchesRegularExpression( '/^[a-z]+(?:-[a-z]+)*$/', $name );
			$this->assertNotSame( '', $tool->description() );
			$this->assertNotEmpty( $tool->schema()->toArray(), $name );
			$this->assertSame( ['readOnlyHint', 'destructiveHint', 'idempotentHint', 'openWorldHint'],
				array_keys( $tool->annotations() ), $name );
		}
	}
}
