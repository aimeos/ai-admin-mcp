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
}
