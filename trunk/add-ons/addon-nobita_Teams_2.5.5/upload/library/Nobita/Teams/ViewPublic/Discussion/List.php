<?php

class Nobita_Teams_ViewPublic_Discussion_List extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$this->_params['renderedNodes'] = XenForo_ViewPublic_Helper_Node::renderNodeTreeFromDisplayArray(
			$this, $this->_params['nodeList'], 2
		);
	}
}
