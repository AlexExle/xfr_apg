<?php

class Nobita_Teams_ControllerAdmin_Forum extends XenForo_ControllerAdmin_Forum
{
    protected function _preDispatch($action)
	{
		$this->assertAdminPermission('socialGroups');

        return parent::_preDispatch($action);
	}

    public function actionEdit()
    {
        $response = parent::actionEdit();
        if($response instanceof XenForo_ControllerResponse_View) {
            $response->templateName = 'Team_forum_edit';
            $params =& $response->params;

            $teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
            if(!empty($params['forum']) && !empty($params['forum']['team_id']))
            {
                $params['team'] = $teamModel->getTeamById($response->params['forum']['team_id']);
            }

            $nodeParentOptions = $params['nodeParentOptions'];

            foreach($nodeParentOptions as $id => $nodeParent)
            {
                if($nodeParent['node_type_id'] != Nobita_Teams_Listener::NODE_TYPE_ID)
                {
                    unset($nodeParentOptions[$id]);
                }
            }
            
            $params['nodeParentOptions'] = $nodeParentOptions;
        }

        return $response;
    }

    public function actionSave()
    {
        $this->_assertPostOnly();

		if ($this->_input->filterSingle('delete', XenForo_Input::STRING))
		{
			return $this->responseReroute('XenForo_ControllerAdmin_Forum', 'deleteConfirm');
		}

		$nodeId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
        $prefixIds = $this->_input->filterSingle('available_prefixes', XenForo_Input::UINT, array('array' => true));

        $writerData = $this->_input->filter(array(
			'title' => XenForo_Input::STRING,
			'node_name' => XenForo_Input::STRING,
			'display_order' => XenForo_Input::UINT,
			'description' => XenForo_Input::STRING,
            'allowed_watch_notifications' => XenForo_Input::STRING,
            'default_sort_order' => XenForo_Input::STRING,
			'default_sort_direction' => XenForo_Input::STRING,
            'list_date_limit_days' => XenForo_Input::UINT,
		));

        $writerData = array_merge($writerData, array(
            'node_type_id' => Nobita_Teams_Listener::NODE_TYPE_ID,
            'style_id' => 0,
            'moderate_threads' => 0,
            'moderate_replies' => 0,
            'allow_posting' => 1,
            'allow_poll' => 1,
            'count_messages' => 1,
            'find_new' => 0,
            'default_prefix_id' => 0,
            'require_prefix' => 0,
            'min_tags' => 0
        ));

        $writerData['team_id'] = $this->_input->filterSingle('_team_id', XenForo_Input::UINT);
        $writer = $this->_getNodeDataWriter();

		if ($nodeId)
		{
			$writer->setExistingData($nodeId);
		}

		if (!in_array($writerData['default_prefix_id'], $prefixIds))
		{
			$writerData['default_prefix_id'] = 0;
		}

		$writer->bulkSet($writerData);
		$writer->save();

		$this->_getPrefixModel()->updatePrefixForumAssociationByForum($writer->get('node_id'), $prefixIds);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('nodes') . $this->getLastHash($writer->get('node_id'))
		);
    }

    protected function _getNodeDataWriter()
    {
        return XenForo_DataWriter::create('XenForo_DataWriter_Forum');
    }
}
