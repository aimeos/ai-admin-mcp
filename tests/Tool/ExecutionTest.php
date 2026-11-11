<?php

namespace Aimeos\Admin\Mcp\Tests\Tool;

use Aimeos\Admin\Mcp\Exception;
use Aimeos\Admin\Mcp\Tool\Catalog\SearchTree;
use Aimeos\Admin\Mcp\Tool\Catalog\Tree;
use Aimeos\Admin\Mcp\Tool\Customer\Aggregate;
use Aimeos\Admin\Mcp\Tool\Customer\Get as CustomerGet;
use Aimeos\Admin\Mcp\Tool\Customer\Search as CustomerSearch;
use Aimeos\Admin\Mcp\Tool\Product\Delete;
use Aimeos\Admin\Mcp\Tool\Product\Find;
use Aimeos\Admin\Mcp\Tool\Product\Save;
use Aimeos\Admin\Mcp\Tool\Product\Search;


class ExecutionTest extends \PHPUnit\Framework\TestCase
{
	private const MEDIA_SAVE_DENIED = 'Permission "save" for "service" is required to change "media" items';

	private \Aimeos\MShop\ContextIface $context;
	private ?string $mediaLabel = null;


	protected function setUp() : void
	{
		\Aimeos\MShop::cache( true );
		$this->context = \TestHelper::context();
	}


	public function testInvalidArguments() : void
	{
		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );

