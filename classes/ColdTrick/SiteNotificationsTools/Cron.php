<?php

namespace ColdTrick\SiteNotificationsTools;

use Elgg\Values;

class Cron {
	
	/**
	 * Cleanup unread site notifications after a retention period
	 *
	 * @param \Elgg\Hook $hook 'cron', 'daily'
	 *
	 * @return void
	 */
	public static function cleanupSiteNotifications(\Elgg\Hook $hook) {
		
		$retention = (int) elgg_get_plugin_setting('retention', 'site_notifications_tools');
		if ($retention < 1) {
			return;
		}
		
		$time = $hook->getParam('time', time());
		$working_date = Values::normalizeTime($time);
		$working_date->modify("-{$retention} days");
		
		echo 'Starting Site notifications cleanup' . PHP_EOL;
		elgg_log('Starting Site notifications cleanup', 'NOTICE');
		
		elgg_call(ELGG_IGNORE_ACCESS, function() use ($working_date) {
			
			$site_notifications = elgg_get_entities([
				'type' => 'object',
				'subtype' => 'site_notification',
				'limit' => false,
				'metadata_name_value_pairs' => [
					'read' => false,
				],
				'created_before' => $working_date,
				'batch' => true,
				'batch_inc_offset' => false,
			]);
			/* @var $notification SiteNotification */
			foreach ($site_notifications as $notification) {
				$notification->delete();
			}
		});
		
		echo 'Finished Site notifications cleanup' . PHP_EOL;
		elgg_log('Finished Site notifications cleanup', 'NOTICE');
	}
}
