<?php

$editor = ['admin', 'editor', 'super'];
$admin = ['admin', 'super'];
$super = ['super'];

return [
	'ai' => [
		/** admin/mcp/resource/ai/write
		 * List of user groups that are allowed to generate texts using the AI provider
		 *
		 * The "ai-write" MCP tool can only be executed by users who are members of
		 * at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/ai/translate
		 * @see admin/mcp/resource/ai/imagine
		 * @see admin/mcp/resource/ai/isolate
		 * @see admin/mcp/tools
		 */
		'write' => $editor,

		/** admin/mcp/resource/ai/translate
		 * List of user groups that are allowed to translate texts using the AI provider
		 *
		 * The "ai-translate" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/ai/write
		 * @see admin/mcp/resource/ai/imagine
		 * @see admin/mcp/resource/ai/isolate
		 * @see admin/mcp/tools
		 */
		'translate' => $editor,

		/** admin/mcp/resource/ai/imagine
		 * List of user groups that are allowed to generate images using the AI provider
		 *
		 * The "ai-imagine" MCP tool can only be executed by users who are members
		 * of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/ai/write
		 * @see admin/mcp/resource/ai/translate
		 * @see admin/mcp/resource/ai/isolate
		 * @see admin/mcp/tools
		 */
		'imagine' => $editor,

		/** admin/mcp/resource/ai/isolate
		 * List of user groups that are allowed to remove the background of images using the AI provider
		 *
		 * The "ai-isolate" MCP tool can only be executed by users who are members
		 * of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/ai/write
		 * @see admin/mcp/resource/ai/translate
		 * @see admin/mcp/resource/ai/imagine
		 * @see admin/mcp/tools
		 */
		'isolate' => $editor,
	],
	'attribute' => [
		/** admin/mcp/resource/attribute/get
		 * List of user groups that are allowed to view attribute items
		 *
		 * The "attribute-get", "attribute-find" and "attribute-search" MCP tools
		 * can only be executed by users who are members of at least one of the
		 * configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/attribute/save
		 * @see admin/mcp/resource/attribute/delete
		 * @see admin/mcp/tools
		 */
		'get' => $editor,

		/** admin/mcp/resource/attribute/save
		 * List of user groups that are allowed to create and update attribute items
		 *
		 * The "attribute-save" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/attribute/get
		 * @see admin/mcp/resource/attribute/delete
		 * @see admin/mcp/tools
		 */
		'save' => $editor,

		/** admin/mcp/resource/attribute/delete
		 * List of user groups that are allowed to delete attribute items
		 *
		 * The "attribute-delete" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/attribute/get
		 * @see admin/mcp/resource/attribute/save
		 * @see admin/mcp/tools
		 */
		'delete' => $editor,
	],
	'catalog' => [
		/** admin/mcp/resource/catalog/get
		 * List of user groups that are allowed to view catalog items
		 *
		 * The "catalog-get", "catalog-find", "catalog-search", "catalog-tree",
		 * "catalog-path" and "catalog-search-tree" MCP tools can only be executed
		 * by users who are members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/catalog/save
		 * @see admin/mcp/resource/catalog/delete
		 * @see admin/mcp/tools
		 */
		'get' => $editor,

		/** admin/mcp/resource/catalog/save
		 * List of user groups that are allowed to create and update catalog items
		 *
		 * The "catalog-save", "catalog-insert" and "catalog-move" MCP tools can
		 * only be executed by users who are members of at least one of the
		 * configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/catalog/get
		 * @see admin/mcp/resource/catalog/delete
		 * @see admin/mcp/tools
		 */
		'save' => $editor,

		/** admin/mcp/resource/catalog/delete
		 * List of user groups that are allowed to delete catalog items
		 *
		 * The "catalog-delete" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/catalog/get
		 * @see admin/mcp/resource/catalog/save
		 * @see admin/mcp/tools
		 */
		'delete' => $editor,
	],
	'coupon' => [
		/** admin/mcp/resource/coupon/get
		 * List of user groups that are allowed to view coupon items
		 *
		 * The "coupon-get", "coupon-search" and "coupon-config" MCP tools can only
		 * be executed by users who are members of at least one of the configured
		 * groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/coupon/save
		 * @see admin/mcp/resource/coupon/delete
		 * @see admin/mcp/tools
		 */
		'get' => $editor,

		/** admin/mcp/resource/coupon/save
		 * List of user groups that are allowed to create and update coupon items
		 *
		 * The "coupon-save" MCP tool can only be executed by users who are members
		 * of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/coupon/get
		 * @see admin/mcp/resource/coupon/delete
		 * @see admin/mcp/tools
		 */
		'save' => $editor,

		/** admin/mcp/resource/coupon/delete
		 * List of user groups that are allowed to delete coupon items
		 *
		 * The "coupon-delete" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/coupon/get
		 * @see admin/mcp/resource/coupon/save
		 * @see admin/mcp/tools
		 */
		'delete' => $editor,
		'code' => [
			/** admin/mcp/resource/coupon/code/get
			 * List of user groups that are allowed to view coupon code items
			 *
			 * The "coupon-code-get" and "coupon-code-search" MCP tools can only be
			 * executed by users who are members of at least one of the configured
			 * groups.
			 *
			 * @param array List of user group names
			 * @since 2026.10
			 * @see admin/mcp/resource/coupon/code/save
			 * @see admin/mcp/resource/coupon/code/delete
			 * @see admin/mcp/tools
			 */
			'get' => $editor,

			/** admin/mcp/resource/coupon/code/save
			 * List of user groups that are allowed to create and update coupon code items
			 *
			 * The "coupon-code-save" MCP tool can only be executed by users who are
			 * members of at least one of the configured groups.
			 *
			 * @param array List of user group names
			 * @since 2026.10
			 * @see admin/mcp/resource/coupon/code/get
			 * @see admin/mcp/resource/coupon/code/delete
			 * @see admin/mcp/tools
			 */
			'save' => $editor,

			/** admin/mcp/resource/coupon/code/delete
			 * List of user groups that are allowed to delete coupon code items
			 *
			 * The "coupon-code-delete" MCP tool can only be executed by users who are
			 * members of at least one of the configured groups.
			 *
			 * @param array List of user group names
			 * @since 2026.10
			 * @see admin/mcp/resource/coupon/code/get
			 * @see admin/mcp/resource/coupon/code/save
			 * @see admin/mcp/tools
			 */
			'delete' => $editor,
		],
	],
	'customer' => [
		/** admin/mcp/resource/customer/get
		 * List of user groups that are allowed to view customer items
		 *
		 * The "customer-get", "customer-find", "customer-search" and
		 * "customer-aggregate" MCP tools can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/customer/save
		 * @see admin/mcp/resource/customer/delete
		 * @see admin/mcp/tools
		 */
		'get' => $editor,

		/** admin/mcp/resource/customer/save
		 * List of user groups that are allowed to create and update customer items
		 *
		 * The "customer-save" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/customer/get
		 * @see admin/mcp/resource/customer/delete
		 * @see admin/mcp/tools
		 */
		'save' => $editor,

		/** admin/mcp/resource/customer/delete
		 * List of user groups that are allowed to delete customer items
		 *
		 * The "customer-delete" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/customer/get
		 * @see admin/mcp/resource/customer/save
		 * @see admin/mcp/tools
		 */
		'delete' => $admin,
	],
	'group' => [
		/** admin/mcp/resource/group/get
		 * List of user groups that are allowed to view group items
		 *
		 * The "group-get" and "group-search" MCP tools can only be executed by
		 * users who are members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/group/save
		 * @see admin/mcp/resource/group/delete
		 * @see admin/mcp/tools
		 */
		'get' => $editor,

		/** admin/mcp/resource/group/save
		 * List of user groups that are allowed to create and update group items
		 *
		 * The "group-save" MCP tool can only be executed by users who are members
		 * of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/group/get
		 * @see admin/mcp/resource/group/delete
		 * @see admin/mcp/tools
		 */
		'save' => $admin,

		/** admin/mcp/resource/group/delete
		 * List of user groups that are allowed to delete group items
		 *
		 * The "group-delete" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/group/get
		 * @see admin/mcp/resource/group/save
		 * @see admin/mcp/tools
		 */
		'delete' => $admin,
	],
	'index' => [
		/** admin/mcp/resource/index/get
		 * List of user groups that are allowed to view product index items
		 *
		 * The "index-search" and "index-aggregate" MCP tools can only be executed
		 * by users who are members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/tools
		 */
		'get' => $editor,
	],
	'locale' => [
		/** admin/mcp/resource/locale/get
		 * List of user groups that are allowed to view locale items
		 *
		 * The "locale-get" and "locale-search" MCP tools can only be executed by
		 * users who are members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/locale/save
		 * @see admin/mcp/resource/locale/delete
		 * @see admin/mcp/tools
		 */
		'get' => $admin,

		/** admin/mcp/resource/locale/save
		 * List of user groups that are allowed to create and update locale items
		 *
		 * The "locale-save" MCP tool can only be executed by users who are members
		 * of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/locale/get
		 * @see admin/mcp/resource/locale/delete
		 * @see admin/mcp/tools
		 */
		'save' => $admin,

		/** admin/mcp/resource/locale/delete
		 * List of user groups that are allowed to delete locale items
		 *
		 * The "locale-delete" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/locale/get
		 * @see admin/mcp/resource/locale/save
		 * @see admin/mcp/tools
		 */
		'delete' => $admin,
		'currency' => [
			/** admin/mcp/resource/locale/currency/get
			 * List of user groups that are allowed to view currency items
			 *
			 * The "locale-currency-get" and "locale-currency-search" MCP tools can
			 * only be executed by users who are members of at least one of the
			 * configured groups.
			 *
			 * @param array List of user group names
			 * @since 2026.10
			 * @see admin/mcp/resource/locale/currency/save
			 * @see admin/mcp/resource/locale/currency/delete
			 * @see admin/mcp/tools
			 */
			'get' => $editor,

			/** admin/mcp/resource/locale/currency/save
			 * List of user groups that are allowed to create and update currency items
			 *
			 * The "locale-currency-save" MCP tool can only be executed by users who
			 * are members of at least one of the configured groups.
			 *
			 * @param array List of user group names
			 * @since 2026.10
			 * @see admin/mcp/resource/locale/currency/get
			 * @see admin/mcp/resource/locale/currency/delete
			 * @see admin/mcp/tools
			 */
			'save' => $super,

			/** admin/mcp/resource/locale/currency/delete
			 * List of user groups that are allowed to delete currency items
			 *
			 * The "locale-currency-delete" MCP tool can only be executed by users who
			 * are members of at least one of the configured groups.
			 *
			 * @param array List of user group names
			 * @since 2026.10
			 * @see admin/mcp/resource/locale/currency/get
			 * @see admin/mcp/resource/locale/currency/save
			 * @see admin/mcp/tools
			 */
			'delete' => $super,
		],
		'language' => [
			/** admin/mcp/resource/locale/language/get
			 * List of user groups that are allowed to view language items
			 *
			 * The "locale-language-get" and "locale-language-search" MCP tools can
			 * only be executed by users who are members of at least one of the
			 * configured groups.
			 *
			 * @param array List of user group names
			 * @since 2026.10
			 * @see admin/mcp/resource/locale/language/save
			 * @see admin/mcp/resource/locale/language/delete
			 * @see admin/mcp/tools
			 */
			'get' => $editor,

			/** admin/mcp/resource/locale/language/save
			 * List of user groups that are allowed to create and update language items
			 *
			 * The "locale-language-save" MCP tool can only be executed by users who
			 * are members of at least one of the configured groups.
			 *
			 * @param array List of user group names
			 * @since 2026.10
			 * @see admin/mcp/resource/locale/language/get
			 * @see admin/mcp/resource/locale/language/delete
			 * @see admin/mcp/tools
			 */
			'save' => $super,

			/** admin/mcp/resource/locale/language/delete
			 * List of user groups that are allowed to delete language items
			 *
			 * The "locale-language-delete" MCP tool can only be executed by users who
			 * are members of at least one of the configured groups.
			 *
			 * @param array List of user group names
			 * @since 2026.10
			 * @see admin/mcp/resource/locale/language/get
			 * @see admin/mcp/resource/locale/language/save
			 * @see admin/mcp/tools
			 */
			'delete' => $super,
		],
		'site' => [
			/** admin/mcp/resource/locale/site/get
			 * List of user groups that are allowed to view site items
			 *
			 * The "locale-site-get", "locale-site-find", "locale-site-search",
			 * "locale-site-tree", "locale-site-path" and "locale-site-search-tree" MCP
			 * tools can only be executed by users who are members of at least one of
			 * the configured groups.
			 *
			 * @param array List of user group names
			 * @since 2026.10
			 * @see admin/mcp/resource/locale/site/save
			 * @see admin/mcp/resource/locale/site/delete
			 * @see admin/mcp/resource/locale/site/insert
			 * @see admin/mcp/resource/locale/site/move
			 * @see admin/mcp/tools
			 */
			'get' => $admin,

			/** admin/mcp/resource/locale/site/save
			 * List of user groups that are allowed to create and update site items
			 *
			 * The "locale-site-save" MCP tool can only be executed by users who are
			 * members of at least one of the configured groups.
			 *
			 * @param array List of user group names
			 * @since 2026.10
			 * @see admin/mcp/resource/locale/site/get
			 * @see admin/mcp/resource/locale/site/delete
			 * @see admin/mcp/resource/locale/site/insert
			 * @see admin/mcp/resource/locale/site/move
			 * @see admin/mcp/tools
			 */
			'save' => $admin,

			/** admin/mcp/resource/locale/site/delete
			 * List of user groups that are allowed to delete site items
			 *
			 * The "locale-site-delete" MCP tool can only be executed by users who are
			 * members of at least one of the configured groups.
			 *
			 * @param array List of user group names
			 * @since 2026.10
			 * @see admin/mcp/resource/locale/site/get
			 * @see admin/mcp/resource/locale/site/save
			 * @see admin/mcp/resource/locale/site/insert
			 * @see admin/mcp/resource/locale/site/move
			 * @see admin/mcp/tools
			 */
			'delete' => $super,

			/** admin/mcp/resource/locale/site/insert
			 * List of user groups that are allowed to create new site items in the site tree
			 *
			 * The "locale-site-insert" MCP tool can only be executed by users who are
			 * members of at least one of the configured groups.
			 *
			 * @param array List of user group names
			 * @since 2026.10
			 * @see admin/mcp/resource/locale/site/get
			 * @see admin/mcp/resource/locale/site/save
			 * @see admin/mcp/resource/locale/site/delete
			 * @see admin/mcp/resource/locale/site/move
			 * @see admin/mcp/tools
			 */
			'insert' => $super,

			/** admin/mcp/resource/locale/site/move
			 * List of user groups that are allowed to move site items within the site tree
			 *
			 * The "locale-site-move" MCP tool can only be executed by users who are
			 * members of at least one of the configured groups.
			 *
			 * @param array List of user group names
			 * @since 2026.10
			 * @see admin/mcp/resource/locale/site/get
			 * @see admin/mcp/resource/locale/site/save
			 * @see admin/mcp/resource/locale/site/delete
			 * @see admin/mcp/resource/locale/site/insert
			 * @see admin/mcp/tools
			 */
			'move' => $super,
		],
	],
	'media' => [
		/** admin/mcp/resource/media/get
		 * List of user groups that are allowed to view media items
		 *
		 * The "media-get" and "media-search" MCP tools can only be executed by
		 * users who are members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/media/save
		 * @see admin/mcp/resource/media/delete
		 * @see admin/mcp/tools
		 */
		'get' => $editor,

		/** admin/mcp/resource/media/save
		 * List of user groups that are allowed to create and update media items
		 *
		 * The "media-save" MCP tool can only be executed by users who are members
		 * of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/media/get
		 * @see admin/mcp/resource/media/delete
		 * @see admin/mcp/tools
		 */
		'save' => $editor,

		/** admin/mcp/resource/media/delete
		 * List of user groups that are allowed to delete media items
		 *
		 * The "media-delete" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/media/get
		 * @see admin/mcp/resource/media/save
		 * @see admin/mcp/tools
		 */
		'delete' => $editor,
	],
	'order' => [
		/** admin/mcp/resource/order/get
		 * List of user groups that are allowed to view order items
		 *
		 * The "order-get", "order-search" and "order-aggregate" MCP tools can only
		 * be executed by users who are members of at least one of the configured
		 * groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/order/save
		 * @see admin/mcp/tools
		 */
		'get' => $editor,

		/** admin/mcp/resource/order/save
		 * List of user groups that are allowed to create and update order items
		 *
		 * The "order-save" MCP tool can only be executed by users who are members
		 * of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/order/get
		 * @see admin/mcp/tools
		 */
		'save' => $editor,
	],
	'plugin' => [
		/** admin/mcp/resource/plugin/get
		 * List of user groups that are allowed to view plugin items
		 *
		 * The "plugin-get", "plugin-search" and "plugin-config" MCP tools can only
		 * be executed by users who are members of at least one of the configured
		 * groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/plugin/save
		 * @see admin/mcp/resource/plugin/delete
		 * @see admin/mcp/tools
		 */
		'get' => $admin,

		/** admin/mcp/resource/plugin/save
		 * List of user groups that are allowed to create and update plugin items
		 *
		 * The "plugin-save" MCP tool can only be executed by users who are members
		 * of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/plugin/get
		 * @see admin/mcp/resource/plugin/delete
		 * @see admin/mcp/tools
		 */
		'save' => $admin,

		/** admin/mcp/resource/plugin/delete
		 * List of user groups that are allowed to delete plugin items
		 *
		 * The "plugin-delete" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/plugin/get
		 * @see admin/mcp/resource/plugin/save
		 * @see admin/mcp/tools
		 */
		'delete' => $admin,
	],
	'price' => [
		/** admin/mcp/resource/price/get
		 * List of user groups that are allowed to view price items
		 *
		 * The "price-get" and "price-search" MCP tools can only be executed by
		 * users who are members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/price/save
		 * @see admin/mcp/resource/price/delete
		 * @see admin/mcp/tools
		 */
		'get' => $editor,

		/** admin/mcp/resource/price/save
		 * List of user groups that are allowed to create and update price items
		 *
		 * The "price-save" MCP tool can only be executed by users who are members
		 * of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/price/get
		 * @see admin/mcp/resource/price/delete
		 * @see admin/mcp/tools
		 */
		'save' => $editor,

		/** admin/mcp/resource/price/delete
		 * List of user groups that are allowed to delete price items
		 *
		 * The "price-delete" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/price/get
		 * @see admin/mcp/resource/price/save
		 * @see admin/mcp/tools
		 */
		'delete' => $editor,
	],
	'product' => [
		/** admin/mcp/resource/product/get
		 * List of user groups that are allowed to view product items
		 *
		 * The "product-get", "product-find" and "product-search" MCP tools can
		 * only be executed by users who are members of at least one of the
		 * configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/product/save
		 * @see admin/mcp/resource/product/delete
		 * @see admin/mcp/tools
		 */
		'get' => $editor,

		/** admin/mcp/resource/product/save
		 * List of user groups that are allowed to create and update product items
		 *
		 * The "product-save" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/product/get
		 * @see admin/mcp/resource/product/delete
		 * @see admin/mcp/tools
		 */
		'save' => $editor,

		/** admin/mcp/resource/product/delete
		 * List of user groups that are allowed to delete product items
		 *
		 * The "product-delete" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/product/get
		 * @see admin/mcp/resource/product/save
		 * @see admin/mcp/tools
		 */
		'delete' => $editor,
	],
	'review' => [
		/** admin/mcp/resource/review/get
		 * List of user groups that are allowed to view review items
		 *
		 * The "review-get" and "review-search" MCP tools can only be executed by
		 * users who are members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/review/save
		 * @see admin/mcp/resource/review/delete
		 * @see admin/mcp/tools
		 */
		'get' => $editor,

		/** admin/mcp/resource/review/save
		 * List of user groups that are allowed to create and update review items
		 *
		 * The "review-save" MCP tool can only be executed by users who are members
		 * of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/review/get
		 * @see admin/mcp/resource/review/delete
		 * @see admin/mcp/tools
		 */
		'save' => $editor,

		/** admin/mcp/resource/review/delete
		 * List of user groups that are allowed to delete review items
		 *
		 * The "review-delete" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/review/get
		 * @see admin/mcp/resource/review/save
		 * @see admin/mcp/tools
		 */
		'delete' => $editor,
	],
	'rule' => [
		/** admin/mcp/resource/rule/get
		 * List of user groups that are allowed to view rule items
		 *
		 * The "rule-get", "rule-search" and "rule-config" MCP tools can only be
		 * executed by users who are members of at least one of the configured
		 * groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/rule/save
		 * @see admin/mcp/resource/rule/delete
		 * @see admin/mcp/tools
		 */
		'get' => $editor,

		/** admin/mcp/resource/rule/save
		 * List of user groups that are allowed to create and update rule items
		 *
		 * The "rule-save" MCP tool can only be executed by users who are members
		 * of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/rule/get
		 * @see admin/mcp/resource/rule/delete
		 * @see admin/mcp/tools
		 */
		'save' => $editor,

		/** admin/mcp/resource/rule/delete
		 * List of user groups that are allowed to delete rule items
		 *
		 * The "rule-delete" MCP tool can only be executed by users who are members
		 * of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/rule/get
		 * @see admin/mcp/resource/rule/save
		 * @see admin/mcp/tools
		 */
		'delete' => $editor,
	],
	'service' => [
		/** admin/mcp/resource/service/get
		 * List of user groups that are allowed to view service items
		 *
		 * The "service-get", "service-find", "service-search" and "service-config"
		 * MCP tools can only be executed by users who are members of at least one
		 * of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/service/save
		 * @see admin/mcp/resource/service/delete
		 * @see admin/mcp/tools
		 */
		'get' => $admin,

		/** admin/mcp/resource/service/save
		 * List of user groups that are allowed to create and update service items
		 *
		 * The "service-save" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/service/get
		 * @see admin/mcp/resource/service/delete
		 * @see admin/mcp/tools
		 */
		'save' => $admin,

		/** admin/mcp/resource/service/delete
		 * List of user groups that are allowed to delete service items
		 *
		 * The "service-delete" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/service/get
		 * @see admin/mcp/resource/service/save
		 * @see admin/mcp/tools
		 */
		'delete' => $admin,
	],
	'stock' => [
		/** admin/mcp/resource/stock/get
		 * List of user groups that are allowed to view stock items
		 *
		 * The "stock-get" and "stock-search" MCP tools can only be executed by
		 * users who are members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/stock/save
		 * @see admin/mcp/resource/stock/delete
		 * @see admin/mcp/tools
		 */
		'get' => $editor,

		/** admin/mcp/resource/stock/save
		 * List of user groups that are allowed to create and update stock items
		 *
		 * The "stock-save" MCP tool can only be executed by users who are members
		 * of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/stock/get
		 * @see admin/mcp/resource/stock/delete
		 * @see admin/mcp/tools
		 */
		'save' => $editor,

		/** admin/mcp/resource/stock/delete
		 * List of user groups that are allowed to delete stock items
		 *
		 * The "stock-delete" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/stock/get
		 * @see admin/mcp/resource/stock/save
		 * @see admin/mcp/tools
		 */
		'delete' => $editor,
	],
	'subscription' => [
		/** admin/mcp/resource/subscription/get
		 * List of user groups that are allowed to view subscription items
		 *
		 * The "subscription-get" and "subscription-search" MCP tools can only be
		 * executed by users who are members of at least one of the configured
		 * groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/subscription/save
		 * @see admin/mcp/resource/subscription/delete
		 * @see admin/mcp/tools
		 */
		'get' => $editor,

		/** admin/mcp/resource/subscription/save
		 * List of user groups that are allowed to create and update subscription items
		 *
		 * The "subscription-save" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/subscription/get
		 * @see admin/mcp/resource/subscription/delete
		 * @see admin/mcp/tools
		 */
		'save' => $editor,

		/** admin/mcp/resource/subscription/delete
		 * List of user groups that are allowed to delete subscription items
		 *
		 * The "subscription-delete" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/subscription/get
		 * @see admin/mcp/resource/subscription/save
		 * @see admin/mcp/tools
		 */
		'delete' => $editor,
	],
	'supplier' => [
		/** admin/mcp/resource/supplier/get
		 * List of user groups that are allowed to view supplier items
		 *
		 * The "supplier-get", "supplier-find" and "supplier-search" MCP tools can
		 * only be executed by users who are members of at least one of the
		 * configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/supplier/save
		 * @see admin/mcp/resource/supplier/delete
		 * @see admin/mcp/tools
		 */
		'get' => $editor,

		/** admin/mcp/resource/supplier/save
		 * List of user groups that are allowed to create and update supplier items
		 *
		 * The "supplier-save" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/supplier/get
		 * @see admin/mcp/resource/supplier/delete
		 * @see admin/mcp/tools
		 */
		'save' => $editor,

		/** admin/mcp/resource/supplier/delete
		 * List of user groups that are allowed to delete supplier items
		 *
		 * The "supplier-delete" MCP tool can only be executed by users who are
		 * members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/supplier/get
		 * @see admin/mcp/resource/supplier/save
		 * @see admin/mcp/tools
		 */
		'delete' => $editor,
	],
	'text' => [
		/** admin/mcp/resource/text/get
		 * List of user groups that are allowed to view text items
		 *
		 * The "text-get" and "text-search" MCP tools can only be executed by users
		 * who are members of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/text/save
		 * @see admin/mcp/resource/text/delete
		 * @see admin/mcp/tools
		 */
		'get' => $editor,

		/** admin/mcp/resource/text/save
		 * List of user groups that are allowed to create and update text items
		 *
		 * The "text-save" MCP tool can only be executed by users who are members
		 * of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/text/get
		 * @see admin/mcp/resource/text/delete
		 * @see admin/mcp/tools
		 */
		'save' => $editor,

		/** admin/mcp/resource/text/delete
		 * List of user groups that are allowed to delete text items
		 *
		 * The "text-delete" MCP tool can only be executed by users who are members
		 * of at least one of the configured groups.
		 *
		 * @param array List of user group names
		 * @since 2026.10
		 * @see admin/mcp/resource/text/get
		 * @see admin/mcp/resource/text/save
		 * @see admin/mcp/tools
		 */
		'delete' => $editor,
	],
];
