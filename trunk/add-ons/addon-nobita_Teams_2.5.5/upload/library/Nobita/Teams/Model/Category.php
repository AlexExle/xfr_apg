<?php

class Nobita_Teams_Model_Category extends Nobita_Teams_Model_Abstract
{
	public static $iconSize = 16;
	public static $iconQuality = 95;

	public function getCategoryById($categoryId, array $fetchOptions = array())
	{
		$joinOptions = $this->prepareCategoryFetchOptions($fetchOptions);

		return $this->_getDb()->fetchRow('
			SELECT team_category.*
				' . $joinOptions['selectFields'] . '
			FROM xf_team_category AS team_category
			' . $joinOptions['joinTables'] . '
			WHERE team_category.team_category_id = ?
		', $categoryId);
	}

	public function getCategoriesByIds(array $categoryIds, array $fetchOptions = array())
	{
		if (!$categoryIds)
		{
			return array();
		}

		$joinOptions = $this->prepareCategoryFetchOptions($fetchOptions);

		return $this->fetchAllKeyed('
			SELECT team_category.*
				' . $joinOptions['selectFields'] . '
			FROM xf_team_category AS team_category
			' . $joinOptions['joinTables'] . '
			WHERE team_category.team_category_id IN (' . $this->_getDb()->quote($categoryIds) . ')
		', 'team_category_id');
	}

	public function getAllCategories(array $fetchOptions = array())
	{
		$joinOptions = $this->prepareCategoryFetchOptions($fetchOptions);

		return $this->fetchAllKeyed('
			SELECT team_category.*
				' . $joinOptions['selectFields'] . '
			FROM xf_team_category AS team_category
			' . $joinOptions['joinTables'] . '
			ORDER BY team_category.lft
		', 'team_category_id');
	}

	public function getViewableCategories(array $fetchOptions = array(), array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		$categories = $this->getAllCategories($fetchOptions);
		if (!$categories)
		{
			return array();
		}

		foreach ($categories AS $key => $category)
		{
			if (!$this->canViewCategory($category, $null, $viewingUser))
			{
				unset($categories[$key]);
			}
		}

		return $categories;
	}

	public function getChildrenOfCategoryId($categoryId, array $fetchOptions = array())
	{
		$joinOptions = $this->prepareCategoryFetchOptions($fetchOptions);

		return $this->fetchAllKeyed('
			SELECT team_category.*
				' . $joinOptions['selectFields'] . '
			FROM xf_team_category AS team_category
			' . $joinOptions['joinTables'] . '
			WHERE team_category.parent_category_id = ?
			ORDER BY team_category.lft
		', 'team_category_id', $categoryId);
	}

	public function getDescendantsOfCategory($category, $rgt = null)
	{
		if (is_array($category))
		{
			$lft = $category['lft'];
			$rgt = $category['rgt'];
		}
		else if (!is_null($rgt))
		{
			$lft = intval($category);
		}
		else if (!$category)
		{
			return $this->getAllCategories();
		}
		else
		{
			$category = $this->getCategoryById($category);
			if (!$category)
			{
				return array();
			}

			$lft = $category['lft'];
			$rgt = $category['rgt'];
		}

		if ($rgt == $lft + 1)
		{
			return array();
		}

		return $this->fetchAllKeyed('
			SELECT *
			FROM xf_team_category
			WHERE lft > ? AND rgt < ?
			ORDER BY lft
		', 'team_category_id', array($lft, $rgt));
	}

	public function getDescendantsOfCategoryIds(array $categoryIds)
	{
		$categories = $this->getAllCategories();

		$ranges = array();
		foreach ($categoryIds AS $categoryId)
		{
			if (isset($categories[$categoryId]))
			{
				$category = $categories[$categoryId];
				$ranges[] = array($category['lft'], $category['rgt']);
			}
		}

		$descendants = array();
		foreach ($categories AS $category)
		{
			foreach ($ranges AS $range)
			{
				if ($category['lft'] > $range[0] && $category['lft'] < $range[1])
				{
					$descendants[$category['team_category_id']] = $category;
					break;
				}
			}
		}

		return $descendants;
	}

	public function getPossibleParentCategories(array $category, array $fetchOptions = array())
	{
		$joinOptions = $this->prepareCategoryFetchOptions($fetchOptions);

		return $this->fetchAllKeyed('
			SELECT team_category.*
				' . $joinOptions['selectFields'] . '
			FROM xf_team_category AS team_category
			' . $joinOptions['joinTables'] . '
			WHERE team_category.lft < ? OR team_category.rgt > ?
			ORDER BY team_category.lft
		', 'team_category_id', array($category['lft'], $category['rgt']));
	}

	/**
	 * Prepares join-related fetch options.
	 *
	 * @param array $fetchOptions
	 *
	 * @return array Containing 'selectFields' and 'joinTables' keys.
	 */
	public function prepareCategoryFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';
		$db = $this->_getDb();

		if (isset($fetchOptions['watchUserId']))
		{
			if (!empty($fetchOptions['watchUserId']))
			{
				$selectFields .= ',
					IF(category_watch.user_id IS NULL, 0, 1) AS category_is_watched';
				$joinTables .= '
					LEFT JOIN xf_team_category_watch AS category_watch
						ON (category_watch.team_category_id = team_category.team_category_id
						AND category_watch.user_id = ' . $this->_getDb()->quote($fetchOptions['watchUserId']) . ')';
			}
			else
			{
				$selectFields .= ',
					0 AS forum_is_watched';
			}
		}

		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables
		);
	}

	public function groupCategoriesByParent(array $categories)
	{
		$grouped = array();
		foreach ($categories AS $category)
		{
			$grouped[$category['parent_category_id']][$category['team_category_id']] = $category;
			//$grouped[$category['parent_category_id']]['parentCategoryId'] = $category['parent_category_id'];
		}

		return $grouped;
	}

	public function ungroupCategories(array $grouped, array $filterIds = null, $parentCategoryId = 0)
	{
		$output = array();

		if (!empty($grouped[$parentCategoryId]))
		{
			foreach ($grouped[$parentCategoryId] AS $category)
			{
				if ($filterIds === null || in_array($category['team_category_id'], $filterIds))
				{
					$output[$category['team_category_id']] = $category;
				}

				$output += $this->ungroupCategories($grouped, $filterIds, $category['team_category_id']);
			}
		}

		return $output;
	}

	public function getDescendantCategoryIdsFromGrouped(array $grouped, $parentCategoryId = 0)
	{
		$parentIds = array($parentCategoryId);
		$output = array();
		do
		{
			$parentId = array_shift($parentIds);
			if (isset($grouped[$parentId]))
			{
				$keys = array_keys($grouped[$parentId]);
				$output = array_merge($output, $keys);
				$parentIds = array_merge($parentIds, $keys);
			}
		}
		while ($parentIds);

		return $output;
	}

	public function getCategoryBreadcrumb(array $category, $includeSelf = true)
	{
		$breadcrumbs = array();

		if (!isset($category['categoryBreadcrumb']))
		{
			$category['categoryBreadcrumb'] = unserialize($category['category_breadcrumb']);
		}

		foreach ($category['categoryBreadcrumb'] AS $catId => $breadcrumb)
		{
			$breadcrumbs[$catId] = array(
				'href' => Nobita_Teams_Link::buildTeamLink('canonical:categories', $breadcrumb),
				'value' => $breadcrumb['category_title']
			);
		}

		if ($includeSelf)
		{
			$breadcrumbs[$category['team_category_id']] = array(
				'href' => Nobita_Teams_Link::buildTeamLink('canonical:categories', $category),
				'value' => $category['category_title']
			);
		}

		return $breadcrumbs;
	}

	/**
	 * Gets permission-based conditions that apply to team fetching functions.
	 *
	 * @param array|null $category Category the teams will belong to
	 * @param array|null $viewingUser
	 * @param array|null $categoryPermissions
	 *
	 * @return array Keys: deleted (boolean), moderated (boolean or integer, if can only view single user's)
	 */
	public function getPermissionBasedFetchConditions(array $category = null, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		$viewAllModerated = XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'viewModerated');
		$viewAllDeleted = XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'viewDeleted');

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'viewModerated'))
		{
			$viewModerated = true;
		}
		else if ($viewingUser['user_id'])
		{
			$viewModerated = $viewingUser['user_id'];
		}
		else
		{
			$viewModerated = false;
		}

		return array(
			'moderated' => $viewModerated,
			'deleted' => $viewAllDeleted
		);
	}

