<?php

namespace Aimeos\Admin\Mcp\Tool\Action;

use Aimeos\Admin\Mcp\Exception;
use Aimeos\MShop\Common\Item\Iface as ItemIface;
use Aimeos\Prisma\Schema\Schema;
use Aimeos\Prisma\Schema\Types\Type;


abstract class Find extends Base
{
	protected const ACTION = 'find';
	protected const PERMISSION = 'get';


	public function description() : string
	{
		return sprintf( 'Returns one Aimeos %s item by code.', str_replace( '/', ' ', $this->domain() ) );
	}


	public function schema() : Schema
	{
		return $this->objectSchema( [
			'code' => Schema::string()->required()->description( 'Unique item code' ),
			'include' => Schema::array()->items( Schema::string() )->default( [] )->description( 'Related domains to load' ),
		] + $this->additionalProperties() );
	}


	/**
	 * @return array<string, Type>
	 */
	protected function additionalProperties() : array
	{
		return [];
	}


	/**
	 * @param array<string, mixed> $arguments
	 */
	protected function find( array $arguments ) : ItemIface
	{
		$manager = $this->manager();

		if( !method_exists( $manager, 'find' ) ) {
			throw new Exception( sprintf( 'Manager for "%s" does not support find()', $this->domain() ), 500 );
		}

		return $manager->find( (string) $arguments['code'], (array) ( $arguments['include'] ?? [] ) );
	}


	protected function run( array $arguments ) : array
	{
		return ['item' => $this->items()->toArray( $this->filterItem( $this->find( $arguments ) ), $this->resultDomain() )];
	}
}
