<?php

namespace Aimeos\Admin\Mcp;

use Aimeos\MShop\Common\Item\Iface as ItemIface;
use Aimeos\MShop\Common\Manager\Iface as ManagerIface;
use Nyholm\Psr7\Stream;
use Nyholm\Psr7\UploadedFile;


class Items
{
	public function __construct( private \Aimeos\MShop\ContextIface $context )
	{
	}


	/**
	 * Serializes an Aimeos item and its loaded references.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray( ItemIface $item, string $domain, int $depth = 0 ) : array
	{
		$result = $this->unprefix( $domain, $item->toArray( true ) );
		$maxDepth = max( 0, (int) $this->context->config()->get( 'admin/mcp/serialization/max-depth', 3 ) );

		if( $depth >= $maxDepth ) {
			return $result;
		}

		if( $item instanceof \Aimeos\MShop\Customer\Item\Iface ) {
			$result['groups'] = array_values( $item->getGroups() );
		}

		if( $item instanceof \Aimeos\MShop\Common\Item\AddressRef\Iface ) {
			$result['address'] = $this->collection( $item->getAddressItems(), $domain . '/address', $depth + 1 );
		}

		if( $item instanceof \Aimeos\MShop\Common\Item\ListsRef\Iface ) {
			$result['lists'] = $this->lists( $item, $depth + 1 );
		}

		if( $item instanceof \Aimeos\MShop\Common\Item\PropertyRef\Iface ) {
			$result['property'] = $this->collection( $item->getPropertyItems( null, false ), $domain . '/property', $depth + 1 );
		}

		if( $item instanceof \Aimeos\MShop\Product\Item\Iface ) {
			$result['stock'] = $this->collection( $item->getStockItems(), 'stock', $depth + 1 );
		}

		if( $item instanceof \Aimeos\MShop\Common\Item\Tree\Iface ) {
			$result['children'] = $this->collection( $item->getChildren(), $domain, $depth + 1 );
			$result['hasChildren'] = $item->hasChildren();
		}

		if( $item instanceof \Aimeos\MShop\Order\Item\Iface ) {
			$result += $this->order( $item, $depth + 1 );
		} elseif( $item instanceof \Aimeos\MShop\Order\Item\Product\Iface ) {
			$result['attribute'] = $this->collection( $item->getAttributeItems(), 'order/product/attribute', $depth + 1 );
			$result['product'] = $this->collection( $item->getProducts(), 'order/product', $depth + 1 );
		} elseif( $item instanceof \Aimeos\MShop\Order\Item\Service\Iface ) {
			$result['attribute'] = $this->collection( $item->getAttributeItems(), 'order/service/attribute', $depth + 1 );
			$result['transaction'] = $this->collection( $item->getTransactions(), 'order/service/transaction', $depth + 1 );
		}

		return $result;
	}


	/**
	 * Updates an item from unprefixed tool input.
	 *
	 * @param array<string, mixed> $entry
	 */
	public function update( ManagerIface $manager, ItemIface $item, array $entry, string $domain ) : ItemIface
	{
		$data = $this->prefix( $domain, $entry );

		if( $item instanceof \Aimeos\MShop\Locale\Item\Site\Iface ) {
			$item = $this->updateSite( $item, $data );
		} elseif( $item instanceof \Aimeos\MShop\Customer\Item\Iface ) {
			$item = $this->updateCustomer( $item, $entry, $data );
		} else {
			$item = $item->fromArray( $data, true );
		}

		if( isset( $entry['address'] ) && $item instanceof \Aimeos\MShop\Common\Item\AddressRef\Iface ) {
			$item = $this->updateAddresses( $manager, $item, (array) $entry['address'], $domain . '/address' );
		}

		if( isset( $entry['lists'] ) && $item instanceof \Aimeos\MShop\Common\Item\ListsRef\Iface ) {
			$item = $this->updateLists( $item, (array) $entry['lists'] );
		}

		if( isset( $entry['property'] ) && $item instanceof \Aimeos\MShop\Common\Item\PropertyRef\Iface ) {
			$item = $this->updateProperties( $manager, $item, (array) $entry['property'], $domain . '/property' );
		}

		if( isset( $entry['stock'] ) && $item instanceof \Aimeos\MShop\Product\Item\Iface ) {
			$item = $this->updateStock( $manager, $item, (array) $entry['stock'] );
		}

		if( $item instanceof \Aimeos\MShop\Media\Item\Iface && ( isset( $entry['file'] ) || isset( $entry['filePreview'] ) ) ) {
			$item = $this->upload( $manager, $item, $entry );
		}

		if( $item instanceof \Aimeos\MShop\Order\Item\Iface ) {
			$item = $this->updateOrder( $item, $entry );
		}

		return $item;
	}


