<?php

$editor = ['admin', 'editor', 'super'];
$admin = ['admin', 'super'];
$super = ['super'];
$editorCrud = ['get' => $editor, 'save' => $editor, 'delete' => $editor];
$adminCrud = ['get' => $admin, 'save' => $admin, 'delete' => $admin];

return [
	'ai' => [
		'write' => $editor,
		'translate' => $editor,
		'imagine' => $editor,
		'isolate' => $editor,
	],
	'attribute' => $editorCrud,
	'catalog' => $editorCrud,
	'coupon' => $editorCrud + ['code' => $editorCrud],
	'customer' => ['get' => $editor, 'save' => $editor, 'delete' => $admin],
	'group' => ['get' => $editor, 'save' => $admin, 'delete' => $admin],
	'index' => ['get' => $editor],
	'locale' => [
		'get' => $admin,
		'save' => $admin,
		'delete' => $admin,
		'language' => ['get' => $editor, 'save' => $super, 'delete' => $super],
		'currency' => ['get' => $editor, 'save' => $super, 'delete' => $super],
		'site' => [
			'get' => $admin,
			'save' => $admin,
			'delete' => $super,
			'insert' => $super,
			'move' => $super,
		],
	],
	'media' => $editorCrud,
	'order' => ['get' => $editor, 'save' => $editor],
	'plugin' => $adminCrud,
	'price' => $editorCrud,
	'product' => $editorCrud,
	'review' => $editorCrud,
	'rule' => $editorCrud,
	'service' => $adminCrud,
	'stock' => $editorCrud,
	'subscription' => $editorCrud,
	'supplier' => $editorCrud,
	'text' => $editorCrud,
];
