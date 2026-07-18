<?php

namespace Aimeos\Admin\Mcp\Tool\Action;

use Aimeos\Prisma\Schema\Schema;


abstract class Config extends Base
{
	protected const ACTION = 'config';
	protected const PERMISSION = 'get';


	public function description() : string
	{
		return sprintf( 'Returns backend configuration fields for an Aimeos %s provider.', str_replace( '/', ' ', $this->domain() ) );
	}


	public function schema() : Schema
	{
		return $this->objectSchema( [
			'provider' => Schema::string()->required()->description( 'Provider and decorators separated by commas' ),
			'type' => Schema::string()->description( 'Provider type where required' ),
		] );
	}


	protected function run( array $arguments ) : array
	{
		$manager = $this->manager();
		$item = $manager->create()->setProvider( (string) $arguments['provider'] ); // @phpstan-ignore method.notFound
		$config = $manager->getProvider( $item, (string) ( $arguments['type'] ?? '' ) )->getConfigBE(); // @phpstan-ignore method.notFound
		$result = [];

		foreach( $config as $entry ) {
			$result[] = [
				'code' => $entry->getCode(),
				'label' => $entry->getLabel(),
				'type' => $entry->getType(),
				'required' => $entry->isRequired(),
				'default' => $entry->getDefault(),
			];
		}

		return ['config' => $result];
	}
}
