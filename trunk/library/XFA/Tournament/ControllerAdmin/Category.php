<?php
 /*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
 * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/

class XFA_Tournament_ControllerAdmin_Category extends XenForo_ControllerAdmin_Abstract
{
    public function actionIndex()
    {
		$viewParams = array(
			'categories' => $this->_getCategoryModel()->getAllCategories()
		);
		
		return $this->responseView('XFA_Tournament_ViewAdmin_Category_List', 'xfa_tourn_category_list', $viewParams);
	}

	protected function _getCategoryAddEditResponse(array $category)
	{
		if (!empty($category['thread_node_id']))
		{
			$threadPrefixes = $this->getModelFromCache('XenForo_Model_ThreadPrefix')->getPrefixOptions(array(
				'node_id' => $category['thread_node_id']
			));
		}
		else
		{
			$threadPrefixes = array();
		}

		$viewParams = array(
			'category'          => $category,
			'nodes'             => $this->getModelFromCache('XenForo_Model_Node')->getAllNodes(),
			'threadPrefixes'    => $threadPrefixes,
		);
		return $this->responseView('XFA_Tournament_ViewAdmin_Category_Edit', 'xfa_tourn_category_edit', $viewParams);
	}

	public function actionAdd()
	{
		$category = array(
			'display_order' => 1
		);
		
		return $this->_getCategoryAddEditResponse($category);
	}

	public function actionEdit()
	{
		$category = $this->_getCategoryOrError();
		
		return $this->_getCategoryAddEditResponse($category);
	}
	
	public function actionSave()
	{
		$this->_assertPostOnly();

		$categoryId = $this->_input->filterSingle('tournament_category_id', XenForo_Input::STRING);

		$dwInput = $this->_input->filter(array(
			'category_title'        => XenForo_Input::STRING,
			'category_description'  => XenForo_Input::STRING,
			'display_order'         => XenForo_Input::UINT,
			'thread_node_id'        => XenForo_Input::UINT,
			'thread_prefix_id'      => XenForo_Input::UINT,
		));

		$dw = XenForo_DataWriter::create('XFA_Tournament_DataWriter_Category');
		if ($categoryId)
		{
			$dw->setExistingData($categoryId);
		}
		$dw->bulkSet($dwInput);
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('tournament-categories') . $this->getLastHash($dw->get('tournament_category_id'))
		);
	}	
	
	public function actionDelete()
	{
		if ($this->isConfirmedPost())
		{
			return $this->_deleteData(
				'XFA_Tournament_DataWriter_Category', 'tournament_category_id',
				XenForo_Link::buildAdminLink('tournament-categories/delete-clean-up', null, array(
					'tournament_category_id' => $this->_input->filterSingle('tournament_category_id', XenForo_Input::UINT),
					'_xfToken' => XenForo_Visitor::getInstance()->csrf_token_page
				))
			);
		}
		else
		{
			$viewParams = array(
				'category' => $this->_getCategoryOrError()
			);
			return $this->responseView('XFA_Tournament_ViewAdmin_Category_Delete', 'xfa_tourn_category_delete', $viewParams);
		}
	}

	public function actionDeleteCleanUp()
	{
		$this->_checkCsrfFromToken($this->_input->filterSingle('_xfToken', XenForo_Input::STRING));

		$id = $this->_input->filterSingle('tournament_category_id', XenForo_Input::UINT);

		$info = $this->_getCategoryModel()->getCategoryById($id);
		if (!$id || $info)
		{
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL,
				XenForo_Link::buildAdminLink('tournament-categories')
			);
		}

		$tournaments = $this->_getTournamentModel()->getTournaments(array(
			'tournament_category_id' => $id
		), array('limit' => 100));
		if (!$tournaments)
		{
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL,
				XenForo_Link::buildAdminLink('tournament-categories')
			);
		}

		$start = microtime(true);
		$limit = 10;

		foreach ($tournaments AS $tournament)
		{
			$dw = XenForo_DataWriter::create('XFA_Tournament_DataWriter_Tournament', XenForo_DataWriter::ERROR_SILENT);
			$dw->setExistingData($tournament);
			$dw->delete();

			if ($limit && microtime(true) - $start > $limit)
			{
				break;
			}
		}

		return $this->responseView('XFA_Tournament_ViewAdmin_Category_DeleteCleanUp', 'xfa_tourn_category_delete_clean_up', array(
			'tournament_category_id' => $id
		));
	}	
	
	protected function _getCategoryOrError($id = null)
	{
		if ($id === null)
		{
			$id = $this->_input->filterSingle('tournament_category_id', XenForo_Input::UINT);
		}

		$info = $this->_getCategoryModel()->getCategoryById($id);
		if (!$info)
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('requested_category_not_found'), 404));
		}

		return $info;
	}	
	
	protected function _getCategoryModel()
	{
		return $this->getModelFromCache('XFA_Tournament_Model_Category');
	}
	
	protected function _getTournamentModel()
	{
    	return $this->getModelFromCache('XFA_Tournament_Model_Tournament');
	}
}