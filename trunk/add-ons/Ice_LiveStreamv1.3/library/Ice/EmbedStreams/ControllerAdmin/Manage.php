<?php
class Ice_EmbedStreams_ControllerAdmin_Manage extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('manageStreams');
	}

	public function actionIndex()
	{		
		$viewParams = array(
			'streams' => $this->_getStreamModel()->getStreams()
		);
		
		return $this->responseView('XenForo_ViewAdmin_Streams_Index', 'ice_livestreams_index', $viewParams);
	}
	
	public function actionCreate(){
		return $this->_getCreateResponse();
	}
	
	public function actionEdit(){
		$streamId = $this->_input->filterSingle('stream_id', XenForo_Input::UINT);
		$stream = $this->_getStreamOrError($streamId);
		return $this->_getEditResponse($stream);
	}
	
	public function actionSave(){
		
		$stream_id = $this->_input->filterSingle('stream_id', XenForo_Input::UINT);
		print_r($this->_input->getInput());
		$this->_assertPostOnly();
		$dwIn = $this->_input->filter(array(
			'username' => XenForo_Input::STRING,
			'stream_username' => XenForo_Input::STRING,
			'display_order' => XenForo_Input::UINT,
			'stream_type' => XenForo_Input::UINT
		));
	
		$dw = XenForo_DataWriter::create('Ice_EmbedStreams_DataWriter_Stream');
		
		if ($this->_getStreamModel()->getStream($stream_id)){
			$dw->setExistingData($stream_id);
		}
		
		$dw->set('stream_id', $stream_id);
		$dw->bulkSet($dwIn);
		$dw->save();
		
		return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('livestreams')
		);
		
	}
	
	public function actionDelete(){
		
		if($this->isConfirmedPost()){
			return $this->_deleteData('Ice_EmbedStreams_DataWriter_Stream',
					 'stream_id', XenForo_Link::buildAdminLink('livestreams'));
		}else{
			
			$stream_id = $this->_input->filterSingle('stream_id', XenForo_Input::UINT);
			$stream = $this->_getStreamOrError($stream_id);
			
			$viewParams = array(
				'stream'=>$stream
			);
			
			return $this->responseView('XenForo_ViewAdmin_EmbedStreams_Stream_Delete', 'ice_livestreams_delete', $viewParams);
		
		}
		
	}
	
	
	protected function _getCreateResponse(){	
		$viewParams = array(
			'title' => 'create',
			'streamOptions' => array("Justin.tv/Twitch.tv", "Own3d.tv")
 		);
		
		return $this->responseView('XenForo_ViewAdmin_Streams_Edit', 'ice_livestreams_editstream', $viewParams);
	}
	
	protected function _getEditResponse($stream){
		$viewParams = array(
				'stream' => $stream,
				'streamOptions' => array("Justin.tv/Twitch.tv", "Own3d.tv")
		);
		
		return $this->responseView('XenForo_ViewAdmin_Streams_Edit', 'ice_livestreams_editstream', $viewParams);
	}
	
	protected function _getStreamModel(){
		return 	$this->getModelFromCache('Ice_EmbedStreams_Model_Streams');
	}
	
	
	protected function _getStreamOrError($streamId)
	{
		$stream = $this->_getStreamModel()->getStream($streamId);
	
		if (!$stream)
		{
			throw $this->responseException($this->responseError("Stream not found", 404));
		}
	
		return $stream;
	}
	
}