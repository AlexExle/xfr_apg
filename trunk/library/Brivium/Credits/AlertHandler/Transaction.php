<?php

class Brivium_Credits_AlertHandler_Transaction extends XenForo_AlertHandler_Abstract
{
	/**
	 * @var Brivium_Credits_Action
	 */
	protected $_actionObj = null;
	/**
	 * Fetches the content required by alerts.
	 *
	 * @param array $contentIds
	 * @param XenForo_Model_Alert $model Alert model invoking this
	 * @param integer $userId User ID the alerts are for
	 * @param array $viewingUser Information about the viewing user (keys: user_id, permission_combination_id, permissions)
	 *
	 * @return array
	 */
	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		/* @var $eventModel Brivium_Credits_Model_Transaction */
		$transactionModel = $model->getModelFromCache('Brivium_Credits_Model_Transaction');
		$transactions =array();
		if(!empty($viewingUser['permissions']) && XenForo_Permission::hasPermission($viewingUser['permissions'], 'BR_CreditsPermission', 'useCredits')){
			$transactions = $transactionModel->getTransactionsByIds($contentIds);
		}
		return $transactions ;
	}

	protected function _prepareAlertAfterAction(array $item, $content, array $viewingUser)
	{
		if($item['content_type']=='brc_transaction'){
			if(isset($item['action'])){
				$action = $this->_getActionObj()->$item['action'];
				if($action && !empty($action['title'])){
					$item['actionTitle'] = $action['title'];
				}else{
					$item['actionTitle'] = new XenForo_Phrase('BRC_action_'.$item['action']);
				}
			}
		}
		unset($item['extra_data']);
		return $item;
	}

	protected function _getDefaultTemplateTitle($contentType, $action)
	{
		$action = $this->_getActionObj()->$action;
		if($action && !empty($action['transaction_complete_alert'])){
			return $action['transaction_complete_alert'];
		}
		return 'BRC_alert_transaction_complete';
	}

	protected function _getActionObj()
	{
		if (!$this->_actionObj)
		{
			$this->_actionObj = XenForo_Application::get('brcActionHandler');
		}

		return $this->_actionObj;
	}
}