<?php

    define('SCORM_REPORT_DEFAULT_PAGE_SIZE', 20);
    define('SCORM_REPORT_ATTEMPTS_ALL_STUDENTS', 0);
    define('SCORM_REPORT_ATTEMPTS_STUDENTS_WITH', 1);
    define('SCORM_REPORT_ATTEMPTS_STUDENTS_WITH_NO', 2);
/**
 * Returns an array of reports to which the current user has access to.
 * Reports are ordered as they should be for display in tabs.
 */
function scorm_report_list($context) {
    global $DB;
    static $reportlist = null;
    if (!is_null($reportlist)){
        return $reportlist;
    }
    $reports = $DB->get_records('scorm_report', null, 'displayorder DESC', 'name, capability');
    $reportdirs = get_plugin_list('scorm');
    // Order the reports tab in descending order of displayorder
    $reportcaps = array();
    foreach ($reports as $key => $obj) {
        if (array_key_exists($obj->name, $reportdirs)) {
            $reportcaps[$obj->name] = $obj->capability;
        }
    }

    // Add any other reports on the end
    foreach ($reportdirs as $reportname => $notused) {
        if (!isset($reportcaps[$reportname])) {
            $reportcaps[$reportname] = null;
        }
    }
    $reportlist = array();
    foreach ($reportcaps as $name => $capability){
        if (empty($capability)){
            $capability = 'mod/scorm:viewreports';
        }
        if (has_capability($capability, $context)){
            $reportlist[] = $name;
        }
    }
    return $reportlist;
}
