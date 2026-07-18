<?php

namespace Aimeos\Admin\Mcp\Tool\Action;

use Aimeos\Prisma\Schema\Schema;


abstract class Search extends Base
{
	protected const ACTION = 'search';
	protected const PERMISSION = 'get';


	public function description() : string
	{
		return sprintf( 'Searches Aimeos %s items using structured filter conditions.', str_replace( '/', ' ', $this->domain() ) );
	}


	public function schema() : Schema
	{
		return $this->objectSchema( [
			'filter' => Schema::object()->description( 'Aimeos filter expression' ),
			'include' => Schema::array()->items( Schema::string() )->default( [] )->description( 'Related domains to load' ),
			'sort' => Schema::array()->items( Schema::string() )->default( [] )->description( 'Sort expressions' ),
			'offset' => Schema::integer()->min( 0 )->default( 0 )->description( 'Result offset' ),
			'limit' => Schema::integer()->min( 1 )->max( 1000 )->default( 100 )->description( 'Maximum number of items' ),
		] );
	}


	protected function run( array $arguments ) : array
	{
		$manager = $this->manager();
		$filter = $manager->filter()
			->order( (array) ( $arguments['sort'] ?? [] ) )
			->slice( (int) ( $arguments['offset'] ?? 0 ), (int) ( $arguments['limit'] ?? 100 ) );
		$this->addSiteFilter( $filter );

		if( $conditions = (array) ( $arguments['filter'] ?? [] ) ) {
			$filter->add( $filter->parse( $conditions ) );
		}

		$total = 0;
		$items = $this->filterItems( $manager->search( $filter, (array) ( $arguments['include'] ?? [] ), $total ) );
		$result = [];

		foreach( $items as $item ) {
			$result[] = $this->items()->toArray( $item, $this->resultDomain() );
		}

		return ['items' => $result, 'total' => $total];
	}
}
