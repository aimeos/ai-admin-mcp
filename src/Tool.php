<?php

namespace Aimeos\Admin\Mcp;

use Aimeos\Prisma\Schema\Schema;


abstract class Tool
{
	protected const ACTION = '';
	protected const DOMAIN = '';
	protected const PERMISSION = '';

	private ?Items $items = null;
	private ?Schemas $schemas = null;


	public function __construct( protected \Aimeos\MShop\ContextIface $context )
	{
	}


	/**
	 * Returns MCP tool behavior hints.
	 *
	 * @return array<string, bool>
	 */
	public function annotations() : array
	{
		$readonly = in_array( $this->action(), ['aggregate', 'config', 'find', 'get', 'path', 'search', 'search-tree', 'tree'], true );

		return [
			'readOnlyHint' => $readonly,
			'destructiveHint' => $this->action() === 'delete',
			'idempotentHint' => $readonly || $this->action() === 'delete',
			'openWorldHint' => false,
		];
	}


	abstract public function description() : string;


	/**
	 * Validates, authorizes and runs the tool.
	 *
	 * @param array<string, mixed> $arguments Tool arguments
	 * @return array<string, mixed> Structured tool result
	 */
	final public function execute( array $arguments ) : array
	{
		if( $errors = $this->schema()->validate( $arguments ) ) {
			throw new Exception( 'Invalid arguments: ' . implode( '; ', $errors ), 400, ['errors' => $errors] );
		}

		$this->authorize();

		return $this->run( $arguments );
	}


	final public function name() : string
	{
		return str_replace( '/', '-', $this->domain() ) . '-' . $this->action();
	}


	abstract public function schema() : Schema;


	protected function action() : string
	{
		return static::ACTION;
	}


	protected function authorize() : void
	{
		$groups = (array) $this->context->config()->get(
			'admin/mcp/resource/' . $this->domain() . '/' . $this->permission(),
			[]
		);

		if( $this->context->view()->access( $groups ) !== true ) {
			throw new Exception( 'Forbidden', 403 );
		}
	}


	protected function domain() : string
	{
		if( static::DOMAIN !== '' ) {
			return static::DOMAIN;
		}

		$class = static::class;
		$prefix = __NAMESPACE__ . '\\Tool\\';
		$separator = strrpos( $class, '\\' );

		if( $separator === false || !str_starts_with( $class, $prefix ) || $separator <= strlen( $prefix ) ) {
			throw new \LogicException( sprintf( 'MCP tool domain is not configured for %s', $class ) );
		}

		$domain = substr( $class, strlen( $prefix ), $separator - strlen( $prefix ) );

		return strtolower( str_replace( '\\', '/', $domain ) );
	}


	final protected function items() : Items
	{
		return $this->items ??= new Items( $this->context );
	}


	final protected function manager() : \Aimeos\MShop\Common\Manager\Iface
	{
		return \Aimeos\MShop::create( $this->context, $this->domain() );
	}


	/**
	 * @param array<string, \Aimeos\Prisma\Schema\Types\Type> $properties
	 */
	final protected function objectSchema( array $properties ) : Schema
	{
		$schema = Schema::for( $this->name(), $properties );
		$schema->type()->withoutAdditionalProperties();

		return $schema;
	}


	protected function permission() : string
	{
		return static::PERMISSION ?: $this->action();
	}


	/**
	 * @param array<string, mixed> $arguments
	 * @return array<string, mixed>
	 */
	abstract protected function run( array $arguments ) : array;


	final protected function schemas() : Schemas
	{
		return $this->schemas ??= new Schemas( $this->context );
	}
}