		( new Find( $this->context ) )->execute( [] );
	}


	public function testForbidden() : void
	{
		$this->setAccess( [] );

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 403 );

		( new Find( $this->context ) )->execute( ['code' => 'CNC'] );
	}


	public function testFindAndSearchProduct() : void
	{
		$found = ( new Find( $this->context ) )->execute( ['code' => 'CNC', 'include' => ['stock']] );
		$searched = ( new Search( $this->context ) )->execute( [
			'filter' => ['==' => ['product.code' => 'CNC']],
			'include' => ['stock'],
		] );

		$this->assertSame( 'CNC', $found['item']['code'] );
		$this->assertSame( 'CNC', $searched['items'][0]['code'] );
		$this->assertSame( 1, $searched['total'] );
		$this->assertSame( 'default', $searched['items'][0]['stock'][0]['type'] );
	}


	public function testCatalogTree() : void
	{
		$result = ( new Tree( $this->context ) )->execute( ['level' => 2] );

		$this->assertSame( 'root', $result['item']['code'] );
		$this->assertNotEmpty( $result['item']['children'] );
	}


	public function testSearchCatalogTree() : void
	{
		$result = ( new SearchTree( $this->context ) )->execute( ['filter' => ['||' => [
			['~=' => ['catalog.code' => 'c']],
			['==' => ['catalog.code' => 'new']],
			['==' => ['catalog.code' => 'internet']],
			['==' => ['catalog.code' => 'root']],
		]]] );

		$this->assertCount( 1, $result['items'] );
		$this->assertSame( 'root', $result['items'][0]['code'] );
		$this->assertSame( ['categories', 'group'], array_column( $result['items'][0]['children'], 'code' ) );
	}


	public function testAggregateCustomers() : void
	{
		$result = ( new Aggregate( $this->context ) )->execute( ['key' => ['customer.status']] );

		$this->assertNotEmpty( $result['values'] );
	}


	public function testSaveAndDeleteProduct() : void
	{
		$this->setAccess( ['editor'] );

		$code = 'test-mcp-' . bin2hex( random_bytes( 4 ) );
		$result = ( new Save( $this->context ) )->execute( ['items' => [[
			'code' => $code,
			'label' => 'MCP test product',
			'type' => 'default',
			'status' => 0,
			'lists' => ['text' => [[
				'type' => 'default',
				'item' => [
					'type' => 'name',
					'languageid' => 'de',
					'content' => 'MCP test product',
				],
			]]],
		]]] );
		$id = (string) ( $result['items'][0]['id'] ?? '' );

		try {
			$this->assertNotSame( '', $id );
			$this->assertSame( $code, $result['items'][0]['code'] );
			$this->assertSame( 'MCP test product', $result['items'][0]['lists']['text'][0]['item']['content'] );
		} finally {
			if( $id !== '' ) {
				$deleted = ( new Delete( $this->context ) )->execute( ['ids' => [$id]] );
				$this->assertSame( [$id], $deleted['ids'] );
			}
		}
	}


	public function testSaveCustomerPasswordRejected() : void
	{
		$view = new \Aimeos\Base\View\Standard();
		$view->addHelper( 'access', new \Aimeos\Base\View\Helper\Access\All( $view ) );
		$this->context->setView( $view );

		$errors = ( new \Aimeos\Admin\Mcp\Tool\Customer\Save( $this->context ) )->schema()
			->validate( ['items' => [['code' => 'test-mcp', 'password' => 'secret']]] );

		$manager = \Aimeos\MShop::create( $this->context, 'customer' );
		$items = new \Aimeos\Admin\Mcp\Items( $this->context );
		$item = $items->update( $manager, $manager->create(), ['code' => 'test-mcp', 'password' => 'secret'], 'customer' );

		$this->assertNotEmpty( $errors );
		$this->assertSame( 'test-mcp', $item->getCode() );
		$this->assertSame( '', $item->getPassword() );
	}


	public function testSaveCustomerEmailOtherAccountIgnored() : void
	{
		$this->setAccess( ['editor'] );

		$manager = \Aimeos\MShop::create( $this->context, 'customer' );
		$this->context->setUser( $manager->find( 'test2@example.com' ) );

		$items = new \Aimeos\Admin\Mcp\Items( $this->context );
		$item = $items->update( $manager, $manager->find( 'test@example.com' ), ['email' => 'mcp@example.test', 'firstname' => 'mcp'], 'customer' );

		$this->assertSame( 'test@example.com', $item->getPaymentAddress()->getEmail() );
		$this->assertSame( 'mcp', $item->getPaymentAddress()->getFirstname() );
	}


	public function testSaveCustomerEmailOwnAccount() : void
	{
		$this->setAccess( ['editor'] );

		$manager = \Aimeos\MShop::create( $this->context, 'customer' );
		$this->context->setUser( $user = $manager->find( 'test@example.com' ) );

		$items = new \Aimeos\Admin\Mcp\Items( $this->context );
		$item = $items->update( $manager, $user, ['email' => 'mcp@example.test'], 'customer' );

		$this->assertSame( 'mcp@example.test', $item->getPaymentAddress()->getEmail() );
	}


	public function testSaveMediaRemoteUrls() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'media' );
		$item = $manager->create()->setUrl( 'test.jpg' )->setPreview( 'test-1.jpg' );

		$item = $this->items()->update( $manager, $item, [
			'label' => 'paths', 'url' => 'https://example.com/test.jpg',
			'previews' => [100 => 'https://example.com/test-100.jpg'], 'filesystem' => 'fs-media',
		], 'media' );

		$this->assertSame( 'paths', $item->getLabel() );
		$this->assertSame( 'https://example.com/test.jpg', $item->getUrl() );
		$this->assertSame( 'https://example.com/test-100.jpg', $item->getPreview() );
		$this->assertSame( 'fs-media', $item->getFileSystem() );
	}


	public function testSaveMediaUnchangedPathsIgnored() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'media' );
		$item = $manager->create()->setUrl( 'test.jpg' )->setPreviews( [1 => 'test-1.jpg'] );

		$item = $this->items()->update( $manager, $item, [
			'label' => 'paths', 'url' => 'test.jpg', 'previews' => [1 => 'test-1.jpg'],
		], 'media' );

		$this->assertSame( 'paths', $item->getLabel() );
		$this->assertSame( 'test.jpg', $item->getUrl() );
	}


	public function testSaveMediaFetchedItemValid() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'media' );
		$media = $manager->search( $manager->filter()->slice( 0, 1 ) )->first();
		$entry = $this->items()->toArray( $media, 'media' );
		$entry['label'] = 'changed';

		$tool = new \Aimeos\Admin\Mcp\Tool\Media\Save( $this->context );
		$item = $this->items()->update( $manager, $media, $entry, 'media' );

		$this->assertArrayHasKey( 'filesystem', $entry );
		$this->assertEmpty( $tool->schema()->validate( ['items' => [$entry]] ) );
		$this->assertSame( 'changed', $item->getLabel() );
		$this->assertSame( $media->getUrl(), $item->getUrl() );
	}


	public function testSaveMediaOutdatedPreviewVersion() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'media' );
		$fetched = $manager->create( ['media.mtime' => '2020-01-01 00:00:00', 'media.mimetype' => 'image/jpeg', 'media.preview' => [1 => 'a-1.jpg']] );
		$entry = $this->items()->toArray( $fetched, 'media' );
		$entry['label'] = 'changed';

		$this->assertStringContainsString( '?v=', $entry['preview'] );

		$reloaded = $manager->create( ['media.mtime' => '2021-01-01 00:00:00', 'media.mimetype' => 'image/jpeg', 'media.preview' => [1 => 'a-1.jpg']] );
		$item = $this->items()->update( $manager, $reloaded, $entry, 'media' );

		$this->assertSame( 'changed', $item->getLabel() );
		$this->assertSame( [1 => 'a-1.jpg'], $item->getPreviews() );
	}


	public function testSaveMediaClearPaths() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'media' );
		$item = $manager->create()->setUrl( 'https://example.com/a.jpg' )->setPreviews( [1 => 'https://example.com/a-1.jpg'] );

		$item = $this->items()->update( $manager, $item, ['url' => '', 'previews' => []], 'media' );

		$this->assertSame( '', $item->getUrl() );
		$this->assertSame( [], $item->getPreviews() );
	}


	public function testSaveMediaLocalPathForbidden() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'media' );

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );

		$this->items()->update( $manager, $manager->create()->setUrl( 'test.jpg' ), ['url' => 'index.php'], 'media' );
	}


	public function testSaveMediaLocalPreviewForbidden() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'media' );

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );

		$this->items()->update( $manager, $manager->create(), [
			'previews' => [1 => 'https://example.com/a.jpg', 2 => '../index.php'],
		], 'media' );
	}


	public function testSaveMediaFilesystemForbidden() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'media' );

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );

		$this->items()->update( $manager, $manager->create(), ['filesystem' => 'fs'], 'media' );
	}


	public function testSaveOrderAddressReplacesType() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$address = $manager->createAddress()->setId( '5' )->setParentId( '7' )->setFirstname( 'old' )->setLastname( 'old' );
		$order = $this->order()->addAddress( $address, 'payment', 1 );

		$item = $this->items()->update( $manager, $order, [
			'address' => [['type' => 'payment', 'firstname' => 'new'], ['type' => 'payment', 'firstname' => 'second']],
		], 'order' );

		$list = $item->getAddress( 'payment' );
		$this->assertCount( 2, $list );
		$this->assertSame( '5', $list[1]->getId() );
		$this->assertSame( '7', $list[1]->getParentId() );
		$this->assertSame( 'new', $list[1]->getFirstname() );
		$this->assertSame( '', $list[1]->getLastname() );
		$this->assertNull( end( $list )->getId() );
		$this->assertSame( 'second', end( $list )->getFirstname() );
	}


	public function testSaveOrderAddressMixed() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$address = $manager->createAddress()->setId( '5' )->setParentId( '7' )->setFirstname( 'old' );

		foreach( [[0, 1], [1, 0]] as $order )
		{
			$entries = [['id' => '5', 'lastname' => 'updated'], ['type' => 'payment', 'firstname' => 'new']];
			$item = $this->items()->update( $manager, $this->order()->addAddress( $address, 'payment', 1 ), [
				'address' => [$entries[$order[0]], $entries[$order[1]]],
			], 'order' );

			$list = $item->getAddress( 'payment' );
			$this->assertCount( 2, $list );
			$this->assertSame( '5', $list[1]->getId() );
			$this->assertSame( 'old', $list[1]->getFirstname() );
			$this->assertSame( 'updated', $list[1]->getLastname() );
			$this->assertNull( end( $list )->getId() );
			$this->assertSame( 'new', end( $list )->getFirstname() );
		}
	}


	public function testSaveOrderAddressUnknownId() : void
	{
		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );

		$this->items()->update( \Aimeos\MShop::create( $this->context, 'order' ), $this->order(), [
			'address' => [['id' => '999', 'firstname' => 'x']],
		], 'order' );
	}


	public function testSaveOrderSubProduct() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$order = $this->order();
		$sub = $manager->createProduct()->setId( '3' )->setProductCode( 'U:TESTSUB' )->setQuantity( 1 );
		$order->getProducts()->first()->setProducts( [$sub] );

		$item = $this->items()->update( $manager, $order, [
			'product' => [['id' => '3', 'statusdelivery' => 4]],
		], 'order' );

		$this->assertSame( 4, $item->getProducts()->first()->getProducts()->first()->getStatusDelivery() );
	}


	public function testSaveOrderProductsLimited() : void
	{
		$order = $this->order();
		$product = $order->getProducts()->first();

		$item = $this->items()->update( \Aimeos\MShop::create( $this->context, 'order' ), $order, [
			'product' => [['id' => $product->getId(), 'statusdelivery' => 3, 'notes' => 'mcp', 'prodcode' => $product->getProductCode()]],
		], 'order' );

		$product = $item->getProducts()->first();
		$this->assertSame( 3, $product->getStatusDelivery() );
		$this->assertSame( 'mcp', $product->getNotes() );
	}


	public function testSaveOrderProductReadonlyForbidden() : void
	{
		$order = $this->order();

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );

		$this->items()->update( \Aimeos\MShop::create( $this->context, 'order' ), $order, [
			'product' => [['id' => $order->getProducts()->first()->getId(), 'statusdelivery' => 3, 'prodcode' => 'injected']],
		], 'order' );
	}


	public function testSaveOrderServiceReadonlyForbidden() : void
	{
		$order = $this->order();

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );

		$this->items()->update( \Aimeos\MShop::create( $this->context, 'order' ), $order, [
			'service' => [['id' => $order->getService( 'payment', 0 )->getId(), 'transaction' => [['status' => 6]]]],
		], 'order' );
	}


	public function testSaveOrderFetchedItemValid() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$order = $this->order();
		$sub = $manager->createProduct()->setId( '3' )->setProductCode( 'U:TESTSUB' )->setQuantity( 1 );
		$order->getProducts()->first()->setProducts( [$sub] );

		$entry = $this->items()->toArray( $order, 'order' );
		$entry['product'][0]['statusdelivery'] = 3;
		$entry['product'][0]['product'][0]['statusdelivery'] = 4;
		unset( $entry['id'] ); // unsaved order

		$errors = ( new \Aimeos\Admin\Mcp\Tool\Order\Save( $this->context ) )->schema()->validate( ['items' => [$entry]] );
		$this->assertEmpty( $errors, (string) json_encode( $errors ) );

		$item = $this->items()->update( $manager, $order, $entry, 'order' );
		$this->assertSame( 3, $item->getProducts()->first()->getStatusDelivery() );
		$this->assertSame( 4, $item->getProducts()->first()->getProducts()->first()->getStatusDelivery() );
	}


	public function testSaveOrderNewProductForbidden() : void
	{
		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );

		$this->items()->update( \Aimeos\MShop::create( $this->context, 'order' ), $this->order(), [
			'product' => [['prodcode' => 'injected', 'statusdelivery' => 3]],
		], 'order' );
	}


	public function testSaveOrderServicesLimited() : void
	{
		$order = $this->order();
		$service = $order->getService( 'payment', 0 );
		$code = $service->getCode();
		$num = count( $order->getServices()->flat( 1 ) );
		$txcount = count( $service->getTransactions() );

		$item = $this->items()->update( \Aimeos\MShop::create( $this->context, 'order' ), $order, [
			'service' => [[
				'id' => $service->getId(), 'code' => $code,
				'attribute' => [['code' => 'mcp', 'value' => 'test']],
			]],
		], 'order' );

		$service = $item->getService( 'payment', 0 );
		$this->assertSame( $num, count( $item->getServices()->flat( 1 ) ) );
		$this->assertSame( $code, $service->getCode() );
		$this->assertSame( $txcount, count( $service->getTransactions() ) );
		$this->assertSame( 1, count( $service->getAttributeItems() ) );
		$this->assertSame( 'test', $service->getAttribute( 'mcp', 'payment' ) );
	}


	public function testSaveOrderServiceAttributesMerged() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$order = $this->order();
		$service = $order->getService( 'payment', 0 );
		$service->setAttributeItems( [
			$manager->createServiceAttribute()->setId( '8' )->setType( 'payment' )->setCode( 'TRANSACTIONID' )->setValue( 'tx-1' ),
			$manager->createServiceAttribute()->setId( '9' )->setType( 'payment' )->setCode( 'note' )->setValue( 'old' ),
		] );

		$item = $this->items()->update( $manager, $order, [
			'service' => [[
				'id' => $service->getId(),
				'attribute' => [['code' => 'mcp', 'value' => 'test'], ['code' => 'note', 'value' => 'new']],
			]],
		], 'order' );

		$service = $item->getService( 'payment', 0 );
		$this->assertSame( 3, count( $service->getAttributeItems() ) );
		$this->assertSame( 'tx-1', $service->getAttribute( 'TRANSACTIONID', 'payment' ) );
		$this->assertSame( 'new', $service->getAttribute( 'note', 'payment' ) );
		$this->assertSame( 'test', $service->getAttribute( 'mcp', 'payment' ) );
		$this->assertSame( 0, count( $service->getAttributeItemsDeleted() ) );
	}


	public function testSaveOrderProductOutdatedMeta() : void
	{
		$order = $this->order();
		$product = $order->getProducts()->first();

		$item = $this->items()->update( \Aimeos\MShop::create( $this->context, 'order' ), $order, [
			'product' => [['id' => $product->getId(), 'notes' => 'mcp', 'mtime' => '2000-01-01 00:00:00', 'ctime' => null, 'editor' => 'other']],
		], 'order' );

		$this->assertSame( 'mcp', $item->getProducts()->first()->getNotes() );
	}


	public function testSaveOrderStatusOutdatedMeta() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$order = $this->order()->addStatus( $manager->createStatus()->setId( '7' )->setType( 'status-payment' )->setValue( '5' ) );

		$status = $this->items()->toArray( $order, 'order' )['status'];
		$this->assertNotEmpty( $status );

		foreach( $status as $idx => $entry ) {
			$status[$idx] = ['mtime' => '2000-01-01 00:00:00', 'ctime' => '2000-01-01 00:00:00', 'editor' => 'other'] + $entry;
		}

		$item = $this->items()->update( $manager, $order, ['status' => $status, 'comment' => 'mcp'], 'order' );
		$this->assertSame( 'mcp', $item->getComment() );
	}


	public function testSaveOrderCouponChangedForbidden() : void
	{
		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );

		$this->items()->update( \Aimeos\MShop::create( $this->context, 'order' ), $this->order(), [
			'coupon' => [['code' => 'MCP', 'productIds' => ['1']]],
		], 'order' );
	}


	public function testSaveOrderToolFetchedCoupon() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$couponManager = \Aimeos\MShop::create( $this->context, 'order/coupon' );
		$price = \Aimeos\MShop::create( $this->context, 'price' )->create()->setValue( '10.00' );
		$product = $manager->createProduct()->setProductCode( 'CNC' )->setQuantity( 1 )->setPrice( $price );
		$order = $manager->save( $manager->create()->off()->addProduct( $product ) );

		try
		{
			$prodId = $order->getProducts()->first()->getId();
			$couponManager->save( $couponManager->create()->setParentId( $order->getId() )->setCode( 'MCP' )->setProductId( $prodId ) );

			$fetched = $manager->get( $order->getId(), ['order/product', 'order/coupon'] );
			$entry = $this->items()->toArray( $fetched, 'order' );
			$entry['product'][0]['statusdelivery'] = 3;
			$this->assertSame( [['code' => 'MCP', 'productIds' => [$prodId]]], $entry['coupon'] );

			$tool = new \Aimeos\Admin\Mcp\Tool\Order\Save( $this->context );
			$this->assertEmpty( $errors = $tool->schema()->validate( ['items' => [$entry]] ), (string) json_encode( $errors ) );
			$tool->execute( ['items' => [$entry]] );

			$filter = $couponManager->filter()->add( 'order.coupon.parentid', '==', $order->getId() );
			$this->assertCount( 1, $couponManager->search( $filter ) );
			$this->assertSame( 3, $manager->get( $order->getId(), ['order/product'] )->getProducts()->first()->getStatusDelivery() );
		}
		finally
		{
			$manager->delete( $order->getId() );
		}
	}


	public function testSaveOrderAddressTypeChangeForbidden() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$order = $this->order()->addAddress( $manager->createAddress()->setId( '5' )->setLastName( 'Delivery' ), 'delivery' );

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );

		$this->items()->update( $manager, $order, ['address' => [['id' => '5', 'type' => 'payment', 'firstname' => 'A']]], 'order' );
	}


	public function testSaveOrderAllTypesReturned() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$order = $this->order()
			->addService( $manager->createService()->setId( '3' )->setCode( 'unitdeliverycode' )->setType( 'delivery' ), 'delivery' )
			->addAddress( $manager->createAddress()->setId( '4' )->setLastName( 'Payment' ), 'payment' )
			->addAddress( $manager->createAddress()->setId( '5' )->setLastName( 'Delivery' ), 'delivery' );

		$entry = $this->items()->toArray( $order, 'order' );
		$this->assertSame( ['4', '5'], array_column( $entry['address'], 'id' ) );
		$this->assertSame( ['2', '3'], array_column( $entry['service'], 'id' ) );

		$item = $this->items()->update( $manager, $order, [
			'address' => [['id' => '4', 'firstname' => 'A'], ['id' => '5', 'firstname' => 'B']],
			'service' => [['id' => '3', 'attribute' => [['code' => 'mcp', 'value' => '1']]]],
		], 'order' );

		$this->assertSame( 'A', current( $item->getAddress( 'payment' ) )->getFirstName() );
		$this->assertSame( 'B', current( $item->getAddress( 'delivery' ) )->getFirstName() );
		$this->assertSame( '1', $item->getService( 'delivery', 0 )->getAttribute( 'mcp', 'delivery' ) );
	}


	public function testSaveOrderStatusAddedMeanwhile() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$order = $this->order()
			->addStatus( $manager->createStatus()->setId( '7' )->setType( 'status-payment' )->setValue( '5' ) )
			->addStatus( $manager->createStatus()->setId( '8' )->setType( 'status-delivery' )->setValue( '1' ) );

		$status = $this->items()->toArray( $order, 'order' )['status'];
		$order->addStatus( $manager->createStatus()->setId( '9' )->setType( 'status-payment' )->setValue( '6' ) );

		$this->assertSame( ['7', '9', '8'], array_column( $this->items()->toArray( $order, 'order' )['status'], 'id' ) );

		$item = $this->items()->update( $manager, $order, ['status' => array_reverse( $status ), 'comment' => 'mcp'], 'order' );
		$this->assertSame( 'mcp', $item->getComment() );
	}


	public function testSaveOrderStatusChangedForbidden() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$order = $this->order()->addStatus( $manager->createStatus()->setId( '7' )->setType( 'status-payment' )->setValue( '5' ) );

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );

		$this->items()->update( $manager, $order, ['status' => [['id' => '7', 'type' => 'status-payment', 'value' => '6']]], 'order' );
	}


	public function testSaveOrderStatusUnknownForbidden() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$order = $this->order()->addStatus( $manager->createStatus()->setId( '7' )->setType( 'status-payment' )->setValue( '5' ) );

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );

		$this->items()->update( $manager, $order, ['status' => [['type' => 'status-payment', 'value' => '5']]], 'order' );
	}


	public function testSaveOrderToolFetchedStatus() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$price = \Aimeos\MShop::create( $this->context, 'price' )->create()->setValue( '10.00' );
		$product = $manager->createProduct()->setProductCode( 'CNC' )->setQuantity( 1 )->setPrice( $price );
		$status = $manager->createStatus()->setType( 'status-payment' )->setValue( '5' );
		$order = $manager->save( $manager->create()->off()->addProduct( $product )->addStatus( $status ) );

		try
		{
			$fetched = $manager->get( $order->getId(), ['order/product', 'order/status'] );
			$entry = $this->items()->toArray( $fetched, 'order' );
			$entry['product'][0]['statusdelivery'] = 3;
			$this->assertNotEmpty( $entry['status'] );

			$tool = new \Aimeos\Admin\Mcp\Tool\Order\Save( $this->context );
			$this->assertEmpty( $errors = $tool->schema()->validate( ['items' => [$entry]] ), (string) json_encode( $errors ) );
			$tool->execute( ['items' => [$entry]] );

			$saved = $manager->get( $order->getId(), ['order/product'] );
			$this->assertSame( 3, $saved->getProducts()->first()->getStatusDelivery() );
			$this->assertSame( '10.00', $saved->getPrice()->getValue() );
		}
		finally
		{
			$manager->delete( $order->getId() );
		}
	}


	public function testSaveOrderServiceAttributeWithAttributeId() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$order = $this->order();
		$service = $order->getService( 'payment', 0 );
		$service->setAttributeItems( [
			$manager->createServiceAttribute()->setId( '8' )->setType( 'payment' )->setCode( 'option' )->setAttributeId( '12' )->setValue( 'old' ),
		] );

		$item = $this->items()->update( $manager, $order, [
			'service' => [['id' => $service->getId(), 'attribute' => [['code' => 'option', 'value' => 'new']]]],
		], 'order' );

		$service = $item->getService( 'payment', 0 );
		$this->assertSame( 1, count( $service->getAttributeItems() ) );
		$this->assertSame( 'new', $service->getAttributeItems()->first()->getValue() );
		$this->assertSame( '12', $service->getAttributeItems()->first()->getAttributeId() );
	}


	public function testSaveOrderServiceAttributeRenameForbidden() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$order = $this->order();
		$service = $order->getService( 'payment', 0 );
		$service->setAttributeItems( [$manager->createServiceAttribute()->setId( '9' )->setType( 'payment' )->setCode( 'note' )] );

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );

		$this->items()->update( $manager, $order, [
			'service' => [['id' => $service->getId(), 'attribute' => [
				['code' => 'mcp', 'value' => '1'], ['id' => '9', 'code' => 'renamed'], ['code' => 'note', 'value' => 'y'],
			]]],
		], 'order' );
	}


	public function testSaveOrderServiceAttributeById() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$order = $this->order();
		$service = $order->getService( 'payment', 0 );
		$service->setAttributeItems( [$manager->createServiceAttribute()->setId( '9' )->setType( 'payment' )->setCode( 'note' )->setValue( 'x' )] );

		$item = $this->items()->update( $manager, $order, [
			'service' => [['id' => $service->getId(), 'attribute' => [['id' => '9', 'type' => 'payment', 'code' => 'note', 'value' => 'y']]]],
		], 'order' );

		$this->assertSame( 'y', $item->getService( 'payment', 0 )->getAttribute( 'note', 'payment' ) );
	}


	public function testSaveOrderNewServiceForbidden() : void
	{
		$this->expectException( Exception::class );
		$this->expectExceptionCode( 400 );

		$this->items()->update( \Aimeos\MShop::create( $this->context, 'order' ), $this->order(), [
			'service' => [['type' => 'payment', 'code' => 'paypalexpress']],
		], 'order' );
	}


	public function testSaveOrderToolLoadsServices() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$service = $manager->createService()->setCode( 'unitpaymentcode' )->setType( 'payment' );
		$order = $manager->save( $manager->create()->addService( $service, 'payment' ) );
		$serviceId = $order->getService( 'payment', 0 )->getId();

		try
		{
			$result = ( new \Aimeos\Admin\Mcp\Tool\Order\Save( $this->context ) )->execute( ['items' => [[
				'id' => $order->getId(),
				'service' => [['id' => $serviceId, 'attribute' => [['code' => 'mcp', 'value' => 'test']]]],
			]]] );

			$saved = $manager->get( $order->getId(), ['order/service'] )->getService( 'payment', 0 );
			$this->assertSame( $order->getId(), $result['items'][0]['id'] );
			$this->assertSame( $serviceId, $saved->getId() );
			$this->assertSame( 'test', $saved->getAttribute( 'mcp', 'payment' ) );
		}
		finally
		{
			$manager->delete( $order->getId() );
		}
	}


	public function testSaveOrderToolAddressKeepsPrice() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$price = \Aimeos\MShop::create( $this->context, 'price' )->create()->setValue( '10.00' );
		$product = $manager->createProduct()->setProductCode( 'CNC' )->setQuantity( 1 )->setPrice( $price );
		$order = $manager->save( $manager->create()->off()->addProduct( $product ) );

		try
		{
			( new \Aimeos\Admin\Mcp\Tool\Order\Save( $this->context ) )->execute( ['items' => [[
				'id' => $order->getId(),
				'address' => [['type' => 'delivery', 'firstname' => 'mcp']],
			]]] );

			$saved = $manager->get( $order->getId(), ['order/address'] );
			$this->assertSame( '10.00', $saved->getPrice()->getValue() );
			$this->assertSame( 'mcp', current( $saved->getAddress( 'delivery' ) )->getFirstname() );
		}
		finally
		{
			$manager->delete( $order->getId() );
		}
	}


	public function testSaveMediaOfRestrictedDomainForbidden() : void
	{
		$this->assertMediaDenied( 'service', Exception::class, 403, self::MEDIA_SAVE_DENIED,
			fn( $id ) => ( new \Aimeos\Admin\Mcp\Tool\Media\Save( $this->context ) )
				->execute( ['items' => [['id' => $id, 'label' => 'Changed by editor']]] ) );
	}


	public function testSaveMediaIntoRestrictedDomainForbidden() : void
	{
		$this->assertMediaDenied( 'product', Exception::class, 403, self::MEDIA_SAVE_DENIED,
			fn( $id ) => ( new \Aimeos\Admin\Mcp\Tool\Media\Save( $this->context ) )
				->execute( ['items' => [['id' => $id, 'domain' => 'service', 'label' => 'Changed by editor']]] ) );
	}


	public function testDeleteMediaOfRestrictedDomainForbidden() : void
	{
		$msg = 'Permission "save" for "plugin" is required to change "media" items';
		$this->assertMediaDenied( 'plugin', Exception::class, 403, $msg,
			fn( $id ) => ( new \Aimeos\Admin\Mcp\Tool\Media\Delete( $this->context ) )->execute( ['ids' => [$id]] ) );
	}


	public function testSaveMediaOfAllowedDomain() : void
	{
		$this->withMedia( ['product'], function( $id ) {
			$result = ( new \Aimeos\Admin\Mcp\Tool\Media\Save( $this->context ) )
				->execute( ['items' => [['id' => $id, 'label' => 'Changed by editor']]] );

			$this->assertSame( 'Changed by editor', $result['items'][0]['label'] );
		} );
	}


	public function testDeleteMediaOfAllowedDomain() : void
	{
		$this->withMedia( ['product'], function( $id ) {
			// missing items are skipped
			$result = ( new \Aimeos\Admin\Mcp\Tool\Media\Delete( $this->context ) )
				->execute( ['ids' => [$id, '2147483647']] );

			$this->assertSame( [$id], $result['ids'] );
		} );
	}


	public function testGetMediaOfRestrictedDomainNotFound() : void
	{
		// items the user isn't allowed to retrieve are reported like missing items
		$msg = 'Item with ID "%s" in "media.id" not found';
		$this->assertMediaDenied( 'service', \Aimeos\MShop\Exception::class, 404, $msg,
			fn( $id ) => ( new \Aimeos\Admin\Mcp\Tool\Media\Get( $this->context ) )->execute( ['id' => $id] ) );
	}


	public function testSearchMediaOfRestrictedDomainExcluded() : void
	{
		$this->withMedia( ['product', 'service'], function( $productId, $serviceId ) {
			$args = ['filter' => ['==' => ['media.label' => $this->mediaLabel()]]];
			$ids = fn( array $result ) => array_map( 'strval', array_column( $result['items'], 'id' ) );

			$result = ( new \Aimeos\Admin\Mcp\Tool\Media\Search( $this->context ) )->execute( $args );

			$this->assertSame( 1, $result['total'] );
			$this->assertSame( [$productId], $ids( $result ) );

			$this->setAccess( ['admin'] );
			$result = ( new \Aimeos\Admin\Mcp\Tool\Media\Search( $this->context ) )->execute( $args );

			$this->assertSame( 2, $result['total'] );
			$this->assertEqualsCanonicalizing( [$productId, $serviceId], $ids( $result ) );
		} );
	}


	public function testSaveProductWithExistingNestedPluginForbidden() : void
	{
		$this->assertNestedPluginForbidden( ['id' => '2147483647'] );
	}


	public function testSaveProductWithNewNestedPluginForbidden() : void
	{
		$this->assertNestedPluginForbidden( [] );
	}


	public function testSaveProductWithMixedNestedPluginPermissionsForbidden() : void
	{
		$config = $this->context->config();
		$get = $config->get( 'admin/mcp/resource/plugin/get' );
		$save = $config->get( 'admin/mcp/resource/plugin/save' );

		$config->set( 'admin/mcp/resource/plugin/get', ['admin'] );
		$config->set( 'admin/mcp/resource/plugin/save', ['editor'] );

		try {
			$this->assertNestedPluginForbidden( [], [['type' => 'default', 'refid' => '2147483647']] );
		} finally {
			$config->set( 'admin/mcp/resource/plugin/get', $get );
			$config->set( 'admin/mcp/resource/plugin/save', $save );
		}
	}


	public function testSaveProductDeletingNestedGroupForbidden() : void
	{
		$this->setAccess( ['admin'] );

		$code = 'test-mcp-' . bin2hex( random_bytes( 4 ) );
		$groupCode = 'test-mcp-group-' . bin2hex( random_bytes( 4 ) );
		$productId = '';

		try
		{
			$result = ( new Save( $this->context ) )->execute( ['items' => [[
				'code' => $code,
				'label' => 'Unauthorized nested deletion test',
				'type' => 'default',
				'status' => 0,
				'lists' => ['group' => [[
					'type' => 'default',
					'item' => ['code' => $groupCode, 'label' => 'Temporary MCP group'],
				]]],
			]]] );
			$productId = (string) ( $result['items'][0]['id'] ?? '' );

			$this->setAccess( ['editor'] );
			$this->expectException( Exception::class );
			$this->expectExceptionCode( 403 );

			( new Save( $this->context ) )->execute( ['items' => [[
				'id' => $productId,
				'lists' => ['group' => []],
			]]] );
		}
		finally
		{
			$this->setAccess( ['admin'] );

			if( $productId !== '' )
			{
				( new Save( $this->context ) )->execute( ['items' => [[
					'id' => $productId,
					'lists' => ['group' => []],
				]]] );
				( new Delete( $this->context ) )->execute( ['ids' => [$productId]] );
			}

			$this->deleteBy( 'group', 'group.code', $groupCode );
		}
	}


	public function testCustomerServiceListsRequireServicePermission() : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'customer' );
		$serviceManager = \Aimeos\MShop::create( $this->context, 'service' );
		$serviceCode = 'test-mcp-' . bin2hex( random_bytes( 4 ) );
		$serviceId = $serviceManager->save( $serviceManager->create()->setType( 'payment' )->setCode( $serviceCode )
			->setLabel( 'MCP service' )->setProvider( 'Standard' )->setConfig( ['apikey' => 'mcp-secret-key'] ) )->getId();

		$listItem = $manager->createListItem()->setType( 'default' )->setRefId( $serviceId )
			->setConfigValue( 'repay', ['token' => 'mcp-secret-token'] );
		$item = $manager->create()->setCode( 'test-mcp-' . bin2hex( random_bytes( 4 ) ) )
			->setLabel( 'MCP service list test' )->addListItem( 'service', $listItem );
		$id = '';

		try
		{
			$id = (string) $manager->save( $item )->getId();
			$args = ['id' => $id, 'include' => ['service']];
			$filter = ['filter' => ['==' => ['customer.id' => $id]], 'include' => ['service']];

			$this->setAccess( ['editor'] );

			$item = ( new CustomerGet( $this->context ) )->execute( $args )['item'];
			$items = ( new CustomerSearch( $this->context ) )->execute( $filter )['items'];

			$this->assertArrayNotHasKey( 'service', $item['lists'] );
			$this->assertArrayNotHasKey( 'service', $items[0]['lists'] );
			$this->assertStringNotContainsString( 'mcp-secret', (string) json_encode( [$item, $items] ) );

			$this->setAccess( ['admin'] );

			$item = ( new CustomerGet( $this->context ) )->execute( $args )['item'];

			$this->assertSame( 'mcp-secret-token', $item['lists']['service'][0]['config']['repay']['token'] );
			$this->assertSame( 'mcp-secret-key', $item['lists']['service'][0]['item']['config']['apikey'] );
		}
		finally
		{
			$id !== '' && $manager->delete( $id );
			$serviceManager->delete( $serviceId );
		}
	}


	public function testProductStockRequiresStockPermission() : void
	{
		$config = $this->context->config();
		$get = $config->get( 'admin/mcp/resource/stock/get' );
		$config->set( 'admin/mcp/resource/stock/get', ['admin'] );

		$manager = \Aimeos\MShop::create( $this->context, 'product' );
		$stockManager = \Aimeos\MShop::create( $this->context, 'stock' );
		$code = 'test-mcp-' . bin2hex( random_bytes( 4 ) );
		$id = (string) $manager->save( $manager->create()->setCode( $code )->setLabel( 'MCP stock test' ) )->getId();

		try
		{
			$stockManager->save( $stockManager->create()->setProductId( $id )->setType( 'default' )->setStockLevel( 10 ) );
			$args = ['code' => $code, 'include' => ['stock']];

			$this->setAccess( ['editor'] );
			$this->assertArrayNotHasKey( 'stock', ( new Find( $this->context ) )->execute( $args )['item'] );

			$this->setAccess( ['admin'] );
			$this->assertSame( 10, ( new Find( $this->context ) )->execute( $args )['item']['stock'][0]['stocklevel'] );
		}
		finally
		{
			$config->set( 'admin/mcp/resource/stock/get', $get );
			$stockManager->delete( $stockManager->search( $stockManager->filter()->add( 'stock.productid', '==', $id ) )->keys() );
			$manager->delete( $id );
		}
	}


	/**
	 * @param array<string, mixed> $plugin
	 * @param array<int, array<string, mixed>> $lists
	 */
	private function assertNestedPluginForbidden( array $plugin, array $lists = [] ) : void
	{
		$this->setAccess( ['editor'] );

		$code = 'test-mcp-' . bin2hex( random_bytes( 4 ) );
		$label = 'Unauthorized MCP plugin ' . bin2hex( random_bytes( 4 ) );
		$this->expectException( Exception::class );
		$this->expectExceptionCode( 403 );

		try
		{
			( new Save( $this->context ) )->execute( ['items' => [[
				'code' => $code,
				'label' => 'Unauthorized nested plugin test',
				'type' => 'default',
				'status' => 0,
				'lists' => ['plugin' => array_merge( [[
					'type' => 'default',
					'item' => $plugin + [
						'type' => 'order',
						'label' => $label,
						'provider' => 'ProductLimit',
						'status' => 0,
						'config' => ['single-number-max' => 1],
					],
				]], $lists )],
			]]] );
		}
		finally
		{
			$this->deleteBy( 'product', 'product.code', $code );
			$this->deleteBy( 'plugin', 'plugin.label', $label );
		}
	}


	/**
	 * Asserts that the editor can't access the media item of the domain and that it's unchanged
	 *
	 * @param string $domain Domain owning the media item
	 * @param class-string<\Exception> $class Expected exception class
	 * @param int $code Expected error code
	 * @param string $message Expected error message, "%s" is replaced by the item ID
	 * @param \Closure $call Function executing the tool with the item ID
	 */
	private function assertMediaDenied( string $domain, string $class, int $code, string $message, \Closure $call ) : void
	{
		$this->withMedia( [$domain], function( $id ) use ( $domain, $class, $code, $message, $call ) {
			try
			{
				$call( $id );
			}
			catch( \Exception $e )
			{
				// exact class because the policy exceptions of the managers extend \Aimeos\MShop\Exception too
				$this->assertSame( $class, get_class( $e ) );
				$this->assertSame( $code, $e->getCode() );
				$this->assertSame( sprintf( $message, $id ), $e->getMessage() );

				$item = \Aimeos\MShop::create( $this->context, 'media' )->get( $id );
				$this->assertSame( $domain, $item->getDomain() );
				$this->assertSame( $this->mediaLabel(), $item->getLabel() );
				return;
			}

			// outside of the try block because the failure is an exception too
			$this->fail( sprintf( 'Expected %s with code %d', $class, $code ) );
		} );
	}


	/**
	 * Executes the function as editor with the IDs of new media items of the domains
	 *
	 * @param string[] $domains Domains owning the media items, one item per domain
	 * @param \Closure $call Function executing the tools with the item IDs in the order of the domains
	 */
	private function withMedia( array $domains, \Closure $call ) : void
	{
		$manager = \Aimeos\MShop::create( $this->context, 'media' );
		$ids = [];

		try
		{
			foreach( $domains as $domain ) {
				$ids[] = (string) $manager->save( $manager->create()->setDomain( $domain )->setType( 'default' )
					->setLabel( $this->mediaLabel() )->setUrl( 'test.jpg' ) )->getId();
			}

			$this->setAccess( ['editor'] );
			$call( ...$ids );
		}
		finally
		{
			$this->setAccess( ['admin'] );
			$manager->delete( $ids );
		}
	}


	/**
	 * Returns the label of the media items created by the test
	 *
	 * The label is unique so items left over by aborted test runs aren't found.
	 *
	 * @return string Media label
	 */
	private function mediaLabel() : string
	{
		return $this->mediaLabel ??= 'MCP test media ' . bin2hex( random_bytes( 4 ) );
	}


	private function deleteBy( string $domain, string $key, string $value ) : void
	{
		$manager = \Aimeos\MShop::create( $this->context, $domain );
		$filter = $manager->filter()->add( $key, '==', $value );
		$manager->delete( $manager->search( $filter )->keys() );
	}


	private function items() : \Aimeos\Admin\Mcp\Items
	{
		return new \Aimeos\Admin\Mcp\Items( $this->context );
	}


	private function order() : \Aimeos\MShop\Order\Item\Iface
	{
		$manager = \Aimeos\MShop::create( $this->context, 'order' );
		$price = \Aimeos\MShop::create( $this->context, 'price' )->create()->setValue( '10.00' );

		$product = $manager->createProduct()->setId( '1' )->setProductCode( 'CNC' )->setQuantity( 1 )->setPrice( $price );
		$service = $manager->createService()->setId( '2' )->setCode( 'unitpaymentcode' )->setType( 'payment' )
			->setTransactions( [$manager->createServiceTransaction()->setType( 'payment' )->setStatus( 5 )] );

		// the order plugins would check the products, e.g. their stock levels
		return $manager->create()->off()->addProduct( $product )->addService( $service, 'payment' );
	}


	/**
	 * @param array<int, string> $groups
	 */
	private function setAccess( array $groups ) : void
	{
		$view = new \Aimeos\Base\View\Standard();
		$view->addHelper( 'access', new \Aimeos\Base\View\Helper\Access\Standard( $view, $groups ) );
		$this->context->setView( $view );
	}
}
