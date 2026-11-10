<?php

namespace Aimeos\Admin\Mcp\Tool\Action;

use Aimeos\Admin\Mcp\Exception;
use Aimeos\Admin\Mcp\Tools;
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

		// shared items (e.g. prices, texts and media) require the read permission of the owning domain
		Tools::restrict( $this->context, $this->manager(), $filter );
	}


	protected function filterItem( ItemIface $item ) : ItemIface
	{
		if( $this->domain() === 'locale/site' && ( $siteId = $this->userSiteId() ) !== ''
			&& $item->getSiteId() !== '' && !str_starts_with( $item->getSiteId(), $siteId ) ) {
			throw new Exception( 'Forbidden', 403 );
		}

		// same rule for shared items as in the filter for searching items
		return Tools::readable( $this->context, $item );
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
