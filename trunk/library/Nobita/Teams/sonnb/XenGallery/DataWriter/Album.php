<?php

class Nobita_Teams_sonnb_XenGallery_DataWriter_Album extends XFCP_Nobita_Teams_sonnb_XenGallery_DataWriter_Album
{
	protected function _getFields()
	{
		$fields = parent::_getFields();
		$fields['sonnb_xengallery_album']['team_id'] = array('type' => self::TYPE_UINT, 'default' => 0);

		return $fields;
	}

	protected function _preSave()
	{
		if (isset($GLOBALS[Nobita_Teams_Listener::XENGALLERY_CONTROLLERPUBLIC_ALBUM_ACTIONSAVE]))
		{
			$GLOBALS[Nobita_Teams_Listener::XENGALLERY_CONTROLLERPUBLIC_ALBUM_ACTIONSAVE]->SocialGroups_actionSave($this);
		}

		return parent::_preSave();
	}

	protected function _postSave()
	{
		if ($this->isInsert() OR $this->get('team_id'))
		{
			// Push an album into group NewsFeed
			/*Nobita_Teams_Container::getModel('Nobita_Teams_Model_Post')->publishNewsFeed($this->get('album_id'), 'sonnb_album', array(
				'message' => '',
				'user_id' => $this->get('user_id'),
				'username' => $this->get('username'),
				'team_id' => $this->get('team_id')
			));*/
		}

		return parent::_postSave();
	}

	protected function _postDelete()
	{
		if ($this->get('team_id'))
		{
		//	Nobita_Teams_Container::getModel('Nobita_Teams_Model_Post')->deleteNewsFeed();
		}

		return parent::_postDelete();
	}

}
