<?php

class XenResource_ControllerPublic_ResourceInlineMod extends XenForo_ControllerPublic_InlineMod_Abstract
{
	/**
	 * Key for inline mod data.
	 *
	 * @var string
	 */
	public $inlineModKey = 'resources';

	/**
	 * @return XenResource_Model_InlineMod_Resource
	 */
	public function getInlineModTypeModel()
	{
		return $this->getModelFromCache('XenResource_Model_InlineMod_Resource');
	}

	/**
	 * Resource deletion handler
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionDelete()
	{
		if ($this->isConfirmedPost())
		{
			$alertInput = $this->_input->filter(array(
				'send_author_alert' => XenForo_Input::BOOLEAN,
				'author_alert_reason' => XenForo_Input::STRING
			));

			$hardDelete = $this->_input->filterSingle('hard_delete', XenForo_Input::STRING);
			$options = array(
				'deleteType' => ($hardDelete ? 'hard' : 'soft'),
				'reason' => $this->_input->filterSingle('reason', XenForo_Input::STRING),
				'authorAlert' => $alertInput['send_author_alert'],
				'authorAlertReason' => $alertInput['author_alert_reason']
			);

			return $this->executeInlineModAction('deleteResources', $options, array('fromCookie' => false));
		}
		else // show confirmation dialog
		{
			$resourceIds = $this->getInlineModIds();

			$handler = $this->_getInlineModResourceModel();
			if (!$handler->canDeleteResources($resourceIds, 'soft', $errorPhraseKey))
			{
				throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
			}

			$redirect = $this->getDynamicRedirect();

			if (!$resourceIds)
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					$redirect
				);
			}

			$viewParams = array(
				'resourceIds' => $resourceIds,
				'resourceCount' => count($resourceIds),
				'canHardDelete' => $handler->canDeleteResources($resourceIds, 'hard'),
				'redirect' => $redirect,
			);

			return $this->responseView('XenResource_ViewPublic_ResourceInlineMod_Delete', 'inline_mod_resource_delete', $viewParams);
		}
	}

	/**
	 * Resource reassign handler
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionReassign()
	{
		if ($this->isConfirmedPost())
		{
			$user = $this->getModelFromCache('XenForo_Model_User')->getUserByName(
				$this->_input->filterSingle('username', XenForo_Input::STRING),
				array('join' => XenForo_Model_User::FETCH_USER_PERMISSIONS)
			);
			$user['permissions'] = XenForo_Permission::unserializePermissions($user['global_permission_cache']);
			if (!$user || !XenForo_Permission::hasPermission($user['permissions'], 'resource', 'view'))
			{
				return $this->responseError(new XenForo_Phrase('you_may_only_reassign_resource_to_user_with_permission_to_view'));
			}

			$alertInput = $this->_input->filter(array(
				'send_author_alert' => XenForo_Input::BOOLEAN,
				'author_alert_reason' => XenForo_Input::STRING
			));

			$options = array(
				'userId' => $user['user_id'],
				'username' => $user['username'],
				'alert' => $alertInput['send_author_alert'],
				'alertReason' => $alertInput['author_alert_reason']
			);

			return $this->executeInlineModAction('reassignResources', $options, array('fromCookie' => false));
		}
		else // show confirmation dialog
		{
			$resourceIds = $this->getInlineModIds();

			$handler = $this->_getInlineModResourceModel();
			if (!$handler->canReassignResources($resourceIds, $errorPhraseKey))
			{
				throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
			}

			$redirect = $this->getDynamicRedirect();

			if (!$resourceIds)
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					$redirect
				);
			}

			$viewParams = array(
				'resourceIds' => $resourceIds,
				'resourceCount' => count($resourceIds),
				'redirect' => $redirect,
			);

			return $this->responseView('XenResource_ViewPublic_ResourceInlineMod_Reassign', 'inline_mod_resource_reassign', $viewParams);
		}
	}

	/**
	 * Resource move handler
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionMove()
	{
		if ($this->isConfirmedPost())
		{
			$id = $this->_input->filterSingle('resource_category_id', XenForo_Input::UINT);
			$category = $this->_getCategoryModel()->getCategoryById($id);
			if (!$category)
			{
				return $this->responseError(new XenForo_Phrase('requested_category_not_found'), 404);
			}

			$options = array(
				'categoryId' => $category['resource_category_id']
			);

			return $this->executeInlineModAction('moveResources', $options, array('fromCookie' => false));
		}
		else // show confirmation dialog
		{
			$resourceIds = $this->getInlineModIds();

			$handler = $this->_getInlineModResourceModel();
			if (!$handler->canMoveResources($resourceIds, $errorPhraseKey))
			{
				throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
			}

			$redirect = $this->getDynamicRedirect();

			if (!$resourceIds)
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					$redirect
				);
			}

			$viewParams = array(
				'resourceIds' => $resourceIds,
				'resourceCount' => count($resourceIds),
				'redirect' => $redirect,
				'categories' => $this->_getCategoryModel()->prepareCategories(
					$this->_getCategoryModel()->getViewableCategories()
				)
			);

			return $this->responseView('XenResource_ViewPublic_ResourceInlineMod_Move', 'inline_mod_resource_move', $viewParams);
		}
	}

	public function actionPrefix()
	{
		$resourceIds = $this->getInlineModIds(!$this->isConfirmedPost());

		$redirect = $this->getDynamicRedirect();

		if (!$resourceIds)
		{
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$redirect
			);
		}

		if ($this->isConfirmedPost())
		{
			$prefixId = $this->_input->filterSingle('prefix_id', XenForo_Input::UINT);

			if (!$this->_getInlineModResourceModel()->applyResourcePrefix($resourceIds, $prefixId, $unchangedResourceIds, array(), $errorKey))
			{
				return $this->responseError(new XenForo_Phrase($errorKey));
			}

			if ($unchangedResourceIds)
			{
				XenForo_Helper_Cookie::setCookie('inlinemod_' . $this->inlineModKey, implode(',', $unchangedResourceIds));
			}
			else
			{
				$this->clearCookie();
			}

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$redirect
			);
		}
		else // show confirmation dialog
		{
			$handler = $this->_getInlineModResourceModel();
			if (!$handler->canEditResources($resourceIds, $errorPhraseKey))
			{
				throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
			}

			$resourceModel = $this->_getResourceModel();
			$prefixModel = $this->_getPrefixModel();

			$resources = $resourceModel->getResourcesByIds($resourceIds);

			$categoryIds = $resourceModel->getCategoryIdsFromResources($resources);

			$prefixes = $prefixModel->getUsablePrefixesInCategories($categoryIds);

			if (empty($prefixes))
			{
				return $this->responseError(new XenForo_Phrase('no_resource_prefixes_available_for_selected_categories'));
			}

			$selectedPrefix = 0;
			$prefixCounts = array(0 => 0);
			foreach ($resources AS $resource)
			{
				$resourcePrefixId = $resource['prefix_id'];

				if (!isset($prefixCounts[$resourcePrefixId]))
				{
					$prefixCounts[$resourcePrefixId] = 1;
				}
				else
				{
					$prefixCounts[$resourcePrefixId]++;
				}

				if ($prefixCounts[$resourcePrefixId] > $prefixCounts[$selectedPrefix])
				{
					$selectedPrefix = $resourcePrefixId;
				}
			}

			$viewParams = array(
				'resourceIds' => $resourceIds,
				'resourceCount' => count($resourceIds),
				'resources' => $resources,
				'categoryIds' => $categoryIds,
				'categoryCount' => count($categoryIds),
				'prefixes' => $prefixes,
				'selectedPrefix' => $selectedPrefix,
				'redirect' => $redirect,
			);

			return $this->responseView('XenResource_ViewPublic_ResourceInlineMod_Prefix', 'inline_mod_resource_prefix', $viewParams);
		}
	}

	/**
	 * Undeletes the specified resources.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionUndelete()
	{
		return $this->executeInlineModAction('undeleteResources');
	}

	/**
	 * Approves the specified resources.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionApprove()
	{
		return $this->executeInlineModAction('approveResources');
	}

	/**
	 * Unapproves the specified resources.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionUnapprove()
	{
		return $this->executeInlineModAction('unapproveResources');
	}

	/**
	 * Features the specified resources.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionFeature()
	{
		return $this->executeInlineModAction('featureResources');
	}

	/**
	 * Unfeatures the specified resources.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionUnfeature()
	{
		return $this->executeInlineModAction('unfeatureResources');
	}

	/**
	 * @return XenResource_Model_InlineMod_Resource
	 */
	protected function _getInlineModResourceModel()
	{
		return $this->getModelFromCache('XenResource_Model_InlineMod_Resource');
	}

	/**
	 * @return XenResource_Model_Resource
	 */
	protected function _getResourceModel()
	{
		return $this->getModelFromCache('XenResource_Model_Resource');
	}

	/**
	 * @return XenResource_Model_Category
	 */
	protected function _getCategoryModel()
	{
		return $this->getModelFromCache('XenResource_Model_Category');
	}

	/**
	 * @return XenResource_Model_Prefix
	 */
	protected function _getPrefixModel()
	{
		return $this->getModelFromCache('XenResource_Model_Prefix');
	}
}