	/**
	 * @param iterable<ItemIface> $items
	 * @return array<int, array<string, mixed>>
	 */
	private function collection( iterable $items, string $domain, int $depth ) : array
	{
		$result = [];

		foreach( $items as $item ) {
			$result[] = $this->toArray( $item, $domain, $depth );
		}

		return $result;
	}


	/**
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	private function lists( \Aimeos\MShop\Common\Item\ListsRef\Iface $item, int $depth ) : array
	{
		$result = [];
		$domains = (array) $this->context->config()->get( 'admin/mcp/lists-domains', [] );
		$resource = $item->getResourceType();

		foreach( $domains as $domain )
		{
			$entries = [];

			foreach( $item->getListItems( (string) $domain, null, null, false ) as $listItem )
			{
				$entry = $this->unprefix( $resource . '/lists', $listItem->toArray( true ) );

				if( $refItem = $listItem->getRefItem() ) {
					$entry['item'] = $this->toArray( $refItem, (string) $domain, $depth );
				}

				$entries[] = $entry;
			}

			if( $entries ) {
				$result[(string) $domain] = $entries;
			}
		}

		return $result;
	}


	/**
	 * @return array<string, mixed>
	 */
	private function order( \Aimeos\MShop\Order\Item\Iface $item, int $depth ) : array
	{
		$coupons = [];

		foreach( $item->getCoupons() as $code => $products )
		{
			$ids = [];

			foreach( $products as $product ) {
				$product instanceof \Aimeos\MShop\Order\Item\Product\Iface ? $ids[] = $product->getId() : null;
			}

			$coupons[] = ['code' => (string) $code, 'productIds' => $ids];
		}

		return [
			'address' => $this->collection( $item->getAddresses()->collapse(), 'order/address', $depth ),
			'coupon' => $coupons,
			'product' => $this->collection( $item->getProducts(), 'order/product', $depth ),
			'service' => $this->collection( $item->getServices()->collapse(), 'order/service', $depth ),
			'status' => $this->collection( $item->getStatuses()->collapse(), 'order/status', $depth ),
		];
	}


	/**
	 * @param array<string, mixed> $entry
	 * @param array<string, mixed> $data
	 */
	private function updateCustomer( \Aimeos\MShop\Customer\Item\Iface $item, array $entry, array $data ) : ItemIface
	{
		$view = $this->context->view();
		$siteId = (string) $this->context->user()?->getSiteId();

		if( !$view->access( ['super'] ) && !( $siteId !== '' && str_starts_with( $item->getSiteId(), $siteId ) ) ) {
			throw new Exception( 'Forbidden', 403 );
		}

		$item = $item->fromArray( $data );

		if( isset( $entry['groups'] ) && $view->access( ['super', 'admin'] ) ) {
			$item->setGroups( array_unique( (array) $entry['groups'] ) );
		}

		if( $view->access( ['super', 'admin'] ) || $item->getId() === $this->context->user()?->getId() )
		{
			isset( $entry['password'] ) ? $item->setPassword( (string) $entry['password'] ) : null;
			isset( $entry['code'] ) ? $item->setCode( (string) $entry['code'] ) : null;
		}

		return $item;
	}


	/**
	 * @param array<string, mixed> $data
	 */
	private function updateSite( \Aimeos\MShop\Locale\Item\Site\Iface $item, array $data ) : ItemIface
	{
		$siteId = (string) $this->context->user()?->getSiteId();

		if( !$this->context->view()->access( ['super'] )
			&& ( $siteId === '' || $item->getSiteId() === '' || !str_starts_with( $item->getSiteId(), $siteId ) ) ) {
			throw new Exception( 'Forbidden', 403 );
		}

		return $item->fromArray( $data, true );
	}


	/**
	 * @param array<int, array<string, mixed>> $entries
	 */
	private function updateAddresses( ManagerIface $manager, \Aimeos\MShop\Common\Item\AddressRef\Iface $item,
		array $entries, string $domain ) : ItemIface
	{
		$addressItems = $item->getAddressItems()->reverse();

		foreach( $entries as $entry )
		{
			$data = $this->prefix( $domain, (array) $entry );
			$address = $addressItems->pop() ?: $manager->createAddressItem(); // @phpstan-ignore method.notFound
			$item->addAddressItem( $address->fromArray( $data, true ) );
		}

		return $item->deleteAddressItems( $addressItems );
	}


