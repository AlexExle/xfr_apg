<?php

class Nobita_Teams_Deferred_Team extends XenForo_Deferred_Abstract
{
	public function execute(array $deferred, array $data, $targetRunTime, &$status)
	{
		$data = array_merge(array(
			'position' => 0,
			'batch' => 100
		), $data);
		$data['batch'] = max(1, $data['batch']);

		/* @var $teamModel Nobita_Teams_Model_Team */
		$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');

		$teamIds = $teamModel->getTeamIdsInRange($data['position'], $data['batch']);
		if (sizeof($teamIds) == 0)
		{
			return true;
		}

		foreach ($teamIds as $teamId)
		{
			$data['position'] = $teamId;

			$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team', XenForo_DataWriter::ERROR_SILENT);
			if ($dw->setExistingData($teamId))
			{
				$dw->rebuildCounters();
				$dw->updateTeamOwner();
				$dw->updateTeamPrivacyIssue();

				$dw->rebuildStaffList();
				$dw->save();
			}
		}

		$rbPhrase = new XenForo_Phrase('rebuilding');
		$typePhrase = new XenForo_Phrase('Teams_handler_phrase_key_teams');
		$status = sprintf('%s... %s (%s)', $rbPhrase, $typePhrase, XenForo_Locale::numberFormat($data['position']));

		return $data;
	}

	public function canCancel()
	{
		return true;
	}

}
