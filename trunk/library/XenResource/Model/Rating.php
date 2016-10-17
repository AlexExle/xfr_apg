<?php

class XenResource_Model_Rating extends XenForo_Model
{
	const FETCH_USER = 0x01;
	const FETCH_RESOURCE = 0x02;
	const FETCH_CATEGORY = 0x04;
	const FETCH_DELETION_LOG = 0x08;

	public function getRatingById($ratingId, array $fetchOptions = array())
	{
		$joinOptions = $this->prepareRatingFetchOptions($fetchOptions);

		return $this->_getDb()->fetchRow('
			SELECT rating.*
				' . $joinOptions['selectFields'] . '
			FROM xf_resource_rating AS rating
			' . $joinOptions['joinTables'] . '
			WHERE resource_rating_id = ?
		', $ratingId);
	}

	public function getRatingByVersionAndUserId($versionId, $userId, array $fetchOptions = array())
	{
		$joinOptions = $this->prepareRatingFetchOptions($fetchOptions);

		return $this->_getDb()->fetchRow('
			SELECT rating.*
				' . $joinOptions['selectFields'] . '
			FROM xf_resource_rating AS rating
			' . $joinOptions['joinTables'] . '
			WHERE resource_version_id = ?
				AND user_id = ?
		', array($versionId, $userId));
	}

	public function getRatingsByIds(array $ratingIds, array $fetchOptions = array())
	{
		if (!$ratingIds)
		{
			return array();
		}

		$joinOptions = $this->prepareRatingFetchOptions($fetchOptions);

		return $this->fetchAllKeyed('
			SELECT rating.*
				' . $joinOptions['selectFields'] . '
			FROM xf_resource_rating AS rating
			' . $joinOptions['joinTables'] . '
			WHERE resource_rating_id IN (' . $this->_getDb()->quote($ratingIds) . ')
		', 'resource_rating_id');
	}

	/**
	* Fetch resource ratings based on the conditions and options specified
	*
	* @param array $conditions
	* @param array $fetchOptions
	*
	* @return array
	*/
	public function getRatings(array $conditions = array(), array $fetchOptions = array())
	{
		$whereClause = $this->prepareRatingConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareRatingOrderOptions($fetchOptions, 'rating.rating_date DESC');
		$joinOptions = $this->prepareRatingFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->fetchAllKeyed($this->limitQueryResults(
			'
				SELECT rating.*
					' . $joinOptions['selectFields'] . '
				FROM xf_resource_rating AS rating
				' . $joinOptions['joinTables'] . '
				WHERE ' . $whereClause . '
				' . $orderClause . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'resource_rating_id');
	}

	/**
	* Count the number of ratings that meet the given criteria.
	*
	* @param array $conditions
	*
	* @return integer
	*/
	public function countRatings(array $conditions = array())
	{
		$fetchOptions = array();

		$whereClause = $this->prepareRatingConditions($conditions, $fetchOptions);
		$joinOptions = $this->prepareRatingFetchOptions($fetchOptions);

		return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM xf_resource_rating AS rating
			' . $joinOptions['joinTables'] . '
			WHERE ' . $whereClause
		);
	}

	public function countReviewsAfterDateInResource($resourceId, $date)
	{
		return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM xf_resource_rating
			WHERE resource_id = ?
				AND is_review = 1
				AND rating_date > ?
		', array($resourceId, $date));
	}

	public function getLatestReviews(array $conditions = array(), array $fetchOptions = array())
	{
		$autoReadDate = XenForo_Application::$time - (XenForo_Application::get('options')->readMarkingDataLifetime * 86400);

		$conditions = array_merge(array(
			'moderated' => false,
			'deleted' => false,
			'is_review' => true,
			'rating_date' => array('>', $autoReadDate)
		), $conditions);

		return $this->getRatings($conditions, $fetchOptions);
	}

	public function countLatestReviews(array $conditions = array())
	{
		$autoReadDate = XenForo_Application::$time - (XenForo_Application::get('options')->readMarkingDataLifetime * 86400);

		$conditions = array_merge(array(
			'moderated' => false,
			'deleted' => false,
			'is_review' => true,
			'rating_date' => array('>', $autoReadDate)
		), $conditions);

		return $this->countRatings($conditions);
	}

	/**
	* Prepares a set of conditions against which to select ratings.
	*
	* @param array $conditions List of conditions.
	* @param array $fetchOptions The fetch options that have been provided. May be edited if criteria requires.
	*
	* @return string Criteria as SQL for where clause
	*/
	public function prepareRatingConditions(array $conditions, array &$fetchOptions)
	{
		$db = $this->_getDb();
		$sqlConditions = array();

		if (!empty($conditions['user_id']))
		{
			if (is_array($conditions['user_id']))
			{
				$sqlConditions[] = 'rating.user_id IN (' . $db->quote($conditions['user_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'rating.user_id = ' . $db->quote($conditions['user_id']);
			}
		}

		if (!empty($conditions['resource_id']))
		{
			if (is_array($conditions['resource_id']))
			{
				$sqlConditions[] = 'rating.resource_id IN (' . $db->quote($conditions['resource_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'rating.resource_id = ' . $db->quote($conditions['resource_id']);
			}
		}

		if (!empty($conditions['resource_version_id']))
		{
			if (is_array($conditions['resource_version_id']))
			{
				$sqlConditions[] = 'rating.resource_version_id IN (' . $db->quote($conditions['resource_version_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'rating.resource_version_id = ' . $db->quote($conditions['resource_version_id']);
			}
		}

		if (!empty($conditions['resource_category_id']))
		{
			$this->addFetchOptionJoin($fetchOptions, self::FETCH_RESOURCE);

			if (is_array($conditions['resource_category_id']))
			{
				$sqlConditions[] = 'resource.resource_category_id IN (' . $db->quote($conditions['resource_category_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'resource.resource_category_id = ' . $db->quote($conditions['resource_category_id']);
			}
		}

		if (isset($conditions['deleted']) || isset($conditions['moderated']))
		{
			$sqlConditions[] = $this->prepareStateLimitFromConditions($conditions, 'rating', 'rating_state');
		}
		else
		{
			// sanity check: only get visible updates unless we've explicitly said to get something else
			$sqlConditions[] = "rating.rating_state = 'visible'";
		}

		if (isset($conditions['is_review']))
		{
			$sqlConditions[] = 'rating.is_review = ' . ($conditions['is_review'] ? 1 : 0);
		}
		if (isset($conditions['count_rating']))
		{
			$sqlConditions[] = 'rating.count_rating = ' . ($conditions['count_rating'] ? 1 : 0);
		}

		if (!empty($conditions['rating_date']) && is_array($conditions['rating_date']))
		{
			$sqlConditions[] = $this->getCutOffCondition("rating.rating_date", $conditions['rating_date']);
		}

		return $this->getConditionsForClause($sqlConditions);
	}

	/**
	 * Construct 'ORDER BY' clause
	 *
	 * @param array $fetchOptions (uses 'order' key)
	 * @param string $defaultOrderSql Default order SQL
	 *
	 * @return string
	 */
	public function prepareRatingOrderOptions(array &$fetchOptions, $defaultOrderSql = '')
	{
		$choices = array(
			'rating_date' => 'resource.rating_date',
		);
		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}

	/**
	 * Prepares join-related fetch options.
	 *
	 * @param array $fetchOptions
	 *
	 * @return array Containing 'selectFields' and 'joinTables' keys.
	 */
	public function prepareRatingFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';
		$db = $this->_getDb();

		if (!empty($fetchOptions['join']))
		{
			if ($fetchOptions['join'] & self::FETCH_RESOURCE)
			{
				$selectFields .= ',
					resource.*, resource.title AS resource_title,
					resource.user_id AS resource_user_id,
					IF(resource_user.username IS NULL, resource.username, resource_user.username) AS resource_username,
					resource_user.gender AS resource_gender,
					resource_user.avatar_date AS resource_avatar_date,
					resource_user.gravatar AS resource_gravatar,
					rating.user_id';

				$joinTables .= '
					INNER JOIN xf_resource AS resource ON
						(resource.resource_id = rating.resource_id)
					LEFT JOIN xf_user AS resource_user ON
						(resource_user.user_id = resource.user_id)';
			}

			if ($fetchOptions['join'] & self::FETCH_CATEGORY && $fetchOptions['join'] & self::FETCH_RESOURCE)
			{
				$selectFields .= ',
					category.*, category.last_update AS category_last_update, resource.last_update';
				$joinTables .= '
					INNER JOIN xf_resource_category AS category ON
						(category.resource_category_id = resource.resource_category_id)';
			}

			if ($fetchOptions['join'] & self::FETCH_USER)
			{
				$selectFields .= ',
						user.*, user_profile.*';
				$joinTables .= '
						LEFT JOIN xf_user AS user ON
							(user.user_id = rating.user_id)
						LEFT JOIN xf_user_profile AS user_profile ON
							(user_profile.user_id = rating.user_id)';
			}

			if ($fetchOptions['join'] & self::FETCH_DELETION_LOG)
			{
				$selectFields .= ',
					deletion_log.delete_date, deletion_log.delete_reason,
					deletion_log.delete_user_id, deletion_log.delete_username';
				$joinTables .= '
					LEFT JOIN xf_deletion_log AS deletion_log ON
						(deletion_log.content_type = \'resource_rating\' AND deletion_log.content_id = rating.resource_rating_id)';
			}
		}

		if (!empty($fetchOptions['permissionCombinationId']))
		{
			$selectFields .= ',
				permission.cache_value AS category_permission_cache';
			$joinTables .= '
				LEFT JOIN xf_permission_cache_content AS permission
					ON (permission.permission_combination_id = ' . $db->quote($fetchOptions['permissionCombinationId']) . '
						AND permission.content_type = \'resource_category\'
						AND permission.content_id = resource.resource_category_id)';
		}

		return array(
				'selectFields' => $selectFields,
				'joinTables'   => $joinTables
		);
	}

	public function prepareRating(array $rating, array $resource, array $category, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (isset($rating['resource_title']))
		{
			$origRating = $rating['rating'];
			$rating = $this->_getResourceModel()->prepareResource($resource, $category, $viewingUser);
			$rating['resource_rating'] = $rating['rating'];
			$rating['rating'] = $origRating;

			$rating['resourceUser'] = array(
				'user_id' => $rating['resource_user_id'],
				'username' => $rating['resource_username'],
				'isIgnoring' => XenForo_Visitor::getInstance()->isIgnoring($rating['resource_user_id']),
				'gender' => $rating['resource_gender'],
				'avatar_date' => $rating['resource_avatar_date'],
				'gravatar' => $rating['resource_gravatar']
			);
		}

		$rating['canReport'] = $this->canReportRating($rating, $resource, $category, $null, $viewingUser);
		$rating['canDelete'] = $this->canDeleteRating($rating, $resource, $category, 'soft', $null, $viewingUser);
		$rating['canUndelete'] = $this->canUndeleteRating($rating, $resource, $category, $null, $viewingUser);
		$rating['canDeleteResponse'] = $this->canDeleteRatingResponse($rating, $resource, $category, $null, $viewingUser);
		$rating['canWarn'] = $this->canWarnRating($rating, $resource, $category, $null, $viewingUser);
		$rating['canReply'] = $this->canReplyToRating($rating, $resource, $category, $null, $viewingUser);

		$rating['canViewAnonymous'] = (
			$rating['user_id'] == $viewingUser['user_id']
			|| $this->getModelFromCache('XenForo_Model_User')->canBypassUserPrivacy($null, $viewingUser)
		);

		if (!empty($resource['user_group_id']))
		{
			$userModel = $this->getModelFromCache('XenForo_Model_User');
			$rating['user'] = $userModel->prepareUser($rating);
		}

		if ($rating['is_anonymous'])
		{
			$rating['user_id'] = 0;
			$rating['username'] = new XenForo_Phrase('rating_anonymous');
		}

		return $rating;
	}

	public function prepareRatings(array $ratings, array $resource, array $category, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		foreach ($ratings AS &$rating)
		{
			$rating = $this->prepareRating($rating, $resource, $category, $viewingUser);
		}

		return $ratings;
	}

	/**
	 * Determines if a user can view this rating for the given resource.
	 * Does not check parent viewing permissions.
	 *
	 * @param array $rating
	 * @param array $resource
	 * @param array $category
	 * @param string $errorPhraseKey
	 * @param array $viewingUser
	 *
	 * @return boolean
	 */
	public function canViewRating(array $rating, array $resource, array $category, &$errorPhraseKey = '', array $viewingUser = null, array $categoryPermissions = null)
	{
		$this->standardizeViewingUserReferenceForCategory($category, $viewingUser, $categoryPermissions);

		if ($rating['rating_state'] == 'deleted')
		{
			if (!XenForo_Permission::hasContentPermission($categoryPermissions, 'viewDeleted'))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Determines if a user can view ratings for the given resource, ensuring
	 * they can view all parent elements as well.
	 *
	 * @param array $rating
	 * @param array $resource
	 * @param array $category
	 * @param string $errorPhraseKey
	 * @param array $viewingUser
	 * @param array|null $categoryPermissions
	 *
	 * @return boolean
	 */
	public function canViewRatingAndContainer(array $rating, array $resource, array $category, &$errorPhraseKey = '', array $viewingUser = null, array $categoryPermissions = null)
	{
		if (!$this->canViewRating($rating, $resource, $category, $errorPhraseKey, $viewingUser, $categoryPermissions))
		{
			return false;
		}

		return $this->_getResourceModel()->canViewResourceAndContainer($resource, $category, $errorPhraseKey, $viewingUser, $categoryPermissions);
	}

	/**
	 * Determines if a user can delete this rating for the given resource.
	 * Does not check resource viewing permissions.
	 *
	 * @param array $rating
	 * @param array $resource
	 * @param array $category
	 * @param string $type
	 * @param string $errorPhraseKey
	 * @param array $viewingUser
	 * @param array|null $categoryPermissions
	 *
	 * @return boolean
	 */
	public function canDeleteRating(array $rating, array $resource, array $category, $type = 'soft', &$errorPhraseKey = '', array $viewingUser = null, array $categoryPermissions = null)
	{
		$this->standardizeViewingUserReferenceForCategory($category, $viewingUser, $categoryPermissions);

		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if ($type == 'hard')
		{
			return XenForo_Permission::hasContentPermission($categoryPermissions, 'deleteReviewAny')
				&& XenForo_Permission::hasContentPermission($categoryPermissions, 'hardDeleteAny');
		}

		if ($rating['user_id'] == $viewingUser['user_id'] && !$rating['author_response'])
		{
			return true;
		}

		return XenForo_Permission::hasContentPermission($categoryPermissions, 'deleteReviewAny');
	}

	/**
	 * Determines if a user can undelete this rating for the given resource.
	 * Does not check resource viewing permissions.
	 *
	 * @param array $rating
	 * @param array $resource
	 * @param array $category
	 * @param string $errorPhraseKey
	 * @param array $viewingUser
	 * @param array|null $categoryPermissions
	 *
	 * @return boolean
	 */
	public function canUndeleteRating(array $rating, array $resource, array $category, &$errorPhraseKey = '', array $viewingUser = null, array $categoryPermissions = null)
	{
		$this->standardizeViewingUserReferenceForCategory($category, $viewingUser, $categoryPermissions);

		return ($viewingUser['user_id']
			&& $rating['rating_state'] == 'deleted'
			&& XenForo_Permission::hasContentPermission($categoryPermissions, 'undelete')
		);
	}

	/**
	 * Determines if a user can delete this rating response for the given resource.
	 * Does not check resource viewing permissions.
	 *
	 * @param array $rating
	 * @param array $resource
	 * @param array $category
	 * @param string $errorPhraseKey
	 * @param array $viewingUser
	 * @param array|null $categoryPermissions
	 *
	 * @return boolean
	 */
	public function canDeleteRatingResponse(array $rating, array $resource, array $category, &$errorPhraseKey = '', array $viewingUser = null, array $categoryPermissions = null)
	{
		$this->standardizeViewingUserReferenceForCategory($category, $viewingUser, $categoryPermissions);

		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if (!$rating['is_review'] || !$rating['author_response'])
		{
			return false;
		}

		return (
			$resource['user_id'] == $viewingUser['user_id']
			|| XenForo_Permission::hasContentPermission($categoryPermissions, 'deleteReviewAny')
		);
	}

	/**
	* Checks that the viewing user may update the specified rating
	*
	* @param array $rating
	* @param array $resource
	* @param array $category
	* @param string $errorPhraseKey
	* @param array $viewingUser
	* @param array|null $categoryPermissions
	*
	* @return boolean
	*/
	public function canUpdateRating(array $rating, array $resource, array $category, &$errorPhraseKey = '', array $viewingUser = null, array $categoryPermissions = null)
	{
		$this->standardizeViewingUserReferenceForCategory($category, $viewingUser, $categoryPermissions);

		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if (!XenForo_Permission::hasContentPermission($categoryPermissions, 'rate'))
		{
			return false;
		}

		if ($rating['user_id'] != $viewingUser['user_id'])
		{
			return false;
		}

		if ($rating['rating_state'] != 'visible')
		{
			return true;
		}

		if (!$rating['is_review'])
		{
			return true;
		}

		if ($rating['author_response'])
		{
			$errorPhraseKey = 'cannot_update_rating_once_author_response';
			return false;
		}

		return true;
	}

	/**
	 * Checks that the viewing user may reply to the specified rating
	 *
	 * @param array $rating
	 * @param array $resource
	 * @param array $category
	 * @param string $errorPhraseKey
	 * @param array $viewingUser
	 * @param array|null $categoryPermissions
	 *
	 * @return boolean
	 */
	public function canReplyToRating(array $rating, array $resource, array $category, &$errorPhraseKey = '', array $viewingUser = null, array $categoryPermissions = null)
	{
		$this->standardizeViewingUserReferenceForCategory($category, $viewingUser, $categoryPermissions);

		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if (isset($resource['resource_user_id']))
		{
			$userId = $resource['resource_user_id'];
		}
		else
		{
			$userId = $resource['user_id'];
		}

		if ($userId != $viewingUser['user_id'])
		{
			return false;
		}

		if (!$rating['is_review'])
		{
			return false;
		}

		if ($rating['author_response'])
		{
			return false;
		}

		if ($rating['rating_state'] != 'visible')
		{
			return false;
		}

		return XenForo_Permission::hasContentPermission($categoryPermissions, 'reviewReply');
	}

	/**
	 * Checks that the viewing user may warn the specified rating
	 *
	 * @param array $rating
	 * @param array $resource
	 * @param array $category
	 * @param string $errorPhraseKey
	 * @param array $viewingUser
	 * @param array|null $categoryPermissions
	 *
	 * @return boolean
	 */
	public function canWarnRating(array $rating, array $resource, array $category, &$errorPhraseKey = '', array $viewingUser = null, array $categoryPermissions = null)
	{
		if (!$rating['is_review'])
		{
			return false;
		}

		$this->standardizeViewingUserReferenceForCategory($category, $viewingUser, $categoryPermissions);

		if ($rating['warning_id'] || empty($rating['user_id']))
		{
			return false;
		}

		if ($rating['user_id'] == $viewingUser['user_id'])
		{
			return false;
		}

		if (!empty($rating['is_admin']) || !empty($rating['is_moderator']))
		{
			return false;
		}

		if (!$viewingUser['user_id'])
		{
			return false;
		}

		return XenForo_Permission::hasContentPermission($categoryPermissions, 'warn');
	}

	/**
	 * Checks that the viewing user may report the specified rating
	 *
	 * @param array $rating
	 * @param array $resource
	 * @param array $category
	 * @param string $errorPhraseKey
	 * @param array $viewingUser
	 * @param array|null $categoryPermissions
	 *
	 * @return boolean
	 */
	public function canReportRating(array $rating, array $resource, array $category, &$errorPhraseKey = '', array $viewingUser = null, array $categoryPermissions = null)
	{
		if (!$rating['is_review'])
		{
			return false;
		}

		if ($rating['rating_state'] != 'visible')
		{
			return false;
		}

		return $this->getModelFromCache('XenForo_Model_User')->canReportContent($errorPhraseKey, $viewingUser);
	}

	/**
	 * Checks that the viewing user may managed a reported rating
	 *
	 * @param array $rating
	 * @param array $resource
	 * @param array $category
	 * @param string $errorPhraseKey
	 * @param array $viewingUser
	 * @param array|null $categoryPermissions
	 *
	 * @return boolean
	 */
	public function canManageReportedRating(array $rating, array $resource, array $category, &$errorPhraseKey = '', array $viewingUser = null, array $categoryPermissions = null)
	{
		$this->standardizeViewingUserReferenceForCategory($category, $viewingUser, $categoryPermissions);

		if (!$viewingUser['user_id'])
		{
			return false;
		}

		return XenForo_Permission::hasContentPermission($categoryPermissions, 'deleteReviewAny');
	}

	public function sendModeratorActionAlert($action, array $rating, array $resource, $reason = '', array $extra = array(), $alertUserId = null)
	{
		$extra = array_merge(array(
			'title' => $resource['title'],
			'link' => XenForo_Link::buildPublicLink('resources/reviews', $resource, array('review' => $rating)),
			'resourceLink' => XenForo_Link::buildPublicLink('resources', $resource),
			'reason' => $reason
		), $extra);

		if ($alertUserId === null)
		{
			$alertUserId = $rating['user_id'];
		}

		if (!$alertUserId)
		{
			return false;
		}

		XenForo_Model_Alert::alert(
			$alertUserId,
			0, '',
			'user', $alertUserId,
			'resource_rating_' . $action,
			$extra
		);
		return true;
	}

	/**
	 * Standardizes the viewing user reference for the specific resource category.
	 *
	 * @param integer|array $categoryId
	 * @param array|null $viewingUser Viewing user; if null, use visitor
	 * @param array|null $categoryPermissions Permissions for this category; if null, use visitor's
	 */
	public function standardizeViewingUserReferenceForCategory($categoryId, array &$viewingUser = null, array &$categoryPermissions = null)
	{
		$this->_getCategoryModel()->standardizeViewingUserReferenceForCategory(
			$categoryId, $viewingUser, $categoryPermissions
		);
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
}