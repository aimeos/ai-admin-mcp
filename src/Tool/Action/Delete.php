<?php

namespace Aimeos\Admin\Mcp\Tool\Action;

use Aimeos\Prisma\Schema\Schema;


abstract class Delete extends Base
{
	protected const ACTION = 'delete';


	public function description() : string
	{
		return sprintf( 'Deletes one or more Aimeos %s items.', str_replace( '/', ' ', $this->domain() ) );
	}


	public function schema() : Schema
	{
		return $this->objectSchema( [
			'ids' => Schema::array()->items( Schema::string() )->min( 1 )->unique()->required()->description( 'IDs of items to delete' ),
		] );
	}


	protected function run( array $arguments ) : array
	{
		$ids = array_map( 'strval', (array) $arguments['ids'] );
		$manager = $this->manager();
		$prefix = str_replace( '/', '.', $this->domain() );

		$filter = $manager->filter()->add( $prefix . '.id', '==', $ids )->slice( 0, count( $ids ) );
		$items = $manager->search( $filter );

		foreach( $items as $item ) {
			$this->items()->permit( $item, 'delete' );
		}

		// Only the checked items are deleted
		$ids = array_values( array_intersect( $ids, $items->keys()->map( fn( $id ) => (string) $id )->toArray() ) );
		$manager->delete( $ids );

		return ['ids' => $ids];
	}
}
