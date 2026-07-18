<?php

namespace Aimeos\Admin\Mcp\Tool\Action;

use Aimeos\Prisma\Schema\Schema;


abstract class Path extends Base
{
	protected const ACTION = 'path';
	protected const PERMISSION = 'get';


	public function description() : string
	{
		return sprintf( 'Returns the path from an Aimeos %s node to the root.', str_replace( '/', ' ', $this->domain() ) );
	}


	public function schema() : Schema
	{
		return $this->objectSchema( [
			'id' => Schema::string()->required()->description( 'Node ID' ),
			'include' => Schema::array()->items( Schema::string() )->default( [] )->description( 'Related domains to load' ),
		] );
	}


	protected function run( array $arguments ) : array
	{
		$items = $this->filterItems( $this->manager()->getPath( // @phpstan-ignore method.notFound
			(string) $arguments['id'],
			(array) ( $arguments['include'] ?? [] )
		) );
		$result = [];

		foreach( $items as $item ) {
			$result[] = $this->items()->toArray( $item, $this->resultDomain() );
		}

		return ['items' => $result];
	}
}
