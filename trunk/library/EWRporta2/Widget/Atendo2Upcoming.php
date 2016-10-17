<?php

class EWRporta2_Widget_Atendo2Upcoming extends XenForo_Model
{
	public function getCachedData(&$widget, $options)
	{
		if ((!$addon = $this->getModelFromCache('XenForo_Model_AddOn')->getAddOnById('EWRatendo2')) || empty($addon['active']))
		{
			return 'killWidget';
		}
		
		$start = XenForo_Application::$time;
		$end = XenForo_Application::$time + (86400 * $options['atendo2upcoming_range']);
		$limit = $options['atendo2upcoming_limit'];
		$params = array();
		
		return $this->getOccurs($start, $end, $limit, $params);
	}
	
	public function getUncachedData($widget, &$options)
	{
		if ($options['atendo2upcoming_filters'] && $params = $this->getModelFromCache('EWRatendo2_Model_Settings')->getSettingByUserId(XenForo_Visitor::getUserId()))
		{
			$start = XenForo_Application::$time;
			$end = XenForo_Application::$time + (86400 * $options['atendo2upcoming_range']);
			$limit = $options['atendo2upcoming_limit'];
		
			$widget['wCached'] = $this->getOccurs($start, $end, $limit, $params);
			$options['params'] = $params;
		}
		
		foreach ($widget['wCached'] AS $key => &$occur)
		{
			$occur = $this->getModelFromCache('EWRatendo2_Model_Occurs')->prepareOccur($occur, false);
		}
		
		return $widget['wCached'];
	}
	
	private function getOccurs($start, $end, $limit, $params)
	{
		list($select, $having, $join, $where) = $this->getModelFromCache('EWRatendo2_Model_Occurs')->getOccurParams($params);
		
		$occurs = $this->_getDb()->fetchAll("
			SELECT EWRatendo2_occurs.*, EWRatendo2_events.*, xf_user.*,
				IF(xf_user.username IS NULL, EWRatendo2_events.username, xf_user.username) AS username
				$select
			FROM EWRatendo2_occurs
				INNER JOIN EWRatendo2_events ON (EWRatendo2_events.event_id = EWRatendo2_occurs.event_id)
				LEFT JOIN xf_user ON (xf_user.user_id = EWRatendo2_events.user_id)
				$join
			WHERE EWRatendo2_events.event_state = 'visible'
				AND (EWRatendo2_occurs.occur_start >= ? AND EWRatendo2_occurs.occur_start <= ?)
				$where
			GROUP BY EWRatendo2_events.event_id
			$having
			ORDER BY EWRatendo2_occurs.occur_start ASC
			LIMIT ?
		", array($start, $end, $limit));
		
		return $occurs;
	}
}