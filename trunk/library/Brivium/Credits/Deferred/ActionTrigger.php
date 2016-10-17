<?php

class Brivium_Credits_Deferred_ActionTrigger extends XenForo_Deferred_Abstract
{
	public function canTriggerManually()
	{
		return false;
	}

	public function execute(array $deferred, array $data, $targetRunTime, &$status)
	{
		$data = array_merge(array(
			'position' => 0,
			'action_id' => '',
			'batch' => 100,
			'criteria' => array(),
			'data_credit' => array(),
		), $data);

		if(empty($data['action_id'])){
			return true;
		}
		$actionId = $data['action_id'];
		$actionObj = XenForo_Application::get('brcActionHandler');
		$action = $actionObj->$actionId;
		$events = $actionObj->getActionEvents($actionId);
		if(!$events || empty($action['title'])){
			return true;
		}
		$data['batch'] = max(1, $data['batch']);

		/* @var $userModel XenForo_Model_User */
		$userModel = XenForo_Model::create('XenForo_Model_User');

		$creditModel = XenForo_Model::create('Brivium_Credits_Model_Credit');

		$criteria = $data['criteria'];
		$criteria['user_state'] = 'valid';
		$criteria['is_banned'] = 0;
		$criteria['brc_user_id_start'] = $data['position'];


		$users = $userModel->getUsersInRange($criteria, array(
			'join' => XenForo_Model_User::FETCH_USER_OPTION | XenForo_Model_User::FETCH_USER_PERMISSIONS,
			'limit' => $data['batch'],
		));
		if (sizeof($users) == 0)
		{
			return true;
		}

		$creditModel->setIsBulk(true);
		$creditModel->setIsWaitSubmit(true);
		$userIds = array();
		$dataCredit = $data['data_credit'];

		foreach ($users AS $userId=>$user)
		{
			$data['position'] = $userId;
			$dataCredit['user'] = $user;
			if($eventIds = $creditModel->updateUserCredit($actionId, $user['user_id'], $dataCredit)){
				if(is_array($eventIds)){
					foreach($eventIds AS $eventId){
						if(empty($userIds[$eventId])){
							$userIds[$eventId] = array();
						}
						$userIds[$eventId][$user['user_id']] = $user['user_id'];
					}
				}
			}
		}

		$creditModel->commitUpdate(true);
		if($userIds){
			//$creditModel->updatePeriodRewardAmount($userIds, $events);
		}

		$actionPhrase = new XenForo_Phrase('BRC_triggering');
		$typePhrase = $action['title'];
		$status = sprintf('%s... %s (%s)', $actionPhrase, $typePhrase, XenForo_Locale::numberFormat($data['position']));

		return $data;
	}

	public function canCancel()
	{
		return true;
	}
}