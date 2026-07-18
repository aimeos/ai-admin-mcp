<?php

namespace Aimeos\Admin\Mcp\Tool\Action;

use Aimeos\Prisma\Schema\Schema;


abstract class Move extends Base
{
	protected const ACTION = 'move';
	protected const PERMISSION = 'save';


	public function description() : string
	{
		return sprintf( 'Moves an Aimeos %s node within its tree.', str_replace( '/', ' ', $this->domain() ) );
	}


	public function schema() : Schema
	{
		return $this->objectSchema( [
			'id' => Schema::string()->required()->description( 'Node ID' ),
			'parentId' => Schema::string()->description( 'Current parent node ID' ),
			'targetId' => Schema::string()->description( 'New parent node ID' ),
			'refId' => Schema::string()->description( 'Sibling node ID to insert before' ),
		] );
	}


	protected function run( array $arguments ) : array
	{
		$id = (string) $arguments['id'];
		$this->manager()->move( // @phpstan-ignore method.notFound
			$id,
			isset( $arguments['parentId'] ) ? (string) $arguments['parentId'] : null,
			isset( $arguments['targetId'] ) ? (string) $arguments['targetId'] : null,
			isset( $arguments['refId'] ) ? (string) $arguments['refId'] : null
		);

		return ['id' => $id];
	}
}
