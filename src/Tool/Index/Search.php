<?php
namespace Aimeos\Admin\Mcp\Tool\Index;
final class Search extends \Aimeos\Admin\Mcp\Tool\Action\Search
{
	protected function resultDomain() : string
	{
		return 'product';
	}
}
