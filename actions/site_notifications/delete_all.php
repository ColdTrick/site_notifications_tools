<?php

use Elgg\Database\Update;

$user_guid = (int) get_input('user_guid');

$user = get_user($user_guid);
if (!$user instanceof ElggUser || !$user->canEdit()) {
	return elgg_error_response(elgg_echo('actionunauthorized'));
}

// going to disable all notifications so later they can be deleted
// this is done for performance when removing large amounts of notifications
$update = Update::table('entities');

$sub = $update->subquery('metadata');
$sub->select('entity_guid')
	->where($update->compare('name', '=', 'read', ELGG_VALUE_STRING))
	->andWhere($update->compare('value', '=', 0, ELGG_VALUE_INTEGER));

$update->set('enabled', $update->param('no', ELGG_VALUE_STRING))
	->where($update->compare('owner_guid', '=', $user->guid, ELGG_VALUE_GUID))
	->andWhere($update->compare('type', '=', 'object', ELGG_VALUE_STRING))
	->andWhere($update->compare('subtype', '=', 'site_notification', ELGG_VALUE_STRING))
	->andWhere($update->compare('guid', 'in', $sub->getSQL()));

if (elgg()->db->updateData($update) === false) {
	return elgg_error_response(elgg_echo('site_notifications_tools:action:delete_all:error'));
}

return elgg_ok_response('', elgg_echo('site_notifications:success:delete'));
