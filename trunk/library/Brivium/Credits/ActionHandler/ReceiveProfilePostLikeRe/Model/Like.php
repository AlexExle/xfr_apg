<?php

class Brivium_Credits_ActionHandler_ReceiveProfilePostLikeRe_Model_Like extends XFCP_Brivium_Credits_ActionHandler_ReceiveProfilePostLikeRe_Model_Like
{
	public function unlikeContent(array $like)
	{
		if($like['content_type']=='post' && $like['content_user_id'] && $like['content_user_id'] != $like['like_user_id']){
			$visitor = XenForo_Visitor::getInstance();
			if ($like['like_user_id'] === null)
			{
				$like['like_user_id'] = $visitor['user_id'];
			}
			if (!$like['like_user_id'])
			{
				return false;
			}
			$dataCredit = array(
				'content_id' 		=>	$like['content_id'],
				'content_type'		=>	'profile_post',
				'user_action_id' 	=>	$like['like_user_id'],
			);
			$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('receiveProfilePostLikeRe',$like['content_user_id'],$dataCredit);
		}
		return parent::unlikeContent($like);
	}
}