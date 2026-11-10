<?php

namespace Aimeos\Admin\Mcp\Tests;

use Aimeos\Admin\Mcp\Exception;


class ToolTest extends \PHPUnit\Framework\TestCase
{
	public function testAuthorize() : void
	{
		// no database required, the permissions are only checked by the policy
		$context = $this->withDenied( new \Aimeos\MShop\Context(), 'service', 'save' );
		$tool = $this->tool( $context, fn( $tool ) => $tool->authorizeFor( 'service', 'save' ) );

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 403 );
		$this->expectExceptionMessageMatches( '/^Action "save" is not allowed for "service"$/' );

		$tool->execute( [] );
	}


	public function testAuthorizeItems() : void
	{
		// the items use the policy of the context like the tool, also for changing references
		$entry = ['lists' => ['service' => [['refid' => '1']]]];
		$context = $this->withDenied( \TestHelper::context(), 'service', 'get' );
		$tool = $this->tool( $context, fn( $tool ) => $tool->update( $entry ) );

		$this->expectException( Exception::class );
		$this->expectExceptionCode( 403 );
		$this->expectExceptionMessageMatches( '/^Action "get" is not allowed for "service"$/' );

		$tool->execute( [] );
	}


	/**
	 * Sets a policy which allows all actions except the denied one
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 * @param string $resource Denied resource, e.g. "service"
	 * @param string $action Denied action, e.g. "save"
	 * @return \Aimeos\MShop\ContextIface Context object with the policy
	 */
	private function withDenied( \Aimeos\MShop\ContextIface $context, string $resource, string $action ) : \Aimeos\MShop\ContextIface
	{
		return $context->setPolicy( new class( [$resource, $action] ) implements \Aimeos\MShop\PolicyIface
		{
			public function __construct( private array $denied )
			{
			}


			public function allowed( \Aimeos\MShop\ContextIface $context, string $resource, string $action ) : ?bool
			{
				return [$resource, $action] !== $this->denied;
			}
		} );
	}


	/**
	 * Returns a tool which runs the passed function
	 *
	 * @param \Aimeos\MShop\ContextIface $context Context object
	 * @param \Closure $run Function executed by the tool
	 */
	private function tool( \Aimeos\MShop\ContextIface $context, \Closure $run ) : \Aimeos\Admin\Mcp\Tool
	{
		return new class( $context, $run ) extends \Aimeos\Admin\Mcp\Tool
		{
			protected const ACTION = 'get';
			protected const DOMAIN = 'product';


			public function __construct( \Aimeos\MShop\ContextIface $context, private \Closure $run )
			{
				parent::__construct( $context );
			}


			public function authorizeFor( string $domain, string $permission ) : void
			{
				$this->authorize( $domain, $permission );
			}


			public function description() : string
			{
				return 'Test tool';
			}


			public function schema() : \Aimeos\Prisma\Schema\Schema
			{
				return $this->objectSchema( [] );
			}


			public function update( array $entry ) : void
			{
				$this->items()->update( $this->manager(), $this->manager()->create(), $entry, 'product' );
			}


			protected function run( array $arguments ) : array
			{
				( $this->run )( $this );
				return [];
			}
		};
	}
}
