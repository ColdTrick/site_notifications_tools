<?php

namespace ColdTrick\SiteNotificationsTools;

use Elgg\Values;
use Elgg\Database\Clauses\OrderByClause;
use Elgg\Database\QueryBuilder;

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
			/* @var $notification \SiteNotification */
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
	
	/**
	 * Delete all site notifications which have been marked to be deleted
	 *
	 * @param \Elgg\Hook $hook 'cron', 'fifteenmin'
	 *
	 * @return void
	 */
	public static function deleteSiteNotifications(\Elgg\Hook $hook) {
		
		echo 'Starting Site notifications deletion' . PHP_EOL;
		elgg_log('Starting Site notifications deletion', 'NOTICE');
		
		elgg_call(ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES, function() {
			$site_notifications = elgg_get_entities([
				'type' => 'object',
				'subtype' => 'site_notification',
				'limit' => false,
				'wheres' => [
					function(QueryBuilder $qb, $main_alias) {
						// disabled site notifications
						return $qb->compare("{$main_alias}.enabled", '=', 'no', ELGG_VALUE_STRING);
					},
					function(QueryBuilder $qb, $main_alias) {
						// from enabled users
						// when a user get disabled and then re-enabled all 'deleted' site notifications return
						$oe = $qb->joinEntitiesTable($main_alias, 'owner_guid', 'inner', 'oe');
						
						return $qb->merge([
							$qb->compare("{$oe}.type", '=', 'user', ELGG_VALUE_STRING),
							$qb->compare("{$oe}.enabled", '=', 'yes', ELGG_VALUE_STRING),
						]);
					},
				],
				'order_by' => new OrderByClause('e.time_created', 'asc'),
				'batch' => true,
				'batch_inc_offset' => false,
			]);
			
			$start_time = microtime(true);
			$max_duration = 300; // five minutes
			
			set_time_limit($max_duration + 10);
			
			/* @var $notification \SiteNotification */
			foreach ($site_notifications as $notification) {
				$notification->delete();
				
				if ((microtime(true) - $start_time) > $max_duration) {
					// max time reached
					break;
				}
			}
		});
		
		echo 'Finished Site notifications deletion' . PHP_EOL;
		elgg_log('Finished Site notifications deletion', 'NOTICE');
	}
}
