<?php

// Display the whole course as "topics" made of of modules
// Included from "view.php"
/**
 * Evaluation topics format for course display - NO layout tables, for accessibility, etc.
 *
 * A duplicate course format to enable the Moodle development team to evaluate
 * CSS for the multi-column layout in place of layout tables.
 * Less risk for the Moodle 1.6 beta release.
 *   1. Straight copy of topics/format.php
 *   2. Replace <table> and <td> with DIVs; inline styles.
 *   3. Reorder columns so that in linear view content is first then blocks;
 * styles to maintain original graphical (side by side) view.
 *
 * Target: 3-column graphical view using relative widths for pixel screen sizes
 * 800x600, 1024x768... on IE6, Firefox. Below 800 columns will shift downwards.
 *
 * http://www.maxdesign.com.au/presentation/em/ Ideal length for content.
 * http://www.svendtofte.com/code/max_width_in_ie/ Max width in IE.
 *
 * Project Course Format includes additional features as folows:
 * - File upload
 * - Section backup
 *
 * @package   Project Course Format
 * @copyright &copy; 2011 VERSION2, Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/completionlib.php');

require_once __DIR__.'/uploader/uploader.php';

$topic = optional_param('topic', -1, PARAM_INT);

if ($topic != -1) {
    $displaysection = course_set_display($course->id, $topic);
} else {
    $displaysection = course_get_display($course->id);
}

$context = get_context_instance(CONTEXT_COURSE, $course->id);

if ($marker >= 0 && has_capability('moodle/course:setcurrentsection', $context) && confirm_sesskey()) {
    $course->marker = $marker;
    $DB->set_field('course', 'marker', $marker, array('id' => $course->id));
}

$uploader = new project_format\Uploader($course->id);
$cmdbaseurl = $CFG->wwwroot . '/course/format/project';
$strsectiondelete  = get_string('sectiondelete', 'format_project');
$strsectionbackup  = get_string('sectionbackup', 'format_project');
$strsectionrestore = get_string('sectionrestore', 'format_project');

$struploadcoursefile = get_string('uploadcoursefile', 'format_project');
$struploadresource   = get_string('uploadresource', 'format_project');

$streditsummary  = get_string('editsummary');
$stradd          = get_string('add');
$stractivities   = get_string('activities');
$strshowallsections = get_string('showallsections', 'format_project');
$strgroups       = get_string('groups');
$strgroupmy      = get_string('groupmy');
$editing         = $PAGE->user_is_editing();

if ($editing) {
    $strsectionhide = get_string('hidesectionfromothers', 'format_project');
    $strsectionshow = get_string('showsectionfromothers', 'format_project');
    $strmarkthissection = get_string('markthissection', 'format_project');
    $strmarkedthissection = get_string('markedthissection', 'format_project');
    $strmoveup   = get_string('moveup');
    $strmovedown = get_string('movedown');
}

// Print the Your progress icon if the track completion is enabled
$completioninfo = new completion_info($course);
echo $completioninfo->display_help_icon();

$headingtitle = get_string('projectoutline', 'format_project');
//if ($PAGE->user_is_editing()) {
//    $headingtitle .= '<span class="commands" style="margin-left:1em;">';
//    $headingtitle .= $OUTPUT->help_icon('uploadcoursefile', 'format_project');
//    $headingtitle .= '<span title="' . $struploadcoursefile . '">' . $uploader->render(0) . '</span>';
//    $headingtitle .= '</span>';
//}
echo $OUTPUT->heading($headingtitle, 2, 'headingblock header outline');

?>
<script type="text/javascript">
//<![CDATA[
function confirm_anchor_sesskey(a, msg)
{
	if (!confirm(msg))
		return false;
	a.href += "&sesskey=<?php echo sesskey(); ?>";
	return true;
}
function sectiondelete_confirm(a)
{
	return confirm_anchor_sesskey(a, "<?php echo get_string('sectiondelete_confirm', 'format_project'); ?>");
}
function sectionbackup_confirm(a)
{
	return confirm_anchor_sesskey(a, "<?php echo get_string('sectionbackup_confirm', 'format_project'); ?>");
}
//]]>
</script>
<?php

// Note, an ordered list would confuse - "1" could be the clipboard or summary.
echo "<ul class='topics'>\n";

/// If currently moving a file then show the current clipboard
if (ismoving($course->id)) {
    $stractivityclipboard = strip_tags(get_string('activityclipboard', '', $USER->activitycopyname));
    $strcancel= get_string('cancel');
    echo '<li class="clipboard">';
    echo $stractivityclipboard.'&nbsp;&nbsp;(<a href="mod.php?cancelcopy=true&amp;sesskey='.sesskey().'">'.$strcancel.'</a>)';
    echo "</li>\n";
}

/// Print Section 0 with general activities

$section = 0;
$thissection = $sections[$section];
unset($sections[0]);

if ($thissection->summary or $thissection->sequence or $PAGE->user_is_editing()) {

    // Note, no need for a 'left side' cell or DIV.
    // Note, 'right side' is BEFORE content.
    echo '<li id="section-0" class="section main clearfix" >';
    echo '<div class="left side">&nbsp;</div>';
    echo '<div class="right side" >&nbsp;</div>';
    echo '<div class="content">';
    if (!is_null($thissection->name)) {
        echo $OUTPUT->heading($thissection->name, 3, 'sectionname');
    }
    echo '<div class="summary">';

    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
    $summarytext = file_rewrite_pluginfile_urls($thissection->summary, 'pluginfile.php', $coursecontext->id, 'course', 'section', $thissection->id);
    $summaryformatoptions = new stdClass();
    $summaryformatoptions->noclean = true;
    $summaryformatoptions->overflowdiv = true;
    echo format_text($summarytext, $thissection->summaryformat, $summaryformatoptions);

    if ($PAGE->user_is_editing() && has_capability('moodle/course:update', $coursecontext)) {
        echo '<a title="'.$streditsummary.'" '.
             ' href="editsection.php?id='.$thissection->id.'"><img src="'.$OUTPUT->pix_url('t/edit') . '" '.
             ' class="icon edit" alt="'.$streditsummary.'" /></a>';
    }
    echo '</div>';

    print_section($course, $thissection, $mods, $modnamesused);

    if ($PAGE->user_is_editing()) {
        print_section_add_menus($course, $section, $modnames);
    }

    echo '</div>';
    echo "</li>\n";
}

/// Now all the normal modules by section
/// Everything below uses "section" terminology.

$timenow = time();
$section = 1;
$sectionmenu = array();

while ($section <= $course->numsections) {

    if (!empty($sections[$section])) {
        $thissection = $sections[$section];

    } else {
        $thissection = new stdClass;
        $thissection->course  = $course->id;   // Create a new section structure
        $thissection->section = $section;
        $thissection->name    = null;
        $thissection->summary  = '';
        $thissection->summaryformat = FORMAT_HTML;
        $thissection->visible  = 1;
        $thissection->id = $DB->insert_record('course_sections', $thissection);
    }

    $showsection = (has_capability('moodle/course:viewhiddensections', $context) or $thissection->visible or !$course->hiddensections);

    if (!empty($displaysection) and $displaysection != $section) {  // Check this section is visible
        if ($showsection) {
            $sectionmenu[$section] = get_section_name($course, $thissection);
        }
        $section++;
        continue;
    }

    if ($showsection) {

        $currentsection = ($course->marker == $section);

        $currenttext = '';
        if (!$thissection->visible) {
            $sectionstyle = ' hidden';
        } else if ($currentsection) {
            $sectionstyle = ' current';
            $currenttext = get_accesshide(get_string('currentsection', 'format_project'));
        } else {
            $sectionstyle = '';
        }

        echo '<li id="section-'.$section.'" class="section main clearfix'.$sectionstyle.'" >'; //'<div class="left side">&nbsp;</div>';

            echo '<div class="left side">'.$currenttext.$section.'</div>';
        // Note, 'right side' is BEFORE content.
        echo '<div class="right side">';

        if ($displaysection == $section) {    // Show the zoom boxes
            echo '<a href="view.php?id='.$course->id.'&amp;topic=0#section-'.$section.'" title="'.$strshowallsections.'">'.
                 '<img src="'.$OUTPUT->pix_url('i/all') . '" class="icon" alt="'.$strshowallsections.'" /></a><br />';
        } else {
            $strshowonlysection = get_string('showonlysection', 'format_project', $section);
            echo '<a href="view.php?id='.$course->id.'&amp;topic='.$section.'" title="'.$strshowonlysection.'">'.
                 '<img src="'.$OUTPUT->pix_url('i/one') . '" class="icon" alt="'.$strshowonlysection.'" /></a><br />';
        }

        $sectiondeleteicon = sprintf(
            '<a href="%s" title="%s" onclick="return sectiondelete_confirm(this);"><img src="%s" alt="%s" /></a>',
            "$cmdbaseurl/delete.php?section=$thissection->id", $strsectiondelete, $OUTPUT->pix_url('t/delete'), $strsectiondelete);

        if ($PAGE->user_is_editing() && has_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $course->id))) {

            if ($course->marker == $section) {  // Show the "light globe" on/off
                echo '<a href="view.php?id='.$course->id.'&amp;marker=0&amp;sesskey='.sesskey().'#section-'.$section.'" title="'.$strmarkedthissection.'">'.'<img src="'.$OUTPUT->pix_url('i/marked') . '" alt="'.$strmarkedthissection.'" /></a><br />';
            } else {
                echo '<a href="view.php?id='.$course->id.'&amp;marker='.$section.'&amp;sesskey='.sesskey().'#section-'.$section.'" title="'.$strmarkthissection.'">'.'<img src="'.$OUTPUT->pix_url('i/marker') . '" alt="'.$strmarkthissection.'" /></a><br />';
            }

            if ($thissection->visible) {        // Show the hide/show eye
                echo '<a href="view.php?id='.$course->id.'&amp;hide='.$section.'&amp;sesskey='.sesskey().'#section-'.$section.'" title="'.$strsectionhide.'">'.
                     '<img src="'.$OUTPUT->pix_url('i/hide') . '" class="icon hide" alt="'.$strsectionhide.'" /></a><br />';
            } else {
                echo '<a href="view.php?id='.$course->id.'&amp;show='.$section.'&amp;sesskey='.sesskey().'#section-'.$section.'" title="'.$strsectionshow.'">'.
                     '<img src="'.$OUTPUT->pix_url('i/show') . '" class="icon hide" alt="'.$strsectionshow.'" /></a><br />';
            }
            if ($section > 1) {                       // Add a arrow to move section up
                echo '<a href="view.php?id='.$course->id.'&amp;random='.rand(1,10000).'&amp;section='.$section.'&amp;move=-1&amp;sesskey='.sesskey().'#section-'.($section-1).'" title="'.$strmoveup.'">'.
                     '<img src="'.$OUTPUT->pix_url('t/up') . '" class="icon up" alt="'.$strmoveup.'" /></a><br />';
            }

            if ($section < $course->numsections) {    // Add a arrow to move section down
                echo '<a href="view.php?id='.$course->id.'&amp;random='.rand(1,10000).'&amp;section='.$section.'&amp;move=1&amp;sesskey='.sesskey().'#section-'.($section+1).'" title="'.$strmovedown.'">'.
                     '<img src="'.$OUTPUT->pix_url('t/down') . '" class="icon down" alt="'.$strmovedown.'" /></a><br />';
            }

            if (!$useajax) {
                echo $sectiondeleteicon; // Add a section delete icon
            }
        }
        echo '</div>';

        if ($useajax && $editing && has_capability('moodle/course:update', $context)) {
            // Because all icons in the 'right side' will be removed unconditionally by Course Ajax,
            // we need to put additional icons outside and fake their locations where they shold be.
            echo '<div style="float:right; width:40px; text-align:center; margin:48px -40px 0 0;">';
            {
                echo $sectiondeleteicon; // Add a section delete icon
            }
            echo '</div>';
        }

        $canupload = has_capability('moodle/course:manageactivities', $context);
        $canbackup = has_capability('moodle/backup:backupsection', $context);
        $canrestore = has_capability('moodle/restore:restoresection', $context);
        if ($editing and $canupload || $canbackup || $canrestore) {
            echo '<div class="section-commands" style="float:right; margin:5px 10px 0 0;">';
            if ($canupload) { // Show the resource upload icon and its help icon
                echo $OUTPUT->help_icon('uploadresource', 'format_project');
                echo '<span title="', $struploadresource, '">', $uploader->render($thissection->id), '</span>';
            }
            if ($canbackup || $canrestore) { // Show the section backup/restore help icon
                echo $OUTPUT->spacer(array('width' => 10, 'height' => 10));
                echo $OUTPUT->help_icon('sectioncommands', 'format_project');
            }
            if ($canbackup) { // Show the section backup icon
                echo '<a href="', $cmdbaseurl, '/backup.php?section=', $thissection->id, '" title="', $strsectionbackup, '"',
                     ' onclick="return sectionbackup_confirm(this);">',
                     '<img src="', $OUTPUT->pix_url('i/backup'), '" class="icon backup" alt="', $strsectionbackup, '" />',
                     '</a>';
            }
            if ($canrestore) { // Show the section restore icon
                echo '<a href="', $cmdbaseurl, '/restore.php?section=', $thissection->id, '" title="', $strsectionrestore, '">',
                     '<img src="', $OUTPUT->pix_url('i/restore'), '" class="icon restore" alt="', $strsectionrestore, '" />',
                     '</a>';
            }
            echo '</div>';
        }

        echo '<div class="content">';
        if (!has_capability('moodle/course:viewhiddensections', $context) and !$thissection->visible) {   // Hidden for students
            echo get_string('notavailable');
        } else {
            if (!is_null($thissection->name)) {
                echo $OUTPUT->heading($thissection->name, 3, 'sectionname');
            }
            echo '<div class="summary">';
            if ($thissection->summary) {
                $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
                $summarytext = file_rewrite_pluginfile_urls($thissection->summary, 'pluginfile.php', $coursecontext->id, 'course', 'section', $thissection->id);
                $summaryformatoptions = new stdClass();
                $summaryformatoptions->noclean = true;
                $summaryformatoptions->overflowdiv = true;
                echo format_text($summarytext, $thissection->summaryformat, $summaryformatoptions);
            } else {
               echo '&nbsp;';
            }

            if ($PAGE->user_is_editing() && has_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $course->id))) {
                echo ' <a title="'.$streditsummary.'" href="editsection.php?id='.$thissection->id.'">'.
                     '<img src="'.$OUTPUT->pix_url('t/edit') . '" class="icon edit" alt="'.$streditsummary.'" /></a><br /><br />';
            }
            echo '</div>';

            print_section($course, $thissection, $mods, $modnamesused);
            echo '<br />';
            if ($PAGE->user_is_editing()) {
                print_section_add_menus($course, $section, $modnames);
            }
        }

        echo '</div>';
        echo "</li>\n";
    }

    unset($sections[$section]);
    $section++;
}

if (!$displaysection and $PAGE->user_is_editing() and has_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $course->id))) {
    // print stealth sections if present
    $modinfo = get_fast_modinfo($course);
    foreach ($sections as $section=>$thissection) {
        if (empty($modinfo->sections[$section])) {
            continue;
        }

        echo '<li id="section-'.$section.'" class="section main clearfix orphaned hidden">'; //'<div class="left side">&nbsp;</div>';

        echo '<div class="left side">';
        echo '</div>';
        // Note, 'right side' is BEFORE content.
        echo '<div class="right side">';
        echo '</div>';
        echo '<div class="content">';
        echo $OUTPUT->heading(get_string('orphanedactivities'), 3, 'sectionname');
        print_section($course, $thissection, $mods, $modnamesused);
        echo '</div>';
        echo "</li>\n";
    }
}


echo "</ul>\n";

if (!empty($sectionmenu)) {
    $select = new single_select(new moodle_url('/course/view.php', array('id'=>$course->id)), 'section', $sectionmenu);
    $select->label = get_string('jumpto');
    $select->class = 'jumpmenu';
    $select->formid = 'sectionmenu';
    echo $OUTPUT->render($select);
}
