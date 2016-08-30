<?php

class Ice_EmbedStreams_Listener_TemplateHook{
		
	public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template){
		if($hookName == 'ice_livestreams_display' && XenForo_Application::getOptions()->stream_hook){
			
			$model = XenForo_Model::create("Ice_EmbedStreams_Model_Streams");
			
			$streams = $model->getOnlineStreams();
			
			$stream_height = XenForo_Application::get('options')->stream_height;
			$stream_width = XenForo_Application::get('options')->stream_width;
		
			$disp_height = $stream_height + 15;
			$disp_width = $stream_width + 20;
			
			$newParams = array(
					"stream_num"=> sizeof($streams),
					"streams" => $streams,
					"stream_height" => $stream_height,
					"stream_width" => $stream_width,
					"disp_width" => $disp_width,
					"disp_height" => $disp_height,
					"full_width" => sizeof($streams) * $disp_width
			);
			
			$template->setParams($newParams);
			$templateNew = $template->create('ice_livestream_forumdisplay', $template->getParams());
			$contents .= $templateNew->render();
		}
		
		if(($hookName == 'forum_list_sidebar' && XenForo_Application::getOptions()->sidebar_streams) || $hookName == 'ice_livestreams_sidebar'){

			$model = XenForo_Model::create("Ice_EmbedStreams_Model_Streams");
			$userModel = XenForo_Model::create("XenForo_Model_User");
			$streams = $model->getOnlineStreams();
		
			foreach($streams as &$stream){
				$user = $userModel->getUserByName($stream['username']);
				$stream['user_id'] = $user['user_id'];
			}
			
			$viewParams = array(
				'streams' => $streams,
				'stream_num' => count($streams)
			);
			
			$template->setParams($viewParams);
			$templateNew = $template->create('ice_livestream_sidebar', $template->getParams());
			$contents .= $templateNew->render();
			
		}
		
	}
	
	
}