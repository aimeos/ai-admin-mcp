<?php

namespace Aimeos\Admin\Mcp\Tool\Ai;

use Aimeos\Prisma\Responses\FileResponse;
use Aimeos\Prisma\Schema\Schema;


final class Imagine extends Base
{
	protected const ACTION = 'imagine';


	public function description() : string
	{
		return 'Creates an image from a text description.';
	}


	public function schema() : Schema
	{
		return $this->objectSchema( [
			'prompt' => Schema::string()->min( 1 )->required()->description( 'Image description' ),
			'size' => Schema::string()->description( 'Requested image dimensions' ),
			'style' => Schema::string()->description( 'Requested image style' ),
		] );
	}


	protected function run( array $arguments ) : array
	{
		return $this->prisma( function() use ( $arguments ) {
			$options = array_filter( [
				'size' => $arguments['size'] ?? null,
				'style' => $arguments['style'] ?? null,
			], fn( $value ) => $value !== '' && $value !== null );
			$prompt = $this->prompt( 'imagine' ) . "\n\n" . (string) $arguments['prompt'];
			/** @var FileResponse $response */
			$response = $this->provider( 'image' )->ensure( 'imagine' )->imagine( $prompt, [], $options ); // @phpstan-ignore method.notFound

			return $this->fileResponse( $response );
		} );
	}
}
