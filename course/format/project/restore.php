<?php
/**
 * Section restore
 *
 * @package   Project Course Format
 * @copyright &copy; 2011 VERSION2, Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__.'/../../../config.php';
require_once __DIR__.'/../../../backup/util/includes/restore_includes.php';

$sectionid = \required_param('section', PARAM_INT);

$section = $DB->get_record('course_sections', array('id' => $sectionid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $section->course), '*', MUST_EXIST);

\require_login($course);

$context = \get_context_instance(CONTEXT_COURSE, $course->id);
\require_capability('moodle/restore:restoresection', $context);


// @see /backup/restorefile.php

require_once $CFG->libdir.'/formslib.php';

$url = new \moodle_url('/course/format/project/restore.php', array('section' => $section->id));

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('course') . ': ' . $course->fullname);
$PAGE->set_heading(get_string('restoresection', 'backup'));
$PAGE->navbar->add($section->name ?: "section{$section->section}");
$PAGE->navbar->add(get_string('restore'), $url);
$PAGE->set_pagelayout('admin');

$tempdir = $CFG->dataroot.'/temp/backup';
if (!\check_dir_exists($tempdir, true, true))
	throw new \restore_controller_exception('cannot_create_backup_temp_dir');

$tempname = \restore_controller::get_tempdir_name($course->id, $USER->id);
$temppath = "$tempdir/$tempname";

$forms = array(/* 'heading' => $form */);

// backup file import form (upload capability required)
if (\has_capability('moodle/restore:uploadfile', $context)) {
	class restore_filepicker_form extends \moodleform
	{
		public function definition()
		{
			$this->_form->addElement('hidden', 'section', $this->_customdata['section']);
			$this->_form->addElement('filepicker', 'file', get_string('files'));
			$this->add_action_buttons(false, get_string('restore'));
		}
	}
	$form = new restore_filepicker_form(null, array('section' => $section->id));
	if ($data = $form->get_data()) {
		$form->save_file('file', $temppath);
		
		goto execute_restore;
	}
	$forms[get_string('importfile', 'backup')] = $form;
}

// user area backup choose form
{
	class restore_chooser_form extends \moodleform
	{
		public function definition()
		{
			global $USER;
			
			$userfiles = get_user_area_files($USER->id);
			
			// array({pathnamehash} => {filename})
			$options = array_map(function ($file) { return $file->get_filename(); }, $userfiles);
			
			$this->_form->addElement('hidden', 'section', $this->_customdata['section']);
			$this->_form->addElement('select', 'hash', get_string('file'), $options);
//			$radios = array();
//			foreach ($options as $hash => $name)
//				$radios[] =& MoodleQuickForm::createElement('radio', 'hash', null, $name, $hash);
//			$this->_form->addGroup($radios, null, get_string('file'), '   ', false);
			$this->add_action_buttons(false, get_string('restore'));
		}
	}
	$form = new restore_chooser_form(null, array('section' => $section->id));
	if ($data = $form->get_data()) {
		$storage = \get_file_storage();
		$file = $storage->get_file_by_hash($data->hash);
		$file->copy_content_to($temppath);
		
		goto execute_restore;
	}
	$forms[get_string('choosefilefromuserbackup', 'backup')] = $form;
}

echo $OUTPUT->header();
foreach ($forms as $heading => $form) {
	echo $OUTPUT->heading($heading);
	echo $OUTPUT->container_start();
	$form->display();
	echo $OUTPUT->container_end();
}
echo $OUTPUT->footer();
exit;

function get_user_area_files($userid)
{
	$userctx = \get_context_instance(CONTEXT_USER, $userid);
	$storage = \get_file_storage();
	$userfiles = $storage->get_area_files($userctx->id, 'user', 'backup', false, 'timecreated');
	$userfiles = array_reverse($userfiles);
	$userfiles = array_filter($userfiles, function ($file) { return !$file->is_directory(); });
	return $userfiles;
}


execute_restore:
{
	// @see /course/modduplicate.php
	
	$target = time() . mt_rand();
	
	$packer = new \zip_packer();
	$packer->extract_to_pathname($temppath, "$tempdir/$target");
	
	$controller = new \restore_controller($target, $course->id,
		\backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $USER->id,
		\backup::TARGET_EXISTING_ADDING);
	
	//$controller->get_plan()->get_setting('overwrite_conf')->set_value(false);  oops...
	
	$overwrite = new \restore_course_overwrite_conf_setting('overwrite_conf', \base_setting::IS_BOOLEAN, false);
	foreach ($controller->get_plan()->get_tasks() as $task) {
		//$task->add_setting($overwrite);  oh, no...
		
		// *HACK* invoke protected method
		$reflector = new ReflectionClass('base_task');
		$method = $reflector->getMethod('add_setting');
		$method->setAccessible(true);
		$method->invokeArgs($task, array($overwrite));
	}
	
	if (!$controller->execute_precheck()) {
		$precheckresults = $controller->get_precheck_results();
		
		$renderer = $PAGE->get_renderer('core', 'backup');
		echo $renderer->header();
		echo $renderer->precheck_notices($precheckresults);
		echo $renderer->continue_button(new moodle_url('/course/view.php', array('id' => $course->id)));
		echo $renderer->footer();
		die;
	}
	
	$controller->execute_plan();
	
	// move restored activities to the desired section.
	foreach ($controller->get_plan()->get_tasks() as $task) {
		if ($task instanceof \restore_activity_task) {
			$cm = \get_coursemodule_from_id('', $task->get_moduleid(), $course->id, true, MUST_EXIST);
			\moveto_module($cm, $section);
		}
	}
	
	$controller->destroy();
	unset($controller);
	
	fulldelete("$tempdir/$target");
	fulldelete($temppath);
}

\redirect("$CFG->wwwroot/course/view.php?id=$course->id#section-$section->section");