	public function prepareCategory(array $category)
	{
		$category['categoryBreadcrumb'] = unserialize($category['category_breadcrumb']);

		$category['canAdd'] = $this->canAddTeam($category);

		$category['fieldCache'] = @unserialize($category['field_cache']);
		if (!is_array($category['fieldCache']))
		{
			$category['fieldCache'] = array();
		}

		$category['ribbonStyling'] = @unserialize($category['ribbon_styling']);
		if (!is_array($category['ribbonStyling']))
		{
			$category['ribbonStyling'] = array();
		}

		return $category;
	}

	public function prepareCategories(array $categories)
	{
		foreach ($categories AS &$category)
		{
			$category = $this->prepareCategory($category);
		}

		return $categories;
	}

	public function rebuildCategoryStructure()
	{
		$grouped = $this->groupCategoriesByParent($this->fetchAllKeyed('
			SELECT *
			FROM xf_team_category
			ORDER BY display_order
		', 'team_category_id'));

		$db = $this->_getDb();
		XenForo_Db::beginTransaction($db);

		$changes = $this->_getStructureChanges($grouped);
		foreach ($changes AS $categoryId => $change)
		{
			$db->update('xf_team_category', $change, 'team_category_id = ' . $db->quote($categoryId));
		}

		XenForo_Db::commit($db);

		return $changes;
	}

	protected function _getStructureChanges(array $grouped, $parentId = 0, $depth = 0,
		$startPosition = 1, &$nextPosition = 0, array $breadcrumb = array()
	)
	{
		$nextPosition = $startPosition;

		if (!isset($grouped[$parentId]))
		{
			return array();
		}

		$changes = array();
		$serializedBreadcrumb = serialize($breadcrumb);

		foreach ($grouped[$parentId] AS $categoryId => $category)
		{
			$left = $nextPosition;
			$nextPosition++;

			$thisBreadcrumb = $breadcrumb + array(
				$categoryId => array(
					'team_category_id' => $categoryId,
					'category_title' => $category['category_title'],
					'parent_category_id' => $category['parent_category_id'],
					'depth' => $category['depth'],
					'lft' => $category['lft'],
					'rgt' => $category['rgt'],
				)
			);

			$changes += $this->_getStructureChanges(
				$grouped, $categoryId, $depth + 1, $nextPosition, $nextPosition, $thisBreadcrumb
			);

			$catChanges = array();
			if ($category['depth'] != $depth)
			{
				$catChanges['depth'] = $depth;
			}
			if ($category['lft'] != $left)
			{
				$catChanges['lft'] = $left;
			}
			if ($category['rgt'] != $nextPosition)
			{
				$catChanges['rgt'] = $nextPosition;
			}
			if ($category['category_breadcrumb'] != $serializedBreadcrumb)
			{
				$catChanges['category_breadcrumb'] = $serializedBreadcrumb;
			}

			if ($catChanges)
			{
				$changes[$categoryId] = $catChanges;
			}

			$nextPosition++;
		}

		return $changes;
	}

	public function applyRecursiveCountsToGrouped(array $grouped, $parentCategoryId = 0)
	{
		if (! isset($grouped[$parentCategoryId]))
		{
			return array();
		}

		$this->_applyRecursiveCountsToGrouped($grouped, $parentCategoryId);

		/*$grouped['parentCategoryId'] = $parentCategoryId;
		$grouped['categoriesGrouped'] = $grouped[0];

		return $grouped;*/
		return array(
			'categoriesGrouped' => $grouped,
			'parentCategoryId' 	=> $parentCategoryId
		);
	}

	protected function _applyRecursiveCountsToGrouped(array &$grouped, $parentCategoryId)
	{
		$output = array(
			'team_count' => 0
		);

		foreach ($grouped[$parentCategoryId] AS $categoryId => &$category)
		{
			if (isset($grouped[$categoryId]))
			{
				$childCounts = $this->_applyRecursiveCountsToGrouped($grouped, $categoryId);

				$category['team_count'] += $childCounts['team_count'];
			}

			$output['team_count'] += $category['team_count'];
		}

		return $output;
	}

	public function canViewCategory(array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'view');
	}

	public function canUploadAttachments(array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		return ($category['allow_uploaded_file']
			&& $viewingUser['user_id']
		);
	}


	/**
	 * Determines if the category can be watched with the given permissions.
	 * This does not check viewing permissions.
	 *
	 * @param array $category
	 * @param string $errorPhraseKey
	 * @param array $viewingUser
	 * @param array|null $categoryPermissions
	 *
	 * @return boolean
	 */
	public function canWatchCategory(array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		return ($viewingUser['user_id'] ? true : false);
	}

	public function canAddTeam(array $category = null, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
		}

		$maxTeams = Nobita_Teams_Option::get('maxAddGroups');
		$bypass = XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'maxAddGroups_bypass');

