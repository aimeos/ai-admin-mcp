<?php

namespace Aimeos\Admin\Mcp\Tests\Tool;

use Aimeos\Admin\Mcp\Exception;
use Aimeos\Admin\Mcp\Tool\Catalog\SearchTree;
use Aimeos\Admin\Mcp\Tool\Catalog\Tree;
use Aimeos\Admin\Mcp\Tool\Customer\Aggregate;
use Aimeos\Admin\Mcp\Tool\Product\Delete;
use Aimeos\Admin\Mcp\Tool\Product\Find;
use Aimeos\Admin\Mcp\Tool\Product\Save;
use Aimeos\Admin\Mcp\Tool\Product\Search;


class ExecutionTest extends \PHPUnit\Framework\TestCase
{
	private \Aimeos\MShop\ContextIface $context;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );
		$this->context = \TestHelper::context();
	}


	public function testInvalidArguments() : void
	{
		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );

		( new Find( $this->context ) )->execute( [] );
	}


	public function testForbidden() : void
	{
		$view = new \Aimeos\Base\View\Standard();
		$view->addHelper( 'access', new \Aimeos\Base\View\Helper\Access\Standard( $view, [] ) );
		$this->context->setView( $view );

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 403 );

		( new Find( $this->context ) )->execute( ['code' => 'CNC'] );
	}


	public function testFindAndSearchProduct() : void
	{
		$found = ( new Find( $this->context ) )->execute( ['code' => 'CNC', 'include' => ['stock']] );
		$searched = ( new Search( $this->context ) )->execute( [
			'filter' => ['==' => ['product.code' => 'CNC']],
			'include' => ['stock'],
		] );

		$this->assertSame( 'CNC', $found['item']['code'] );
		$this->assertSame( 'CNC', $searched['items'][0]['code'] );
		$this->assertSame( 1, $searched['total'] );
		$this->assertSame( 'default', $searched['items'][0]['stock'][0]['type'] );
	}


	public function testCatalogTree() : void
	{
		$result = ( new Tree( $this->context ) )->execute( ['level' => 2] );

		$this->assertSame( 'root', $result['item']['code'] );
		$this->assertNotEmpty( $result['item']['children'] );
	}


	public function testSearchCatalogTree() : void
	{
		$result = ( new SearchTree( $this->context ) )->execute( ['filter' => ['||' => [
			['~=' => ['catalog.code' => 'c']],
			['==' => ['catalog.code' => 'new']],
			['==' => ['catalog.code' => 'internet']],
			['==' => ['catalog.code' => 'root']],
		]]] );

		$this->assertCount( 1, $result['items'] );
		$this->assertSame( 'root', $result['items'][0]['code'] );
		$this->assertSame( ['categories', 'group'], array_column( $result['items'][0]['children'], 'code' ) );
	}


	public function testAggregateCustomers() : void
	{
		$result = ( new Aggregate( $this->context ) )->execute( ['key' => ['customer.status']] );

		$this->assertNotEmpty( $result['values'] );
	}


	public function testSaveAndDeleteProduct() : void
	{
		$code = 'test-mcp-' . bin2hex( random_bytes( 4 ) );
		$result = ( new Save( $this->context ) )->execute( ['items' => [[
			'code' => $code,
			'label' => 'MCP test product',
			'type' => 'default',
			'status' => 0,
			'lists' => ['text' => [[
				'type' => 'default',
				'item' => [
					'type' => 'name',
					'languageid' => 'de',
					'content' => 'MCP test product',
				],
			]]],
		]]] );
		$id = (string) ( $result['items'][0]['id'] ?? '' );

		try {
			$this->assertNotSame( '', $id );
			$this->assertSame( $code, $result['items'][0]['code'] );
			$this->assertSame( 'MCP test product', $result['items'][0]['lists']['text'][0]['item']['content'] );
		} finally {
			if( $id !== '' ) {
				$deleted = ( new Delete( $this->context ) )->execute( ['ids' => [$id]] );
				$this->assertSame( [$id], $deleted['ids'] );
			}
		}
	}
}
