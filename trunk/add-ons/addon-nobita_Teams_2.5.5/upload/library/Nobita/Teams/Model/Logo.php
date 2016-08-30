<?php

class Nobita_Teams_Model_Logo extends Nobita_Teams_Model_Abstract
{
	public function uploadLogo(XenForo_Upload $upload, $teamId)
	{
		if (! $teamId)
		{
			throw new XenForo_Exception('Missing team ID.', true);
		}

		if (!$upload->isValid())
		{
			throw new XenForo_Exception($upload->getErrors(), true);
		}

		if (!$upload->isImage())
		{
			throw new XenForo_Exception(new XenForo_Phrase('uploaded_file_is_not_valid_image'), true);
		};

		$imageType = $upload->getImageInfoField('type');
		if (!in_array($imageType, array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG)))
		{
			throw new XenForo_Exception(new XenForo_Phrase('uploaded_file_is_not_valid_image'), true);
		}

		$baseTempFile = $upload->getTempFile();

		$width = $upload->getImageInfoField('width');
		$height = $upload->getImageInfoField('height');

		if ($width < 280 || $height < 280)
		{
			throw new XenForo_Exception(new XenForo_Phrase('Teams_avatar_required_least_280x280_pixels'), true);
		}

		return $this->applyLogo($teamId, $baseTempFile, $imageType, $width, $height);
	}

	public function applyLogo($teamId, $fileName, $imageType = false, $width = false, $height = false)
	{
		if (!$imageType || !$width || !$height)
		{
			$imageInfo = @getimagesize($fileName);
			if (!$imageInfo)
			{
				throw new XenForo_Exception('Non-image passed in to applyLogo', true);
			}
			$width = $imageInfo[0];
			$height = $imageInfo[1];
			$imageType = $imageInfo[2];
		}

		if (!in_array($imageType, array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG)))
		{
			throw new XenForo_Exception('Invalid image type passed in to applyLogo', true);
		}

		if (!XenForo_Image_Abstract::canResize($width, $height))
		{
			throw new XenForo_Exception(new XenForo_Phrase('uploaded_image_is_too_big'), true);
		}

		$maxFileSize = XenForo_Application::getOptions()->Teams_avatarFileSize;
		if ($maxFileSize && filesize($fileName) > $maxFileSize)
		{
			@unlink($fileName);

			throw new XenForo_Exception(new XenForo_Phrase('your_avatar_file_size_large_smaller_x', array(
				'size' => XenForo_Locale::numberFormat($maxFileSize, 'size')
			)), true);
		}

		// should be use 280x280px because of grid style
		$maxDimensions = 280;

		$imageQuality = intval(Nobita_Teams_Option::get('logoQuality'));
		$outputType = $imageType;

		$image = XenForo_Image_Abstract::createFromFile($fileName, $imageType);
		if (!$image)
		{
			return false;
		}

		$image->thumbnailFixedShorterSide($maxDimensions);

		if ($image->getOrientation() != XenForo_Image_Abstract::ORIENTATION_SQUARE)
		{
			$cropX = floor(($image->getWidth() - $maxDimensions) / 2);
			$cropY = floor(($image->getHeight() - $maxDimensions) / 2);
			$image->crop($cropX, $cropY, $maxDimensions, $maxDimensions);
		}

		$newTempFile = tempnam(XenForo_Helper_File::getTempDir(), 'xf');
		if (!$newTempFile)
		{
			return false;
		}

		$image->output($outputType, $newTempFile, $imageQuality);
		unset($image);

		$filePath = $this->getAvatarFilePath($teamId);
		$directory = dirname($filePath);

		if (XenForo_Helper_File::createDirectory($directory, true) && is_writable($directory))
		{
			if (file_exists($filePath))
			{
				@unlink($filePath);
			}

			$writeSuccess = XenForo_Helper_File::safeRename($newTempFile, $filePath);
			if ($writeSuccess && file_exists($newTempFile))
			{
				@unlink($newTempFile);
			}
		}
		else
		{
			$writeSuccess = false;
		}

		$date = XenForo_Application::$time;
		if ($writeSuccess)
		{
			$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team');
			$dw->setExistingData($teamId);
			$dw->set('team_avatar_date', $date);
			$dw->save();
		}

		return ($writeSuccess) ? $date : 0;
	}

	public function canUploadLogo(array $team, array $category, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		if (!$viewingUser['user_id'])
		{
			return false;
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

		return $this->_getMemberRoleModel()->hasModeratorPermission($memberRecord['member_role_id'], 'manageLogo');
	}

	public function getAvatarFilePath($teamId, $externalDataPath = null)
	{
		if ($externalDataPath === null)
		{
			$externalDataPath = XenForo_Helper_File::getExternalDataPath();
		}

		return sprintf('%s/nobita/teams/avatars/%d/%d.jpg',
			$externalDataPath,
			floor($teamId / 1000),
			$teamId
		);
	}


	public function deleteLogo($teamId, $updateTeam = true)
	{
		$filePath = $this->getAvatarFilePath($teamId);
		@unlink($filePath);

		$dwData = array(
			'team_avatar_date' => 0,
			'team_id' => $teamId
		);

		if ($updateTeam)
		{
			$dw = XenForo_DataWriter::create('Nobita_Teams_DataWriter_Team', XenForo_DataWriter::ERROR_SILENT);
			$dw->setExistingData($teamId);
			$dw->bulkSet($dwData);
			$dw->save();
		}

		return $dwData;
	}
}
