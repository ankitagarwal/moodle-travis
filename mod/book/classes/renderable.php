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
 * Log live report renderable.
 *
 * @package    report_loglive
 * @copyright  2014 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Log live report renderable.
 *
 * @package    report_loglive
 * @copyright  2014 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_book_renederable implements renderable {

    /** @var array chapter content */
    public $chapters;

    /** @var int perpage records to show */
    public $toc;

    /** @var stdClass course record */
    public $course;

    /** @var moodle_url url of report page */
    public $url;

    /** @var int timestamp from which records should be displayed */
    public $since;

    /** @var string name of the selected reader. */
    public $selectedreader;

    /**
     * Constructor.
     *
     * @param string $reader (optional)reader pluginname from which logs will be fetched.
     * @param stdClass|int $course (optional) course record or id
     * @param moodle_url|string $url (optional) page url.
     * @param int $since date (optional) timestamp from which records will be fetched.
     * @param int $page (optional) page number.
     * @param int $perpage (optional) number of records to show per page.
     */
    public function __construct($reader = "", $course = 0, $url = "", $since = 0, $page = 0, $perpage = 100) {

        global $PAGE;
        // Use first reader as selected reader, if not passed.
        if (empty($reader)) {
            $readers = $this->manager->get_readers();
            if (!empty($readers)) {
                reset($readers);
                $reader = key($readers);
            } else {
                $reader = null;
            }
        }
        // Use page url if empty.
        if (empty($url)) {
            $url = $PAGE->url;
        } else {
            $url = new moodle_url($url);
        }
        $this->selectedreader = $reader;

        // Use site course id, if course is empty.
        if (!empty($course) && is_int($course)) {
            $course = get_course($course);
        }
        $this->course = $course;
        $this->since = $since;
        $this->page = $page;
        $this->perpage = $perpage;
        $this->url = $url;
        $this->manager = new \report_loglive\manager();
    }

    /**
     * Return list of log table fields as cols => header
     *
     * @return array list of log fields to display.
     */
    public function get_table_fields() {

        // Prepend coursename if showing all courses.
        $cols = array();
        if (empty($this->course)) {
            $cols = array(
                'course' => get_string('course')
            );
        }
        $cols = $cols + array(
                'time' => get_string('time'),
                'fullnameuser' => get_string('fullnameuser'),
                'relatedfullnameuser' => get_string('eventrelatedfullnameuser', 'report_loglive'),
                'context' => get_string('eventcontext', 'report_loglive'),
                'component' => get_string('eventcomponent', 'report_loglive'),
                'eventname' => get_string('eventname'),
                'description' => get_string('description'),
                'origin' => get_string('eventorigin', 'report_loglive'),
                'ip' => get_string('ip_address')
            );
        return $cols;
    }

    /**
     * Return log data totalcount and logs.
     *
     * @return array log data to display.
     */
    public function get_logs() {
        global $OUTPUT;

        // Build log report and process it.
        $logevents = $this->manager->build_logs($this->manager->get_reader_object($this->selectedreader), $this->course,
            $this->since, $this->page * $this->perpage, $this->perpage);

        // Fetch userfullname and course shortname to be shown in report.
        $eventextradata = $this->manager->get_users_and_courses_used($logevents['events']);

        // Get log data from event and update list.
        foreach ($logevents['events'] as $key => $event) {
            // If user can't view this event then remove it from list. @Todo MDL-41266
            // if (!$event->can_view()) {
            //    unset($logevents['events'][$key]);
            //    continue;
            // }

            if ($event->contextid) {
                $context = context::instance_by_id($event->contextid, IGNORE_MISSING);
            } else {
                $context = false;
            }

            // Get extra event data for origin and realuserid.
            $logextra = $event->get_logextra();

            // Create log row data.
            $row = array();
            // Add course shortname if all courses are displayed.
            if (empty($this->course)) {
                if ((empty($event->courseid))) {
                    $row[] = get_string('site');
                } else {
                    $row[] = $eventextradata['courseshortname'][$event->courseid];
                }
            }

            // Add time stamp.
            $recenttimestr = get_string('strftimerecent', 'core_langconfig');
            $row[] = userdate($event->timecreated, $recenttimestr);

            // Add username who did the action.
            if (!empty($logextra['realuserid'])) {
                $a = new stdClass();
                $a->realusername = html_writer::link(new moodle_url("/user/view.php?id={$event->userid}&course={$event->courseid}"),
                    $eventextradata['userfullname'][$logextra['realuserid']]);
                $a->asusername = html_writer::link(new moodle_url("/user/view.php?id={$event->userid}&course={$event->courseid}"),
                    $eventextradata['userfullname'][$event->userid]);
                $username = get_string('loggedas', 'core', $a);
            } else {
                $username = $eventextradata['userfullname'][$event->userid];
                $params = array('id' => $event->userid);
                if ($event->courseid) {
                    $params['course'] = $event->courseid;
                }
                $username = html_writer::link(new moodle_url("/user/view.php", $params), $username);
            }
            $row[] = $username;

            // Add affected user.
            if (!empty($event->relateduserid)) {
                $row[] = html_writer::link(new moodle_url("/user/view.php?id=" . $event->relateduserid . "&course=" .
                        $event->courseid), $eventextradata['userfullname'][$event->relateduserid]);
            } else {
                $row[] = '-';
            }

            // Add context name.
            $contextname = get_string('other');
            if ($context) {
                $contextname = $context->get_context_name(true);
                if ($url = $context->get_url()) {
                    $contextname = $OUTPUT->action_link($url, $contextname , new popup_action('click', $url, 'contextname'),
                        array('height' => 440, 'width' => 700));
                }
            }
            $row[] = $contextname;

            // Component.
            $componentname = $event->component;
            if (($event->component === 'core') || ($event->component === 'legacy')) {
                $row[] = get_string('coresystem');
            } else if (get_string_manager()->string_exists('pluginname', $event->component)) {
                $row[] = get_string('pluginname', $event->component);
            } else {
                $row[] = $componentname;
            }

            // Event name.
            if ($event instanceof \logstore_legacy\event\legacy_logged) {
                $eventname = $event->eventname;
            } else {
                $eventname = $event->get_name();
            }
            if ($url = $event->get_url()) {
                $eventname = $OUTPUT->action_link($url, $eventname , new popup_action('click', $url, 'action'),
                    array('height' => 440, 'width' => 700));
            }
            $row[] = $eventname;

            // Description.
            $row[] = $event->get_description();

            // Add event origin, normally IP/cron.
            $row[] = $logextra['origin'];
            $link = new moodle_url("/iplookup/index.php?ip={$logextra['ip']}&user=$event->userid");
            $row[] = $OUTPUT->action_link($link, $logextra['ip'], new popup_action('click', $link, 'iplookup',
                array('height' => 440, 'width' => 700)));
            // Replace event data with log data to show.
            $logevents['events'][$key] = $row;
        }

        return $logevents;
    }
}