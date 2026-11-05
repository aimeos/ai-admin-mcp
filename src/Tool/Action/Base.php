<?php

namespace Aimeos\Admin\Mcp\Tool\Action;

use Aimeos\Admin\Mcp\Exception;
use Aimeos\MShop\Common\Item\Iface as ItemIface;


abstract class Base extends \Aimeos\Admin\Mcp\Tool
{
	/**
	 * Restricts the filter to the items the user is allowed to view
	 */
	protected function addAccessFilter( \Aimeos\Base\Criteria\Iface $filter ) : void
	{
		if( $this->domain() === 'locale/site' && ( $siteId = $this->userSiteId() ) !== '' ) {
			$filter->add( 'locale.site.siteid', '=~', $siteId );
		}

		// Items shared between domains (e.g. prices, texts and media) require the permission of the owning domain
		$key = str_replace( '/', '.', $this->domain() ) . '.domain';

		if( isset( $this->manager()->getSearchAttributes( false )[$key] ) )
		{
			$domains = array_keys( (array) $this->context->config()->get( 'admin/mcp/resource', [] ) );
			$allowed = array_filter( array_map( 'strval', $domains ), fn( $domain ) => $this->allowed( $domain, 'get' ) );

			$filter->add( $key, '==', array_merge( [''], $allowed ) );
		}
	}


	protected function filterItem( ItemIface $item ) : ItemIface
	{
		if( $this->domain() === 'locale/site' && ( $siteId = $this->userSiteId() ) !== ''
			&& $item->getSiteId() !== '' && !str_starts_with( $item->getSiteId(), $siteId ) ) {
			throw new Exception( 'Forbidden', 403 );
		}

		$this->items()->permit( $item, 'get' );

		return $item;
	}


	/**
	 * @param iterable<ItemIface> $items
	 * @return array<int|string, ItemIface>
	 */
	protected function filterItems( iterable $items ) : array
	{
		$result = [];

		foreach( $items as $key => $item )
		{
			if( $this->domain() !== 'locale/site' || ( $siteId = $this->userSiteId() ) === ''
				|| $item->getSiteId() === '' || str_starts_with( $item->getSiteId(), $siteId ) ) {
				$result[$key] = $item;
			}
		}

		return $result;
	}


	protected function resultDomain() : string
	{
		return $this->domain();
	}


	protected function userSiteId() : string
	{
		return (string) $this->context->user()?->getSiteId();
	}
}
