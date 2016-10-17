<?php

class Nobita_Teams_ControllerAdmin_Category extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('socialGroups');
	}

	public function actionIndex()
	{
		$viewParams = array(
			'categories' => $this->_getCategoryModel()->getAllCategories()
		);

		return $this->responseView('Nobita_Teams_ViewAdmin_Category_List', 'Team_category_list', $viewParams);
	}

	/**
	 * Gets the category add/edit form response.
	 *
	 * @param array $category
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	protected function _getCategoryAddEditResponse(array $category)
	{
		$fieldModel = $this->_getFieldModel();
		$userGroups = $this->_getUserGroupModel()->getAllUserGroups();

		if (!empty($category['team_category_id']))
		{
			$categories = $this->_getCategoryModel()->getPossibleParentCategories($category);
			$selectedFields = $fieldModel->getFieldIdsInCategory($category['team_category_id']);

			$selUserGroupIds = explode(',', $category['allowed_user_group_ids']);
			if (in_array(-1, $selUserGroupIds))
			{
				$allUserGroups = true;
				$selUserGroupIds = array_keys($userGroups);
			}
			else
			{
				$allUserGroups = false;
			}

			$ribbons = @unserialize($category['ribbon_styling']);
			if (!$ribbons)
			{
				$ribbons = array();
			}
		}
		else
		{
			$categories = $this->_getCategoryModel()->getAllCategories();
			$selectedFields = array();
			$allUserGroups = true;
			$selUserGroupIds = array_keys($userGroups);
			$ribbons = array('userBanner bannerHidden');
		}
		$fields = $fieldModel->prepareTeamFields($fieldModel->getTeamFields());

		$displayStyles = array(
			'userBanner bannerHidden',
			'userBanner bannerPrimary',
			'userBanner bannerSecondary',
			'userBanner bannerRed',
			'userBanner bannerGreen',
			'userBanner bannerOlive',
			'userBanner bannerLightGreen',
			'userBanner bannerBlue',
			'userBanner bannerRoyalBlue',
			'userBanner bannerSkyBlue',
			'userBanner bannerGray',
			'userBanner bannerSilver',
			'userBanner bannerYellow',
			'userBanner bannerOrange',
		);

		$disableTabs = array();
		if (!empty($category['disable_tabs_default']))
		{
			$disableTabs = array_map('trim', explode(',', $category['disable_tabs_default']));
		}


		$viewParams = array(
			'category' => $category,
			'categories' => $categories,

			'fieldsGrouped' => $fieldModel->groupTeamFields($fields),
			'fieldGroups' => $fieldModel->getTeamFieldGroups(),
			'selectedFields' => $selectedFields,

			'allUserGroups' => $allUserGroups,
			'selUserGroupIds' => $selUserGroupIds,
			'userGroups' => $userGroups,

			'nodes' => Nobita_Teams_Container::getModel('XenForo_Model_Node')->getAllNodes(),

			'displayStyles' => $displayStyles,
			'ribbons' => $ribbons,

			'tabs' => Nobita_Teams_Option::getTabsSupported(),
			'disableTabs' => $disableTabs,
		);

		return $this->responseView('Nobita_Teams_ViewAdmin_Category_Edit', 'Team_category_edit', $viewParams);
	}

	/**
	 * Displays a form to create a new category.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionAdd()
	{
		return $this->_getCategoryAddEditResponse(array(
			'display_order' => 1,
			'parent_category_id' => $this->_input->filterSingle('parent_category_id', XenForo_Input::UINT),
			'allow_team_create' => 1,
			'allow_uploaded_file' => 0,
			'default_cover_path' => '',
			'default_privacy' => 'open',
			'min_tags' => 0
		));
	}

	/**
	 * Displays a form to edit an existing category.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionEdit()
	{
		$category = $this->_getCategoryOrError();

		$savedRedirect = XenForo_Application::getSession()->get('teamCategoryRedirect');
		if ($savedRedirect)
		{
			XenForo_Application::getSession()->remove('teamCategoryRedirect');

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$savedRedirect
			);
		}

		return $this->_getCategoryAddEditResponse($category);
	}

	public function actionSave()
	{
		$this->_assertPostOnly();

		$categoryId = $this->_input->filterSingle('team_category_id', XenForo_Input::STRING);

		$dwInput = $this->_input->filter(array(
			'category_title' => XenForo_Input::STRING,
			'category_description' => XenForo_Input::STRING,
			'parent_category_id' => XenForo_Input::UINT,
			'display_order' => XenForo_Input::UINT,
			'always_moderate_create' => XenForo_Input::BOOLEAN,
			'allow_team_create' => XenForo_Input::BOOLEAN,
			'allow_uploaded_file' => XenForo_Input::BOOLEAN,

			'default_cover_path' => XenForo_Input::STRING,
			'ribbon_styling' => XenForo_Input::ARRAY_SIMPLE,
			'default_privacy' => XenForo_Input::STRING,

			'min_tags' => XenForo_Input::UINT,

			'allow_enable_disable_tabs' => XenForo_Input::BOOLEAN,
		));

		$userGroupDw = $this->_input->filter(array(
			'usable_user_group_type' => XenForo_Input::STRING,
			'user_group_ids' => array(XenForo_Input::UINT, 'array' => true)
		));
		if ($userGroupDw['usable_user_group_type'] == 'all')
		{
			$allowedGroupIds = array(-1); // -1 is a sentinel for all groups
		}
		else
		{
			$allowedGroupIds = $userGroupDw['user_group_ids'];
		}

		$input = $this->_input->filter(array(
			'available_fields' => array(XenForo_Input::STRING, 'array' => true)
		));

		$disableTabs = $this->_input->filterSingle('disable_tabs', array(XenForo_Input::STRING, 'array' => true));

		$disabledTabs = '';
		if ($disableTabs)
		{
			$disabledTabs = implode(',', $disableTabs);
		}
		$dwInput['disable_tabs_default'] = $disabledTabs;

		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Category');

		if ($categoryId)
		{
			$dw->setExistingData($categoryId);
		}

		$dw->bulkSet($dwInput);
		$dw->set('allowed_user_group_ids', $allowedGroupIds);
		$dw->setExtraData(Nobita_Teams_DataWriter_Category::DATA_FIELD_IDS, $input['available_fields']);
		$dw->save();

		$categoryId = $dw->get('team_category_id');
		$redirect = $this->_buildLink('team-categories') . $this->getLastHash($categoryId);


		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			$redirect
		);
	}

	public function actionIcon()
	{
		$category = $this->_getCategoryOrError();

		return $this->responseView('Nobita_Teams_ViewAdmin_Category_Icon', 'Team_category_icon', array(
			'category' => $category
		));
	}

	public function actionIconUpload()
	{
		$this->_assertPostOnly();
		$category = $this->_getCategoryOrError();

		$icon = XenForo_Upload::getUploadedFiles('icon');
		$icon = reset($icon);

		$categoryModel = $this->_getCategoryModel();
		if ($icon)
		{
			$categoryModel->uploadCategoryIcon($icon, $category['team_category_id']);
		}
		else if ($this->_input->filterSingle('delete', XenForo_Input::UINT))
		{
			$categoryModel->deleteCategoryIcon($category['team_category_id']);
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('team-categories') . $this->getLastHash($category['team_category_id'])
		);
	}

	/**
	 * Deletes the specified category
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionDelete()
	{
		if ($this->isConfirmedPost())
		{
			return $this->_deleteData(
				'Nobita_Teams_DataWriter_Category', 'team_category_id',
				XenForo_Link::buildAdminLink('team-categories/delete-clean-up', null, array(
					'team_category_id' => $this->_input->filterSingle('team_category_id', XenForo_Input::UINT),
					'_xfToken' => XenForo_Visitor::getInstance()->csrf_token_page
				))
			);
		}
		else // show confirmation dialog
		{
			$viewParams = array(
				'category' => $this->_getCategoryOrError()
			);
			return $this->responseView('Nobita_Teams_ViewAdmin_Category_Delete', 'Team_category_delete', $viewParams);
		}
	}

	public function actionDeleteCleanUp()
	{
		$this->_checkCsrfFromToken($this->_input->filterSingle('_xfToken', XenForo_Input::STRING));

		$id = $this->_input->filterSingle('team_category_id', XenForo_Input::UINT);
		$category = $this->_getCategoryModel()->getCategoryById($id);

		if (!$id || $category)
		{
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL,
				XenForo_Link::buildAdminLink('team-categories')
			);
		}

		$teams = $this->_getTeamModel()->getTeams(
			array(
				'team_category_id' => $id,
				'deleted' => true,
				'moderated' => true
			),
			array(
				'limit' => 100
			)
		);
		if (!$teams)
		{
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL,
				XenForo_Link::buildAdminLink('team-categories')
			);
		}

		$start = microtime(true);
		$limit = 10;

		foreach ($teams AS $team)
		{
			$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team', XenForo_DataWriter::ERROR_SILENT);
			$dw->setExistingData($team);
			$dw->delete();

			if ($limit && microtime(true) - $start > $limit)
			{
				break;
			}
		}

		return $this->responseView('Nobita_Teams_ViewAdmin_Category_DeleteCleanUp', 'Team_category_delete_clean_up', array(
			'team_category_id' => $id
		));
	}

	/**
	 * Gets the specified record or errors.
	 *
	 * @param string $id
	 *
	 * @return array
	 */
	protected function _getCategoryOrError($id = null)
	{
		if ($id === null)
		{
			$id = $this->_input->filterSingle('team_category_id', XenForo_Input::UINT);
		}

		$info = $this->_getCategoryModel()->getCategoryById($id);
		if (!$info)
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('requested_category_not_found'), 404));
		}

		return $info;
	}

	protected function _getTeamModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
	}

	protected function _getCategoryModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Category');
	}

	protected function _getFieldModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Field');
	}

	protected function _getUserGroupModel()
	{
		return Nobita_Teams_Container::getModel('XenForo_Model_UserGroup');
	}
}
