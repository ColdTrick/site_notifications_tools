<?php

$user_guid = (int) get_input('user_guid');

$user = get_user($user_guid);
if (!$user instanceof ElggUser || !$user->canEdit()) {
	return elgg_error_response(elgg_echo('actionunauthorized'));
}

// depending on how many items there are this could take a while
set_time_limit(0);

$site_notifications = elgg_get_entities([
	'type' => 'object',
	'subtype' => 'site_notification',
	'owner_guid' => $user->guid,
	'limit' => false,
	'metadata_name_value_pairs' => [
		'read' => false,
	],
	'batch' => true,
	'batch_inc_offset' => false,
]);
/* @var $notification SiteNotification */
foreach ($site_notifications as $notification) {
	if (!$notification->delete()) {
		return elgg_error_response(elgg_echo('site_notifications_tools:action:delete_all:error'));
	}
}

return elgg_ok_response('', elgg_echo('site_notifications:success:delete'));