	/**
	 * @param array<string, array<int, array<string, mixed>>> $entries
	 */
	private function updateLists( \Aimeos\MShop\Common\Item\ListsRef\Iface $item, array $entries ) : ItemIface
	{
		$resource = $item->getResourceType();
		$listManager = \Aimeos\MShop::create( $this->context, $resource . '/lists' );

		foreach( $entries as $domain => $list )
		{
			$domainManager = \Aimeos\MShop::create( $this->context, (string) $domain );
			$listItems = $item->getListItems( (string) $domain, null, null, false );
			$refItems = $item->getRefItems( (string) $domain, null, null, false );

			foreach( $list as $raw )
			{
				$raw = (array) $raw;
				$listData = $this->prefix( $resource . '/lists', array_diff_key( $raw, ['item' => true] ) );
				$listId = (string) ( $listData[$resource . '.lists.id'] ?? '' );
				$listType = (string) ( $listData[$resource . '.lists.type'] ?? 'default' );
				$refEntry = (array) ( $raw['item'] ?? [] );
				$refData = $this->prefix( (string) $domain, $refEntry );
				$refId = (string) ( $refData[str_replace( '/', '.', (string) $domain ) . '.id']
					?? $listData[$resource . '.lists.refid'] ?? '' );

				$listItem = $listItems->get( $listId ) ?? $item->getListItem( (string) $domain, $listType, $refId );

				if( !$listItem )
				{
					$listItem = $listManager->create();

					if( !$listItem instanceof \Aimeos\MShop\Common\Item\Lists\Iface ) {
						throw new Exception( 'Invalid lists manager', 500 );
					}
				}
				$refItem = null;

				if( $refEntry ) {
					$refItem = $listItem->getRefItem() ?? $refItems->get( $refId ) ?? $domainManager->create();
					$refItem = $this->update( $domainManager, $refItem, $refEntry, (string) $domain );
				}

				$item->addListItem( (string) $domain, $listItem->fromArray( $listData, true ), $refItem );
				unset( $listItems[$listItem->getId()] );
			}

			$item->deleteListItems( $listItems );
		}

		return $item;
	}


	/**
	 * @param array<int, array<string, mixed>> $entries
	 */
	private function updateProperties( ManagerIface $manager, \Aimeos\MShop\Common\Item\PropertyRef\Iface $item,
		array $entries, string $domain ) : ItemIface
	{
		$propertyItems = $item->getPropertyItems( null, false )->reverse();

		foreach( $entries as $entry )
		{
			$data = $this->prefix( $domain, (array) $entry );
			$property = $propertyItems->pop() ?: $manager->createPropertyItem(); // @phpstan-ignore method.notFound
			$item->addPropertyItem( $property->fromArray( $data ) );
		}

		return $item->deletePropertyItems( $propertyItems );
	}


	/**
	 * @param array<int, array<string, mixed>> $entries
	 */
	private function updateStock( ManagerIface $manager, \Aimeos\MShop\Product\Item\Iface $item, array $entries ) : ItemIface
	{
		$stockItems = $item->getStockItems()->col( null, 'stock.type' );

		foreach( $entries as $entry )
		{
			$data = $this->prefix( 'stock', (array) $entry );
			$type = (string) ( $data['stock.type'] ?? '' );
			$stockItem = $stockItems->get( $type ) ?: $manager->createStockItem(); // @phpstan-ignore method.notFound
			$item->addStockItem( $stockItem->fromArray( $data ) );
			unset( $stockItems[$type] );
		}

		return $item->deleteStockItems( $stockItems );
	}


	/**
	 * @param array<string, mixed> $entry
	 */
	private function upload( ManagerIface $manager, \Aimeos\MShop\Media\Item\Iface $item, array $entry ) : ItemIface
	{
		$file = isset( $entry['file'] ) ? $this->uploadedFile( (array) $entry['file'] ) : null;
		$preview = isset( $entry['filePreview'] ) ? $this->uploadedFile( (array) $entry['filePreview'] ) : null;

		return $manager->upload( $item, $file, $preview ); // @phpstan-ignore method.notFound
	}


	/**
	 * @param array<string, mixed> $data
	 */
	private function uploadedFile( array $data ) : UploadedFile
	{
		$binary = base64_decode( (string) ( $data['base64'] ?? '' ), true );

		if( $binary === false ) {
			throw new Exception( 'Invalid base64 file content', 400 );
		}

		$maxSize = max( 1, (int) $this->context->config()->get( 'admin/mcp/media/max-size', 10485760 ) );

		if( strlen( $binary ) > $maxSize ) {
			throw new Exception( 'File exceeds the maximum allowed size', 413 );
		}

		return new UploadedFile(
			Stream::create( $binary ),
			strlen( $binary ),
			UPLOAD_ERR_OK,
			(string) ( $data['fileName'] ?? '' ),
			isset( $data['mimeType'] ) ? (string) $data['mimeType'] : null
		);
	}


