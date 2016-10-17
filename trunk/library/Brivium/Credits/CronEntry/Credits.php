<?php

/**
 * Cron entry for updating view counts.
 */
class Brivium_Credits_CronEntry_Credits
{
	/**
	 * Updates view counters for various content types.
	 */
	public static function runCreditUpdate()
	{
		XenForo_Model::create('Brivium_Credits_Model_CreditStast')->updateCreditStasts();
	}
}