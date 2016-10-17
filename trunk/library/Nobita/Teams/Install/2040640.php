<?php

class Nobita_Teams_Install_2040640 implements Nobita_Teams_Install_Skeleton
{
    public function isUpdate($oldVersionId, $newVersionId)
    {
        return ($oldVersionId < 2040640) ? true : false;
    }

	public function doUpdate(Zend_Db_Adapter_Abstract $db, $oldVersionId)
    {
        try
        {
            $db->query("ALTER TABLE xf_team_profile ADD COLUMN staff_list blob not null");
        }
        catch(Zend_Db_Exception $e) {}
    }

	public function doUninstall(Zend_Db_Adapter_Abstract $db)
    {
    }

	public function doUpdatePermissions()
    {
        return array();
    }
}
