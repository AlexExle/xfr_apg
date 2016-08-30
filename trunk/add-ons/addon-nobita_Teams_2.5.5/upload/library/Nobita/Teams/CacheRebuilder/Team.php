<?php

class Nobita_Teams_CacheRebuilder_Team extends XenForo_CacheRebuilder_Abstract
{
	/**
	 * Gets rebuild message.
	 */
	public function getRebuildMessage()
	{
		return new XenForo_Phrase('Teams_handler_phrase_key_teams');
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

		/* @var $teamModel Nobita_Teams_Model_Team */
		$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');

		$teamIds = $teamModel->getTeamIdsInRange($position, $options['batch']);
		if (sizeof($teamIds) == 0)
		{
			return true;
		}

		XenForo_Db::beginTransaction();

		foreach ($teamIds AS $teamId)
		{
			$position = $teamID;

			$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team', XenForo_DataWriter::ERROR_SILENT);
			if ($dw->setExistingData($teamId))
			{
				$dw->rebuildCounters();
				$dw->updateTeamOwner();
				$dw->updateTeamPrivacyIssue();

				$dw->save();
			}
		}

		XenForo_Db::commit();

		$detailedMessage = XenForo_Locale::numberFormat($position);

		return $position;
	}


}
