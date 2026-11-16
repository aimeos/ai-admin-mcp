<?php

namespace Aimeos\Admin\Mcp\Tool\Action;

use Aimeos\Admin\Mcp\Tools;
use Aimeos\MShop\Common\Item\Iface as ItemIface;


abstract class Base extends \Aimeos\Admin\Mcp\Tool
{
	/**
	 * Restricts the filter to the items the user is allowed to view
	 *
	 * Shared items (e.g. prices, texts and media) require the read permission of the
	 * owning domain and sites must be within the site tree of the user.
	 */
	protected function addAccessFilter( \Aimeos\Base\Criteria\Iface $filter ) : void
	{
		Tools::restrict( $this->context, $this->manager(), $filter );
	}


	protected function filterItem( ItemIface $item ) : ItemIface
	{
		// same rules as in the filter for searching items
		return Tools::readable( $this->context, $item );
	}


	/**
	 * @param iterable<ItemIface> $items
	 * @return array<int|string, ItemIface>
	 */
	protected function filterItems( iterable $items ) : array
	{
		// e.g. the path of a site contains the parent sites outside the site tree of the user
		return map( $items )->filter( fn( $item ) => Tools::visible( $this->context, $item ) )->all();
	}


	protected function resultDomain() : string
	{
		return $this->domain();
	}
}
