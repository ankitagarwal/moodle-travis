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

namespace mod_workshop\event;
/**
 * Event for when a workshop activity is viewed.
 *
 * @package    core
 * @copyright  2013 Adrian Greeve
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class workshop_viewed extends \core\event\course_module_viewed {

    /** A reference to the workshop object */
    protected $workshop;
    /** A reference to the user object */
    protected $user;

    /**
     * Set method for returning in the legacy event and log methods.
     *
     * @param object $workshopobject Workshop object.
     * @param object $userobject $USER object.
     */
    public function set_legacy_event_objects($workshopobject, $userobject) {
        $this->workshop = $workshopobject;
        $this->user = $userobject;
    }

    protected function init() {
        parent::init();
        $this->data['objecttable'] = 'workshop';
    }

    /**
     * Returns localised description of what happened.
     *
     * @return string|\lang_string
     */
    public function get_description() {
        return 'A workshop with the id of ' . $this->objectid . ' was viewed by a user with the id of ' . $this->userid;
    }

    /**
     * Returns localised general event name.
     *
     * @return string|\lang_string
     */
    public static function get_name() {
        return get_string('workshopviewed', 'mod_workshop');
    }

    /**
     * Does this event replace a legacy event?
     *
     * @return null|string legacy event name
     */
    protected function get_legacy_eventname() {
        return 'workshop_viewed';
    }

    /**
     * Returns relevant URL.
     * @return \moodle_url
     */
    public function get_url() {
        $url = "/mod/workshop/view.php";
        return new \moodle_url($url, array('id'=>$this->objectid));
    }

    /**
     * Legacy event data if get_legacy_eventname() is not empty.
     *
     * @return mixed
     */
    protected function get_legacy_eventdata() {
        return (object)array('workshop' => $this->workshop, 'user' => $this->user);
    }

    /**
     * replace add_to_log() statement.
     *
     * @return array of parameters to be passed to legacy add_to_log() function.
     */
    protected function get_legacy_logdata() {
        $url = new \moodle_url('/mod/workshop/view.php', array('id' => $this->workshop->cm->id));
        $baseurl = new \moodle_url('/mod/workshop/');
        $baseurl = $baseurl->out();
        $logurl = substr($url->out(), strlen($baseurl));
        return array($this->workshop->course->id, 'workshop', 'view', $logurl, $this->workshop->id, $this->workshop->cm->id);
    }
}