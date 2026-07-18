<?php

namespace Aimeos\Admin\Mcp\Tool\Action;

use Aimeos\Prisma\Schema\Schema;


abstract class SearchTree extends Base
{
	protected const ACTION = 'search-tree';
	protected const PERMISSION = 'get';


	public function description() : string
	{
		return sprintf( 'Searches an Aimeos %s tree and includes matching parent paths.', str_replace( '/', ' ', $this->domain() ) );
	}


	public function schema() : Schema
	{
		return $this->objectSchema( [
			'filter' => Schema::object()->description( 'Aimeos filter expression' ),
			'include' => Schema::array()->items( Schema::string() )->default( [] )->description( 'Related domains to load' ),
			'limit' => Schema::integer()->min( 1 )->max( 1000 )->default( 100 )->description( 'Maximum number of matching nodes' ),
		] );
	}


	protected function run( array $arguments ) : array
	{
		$manager = $this->manager();
		$prefix = str_replace( '/', '.', $this->domain() );
		$include = (array) ( $arguments['include'] ?? [] );
		$filter = $manager->filter()->order( ['-' . $prefix . '.level', 'sort:' . $prefix . ':position'] );
		$this->addSiteFilter( $filter );

		if( $conditions = (array) ( $arguments['filter'] ?? [] ) ) {
			$filter->add( $filter->parse( $conditions ) );
		}

		$items = $manager->search( $filter->slice( 0, (int) ( $arguments['limit'] ?? 100 ) ), $include );

		foreach( $items as $key => $item )
		{
			if( isset( $items[$item->getParentId()] ) ) {
				$items[$item->getParentId()]->addChild( $item );
				unset( $items[$key] );
			}
		}

		$items = $this->parents( $items->values(), $include );
		$result = [];

		foreach( $this->filterItems( $items ) as $item ) {
			$result[] = $this->items()->toArray( $item, $this->resultDomain() );
		}

		return ['items' => $result];
	}


	/**
	 * @param array<int, string> $include
	 */
	private function parents( \Aimeos\Map $items, array $include ) : \Aimeos\Map
	{
		$parentIds = map();

		foreach( $items as $item ) {
			$item instanceof \Aimeos\MShop\Common\Item\Tree\Iface ? $parentIds->push( $item->getParentId() ) : null;
		}

		if( ( $parentIds = $parentIds->filter() )->isEmpty() ) {
			return $items;
		}

		$manager = $this->manager();
		$prefix = str_replace( '/', '.', $this->domain() );
		$filter = $manager->filter()
			->add( $prefix . '.id', '==', $parentIds->unique() )
			->order( ['-' . $prefix . '.level', 'sort:' . $prefix . ':position'] )
			->slice( 0, 0x7fffffff );
		$this->addSiteFilter( $filter );
		$parents = $manager->search( $filter, $include );
		$indexes = $parentIds->unique()->flip();
		$itemKeys = map();

		foreach( $items as $key => $item ) {
			$item instanceof \Aimeos\MShop\Common\Item\Iface ? $itemKeys[$item->getId()] = $key : null;
		}

		foreach( $parents as $parentId => $parent )
		{
			if( isset( $itemKeys[$parentId] ) ) {
				$items[$itemKeys[$parentId]]->addChild( $items[$indexes[$parentId]] );
				unset( $items[$indexes[$parentId]] );
			} else {
				$items[$indexes[$parentId]] = $parent->addChild( $items[$indexes[$parentId]] );
			}
		}

		return $this->parents( $items, $include );
	}
}
