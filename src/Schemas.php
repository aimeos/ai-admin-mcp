<?php

namespace Aimeos\Admin\Mcp;

use Aimeos\Prisma\Schema\Schema;
use Aimeos\Prisma\Schema\Types\ObjectType;
use Aimeos\Prisma\Schema\Types\Type;


class Schemas
{
	public function __construct( private \Aimeos\MShop\ContextIface $context )
	{
	}


	public function binary() : ObjectType
	{
		return Schema::object( [
			'base64' => Schema::string()->required()->description( 'Base64-encoded file content' ),
			'mimeType' => Schema::string()->description( 'Media type of the file' ),
			'fileName' => Schema::string()->required()->description( 'Original file name' ),
		] )->withoutAdditionalProperties();
	}


	/**
	 * @param array<int, \Aimeos\Base\Criteria\Attribute\Iface> $attributes
	 * @return array<string, Type>
	 */
	public function fields( array $attributes ) : array
	{
		$fields = [];

		foreach( $attributes as $attribute )
		{
			$attributeCode = (string) $attribute->getCode();

			if( str_contains( $attributeCode, ':' ) ) {
				continue;
			}

			$pos = strrpos( $attributeCode, '.' );
			$code = substr( $attributeCode, $pos === false ? 0 : $pos + 1 );
			// accept the values as returned by the items, e.g. parent IDs as strings and tax flags as booleans
			$type = match( true ) {
				$code === 'id' => Schema::string(),
				$code === 'taxflag' => Schema::anyOf( [Schema::boolean(), Schema::integer()] ),
				str_ends_with( $code, 'id' ) => Schema::anyOf( [Schema::string(), Schema::integer()] ),
				default => $this->type( (string) $attribute->getType() ),
			};
			$fields[$code] = $type->description( (string) $attribute->getLabel() );
		}

		return $fields;
	}


	public function item( string $domain ) : ObjectType
	{
		$manager = \Aimeos\MShop::create( $this->context, $domain );
		$fields = $this->fields( $manager->getSearchAttributes( false ) );
		$item = $manager->create();

		if( $item instanceof \Aimeos\MShop\Customer\Item\Iface ) {
			$fields['groups'] = Schema::array()->items( Schema::string() )->description( 'Assigned customer group IDs' );
		}

		if( $item instanceof \Aimeos\MShop\Common\Item\AddressRef\Iface ) {
			$fields['address'] = Schema::array()->items( $this->item( $domain . '/address' ) );
		}

		if( $item instanceof \Aimeos\MShop\Common\Item\ListsRef\Iface ) {
			$fields['lists'] = Schema::object()->description( 'Referenced items grouped by domain' );
		}

		if( $item instanceof \Aimeos\MShop\Common\Item\PropertyRef\Iface ) {
			$fields['property'] = Schema::array()->items( $this->item( $domain . '/property' ) );
		}

		if( $item instanceof \Aimeos\MShop\Product\Item\Iface ) {
			$fields['stock'] = Schema::array()->items( $this->item( 'stock' ) );
		}

		if( $item instanceof \Aimeos\MShop\Media\Item\Iface )
		{
			// local paths and file systems are only set by uploads, unchanged values are accepted so fetched items can be saved again
			if( isset( $fields['filesystem'] ) ) {
				$fields['filesystem'] = $fields['filesystem']->description( 'File system of the media files, can not be changed' );
			}

			foreach( ['url', 'preview', 'previews'] as $key )
			{
				if( isset( $fields[$key] ) ) {
					$fields[$key] = $fields[$key]->description( 'Absolute http(s) URLs of remote files only, use file/filePreview for local files' );
				}
			}

			$fields['file'] = $this->binary();
			$fields['filePreview'] = $this->binary();
		}

		if( $item instanceof \Aimeos\MShop\Order\Item\Iface )
		{
			$fields['address'] = Schema::array()->items( $this->item( 'order/address' ) )
				->description( 'Updates addresses by ID or replaces the ones of the same type without ID, the type of addresses with ID can not be changed' );
			$fields['product'] = Schema::array()->items( $this->orderProduct() );
			$fields['service'] = Schema::array()->items( $this->orderService() );
			$fields['status'] = Schema::array()->items( $this->item( 'order/status' ) )->description( 'Read-only, statuses are referenced by ID' );
			$fields['coupon'] = Schema::array()->items( Schema::object( [
				'code' => Schema::string(),
				'productIds' => Schema::array()->items( Schema::string() ),
			] )->withoutAdditionalProperties() )->description( 'Read-only' );
		}

		return Schema::object( $fields )->withoutAdditionalProperties();
	}


	public function json() : Type
	{
		return Schema::anyOf( [
			Schema::object(),
			Schema::array(),
			Schema::string(),
			Schema::number(),
			Schema::boolean(),
		] );
	}


	public function type( string $type ) : Type
	{
		return match( $type ) {
			'bool', 'boolean' => Schema::boolean(),
			'float' => Schema::number(),
			'int', 'integer' => Schema::integer(),
			'json' => $this->json(),
			default => Schema::string(),
		};
	}


	private function orderProduct( bool $sub = false ) : ObjectType
	{
		$fields = $this->fields( \Aimeos\MShop::create( $this->context, 'order/product' )->getSearchAttributes( false ) );
		$fields['id'] = Schema::string()->required()->description( 'ID of the existing ordered product' );
		$fields['attribute'] = Schema::array()->items( $this->item( 'order/product/attribute' ) )->description( 'Read-only' );

		$fields['product'] = $sub
			? Schema::array()->max( 0 )->description( 'Sub-products of bundles have no sub-products' )
			: Schema::array()->items( $this->orderProduct( true ) )->description( 'Sub-products of bundles' );

		return Schema::object( $fields )->withoutAdditionalProperties()->description( sprintf(
			'Only %1$s of existing ordered products can be changed, other fields must be unchanged',
			join( ', ', \Aimeos\Admin\Mcp\Items::ORDER_PRODUCT_FIELDS )
		) );
	}


	private function orderService() : ObjectType
	{
		$fields = $this->fields( \Aimeos\MShop::create( $this->context, 'order/service' )->getSearchAttributes( false ) );
		$fields['id'] = Schema::string()->required()->description( 'ID of the existing order service' );
		$fields['attribute'] = Schema::array()->items( $this->item( 'order/service/attribute' ) )
			->description( 'Updates or adds attributes of the order service, existing attributes are referenced by ID or by type and code, type and code of existing attributes can not be changed, attributes are never removed' );
		$fields['transaction'] = Schema::array()->items( $this->item( 'order/service/transaction' ) )->description( 'Read-only' );

		return Schema::object( $fields )->withoutAdditionalProperties()
			->description( 'Only the attributes of existing order services can be changed, other fields must be unchanged' );
	}
}
