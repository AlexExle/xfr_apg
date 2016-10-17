<?php
class Ice_EmbedStreams_ControllerPublic_Index extends XenForo_ControllerPublic_Abstract{
	
	public function actionIndex()
	{
		
		$model = $this->getModelFromCache("Ice_EmbedStreams_Model_Streams");
			
		$streams = $model->getOnlineStreams();
			
		$stream_height = XenForo_Application::get('options')->stream_height_large;
		$stream_width = XenForo_Application::get('options')->stream_width_large;
		
		$disp_height = $stream_height + 15;
		$disp_width = $stream_width + 20;
			
		$finalStreams = array();
			
		foreach($streams as $stream){
			if($stream['live']==1){
				$finalStreams[] = $stream;
			}
		}
		
		$viewParams = array(
				"stream_num"=> sizeof($finalStreams),
				"streams" => $finalStreams,
				"stream_height" => $stream_height,
				"stream_width" => $stream_width,
				"disp_width" => $disp_width,
				"disp_height" => $disp_height,
				"full_width" => sizeof($finalStreams) * $disp_width
		);
		
		return $this->responseView('Ice_LiveStreams_ViewPublic_Streams', 'ice_livestream_pagedisplay', $viewParams);			
	}
	
}