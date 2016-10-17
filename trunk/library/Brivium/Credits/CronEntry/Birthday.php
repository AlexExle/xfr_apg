<?php

/**
 * Cron entry for Birthday.
 */
class Brivium_Credits_CronEntry_Birthday
{
	public static function runBirthdayUpdate()
	{
		$creditModel = XenForo_Model::create('Brivium_Credits_Model_Credit');
		$actionObj = XenForo_Application::get('brcActionHandler');
		$events = $actionObj->getActionEvents('birthday');
		if(!$events){
			return false;
		}
		/* @var $userModel XenForo_Model_User */
		$userModel = XenForo_Model::create('XenForo_Model_User');
		$criteria = array(
			'user_state' => 'valid',
			'is_banned' => 0,
			'dob_month' => date("m"),
			'dob_day' => date("j"),
		);
		$users = $userModel->getUsers($criteria, array(
			'join' => XenForo_Model_User::FETCH_USER_FULL
		));

		$creditModel->setIsBulk(true);
		$creditModel->setIsWaitSubmit(true);
		$userIds = array();
		$userProfileModel = XenForo_Model::create('XenForo_Model_UserProfile');
		foreach ($users AS $user)
		{
			$birthday = $userProfileModel->getUserBirthdayDetails($user,true);
			if(!empty($birthday['age'])){
				if($creditModel->updateUserCredit('birthday',$user['user_id'],array('user'=>$user,'multiplier'=>$birthday['age']))){
					$userIds[$user['user_id']] = $user['user_id'];
				}
			}
		}
		$creditModel->commitUpdate();
	}
}