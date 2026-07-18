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
		$this->manager()->delete( $ids );

		return ['ids' => $ids];
	}
}
