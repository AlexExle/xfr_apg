<?php

class Brivium_Credits_ActionHandler_UpdateFullProfile_DataWriter_User extends XFCP_Brivium_Credits_ActionHandler_UpdateFullProfile_DataWriter_User
{
	/**
	 * Post-save handling.
	 */
	protected function _postSave()
	{
		$userId = $this->get('user_id');
		$creditModel = $this->_getCreditModel();
		$triggered = false;
		if (isset($GLOBALS['BRC_UpdateFullProfile_CPAccount'])) {
			$isComplete = $this->_checkUpdateFullProfile();
			$lastFullDate = $this->_checkGetUpdateFull($userId);
			if($isComplete && !$lastFullDate){
				$triggered = true;
				$creditModel->updateUserCredit('updateFullProfile',$userId);
			}
			unset($GLOBALS['BRC_UpdateFullProfile_CPAccount']);
		}

		if(!$triggered && $this->isChanged('avatar_date') && $this->get('avatar_date')){
			$isComplete = $this->_checkUpdateFullProfile();
			$lastFullDate = $this->_checkGetUpdateFull($userId);
			if($isComplete && !$lastFullDate){
				$creditModel->updateUserCredit('updateFullProfile',$userId);
			}
		}
		return parent::_postSave();
	}

	protected  $_updateFull = null;
	protected  $_getUpdateFull = null;

	protected function _checkGetUpdateFull($userId)
	{
		if(is_null($this->_getUpdateFull)){
			$transactionModel = $this->_getTransactionModel();
			$lastFullDate = $transactionModel->getLastTransactionDate(array(
				'action_id'	=>	'updateFullProfile',
				'user_id'	=>	$userId,
			));
			if($lastFullDate){
				$lastUnFullDate = $transactionModel->getLastTransactionDate(array(
					'action_id'	=>	'updateFullProfileRe',
					'user_id'	=>	$userId,
				));
				if($lastUnFullDate && $lastUnFullDate > $lastFullDate){
					$lastFullDate = 0;
				}
			}
			$this->_getUpdateFull = $lastFullDate;
		}
		return $this->_getUpdateFull;
	}

	protected function _checkUpdateFullProfile()
	{
		if(is_null($this->_updateFull)){
			$user = $this->getMergedData();

			$profileFields = XenForo_Application::getOptions()->BRC_fullProfileRequiredFields;
			if(!$profileFields){
				$profileFields = array(
					'homepage' => 1,
					'location' => 1,
					'occupation' => 1,
					'dob_day' => 1,
					'dob_month' => 1,
					'dob_year' => 1,
					'about' => 1,
					'avatar_date' => 1,
				);
			}

			$this->_updateFull = true;

			foreach($profileFields AS $fieldName=>$check){
				if($check && empty($user[$fieldName])){
					$this->_updateFull = false;
					break;
				}
			}
		}
		return $this->_updateFull;
	}

	protected function _getCreditModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Credit');
	}

	protected function _getTransactionModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Transaction');
	}
}