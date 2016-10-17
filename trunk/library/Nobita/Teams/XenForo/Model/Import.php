<?php

class Nobita_Teams_XenForo_Model_Import extends XFCP_Nobita_Teams_XenForo_Model_Import
{
	public function group_importCategory($oldId, array $import, $writerName, $importMode = true)
	{
		$newId = $this->group_importGroup($oldId, $import, $writerName, 'team_category_id', $importMode);

        Nobita_Teams_Container::getModel('Nobita_Teams_Model_Category')->rebuildCategoryStructure();

		return $newId;
	}

	public function group_importGroup($oldId, array $import, $dwClass, $key, $importMode = true, $returnFullData = false)
    {
        XenForo_Db::beginTransaction();

        $dw = XenForo_DataWriter::create($dwClass, XenForo_DataWriter::ERROR_SILENT);
        if ($importMode)
        {
            $dw->setImportMode(true);
        }
        $dw->bulkSet($import, array('ignoreInvalidFields' => true));

        $newId = false;
        if ($dw->save())
        {
            if ($returnFullData)
            {
                $newId = $dw->getMergedData();
            }
            else
            {
                $newId = $dw->get($key);
            }
        }

        XenForo_Db::commit();

        return $newId;
    }

    public function group_importThread($oldId, array $info, $logKey)
    {
        try
        {
            $threadId = $this->_importData($oldId, 'XenForo_DataWriter_Discussion_Thread', $logKey, 'thread_id', $info);
            if ($threadId)
            {
                if (Nobita_Teams_Container::getModel('XenForo_Model_Thread')->isModerated($info))
                {
                    try
                    {
                        $this->_getDb()->query('
                            INSERT IGNORE INTO xf_moderation_queue
                                (content_type, content_id, content_date)
                            VALUES
                                (?, ?, ?)
                        ', array('thread', $threadId, $info['post_date']));
                    }
                    catch(Exception $e) {
                        // Sometime it is duplicate key?
                    }
                    
                }
            }

            return $threadId;
        }
        catch(Exception $e)
        {
            return 0;
        }
        
    }

    public function group_importPost($oldId, array $info, $logKey)
    {
        if (isset($info['ip']))
        {
            $ip = $info['ip'];
        }
        else
        {
            $ip = false;
        }
        unset($info['ip']);

        $postId = false;

        try
        {
            $postId = $this->_importData($oldId, 'XenForo_DataWriter_DiscussionMessage_Post', $logKey, 'post_id', $info);
        }
        catch(Zend_Db_Exception $e) {}
        
        if ($postId)
        {
            if ($info['message_state'] == 'moderated')
            {
                $this->_getDb()->query('
                    INSERT IGNORE INTO xf_moderation_queue
                        (content_type, content_id, content_date)
                    VALUES
                        (?, ?, ?)
                ', array('post', $postId, $info['post_date']));
            }

            if ($ip)
            {
                $ipId = $this->importIp($info['user_id'], 'post', $postId, 'insert', $ip, $info['post_date']);
                if ($ipId)
                {
                    $this->_getDb()->update('xf_post',
                        array('ip_id' => $ipId),
                        'post_id = ' . $this->_getDb()->quote($postId)
                    );
                }
            }
        }

        return $postId;
    }

}