	/**
	 * @param array<string, mixed> $entry
	 */
	private function updateOrder( \Aimeos\MShop\Order\Item\Iface $item, array $entry ) : ItemIface
	{
		if( isset( $entry['address'] ) )
		{
			$addresses = [];
			$manager = \Aimeos\MShop::create( $this->context, 'order/address' );

			foreach( (array) $entry['address'] as $raw )
			{
				$data = $this->prefix( 'order/address', (array) $raw );
				$type = (string) ( $data['order.address.type'] ?? 'billing' );
				$addresses[$type][] = $manager->create()->fromArray( $data, true );
			}

			$item->setAddresses( $addresses );
		}

		if( isset( $entry['product'] ) ) {
			$item->setProducts( array_map( fn( $raw ) => $this->orderProduct( (array) $raw ), (array) $entry['product'] ) );
		}

		if( isset( $entry['service'] ) )
		{
			$item->setServices( [] );

			foreach( (array) $entry['service'] as $raw ) {
				$service = $this->orderService( (array) $raw );
				$item->addService( $service, $service->getType() );
			}
		}

		return $item;
	}


	/**
	 * @param array<string, mixed> $entry
	 */
	private function orderProduct( array $entry ) : \Aimeos\MShop\Order\Item\Product\Iface
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order/product' );
		$data = $this->prefix( 'order/product', $entry );
		/** @var \Aimeos\MShop\Order\Item\Product\Iface $item */
		$item = $manager->create()->fromArray( $data, true );

		if( isset( $entry['attribute'] ) )
		{
			$attrManager = \Aimeos\MShop::create( $this->context, 'order/product/attribute' );
			$attributes = [];

			foreach( (array) $entry['attribute'] as $raw ) {
				$data = $this->prefix( 'order/product/attribute', (array) $raw );
				$attribute = $attrManager->create()->fromArray( $data, true );

				if( !$attribute instanceof \Aimeos\MShop\Order\Item\Product\Attribute\Iface ) {
					throw new Exception( 'Invalid order product attribute manager', 500 );
				}

				$attributes[] = $attribute;
			}

			$item->setAttributeItems( map( $attributes ) );
		}

		if( isset( $entry['product'] ) ) {
			$item->setProducts( array_map( fn( $raw ) => $this->orderProduct( (array) $raw ), (array) $entry['product'] ) );
		}

		return $item;
	}


	/**
	 * @param array<string, mixed> $entry
	 */
	private function orderService( array $entry ) : \Aimeos\MShop\Order\Item\Service\Iface
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order/service' );
		$data = $this->prefix( 'order/service', $entry );
		/** @var \Aimeos\MShop\Order\Item\Service\Iface $item */
		$item = $manager->create()->fromArray( $data, true );

		if( isset( $entry['attribute'] ) )
		{
			$attrManager = \Aimeos\MShop::create( $this->context, 'order/service/attribute' );
			$attributes = [];

			foreach( (array) $entry['attribute'] as $raw ) {
				$data = $this->prefix( 'order/service/attribute', (array) $raw );
				$attribute = $attrManager->create()->fromArray( $data, true );

				if( !$attribute instanceof \Aimeos\MShop\Order\Item\Service\Attribute\Iface ) {
					throw new Exception( 'Invalid order service attribute manager', 500 );
				}

				$attributes[] = $attribute;
			}

			$item->setAttributeItems( map( $attributes ) );
		}

		if( isset( $entry['transaction'] ) )
		{
			$transactionManager = \Aimeos\MShop::create( $this->context, 'order/service/transaction' );
			$transactions = [];

			foreach( (array) $entry['transaction'] as $raw ) {
				$data = $this->prefix( 'order/service/transaction', (array) $raw );
				$transactions[] = $transactionManager->create()->fromArray( $data, true );
			}

			$item->setTransactions( $transactions );
		}

		return $item;
	}


	/**
	 * Prefixes item fields and excludes nested tool structures.
	 *
	 * @param array<string, mixed> $entry
	 * @return array<string, mixed>
	 */
	private function prefix( string $domain, array $entry ) : array
	{
		$result = [];
		$prefix = str_replace( '/', '.', $domain ) . '.';
		$nested = ['address', 'file', 'filePreview', 'groups', 'item', 'lists', 'product', 'property', 'service', 'stock'];

		foreach( $entry as $key => $value )
		{
			if( !in_array( $key, $nested, true ) ) {
				$result[$prefix . $key] = $value;
			}
		}

		return $result;
	}


	/**
	 * @param array<string, mixed> $values
	 * @return array<string, mixed>
	 */
	private function unprefix( string $domain, array $values ) : array
	{
		$result = [];
		$prefix = str_replace( '/', '.', $domain ) . '.';

		foreach( $values as $key => $value ) {
			$result[str_starts_with( (string) $key, $prefix ) ? substr( (string) $key, strlen( $prefix ) ) : (string) $key] = $value;
		}

		return $result;
	}
}
