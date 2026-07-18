<?php


class TestHelper
{
	private static ?\Aimeos\Bootstrap $aimeos = null;
	/** @var array<string, \Aimeos\MShop\ContextIface> */
	private static array $contexts = [];


	public static function bootstrap() : void
	{
		self::aimeos();
		\Aimeos\MShop::cache( false );
	}


	public static function context( string $site = 'unittest' ) : \Aimeos\MShop\ContextIface
	{
		if( !isset( self::$contexts[$site] ) ) {
			self::$contexts[$site] = self::createContext( $site );
		}

		return clone self::$contexts[$site];
	}


	public static function view( \Aimeos\MShop\ContextIface $context ) : \Aimeos\Base\View\Iface
	{
		$view = new \Aimeos\Base\View\Standard();
		$view->addHelper( 'access', new \Aimeos\Base\View\Helper\Access\All( $view ) );
		$view->addHelper( 'translate', new \Aimeos\Base\View\Helper\Translate\Standard(
			$view,
			new \Aimeos\Base\Translation\None( 'de_DE' )
		) );

		return $view;
	}


	private static function aimeos() : \Aimeos\Bootstrap
	{
		if( self::$aimeos === null )
		{
			require_once 'Bootstrap.php';
			spl_autoload_register( 'Aimeos\\Bootstrap::autoload' );
			self::$aimeos = new \Aimeos\Bootstrap();
		}

		return self::$aimeos;
	}


	private static function createContext( string $site ) : \Aimeos\MShop\ContextIface
	{
		$context = new \Aimeos\MShop\Context();
		$paths = self::aimeos()->getConfigPaths();
		$config = new \Aimeos\Base\Config\Decorator\Memory( new \Aimeos\Base\Config\PHPArray( [], $paths ) );
		$config->apply( ['resource' => ['fs' => ['adapter' => 'Standard', 'basedir' => __DIR__ . '/tmp']]] );
		$context->setConfig( $config );
		$context->setLogger( new \Aimeos\Base\Logger\Errorlog() );
		$context->setDatabaseManager( new \Aimeos\Base\DB\Manager\Standard( $config->get( 'resource', [] ), 'PDO' ) );
		$context->setFilesystemManager( new \Aimeos\Base\Filesystem\Manager\Standard( $config->get( 'resource', [] ) ) );
		$context->setMessageQueueManager( new \Aimeos\Base\MQueue\Manager\Standard( $config->get( 'resource', [] ) ) );
		$context->setCache( new \Aimeos\Base\Cache\None() );
		$context->setI18n( ['de' => new \Aimeos\Base\Translation\None( 'de' )] );
		$context->setPassword( new \Aimeos\Base\Password\Standard() );
		$context->setSession( new \Aimeos\Base\Session\None() );
		$locale = \Aimeos\MShop::create( $context, 'locale' )->bootstrap( $site, '', '', false );
		$context->setLocale( $locale );
		$context->setView( self::view( $context ) );

		return $context->setEditor( 'ai-admin-mcp' );
	}
}
