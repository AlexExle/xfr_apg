<?php

class Brivium_Credits_ActionHandler_ImportVbb_Importer_vBulletin extends XFCP_Brivium_Credits_ActionHandler_ImportVbb_Importer_vBulletin
{
	public function getSteps() {
		$steps = parent::getSteps();

		$actionObj = XenForo_Application::get('brcActionHandler');
		$events = $actionObj->getActionEvents('interest');
		if($events){
			$steps['brcvbCredits'] = array(
				'title' => new XenForo_Phrase('BRC_import_vbcredits'),
				'depends' => array('users')
			);
			$steps['brckBank'] = array(
				'title' => new XenForo_Phrase('BRC_import_kbank'),
				'depends' => array('users')
			);
		}
		return $steps;
	}

	public function stepBrcvbCredits($start, array $options) {
		$field = 'credits';
		return $this->_mergeCredits($start, $options, $field);
	}

	public function stepBrckBank($start, array $options) {
		$field = $this->_sourceDb->fetchOne('
			SELECT value
			FROM `' . $this->_prefix . 'setting`
			WHERE varname = ?
		', 'kbankf');
		if(!empty($field)) return $this->_mergeCredits($start, $options, $field);
		return true;
	}

	private function _mergeCredits($start, array $options, $field) {
		$options = array_merge(array(
			'limit' => 200,
			'max' => false,
		), $options);

		$sDb = $this->_sourceDb;
		$prefix = $this->_prefix;

		/* @var $model XenForo_Model_Import */
		$model = $this->_importModel;

		if ($options['max'] === false)
		{
			$options['max'] = $sDb->fetchOne('
				SELECT MAX(userid)
				FROM ' . $prefix . 'user
			');
		}
		$users = $sDb->fetchAll(
			$sDb->limit('
				SELECT user.*
				FROM ' . $this->_prefix . 'user AS user
				WHERE user.userid > ' . $sDb->quote($start) . '
				ORDER BY user.userid
			', $options['limit'])
		);
		if (!$users)
		{
			return true;
		}
		$userIdMap = $model->getUserIdsMapFromArray($users, 'userid');
		XenForo_Db::beginTransaction();
		$next = 0;
		$total = 0;
		$creditModel = XenForo_Model::create('Brivium_Credits_Model_Credit');

		$actionObj = XenForo_Application::get('brcActionHandler');


		foreach ($users AS $user)
		{
			$next = $user['userid'];
			$userId = $this->_mapLookUp($userIdMap, $user['userid']);
			if (!$userId || !isset($user[$field]))
			{
				continue;
			}
			$imported = $creditModel->updateUserCredit('importVbb',$user['userid'],array('amount' => $user[$field]));
			if (is_array($imported))
			{
				$total++;
			}
		}

		XenForo_Db::commit();
		$this->_session->incrementStepImportTotal($total);
		return array($next, $options, $this->_getProgressOutput($next, $options['max']));
	}
}