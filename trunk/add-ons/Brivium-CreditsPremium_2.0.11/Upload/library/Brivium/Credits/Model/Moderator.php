<?php

class Brivium_Credits_Model_Moderator extends XFCP_Brivium_Credits_Model_Moderator
{
	public function getGeneralModeratorInterfaceGroupIds()
	{
		$ids = parent::getGeneralModeratorInterfaceGroupIds();
		$ids[] = 'BR_creditsModerator';
		return $ids;
	}
}