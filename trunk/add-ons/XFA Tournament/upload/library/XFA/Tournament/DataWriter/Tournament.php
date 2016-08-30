<?php
 /*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
 * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/

class XFA_Tournament_DataWriter_Tournament extends XenForo_DataWriter
{
	const OPTION_DELETE_THREAD_TITLE_TEMPLATE   = 'deleteThreadTitleTemplate';
	const OPTION_DELETE_THREAD_ACTION           = 'deleteThreadAction';
	const OPTION_DELETE_ADD_POST                = 'deleteAddPost';   
	const DATA_THREAD_WATCH_DEFAULT             = 'watchDefault'; 
    
    protected function _getFields() {
		return array(
			'xfa_tourn_tournament' => array(
				'tournament_id'             => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'tournament_category_id'    => array('type' => self::TYPE_UINT, 'default' => 0),
				'type'                      => array('type' => self::TYPE_STRING, 'default' => 'single_el', 'allowedValues' => array('single_el', 'double_el', 'round_robin')),
				'third_place'               => array('type' => self::TYPE_UINT, 'default' => 0),
				'title'                     => array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 100,
					'requiredError' => 'please_enter_valid_title'
				),
				'private'                   => array('type' => self::TYPE_UINT, 'default' => 0),
				'automatic_generation'      => array('type' => self::TYPE_UINT, 'default' => 0),
				'description'               => array('type' => self::TYPE_STRING, 'default' => ''),
				'user_id'                   => array('type' => self::TYPE_UINT, 'required' => true),
				'username'                  => array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 50,
					'requiredError' => 'please_enter_valid_name'
				),
				'slots'                     => array('type' => self::TYPE_UINT, 'default' => 1),
				'user_count'                => array('type' => self::TYPE_UINT_FORCED, 'default' => 0),
				'creation_date'             => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
				'end_date'                  => array('type' => self::TYPE_UINT, 'default' => 0),
				'last_update'               => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
				'invites'                   => array('type' => self::TYPE_STRING, 'default' => ''),
				'winner_id'                 => array('type' => self::TYPE_UINT, 'default' => 0),
				'winner_username'           => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 50),
				'discussion_thread_id'      => array('type' => self::TYPE_UINT, 'default' => 0),
				'rules'                     => array('type' => self::TYPE_STRING, 'default' => ''),
				'team_mode'                     => array('type' => self::TYPE_UINT, 'default' => '')
			)
		);
    }
        
    protected function _getExistingData($data)
    {
        if (!$id = $this->_getExistingPrimaryKey($data, 'tournament_id'))
        {
            return false;
        }
     
        return array('xfa_tourn_tournament' => $this->_getTournamentModel()->getTournamentById($id));
    }    
    
    protected function _getUpdateCondition($tableName)
    {
        return 'tournament_id = ' . $this->_db->quote($this->getExisting('tournament_id'));
    }
        
    protected function _getTournamentModel()
    {
        return $this->getModelFromCache('XFA_Tournament_Model_Tournament');
	}   
	
	protected function _getDefaultOptions()
	{
		$options = XenForo_Application::getOptions();

		return array(
			self::OPTION_DELETE_THREAD_ACTION           => $options->get('xfa_tourn_tournamentRemovalThreadAction', 'action'),
			self::OPTION_DELETE_THREAD_TITLE_TEMPLATE   => $options->get('xfa_tourn_tournamentRemovalThreadAction', 'update_title') ? $options->get('xfa_tourn_tournamentRemovalThreadAction', 'title_template') : '',
			self::OPTION_DELETE_ADD_POST                => $options->get('xfa_tourn_tournamentRemovalThreadAction', 'add_post')
		);
	}
	
	protected function _postSave()
	{	
    	/* Update category */
    	if($catDw = $this->_getCategoryDwForUpdate())
    	{
            $catDw->tournamentUpdate($this);
            $catDw->save(); 	
            
            /* Create thread on insert */
            if ($this->isInsert())
            {
    			$nodeId     = $catDw->get('thread_node_id');
    			$prefixId   = $catDw->get('thread_prefix_id');
    
    			$threadId = $this->_insertDiscussionThread($nodeId, $prefixId);
    			if ($threadId)
    			{
    				$this->set('discussion_thread_id',
    					$threadId, '', array('setAfterPreSave' => true)
    				);

        			$this->_db->update('xfa_tourn_tournament', array('discussion_thread_id' => $threadId),
        				'tournament_id = ' .  $this->_db->quote($this->get('tournament_id'))
        			);
    			}                    
            }            
        }
        
        /* Handle win count */
		if ($this->isUpdate() && $this->isChanged('winner_id'))
        {
           /* /* Decrement for existing winner if any
            if ($this->getExisting('winner_id'))
            {
    			$this->_db->query('
    				UPDATE xf_user
    				SET xfa_tourn_wins = IF(xfa_tourn_wins > 0, xfa_tourn_wins - 1, 0)
    				WHERE user_id = ?
    			', $this->getExisting('winner_id'));     
            }
            
    		/* Increment wins count 
    		if ($this->get('winner_id'))
    		{
    			$this->_db->query('
    				UPDATE xf_user
    				SET xfa_tourn_wins = xfa_tourn_wins + 1
    				WHERE user_id = ?
    			', $this->get('winner_id'));
            }*/
            
            /* Post message if thread */
            if ($this->get('discussion_thread_id'))
            {
    			$postWriter = XenForo_DataWriter::create('XenForo_DataWriter_DiscussionMessage_Post', XenForo_DataWriter::ERROR_SILENT);

				$team_mode = $this->get("team_mode");

				if ($team_mode == 1)
				{
					$teamModel = Nobita_Teams_Container::getModel('Nobita_Teams_Model_Team');
					$winner = $teamModel->getTeamById($this->get('winner_id'));
					$message = new XenForo_Phrase('xfa_tourn_message_x_won_tournament', array(
						'title' => $this->get('title'),
						'username' => $winner['title'],
						'userLink' => Nobita_Teams_Link::buildTeamLink('canonical:', $winner),
						'link' => XenForo_Link::buildPublicLink('canonical:tournaments', $this->getMergedData())
					), false);

					$postWriter->set('thread_id', $this->get('discussion_thread_id'));
					$postWriter->set('message', $message->render());
					$postWriter->set('user_id', $this->get('user_id'));
					$postWriter->set('username', $this->get('username'));
					$postWriter->setOption(XenForo_DataWriter_DiscussionMessage::OPTION_IS_AUTOMATED, true);
					$postWriter->setOption(XenForo_DataWriter_DiscussionMessage::OPTION_PUBLISH_FEED, false);
					$postWriter->save();
				}
                else {
					$winner = $this->getModelFromCache('XenForo_Model_User')->getUserById($this->get('winner_id'));

					$message = new XenForo_Phrase('xfa_tourn_message_x_won_tournament', array(
						'title' => $this->get('title'),
						'username' => $winner['username'],
						'userLink' => XenForo_Link::buildPublicLink('canonical:members', $winner),
						'link' => XenForo_Link::buildPublicLink('canonical:tournaments', $this->getMergedData())
					), false);

					$postWriter->set('thread_id', $this->get('discussion_thread_id'));
					$postWriter->set('message', $message->render());
					$postWriter->set('user_id', $this->get('user_id'));
					$postWriter->set('username', $this->get('username'));
					$postWriter->setOption(XenForo_DataWriter_DiscussionMessage::OPTION_IS_AUTOMATED, true);
					$postWriter->setOption(XenForo_DataWriter_DiscussionMessage::OPTION_PUBLISH_FEED, false);
					$postWriter->save();
				}
            }	            
        }
        
		/* Handle thread title update upon title change */
		if ($this->isUpdate() && $this->isChanged('title'))
		{
			$threadDw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread', XenForo_DataWriter::ERROR_SILENT);
			if ($threadDw->setExistingData($this->get('discussion_thread_id')) && $threadDw->get('discussion_type') == 'tournament')
			{
    			/* Get effective thread title */
				$threadTitle = $this->_stripTemplateComponents($threadDw->get('title'), $this->getOption(self::OPTION_DELETE_THREAD_TITLE_TEMPLATE));

                /* Only change it if not edited since the original title based on the tournament (getExiting) */
				if ($threadTitle == $this->getExisting('title'))
				{
					$threadDw->set('title', $this->_getThreadTitle());
					$threadDw->save();
				}
			}
		}
		
		/* Upon thread move upon category change */
		$catDw = $this->_getCategoryDwForUpdate();
				
		if ($this->isUpdate() && $this->isChanged('document_category_id') && $this->get('discussion_thread_id'))
		{
			$nodeId     = $catDw->get('thread_node_id');
			$prefixId   = $catDw->get('thread_prefix_id');

			$threadDw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread', XenForo_DataWriter::ERROR_SILENT);
			if ($threadDw->setExistingData($this->get('discussion_thread_id')) && $threadDw->get('discussion_type') == 'tournament')
			{
				if ($nodeId)
				{
					$threadDw->set('node_id',   $nodeId);
					$threadDw->set('prefix_id', $prefixId);
					if ($threadDw->get('discussion_state') == 'deleted')
					{
						$threadDw->set('discussion_state', 'visible');
					}
				}
				else
				{
					$threadDw->set('discussion_state', 'deleted');
				}
				$threadDw->save();
			}
		}  
		
		/* Alert */
		if ($this->isInsert())
		{
            $visitor = XenForo_Visitor::getInstance();
        
            $users = $this->_getTournamentModel()->getUsersWithNewAlert();
            
            foreach($users AS $user)
            {
                if ($user['user_id'] != $this->get('user_id') && XenForo_Model_Alert::userReceivesAlert($user, 'xfa_tourn', 'new'))
                {
            		XenForo_Model_Alert::alert(
            			$user['user_id'],
            			$visitor['user_id'],
            			$visitor['username'],
            			'xfa_tourn',
            			$this->get('tournament_id'),
            			'new'
            		);
                }
            }     		
		}      
    }	
    
	protected function _postDelete()
	{	        
        /* Decrement wins count */
        if ($this->get('winner_id'))
        {
			$this->_db->query('
				UPDATE xf_user
				SET xfa_tourn_wins = IF(xfa_tourn_wins > 0, xfa_tourn_wins - 1, 0)
				WHERE user_id = ?
			', $this->get('winner_id'));        
        }
        
    	/* If a thread exist, perform tournament deletion thread action */
		if ($this->get('discussion_thread_id'))
		{
			$threadDw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread', XenForo_DataWriter::ERROR_SILENT);
			if ($threadDw->setExistingData($this->get('discussion_thread_id')) && $threadDw->get('discussion_type') == 'tournament')
			{
    			/* Select deletion action as configured */
				switch ($this->getOption(self::OPTION_DELETE_THREAD_ACTION))
				{
    				/* If deletion action set to delete, change state to deleted */
					case 'delete':
						$threadDw->set('discussion_state', 'deleted');
						break;
                    /* If deletion action set to close, close the thread */
					case 'close':
						$threadDw->set('discussion_open', 0);
						break;
				}

                /* Change title if required */
				if ($this->getOption(self::OPTION_DELETE_THREAD_TITLE_TEMPLATE))
				{
					$threadTitle = str_replace(
						'{title}', $threadDw->get('title'),
						$this->getOption(self::OPTION_DELETE_THREAD_TITLE_TEMPLATE)
					);
					$threadDw->set('title', $threadTitle);
				}

				$threadDw->save();

                /* Post a message if required */
				if ($this->getOption(self::OPTION_DELETE_ADD_POST))
				{
					$forum = $this->getModelFromCache('XenForo_Model_Forum')->getForumById($threadDw->get('node_id'));
					if ($forum)
					{
						$messageState = $this->getModelFromCache('XenForo_Model_Post')->getPostInsertMessageState(
							$threadDw->getMergedData(), $forum
						);
					}
					else
					{
						$messageState = 'visible';
					}

					$user = $this->_getUserModel()->getUserById($this->get('user_id'));
					if ($user)
					{
						$this->set('username', $user['username'], '', array('setAfterPreSave' => true));
					}

					$message = new XenForo_Phrase('xfa_tourn_message_delete_tournament');

					$writer = XenForo_DataWriter::create('XenForo_DataWriter_DiscussionMessage_Post');
					$writer->setOption(XenForo_DataWriter_DiscussionMessage::OPTION_IS_AUTOMATED, true);
					$writer->bulkSet(array(
						'user_id'       => $this->get('user_id'),
						'username'      => $this->get('username'),
						'message_state' => $messageState,
						'thread_id'     => $threadDw->get('thread_id'),
						'message'       => strval($message)
					));
					$writer->save();
				}
			}
		}       
		
    	if($catDw = $this->_getCategoryDwForUpdate())
    	{
            $catDw->tournamentRemoved($this);
            $catDw->save(); 	
        } 
    }	
    
	protected function _getCategoryDwForUpdate()
	{
		$dw = XenForo_DataWriter::create('XFA_Tournament_DataWriter_Category', XenForo_DataWriter::ERROR_SILENT);
		if ($dw->setExistingData($this->get('tournament_category_id')))
		{
			return $dw;
		}
		else
		{
			return false;
		}
	}
	
	public function updateUserCount($adjust = null)
	{
		if ($adjust === null)
		{
			$this->set('user_count', $this->_db->fetchOne("
				SELECT COUNT(*)
				FROM xfa_tourn_participant
				WHERE participant_id = ?
			", $this->get('tournament_id')));
		}
		else
		{
			$this->set('user_count', $this->get('user_count') + $adjust);
		}
	}

	public function rebuildCounters()
	{
		$this->updateUserCount();
	}	
	
	protected function _getThreadTitle()
	{
		$title = $this->get('title');

		$title = XenForo_Helper_String::wholeWordTrim($title, 100);

		return $title;
	}	
	
	protected function _stripTemplateComponents($string, $template)
	{
		if (!$template) {
			return $string;
		}

		$template = str_replace('\{title\}', '(.*)', preg_quote($template, '/'));

		if (preg_match('/^' . $template . '$/', $string, $match)) {
			return $match[1];
		}

		return $string;
	}	
	
	protected function _insertDiscussionThread($nodeId, $prefixId = 0)
	{
    	/* Check node id is an effective forum */
		if (!$nodeId)
		{
			return false;
		}

		$forum = $this->getModelFromCache('XenForo_Model_Forum')->getForumById($nodeId);
		if (!$forum)
		{
			return false;
		}

        /* Create and initialize the datawriter */
		$threadDw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread', XenForo_DataWriter::ERROR_SILENT);
		$threadDw->setExtraData(XenForo_DataWriter_Discussion_Thread::DATA_FORUM, $forum);
		$threadDw->bulkSet(array(
			'node_id'           => $nodeId,
			'title'             => $this->_getThreadTitle(),
			'user_id'           => $this->get('user_id'),
			'username'          => $this->get('username'),
			'discussion_type'   => 'tournament',
			'prefix_id'         => $prefixId
		));
		$threadDw->set('discussion_state', $this->getModelFromCache('XenForo_Model_Post')->getPostInsertMessageState(array(), $forum));
		$threadDw->setOption(XenForo_DataWriter_Discussion::OPTION_PUBLISH_FEED, false);

        /* Get the description as message text, snippet it and strip the bbcode from the snippet */
		$messageText    = $this->get('description');
		$parser         = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('XenForo_BbCode_Formatter_BbCode_AutoLink', false));
		$snippet        = $parser->render(XenForo_Helper_String::wholeWordTrim($messageText, 500));

		$message = new XenForo_Phrase('xfa_tourn_message_create_tournament', array(
			'title'     => $this->get('title'),
			'username'  => $this->get('username'),
			'userId'    => $this->get('user_id'),
			'snippet'   => $snippet,
			'link'      => XenForo_Link::buildPublicLink('canonical:tournaments', $this->getMergedData())
		), false);

        /* Create the first message */
		$postWriter = $threadDw->getFirstMessageDw();
		$postWriter->set('message', $message->render());
		$postWriter->setExtraData(XenForo_DataWriter_DiscussionMessage_Post::DATA_FORUM, $forum);
		$postWriter->setOption(XenForo_DataWriter_DiscussionMessage::OPTION_IS_AUTOMATED, true);
		$postWriter->setOption(XenForo_DataWriter_DiscussionMessage::OPTION_PUBLISH_FEED, false);

        /* Save the thread alon with the message */
		if (!$threadDw->save())
		{
			return false;
		}

        /* Update the thread id in the document and mark it as read and auto-set it as watched if set by default */
		$this->set('discussion_thread_id',
			$threadDw->get('thread_id'), '', array('setAfterPreSave' => true)
		);
		$postSaveChanges['discussion_thread_id'] = $threadDw->get('thread_id');

		$this->getModelFromCache('XenForo_Model_Thread')->markThreadRead(
			$threadDw->getMergedData(), $forum, XenForo_Application::$time
		);

		$this->getModelFromCache('XenForo_Model_ThreadWatch')->setThreadWatchStateWithUserDefault(
			$this->get('user_id'), $threadDw->get('thread_id'),
			$this->getExtraData(self::DATA_THREAD_WATCH_DEFAULT)
		);

		return $threadDw->get('thread_id');
	}			
}