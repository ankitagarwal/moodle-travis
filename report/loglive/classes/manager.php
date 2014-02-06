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
 * Log live report manager.
 *
 * @package    report_loglive
 * @copyright  2014 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_loglive;
require_once($CFG->dirroot.'/lib/tablelib.php');

/**
 * Log live report manager.
 * //TODO add docs
 * @package    report_loglive
 * @copyright  2014 Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class manager {

    /** @var int number of entries to show per page */
    public $perpage = 100;

    /** @var int number of seconds to show logs from, by default. */
    public $cutoff = 3600;

    /** @var \core\log\manager log manager */
    protected $logmanager;

    /** @var  int refresh rate in seconds */
    public $refresh;

    /**
     * Construct.
     */
    public function __construct() {
        if (defined('BEHAT_SITE_RUNNING')) {
            // Hack for behat tests.
            $this->refresh = 5;
        } else {
            if (defined('REPORT_LOGLIVE_REFRESH')) {
                // Backward compatibility.
                $this->refresh = REPORT_LOGLIVE_REFERESH;
            } else {
                // Default.
                $this->refresh = 60;
            }
        }
    }

    /**
     * An api to read logging stores and get events and return in an array
     *
     * @param string $reader Name of the logging store to use.
     * @param int $courseid Course id, 0 for all.
     * @param bool|int $since Timestamp to indicate the start point of logs.
     * @param int $page Page offset.
     *
     * @return \renderable return a renderable object.
     */
    public function get_renderable($reader, $courseid = 0, $since = false, $page = 0) {
        global $PAGE;

        if (empty($since) || !is_numeric($since)) {
            $since = time() - $this->cutoff;
        }
        $renderable = new \report_loglive_renderable($reader, $courseid, $PAGE->url,
                $since, $page, $this->perpage, 'timecreated DESC');
        return $renderable;
    }

    /**
     * An api to read logging stores and print logs
     *
     * @param string $reader Name of the logging store to use.
     * @param int $courseid Course id, 0 for all.
     * @param int $page Page offset.
     *
     * @return array list of logs.
     */
    public function print_livelogs($reader, $courseid = 0, $page = 0) {
        global $PAGE;
        $since = time() - $this->cutoff;
        $renderable = $this->get_renderable($reader, $courseid, $since, $page);
        $output = $PAGE->get_renderer('report_loglive');
        $output->render($renderable);
    }

    /**
     * An api to read logging stores and get events and return in json format
     *
     * @param string $reader Name of the logging store to use.
     * @param int $courseid Course id, 0 for all.
     * @param bool|int $since Timestamp to indicate the start point of logs.
     * @param int $page Page offset.
     *
     * @return string json encoded logs
     */
    public function get_livelogs_json($reader, $courseid, $since = false, $page = 0) {
        global $PAGE;
        $renderable = $this->get_renderable($reader, $courseid, $since, $page);
        $output = $PAGE->get_renderer('report_loglive');
        $result = $output->render($renderable);
        $return = new \stdClass();
        $return->logs = $result['logs'];
        $return->until = $result['until'];
        return json_encode($return);
    }

    /**
     * Build log data for log report.
     *
     * @param \core\log\reader $reader reader object from which logs will be fetched.
     * @param int $courseid (optional) course id.
     * @param int $since date (optional) timestamp from which records will be fetched.
     * @param int $limitfrom (optional) return a subset of records, starting at this point.
     * @param int $limitnum (optional) return a subset comprising this many records in total (required if $limitfrom is set).
     * @param string $order (optional) sortorder of fetched records
     * @return array.
     */
    public function build_logs(\core\log\reader $reader, $courseid = 0, $since = 0, $limitfrom = 0, $limitnum = 100,
                               $order = "timecreated DESC") {
        $joins = array();
        $params = array();

        if (!empty($courseid)) {
            $joins[] = "courseid = :courseid";
            $params['courseid'] = $courseid;
        }

        if ($since) {
            $joins[] = "timecreated >= :since";
            $params['since'] = $since;
        }

        $selector = implode(' AND ', $joins);
        $result = array();
        $result['events'] = $reader->get_events_select($selector, $params, $order, $limitfrom, $limitnum);
        $result['totalcount'] = $reader->get_events_select_count($selector, $params);
        return $result;
    }

    /**
     * Helper function to returns list of course shortname and user fullname used in events list.
     *
     * @param \core\event\base $events list of events.
     * @return array list of user fullname and course shortnames.
     */
    public function get_users_and_courses_used($events) {
        global $SITE;
        $result = array();
        $result['userfullname'] = array();
        $result['courseshortname'] = array();
        // For each event cache full username and course. //TODO use request muc.
        foreach ($events as $event) {
            $logextra = $event->get_logextra();
            if (!isset($result['userfullname'][$event->userid])) {
                $result['userfullname'][$event->userid] = fullname(get_complete_user_data('id', $event->userid));
            }
            if (!empty($logextra['realuserid']) && !isset($result['userfullname'][$logextra['realuserid']])) {
                $result['userfullname'][$logextra['realuserid']] = fullname(get_complete_user_data('id', $logextra['realuserid']));
            }
            if (!empty($event->relateduserid) && !isset($result['userfullname'][$event->relateduserid])) {
                $result['userfullname'][$event->relateduserid] = fullname(get_complete_user_data('id', $event->relateduserid));
            }

            if (!isset($result['courseshortname'][$event->courseid])) {
                if ($event->courseid == $SITE->id) {
                    $result['courseshortname'][$event->courseid] = format_string($SITE->shortname);
                } else if (!empty($event->courseid)) {
                    $url = new \moodle_url("/course/view.php", array('id' => $event->courseid));
                    $course = get_course($event->courseid, false);
                    $result['courseshortname'][$event->courseid] = \html_writer::link($url, format_string($course->shortname));
                }
            }
        }
        return $result;
    }

    /**
     * Get a list of enabled reader objects
     *
     * @param bool $nameonly if true only reader names will be returned.
     * @return array list of reader objects or names is returned as an array.
     */
    public function get_readers($nameonly = false) {
        if (!isset($this->manager)) {
            $this->logmanager = get_log_manager('\core\log\sql_select_reader');
        }

        $readers = $this->logmanager->get_readers();
        if ($nameonly) {
            foreach ($readers as $pluginname => $reader) {
                $readers[$pluginname] = $reader->get_name();
            }
        }
        return $readers;
    }

    /**
     * Print reader selector or return as html.
     */
    public function print_readers_select() {
        global $PAGE, $OUTPUT;
        $reader = $this->get_selected_reader();
        $readers = $this->get_readers(true);
        if (empty($readers)) {
            echo get_string('noreaderenabled', 'report_loglive');
            return;
        }
        $url = new \moodle_url($PAGE->url);
        $url->remove_params('reader');
        $select = new \single_select($url, 'reader', $readers, $reader, null);
        $select->set_label(get_string('selectreader', 'report_loglive'));
        echo $OUTPUT->render($select);
    }

    /**
     * Returns the name of currently selected reader.
     *
     * @param bool $returndefault Return
     *
     * @return bool|mixed Name of currently selected reader, default if $returndefault set to true, false otherwise.
     */
    public function get_selected_reader($returndefault = true) {
        $readers = $this->get_readers();
        $reader = optional_param('reader', '', PARAM_COMPONENT);
        if (!empty($readers[$reader])) {
            return $reader;
        }
        if ($returndefault && !empty($readers)) {
            return key($readers);
        }
        return false;
    }

    /**
     * Get a reader object corresponding to the passed reader name.
     *
     * @param string $reader reader name.
     *
     * @return bool|\core\log\reader reader object if found, false otherwise.
     */
    public function get_reader_object($reader) {
        $readers = $this->get_readers();
        if (!empty($readers[$reader])) {
            return $readers[$reader];
        }
        return false;
    }
}

