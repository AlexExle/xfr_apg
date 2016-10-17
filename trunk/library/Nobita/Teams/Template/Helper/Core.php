<?php

class Nobita_Teams_Template_Helper_Core
{
	protected static $_covers = array();
	private static $_invalidCharacters = '`~!@#$%^&*()_-+={}\\|;:\'",<.>/?';

	public static function helperLogoUrl(array $team, $canonical = false)
	{
		if (empty($team['team_avatar_date']) AND Nobita_Teams_Option::get('enableLogoMaker'))
		{
			$content = static::_autoMakeLogo($team);
			if ($content)
			{
				return $content;
			}
		}

		$url = self::getAvatarUrl($team);

		if ($canonical)
		{
			$url = XenForo_Link::convertUriToAbsoluteUri($url, true);
		}

		return htmlspecialchars($url);
	}

	public static function getCharacterFromString($string, $length = 1)
    {
        if(!utf8_strlen($string)) {
            return '';
        }

        $character = '';

        for($index = 0; $index <= utf8_strlen($string); $index++) {
            $part = utf8_substr($string, $index, $length);

            if(utf8_strpos(self::$_invalidCharacters, $part) === false) {
                $character = $part;
                break;
            }
        }

        return $character;
    }

	protected static function _autoMakeLogo(array $team)
	{
		if(!function_exists('imageftbbox'))
		{
			// Bug reported: http://nobita.me/threads/894/
			return false;
		}

		$shortName = static::getCharacterFromString($team['title'], 2);
		$shortName = utf8_strtoupper($shortName);

		$tempFile = XenForo_Helper_File::getInternalDataPath() . '/groups/logos/' . md5($shortName);

		if (file_exists($tempFile))
		{
			$content = file_get_contents($tempFile);
			return 'data:image/png;base64,' . base64_encode($content);
		}

		$size = 380;

		if(Nobita_Teams_Option::get('logoTextColor'))
		{
			$textColor = Nobita_Teams_Option::get('logoTextColor');
		}
		else
		{
			$textColor = XenForo_Template_Helper_Core::styleProperty('textCtrlBackground');
			$textColor = empty($textColor) ? '#ffffff' : $textColor;
		}

		if(Nobita_Teams_Option::get('logoBgColor'))
		{
			$backgroundColor = Nobita_Teams_Option::get('logoBgColor');
		}
		else
		{
			$backgroundColor = XenForo_Template_Helper_Core::styleProperty('primaryMedium');
			$backgroundColor = empty($backgroundColor) ? '#176093' : $backgroundColor;
		}

		$resource = XenForo_Image_Gd::createImageDirect($size, $size);
		$resource->setBackground($backgroundColor);
		$resource->drawText($shortName, $textColor, null, Nobita_Teams_Option::get('logoFont'));

		$directory = dirname($tempFile);

		if (!XenForo_Helper_File::createDirectory($directory, true) OR !is_writable($directory))
		{
			throw new XenForo_Exception('Info: Could not make the logo path: ' . $tempFile);
			return false;
		}

		$resource->output(IMAGETYPE_PNG, $tempFile, 100);
		$content = file_get_contents($tempFile);

		$encoded = 'data:image/png;base64,' . base64_encode($content);
		return $encoded;
	}

	public static function getAvatarUrl(array $team)
	{
		if (! empty($team['team_id']) && ! empty($team['team_avatar_date']))
		{
			return self::_getCustomAvatarUrl($team);
		}

		return self::_getDefaultAvatarUrl();
	}

	protected static function _getDefaultAvatarUrl()
	{
		return "styles/Nobita/Teams/avatars/avatar_l.jpg";
	}

	/**
	 * Returns the URL to a team's custom avatar
	 *
	 * @param array $team
	 * @param string $size (s,m,l)
	 *
	 * @return string
	 */
	protected static function _getCustomAvatarUrl(array $team)
	{
		$group = floor($team['team_id'] / 1000);

		return XenForo_Application::$externalDataUrl
			. "/nobita/teams/avatars/$group/$team[team_id].jpg?$team[team_avatar_date]";
	}

	public static function helperCoverUrl(array $team, array $category = null, $source = false)
	{
		$default = 'styles/Nobita/Teams/default.png';
		if (Nobita_Teams_Option::get('enableCoverMaker') && empty($team['cover_date']) && ! empty($team['title']))
		{
			if (self::_autoMakeCover($team))
			{
				$default = self::_autoMakeCover($team);
			}
		}

		if (empty($team['team_id']))
		{
			return $default;
		}

		if (empty($team['cover_date']))
		{
			if ($category === null)
			{
				$category = array(
					'team_category_id' => $team['team_category_id'],
					'default_cover_path' => $team['default_cover_path']
				);
			}

			return empty($category['default_cover_path']) ? $default : $category['default_cover_path'];
		}

		return sprintf('%s?t=%d',
			Nobita_Teams_Container::getModel('Nobita_Teams_Model_Cover')->getCoverUrl($team['team_id'], $source),
			$team['cover_date']
		);
	}

