<?php

namespace Aimeos\Admin\Mcp\Tests\Tool;

use Aimeos\Admin\Mcp\Tool\Ai\Write;
use Aimeos\Prisma\Prisma;
use Aimeos\Prisma\Responses\TextResponse;


class AiTest extends \PHPUnit\Framework\TestCase
{
	protected function tearDown() : void
	{
		Prisma::reset();
	}


	public function testWrite() : void
	{
		$context = \TestHelper::context();
		$context->config()->set( 'admin/ai/write', [
			'provider' => 'openai',
			'model' => 'gpt-4o-mini',
			'api_key' => 'test',
		] );
		$fake = Prisma::fake( [TextResponse::fake( 'Generated text' )] );
		$result = ( new Write( $context ) )->execute( ['prompt' => 'Input'] );

		$this->assertSame( ['text' => 'Generated text'], $result );
		$fake->assertCalled( 'write', fn( $args ) => $args[0] === 'Input' );
	}


	public function testWriteSiteEndpointWithoutConfigKey() : void
	{
		$context = \TestHelper::context();
		$context->config()->set( 'admin/ai/write', ['provider' => 'openai', 'api_key' => 'config-secret'] );
		$site = $context->locale()->getSiteItem()->setConfigValue( 'admin/ai/write', [
			'provider' => 'openai',
			'url' => 'https://attacker.example.com',
		] );
		$context->config()->apply( $site->getConfig() );

		$this->expectException( \Aimeos\Admin\Mcp\Exception::class );
		$this->expectExceptionMessage( 'No API key' );

		( new Write( $context ) )->execute( ['prompt' => 'Input'] );
	}


	public function testWriteSiteInternalHost() : void
	{
		$context = \TestHelper::context();
		$site = $context->locale()->getSiteItem()->setConfigValue( 'admin/ai/write', [
			'provider' => 'openai',
			'api_key' => 'site-secret',
			'url' => 'http://169.254.169.254/',
		] );
		$context->config()->apply( $site->getConfig() );

		$this->expectException( \Aimeos\Admin\Mcp\Exception::class );
		$this->expectExceptionMessage( 'does not resolve to a public address' );

		( new Write( $context ) )->execute( ['prompt' => 'Input'] );
	}
}
