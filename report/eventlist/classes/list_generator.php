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
 * Event documentation
 *
 * @package   report_eventlist
 * @copyright 2014 Adrian Greeve <adrian@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_eventlist;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for returning system event information.
 *
 * @package   report_eventlist
 * @copyright 2014 Adrian Greeve <adrian@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class list_generator {

    /**
     * Init method.
     */
    public function __construct() {

    }

    /**
     * Convenience method. Returns all of the core events either with or without details.
     *
     * @param bool $detail True will return details, but no abstract classes, False will return all events, but no details.
     * @return array All events.
     */
    public static function get_all_events_list($detail = true) {
        return array_merge(self::get_core_events_list($detail), self::get_non_core_event_list($detail));
    }

    /**
     * Return all of the core event files.
     *
     * @param bool $detail True will return details, but no abstract classes, False will return all events, but no details.
     * @return array Core events.
     */
    public static function get_core_events_list($detail = true) {
        global $CFG;

        // Disable developer debugging as deprecated events will fire warnings.
        // Setup backup variables to restore the following settings back to what they were when we are finished.
        $debuglevel = $CFG->debug;
        $debugdisplay = $CFG->debugdisplay;
        $debugdeveloper = $CFG->debugdeveloper;
        $CFG->debug          = 0;
        $CFG->debugdisplay   = false;
        $CFG->debugdeveloper = false;

        $eventinformation = array();
        $directory = $CFG->libdir . '/classes/event';
        $files = self::get_file_list($directory);
        foreach ($files as $file => $location) {
            $functionname = '\\core\\event\\' . $file;
            // Check to see if this is actually a valid event.
            if (method_exists($functionname, 'get_static_info')) {
                if ($detail) {
                    $ref = new \ReflectionClass($functionname);
                    if (!$ref->isAbstract() && $file != 'manager') {
                        $eventinformation = self::format_data($eventinformation, $functionname);
                    }
                } else {
                    $eventinformation[$functionname] = $file;
                }
            }
        }
        // Now enable developer debugging as event information has been retrieved.
        $CFG->debug          = $debuglevel;
        $CFG->debugdisplay   = $debugdisplay;
        $CFG->debugdeveloper = $debugdeveloper;
        return $eventinformation;
    }

    /**
     * Returns the appropriate string for the CRUD character.
     *
     * @param string $crudcharacter The CRUD character.
     * @return string get_string for the specific CRUD character.
     */
    public static function get_crud_string($crudcharacter) {
        switch ($crudcharacter) {
            case 'c':
                return get_string('create', 'report_eventlist');
                break;

            case 'u':
                return get_string('update', 'report_eventlist');
                break;

            case 'd':
                return get_string('delete', 'report_eventlist');
                break;

            case 'r':
            default:
                return get_string('read', 'report_eventlist');
                break;
        }
    }

    /**
     * Returns the appropriate string for the event education level.
     *
     * @param int $edulevel Takes either the edulevel constant or string.
     * @return string get_string for the specific education level.
     */
    public static function get_edulevel_string($edulevel) {
        switch ($edulevel) {
            case \core\event\base::LEVEL_PARTICIPATING:
                return get_string('participating', 'report_eventlist');
                break;

            case \core\event\base::LEVEL_TEACHING:
                return get_string('teaching', 'report_eventlist');
                break;

            case \core\event\base::LEVEL_OTHER:
            default:
                return get_string('other', 'report_eventlist');
                break;
        }
    }

    /**
     * Returns a list of files (events) with a full directory path for events in a specified directory.
     *
     * @param string $directory location of files.
     * @return array full location of files from the specified directory.
     */
    private static function get_file_list($directory) {
        global $CFG;
        $directoryroot = $CFG->dirroot;
        $finaleventfiles = array();
        if (is_dir($directory)) {
            if ($handle = opendir($directory)) {
                $eventfiles = scandir($directory);
                foreach ($eventfiles as $file) {
                    if ($file != '.' && $file != '..') {
                        // Ignore the file if it is external to the system.
                        if (strrpos($directory, $directoryroot) !== false) {
                            $location = substr($directory, strlen($directoryroot));
                            $eventname = substr($file, 0, -4);
                            $finaleventfiles[$eventname] = $location  . '/' . $file;
                        }
                    }
                }
            }
        }
        return $finaleventfiles;
    }

    /**
     * This function returns an array of all events for the plugins of the system.
     *
     * @param bool $detail True will return details, but no abstract classes, False will return all events, but no details.
     * @return array A list of events from all plug-ins.
     */
    public static function get_non_core_event_list($detail = true) {
        global $CFG;
        // Disable developer debugging as deprecated events will fire warnings.
        // Setup backup variables to restore the following settings back to what they were when we are finished.
        $debuglevel = $CFG->debug;
        $debugdisplay = $CFG->debugdisplay;
        $debugdeveloper = $CFG->debugdeveloper;
        $CFG->debug          = 0;
        $CFG->debugdisplay   = false;
        $CFG->debugdeveloper = false;

        $noncorepluginlist = array();
        $plugintypes = \core_component::get_plugin_types();
        foreach ($plugintypes as $plugintype => $notused) {
            $pluginlist = \core_component::get_plugin_list($plugintype);
            foreach ($pluginlist as $plugin => $directory) {
                $plugindirectory = $directory . '/classes/event';
                // print_object($plugindirectory);
                foreach (self::get_file_list($plugindirectory) as $eventname => $notused) {
                    $plugineventname = '\\' . $plugintype . '_' . $plugin . '\\event\\' . $eventname;
                    // Check that this is actually an event.
                    if (method_exists($plugineventname, 'get_static_info')) {
                        if ($detail) {
                            $ref = new \ReflectionClass($plugineventname);
                            if (!$ref->isAbstract() && $plugin != 'legacy') {
                                $noncorepluginlist = self::format_data($noncorepluginlist, $plugineventname);
                            }
                        } else {
                            $noncorepluginlist[$plugineventname] = $eventname;
                        }
                    }
                }
            }
        }
        // Now enable developer debugging as event information has been retrieved.
        $CFG->debug          = $debuglevel;
        $CFG->debugdisplay   = $debugdisplay;
        $CFG->debugdeveloper = $debugdeveloper;

        return $noncorepluginlist;
    }

    /**
     * Get the full list of observers for the system.
     *
     * @return array An array of observers in the system.
     */
    public static function get_observer_list() {
        return \core\event\manager::get_all_observers();
    }

    /**
     * Returns the event data list section with url links and other formatting.
     *
     * @param array $eventdata The event data list section.
     * @param string $eventfullpath Full directory path to the events for this plugin / subplugin.
     * @return array The event data list section with additional formatting.
     */
    private static function format_data($eventdata, $eventfullpath) {
        $eventdata[$eventfullpath] = $eventfullpath::get_static_info();
        $eventdata[$eventfullpath]['fulleventname'] = \html_writer::span($eventfullpath::get_name());
        $eventdata[$eventfullpath]['fulleventname'] .= \html_writer::empty_tag('br');
        $eventdata[$eventfullpath]['fulleventname'] .= \html_writer::span($eventdata[$eventfullpath]['eventname'],
                'path-admin-tool-eventlist-name-path');
        $eventdata[$eventfullpath]['crud'] = self::get_crud_string($eventdata[$eventfullpath]['crud']);
        $eventdata[$eventfullpath]['edulevel'] = self::get_edulevel_string($eventdata[$eventfullpath]['edulevel']);
        $url = new \moodle_url('eventdetail.php', array('eventname' => $eventfullpath));
        $eventdata[$eventfullpath]['devdetail'] = \html_writer::link($url, get_string('details', 'report_eventlist'));
        unset($eventdata[$eventfullpath]['target']);
        return $eventdata;
    }
}
