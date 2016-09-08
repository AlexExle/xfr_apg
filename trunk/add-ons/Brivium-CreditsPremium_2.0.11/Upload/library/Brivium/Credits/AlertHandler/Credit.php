<?php

class Brivium_Credits_AlertHandler_Credit extends XenForo_AlertHandler_Abstract
{
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
		/* @var $eventModel Brivium_Credits_Model_Event */
		$eventModel = $model->getModelFromCache('Brivium_Credits_Model_Event');
		$events =array();
		if(!empty($viewingUser['permissions']) && XenForo_Permission::hasPermission($viewingUser['permissions'], 'BR_CreditsPermission', 'useCredits')){
			$events = $eventModel->getEventsByIds($contentIds);
		}
		return $events ;
	}
	protected function _prepareAlertAfterAction(array $item, $content, array $viewingUser)
	{
		if($item['content_type']=='credit'){
			$amount = 0;
			if ($item['extra_data'])
			{
				$item['extra'] = unserialize($item['extra_data']);
				$amount = isset($item['extra']['amount'])?$item['extra']['amount']:0;
			}
			$item['amount'] = $amount;
			if(isset($item['extra']['reverted'])&&$item['extra']['reverted']){
				$item['reverted'] = true;
			}else{
				$item['reverted'] = false;
			}
			if(isset($item['action'])){
				$item['actionTitle'] = $item['reverted']?
					new XenForo_Phrase('BRC_action_'.$item['action'].'_reverted'):
					new XenForo_Phrase('BRC_action_'.$item['action']);
			}
		}
		//pr($item);die;
		unset($item['extra_data']);
		return $item;
	}

	protected function _getDefaultTemplateTitle($contentType, $action) {
		return 'BRC_alert_credit_' . $action;
	}
}