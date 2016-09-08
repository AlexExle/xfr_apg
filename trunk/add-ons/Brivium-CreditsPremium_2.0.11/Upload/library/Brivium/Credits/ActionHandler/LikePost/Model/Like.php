<?php

class Brivium_Credits_ActionHandler_LikePost_Model_Like extends XFCP_Brivium_Credits_ActionHandler_LikePost_Model_Like
{
	public function likeContent($contentType, $contentId, $contentUserId, $likeUserId = null, $likeDate = null)
	{
		if($contentType=='post'){
			if ($likeUserId === null)
			{
				$likeUserId = XenForo_Visitor::getUserId();
			}
			if (!$likeUserId || $contentUserId == $likeUserId)
			{
				return false;
			}
			$GLOBALS['BRC_LikePost_Type'] = true;
		}
		return parent::likeContent($contentType, $contentId, $contentUserId, $likeUserId , $likeDate);
	}
}