<?php

class Nobita_Teams_Helper_Member
{
	/**
	 * List of last seen selectable
	 *
	 * $key 			Filter key
	 * $key_value 		The timestamp of special key
	 *
	 * @return array
	 */
	public static $lastSeenFilterable = array(
		'1_month' 	=> 2592000, // 86400 * 30
		'3_months' 	=> 7776000, // 86400 *
		'6_months' 	=> 15552000,
		'1_year' 	=> 31536000
	);

	public function __construct()
	{
	}

	public static function getMembersBaseTypeAdmins($teamId, array $fetchOptions = null)
	{
		$memberModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member');
		$memberRoleModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_MemberRole');

		$staffIds = $memberRoleModel->getStaffIds();
		if (empty($staffIds))
		{
			return array(array(), 0);
		}

		$conditions = array('member_role_id' => $staffIds);

		$members = $memberModel->getMembersByTeamId($teamId, $conditions, $fetchOptions);
		return array($members, count($members));
	}

	public static function getMembersBaseType($type, $teamId, $page, $perPage, array $extraCondition = null)
	{
		$fetchOptions = array(
			'page' => $page,
			'perPage' => $perPage,
			'join' => Nobita_Teams_Model_Member::FETCH_USER
		);

		$method = 'getMembersBaseType' . ucfirst($type);
		$class = XenForo_Application::resolveDynamicClass(__CLASS__);

		if (!method_exists($class, $method))
		{
			return array(array(), 0);
		}

		list($members, $memberCount) = $class::$method($teamId, $fetchOptions, $extraCondition);
		return array($members, $memberCount);
	}

	public static function getMembersBaseTypeLastSeenGroup($teamId, $fetchOptions, array $extraCondition)
	{
		$conditions = array('member_state' => 'accept') + $extraCondition;

		$members = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member')->getMembersByTeamId($teamId, $conditions, $fetchOptions);
		$memberCount = count($members);

		return array($members, $memberCount);
	}

	public static function getMembersBaseTypeActivities($teamId, array $fetchOptions)
	{
		$conditions = array('member_state' => 'accept');

		$fetchOptions = array_merge(array(
			'order' => 'last_view_date',
			'direction' => 'desc',
			'limit' => 20
		), $fetchOptions);

		$members = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member')->getMembersByTeamId($teamId, $conditions, $fetchOptions);
		$memberCount = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member')->countMembersInTeam($teamId, $conditions);

		return array($members, $memberCount);
	}

	public static function getMembersBaseTypeAll($teamId, array $fetchOptions)
	{
		$conditions = array('member_state' => 'accept');

		$members = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member')->getMembersByTeamId($teamId, $conditions, $fetchOptions);
		$memberCount = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member')->countMembersInTeam($teamId, $conditions);

		return array($members, $memberCount);
	}

	public static function getMembersBaseTypeAlphabetical($teamId, array $fetchOptions)
	{
		$conditions = array('member_state' => 'accept');
		$fetchOptions['order'] = 'alphabetical';

		$members = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member')->getMembersByTeamId($teamId, $conditions, $fetchOptions);
		$memberCount = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member')->countMembersInTeam($teamId, $conditions);

		return array($members, $memberCount);
	}

	public static function getMembersBaseTypeDate($teamId, array $fetchOptions)
	{
		$conditions = array('member_state' => 'accept');

		$fetchOptions['order'] = 'date';
		$fetchOptions['direction'] = 'desc';

		$members = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member')->getMembersByTeamId($teamId, $conditions, $fetchOptions);
		$memberCount = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member')->countMembersInTeam($teamId, $conditions);

		return array($members, $memberCount);
	}

	public static function getMembersBaseTypeRequests($teamId, array $fetchOptions)
	{
		$conditions = array('member_state' => 'request', 'not_in_action' => 'invite');

		$fetchOptions['order'] = 'date';
		$fetchOptions['direction'] = 'desc';

		$members = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member')->getMembersByTeamId($teamId, $conditions, $fetchOptions);
		$memberCount = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member')->countMembersInTeam($teamId, $conditions);

		return array($members, $memberCount);
	}

	public static function getMembersBaseTypeInvited($teamId, array $fetchOptions)
	{
		// reset fetchOptions
		$fetchOptions = array(
			'join' => Nobita_Teams_Model_Member::FETCH_USER
		);

		$conditions = array('member_state' => 'request', 'action' => 'invite');

		$fetchOptions['order'] = 'date';
		$fetchOptions['direction'] = 'desc';

		$members = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member')->getMembersByTeamId($teamId, $conditions, $fetchOptions);
		return array($members, count($members));
	}

	public static function getMembersBaseTypeBlocked($teamId, $fetchOptions)
	{
		$members = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Banning')->getAllBanningActiveForTeam($teamId);

		foreach ($members as &$user)
		{
			$user['banUser'] = array(
				'user_id' => $user['ban_user_id'],
				'username' => $user['ban_username']
			);

			$user['banningInfo'] = array(
				'team_id' => $user['team_id'],
				'user_id' => $user['user_id'],
				'banning_id' => Nobita_Teams_Container::getModel('Nobita_Teams_Model_Banning')->generateBanningKey($user['team_id'], 'banlist', $user['user_id'])
			);
		}
		unset($user);

		return array($members, count($members));
	}

	public static function getMembersBaseSimilarUsername($username, $teamId)
	{
		$memberModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Member');

		if ('' !== $username && utf8_strlen($username) > 2)
		{
			$users = $memberModel->getMembersByTeamId($teamId, array(
				'username' => array($username , 'r'),
			),
			array(
				'limit' => 20,
				'join' => Nobita_Teams_Model_Member::FETCH_USER,
			));
		}
		else
		{
			$users = array();
		}

		return array($users, count($users));
	}

}
