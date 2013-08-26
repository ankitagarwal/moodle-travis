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
 * Unit tests for the lib/upgradelib.php library.
 *
 * @package   core
 * @category  phpunit
 * @copyright 2013 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/upgradelib.php');


/**
 * Tests various classes and functions in upgradelib.php library.
 */
class core_upgradelib_testcase extends advanced_testcase {

    /**
     * Test the {@link upgrade_stale_php_files_present() function
     */
    public function test_upgrade_stale_php_files_present() {
        // Just call the function, must return bool false always
        // if there aren't any old files in the codebase.
        $this->assertFalse(upgrade_stale_php_files_present());
    }

    public function test_upgrade_course_section_sequence() {

        global $DB;

        $this->resetAfterTest();
        $gen = $this->getDataGenerator();

        // Crete lots of courses.
        $courses = array();
        for ($i = 0; $i < 10; $i++) {
            $courses[] = $gen->create_course();
        }

        // Create lots of course sections.
        $sections = array();
        foreach ($courses as $course) {
            for ($i = 0; $i < 10; $i++) {
                $record = new stdClass();
                $record->course = $course->id;
                $record->section = $i;
                $sec = $gen->create_course_section($record);
                $sections[$course->id][$sec->section] = $sec;
            }
        }

        // Create lots of modules.
        $modules = array();
        foreach ($courses as $course) {
            foreach ($sections[$course->id] as $section) {
                $record = new stdClass();
                $record->course = $course->id;
                $options = array('section' => $section->section);
                $modules[$course->id][$section->id] = array($gen->create_module('assign', $record, $options),
                                                            $gen->create_module('forum', $record, $options));
            }
        }

        // Store correct section data for future use.
        $correctsections = $DB->get_records('course_sections');

        // Let us break some of the sequences.
        $brokensections = array();
        $brokencount = 0;

        // Incorrect ids at the end of sequence.
        for ($i = 5; $i < 10; $i++, $brokencount++) {
            $id = $sections[$i][2]->id;
            $seq = $correctsections[$id]->sequence;
            $seq .= ',25452, 32569';
            $DB->set_field('course_sections', 'sequence', $seq, array('id' => $id));
            $brokensections[] = $id;
        }

        // Incorrect ids in middle of the sequence.
        for ($i = 5; $i < 10; $i++, $brokencount++) {
            $id = $sections[$i][3]->id;
            $seq = $correctsections[$id]->sequence;
            $seq = explode(',', $seq);
            $seq = array($seq[0], 25452, 32569, $seq[1]);
            $seq = implode(',', $seq);
            $DB->set_field('course_sections', 'sequence', $seq, array('id' => $id));
            $brokensections[] = $id;
        }

        // Repeated ids in  sequences.
        for ($i = 3; $i < 8; $i++, $brokencount++) {
            $id = $sections[$i][4]->id;
            $seq = $correctsections[$id]->sequence;
            $seq = explode(',', $seq);
            $seq[] = $seq[0];
            $seq = implode(',', $seq);
            $DB->set_field('course_sections', 'sequence', $seq, array('id' => $id));
            $brokensections[] = $id;
        }

        // Missing ids in  sequences.
        for ($i = 3; $i < 8; $i++, $brokencount++) {
            $id = $sections[$i][5]->id;
            $seq = $correctsections[$id]->sequence;
            $seq = substr($seq, 0, strpos($seq, ','));
            $DB->set_field('course_sections', 'sequence', $seq, array('id' => $id));
            $brokensections[] = $id;
        }

        $sql = upgrade_get_corrupt_sequence_section_sql();
        $coursesections = $DB->get_records_sql($sql);

        // Make sure the sql finds all broken records.
        $this->assertSame($brokencount, count($coursesections));
        $coursesections = array_keys($coursesections);
        sort($coursesections);
        sort($brokensections);
        $this->assertSame($coursesections, $brokensections); // Order is not important.

        // Run the fix.
        upgrade_fix_course_section_sequence();

        // Make sure everything got fixed as before.
        $coursesections = $DB->get_records('course_sections');
        foreach ($coursesections as $section) {
            $this->assertSame($correctsections[$section->id]->sequence, $section->sequence);
        }
    }
}
