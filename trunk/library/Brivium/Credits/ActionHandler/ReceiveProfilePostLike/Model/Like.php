<?php

class Brivium_Credits_ActionHandler_ReceiveProfilePostLike_Model_Like extends XFCP_Brivium_Credits_ActionHandler_ReceiveProfilePostLike_Model_Like
{
	public function likeContent($contentType, $contentId, $contentUserId, $likeUserId = null, $likeDate = null)
	{
		if($contentType=='profile_post' && $contentUserId){
			$visitor = XenForo_Visitor::getInstance();
			if ($likeUserId === null)
			{
				$likeUserId = $visitor['user_id'];
			}
			if (!$likeUserId || $contentUserId == $likeUserId)
			{
				return false;
			}
			$dataCredit = array(
				'content_id' 		=>	$contentId,
				'content_type'		=>	'profile_post',
				'user_action_id' 	=>	$visitor['user_id'],
			);
			$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('receiveProfilePostLike',$contentUserId,$dataCredit);
		}
		return parent::likeContent($contentType, $contentId, $contentUserId, $likeUserId , $likeDate);
	}
}