<?php
/**
 *  SID enabled session handler
 */
class sid_session extends database_session
{
	public function __construct()
	{
		$key = 'MoodleSession'.$GLOBALS['CFG']->sessioncookie;
		$_COOKIE[$key] = $_REQUEST[$key];
		
		$GLOBALS['CFG']->usesid = true;
		
		parent::__construct();
	}
}
