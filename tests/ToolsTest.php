<?php

namespace Aimeos\Admin\Mcp\Tests;


class ToolsTest extends \PHPUnit\Framework\TestCase
{
	public function testForbidden() : void
	{
		$previous = new \Aimeos\MShop\PolicyException( 'test' );
		$e = \Aimeos\Admin\Mcp\Tools::forbidden( 'test', $previous );

		$this->assertInstanceOf( \Aimeos\Admin\Mcp\Exception::class, $e );
		$this->assertSame( $previous, $e->getPrevious() );
		$this->assertEquals( 403, $e->getCode() );
	}


	public function testPolicy() : void
	{
		// the default policy uses the user groups configured for the interface
		$context = \TestHelper::context();
		$context->config()->set( 'admin/mcp/resource/product/save', ['editor'] );
		$context->config()->set( 'admin/mcp/resource/service/save', ['admin'] );

		$view = new \Aimeos\Base\View\Standard();
		$view->addHelper( 'access', new \Aimeos\Base\View\Helper\Access\Standard( $view, ['editor'] ) );
		$context->setView( $view );

		$policy = \Aimeos\Admin\Mcp\Tools::policy();

		$this->assertTrue( $policy->allowed( $context, 'product', 'save' ) );
		$this->assertFalse( $policy->allowed( $context, 'service', 'save' ) );
		$this->assertNull( $policy->allowed( $context, 'unknown', 'save' ) );
	}
}
