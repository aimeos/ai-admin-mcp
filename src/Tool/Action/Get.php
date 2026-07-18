<?php

namespace Aimeos\Admin\Mcp\Tool\Action;

use Aimeos\Prisma\Schema\Schema;


abstract class Get extends Base
{
	protected const ACTION = 'get';


	public function description() : string
	{
		return sprintf( 'Returns one Aimeos %s item by ID.', str_replace( '/', ' ', $this->domain() ) );
	}


	public function schema() : Schema
	{
		return $this->objectSchema( [
			'id' => Schema::string()->required()->description( 'Unique item ID' ),
			'include' => Schema::array()->items( Schema::string() )->default( [] )->description( 'Related domains to load' ),
		] );
	}


	protected function run( array $arguments ) : array
	{
		$item = $this->manager()->get( (string) $arguments['id'], (array) ( $arguments['include'] ?? [] ) );

		return ['item' => $this->items()->toArray( $this->filterItem( $item ), $this->resultDomain() )];
	}
}
