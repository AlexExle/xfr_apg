<?php

class Brivium_Credits_CacheRebuilder_Credit extends XenForo_CacheRebuilder_Abstract
{
	/**
	 * Gets rebuild message.
	 */
	public function getRebuildMessage()
	{
		return new XenForo_Phrase('BRC_credits');
	}

	/**
	 * Shows the exit link.
	 */
	public function showExitLink()
	{
		return true;
	}

	/**
	 * Rebuilds the data.
	 *
	 * @see XenForo_CacheRebuilder_Abstract::rebuild()
	 */
	public function rebuild($position = 0, array &$options = array(), &$detailedMessage = '')
	{
		$options['batch'] = max(1, isset($options['batch']) ? $options['batch'] : 10);
		XenForo_Db::beginTransaction();

		XenForo_Model::create('Brivium_Credits_Model_Event')->rebuildEventCache();
		XenForo_Model::create('Brivium_Credits_Model_Currency')->rebuildCurrencyCaches();

		XenForo_Db::commit();

		$detailedMessage = XenForo_Locale::numberFormat($position);

		return true;
	}
}