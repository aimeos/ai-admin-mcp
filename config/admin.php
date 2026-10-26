<?php

return [
	'ai' => [
		/** admin/ai/write
		 * AI provider settings for generating texts
		 *
		 * The "provider" key contains the name of the Prisma provider used to
		 * generate texts for the "ai-write" MCP tool. The "model" key is only used
		 * by providers which support selecting the model and all other keys like
		 * "api_key" or "url" are passed to the provider as configuration, e.g.
		 *
		 *  ['provider' => 'openai', 'model' => 'gpt-5.6-sol', 'api_key' => '...']
		 *
		 * Don't store API keys in your code repository but use environment
		 * variables or a local configuration file instead.
		 *
		 * @param array Associative list of provider settings
		 * @since 2026.10
		 * @see admin/ai/translate
		 * @see admin/ai/imagine
		 * @see admin/ai/isolate
		 * @see admin/mcp/resource/ai/write
		 */
		'write' => [
			'provider' => 'openai',
			'model' => 'gpt-5.6-sol',
		],

		/** admin/ai/translate
		 * AI provider settings for translating texts
		 *
		 * The "provider" key contains the name of the Prisma provider used to
		 * translate texts for the "ai-translate" MCP tool. The "model" key is only
		 * used by providers which support selecting the model and all other keys
		 * like "api_key" or "url" are passed to the provider as configuration,
		 * e.g.
		 *
		 *  ['provider' => 'deepl', 'api_key' => '...']
		 *
		 * Don't store API keys in your code repository but use environment
		 * variables or a local configuration file instead.
		 *
		 * @param array Associative list of provider settings
		 * @since 2026.10
		 * @see admin/ai/write
		 * @see admin/ai/imagine
		 * @see admin/ai/isolate
		 * @see admin/mcp/resource/ai/translate
		 */
		'translate' => [
			'provider' => 'deepl',
		],

		/** admin/ai/imagine
		 * AI provider settings for generating images
		 *
		 * The "provider" key contains the name of the Prisma provider used to
		 * generate images for the "ai-imagine" MCP tool. The "model" key is only
		 * used by providers which support selecting the model and all other keys
		 * like "api_key" or "url" are passed to the provider as configuration,
		 * e.g.
		 *
		 *  ['provider' => 'openai', 'model' => 'gpt-image-1.5', 'api_key' => '...']
		 *
		 * Don't store API keys in your code repository but use environment
		 * variables or a local configuration file instead.
		 *
		 * @param array Associative list of provider settings
		 * @since 2026.10
		 * @see admin/ai/write
		 * @see admin/ai/translate
		 * @see admin/ai/isolate
		 * @see admin/mcp/resource/ai/imagine
		 */
		'imagine' => [
			'provider' => 'openai',
			'model' => 'gpt-image-1.5',
		],

		/** admin/ai/isolate
		 * AI provider settings for removing the background of images
		 *
		 * The "provider" key contains the name of the Prisma provider used to
		 * remove the image background for the "ai-isolate" MCP tool. The "model"
		 * key is only used by providers which support selecting the model and all
		 * other keys like "api_key" or "url" are passed to the provider as
		 * configuration, e.g.
		 *
		 *  ['provider' => 'removebg', 'api_key' => '...']
		 *
		 * Don't store API keys in your code repository but use environment
		 * variables or a local configuration file instead.
		 *
		 * @param array Associative list of provider settings
		 * @since 2026.10
		 * @see admin/ai/write
		 * @see admin/ai/translate
		 * @see admin/ai/imagine
		 * @see admin/mcp/resource/ai/isolate
		 */
		'isolate' => [
			'provider' => 'removebg',
		],
	],
	'mcp' => [
		/** admin/mcp/lists-domains
		 * Domains of the list items included in the items returned by the MCP tools
		 *
		 * Items like products or catalogs can reference other items via list
		 * items, e.g. texts, prices or media. Only referenced items of the
		 * domains in this list are added to the "lists" section of the returned
		 * items, so the MCP client can see and update them together with the
		 * parent item. Remove domains to keep the responses small or add the
		 * domains of your own extensions if their items should be returned too.
		 *
		 * @param array List of domain names
		 * @since 2026.10
		 * @see admin/mcp/serialization/max-depth
		 */
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
				/** admin/mcp/ai/isolate/max-size
				 * Maximum size of the images passed to the ai-isolate tool in bytes
				 *
				 * The ai-isolate tool removes the background of images sent by the MCP
				 * client as base64 encoded content. Larger images are rejected with
				 * an error to limit the memory usage and the costs of the AI provider.
				 * The default value is 10MB.
				 *
				 * @param integer Number of bytes
				 * @since 2026.10
				 * @see admin/mcp/media/max-size
				 */
				'max-size' => 10485760,
			],
		],
		'media' => [
			/** admin/mcp/media/max-size
			 * Maximum size of the files uploaded by the media-save tool in bytes
			 *
			 * MCP clients upload media files and preview images as base64 encoded
			 * content. Files larger than this value are rejected with an error.
			 * The default value is 10MB.
			 *
			 * @param integer Number of bytes
			 * @since 2026.10
			 * @see admin/mcp/ai/isolate/max-size
			 */
			'max-size' => 10485760,
		],
		'serialization' => [
			/** admin/mcp/serialization/max-depth
			 * Maximum nesting level of referenced items returned by the MCP tools
			 *
			 * Returned items contain their referenced items like list items,
			 * addresses, properties, stock or child nodes, which can contain
			 * referenced items themselves. This setting limits how deep these
			 * references are followed to keep the responses small. A value of
			 * "0" returns the items without any references.
			 *
			 * @param integer Number of levels
			 * @since 2026.10
			 * @see admin/mcp/lists-domains
			 */
			'max-depth' => 3,
		],
	],
];
