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
 * Observer class containing methods monitoring various events.
 *
 * @package    tool_monitor
 * @copyright  2014 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_monitor;

defined('MOODLE_INTERNAL') || die();

/**
 * Observer class containing methods monitoring various events.
 *
 * @since      Moodle 2.8
 * @package    tool_monitor
 * @copyright  2014 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eventobservers {

    /** @var array $buffer buffer of events. */
    protected $buffer = array();

    /** @var int Number of entries in the buffer. */
    protected $count = 0;

    /** @var  eventobservers a reference to a self instance. */
    protected static $instance;

    /**
     * Course delete event observer.
     * This observer monitors course delete event, and when a course is deleted it deletes any rules and subscriptions associated
     * with it, so no orphan data is left behind.
     *
     * @param \core\event\course_deleted $event The course deleted event.
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        $rules = \tool_monitor\rule_manager::get_rules_by_courseid($event->courseid);
        foreach ($rules as $rule) {
            \tool_monitor\rule_manager::delete_rule($rule->id);
        }
    }

    public static function process_event(\core\event\base $event) {

        if (empty(self::$instance)) {
            self::$instance = new static();
            // Register shutdown handler - this is useful for buffering, processing events, etc.
            \core_shutdown_manager::register_function(array(self::$instance, 'process_buffer'));
        }

        self::$instance->buffer_event($event);
    }

    /**
     * Api to buffer events to store, to reduce db queries.
     *
     * @param \core\event\base $event
     */
    protected function buffer_event(\core\event\base $event) {

        $eventdata = $event->get_data();
        $eventobj = new \stdClass();
        $eventobj->eventname = $eventdata['eventname'];
        $eventobj->contextid = $eventdata['contextid'];
        $eventobj->contextlevel = $eventdata['contextlevel'];
        $eventobj->contextinstanceid = $eventdata['contextinstanceid'];
        $eventobj->link = $event->get_url()->out();
        $eventobj->courseid = $eventdata['courseid'];
        $eventobj->timecreated = $eventdata['timecreated'];

        $this->buffer[] = $eventobj;
        $this->count++;
    }

    /**
     * This method process all events stored in the buffer.
     *
     * This is a multi purpose api. It does the following:-
     * 1. Write event data to tool_monitor_events
     * 2. Find out users that need to be notified about rule completion and schedule a task to send them messages.
     */
    public function process_buffer() {
        global $DB;

        $DB->insert_records('tool_monitor_events', $this->buffer); // Insert the whole chunk into the database.

        $select = "SELECT COUNT(id) FROM {tool_monitor_events} ";
        $now = time();
        $messagestosend = array();

        // Let us now process the events and check for subscriptions.
        foreach ($this->buffer as $eventobj) {
            $subscriptions = subscription_manager::get_subscriptions_by_event($eventobj);
            foreach ($subscriptions as $sub) {
                $subscription = new subscription($sub);
                $starttime = $now - $subscription->timewindow;
                if ($subscription->courseid == 0) {
                    // Site level subscription.
                    $where = "eventname = :eventname AND contextlevel = :contextlevel AND timecreated > :starttime";
                    $params = array('eventname' => $eventobj->eventname, 'contextlevel' => CONTEXT_SYSTEM,
                            'starttime' => $starttime);
                } else {
                    // Course level subscription.
                    if ($subscription->cmid == 0) {
                        // All modules.
                        $where = "eventname = :eventname AND courseid = :courseid AND timecreated > :starttime";
                        $params = array('eventname' => $eventobj->eventname, 'courseid' => $eventobj->courseid,
                                'starttime' => $starttime);
                    } else {
                        // Specific module.
                        $where = "eventname = :eventname AND courseid = :courseid AND contextinstanceid = :cmid
                                AND timecreated > :starttime";
                        $params = array('eventname' => $eventobj->eventname, 'courseid' => $eventobj->courseid,
                                'cmid' => $eventobj->contextinstanceid, 'starttime' => $starttime);
                    }
                }
                $sql = $select . "WHERE " . $where;
                $count = $DB->count_records_sql($sql, $params);
                if (!empty($count) && $count > $subscription->frequency) {
                    $messagestosend[] = $this->generate_message($subscription, $eventobj);
                }
            }
        }

        // Schedule a task to send notification.
        if (!empty($messagestosend)) {
            $adhocktask = new notification_task();
            $adhocktask->set_custom_data($messagestosend);
            \core\task\manager::queue_adhoc_task($adhocktask);
        }

        // Clean up.
        $this->buffer = array();
        $this->count = 0;
    }

    /**
     * Generates the message object for a give subscription and event.
     *
     * @param subscription $subscription Subscription instance
     * @param \stdClass $eventobj Event data
     *
     * @return \stdClass message object
     */
    protected function generate_message(subscription $subscription, $eventobj) {

        $user = \core_user::get_user($subscription->userid);
        $context = \context_user::instance($user->id);
        $template = $subscription->template;
        $template = $this->replace_placeholders($template, $subscription, $eventobj, $context);
        $msgdata = new \stdClass();
        $msgdata->component         = 'tool_monitor'; // Your component name.
        $msgdata->name              = 'notification'; // This is the message name from messages.php.
        $msgdata->userfrom          = \core_user::get_noreply_user();
        $msgdata->userto            = $user;
        $msgdata->subject           = $subscription->get_name($context);
        $msgdata->fullmessage       = format_text($template, $subscription->templateformat, array('context' => $context));
        $msgdata->fullmessageformat = FORMAT_HTML;
        $msgdata->fullmessagehtml   = format_text($template, $subscription->templateformat, array('context' => $context));
        $msgdata->smallmessage      = '';
        $msgdata->notification      = 1; // This is only set to 0 for personal messages between users.

        return $msgdata;
    }

    /**
     * Replace place holders in the template with respective content.
     *
     * @param string $template Message template.
     * @param subscription $subscription subscription instance
     * @param \stdclass $eventobj Event data
     * @param \context $context context object
     *
     * @return mixed final template string.
     */
    protected function replace_placeholders($template, subscription $subscription, $eventobj, $context) {
        $template = str_replace('{link}', $eventobj->link, $template);
        if ($eventobj->contextlevel = CONTEXT_MODULE && !empty($eventobj->contextinstanceid)
                && (strpos($template, '{modulelink}' !== false))) {
            $cm = get_fast_modinfo($eventobj->courseid)->get_cm($eventobj->contextinstanceid);
            $modulelink = $cm->url;
            $template = str_replace('{modulelink}', $modulelink, $template);
        }
        $template = str_replace('{rulename}', $subscription->get_name($context), $template);
        $template = str_replace('{description}', $subscription->get_description($context), $template);
        $template = str_replace('{eventname}', $subscription->get_name($context), $template);

        return $template;
    }
}
