<?php

$prisma = realpath( __DIR__ . '/../../../../prisma/src' );

if( $prisma !== false )
{
	spl_autoload_register( function( string $class ) use ( $prisma ) {
		$prefix = 'Aimeos\\Prisma\\';

		if( str_starts_with( $class, $prefix ) ) {
			$file = $prisma . '/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
			is_file( $file ) ? require $file : null;
		}
	} );
}
