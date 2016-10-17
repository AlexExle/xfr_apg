<?php
class Brivium_KeyCode_ControllerPublic_KeyCode extends XenForo_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		$codeModel = $this->_getCodeModel();
		$categories = $codeModel->getAllCategories(1);
		$visitor = XenForo_Visitor::getInstance();
		foreach($categories AS $k=>&$category){
			$conditions['code_category_id'] = $category['code_category_id'];
			if(!$codeModel->checkInclude($visitor,$category)){
				unset($categories[$k]);
			}else{
				$category['amount_left'] = count($codeModel->getUnusingCodesByCategoryId($category['code_category_id']));
			}
		}
		$viewParams = array(
			'categories' => $categories,
		);
		return $this->responseView('Brivium_KeyCode_ViewPublic_KeyCode_ListCodes', 'BRKC_list_categories', $viewParams);
	}

	public function actionViewCodes()
	{
		$this->_assertRegistrationRequired();
		$codeModel = $this->_getCodeModel();
		$userId = XenForo_Visitor::getUserId();
		$categoryId = $this->_input->filterSingle('code_category_id', XenForo_Input::UINT);

		$category = $codeModel->getCategoryById($categoryId);
		if(!$category){
			return $this->responseError(new XenForo_Phrase('BRKC_requested_category_not_found'));
		}

		$conditions = array(
			'user_id' => $userId,
			'code_category_id' => $categoryId,
		);

		$totalCodes = $codeModel->countCodes($conditions);
		$codes = $codeModel->getCodes($conditions);
		$viewParams = array(
			'category' => $category,
			'codes' => $codes,
		);
		return $this->responseView(
			'Brivium_KeyCode_ViewPublic_ViewCodes',
			'BRKC_code_list',
			$viewParams
		);
	}

	public function actionViewCode()
	{
		$this->_assertRegistrationRequired();
		$codeModel = $this->_getCodeModel();
		$userId = XenForo_Visitor::getUserId();
		$codeId = $this->_input->filterSingle('code_id', XenForo_Input::UINT);
		$codeModel = $this->_getCodeModel();
		$code = $codeModel->getCodeById($codeId);
		if(!$code){
			return $this->responseError(new XenForo_Phrase('BRKC_requested_code_not_found'));
		}
		$category = $codeModel->getCategoryById($code['code_category_id']);
		if(!$category){
			return $this->responseError(new XenForo_Phrase('BRKC_requested_category_not_found'));
		}

		$viewParams = array(
			'category' => $category,
			'code' => $code,
		);
		return $this->responseView(
			'Brivium_KeyCode_ViewPublic_ViewCode',
			'BRKC_view_code',
			$viewParams
		);
	}

	public function actionGetCode()
	{
		$this->_assertRegistrationRequired();
		$visitor = XenForo_Visitor::getInstance();
		$categoryId = $this->_input->filterSingle('code_category_id', XenForo_Input::UINT);

		$codeModel = $this->_getCodeModel();

		$creditVersion = $codeModel->checkCreditVersion();
		if(!$creditVersion){
			return $this->responseError(new XenForo_Phrase('BRKC_credit_addon_required'));
		}

		$category = $codeModel->getCategoryById($categoryId);
		if(!$category){
			return $this->responseError(new XenForo_Phrase('BRKC_requested_category_not_found'));
		}

		$options = XenForo_Application::get("options");

		if(!$codeModel->checkInclude($visitor,$category)|| !$visitor['user_id']){
			return $this->responseError(new XenForo_Phrase('BRKC_do_not_have_permission_to_get_code_from_this_category',array('category' => $category['title'])));
		}
		if ($this->isConfirmedPost())
		{
			$code = $codeModel->getUnusingCodeByCategoryId($categoryId);
			if(!$code){
				return $this->responseError(new XenForo_Phrase('BRKC_no_codes_have_been_added_in_x',array('category' => $category['title'])));
			}
			$conditions = array(
				'user_id' => $visitor['user_id'],
			);
			$time = XenForo_Locale::getDayStartTimestamps();

			$maxCodeOptions = $options->BRKC_maxCodeOptions;

			if(!empty($maxCodeOptions['enabled'])){
				// count total codes get
				$totalCodesGet = $codeModel->countCodes($conditions);
				if(!empty($maxCodeOptions['max_total']) && $totalCodesGet >= $maxCodeOptions['max_total']){
					return $this->responseError(new XenForo_Phrase('BRKC_sorry_you_already_get_x_codes',array('number'=>$maxCodeOptions['max_total'])));
				}
				$totalCodesGet = 0;

				// count total codes get in day
				$conditions['date_receive'] = array('>=',$time['today']);
				$totalCodesGet = $codeModel->countCodes($conditions);
				if(!empty($maxCodeOptions['max_day']) && $totalCodesGet >= $maxCodeOptions['max_day']){
					return $this->responseError(new XenForo_Phrase('BRKC_sorry_you_already_get_x_codes_in_day',array('number'=>$maxCodeOptions['max_day'],'category' => $category['title'])));
				}
			}

			$maxCodeOptions = @unserialize($category['max_code_options']);
			if(!empty($maxCodeOptions['enabled'])){
				// count total codes get in category
				if(isset($conditions['date_receive']))unset($conditions['date_receive']);
				$totalCodesGet = $codeModel->countCodes($conditions);
				if(!empty($maxCodeOptions['max_day']) && $totalCodesGet >= $maxCodeOptions['max_day']){
					return $this->responseError(new XenForo_Phrase('BRKC_sorry_you_already_get_x_codes_of_y_in_day',array('number'=>$maxCodeOptions['max_total'],'category' => $category['title'])));
				}
				$totalCodesGet = 0;

				// count total codes get in category in day
				$conditions['code_category_id'] = $categoryId;
				$conditions['date_receive'] = array('>=',$time['today']);

				$totalCodesGet = $codeModel->countCodes($conditions);
				if(!empty($maxCodeOptions['max_total']) && $totalCodesGet >= $maxCodeOptions['max_total']){
					return $this->responseError(new XenForo_Phrase('BRKC_sorry_you_already_get_x_codes_in_y',array('number'=>$maxCodeOptions['max_day'],'category' => $category['title'])));
				}
				$totalCodesGet = 0;
			}

			$creditModel = $this->_getCreditModel();
			$currencyId = $category['currency_id'];
			$price = $category['price'];
			$currency = array();
			if($creditVersion >= 1000000){
				list($event, $currency) = $this->_getCreditHelper()->assertEventAndCurrencyValidAndViewable('BRKC_PurchaseKeyCode',$currencyId);
				$userCredit = $visitor[$currency['column']];
				if ( ($userCredit - $price) < 0) {
					return $this->responseError(new XenForo_Phrase('BRC_not_enough_amount',array('amount' => XenForo_Application::get('brcCurrencies')->currencyFormat($price,false,$currencyId))));
				}

			}else{
				$userCredit = $visitor['credits'];
				if ( ($userCredit - $price) < 0) {
					return $this->responseError(new XenForo_Phrase('BRC_not_enough_amount',array('amount' => Brivium_Credits_Currency::currencyFormat($price))));
				}
			}


			$message = new XenForo_Phrase('BRKC_purchase_key_code_of_x',array('title'=>$category['title']));
			$dataCredit = array(
				'amount' 			=>	-$price,
				'user'				=>	$visitor->toArray(),
				'content_id'		=>	$code['code_id'],
				'content_type'		=>	'key_code',
				'currency_id'		=>	!empty($currency['currency_id'])?$currency['currency_id']:0,
				'message' 			=>	$message->render(),
			);

			if(!$creditModel->updateUserCredit('BRKC_PurchaseKeyCode', $visitor['user_id'], $dataCredit, $errorString)){
				return $this->responseError($errorString);
			}

			$writer = XenForo_DataWriter::create('Brivium_KeyCode_DataWriter_Code');
			$writer->setExistingData($code['code_id']);
			$writer->set('user_id',XenForo_Visitor::getUserId());
			$writer->set('date_receive',XenForo_Application::$time);
			$writer->save();

			$code = $writer->getMergedData();

			$codeModel->sendEmail($visitor, $code, $userCredit, $category);

			$viewParams = array(
				'category' => $category,
				'code' => $code
			);
			return $this->responseView(
				'Brivium_KeyCode_ViewPublic_KeyCode_GetCode',
				'BRKC_view_code',
				$viewParams
			);
		}else{
			$category = $codeModel->getCategoryById($categoryId);
			$viewParams = array(
				'category' => $category,
				'code' => array(),
			);
			return $this->responseView('Brivium_KeyCode_ViewPublic_KeyCode','BRKC_get_code_confirm',$viewParams);
		}
	}


	public static function getSessionActivityDetailsForList(array $activities)
	{
		$output = array();
		foreach ($activities AS $key => $activity)
		{
			$output[$key] = new XenForo_Phrase('BRKC_viewing_key_code');
		}

		return $output;
	}


	protected function _getCreditHelper()
	{
		return $this->getHelper('Brivium_Credits_ControllerHelper_Credit');
	}
	/**
	 * Gets the action model.
	 *
	 * @return Brivium_Credits_Model_Credit
	 */
	protected function _getCreditModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Credit');
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
}