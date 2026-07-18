<?php

namespace Aimeos\Admin\Mcp\Tool\Ai;

use Aimeos\Prisma\Responses\TextResponse;
use Aimeos\Prisma\Schema\Schema;


final class Translate extends Base
{
	protected const ACTION = 'translate';


	public function description() : string
	{
		return 'Translates one or more texts into a target language.';
	}


	public function schema() : Schema
	{
		return $this->objectSchema( [
			'texts' => Schema::array()->items( Schema::string() )->min( 1 )->required()->description( 'Texts to translate' ),
			'to' => Schema::string()->min( 1 )->required()->description( 'Target language code' ),
			'from' => Schema::string()->description( 'Source language code; omitted for automatic detection' ),
		] );
	}


	protected function run( array $arguments ) : array
	{
		return $this->prisma( function() use ( $arguments ) {
			$texts = array_map( 'strval', (array) $arguments['texts'] );
			$from = isset( $arguments['from'] ) ? (string) $arguments['from'] : null;
			/** @var TextResponse $response */
			$response = $this->provider( 'text' )->ensure( 'translate' )->translate( $texts, (string) $arguments['to'], $from ); // @phpstan-ignore method.notFound

			return ['texts' => array_map( 'strval', $response->texts() )];
		} );
	}
}
