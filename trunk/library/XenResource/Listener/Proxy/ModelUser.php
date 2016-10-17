<?php

class XenResource_Listener_Proxy_ModelUser extends XFCP_XenResource_Listener_Proxy_ModelUser
{
	public function prepareUserConditions(array $conditions, array &$fetchOptions)
	{
		$result = parent::prepareUserConditions($conditions, $fetchOptions);

		if (!empty($conditions['resource_count']) && is_array($conditions['resource_count']))
		{
			$result .= ' AND (' . $this->getCutOffCondition("user.resource_count", $conditions['resource_count']) . ')';
		}

		return $result;
	}

	public function prepareUserOrderOptions(array &$fetchOptions, $defaultOrderSql = '')
	{
		$choices = array(
			'resource_count' => 'user.resource_count'
		);
		$order = $this->getOrderByClause($choices, $fetchOptions);
		if ($order)
		{
			return $order;
		}

		return parent::prepareUserOrderOptions($fetchOptions, $defaultOrderSql);
	}

	public function mergeUsers(array $target, array $source)
	{
		$success = parent::mergeUsers($target, $source);
		if ($success && $target['user_id'] != $source['user_id'] && !empty($source['resource_count']))
		{
			$this->_getDb()->query("
				UPDATE xf_user
				SET resource_count = resource_count + ?
				WHERE user_id = ?
			", array($source['resource_count'], $target['user_id']));
		}

		return $success;
	}
}