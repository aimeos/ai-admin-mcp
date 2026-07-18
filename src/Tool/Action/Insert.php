<?php

namespace Aimeos\Admin\Mcp\Tool\Action;

use Aimeos\Prisma\Schema\Schema;


abstract class Insert extends Base
{
	protected const ACTION = 'insert';
	protected const PERMISSION = 'save';


	public function description() : string
	{
		return sprintf( 'Inserts a new Aimeos %s node into its tree.', str_replace( '/', ' ', $this->domain() ) );
	}


	public function schema() : Schema
	{
		return $this->objectSchema( [
			'item' => $this->schemas()->item( $this->domain() )->required()->description( 'New node values' ),
			'parentId' => Schema::string()->description( 'Parent node ID' ),
			'refId' => Schema::string()->description( 'Sibling node ID to insert before' ),
		] );
	}


	protected function run( array $arguments ) : array
	{
		$manager = $this->manager();
		$item = $this->items()->update( $manager, $manager->create(), (array) $arguments['item'], $this->domain() );
		$item = $manager->insert( // @phpstan-ignore method.notFound
			$item,
			isset( $arguments['parentId'] ) ? (string) $arguments['parentId'] : null,
			isset( $arguments['refId'] ) ? (string) $arguments['refId'] : null
		);

		return ['item' => $this->items()->toArray( $item, $this->resultDomain() )];
	}
}
