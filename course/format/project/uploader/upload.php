<?php

function on_error($code, $message, $file, $line)
{
	@file_put_contents(__DIR__.'/error.log',
		"Error: $message in $file on line $line\n", FILE_APPEND);
	
	header('HTTP/1.1 403 Forbidden');
	die;
}
set_error_handler('on_error', error_reporting());

define('SESSION_CUSTOM_CLASS', 'sid_session');
define('SESSION_CUSTOM_FILE', '/course/format/project/uploader/sidsession.php');

require_once __DIR__.'/../../../../config.php';
require_once __DIR__.'/../../../../repository/lib.php';

try {
	$courseid = \required_param('course', PARAM_INT);
	
	$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
	
	\require_login($course, false, null, true, /*$preventredirect=*/true);
	
	if (!\confirm_sesskey())
		throw new \moodle_exception('invalidsesskey');
	
	// @see /repository/upload/lib.php
	
	if (!isset($_FILES['Filedata']) || !empty($_FILES['Filedata']['error']))
		throw new \moodle_exception('nofile');
	
	if (filesize($_FILES['Filedata']['tmp_name']) > \get_max_upload_file_size())
		throw new \file_exception('maxbytes');
	
	$userctx = \get_context_instance(CONTEXT_USER, $USER->id);
	
	$record = (object)array(
		'filearea'  => 'draft',
		'component' => 'user',
		'filepath'  => '/',
		'filename'  => \clean_param($_FILES['Filedata']['name'], PARAM_FILE),
		'itemid'    => \file_get_unused_draft_itemid(),
		'license'   => $CFG->sitedefaultlicense,
		'author'    => \fullname($USER),
		'contextid' => $userctx->id,
		'userid'    => $USER->id,
		'source'    => '',
	);
	if (\repository::draftfile_exists(
			$record->itemid, $record->filepath, $record->filename))
	{
		$record->filename = \repository::get_unused_filename(
			$record->itemid, $record->filepath, $record->filename);
	}
	
	$storage = \get_file_storage();
	$file = $storage->create_file_from_pathname($record, $_FILES['Filedata']['tmp_name']);
	
	if ($sectionid = \optional_param('section', 0, PARAM_INT)) {
		// add as a resource
		require_once __DIR__.'/resource.php';
		create_resource_file($courseid, $sectionid, $file);
	} else {
		// add as a course file
		
		// TODO: where is the Moodle course file?
	}
	
	\send_headers('text/plain', /*$cacheable=*/false);
	exit('ok');
	
} catch (\Exception $ex) {
	on_error($ex->getCode(), $ex->getMessage(), $ex->getFile(), $ex->getLine());
}
