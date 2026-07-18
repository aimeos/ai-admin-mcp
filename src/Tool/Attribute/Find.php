<?php
namespace Aimeos\Admin\Mcp\Tool\Attribute;
use Aimeos\Admin\Mcp\Exception;
use Aimeos\MShop\Common\Item\Iface;
use Aimeos\Prisma\Schema\Schema;
final class Find extends \Aimeos\Admin\Mcp\Tool\Action\Find
{
	protected function additionalProperties() : array
	{
		return [
			'domain' => Schema::string()->required()->description( 'Attribute domain' ),
			'type' => Schema::string()->required()->description( 'Attribute type' ),
		];
	}
	/**
	 * @param array<string, mixed> $arguments
	 */
	protected function find( array $arguments ) : Iface
	{
		$manager = $this->manager();

		if( !method_exists( $manager, 'find' ) ) {
			throw new Exception( 'Attribute manager does not support find()', 500 );
		}

		return $manager->find( (string) $arguments['code'], (array) ( $arguments['include'] ?? [] ),
			(string) $arguments['domain'], (string) $arguments['type'] );
	}
}
