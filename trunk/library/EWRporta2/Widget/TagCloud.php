<?php

class EWRporta2_Widget_TagCloud extends XenForo_Model
{
	public function getCachedData($widget, &$options)
	{
		$tagCloud = $this->getModelFromCache('XenForo_Model_Tag')->getTagsForCloud(
			XenForo_Application::getOptions()->tagCloud['count'], XenForo_Application::getOptions()->tagCloudMinUses
		);
		$tagCloudLevels = $this->getModelFromCache('XenForo_Model_Tag')->getTagCloudLevels($tagCloud);
		
		return array(
			'tagCloud' => $tagCloud,
			'tagCloudLevels' => $tagCloudLevels,
		);
	}
}