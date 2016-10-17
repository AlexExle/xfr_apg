<?php
class Brivium_KeyCode_ControllerAdmin_KeyCode extends XenForo_ControllerAdmin_Abstract
{
	public function actionIndex()
	{
		$codeModel = $this->_getCodeModel();

		$categoryId = $this->_input->filterSingle('code_category_id', XenForo_Input::UINT);
		$page = $this->_input->filterSingle('page', XenForo_Input::UINT);
		$perPage = 100;
		$conditions = array();

		$filter = $this->_input->filterSingle('_filter', XenForo_Input::ARRAY_SIMPLE);
		if ($filter && isset($filter['value']))
		{
			$conditions['code'] = array($filter['value'], empty($filter['prefix']) ? 'lr' : 'r');
			$filterView = true;
		}
		else
		{
			$filterView = false;
		}

		$pageParams = array();
		if($categoryId){
			$conditions['code_category_id'] = $categoryId;
			$pageParams['code_category_id'] = $categoryId;
		}else{
			//return $this->responseError(new XenForo_Phrase('BRKC_requested_category_not_found'));
		}
		$fetchOptions = array(
			'order' => 'date_receive',
			'page' => $page,
			'perPage' => $perPage,
			'join'	=>	Brivium_KeyCode_Model_Code::FETCH_CODE_FULL,
		);
		$totalCodes = $codeModel->countCodes($conditions);

		$codes = $codeModel->getCodes($conditions, $fetchOptions);

		$category = $codeModel->getCategoryById($categoryId);
		$viewParams = array(
			'category' => $category,
			'codes' => $codes,

			'page' => $page,
			'perPage' => $perPage,
			'total' => $totalCodes,
			'pageParams' => $pageParams,

			'filterView' => $filterView,
			'filterMore' => ($filterView && $totalCodes > $perPage)
		);
		return $this->responseView('Brivium_KeyCode_ViewAdmin_KeyCode_ListCodes', 'BRKC_code_list', $viewParams);
	}

	public function actionAdd()
	{
		return $this->responseReroute('Brivium_KeyCode_ControllerAdmin_KeyCode', 'edit');
	}

	public function actionEdit()
	{
		$codeId = $this->_input->filterSingle('code_id', XenForo_Input::UINT);
		$codeModel = $this->_getCodeModel();
		$code = $codeModel->getCodeById($codeId);
		$user = array();
		if($code['user_id']){
			$user = $this->_getUserModel()->getUserById($code['user_id']);
		}
		$categories = $codeModel->getCategoriesForOptionsTag();
		$viewParams = array(
			'categories' => $categories,
			'user' => $user,
			'code' => $code,
		);

		return $this->responseView('Brivium_KeyCode_ViewAdmin_KeyCode_Code', 'BRKC_code_edit', $viewParams);
	}

	public function actionSave()
	{
		$this->_assertPostOnly();

		$codeId = $this->_input->filterSingle('code_id', XenForo_Input::UINT);

		$dwInput = $this->_input->filter(array(
			'code' => XenForo_Input::STRING,
			'code_category_id' => XenForo_Input::UINT,
		));
		$userId = 0;
		$username = $this->_input->filterSingle('username', XenForo_Input::STRING);
		if ($username)
		{
			if ($user = $this->getModelFromCache('XenForo_Model_User')->getUserByName($username))
			{
				$userId = $user['user_id'];
			}
		}
		if ($codeId)
		{
			$dw = XenForo_DataWriter::create('Brivium_KeyCode_DataWriter_Code');
			$dw->setExistingData($codeId);
			if($dw->getExisting('user_id')!=$userId){
				$dw->set('user_id',$userId);
				if(!$dw->getExisting('user_id'))
				$dw->set('date_receive',$now);
			}
			$dw->bulkSet($dwInput);
			$dw->save();
		}else{
			$now = XenForo_Application::$time;
			$codes = explode("\n", $dwInput['code']);
			foreach($codes AS $code){
				$dw = XenForo_DataWriter::create('Brivium_KeyCode_DataWriter_Code', XenForo_DataWriter::ERROR_SILENT);
				$dw->set('code_category_id',$dwInput['code_category_id']);
				$dw->set('code',$code);
				$dw->set('user_id',$userId);
				$dw->set('date_receive',$now);
				$dw->save();
			}
		}

		$redirectType = ($codeId
			? XenForo_ControllerResponse_Redirect::RESOURCE_CREATED
			: XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED);

		return $this->responseRedirect(
			$redirectType,
			XenForo_Link::buildAdminLink('key-code','',array('code_category_id'=>$dwInput['code_category_id']))
		);
	}

	public function actionDelete()
	{
		$codeModel = $this->_getCodeModel();
		$codeId = $this->_input->filterSingle('code_id', XenForo_Input::UINT);

		if ($this->isConfirmedPost())
		{
			$dw = XenForo_DataWriter::create('Brivium_KeyCode_DataWriter_Code');
			$dw->setExistingData($codeId);
			$dw->delete();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('key-code')
			);
		}
		else // show confirmation dialog
		{
			$code = $codeModel->getCodeById($codeId);
			$viewParams = array(
				'code' => $code
			);

			return $this->responseView('Brivium_KeyCode_ViewAdmin_KeyCode_DeleteCode', 'BRKC_code_delete', $viewParams);
		}
	}
	/**
	 * Gets the action model.
	 *
	 * @return Brivium_KeyCode_Model_Code
	 */
	protected function _getCodeModel()
	{
		return $this->getModelFromCache('Brivium_KeyCode_Model_Code');
	}

	/**
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
}