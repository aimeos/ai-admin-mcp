<?php

namespace Aimeos\Admin\Mcp\Tests\Tool;

use Aimeos\Admin\Mcp\Tool\Attribute\Find;
use Aimeos\Admin\Mcp\Tool\Product\Save;
use Aimeos\Admin\Mcp\Tool\Product\Search;
use Aimeos\Prisma\Schema\Schema;


class SchemaTest extends \PHPUnit\Framework\TestCase
{
	public function testSearchSchema() : void
	{
		$schema = ( new Search( \TestHelper::context() ) )->schema();

		$this->assertInstanceOf( Schema::class, $schema );
		$this->assertSame( [], $schema->validate( [] ) );
		$this->assertNotEmpty( $schema->validate( ['unknown' => true] ) );
		$this->assertNotEmpty( $schema->validate( ['limit' => 0] ) );
		$this->assertSame( [], $schema->validate( [
			'filter' => ['==' => ['product.code' => 'CNC']],
			'include' => ['stock'],
			'limit' => 10,
		] ) );
	}


	public function testDynamicSaveSchema() : void
	{
		$schema = ( new Save( \TestHelper::context() ) )->schema()->toArray();
		$item = $schema['properties']['items']['items']['properties'] ?? [];

		$this->assertArrayHasKey( 'code', $item );
		$this->assertArrayHasKey( 'stock', $item );
		$this->assertFalse( $schema['additionalProperties'] ?? true );
	}


	public function testAttributeFindSchema() : void
	{
		$schema = ( new Find( \TestHelper::context() ) )->schema();

		$this->assertNotEmpty( $schema->validate( ['code' => 'xs'] ) );
		$this->assertSame( [], $schema->validate( ['code' => 'xs', 'domain' => 'product', 'type' => 'size'] ) );
	}
}
