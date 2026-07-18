<?php

namespace Aimeos\Admin\Mcp\Tool\Ai;

use Aimeos\Admin\Mcp\Exception;
use Aimeos\Prisma\Files\Image;
use Aimeos\Prisma\Responses\FileResponse;
use Aimeos\Prisma\Schema\Schema;


final class Isolate extends Base
{
	protected const ACTION = 'isolate';


	public function description() : string
	{
		return 'Removes the background from an image.';
	}


	public function schema() : Schema
	{
		return $this->objectSchema( [
			'image' => $this->schemas()->binary()->required()->description( 'Source image' ),
		] );
	}


	protected function run( array $arguments ) : array
	{
		$data = (array) $arguments['image'];
		$binary = base64_decode( (string) $data['base64'], true );

		if( $binary === false ) {
			throw new Exception( 'Invalid base64 image content', 400 );
		}

		$maxSize = max( 1, (int) $this->context->config()->get( 'admin/mcp/ai/isolate/max-size', 10485760 ) );

		if( strlen( $binary ) > $maxSize ) {
			throw new Exception( 'Image exceeds the maximum allowed size', 413 );
		}

		return $this->prisma( function() use ( $binary, $data ) {
			$image = Image::fromBinary( $binary, isset( $data['mimeType'] ) ? (string) $data['mimeType'] : null );
			/** @var FileResponse $response */
			// @phpstan-ignore-next-line method.notFound
			$response = $this->provider( 'image' )->ensure( 'isolate' )->isolate( $image, [
				'crop' => true,
				'size' => 'auto',
				'format' => 'png',
			] );

			return $this->fileResponse( $response );
		} );
	}
}
