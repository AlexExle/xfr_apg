<?php

class Brivium_Credits_ActionHandler_ReceivePostLike_ControllerPublic_Post extends XFCP_Brivium_Credits_ActionHandler_ReceivePostLike_ControllerPublic_Post
{
	public function actionLike()
	{
		$isPost = $this->_request->isPost();
		$response = parent::actionLike();
		$likeUserId = XenForo_Visitor::getUserId();
		if ($likeUserId && $isPost && !empty($GLOBALS['BRC_ReceivePostLike_Type']))
		{
			$nodeId = 0;
			if(!empty($response->params) && !empty($response->params['forum']) && !empty($response->params['post'])){
				$forum = $response->params['forum'];
				$post = $response->params['post'];
			}
			$postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);
			if(empty($forum['node_id'])){
				$ftpHelper = $this->getHelper('ForumThreadPost');
				list($post, $thread, $forum) = $ftpHelper->assertPostValidAndViewable($postId);
			}
			$nodeId  = !empty($forum['node_id'])?$forum['node_id']:0;

			if(!empty($nodeId) && $post['user_id']){
				$dataCredit = array(
					'content_id' 		=>	$postId,
					'content_type'		=>	'post',
					'node_id' 			=>	$nodeId,
					'user_action_id' 	=>	$post['user_id'],
					'extraData' 		=>	array('post_id'=>$postId)
				);
				$this->getModelFromCache('Brivium_Credits_Model_Credit')->updateUserCredit('receivePostLike', $post['user_id'], $dataCredit);
			}
			unset($GLOBALS['BRC_ReceivePostLike_Type']);
		}
		return $response;
	}
}