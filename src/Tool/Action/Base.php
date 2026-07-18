<?php

namespace Aimeos\Admin\Mcp\Tool\Action;

use Aimeos\Admin\Mcp\Exception;
use Aimeos\MShop\Common\Item\Iface as ItemIface;


abstract class Base extends \Aimeos\Admin\Mcp\Tool
{
	protected function addSiteFilter( \Aimeos\Base\Criteria\Iface $filter ) : void
	{
		if( $this->domain() === 'locale/site' && ( $siteId = $this->userSiteId() ) !== '' ) {
			$filter->add( 'locale.site.siteid', '=~', $siteId );
		}
	}


	protected function filterItem( ItemIface $item ) : ItemIface
	{
		if( $this->domain() === 'locale/site' && ( $siteId = $this->userSiteId() ) !== ''
			&& $item->getSiteId() !== '' && !str_starts_with( $item->getSiteId(), $siteId ) ) {
			throw new Exception( 'Forbidden', 403 );
		}

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
