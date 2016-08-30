<?php

class Nobita_Teams_ControllerAdmin_MemberRole extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('socialGroups');
	}

	public function actionIndex()
	{
		$viewParams = array(
			'memberRoles' => $this->_getMemberRoleModel()->getAllMemberRoles()
		);

		return $this->responseView('Nobita_Teams_ViewPublic_UserRole_List', 'Team_member_role_list', $viewParams);
	}

	public function actionAdd()
	{
		return $this->_getAddOrEditResponse(array(
			'display_order' => 10,
			'roles' => array(
				'invitePeople' => 1
			)
		));
	}

	protected function _getBasicUserRolesOptions()
	{
		return $this->_getMemberRoleModel()->getBasicMemberRoles();
	}

	protected function _getAddOrEditResponse(array $memberRole = null)
	{
		$viewParams = array(
			'memberRole' => $memberRole,
			'permissionsGrouped' => $this->_getBasicUserRolesOptions(),
			'memberRoleGrouped' => $this->_getMemberRoleModel()->getMemberRoleGrouped()
		);

		return $this->responseView('Nobita_Teams_ViewPublic_MemberRole_Edit', 'Team_member_role_edit', $viewParams);
	}

	public function actionEdit()
	{
		$memberRole = $this->_getMemberRoleOrError();
		return $this->_getAddOrEditResponse($memberRole);
	}

	protected function _getMemberRoleOrError($id = null)
	{
		if ($id === null)
		{
			$id = $this->_input->filterSingle('member_role_id', XenForo_Input::STRING);
		}

		$result = $this->_getMemberRoleModel()->getMemberRoleById($id);
		if (!$result)
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('Teams_requested_member_role_not_found'), 404));
		}

		return $this->_getMemberRoleModel()->prepareMemberRole($result);
	}

	public function actionSave()
	{
		$memberRoleId = $this->_input->filterSingle('member_role_id', XenForo_Input::STRING);
		$memberRoleTitle = $this->_input->filterSingle('member_role_title', XenForo_Input::STRING);

		$memberRole = $this->_getMemberRoleModel()->getMemberRoleById($memberRoleId);

		$input = $this->_input->filter(array(
			'roles' => XenForo_Input::ARRAY_SIMPLE,
			'display_order' => XenForo_Input::UINT,
			'notice' => XenForo_Input::BOOLEAN,
			'is_staff' => XenForo_Input::BOOLEAN
		));

		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_MemberRole');
		if($memberRole)
		{
			$dw->setExistingData($memberRole);
		}
		else
		{
			$dw->set('member_role_id', $memberRoleId);
		}

		$dw->setExtraData(Nobita_Teams_DataWriter_MemberRole::DATA_MEMBER_ROLE_TITLE, $memberRoleTitle);
		$dw->bulkSet($input);
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('team-member-roles') . $this->getLastHash($dw->get('member_role_id'))
		);
	}

	public function actionDelete()
	{
		$memberRole = $this->_getMemberRoleOrError();
		if ($this->isConfirmedPost())
		{
			return $this->_deleteData('Nobita_Teams_DataWriter_MemberRole', $memberRole, $this->_buildLink('team-member-roles'));
		}
		else
		{
			return $this->responseView('', 'Team_member_role_delete', array(
				'memberRole' => $memberRole
			));
		}
	}

	protected function _getMemberRoleModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_MemberRole');
	}
}
