<?php

namespace Aimeos\Admin\Mcp;

use Aimeos\MShop\Common\Item\Iface as ItemIface;
use Aimeos\MShop\Common\Manager\Iface as ManagerIface;
use Nyholm\Psr7\Stream;
use Nyholm\Psr7\UploadedFile;


class Items
{
	/** Fields of ordered products which can be changed, same as in the admin backend */
	public const ORDER_PRODUCT_FIELDS = ['statusdelivery', 'statuspayment', 'qtyopen', 'timeframe', 'notes'];


	/**
	 * @param \Closure(string, string):bool $allowed
	 */
	public function __construct( private \Aimeos\MShop\ContextIface $context, private \Closure $allowed )
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

		if( $item instanceof \Aimeos\MShop\Product\Item\Iface && ( $this->allowed )( 'stock', 'get' ) ) {
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
	 * Checks if the user has the permission for the domain owning the item.
	 *
	 * Items shared between domains (e.g. prices, texts and media) are owned by the
	 * domain stored in their "<type>.domain" field. Managing them requires the same
	 * permission as managing the owning domain, so they can't be changed via their
	 * own tools if the user isn't allowed to manage the owning domain.
	 */
	public function permit( ItemIface $item, string $permission ) : void
	{
		$domain = (string) $item->get( str_replace( '/', '.', $item->getResourceType() ) . '.domain' );

		if( $domain !== '' ) {
			$this->authorize( $domain, $permission );
		}
	}


	/**
	 * Updates an item from unprefixed tool input.
	 *
	 * @param array<string, mixed> $entry
	 */
	public function update( ManagerIface $manager, ItemIface $item, array $entry, string $domain ) : ItemIface
	{
		$data = $this->prefix( $domain, $entry );

		if( $item instanceof \Aimeos\MShop\Media\Item\Iface ) {
			$data = $this->mediaPaths( $item, $data );
		}

		// Check before and after the update so items can't be moved into or out of restricted domains
		$this->permit( $item, 'save' );

		if( $item instanceof \Aimeos\MShop\Locale\Item\Site\Iface ) {
			$item = $this->updateSite( $item, $data );
		} elseif( $item instanceof \Aimeos\MShop\Customer\Item\Iface ) {
			$item = $this->updateCustomer( $item, $entry, $data );
		} else {
			$item = $item->fromArray( $data, true );
		}

		$this->permit( $item, 'save' );

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
	 * Throws an exception if the user lacks the permission for the domain
	 */
	private function authorize( string $domain, string $permission ) : void
	{
		if( !( $this->allowed )( $domain, $permission ) ) {
			throw new Exception( 'Forbidden', 403 );
		}
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
			// List and referenced items can contain sensitive data of the referenced domain
			// (e.g. payment tokens in customer/service list configs or provider credentials
			// in service configs), so they are only returned if the user can view that domain
			if( !( $this->allowed )( (string) $domain, 'get' ) ) {
				continue;
			}

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
			'address' => $this->collection( $item->getAddresses()->flat( 1 ), 'order/address', $depth ),
			'coupon' => $coupons,
			'product' => $this->collection( $item->getProducts(), 'order/product', $depth ),
			'service' => $this->collection( $item->getServices()->flat( 1 ), 'order/service', $depth ),
			'status' => $this->collection( $item->getStatuses()->flat( 1 ), 'order/status', $depth ),
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

		// Passwords can't be set via MCP
		if( isset( $entry['code'] ) && ( $view->access( ['super', 'admin'] ) || $item->getId() === $this->context->user()?->getId() ) ) {
			$item->setCode( (string) $entry['code'] );
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
			&& ( $siteId === '' || $item->getSiteId() === '' || !str_starts_with( $item->getSiteId(), $siteId ) )
		) {
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
			$list = (array) $list;

			// Referenced items are created/updated with their own manager, so a nested
			// item requires the "save" permission of that domain. Group memberships are
			// granted through list references, so linking a group is privileged too and
			// always requires "save" - even when only a reference ID is passed.
			$default = ( (string) $domain === 'group' ) ? 'save' : 'get';
			$permissions = map( $list )
				->map( fn( $raw ) => !empty( ( (array) $raw )['item'] ) ? 'save' : $default )
				->unique();

			foreach( $permissions as $permission ) {
				$this->authorize( (string) $domain, $permission );
			}

			$domainManager = null;
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

				if( $refEntry )
				{
					$domainManager ??= \Aimeos\MShop::create( $this->context, (string) $domain );
					$refItem = $listItem->getRefItem() ?? $refItems->get( $refId ) ?? $domainManager->create();
					$refItem = $this->update( $domainManager, $refItem, $refEntry, (string) $domain );
				}

				$item->addListItem( (string) $domain, $listItem->fromArray( $listData, true ), $refItem );
				unset( $listItems[$listItem->getId()] );
			}

			if( !$listItems->isEmpty() ) {
				$this->authorize( (string) $domain, 'delete' );
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
	 * Updates the parts of an order which can be changed in the admin backend too
	 *
	 * Ordered products and services are immutable except for their delivery/payment
	 * states and the service attributes. Transactions are only added by the payment
	 * providers and can't be changed at all. Unchanged values of read-only fields are
	 * accepted so orders returned by the tools can be saved again.
	 *
	 * @param array<string, mixed> $entry
	 */
	private function updateOrder( \Aimeos\MShop\Order\Item\Iface $item, array $entry ) : ItemIface
	{
		if( isset( $entry['status'] ) ) {
			$this->orderStatuses( $item, (array) $entry['status'] );
		}

		if( isset( $entry['coupon'] ) ) {
			$this->orderCoupons( $item, (array) $entry['coupon'] );
		}

		if( isset( $entry['address'] ) ) {
			$this->orderAddresses( $item, (array) $entry['address'] );
		}

		if( isset( $entry['product'] ) ) {
			$this->orderProducts( $item, (array) $entry['product'] );
		}

		if( isset( $entry['service'] ) ) {
			$this->orderServices( $item, (array) $entry['service'] );
		}

		return $item;
	}


	/**
	 * Tests that the passed statuses are equal to the stored ones
	 *
	 * Statuses are matched by ID because new ones can be added in the meantime and
	 * the order groups them by type and value, so their positions can change.
	 *
	 * @param array<int|string, mixed> $entries
	 * @throws Exception If a status is unknown or has been changed
	 */
	private function orderStatuses( \Aimeos\MShop\Order\Item\Iface $item, array $entries ) : void
	{
		$statuses = $item->getStatuses()->flat( 1 );

		foreach( $entries as $raw )
		{
			$raw = (array) $raw;
			$status = $this->byId( $statuses, (string) ( $raw['id'] ?? '' ), 'Order status' );
			$this->unchanged( $this->toArray( $status, 'order/status' ), array_diff_key( $raw, ['id' => 1] ), 'Order status' );
		}
	}


	/**
	 * Tests that the passed coupons are equal to the stored ones
	 *
	 * The coupons are read from the storage instead of the order because saving an
	 * order with loaded coupons adds their product references again.
	 *
	 * @param array<int|string, mixed> $entries
	 * @throws Exception If a coupon has been changed
	 */
	private function orderCoupons( \Aimeos\MShop\Order\Item\Iface $item, array $entries ) : void
	{
		$stored = [];
		$manager = \Aimeos\MShop::create( $this->context, 'order/coupon' );
		$filter = $manager->filter()->add( 'order.coupon.parentid', '==', $item->getId() )->slice( 0, 0x7fffffff );

		foreach( $item->getId() ? $manager->search( $filter ) : [] as $coupon )
		{
			$stored[$coupon->getCode()] ??= [];
			$coupon->getProductId() ? $stored[$coupon->getCode()][] = (string) $coupon->getProductId() : null;
		}

		foreach( $entries as $entry )
		{
			$code = (string) ( ( (array) $entry )['code'] ?? '' );
			$ids = array_map( 'strval', (array) ( ( (array) $entry )['productIds'] ?? [] ) );

			if( !isset( $stored[$code] ) || array_diff( $ids, $stored[$code] ) !== [] ) {
				throw new Exception( 'Order field "coupon" can not be changed', 400 );
			}
		}
	}


	/**
	 * Updates existing order addresses by ID and replaces or adds the ones without ID
	 *
	 * Addresses with ID are updated first and their positions are kept. Addresses without
	 * ID replace the remaining existing addresses of the same type in order of their position
	 * and are added if there are no more existing ones. The stored rows are reused because
	 * the order manager doesn't delete removed addresses.
	 *
	 * @param array<int|string, mixed> $entries
	 */
	private function orderAddresses( \Aimeos\MShop\Order\Item\Iface $item, array $entries ) : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order/address' );
		$used = $new = [];

		foreach( $entries as $raw )
		{
			$data = $this->prefix( 'order/address', (array) $raw );
			$id = (string) ( $data['order.address.id'] ?? '' );
			unset( $data['order.address.id'] );

			if( $id === '' ) {
				$new[] = $data;
				continue;
			}

			// addresses are grouped by type in the order, so the type of existing ones is read-only
			[$type, $pos] = $this->orderAddressPos( $item, $id );
			$this->unchanged( ['type' => $type], array_intersect_key( (array) $raw, ['type' => 1] ), 'Order address' );
			unset( $data['order.address.type'] );

			$item->getAddress( $type, $pos )->fromArray( $data );
			$used[$type][] = $pos;
		}

		foreach( $new as $data )
		{
			// read the type first because fromArray() removes the consumed keys from $data
			$type = (string) ( $data['order.address.type'] ?? \Aimeos\MShop\Order\Item\Address\Base::TYPE_PAYMENT );
			$address = $manager->create()->fromArray( $data );
			$positions = array_diff( array_keys( $item->getAddress( $type ) ), $used[$type] ?? [] );

			if( ( $pos = reset( $positions ) ) !== false )
			{
				$old = $item->getAddress( $type, $pos );
				$address->setId( $old->getId() )->setParentId( $old->getParentId() ); // @phpstan-ignore method.notFound
				$item->addAddress( $address, $type, $pos ); // @phpstan-ignore argument.type
				$used[$type][] = $pos;
			}
			else
			{
				$item->addAddress( $address, $type ); // @phpstan-ignore argument.type
				$used[$type][] = array_key_last( $item->getAddress( $type ) );
			}
		}
	}


	/**
	 * Returns the type and position of the order address with the given ID
	 *
	 * @return array{0: string, 1: int|string}
	 * @throws Exception If no address with that ID is available
	 */
	private function orderAddressPos( \Aimeos\MShop\Order\Item\Iface $item, string $id ) : array
	{
		foreach( $item->getAddresses() as $type => $list )
		{
			foreach( $list as $pos => $address )
			{
				if( $address->getId() === $id ) {
					return [(string) $type, $pos];
				}
			}
		}

		throw new Exception( sprintf( 'Order address with ID "%1$s" not found', $id ), 400 );
	}


	/**
	 * Updates the allowed fields of existing ordered products including the sub-products of bundles
	 *
	 * @param array<int|string, mixed> $entries
	 */
	private function orderProducts( \Aimeos\MShop\Order\Item\Iface $item, array $entries ) : void
	{
		$products = [];

		foreach( $item->getProducts() as $product ) {
			$products = array_merge( $products, [$product], $product->getProducts()->values()->all() );
		}

		$editable = array_flip( array_merge( self::ORDER_PRODUCT_FIELDS, ['id', 'product'] ) );

		while( ( $raw = array_shift( $entries ) ) !== null )
		{
			$raw = (array) $raw;
			$product = $this->byId( map( $products ), (string) ( $raw['id'] ?? '' ), 'Ordered product' );
			$this->unchanged( $this->toArray( $product, 'order/product' ), array_diff_key( $raw, $editable ), 'Ordered product' );

			$data = $this->prefix( 'order/product', array_intersect_key( $raw, array_flip( self::ORDER_PRODUCT_FIELDS ) ) );
			$product->fromArray( $data, true );

			// sub-products of bundles as returned by the tools
			array_push( $entries, ...array_values( (array) ( $raw['product'] ?? [] ) ) );
		}
	}


	/**
	 * Updates or adds attributes of existing order services
	 *
	 * Attributes are merged and never removed because the service providers store
	 * data like transaction references in them which is required for e.g. refunds.
	 *
	 * @param array<int|string, mixed> $entries
	 */
	private function orderServices( \Aimeos\MShop\Order\Item\Iface $item, array $entries ) : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order/service/attribute' );

		foreach( $entries as $raw )
		{
			$raw = (array) $raw;
			$service = $this->byId( $item->getServices()->flat( 1 ), (string) ( $raw['id'] ?? '' ), 'Order service' );
			$this->unchanged( $this->toArray( $service, 'order/service' ), array_diff_key( $raw, ['id' => 1, 'attribute' => 1] ), 'Order service' );

			foreach( (array) ( $raw['attribute'] ?? [] ) as $attr )
			{
				$data = $this->prefix( 'order/service/attribute', (array) $attr );
				$id = (string) ( $data['order.service.attribute.id'] ?? '' );
				unset( $data['order.service.attribute.id'] );

				if( $id !== '' )
				{
					// type and code are read-only because the service looks up its attributes by them
					$attrItem = $this->byId( $service->getAttributeItems(), $id, 'Order service attribute' ); // @phpstan-ignore method.notFound
					$current = ['type' => $attrItem->getType(), 'code' => $attrItem->getCode()]; // @phpstan-ignore method.notFound, method.notFound
					$this->unchanged( $current, array_intersect_key( (array) $attr, $current ), 'Order service attribute' );

					unset( $data['order.service.attribute.type'], $data['order.service.attribute.code'] );
					$attrItem->fromArray( $data );
					continue;
				}

				$values = $data; // fromArray() removes the consumed keys
				$new = $manager->create()->setType( $service->getType() )->fromArray( $values ); // @phpstan-ignore method.notFound, method.notFound
				$found = $service->getAttributeItems()->find( fn( $a ) => $a->getType() === $new->getType() && $a->getCode() === $new->getCode() ); // @phpstan-ignore method.notFound

				// update the existing attribute with the same type and code, also if it has an attribute ID
				if( $found ) {
					$found->fromArray( $data );
				} else {
					$service->setAttributeItem( $new ); // @phpstan-ignore method.notFound
				}
			}
		}
	}


	/**
	 * Allows only remote URLs for media files and previews
	 *
	 * Local paths and file systems are only set by uploads because the media manager
	 * deletes the referenced files when they are replaced. Unchanged values are ignored
	 * so items returned by the tools can be saved again and empty values clear them.
	 *
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 * @throws Exception If a local path or another file system is passed
	 */
	private function mediaPaths( \Aimeos\MShop\Media\Item\Iface $item, array $data ) : array
	{
		$remote = fn( $url ) => is_string( $url ) && preg_match( '#^https?://#i', $url ) === 1;
		// local previews are returned with the modification time as version which changes on every save
		$version = fn( $url ) => is_string( $url ) && !$remote( $url ) ? preg_replace( '#\?v=\d+$#', '', $url ) : $url;
		$current = [
			'media.url' => $item->getUrl(),
			'media.preview' => $version( $item->getPreview() ),
			'media.previews' => $item->getPreviews(),
			'media.filesystem' => $item->getFileSystem(),
		];

		if( array_key_exists( 'media.preview', $data ) ) {
			$data['media.preview'] = $version( $data['media.preview'] );
		}

		foreach( $current as $key => $value )
		{
			if( !array_key_exists( $key, $data ) || $data[$key] == $value ) {
				unset( $data[$key] );
				continue;
			}

			$urls = $key === 'media.previews' && is_array( $data[$key] ) ? $data[$key] : [$data[$key]];

			// empty values remove the files from the item
			if( $key === 'media.filesystem' || array_filter( $urls, fn( $url ) => $url !== '' && $url !== null && !$remote( $url ) ) ) {
				throw new Exception( sprintf( 'Only absolute http(s) URLs are allowed for "%1$s", upload local files instead', substr( $key, 6 ) ), 400 );
			}
		}

		return $data;
	}


	/**
	 * Tests that the passed values of read-only fields are equal to the current ones
	 *
	 * The values may be a subset of the current ones, e.g. if the item has been
	 * returned with a lower nesting depth.
	 *
	 * @param array<string, mixed> $current Current values as returned by toArray()
	 * @param array<string, mixed> $values Passed values of the read-only fields
	 * @throws Exception If a read-only value has been changed
	 */
	private function unchanged( array $current, array $values, string $label ) : void
	{
		// set by the managers on save without updating the items, so returned items can be outdated
		$ignore = ['ctime' => 1, 'mtime' => 1, 'editor' => 1];

		$same = function( $cur, $value ) use ( &$same, $ignore ) : bool {
			if( !is_array( $value ) ) {
				return !is_array( $cur ) && $value == $cur;
			}

			foreach( array_diff_key( $value, $ignore ) as $key => $val )
			{
				if( !is_array( $cur ) || !$same( $cur[$key] ?? null, $val ) ) {
					return false;
				}
			}

			return true;
		};

		foreach( array_diff_key( $values, $ignore ) as $key => $value )
		{
			if( !$same( $current[$key] ?? null, $value ) ) {
				throw new Exception( sprintf( '%1$s field "%2$s" can not be changed', $label, $key ), 400 );
			}
		}
	}


	/**
	 * Returns the item with the given ID from the list
	 *
	 * @param \Aimeos\Map<int|string, ItemIface> $items
	 * @throws Exception If no item with that ID is available
	 */
	private function byId( \Aimeos\Map $items, string $id, string $label ) : ItemIface
	{
		foreach( $items as $item )
		{
			if( $id !== '' && $item->getId() === $id ) {
				return $item;
			}
		}

		throw new Exception( sprintf( '%1$s with ID "%2$s" not found', $label, $id ), 400 );
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
