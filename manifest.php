<?php

return [
	'name' => 'ai-admin-mcp',
	'config' => [
		'config',
	],
	'depends' => [
		'aimeos-core',
	],
	'include' => [
		'src',
	],
	'template' => [
		'admin/mcp/templates' => [
			'templates/admin/mcp',
		],
	],
];
