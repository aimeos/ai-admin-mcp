<?php

return [
	'ai' => [
		'write' => [
			'provider' => 'openai',
			'model' => 'gpt-5.6-sol',
		],
		'translate' => [
			'provider' => 'deepl',
		],
		'imagine' => [
			'provider' => 'openai',
			'model' => 'gpt-image-1.5',
		],
		'isolate' => [
			'provider' => 'removebg',
		],
	],
	'mcp' => [
		'lists-domains' => [
			'attribute',
			'catalog',
			'customer',
			'group',
			'media',
			'price',
			'product',
			'service',
			'supplier',
			'text',
		],
		'ai' => [
			'isolate' => [
				'max-size' => 10485760,
			],
		],
		'media' => [
			'max-size' => 10485760,
		],
		'serialization' => [
			'max-depth' => 3,
		],
	],
];
