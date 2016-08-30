<?php

interface Nobita_Teams_Install_Skeleton
{
	public function isUpdate($oldVersionId, $newVersionId);

	public function doUpdate(Zend_Db_Adapter_Abstract $db, $oldVersionId);

	public function doUninstall(Zend_Db_Adapter_Abstract $db);

	public function doUpdatePermissions();
}