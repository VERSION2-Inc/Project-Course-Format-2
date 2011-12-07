<?php

require_once __DIR__.'/../../../../course/lib.php';
require_once __DIR__.'/../../../../lib/resourcelib.php';
require_once __DIR__.'/../../../../mod/resource/lib.php';
require_once __DIR__.'/../../../../mod/resource/locallib.php';

function create_resource_file($courseid, $sectionid, stored_file $file)
{
	global $DB, $USER;
	
	// @see /course/modedit.php
	
	$module  = $DB->get_record('modules', array('name' => 'resource'), '*', MUST_EXIST);
	$section = $DB->get_record('course_sections', array('id' => $sectionid), '*', MUST_EXIST);
	
	$coursectx = \get_context_instance(CONTEXT_COURSE, $courseid);
	\require_capability('moodle/course:manageactivities', $coursectx);
	
	$cm = (object)array(
		'course'           => $courseid,
		'module'           => $module->id,
		'instance'         => 0, // to be set in resource_add_instance()
		'section'          => $section->id,
		'visible'          => 1,
		'groupmode'        => 0,
		'groupingid'       => 0,
		'groupmembersonly' => 0,
	);
	$cm->id = \add_course_module($cm);
	
	$data = (object)array(
		// resource record
		'course'       => $courseid,
		'name'         => $file->get_filename(),
		'intro'        => $file->get_filename(),
		'introformat'  => FORMAT_HTML,
		'display'      => RESOURCELIB_DISPLAY_AUTO,
		'revision'     => 1,
		// additional data
		'coursemodule' => $cm->id,
		'files'        => $file->get_itemid(),
		'printheading' => 0,
		'printintro'   => 0,
	);
	\resource_add_instance($data, null);
	
	\add_mod_to_section((object)array(
		'course'       => $courseid,
		'section'      => $section->section, // not an ID but a position
		'coursemodule' => $cm->id,
	));
	\rebuild_course_cache($courseid);
	
	\events_trigger('mod_created', (object)array(
		'modulename' => $module->name,
		'name'       => $data->name,
		'cmid'       => $cm->id,
		'courseid'   => $courseid,
		'userid'     => $USER->id,
	));
}
