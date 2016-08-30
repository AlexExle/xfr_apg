<?php

class Nobita_Teams_XenGallery_DataWriter_Media extends XFCP_Nobita_Teams_XenGallery_DataWriter_Media
{
	/**
	 * @var array
	 */
	protected $_teamData;

	protected function _getFields()
	{
		$fields = parent::_getFields();
		$fields['xengallery_media']['social_group_id'] = array('type' => self::TYPE_UINT, 'default' => 0);

		return $fields;
	}

	protected function _preSave()
	{
		$groupId = Nobita_Teams_XenGallery_Media::getGroupId();
		if ($groupId)
		{
			$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');

			if (!$this->_teamData)
			{
				$this->_teamData = $teamModel->getFullTeamById($groupId);
			}

			if (!$this->_teamData)
			{
				$this->error(new XenForo_Phrase('Teams_requested_team_not_found'));
				return false;
			}

			if (!$teamModel->canViewTeam($this->_teamData, $this->_teamData))
			{
				// special errors
				$this->error(new XenForo_Phrase('Teams_requested_team_not_found'));
				return false;
			}

			$this->set('social_group_id', $this->_teamData['team_id']);
		}

		return parent::_preSave();
	}
}