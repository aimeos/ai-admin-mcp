# Aimeos admin MCP tools

`aimeos/ai-admin-mcp` provides framework-neutral administrative tools for Aimeos.
Each tool is bound to an Aimeos context, validates model-supplied arguments with
`aimeos/prisma`, checks Aimeos permissions and returns structured PHP arrays.

The package contains no MCP transport, MCP SDK or framework integration. Consumers
obtain the enabled tools from the catalog and adapt them to their server runtime:

```php
$tools = \Aimeos\Admin\Mcp\Tools::create( $context );

foreach( $tools as $tool ) {
	$definition = [
		'name' => $tool->name(),
		'description' => $tool->description(),
		'inputSchema' => $tool->schema()->toArray(),
		'annotations' => $tool->annotations(),
	];
	$result = $tool->execute( $arguments );
}
```

Tool names use lower-case words separated by hyphens, for example
`product-search`, `catalog-tree` and `locale-language-save`.

The built-in catalog covers AI, attribute, catalog, coupon and coupon code,
customer, group, index, locale, language, currency, site, media, order, plugin,
price, product, review, rule, service, stock, subscription, supplier and text.
Depending on the domain, tools provide get, find, search, aggregate, save, delete,
provider configuration and tree operations.

Set `admin/mcp/tools` to a list of tool class names to replace the built-in catalog.
Permissions are configured below `admin/mcp/resource`; rejected arguments and
authorization failures are reported as `Aimeos\Admin\Mcp\Exception` instances.
