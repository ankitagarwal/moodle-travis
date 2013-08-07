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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
* Event observers for mod_workshop.
*
* @package mod_workshop
* @copyright 2013 Adrian Greeve <adrian@moodle.com>
* @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();

/**
* Event observer for mod_workshop.
*/
class mod_workshop_observer {

    /**
     * Triggered when the '\mod_workshop\event\workshop_viewed' event is triggered.
     *
     * This does the same job as {@link workshopallocation_scheduled_cron()} but for the
     * single workshop. The idea is that we do not need to wait for cron to execute.
     * Displaying the workshop main view.php can trigger the scheduled allocation, too.
     *
     * @param \mod_workshop\event\workshop_viewed $event
     * @return bool
     */
    public static function workshopallocation_scheduled_workshop_viewed($event) {
        global $DB;

        $workshop = $event->get_record_snapshot('workshop', $event->objectid);
        $course   = $event->get_record_snapshot('course', $event->courseid);
        $cm       = get_coursemodule_from_instance('workshop', $workshop->id, $event->context->instanceid, false, MUST_EXIST);

        $workshop = new workshop($workshop, $cm, $course);
        $now = time();

        // Non-expensive check to see if the scheduled allocation can even happen.
        if ($workshop->phase == workshop::PHASE_SUBMISSION and $workshop->submissionend > 0 and $workshop->submissionend < $now) {

            // Make sure the scheduled allocation has been configured for this workshop, that it has not
            // been executed yet and that the passed workshop record is still valid.
            $sql = "SELECT a.id
                      FROM {workshopallocation_scheduled} a
                      JOIN {workshop} w ON a.workshopid = w.id
                     WHERE w.id = :workshopid
                           AND a.enabled = 1
                           AND w.phase = :phase
                           AND w.submissionend > 0
                           AND w.submissionend < :now
                           AND (a.timeallocated IS NULL OR a.timeallocated < w.submissionend)";
            $params = array('workshopid' => $workshop->id, 'phase' => workshop::PHASE_SUBMISSION, 'now' => $now);

            if ($DB->record_exists_sql($sql, $params)) {
                // Allocate submissions for assessments.
                $allocator = $workshop->allocator_instance('scheduled');
                $result = $allocator->execute();
                // todo inform the teachers about the results
            }
        }

        return true;
    }
}