<?php
class Brivium_KeyCode_Model_Code extends XenForo_Model
{
	const FETCH_CODE_RECEIVE    			= 0x01;
	const FETCH_CODE_CATEGORY    			= 0x02;
	const FETCH_CODE_FULL    			= 0x07;
	/**
	 * Get all codes , in their relative display order.
	 *
	 * @return array Format: [] => code info
	 */
	public function getAllCodes()
	{
		return $this->fetchAllKeyed('
			SELECT *
			FROM xf_brivium_keycode_code
			ORDER BY create_date
		', 'code_id');
	}

	/**
	 * Returns code records based on code_id.
	 *
	 * @param string $codeId
	 *
	 * @return array|false
	 */
	public function getCodeById($codeId = 0)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_brivium_keycode_code
			WHERE  code_id = ?
		', array( $codeId));
	}

	/**
	 * Returns code records based on code.
	 *
	 * @param string $code
	 *
	 * @return array|false
	 */
	public function getCodeByCode($code = '')
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_brivium_keycode_code
			WHERE  code = ?
		', array( $code));
	}
	/**
	 * Prepares a collection of code fetching related conditions into an SQL clause
	 *
	 * @param array $conditions List of conditions
	 * @param array $fetchOptions Modifiable set of fetch options (may have joins pushed on to it)
	 *
	 * @return string SQL clause (at least 1=1)
	 */
	public function prepareCodeConditions(array $conditions, array &$fetchOptions)
	{
		$sqlConditions = array();
		$db = $this->_getDb();

		if (!empty($conditions['code']))
		{
			if (is_array($conditions['code']))
			{
				$sqlConditions[] = 'code.code LIKE ' . XenForo_Db::quoteLike($conditions['code'][0], $conditions['code'][1], $db);
			}
			else
			{
				$sqlConditions[] = 'code.code LIKE ' . XenForo_Db::quoteLike($conditions['code'], 'lr', $db);
			}
		}
		if (!empty($conditions['code_id']))
		{
			if (is_array($conditions['code_id']))
			{
				$sqlConditions[] = 'code.code_id IN (' . $db->quote($conditions['code_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'code.code_id = ' . $db->quote($conditions['code_id']);
			}
		}
		if (!empty($conditions['user_id']))
		{
			if (is_array($conditions['user_id']))
			{
				$sqlConditions[] = 'code.user_id IN (' . $db->quote($conditions['user_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'code.user_id = ' . $db->quote($conditions['user_id']);
			}
		}
		if (!empty($conditions['code_category_id']))
		{
			$sqlConditions[] = 'code.code_category_id = ' . $db->quote($conditions['code_category_id']);
		}
		if (!empty($conditions['code_state']))
		{
			$sqlConditions[] = 'code.code_state = ' . $db->quote($conditions['code_state']);
		}
		if (!empty($conditions['date_receive']) && is_array($conditions['date_receive']))
		{
			list($operator, $cutOff) = $conditions['date_receive'];

			$this->assertValidCutOffOperator($operator);
			$sqlConditions[] = "code.date_receive $operator " . $db->quote($cutOff);
		}
		if (!empty($conditions['create_date']) && is_array($conditions['create_date']))
		{
			list($operator, $cutOff) = $conditions['create_date'];

			$this->assertValidCutOffOperator($operator);
			$sqlConditions[] = "code.create_date $operator " . $db->quote($cutOff);
		}

		return $this->getConditionsForClause($sqlConditions);
	}

	public function prepareCodeFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';
		$orderBy = '';
		$orderBySecondary = '';
		if (!empty($fetchOptions['order']))
		{
			switch ($fetchOptions['order'])
			{
				case 'create_date':
					$orderBy = 'code.' . $fetchOptions['order'];
					break;
				case 'code':
				case 'user_id':
				case 'date_receive':
				case 'code_state':
					$orderBy = 'code.' . $fetchOptions['order'];
					$orderBySecondary = ', code.create_date DESC';
					break;
				default:
					$orderBy = 'code.create_date';
			}
			if (!isset($fetchOptions['orderDirection']) || $fetchOptions['orderDirection'] == 'desc')
			{
				$orderBy .= ' DESC';
			}
			else
			{
				$orderBy .= ' ASC';
			}
			$orderBy .= $orderBySecondary;
		}

		if (!empty($fetchOptions['join']))
		{
			if ($fetchOptions['join'] & self::FETCH_CODE_RECEIVE)
			{
				$selectFields .= ', user_receive.username AS username';
				$joinTables .= '
					LEFT JOIN xf_user AS user_receive ON
						(user_receive.user_id = code.user_id)';
			}
			if ($fetchOptions['join'] & self::FETCH_CODE_CATEGORY)
			{
				$selectFields .= ',category.*';
				$joinTables .= '
					LEFT JOIN xf_brivium_keycode_category AS category ON
						(category.code_category_id = code.code_category_id)';
			}

		}
		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables,
			'orderClause'  => ($orderBy ? "ORDER BY $orderBy" : '')
		);
	}

	/**
	 * Gets codes that match the given conditions.
	 *
	 * @param array $conditions Conditions to apply to the fetching
	 * @param array $fetchOptions Collection of options that relate to fetching
	 *
	 * @return array Format: [code id] => info
	 */
	public function getCodes(array $conditions, array $fetchOptions = array())
	{
		$whereConditions = $this->prepareCodeConditions($conditions, $fetchOptions);

		$sqlClauses = $this->prepareCodeFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->fetchAllKeyed($this->limitQueryResults(			'
				SELECT code.*
					' . $sqlClauses['selectFields'] . '
				FROM xf_brivium_keycode_code AS code
				' . $sqlClauses['joinTables'] . '
				WHERE ' . $whereConditions . '
				' . $sqlClauses['orderClause'] . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'code_id');
	}

	public function getUnusingCodesByCategoryId($categoryId = 0)
	{
		return $this->fetchAllKeyed('
				SELECT code.*
				FROM xf_brivium_keycode_code AS code
				WHERE code_category_id = '.$categoryId.' AND user_id = 0
			'
		, 'code_id');
	}

	public function getUnusingCodeByCategoryId($categoryId = 0)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_brivium_keycode_code
			WHERE code_category_id = ? AND user_id = ?
		', array( $categoryId,0));
	}

	/**
	 * Gets the count of codes with the specified criteria.
	 *
	 * @param array $conditions Conditions to apply to the fetching
	 *
	 * @return integer
	 */
	public function countCodes(array $conditions)
	{
		$fetchOptions = array();
		$whereConditions = $this->prepareCodeConditions($conditions, $fetchOptions);
		return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM xf_brivium_keycode_code AS code
			WHERE ' . $whereConditions . '
		');
	}

	/**
	 * Get all categories.
	 *
	 * @return array Format: [] => code info
	 */
	public function getAllCategories($active = 0)
	{
		$where ="";
		if($active)$where = "WHERE active = {$active}";
		return $this->fetchAllKeyed('
				SELECT *
				FROM xf_brivium_keycode_category
				'.$where .'
				ORDER BY title
			', 'code_category_id');
	}

	/**
	 * Returns category records based on code_id.
	 *
	 * @param string $categoryId
	 *
	 * @return array|false
	 */
	public function getCategoryById($categoryId = 0)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_brivium_keycode_category
			WHERE  code_category_id = ?
		', array( $categoryId));
	}

	/**
	 * Returns an array of all Categories, suitable for use in ACP template syntax as options source.
	 *
	 * @param array $currencyTree
	 *
	 * @return array
	 */
	public function getCategoriesForOptionsTag($selectedId = null, $allCategories= null)
	{
		if ($allCategories === null)
		{
			$allCategories = $this->getAllCategories();
		}

		$categories = array();
		foreach ($allCategories AS $id => $category)
		{
			$categories[$id] = array(
				'value' => $category['code_category_id'],
				'label' => $category['title'],
				'selected' => ($selectedId == $category['code_category_id']),
			);
		}

		return $categories;
	}

	public function checkCreditVersion()
	{
		$xenAddons = XenForo_Application::get('addOns');
		return $xenAddons['Brivium_Credits'];
	}

	public function checkValidMoneyTypeOptions($moneyTypeOptions, &$errorString)
	{
		if(empty($options['enabled'])) return true;
		if(	!empty($moneyTypeOptions['money_type']) && $moneyTypeOptions['money_type']!='trophy_points'){
			if(($moneyTypeOptions['money_type']=='brivium_credit_premium' && !$playModel->canPurchaseCreditPremium($errorString, 'BRRPS_play')) ||
				($moneyTypeOptions['money_type']=='brivium_credit_free' && !$playModel->canPurchaseCreditFree($errorString, 'BRRPS_play'))
			){
				return false;
			}
		}
		return true;
	}

	public function checkInclude($user,$category){
		$check = true;
		$includeGroups = unserialize($category['user_groups']);
		if(!empty($includeGroups) && $includeGroups!= $this->_getDefaultDataArray()){
			$check = false;
			$inGroups = $user['user_group_id'];

			if (!empty($user['secondary_group_ids']))
			{
				$inGroups .= ','.$user['secondary_group_ids'];
			}

			$groupCheck = explode(',',$inGroups);

			unset($inGroups);

			foreach ($groupCheck AS $groupId)
			{
				if (in_array($groupId, $includeGroups))
				{
					$check = true;
					break;
				}
			}
		}
		return $check;
	}

	public function sendEmail($user, $code, $userCredit, $category){
		$currentCredit = $userCredit - $category['price'];
		$params = array(
			'user' => $user,
			'category' => $category,
			'code' => $code,
			'currentCredit' => $currentCredit,

			'boardTitle' => XenForo_Application::get('options')->boardTitle
		);
		$mail = XenForo_Mail::create('BRKC_key_code_info', $params, $user['language_id']);
		return $mail->send($user['email'], $user['username']);
	}

	protected function _getDefaultDataArray(){
		return array(0=>0);
	}

}