<?php

namespace Aimeos\Admin\Mcp\Tool\Action;

use Aimeos\Prisma\Schema\Schema;


abstract class Aggregate extends Base
{
	protected const ACTION = 'aggregate';
	protected const PERMISSION = 'get';


	public function description() : string
	{
		return sprintf( 'Aggregates Aimeos %s items by one or more keys.', str_replace( '/', ' ', $this->domain() ) );
	}


	public function schema() : Schema
	{
		return $this->objectSchema( [
			'key' => Schema::array()->items( Schema::string() )->min( 1 )->required()->description( 'Columns to group by' ),
			'value' => Schema::string()->description( 'Column whose values are aggregated' ),
			'type' => Schema::string()->description( 'Aggregation type such as sum or avg; omitted for count' ),
			'filter' => Schema::object()->description( 'Aimeos filter expression' ),
			'sort' => Schema::array()->items( Schema::string() )->default( [] )->description( 'Sort expressions' ),
			'limit' => Schema::integer()->min( 1 )->max( 10000 )->default( 10000 )->description( 'Maximum number of aggregate values' ),
		] );
	}


	protected function run( array $arguments ) : array
	{
		$manager = $this->manager();
		$filter = $manager->filter()
			->order( (array) ( $arguments['sort'] ?? [] ) )
			->slice( 0, (int) ( $arguments['limit'] ?? 10000 ) );
		$this->addSiteFilter( $filter );

		if( $conditions = (array) ( $arguments['filter'] ?? [] ) ) {
			$filter->add( $filter->parse( $conditions ) );
		}

		if( !is_callable( [$manager, 'aggregate'] ) ) {
			throw new \Aimeos\Admin\Mcp\Exception( sprintf( 'Manager for "%s" does not support aggregate()', $this->domain() ), 500 );
		}

		$values = $manager->aggregate(
			$filter,
			(array) $arguments['key'],
			isset( $arguments['value'] ) ? (string) $arguments['value'] : null,
			isset( $arguments['type'] ) ? (string) $arguments['type'] : null
		)->all();

		return ['values' => $values];
	}
}
