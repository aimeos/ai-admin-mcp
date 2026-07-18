<?php

namespace Aimeos\Admin\Mcp;


class Exception extends \RuntimeException
{
	/** @var array<string, mixed> */
	private array $details;


	/**
	 * @param array<string, mixed> $details Additional machine-readable error details
	 */
	public function __construct( string $message, int $code = 0, array $details = [], ?\Throwable $previous = null )
	{
		parent::__construct( $message, $code, $previous );
		$this->details = $details;
	}


	/**
	 * @return array<string, mixed>
	 */
	public function details() : array
	{
		return $this->details;
	}
}
