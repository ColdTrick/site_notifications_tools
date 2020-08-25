<?php

namespace ColdTrick\SiteNotificationsTools;

use Elgg\DefaultPluginBootstrap;

class Bootstrap extends DefaultPluginBootstrap {
	
	/**
	 * {@inheritDoc}
	 * @see \Elgg\DefaultPluginBootstrap::init()
	 */
	public function init() {
		$this->registerHooks();
	}
	
	/**
	 * Register plugin hooks
	 *
	 * @return void
	 */
	protected function registerHooks() {
		$hooks = $this->elgg()->hooks;
		
		$hooks->registerHandler('cron', 'daily', __NAMESPACE__ . '\Cron::cleanupSiteNotifications');
		$hooks->registerHandler('cron', 'fifteenmin', __NAMESPACE__ . '\Cron::deleteSiteNotifications');
		$hooks->registerHandler('register', 'menu:title', __NAMESPACE__ . '\Menus\Title::registerDeleteAll');
	}
}
