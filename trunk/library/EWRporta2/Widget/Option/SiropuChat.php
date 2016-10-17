<?php

class EWRporta2_Widget_Option_SiropuChat
{
	public static function renderRoomSelect(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$preparedOption['formatParams'] = self::getRoomOptions($preparedOption['option_value']);

		return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal(
			'option_list_option_select', $view, $fieldPrefix, $preparedOption, $canEdit
		);
	}
	
	public static function getRoomOptions($selectedRoom)
	{
		$roomsModel = XenForo_Model::create('Siropu_Chat_Model');

		$rooms = $roomsModel->getAllRooms();
		$options = array();
		
		foreach ($rooms AS $room)
		{
			$options[] = array(
				'label' => $room['room_name'],
				'value' => $room['room_id'],
				'selected' => ($selectedRoom == $room['room_id'])
			);
		}

		return $options;
	}
}