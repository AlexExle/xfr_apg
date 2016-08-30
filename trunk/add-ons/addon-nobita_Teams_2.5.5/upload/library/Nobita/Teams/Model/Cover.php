<?php

class Nobita_Teams_Model_Cover extends Nobita_Teams_Model_Abstract
{
	protected $_dimensions = array(
		'width' => 1024,
		'height' => 350,
	);

	public function setDimension(array $dimensions)
	{
		$this->_dimensions = array_merge($this->_dimensions, $dimensions);
	}

	public function getDimension($key)
	{
		if(isset($this->_dimensions[$key]))
		{
			return $this->_dimensions[$key];
		}

		throw new InvalidArgumentException("Unknown cover dimension $key");
	}

	public function doUpload(XenForo_Upload $upload, array $team)
	{
		if (! $upload->isValid())
		{
			throw new XenForo_Exception($upload->getErrors(), true);
		}

		if (! $upload->isImage())
		{
			throw new XenForo_Exception(new XenForo_Phrase('uploaded_file_is_not_valid_image'), true);
		}

		$imageType = $upload->getImageInfoField('type');
		if (!in_array($imageType, array(IMAGETYPE_PNG, IMAGETYPE_JPEG)))
		{
			throw new XenForo_Exception(new XenForo_Phrase('uploaded_file_is_not_valid_image'), true);
		}

		$tempFile = $upload->getTempFile();

		$width = $upload->getImageInfoField('width');
		$height = $upload->getImageInfoField('height');

		if ($height < $this->getDimension('height'))
		{
			throw new XenForo_Exception(new XenForo_Phrase('Teams_upload_image_greater_x', array(
				'min' => $this->getDimension('height')
			)), true);
		}

		if (! XenForo_Image_Abstract::canResize($width, $height))
		{
			throw new XenForo_Exception(new XenForo_Phrase('uploaded_image_is_too_big'), true);
		}

		$maxFileSize = XenForo_Application::getOptions()->Teams_coverFileSize;
		if ($maxFileSize && filesize($tempFile) > $maxFileSize)
		{
			@unlink($tempFile);

			throw new XenForo_Exception(new XenForo_Phrase('Teams_your_cover_file_size_large_smaller_x', array(
				'size' => XenForo_Locale::numberFormat($maxFileSize, 'size')
			)), true);
		}

		if ($team['cover_date'])
		{
			$this->deleteCover($team['team_id']);
		}

		$source = $this->getCoverPath($team['team_id'], true);
		$directory = dirname($source);

		$success = false;
		if (XenForo_Helper_File::createDirectory($directory, true) && is_writable($directory))
		{
			$success = XenForo_Helper_File::safeRename($tempFile, $source);
		}

		if ($success)
		{
			return $this->cropCover($team, array(
				'cropX' => 0,
				'cropY' => 0,
				'containerW' => $this->getDimension('width')
			));
		}
		else
		{
			return false;
		}
	}

	public function cropCover(array $team, array $crops)
	{
		$source = $this->getCoverPath($team['team_id'], true);
		$imageinfo = getimagesize($source);

		if (! file_exists($source) OR !$imageinfo)
		{
			return false;
		}

		$width = isset($crops['containerW']) ? $crops['containerW'] : $this->getDimension('width');
		if ($width < $this->getDimension('width'))
		{
			$width = $this->getDimension('width');
		}

		$image = XenForo_Image_Gd::createFromFileDirect($source, $imageinfo[2]);

		$ratio = $width / $image->getWidth();
		$resizeHeight = $image->getHeight() * $ratio;

		$image->resize($width, $resizeHeight);
		$image->crop(0, $crops['cropY'] * $ratio, $width, $this->getDimension('height'));

		$output = $this->getCoverPath($team['team_id']);
		$directory = dirname($output);

		if (!XenForo_Helper_File::createDirectory($directory, true) OR !is_writable($directory))
		{
			throw new XenForo_Exception("Could not create directory for path $directory");
			return false;
		}

		if (file_exists($output))
		{
			@unlink($output);
		}

		$coverQuality = (int) Nobita_Teams_Option::get('coverQuality');
		$image->output(IMAGETYPE_JPEG, $output, $coverQuality);

		$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team');
		$dw->setExistingData($team);
		$dw->set('cover_date', XenForo_Application::$time);
		$dw->save();

		return XenForo_Application::$time;
	}

	public function deleteCover($teamId, $update = true, $externalDataPath = null)
	{
		if ($externalDataPath === null)
		{
			$externalDataPath = XenForo_Helper_File::getExternalDataPath();
		}

		$source = $this->getCoverPath($teamId, true);
		@unlink($source);

		$crop = $this->getCoverPath($teamId);
		@unlink($crop);

		if ($update)
		{
			$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team');
			$dw->setExistingData($teamId);
			$dw->set('cover_date', 0);
			$dw->save();
		}
	}

	public function getCoverPath($teamId, $source = false, $externalDataPath = null)
	{
		if ($externalDataPath === null)
		{
			$externalDataPath = XenForo_Helper_File::getExternalDataPath();
		}

		return sprintf('%s/teams/covers/%s%d/%d.jpg',
			$externalDataPath,
			$source ? 'source/' : '',
			floor($teamId / 1000),
			$teamId
		);
	}

	public function getCoverUrl($teamId, $source = false)
	{
		return sprintf('%s/teams/covers/%s%d/%d.jpg',
			XenForo_Application::$externalDataPath,
			$source ? 'source/' : '',
			floor($teamId / 1000),
			$teamId
		);
	}

	public function canUploadCover(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (XenForo_Visitor::getInstance()->isBrowsingWith('mobile'))
		{
			$errorPhraseKey = 'Teams_the_function_dont_support_on_mobile_device';
			return false;
		}

		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'Teams', 'manageCover'))
		{
			// permission go from User Group Permission
			return true;
		}

		if($this->isTeamOwner($team, $viewingUser))
		{
			return true;
		}

		$memberRecord = $this->getTeamMemberRecord($team['team_id'], $viewingUser);
		if(empty($memberRecord))
		{
			return false;
		}

		return $this->_getMemberRoleModel()->hasModeratorPermission($memberRecord['member_role_id'], 'manageCover');
	}


	public function canRepositionCover(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		return (
			   !empty($team['cover_date'])
			&& !empty($viewingUser['user_id'])
			&& $this->canUploadCover($team, $category, $errorPhraseKey, $viewingUser)
		);
	}
}
