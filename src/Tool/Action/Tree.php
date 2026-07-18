<?php

namespace Aimeos\Admin\Mcp\Tool\Action;

use Aimeos\Prisma\Schema\Schema;


abstract class Tree extends Base
{
	protected const ACTION = 'tree';
	protected const PERMISSION = 'get';


	public function description() : string
	{
		return sprintf( 'Returns an Aimeos %s tree.', str_replace( '/', ' ', $this->domain() ) );
	}


	public function schema() : Schema
	{
		return $this->objectSchema( [
			'id' => Schema::string()->description( 'Root node ID; omitted for the domain root' ),
			'level' => Schema::integer()->min( 1 )->max( 3 )->default( 3 )->description( '1=node, 2=children, 3=whole subtree' ),
			'include' => Schema::array()->items( Schema::string() )->default( [] )->description( 'Related domains to load' ),
		] );
	}


	protected function run( array $arguments ) : array
	{
		$item = $this->manager()->getTree( // @phpstan-ignore method.notFound
			isset( $arguments['id'] ) ? (string) $arguments['id'] : null,
			(array) ( $arguments['include'] ?? [] ),
			(int) ( $arguments['level'] ?? 3 )
		);

		return ['item' => $this->items()->toArray( $this->filterItem( $item ), $this->resultDomain() )];
	}
}