	protected static function _autoMakeCover(array $team)
	{
		if(!function_exists('imageftbbox'))
		{
			// Bug reported: http://nobita.me/threads/894/
			return false;
		}

		$tempFile = XenForo_Helper_File::getInternalDataPath() . '/groups/covers/' . md5($team['team_id']);
		if (file_exists($tempFile))
		{
			$content = file_get_contents($tempFile);
			return 'data:image/jpeg;base64,' . base64_encode($content);
		}

		// GOOD. Im replace default cover with beatify cover
		$ratio = 1;
		$coverModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Cover');

		$resource = XenForo_Image_Gd::createImageDirect(
			$coverModel->getDimension('width') * $ratio, $coverModel->getDimension('height') * $ratio
		);

		$directory = dirname($tempFile);
		if (! XenForo_Helper_File::createDirectory($directory) OR !is_writable($directory))
		{
			throw new XenForo_Exception("Info: Could not make cover for path: $tempFile");
			return false;
		}

		if(Nobita_Teams_Option::get('coverBgColor'))
		{
			$backgroundColor = Nobita_Teams_Option::get('coverBgColor');
		}
		else
		{
			$backgroundColor = XenForo_Template_Helper_Core::styleProperty('primaryMedium');
			$backgroundColor = empty($backgroundColor) ? '#176093' : $backgroundColor;
		}

		if(Nobita_Teams_Option::get('coverTextColor'))
		{
			$textColor = Nobita_Teams_Option::get('coverTextColor');
		}
		else
		{
			$textColor = XenForo_Template_Helper_Core::styleProperty('textCtrlBackground');
			$textColor = empty($textColor) ? '#ffffff' : $textColor;
		}

		$resource->setBackground($backgroundColor);
		$resource->drawText($team['title'], $textColor, 50 * $ratio, Nobita_Teams_Option::get('coverFont'));

		$resource->output(IMAGETYPE_PNG, $tempFile, 90);

		$content = file_get_contents($tempFile);
		return 'data:image/jpeg;base64,' . base64_encode($content);
	}

	public static function helperCategoryIcon(array $category)
	{
		if (! $category['icon_date'])
		{
			return '';
		}

		$group = floor($category['team_category_id'] / 1000);
		$iconPath = sprintf('%s/nobita/teams/category_icons/%d/%d.jpg?%d',
			XenForo_Application::$externalDataUrl,
			$group,
			$category['team_category_id'],
			$category['icon_date']
		);

		return '<img src="'. htmlspecialchars($iconPath) .'" alt="'. htmlspecialchars($category['team_category_title']) .'" />';
	}

	public static function helperNumberFormat($number)
	{
		if (!is_numeric($number))
		{
			return '';
		}

		if ($number < 1E3)
		{
			return $number;
		}

		if ($number >= 1E3 && $number < 1E6)
		{
			return round($number / 1000, 1) . 'K';
		}
		elseif ($number >= 1E6)
		{
			return round($number/ 1E6, 1) . 'M';
		}
	}

	public static function routePrefix($full = false)
	{
		$link = Nobita_Teams_Option::get('routePrefix');
		if ($full)
		{
			$link = 'full:'.$link;
		}

		return $link;
	}

	public static function buildGroupLinkType($subLink)
	{
		return Nobita_Teams_Option::get('routePrefix').'/'.$subLink;
	}

	public static function buildGroupRoute($action = null, $data = null)
	{
		$params = func_get_args();
		$fullLink = '';

		self::_determineUserFullLink($action, $fullLink);

		array_shift($params);
		array_shift($params);

		$extraData = $params;

		if (is_array($extraData))
		{
			foreach($extraData as $passData)
			{
				if (is_array($passData))
				{
					$extraParams += $passData;
				}
				else
				{
					$parts = explode('=', $passData, 2);
					if (! empty($parts))
					{
						$extraParams[$parts[0]] = $parts[1];
					}
				}
			}
		}

		$type = empty($fullLink) ? $action : ($fullLink.':'.$action);
		return Nobita_Teams_Link::buildTeamLink($type, $data, (array)$extraParams);
	}

	protected static function _determineUserFullLink(&$action, &$fullLink)
	{
		$parts = explode(':', $action, 2);

		if (count($parts) > 1)
		{
			$fullLink = reset($parts);
		}
		$action = end($parts);
	}

	public static function helperRibbon(array $team)
	{
		if (empty($team['ribbon_display_class']) OR empty($team['ribbon_text']))
		{
			return '';
		}

		$baseSpan = '<span class="' . htmlspecialchars($team['ribbon_display_class']) . '">' . htmlspecialchars($team['ribbon_text']) . '</span>';
		$routePrefix = Nobita_Teams_Option::get('routePrefix');

		$teamId = 0;
		if (! empty($team['ribbon_team_id']))
		{
			$teamId = $team['ribbon_team_id'];
		}
		elseif (! empty($team['team_id']))
		{
			$teamId = $team['team_id'];
		}

		if ($teamId)
		{
			$baseSpan = '<a href="' . $routePrefix . '/' . $teamId . '/">'. $baseSpan .'</a>';
		}

		return $baseSpan;
	}

	public static function helperUserBanner($user, $extraClass = '', $disableStacking = false)
	{
		$response = call_user_func_array($GLOBALS['XenForoHelperUserBanner'], func_get_args());

		$response .= "\n" . static::helperRibbon($user);

		return $response;
	}

}
