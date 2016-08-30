<?php

class Nobita_Teams_Install_2050000 implements Nobita_Teams_Install_Skeleton
{
    public function isUpdate($oldVersionId, $newVersionId)
    {
        return ($oldVersionId < 2050077) ? true : false;
    }

	public function doUpdate(Zend_Db_Adapter_Abstract $db, $oldVersionId)
    {
        $db->delete('xf_content_type_field', 'content_type = \'team_comment\'');

        $db->query("
            INSERT INTO xf_node_type
                (node_type_id, handler_class, controller_admin_class, datawriter_class, public_route_prefix, permission_group_id)
            VALUES
                (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                handler_class = VALUES(handler_class),
                controller_admin_class = VALUES(controller_admin_class),
                datawriter_class = VALUES(datawriter_class),
                public_route_prefix = VALUES(public_route_prefix),
                permission_group_id = VALUES(permission_group_id)
        ", array(
            Nobita_Teams_Listener::NODE_TYPE_ID,
            'Nobita_Teams_NodeHandler_Forum',
            'Nobita_Teams_ControllerAdmin_Forum',
            'XenForo_DataWriter_Forum',
            'forums',
            'forum',
        ));

        try
        {
            $db->query("ALTER TABLE xf_forum ADD COLUMN team_id int unsigned not null default '0'");
        }
        catch(Zend_Db_Exception $e) {}
        try
        {
            $db->query("ALTER TABLE xf_forum ADD INDEX team_id (team_id)");
        }
        catch(Zend_Db_Exception $e) {}

        try
        {
            $db->query("ALTER TABLE xf_team_member ADD INDEX last_view_date (last_view_date)");
        }
        catch(Zend_Db_Exception $e) {}

        try
        {
            $db->query("ALTER TABLE xf_team CHANGE last_activity last_updated int unsigned not null default '0'");
        }
        catch(Zend_Db_Exception $e) {}
        try
        {
            $db->query("ALTER TABLE xf_team ADD INDEX last_updated (last_updated)");
        }
        catch(Zend_Db_Exception $e) {}

        try
        {
            $db->query("
                ALTER TABLE xf_team_category DROP COLUMN associate_node_ids,
                    DROP COLUMN allow_change_forums
            ");
        }
        catch(Zend_Db_Exception $e) {}

        try
        {
            $db->query("
                ALTER TABLE xf_team_profile DROP COLUMN node_ids,
                    DROP COLUMN last_update_forums_date
            ");
        }
        catch(Zend_Db_Exception $e) {}

        try
        {
            $db->query("ALTER TABLE xf_team_event ADD INDEX begin_date (begin_date),
                ADD INDEX end_date (end_date)");
        }
        catch(Zend_Db_Exception $e) {}

        try
        {
            $db->query("ALTER TABLE xf_team_category DROP COLUMN thread_node_id");
        }
        catch(Zend_Db_Exception $e) {}
        try
        {
            $db->query("ALTER TABLE xf_team_category DROP COLUMN thread_prefix_id");
        }
        catch(Zend_Db_Exception $e) {}

        try
        {
            $db->query("ALTER TABLE xf_team DROP COLUMN discussion_thread_id");
        }
        catch(Zend_Db_Exception $e) {}

        try
        {
            $db->query("ALTER TABLE xf_user DROP COLUMN team_cache");
        }
        catch(Zend_Db_Exception $e) {}

        try
        {
            $db->query("RENAME TABLE xf_team_post_comment TO xf_team_comment");
        }
        catch(Zend_Db_Exception $e) {}

        try
        {
            $db->query("ALTER TABLE xf_team_comment DROP INDEX post_user");
        }
        catch(Zend_Db_Exception $e) {}

        try
        {
            $db->query("ALTER TABLE xf_team_comment CHANGE post_id content_id int unsigned not null");
        }
        catch(Zend_Db_Exception $e) {}

        try
        {
            $db->query("ALTER TABLE xf_team_comment CHANGE comment_type content_type varbinary(25) not null");
        }
        catch(Zend_Db_Exception $e) {}

        try
        {
            $db->query("ALTER TABLE xf_team_comment ADD INDEX user_id (user_id)");
        }
        catch(Zend_Db_Exception $e) {}

        try
        {
            $db->query("ALTER TABLE xf_team_comment ADD INDEX team_id (team_id)");
        }
        catch(Zend_Db_Exception $e) {}
        try
        {
            $db->query("ALTER TABLE xf_team_comment ADD INDEX content_type_id (content_id, content_type)");
        }
        catch(Zend_Db_Exception $e) {}

        try
        {
            $db->query("ALTER TABLE xf_team_post CHANGE latest_comment_ids latest_comment_ids varchar(255) not null default '[]'");
        }
        catch(Zend_Db_Exception $e) {}

        try
        {
            $db->query("ALTER TABLE xf_team_event ADD COLUMN comment_count int unsigned not null default '0'");
        }
        catch(Zend_Db_Exception $e) {}
        try
        {
            $db->query("ALTER TABLE xf_team_event ADD COLUMN latest_comment_ids varchar(255) not null default '[]'");
        }
        catch(Zend_Db_Exception $e) {}
        try
        {
            $db->query("ALTER TABLE xf_team_event ADD COLUMN first_comment_date int unsigned not null default '0'");
        }
        catch(Zend_Db_Exception $e) {}
        try
        {
            $db->query("ALTER TABLE xf_team_event ADD COLUMN last_comment_date int unsigned not null default '0'");
        }
        catch(Zend_Db_Exception $e) {}

        try
        {
            $db->query("ALTER TABLE xf_team_post CHANGE wall_message_type share_privacy varbinary(25) not null");
        }
        catch(Zend_Db_Exception $e) {}

        try
        {
            $db->query("ALTER TABLE xf_team ADD COLUMN thread_count int unsigned not null default '0'");
        }
        catch(Zend_Db_Exception $e) {}
        try
        {
            $db->query("ALTER TABLE xf_team ADD COLUMN thread_post_count int unsigned not null default '0'");
        }
        catch(Zend_Db_Exception $e) {}

        $this->_migrateMemberRolesData();
        Nobita_Teams_Container::getModel('XenForo_Model_Node')->rebuildNodeTypeCache();
    }

    protected function _migrateMemberRolesData()
    {
        $db = XenForo_Application::getDb();

        $phraseModel = XenForo_Model::create('XenForo_Model_Phrase');
        $memberRoleModel = XenForo_Model::create('Nobita_Teams_Model_MemberRole');

        try
        {
            $memberRoles = $db->fetchAll('SELECT member_role_id, member_role_title FROM xf_team_member_role');
            foreach($memberRoles as $memberRole) {
                $phraseId = $memberRoleModel->getMemberRolePhrase($memberRole['member_role_id']);

                $phraseModel->insertOrUpdateMasterPhrase(
                    $phraseId, $memberRole['member_role_title'], 'nobita_Teams', array(
                        'global_cache' => true
                    )
                );
            }
        }
        catch(Zend_Db_Exception $e) {}

        try
        {
            $db->query("ALTER TABLE xf_team_member_role DROP COLUMN member_role_title");
        }
        catch(Zend_Db_Exception $e) {}

        try
        {
            $db->query("ALTER TABLE xf_team_member CHANGE position member_role_id varbinary(25) not null");
            $db->query("ALTER TABLE xf_team_member ADD INDEX member_role_id (member_role_id)");
        }
        catch(Zend_Db_Exception $e) {}
    }

	public function doUninstall(Zend_Db_Adapter_Abstract $db)
    {
        $nodeIds = $db->fetchCol('SELECT node_id FROM xf_node WHERE node_type_id = ?',
            Nobita_Teams_Listener::NODE_TYPE_ID);

        try
        {
            $db->query("ALTER TABLE xf_forum DROP COLUMN team_id");
        }
        catch(Zend_Db_Exception $e) {}
        try
        {
            $db->query("
                UPDATE xf_node_type
                SET xf_node_type = 'forum',
                    handler_class = 'XenForo_NodeHandler_Forum',
                    controller_admin_class = 'XenForo_ControllerAdmin_Forum',
                    datawriter_class = 'XenForo_DataWriter_Forum',
                    moderator_interface_group_id = 'forumModeratorPermissions'
                WHERE node_type_id = ?
            ", array(
                Nobita_Teams_Listener::NODE_TYPE_ID
            ));
        }
        catch(Zend_Db_Exception $e) {}
    }

	public function doUpdatePermissions()
    {
        return array();
    }
}
