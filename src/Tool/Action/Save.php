<?php

namespace Aimeos\Admin\Mcp\Tool\Action;

use Aimeos\Prisma\Schema\Schema;


abstract class Save extends Base
{
	protected const ACTION = 'save';


	public function description() : string
	{
		return sprintf( 'Creates or updates one or more Aimeos %s items.', str_replace( '/', ' ', $this->domain() ) );
	}


	public function schema() : Schema
	{
		return $this->objectSchema( [
			'items' => Schema::array()->items( $this->schemas()->item( $this->domain() ) )->min( 1 )->required()->description( 'Items to create or update' ),
		] );
	}


	protected function run( array $arguments ) : array
	{
		$entries = (array) $arguments['items'];
		$manager = $this->manager();
		$prefix = str_replace( '/', '.', $this->domain() );
		$ids = array_values( array_filter( array_map( fn( $entry ) => (string) ( $entry['id'] ?? '' ), $entries ) ) );
		$existing = map();

		if( $ids )
		{
			$filter = $manager->filter()->add( $prefix . '.id', '==', $ids )->slice( 0, count( $ids ) );
			$existing = $manager->search( $filter, $this->references( $entries ) );
		}

		$items = [];

		foreach( $entries as $entry )
		{
			$entry = (array) $entry;
			$item = isset( $entry['id'] ) ? $existing->get( (string) $entry['id'] ) : null;
			$items[] = $this->items()->update( $manager, $item ?: $manager->create(), $entry, $this->domain() );
		}

		$saved = $manager->save( map( $items ) );
		$result = [];

		foreach( is_iterable( $saved ) ? $saved : [$saved] as $item ) {
			$result[] = $this->items()->toArray( $item, $this->resultDomain() );
		}

		return ['items' => $result];
	}


	/**
	 * @param array<int, array<string, mixed>> $entries
	 * @return array<int, string>
	 */
	private function references( array $entries ) : array
	{
		$result = [];

		foreach( $entries as $entry )
		{
			$result = array_merge( $result, array_keys( (array) ( $entry['lists'] ?? [] ) ) );
			isset( $entry['property'] ) ? $result[] = $this->domain() . '/property' : null;
			isset( $entry['stock'] ) ? $result[] = 'stock' : null;
		}

		return array_values( array_unique( $result ) );
	}
}
