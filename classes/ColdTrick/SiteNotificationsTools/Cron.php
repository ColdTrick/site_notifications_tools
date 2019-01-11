<?php

namespace ColdTrick\SiteNotificationsTools;

use Elgg\Values;
use Elgg\Database\Clauses\OrderByClause;

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
			
			// in case of a large backlog don't try to cleanup everything at once
			// only take 60 sec to cleanup
			$time_left = 60;
			set_time_limit(120);
			
			$site_notifications = elgg_get_entities([
				'type' => 'object',
				'subtype' => 'site_notification',
				'limit' => false,
				'metadata_name_value_pairs' => [
					'read' => false,
				],
				'created_before' => $working_date,
				'order_by' => new OrderByClause('e.time_created', 'asc'),
				'batch' => true,
				'batch_inc_offset' => false,
			]);
			/* @var $notification SiteNotification */
			foreach ($site_notifications as $notification) {
				$notification->delete();
				
				// reduce timer
				$time_left = $time_left - microtime(true);
				if ($time_left < 0) {
					// no more time this run
					break;
				}
			}
		});
		
		echo 'Finished Site notifications cleanup' . PHP_EOL;
		elgg_log('Finished Site notifications cleanup', 'NOTICE');
	}
}
