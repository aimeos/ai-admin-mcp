<?php

namespace Aimeos\Admin\Mcp\Tool\Ai;

use Aimeos\Prisma\Responses\TextResponse;
use Aimeos\Prisma\Schema\Schema;


final class Write extends Base
{
	protected const ACTION = 'write';


	public function description() : string
	{
		return 'Writes professional product or editorial text in the language of the prompt.';
	}


	public function schema() : Schema
	{
		return $this->objectSchema( [
			'prompt' => Schema::string()->min( 1 )->required()->description( 'Writing instructions and source material' ),
		] );
	}


	protected function run( array $arguments ) : array
	{
		return $this->prisma( function() use ( $arguments ) {
			$provider = $this->provider( 'text' );
			$provider->withSystemPrompt( $this->prompt( 'write' ) );
			/** @var TextResponse $response */
			$response = $provider->ensure( 'write' )->write( (string) $arguments['prompt'] ); // @phpstan-ignore method.notFound

			return ['text' => trim( (string) $response->text() )];
		} );
	}
}
