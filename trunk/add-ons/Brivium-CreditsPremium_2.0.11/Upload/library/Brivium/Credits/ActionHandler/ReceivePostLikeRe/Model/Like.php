<?php

class Brivium_Credits_ActionHandler_ReceivePostLikeRe_Model_Like extends XFCP_Brivium_Credits_ActionHandler_ReceivePostLikeRe_Model_Like
{
	public function unlikeContent(array $like)
	{
		if($like['content_type']=='post' && $like['content_user_id'] != $like['like_user_id']){
			if ($like['like_user_id'] === null)
			{
				$likeUserId = XenForo_Visitor::getUserId();
			}
			if (!$like['like_user_id'])
			{
				return false;
			}
			$GLOBALS['BRC_LikePostRe_Type'] = true;
		}
		return parent::unlikeContent($like);
	}
}