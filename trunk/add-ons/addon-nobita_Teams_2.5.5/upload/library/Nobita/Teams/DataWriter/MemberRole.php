<?php
/**
 * @project		customize group title for each member
 * @date 		19-06-2014
 * @author		truonglv<at>outlook.com
 * @package		Nobita_Teams
 */
class Nobita_Teams_DataWriter_MemberRole extends XenForo_DataWriter
{
	const DATA_MEMBER_ROLE_TITLE = 'dataMemberRoleTitle';

	protected function _getFields()
	{
		return array(
			'xf_team_member_role' => array(
				'member_role_id' => array(
					'type' => self::TYPE_BINARY,
					'required' => true,
					'maxLength' => 25,
					'verification' => array('$this', '_verifyMemberRoleId'),
					'requiredError' => 'please_enter_valid_field_id'
				),
				'roles' 		=> array(
					'type' => self::TYPE_SERIALIZED,
					'default' => 'a:0:{}'
				),
				'display_order' 		=> array(
					'type' => self::TYPE_UINT,
					'default' => 10
				),
				'notice' 				=> array(
					'type' => self::TYPE_BOOLEAN,
					'default' => 0
				),
				'is_staff' 				=> array(
					'type' => self::TYPE_BOOLEAN,
					'default' => 0
				)
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (! $id = $this->_getExistingPrimaryKey($data, 'member_role_id'))
		{
			return false;
		}

		return array('xf_team_member_role' => $this->_getMemberRoleModel()->getMemberRoleById($id));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'member_role_id = ' . $this->_db->quote($this->getExisting('member_role_id'));
	}

	protected function _verifyMemberRoleId(&$id)
	{
		if(preg_match('/[^a-z0-9_]/', $id))
		{
			$this->error(new XenForo_Phrase('Teams_please_enter_an_id_using_only_alphanumeric'), 'member_role_id');
			return false;
		}

		if ($id !== $this->getExisting('member_role_id') && $this->_getMemberRoleModel()->getMemberRoleById($id))
		{
			$this->error(new XenForo_Phrase('field_ids_must_be_unique'), 'member_role_id');
			return false;
		}

		return true;
	}

	protected function _preSave()
	{
		$memerRoleTitle = $this->getExtraData(self::DATA_MEMBER_ROLE_TITLE);
		if(!utf8_strlen($memerRoleTitle))
		{
			$this->error(new XenForo_Phrase('Teams_please_enter_valid_member_role_title'));
			return false;
		}
	}

	protected function _postSave()
	{
		$this->_getMemberRoleModel()->insertOrUpdateMasterPhrase(
			$this->get('member_role_id'), $this->getExtraData(self::DATA_MEMBER_ROLE_TITLE)
		);
		$this->_getMemberRoleModel()->saveMemberRolesToCache();
	}

	protected function _preDelete()
	{
		$memberRoleId = $this->get('member_role_id');
		if (in_array($memberRoleId, array('member', 'admin')))
		{
			throw new XenForo_Exception("The member role '{$memberRoleId}' could not be delete.", true);
			return false;
		}
	}

	protected function _postDelete()
	{
		$db = $this->_db;

		$memberRoleId = $this->get('member_role_id');

		$db->update('xf_team_member', array('member_role_id' => 'member'), 'member_role_id = ' . $db->quote($memberRoleId));
		$this->_getMemberRoleModel()->saveMemberRolesToCache();

		$phraseId = $this->_getMemberRoleModel()->getMemberRolePhrase($this->get('member_role_id'));
		$this->_getPhraseModel()->deleteMasterPhrase($phraseId);
	}

	protected function _getPhraseModel()
	{
		return $this->getModelFromCache('XenForo_Model_Phrase');
	}

	protected function _getMemberRoleModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_MemberRole');
	}
}
