<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file gives an overview of the monitors present in site.
 *
 * @package    report_monitor
 * @copyright  2014 onwards Simey Lameze <lameze@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once('locallib.php');

$ruleid = optional_param('ruleid', 0, PARAM_INT);
$courseid = optional_param('id', optional_param('courseid', 0, PARAM_INT), PARAM_INT);
$ruledata = new \stdClass();

// Get rule data to edit form
if ($ruleid) {
    $rule = \report_monitor\rule_manager::get_rule($ruleid);

    $ruledata->ruleid = $rule->id;
    $ruledata->courseid = $rule->courseid;
    $ruledata->name = $rule->name;
    $ruledata->plugin = $rule->plugin;
    $ruledata->event = $rule->event;
    $ruledata->description['text'] = $rule->description;
    $ruledata->rule['frequency'] = $rule->frequency;
    $ruledata->rule['minutes'] = $rule->minutes;
    $ruledata->message_template['text'] = $rule->message_template;
    $courseid = $rule->courseid;
}

// Validate course id
if (empty($courseid)) {
    require_login();
    $context = context_system::instance();
    $coursename = format_string($SITE->fullname, true, array('context' => $context));
    $PAGE->set_context($context);
} else {
    $course = get_course($courseid);
    $ruledata->courseid = $course->id;
    require_login($course);
    $context = context_course::instance($course->id);
    $coursename = format_string($course->fullname, true, array('context' => $context));
}
require_capability('report/monitor:managerules', $context);

// Set up the page.
$a = new stdClass();
$a->coursename = $coursename;
$a->reportname = get_string('pluginname', 'report_monitor');
$title = get_string('title', 'report_monitor', $a);
$url = new moodle_url("/report/monitor/edit.php", array('id' => $courseid));
$indexurl = new moodle_url("/report/monitor/index.php", array('id' => $courseid));

$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->requires->js('/report/monitor/event.js');

// Site level report.
if (empty($courseid)) {
    admin_externalpage_setup('reprotmonitorrules', '', null, '', array('pagelayout' => 'report'));
}

$mform = new report_monitor\rule_form();
if ($mformdata = $mform->get_data()) {
    $ruledata = new \stdClass();
    $ruledata->courseid = $mformdata->courseid;
    $ruledata->name = $mformdata->name;
    $ruledata->plugin = $mformdata->plugin;
    $ruledata->event = $mformdata->event;
    $ruledata->description = $mformdata->description['text'];
    $ruledata->frequency = $mformdata->rule['frequency'];
    $ruledata->minutes = $mformdata->rule['minutes'];
    $ruledata->message_template = $mformdata->message_template['text'];

    if (empty($mformdata->ruleid)) {
        \report_monitor\rule_manager::add_rule($ruledata);
        //echo $OUTPUT->notification('Rule added.', 'notifysuccess');
    } else {
        $ruledata->id = $mformdata->ruleid;
        //echo $OUTPUT->notification('Rule updated.', 'notifysuccess');
        \report_monitor\rule_manager::update_rule($ruledata);
    }
    $courseid = $mformdata->courseid;
    $url = new moodle_url("/report/monitor/managerules.php", array('id' => $courseid));
    redirect($url);
} else {
    echo $OUTPUT->header();
    $mform->set_data($ruledata);
    $mform->display();
}
echo $OUTPUT->footer();