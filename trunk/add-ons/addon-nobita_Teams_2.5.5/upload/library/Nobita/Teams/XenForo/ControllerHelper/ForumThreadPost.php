<?php

class Nobita_Teams_XenForo_ControllerHelper_ForumThreadPost extends XFCP_Nobita_Teams_XenForo_ControllerHelper_ForumThreadPost
{
    public function assertForumValidAndViewable($forumIdOrName, array $fetchOptions = array())
	{
        $fetchOptions[Nobita_Teams_Listener::FORUM_FETCHOPTIONS_JOIN_TEAM] = true;
        return parent::assertForumValidAndViewable($forumIdOrName, $fetchOptions);
    }

    public function assertThreadValidAndViewable($threadId,
		array $threadFetchOptions = array(), array $forumFetchOptions = array()
	)
	{
        $threadFetchOptions[Nobita_Teams_Listener::THREAD_FETCHOPTIONS_JOIN_TEAM] = true;
        return parent::assertThreadValidAndViewable($threadId, $threadFetchOptions, $forumFetchOptions);
    }
}
