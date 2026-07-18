<?php

namespace Aimeos\Admin\Mcp;


final class Tools
{
	/** @var array<string, array<int, string>> */
	private const ACTIONS = [
		'ai' => ['Write', 'Translate', 'Imagine', 'Isolate'],
		'attribute' => ['Get', 'Find', 'Search', 'Save', 'Delete'],
		'catalog' => ['Get', 'Find', 'Search', 'Tree', 'Path', 'SearchTree', 'Save', 'Delete', 'Insert', 'Move'],
		'coupon' => ['Get', 'Search', 'Config', 'Save', 'Delete'],
		'coupon/code' => ['Get', 'Search', 'Save', 'Delete'],
		'customer' => ['Get', 'Find', 'Search', 'Aggregate', 'Save', 'Delete'],
		'group' => ['Get', 'Search', 'Save', 'Delete'],
		'index' => ['Search', 'Aggregate'],
		'locale' => ['Get', 'Search', 'Save', 'Delete'],
		'locale/language' => ['Get', 'Search', 'Save', 'Delete'],
		'locale/currency' => ['Get', 'Search', 'Save', 'Delete'],
		'locale/site' => ['Get', 'Find', 'Search', 'Tree', 'Path', 'SearchTree', 'Save', 'Delete', 'Insert', 'Move'],
		'media' => ['Get', 'Search', 'Save', 'Delete'],
		'order' => ['Get', 'Search', 'Aggregate', 'Save'],
		'plugin' => ['Get', 'Search', 'Config', 'Save', 'Delete'],
		'price' => ['Get', 'Search', 'Save', 'Delete'],
		'product' => ['Get', 'Find', 'Search', 'Save', 'Delete'],
		'review' => ['Get', 'Search', 'Save', 'Delete'],
		'rule' => ['Get', 'Search', 'Config', 'Save', 'Delete'],
		'service' => ['Get', 'Find', 'Search', 'Config', 'Save', 'Delete'],
		'stock' => ['Get', 'Search', 'Save', 'Delete'],
		'subscription' => ['Get', 'Search', 'Save', 'Delete'],
		'supplier' => ['Get', 'Find', 'Search', 'Save', 'Delete'],
		'text' => ['Get', 'Search', 'Save', 'Delete'],
	];


	/**
	 * Returns all built-in tool classes.
	 *
	 * @return array<int, class-string<Tool>>
	 */
	public static function classes() : array
	{
		$result = [];

		foreach( self::ACTIONS as $domain => $actions )
		{
			$namespace = str_replace( ' ', '\\', ucwords( str_replace( '/', ' ', $domain ) ) );

			foreach( $actions as $action ) {
				$result[] = __NAMESPACE__ . '\\Tool\\' . $namespace . '\\' . $action;
			}
		}

		return $result;
	}


	/**
	 * Creates enabled tools for the current Aimeos context.
	 *
	 * @return array<string, Tool> Tools indexed by canonical name
	 */
	public static function create( \Aimeos\MShop\ContextIface $context ) : array
	{
		$classes = $context->config()->get( 'admin/mcp/tools', self::classes() );

		if( !is_array( $classes ) ) {
			throw new Exception( 'Configuration "admin/mcp/tools" must be an array', 500 );
		}

		$result = [];

		foreach( $classes as $class )
		{
			if( !is_string( $class ) || !is_subclass_of( $class, Tool::class ) ) {
				throw new Exception( sprintf( 'Invalid MCP tool class: %s', is_scalar( $class ) ? (string) $class : gettype( $class ) ), 500 );
			}

			$tool = new $class( $context );

			if( isset( $result[$tool->name()] ) ) {
				throw new Exception( sprintf( 'Duplicate MCP tool name: %s', $tool->name() ), 500 );
			}

			$result[$tool->name()] = $tool;
		}

		return $result;
	}
}
