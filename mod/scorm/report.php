<?php

// Includes and parameters from old scorm report file

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/scorm/report/reportlib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->dirroot.'/mod/scorm/locallib.php');
require_once($CFG->dirroot.'/mod/scorm/reportsettings_form.php');
require_once($CFG->libdir.'/formslib.php');

$id = required_param('id', PARAM_INT);// Course Module ID, or

$action = optional_param('action', '', PARAM_ALPHA);
$attemptids = optional_param('attemptid', array(), PARAM_RAW);
$download = optional_param('download', '', PARAM_RAW);
$mode = optional_param('mode', '', PARAM_ALPHA); // Report mode
    
// Building the url to use for links.+ data details buildup
$url = new moodle_url('/mod/scorm/report.php');
if ($action !== '') {
    $url->param('action', $action);
}

$url->param('id', $id);
$cm = get_coursemodule_from_id('scorm', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$scorm = $DB->get_record('scorm', array('id'=>$cm->instance), '*', MUST_EXIST);
    
$PAGE->set_url($url);

require_login($course->id, false, $cm);
//END of url setting + data buildup
    
// checking login +logging +getting context
$contextmodule = get_context_instance(CONTEXT_MODULE, $cm->id);
require_capability('mod/scorm:viewreports', $contextmodule);
add_to_log($course->id, 'scorm', 'report', 'report.php?id='.$cm->id, $scorm->id, $cm->id);
$userdata = null;
if (!empty($download)) {
    $noheader = true;
}
/// Print the page header
if (empty($noheader)) {

    $strscorms = get_string('modulenameplural', 'scorm');
    $strscorm = get_string('modulename', 'scorm');
    $strreport = get_string('report', 'scorm');
    $strattempt = get_string('attempt', 'scorm');
    $strname = get_string('name');

    $PAGE->set_title("$course->shortname: ".format_string($scorm->name));
    $PAGE->set_heading($course->fullname);
    $PAGE->navbar->add($strreport, new moodle_url('/mod/scorm/report.php', array('id'=>$cm->id)));

    echo $OUTPUT->header();
    $currenttab = 'reports';
    require($CFG->dirroot . '/mod/scorm/tabs.php');
    echo $OUTPUT->heading(format_string($scorm->name));
}

$reportlist = scorm_report_list($contextmodule);
if (count($reportlist)==0){
    print_error('erroraccessingreport', 'scorm');
}
foreach ($reportlist as $reportobj)
    $modelist[]=$reportobj->name;
if ($mode == '') {
// Default to listing of plugins.
    foreach ($reportlist as $report => $reportobj) {
       $pluginfile = $reportobj->plugindir.'/mod.php';
       if (file_exists($pluginfile)) {
           ob_start();
           include($pluginfile); // Fragment for listing
           $html = ob_get_contents();
           ob_end_clean();
           // add div only if plugin accessible
           if ($html !== '') {
               echo '<div class="plugin">';
               echo $html;
               echo '</div>';
           }
       }
    }
//end of default mode condition.
} else if (!in_array($mode, $modelist)){
    print_error('erroraccessingreport', 'scorm');
}
// Open the selected Scorm report and display it

// DISPLAY PLUGIN REPORT
if($mode!=NULL)
{
    if (!is_readable("report/$mode/report.php")) {
        print_error('reportnotfound', 'scorm', '', $mode);
    }

    include("report/default.php"); // Parent class
    include("report/$mode/report.php"); // Current report class

    $reportclassname = "scorm_{$mode}_report";
    if (!class_exists($reportclassname)) {
        print_error('reportnotfound', 'scorm', '', $mode);
    }
    $report = new $reportclassname();
    
    if (!$report->display($scorm, $cm, $course, $attemptids, $action, $download)) { // Run the report!
        print_error("preprocesserror", 'scorm');
    }
    if (!$report->settings($scorm, $cm, $course)) { // Run the report!
        print_error("preprocesserror", 'scorm');
    }
}

// Print footer

if (empty($noheader)) {
    echo $OUTPUT->footer();
}