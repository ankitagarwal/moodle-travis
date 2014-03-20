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
 * Displays live view of recent logs
 *
 * This file generates live view of recent logs.
 *
 * @package    report_loglive
 * @copyright  2011 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/course/lib.php');

$id      = optional_param('id', 0, PARAM_INT);
$page    = optional_param('page', 0, PARAM_INT);

if (empty($id)) {
    require_login();
    $context = context_system::instance();
    $coursename = format_string($SITE->fullname, true, array('context' => $context));
} else {
    $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
    require_login($course);
    $context = context_course::instance($course->id);
    $coursename = format_string($course->fullname, true, array('context' => $context));
}
require_capability('report/loglive:view', $context);

$manager = new \report_loglive\manager();
$reader = $manager->get_selected_reader();
$refresh = $manager->refresh;

// Include and trigger ajax requests.
if ($page == 0) {
    // Tell Js to fetch new logs only, by passing time().
    $jsparams = array('since' => time() , 'courseid' => $id, 'page' => $page, 'reader' => $reader,
            'interval' => $refresh, 'perpage' => $manager->perpage);
    $PAGE->requires->strings_for_js(array('pause', 'resume'), 'report_loglive');
    $PAGE->requires->yui_module('moodle-report_loglive-fetchlogs', 'Y.M.report_loglive.FetchLogs.init', array($jsparams));
}
$url = new moodle_url('/report/loglive/index.php', array('id' => $id, 'reader' => $reader,
        'page' => $page));

$strlivelogs = get_string('livelogs', 'report_loglive');
$strupdatesevery = get_string('updatesevery', 'moodle', $refresh);

if (empty($id)) {
    admin_externalpage_setup('reportloglive', '', null, '', array('pagelayout' => 'report'));
} else {
    $PAGE->set_pagelayout('incourse');
}
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title("$coursename: $strlivelogs ($strupdatesevery)");
$PAGE->set_heading("$coursename: $strlivelogs ($strupdatesevery)");

echo $OUTPUT->header();
$manager->print_readers_select();
// Add live log controls.
if ($page == 0) {
    echo html_writer::tag('button' , get_string('pause', 'report_loglive'), array('id' => 'livelogs-pause-button'));
    $icon = new pix_icon('i/loading_small', 'loading', 'moodle', array('class' => 'spinner'));
    echo $OUTPUT->render($icon);
}
$manager->print_livelogs($reader, $id, $page);

// Trigger a logs viewed event.
$event = \report_loglive\event\report_viewed::create(array('context' => $context));
$event->trigger();

echo $OUTPUT->footer();
