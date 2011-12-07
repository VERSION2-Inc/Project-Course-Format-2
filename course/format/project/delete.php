<?php
/**
 * Section delete
 *
 * @package   Project Course Format
 * @copyright &copy; 2011 VERSION2, Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__.'/../../../config.php';
require_once __DIR__.'/../../../course/lib.php';

$sectionid = \required_param('section', PARAM_INT);

$section = $DB->get_record('course_sections', array('id' => $sectionid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $section->course), '*', MUST_EXIST);

\require_login($course);

$context = \get_context_instance(CONTEXT_COURSE, $course->id);
\require_capability('moodle/course:update', $context);
\require_capability('moodle/course:manageactivities', $context);

\confirm_sesskey() or \print_error('confirmsesskeybad', 'error');


// we need modname for loading "/mod/$modname/lib.php".
$section_modules = $DB->get_records_sql("SELECT cm.*, m.name AS modname
	FROM {course_modules} cm JOIN {modules} m ON cm.module = m.id
	WHERE cm.course = ? AND cm.section = ?", array($course->id, $section->id));

{
	$transaction = $DB->start_delegated_transaction();
	
	// delete section modules.
	foreach ($section_modules as $cm) {
		// @see /course/mod.php # if (!empty($delete)) {...
		
		$modcontext = \get_context_instance(CONTEXT_MODULE, $cm->id);
		
		$modlib = "$CFG->dirroot/mod/$cm->modname/lib.php";
		file_exists($modlib) or \print_error('modulemissingcode', '', '', $modlib);
		require_once $modlib;
		
		if (!call_user_func($cm->modname.'_delete_instance', $cm->instance)) {
			echo $OUTPUT->notification("Could not delete the $cm->modname (instance)");
		}
		
		$fs = \get_file_storage();
		$fs->delete_area_files($modcontext->id);
		
		if (!\delete_course_module($cm->id)) {
			echo $OUTPUT->notification("Could not delete the $cm->modname (coursemodule)");
		}
		//\delete_mod_from_section($cm->id, $cm->section);
		
		\events_trigger('mod_deleted', (object)array(
			'modulename' => $cm->modname, 'cmid' => $cm->id,
			'courseid' => $course->id, 'userid' => $USER->id,
		));
		\add_to_log($course->id, 'course', 'delete mod',
			"view.php?id=$cm->course", "$cm->modname $cm->instance", $cm->id);
	}
	
	// delete section.
	$DB->delete_records('course_sections', array('id' => $section->id));
	$DB->execute("UPDATE {course_sections} SET section = section - 1
		WHERE course = ? AND section > ?", array($course->id, $section->section));
	$DB->set_field('course', 'numsections', $course->numsections - 1, array('id' => $course->id));
	
	
	\rebuild_course_cache($course->id);
	
	$transaction->allow_commit();
}

\redirect("$CFG->wwwroot/course/view.php?id=$course->id");
