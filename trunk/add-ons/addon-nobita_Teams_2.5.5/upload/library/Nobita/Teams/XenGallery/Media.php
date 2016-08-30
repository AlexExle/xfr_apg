<?php

class Nobita_Teams_XenGallery_Media
{
	/**
	 * @see Nobita_Teams_XenGallery_ControllerPublic_Media::actionSaveMedia
	 */
	protected static $groupId;

	public static function setGroupId($groupId)
	{
		self::$groupId = $groupId;
	}

	public static function getGroupId()
	{
		return self::$groupId;
	}

	public static function isVisibleCategory($categoryId)
	{
		$storeCategoryId = Nobita_Teams_Option::get('XenMediaCategoryId');
		$hide = Nobita_Teams_Option::get('XenMediaHideStoreCat');

		if (!$hide)
		{
			return true;
		}

		return $storeCategoryId != $categoryId;
	}

}
