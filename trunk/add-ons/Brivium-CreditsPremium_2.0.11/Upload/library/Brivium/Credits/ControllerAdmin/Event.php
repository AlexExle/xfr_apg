<?php

class Brivium_Credits_ControllerAdmin_Event extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('BRC_action');
	}

	public function actionIndex()
	{
		$eventModel = $this->_getEventModel();
		$fetchOptions = array();

		$currencyOptions = $this->_getCurrencyModel()->getCurrencyOptionsArray();

		$currencyId = $this->_input->filterSingle('currency_id', XenForo_Input::UINT);
		$currency = !empty($currencyOptions[$currencyId])?$currencyOptions[$currencyId]:array();
		if (!$currency)
		{
			$currency = reset($currencyOptions);
			if($currency){
				$currencyId = $currency['value'];
			}
		}
		$events = $eventModel->getEventsInCurrency($currencyId, $fetchOptions);
		$events = $eventModel->prepareEvents($events);

		$orders = array();
		foreach ($events as $eventId => $event)
		{
		    $orders[$eventId] = $event['display_order'];
		}
		array_multisort($orders, SORT_ASC, $events);

		$viewParams = array(
			'events' => $events,
			'currencyId' => $currencyId,
			'currency' => $currency,
			'currencyOptions' => $currencyOptions
		);
		return $this->responseView('Brivium_Credits_ViewAdmin_Events_Lists', 'BRC_event_list', $viewParams);
	}

	public function actionAdd()
	{
		$actionId = $this->_input->filterSingle('action_id', XenForo_Input::STRING);
		if ($actionId)
		{
			return $this->responseReroute('Brivium_Credits_ControllerAdmin_Event', 'edit');
		}
		$actionObj = XenForo_Application::get('brcActionHandler');
		$actions = $actionObj->getActions();
		$viewParams = array(
			'actions' => $actions,
		);

		return $this->responseView('Brivium_Credits_ViewAdmin_Events_Add','BRC_event_add', $viewParams);
	}

	public function actionEdit()
	{
		$eventModel = $this->_getEventModel();
		$actionModel = $this->_getActionModel();

		$actionObj = XenForo_Application::get('brcActionHandler');

		if ($eventId = $this->_input->filterSingle('event_id', XenForo_Input::UINT))
		{
			$event = $eventModel->getEventById($eventId);
			$actionId = $event['action_id'];
			$handler = $actionObj->getActionHandler($actionId);
			if(!$handler){
				$this->responseError(new XenForo_Phrase('BRC_requested_action_not_found'));
			}
		}
		else
		{
			if (!$actionId = $this->_input->filterSingle('action_id', XenForo_Input::STRING))
			{
				return $this->responseReroute(__CLASS__, 'add');
			}

			$handler = $actionObj->getActionHandler($actionId);
			if(!$handler){
				$this->responseError(new XenForo_Phrase('BRC_requested_action_not_found'));
			}
			$event = $handler->getDefaultEvent();
		}


		if(!$handler){
			$this->responseError(new XenForo_Phrase('BRC_requested_action_not_found'));
		}

		$event =  $handler->prepareEvent($event);
		$action = $actionObj->$actionId;

		$viewParams = array(
			'event' => $event,
			'action' => $action,
			'currencyOptions' => $this->_getCurrencyModel()->getCurrencyOptionsArray($event['currency_id'])
		);

		$viewParams =  $handler->prepareEventEditParams($event, $viewParams);

		$editTemplate = !empty($action['edit_template'])?$action['edit_template']:'BRC_action_edit_default';
		return $this->responseView('Brivium_Credits_ViewAdmin_Events_Edit',$editTemplate, $viewParams);
	}

	public function actionSave()
	{
		$this->_assertPostOnly();
		$eventId = $this->_input->filterSingle('event_id', XenForo_Input::UINT);

		$dwInput = $this->_input->filter(array(
			'action_id' => XenForo_Input::STRING,

			'amount' 			=> XenForo_Input::FLOAT,
			'sub_amount' 		=> XenForo_Input::FLOAT,
			'multiplier' 		=> XenForo_Input::FLOAT,
			'sub_multiplier' 	=> XenForo_Input::FLOAT,


			'currency_id' 		=> XenForo_Input::UINT,

			'active' 			=> XenForo_Input::UINT,
			'alert' 			=> XenForo_Input::UINT,
			//'display_order' => XenForo_Input::UINT,


			'moderate'			=> XenForo_Input::UINT,
			'times' 			=> XenForo_Input::UINT,
			'max_time' 			=> XenForo_Input::UINT,

			'apply_max' 		=> XenForo_Input::UINT,

			'extra_min' 		=> XenForo_Input::FLOAT,
			'extra_max' 		=> XenForo_Input::FLOAT,
			'extra_min_handle' 	=> XenForo_Input::UINT,

			'target' 			=> XenForo_Input::STRING,

			'allow_negative' 	=> XenForo_Input::UINT,
			'negative_handle' 	=> XenForo_Input::STRING,

			'extra_data' 		=> XenForo_Input::ARRAY_SIMPLE,
		));

		$dwInput['forums'] = $this->_input->filterSingle('forums', XenForo_Input::ARRAY_SIMPLE);
		$dwInput['user_groups'] = $this->_input->filterSingle('user_groups', XenForo_Input::ARRAY_SIMPLE);

		if(!$dwInput['target'])$dwInput['target'] = 'user';
		$phrase = $this->_input->filter(array(
			'title' => XenForo_Input::STRING,
			'reverted_title' => XenForo_Input::STRING,
			'explain' => XenForo_Input::STRING
		));
		$writer = XenForo_DataWriter::create('Brivium_Credits_DataWriter_Event');
		if ($eventId)
		{
			$writer->setExistingData($eventId);
		}
		$writer->bulkSet($dwInput);
		$writer->save();

		$eventId = $writer->get('event_id');
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('brc-events', null, array('currency_id'=>$dwInput['currency_id'])) . $this->getLastHash($eventId)
		);
	}

	public function actionDelete()
	{
		$eventModel = $this->_getEventModel();
		$actionModel = $this->_getActionModel();
		$eventId = $this->_input->filterSingle('event_id', XenForo_Input::STRING);
		$event = $eventModel->getEventById($eventId);
		if(!$event){
			$this->responseError(new XenForo_Phrase('BRC_requested_event_not_found'));
		}
		if ($this->isConfirmedPost())
		{
			$dw = XenForo_DataWriter::create('Brivium_Credits_DataWriter_Event');
			$dw->setExistingData($eventId);
			$dw->delete();
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('brc-events', null, array('currency_id'=>$event['currency_id']))
			);
		}
		else // show confirmation dialog
		{
			$actionId = $event['action_id'];
			$action = XenForo_Application::get('brcActionHandler')->$actionId;

			$event['title'] =	!empty($action['title'])?$action['title']:'';
			$event['explain'] = !empty($action['explain'])?$action['explain']:'';


			$viewParams = array(
				'event' => $event
			);

			return $this->responseView('Brivium_Credits_ViewAdmin_Credits_DeleteEvent', 'BRC_event_delete', $viewParams);
		}
	}

	public function actionSaveConfig()
	{
		$events = $this->_input->filterSingle('events', XenForo_Input::ARRAY_SIMPLE);
		foreach($events AS $eventId=>$event){
			$dw = XenForo_DataWriter::create('Brivium_Credits_DataWriter_Event');
			$dw->setExistingData($eventId);
			$dw->setOption(Brivium_Credits_DataWriter_Event::OPTION_REBUILD_CACHE, false);

			if(empty($event['active']))$event['active']=0;
			if(empty($event['alert']))$event['alert']=0;
			if(empty($event['moderate']))$event['moderate']=0;

			$dw->set('amount',$event['amount']);
			$dw->set('active',$event['active']);
			$dw->set('alert',$event['alert']);
			$dw->set('moderate',$event['moderate']);
			if($dw->hasChanges()){
				$dw->save();
			}
		}
		$this->_getEventModel()->rebuildEventCache();
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
			XenForo_Link::buildAdminLink('brc-events')
		);
	}

	public function actionExport()
	{
		$addOnId = $this->_input->filterSingle('addon_id', XenForo_Input::STRING);
		if ($this->isConfirmedPost() && $currencyId = $this->_input->filterSingle('currency_id', XenForo_Input::UINT))
		{
			$this->_routeMatch->setResponseType('xml');
			$viewParams = array(
				'currencyId' => $currencyId,
				'xml' => $this->_getEventModel()->getEventXml($currencyId, $addOnId)
			);

			return $this->responseView('Brivium_Credits_ViewAdmin_Events_ExportXml', '', $viewParams);
		}
		else
		{
			$addOnModel = $this->_getAddOnModel();
			$viewParams = array(
				'currencyOptions' => $this->_getCurrencyModel()->getCurrencyOptionsArray(),
				'addOnOptions' => $addOnModel->getAddOnOptionsListIfAvailable(),
			);

			return $this->responseView('Brivium_Credits_ViewAdmin_Event_Export', 'BRC_event_export', $viewParams);
		}
	}

	public function actionImport()
	{
		$eventModel = $this->_getEventModel();

		if ($this->isConfirmedPost() && $currencyId = $this->_input->filterSingle('currency_id', XenForo_Input::UINT))
		{
			$upload = XenForo_Upload::getUploadedFile('upload');
			if (!$upload)
			{
				return $this->responseError(new XenForo_Phrase('please_upload_valid_language_xml_file'));
			}

			$document = $this->getHelper('Xml')->getXmlFromFile($upload);
			$caches = $eventModel->importEventsCurrencyXml($document, $currencyId);
			return $this->responseMessage(new XenForo_Phrase('BRC_import_successfully'));
		}
		else
		{
			$viewParams = array(
				'currencyOptions' => $this->_getCurrencyModel()->getCurrencyOptionsArray()
			);
			return $this->responseView('Brivium_Credits_ViewAdmin_Event_Import', 'BRC_event_import',$viewParams);
		}
	}

	protected function _getEditEventTemplate()
	{
		$allEventTemplates = $this->_getAdminTemplateModel()->getAdminTemplatesForAdminQuickSearch('BRC_event_edit_template_');
		$templates = array();
		$templates[] = 'BRC_event_edit';
		foreach($allEventTemplates AS $key=>&$template){
			if($pos = strpos($template['title'], 'BRC_event_edit_template_')!= 1){
				$templateName = substr($template['title'] , ($pos+24));
				$templates[$templateName] = $template['title'];
			}
		}
		return $templates;
	}

	/**
	 * Gets the currency model.
	 *
	 * @return Brivium_Credits_Model_Currency
	 */
	protected function _getCurrencyModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Currency');
	}


	/**
	 * Gets the action model.
	 *
	 * @return Brivium_Credits_Model_Action
	 */
	protected function _getActionModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Action');
	}
	/**
	 * Gets the event model.
	 *
	 * @return Brivium_Credits_Model_Event
	 */
	protected function _getEventModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Event');
	}
	/**
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}

	/**
	 * Gets the event model.
	 *
	 * @return Brivium_Credits_Model_Credit
	 */
	protected function _getCreditModel()
	{
		return $this->getModelFromCache('Brivium_Credits_Model_Credit');
	}

	/**
	 * @return XenForo_Model_AddOn
	 */
	protected function _getAddOnModel()
	{
		return $this->getModelFromCache('XenForo_Model_AddOn');
	}
}