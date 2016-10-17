<?php

class Nobita_Teams_BbCode_Formatter_Comment extends XenForo_BbCode_Formatter_Base
{
	private $_disabledTags = array('code', 'html', 'php', 'spoiler');

	public function getTags()
	{
		$tags = parent::getTags();
		foreach($tags as $tagName => $options)
		{
			if(in_array($tagName, $this->getDisabledTags()))
			{
				unset($tags[$tagName]);
			}
		}

		return $tags;
	}

	public function getDisabledTags()
	{
		return $this->_disabledTags;
	}

	public function getWysiwygButtons()
	{
		return array(
			'basic' => true,
			'extended' => true,
			'align' => true,
			'indent' => false,
			'smilies' => true,
			'link' => true,
			'image' => true,
			'media' => true,
			'block' => false,
			'list' => true
		);
	}
}