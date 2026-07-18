<?php

require_once __DIR__ . '/prisma.php';

spl_autoload_register( function( string $class ) {
	$prefix = 'Aimeos\\Admin\\Mcp\\';

	if( str_starts_with( $class, $prefix ) ) {
		$file = dirname( __DIR__ ) . '/src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		is_file( $file ) ? require $file : null;
	}
} );
