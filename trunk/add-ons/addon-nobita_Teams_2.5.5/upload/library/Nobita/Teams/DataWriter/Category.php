<?php

class Nobita_Teams_DataWriter_Category extends XenForo_DataWriter
{
	/**
	 * Title of the phrase that will be created when a call to set the
	 * existing data fails (when the data doesn't exist).
	 *
	 * @var string
	 */
	protected $_existingDataErrorPhrase = 'requested_category_not_found';
	/**
	 * Option that represents whether associated caches will be automatically
	 * rebuilt. Defaults to true.
	 *
	 * @var string
	 */
	const OPTION_REBUILD_CACHE = 'rebuildCache';

	const DATA_FIELD_IDS = 'fieldIds';

	/**
	* Gets the fields that are defined for the table. See parent for explanation.
	*
	* @return array
	*/
	protected function _getFields()
	{
		return array(
			'xf_team_category' => array(
				'team_category_id' => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'category_title'       => array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 100,
					'requiredError' => 'please_enter_valid_title'
				),
				'category_description' => array('type' => self::TYPE_STRING, 'default' => 0),
				'parent_category_id'   => array('type' => self::TYPE_UINT, 'default' => 0,
					'verification' => array('$this', '_validateParentCategoryId')
					),
				'depth'                => array('type' => self::TYPE_UINT, 'default' => 0),
				'lft'                  => array('type' => self::TYPE_UINT, 'default' => 0),
				'rgt'                  => array('type' => self::TYPE_UINT, 'default' => 0),
				'display_order'        => array('type' => self::TYPE_UINT, 'default' => 1),
				'team_count'       => array('type' => self::TYPE_UINT_FORCED, 'default' => 0),
				'category_breadcrumb'  => array('type' => self::TYPE_SERIALIZED, 'default' => 'a:0:{}'),
				'always_moderate_create'      => array('type' => self::TYPE_BOOLEAN, 'default' => 0),
				'field_cache'          => array('type' => self::TYPE_SERIALIZED, 'default' => ''),
				'featured_count' => array('type' => self::TYPE_UINT, 'default' => 0),

				'allow_team_create' => array('type' => self::TYPE_BOOLEAN, 'default' => 1),
				'allowed_user_group_ids' => array('type' => self::TYPE_UNKNOWN, 'default' => '',
					'verification' => array('$this', '_verifyAllowedUserGroupIds')
				),
				'allow_uploaded_file' => array('type' => self::TYPE_BOOLEAN, 'default' => 0),

				// added 1.1.2
				'default_cover_path' => array('type' => self::TYPE_STRING, 'default' => '',
					'maxLength' => 100),
				'ribbon_styling' 	=> array('type' => self::TYPE_SERIALIZED, 'default' => 'a:0:{}'),
				'icon_date' 		=> array('type' => self::TYPE_UINT, 'default' => 0),

				// added 2.1.1
				'default_privacy' 	=> array('type' => self::TYPE_BINARY, 'default' => 'open',
					'allowedValues' => array('open', 'closed', 'secret')),
				'disable_tabs_default' => array('type' => self::TYPE_BINARY, 'default' => ''),

				'allow_enable_disable_tabs' => array('type' => self::TYPE_BOOLEAN, 'default' => 0),

				'min_tags' => array('type' => self::TYPE_UINT, 'default' => 0),
			)
		);
	}

	/**
	* Gets the actual existing data out of data that was passed in. See parent for explanation.
	*
	* @param mixed
	*
	* @return array|bool
	*/
	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data))
		{
			return false;
		}

		return array('xf_team_category' => $this->_getCategoryModel()->getCategoryById($id));
	}

	/**
	* Gets SQL condition to update the existing record.
	*
	* @return string
	*/
	protected function _getUpdateCondition($tableName)
	{
		return 'team_category_id = ' . $this->_db->quote($this->getExisting('team_category_id'));
	}

	/**
	* Gets the default set of options for this data writer.
	*
	* @return array
	*/
	protected function _getDefaultOptions()
	{
		return array(
			self::OPTION_REBUILD_CACHE => true
		);
	}

	protected function _validateParentCategoryId(&$parentId)
	{
		if ($this->isUpdate() && $parentId != 0 && $parentId != $this->getExisting('parent_category_id'))
		{
			$possibleParents = $this->_getCategoryModel()->getPossibleParentCategories($this->getMergedExistingData());
			if (!isset($possibleParents[$parentId]))
			{
				$this->error(new XenForo_Phrase('please_select_valid_parent_category'), 'parent_category_id');
				return false;
			}
		}

		return true;
	}

	protected function _updateRibbonAssociations()
	{
		if (!$this->get('team_count'))
		{
			return; // nothing to do if cat don't have any team.
		}

		$teams = $this->_getTeamModel()->getAllTeamsByCategoryId($this->get('team_category_id'));

		foreach ($teams as $team)
		{
			if (!$team['ribbon_display_class'])
			{
				continue;
			}

			$this->_getTeamModel()->updateRibbonAssociations($team, $this->getMergedData());
		}
	}

	protected function _postSave()
	{
		$db = $this->_db;

		if ($this->isInsert()
			|| $this->isChanged('display_order')
			|| $this->isChanged('parent_category_id')
			|| $this->isChanged('category_title')
		)
		{
			$this->_getCategoryModel()->rebuildCategoryStructure();
		}

		$newFieldIds = $this->getExtraData(self::DATA_FIELD_IDS);
		if (is_array($newFieldIds))
		{
			$this->_updateFieldAssociations($newFieldIds);
			$this->_getFieldModel()->rebuildFieldCategoryAssociationCache(array($this->get('team_category_id')));
		}

		if ($this->isUpdate() && $this->get('team_count') && $this->isChanged('ribbon_styling'))
		{
			$this->_updateRibbonAssociations();
		}
	}

	protected function _updateFieldAssociations(array $fieldIds)
	{
		$fieldIds = array_unique($fieldIds);

		$db = $this->_db;
		$categoryId = $this->get('team_category_id');

		$db->delete('xf_team_field_category', 'team_category_id = ' . $db->quote($categoryId));

		foreach ($fieldIds AS $fieldId)
		{
			$db->insert('xf_team_field_category', array(
				'field_id' => $fieldId,
				'team_category_id' => $categoryId
			));
		}

		return $fieldIds;
	}

	protected function _postDelete()
	{
		$categoryId = $this->get('team_category_id');
		$db = $this->_db;

		$db->update('xf_team_category',
			array('parent_category_id' => $this->get('parent_category_id')),
			'parent_category_id = ' . $this->_db->quote($categoryId)
		);

		$db->delete('xf_team_field_category', 'team_category_id = ' . $db->quote($categoryId));
		$db->delete('xf_team_category_watch', 'team_category_id = ' . $db->quote($categoryId));

		$this->_getCategoryModel()->rebuildCategoryStructure();

		$iconPath = $this->_getCategoryModel()->getCategoryIconFilePath($categoryId);
		@unlink($iconPath);
	}

	/**
	 * Called when a team is updated in this category.
	 *
	 * @param Nobita_Teams_DataWriter_Team $team
	 */
	public function teamUpdate(Nobita_Teams_DataWriter_Team $teamDw)
	{
		if ($teamDw->get('team_state') != "visible")
		{
			// nothing to do!
			return;
		}

		if ($teamDw->isUpdate() && $teamDw->isChanged('team_category_id'))
		{
			$this->updateTeamCount(1);
			$this->updateFeaturedCount();

			$oldCat = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Category', XenForo_DataWriter::ERROR_SILENT);
			if ($oldCat->setExistingData($teamDw->getExisting('team_category_id')))
			{
				$oldCat->teamRemoved($teamDw);
				$oldCat->save();
			}
		}
		else if ($teamDw->isChanged('team_state'))
		{
			$this->updateTeamCount(1);
		}

		if ($this->isUpdate() && $this->isChanged('team_state'))
		{
			$this->updateFeaturedCount();
		}
	}

	/**
	 * Called when a team is removed from view in this category.
	 * Can apply to moves, deletes, etc.
	 *
	 * @param Nobita_Teams_DataWriter_Team $teamDw
	 */
	public function teamRemoved(Nobita_Teams_DataWriter_Team $teamDw)
	{
		if ($teamDw->getExisting('team_state') != "visible")
		{
			return;
		}

		$this->updateTeamCount(-1);
		$this->updateFeaturedCount();
	}

	public function updateTeamCount($adjust = null)
	{
		if ($adjust === null)
		{
			$this->set('team_count', $this->_db->fetchOne("
				SELECT COUNT(*)
				FROM xf_team
				WHERE team_category_id = ?
					AND team_state = 'visible'
			", $this->get('team_category_id')));
		}
		else
		{
			$this->set('team_count', $this->get('team_count') + $adjust);
		}
	}

	public function updateFeaturedCount($adjust = null)
	{
		if ($adjust === null)
		{
			$this->set('featured_count', $this->_db->fetchOne("
				SELECT COUNT(*)
				FROM xf_team_feature AS featured
				INNER JOIN xf_team AS team ON (team.team_id = featured.team_id)
				WHERE team.team_category_id = ?
					AND team.team_state = 'visible'
			", $this->get('team_category_id')));
		}
		else
		{
			$this->set('featured_count', $this->get('featured_count') + $adjust);
		}
	}

	/**
	 * Verifies the allowed user group IDs.
	 *
	 * @param array|string $userGroupIds Array or comma-delimited list
	 *
	 * @return boolean
	 */
	protected function _verifyAllowedUserGroupIds(&$userGroupIds)
	{
		if (!is_array($userGroupIds))
		{
			$userGroupIds = preg_split('#,\s*#', $userGroupIds);
		}

		$userGroupIds = array_map('intval', $userGroupIds);
		$userGroupIds = array_unique($userGroupIds);
		sort($userGroupIds, SORT_NUMERIC);
		$userGroupIds = implode(',', $userGroupIds);

		return true;
	}

	public function rebuildCounters()
	{
		$this->updateTeamCount();
		$this->updateFeaturedCount();
	}

	/**
	 * @return Nobita_Teams_Model_Category
	 */
	protected function _getCategoryModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Category');
	}

	protected function _getFieldModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Field');
	}

	protected function _getTeamModel()
	{
		return Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
	}
}
