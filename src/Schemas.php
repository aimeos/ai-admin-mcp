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
			$type = $code === 'id' ? Schema::string() : $this->type( (string) $attribute->getType() );
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

		if( $item instanceof \Aimeos\MShop\Media\Item\Iface ) {
			$fields['file'] = $this->binary();
			$fields['filePreview'] = $this->binary();
		}

		if( $item instanceof \Aimeos\MShop\Order\Item\Iface )
		{
			$fields['address'] = Schema::array()->items( $this->item( 'order/address' ) );
			$fields['product'] = Schema::array()->items( $this->orderProduct() );
			$fields['service'] = Schema::array()->items( $this->orderService() );
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


	private function orderProduct() : ObjectType
	{
		$fields = $this->fields( \Aimeos\MShop::create( $this->context, 'order/product' )->getSearchAttributes( false ) );
		$fields['attribute'] = Schema::array()->items( $this->item( 'order/product/attribute' ) );
		$fields['product'] = Schema::array()->items( Schema::object(
			$this->fields( \Aimeos\MShop::create( $this->context, 'order/product' )->getSearchAttributes( false ) ) + [
				'attribute' => Schema::array()->items( $this->item( 'order/product/attribute' ) ),
			]
		)->withoutAdditionalProperties() );

		return Schema::object( $fields )->withoutAdditionalProperties();
	}


	private function orderService() : ObjectType
	{
		$fields = $this->fields( \Aimeos\MShop::create( $this->context, 'order/service' )->getSearchAttributes( false ) );
		$fields['attribute'] = Schema::array()->items( $this->item( 'order/service/attribute' ) );
		$fields['transaction'] = Schema::array()->items( $this->item( 'order/service/transaction' ) );

		return Schema::object( $fields )->withoutAdditionalProperties();
	}
}