		if($bypass)
		{
			// Fixed bug: User cannot bypass create groups
			return true;
		}

		if (!$maxTeams && !$bypass)
		{
			$errorPhraseKey = 'Teams_you_do_not_have_permission_to_create_group';
			return false;
		}

		if ($viewingUser['manage_team_count'] >= $maxTeams && !$bypass)
		{
			$errorPhraseKey = 'Teams_you_only_created_x_teams';
			return false;
		}

		if ($category)
		{
			if (!$category['allow_team_create'])
			{
				$errorPhraseKey = 'Teams_category_not_allow_create_teams';
				return false;
			}

			if (!XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'add'))
			{
				return false;
			}

			if (empty($category['allowed_user_group_ids']))
			{
				// bug reports: http://nobita.me/posts/4105
				return XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'add');
			}

			$userGroupIds = explode(',', $category['allowed_user_group_ids']);
			if (in_array(-1, $userGroupIds) || in_array($viewingUser['user_group_id'], $userGroupIds))
			{
				return true;
			}

			if ($viewingUser['secondary_group_ids'])
			{
				foreach (explode(',', $viewingUser['secondary_group_ids']) AS $userGroupId)
				{
					if (in_array($userGroupId, $userGroupIds))
					{
						return true;
					}
				}
			}

			$errorPhraseKey = 'Teams_category_not_permission_to_create';
			return false;
		}
		else
		{
			return  XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'add');
		}
	}

	public function getCategoryIconFilePath($categoryId, $externalDataPath = null)
	{
		if ($externalDataPath === null)
		{
			$externalDataPath = XenForo_Helper_File::getExternalDataPath();
		}

		return sprintf('%s/nobita/teams/category_icons/%d/%d.jpg',
			$externalDataPath,
			floor($categoryId / 1000),
			$categoryId
		);
	}

	public function uploadCategoryIcon(XenForo_Upload $upload, $categoryId)
	{
		if (!$categoryId)
		{
			throw new XenForo_Exception("Missing category ID.");
		}

		if (!$upload->isValid())
		{
			throw new XenForo_Exception($upload->getErrors(), true);
		}

		if (!$upload->isImage())
		{
			throw new XenForo_Exception(new XenForo_Phrase('uploaded_file_is_not_valid_image'), true);
		};

		$baseTempFile = $upload->getTempFile();

		$imageType = $upload->getImageInfoField('type');
		$width = $upload->getImageInfoField('width');
		$height = $upload->getImageInfoField('height');

		return $this->applyCategoryIcon($categoryId, $baseTempFile, $imageType, $width, $height);
	}

	public function applyCategoryIcon($categoryId, $fileName, $imageType = false, $width = false, $height = false)
	{
		if (!$imageType || !$width || !$height)
		{
			$imageInfo = getimagesize($fileName);
			if (!$imageInfo)
			{
				throw new XenForo_Exception('Non-image passed in to applyCategoryIcon');
			}
			$width = $imageInfo[0];
			$height = $imageInfo[1];
			$imageType = $imageInfo[2];
		}

		if (!in_array($imageType, array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG)))
		{
			throw new XenForo_Exception(new XenForo_Phrase('uploaded_file_is_not_valid_image'), true);
		}

		if (!XenForo_Image_Abstract::canResize($width, $height))
		{
			throw new XenForo_Exception(new XenForo_Phrase('uploaded_image_is_too_big'), true);
		}

		$maxDimensions = self::$iconSize;
		$imageQuality = self::$iconQuality;
		$outputType = $imageType;

		$image = XenForo_Image_Abstract::createFromFile($fileName, $imageType);
		if (!$image)
		{
			return false;
		}

		$image->thumbnailFixedShorterSide($maxDimensions);

		if ($image->getOrientation() != XenForo_Image_Abstract::ORIENTATION_SQUARE)
		{
			$cropX = floor(($image->getWidth() - $maxDimensions) / 2);
			$cropY = floor(($image->getHeight() - $maxDimensions) / 2);
			$image->crop($cropX, $cropY, $maxDimensions, $maxDimensions);
		}

		$newTempFile = tempnam(XenForo_Helper_File::getTempDir(), 'xf');
		if (!$newTempFile)
		{
			return false;
		}

		$image->output($outputType, $newTempFile, $imageQuality);
		unset($image);

		$filePath = $this->getCategoryIconFilePath($categoryId);
		$directory = dirname($filePath);

		if (XenForo_Helper_File::createDirectory($directory, true) && is_writable($directory))
		{
			if (file_exists($filePath))
			{
				@unlink($filePath);
			}

			$writeSuccess = XenForo_Helper_File::safeRename($newTempFile, $filePath);
			if ($writeSuccess && file_exists($newTempFile))
			{
				@unlink($newTempFile);
			}
		}
		else
		{
			$writeSuccess = false;
		}

		if ($writeSuccess)
		{
			$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Category');
			$dw->setExistingData($categoryId);
			$dw->set('icon_date', XenForo_Application::$time);
			$dw->save();
		}

		return $writeSuccess;
	}

	public function deleteCategoryIcon($categoryId)
	{
		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Category');
		$dw->setExistingData($categoryId);
		$dw->set('icon_date', 0);
		$dw->save();

		$filePath = $this->getCategoryIconFilePath($categoryId);
		@unlink($filePath);
	}



}
