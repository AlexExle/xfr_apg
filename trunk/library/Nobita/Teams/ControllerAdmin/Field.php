<?php

class Nobita_Teams_ControllerAdmin_Field extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('socialGroups');
	}

	public function actionIndex()
	{
		$fieldModel = $this->_getFieldModel();

		$fields = $fieldModel->prepareTeamFields($fieldModel->getTeamFields());

		$viewParams = array(
			'fieldsGrouped' => $fieldModel->groupTeamFields($fields),
			'fieldCount' => count($fields),
			'fieldGroups' => $fieldModel->getTeamFieldGroups(),
			'fieldTypes' => $fieldModel->getTeamFieldTypes()
		);

		return $this->responseView('Nobita_Teams_ViewAdmin_Field_List', 'Team_field_list', $viewParams);
	}
	
	/**
	 * Gets the add/edit form response for a field.
	 *
	 * @param array $field
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	protected function _getFieldAddEditResponse(array $field)
	{
		$fieldModel = $this->_getFieldModel();

		$typeMap = $fieldModel->getTeamFieldTypeMap();
		$validFieldTypes = $fieldModel->getTeamFieldTypes();

		if (!empty($field['field_id']))
		{
			$selCategoryIds = $this->_getFieldModel()->getCategoryAssociationsByField($field['field_id']);

			$masterTitle = $fieldModel->getTeamFieldMasterTitlePhraseValue($field['field_id']);
			$masterDescription = $fieldModel->getTeamFieldMasterDescriptionPhraseValue($field['field_id']);

			$existingType = $typeMap[$field['field_type']];
			foreach ($validFieldTypes AS $typeId => $type)
			{
				if ($typeMap[$typeId] != $existingType)
				{
					unset($validFieldTypes[$typeId]);
				}
			}
		}
		else
		{
			$selCategoryIds = array();
			$masterTitle = '';
			$masterDescription = '';
			$existingType = false;
		}

		if (!$selCategoryIds)
		{
			$selCategoryIds = array(0);
		}

		$fields = $fieldModel->prepareTeamFields($fieldModel->getTeamFields());
		foreach ($fields as $fieldId => $fieldInfo)
		{
			if ($fieldInfo['display_group'] != 'new_tab')
			{
				unset($fields[$fieldId]);
			}
		}

		$viewParams = array(
			'field' => $field,
			'masterTitle' => $masterTitle,
			'masterDescription' => $masterDescription,
			'masterFieldChoices' => $fieldModel->getTeamFieldChoices($field['field_id'], $field['field_choices'], true),

			'fieldGroups' => $fieldModel->getTeamFieldGroups(),
			'validFieldTypes' => $validFieldTypes,
			'fieldTypeMap' => $typeMap,
			'existingType' => $existingType,

			'categories' => $this->_getCategoryModel()->getAllCategories(),
			'selCategoryIds' => $selCategoryIds,
			'fields' => $fields
		);

		return $this->responseView('Nobita_Teams_ViewAdmin_Field_Edit', 'Team_field_edit', $viewParams);
	}
	
	/**
	 * Displays form to add a custom team field.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionAdd()
	{
		return $this->_getFieldAddEditResponse(array(
			'field_id' => null,
			'display_group' => 'above_info',
			'display_order' => 1,
			'field_type' => 'textbox',
			'field_choices' => '',
			'match_type' => 'none',
			'match_regex' => '',
			'match_callback_class' => '',
			'match_callback_method' => '',
			'max_length' => 0,
			'required' => 0,
			'display_template' => ''
		));
	}
	
	/**
	 * Displays form to edit a custom team field.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionEdit()
	{
		$field = $this->_getFieldOrError($this->_input->filterSingle('field_id', XenForo_Input::STRING));
		return $this->_getFieldAddEditResponse($field);
	}
	
	/**
	 * Saves a custom resource field.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionSave()
	{
		$fieldId = $this->_input->filterSingle('field_id', XenForo_Input::STRING);

		$newFieldId = $this->_input->filterSingle('new_field_id', XenForo_Input::STRING);
		$dwInput = $this->_input->filter(array(
			'display_group' => XenForo_Input::STRING,
			'display_order' => XenForo_Input::UINT,
			'field_type' => XenForo_Input::STRING,
			'match_type' => XenForo_Input::STRING,
			'match_regex' => XenForo_Input::STRING,
			'match_callback_class' => XenForo_Input::STRING,
			'match_callback_method' => XenForo_Input::STRING,
			'max_length' => XenForo_Input::UINT,
			'required' => XenForo_Input::UINT,
			'display_template' => XenForo_Input::STRING
		));
		
		$parentTab = $this->_input->filterSingle('_parent_tab', XenForo_Input::STRING);
		if ($dwInput['display_group'] == 'parent_tab')
		{
			$parentField = $this->_getFieldModel()->getTeamFieldById($parentTab);
			if (!$parentField)
			{
				return $this->responseError(new XenForo_Phrase('Teams_requested_field_not_found'));
			}
			
			if ($parentField['display_group'] != 'new_tab')
			{
				return $this->responseError(new XenForo_Phrase('Teams_this_field_can_not_have_child_tab'));
			}
		}
		else
		{
			$parentTab = '';
		}

		$categoryIds = $this->_input->filterSingle('team_category_ids', XenForo_Input::UINT, array('array' => true));

		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_TeamField');
		if ($fieldId)
		{
			$dw->setExistingData($fieldId);
		}
		else
		{
			$dw->set('field_id', $newFieldId);
		}
	
		$dw->bulkSet($dwInput);
		if ($parentTab)
		{
			$dw->set('parent_tab_id', $parentTab);
		}

		$dw->setExtraData(Nobita_Teams_DataWriter_TeamField::DATA_CATEGORY_IDS, $categoryIds);

		$dw->setExtraData(
			Nobita_Teams_DataWriter_TeamField::DATA_TITLE,
			$this->_input->filterSingle('title', XenForo_Input::STRING)
		);
		$dw->setExtraData(
			Nobita_Teams_DataWriter_TeamField::DATA_DESCRIPTION,
			$this->_input->filterSingle('description', XenForo_Input::STRING)
		);

		$fieldChoices = $this->_input->filterSingle('field_choice', XenForo_Input::STRING, array('array' => true));
		$fieldChoicesText = $this->_input->filterSingle('field_choice_text', XenForo_Input::STRING, array('array' => true));
		$fieldChoicesCombined = array();
		foreach ($fieldChoices AS $key => $choice)
		{
			if (isset($fieldChoicesText[$key]))
			{
				$fieldChoicesCombined[$choice] = $fieldChoicesText[$key];
			}
		}

		$dw->setFieldChoices($fieldChoicesCombined);

		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('team-fields') . $this->getLastHash($dw->get('field_id'))
		);
	}
	
	/**
	 * Deletes a custom team field.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionDelete()
	{
		if ($this->isConfirmedPost())
		{
			return $this->_deleteData(
				'Nobita_Teams_DataWriter_TeamField', 'field_id',
				XenForo_Link::buildAdminLink('team-fields')
			);
		}
		else
		{
			$field = $this->_getFieldOrError($this->_input->filterSingle('field_id', XenForo_Input::STRING));

			$viewParams = array(
				'field' => $field
			);

			return $this->responseView('Nobita_Teams_ViewAdmin_Field_Delete', 'Team_field_delete', $viewParams);
		}
	}
	
	/**
	 * Gets the specified field or throws an exception.
	 *
	 * @param string $id
	 *
	 * @return array
	 */
	protected function _getFieldOrError($id)
	{
		$field = $this->getRecordOrError(
			$id, $this->_getFieldModel(), 'getTeamFieldById',
			'requested_field_not_found'
		);

		return $this->_getFieldModel()->prepareTeamField($field);
	}
	
	protected function _getCategoryModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Category');
	}
	
	protected function _getFieldModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Field');
	}
}