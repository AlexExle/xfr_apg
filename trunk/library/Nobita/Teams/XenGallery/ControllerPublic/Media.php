<?php

class Nobita_Teams_XenGallery_ControllerPublic_Media extends XFCP_Nobita_Teams_XenGallery_ControllerPublic_Media
{
	public function actionIndex()
	{
		$response = parent::actionIndex();
		if ($response instanceof XenForo_ControllerResponse_View)
		{
			$subView = $response->subView;
			if ($subView instanceof XenForo_ControllerResponse_View
				&& !empty($subView->params)
			)
			{
				$subViewParams =& $subView->params;

				if (!empty($subViewParams['media']))
				{
					foreach($subViewParams['media'] as $mediaId => $media)
					{
						if (isset($media['team_privacy_state']) && $media['team_privacy_state'] == 'secret' && !empty($media['social_group_id']))
						{
							$group = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member')->getTeamMemberRecord($media['social_group_id']);

							$remove = false;
							if (empty($group))
							{
								$remove = true;
							}
							else
							{
								$remove = $group['member_state'] != 'accept';
							}

							if ($remove)
							{
								unset($subViewParams['media'][$mediaId]);
							}
						}
					}
				}
			}
		}

		return $response;
	}

	public function actionSaveMedia()
	{
		$groupId = $this->_input->filterSingle('group_id', XenForo_Input::UINT);
		if(!empty($groupId))
		{
			// Fixed bug: http://nobita.me/threads/633/
			Nobita_Teams_XenGallery_Media::setGroupId($groupId);
			$this->_request->setParam('container_type', 'category');
			$this->_request->setParam('container_id', Nobita_Teams_Option::get('XenMediaCategoryId'));
		}

		$response = parent::actionSaveMedia();
		if ($response instanceof XenForo_ControllerResponse_Redirect
			&& $groupId
		)
		{
			$response->redirectTarget = $this->_buildLink(TEAM_ROUTE_PREFIX . '/photos', array('team_id' => $groupId));
		}

		return $response;
	}

	public function actionView()
	{
		$response = parent::actionView();
		if ($response instanceof XenForo_ControllerResponse_View)
		{
			$params = $response->params;
			if (!empty($params['media']['social_group_id']))
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
					$this->_buildLink(TEAM_ROUTE_PREFIX . '/media', $params['media'])
				);
			}
		}

		return $response;
	}

}
