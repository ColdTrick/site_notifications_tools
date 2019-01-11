<?php

/* @var $plugin \ElggPlugin */
$plugin = elgg_extract('entity', $vars);

echo elgg_view_field([
	'#type' => 'checkbox',
	'#label' => elgg_echo('site_notifications_tools:settings:delete_all'),
	'#help' => elgg_echo('site_notifications_tools:settings:delete_all:help'),
	'name' => 'params[delete_all]',
	'value' => 1,
	'checked' => (bool) $plugin->delete_all,
	'switch' => true,
]);
