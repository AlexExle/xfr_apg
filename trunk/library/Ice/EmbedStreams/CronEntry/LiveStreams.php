<?php
class Ice_EmbedStreams_CronEntry_LiveStreams {
	
	public static function updateLiveStreams(){
		XenForo_Model::create('Ice_EmbedStreams_Model_Streams')->cronStreamsUpdate();
	}
	
}