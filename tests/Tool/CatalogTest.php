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


	public function testCreatePolicyDefault() : void
	{
		$context = \TestHelper::context()->setPolicy( null );
		$tool = Tools::create( $context )['product-search'];

		// the shared context must not be changed, only the copy used by the tools
		$copy = ( new \ReflectionProperty( Tool::class, 'context' ) )->getValue( $tool );
		$this->assertInstanceOf( \Aimeos\MShop\Policy\Actions::class, $copy->policy() );
		$this->assertNull( $context->policy() );
	}


	public function testCreatePolicyCustom() : void
	{
		$context = \TestHelper::context()->setPolicy( new class implements \Aimeos\MShop\PolicyIface {
			public function allowed( \Aimeos\MShop\ContextIface $context, string $resource, string $action ) : ?bool {
				return false;
			}
		} );
		$tool = Tools::create( $context )['product-search'];

		$this->assertSame( $context->policy(), ( new \ReflectionProperty( Tool::class, 'context' ) )->getValue( $tool )->policy() );
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
