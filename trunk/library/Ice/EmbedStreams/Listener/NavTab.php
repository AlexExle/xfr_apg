<?php

class Ice_EmbedStreams_Listener_NavTab
{
	public static function createTab(array &$extraTabs, $selectedTabId)
	{
		if(XenForo_Application::getOptions()->display_stream_tab){
			$numOnline = $value = XenForo_Model::create('XenForo_Model_DataRegistry')->get('lsonline');
	
			$extraTabs['livestream'] = array(
				'title'		=> (new XenForo_Phrase('ice_livestream_livestreams')) . " ($numOnline)",
				'href'		=> XenForo_Link::buildPublicLink('livestreams'),
				'position'	=> 'middle'
			);
		}
	}
}