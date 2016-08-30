<?php
class Ice_EmbedStreams_Model_Streams extends Xenforo_Model
{
	
	public function getStreams(){
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_ice_livestreams
			ORDER BY display_order');
	}
	
	public function getStream($streamId){
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_ice_livestreams
			WHERE stream_id = ?
		', $streamId);
	}
	
	public function getOnlineStreams(){
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_ice_livestreams
			WHERE live = 1
			ORDER BY display_order');
	}
	
	public function cronStreamsUpdate(){

		$streams = $this->getStreams();
		$online = 0;
		
		foreach($streams as $stream){
		
			switch($stream['stream_type']){
				case 0:

					//$url = 'http://api.justin.tv/api/stream/list.json?channel='.$stream['stream_username'];

					$arrContextOptions=array(
						"ssl"=>array(
							"verify_peer"=>false,
							"verify_peer_name"=>false,
						),
					);
					$url = 'https://api.twitch.tv/kraken/streams/'.$stream['stream_username'];
					$content = file_get_contents($url, false, stream_context_create($arrContextOptions));
					$json = json_decode($content);

						if(!is_null($json->{'stream'})){
							$this->_getDb()->update("xf_ice_livestreams", array('live'=>1), "`stream_id`=".$stream['stream_id']);
							$online++;
						}
					else{
						$this->_getDb()->update("xf_ice_livestreams", array('live'=>0), "`stream_id`=".$stream['stream_id']);
					}
					break;
				case 1:
					$own3d = "http://api.own3d.tv/liveCheck.php?live_id=" . $stream['stream_username'];
					$xml = simplexml_load_file($own3d);
					$xmlIsLive = $xml->xpath("/own3dReply/liveEvent/isLive");					
					if ($xmlIsLive[0] == 'true'){
						$this->_getDb()->update("xf_ice_livestreams", array('live'=>1), "`stream_id`=".$stream['stream_id']);
						$online++;
					}else{
						$this->_getDb()->update("xf_ice_livestreams", array('live'=>0), "`stream_id`=".$stream['stream_id']);
					}
					break;
				case 2:
					
					break;
			}
			
		
		}		
		
		XenForo_Model::create('XenForo_Model_DataRegistry')->set('lsonline', $online);
		
		
	}
	
}