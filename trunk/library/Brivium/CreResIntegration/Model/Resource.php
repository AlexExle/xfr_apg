<?php

class Brivium_CreResIntegration_Model_Resource extends XFCP_Brivium_CreResIntegration_Model_Resource
{
	/**
	 * Returns a user record based on an input title
	 *
	 * @param string $title
	 * @param array $fetchOptions Resource fetch options
	 *
	 * @return array|false
	 */
	public function getResourceByTitle($title, array $fetchOptions = array())
	{
		$joinOptions = $this->prepareResourceFetchOptions($fetchOptions);

		return $this->_getDb()->fetchRow('
			SELECT resource.*
				' . $joinOptions['selectFields'] . '
			FROM xf_resource AS resource
			' . $joinOptions['joinTables'] . '
			WHERE resource.title = ?
		', $title);
	}

	public function canDownloadResource(array $resource, array $category, &$errorPhraseKey = '', array $viewingUser = null, array $categoryPermissions = null)
	{
		$result = parent::canDownloadResource($resource, $category, $errorPhraseKey, $viewingUser, $categoryPermissions);
		if($result && empty($GLOBALS['BRCRI_purchaseProcess'])){
			$purchasedModel = $this->_getResourcePurchasedModel();
			if(!$this->canDownloadWithoutPurchase($resource, $category)){
				if($resource['credit_price']!=0 && $viewingUser['user_id']!=$resource['user_id'] && !$purchasedModel->checkResourcePurchased($resource['resource_id'], $viewingUser['user_id'])){
					return false;
				}
			}
		}
		return $result;
	}

	public function prepareResourceConditions(array $conditions, array &$fetchOptions)
	{
		$priceConditions = array();
		if (!empty($conditions['price']) && is_array($conditions['price'])){
			$priceConditions = $conditions['price'];
			$conditions['price'] = array();
		}
		$result = parent::prepareResourceConditions($conditions, $fetchOptions);
		$sqlConditions = array($result);
		$db = $this->_getDb();
		if (!empty($conditions['title']))
		{
			if (is_array($conditions['title']))
			{
				$sqlConditions[] = 'resource.title LIKE ' . XenForo_Db::quoteLike($conditions['title'][0], $conditions['title'][1], $db);
			}
			else
			{
				$sqlConditions[] = 'resource.title LIKE ' . XenForo_Db::quoteLike($conditions['title'], 'lr', $db);
			}
		}
		if (!empty($conditions['brcri_currency_id']))
		{
			$sqlConditions[] = 'resource.brcri_currency_id = ' . $db->quote($conditions['brcri_currency_id']);
		}
		if (!empty($priceConditions) && is_array($priceConditions))
		{
			list($operator, $cutOff) = $priceConditions;
			$cutOff = floatval($cutOff);

			$this->assertValidCutOffOperator($operator);
			if($operator=='='&&$cutOff==0){
				$sqlConditions[] = "( resource.credit_price $operator " . $db->quote($cutOff) . " AND resource.price $operator " . $db->quote($cutOff)." )";
			}else{
				$sqlConditions[] = "( resource.credit_price $operator " . $db->quote($cutOff) . " OR resource.price $operator " . $db->quote($cutOff)." )";
			}
		}
		if (!empty($conditions['purchased_user_id']))
		{
			$sqlConditions[] = 'resource_purchased.user_id = ' . $db->quote($conditions['purchased_user_id']);
			$fetchOptions['BRCRI_join'] = true;
		}
		if (count($sqlConditions) > 1) {
			return $this->getConditionsForClause($sqlConditions);
		} else {
			return $result;
		}

	}
	public function prepareResourceFetchOptions(array $fetchOptions)
	{
		$result = parent::prepareResourceFetchOptions($fetchOptions);
		extract($result);
		if (!empty($fetchOptions['BRCRI_join']))
		{
			$selectFields .= ',
				resource_purchased.purchased_date ,
				resource_purchased.active AS purchased_active,
				resource.last_update';
			$joinTables .= '
				LEFT JOIN xf_resource_purchased AS resource_purchased ON
					(resource_purchased.resource_id = resource.resource_id)';
		}
		if (isset($fetchOptions['purchaseUserId']))
		{
			$fetchOptions['purchaseUserId'] = intval($fetchOptions['purchaseUserId']);
			if ($fetchOptions['purchaseUserId'])
			{
				// note: quoting is skipped; intval'd above
				$selectFields .= ',
					IF(resource_purchased.user_id IS NOT NULL AND resource_purchased.active = 1, 1, 0) AS is_purchased';
				$joinTables .= '
					LEFT JOIN xf_resource_purchased AS resource_purchased ON
						(resource_purchased.resource_id = resource.resource_id AND resource_purchased.user_id = ' . $fetchOptions['purchaseUserId'] . ')';
			}
			else
			{
				$selectFields .= ',
					0 AS is_purchased';
			}
		}
		return compact('selectFields' , 'joinTables');
	}


	/**
	 * Fetch resources based on the conditions and options specified
	 *
	 * @param array $conditions
	 * @param array $fetchOptions
	 *
	 * @return array
	 */
	public function getResources(array $conditions = array(), array $fetchOptions = array())
	{
		if (isset($GLOBALS['BRCRI_CPR_actionCategory'])) {
			if (isset($GLOBALS['BRCRI_CPR_actionCategory']['brcri_currency_id'])) {
				$conditions['brcri_currency_id'] = $GLOBALS['BRCRI_CPR_actionCategory']['brcri_currency_id'];
			}
			if (isset($GLOBALS['BRCRI_CPR_actionCategory']['credit_price'])) {
				$conditions['credit_price'] = $GLOBALS['BRCRI_CPR_actionCategory']['credit_price'];
			}
			unset($GLOBALS['BRCRI_CPR_actionCategory']);
		}
		return parent::getResources($conditions, $fetchOptions);
	}

	public function canDownloadWithoutPurchase($resource, $category, &$errorPhraseKey = '', $viewingUser = null, $categoryPermissions = null)
	{
		$this->standardizeViewingUserReferenceForCategory($category, $viewingUser, $categoryPermissions);

		if ($viewingUser['user_id'] == $resource['user_id'])
		{
			return true;
		}

		return XenForo_Permission::hasContentPermission($categoryPermissions, 'DownloadNotPurchase');
	}

	public function prepareResource(array $resource, array $category = null, array $viewingUser = null)
	{
		$resource = parent::prepareResource($resource, $category, $viewingUser);
		$resource['canDownloadWithoutPurchase'] = $this->canDownloadWithoutPurchase($resource, $category, $null, $viewingUser);

		return $resource;
	}


	protected function _getResourcePurchasedModel()
	{
		return $this->getModelFromCache('Brivium_CreResIntegration_Model_Purchased');
	}

}