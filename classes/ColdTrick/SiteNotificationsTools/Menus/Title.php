<?php

namespace ColdTrick\SiteNotificationsTools\Menus;

use Elgg\Menu\MenuItems;

class Title {
	
	/**
	 * Add a delete all button, when needed
	 *
	 * @param \Elgg\Hook $hook 'register', 'menu:title'
	 *
	 * @return void|MenuItems
	 */
	public static function registerDeleteAll(\Elgg\Hook $hook) {
		
		$page_owner = elgg_get_page_owner_entity();
		if (!$page_owner instanceof \ElggUser || !$page_owner->canEdit()) {
			return;
		}
		
		if (!elgg_in_context('site_notifications')) {
			return;
		}
		
		if (!(bool) elgg_get_plugin_setting('delete_all', 'site_notifications_tools')) {
			return;
		}
		
		$count = elgg_get_entities([
			'type' => 'object',
			'subtype' => 'site_notification',
			'owner_guid' => $page_owner->guid,
			'count' => true,
			'metadata_name_value_pairs' => [
				'read' => false,
			],
		]);
		if (empty($count)) {
			return;
		}
		
		$result = $hook->getValue();
		
		$result[] = \ElggMenuItem::factory([
			'name' => 'delete_all',
			'text' => elgg_echo('site_notifications_tools:delete_all'),
			'href' => elgg_generate_action_url('site_notifications/delete_all', [
				'user_guid' => $page_owner->guid,
			]),
			'badge' => $count,
			'confirm' => elgg_echo('deleteconfirm:plural'),
			'link_class' => 'elgg-button elgg-button-delete',
		]);
		
		return $result;
	}
}
