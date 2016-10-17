<?php

class Brivium_CreResIntegration_Model_Purchased extends XenForo_Model
{
	const FETCH_PURCHASED_USER    			= 0x01;
	const FETCH_PURCHASED_FULL    			= 0x07;

	/**
	*	get Category by its id
	* 	@param integer $purchasedId
	* 	@param array $fetchOptions Collection of options related to fetching
	*	@return array|false Category info
	*/
	public function getResourcePurchasedById($purchasedId,$fetchOptions = array()){
		$joinOptions = $this->prepareResourcePurchasedFetchOptions($fetchOptions);
		return $this->_getDb()->fetchRow('
			SELECT purchased.*
			' .$joinOptions['selectFields']. '
			FROM xf_resource_purchased AS purchased
			' .$joinOptions['joinTables']. '
			WHERE purchased.resource_purchased_id = ?
		',$purchasedId);
	}
	/**
	*	Gets multi purchaseds.
	*
	*	@param array $purchasedIds
	*	@param array $fetchOptions Collection of options related to fetching
	*
	*	@return array Format: [purchased id] => info
	*/
	public function getResourcePurchasedsByIds(array $purchasedIds)
	{
		if (!$purchasedIds)
		{
			return array();
		}
		return $this->fetchAllKeyed('
			SELECT purchased.*
			FROM xf_resource_purchased AS purchased
			WHERE purchased.resource_purchased_id IN (' . $this->_getDb()->quote($purchasedIds) . ')
		', 'resource_purchased_id');
	}


	/**
	 * Prepares a collection of resource purchase record fetching related conditions into an SQL clause
	 *
	 * @param array $conditions List of conditions
	 * @param array $fetchOptions Modifiable set of fetch options (may have joins pushed on to it)
	 *
	 * @return string SQL clause (at least 1=1)
	 */
	public function prepareResourcePurchasedConditions(array $conditions, array &$fetchOptions)
	{
		$sqlConditions = array();
		$db = $this->_getDb();

		if (!empty($conditions['resource_purchased_id']))
		{
			$sqlConditions[] = 'purchased.resource_purchased_id = ' . $db->quote($conditions['resource_purchased_id']);
		}
		if (!empty($conditions['currency_id']))
		{
			$sqlConditions[] = 'purchased.currency_id = ' . $db->quote($conditions['currency_id']);
		}
		if (!empty($conditions['resource_version_id']))
		{
			$sqlConditions[] = 'purchased.resource_version_id = ' . $db->quote($conditions['resource_version_id']);
		}
		if (!empty($conditions['resource_id']))
		{
			if (is_array($conditions['resource_id']))
			{
				$sqlConditions[] = 'purchased.resource_id IN (' . $db->quote($conditions['resource_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'purchased.resource_id = ' . $db->quote($conditions['resource_id']);
			}
		}
		if (!empty($conditions['user_id']))
		{
			if (is_array($conditions['user_id']))
			{
				$sqlConditions[] = 'purchased.user_id IN (' . $db->quote($conditions['user_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'purchased.user_id = ' . $db->quote($conditions['user_id']);
			}
		}
		if (!empty($conditions['active']))
		{
			$sqlConditions[] = 'purchased.active = ' . $db->quote($conditions['active']);
		}
		if (!empty($conditions['resource_price']) && is_array($conditions['resource_price']))
		{
			list($operator, $cutOff) = $conditions['resource_price'];

			$this->assertValidCutOffOperator($operator);
			$sqlConditions[] = "purchased.resource_price $operator " . $db->quote($cutOff);
		}
		if (!empty($conditions['purchased_date']) && is_array($conditions['purchased_date']))
		{
			list($operator, $cutOff) = $conditions['purchased_date'];

			$this->assertValidCutOffOperator($operator);
			$sqlConditions[] = "purchased.purchased_date $operator " . $db->quote($cutOff);
		}
		if (!empty($conditions['start']))
		{
			$sqlConditions[] = 'purchased.purchased_date >= ' . $db->quote($conditions['start']);
		}

		if (!empty($conditions['end']))
		{
			$sqlConditions[] = 'purchased.purchased_date <= ' . $db->quote($conditions['end']);
		}

		return $this->getConditionsForClause($sqlConditions);
	}
	public function prepareResourcePurchasedFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';
		$orderBy = '';
		$orderBySecondary = '';
		if (!empty($fetchOptions['order']))
		{
			switch ($fetchOptions['order'])
			{
				case 'user_id':
				case 'resource_price':
					$orderBy = 'purchased.' . $fetchOptions['order'];
					$orderBySecondary = ', purchased.purchased_date DESC';
					break;
				default:
					$orderBy = 'purchased.purchased_date';
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
			if($fetchOptions['join'] & self::FETCH_PURCHASED_USER)
			{
				$selectFields .= ', user.*';
				$joinTables .= '
					LEFT JOIN xf_user AS user ON
						(user.user_id = purchased.user_id)';
			}

		}
		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables,
			'orderClause'  => ($orderBy ? "ORDER BY $orderBy" : '')
		);
	}

	/**
	 * Gets resource purchase record that match the given conditions.
	 *
	 * @param array $conditions Conditions to apply to the fetching
	 * @param array $fetchOptions Collection of options that relate to fetching
	 *
	 * @return array Format: [resource purchase record id] => info
	 */
	public function getResourcePurchaseds(array $conditions, array $fetchOptions = array())
	{
		$whereConditions = $this->prepareResourcePurchasedConditions($conditions, $fetchOptions);

		$sqlClauses = $this->prepareResourcePurchasedFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
		return $this->fetchAllKeyed($this->limitQueryResults(			'
				SELECT purchased.*
					' . $sqlClauses['selectFields'] . '
				FROM xf_resource_purchased AS purchased
				' . $sqlClauses['joinTables'] . '
				WHERE ' . $whereConditions . '
				' . $sqlClauses['orderClause'] . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'resource_purchased_id');
	}

	/**
	 * Gets the count of purchaseds with the specified criteria.
	 *
	 * @param array $conditions Conditions to apply to the fetching
	 *
	 * @return integer
	 */
	public function countResourcePurchaseds(array $conditions)
	{
		$fetchOptions = array();
		$whereConditions = $this->prepareResourcePurchasedConditions($conditions, $fetchOptions);
		return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM xf_resource_purchased AS purchased
			WHERE ' . $whereConditions . '
		');
	}
	public function prepareResourcePurchaseds(array $purchaseds)
	{
		if(!$purchaseds) return array();
		foreach($purchaseds AS &$purchased){
			$purchased = $this->prepareResourcePurchased($purchased);
		}
		return $purchaseds;
	}

	public function prepareResourcePurchased(array $purchased)
	{
		if(!$purchased) return array();
		$purchased['resource'] = $this->_getResourceModel()->getResourceById($purchased['resource_id']);
		return $purchased;
	}

	public function checkResourcePurchased($resourceId, $userId){
		return $this->_getDb()->fetchOne('
			SELECT 1
			FROM xf_resource_purchased
			WHERE user_id = ?
				AND resource_id = ?
				AND active = 1
			LIMIT 1
		', array($userId, $resourceId));
	}

	public function purchaseResource(array $resource,$category, $user, $eventTrigger, $userTax, $userActionTax, &$error){
		$creditModel = $this->_getCreditModel();

		$userTaxedAmount = $resource['credit_price'] + $userTax ;
		$userActionTaxedAmount = $resource['credit_price'] - $userActionTax;

		$dataCredit = array(
			'amount' 			=>	-$userTaxedAmount,
			'user'				=>	$user,
			'currency_id'		=>	$eventTrigger['currency_id'],
			'content_id' 		=>	$resource['resource_id'],
			'content_type'		=>	'xen_resource',
			'message'			=>	'['.$resource['title'].']',
		);
		$creditModel->setIsWaitSubmit(true);

		if(!$creditModel->updateUserCredit('ResourcePurchased', $user['user_id'], $dataCredit, $error)){
			return false;
		}
		$dataCredit = array(
			'user_action_id' 	=>	$user['user_id'],
			'user'				=>	$resource,
			'amount' 			=>	$userActionTaxedAmount,
			'currency_id'		=>	$resource['brcri_currency_id'],
			'content_id' 		=>	$resource['resource_id'],
			'content_type'		=>	'xen_resource',
			'message'			=>	'['.$resource['title'].']',
		);
		$creditModel->updateUserCredit('ResourceGetPurchased', $resource['user_id'], $dataCredit, $error);
		$creditModel->commitUpdate();

		$db = $this->_getDb();
		$db->query('
			INSERT INTO xf_resource_purchased
				(resource_version_id, user_id, resource_id,currency_id, purchased_date, resource_price)
			VALUES
				(?, ?, ?, ?, ?, ?)
		', array($resource['current_version_id'], $user['user_id'], $resource['resource_id'], $resource['brcri_currency_id'], XenForo_Application::$time, $resource['credit_price']));

		return true;
	}

	public function resourcePriceFormat(array $resource){
		return XenForo_Application::get('brcCurrencies')->currencyFormat($resource['credit_price'],false,$resource['brcri_currency_id']);
	}
	/**
	 * @return XenResource_Model_Resource
	 */
	protected function _getResourceModel()
	{
		return $this->getModelFromCache('XenResource_Model_Resource');
	}

	/**
	 * Gets the action model.
	 *
	 * @return Brivium_Credits_Model_Credit
	 */
	protected function _getCreditModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Credit');
	}
}