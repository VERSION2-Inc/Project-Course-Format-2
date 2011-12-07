<?php
/**
 * Section backup
 *
 * @package   Project Course Format
 * @copyright &copy; 2011 VERSION2, Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__.'/../../../config.php';
require_once __DIR__.'/../../../backup/util/includes/backup_includes.php';

$sectionid = \required_param('section', PARAM_INT);

$section = $DB->get_record('course_sections', array('id' => $sectionid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $section->course), '*', MUST_EXIST);

\require_login($course);

$context = \get_context_instance(CONTEXT_COURSE, $course->id);
\require_capability('moodle/backup:backupsection', $context);

\confirm_sesskey() or \print_error('confirmsesskeybad', 'error');


$datetime = date('Ymd-Hi');
$course_name  = trim($course->shortname) ?: "course{$course->id}";
$section_name = trim($section->name) ?: "section{$section->section}";
$filename = \clean_filename("{$course_name}-{$section_name}-{$datetime}.mbz");
$filename = strtr($filename, array(' ' => '_'));

// @see /backup/util/helper/backup_cron_helper.class.php
{
	$controller = new \backup_controller(
		\backup::TYPE_1SECTION,
		$section->id,
		\backup::FORMAT_MOODLE,
		\backup::INTERACTIVE_NO,
		\backup::MODE_GENERAL, // MODE_AUTOMATED will save files to 'automated' area...
		$USER->id);
	
	$plan = $controller->get_plan();
	
	$settings = array(
		'users'                  => false,
		'role_assignments'       => false,
		'user_files'             => false,
		'activities'             => true,
		'blocks'                 => false,
		'filters'                => false,
		'comments'               => false,
		'completion_information' => false,
		'logs'                   => false,
		'histories'              => false,
	);
	foreach ($settings as $setting => $value) {
		if ($plan->setting_exists($setting)) {
			$plan->get_setting($setting)->set_value($value);
		}
	}
	$plan->get_setting('filename')->set_value($filename);
	
	$controller->set_status(\backup::STATUS_AWAITING);
	$controller->execute_plan();
	
	//$results = $controller->get_results();
	//$file = $results['backup_destination'];
	
	$controller->destroy();
	unset($controller);
}

\redirect("$CFG->wwwroot/course/view.php?id=$course->id#section-$section->section");
//\redirect("$CFG->wwwroot/backup/restorefile.php?contextid=$context->id");
