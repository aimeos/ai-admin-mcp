<?php

namespace Aimeos\Admin\Mcp\Tool\Ai;

use Aimeos\Admin\Mcp\Exception;
use Aimeos\Prisma\Prisma;
use Aimeos\Prisma\Responses\FileResponse;


abstract class Base extends \Aimeos\Admin\Mcp\Tool\Action\Base
{

	public function annotations() : array
	{
		return [
			'readOnlyHint' => true,
			'destructiveHint' => false,
			'idempotentHint' => false,
			'openWorldHint' => true,
		];
	}


	/**
	 * @return array<string, mixed>
	 */
	protected function fileResponse( FileResponse $response ) : array
	{
		return [
			'base64' => $response->base64(),
			'mimeType' => $response->mimeType(),
			'description' => $response->description(),
		];
	}


	protected function prisma( callable $callback ) : mixed
	{
		try {
			return $callback();
		} catch( \Aimeos\Prisma\Exceptions\PrismaException $e ) {
			throw new Exception( $e->getMessage(), (int) $e->getCode(), [], $e );
		}
	}


	protected function prompt( string $name ) : string
	{
		$theme = $this->context->locale()->getSiteItem()->getTheme();
		$paths = ( new \Aimeos\Bootstrap() )->getTemplatePaths( 'admin/mcp/templates', $theme );
		$view = new \Aimeos\Base\View\Standard( $paths );

		return trim( $view->render( 'ai/' . $name ) );
	}


	protected function provider( string $type ) : \Aimeos\Prisma\Contracts\Provider
	{
		$site = $this->context->locale()->getSiteItem()->getConfigValue( 'admin/ai/' . $this->action() );
		$custom = is_array( $site ) && $site !== [];

		// site settings replace the application settings, so the configured API key is never sent to site endpoints
		$settings = $custom ? $site : (array) $this->context->config()->get( 'admin/ai/' . $this->action(), [] );
		$name = (string) ( $settings['provider'] ?? '' );
		$model = isset( $settings['model'] ) ? (string) $settings['model'] : null;
		unset( $settings['provider'], $settings['model'] );
		$settings = array_filter( $settings, fn( $value ) => $value !== '' && $value !== null );

		$provider = Prisma::type( $type )->using( $name, $settings )->model( $model );

		// only endpoints from the application config may be internal hosts like local LLMs
		return $custom ? $provider : $provider->withInternalHosts();
	}
}
