<?php

namespace project_format;

class Uploader
{
	public function __construct($courseid)
	{
		global $CFG, $USER, $COURSE;
		
		$this->wwwroot = $CFG->httpswwwroot;
		
		$sesskey = 'sesskey';
		$sessid  = \sesskey();
		
		$raw_session_name = session_name();
		$raw_session_id   = session_id();
		
		$this->script = <<<SCRIPT
<script type="text/javascript" src="$this->wwwroot/course/format/project/uploader/uploader.js"></script>
<script type="text/javascript">
//<![CDATA[
	var uploader = new project_format.Uploader(
		"$this->wwwroot", $courseid, "$sesskey", "$sessid",
		"$raw_session_name", "$raw_session_id"
	);
//]]>
</script>
SCRIPT;
	}
	
	public function render($sectionid)
	{
		$html = $this->script
		      . '<script type="text/javascript">/*<![CDATA[*/'
		      . " uploader.write($sectionid);"
		      . ' /*]]>*/</script>';
		$this->script = null;
		return $html;
	}
	
	private $wwwroot, $script;
}
