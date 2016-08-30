<?php
 /*************************************************************************
 * XenForo Tournament - Xen Factory (c) 2015
 * All Rights Reserved.
 * Created by Clement Letonnelier aka. MtoR
 **************************************************************************
 * This file is subject to the terms and conditions defined in the Licence
 * Agreement available at http://xen-factory.com/pages/license-agreement/.
  *************************************************************************/

class XFA_Tournament_Model_Category extends XenForo_Model
{
	public function getCategoryById($categoryId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xfa_tourn_category
			WHERE tournament_category_id = ?
		', $categoryId);
	}    
    
    public function getAllCategories()
	{
		return $this->fetchAllKeyed('
			SELECT *
			FROM xfa_tourn_category
			ORDER BY display_order
		', 'tournament_category_id');
	}
}